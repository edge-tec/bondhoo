<?php

namespace App\Services\Messenger;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MessengerSoundService
{
    /**
     * Get the authoritative curated Messenger sound library catalogue.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getSoundLibrary(): array
    {
        return [
            'incoming_call' => [
                [
                    'id' => 'bondhoo_ring',
                    'name' => 'Bondhoo Ring (ডিফল্ট)',
                    'file' => '/sounds/messenger/bondhoo_ring.wav',
                    'duration_sec' => 3.2,
                    'is_default' => true,
                ],
                [
                    'id' => 'digital_bell',
                    'name' => 'Digital Bell (ডিজিটাল বেল)',
                    'file' => '/sounds/messenger/digital_bell.wav',
                    'duration_sec' => 2.8,
                    'is_default' => false,
                ],
                [
                    'id' => 'celesta_chime',
                    'name' => 'Celesta Chime (সেলেনিয়াম মেলোডি)',
                    'file' => '/sounds/messenger/celesta_chime.wav',
                    'duration_sec' => 3.0,
                    'is_default' => false,
                ],
            ],
            'incoming_message' => [
                [
                    'id' => 'bubble_pop',
                    'name' => 'Bubble Pop (ডিফল্ট বাবল)',
                    'file' => '/sounds/messenger/bubble_pop.wav',
                    'duration_sec' => 0.18,
                    'is_default' => true,
                ],
                [
                    'id' => 'clear_ding',
                    'name' => 'Clear Ding (ক্রিস্টাল ডিং)',
                    'file' => '/sounds/messenger/clear_ding.wav',
                    'duration_sec' => 0.25,
                    'is_default' => false,
                ],
                [
                    'id' => 'soft_chime',
                    'name' => 'Soft Chime (নরম সুর)',
                    'file' => '/sounds/messenger/soft_chime.wav',
                    'duration_sec' => 0.22,
                    'is_default' => false,
                ],
            ],
            'outgoing_message' => [
                [
                    'id' => 'subtle_sent',
                    'name' => 'Subtle Sent (ডিফল্ট কনফার্মেশন)',
                    'file' => '/sounds/messenger/subtle_sent.wav',
                    'duration_sec' => 0.12,
                    'is_default' => true,
                ],
                [
                    'id' => 'soft_swoosh',
                    'name' => 'Soft Swoosh (সফট সোয়াশ)',
                    'file' => '/sounds/messenger/soft_swoosh.wav',
                    'duration_sec' => 0.15,
                    'is_default' => false,
                ],
                [
                    'id' => 'quick_click',
                    'name' => 'Quick Click (কুইক ক্লিক)',
                    'file' => '/sounds/messenger/quick_click.wav',
                    'duration_sec' => 0.08,
                    'is_default' => false,
                ],
            ],
        ];
    }

    /**
     * Get the relative asset URL for a library sound.
     */
    public function getSoundUrl(string $category, string $soundId): ?string
    {
        $library = $this->getSoundLibrary();
        $items = $library[$category] ?? [];
        foreach ($items as $item) {
            if ($item['id'] === $soundId) {
                return $item['file'];
            }
        }

        return null;
    }

    /**
     * Get or initialize the user's sound preferences.
     *
     * @return array<string, mixed>
     */
    public function getUserSoundSettings(User $user): array
    {
        /** @var UserSetting $settings */
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        return [
            'messenger_sounds_enabled' => (bool) ($settings->messenger_sounds_enabled ?? true),
            'incoming_call_sound_enabled' => (bool) ($settings->incoming_call_sound_enabled ?? true),
            'incoming_call_sound' => (string) ($settings->incoming_call_sound ?: 'bondhoo_ring'),
            'incoming_call_volume' => (int) ($settings->incoming_call_volume ?? 80),
            'incoming_message_sound_enabled' => (bool) ($settings->incoming_message_sound_enabled ?? true),
            'incoming_message_sound' => (string) ($settings->incoming_message_sound ?: 'bubble_pop'),
            'incoming_message_volume' => (int) ($settings->incoming_message_volume ?? 75),
            'outgoing_message_sound_enabled' => (bool) ($settings->outgoing_message_sound_enabled ?? true),
            'outgoing_message_sound' => (string) ($settings->outgoing_message_sound ?: 'subtle_sent'),
            'outgoing_message_volume' => (int) ($settings->outgoing_message_volume ?? 70),
            'custom_call_sound_path' => $settings->custom_call_sound_path ? Storage::url($settings->custom_call_sound_path) : null,
            'custom_incoming_msg_sound_path' => $settings->custom_incoming_msg_sound_path ? Storage::url($settings->custom_incoming_msg_sound_path) : null,
            'custom_outgoing_msg_sound_path' => $settings->custom_outgoing_msg_sound_path ? Storage::url($settings->custom_outgoing_msg_sound_path) : null,
            'sound_library' => $this->getSoundLibrary(),
        ];
    }

    /**
     * Update the user's sound settings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateUserSoundSettings(User $user, array $data): array
    {
        /** @var UserSetting $settings */
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        $attributes = [];

        if (array_key_exists('messenger_sounds_enabled', $data)) {
            $attributes['messenger_sounds_enabled'] = (bool) $data['messenger_sounds_enabled'];
        }

        if (array_key_exists('incoming_call_sound_enabled', $data)) {
            $attributes['incoming_call_sound_enabled'] = (bool) $data['incoming_call_sound_enabled'];
        }
        if (! empty($data['incoming_call_sound'])) {
            $attributes['incoming_call_sound'] = (string) $data['incoming_call_sound'];
        }
        if (array_key_exists('incoming_call_volume', $data)) {
            $attributes['incoming_call_volume'] = max(0, min(100, (int) $data['incoming_call_volume']));
        }

        if (array_key_exists('incoming_message_sound_enabled', $data)) {
            $attributes['incoming_message_sound_enabled'] = (bool) $data['incoming_message_sound_enabled'];
        }
        if (! empty($data['incoming_message_sound'])) {
            $attributes['incoming_message_sound'] = (string) $data['incoming_message_sound'];
        }
        if (array_key_exists('incoming_message_volume', $data)) {
            $attributes['incoming_message_volume'] = max(0, min(100, (int) $data['incoming_message_volume']));
        }

        if (array_key_exists('outgoing_message_sound_enabled', $data)) {
            $attributes['outgoing_message_sound_enabled'] = (bool) $data['outgoing_message_sound_enabled'];
        }
        if (! empty($data['outgoing_message_sound'])) {
            $attributes['outgoing_message_sound'] = (string) $data['outgoing_message_sound'];
        }
        if (array_key_exists('outgoing_message_volume', $data)) {
            $attributes['outgoing_message_volume'] = max(0, min(100, (int) $data['outgoing_message_volume']));
        }

        $settings->update($attributes);

        return $this->getUserSoundSettings($user);
    }

    /**
     * Reset sound settings to factory defaults.
     *
     * @return array<string, mixed>
     */
    public function resetUserSoundSettings(User $user, ?string $type = null): array
    {
        /** @var UserSetting $settings */
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        if ($type === 'incoming_call') {
            $settings->update([
                'incoming_call_sound_enabled' => true,
                'incoming_call_sound' => 'bondhoo_ring',
                'incoming_call_volume' => 80,
                'custom_call_sound_path' => null,
            ]);
        } elseif ($type === 'incoming_message') {
            $settings->update([
                'incoming_message_sound_enabled' => true,
                'incoming_message_sound' => 'bubble_pop',
                'incoming_message_volume' => 75,
                'custom_incoming_msg_sound_path' => null,
            ]);
        } elseif ($type === 'outgoing_message') {
            $settings->update([
                'outgoing_message_sound_enabled' => true,
                'outgoing_message_sound' => 'subtle_sent',
                'outgoing_message_volume' => 70,
                'custom_outgoing_msg_sound_path' => null,
            ]);
        } else {
            // Global reset of all 3 sound types
            $settings->update([
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
                'custom_call_sound_path' => null,
                'custom_incoming_msg_sound_path' => null,
                'custom_outgoing_msg_sound_path' => null,
            ]);
        }

        return $this->getUserSoundSettings($user);
    }

    /**
     * Upload custom audio file for a sound category.
     *
     * @return array<string, mixed>
     */
    public function uploadCustomSound(User $user, string $type, UploadedFile $file): array
    {
        if (! in_array($type, ['incoming_call', 'incoming_message', 'outgoing_message'], true)) {
            throw ValidationException::withMessages([
                'type' => ['অবৈধ সাউন্ড ক্যাটাগরি।'],
            ]);
        }

        $path = $file->store("sounds/custom/{$user->id}", 'public');

        /** @var UserSetting $settings */
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        if ($type === 'incoming_call') {
            $settings->update([
                'custom_call_sound_path' => $path,
                'incoming_call_sound' => 'custom',
            ]);
        } elseif ($type === 'incoming_message') {
            $settings->update([
                'custom_incoming_msg_sound_path' => $path,
                'incoming_message_sound' => 'custom',
            ]);
        } else {
            $settings->update([
                'custom_outgoing_msg_sound_path' => $path,
                'outgoing_message_sound' => 'custom',
            ]);
        }

        return $this->getUserSoundSettings($user);
    }
}
