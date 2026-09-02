<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Mail\WelcomeMail;
use App\Models\EmailTemplate;
use App\Notifications\Concerns\SkipsMailWithoutEmail;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use SkipsMailWithoutEmail;

    public function __construct(
        public string $name,
        public string $role = 'customer' // customer | seller
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable, ['mail']);
    }

    public function toMail(object $notifiable): WelcomeMail|TemplateMail
    {
        $appName = config('app.name', 'Tijaar');
        $frontend = config('app.frontend_url', 'http://localhost:3000');
        $dashboardUrl = $this->role === 'seller'
            ? rtrim($frontend, '/') . '/vendor/dashboard'
            : rtrim($frontend, '/');

        $template = EmailTemplate::getBySlug('welcome');
        if ($template) {
            $data = [
                'name' => $this->name,
                'app_name' => $appName,
                'dashboard_url' => $dashboardUrl,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;
            return new TemplateMail($subject, $bodyHtml, $bodyPlain);
        }

        return new WelcomeMail($this->name, $this->role, $dashboardUrl);
    }
}
