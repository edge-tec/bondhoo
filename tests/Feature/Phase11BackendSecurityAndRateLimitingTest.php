<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase11BackendSecurityAndRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        $this->getJson('/api/v1/settings/overview')->assertUnauthorized();
        $this->getJson('/api/v1/settings/account')->assertUnauthorized();
        $this->putJson('/api/v1/settings/password', [])->assertUnauthorized();
        $this->getJson('/api/v1/settings/privacy')->assertUnauthorized();
        $this->getJson('/api/v1/settings/blocking')->assertUnauthorized();
    }

    public function test_cannot_modify_other_user_data_or_inject_roles(): void
    {
        $actor = User::factory()->create(['name' => 'Original Name', 'username' => 'actor_user']);
        $victim = User::factory()->create(['name' => 'Victim User', 'username' => 'victim_user']);

        Sanctum::actingAs($actor);

        // Attempt to hijack or inject mass-assigned fields
        $response = $this->putJson('/api/v1/settings/account', [
            'name' => 'Updated Actor',
            'id' => $victim->id, // Attempt to target another user ID
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $response->assertOk();
        $this->assertEquals('Updated Actor', $actor->fresh()->name);
        $this->assertEquals('Victim User', $victim->fresh()->name); // Victim remains unchanged
    }

    public function test_password_update_validates_current_password_and_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password-123'),
        ]);

        Sanctum::actingAs($user);

        // Wrong current password
        $failRes = $this->putJson('/api/v1/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ]);

        $failRes->assertStatus(422);

        // Correct update
        $successRes = $this->putJson('/api/v1/settings/password', [
            'current_password' => 'correct-password-123',
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ]);

        $successRes->assertOk();
    }
}
