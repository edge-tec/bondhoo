<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Federation\ActivityPubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPubFederationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webfinger_returns_valid_jrd_json(): void
    {
        $response = $this->get('/.well-known/webfinger?resource=acct:testuser@jugajug.com');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'subject',
            'aliases',
            'links',
        ]);
        $this->assertSame('acct:testuser@jugajug.com', $response->json('subject'));
    }

    public function test_actor_profile_endpoint_returns_valid_activitypub_person(): void
    {
        $user = User::factory()->create(['username' => 'sheikh_hasib']);

        $response = $this->get("/api/v2/federation/actors/{$user->username}");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'Person',
            'preferredUsername' => 'sheikh_hasib',
        ]);
        $this->assertArrayHasKey('publicKey', $response->json());
    }

    public function test_inbound_federated_activity_processing(): void
    {
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://mastodon.social/users/dev/statuses/102/activity',
            'type' => 'Create',
            'actor' => 'https://mastodon.social/users/dev',
            'object' => [
                'id' => 'https://mastodon.social/users/dev/statuses/102',
                'type' => 'Note',
                'content' => 'Hello from federated mastodon to Jugajug Bangladesh!',
            ],
        ];

        $response = $this->postJson('/api/v2/federation/inbox', $payload);

        $response->assertStatus(202);
        $this->assertDatabaseHas('federated_activities', [
            'activity_id' => 'https://mastodon.social/users/dev/statuses/102/activity',
            'type' => 'Create',
            'direction' => 'inbound',
            'status' => 'processed',
        ]);
    }

    public function test_outbound_activity_broadcast(): void
    {
        $author = User::factory()->create(['username' => 'mizanur']);
        $service = app(ActivityPubService::class);

        $activity = $service->broadcastOutbound($author, [
            'id' => 'https://jugajug.com/posts/999',
            'type' => 'Note',
            'content' => 'Public post federated out to Mastodon',
        ]);

        $this->assertDatabaseHas('federated_activities', [
            'id' => $activity->id,
            'direction' => 'outbound',
            'type' => 'Create',
        ]);
    }
}
