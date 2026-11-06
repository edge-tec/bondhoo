<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccountDeletionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_account_deletion_page_loads_successfully_without_auth(): void
    {
        $response = $this->get('/account/delete');

        $response->assertStatus(200);
        $response->assertSee('BONDHOO');
        $response->assertSee('অ্যাকাউন্ট ও সম্পর্কিত ডেটা ডিলিট করার নির্দেশিকা');
        $response->assertSee('Delete Account');
        $response->assertSee('Google Play', false);
    }

    public function test_public_deletion_request_submission_records_audit_log_for_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'user-to-delete@example.com',
        ]);

        $response = $this->post('/account/delete/request', [
            'email' => 'user-to-delete@example.com',
            'reason' => 'No longer using the platform',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'account.deletion_request_public',
        ]);
    }

    public function test_public_deletion_request_validates_email_format(): void
    {
        $response = $this->post('/account/delete/request', [
            'email' => 'invalid-email-address',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
