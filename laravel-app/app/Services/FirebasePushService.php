<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebasePushService
{
    public function send(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        bool $highPriority = true,
        string $channelId = 'tenantpro_default'
    ): bool
    {
        try {
            $credentials = $this->credentials();
            if (!$credentials || empty($credentials['project_id'])) {
                Log::warning('FCM push skipped: Firebase service-account credentials are not configured.');
                return false;
            }

            $response = Http::withToken($this->accessToken($credentials))
                ->timeout(12)
                ->post('https://fcm.googleapis.com/v1/projects/'.$credentials['project_id'].'/messages:send', [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => collect(array_merge($data, ['title' => $title, 'body' => $body]))
                            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) ($value ?? '')])
                            ->all(),
                        'android' => [
                            'priority' => $highPriority ? 'HIGH' : 'NORMAL',
                            'ttl' => $highPriority ? '600s' : '3600s',
                            'notification' => [
                                'channel_id' => $channelId,
                                'default_sound' => true,
                                'notification_priority' => $highPriority ? 'PRIORITY_HIGH' : 'PRIORITY_DEFAULT',
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) return true;

            Log::warning('FCM push failed.', ['status' => $response->status(), 'response' => $response->json()]);
        } catch (Throwable $exception) {
            Log::warning('FCM push failed.', ['message' => $exception->getMessage()]);
        }

        return false;
    }

    private function credentials(): ?array
    {
        $inline = config('services.firebase.credentials_json');
        if (is_string($inline) && trim($inline) !== '') {
            $decoded = json_decode($inline, true);
            if (is_array($decoded)) return $decoded;
        }

        $path = config('services.firebase.credentials');
        if (!$path) $path = base_path('../firebase-service-account.json');
        if (!is_file($path)) return null;

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function accessToken(array $credentials): string
    {
        $cacheKey = 'firebase_access_token_'.sha1((string) $credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsigned = $header.'.'.$claims;
            openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            return Http::asForm()->timeout(12)->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$this->base64Url($signature),
            ])->throw()->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
