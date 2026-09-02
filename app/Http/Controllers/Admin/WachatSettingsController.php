<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WachatSettingsController extends Controller
{
    private const API_TOGGLES = [
        'wachat_enabled',
        'notification_whatsapp_enabled',
    ];

    private const EVENT_TOGGLES = [
        'wachat_msg_order_placed_customer',
        'wachat_msg_order_placed_seller',
        'wachat_msg_payment_success',
        'wachat_msg_order_approved',
        'wachat_msg_order_shipped',
        'wachat_msg_order_delivered_seller',
    ];

    public function index(): View
    {
        $settings = [];
        foreach (self::API_TOGGLES as $key) {
            $default = $key === 'wachat_enabled' ? '0' : '1';
            $settings[$key] = Setting::get($key, config("settings_defaults.{$key}", $default));
        }
        $settings['wachat_api_key'] = Setting::get('wachat_api_key', config('settings_defaults.wachat_api_key', ''));
        $settings['wachat_sender'] = Setting::get('wachat_sender', config('settings_defaults.wachat_sender', ''));
        $settings['wachat_api_endpoint'] = Setting::get(
            'wachat_api_endpoint',
            config('settings_defaults.wachat_api_endpoint', 'https://custom2.waghl.com/send-message')
        );

        return view('admin.wachat-settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'wachat_enabled' => 'nullable',
            'notification_whatsapp_enabled' => 'nullable',
            'wachat_api_key' => 'nullable|string|max:255',
            'wachat_sender' => 'nullable|string|max:30',
            'wachat_api_endpoint' => 'nullable|string|max:500',
        ]);

        foreach (self::API_TOGGLES as $key) {
            Setting::set($key, $this->toggleOn($request, $key) ? '1' : '0');
        }

        $apiKey = trim((string) $request->input('wachat_api_key', ''));
        if ($apiKey !== '') {
            Setting::set('wachat_api_key', $apiKey);
        }

        Setting::set('wachat_sender', trim((string) $request->input('wachat_sender', '')));

        $endpoint = trim((string) $request->input('wachat_api_endpoint', ''));
        Setting::set(
            'wachat_api_endpoint',
            $endpoint !== '' ? $endpoint : 'https://custom2.waghl.com/send-message'
        );

        return back()->with('success', 'WaChat API settings updated.');
    }

    public function events(): View
    {
        $settings = [];
        foreach (self::EVENT_TOGGLES as $key) {
            $settings[$key] = Setting::get($key, config("settings_defaults.{$key}", '1'));
        }

        return view('admin.wachat-settings.events', compact('settings'));
    }

    public function updateEvents(Request $request): RedirectResponse
    {
        $request->validate([
            'wachat_msg_order_placed_customer' => 'nullable',
            'wachat_msg_order_placed_seller' => 'nullable',
            'wachat_msg_payment_success' => 'nullable',
            'wachat_msg_order_approved' => 'nullable',
            'wachat_msg_order_shipped' => 'nullable',
            'wachat_msg_order_delivered_seller' => 'nullable',
        ]);

        foreach (self::EVENT_TOGGLES as $key) {
            Setting::set($key, $this->toggleOn($request, $key) ? '1' : '0');
        }

        return back()->with('success', 'WaChat message events updated.');
    }

    /**
     * Send a one-off test WhatsApp message (admin diagnostics).
     */
    public function testSend(Request $request): RedirectResponse
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
            'test_message' => 'nullable|string|max:500',
        ]);

        $phone = trim((string) $request->input('test_phone'));
        $message = trim((string) $request->input('test_message', ''));
        if ($message === '') {
            $message = 'Tijaar WaChat test: WhatsApp events are configured correctly.';
        }

        $result = \App\Services\WachatService::send($phone, $message);

        if ($result['ok'] ?? false) {
            return back()->with('success', 'Test WhatsApp message sent to '.$phone.'.');
        }

        return back()->with('error', $result['message'] ?? 'Failed to send test WhatsApp message.');
    }

    /**
     * Checkbox + hidden "0" may arrive as string or [0,1] depending on client.
     */
    private function toggleOn(Request $request, string $key): bool
    {
        $val = $request->input($key, '0');
        if (is_array($val)) {
            $val = end($val);
        }

        return in_array((string) $val, ['1', 'true', 'on', 'yes'], true);
    }
}
