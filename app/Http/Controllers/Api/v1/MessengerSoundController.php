<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Messenger\MessengerSoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerSoundController extends Controller
{
    public function __construct(
        protected MessengerSoundService $soundService
    ) {}

    /**
     * Get the current user's sound preferences and sound catalogue.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $settings = $this->soundService->getUserSoundSettings($user);

        return response()->json([
            'success' => true,
            'message' => 'মেসেঞ্জার সাউন্ড সেটিংস সফলভাবে লোড হয়েছে।',
            'data' => $settings,
        ]);
    }

    /**
     * Update the user's sound preferences.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'messenger_sounds_enabled' => ['nullable', 'boolean'],

            // 1. Incoming Call
            'incoming_call_sound_enabled' => ['nullable', 'boolean'],
            'incoming_call_sound' => ['nullable', 'string', 'in:bondhoo_ring,digital_bell,celesta_chime,custom'],
            'incoming_call_volume' => ['nullable', 'integer', 'min:0', 'max:100'],

            // 2. Incoming Message
            'incoming_message_sound_enabled' => ['nullable', 'boolean'],
            'incoming_message_sound' => ['nullable', 'string', 'in:bubble_pop,clear_ding,soft_chime,custom'],
            'incoming_message_volume' => ['nullable', 'integer', 'min:0', 'max:100'],

            // 3. Outgoing Message
            'outgoing_message_sound_enabled' => ['nullable', 'boolean'],
            'outgoing_message_sound' => ['nullable', 'string', 'in:subtle_sent,soft_swoosh,quick_click,custom'],
            'outgoing_message_volume' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'incoming_call_sound.in' => 'নির্বাচিত কল রিংটোনটি সঠিক নয়।',
            'incoming_message_sound.in' => 'নির্বাচিত মেসেজ টোনটি সঠিক নয়।',
            'outgoing_message_sound.in' => 'নির্বাচিত সেন্ট টোনটি সঠিক নয়।',
            'incoming_call_volume.max' => 'ভলিউম ০ থেকে ১০০ এর মধ্যে হতে হবে।',
            'incoming_message_volume.max' => 'ভলিউম ০ থেকে ১০০ এর মধ্যে হতে হবে।',
            'outgoing_message_volume.max' => 'ভলিউম ০ থেকে ১০০ এর মধ্যে হতে হবে।',
        ]);

        $settings = $this->soundService->updateUserSoundSettings($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'মেসেঞ্জার সাউন্ড পছন্দ সফলভাবে সংরক্ষিত হয়েছে।',
            'data' => $settings,
        ]);
    }

    /**
     * Restore default sound settings for a category or all sounds.
     */
    public function reset(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $type = $request->input('type');
        if ($type && ! in_array($type, ['incoming_call', 'incoming_message', 'outgoing_message'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'অবৈধ সাউন্ড ক্যাটাগরি।',
            ], 422);
        }

        $settings = $this->soundService->resetUserSoundSettings($user, $type);

        return response()->json([
            'success' => true,
            'message' => 'ডিফল্ট সাউন্ড সেটিংস পুনরুদ্ধার করা হয়েছে।',
            'data' => $settings,
        ]);
    }

    /**
     * Upload a custom audio file for a sound category.
     */
    public function upload(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'type' => ['required', 'string', 'in:incoming_call,incoming_message,outgoing_message'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,ogg,aac,m4a', 'max:2048'], // max 2MB
        ], [
            'type.in' => 'অবৈধ সাউন্ড ক্যাটাগরি।',
            'audio.required' => 'একটি অডিও ফাইল নির্বাচন করুন।',
            'audio.mimes' => 'শুধুমাত্র MP3, WAV, OGG, AAC বা M4A ফরম্যাটের অডিও ফাইল গ্রহণযোগ্য।',
            'audio.max' => 'অডিও ফাইলের সাইজ সর্বোচ্চ ২ মেগাবাইট হতে পারবে।',
        ]);

        $settings = $this->soundService->uploadCustomSound($user, (string) $request->input('type'), $request->file('audio'));

        return response()->json([
            'success' => true,
            'message' => 'কাস্টম অডিও সফলভাবে আপলোড হয়েছে।',
            'data' => $settings,
        ]);
    }
}
