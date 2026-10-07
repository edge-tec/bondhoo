<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EnterpriseSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SearchIndexTest — সার্চ ইন্ডেক্সিং, সাজেশন ও রি-ইন্ডেক্স টেস্ট
 */
class SearchIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * এন্টারপ্রাইজ সার্চ সার্ভিস দ্বারা কুয়েরি রেজাল্ট ও সাজেশন প্রাপ্তি যাচাই।
     */
    public function test_search_service_returns_matching_entities_and_suggestions(): void
    {
        $user = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur',
            'status' => 'active',
        ]);

        $service = app(EnterpriseSearchService::class);
        $results = $service->search('mizanur');

        $this->assertNotEmpty($results['users']);
        $this->assertEquals($user->id, $results['users'][0]->id);

        $suggestions = $service->getSuggestions('mizan');
        $this->assertContains('mizanur', $suggestions);
    }

    /**
     * সার্চ রি-ইন্ডেক্সিং কমান্ড ও এপিআই সফল হওয়া যাচাই।
     */
    public function test_search_reindex_command_and_api_execution(): void
    {
        User::factory()->count(3)->create();

        $this->artisan('jugajug:search-reindex')
            ->expectsOutputToContain('সার্চ রি-ইন্ডেক্সিং সফলভাবে সম্পন্ন হয়েছে!')
            ->assertExitCode(0);

        $admin = User::factory()->create();
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/search/reindex');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['indexed_users', 'indexed_posts', 'indexed_pages', 'indexed_groups'],
                'message',
            ]);
    }
}
