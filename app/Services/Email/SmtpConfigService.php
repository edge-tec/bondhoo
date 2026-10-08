<?php

namespace App\Services\Email;

use App\Mail\SmtpTestMail;
use App\Models\EmailLog;
use App\Models\SmtpSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;

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
     * Sets verify_peer to false to handle self-signed/mismatched certificates (e.g. Contabo VPS).
     */
    public function applyToMailer(?SmtpSetting $setting = null): void
    {
        $setting = $setting ?: $this->getActiveSettings();

        if (! $setting) {
            return;
        }

        $encryption = $this->normalizeEncryption($setting->mail_encryption);

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

        $ehloDomain = $this->extractDomain($setting->mail_from_address);
        Config::set('mail.mailers.smtp.local_domain', $ehloDomain);

        if (! empty($setting->mail_from_address)) {
            Config::set('mail.from.address', $setting->mail_from_address);
        }
        if (! empty($setting->mail_from_name)) {
            Config::set('mail.from.name', $setting->mail_from_name);
        }
    }

    /**
     * Test SMTP connection and dispatch an authentic test email.
     * Captures the SMTP Message-ID from the Symfony transport for delivery tracing.
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

        $encryption = $this->normalizeEncryption($encryption);
        $ehloDomain = $this->extractDomain($fromAddress);

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
            'local_domain' => $ehloDomain,
        ]);

        $log = EmailLog::create([
            'recipient' => $toEmail,
            'from_address' => $fromAddress,
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
            $mailable = new SmtpTestMail(
                toEmail: $toEmail,
                smtpHost: $host,
                smtpPort: $port,
                encryption: $encryption ?: 'None',
                fromAddress: $fromAddress,
                fromName: $fromName
            );

            /** @var SentMessage|null $sentMessage */
            $sentMessage = Mail::mailer('test_smtp')->to($toEmail)->send($mailable);

            // Extract SMTP Message-ID from the Symfony transport response
            $messageId = null;
            $smtpResponse = null;
            if ($sentMessage instanceof SentMessage) {
                $messageId = $sentMessage->getMessageId();
                $smtpResponse = $sentMessage->getDebug();
            }

            $log->update([
                'status' => 'smtp_accepted',
                'smtp_message_id' => $messageId,
                'smtp_response' => $smtpResponse ? mb_substr($smtpResponse, 0, 500) : null,
                'sent_at' => now(),
            ]);

            $dns = $this->checkDnsDeliverability($fromAddress);
            $deliveryWarnings = $this->buildDeliveryWarnings($dns, $fromAddress);

            $successMsg = "ইমেইল SMTP সার্ভার কর্তৃক গৃহীত হয়েছে ({$toEmail})।";
            if (! empty($deliveryWarnings)) {
                $successMsg .= ' সতর্কতা: কিছু DNS ত্রুটি পাওয়া গেছে যা ডেলিভারি প্রভাবিত করতে পারে।';
            }

            return [
                'success' => true,
                'message' => $successMsg,
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption ?: 'None',
                    'recipient' => $toEmail,
                    'from_address' => $fromAddress,
                    'log_id' => $log->id,
                    'smtp_message_id' => $messageId,
                    'status_note' => 'SMTP সার্ভার ইমেইল গ্রহণ করেছে। এর মানে এই নয় যে ইমেইল ইনবক্সে পৌঁছেছে — SPF, DKIM, DMARC ও recipient সার্ভার পলিসির উপর নির্ভর করে।',
                    'delivery_warnings' => $deliveryWarnings,
                    'dns_status' => $dns,
                ],
            ];
        } catch (\Throwable $e) {
            $safeError = $this->sanitizeError($e->getMessage(), $password);

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
     * Direct SMTP Connection & Handshake Verification (without sending an email).
     * Tests socket connection, TLS/STARTTLS handshake, EHLO greeting, and authentication.
     *
     * @return array{success: bool, message: string, details?: array}
     */
    public function verifyConnection(?array $overrideConfig = null): array
    {
        $settings = $this->getActiveSettings();

        $host = $overrideConfig['mail_host'] ?? $settings->mail_host;
        $port = isset($overrideConfig['mail_port']) ? (int) $overrideConfig['mail_port'] : $settings->mail_port;
        $username = $overrideConfig['mail_username'] ?? $settings->mail_username;
        $password = ! empty($overrideConfig['mail_password']) ? $overrideConfig['mail_password'] : $settings->getDecryptedPassword();
        $encryption = $overrideConfig['mail_encryption'] ?? $settings->mail_encryption;
        $timeout = isset($overrideConfig['timeout']) ? (int) $overrideConfig['timeout'] : ($settings->timeout ?: 15);

        $encryption = $this->normalizeEncryption($encryption);

        $startTime = microtime(true);

        try {
            $ehloDomain = $this->extractDomain($username);

            Config::set('mail.mailers.test_smtp_verify', [
                'transport' => 'smtp',
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username' => $username,
                'password' => $password,
                'timeout' => $timeout,
                'verify_peer' => false,
                'local_domain' => $ehloDomain,
            ]);

            $transport = Mail::mailer('test_smtp_verify')->getSymfonyTransport();
            $transport->start();
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            $transport->stop();

            return [
                'success' => true,
                'message' => "SMTP সার্ভার সংযোগ ও প্রমাণীকরণ সম্পূর্ণ সফল! (লেটেন্সি: {$latencyMs}ms)",
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption ?: 'None',
                    'latency_ms' => $latencyMs,
                    'auth_verified' => ! empty($username),
                ],
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            $safeError = $this->sanitizeError($e->getMessage(), $password);

            Log::warning('SMTP quick connection verification failed: '.$safeError);

            return [
                'success' => false,
                'message' => "SMTP সংযোগে ত্রুটি: {$safeError}",
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption ?: 'None',
                    'latency_ms' => $latencyMs,
                    'error' => $safeError,
                ],
            ];
        }
    }

    /**
     * Check DNS records (SPF, DMARC, MX, DKIM, PTR) for email deliverability diagnostics.
     *
     * @return array{domain: string, spf: array, dmarc: array, mx: array, dkim: array, ptr: array, is_healthy: bool}
     */
    public function checkDnsDeliverability(?string $fromAddress = null): array
    {
        $domain = 'bondhoo.com';
        if ($fromAddress && str_contains($fromAddress, '@')) {
            $domain = substr(strrchr($fromAddress, '@'), 1);
        } else {
            $settings = $this->getActiveSettings();
            if (! empty($settings->mail_from_address) && str_contains($settings->mail_from_address, '@')) {
                $domain = substr(strrchr($settings->mail_from_address, '@'), 1);
            }
        }

        $domain = strtolower(trim($domain));

        // 1. SPF Check
        $spfFound = false;
        $spfRecord = null;
        $txtRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        foreach ($txtRecords as $rec) {
            $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
            if (str_starts_with($txt, 'v=spf1')) {
                $spfFound = true;
                $spfRecord = $txt;
                break;
            }
        }

        // 2. DMARC Check
        $dmarcFound = false;
        $dmarcValid = false;
        $dmarcRecord = null;
        $dmarcRecords = @dns_get_record('_dmarc.'.$domain, DNS_TXT) ?: [];
        foreach ($dmarcRecords as $rec) {
            $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
            if (! empty($txt)) {
                $dmarcFound = true;
                $dmarcRecord = $txt;
                if (str_starts_with($txt, 'v=DMARC1')) {
                    $dmarcValid = true;
                }
                break;
            }
        }

        // 3. MX Check
        $mxRecords = @dns_get_record($domain, DNS_MX) ?: [];
        $mxFound = ! empty($mxRecords);
        $mxHosts = [];
        foreach ($mxRecords as $mx) {
            $mxHosts[] = ($mx['target'] ?? '').' (Prio: '.($mx['pri'] ?? 10).')';
        }

        // 4. DKIM Check (common selectors)
        $dkimFound = false;
        $dkimSelector = null;
        $dkimSelectors = ['default', 'mail', 'dkim', 'k1', 's1', 'google', 'smtp'];
        foreach ($dkimSelectors as $selector) {
            $dkimRecords = @dns_get_record("{$selector}._domainkey.{$domain}", DNS_TXT) ?: [];
            foreach ($dkimRecords as $rec) {
                $txt = $rec['txt'] ?? ($rec['entries'][0] ?? '');
                if (str_contains($txt, 'v=DKIM1')) {
                    $dkimFound = true;
                    $dkimSelector = $selector;
                    break 2;
                }
            }
        }

        // 5. PTR Check (reverse DNS for the sending IP)
        $ptrStatus = 'unknown';
        $ptrHost = null;
        $ptrMatches = false;
        $serverARecords = @dns_get_record("mail.{$domain}", DNS_A) ?: [];
        if (! empty($serverARecords)) {
            $serverIp = $serverARecords[0]['ip'] ?? null;
            if ($serverIp) {
                $reversedIp = implode('.', array_reverse(explode('.', $serverIp)));
                $ptrRecords = @dns_get_record("{$reversedIp}.in-addr.arpa", DNS_PTR) ?: [];
                if (! empty($ptrRecords)) {
                    $ptrHost = $ptrRecords[0]['target'] ?? null;
                    $ptrStatus = 'found';
                    // Check if PTR matches the sending domain
                    $ptrMatches = $ptrHost && (
                        str_ends_with(rtrim($ptrHost, '.'), $domain) ||
                        rtrim($ptrHost, '.') === "mail.{$domain}"
                    );
                } else {
                    $ptrStatus = 'missing';
                }
            }
        }

        return [
            'domain' => $domain,
            'spf' => [
                'status' => $spfFound ? 'ok' : 'missing',
                'record' => $spfRecord,
                'recommended' => 'v=spf1 mx ip4:109.199.110.101 ~all',
            ],
            'dmarc' => [
                'status' => $dmarcValid ? 'ok' : ($dmarcFound ? 'invalid' : 'missing'),
                'record' => $dmarcRecord,
                'recommended' => 'v=DMARC1; p=none; sp=none;',
            ],
            'mx' => [
                'status' => $mxFound ? 'ok' : 'missing',
                'count' => count($mxRecords),
                'hosts' => $mxHosts,
                'recommended' => 'mail.'.$domain.' (Priority 10)',
            ],
            'dkim' => [
                'status' => $dkimFound ? 'ok' : 'missing',
                'selector' => $dkimSelector,
                'recommended' => 'DKIM key at default._domainkey.'.$domain,
            ],
            'ptr' => [
                'status' => $ptrStatus,
                'host' => $ptrHost,
                'matches_domain' => $ptrMatches,
                'recommended' => "mail.{$domain}",
            ],
            'is_healthy' => $spfFound && $dmarcValid && $mxFound,
        ];
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
     * Build actionable delivery warnings based on DNS check results.
     *
     * @return array<string>
     */
    protected function buildDeliveryWarnings(array $dns, ?string $fromAddress): array
    {
        $warnings = [];
        $domain = $dns['domain'] ?? 'unknown';

        if (($dns['spf']['status'] ?? '') !== 'ok') {
            $warnings[] = "❌ SPF রেকর্ড অনুপস্থিত ({$domain})। Gmail/Yahoo ইমেইল reject বা spam করবে। DNS-এ যোগ করুন: {$dns['spf']['recommended']}";
        }

        if (($dns['mx']['status'] ?? '') !== 'ok') {
            $warnings[] = "❌ MX রেকর্ড অনুপস্থিত ({$domain})। Bounce ইমেইল ফেরত আসবে না। DNS-এ যোগ করুন: {$dns['mx']['recommended']}";
        }

        if (($dns['dmarc']['status'] ?? '') !== 'ok') {
            $warnings[] = "⚠️ DMARC রেকর্ড ত্রুটিপূর্ণ বা অনুপস্থিত ({$domain})। SPF ছাড়া DMARC fail করবে।";
        }

        if (($dns['dkim']['status'] ?? '') !== 'ok') {
            $warnings[] = "⚠️ DKIM রেকর্ড পাওয়া যায়নি ({$domain})। ডেলিভারিবিলিটি কমবে।";
        }

        if (isset($dns['ptr']) && ! ($dns['ptr']['matches_domain'] ?? false) && ($dns['ptr']['status'] ?? '') === 'found') {
            $ptrHost = $dns['ptr']['host'] ?? 'N/A';
            $warnings[] = "⚠️ PTR mismatch: Reverse DNS দেখাচ্ছে '{$ptrHost}' কিন্তু From ডোমেইন '{$domain}'। Contabo panel থেকে PTR পরিবর্তন করুন।";
        }

        return $warnings;
    }

    /**
     * Normalize encryption value to standard format.
     */
    protected function normalizeEncryption(?string $encryption): ?string
    {
        if (in_array(strtolower((string) $encryption), ['none', 'null', ''], true)) {
            return null;
        }

        return $encryption;
    }

    /**
     * Extract domain from an email address with fallback.
     */
    protected function extractDomain(?string $emailOrUsername): string
    {
        if ($emailOrUsername && str_contains($emailOrUsername, '@')) {
            return substr(strrchr($emailOrUsername, '@'), 1);
        }

        return 'bondhoo.com';
    }

    /**
     * Sanitize error messages to never expose passwords or secrets.
     */
    protected function sanitizeError(string $error, ?string $password): string
    {
        if (! empty($password)) {
            $error = str_replace($password, '********', $error);
        }

        return $error;
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
