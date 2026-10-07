<?php

namespace Tests\Feature;

use App\Jobs\DispatchNotificationJob;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_retrieve_notifications(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Create sample notification in database
        $notificationId = Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'notification.welcome',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Welcome to Jugajug!', 'message' => 'Your account is ready.']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'id' => $notificationId,
                        'type' => 'notification.welcome',
                    ],
                ],
            ]);
    }

    public function test_user_can_get_unread_notification_count(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test 1']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test 2']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 2,
                ],
            ]);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $notificationId = Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notificationId,
                    'read' => true,
                ],
            ]);

        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated_count' => 1,
                ],
            ]);

        $unreadRemaining = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadRemaining);
    }

    public function test_comment_triggers_notification_job_for_post_author(): void
    {
        Queue::fake();

        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Post to comment on',
        ]);

        $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Great work!',
            ]);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($author) {
            return $job->recipientId === $author->id && $job->type === 'notification.comment';
        });
    }

    public function test_reaction_triggers_notification_job_for_post_author(): void
    {
        Queue::fake();

        $author = User::factory()->create();
        $reactor = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Post to react to',
        ]);

        $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        Queue::assertPushed(DispatchNotificationJob::class, function ($job) use ($author) {
            return $job->recipientId === $author->id && $job->type === 'notification.reaction';
        });
    }

    public function test_user_can_filter_notifications_by_category(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Create social notification
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.comment',
            'category' => 'social',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Social Notif']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create security notification
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.security.login',
            'category' => 'security',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Security Notif']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications?category=security');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('security', $data[0]['category']);
    }

    public function test_user_can_filter_notifications_by_unread_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Unread
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.unread',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Unread']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Read
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'notification.read',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Read']),
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications?filter=unread');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertFalse($data[0]['is_read']);
    }

    public function test_user_can_delete_single_notification(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $id = Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'To Delete']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/notifications/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'deleted' => true,
                ],
            ]);

        $this->assertNull(DB::table('notifications')->where('id', $id)->first());
    }

    public function test_user_cannot_delete_another_users_notification_idor(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('test')->plainTextToken;

        $id = Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user2->id,
            'data' => json_encode(['title' => 'User 2 Notif']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->deleteJson("/api/v1/notifications/{$id}");

        $response->assertStatus(404);
        $this->assertNotNull(DB::table('notifications')->where('id', $id)->first());
    }

    public function test_user_cannot_mark_another_users_notification_as_read_idor(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('test')->plainTextToken;

        $id = Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'notification.test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user2->id,
            'data' => json_encode(['title' => 'User 2 Notif']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->putJson("/api/v1/notifications/{$id}/read");

        $response->assertStatus(404);
        $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_user_can_get_and_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $getRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications/preferences');

        $getRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'email_notifications',
                    'push_notifications',
                ],
            ]);

        $updateRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/v1/notifications/preferences', [
                'email_notifications' => false,
                'push_notifications' => true,
                'comment_alerts' => true,
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email_notifications' => false,
                    'push_notifications' => true,
                    'comment_alerts' => true,
                ],
            ]);
    }

    public function test_dispatch_notification_job_intelligent_grouping(): void
    {
        $recipient = User::factory()->create();
        $cacheService = app(CacheServiceInterface::class);
        $queueService = app(QueueServiceInterface::class);
        $realtimeService = app(RealtimeServiceInterface::class);

        $job1 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.reaction',
            data: [
                'group_key' => 'post_reaction_999',
                'actor_name' => 'মায়িশা',
                'message' => 'মায়িশা আপনার পোস্টে প্রতিক্রিয়া জানিয়েছেন।',
            ],
            channels: ['database']
        );
        $job1->handle($cacheService, $queueService, $realtimeService);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'group_key' => 'post_reaction_999',
        ]);

        $initialCount = DB::table('notifications')->where('notifiable_id', $recipient->id)->count();
        $this->assertEquals(1, $initialCount);

        // Dispatch second reaction with same group_key
        $job2 = new DispatchNotificationJob(
            recipientId: $recipient->id,
            type: 'notification.reaction',
            data: [
                'group_key' => 'post_reaction_999',
                'actor_name' => 'রাকিব',
                'message' => 'রাকিব আপনার পোস্টে প্রতিক্রিয়া জানিয়েছেন।',
            ],
            channels: ['database']
        );
        $job2->handle($cacheService, $queueService, $realtimeService);

        // Count should remain 1 due to intelligent grouping/deduplication
        $afterCount = DB::table('notifications')->where('notifiable_id', $recipient->id)->count();
        $this->assertEquals(1, $afterCount);

        $notif = DB::table('notifications')->where('notifiable_id', $recipient->id)->first();
        $data = json_decode($notif->data, true);
        $this->assertEquals(2, $data['group_count']);
        $this->assertContains('রাকিব', $data['actors']);
    }

    public function test_authenticated_user_can_view_notifications_web_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->get('/notifications');

        $response->assertStatus(200)
            ->assertSee('নোটিফিকেশন সেন্টার');
    }
}
