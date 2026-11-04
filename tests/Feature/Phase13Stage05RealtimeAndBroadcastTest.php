<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Page;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Mockery;
use Pusher\Pusher;
use Tests\TestCase;

class Phase13Stage05RealtimeAndBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected Group $privateGroup;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $pusher = Mockery::mock(Pusher::class);
        $pusher->shouldReceive('authorizeChannel')->andReturn(json_encode(['auth' => 'test-auth-signature']));
        $broadcaster = new PusherBroadcaster($pusher);
        Broadcast::extend('mock_pusher', fn () => $broadcaster);
        config([
            'broadcasting.connections.mock_pusher' => ['driver' => 'mock_pusher'],
            'broadcasting.default' => 'mock_pusher',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->userA = User::factory()->create(['name' => 'Alice']);
        $this->userB = User::factory()->create(['name' => 'Bob']);

        $this->privateGroup = Group::create([
            'name' => 'Realtime Private Group',
            'slug' => 'realtime-private-group',
            'creator_id' => $this->userA->id,
            'privacy' => Group::PRIVACY_PRIVATE,
        ]);

        GroupMember::create([
            'group_id' => $this->privateGroup->id,
            'user_id' => $this->userA->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->page = Page::create([
            'name' => 'Broadcast Test Page',
            'slug' => 'broadcast-test-page',
            'category' => 'Technology',
            'owner_id' => $this->userA->id,
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Private User Channel Authentication: User B CANNOT authenticate to User A's channel
     */
    public function test_user_cannot_authenticate_to_another_users_private_channel(): void
    {
        $response = $this->actingAs($this->userB)->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$this->userA->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 2. User CAN authenticate to their own private channel
     */
    public function test_user_can_authenticate_to_own_private_channel(): void
    {
        $response = $this->actingAs($this->userA)->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$this->userA->id}",
            'socket_id' => '1234.5678',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['auth']);
    }

    /**
     * 3. Private Page Admin Channel: Non-admin cannot authenticate to page admin broadcast
     */
    public function test_non_admin_cannot_authenticate_to_page_admin_channel(): void
    {
        $response = $this->actingAs($this->userB)->postJson('/broadcasting/auth', [
            'channel_name' => "private-page.{$this->page->id}.admin",
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 4. Page Owner CAN authenticate to page admin channel
     */
    public function test_page_owner_can_authenticate_to_page_admin_channel(): void
    {
        $response = $this->actingAs($this->userA)->postJson('/broadcasting/auth', [
            'channel_name' => "private-page.{$this->page->id}.admin",
            'socket_id' => '1234.5678',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['auth']);
    }
}
