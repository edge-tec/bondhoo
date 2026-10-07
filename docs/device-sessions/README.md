# Multi-Device Sessions & Management Specification

## 1. Device Sessions
Each installation maintains an independent session record in `device_sessions`.
- Unique `device_id` generated on first run.
- Stores `platform` ('web', 'android', 'ios', 'macos', 'windows', 'linux'), `device_name`, `app_version`, `push_token`, and `last_active_at`.

## 2. Session Lifecycle Endpoints
- `POST /api/v1/devices/register`: Register or update active device with new push token or app version.
- `GET /api/v1/devices`: List all registered devices for current user with IP, user agent, and last active timestamp.
- `DELETE /api/v1/devices/{id}`: Revoke a specific device session remotely.
- `POST /api/v1/auth/logout-all`: Invalidate all devices except current one.

## 3. Revocation Enforcement
When a session is revoked, the backend rejects subsequent requests from that token with HTTP 401 Unauthorized, prompting the native client to wipe credentials and return to login screen.
