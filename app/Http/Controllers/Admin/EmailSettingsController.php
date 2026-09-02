<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Email Setting hub: SMTP credentials and which transactional emails are sent.
 */
class EmailSettingsController extends Controller
{
    protected const SMTP_KEYS = [
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    protected const EVENT_KEYS = [
        'email_verification_required',
        'email_welcome_enabled',
        'email_password_reset_enabled',
        'email_order_placed_enabled',
        'email_order_shipped_enabled',
    ];

    public function index(): View
    {
        $items = [
            [
                'label' => 'SMTP',
                'description' => 'Mail server host, credentials, encryption, and from address.',
                'route' => 'admin.email-settings.smtp',
            ],
            [
                'label' => 'Email Events',
                'description' => 'Turn registration verification and transactional emails on or off.',
                'route' => 'admin.email-settings.events',
            ],
            [
                'label' => 'Email Templates',
                'description' => 'Edit welcome, OTP, order, and payout email content and placeholders.',
                'route' => 'admin.email-templates.index',
            ],
        ];

        return view('admin.email-settings.index', compact('items'));
    }

    public function smtp(): View
    {
        $settings = [];
        foreach (self::SMTP_KEYS as $key) {
            $settings[$key] = Setting::get($key, config('settings_defaults.'.$key, ''));
        }

        return view('admin.email-settings.smtp', compact('settings'));
    }

    public function updateSmtp(Request $request): RedirectResponse
    {
        $request->validate([
            'mail_mailer' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|string|max:10',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:ssl,tls',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        foreach (self::SMTP_KEYS as $key) {
            if ($key === 'mail_password' && !$request->filled($key)) {
                continue;
            }
            $value = $request->input($key, '');
            if ($key === 'mail_encryption' && ($value === 'null' || $value === null)) {
                $value = '';
            }
            Setting::set($key, (string) ($value ?? ''));
        }

        Cache::forget('settings');
        $this->syncMailConfigToRuntime();

        ActivityLogger::log([
            'action_type' => 'settings_update',
            'target_table' => 'settings',
            'action_on' => null,
            'description' => 'Email SMTP settings updated',
        ], $request);

        return back()->with('success', 'SMTP settings saved.');
    }

    public function events(): View
    {
        $settings = [];
        foreach (self::EVENT_KEYS as $key) {
            $settings[$key] = Setting::get($key, config('settings_defaults.'.$key, '1'));
        }

        return view('admin.email-settings.events', compact('settings'));
    }

    public function updateEvents(Request $request): RedirectResponse
    {
        $request->validate([
            'email_verification_required' => 'required|in:0,1',
            'email_welcome_enabled' => 'required|in:0,1',
            'email_password_reset_enabled' => 'required|in:0,1',
            'email_order_placed_enabled' => 'required|in:0,1',
            'email_order_shipped_enabled' => 'required|in:0,1',
        ]);

        foreach (self::EVENT_KEYS as $key) {
            Setting::set($key, (string) $request->input($key, '0'));
        }

        Cache::forget('settings');

        ActivityLogger::log([
            'action_type' => 'settings_update',
            'target_table' => 'settings',
            'action_on' => null,
            'description' => 'Email event settings updated',
        ], $request);

        return back()->with('success', 'Email event settings saved.');
    }

    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Please enter an email address to send the test to.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $this->syncMailConfigToRuntime();

        $mailHost = Setting::get('mail_host');
        if (empty($mailHost)) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP host is not configured. Please save your SMTP settings (Host and Port) first, then try again.',
            ], 422);
        }

        try {
            $to = $request->input('email');
            Mail::raw(
                "This is a test email from ".config('app.name', 'Tijaar').".\n\nIf you received this, your SMTP configuration is working correctly.",
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('['.config('app.name', 'Tijaar').'] SMTP test – success');
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to '.$to.'. Check the inbox (and spam folder) to confirm delivery.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $this->parseSmtpExceptionMessage($e->getMessage()),
            ], 500);
        }
    }

    protected function parseSmtpExceptionMessage(string $raw): string
    {
        $lower = strtolower($raw);
        if (str_contains($lower, 'connection refused') || str_contains($lower, 'could not connect')) {
            return 'Could not connect to the SMTP server. Check that the Host and Port are correct, the server is reachable, and no firewall is blocking the connection.';
        }
        if (str_contains($lower, 'authentication') && (str_contains($lower, 'failed') || str_contains($lower, 'invalid'))) {
            return 'SMTP authentication failed. Check your Username and Password. Some providers require an app password instead of the account password.';
        }
        if (str_contains($lower, 'ssl') || str_contains($lower, 'tls') || str_contains($lower, 'certificate')) {
            return 'Secure connection (SSL/TLS) failed. Try changing Encryption to ssl (for port 465) or tls (for port 587), or use no encryption for port 25.';
        }
        if (str_contains($lower, 'timeout') || str_contains($lower, 'timed out')) {
            return 'Connection to the SMTP server timed out. Check Host, Port, and network/firewall.';
        }
        if (str_contains($lower, 'could not find host') || str_contains($lower, 'getaddrinfo')) {
            return 'SMTP host could not be resolved. Check that the Host value is correct (e.g. smtp.example.com).';
        }
        if (str_contains($lower, 'stream_socket_client')) {
            return 'Unable to connect to the SMTP server. Verify Host, Port, and Encryption (e.g. ssl for 465, tls for 587).';
        }

        return 'Email could not be sent: '.$raw;
    }

    protected function syncMailConfigToRuntime(): void
    {
        $mailKeys = [
            'mail_mailer' => 'mail.default',
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_encryption' => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];
        foreach ($mailKeys as $settingKey => $configKey) {
            $v = Setting::get($settingKey);
            if ($settingKey === 'mail_encryption' && ($v === null || $v === '')) {
                Config::set($configKey, null);
                continue;
            }
            if ($v !== null && $v !== '') {
                Config::set($configKey, $v);
            }
        }
    }
}
