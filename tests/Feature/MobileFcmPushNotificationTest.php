<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DeviceSession;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\MessengerService;
use App\Services\Notification\EnterpriseNotificationService;
use App\Services\Push\PushNotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileFcmPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected Conversation $conversation;

    protected PushNotificationService $pushService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->userA = User::factory()->create([
            'name' => 'সাকিব কলার',
            'username' => 'shakib_caller',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'তামিম রিসিভার',
            'username' => 'tamim_receiver',
        ]);

        // Friendship
        Friendship::create([
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Direct Conversation
        $this->conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userB->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        $this->pushService = app(PushNotificationService::class);
    }

    public function test_mobile_device_can_register_fcm_push_token(): void
    {
        $response = $this->actingAs($this->userB, 'sanctum')->postJson('/api/v1/devices/register', [
            'device_id' => 'android_pixel_8_pro_unique_id',
            'device_name' => 'Google Pixel 8 Pro',
            'platform' => 'android',
            'app_version' => '2.4.0',
            'push_token' => 'fcm_sample_token_xyz_987654321',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'user_id' => $this->userB->id,
            'device_id' => 'android_pixel_8_pro_unique_id',
            'platform' => 'android',
            'push_token' => 'fcm_sample_token_xyz_987654321',
            'is_revoked' => false,
        ]);
    }

    public function test_incoming_call_triggers_high_priority_voip_fcm_push(): void
    {
        // Register callee mobile device
        DeviceSession::create([
            'user_id' => $this->userB->id,
            'device_id' => 'callee_phone_123',
            'platform' => 'android',
            'push_token' => 'fcm_token_callee_456',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $fcmCalled = false;
        Http::fake([
            'https://fcm.googleapis.com/*' => function ($request) use (&$fcmCalled) {
                $fcmCalled = true;
                $data = $request->data();

                // Assert payload structure
                $this->assertArrayHasKey('message', $data);
                $this->assertEquals('fcm_token_callee_456', $data['message']['token']);
                $this->assertEquals('high', $data['message']['android']['priority']);
                $this->assertEquals('incoming_calls', $data['message']['android']['notification']['channel_id']);
                $this->assertEquals('call.incoming', $data['message']['data']['type']);

                return Http::response(['name' => 'projects/bondhoo-messenger/messages/123456'], 200);
            },
        ]);

        // Initiate call from User A to User B
        $callingService = app(CallingService::class);
        $call = $callingService->initiateCall(
            caller: $this->userA,
            conversation: $this->conversation,
            callType: 'audio'
        );

        $this->assertNotNull($call);
        $this->assertTrue($fcmCalled, 'FCM VoIP Push must be dispatched when call is initiated');
    }

    public function test_chat_message_dispatches_fcm_push_to_recipient_mobile(): void
    {
        // Register recipient device
        DeviceSession::create([
            'user_id' => $this->userB->id,
            'device_id' => 'recipient_phone_789',
            'platform' => 'android',
            'push_token' => 'fcm_token_recipient_999',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $fcmCalled = false;
        Http::fake([
            'https://fcm.googleapis.com/*' => function ($request) use (&$fcmCalled) {
                $fcmCalled = true;
                $data = $request->data();

                $this->assertEquals('fcm_token_recipient_999', $data['message']['token']);
                $this->assertEquals($this->userA->name, $data['message']['notification']['title']);
                $this->assertEquals('কেমন আছেন ভাই?', $data['message']['notification']['body']);
                $this->assertEquals('chat.message', $data['message']['data']['type']);

                return Http::response(['name' => 'projects/bondhoo-messenger/messages/654321'], 200);
            },
        ]);

        $messengerService = app(MessengerService::class);
        $message = $messengerService->sendMessage($this->userA, $this->conversation, [
            'body' => 'কেমন আছেন ভাই?',
            'type' => 'text',
        ]);

        $this->assertNotNull($message);
        $this->assertTrue($fcmCalled, 'FCM message push must be dispatched on new chat message');
    }

    public function test_muted_conversation_suppresses_fcm_chat_message_push(): void
    {
        // Mute conversation for User B
        ConversationParticipant::where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->userB->id)
            ->update(['is_muted' => true]);

        DeviceSession::create([
            'user_id' => $this->userB->id,
            'device_id' => 'recipient_muted_phone',
            'platform' => 'android',
            'push_token' => 'fcm_token_muted',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $fcmCalled = false;
        Http::fake([
            'https://fcm.googleapis.com/*' => function () use (&$fcmCalled) {
                $fcmCalled = true;

                return Http::response(['name' => 'dummy'], 200);
            },
        ]);

        $messengerService = app(MessengerService::class);
        $messengerService->sendMessage($this->userA, $this->conversation, [
            'body' => 'এটি একটি সাইলেন্ট মেসেজ',
            'type' => 'text',
        ]);

        $this->assertFalse($fcmCalled, 'FCM push must NOT be sent if conversation is muted by recipient');
    }

    public function test_fcm_unregistered_token_automatically_revokes_device_session(): void
    {
        $device = DeviceSession::create([
            'user_id' => $this->userB->id,
            'device_id' => 'stale_token_phone',
            'platform' => 'android',
            'push_token' => 'stale_dead_token_123',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'UNREGISTERED',
                ],
            ], 404),
        ]);

        $this->pushService->sendToUser($this->userB, 'টেস্ট', 'টেস্ট বডি');

        $device->refresh();
        $this->assertTrue($device->is_revoked, 'Device with unregistered token must be marked as revoked');
        $this->assertNull($device->push_token, 'Push token should be cleared on invalid registration');
    }

    public function test_enterprise_notification_service_dispatches_push_via_fcm(): void
    {
        DeviceSession::create([
            'user_id' => $this->userB->id,
            'device_id' => 'notification_receiver_device',
            'platform' => 'android',
            'push_token' => 'fcm_notification_token_777',
            'is_revoked' => false,
            'last_active_at' => now(),
        ]);

        $fcmCalled = false;
        Http::fake([
            'https://fcm.googleapis.com/*' => function ($request) use (&$fcmCalled) {
                $fcmCalled = true;
                $data = $request->data();
                $this->assertEquals('fcm_notification_token_777', $data['message']['token']);
                $this->assertEquals('নিরাপত্তা সতর্কতা', $data['message']['notification']['title']);

                return Http::response(['name' => 'projects/bondhoo-messenger/messages/777'], 200);
            },
        ]);

        $notificationService = app(EnterpriseNotificationService::class);
        $result = $notificationService->dispatch(
            userId: $this->userB->id,
            type: 'security_alert',
            title: 'নিরাপত্তা সতর্কতা',
            message: 'আপনার অ্যাকাউন্টে নতুন ডিভাইস থেকে লগইন করা হয়েছে।',
            payload: ['device' => 'Windows PC'],
            priority: 'critical'
        );

        $this->assertEquals('delivered', $result['status']);
        $this->assertTrue($fcmCalled, 'EnterpriseNotificationService must trigger FCM push to mobile devices');
    }
}
