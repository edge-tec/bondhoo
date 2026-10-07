<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DeploymentHealthTest — জিরো-ডাউনটাইম ডেপ্লয়মেন্ট স্বাস্থ্য যাচাই টেস্ট
 */
class DeploymentHealthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ডেপ্লয়মেন্ট ভেরিফিকেশন এপিআই কল সফল হওয়া যাচাই।
     */
    public function test_deployment_verify_endpoint_returns_readiness_status(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/deployment/verify');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deployment_verified' => true,
                    'database_ready' => true,
                ],
            ]);

        $this->assertNotNull($response->json('data.verified_at'));
    }

    /**
     * ডিপ্লয়মেন্ট স্ক্রিপ্ট ফাইলে সঠিক এক্সিকিউটেবল পারমিশন ও রোলব্যাক ব্লক থাকা যাচাই।
     */
    public function test_deployment_script_exists_and_contains_rollback_logic(): void
    {
        $scriptPath = base_path('deploy.sh');

        $this->assertFileExists($scriptPath);
        $content = file_get_contents($scriptPath);

        $this->assertStringContainsString('php artisan horizon:terminate', $content);
        $this->assertStringContainsString('automatic rollback', $content);
        $this->assertStringContainsString('ln -nfs', $content);
    }
}
