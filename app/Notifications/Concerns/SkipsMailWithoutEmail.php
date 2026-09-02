<?php

namespace App\Notifications\Concerns;

trait SkipsMailWithoutEmail
{
    /**
     * Only queue the mail channel when the notifiable has a valid email.
     * Prevents Symfony "An email must have a To, Cc, or Bcc header" in logs.
     *
     * @return list<string>
     */
    protected function channelsFor(object $notifiable, array $channels): array
    {
        $email = null;
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $email = $notifiable->routeNotificationFor('mail', $this);
        }
        if ($email === null && isset($notifiable->email)) {
            $email = $notifiable->email;
        }

        $valid = false;
        if (is_string($email)) {
            $valid = filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
        } elseif (is_array($email)) {
            foreach ($email as $addr) {
                if (is_string($addr) && filter_var(trim($addr), FILTER_VALIDATE_EMAIL)) {
                    $valid = true;
                    break;
                }
            }
        }

        if ($valid) {
            return $channels;
        }

        return array_values(array_filter($channels, fn ($c) => $c !== 'mail'));
    }
}
