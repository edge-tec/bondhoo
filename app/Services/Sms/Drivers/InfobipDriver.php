<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipDriver implements SmsGatewayInterface
{
    public function __construct(
        protected array $config
    ) {}

    public function send(string $to, string $message): array
    {
        $apiKey = $this->config['api_key'] ?? '';
        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $from = $this->config['from'] ?? 'JUGAJUG';

        if (empty($apiKey) || empty($baseUrl)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Infobip credentials not configured.',
            ];
        }

        $url = "{$baseUrl}/sms/2/text/advanced";

        try {
            $response = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(10)->post($url, [
                'messages' => [
                    [
                        'from' => $from,
                        'destinations' => [
                            ['to' => $to],
                        ],
                        'text' => $message,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => 'INFOBIP_'.uniqid(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Infobip error: '.$response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Infobip SMS error: '.$e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
