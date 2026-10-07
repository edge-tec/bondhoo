<?php

namespace App\Services\Email;

use App\Models\EmailLog;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function __construct(
        protected SmtpConfigService $smtpConfigService
    ) {}

    /**
     * Dispatch an email with full idempotency, user preferences, logging, and error resilience.
     */
    public function send(
        string $to,
        Mailable $mailable,
        string $emailType,
        ?User $user = null,
        ?string $idempotencyKey = null,
        array $metadata = []
    ): bool {
        // 1. Check if email system is globally enabled
        $smtpSettings = $this->smtpConfigService->getActiveSettings();
        if (! $smtpSettings->is_enabled) {
            Log::info("Email sending skipped: Email system is currently disabled by Admin. Recipient: {$to}, Type: {$emailType}");

            return false;
        }

        // 2. Check Idempotency / Duplicate Protection
        if (! empty($idempotencyKey) && $this->isDuplicate($idempotencyKey)) {
            Log::info("Email sending skipped: Duplicate idempotency key detected [{$idempotencyKey}]. Recipient: {$to}");

            return true; // Already queued or sent
        }

        // 3. Check User Notification Preferences
        if ($user && ! $this->shouldSendToUser($user, $emailType)) {
            Log::info("Email sending skipped: User #{$user->id} notification preferences opted out of [{$emailType}]");

            return false;
        }

        // 4. Ensure runtime mailer is configured with active SMTP settings
        $this->smtpConfigService->applyToMailer($smtpSettings);

        // 5. Create Delivery Log
        $log = EmailLog::create([
            'user_id' => $user?->id,
            'recipient' => $to,
            'email_type' => $emailType,
            'subject' => method_exists($mailable, 'envelope') ? ($mailable->envelope()->subject ?? 'Bondhoo Notification') : 'Bondhoo Notification',
            'mail_class' => get_class($mailable),
            'ip_address' => request()->ip(),
            'status' => 'queued',
            'attempts' => 1,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $metadata,
        ]);

        // 6. Safe Dispatch
        try {
            if ($mailable instanceof ShouldQueue && config('queue.default') !== 'sync') {
                Mail::to($to)->queue($mailable);
                $log->update([
                    'status' => 'queued',
                ]);
            } else {
                Mail::to($to)->send($mailable);
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            return true;
        } catch (Exception $e) {
            $safeError = $e->getMessage();
            $decryptedPassword = $smtpSettings->getDecryptedPassword();
            if (! empty($decryptedPassword)) {
                $safeError = str_replace($decryptedPassword, '********', $safeError);
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $safeError,
            ]);

            Log::error("Failed to send [{$emailType}] email to {$to}: {$safeError}");

            return false;
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
            'welcome',
            'password_reset',
            'password_changed',
            'new_login',
            'security_alert',
            'suspicious_login',
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
}
