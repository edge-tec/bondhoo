<?php

namespace App\Jobs;

use App\Events\GroupPostCreatedEvent;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledGroupPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): int
    {
        $posts = Post::whereNotNull('group_id')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with(['group', 'user.profile', 'media'])
            ->get();

        $publishedCount = 0;

        foreach ($posts as $post) {
            $post->update([
                'status' => 'published',
            ]);

            if ($post->group) {
                $post->group->increment('posts_count');
                try {
                    broadcast(new GroupPostCreatedEvent($post->group_id, $post->toResponseArray($post->user)))->toOthers();
                } catch (\Throwable $e) {
                    // Ignore broadcast exceptions in worker
                }
            }

            $publishedCount++;
        }

        return $publishedCount;
    }
}
