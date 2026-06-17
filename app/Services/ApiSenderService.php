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

    private int $batchSize = 50;

    public function send(string $type, array $payload): array
    {
        $baseUrl        = rtrim(Setting::get("endpoint_{$type}", Setting::get('api_base_url', '')), '/');
        $encryptedToken = Setting::get('api_token', '');
        try {
            $token = $encryptedToken ? Crypt::decryptString($encryptedToken) : '';
        } catch (\Exception) {
            $token = $encryptedToken;
        }
        $endpoint = $baseUrl ?: ($this->defaultEndpoints[$type] ?? '');

        if (empty($endpoint)) {
            return [
                'success'   => false,
                'message'   => "No endpoint configured for type: {$type}",
                'http_code' => 0,
                'body'      => null,
            ];
        }

        $batches = array_chunk($payload, $this->batchSize);

        if (count($batches) === 1) {
            return $this->postBatch($endpoint, $token, $payload);
        }

        // Multiple batches — merge results
        $mergedResults = [];
        $lastHttpCode  = 200;

        foreach ($batches as $batch) {
            $result = $this->postBatch($endpoint, $token, $batch);
            $lastHttpCode = $result['http_code'];

            if (!$result['success']) {
                return $result;
            }

            $body = $result['body'];
            if (is_array($body) && isset($body['results'])) {
                $mergedResults = array_merge($mergedResults, $body['results']);
            }
        }

        return [
            'success'   => true,
            'message'   => 'OK',
            'http_code' => $lastHttpCode,
            'body'      => empty($mergedResults) ? [] : ['results' => $mergedResults],
        ];
    }

    private function postBatch(string $endpoint, string $token, array $batch): array
    {
        $response = Http::withToken($token)
            ->timeout(60)
            ->post($endpoint, ['data' => $batch]);

        return [
            'success'   => $response->successful(),
            'message'   => $response->successful() ? 'OK' : $response->reason(),
            'http_code' => $response->status(),
            'body'      => $response->json() ?? $response->body(),
        ];
    }
}
