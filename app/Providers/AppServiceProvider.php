<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Setting;
use App\Observers\OrderObserver;
use App\Events\PushNotificationRequested;
use App\Listeners\SendPushNotificationListener;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();
        Order::observe(OrderObserver::class);

        Event::listen(PushNotificationRequested::class, SendPushNotificationListener::class);

        $this->syncMailConfigFromSettings();

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontend = config('app.frontend_url', 'http://localhost:3000');
            return "{$frontend}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
    }

    /**
     * Apply mail configuration from settings table so API and all mail use admin-configured SMTP.
     */
    protected function syncMailConfigFromSettings(): void
    {
        try {
            $host = Setting::get('mail_host');
        } catch (\Throwable $e) {
            return;
        }
        if ($host === null || $host === '') {
            return;
        }
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
