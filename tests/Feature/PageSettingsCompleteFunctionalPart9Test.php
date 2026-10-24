<?php

namespace Tests\Feature;

use App\Events\PageSettingsUpdatedEvent;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageSettingsCompleteFunctionalPart9Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $visitor;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->owner = User::factory()->create([
            'name' => 'Page Owner',
            'email' => 'owner@jugajug.test',
            'username' => 'pageowner',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Page Administrator',
            'email' => 'admin@jugajug.test',
            'username' => 'pageadmin',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->visitor = User::factory()->create([
            'name' => 'Regular Visitor',
            'email' => 'visitor@jugajug.test',
            'username' => 'visitoruser',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Jugajug Tech Hub',
            'slug' => 'jugajug-tech-hub',
            'username' => 'jugajugtech',
            'category' => 'Technology',
            'sub_category' => 'Software Company',
            'bio' => 'Empowering open software.',
            'description' => 'A premier platform for enterprise technology and innovation.',
            'website' => 'https://jugajug.test',
            'email' => 'tech@jugajug.test',
            'phone' => '+8801700112233',
            'address' => '71 Innovation Ave',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'zip_code' => '1212',
            'cta_type' => 'learn_more',
            'cta_url' => 'https://jugajug.test/learn',
            'visibility' => 'public',
            'status' => 'active',
            'social_links' => [
                'facebook' => 'https://facebook.com/jugajugtech',
                'twitter' => 'https://x.com/jugajugtech',
            ],
        ]);

        // Add manager with Administrator role
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->manager->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_page_owner_and_authorized_manager_can_update_general_information_and_discoverability_settings(): void
    {
        Event::fake([PageSettingsUpdatedEvent::class]);

        $payload = [
            'name' => 'Jugajug Global Innovation',
            'bio' => 'Updated tech bio.',
            'description' => 'Detailed enterprise description.',
            'category' => 'Science & Tech',
            'sub_category' => 'Artificial Intelligence',
            'cta_type' => 'contact_us',
            'cta_url' => 'https://jugajug.test/contact',
            'visibility' => 'unlisted',
            'settings' => [
                'discoverability' => [
                    'search_engine_indexing' => false,
                    'show_in_directory' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->putJson("/pages/{$this->page->slug}/settings", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page settings updated successfully.',
            ]);

        $this->page->refresh();
        $this->assertSame('Jugajug Global Innovation', $this->page->name);
        $this->assertSame('Updated tech bio.', $this->page->bio);
        $this->assertSame('Science & Tech', $this->page->category);
        $this->assertSame('Artificial Intelligence', $this->page->sub_category);
        $this->assertSame('contact_us', $this->page->cta_type);
        $this->assertSame('unlisted', $this->page->visibility);

        $settings = $this->page->getMergedSettings();
        $this->assertFalse($settings['discoverability']['search_engine_indexing']);
        $this->assertFalse($settings['discoverability']['show_in_directory']);

        Event::assertDispatched(PageSettingsUpdatedEvent::class, function ($event) {
            return (int) $event->pageId === (int) $this->page->id
                && (int) $event->actorId === (int) $this->manager->id;
        });

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->manager->id,
            'action' => 'settings_updated',
        ]);
    }

    public function test_unauthorized_user_cannot_update_page_settings(): void
    {
        $response = $this->actingAs($this->visitor)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
        $this->page->refresh();
        $this->assertSame('Jugajug Tech Hub', $this->page->name);
    }

    public function test_settings_update_validation_rejects_taken_username(): void
    {
        $otherPage = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Other Tech',
            'slug' => 'other-tech',
            'username' => 'othertarget',
            'category' => 'Technology',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'username' => 'othertarget',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_optimistic_concurrency_control_prevents_silent_overwrite_of_newer_settings(): void
    {
        // Manager A retrieves page with original updated_at timestamp
        $originalUpdatedAt = $this->page->updated_at->copy()->subMinutes(5)->toIso8601String();

        // Manager B saves changes in the meantime, advancing updated_at
        $this->page->name = 'Manager B Name';
        $this->page->touch();
        $this->page->save();

        // Manager A submits older last_updated_at
        $response = $this->actingAs($this->manager)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'name' => 'Manager A Conflicting Name',
                'last_updated_at' => $originalUpdatedAt,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        $this->page->refresh();
        $this->assertSame('Manager B Name', $this->page->name);
    }

    public function test_messaging_settings_downstream_enforcement_allow_messages_and_auto_reply(): void
    {
        // 1. Disable direct messaging
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'messaging' => [
                        'allow_messages' => false,
                    ],
                ],
            ])->assertStatus(200);

        // Visitor attempts to send message -> rejected with 403
        $respDisabled = $this->actingAs($this->visitor)
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Hello from visitor!',
            ]);

        $respDisabled->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Direct messaging is currently disabled for this page.',
            ]);

        // 2. Enable messaging with auto-reply
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'messaging' => [
                        'allow_messages' => true,
                        'auto_reply_enabled' => true,
                        'auto_reply_message' => 'Welcome to Jugajug Tech! We will reply promptly.',
                    ],
                ],
            ])->assertStatus(200);

        // Visitor sends message -> message created and auto-reply sent
        $respEnabled = $this->actingAs($this->visitor)
            ->postJson("/pages/{$this->page->slug}/messages", [
                'body' => 'Hello, can you help me?',
            ]);

        $respEnabled->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('page_messages', [
            'sender_type' => 'user',
            'body' => 'Hello, can you help me?',
        ]);

        $this->assertDatabaseHas('page_messages', [
            'sender_type' => 'page',
            'body' => 'Welcome to Jugajug Tech! We will reply promptly.',
        ]);
    }

    public function test_moderation_settings_downstream_enforcement_allow_comments_and_blocked_keywords(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Official tech release announcement!',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // 1. Configure blocked keywords
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'moderation' => [
                        'allow_comments' => true,
                        'blocked_keywords' => ['cryptoscam', 'fakenews'],
                    ],
                ],
            ])->assertStatus(200);

        // Comment containing blocked keyword is rejected with 422
        $respScam = $this->actingAs($this->visitor)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Check this cryptoscam link now!',
            ]);

        $respScam->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        // Clean comment succeeds with 201
        $respClean = $this->actingAs($this->visitor)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Great official update, congratulations!',
            ]);

        $respClean->assertStatus(201)
            ->assertJson(['success' => true]);

        // 2. Disable comments entirely for page
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'moderation' => [
                        'allow_comments' => false,
                    ],
                ],
            ])->assertStatus(200);

        // Comment attempt rejected with 403
        $respDisabled = $this->actingAs($this->visitor)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Another comment attempt.',
            ]);

        $respDisabled->assertStatus(403);
    }

    public function test_publishing_settings_downstream_enforcement_allowed_formats(): void
    {
        // Restrict allowed formats to images only (disallow pure text posts)
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'publishing' => [
                        'allowed_formats' => ['image', 'video'],
                    ],
                ],
            ])->assertStatus(200);

        // Post without media (pure text) is rejected with 422
        $respText = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'This is a pure text post attempt.',
            ]);

        $respText->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Text posts are currently disabled for this page.',
            ]);

        // Post with image file succeeds
        $image = UploadedFile::fake()->image('banner.jpg', 600, 400);
        $respImage = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Image post with banner.',
                'media' => [$image],
            ]);

        $respImage->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_followers_and_audience_settings_downstream_enforcement(): void
    {
        // Set default post audience to 'followers'
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'audience' => [
                        'default_post_audience' => 'followers',
                    ],
                ],
            ])->assertStatus(200);

        // Manager posts without explicitly specifying audience
        $resp = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Follower exclusive update.',
            ]);

        $resp->assertStatus(200);
        $post = Post::latest('id')->first();
        $this->assertSame('followers', $post->audience);
    }

    public function test_privacy_settings_downstream_enforcement_contact_info_masking(): void
    {
        // 1. Hide contact information entirely
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'privacy' => [
                        'show_contact_info' => false,
                    ],
                ],
            ])->assertStatus(200);

        // Visitor requests about info: contact fields masked to null
        $respVisitor = $this->actingAs($this->visitor)
            ->getJson("/pages/{$this->page->slug}/about");

        $respVisitor->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => null,
                    'phone' => null,
                    'address' => null,
                    'city' => null,
                    'zip_code' => null,
                    'website' => null,
                ],
            ]);

        // Page manager requests about info: sees authoritative contact information
        $respManager = $this->actingAs($this->manager)
            ->getJson("/pages/{$this->page->slug}/about");

        $respManager->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => 'tech@jugajug.test',
                    'phone' => '+8801700112233',
                    'address' => '71 Innovation Ave',
                    'city' => 'Dhaka',
                    'zip_code' => '1212',
                    'website' => 'https://jugajug.test',
                ],
            ]);

        // 2. Enable show_contact_info but hide phone number specifically
        $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'settings' => [
                    'privacy' => [
                        'show_contact_info' => true,
                        'public_email' => true,
                        'public_phone' => false,
                        'public_address' => true,
                    ],
                ],
            ])->assertStatus(200);

        $respVisitor2 = $this->actingAs($this->visitor)
            ->getJson("/pages/{$this->page->slug}/about");

        $respVisitor2->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => 'tech@jugajug.test',
                    'phone' => null, // Hidden specifically
                    'address' => '71 Innovation Ave',
                ],
            ]);
    }

    public function test_linked_accounts_and_contact_information_persisted_and_returned(): void
    {
        $payload = [
            'website' => 'https://jugajug.enterprise',
            'email' => 'corporate@jugajug.enterprise',
            'phone' => '+8801999887766',
            'address' => 'Floor 14, Silicon Center',
            'city' => 'Chittagong',
            'country' => 'Bangladesh',
            'zip_code' => '4000',
            'social_links' => [
                'facebook' => 'https://facebook.com/jugajug.enterprise',
                'twitter' => 'https://x.com/jugajug_corp',
                'linkedin' => 'https://linkedin.com/company/jugajug',
                'youtube' => 'https://youtube.com/@jugajugtech',
                'github' => 'https://github.com/jugajug',
            ],
        ];

        $resp = $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/settings", $payload);

        $resp->assertStatus(200);

        $this->page->refresh();
        $this->assertSame('https://jugajug.enterprise', $this->page->website);
        $this->assertSame('corporate@jugajug.enterprise', $this->page->email);
        $this->assertSame('+8801999887766', $this->page->phone);
        $this->assertSame('Floor 14, Silicon Center', $this->page->address);
        $this->assertSame('Chittagong', $this->page->city);
        $this->assertSame('4000', $this->page->zip_code);

        $social = $this->page->social_links;
        $this->assertSame('https://github.com/jugajug', $social['github']);
        $this->assertSame('https://youtube.com/@jugajugtech', $social['youtube']);
    }

    public function test_tagging_and_notifications_settings_persisted(): void
    {
        $payload = [
            'settings' => [
                'tagging' => [
                    'allow_mentions' => false,
                    'who_can_tag' => 'followers',
                ],
                'notifications' => [
                    'email_alerts' => false,
                    'in_app_follows' => true,
                    'in_app_comments' => false,
                    'in_app_messages' => true,
                ],
            ],
        ];

        $this->actingAs($this->manager)
            ->putJson("/pages/{$this->page->slug}/settings", $payload)
            ->assertStatus(200);

        $this->page->refresh();
        $settings = $this->page->getMergedSettings();

        $this->assertFalse($settings['tagging']['allow_mentions']);
        $this->assertSame('followers', $settings['tagging']['who_can_tag']);
        $this->assertFalse($settings['notifications']['email_alerts']);
        $this->assertTrue($settings['notifications']['in_app_follows']);
        $this->assertFalse($settings['notifications']['in_app_comments']);
        $this->assertTrue($settings['notifications']['in_app_messages']);
    }

    public function test_activity_history_and_audit_logs_retrieval(): void
    {
        // Perform a settings change to generate an audit log entry
        $this->actingAs($this->manager)
            ->putJson("/pages/{$this->page->slug}/settings", [
                'name' => 'Audited Name Change',
            ])->assertStatus(200);

        // Manager fetches audit logs
        $resp = $this->actingAs($this->manager)
            ->getJson("/pages/{$this->page->slug}/audit-logs");

        $resp->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($resp->json('data'));
        $this->assertSame('settings_updated', $resp->json('data.0.action'));

        // Visitor cannot access audit logs
        $this->actingAs($this->visitor)
            ->getJson("/pages/{$this->page->slug}/audit-logs")
            ->assertStatus(403);
    }

    public function test_archive_restore_and_delete_destructive_actions(): void
    {
        // 1. Archive page
        $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/archive")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page has been archived successfully.',
            ]);

        $this->page->refresh();
        $this->assertSame('disabled', $this->page->status);

        // 2. Delete page (requires owner)
        $this->actingAs($this->manager)
            ->deleteJson("/pages/{$this->page->slug}")
            ->assertStatus(403); // Administrator cannot delete page

        $this->actingAs($this->owner)
            ->deleteJson("/pages/{$this->page->slug}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page moved to trash successfully.',
            ]);

        $this->assertSoftDeleted('pages', ['id' => $this->page->id]);

        // 3. Restore page (requires owner)
        $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->id}/restore")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page restored successfully.',
            ]);

        $this->page->refresh();
        $this->assertNull($this->page->deleted_at);
        $this->assertSame('active', $this->page->status);
    }

    public function test_security_sensitive_transfer_ownership_operations(): void
    {
        $newOwnerCandidate = User::factory()->create([
            'email' => 'futureowner@jugajug.test',
            'username' => 'futureowner',
        ]);

        // 1. Non-owner cannot transfer ownership
        $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/transfer-ownership", [
                'new_owner_id' => $newOwnerCandidate->id,
            ])->assertStatus(403);

        // 2. Cannot transfer to oneself
        $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/transfer-ownership", [
                'new_owner_id' => $this->owner->id,
            ])->assertStatus(422);

        // 3. Incorrect password fails re-authentication
        $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/transfer-ownership", [
                'new_owner_id' => $newOwnerCandidate->id,
                'password' => 'WrongPassword!',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // 4. Successful transfer with valid password
        $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/transfer-ownership", [
                'new_owner_id' => $newOwnerCandidate->id,
                'password' => 'Secret123!',
            ])->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->page->refresh();
        $this->assertSame($newOwnerCandidate->id, $this->page->owner_id);

        // Prior owner retained as active member
        $this->assertDatabaseHas('page_members', [
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'status' => 'active',
        ]);
    }
}
