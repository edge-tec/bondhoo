<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReportController extends Controller
{
    /**
     * Submit a report against a post, comment, or user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => ['required', 'string', 'in:post,comment,user'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:spam,harassment,hate_speech,false_information,violence,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $modelClass = match ($validated['reportable_type']) {
            'post' => Post::class,
            'comment' => Comment::class,
            'user' => User::class,
            default => throw new InvalidArgumentException('Invalid target type.'),
        };

        // Ensure target exists
        $modelClass::findOrFail($validated['reportable_id']);

        // Check if user already submitted a pending report for this target
        $existing = Report::where('reporter_id', $request->user()->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $validated['reportable_id'])
            ->where('status', Report::STATUS_PENDING)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a pending report for this item.',
            ], 422);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. Our moderation team will review it.',
            'data' => $report,
        ], 201);
    }

    /**
     * List all reports (Admin/Moderator only).
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = Report::with(['reporter.profile', 'reviewer']);

        if ($status) {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Review/action a report (Admin/Moderator only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:reviewed,dismissed,actioned'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $report->admin_notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully.',
            'data' => $report,
        ]);
    }
}
