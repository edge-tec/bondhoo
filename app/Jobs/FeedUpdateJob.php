<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FeedUpdateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $postId
    ) {
        $this->onQueue(QueueServiceInterface::QUEUE_DEFAULT);
    }

    public function handle(CacheServiceInterface $cacheService): void
    {
        $post = Post::find($this->postId);
        if (! $post) {
            return;
        }

        // Invalidate author's feed cache in Redis: feed:user:{id}
        $cacheService->forget("feed:user:{$post->user_id}");
        $cacheService->forget('feed:public');

        Log::info("Feed cache invalidated for post ID {$this->postId} (author: {$post->user_id})");
    }
}
