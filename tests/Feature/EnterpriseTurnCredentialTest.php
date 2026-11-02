<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Calling\CallingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EnterpriseTurnCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected CallingService $callingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizan_dev',
        ]);

        $this->callingService = app(CallingService::class);
    }

    public function test_get_ice_servers_returns_ephemeral_hmac_credentials_when_configured(): void
    {
        $testSecret = 'coturn_shared_secret_enterprise_2026';
        $testTurnUrl = 'turn.jugajug.com:3478';

        Config::set('services.turn.url', $testTurnUrl);
        Config::set('services.turn.secret', $testSecret);
        Config::set('services.turn.ttl', 7200);

        $iceServers = $this->callingService->getIceServers($this->user);

        $this->assertIsArray($iceServers);
        $this->assertGreaterThanOrEqual(2, count($iceServers));

        // Find the TURN server configuration
        $turnConfig = null;
        foreach ($iceServers as $server) {
            if (isset($server['username']) && isset($server['credential'])) {
                $turnConfig = $server;
                break;
            }
        }

        $this->assertNotNull($turnConfig, 'A TURN server configuration with username and credential must be returned.');

        // Validate URLs include UDP, TCP, and TLS (TURNS)
        $urls = $turnConfig['urls'];
        $hasUdp = false;
        $hasTcp = false;
        $hasTurns = false;

        foreach ($urls as $url) {
            if (str_contains($url, 'transport=udp')) {
                $hasUdp = true;
            }
            if (str_contains($url, 'turn:') && str_contains($url, 'transport=tcp')) {
                $hasTcp = true;
            }
            if (str_contains($url, 'turns:') && str_contains($url, 'transport=tcp')) {
                $hasTurns = true;
            }
        }

        $this->assertTrue($hasUdp, 'TURN URLs must include UDP transport.');
        $this->assertTrue($hasTcp, 'TURN URLs must include TCP transport.');
        $this->assertTrue($hasTurns, 'TURN URLs must include secure TLS (turns:) transport.');

        // Verify ephemeral username format: timestamp:userId
        $username = $turnConfig['username'];
        $this->assertStringContainsString(":{$this->user->id}", $username);

        $parts = explode(':', $username);
        $this->assertCount(2, $parts);
        $expiryTimestamp = (int) $parts[0];
        $this->assertGreaterThan(time(), $expiryTimestamp);
        $this->assertLessThanOrEqual(time() + 7205, $expiryTimestamp);

        // Verify cryptographic HMAC-SHA1 credential
        $expectedCredential = base64_encode(hash_hmac('sha1', $username, $testSecret, true));
        $this->assertEquals($expectedCredential, $turnConfig['credential']);
    }

    public function test_api_ice_servers_endpoint_returns_json_response_with_sanctum_auth(): void
    {
        Config::set('services.turn.url', 'turn.jugajug.com:3478');
        Config::set('services.turn.secret', 'test_secret_abc123');

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/calls/ice-servers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ice_servers' => [
                        '*' => [
                            'urls',
                        ],
                    ],
                ],
            ]);
    }

    public function test_fallback_openrelay_returned_when_turn_unconfigured(): void
    {
        Config::set('services.turn.url', null);
        Config::set('services.turn.secret', null);

        $iceServers = $this->callingService->getIceServers($this->user);

        $this->assertNotEmpty($iceServers);

        // STUN server should be present
        $this->assertArrayHasKey('urls', $iceServers[0]);
        $this->assertStringStartsWith('stun:', $iceServers[0]['urls'][0]);
    }
}
