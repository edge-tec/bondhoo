<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageEvent;
use App\Models\PageEventRsvp;
use App\Services\Page\EnterprisePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ ইভেন্ট ম্যানেজমেন্ট কন্ট্রোলার
 */
class PageEventV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের ইভেন্ট তালিকা প্রদর্শন
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $events = PageEvent::where('page_id', $page->id)
            ->where('status', 'published')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * নতুন পেজ ইভেন্ট তৈরি করা
     */
    public function store(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageEvents', $page)) {
            abort(403, 'You do not have permission to manage events for this page.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover_image_url' => ['nullable', 'string', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'is_online' => ['nullable', 'boolean'],
        ]);

        $event = $this->enterprisePageService->createEvent($page, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data' => $event,
        ], 201);
    }

    /**
     * ইভেন্টের বিস্তারিত তথ্য প্রদর্শন
     */
    public function show(Request $request, Page $page, int $id): JsonResponse
    {
        $event = PageEvent::where('page_id', $page->id)->findOrFail($id);

        $userRsvp = null;
        if ($user = $request->user()) {
            $userRsvp = PageEventRsvp::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->value('status');
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($event->toArray(), [
                'user_rsvp' => $userRsvp,
            ]),
        ]);
    }

    /**
     * ইভেন্টে RSVP প্রতিক্রিয়া নিবন্ধন
     */
    public function rsvp(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        $event = PageEvent::where('page_id', $page->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:going,interested,not_going'],
        ]);

        $status = $validated['status'];

        PageEventRsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['status' => $status]
        );

        $rsvpCount = PageEventRsvp::where('event_id', $event->id)->whereIn('status', ['going', 'interested'])->count();
        $goingCount = PageEventRsvp::where('event_id', $event->id)->where('status', 'going')->count();
        $interestedCount = PageEventRsvp::where('event_id', $event->id)->where('status', 'interested')->count();

        $event->update([
            'rsvp_count' => $rsvpCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "RSVP updated to '{$status}'.",
            'data' => [
                'status' => $status,
                'rsvp_count' => $rsvpCount,
                'going_count' => $goingCount,
                'interested_count' => $interestedCount,
            ],
        ]);
    }

    /**
     * ইভেন্ট ডিলিট করা
     */
    public function destroy(Request $request, Page $page, int $id): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageEvents', $page)) {
            abort(403, 'You do not have permission to delete events for this page.');
        }

        $event = PageEvent::where('page_id', $page->id)->findOrFail($id);
        $event->delete();

        $this->enterprisePageService->logAudit($page, $user, 'event.delete', 'PageEvent', $id);

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
