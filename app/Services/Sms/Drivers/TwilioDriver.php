<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioDriver implements SmsGatewayInterface
{
    public function __construct(
        protected array $config
    ) {}

    public function send(string $to, string $message): array
    {
        $sid = $this->config['account_sid'] ?? '';
        $token = $this->config['auth_token'] ?? '';
        $from = $this->config['from'] ?? '';

        if (empty($sid) || empty($token) || empty($from)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Twilio credentials not configured.',
            ];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(10)
                ->post($url, [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? ('TW_'.uniqid()),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Twilio error: '.$response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio SMS error: '.$e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
