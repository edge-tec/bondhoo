<?php

namespace App\Services\Email;

use App\Models\EmailLog;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;

class EmailService
{
    /**
     * Security-critical and immediate email types that must ALWAYS be dispatched
     * synchronously to the SMTP server to guarantee immediate delivery and accurate
     * acceptance reporting (never delayed in background queues).
     */
    public const CRITICAL_SYNC_TYPES = [
        'verify_email',
        'resend_email',
        'password_reset',
        'password_changed',
        'new_login',
        'security_alert',
        'suspicious_login',
        '2fa_challenge',
        'smtp_test',
    ];

    public function __construct(
        protected SmtpConfigService $smtpConfigService
    ) {}

    /**
     * Dispatch an email with full idempotency, user preferences, logging, and error resilience.
     * Status lifecycle: queued → smtp_accepted (SMTP server accepted) → sent (confirmed delivery).
     *
     * @return bool True if successfully accepted or queued; false on failure/rejection.
     */
    public function send(
        string $to,
        Mailable $mailable,
        string $emailType,
        ?User $user = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
        bool $forceSync = false
    ): bool {
        $result = $this->sendWithResult(
            to: $to,
            mailable: $mailable,
            emailType: $emailType,
            user: $user,
            idempotencyKey: $idempotencyKey,
            metadata: $metadata,
            forceSync: $forceSync
        );

        return (bool) ($result['success'] ?? false);
    }

    /**
     * Dispatch an email and return a detailed result array for accurate UX and error handling.
     *
     * @return array{success: bool, status: string, message_id: ?string, log_id: ?int, error: ?string, category?: string}
     */
    public function sendWithResult(
        string $to,
        Mailable $mailable,
        string $emailType,
        ?User $user = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
        bool $forceSync = false
    ): array {
        // 1. Check if email system is globally enabled
        $smtpSettings = $this->smtpConfigService->getActiveSettings();
        if (! $smtpSettings->is_enabled) {
            Log::info("Email sending skipped: Email system is currently disabled by Admin. Recipient: {$to}, Type: {$emailType}");

            return [
                'success' => false,
                'status' => 'disabled',
                'message_id' => null,
                'log_id' => null,
                'error' => 'Email delivery is currently disabled by system administrator.',
            ];
        }

        // 2. Check Idempotency / Duplicate Protection
        if (! empty($idempotencyKey) && $this->isDuplicate($idempotencyKey)) {
            Log::info("Email sending skipped: Duplicate idempotency key detected [{$idempotencyKey}]. Recipient: {$to}");

            return [
                'success' => true,
                'status' => 'duplicate',
                'message_id' => null,
                'log_id' => null,
                'error' => null,
            ];
        }

        // 3. Check User Notification Preferences
        if ($user && ! $this->shouldSendToUser($user, $emailType)) {
            Log::info("Email sending skipped: User #{$user->id} notification preferences opted out of [{$emailType}]");

            return [
                'success' => false,
                'status' => 'opted_out',
                'message_id' => null,
                'log_id' => null,
                'error' => 'User has opted out of email notifications of this type.',
            ];
        }

        // 4. Ensure runtime mailer is configured with active SMTP settings
        $this->smtpConfigService->applyToMailer($smtpSettings);

        // 5. Create Delivery Log
        $subject = 'Bondhoo Notification';
        if (method_exists($mailable, 'envelope')) {
            $envelope = $mailable->envelope();
            $subject = $envelope?->subject ?? $subject;
        }

        $log = EmailLog::create([
            'user_id' => $user?->id,
            'recipient' => $to,
            'from_address' => $smtpSettings->mail_from_address,
            'email_type' => $emailType,
            'subject' => $subject,
            'mail_class' => get_class($mailable),
            'ip_address' => request()->ip(),
            'status' => 'queued',
            'attempts' => 1,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $metadata,
        ]);

        // 6. Determine whether to send synchronously or via background queue
        $mustSendSync = $forceSync
            || in_array($emailType, self::CRITICAL_SYNC_TYPES, true)
            || ! ($mailable instanceof ShouldQueue)
            || config('queue.default') === 'sync';

        // 7. Dispatch
        try {
            if ($mustSendSync) {
                /** @var SentMessage|null $sentMessage */
                $sentMessage = Mail::to($to)->send($mailable);

                // Extract SMTP Message-ID for delivery tracing
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

                Log::info("Email [{$emailType}] accepted by SMTP server for {$to}. Message-ID: ".($messageId ?: 'N/A'));

                return [
                    'success' => true,
                    'status' => 'smtp_accepted',
                    'message_id' => $messageId,
                    'log_id' => $log->id,
                    'error' => null,
                ];
            }

            // Non-critical asynchronous dispatch
            Mail::to($to)->queue($mailable);
            $log->update([
                'status' => 'queued',
            ]);

            return [
                'success' => true,
                'status' => 'queued',
                'message_id' => null,
                'log_id' => $log->id,
                'error' => null,
            ];
        } catch (Exception $e) {
            $rawError = $e->getMessage();
            $safeError = $this->sanitizeError($rawError, $smtpSettings->getDecryptedPassword());
            $category = $this->classifyError($rawError);

            $log->update([
                'status' => 'failed',
                'error_message' => "[{$category}] {$safeError}",
            ]);

            Log::error("Failed to send [{$emailType}] email to {$to} [Category: {$category}]: {$safeError}");

            return [
                'success' => false,
                'status' => 'failed',
                'category' => $category,
                'message_id' => null,
                'log_id' => $log->id,
                'error' => $safeError,
            ];
        }
    }

    /**
     * Check if an email with the given idempotency key was already queued/sent in the last 15 minutes.
     */
    public function isDuplicate(string $idempotencyKey, int $cooldownMinutes = 15): bool
    {
        return EmailLog::where('idempotency_key', $idempotencyKey)
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->where('status', '!=', 'failed')
            ->exists();
    }

    /**
     * Verify user email preferences. Security emails are mandatory and cannot be disabled.
     */
    public function shouldSendToUser(User $user, string $emailType): bool
    {
        // Mandatory security and account emails
        $mandatoryTypes = [
            'verify_email',
            'resend_email',
            'welcome',
            'password_reset',
            'password_changed',
            'new_login',
            'security_alert',
            'suspicious_login',
            '2fa_challenge',
            'smtp_test',
        ];

        if (in_array($emailType, $mandatoryTypes, true)) {
            return true;
        }

        $settings = $user->notificationSettings;
        if (! $settings) {
            return true; // Default is true for new users
        }

        // Master toggle
        if (! $settings->email_notifications) {
            return false;
        }

        return match ($emailType) {
            'friend_request' => (bool) $settings->friend_request_alerts,
            'friend_accepted' => (bool) $settings->friend_accepted_alerts,
            'comment' => (bool) $settings->comment_alerts,
            'mention' => (bool) $settings->mention_alerts,
            'message' => (bool) $settings->message_alerts,
            'like' => (bool) $settings->like_alerts,
            'share' => (bool) $settings->share_alerts,
            'follower' => (bool) $settings->follower_alerts,
            'page' => (bool) $settings->page_alerts,
            'group' => (bool) $settings->group_alerts,
            default => true,
        };
    }

    /**
     * Classify SMTP/Transport errors into structured diagnostic categories.
     */
    public function classifyError(string $errorMessage): string
    {
        $lower = strtolower($errorMessage);

        if (str_contains($lower, 'authentication') || str_contains($lower, '535') || str_contains($lower, 'credentials')) {
            return 'smtp_auth_failed';
        }

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout') || str_contains($lower, 'operation timed out')) {
            return 'connection_timeout';
        }

        if (str_contains($lower, 'connection refused') || str_contains($lower, 'refused') || str_contains($lower, '111')) {
            return 'connection_refused';
        }

        if (str_contains($lower, 'certificate') || str_contains($lower, 'tls') || str_contains($lower, 'ssl') || str_contains($lower, 'handshake')) {
            return 'tls_failure';
        }

        if (str_contains($lower, '550') || str_contains($lower, '551') || str_contains($lower, '552') || str_contains($lower, '553') || str_contains($lower, 'user not found') || str_contains($lower, 'mailbox unavailable')) {
            return 'recipient_rejected';
        }

        if (str_contains($lower, 'rate limit') || str_contains($lower, 'too many') || str_contains($lower, '452')) {
            return 'provider_rate_limit';
        }

        if (str_contains($lower, 'getaddrinfo') || str_contains($lower, 'dns') || str_contains($lower, 'name or service not known')) {
            return 'dns_failure';
        }

        if (preg_match('/4\d{2}\s/', $lower)) {
            return 'temporary_smtp_failure';
        }

        if (preg_match('/5\d{2}\s/', $lower)) {
            return 'permanent_smtp_failure';
        }

        return 'general_smtp_failure';
    }

    /**
     * Sanitize error message to prevent accidental leakage of SMTP passwords or secrets.
     */
    protected function sanitizeError(string $error, ?string $secret): string
    {
        if (! empty($secret)) {
            $error = str_replace($secret, '********', $error);
        }

        return $error;
    }
}
