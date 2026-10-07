# Real-time WebSocket Protocol & Event Catalog

## 1. WebSocket Transport
Jugajug uses WebSockets (WSS) via Laravel Reverb / Pusher-compatible protocol as the primary real-time transport.
- WebSocket URL: `wss://jugajug.com/app/{key}`
- Auth Endpoint: `/api/v1/broadcasting/auth` (Bearer Sanctum token)

## 2. Channels
- `private-conversation.{conversation_id}`: Conversation-level events (messages, typing, reads).
- `private-user.{user_id}`: User-level events (incoming calls, device sync, notifications).
- `presence-conversation.{conversation_id}`: Online participant presence, typing indicators.

## 3. Event Catalog
| Event Name | Channel | Description | Payload |
|---|---|---|---|
| `message.created` | `private-conversation.{id}` | New message broadcast | `{ id, content, sender_id, type, created_at, client_uuid }` |
| `message.edited` | `private-conversation.{id}` | Message updated | `{ id, content, edited_at }` |
| `message.deleted` | `private-conversation.{id}` | Message removed | `{ id }` |
| `message.delivered` | `private-conversation.{id}` | Delivered receipt | `{ message_id, user_id, delivered_at }` |
| `message.read` | `private-conversation.{id}` | Read receipt | `{ message_id, user_id, read_at }` |
| `message.pinned` | `private-conversation.{id}` | Pinned message | `{ pin_id, message_id, pinned_by }` |
| `message.unpinned` | `private-conversation.{id}` | Unpinned message | `{ message_id }` |
| `typing.started` | `presence-conversation.{id}` | User started typing | `{ user_id, name }` |
| `typing.stopped` | `presence-conversation.{id}` | User stopped typing | `{ user_id }` |
| `call.incoming` | `private-user.{target_user_id}` | Incoming call alert | `{ call_id, caller, call_type, room_id }` |
| `call.accepted` | `private-conversation.{id}` | Call answered | `{ call_id, user_id }` |
| `call.rejected` | `private-conversation.{id}` | Call rejected | `{ call_id, user_id }` |
| `call.ended` | `private-conversation.{id}` | Call finished | `{ call_id, duration_seconds }` |
| `call.signal` | `private-user.{target_user_id}` | WebRTC signaling packet | `{ call_id, signal_type, payload, sender_id }` |
| `call.participant_state` | `private-conversation.{id}` | Mic/Camera/Screen state | `{ call_id, user_id, is_muted, is_camera_off, is_screen_sharing }` |
