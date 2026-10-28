<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndLegalComplianceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Public Terms of service page is accessible by unauthenticated guests.
     */
    public function test_guest_can_access_terms_of_service(): void
    {
        $response = $this->get('/terms');
        $response->assertStatus(200)
            ->assertSee('ব্যবহারের শর্তাবলী');
    }

    /**
     * 2. Public Privacy Policy page is accessible by unauthenticated guests.
     */
    public function test_guest_can_access_privacy_policy(): void
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200)
            ->assertSee('গোপনীয়তা ও তথ্য সুরক্ষা নীতি')
            ->assertSee('Privacy Policy');
    }

    /**
     * 3. Public Cookies policy page is accessible by unauthenticated guests.
     */
    public function test_guest_can_access_cookie_policy(): void
    {
        $response = $this->get('/cookies');
        $response->assertStatus(200)
            ->assertSee('কুকি');
    }

    /**
     * 4. Public Community Guidelines page is accessible by unauthenticated guests.
     */
    public function test_guest_can_access_community_guidelines(): void
    {
        $response = $this->get('/community-guidelines');
        $response->assertStatus(200)
            ->assertSee('কমিউনিটি নির্দেশিকা')
            ->assertSee('Community Guidelines');
    }

    /**
     * 5. Dynamic XML Sitemap is generated with valid XML header and public URLs.
     */
    public function test_dynamic_sitemap_returns_valid_xml_with_public_urls(): void
    {
        $user = User::factory()->create([
            'username' => 'seo_test_user_'.uniqid(),
            'status' => 'active',
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'audience' => 'public',
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('/terms</loc>', $content);
        $this->assertStringContainsString('/privacy</loc>', $content);
        $this->assertStringContainsString('/community-guidelines</loc>', $content);
        $this->assertStringContainsString('/u/'.$user->username.'</loc>', $content);
        $this->assertStringContainsString('/p/'.$post->id.'</loc>', $content);
    }

    /**
     * 6. Robots.txt contains sitemap declaration and security disallows.
     */
    public function test_robots_txt_exists_and_declares_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /api/', $content);
        $this->assertStringContainsString('Sitemap: https://jugajug.com/sitemap.xml', $content);
    }

    /**
     * 7. OpenGraph meta tags and cookie consent elements are rendered on the app layout.
     */
    public function test_app_layout_renders_opengraph_and_cookie_consent(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user, 'web')->get('/dashboard');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('property="og:site_name" content="Jugajug (যোগাযোগ)"', $content);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $content);
        $this->assertStringContainsString('id="jugajug-cookie-banner"', $content);
        $this->assertStringContainsString('setJugajugCookieConsent', $content);
    }
}
