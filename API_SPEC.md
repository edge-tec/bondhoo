# REST API Specification: Version 1 (`/api/v1`)

## 1. Global Conventions & Protocol

### 1.1 Base URL & Content Negotiation
- **Base URL**: `https://api.domain.com/api/v1` (or `http://localhost:8000/api/v1` locally)
- **Content-Type**: `application/json`
- **Accept**: `application/json`

### 1.2 Authentication Scheme
- **Mobile (Flutter)**: HTTP Bearer Token in `Authorization` header:
  `Authorization: Bearer 1|xyz987...`
- **Web (Inertia / Vue 3)**: Laravel Sanctum first-party stateful session cookie with `X-XSRF-TOKEN` CSRF header.

### 1.3 Standard Response Envelopes

#### Success Envelope (200 OK / 201 Created)
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {},
  "meta": {
    "per_page": 15,
    "has_more": true,
    "next_cursor": "eyJpZCI6MTB9",
    "prev_cursor": null
  }
}
```

#### Error Envelope (4xx / 5xx)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email address has already been taken."
    ]
  }
}
```

---

## 2. Phase 1: Authentication & Account Lifecycle Endpoints

### 2.1 User Registration
Registers a new user account, creates default profile and privacy settings, and returns an auth token.

- **Route**: `POST /api/v1/auth/register`
- **Rate Limit**: 5 requests/minute (`throttle:auth`)
- **Headers**: `Accept: application/json`
- **Request Body**:
```json
{
  "name": "Jane Doe",
  "username": "janedoe",
  "email": "jane@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "device_name": "iPhone 15 Pro"
}
```
- **Validation Rules**:
  - `name`: `nullable|string|max:255`
  - `username`: `required|string|alpha_dash|min:3|max:30|unique:users,username`
  - `email`: `required|string|email|max:255|unique:users,email`
  - `password`: `required|string|min:8|confirmed`
  - `device_name`: `nullable|string|max:100`
- **Success Response** (`201 Created`):
```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "user": {
      "id": 42,
      "name": "Jane Doe",
      "username": "janedoe",
      "email": "jane@example.com",
      "email_verified": false,
      "profile": {
        "display_name": "Jane Doe",
        "avatar_url": null,
        "bio": null
      }
    },
    "token": "1|N4u9J3x5Q82mP...",
    "token_type": "Bearer"
  }
}
```

---

### 2.2 User Login
Authenticates user using email, username, or phone number.

- **Route**: `POST /api/v1/auth/login`
- **Rate Limit**: 5 requests/minute (`throttle:auth`)
- **Request Body**:
```json
{
  "identifier": "janedoe",
  "password": "SecurePassword123!",
  "device_name": "Flutter Android"
}
```
- **Validation Rules**:
  - `identifier`: `required|string`
  - `password`: `required|string`
  - `device_name`: `nullable|string`
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 42,
      "name": "Jane Doe",
      "username": "janedoe",
      "email": "jane@example.com",
      "email_verified": true
    },
    "token": "2|K7v8L1w9M64zX...",
    "token_type": "Bearer"
  }
}
```

---

### 2.3 User Logout (Current Device)
Revokes the access token used for the current request.

- **Route**: `POST /api/v1/auth/logout`
- **Middleware**: `auth:sanctum`
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "Successfully logged out.",
  "data": {
    "logged_out": true
  }
}
```

---

### 2.4 Logout from All Devices
Revokes all active Sanctum tokens for the authenticated user.

- **Route**: `POST /api/v1/auth/logout-all`
- **Middleware**: `auth:sanctum`
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "Logged out from all devices.",
  "data": {
    "all_logged_out": true
  }
}
```

---

### 2.5 Authenticated User Profile (`Me`)
Retrieves the current authenticated user's record and relationships.

- **Route**: `GET /api/v1/auth/me`
- **Middleware**: `auth:sanctum`
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Jane Doe",
    "username": "janedoe",
    "email": "jane@example.com",
    "email_verified": true,
    "profile": {
      "display_name": "Jane Doe",
      "avatar_url": "https://s3.amazonaws.com/.../avatar.webp",
      "bio": "Software Architect",
      "location": "Dhaka, Bangladesh"
    },
    "privacy_settings": {
      "profile_visibility": "public",
      "post_default_privacy": "public"
    }
  }
}
```

---

### 2.6 Email Verification
Verifies user email via OTP code or verification token.

- **Route**: `POST /api/v1/auth/verify-email`
- **Rate Limit**: 10 requests/minute
- **Request Body**:
```json
{
  "email": "jane@example.com",
  "otp": "481920"
}
```
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "Email address verified successfully.",
  "data": {
    "verified": true
  }
}
```

---

### 2.7 Resend Email Verification
Resends verification OTP to the user's registered email address.

- **Route**: `POST /api/v1/auth/resend-email`
- **Rate Limit**: 3 requests/minute
- **Request Body**:
```json
{
  "email": "jane@example.com"
}
```
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "A new verification code has been dispatched to your email."
}
```

---

### 2.8 Forgot Password (Request Reset)
Generates and dispatches a password reset OTP/token.

- **Route**: `POST /api/v1/auth/forgot-password`
- **Rate Limit**: 5 requests/minute
- **Request Body**:
```json
{
  "email": "jane@example.com"
}
```
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "If an account exists with this email, a password reset token has been dispatched."
}
```

---

### 2.9 Reset Password
Resets account password using the verified reset token.

- **Route**: `POST /api/v1/auth/reset-password`
- **Request Body**:
```json
{
  "email": "jane@example.com",
  "token": "64_char_hex_token_or_otp",
  "password": "NewSecurePassword456!",
  "password_confirmation": "NewSecurePassword456!"
}
```
- **Success Response** (`200 OK`):
```json
{
  "success": true,
  "message": "Password has been reset successfully. You may now log in."
}
```

---

## 3. Core Social Network Endpoints (Phases 2 – 11)

### 3.1 Profiles & Privacy Settings (Phase 2)
- `GET /api/v1/profile` - Get authenticated profile
- `PUT /api/v1/profile` - Update bio, location, headline
- `PUT /api/v1/profile/username` - Change unique username
- `POST /api/v1/profile/avatar` - Upload new profile avatar
- `POST /api/v1/profile/cover` - Upload new profile cover banner
- `GET /api/v1/users/{username}` - Get public profile by username
- `PUT /api/v1/profile/settings` - Update privacy settings

### 3.2 Friendships, Follows & Blocking (Phase 3)
- `GET /api/v1/friends` - List accepted friends
- `GET /api/v1/friends/requests` - List incoming friend requests
- `POST /api/v1/friends/{userId}/request` - Send friend request
- `POST /api/v1/friends/{userId}/accept` - Accept incoming request
- `POST /api/v1/friends/{userId}/reject` - Decline incoming request
- `DELETE /api/v1/friends/{userId}` - Unfriend user
- `POST /api/v1/users/{userId}/follow` - Follow user
- `DELETE /api/v1/users/{userId}/follow` - Unfollow user
- `POST /api/v1/users/{userId}/block` - Block user
- `DELETE /api/v1/users/{userId}/block` - Unblock user

### 3.3 Posts & Media Engine (Phases 4 & 5)
- `POST /api/v1/posts` - Create post (with `content`, `audience`, `media_ids[]`)
- `GET /api/v1/posts/{id}` - View post details
- `PUT /api/v1/posts/{id}` - Edit post content
- `DELETE /api/v1/posts/{id}` - Soft-delete post
- `POST /api/v1/media/upload` - Upload image attachment (returns `media_id` & URLs)

### 3.4 Reactions & Comments (Phase 6)
- `POST /api/v1/posts/{id}/react` - React to post (`type`: `like`, `love`, `haha`, etc.)
- `GET /api/v1/posts/{id}/reactions` - List reactions breakdown
- `GET /api/v1/posts/{id}/comments` - List root comments (cursor pagination)
- `POST /api/v1/posts/{id}/comments` - Add comment or reply (`body`, `parent_id`)
- `PUT /api/v1/comments/{id}` - Edit comment
- `DELETE /api/v1/comments/{id}` - Delete comment

### 3.5 Home News Feed (Phase 7)
- `GET /api/v1/feed` - Get personalized news feed:
  - **Query Parameters**:
    - `cursor`: Base64 cursor string
    - `per_page`: Default `15` (max `50`)
    - `algorithm`: `chronological` (default) or `smart`
  - **Inclusions**: Viewer posts + accepted friends posts + following posts.
  - **Exclusions**: Blocked users, friends-only posts from non-friends, soft-deleted posts, future scheduled posts.

### 3.6 Notifications (Phase 9)
- `GET /api/v1/notifications` - Paginated notifications
- `GET /api/v1/notifications/unread-count` - Unread notification counter badge
- `PUT /api/v1/notifications/{id}/read` - Mark single notification as read
- `POST /api/v1/notifications/read-all` - Mark all notifications as read

### 3.7 Search & Discovery (Phase 10)
- `GET /api/v1/search?q={query}&type={all|users|posts}` - Universal search

### 3.8 Content Moderation & Reporting (Phase 11)
- `POST /api/v1/reports` - Report content (`reportable_type`, `reportable_id`, `reason`, `details`)
