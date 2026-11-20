<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatPoll;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Services\Messenger\EnterpriseMessengerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerEnterpriseApiController extends Controller
{
    public function __construct(
        protected EnterpriseMessengerService $enterpriseService
    ) {}

    // =========================================================================
    // 1. POLLS & VOTING
    // =========================================================================

    public function createPoll(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:200'],
            'is_multiple_choice' => ['nullable', 'boolean'],
            'is_anonymous' => ['nullable', 'boolean'],
            'can_change_vote' => ['nullable', 'boolean'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        $poll = $this->enterpriseService->createPoll(
            $conversation,
            $request->user(),
            $validated['question'],
            $validated['options'],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Poll created successfully.',
            'data' => $poll->toResponseArray($request->user()),
        ], 201);
    }

    public function votePoll(Request $request, int $id): JsonResponse
    {
        $poll = ChatPoll::with(['conversation', 'options.votes.user'])->findOrFail($id);

        if (! $request->has('option_ids') && $request->filled('option_id')) {
            $request->merge(['option_ids' => [(int) $request->input('option_id')]]);
        }

        $validated = $request->validate([
            'option_ids' => ['required', 'array', 'min:1'],
            'option_ids.*' => ['required', 'integer'],
        ]);

        try {
            $pollData = $this->enterpriseService->castPollVote(
                $poll,
                $request->user(),
                $validated['option_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded successfully.',
            'data' => $pollData,
        ]);
    }

    public function closePoll(Request $request, int $id): JsonResponse
    {
        $poll = ChatPoll::with(['conversation'])->findOrFail($id);
        try {
            $closedPoll = $this->enterpriseService->closePoll($poll, $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Poll closed successfully.',
            'data' => $closedPoll->toResponseArray($request->user()),
        ]);
    }

    public function getPoll(Request $request, int $id): JsonResponse
    {
        $poll = ChatPoll::with(['creator.profile', 'options.votes.user.profile', 'conversation'])->findOrFail($id);

        if (! $poll->conversation->hasParticipant($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $poll->toResponseArray($request->user()),
        ]);
    }

    // =========================================================================
    // 2. DISAPPEARING MESSAGES TIMER
    // =========================================================================

    public function setDisappearingTimer(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:7776000'],
        ]);

        $updated = $this->enterpriseService->setDisappearingTimer(
            $conversation,
            $request->user(),
            $validated['duration_seconds']
        );

        return response()->json([
            'success' => true,
            'message' => 'Disappearing messages timer updated.',
            'data' => [
                'conversation_id' => $updated->id,
                'disappearing_duration' => (int) ($updated->settings['disappearing_duration'] ?? 0),
            ],
        ]);
    }

    // =========================================================================
    // 3. SCHEDULED MESSAGES
    // =========================================================================

    public function getScheduledMessages(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        if (! $conversation->hasParticipant($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $messages = ScheduledMessage::where('conversation_id', $id)
            ->where('sender_id', $request->user()->id)
            ->where('status', ScheduledMessage::STATUS_PENDING)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (ScheduledMessage $m) => $m->toResponseArray());

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function scheduleMessage(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $scheduled = $this->enterpriseService->scheduleMessage(
            $conversation,
            $request->user(),
            $validated['body'],
            $scheduledAt,
            $validated['timezone'] ?? 'UTC',
            $validated['metadata'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Message scheduled successfully.',
            'data' => $scheduled->toResponseArray(),
        ], 201);
    }

    public function cancelScheduledMessage(Request $request, int $id): JsonResponse
    {
        $this->enterpriseService->cancelScheduledMessage($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Scheduled message cancelled.',
        ]);
    }

    // =========================================================================
    // 4. LOCATION SHARING
    // =========================================================================

    public function shareLocation(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'live_duration_seconds' => ['nullable', 'integer', 'min:60', 'max:86400'],
        ]);

        $message = $this->enterpriseService->shareLocation(
            $conversation,
            $request->user(),
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['address'] ?? null,
            $validated['live_duration_seconds'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Location shared successfully.',
            'data' => $message->toResponseArray($request->user()),
        ], 201);
    }

    public function stopLiveLocation(Request $request, int $id, int $messageId): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $message = $this->enterpriseService->stopLiveLocation($conversation, $request->user(), $messageId);

        return response()->json([
            'success' => true,
            'message' => 'Live location sharing stopped.',
            'data' => $message->toResponseArray($request->user()),
        ]);
    }

    // =========================================================================
    // 5. CHAT THEMES & CUSTOMIZATION
    // =========================================================================

    public function updateTheme(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'theme_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bubble_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'wallpaper_url' => ['nullable', 'string', 'max:500'],
            'gradient' => ['nullable', 'string', 'max:100'],
            'sound_preset' => ['nullable', 'string', 'max:50'],
        ]);

        $updated = $this->enterpriseService->updateTheme($conversation, $request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Chat theme updated successfully.',
            'data' => $updated->settings['theme'] ?? [],
        ]);
    }

    public function resetTheme(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $this->enterpriseService->resetTheme($conversation, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Chat theme reset to default.',
        ]);
    }

    // =========================================================================
    // 6. VOICE NOTE TRANSCRIPTION & VIDEO NOTES
    // =========================================================================

    public function transcribeVoice(Request $request, int $id): JsonResponse
    {
        $message = Message::with(['conversation'])->findOrFail($id);

        if (! $message->conversation->hasParticipant($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $result = $this->enterpriseService->transcribeVoiceMessage($message);

        return response()->json([
            'success' => true,
            'message' => 'Voice message transcribed.',
            'data' => $result,
        ]);
    }

    public function sendVideoNote(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $validated = $request->validate([
            'video_url' => ['nullable', 'string', 'url'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:51200'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $videoUrl = $validated['video_url'] ?? null;
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('video_notes', 'public');
            $videoUrl = '/storage/'.$path;
        }

        if (! $videoUrl) {
            return response()->json(['success' => false, 'message' => 'Video file or URL is required.'], 422);
        }

        $message = $this->enterpriseService->sendVideoNote(
            $conversation,
            $request->user(),
            $videoUrl,
            $validated['duration'] ?? 15
        );

        return response()->json([
            'success' => true,
            'message' => 'Video note sent successfully.',
            'data' => $message->toResponseArray($request->user()),
        ], 201);
    }

    // =========================================================================
    // 7. IN-CHAT MESSAGE TRANSLATION
    // =========================================================================

    public function translateMessage(Request $request, int $id): JsonResponse
    {
        $message = Message::with(['conversation'])->findOrFail($id);

        if (! $message->conversation->hasParticipant($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'target_language' => ['required', 'string', 'in:bn,en,es,ar,fr,de,hi'],
        ]);

        $result = $this->enterpriseService->translateMessage($message, $validated['target_language']);

        return response()->json([
            'success' => true,
            'message' => 'Message translated successfully.',
            'data' => $result,
        ]);
    }

    // =========================================================================
    // 8. BROADCAST CHANNELS
    // =========================================================================

    public function createChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
            'comments_enabled' => ['nullable', 'boolean'],
            'reactions_enabled' => ['nullable', 'boolean'],
        ]);

        $channel = $this->enterpriseService->createChannel(
            $request->user(),
            $validated['title'],
            $validated['description'] ?? null,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Channel created successfully.',
            'data' => $channel->toResponseArray($request->user()),
        ], 201);
    }

    public function joinChannel(Request $request, int $id): JsonResponse
    {
        $channel = Conversation::where('type', Conversation::TYPE_CHANNEL)->findOrFail($id);
        $participant = $this->enterpriseService->joinChannel($channel, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Subscribed to channel successfully.',
            'data' => [
                'channel_id' => $channel->id,
                'role' => $participant->role,
            ],
        ]);
    }

    public function leaveChannel(Request $request, int $id): JsonResponse
    {
        $channel = Conversation::where('type', Conversation::TYPE_CHANNEL)->findOrFail($id);
        $this->enterpriseService->leaveChannel($channel, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed from channel.',
        ]);
    }
}
