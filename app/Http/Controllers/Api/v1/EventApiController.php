<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventApiController extends Controller
{
    /**
     * GET /api/v1/events
     *
     * List events with filters and current user's RSVP status.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $query = Event::with(['creator.profile', 'attendees.user.profile']);

        if ($request->has('location_type') && in_array($request->query('location_type'), ['venue', 'online', 'hybrid'], true)) {
            $query->where('location_type', $request->query('location_type'));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $events = $query->latest('start_time')->paginate(20);

        $userId = $user?->id;
        $items = collect($events->items())->map(function (Event $event) use ($userId): array {
            $userRsvp = $userId
                ? $event->attendees->firstWhere('user_id', $userId)?->status
                : null;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_time' => $event->start_time?->toIso8601String(),
                'start_time_formatted' => $event->start_time?->locale('bn')->translatedFormat('l, d F, Y • h:i A'),
                'end_time' => $event->end_time?->toIso8601String(),
                'location_type' => $event->location_type,
                'address' => $event->address,
                'online_link' => $event->online_link,
                'cover_image' => $event->cover_image,
                'going_count' => (int) ($event->going_count ?? $event->attendees->where('status', 'going')->count()),
                'interested_count' => (int) ($event->interested_count ?? $event->attendees->where('status', 'interested')->count()),
                'user_rsvp' => $userRsvp,
                'creator' => [
                    'id' => $event->creator?->id,
                    'name' => $event->creator?->name,
                    'avatar' => $event->creator?->avatar_url ?? $event->creator?->profile?->avatar_url,
                ],
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'events' => $items,
            ],
            'events' => $items,
            'meta' => [
                'total' => $events->total(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
            'message' => 'ইভেন্ট তালিকা সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/events
     *
     * Create a new community event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'location_type' => 'required|string|in:venue,online,hybrid',
            'address' => 'nullable|string|max:255',
            'online_link' => 'nullable|url|max:255',
            'cover_image' => 'nullable|string|max:500',
            'max_attendees' => 'nullable|integer|min:1',
        ]);

        /** @var User $user */
        $user = $request->user();

        $event = Event::create([
            'creator_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'location_type' => $validated['location_type'],
            'address' => $validated['address'] ?? null,
            'online_link' => $validated['online_link'] ?? null,
            'cover_image' => $validated['cover_image'] ?? null,
            'max_attendees' => $validated['max_attendees'] ?? null,
            'going_count' => 1,
            'interested_count' => 0,
        ]);

        // Creator automatically attends as going
        EventAttendee::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'going',
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'event' => $event->load(['creator.profile']),
            ],
            'message' => 'নতুন ইভেন্ট সফলভাবে তৈরি হয়েছে! 🎉',
        ], 201);
    }

    /**
     * POST /api/v1/events/{id}/rsvp
     *
     * Toggle or update RSVP to an event.
     */
    public function rsvp(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'required|string|in:going,interested,declined',
        ]);

        $status = $validated['status'];
        $attendee = EventAttendee::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($attendee) {
            if ($attendee->status === $status) {
                // Toggle off
                $attendee->delete();
                $status = null;
            } else {
                $attendee->update(['status' => $status]);
            }
        } else {
            EventAttendee::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);
        }

        // Recalculate counts
        $goingCount = EventAttendee::where('event_id', $event->id)->where('status', 'going')->count();
        $interestedCount = EventAttendee::where('event_id', $event->id)->where('status', 'interested')->count();

        $event->update([
            'going_count' => $goingCount,
            'interested_count' => $interestedCount,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'event_id' => $event->id,
                'user_rsvp' => $status,
                'going_count' => $goingCount,
                'interested_count' => $interestedCount,
            ],
            'message' => $status ? 'ইভেন্ট আরএসভিপি সফলভাবে সংরক্ষিত হয়েছে!' : 'আরএসভিপি প্রত্যাহার করা হয়েছে।',
        ]);
    }
}
