<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessengerImageMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_upload_image_attachment_stores_metadata_and_generates_variants(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test_photo.png', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
                'collection' => 'message',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'filename',
                    'mime_type',
                    'size',
                    'width',
                    'height',
                    'url',
                    'preview_url',
                    'thumbnail_url',
                    'download_url',
                    'urls' => [
                        'original',
                        'thumbnail',
                        'medium',
                        'large',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(800, $data['width']);
        $this->assertEquals(600, $data['height']);
        $this->assertNotEmpty($data['preview_url']);
        $this->assertNotEmpty($data['url']);

        $media = Media::find($data['id']);
        $this->assertNotNull($media);
        $this->assertEquals('test_photo.png', $media->name);
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals(800, $media->width);
        $this->assertEquals(600, $media->height);
    }

    public function test_sending_message_with_image_attachment_suppresses_redundant_filename(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $file = UploadedFile::fake()->image('nature_shot.jpg', 640, 480);
        $uploadRes = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
                'collection' => 'message',
            ]);

        $mediaId = $uploadRes->json('data.id');

        // Send message where body is accidentally the file's name
        $msgRes = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'nature_shot.jpg',
                'media_ids' => [$mediaId],
                'type' => 'media',
            ]);

        $msgRes->assertStatus(201);
        $msgData = $msgRes->json('data');

        // Redundant filename must be suppressed to empty string so raw filename is never shown
        $this->assertEquals('', $msgData['body']);
        $this->assertNotEmpty($msgData['media']);
        $this->assertTrue($msgData['is_image']);
        $this->assertEquals('nature_shot.jpg', $msgData['media'][0]['name']);
        $this->assertNotEmpty($msgData['media'][0]['preview_url']);
        $this->assertEquals(640, $msgData['media'][0]['width']);
    }

    public function test_sending_message_with_custom_caption_preserves_caption(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $file = UploadedFile::fake()->image('sunset.png', 400, 300);
        $uploadRes = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $mediaId = $uploadRes->json('data.id');

        $msgRes = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'আজকের সুন্দর সূর্যাস্ত!',
                'media_ids' => [$mediaId],
                'type' => 'media',
            ]);

        $msgRes->assertStatus(201);
        $msgData = $msgRes->json('data');

        $this->assertEquals('আজকের সুন্দর সূর্যাস্ত!', $msgData['body']);
        $this->assertNotEmpty($msgData['media']);
    }

    public function test_attachment_view_endpoint_allows_conversation_participant(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        $uploadRes = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $mediaId = $uploadRes->json('data.id');

        $messengerService->sendMessage($sender, $conv, [
            'body' => '',
            'media_ids' => [$mediaId],
        ]);

        // Participant (recipient) should be able to view/stream the image inline
        $viewRes = $this->actingAs($recipient, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/view");

        $viewRes->assertStatus(200);
        $viewRes->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('inline', $viewRes->headers->get('Content-Disposition') ?? '');
    }

    public function test_attachment_view_endpoint_blocks_non_participant_with_403(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $file = UploadedFile::fake()->image('secret.png', 200, 200);
        $uploadRes = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $mediaId = $uploadRes->json('data.id');

        $messengerService->sendMessage($sender, $conv, [
            'body' => 'Secret photo',
            'media_ids' => [$mediaId],
        ]);

        // Outsider should be blocked by IDOR authorization
        $viewRes = $this->actingAs($outsider, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/view");

        $viewRes->assertStatus(403);
    }

    public function test_attachment_view_endpoint_blocks_unauthenticated_user(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'message',
            'name' => 'private_doc.jpg',
            'original_path' => 'message/private_doc.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 5000,
        ]);

        // No authentication token provided - must be rejected with 401
        $viewRes = $this->getJson("/api/v1/messages/attachments/{$media->id}/view");
        $viewRes->assertStatus(401);
    }

    public function test_media_to_response_array_exposes_complete_variant_urls(): void
    {
        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'message',
            'name' => 'sample_landscape.webp',
            'original_path' => 'message/sample_landscape.webp',
            'thumbnail_path' => 'message/variants/thumb_sample_landscape.webp',
            'medium_path' => 'message/variants/med_sample_landscape.webp',
            'large_path' => 'message/variants/large_sample_landscape.webp',
            'mime_type' => 'image/webp',
            'size' => 12345,
            'width' => 1200,
            'height' => 800,
        ]);

        $array = $media->toResponseArray();

        $this->assertEquals('sample_landscape.webp', $array['name']);
        $this->assertEquals('sample_landscape.webp', $array['filename']);
        $this->assertEquals(1200, $array['width']);
        $this->assertEquals(800, $array['height']);
        $this->assertNotEmpty($array['url']);
        $this->assertNotEmpty($array['preview_url']);
        $this->assertNotEmpty($array['thumbnail_url']);
        $this->assertNotEmpty($array['download_url']);
        $this->assertIsArray($array['urls']);
        $this->assertArrayHasKey('thumbnail', $array['urls']);
        $this->assertArrayHasKey('medium', $array['urls']);
        $this->assertArrayHasKey('original', $array['urls']);
    }

    public function test_attachment_view_endpoint_serves_thumbnail_variant(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('gallery_item.png', 1000, 800);
        $uploadRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $mediaId = $uploadRes->json('data.id');

        $viewRes = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/view?variant=thumbnail");

        $viewRes->assertStatus(200);
        $this->assertNotEmpty($viewRes->headers->get('ETag'));
        $this->assertStringContainsString('private', $viewRes->headers->get('Cache-Control') ?? '');
    }

    public function test_unassociated_attachment_accessible_only_by_uploader(): void
    {
        $uploader = User::factory()->create();
        $stranger = User::factory()->create();

        $file = UploadedFile::fake()->image('draft_attachment.jpg', 400, 400);
        $uploadRes = $this->actingAs($uploader, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $mediaId = $uploadRes->json('data.id');

        // Uploader can view before sending message
        $uploaderView = $this->actingAs($uploader, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/view");
        $uploaderView->assertStatus(200);

        // Stranger cannot view draft upload (IDOR check)
        $strangerView = $this->actingAs($stranger, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/view");
        $strangerView->assertStatus(403);
    }
}
