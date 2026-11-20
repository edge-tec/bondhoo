# PHASE 13.7 & 13.8 — Database Integrity, Notifications & Background Processing Audit

## 1. Database Schema & Migration Integrity
- **Migration Status:** All 24 migration batches ran cleanly (`php artisan migrate:status`).
- **Latest Enterprise Migration (`2026_11_20_143000_create_ultra_enterprise_messenger_tables.php`):**
  - `chat_polls`: `conversation_id`, `message_id`, `creator_id` foreign keys with cascade delete.
  - `chat_poll_options`: `poll_id` foreign key with cascade delete.
  - `chat_poll_votes`: unique composite index on `['poll_id', 'poll_option_id', 'user_id']` preventing duplicate votes under concurrent requests.
  - `scheduled_messages`: composite index on `['status', 'scheduled_at']` for fast queue polling.
  - `messages.expires_at`: indexed timestamp column for disappearing message expiration queries.
- **Transaction Boundaries:**
  - `EnterpriseMessengerService::createPoll`, `castPollVote`, `scheduleMessage`, `dispatchDueScheduledMessages`, and `GroupService::createGroup` wrap multi-table writes in `DB::transaction(...)`.

## 2. Notifications & Background Processing
- **Scheduled Queue Command:** `messenger:process-queue` (`ProcessEnterpriseMessengerQueueCommand.php`) is registered in `routes/console.php` (`Schedule::command('messenger:process-queue')->everyMinute()->withoutOverlapping()`).
  - Dispatches due `scheduled_messages` (`status = 'pending'`, `scheduled_at <= now()`) and purges expired disappearing messages (`expires_at <= now()`).
- **Real-Time Notification Broadcasting:**
  - Fixed `RealtimeService::broadcastToUser($userId, $event, $payload)` so custom notification events (`notification.updated`, `notification.read`, `notification.read_all`, `notification.deleted`) are broadcast with their actual event names instead of hardcoded `notification.new`.
- **Android Notification Sync:**
  - Fixed `ReverbWebSocketManager.kt` to handle `notification.read_all` and parse `id` (fallback `notification_id`) on read/delete events.
