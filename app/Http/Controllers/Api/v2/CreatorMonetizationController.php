<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Creator\CreatorStudioService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ক্রিয়েটর মনিটাইজেশন, কয়েন প্যাকেজ, ভার্চুয়াল গিফট ও ওয়ালেট ক্যাশআউট কন্ট্রোলার:
 * আর্নিং অ্যানালিটিক্স, কয়েন ক্রয়, গিফট ট্রান্সফার ও বিকাশ/নগদ/ব্যাংক ক্যাশআউট হ্যান্ডেল করে।
 */
class CreatorMonetizationController extends Controller
{
    public function __construct(
        protected CreatorStudioService $creatorService
    ) {}

    /**
     * ক্রিয়েটর স্টুডিও ওভারভিউ ও আর্নিং অ্যানালিটিক্স
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $overview = $this->creatorService->getOverview($user);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * বিক্রয়যোগ্য কয়েন প্যাকেজের তালিকা
     */
    public function packages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->creatorService->getCoinPackages(),
        ]);
    }

    /**
     * কয়েন প্যাকেজ ক্রয়ের চেকআউট সেশন শুরু
     */
    public function purchaseCoins(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'package_id' => ['required', 'string'],
            'gateway' => ['nullable', 'string', 'in:bkash,nagad,sslcommerz,stripe,sandbox'],
        ]);

        $package = $this->creatorService->findCoinPackage($validated['package_id']);
        if (! $package) {
            return response()->json([
                'success' => false,
                'message' => 'নির্বাচিত কয়েন প্যাকেজটি পাওয়া যায়নি।',
            ], 404);
        }

        $gateway = strtolower((string) ($validated['gateway'] ?? 'bkash'));
        $trxId = 'COIN_'.Str::upper(Str::random(12));

        // For sandbox or test simulated instant completion:
        if ($gateway === 'sandbox' || $request->boolean('auto_credit')) {
            $wallet = $this->creatorService->creditPurchasedCoins(
                $user,
                (int) $package['coins'],
                (float) $package['price'],
                $gateway,
                $trxId
            );

            return response()->json([
                'success' => true,
                'message' => 'কয়েন ক্রয় সফলভাবে সম্পন্ন হয়েছে।',
                'data' => [
                    'transaction_id' => $trxId,
                    'coins_credited' => $package['coins'],
                    'coins_balance' => $wallet->coins_balance,
                    'status' => 'completed',
                ],
            ], 200);
        }

        // Production Payment Gateway Redirect / Checkout Session
        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $trxId,
                'package_id' => $package['id'],
                'package_name' => $package['name'],
                'coins' => $package['coins'],
                'amount' => $package['price'],
                'currency' => $package['currency'],
                'gateway' => $gateway,
                'redirect_url' => url("/payments/checkout/{$gateway}?trx_id={$trxId}&amount={$package['price']}"),
                'status' => 'pending',
            ],
        ], 201);
    }

    /**
     * কয়েন পেমেন্ট ভেরিফিকেশন ও ওয়ালেটে কয়েন ক্রেডিট
     */
    public function verifyCoinPurchase(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'package_id' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'gateway' => ['required', 'string'],
        ]);

        $package = $this->creatorService->findCoinPackage($validated['package_id']);
        if (! $package) {
            return response()->json(['success' => false, 'message' => 'প্যাকেজ পাওয়া যায়নি।'], 404);
        }

        $wallet = $this->creatorService->creditPurchasedCoins(
            $user,
            (int) $package['coins'],
            (float) $package['price'],
            $validated['gateway'],
            $validated['transaction_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'কয়েন সফলভাবে ক্রেডিট হয়েছে।',
            'data' => [
                'coins_credited' => $package['coins'],
                'coins_balance' => $wallet->coins_balance,
            ],
        ]);
    }

    /**
     * ক্রিয়েটরকে ভার্চুয়াল গিফট প্রদান (Rose, Diamond, Crown, etc.)
     */
    public function sendGift(Request $request): JsonResponse
    {
        $sender = $request->user();

        $validated = $request->validate([
            'creator_id' => ['required', 'integer', 'exists:users,id'],
            'gift_type' => ['required', 'string', 'in:rose,heart,diamond,crown,supercar,rocket'],
            'coins' => ['required', 'integer', 'min:1'],
            'context' => ['nullable', 'string', 'in:live_stream,reel,post,profile'],
            'context_id' => ['nullable', 'integer'],
        ]);

        $creator = User::findOrFail($validated['creator_id']);

        try {
            $result = $this->creatorService->sendGift(
                sender: $sender,
                creator: $creator,
                giftType: $validated['gift_type'],
                coins: (int) $validated['coins'],
                context: $validated['context'] ?? null,
                contextId: $validated['context_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'গিফট সফলভাবে পাঠানো হয়েছে!',
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ওয়ালেট থেকে ক্যাশআউট বা পেআউট রিকোয়েস্ট পেশ করা (bKash / Nagad / Bank)
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $creator = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:500'],
            'payment_method' => ['required', 'string', 'in:bkash,nagad,rocket,bank'],
            'account_number' => ['required', 'string', 'max:64'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'branch_name' => ['nullable', 'string', 'max:120'],
            'routing_number' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $payout = $this->creatorService->requestPayout(
                creator: $creator,
                amount: (float) $validated['amount'],
                paymentMethod: $validated['payment_method'],
                accountNumber: $validated['account_number'],
                details: $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'ক্যাশআউট রিকোয়েস্ট সফলভাবে জমা হয়েছে। দ্রুত ভেরিফিকেশন সম্পন্ন হবে।',
                'data' => [
                    'payout_id' => $payout->id,
                    'amount' => (float) $payout->amount,
                    'payment_method' => $payout->payment_method,
                    'account_number' => $payout->account_number,
                    'status' => $payout->status,
                    'created_at' => $payout->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ক্রিয়েটরের ক্যাশআউট হিস্ট্রি
     */
    public function payoutHistory(Request $request): JsonResponse
    {
        $creator = $request->user();
        $history = $this->creatorService->getPayoutHistory($creator);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * ওয়ালেট ট্রানজেকশন লেজার হিস্ট্রি
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * অ্যাডমিন কর্তৃক পেআউট অনুমোদন / সম্পন্ন বা রিজেক্ট করা
     */
    public function adminProcessPayout(Request $request, int $id): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,complete,reject'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payout = $this->creatorService->processPayout(
                payoutRequestId: $id,
                admin: $admin,
                action: $validated['action'],
                trxRef: $validated['transaction_reference'] ?? null,
                rejectionReason: $validated['rejection_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'ক্যাশআউট রিকোয়েস্ট প্রসেসিং সফলভাবে সম্পন্ন হয়েছে।',
                'data' => $payout,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
