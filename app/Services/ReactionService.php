<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReactionService
{
    public function __construct(
        protected CacheServiceInterface $cacheService,
        protected NotificationServiceInterface $notificationService,
        protected RealtimeServiceInterface $realtimeService
    ) {}

    /**
     * React to a Post or Comment. Toggles off if same type, changes type if different.
     */
    public function react(User $user, Model $target, string $type): array
    {
        $type = strtolower($type);
        if (! in_array($type, Reaction::TYPES, true)) {
            throw new InvalidArgumentException("Invalid reaction type [{$type}]. Allowed: ".implode(', ', Reaction::TYPES));
        }

        $reactableType = get_class($target);
        $reactableId = $target->id;

        return DB::transaction(function () use ($user, $target, $reactableType, $reactableId, $type) {
            $existing = Reaction::where('user_id', $user->id)
                ->where('reactable_type', $reactableType)
                ->where('reactable_id', $reactableId)
                ->first();

            $currentType = null;
            $reacted = false;

            if ($existing) {
                if ($existing->type === $type) {
                    // Toggle off (remove reaction)
                    $existing->delete();
                } else {
                    // Switch reaction type (e.g. from like to love)
                    $existing->update(['type' => $type]);
                    $currentType = $type;
                    $reacted = true;
                }
            } else {
                Reaction::create([
                    'user_id' => $user->id,
                    'reactable_type' => $reactableType,
                    'reactable_id' => $reactableId,
                    'type' => $type,
                ]);
                $currentType = $type;
                $reacted = true;

                // Notify post/comment author if reactor is not author
                if (isset($target->user_id) && $target->user_id !== $user->id) {
                    $this->notificationService->send(
                        recipientId: $target->user_id,
                        type: 'notification.reaction',
                        data: [
                            'actor_id' => $user->id,
                            'actor_name' => $user->name ?? $user->username,
                            'target_id' => $target->id,
                            'reaction_type' => $type,
                            'title' => 'New reaction on your content',
                            'message' => ($user->name ?? $user->username)." reacted with {$type}.",
                        ],
                        channels: ['database']
                    );
                }
            }

            // Recalculate total count and update denormalized field
            $totalCount = Reaction::where('reactable_type', $reactableType)
                ->where('reactable_id', $reactableId)
                ->count();

            if (isset($target->likes_count)) {
                $target->update(['likes_count' => $totalCount]);
            }

            // Sync Redis cache key: post:{id}:reactions
            if ($target instanceof Post) {
                $this->cacheService->set("post:{$target->id}:reactions", $totalCount, 3600);
            }

            $breakdown = Reaction::where('reactable_type', $reactableType)
                ->where('reactable_id', $reactableId)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // লাইভ পোস্ট চ্যানেলে রিয়েল-টাইম রিঅ্যাকশন আপডেট ব্রডকাস্ট করা
            if ($target instanceof Post) {
                $this->realtimeService->broadcastPostReaction($target->id, $totalCount, $breakdown);
            }

            return [
                'reacted' => $reacted,
                'type' => $currentType,
                'total_reactions' => $totalCount,
                'reactions_count' => $totalCount,
                'likes_count' => $totalCount,
                'breakdown' => $breakdown,
            ];
        });
    }

    /**
     * Get reaction breakdown and total count for target.
     */
    public function getSummary(Model $target): array
    {
        $reactableType = get_class($target);

        $breakdown = Reaction::where('reactable_type', $reactableType)
            ->where('reactable_id', $target->id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $total = array_sum($breakdown);

        return [
            'total' => $total,
            'total_reactions' => $total,
            'reactions_count' => $total,
            'likes_count' => $total,
            'breakdown' => $breakdown,
        ];
    }
}
