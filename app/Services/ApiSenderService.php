<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ApiSenderService
{
    private array $defaultEndpoints = [
        'discharge' => '/api/discharge.php',
        'load'      => '/api/load.php',
        'release'   => '/api/release.php',
        'receive'   => '/api/receive.php',
    ];

    public function send(string $type, array $payload): array
    {
        $baseUrl      = rtrim(Setting::get("endpoint_{$type}", Setting::get('api_base_url', '')), '/');
        $encryptedToken = Setting::get('api_token', '');
        try {
            $token = $encryptedToken ? Crypt::decryptString($encryptedToken) : '';
        } catch (\Exception) {
            $token = $encryptedToken;
        }
        $endpoint  = $baseUrl ?: ($this->defaultEndpoints[$type] ?? '');

        if (empty($endpoint)) {
            return [
                'success'   => false,
                'message'   => "No endpoint configured for type: {$type}",
                'http_code' => 0,
                'body'      => null,
            ];
        }

        $response = Http::withToken($token)
            ->timeout(60)
            ->post($endpoint, ['data' => $payload]);

        return [
            'success'   => $response->successful(),
            'message'   => $response->successful() ? 'OK' : $response->reason(),
            'http_code' => $response->status(),
            'body'      => $response->json() ?? $response->body(),
        ];
    }
}
