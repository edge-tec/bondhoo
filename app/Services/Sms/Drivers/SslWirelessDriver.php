<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslWirelessDriver implements SmsGatewayInterface
{
    public function __construct(
        protected array $config
    ) {}

    public function send(string $to, string $message): array
    {
        $endpoint = $this->config['endpoint'] ?? 'https://smsplus.sslwireless.com/api/v3/send-sms';
        $apiToken = $this->config['api_token'] ?? '';
        $sid = $this->config['sid'] ?? '';
        $csmsId = ($this->config['csms_id'] ?? 'JUGAJUG').'_'.uniqid();

        if (empty($apiToken) || empty($sid)) {
            Log::warning('SSL Wireless credentials not configured. Falling back to log.');

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'SSL Wireless credentials not configured.',
            ];
        }

        try {
            $response = Http::timeout(10)->post($endpoint, [
                'api_token' => $apiToken,
                'sid' => $sid,
                'msisdn' => $to,
                'sms' => $message,
                'csms_id' => $csmsId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $csmsId,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'SSL Wireless responded with status '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('SSL Wireless SMS dispatch error: '.$e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
