<?php

namespace App\Support;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push notifications to members' phones through Firebase Cloud Messaging (HTTP v1).
 *
 * It does nothing until FCM_CREDENTIALS in .env points at your Firebase
 * service-account JSON file (see PUSH_SETUP.md), so it is safe to leave in.
 * Sending happens after the API response, so it never slows the app down.
 */
class PushNotifier
{
    public static function configured(): bool
    {
        $path = self::credentialsPath();

        return $path !== null && is_file($path);
    }

    /** Push [$title]/[$body] to every phone [$userId] has signed in on. */
    public static function toUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (! self::configured()) {
            return;
        }

        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        app()->terminating(function () use ($tokens, $title, $body, $data) {
            try {
                foreach ($tokens as $token) {
                    self::sendTo($token, $title, $body, $data);
                }
            } catch (\Throwable $e) {
                Log::warning('Push failed: '.$e->getMessage());
            }
        });
    }

    private static function sendTo(string $token, string $title, string $body, array $data): void
    {
        $credentials = self::credentials();
        $accessToken = self::accessToken($credentials);
        if (! $accessToken) {
            return;
        }

        $response = Http::withToken($accessToken)
            ->timeout(8)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => mb_strimwidth($body, 0, 180, '…')],
                    // FCM data values must all be strings.
                    'data' => array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                ],
            ]);

        // The phone uninstalled the app or the token expired: forget it.
        if (in_array($response->status(), [400, 404], true) && str_contains($response->body(), 'UNREGISTERED')) {
            DeviceToken::where('token', $token)->delete();
        } elseif ($response->failed()) {
            Log::warning('FCM rejected a push: '.$response->body());
        }
    }

    private static function credentialsPath(): ?string
    {
        $path = config('services.fcm.credentials');
        if (! $path) {
            return null;
        }

        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) ? $path : base_path($path);
    }

    private static function credentials(): array
    {
        return json_decode((string) file_get_contents(self::credentialsPath()), true) ?: [];
    }

    /** OAuth2 access token from the service account (signed JWT), cached for 50 minutes. */
    private static function accessToken(array $credentials): ?string
    {
        if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
            return null;
        }

        return Cache::remember('fcm_access_token', 3000, function () use ($credentials) {
            $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$b64($signature),
            ]);

            return $response->json('access_token');
        });
    }
}
