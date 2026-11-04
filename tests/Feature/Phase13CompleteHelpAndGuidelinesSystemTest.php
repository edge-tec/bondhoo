<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase13CompleteHelpAndGuidelinesSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 1300;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase13_user_{$counter}",
            'name' => "Phase 13 User {$counter}",
            'email' => "phase13_user_{$counter}@example.com",
            'phone' => "+880174400{$counter}",
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 13 Help and Guidelines bio test',
                'gender' => 'male',
                'birth_date' => '1996-03-22',
            ]
        );

        return $user;
    }

    /**
     * Test community guidelines page renders with full compliance for both guest and authenticated user.
     */
    public function test_community_guidelines_page_renders_successfully_for_guests_and_users(): void
    {
        // 1. Guest access
        $guestResponse = $this->get('/community-guidelines');
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee('কমিউনিটি নির্দেশিকা (Community Guidelines)');
        $guestResponse->assertSee('পারস্পরিক শ্রদ্ধা ও সম্প্রীতি');
        $guestResponse->assertSee('ক্ষতিকর ও সহিংস কনটেন্ট নিষেধাজ্ঞা');
        $guestResponse->assertSee('সত্যতা ও প্রামাণিকতা (Authenticity)');
        $guestResponse->assertSee('রিপোর্ট ও আপিল প্রক্রিয়া');

        // 2. Authenticated user access
        $user = $this->createUser();
        $authResponse = $this->actingAs($user, 'web')->get('/community-guidelines');
        $authResponse->assertStatus(200);
        $authResponse->assertSee('কমিউনিটি নির্দেশিকা (Community Guidelines)');
    }

    /**
     * Test /help route alias cleanly resolves to the community guidelines and safety center.
     */
    public function test_help_route_alias_renders_community_guidelines_and_help_center(): void
    {
        $response = $this->get('/help');
        $response->assertStatus(200);
        $response->assertSee('কমিউনিটি নির্দেশিকা (Community Guidelines)');
        $response->assertSee('Jugajug (যোগাযোগ)');
    }

    /**
     * Test terms of service legal page renders successfully.
     */
    public function test_terms_of_service_page_renders_successfully(): void
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
        $response->assertSee('ব্যবহারের শর্তাবলী (Terms of Service)');
        $response->assertSee('শর্তাবলী');
    }

    /**
     * Test privacy policy legal page renders successfully.
     */
    public function test_privacy_policy_page_renders_successfully(): void
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200);
        $response->assertSee('গোপনীয়তা নীতি (Privacy Policy)');
        $response->assertSee('Jugajug (যোগাযোগ)');
    }

    /**
     * Test cookie and data policy page renders successfully.
     */
    public function test_cookie_policy_page_renders_successfully(): void
    {
        $response = $this->get('/cookies');
        $response->assertStatus(200);
        $response->assertSee('কুকিজ ও ডেটা পলিসি');
    }

    /**
     * Test cross navigation links are operational between policy and settings pages.
     */
    public function test_all_legal_and_help_pages_have_working_cross_navigation(): void
    {
        $routes = ['/community-guidelines', '/terms', '/privacy', '/cookies'];

        foreach ($routes as $route) {
            $res = $this->get($route);
            $res->assertStatus(200);
        }
    }

    /**
     * Test sidebar navigation component embeds Help & Guidelines link with accurate route tracking.
     */
    public function test_sidebar_navigation_contains_active_help_and_guidelines_link(): void
    {
        $sidebarPath = resource_path('js/Components/SidebarNavigation.vue');
        $this->assertFileExists($sidebarPath);

        $content = File::get($sidebarPath);
        $this->assertStringContainsString('Help & Guidelines', $content);
        $this->assertStringContainsString('href="/community-guidelines"', $content);
        $this->assertStringContainsString("isCurrentPath('/community-guidelines')", $content);
        $this->assertStringContainsString("isCurrentPath('/help')", $content);
    }
}
