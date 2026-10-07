# Jugajug Messenger Architecture & API Specification

## 1. Overview
The Jugajug Messenger is a single, client-independent, API-first communication platform powering Web, Android, iOS, macOS, Windows, and Linux. All clients use the same REST API contracts, WebSocket realtime channels, and synchronization endpoints.

## 2. API Endpoints
Base URL: `/api/v1`

### 2.1 Conversations
- `GET /conversations`: Retrieve paginated list of conversations with last messages, unread counts, and active participants.
- `POST /conversations`: Create 1-to-1 or group conversation.
- `GET /conversations/{id}`: Detailed conversation metadata, members, and settings.
- `DELETE /conversations/{id}`: Delete or leave conversation.
- `POST /conversations/{id}/mute`: Mute/unmute conversation notifications.

### 2.2 Messages
- `GET /conversations/{id}/messages`: Fetch paginated messages with cursor-based pagination.
- `POST /conversations/{id}/messages`: Send message with text, media, voice, parent reply, and idempotency key (`client_uuid`).
- `PUT /messages/{id}`: Edit message content.
- `DELETE /messages/{id}`: Delete message for self or everyone.
- `POST /messages/{id}/read`: Mark message as read, triggering delivery/read receipts.
- `POST /messages/{id}/delivered`: Mark message as delivered.

### 2.3 Pinned & Saved Messages
- `GET /conversations/{id}/pins`: List pinned messages.
- `POST /conversations/{id}/messages/{messageId}/pin`: Pin message in conversation.
- `DELETE /conversations/{id}/messages/{messageId}/pin`: Unpin message.
- `GET /saved-messages`: List saved messages for current user.
- `POST /messages/{messageId}/save`: Bookmark/save message.
- `DELETE /messages/{messageId}/save`: Remove message bookmark.

### 2.4 Voice Notes
- `POST /conversations/{id}/voice-messages`: Multipart upload of audio file (`audio/webm`, `audio/mp4`, `audio/ogg`, `audio/wav`, `audio/aac`). Calculates duration and generates waveform array.

### 2.5 Search
- `GET /messenger/search?query={term}&conversation_id={optional}`: Full-text search across conversations, message content, and media types.
