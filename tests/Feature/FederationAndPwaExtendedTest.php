<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use App\Services\Federation\ActivityPubService;
use App\Services\Story\StoryAIEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FederationAndPwaExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_actor_profile_caching(): void
    {
        $service = app(ActivityPubService::class);
        $remoteUri = 'https://mastodon.social/users/gargron';

        $actor1 = $service->getOrCacheRemoteActor($remoteUri);
        $actor2 = $service->getOrCacheRemoteActor($remoteUri);

        $this->assertSame($actor1->id, $actor2->id);
        $this->assertSame('gargron', $actor1->username);
        $this->assertSame('mastodon.social', $actor1->domain);
    }

    public function test_activitypub_outbox_endpoint_returns_collection(): void
    {
        $user = User::factory()->create(['username' => 'creator_bd']);

        $response = $this->get("/api/v2/federation/users/{$user->username}/outbox");

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'OrderedCollection',
        ]);
        $this->assertStringContainsString('outbox', $response->json('id'));
    }

    public function test_stories_ai_engine_ranks_and_attaches_stickers(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $user->id,
            'content' => 'Day in Coxs Bazar',
            'type' => 'text',
            'privacy' => 'public',
            'expires_at' => now()->addHours(20),
        ]);

        $engine = new StoryAIEngine;
        $interactive = $engine->attachInteractiveElement($story, 'poll', [
            'question' => 'Do you love beach sunsets?',
            'options' => ['Yes', 'Absolutely'],
        ]);

        $this->assertIsArray($interactive);
        $this->assertSame('poll', $interactive[0]['type']);

        $elements = $engine->getInteractiveElements($story);
        $this->assertCount(1, $elements);

        $ranked = $engine->rankStories(collect([$story]), $viewer);
        $this->assertCount(1, $ranked);
        $this->assertGreaterThan(0.0, $ranked->first()->ai_rank);
    }

    public function test_pwa_manifest_file_exists_and_is_valid_json(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertNotNull($json);
        $this->assertContains($json['short_name'], ['Bondhoo', 'Jugajug']);
        $this->assertSame('standalone', $json['display']);
    }

    public function test_pwa_service_worker_file_exists(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('jugajug-v2-cache', $content);
        $this->assertStringContainsString('addEventListener', $content);
    }

    public function test_kubernetes_helm_and_canary_manifests_exist(): void
    {
        $this->assertFileExists(base_path('k8s/canary-deployment.yaml'));
        $this->assertFileExists(base_path('k8s/statefulset-redis-cluster.yaml'));
        $this->assertFileExists(base_path('k8s/helm/Chart.yaml'));
        $this->assertFileExists(base_path('k8s/helm/values.yaml'));
    }
}
