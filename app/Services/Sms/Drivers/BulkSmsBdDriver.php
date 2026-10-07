<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsBdDriver implements SmsGatewayInterface
{
    public function __construct(
        protected array $config
    ) {}

    public function send(string $to, string $message): array
    {
        $endpoint = $this->config['endpoint'] ?? 'http://bulksmsbd.net/api/smsapi';
        $apiKey = $this->config['api_key'] ?? '';
        $senderId = $this->config['sender_id'] ?? 'JUGAJUG';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'BulkSMSBD API key not configured.',
            ];
        }

        try {
            $response = Http::timeout(10)->post($endpoint, [
                'api_key' => $apiKey,
                'type' => 'text',
                'number' => $to,
                'senderid' => $senderId,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => 'BSBD_'.uniqid(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'BulkSMSBD responded with status '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD dispatch error: '.$e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
