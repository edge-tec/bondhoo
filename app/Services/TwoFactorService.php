<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\RecoveryCode;
use App\Models\User;
use App\Models\UserTwoFactor;
use App\Notifications\EmailVerificationOtpNotification;
use App\Notifications\SmsOtpNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TwoFactorService
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Generate fresh TOTP secret, backup codes and QR setup data.
     *
     * @return array{secret: string, qr_uri: string, qr_code_url: string, recovery_codes: string[]}
     */
    public function setupTotp(User $user): array
    {
        $secret = $this->generateBase32Secret(16);
        $appName = rawurlencode(config('app.name', 'JUGAJUG'));
        $account = rawurlencode($user->email ?: $user->username);
        $qrUri = "otpauth://totp/{$appName}:{$account}?secret={$secret}&issuer={$appName}&algorithm=SHA1&digits=6&period=30";

        // Generate 8 recovery codes
        $recoveryCodes = [];
        $hashedCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4).'-'.Str::random(4));
            $recoveryCodes[] = $code;
            $hashedCodes[] = Hash::make($code);
        }

        // 1. Store in user_two_factor table
        UserTwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => Crypt::encryptString($secret),
                'type' => 'totp',
                'is_enabled' => false,
                'confirmed_at' => null,
            ]
        );

        // 2. Store in recovery_codes table
        RecoveryCode::where('user_id', $user->id)->delete();
        foreach ($recoveryCodes as $recCode) {
            RecoveryCode::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($recCode),
                'used_at' => null,
            ]);
        }

        // Encrypt secret in user model for backwards compatibility
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($hashedCodes)),
        ]);

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($qrUri),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Enable 2FA after verifying the first confirmation code.
     */
    public function enable(User $user, string $code, string $type = 'app'): bool
    {
        if ($type === 'app') {
            $user2fa = UserTwoFactor::where('user_id', $user->id)->first();
            $secret = $user2fa ? $user2fa->getDecryptedSecret() : ($user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null);

            if (! $secret) {
                throw ValidationException::withMessages([
                    'code' => ['টু-ফ্যাক্টর সিক্রেট সেটআপ করা হয়নি। প্রথমে সেটআপ সম্পন্ন করুন।'],
                ]);
            }

            if (! $this->verifyTotpCode($secret, $code)) {
                throw ValidationException::withMessages([
                    'code' => ['প্রদত্ত অথেনটিকেটর কোডটি সঠিক নয়। অনুগ্রহ করে যাচাই করুন।'],
                ]);
            }

            // Update user_two_factor table
            UserTwoFactor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'is_enabled' => true,
                    'type' => $type,
                    'confirmed_at' => now(),
                    'last_used_at' => now(),
                ]
            );
        } elseif ($type === 'email') {
            $this->otpService->verifyOtp($user->email, '2fa_enable', $code);
        } elseif ($type === 'sms') {
            $this->otpService->verifyOtp($user->phone, '2fa_enable', $code);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_type' => $type,
            'two_factor_confirmed_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.2fa_enabled',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'new_values' => ['type' => $type],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable(User $user, string $password): bool
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['টু-ফ্যাক্টর নিষ্ক্রিয় করতে সঠিক পাসওয়ার্ড আবশ্যক।'],
            ]);
        }

        // 1. Clear user_two_factor configuration
        UserTwoFactor::where('user_id', $user->id)->update([
            'is_enabled' => false,
            'secret' => null,
            'confirmed_at' => null,
        ]);

        // 2. Delete recovery codes
        RecoveryCode::where('user_id', $user->id)->delete();

        // 3. Reset user table fields
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.2fa_disabled',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Send challenge code when user has email or SMS 2FA.
     */
    public function sendChallengeOtp(User $user): array
    {
        try {
            if ($user->two_factor_type === 'sms' && $user->phone) {
                $otp = $this->otpService->generateOtp($user->phone, 'login_2fa', $user);
                $user->notify(new SmsOtpNotification($otp['plain_otp'], 'লগইন যাচাই'));

                return ['method' => 'sms', 'destination' => $this->mask($user->phone)];
            }

            // Default to email
            $destination = $user->email ?: $user->phone;
            $otp = $this->otpService->generateOtp($destination, 'login_2fa', $user);
            $user->notify(new EmailVerificationOtpNotification($otp['plain_otp'], 'লগইন টু-ফ্যাক্টর অনুমোদন'));

            return ['method' => 'email', 'destination' => $this->mask($destination)];
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            Log::warning('2FA OTP delivery issue: '.$e->getMessage());

            throw ValidationException::withMessages([
                'otp' => ['ওটিপি (OTP) পাঠাতে সাময়িক সমস্যা হচ্ছে। আপনি আপনার টু-ফ্যাক্টর অথেনটিকেটর অ্যাপ অথবা ব্যাকআপ রিকভারি কোড ব্যবহার করতে পারেন।'],
            ]);
        }
    }

    /**
     * Verify incoming 2FA code (app, email, sms, or backup recovery code).
     */
    public function verifyChallenge(User $user, string $code): bool
    {
        $code = trim($code);

        // 1. Check recovery_codes database table
        $hasRecoveryCodesTableRecords = RecoveryCode::where('user_id', $user->id)->exists();
        if ($hasRecoveryCodesTableRecords) {
            $unusedRecoveryCodes = RecoveryCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->get();

            foreach ($unusedRecoveryCodes as $recCode) {
                if ($recCode->matches($code)) {
                    $recCode->update(['used_at' => now()]);

                    if ($user->two_factor_recovery_codes) {
                        try {
                            $hashedCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?: [];
                            foreach ($hashedCodes as $idx => $h) {
                                if (Hash::check(strtoupper($code), $h)) {
                                    unset($hashedCodes[$idx]);
                                    $user->update([
                                        'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($hashedCodes))),
                                    ]);
                                    break;
                                }
                            }
                        } catch (\Throwable) {
                        }
                    }

                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'auth.2fa_recovery_code_used',
                        'entity_type' => User::class,
                        'entity_id' => $user->id,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    return true;
                }
            }

            // If it was a recovery code attempt, reject immediately
            if (str_contains($code, '-') || strlen($code) > 6) {
                throw ValidationException::withMessages([
                    'code' => ['অবৈধ অথবা ইতিপূর্বে ব্যবহৃত ব্যাকআপ রিকভারি কোড।'],
                ]);
            }
        } elseif ((str_contains($code, '-') || strlen($code) > 6) && $user->two_factor_recovery_codes) {
            return $this->verifyAndConsumeRecoveryCode($user, $code);
        }

        // 2. Authenticator App TOTP (Google / Microsoft Authenticator)
        $user2fa = UserTwoFactor::where('user_id', $user->id)->first();
        $secret = $user2fa ? $user2fa->getDecryptedSecret() : ($user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null);

        if ($secret && $this->verifyTotpCode($secret, $code)) {
            UserTwoFactor::where('user_id', $user->id)->update(['last_used_at' => now()]);

            return true;
        }

        // 3. Email OTP
        if ($user->two_factor_type === 'email' && $user->email) {
            return $this->otpService->verifyOtp($user->email, 'login_2fa', $code);
        }

        // 4. SMS OTP
        if ($user->two_factor_type === 'sms' && $user->phone) {
            return $this->otpService->verifyOtp($user->phone, 'login_2fa', $code);
        }

        throw ValidationException::withMessages([
            'code' => ['প্রদত্ত টু-ফ্যাক্টর অথেন্টিকেশন কোডটি সঠিক নয়।'],
        ]);
    }

    /**
     * Verify and consume a one-time backup recovery code.
     */
    protected function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            throw ValidationException::withMessages(['code' => ['কোনো রিকভারি কোড পাওয়া যায়নি।']]);
        }

        $hashedCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?: [];
        $matchedIndex = null;

        foreach ($hashedCodes as $index => $hash) {
            if (Hash::check(strtoupper($code), $hash)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            throw ValidationException::withMessages(['code' => ['অবৈধ ব্যাকআপ রিকভারি কোড।']]);
        }

        // Remove consumed recovery code
        unset($hashedCodes[$matchedIndex]);
        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($hashedCodes))),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.2fa_recovery_code_used',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Verify RFC 6238 TOTP code with standard 30s window.
     */
    public function verifyTotpCode(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $calculatedCode = $this->calculateTotp($secret, $currentTimeSlice + $offset);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate 6-digit TOTP code for a time slice.
     */
    protected function calculateTotp(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $packedTime = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $packedTime, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $value = $unpacked[1] & 0x7FFFFFFF;
        $modulo = $value % 1000000;

        return str_pad((string) $modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate RFC 3548 / 4648 Base32 secret string.
     */
    protected function generateBase32Secret(int $length = 16): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Decode Base32 string to binary.
     */
    protected function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper($b32);
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            $val = strpos($alphabet, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }

    /**
     * Mask email or phone for privacy.
     */
    protected function mask(string $val): string
    {
        if (str_contains($val, '@')) {
            [$name, $domain] = explode('@', $val);

            return substr($name, 0, 2).'***@'.$domain;
        }

        return substr($val, 0, 4).'***'.substr($val, -2);
    }
}
