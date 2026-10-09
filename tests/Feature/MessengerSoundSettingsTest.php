<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use App\Services\Messenger\MessengerSoundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessengerSoundSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_sound_settings(): void
    {
        $response = $this->getJson('/api/v1/settings/messenger-sounds');
        $response->assertStatus(401);

        $updateResponse = $this->putJson('/api/v1/settings/messenger-sounds', [
            'messenger_sounds_enabled' => false,
        ]);
        $updateResponse->assertStatus(401);

        $resetResponse = $this->postJson('/api/v1/settings/messenger-sounds/reset');
        $resetResponse->assertStatus(401);
    }

    public function test_authenticated_user_receives_sound_settings_and_library_catalogue(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/settings/messenger-sounds');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'messenger_sounds_enabled',
                    'incoming_call_sound_enabled',
                    'incoming_call_sound',
                    'incoming_call_volume',
                    'incoming_message_sound_enabled',
                    'incoming_message_sound',
                    'incoming_message_volume',
                    'outgoing_message_sound_enabled',
                    'outgoing_message_sound',
                    'outgoing_message_volume',
                    'sound_library' => [
                        'incoming_call',
                        'incoming_message',
                        'outgoing_message',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertTrue($data['messenger_sounds_enabled']);
        $this->assertEquals('bondhoo_ring', $data['incoming_call_sound']);
        $this->assertEquals('bubble_pop', $data['incoming_message_sound']);
        $this->assertEquals('subtle_sent', $data['outgoing_message_sound']);
    }

    public function test_three_default_sound_types_are_genuinely_distinct_assets(): void
    {
        $service = app(MessengerSoundService::class);
        $library = $service->getSoundLibrary();

        $callDefaultUrl = $service->getSoundUrl('incoming_call', 'bondhoo_ring');
        $incomingMsgDefaultUrl = $service->getSoundUrl('incoming_message', 'bubble_pop');
        $outgoingSentDefaultUrl = $service->getSoundUrl('outgoing_message', 'subtle_sent');

        // Verify none of the 3 URLs match
        $this->assertNotNull($callDefaultUrl);
        $this->assertNotNull($incomingMsgDefaultUrl);
        $this->assertNotNull($outgoingSentDefaultUrl);
        $this->assertNotEquals($callDefaultUrl, $incomingMsgDefaultUrl);
        $this->assertNotEquals($callDefaultUrl, $outgoingSentDefaultUrl);
        $this->assertNotEquals($incomingMsgDefaultUrl, $outgoingSentDefaultUrl);

        // Verify the physical audio files exist in public directory
        $this->assertFileExists(public_path('sounds/messenger/bondhoo_ring.wav'));
        $this->assertFileExists(public_path('sounds/messenger/bubble_pop.wav'));
        $this->assertFileExists(public_path('sounds/messenger/subtle_sent.wav'));

        // Verify audio file sizes differ, proving they are genuinely distinct waveforms
        $callSize = filesize(public_path('sounds/messenger/bondhoo_ring.wav'));
        $msgSize = filesize(public_path('sounds/messenger/bubble_pop.wav'));
        $sentSize = filesize(public_path('sounds/messenger/subtle_sent.wav'));

        $this->assertNotEquals($callSize, $msgSize);
        $this->assertNotEquals($callSize, $sentSize);
        $this->assertNotEquals($msgSize, $sentSize);

        // Verify each library category has multiple curated options
        $this->assertGreaterThanOrEqual(3, count($library['incoming_call']));
        $this->assertGreaterThanOrEqual(3, count($library['incoming_message']));
        $this->assertGreaterThanOrEqual(3, count($library['outgoing_message']));
    }

    public function test_user_can_update_sound_preferences_independently(): void
    {
        $user = User::factory()->create();

        $payload = [
            'incoming_call_sound_enabled' => false,
            'incoming_call_sound' => 'digital_bell',
            'incoming_call_volume' => 95,
            'incoming_message_sound_enabled' => true,
            'incoming_message_sound' => 'clear_ding',
            'incoming_message_volume' => 45,
            'outgoing_message_sound_enabled' => false,
            'outgoing_message_sound' => 'quick_click',
            'outgoing_message_volume' => 30,
        ];

        $response = $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'incoming_call_sound_enabled' => false,
                    'incoming_call_sound' => 'digital_bell',
                    'incoming_call_volume' => 95,
                    'incoming_message_sound_enabled' => true,
                    'incoming_message_sound' => 'clear_ding',
                    'incoming_message_volume' => 45,
                    'outgoing_message_sound_enabled' => false,
                    'outgoing_message_sound' => 'quick_click',
                    'outgoing_message_volume' => 30,
                ],
            ]);

        // Verify database persistence
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'incoming_call_sound' => 'digital_bell',
            'incoming_call_volume' => 95,
            'incoming_message_sound' => 'clear_ding',
            'incoming_message_volume' => 45,
            'outgoing_message_sound' => 'quick_click',
            'outgoing_message_volume' => 30,
        ]);
    }

    public function test_validation_rejects_out_of_bounds_volume_and_invalid_sound_ids(): void
    {
        $user = User::factory()->create();

        // Volume above 100
        $response = $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'incoming_call_volume' => 150,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['incoming_call_volume']);

        // Volume below 0
        $response = $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'outgoing_message_volume' => -10,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['outgoing_message_volume']);

        // Invalid sound ID not in library
        $response = $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'incoming_call_sound' => 'malicious_file_exploit.exe',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['incoming_call_sound']);
    }

    public function test_global_toggle_disables_messenger_sounds_without_clearing_preferences(): void
    {
        $user = User::factory()->create();

        // First configure customized sound selections
        $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'incoming_call_sound' => 'digital_bell',
            'incoming_call_volume' => 88,
        ]);

        // Toggle master switch off
        $response = $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'messenger_sounds_enabled' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'messenger_sounds_enabled' => false,
                    'incoming_call_sound' => 'digital_bell',
                    'incoming_call_volume' => 88,
                ],
            ]);

        // Verify preferences are retained in database
        $settings = UserSetting::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $settings->messenger_sounds_enabled);
        $this->assertEquals('digital_bell', $settings->incoming_call_sound);
        $this->assertEquals(88, $settings->incoming_call_volume);
    }

    public function test_user_can_reset_individual_sound_category_to_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'incoming_call_sound' => 'celesta_chime',
            'incoming_call_volume' => 50,
            'incoming_message_sound' => 'soft_chime',
            'incoming_message_volume' => 40,
        ]);

        // Reset only the incoming_call category
        $response = $this->actingAs($user)->postJson('/api/v1/settings/messenger-sounds/reset', [
            'type' => 'incoming_call',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'incoming_call_sound_enabled' => true,
                    'incoming_call_sound' => 'bondhoo_ring',
                    'incoming_call_volume' => 80,
                    'incoming_message_sound' => 'soft_chime',
                    'incoming_message_volume' => 40,
                ],
            ]);
    }

    public function test_user_can_factory_reset_all_sound_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/settings/messenger-sounds', [
            'messenger_sounds_enabled' => false,
            'incoming_call_sound' => 'digital_bell',
            'incoming_call_volume' => 30,
            'incoming_message_sound' => 'soft_chime',
            'incoming_message_volume' => 20,
            'outgoing_message_sound' => 'soft_swoosh',
            'outgoing_message_volume' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/settings/messenger-sounds/reset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'messenger_sounds_enabled' => true,
                    'incoming_call_sound_enabled' => true,
                    'incoming_call_sound' => 'bondhoo_ring',
                    'incoming_call_volume' => 80,
                    'incoming_message_sound_enabled' => true,
                    'incoming_message_sound' => 'bubble_pop',
                    'incoming_message_volume' => 75,
                    'outgoing_message_sound_enabled' => true,
                    'outgoing_message_sound' => 'subtle_sent',
                    'outgoing_message_volume' => 70,
                ],
            ]);
    }

    public function test_uploaded_custom_audio_is_validated_by_mime_and_size(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 1. Invalid file extension/mime (e.g. php or txt)
        $invalidFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');
        $response = $this->actingAs($user)->postJson('/api/v1/settings/messenger-sounds/upload', [
            'type' => 'incoming_call',
            'audio' => $invalidFile,
        ]);
        $response->assertStatus(422);

        // 2. Over 2MB audio file
        $oversizedAudio = UploadedFile::fake()->create('huge_sound.mp3', 3000, 'audio/mpeg');
        $response = $this->actingAs($user)->postJson('/api/v1/settings/messenger-sounds/upload', [
            'type' => 'incoming_call',
            'audio' => $oversizedAudio,
        ]);
        $response->assertStatus(422);

        // 3. Valid audio file
        $validAudio = UploadedFile::fake()->create('custom_ringtone.mp3', 250, 'audio/mpeg');
        $response = $this->actingAs($user)->postJson('/api/v1/settings/messenger-sounds/upload', [
            'type' => 'incoming_call',
            'audio' => $validAudio,
        ]);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'incoming_call_sound' => 'custom',
                ],
            ]);

        // Verify the user setting was automatically updated to use the custom sound
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'incoming_call_sound' => 'custom',
        ]);
    }
}
