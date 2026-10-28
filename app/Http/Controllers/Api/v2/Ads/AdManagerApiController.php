<?php

namespace App\Http\Controllers\Api\v2\Ads;

use App\Http\Controllers\Controller;
use App\Services\Ads\AdsEngineService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * সেলফ-সার্ভ অ্যাড ম্যানেজার ও পোস্ট বুস্টিং কন্ট্রোলার:
 * ক্যাম্পেইন তৈরি, পোস্ট বুস্টিং, বাজেট ও টার্গেটিং, রিয়েলটাইম মেট্রিক্স,
 * ইমপ্রেশন ও ক্লিক কাউন্টিং এবং স্পনসরড পোস্ট ফিড হ্যান্ডেল করে।
 */
class AdManagerApiController extends Controller
{
    public function __construct(
        protected AdsEngineService $adsEngine
    ) {}

    /**
     * ইউজারের অ্যাড ক্যাম্পেইন তালিকা
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = $this->adsEngine->getUserCampaigns($request->user());

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * নির্দিষ্ট পোস্ট বুস্ট করা (Self-Serve Post Boost)
     */
    public function boost(Request $request, int $postId): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'total_budget' => ['required', 'numeric', 'min:100'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:90'],
            'objective' => ['nullable', 'string', 'in:reach,engagement,traffic,conversions'],
            'start_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:all,male,female'],
            'age_min' => ['nullable', 'integer', 'min:13', 'max:65'],
            'age_max' => ['nullable', 'integer', 'min:13', 'max:65'],
            'locations' => ['nullable', 'array'],
            'locations.*' => ['string'],
            'interests' => ['nullable', 'array'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_link' => ['nullable', 'url', 'max:500'],
            'pay_from_wallet' => ['nullable', 'boolean'],
        ]);

        try {
            $campaign = $this->adsEngine->boostPost($user, $postId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'পোস্টটি সফলভাবে বুস্ট করা হয়েছে এবং স্পনসরড ক্যাম্পেইন চালু হয়েছে!',
                'data' => $this->adsEngine->getCampaignAnalytics($user, $campaign->id),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * কাস্টম সাধারণ বিজ্ঞাপন ক্যাম্পেইন তৈরি
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'total_budget' => ['required', 'numeric', 'min:100'],
            'daily_budget' => ['required', 'numeric', 'min:50'],
            'objective' => ['nullable', 'string', 'in:reach,engagement,traffic,conversions'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'targeting_criteria' => ['nullable', 'array'],
            'ad_creatives' => ['nullable', 'array'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_link' => ['nullable', 'url', 'max:500'],
        ]);

        $campaign = $this->adsEngine->createCampaign($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'বিজ্ঞাপন ক্যাম্পেইন সফলভাবে তৈরি হয়েছে।',
            'data' => $campaign,
        ], 201);
    }

    /**
     * নির্দিষ্ট ক্যাম্পেইনের পারফরম্যান্স অ্যানালিটিক্স
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $analytics = $this->adsEngine->getCampaignAnalytics($user, $id);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * ক্যাম্পেইন সাময়িকভাবে পজ করা
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $campaign = $this->adsEngine->pauseCampaign($user, $id);

            return response()->json([
                'success' => true,
                'message' => 'ক্যাম্পেইন সাময়িকভাবে পজ করা হয়েছে।',
                'data' => $campaign,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * পজ থাকা ক্যাম্পেইন পুনরায় চালু করা
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $campaign = $this->adsEngine->resumeCampaign($user, $id);

            return response()->json([
                'success' => true,
                'message' => 'ক্যাম্পেইন পুনরায় চালু করা হয়েছে।',
                'data' => $campaign,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ইমপ্রেশন ট্র্যাকিং (ভিউ কাউন্ট ও CPM খরচ আপডেট)
     */
    public function recordImpression(Request $request, int $id): JsonResponse
    {
        $viewerId = $request->user()?->id;
        $result = $this->adsEngine->recordImpression($id, $viewerId);

        return response()->json([
            'success' => $result['recorded'] ?? false,
            'data' => $result,
        ]);
    }

    /**
     * ক্লিক ট্র্যাকিং (ক্লিক কাউন্ট ও CPC খরচ আপডেট)
     */
    public function recordClick(Request $request, int $id): JsonResponse
    {
        $viewerId = $request->user()?->id;
        $result = $this->adsEngine->recordClick($id, $viewerId);

        return response()->json([
            'success' => $result['recorded'] ?? false,
            'data' => $result,
        ]);
    }

    /**
     * ফিডের জন্য স্পনসরড বিজ্ঞাপনের তালিকা
     */
    public function feedAds(Request $request): JsonResponse
    {
        $limit = max(1, min(10, (int) $request->input('limit', 2)));
        $ads = $this->adsEngine->getSponsoredAdsForFeed($limit);

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }
}
