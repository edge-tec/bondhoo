<?php

namespace App\Services\Sms\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send SMS to the given recipient.
     *
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function send(string $to, string $message): array;
}
