<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\Group\GroupInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupInsightsController extends Controller
{
    public function __construct(
        protected GroupInsightsService $insightsService
    ) {}

    /**
     * 1. Overview Insights
     */
    public function overview(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $data = $this->insightsService->getOverview($group, $user, $period, $start, $end);

        return $this->successResponse(
            data: $data,
            message: 'Overview insights retrieved successfully.'
        );
    }

    /**
     * 2. Member Insights
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $data = $this->insightsService->getMemberInsights($group, $user, $period, $start, $end);

        return $this->successResponse(
            data: $data,
            message: 'Member insights retrieved successfully.'
        );
    }

    /**
     * 3. Content Insights
     */
    public function content(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $data = $this->insightsService->getContentInsights($group, $user, $period, $start, $end);

        return $this->successResponse(
            data: $data,
            message: 'Content insights retrieved successfully.'
        );
    }

    /**
     * 4. Audience Insights
     */
    public function audience(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $data = $this->insightsService->getAudienceInsights($group, $user, $period, $start, $end);

        return $this->successResponse(
            data: $data,
            message: 'Audience insights retrieved successfully.'
        );
    }

    /**
     * 5. Engagement Insights
     */
    public function engagement(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $data = $this->insightsService->getEngagementInsights($group, $user, $period, $start, $end);

        return $this->successResponse(
            data: $data,
            message: 'Engagement insights retrieved successfully.'
        );
    }

    /**
     * 6. Reports List
     */
    public function reports(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $data = $this->insightsService->getReports($group, $user);

        return $this->successResponse(
            data: $data,
            message: 'Group reports retrieved successfully.'
        );
    }

    /**
     * 7. Export Insights
     */
    public function export(Request $request, int $id): Response
    {
        $user = $request->user();
        $group = Group::findOrFail($id);

        $format = (string) $request->query('format', 'csv');
        $period = (string) $request->query('period', '30d');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $result = $this->insightsService->export($group, $user, $format, $period, $start, $end);

        if (strtolower($format) === 'json') {
            return $this->successResponse(
                data: $result,
                message: 'Insights exported successfully.'
            );
        }

        $filename = "group_{$group->id}_insights_{$period}.csv";

        return response($result, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
