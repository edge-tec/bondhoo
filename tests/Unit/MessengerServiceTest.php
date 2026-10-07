<?php

namespace Tests\Unit;

use App\Events\MessageDeliveredEvent;
use App\Events\MessageReadEvent;
use App\Events\NewMessageEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * মেসেঞ্জার সার্ভিস ইউনিট টেস্ট:
 * ডিরেক্ট কনভার্সন ডুপ্লিকেট প্রতিরোধ, গ্রুপ চ্যাট, মেসেজ ডেলিভারি এবং সিন স্ট্যাটাস টেস্ট।
 */
class MessengerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessengerServiceInterface $messengerService;

    protected CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messengerService = app(MessengerServiceInterface::class);
        $this->cacheService = app(CacheServiceInterface::class);
    }

    public function test_get_or_create_direct_conversation_creates_new_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = $this->messengerService->getOrCreateDirectConversation($user1, $user2->id);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals(Conversation::TYPE_DIRECT, $conversation->type);
        $this->assertCount(2, $conversation->participants);
    }

    public function test_get_or_create_direct_conversation_reuses_existing_conversation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // প্রথমবার কল করলে নতুন তৈরি হবে
        $conv1 = $this->messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // দ্বিতীয়বার অন্য ইউজার থেকে কল করলেও হুবহু একই কনভার্সন পাওয়া যাবে (ডুপ্লিকেট হবে না)
        $conv2 = $this->messengerService->getOrCreateDirectConversation($user2, $user1->id);

        $this->assertEquals($conv1->id, $conv2->id);
        $this->assertEquals(1, Conversation::count());
    }

    public function test_cannot_create_conversation_with_self(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->messengerService->getOrCreateDirectConversation($user, $user->id);
    }

    public function test_create_group_conversation_with_participants(): void
    {
        $creator = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $conversation = $this->messengerService->createGroupConversation(
            creator: $creator,
            title: 'Project Alpha Team',
            participantIds: [$member1->id, $member2->id]
        );

        $this->assertEquals(Conversation::TYPE_GROUP, $conversation->type);
        $this->assertEquals('Project Alpha Team', $conversation->title);
        $this->assertCount(3, $conversation->participants);

        // Verify creator is admin
        $creatorParticipant = $conversation->participants->firstWhere('user_id', $creator->id);
        $this->assertEquals('admin', $creatorParticipant->role);
    }

    public function test_send_message_updates_conversation_and_increments_redis_counters(): void
    {
        Event::fake([NewMessageEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversation = $this->messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $message = $this->messengerService->sendMessage($sender, $conversation, [
            'body' => 'Hello there!',
        ]);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello there!', $message->body);
        $this->assertEquals(Message::STATUS_SENT, $message->delivery_status);

        // Check conversation last_message_id updated
        $this->assertEquals($message->id, $conversation->fresh()->last_message_id);

        // Check Redis counters incremented
        $unreadKey = "conversation:{$conversation->id}:unread:{$recipient->id}";
        $this->assertEquals(1, (int) $this->cacheService->get($unreadKey));

        $userTotalKey = "user:{$recipient->id}:unread_messages";
        $this->assertEquals(1, (int) $this->cacheService->get($userTotalKey));

        // Verify NewMessageEvent was broadcast
        Event::assertDispatched(NewMessageEvent::class, function ($event) use ($conversation, $message) {
            return $event->conversationId === $conversation->id
                && $event->messageData['id'] === $message->id;
        });
    }

    public function test_mark_as_delivered_updates_status_and_dispatches_event(): void
    {
        Event::fake([MessageDeliveredEvent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->messengerService->getOrCreateDirectConversation($sender, $recipient->id);

        $message = $this->messengerService->sendMessage($sender, $conversation, ['body' => 'Ping']);

        $this->assertEquals(Message::STATUS_SENT, $message->delivery_status);

        $delivered = $this->messengerService->markAsDelivered($message, $recipient);

        $this->assertEquals(Message::STATUS_DELIVERED, $delivered->delivery_status);
        $this->assertNotNull($delivered->delivered_at);

        Event::assertDispatched(MessageDeliveredEvent::class, function ($event) use ($conversation, $message) {
            return $event->conversationId === $conversation->id
                && $event->messageId === $message->id;
        });
    }

    public function test_mark_conversation_as_read_resets_redis_and_marks_messages_seen(): void
    {
        Event::fake([MessageReadEvent::class]);

        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $conversation = $this->messengerService->getOrCreateDirectConversation($sender, $reader->id);

        // Sender sends 2 messages
        $msg1 = $this->messengerService->sendMessage($sender, $conversation, ['body' => 'First']);
        $msg2 = $this->messengerService->sendMessage($sender, $conversation, ['body' => 'Second']);

        // Reader has 2 unread messages
        $unreadKey = "conversation:{$conversation->id}:unread:{$reader->id}";
        $this->assertEquals(2, (int) $this->cacheService->get($unreadKey));

        // Reader marks conversation as read
        $updated = $this->messengerService->markConversationAsRead($conversation, $reader);
        $this->assertEquals(2, $updated);

        // Messages should now have status 'seen'
        $this->assertEquals(Message::STATUS_SEEN, $msg1->fresh()->delivery_status);
        $this->assertEquals(Message::STATUS_SEEN, $msg2->fresh()->delivery_status);
        $this->assertNotNull($msg1->fresh()->read_at);

        // Redis counter for this conversation should be 0
        $this->assertEquals(0, (int) $this->cacheService->get($unreadKey));

        // Event was broadcast
        Event::assertDispatched(MessageReadEvent::class, function ($event) use ($conversation, $reader, $msg2) {
            return $event->conversationId === $conversation->id
                && $event->readerId === $reader->id
                && $event->lastReadMessageId === $msg2->id;
        });
    }
}
