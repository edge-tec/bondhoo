<?php

namespace App\Console\Commands;

use App\Events\PagePostPublishedEvent;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * শিডিউলড পেজ পোস্ট পাবলিশার কমান্ড:
 * ব্যাকগ্রাউন্ডে ব্রাউজার বন্ধ থাকলেও নির্ধারিত সময়ে শিডিউলড পোস্ট স্বয়ংক্রিয়ভাবে প্রকাশ করে।
 */
class PublishScheduledPagePostsCommand extends Command
{
    protected $signature = 'pages:publish-scheduled';

    protected $description = 'Publishes due scheduled posts for enterprise social pages.';

    public function handle(): int
    {
        $duePosts = Post::whereNotNull('page_id')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($duePosts->isEmpty()) {
            $this->info('0 scheduled post(s) published successfully.');

            return Command::SUCCESS;
        }

        $publishedCount = 0;

        foreach ($duePosts as $post) {
            DB::transaction(function () use ($post, &$publishedCount) {
                $affected = Post::where('id', $post->id)
                    ->where('status', 'scheduled')
                    ->update([
                        'status' => 'published',
                    ]);

                if ($affected > 0) {
                    if ($post->page_id) {
                        Page::where('id', $post->page_id)->increment('posts_count');
                    }

                    $publishedCount++;

                    try {
                        broadcast(new PagePostPublishedEvent((int) $post->page_id, [
                            'id' => $post->id,
                            'content' => $post->content,
                            'status' => 'published',
                            'published_at' => now()->toIso8601String(),
                        ]));
                    } catch (\Throwable $e) {
                        Log::warning('Could not broadcast PagePostPublishedEvent: '.$e->getMessage());
                    }

                    Log::info("Scheduled post #{$post->id} published successfully for Page #{$post->page_id}.");
                }
            });
        }

        $this->info("{$publishedCount} scheduled post(s) published successfully.");

        return Command::SUCCESS;
    }
}
