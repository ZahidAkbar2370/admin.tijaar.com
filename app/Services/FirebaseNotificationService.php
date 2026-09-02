<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\NotificationPreference;
use App\Models\PushNotification;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected ?array $credentials = null;

    protected ?string $accessToken = null;

    protected ?int $accessTokenExpiry = null;

    public function sendToUser(int $userId, string $title, string $body = '', array $data = []): void
    {
        $type = (string) ($data['type'] ?? NotificationPreference::TYPE_ORDER);
        if (! in_array($type, NotificationPreference::TYPES, true)) {
            $type = NotificationPreference::TYPE_ORDER;
        }

        $tokens = FcmToken::where('user_id', $userId)->get();
        if ($tokens->isEmpty()) {
            return;
        }

        $tokens = $tokens->filter(function (FcmToken $fcmToken) use ($userId, $type) {
            $channel = NotificationPreference::channelForDeviceType((string) $fcmToken->device_type);

            return NotificationPreference::userAllows($userId, $channel, $type);
        });

        if ($tokens->isEmpty()) {
            return;
        }

        $notification = PushNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
        ]);

        $dataForFcm = array_map(fn ($v) => (string) $v, $data);

        foreach ($tokens as $fcmToken) {
            try {
                $this->sendToToken($fcmToken->fcm_token, $title, $body, $dataForFcm);
                $fcmToken->update(['last_used_at' => now()]);
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                if ($this->isInvalidTokenError($message)) {
                    $fcmToken->delete();
                    Log::channel('single')->info('FCM: removed invalid token for user ' . $userId, ['token_id' => $fcmToken->id]);
                } else {
                    Log::channel('single')->warning('FCM: send failed for user ' . $userId, ['error' => $message]);
                }
            }
        }
    }

    protected function sendToToken(string $token, string $title, string $body, array $data): void
    {
        $accessToken = $this->getAccessToken();
        $projectId = $this->getProjectId();
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];
        if (!empty($data)) {
            $payload['message']['data'] = $data;
        }

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$response->successful()) {
            $body = $response->json();
            $msg = $body['error']['message'] ?? $response->body();
            throw new \RuntimeException($msg);
        }
    }

    protected function isInvalidTokenError(string $message): bool
    {
        return str_contains($message, 'UNREGISTERED')
            || str_contains($message, 'NOT_FOUND')
            || str_contains($message, 'INVALID_ARGUMENT')
            || str_contains($message, 'invalid');
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken && $this->accessTokenExpiry && time() < $this->accessTokenExpiry - 60) {
            return $this->accessToken;
        }

        $cred = $this->getCredentials();
        $now = time();
        $payload = [
            'iss' => $cred['client_email'],
            'sub' => $cred['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $jwt = JWT::encode($payload, $cred['private_key'], 'RS256');

        $response = Http::asForm()->post($cred['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to get Firebase access token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        $this->accessTokenExpiry = $now + (int) $response->json('expires_in', 3600);
        return $this->accessToken;
    }

    protected function getProjectId(): string
    {
        $cred = $this->getCredentials();
        return config('firebase.project_id') ?: $cred['project_id'];
    }

    protected function getCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = config('firebase.credentials');
        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:#', $path)) {
            $fullPath = $path;
        } else {
            $fullPath = base_path($path);
        }

        if (!is_file($fullPath)) {
            throw new \RuntimeException('Firebase credentials file not found: ' . $fullPath);
        }

        $json = file_get_contents($fullPath);
        $this->credentials = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($this->credentials['private_key'])) {
            throw new \RuntimeException('Invalid Firebase credentials JSON');
        }

        return $this->credentials;
    }
}
