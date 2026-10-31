<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseNotificationAndSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Notifications Listing and Unread Counter.
     */
    public function test_notifications_listing_and_unread_counter(): void
    {
        $user = User::factory()->create(['name' => 'Recipient User']);
        $actor = User::factory()->create(['name' => 'Sadia Islam']);

        // Create a database notification
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\PostReactionNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'New Reaction',
                'message' => 'Sadia Islam reacted to your photo.',
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'category' => 'likes',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unread count
        $countRes = $this->actingAs($user)->getJson('/api/v1/notifications/unread-count');
        $countRes->assertStatus(200);
        $this->assertEquals(1, $countRes->json('data.unread_count'));

        // List notifications
        $listRes = $this->actingAs($user)->getJson('/api/v1/notifications');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data'));
    }

    /**
     * 2. Test Mark Notification As Read and Mark All Read.
     */
    public function test_mark_as_read_and_mark_all_read(): void
    {
        $user = User::factory()->create();

        $uuid = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $uuid,
            'type' => 'App\Notifications\NewCommentNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'New Comment',
                'message' => 'Rahim Ahmed commented: Nice post!',
                'category' => 'comments',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mark single as read
        $readRes = $this->actingAs($user)->patchJson("/api/v1/notifications/{$uuid}/read");
        $readRes->assertStatus(200);
        $this->assertEquals(0, $readRes->json('data.unread_count'));

        // Mark all as read
        $readAllRes = $this->actingAs($user)->postJson('/api/v1/notifications/read-all');
        $readAllRes->assertStatus(200);
    }

    /**
     * 3. Test Notification Preferences Retrieval and Update.
     */
    public function test_notification_preferences(): void
    {
        $user = User::factory()->create();

        // Get preferences
        $prefRes = $this->actingAs($user)->getJson('/api/v1/notifications/preferences');
        $prefRes->assertStatus(200);

        // Update preferences
        $updateRes = $this->actingAs($user)->patchJson('/api/v1/notifications/preferences', [
            'push_enabled' => true,
            'likes' => true,
            'comments' => true,
        ]);
        $updateRes->assertStatus(200);
    }

    /**
     * 4. Test Global Search and Search History.
     */
    public function test_global_search_and_search_history(): void
    {
        $user = User::factory()->create(['name' => 'Searcher']);
        User::factory()->create(['name' => 'Sadia Islam', 'username' => 'sadia_islam']);

        // Execute global search
        $searchRes = $this->actingAs($user)->getJson('/api/v1/search?q=Sadia');
        $searchRes->assertStatus(200);

        // Get search history
        $historyRes = $this->actingAs($user)->getJson('/api/v1/search/history');
        $historyRes->assertStatus(200);

        // Clear search history
        $clearRes = $this->actingAs($user)->deleteJson('/api/v1/search/history');
        $clearRes->assertStatus(200);
    }
}
