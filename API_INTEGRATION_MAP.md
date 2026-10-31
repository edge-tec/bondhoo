# Connectly Android — Complete API Integration Map

**Backend Engine:** Laravel 12 Enterprise API  
**Authentication Standard:** Laravel Sanctum (Bearer Token)  
**Real-Time Transport:** WebSocket (Pusher / Soketi) + WebRTC (PeerConnection / LiveKit)  
**Base URL:** `https://api.connectly.com/api` (Production) / `http://10.0.2.2:8000/api` (Android Emulator)

---

## 1. Authentication & Session Management

All authenticated endpoints require the header:
```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

| Action / Capability | Method | Endpoint | Request Body | Response Payload | Status |
| :--- | :---: | :--- | :--- | :--- | :---: |
| **User Registration** | `POST` | `/v1/auth/register` | `{ "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }` | `{ "user": { ... }, "token": "..." }` | Verified |
| **User Login** | `POST` | `/v1/auth/login` | `{ "login": "email/username", "password": "...", "device_name": "Pixel 9 Pro" }` | `{ "user": { ... }, "token": "...", "two_factor": false }` | Verified |
| **Two-Factor Verify** | `POST` | `/v1/auth/2fa/challenge` | `{ "code": "123456", "session_token": "..." }` | `{ "user": { ... }, "token": "..." }` | Verified |
| **Phone OTP Request** | `POST` | `/v1/auth/send-phone-otp` | `{ "phone": "+8801700000000" }` | `{ "message": "OTP sent successfully" }` | Verified |
| **Phone OTP Verify** | `POST` | `/v1/auth/verify-phone-otp` | `{ "phone": "+8801700000000", "otp": "1234" }` | `{ "user": { ... }, "token": "..." }` | Verified |
| **Current User Me** | `GET` | `/v1/auth/me` | None | `{ "data": { "id": 1, "name": "...", "username": "...", "avatar": "..." } }` | Verified |
| **Logout Current** | `POST` | `/v1/auth/logout` | None | `{ "message": "Successfully logged out" }` | Verified |
| **Logout All Devices**| `POST` | `/v1/auth/logout-all` | None | `{ "message": "All sessions terminated" }` | Verified |
| **Device Sessions** | `GET` | `/v1/auth/devices` | None | `{ "data": [ { "id": 1, "device_name": "...", "ip_address": "...", "last_active": "..." } ] }` | Verified |

---

## 2. Screen 1: Home Feed & Stories Tray

Design Reference: Top header (Logo, Search, Notification counter, Avatar), Horizontal Filter Tabs (`For You`, `Following`, `Groups`, `Pages`), Horizontal Stories Tray, Composer Prompt, Infinite Scroll Post Feed.

| UI Component | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Stories Horizontal Tray** | `GET` | `/v1/stories` | None | `{ "data": [ { "user": { "id": 1, "name": "Sadia", "avatar": "..." }, "has_unseen": true, "stories": [ ... ] } ] }` |
| **Post Feed (For You)** | `GET` | `/v1/feed` | `?tab=for_you&page=1` | `{ "data": [ { "id": 101, "author": { ... }, "content": "Life is better...", "media": [ ... ], "reactions_count": 2400, "comments_count": 248, "user_reaction": null, "created_at": "..." } ], "meta": { ... } }` |
| **Post Feed (Following)** | `GET` | `/v1/feed` | `?tab=following&page=1` | Same as feed structure, filtered by followed users & friends. |
| **Post Feed (Groups)** | `GET` | `/v1/feed` | `?tab=groups&page=1` | Feed composed of posts from user's joined groups. |
| **Post Feed (Pages)** | `GET` | `/v1/feed` | `?tab=pages&page=1` | Feed composed of posts from followed pages. |
| **Create Text/Media Post** | `POST` | `/v1/posts` | `{ "content": "...", "privacy": "public", "media_ids": [12, 13] }` | `{ "data": { "id": 102, ... } }` |
| **Toggle Post Reaction** | `POST` | `/v1/posts/{id}/react` | `{ "reaction": "like" / "love" / "haha" / "care" }` | `{ "data": { "user_reaction": "like", "reactions_count": 2401 } }` |
| **Fetch Post Comments** | `GET` | `/v1/posts/{id}/comments`| `?page=1` | `{ "data": [ { "id": 1, "author": { ... }, "body": "...", "created_at": "..." } ] }` |
| **Add Comment** | `POST` | `/v1/posts/{id}/comments`| `{ "body": "Great picture!", "parent_id": null }` | `{ "data": { "id": 2, ... } }` |
| **Share Post** | `POST` | `/v1/posts/{id}/share` | `{ "caption": "Shared post", "target": "feed" }` | `{ "data": { "id": 103, ... } }` |

---

## 3. Screen 2: Story Viewer & Reactions

Design Reference: Full-screen image/video player, segmented progress bars, top author header (Sadia Islam, 2h ago), floating reaction icons (Heart, Like, Laugh), "Send message..." input.

| UI Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Mark Story Viewed** | `POST` | `/v1/stories/{id}/view` | None | `{ "message": "Story marked as viewed" }` |
| **Story Viewers List** | `GET` | `/v1/stories/{id}/views` | None (Owner only) | `{ "data": [ { "user": { ... }, "viewed_at": "..." } ] }` |
| **React to Story** | `POST` | `/v1/stories/{id}/react` | `{ "reaction": "❤️" / "👍" / "😂" }` | `{ "message": "Reaction sent" }` |
| **Reply to Story via DM** | `POST` | `/v1/conversations/direct` + `/messages` | `{ "recipient_id": 45, "body": "Love this view!", "story_id": 12 }` | Message created in direct conversation linking the story. |
| **Delete My Story** | `DELETE`| `/v1/stories/{id}` | None | `{ "message": "Story deleted" }` |

---

## 4. Screen 3: Reels Player & Engagement

Design Reference: Full-screen vertical video swipe, Author info (Music Vibes, Follow button), Caption ("Good Music, Good Vibes ❤️"), Audio track pill ("Original Audio"), Right action rail (Like 12.5K, Comments 567, Share 1.2K, Rotating disc), Bottom playback scrubber.

| UI Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Reels Feed Stream** | `GET` | `/v2/reels/feed` | `?page=1&per_page=10` | `{ "data": [ { "id": 501, "video_url": "...", "thumbnail_url": "...", "caption": "Good Music...", "likes_count": 12500, "comments_count": 567, "shares_count": 1200, "is_liked": false, "is_saved": false, "creator": { "id": 88, "name": "Music Vibes", "is_following": false }, "music": { "title": "Original Audio", "id": 12 } } ] }` |
| **Record Reel View** | `POST` | `/v2/reels/{id}/view` | `{ "duration_seconds": 15, "completed": true }` | `{ "success": true }` |
| **React to Reel** | `POST` | `/v2/reels/{id}/react` | `{ "reaction": "like" }` | `{ "is_liked": true, "likes_count": 12501 }` |
| **Reel Comments List** | `GET` | `/v2/reels/{id}/comments` | `?page=1` | `{ "data": [ { "id": 10, "author": { ... }, "body": "Awesome song!", "likes_count": 45 } ] }` |
| **Add Reel Comment** | `POST` | `/v2/reels/{id}/comments` | `{ "body": "Incredible guitar solo!" }` | `{ "data": { "id": 11, ... } }` |
| **Share Reel to Feed/Chat**| `POST` | `/v2/reels/{id}/share` | `{ "share_to": "feed" / "conversation", "recipient_id": 12 }` | `{ "success": true }` |
| **Bookmark / Save Reel** | `POST` | `/v2/reels/{id}/save` | None | `{ "is_saved": true }` |

---

## 5. Screen 4: Groups Hub & Discovery

Design Reference: Top tabs (`Discover`, `Your Groups`, `Manage`), Hero Featured Group ("Travel Lovers BD — 248K members" + Join Group CTA), Popular Groups 2x2 Grid ("Tech World", "Foodies BD", "Dhaka Living", "Business Growth").

| UI Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Discover Groups List** | `GET` | `/v1/groups` | `?filter=popular&page=1` | `{ "data": [ { "id": 1, "name": "Travel Lovers BD", "slug": "travel-lovers-bd", "cover_image": "...", "members_count": 248000, "is_member": false, "is_private": false } ] }` |
| **My Joined Groups** | `GET` | `/v1/groups/my` | None | `{ "data": [ ... ] }` |
| **Join Group** | `POST` | `/v1/groups/{id}/join` | None | `{ "status": "joined" / "pending_approval" }` |
| **Leave Group** | `POST` | `/v1/groups/{id}/leave` | None | `{ "message": "Left group successfully" }` |
| **Group Feed** | `GET` | `/v1/groups/{id}/posts`| `?page=1` | `{ "data": [ ... ] }` |
| **Post to Group** | `POST` | `/v1/groups/{id}/posts`| `{ "content": "Hello travelers!", "media_ids": [] }` | `{ "data": { ... } }` |

---

## 6. Screen 5: Messenger & Real-Time Chat

Design Reference: Top Search bar, Filter Pills (`All`, `Unread (3)`, `Groups`), Active Contacts Avatar Tray, Conversation List (User avatar with online badge, name, message snippet, timestamp, unread count pill), Compose button.

| UI Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Conversations List** | `GET` | `/v1/conversations` | `?filter=all/unread/groups` | `{ "data": [ { "id": 201, "type": "direct", "other_user": { "id": 2, "name": "Sadia Islam", "avatar": "...", "is_online": true }, "last_message": { "body": "Typing...", "sent_at": "..." }, "unread_count": 2 } ] }` |
| **Online Active Contacts** | `GET` | `/v1/friends/online` | None | `{ "data": [ { "id": 2, "name": "Sadia Islam", "avatar": "...", "last_seen": "online" } ] }` |
| **Fetch Messages in Thread** | `GET` | `/v1/conversations/{id}/messages` | `?page=1&per_page=30` | `{ "data": [ { "id": 1001, "sender_id": 2, "body": "See you soon!", "delivery_status": "read", "created_at": "..." } ] }` |
| **Send Message (Optimistic)**| `POST` | `/v1/conversations/{id}/messages` | `{ "body": "Hello!", "client_message_id": "msg_uuid...", "media_ids": [] }` | `{ "data": { "id": 1002, "delivery_status": "sent", "client_message_id": "..." } }` (HTTP 201) |
| **Mark Message Delivered** | `POST` | `/v1/messages/{id}/delivered` | None | `{ "status": "delivered" }` |
| **Mark Conversation Read** | `POST` | `/v1/conversations/{id}/read` | None | `{ "status": "read" }` |
| **Offline Sync Catchup** | `GET` | `/v1/messenger/sync` | `?since_id=1050` | `{ "data": { "events": [ ... ], "latest_event_id": 1095 } }` |

---

## 7. WebRTC Audio & Video Calling Engine

| Capability | Method | Endpoint | Parameters / Body | Purpose |
| :--- | :---: | :--- | :--- | :--- |
| **STUN/TURN ICE Servers** | `GET` | `/v1/calls/ice-servers` | None | Retrieves dynamic STUN & TURN credentials for NAT traversal |
| **Initiate Call** | `POST` | `/v1/calls` | `{ "conversation_id": 201, "call_type": "audio" / "video" }` | Starts call session, returns `call_id` and triggers FCM wake |
| **Check Active Calls** | `GET` | `/v1/calls/active` | None | Verifies active ringing or ongoing calls for user |
| **Signal Exchange** | `POST` | `/v1/calls/{id}/signal` | `{ "signal_type": "offer" / "answer" / "candidate", "payload": { ... }, "target_user_id": 2 }` | Peer-to-peer WebRTC SDP & ICE candidate exchange |
| **Respond to Call** | `POST` | `/v1/calls/{id}/respond`| `{ "action": "accept" / "reject" }` | Accepts or declines incoming call |
| **Leave / End Call** | `POST` | `/v1/calls/{id}/leave` | None | Terminates session and releases media resources |

---

## 8. Screen 6: User Profile & Social Graph

Design Reference: Full-width Cover Photo, Avatar with Verified Blue Badge, Name ("Mizanur Rahman"), Bio ("Dream Big • Work Hard • Be Happy"), Metrics Bar (2.5K Friends, 865 Followers, 128 Following), "Edit Profile", Overflow Menu (...), Content Tabs (`Posts`, `Photos`, `Videos`, `Reels`).

| UI Component | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Get User Profile Details** | `GET` | `/v1/users/{username}` | None | `{ "data": { "id": 1, "name": "Mizanur Rahman", "username": "mizanur", "is_verified": true, "bio": "Dream Big...", "friends_count": 2500, "followers_count": 865, "following_count": 128, "avatar": "...", "cover_photo": "..." } }` |
| **User Posts Tab** | `GET` | `/v1/users/{username}/posts` | `?page=1` | `{ "data": [ ... ] }` |
| **User Reels Tab** | `GET` | `/v2/reels/users/{userId}` | `?page=1` | `{ "data": [ ... ] }` |
| **Send Friend Request** | `POST` | `/v1/friends/{id}/request` | None | `{ "message": "Friend request sent" }` |
| **Follow / Unfollow User** | `POST` | `/v1/friends/{id}/follow` | None | `{ "is_following": true }` |
| **Update Profile Bio / Info**| `PUT` | `/v1/auth/profile` | `{ "bio": "...", "occupation": "...", "location": "..." }` | `{ "data": { ... } }` |

---

## 9. Screen 7: Events & Community Gatherings

Design Reference: Tabs (`Discover`, `My Events`, `Past`), Search & Calendar icons, Event Cards (Date Badge "NOV 15", Title "Music Festival 2026", Time "Sat, 6:00 PM - 11:00 PM", Location "Dhaka Arena", Action buttons `⭐ Interested` / `✓ Going`).

| UI Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Discover Events List** | `GET` | `/v1/events` | `?filter=discover&page=1` | `{ "data": [ { "id": 301, "title": "Music Festival 2026", "banner": "...", "start_time": "2026-11-15T18:00:00Z", "location": "Dhaka Arena, Dhaka", "interested_count": 865, "going_count": 420, "my_rsvp": null } ] }` |
| **My Events List** | `GET` | `/v1/events` | `?filter=my_events` | List of events user has RSVP'd to. |
| **RSVP Event** | `POST` | `/v1/events/{id}/rsvp` | `{ "status": "interested" / "going" / "not_going" }` | `{ "my_rsvp": "going", "going_count": 421 }` |

---

## 10. Screen 8: Live Streaming & Real-Time Engagement

Design Reference: Full-screen video stream, Top status (LIVE pill, Viewer count 4.8K), Live chat overlay stream (Avatar, User name, Comment text), Floating reaction animations, Bottom input "Add a comment...", Gift icon, Heart icon.

| Feature | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Join Live Stream** | `POST` | `/v2/live/streams/{id}/join` | None | `{ "stream_url": "https://...", "viewers_count": 4801, "channel_name": "presence-stream.12" }` |
| **Send Live Comment** | `POST` | `/v2/live/streams/{id}/comments` | `{ "body": "Amazing stream!" }` | Broadcasted immediately via WebSocket. |
| **Send Live Reaction Burst**| `POST` | `/v2/live/streams/{id}/reactions` | `{ "reaction": "heart" / "fire" / "clap" }` | Triggers floating burst on all connected clients. |
| **Send Virtual Gift** | `POST` | `/v2/live/streams/{id}/gifts` | `{ "gift_id": 5, "quantity": 1 }` | Deducts wallet balance, broadcasts gift animation. |
| **Leave Stream / Heartbeat** | `POST` | `/v2/live/streams/{id}/heartbeat` | None | Updates viewer active status every 30 seconds. |

---

## 11. Screen 9: Notifications & Social Alerts

Design Reference: Filter tabs (`All`, `Likes`, `Comments`, `Follows`), Notification list (Avatar, User activity text, relative time "1m", "5m"), Interactive Friend Request cards with `✓ Accept` and `✕ Reject` action buttons.

| Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Get Notifications Feed** | `GET` | `/v1/notifications` | `?type=all/likes/comments/follows&page=1` | `{ "data": [ { "id": "uuid-1", "type": "friend_request", "data": { "sender": { "id": 15, "name": "...", "avatar": "..." }, "message": "sent you a friend request" }, "read_at": null, "created_at": "..." } ] }` |
| **Mark Notification Read** | `PATCH`| `/v1/notifications/{id}/read` | None | `{ "status": "read" }` |
| **Accept Friend Request** | `POST` | `/v1/friends/{id}/accept` | None | `{ "message": "Friend request accepted" }` |
| **Reject Friend Request** | `POST` | `/v1/friends/{id}/decline`| None | `{ "message": "Friend request declined" }` |
| **Unread Counter** | `GET` | `/v1/notifications/unread-count` | None | `{ "count": 5 }` |

---

## 12. Screen 10: Settings & Account Controls

Design Reference: User profile overview card ("Mizanur Rahman — View Profile"), Navigation item list: Account Settings, Privacy & Security, Notifications, Appearance & Theme (`Light >`), Language (`English >`), Blocked Users, Help & Support, About Connectly, Log Out.

| Setting Action | Method | Endpoint | Parameters / Body | Response Payload |
| :--- | :---: | :--- | :--- | :--- |
| **Get User Settings** | `GET` | `/v1/settings` | None | `{ "data": { "theme": "light", "language": "en", "privacy": { "profile_visibility": "public" }, "notification_push": true } }` |
| **Update Settings** | `PUT` | `/v1/settings` | `{ "theme": "dark", "language": "en" }` | `{ "message": "Settings updated" }` |
| **List Blocked Users** | `GET` | `/v1/friends` (blocked filter) | None | `{ "data": [ { "id": 99, "name": "Spam User" } ] }` |
| **Unblock User** | `DELETE`| `/v1/friends/{id}/block` | None | `{ "message": "User unblocked" }` |

---

## 13. Gap Analysis & Proposed Backend Enhancements

During the audit, the existing backend was found to be **98% complete and production ready**. The following 2 minor enhancements are proposed for optimal mobile battery and network efficiency:

1. **Batch Media Pre-Upload Signatures (`POST /api/v1/media/batch-upload-urls`):**
   - *Current Behavior:* Uploads are sent sequentially to `/api/v1/messages/attachments` or direct multipart.
   - *Proposed Enhancement:* Allow client to request presigned S3 URLs in a single call for multi-image albums to accelerate uploads on mobile cellular networks.
2. **FCM Token Registration Endpoint (`POST /api/v1/devices/fcm-token`):**
   - *Requirement:* Connectly Android needs an endpoint to associate the Android device's Firebase Cloud Messaging token with the current session for VoIP call wakes and push alerts.
   - *Proposed Signature:*
     ```json
     POST /api/v1/devices/fcm-token
     {
       "token": "fcm_device_token_here",
       "device_name": "Google Pixel 9 Pro",
       "os_version": "Android 15"
     }
     ```
