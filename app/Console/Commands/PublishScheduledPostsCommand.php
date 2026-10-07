<?php

namespace App\Console\Commands;

use App\Events\PagePostPublishedEvent;
use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publishes due scheduled posts for personal profiles, groups, and pages.';

    public function handle(): int
    {
        $duePosts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($duePosts->isEmpty()) {
            $this->info('0 scheduled post(s) published.');

            return Command::SUCCESS;
        }

        $publishedCount = 0;

        foreach ($duePosts as $post) {
            DB::transaction(function () use ($post, &$publishedCount) {
                $affected = Post::where('id', $post->id)
                    ->where('status', 'scheduled')
                    ->update(['status' => 'published']);

                if ($affected > 0) {
                    if ($post->page_id) {
                        Page::where('id', $post->page_id)->increment('posts_count');
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
                    }

                    if ($post->group_id) {
                        Group::where('id', $post->group_id)->increment('posts_count');
                    }

                    $publishedCount++;
                    Log::info("Scheduled post #{$post->id} published successfully.");
                }
            });
        }

        $this->info("{$publishedCount} scheduled post(s) published successfully.");

        return Command::SUCCESS;
    }
}
