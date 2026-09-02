<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecaptchaSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.recaptcha-settings.index', [
            'settings' => [
                'recaptcha_enabled' => Setting::get('recaptcha_enabled', '0'),
                'recaptcha_site_key' => Setting::get('recaptcha_site_key', config('settings_defaults.recaptcha_site_key', '')),
                'recaptcha_secret_key' => Setting::get('recaptcha_secret_key', config('settings_defaults.recaptcha_secret_key', '')),
                'recaptcha_on_login' => Setting::get('recaptcha_on_login', '1'),
                'recaptcha_on_register' => Setting::get('recaptcha_on_register', '1'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'recaptcha_enabled' => 'nullable|in:0,1',
            'recaptcha_site_key' => 'nullable|string|max:255',
            'recaptcha_secret_key' => 'nullable|string|max:255',
            'recaptcha_on_login' => 'nullable|in:0,1',
            'recaptcha_on_register' => 'nullable|in:0,1',
        ]);

        Setting::set('recaptcha_enabled', (string) $request->input('recaptcha_enabled', '0') === '1' ? '1' : '0');
        Setting::set('recaptcha_on_login', (string) $request->input('recaptcha_on_login', '0') === '1' ? '1' : '0');
        Setting::set('recaptcha_on_register', (string) $request->input('recaptcha_on_register', '0') === '1' ? '1' : '0');
        Setting::set('recaptcha_site_key', trim((string) $request->input('recaptcha_site_key', '')));

        $secret = trim((string) $request->input('recaptcha_secret_key', ''));
        // Blank secret keeps the existing value (same pattern as payment credentials).
        if ($secret !== '') {
            Setting::set('recaptcha_secret_key', $secret);
        }

        return back()->with('success', 'Google reCAPTCHA settings updated.');
    }
}
