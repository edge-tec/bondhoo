# Offline-First Synchronization & Event Replay Protocol

## 1. Synchronization Architecture
The Jugajug Messenger uses a two-tier real-time & catchup synchronization model:
1. **Real-time Tier**: WebSockets push instantaneous events (`message.created`, `message.delivered`, `call.incoming`).
2. **Recovery / Replay Tier**: The sync endpoint `/api/v1/messenger/sync?since_id={id}&limit={n}` recovers missed events after disconnection or app restart.

## 2. Monotonic Sync Cursor
- Every meaningful change is persisted to `messenger_sync_events` with an autoincrementing integer `id`.
- The client stores `last_sync_id`.
- When network reconnects, the client requests `GET /api/v1/messenger/sync?since_id=last_sync_id`.
- If `has_more` is true, the client pages through events until caught up with the server.

## 3. Offline Outbox & Idempotency
- When offline, messages are stored locally in the outbox with an idempotency key (`client_uuid`).
- Upon reconnection, outbox items are posted sequentially.
- If the server already processed a message with that `client_uuid` (e.g. connection dropped during HTTP response), it returns the existing message without creating a duplicate.
