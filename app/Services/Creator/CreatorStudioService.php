<?php

namespace App\Services\Creator;

use App\Models\CreatorEarning;
use App\Models\CreatorPayoutRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Marketplace\Payments\PaymentGatewayManager;
use App\Services\RealtimeService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ক্রিয়েটর আর্নিং, কয়েন ক্রয় ও ওয়ালেট পেআউট/ক্যাশআউট সার্ভিস:
 * ক্রিয়েটরদের মনিটাইজেশন, কয়েন প্যাকেজ পারচেজ, লাইভ ও পোস্ট ভার্চুয়াল গিফটিং,
 * আর্নিং টু ওয়ালেট রূপান্তর এবং বিকাশ/নগদ/ব্যাংক ক্যাশআউট ম্যানেজ করে।
 */
class CreatorStudioService
{
    /**
     * Platform commission rate (15%).
     */
    public const PLATFORM_FEE_PERCENT = 15.0;

    /**
     * Value of 1 coin/star in BDT (1 Coin = 1.20 BDT gross, creator gets ~ 1.02 BDT net).
     */
    public const COIN_VALUE_BDT = 1.20;

    /**
     * Minimum payout threshold in BDT.
     */
    public const MIN_PAYOUT_AMOUNT_BDT = 500.00;

    public function __construct(
        protected PaymentGatewayManager $paymentManager,
        protected RealtimeService $realtime
    ) {}

    /**
     * Standard coin packages catalog available for purchase.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCoinPackages(): array
    {
        return [
            [
                'id' => 'coin_pkg_100',
                'name' => '100 কয়েন প্যাক',
                'coins' => 100,
                'price' => 120.00,
                'currency' => 'BDT',
                'badge' => null,
            ],
            [
                'id' => 'coin_pkg_500',
                'name' => '500 কয়েন প্যাক',
                'coins' => 500,
                'price' => 600.00,
                'currency' => 'BDT',
                'badge' => 'জনপ্রিয়',
            ],
            [
                'id' => 'coin_pkg_1000',
                'name' => '1,000 কয়েন প্যাক (বোনাস সহ)',
                'coins' => 1050,
                'price' => 1150.00,
                'currency' => 'BDT',
                'badge' => '৫% বোনাস',
            ],
            [
                'id' => 'coin_pkg_5000',
                'name' => '5,000 কয়েন প্যাক (ভিআইপি)',
                'coins' => 5500,
                'price' => 5500.00,
                'currency' => 'BDT',
                'badge' => '১০% বোনাস',
            ],
            [
                'id' => 'coin_pkg_10000',
                'name' => '10,000 কয়েন মেগা প্যাক',
                'coins' => 11500,
                'price' => 10500.00,
                'currency' => 'BDT',
                'badge' => '১৫% বোনাস',
            ],
        ];
    }

    /**
     * Find specific coin package by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findCoinPackage(string $packageId): ?array
    {
        foreach ($this->getCoinPackages() as $package) {
            if ($package['id'] === $packageId) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Credit purchased coins directly to user's wallet.
     */
    public function creditPurchasedCoins(User $user, int $coins, float $amountPaid, string $gateway, string $transactionRef): Wallet
    {
        return DB::transaction(function () use ($user, $coins, $amountPaid, $gateway, $transactionRef): Wallet {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0.00,
                    'pending_balance' => 0.00,
                    'coins_balance' => 0,
                    'currency' => 'BDT',
                    'status' => Wallet::STATUS_ACTIVE,
                ]
            );

            // Row lock
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $newCoinsBalance = $lockedWallet->coins_balance + $coins;

            $lockedWallet->update([
                'coins_balance' => $newCoinsBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_COIN_PURCHASE,
                'amount' => $amountPaid,
                'coins' => $coins,
                'currency' => 'BDT',
                'reference_id' => $transactionRef,
                'description' => "{$gateway} পেমেন্টের মাধ্যমে {$coins} কয়েন ক্রয় সম্পন্ন।",
                'balance_after' => $lockedWallet->balance,
                'coins_after' => $newCoinsBalance,
            ]);

            Log::info("CreatorStudioService: User {$user->id} purchased {$coins} coins via {$gateway}.", [
                'trx_ref' => $transactionRef,
                'amount' => $amountPaid,
            ]);

            return $lockedWallet->fresh();
        });
    }

    /**
     * Send virtual gift from a user/viewer to a creator with atomic wallet ledger entries.
     *
     * @return array{success: bool, gift_type: string, coins: int, sender_coins_left: int, creator_net_earned: float}
     *
     * @throws Exception
     */
    public function sendGift(User $sender, User $creator, string $giftType, int $coins, ?string $context = null, ?int $contextId = null): array
    {
        if ($sender->id === $creator->id) {
            throw new InvalidArgumentException('নিজের প্রোফাইলে বা স্ট্রিমে নিজে গিফট পাঠানো যাবে না।');
        }

        if ($coins <= 0) {
            throw new InvalidArgumentException('কয়েনের সংখ্যা অবশ্যই ১ বা তার বেশি হতে হবে।');
        }

        return DB::transaction(function () use ($sender, $creator, $giftType, $coins, $context, $contextId): array {
            // 1. Lock Sender Wallet
            $senderWallet = Wallet::firstOrCreate(
                ['user_id' => $sender->id],
                ['coins_balance' => 0, 'balance' => 0.00, 'currency' => 'BDT', 'status' => Wallet::STATUS_ACTIVE]
            );

            $lockedSenderWallet = Wallet::where('id', $senderWallet->id)->lockForUpdate()->firstOrFail();

            if (! $lockedSenderWallet->hasSufficientCoins($coins)) {
                throw new Exception("পর্যাপ্ত কয়েন নেই। আপনার ব্যালেন্স: {$lockedSenderWallet->coins_balance} কয়েন। অনুগ্রহ করে কয়েন রিচার্জ করুন।");
            }

            // Deduct coins from sender
            $senderCoinsAfter = $lockedSenderWallet->coins_balance - $coins;
            $lockedSenderWallet->update(['coins_balance' => $senderCoinsAfter]);

            WalletTransaction::create([
                'wallet_id' => $lockedSenderWallet->id,
                'user_id' => $sender->id,
                'type' => WalletTransaction::TYPE_GIFT_SENT,
                'amount' => 0.00,
                'coins' => -$coins,
                'currency' => 'BDT',
                'reference_id' => "GIFT_{$giftType}_".Str::random(8),
                'description' => "ক্রিয়েটর {$creator->name}-কে '{$giftType}' গিফট পাঠানো হয়েছে।",
                'balance_after' => $lockedSenderWallet->balance,
                'coins_after' => $senderCoinsAfter,
            ]);

            // 2. Lock Creator Wallet & Credit Net Monetization
            $creatorWallet = Wallet::firstOrCreate(
                ['user_id' => $creator->id],
                ['coins_balance' => 0, 'balance' => 0.00, 'currency' => 'BDT', 'status' => Wallet::STATUS_ACTIVE]
            );

            $lockedCreatorWallet = Wallet::where('id', $creatorWallet->id)->lockForUpdate()->firstOrFail();

            // Monetary calculation: 1 coin = 1.20 BDT gross, 15% platform fee = 1.02 BDT net
            $grossBdt = round($coins * self::COIN_VALUE_BDT, 2);
            $platformFeeBdt = round($grossBdt * (self::PLATFORM_FEE_PERCENT / 100), 2);
            $netEarnedBdt = round($grossBdt - $platformFeeBdt, 2);

            $creatorBalanceAfter = $lockedCreatorWallet->balance + $netEarnedBdt;
            $lockedCreatorWallet->update(['balance' => $creatorBalanceAfter]);

            WalletTransaction::create([
                'wallet_id' => $lockedCreatorWallet->id,
                'user_id' => $creator->id,
                'type' => WalletTransaction::TYPE_GIFT_RECEIVED,
                'amount' => $netEarnedBdt,
                'coins' => $coins,
                'currency' => 'BDT',
                'reference_id' => "EARN_{$giftType}_".Str::random(8),
                'description' => "{$sender->name} এর কাছ থেকে '{$giftType}' গিফট বাবদ নেট আয় ক্রেডিট।",
                'balance_after' => $creatorBalanceAfter,
                'coins_after' => $lockedCreatorWallet->coins_balance,
            ]);

            // 3. Update Monthly Creator Earnings Table
            $this->awardStars($creator, $coins);

            // 4. Real-time broadcast if in stream or messenger
            if ($context === 'live_stream' && $contextId) {
                $payload = [
                    'stream_id' => $contextId,
                    'gift_type' => $giftType,
                    'coins' => $coins,
                    'sender' => ['id' => $sender->id, 'name' => $sender->name],
                    'creator' => ['id' => $creator->id, 'name' => $creator->name],
                    'timestamp' => now()->toIso8601String(),
                ];

                $this->realtime->broadcast("live.stream_{$contextId}", 'live.gift.received', $payload);
            }

            return [
                'success' => true,
                'gift_type' => $giftType,
                'coins' => $coins,
                'sender_coins_left' => $senderCoinsAfter,
                'creator_net_earned' => $netEarnedBdt,
            ];
        });
    }

    /**
     * Credit stars to creator and calculate monthly monetary value in creator_earnings table.
     */
    public function awardStars(User $creator, int $starsCount): CreatorEarning
    {
        $period = now()->format('Y-m');
        $starValueUsd = 0.01;
        $earnedAmount = round($starsCount * $starValueUsd, 2);

        $earning = CreatorEarning::firstOrCreate(
            ['user_id' => $creator->id, 'period_month' => $period],
            [
                'stars_received' => 0,
                'stars_amount' => 0.00,
                'subscription_amount' => 0.00,
                'ad_rev_share' => 0.00,
                'gross_total' => 0.00,
                'platform_fee' => 0.00,
                'net_payout' => 0.00,
                'payout_status' => 'pending',
            ]
        );

        $newStars = $earning->stars_received + $starsCount;
        $newStarsAmount = $earning->stars_amount + $earnedAmount;
        $gross = $newStarsAmount + $earning->subscription_amount + $earning->ad_rev_share;
        $fee = round($gross * (self::PLATFORM_FEE_PERCENT / 100), 2);
        $net = $gross - $fee;

        $earning->update([
            'stars_received' => $newStars,
            'stars_amount' => $newStarsAmount,
            'gross_total' => $gross,
            'platform_fee' => $fee,
            'net_payout' => $net,
        ]);

        return $earning;
    }

    /**
     * Creator submits a cashout / payout withdrawal request to bKash, Nagad, Rocket, or Bank.
     *
     * @param  array<string, mixed>  $details
     *
     * @throws Exception
     */
    public function requestPayout(User $creator, float $amount, string $paymentMethod, string $accountNumber, array $details = []): CreatorPayoutRequest
    {
        if ($amount < self::MIN_PAYOUT_AMOUNT_BDT) {
            throw new InvalidArgumentException('সর্বনিম্ন ক্যাশআউট রিকোয়েস্ট পরিমাণ '.self::MIN_PAYOUT_AMOUNT_BDT.' BDT।');
        }

        $validMethods = [
            CreatorPayoutRequest::METHOD_BKASH,
            CreatorPayoutRequest::METHOD_NAGAD,
            CreatorPayoutRequest::METHOD_ROCKET,
            CreatorPayoutRequest::METHOD_BANK,
        ];

        if (! in_array(strtolower($paymentMethod), $validMethods, true)) {
            throw new InvalidArgumentException("অসমর্থিত পেমেন্ট মেথড: {$paymentMethod}");
        }

        return DB::transaction(function () use ($creator, $amount, $paymentMethod, $accountNumber, $details): CreatorPayoutRequest {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $creator->id],
                ['balance' => 0.00, 'pending_balance' => 0.00, 'coins_balance' => 0, 'status' => Wallet::STATUS_ACTIVE]
            );

            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if (! $lockedWallet->hasSufficientBalance($amount)) {
                throw new Exception("পর্যাপ্ত ওয়ালেট ব্যালেন্স নেই। আপনার বর্তমান ক্যাশআউটযোগ্য ব্যালেন্স: {$lockedWallet->balance} BDT।");
            }

            // Deduct from available balance, hold in pending_balance
            $newBalance = $lockedWallet->balance - $amount;
            $newPending = $lockedWallet->pending_balance + $amount;

            $lockedWallet->update([
                'balance' => $newBalance,
                'pending_balance' => $newPending,
            ]);

            $payout = CreatorPayoutRequest::create([
                'user_id' => $creator->id,
                'amount' => $amount,
                'currency' => 'BDT',
                'payment_method' => strtolower($paymentMethod),
                'account_number' => $accountNumber,
                'account_name' => $details['account_name'] ?? null,
                'bank_name' => $details['bank_name'] ?? null,
                'branch_name' => $details['branch_name'] ?? null,
                'routing_number' => $details['routing_number'] ?? null,
                'status' => CreatorPayoutRequest::STATUS_PENDING,
            ]);

            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'user_id' => $creator->id,
                'type' => WalletTransaction::TYPE_PAYOUT_HOLD,
                'amount' => -$amount,
                'coins' => 0,
                'currency' => 'BDT',
                'reference_id' => "PAYOUT_REQ_{$payout->id}",
                'description' => "{$paymentMethod} ({$accountNumber})-এ ক্যাশআউট রিকোয়েস্ট পেন্ডিং রয়েছে।",
                'balance_after' => $newBalance,
                'coins_after' => $lockedWallet->coins_balance,
            ]);

            return $payout;
        });
    }

    /**
     * Admin processes payout request (Approve/Complete or Reject).
     *
     * @throws Exception
     */
    public function processPayout(int $payoutRequestId, User $admin, string $action, ?string $trxRef = null, ?string $rejectionReason = null): CreatorPayoutRequest
    {
        return DB::transaction(function () use ($payoutRequestId, $admin, $action, $trxRef, $rejectionReason): CreatorPayoutRequest {
            $payout = CreatorPayoutRequest::where('id', $payoutRequestId)->lockForUpdate()->firstOrFail();

            if ($payout->status !== CreatorPayoutRequest::STATUS_PENDING && $payout->status !== CreatorPayoutRequest::STATUS_PROCESSING) {
                throw new Exception("এই ক্যাশআউট রিকোয়েস্টটি ইতোমধ্যেই '{$payout->status}' অবস্থায় রয়েছে।");
            }

            $wallet = Wallet::where('user_id', $payout->user_id)->lockForUpdate()->firstOrFail();

            if ($action === 'complete' || $action === 'approve') {
                // Deduct from pending balance
                $newPending = max(0.00, $wallet->pending_balance - $payout->amount);
                $wallet->update(['pending_balance' => $newPending]);

                $payout->update([
                    'status' => CreatorPayoutRequest::STATUS_COMPLETED,
                    'transaction_reference' => $trxRef ?: ('TRX_'.Str::upper(Str::random(10))),
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $payout->user_id,
                    'type' => WalletTransaction::TYPE_PAYOUT_SETTLED,
                    'amount' => 0.00,
                    'coins' => 0,
                    'currency' => 'BDT',
                    'reference_id' => "PAYOUT_DONE_{$payout->id}",
                    'description' => "ক্যাশআউট সফলভাবে সম্পন্ন হয়েছে (TrxID: {$payout->transaction_reference})।",
                    'balance_after' => $wallet->balance,
                    'coins_after' => $wallet->coins_balance,
                ]);
            } elseif ($action === 'reject') {
                // Refund from pending_balance back to active balance
                $newPending = max(0.00, $wallet->pending_balance - $payout->amount);
                $newBalance = $wallet->balance + $payout->amount;

                $wallet->update([
                    'balance' => $newBalance,
                    'pending_balance' => $newPending,
                ]);

                $payout->update([
                    'status' => CreatorPayoutRequest::STATUS_REJECTED,
                    'rejection_reason' => $rejectionReason ?: 'ক্যাশআউট রিকোয়েস্ট বাতিল করা হয়েছে। ব্যালেন্স ফেরত দেওয়া হয়েছে।',
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $payout->user_id,
                    'type' => WalletTransaction::TYPE_PAYOUT_REFUND,
                    'amount' => $payout->amount,
                    'coins' => 0,
                    'currency' => 'BDT',
                    'reference_id' => "PAYOUT_REFUND_{$payout->id}",
                    'description' => "ক্যাশআউট বাতিল: ব্যালেন্স রিফান্ড হয়েছে ({$payout->rejection_reason})।",
                    'balance_after' => $newBalance,
                    'coins_after' => $wallet->coins_balance,
                ]);
            } else {
                throw new InvalidArgumentException("অসমর্থিত অ্যাকশন: {$action}");
            }

            return $payout->fresh();
        });
    }

    /**
     * Get Creator Studio dashboard overview with full wallet, coins, and monetization analytics.
     *
     * @return array<string, mixed>
     */
    public function getOverview(User $creator): array
    {
        $period = now()->format('Y-m');
        $earning = CreatorEarning::where('user_id', $creator->id)->where('period_month', $period)->first();

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $creator->id],
            ['balance' => 0.00, 'pending_balance' => 0.00, 'coins_balance' => 0, 'currency' => 'BDT', 'status' => Wallet::STATUS_ACTIVE]
        );

        $pendingPayoutsTotal = (float) CreatorPayoutRequest::where('user_id', $creator->id)
            ->where('status', CreatorPayoutRequest::STATUS_PENDING)
            ->sum('amount');

        $lifetimePayoutsTotal = (float) CreatorPayoutRequest::where('user_id', $creator->id)
            ->where('status', CreatorPayoutRequest::STATUS_COMPLETED)
            ->sum('amount');

        return [
            'creator_id' => $creator->id,
            'creator_name' => $creator->name,
            'month' => $period,
            'stars_count' => $earning?->stars_received ?? 0,
            'net_payout_usd' => (float) ($earning?->net_payout ?? 0),
            'wallet' => [
                'cashout_balance' => (float) $wallet->balance,
                'pending_payout_balance' => (float) $wallet->pending_balance,
                'coins_balance' => (int) $wallet->coins_balance,
                'currency' => $wallet->currency ?: 'BDT',
                'status' => $wallet->status,
                'can_cashout' => (float) $wallet->balance >= self::MIN_PAYOUT_AMOUNT_BDT,
                'min_cashout_limit' => self::MIN_PAYOUT_AMOUNT_BDT,
            ],
            'monthly_earnings' => [
                'stars_count' => $earning?->stars_received ?? 0,
                'stars_revenue_bdt' => (float) ($earning?->stars_amount ?? 0),
                'subscription_revenue_bdt' => (float) ($earning?->subscription_amount ?? 0),
                'ad_revenue_bdt' => (float) ($earning?->ad_rev_share ?? 0),
                'gross_revenue_bdt' => (float) ($earning?->gross_total ?? 0),
                'platform_fee_bdt' => (float) ($earning?->platform_fee ?? 0),
                'net_payout_bdt' => (float) ($earning?->net_payout ?? 0),
                'payout_status' => $earning?->payout_status ?? 'pending',
            ],
            'lifetime_payouts_completed_bdt' => $lifetimePayoutsTotal,
            'pending_payouts_requested_bdt' => $pendingPayoutsTotal,
        ];
    }

    /**
     * Get Creator payout requests history with pagination.
     */
    public function getPayoutHistory(User $creator, int $perPage = 15): LengthAwarePaginator
    {
        return CreatorPayoutRequest::where('user_id', $creator->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
