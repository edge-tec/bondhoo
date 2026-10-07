# Messenger & Calling Security Specification

## 1. Authorization & Tenant Isolation
- **Conversation Access**: Every message, pin, save, and sync request verifies that the authenticated user is an active participant in `conversation_user`.
- **Call Authorization**: Calls can only be initiated within conversations where the user is an active member. Signals and participant state updates reject unauthorized participants with HTTP 403 Forbidden.
- **Media Protection**: Voice notes, media attachments, and shared files validate MIME type, file signature magic bytes, and ownership.

## 2. Real-time Security
- WebSocket channels are protected by Laravel Sanctum authentication via `/api/v1/broadcasting/auth`.
- Users cannot subscribe to another user's `private-user.{id}` channel or conversation channels they are not members of.
- WebRTC signaling packets are validated against call membership before broadcast.

## 3. Ephemeral TURN Credentials
- Never hardcode persistent TURN credentials in client applications.
- Credentials are generated server-side using HMAC-SHA1 timestamps with an expiration window of 24 hours.

## 4. Input Sanitization & Anti-Abuse
- All message content and voice uploads are sanitized to prevent XSS.
- Rate limiting is enforced across all messaging and signaling endpoints.
