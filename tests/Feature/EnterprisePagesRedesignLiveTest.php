<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterprisePagesRedesignLiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $member;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create([
            'name' => 'Enterprise Director',
            'username' => 'enterprise_dir',
            'email' => 'director@enterprise.com',
        ]);

        $this->member = User::factory()->create([
            'name' => 'Team Contributor',
            'username' => 'team_contrib',
            'email' => 'contrib@enterprise.com',
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Apex Cloud Solutions',
            'slug' => 'apex-cloud-solutions',
            'username' => 'apexcloud',
            'category' => 'Technology & Software',
            'bio' => 'Empowering modern business with next-gen cloud infra.',
            'description' => 'Comprehensive enterprise cloud consulting and architecture.',
            'website' => 'https://apexcloud.example.com',
            'email' => 'hello@apexcloud.example.com',
            'phone' => '+8801900000000',
            'address' => 'Banani Commercial Area, Road 11',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 125,
            'posts_count' => 12,
            'is_verified' => true,
        ]);
    }

    public function test_pages_dashboard_renders_executive_overview_metrics_and_tabs(): void
    {
        $response = $this->actingAs($this->owner)->get('/pages');

        $response->assertStatus(200);
        $response->assertSee('পেইজ ও পাবলিক এন্টারপ্রাইজ হাব');
        $response->assertSee('নতুন পেইজ তৈরি করুন');
        $response->assertSee('Apex Cloud Solutions');
        $response->assertSee('Technology & Software');
        $response->assertSee('ওভারভিউ ও সব পেইজ');
        $response->assertSee('আমার পেইজসমূহ');
        $response->assertSee('আমন্ত্রণসমূহ');
    }

    public function test_pages_dashboard_displays_pending_team_invitations(): void
    {
        $token = Str::random(40);
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->member->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'invited',
            'invitation_token' => $token,
        ]);

        $response = $this->actingAs($this->member)->get('/pages');

        $response->assertStatus(200);
        $response->assertSee('Apex Cloud Solutions');
        $response->assertSee('গ্রহণ করুন');
        $response->assertSee('প্রত্যাখ্যান');
    }

    public function test_user_can_accept_team_invitation_via_token(): void
    {
        $token = Str::random(40);
        $membership = PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->member->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'invited',
            'invitation_token' => $token,
        ]);

        $response = $this->actingAs($this->member)->postJson('/api/v2/pages/team/accept', [
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('page_members', [
            'id' => $membership->id,
            'status' => 'active',
            'invitation_token' => null,
        ]);
    }

    public function test_user_can_decline_team_invitation_via_token(): void
    {
        $token = Str::random(40);
        $membership = PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->member->id,
            'role' => Page::ROLE_MODERATOR,
            'status' => 'invited',
            'invitation_token' => $token,
        ]);

        $response = $this->actingAs($this->member)->postJson('/api/v2/pages/team/decline', [
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('page_members', [
            'id' => $membership->id,
        ]);
    }

    public function test_public_page_view_renders_profile_tabs_and_meta(): void
    {
        $response = $this->actingAs($this->member)->get("/pages/{$this->page->slug}");

        $response->assertStatus(200);
        $response->assertSee('Apex Cloud Solutions');
        $response->assertSee('Technology & Software');
        $response->assertSee('ফলো করুন');
        $response->assertSee('বার্তা পাঠান');
        $response->assertSee('শেয়ার');
    }

    public function test_realtime_follow_toggle_updates_count_and_status(): void
    {
        // Follow
        $followResponse = $this->actingAs($this->member)->postJson("/api/v1/pages/{$this->page->id}/follow");
        $followResponse->assertStatus(200);
        $followResponse->assertJsonPath('data.is_following', true);
        $this->assertEquals(126, $followResponse->json('data.followers_count'));

        // Unfollow
        $unfollowResponse = $this->actingAs($this->member)->postJson("/api/v1/pages/{$this->page->id}/follow");
        $unfollowResponse->assertStatus(200);
        $unfollowResponse->assertJsonPath('data.is_following', false);
        $this->assertEquals(125, $unfollowResponse->json('data.followers_count'));
    }
}
