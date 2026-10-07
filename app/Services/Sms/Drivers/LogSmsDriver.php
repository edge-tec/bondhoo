<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LogSmsDriver implements SmsGatewayInterface
{
    public function send(string $to, string $message): array
    {
        $messageId = 'LOG_SMS_'.uniqid();

        Log::info("SMS Sent via LogDriver to {$to}: {$message}");

        // Keep last sent SMS in cache for inspection and testing verification
        Cache::put("last_sms:{$to}", [
            'to' => $to,
            'message' => $message,
            'message_id' => $messageId,
            'sent_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));

        return [
            'success' => true,
            'message_id' => $messageId,
            'error' => null,
        ];
    }
}
