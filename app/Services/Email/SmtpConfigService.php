<?php

namespace App\Services\Email;

use App\Mail\SmtpTestMail;
use App\Models\EmailLog;
use App\Models\SmtpSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmtpConfigService
{
    public const CACHE_KEY = 'bondhoo_smtp_active_settings';

    /**
     * Get the active SMTP settings from Cache / Database or fallback to .env config.
     */
    public function getActiveSettings(): SmtpSetting
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached instanceof SmtpSetting) {
                return $cached;
            }
        } catch (\Throwable) {
            // Ignore cache read failures
        }

        Cache::forget(self::CACHE_KEY);

        $setting = SmtpSetting::first();

        if (! $setting) {
            // Initialize default record from current Laravel mail configuration
            $setting = SmtpSetting::create([
                'mail_mailer' => config('mail.default', 'smtp'),
                'mail_host' => config('mail.mailers.smtp.host', '127.0.0.1'),
                'mail_port' => (int) config('mail.mailers.smtp.port', 587),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_password' => config('mail.mailers.smtp.password'),
                'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls') ?: 'tls',
                'mail_from_address' => config('mail.from.address', 'noreply@bondhoo.com'),
                'mail_from_name' => config('mail.from.name', 'Bondhoo'),
                'mail_reply_to' => config('mail.from.address', 'support@bondhoo.com'),
                'smtp_auth' => true,
                'timeout' => 30,
                'rate_limit_per_minute' => 60,
                'is_enabled' => true,
            ]);
        }

        try {
            Cache::put(self::CACHE_KEY, $setting, 3600);
        } catch (\Throwable) {
        }

        return $setting;
    }

    /**
     * Update and persist SMTP settings.
     */
    public function saveSettings(array $data): SmtpSetting
    {
        $setting = SmtpSetting::first() ?: new SmtpSetting;

        $updateData = [
            'mail_mailer' => $data['mail_mailer'] ?? 'smtp',
            'mail_host' => $data['mail_host'] ?? $setting->mail_host,
            'mail_port' => isset($data['mail_port']) ? (int) $data['mail_port'] : ($setting->mail_port ?? 587),
            'mail_username' => array_key_exists('mail_username', $data) ? $data['mail_username'] : $setting->mail_username,
            'mail_encryption' => $data['mail_encryption'] ?? ($setting->mail_encryption ?: 'tls'),
            'mail_from_address' => $data['mail_from_address'] ?? $setting->mail_from_address,
            'mail_from_name' => $data['mail_from_name'] ?? ($setting->mail_from_name ?: 'Bondhoo'),
            'mail_reply_to' => array_key_exists('mail_reply_to', $data) ? $data['mail_reply_to'] : $setting->mail_reply_to,
            'smtp_auth' => isset($data['smtp_auth']) ? (bool) $data['smtp_auth'] : true,
            'timeout' => isset($data['timeout']) ? (int) $data['timeout'] : ($setting->timeout ?? 30),
            'rate_limit_per_minute' => isset($data['rate_limit_per_minute']) ? (int) $data['rate_limit_per_minute'] : ($setting->rate_limit_per_minute ?? 60),
            'is_enabled' => isset($data['is_enabled']) ? (bool) $data['is_enabled'] : true,
        ];

        // Only update password if a new non-empty password is provided
        if (! empty($data['mail_password'])) {
            $setting->mail_password = $data['mail_password'];
        }

        $setting->fill($updateData);
        $setting->save();

        Cache::forget(self::CACHE_KEY);
        $this->applyToMailer($setting);
        $this->syncToEnvFile($setting);

        return $setting;
    }

    /**
     * Apply active database SMTP configuration dynamically into Laravel's runtime mail config.
     */
    public function applyToMailer(?SmtpSetting $setting = null): void
    {
        $setting = $setting ?: $this->getActiveSettings();

        if (! $setting) {
            return;
        }

        $encryption = $setting->mail_encryption;
        if (in_array(strtolower((string) $encryption), ['none', 'null', ''], true)) {
            $encryption = null;
        }

        if (! app()->environment('testing')) {
            Config::set('mail.default', $setting->is_enabled ? 'smtp' : 'log');
        }
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $setting->mail_host);
        Config::set('mail.mailers.smtp.port', $setting->mail_port);
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $setting->mail_username);
        Config::set('mail.mailers.smtp.password', $setting->getDecryptedPassword());
        Config::set('mail.mailers.smtp.timeout', $setting->timeout);
        Config::set('mail.mailers.smtp.verify_peer', false);

        if (! empty($setting->mail_from_address)) {
            Config::set('mail.from.address', $setting->mail_from_address);
        }
        if (! empty($setting->mail_from_name)) {
            Config::set('mail.from.name', $setting->mail_from_name);
        }
    }

    /**
     * Test SMTP connection and dispatch an authentic test email.
     *
     * @return array{success: bool, message: string, details?: array}
     */
    public function testConnection(string $toEmail, ?array $overrideConfig = null): array
    {
        $settings = $this->getActiveSettings();

        $host = $overrideConfig['mail_host'] ?? $settings->mail_host;
        $port = isset($overrideConfig['mail_port']) ? (int) $overrideConfig['mail_port'] : $settings->mail_port;
        $username = $overrideConfig['mail_username'] ?? $settings->mail_username;
        $password = ! empty($overrideConfig['mail_password']) ? $overrideConfig['mail_password'] : $settings->getDecryptedPassword();
        $encryption = $overrideConfig['mail_encryption'] ?? $settings->mail_encryption;
        $fromAddress = $overrideConfig['mail_from_address'] ?? $settings->mail_from_address ?? config('mail.from.address');
        $fromName = $overrideConfig['mail_from_name'] ?? $settings->mail_from_name ?? config('mail.from.name');

        if (in_array(strtolower((string) $encryption), ['none', 'null', ''], true)) {
            $encryption = null;
        }

        // Temporarily configure test smtp mailer
        Config::set('mail.mailers.test_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => 15,
            'verify_peer' => false,
        ]);

        $log = EmailLog::create([
            'recipient' => $toEmail,
            'email_type' => 'smtp_test',
            'subject' => 'Bondhoo Enterprise SMTP কনফিগারেশন টেস্ট',
            'mail_class' => SmtpTestMail::class,
            'status' => 'queued',
            'metadata' => [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'from_address' => $fromAddress,
            ],
        ]);

        try {
            Mail::mailer('test_smtp')->to($toEmail)->send(new SmtpTestMail(
                toEmail: $toEmail,
                smtpHost: $host,
                smtpPort: $port,
                encryption: $encryption ?: 'None',
                fromAddress: $fromAddress,
                fromName: $fromName
            ));

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "টেস্ট ইমেইল সফলভাবে পাঠানো হয়েছে ({$toEmail})। SMTP সংযোগ সম্পূর্ণ সক্রিয়।",
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption ?: 'None',
                    'recipient' => $toEmail,
                    'log_id' => $log->id,
                ],
            ];
        } catch (\Throwable $e) {
            $safeError = $e->getMessage();
            // Sanitize: never expose passwords in error messages
            if (! empty($password)) {
                $safeError = str_replace($password, '********', $safeError);
            }

            if (isset($log) && $log instanceof EmailLog) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $safeError,
                ]);
            }

            Log::error('SMTP Test connection failed: '.$safeError);

            return [
                'success' => false,
                'message' => "SMTP সংযোগে ত্রুটি: {$safeError}",
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption ?: 'None',
                    'error' => $safeError,
                    'log_id' => $log->id ?? null,
                ],
            ];
        }
    }

    /**
     * Get safe sanitized representation of SMTP settings for API/UI.
     */
    public function getSafeSettings(): array
    {
        $setting = $this->getActiveSettings();

        return [
            'id' => $setting->id,
            'mail_mailer' => $setting->mail_mailer,
            'mail_host' => $setting->mail_host,
            'mail_port' => $setting->mail_port,
            'mail_username' => $setting->mail_username,
            'mail_password_masked' => $setting->masked_password,
            'has_password' => ! empty($setting->getDecryptedPassword()),
            'mail_encryption' => $setting->mail_encryption,
            'mail_from_address' => $setting->mail_from_address,
            'mail_from_name' => $setting->mail_from_name,
            'mail_reply_to' => $setting->mail_reply_to,
            'smtp_auth' => $setting->smtp_auth,
            'timeout' => $setting->timeout,
            'rate_limit_per_minute' => $setting->rate_limit_per_minute,
            'is_enabled' => $setting->is_enabled,
            'updated_at' => $setting->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Safely synchronize SMTP settings to the .env file if writable.
     */
    protected function syncToEnvFile(SmtpSetting $setting): void
    {
        try {
            $envPath = base_path('.env');
            if (! file_exists($envPath) || ! is_writable($envPath)) {
                return;
            }

            $content = file_get_contents($envPath);
            if ($content === false) {
                return;
            }

            $encryption = $setting->mail_encryption;
            if (in_array(strtolower((string) $encryption), ['none', 'null', ''], true)) {
                $encryption = 'null';
            }

            $decryptedPassword = $setting->getDecryptedPassword();

            $envUpdates = [
                'MAIL_MAILER' => $setting->is_enabled ? 'smtp' : 'log',
                'MAIL_HOST' => $setting->mail_host ?? '127.0.0.1',
                'MAIL_PORT' => (string) ($setting->mail_port ?? 587),
                'MAIL_USERNAME' => $setting->mail_username ?: 'null',
                'MAIL_ENCRYPTION' => $encryption ?: 'null',
                'MAIL_FROM_ADDRESS' => '"'.($setting->mail_from_address ?: 'noreply@bondhoo.com').'"',
                'MAIL_FROM_NAME' => '"'.($setting->mail_from_name ?: 'Bondhoo').'"',
            ];

            if (! empty($decryptedPassword)) {
                $envUpdates['MAIL_PASSWORD'] = '"'.addcslashes($decryptedPassword, '"$').'"';
            }

            foreach ($envUpdates as $key => $val) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, "{$key}={$val}", $content);
                } else {
                    $content .= PHP_EOL."{$key}={$val}";
                }
            }

            file_put_contents($envPath, $content, LOCK_EX);
        } catch (\Throwable $e) {
            Log::warning('SMTP settings .env file sync skipped: '.$e->getMessage());
        }
    }
}
