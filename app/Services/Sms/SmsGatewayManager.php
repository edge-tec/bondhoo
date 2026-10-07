<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsGatewayInterface;
use App\Services\Sms\Drivers\BulkSmsBdDriver;
use App\Services\Sms\Drivers\InfobipDriver;
use App\Services\Sms\Drivers\LogSmsDriver;
use App\Services\Sms\Drivers\SslWirelessDriver;
use App\Services\Sms\Drivers\TwilioDriver;
use InvalidArgumentException;

class SmsGatewayManager
{
    /**
     * Cache of resolved driver instances.
     *
     * @var array<string, SmsGatewayInterface>
     */
    protected array $drivers = [];

    /**
     * Get a driver instance by name.
     */
    public function driver(?string $name = null): SmsGatewayInterface
    {
        $name = $name ?: config('sms.default', 'log');

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Send SMS automatically selecting best gateway for destination.
     *
     * @return array{success: bool, message_id: ?string, error: ?string, gateway: string}
     */
    public function send(string $to, string $message, ?string $preferredGateway = null): array
    {
        $gatewayName = $preferredGateway ?: $this->resolveGatewayForNumber($to);
        $gateway = $this->driver($gatewayName);
        $result = $gateway->send($to, $message);

        return array_merge($result, ['gateway' => $gatewayName]);
    }

    /**
     * Automatically pick appropriate gateway based on country prefix.
     */
    protected function resolveGatewayForNumber(string $phone): string
    {
        $default = config('sms.default', 'log');
        if ($default === 'log') {
            return 'log';
        }

        // Bangladesh number (+880 or 01...)
        if (str_starts_with($phone, '+880') || str_starts_with($phone, '880') || str_starts_with($phone, '01')) {
            return config('sms.gateways.ssl_wireless.api_token') ? 'ssl_wireless' : 'bulksmsbd';
        }

        // International destination
        return config('sms.gateways.twilio.account_sid') ? 'twilio' : 'infobip';
    }

    /**
     * Create the driver instance.
     */
    protected function createDriver(string $name): SmsGatewayInterface
    {
        $config = config("sms.gateways.{$name}", []);

        return match ($name) {
            'ssl_wireless' => new SslWirelessDriver($config),
            'bulksmsbd' => new BulkSmsBdDriver($config),
            'twilio' => new TwilioDriver($config),
            'infobip' => new InfobipDriver($config),
            'log' => new LogSmsDriver,
            default => throw new InvalidArgumentException("Unsupported SMS driver [{$name}]."),
        };
    }
}
