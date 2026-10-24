<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageCategory;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 8 — Secure Page Roles, Team Management & Permissions Test Suite
 */
class PageRolesPermissionsAndTeamManagementPart8Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $manager;

    protected User $editor;

    protected User $contentManager;

    protected User $moderator;

    protected User $analyst;

    protected User $viewer;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Company', 'slug' => 'company', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Enterprise', 'slug' => 'enterprise', 'is_active' => true]);

        // Users
        $this->owner = User::factory()->create(['name' => 'Page Sovereign', 'username' => 'pagesovereign', 'email' => 'sovereign@example.com']);
        $this->admin = User::factory()->create(['name' => 'Page Admin', 'username' => 'pageadmin', 'email' => 'admin@example.com']);
        $this->manager = User::factory()->create(['name' => 'Operations Manager', 'username' => 'opsmanager', 'email' => 'ops@example.com']);
        $this->editor = User::factory()->create(['name' => 'Content Editor', 'username' => 'contenteditor', 'email' => 'editor@example.com']);
        $this->contentManager = User::factory()->create(['name' => 'Content Publisher', 'username' => 'contentpub', 'email' => 'pub@example.com']);
        $this->moderator = User::factory()->create(['name' => 'Community Moderator', 'username' => 'commmod', 'email' => 'mod@example.com']);
        $this->analyst = User::factory()->create(['name' => 'Insights Analyst', 'username' => 'insightsanalyst', 'email' => 'analyst@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Restricted Viewer', 'username' => 'restviewer', 'email' => 'viewer@example.com']);
        $this->stranger = User::factory()->create(['name' => 'Random User', 'username' => 'randomuser', 'email' => 'stranger@example.com']);

        // Base Page
        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'Jugajug Enterprise Systems',
            'slug' => 'jugajug-enterprise',
            'username' => 'jugajugenterprise',
            'bio' => 'Enterprise social technology platform.',
            'description' => 'Jugajug Enterprise develops mission critical social systems.',
            'category' => 'Enterprise',
            'sub_category' => 'Security',
            'website' => 'https://jugajug.example.com',
            'email' => 'info@jugajug.example.com',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
        ]);

        // Assign team members
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->admin->id, 'role' => Page::ROLE_ADMIN, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->manager->id, 'role' => Page::ROLE_MANAGER, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->editor->id, 'role' => Page::ROLE_EDITOR, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->contentManager->id, 'role' => Page::ROLE_CONTENT_MANAGER, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->moderator->id, 'role' => Page::ROLE_MODERATOR, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->analyst->id, 'role' => Page::ROLE_ANALYST, 'status' => 'active', 'joined_at' => now()]);
        PageMember::create(['page_id' => $this->page->id, 'user_id' => $this->viewer->id, 'role' => Page::ROLE_VIEWER, 'status' => 'active', 'joined_at' => now()]);
    }

    /**
     * 1. Page Owner has full authority across all operations
     */
    public function test_page_owner_has_full_authority_over_page(): void
    {
        $this->actingAs($this->owner);

        // Edit page details
        $res = $this->putJson("/pages/{$this->page->slug}/details", [
            'name' => 'Jugajug Global Technologies',
            'category' => 'Technology',
            'bio' => 'Updated bio by owner',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Manage settings
        $res = $this->putJson("/pages/{$this->page->slug}/settings", [
            'settings' => ['messaging' => ['auto_reply_enabled' => true]],
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Publish post
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Official Owner Announcement',
            'status' => 'published',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // View analytics
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/analytics/overview");
        $res->assertOk()->assertJson(['success' => true]);

        // Invite team member
        $targetUser = User::factory()->create();
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $targetUser->email,
            'role' => 'editor',
        ]);
        $res->assertOk()->assertJson(['success' => true]);
    }

    /**
     * 2. Administrator has operational authority but cannot transfer ownership or delete page
     */
    public function test_admin_has_operational_authority_but_cannot_transfer_ownership_or_delete_page(): void
    {
        $this->actingAs($this->admin);

        // Admin CAN edit page info
        $res = $this->putJson("/pages/{$this->page->slug}/details", [
            'name' => 'Jugajug Enterprise Systems',
            'category' => 'Enterprise',
            'bio' => 'Updated bio by admin',
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Admin CAN manage settings
        $res = $this->putJson("/pages/{$this->page->slug}/settings", [
            'settings' => ['privacy' => ['public_email' => false]],
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        // Admin CAN publish content
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Post published by Admin',
            'status' => 'published',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // Admin CAN delete posts
        $post = Post::factory()->create(['page_id' => $this->page->id, 'user_id' => $this->owner->id]);
        $res = $this->deleteJson("/api/v2/pages/{$this->page->id}/posts/{$post->id}");
        $res->assertOk()->assertJson(['success' => true]);

        // Admin CANNOT delete the page (403)
        $res = $this->deleteJson("/pages/{$this->page->slug}");
        $res->assertForbidden();

        // Admin CANNOT delete the page via API (403)
        $res = $this->deleteJson("/api/v2/pages/{$this->page->id}");
        $res->assertForbidden();

        // Admin CANNOT transfer ownership via Web (403)
        $res = $this->postJson("/pages/{$this->page->slug}/transfer-ownership", [
            'identifier' => $this->editor->email,
        ]);
        $res->assertForbidden();

        // Admin CANNOT transfer ownership via API (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/transfer-ownership", [
            'new_owner_id' => $this->editor->id,
        ]);
        $res->assertForbidden();
    }

    /**
     * 3. Editor and Content Manager can manage posts but cannot manage team, settings, or delete page
     */
    public function test_editor_and_content_manager_permission_boundaries(): void
    {
        // Testing Editor
        $this->actingAs($this->editor);

        // Editor CAN create posts
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Editorial article content',
            'status' => 'published',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // Editor CANNOT update page settings (403)
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/settings", [
            'messaging' => ['auto_reply_enabled' => false],
        ]);
        $res->assertForbidden();

        // Editor CANNOT invite team members (403)
        $newUser = User::factory()->create();
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $newUser->email,
            'role' => 'editor',
        ]);
        $res->assertForbidden();

        // Editor CANNOT delete page (403)
        $res = $this->deleteJson("/pages/{$this->page->slug}");
        $res->assertForbidden();

        // Testing Content Manager
        $this->actingAs($this->contentManager);

        // Content Manager CAN create posts
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Content manager announcement',
            'status' => 'published',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // Content Manager CANNOT invite team members (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $newUser->email,
            'role' => 'viewer',
        ]);
        $res->assertForbidden();
    }

    /**
     * 4. Moderator can moderate comments and block users but cannot create posts or manage team/settings
     */
    public function test_moderator_permission_boundaries(): void
    {
        $this->actingAs($this->moderator);

        // Moderator CAN delete offensive comment
        $post = Post::factory()->create(['page_id' => $this->page->id, 'user_id' => $this->owner->id]);
        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $this->stranger->id,
            'body' => 'Inappropriate content',
        ]);

        $res = $this->deleteJson("/pages/comments/{$comment->id}");
        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);

        // Moderator CAN block malicious user
        $badUser = User::factory()->create();
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/moderation/block", [
            'target_user_id' => $badUser->id,
            'reason' => 'Spam violations',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // Moderator CANNOT create posts (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Unauthorized moderator post',
            'status' => 'published',
        ]);
        $res->assertForbidden();

        // Moderator CANNOT update settings (403)
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/settings", [
            'messaging' => ['auto_reply_enabled' => false],
        ]);
        $res->assertForbidden();

        // Moderator CANNOT invite team members (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $this->stranger->email,
            'role' => 'viewer',
        ]);
        $res->assertForbidden();
    }

    /**
     * 5. Analyst can only view analytics and cannot create content or manage team
     */
    public function test_analyst_can_only_view_analytics(): void
    {
        $this->actingAs($this->analyst);

        // Analyst CAN view analytics overview
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/analytics/overview");
        $res->assertOk()->assertJson(['success' => true]);

        // Analyst CAN view audience insights
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/analytics/audience");
        $res->assertOk()->assertJson(['success' => true]);

        // Analyst CANNOT create posts (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Analyst attempting to post',
            'status' => 'published',
        ]);
        $res->assertForbidden();

        // Analyst CANNOT moderate comments (403)
        $post = Post::factory()->create(['page_id' => $this->page->id, 'user_id' => $this->owner->id]);
        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $this->stranger->id,
            'body' => 'Harmless comment',
        ]);
        $res = $this->deleteJson("/pages/comments/{$comment->id}");
        $res->assertForbidden();

        // Analyst CANNOT manage team (403)
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $this->stranger->email,
            'role' => 'viewer',
        ]);
        $res->assertForbidden();
    }

    /**
     * 6. Viewer has strictly read-only access
     */
    public function test_viewer_has_strictly_read_only_access(): void
    {
        $this->actingAs($this->viewer);

        // Viewer CAN view page
        $res = $this->getJson("/api/v2/pages/{$this->page->id}");
        $res->assertOk()->assertJson(['success' => true]);

        // Viewer CANNOT view analytics (403)
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/analytics/overview");
        $res->assertForbidden();

        // Viewer CANNOT create posts (403)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Viewer posting',
            'status' => 'published',
        ]);
        $res->assertForbidden();

        // Viewer CANNOT manage team (403)
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/team");
        $res->assertForbidden();
    }

    /**
     * 7. Custom permissions override role defaults accurately
     */
    public function test_custom_permissions_override_role_defaults(): void
    {
        // Give Moderator custom permission to create posts
        $modMember = PageMember::where('page_id', $this->page->id)->where('user_id', $this->moderator->id)->first();
        $modMember->update([
            'custom_permissions' => ['posts.create' => true, 'posts.publish' => true],
        ]);

        $this->actingAs($this->moderator);
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/posts", [
            'content' => 'Moderator allowed by custom permission',
            'status' => 'published',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        // Explicitly deny Editor permission to edit posts
        $editorMember = PageMember::where('page_id', $this->page->id)->where('user_id', $this->editor->id)->first();
        $editorMember->update([
            'custom_permissions' => ['posts.edit' => false],
        ]);

        $post = Post::factory()->create(['page_id' => $this->page->id, 'user_id' => $this->owner->id]);
        $this->actingAs($this->editor);
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/posts/{$post->id}", [
            'content' => 'Editor edit should be denied',
        ]);
        $res->assertForbidden();
    }

    /**
     * 8. Real Team Invitations, Token Acceptance, Decline, and Revocation
     */
    public function test_real_team_invitation_lifecycle(): void
    {
        $this->actingAs($this->owner);
        $invitee = User::factory()->create(['name' => 'Future Admin', 'email' => 'future@example.com']);

        // 1. Owner invites new member
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $invitee->email,
            'role' => 'editor',
        ]);
        $res->assertCreated()->assertJson(['success' => true]);

        $memberRecord = PageMember::where('page_id', $this->page->id)->where('user_id', $invitee->id)->first();
        $this->assertNotNull($memberRecord);
        $this->assertEquals('invited', $memberRecord->status);
        $this->assertNotNull($memberRecord->invitation_token);

        // Verify audit log
        $audit = PageAuditLog::where('page_id', $this->page->id)
            ->where('action', 'team.invite')
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->owner->id, $audit->actor_id);

        // 2. Invitee accepts invitation
        $this->actingAs($invitee);
        $res = $this->postJson('/api/v2/pages/team/accept', [
            'token' => $memberRecord->invitation_token,
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        $memberRecord->refresh();
        $this->assertEquals('active', $memberRecord->status);
        $this->assertNull($memberRecord->invitation_token);
        $this->assertNotNull($memberRecord->joined_at);

        // 3. Test decline lifecycle with another user
        $decliningUser = User::factory()->create(['email' => 'decline@example.com']);
        $this->actingAs($this->owner);
        $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $decliningUser->email,
            'role' => 'analyst',
        ]);
        $declineMember = PageMember::where('page_id', $this->page->id)->where('user_id', $decliningUser->id)->first();

        $this->actingAs($decliningUser);
        $res = $this->postJson('/api/v2/pages/team/decline', [
            'token' => $declineMember->invitation_token,
        ]);
        $res->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('page_members', ['id' => $declineMember->id]);

        // 4. Test revocation of pending invitation by owner
        $revokedUser = User::factory()->create(['email' => 'revoked@example.com']);
        $this->actingAs($this->owner);
        $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $revokedUser->email,
            'role' => 'moderator',
        ]);
        $revokedMember = PageMember::where('page_id', $this->page->id)->where('user_id', $revokedUser->id)->first();

        // Revoke via web route
        $res = $this->deleteJson("/pages/{$this->page->slug}/team/{$revokedMember->id}/revoke");
        $res->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('page_members', ['id' => $revokedMember->id]);
    }

    /**
     * 9. Privilege Escalation Protection: Non-owner cannot invite or promote admin
     */
    public function test_privilege_escalation_prevention_for_admin_role(): void
    {
        $this->actingAs($this->admin);
        $targetUser = User::factory()->create();

        // Admin attempting to invite another user as admin is BLOCKED (403)
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $targetUser->email,
            'role' => 'admin',
        ]);
        $res->assertForbidden();

        // Admin attempting to invite another user as admin via API is rejected (422)
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $targetUser->email,
            'role' => 'admin',
        ]);
        $res->assertStatus(422)->assertJson(['success' => false]);

        // Admin attempting to promote Editor to Admin is rejected
        $editorMember = PageMember::where('page_id', $this->page->id)->where('user_id', $this->editor->id)->first();
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/team/{$editorMember->id}", [
            'role' => 'admin',
        ]);
        $res->assertStatus(422)->assertJson(['success' => false]);

        // Admin attempting to remove an existing Admin is rejected
        $anotherAdmin = User::factory()->create();
        $anotherAdminMember = PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $anotherAdmin->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $res = $this->deleteJson("/api/v2/pages/{$this->page->id}/team/{$anotherAdminMember->id}");
        $res->assertStatus(422)->assertJson(['success' => false]);
    }

    /**
     * 10. Protection against removing or demoting the Page Owner and self-invitation
     */
    public function test_ownership_protection_and_self_invitation_prevention(): void
    {
        $this->actingAs($this->admin);

        // Attempting to invite the Page Owner as a team member returns 422
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $this->owner->email,
            'role' => 'editor',
        ]);
        $res->assertStatus(422)->assertJson(['success' => false]);

        // User attempting to invite themselves returns 422
        $res = $this->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $this->admin->email,
            'role' => 'editor',
        ]);
        $res->assertStatus(422)->assertJson(['success' => false]);

        // Attempting to assign 'owner' as a member role returns 422
        $this->actingAs($this->owner);
        $editorMember = PageMember::where('page_id', $this->page->id)->where('user_id', $this->editor->id)->first();
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/team/{$editorMember->id}", [
            'role' => 'owner',
        ]);
        $res->assertStatus(422);
    }

    /**
     * 11. Ownership Transfer: Only current owner can transfer, demotes old owner, promotes new owner
     */
    public function test_ownership_transfer_workflow_and_audit(): void
    {
        $newOwner = User::factory()->create(['name' => 'Next CEO', 'email' => 'ceo@example.com']);

        // Non-owner cannot transfer ownership (403)
        $this->actingAs($this->admin);
        $res = $this->postJson("/pages/{$this->page->slug}/transfer-ownership", [
            'identifier' => $newOwner->email,
        ]);
        $res->assertForbidden();

        // Current owner transfers ownership
        $this->actingAs($this->owner);
        $res = $this->postJson("/pages/{$this->page->slug}/transfer-ownership", [
            'identifier' => $newOwner->email,
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        $this->page->refresh();
        // New owner is now owner_id
        $this->assertEquals($newOwner->id, $this->page->owner_id);

        // Previous owner is demoted to admin member
        $oldOwnerMember = PageMember::where('page_id', $this->page->id)->where('user_id', $this->owner->id)->first();
        $this->assertNotNull($oldOwnerMember);
        $this->assertEquals(Page::ROLE_ADMIN, $oldOwnerMember->role);

        // New owner has no leftover member record
        $this->assertDatabaseMissing('page_members', [
            'page_id' => $this->page->id,
            'user_id' => $newOwner->id,
        ]);

        // Audit log verified
        $audit = PageAuditLog::where('page_id', $this->page->id)
            ->where('action', 'page.transfer_ownership')
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->owner->id, $audit->actor_id);
    }

    /**
     * 12. Page Deletion (Soft Delete) and Restoration restricted to Page Owner
     */
    public function test_page_deletion_and_restoration_lifecycle(): void
    {
        // Non-owner cannot delete
        $this->actingAs($this->admin);
        $res = $this->deleteJson("/pages/{$this->page->slug}");
        $res->assertForbidden();

        // Owner deletes page
        $this->actingAs($this->owner);
        $res = $this->deleteJson("/pages/{$this->page->slug}");
        $res->assertOk()->assertJson(['success' => true]);

        $this->page->refresh();
        $this->assertTrue($this->page->trashed());
        $this->assertEquals(Page::STATUS_TRASH, $this->page->status);

        // Non-owner cannot restore page (403)
        $this->actingAs($this->admin);
        $res = $this->postJson("/pages/{$this->page->id}/restore");
        $res->assertForbidden();

        // Owner restores page
        $this->actingAs($this->owner);
        $res = $this->postJson("/pages/{$this->page->id}/restore");
        $res->assertOk()->assertJson(['success' => true]);

        $this->page->refresh();
        $this->assertFalse($this->page->trashed());
        $this->assertEquals(Page::STATUS_ACTIVE, $this->page->status);
    }

    /**
     * 13. IDOR and Direct API Calls by Unauthenticated or Unauthorized users are blocked
     */
    public function test_idor_and_unauthorized_direct_api_calls_are_blocked(): void
    {
        // Unauthenticated access
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/team");
        $res->assertUnauthorized();

        // Authenticated user with no relationship to page (Stranger)
        $this->actingAs($this->stranger);

        // Cannot view team
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/team");
        $res->assertForbidden();

        // Cannot view analytics
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/analytics/overview");
        $res->assertForbidden();

        // Cannot view drafts
        $res = $this->getJson("/api/v2/pages/{$this->page->id}/posts/drafts");
        $res->assertForbidden();

        // Cannot update settings
        $res = $this->putJson("/api/v2/pages/{$this->page->id}/settings", [
            'privacy' => ['public_email' => false],
        ]);
        $res->assertForbidden();

        // Cannot invite members
        $anotherStranger = User::factory()->create();
        $res = $this->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $anotherStranger->email,
            'role' => 'editor',
        ]);
        $res->assertForbidden();

        // Cannot delete page
        $res = $this->deleteJson("/api/v2/pages/{$this->page->id}");
        $res->assertForbidden();
    }
}
