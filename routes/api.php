<?php

use App\Http\Controllers\Admin\AuthManagementController;
use App\Http\Controllers\Admin\EmailManagementController;
use App\Http\Controllers\Api\v1\Admin\AdminAuthController;
use App\Http\Controllers\Api\v1\Admin\InfrastructureController;
use App\Http\Controllers\Api\v1\AnalyticsController;
use App\Http\Controllers\Api\v1\AppVersionController;
use App\Http\Controllers\Api\v1\Auth\DeviceManagementController;
use App\Http\Controllers\Api\v1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\v1\Auth\LoginController;
use App\Http\Controllers\Api\v1\Auth\PasswordController;
use App\Http\Controllers\Api\v1\Auth\PhoneOtpController;
use App\Http\Controllers\Api\v1\Auth\RegisterController;
use App\Http\Controllers\Api\v1\Auth\TwoFactorController;
use App\Http\Controllers\Api\v1\Auth\WebAuthnController;
use App\Http\Controllers\Api\v1\CallController;
use App\Http\Controllers\Api\v1\CallSignalController;
use App\Http\Controllers\Api\v1\CommentController;
use App\Http\Controllers\Api\v1\ConversationController;
use App\Http\Controllers\Api\v1\DeviceSessionController;
use App\Http\Controllers\Api\v1\FriendController;
use App\Http\Controllers\Api\v1\GroupController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\v1\MessageController;
use App\Http\Controllers\Api\v1\MessengerSearchController;
use App\Http\Controllers\Api\v1\MessengerSoundController;
use App\Http\Controllers\Api\v1\MessengerSyncController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PageController;
use App\Http\Controllers\Api\v1\PinnedMessageController;
use App\Http\Controllers\Api\v1\PostController;
use App\Http\Controllers\Api\v1\PresenceController;
use App\Http\Controllers\Api\v1\ProfileController;
use App\Http\Controllers\Api\v1\ProfileEnterpriseApiController;
use App\Http\Controllers\Api\v1\ReactionController;
use App\Http\Controllers\Api\v1\ReportController;
use App\Http\Controllers\Api\v1\SavedMessageController;
use App\Http\Controllers\Api\v1\SearchController;
use App\Http\Controllers\Api\v1\StoryController;
use App\Http\Controllers\Api\v1\SystemController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\VoiceMessageController;
use App\Http\Controllers\Api\v2\Admin\MediaManagementApiController;
use App\Http\Controllers\Api\v2\AdminVerificationV2Controller;
use App\Http\Controllers\Api\v2\AIV2Controller;
use App\Http\Controllers\Api\v2\ApiV2Controller;
use App\Http\Controllers\Api\v2\Auth\AuthController as AuthV2Controller;
use App\Http\Controllers\Api\v2\CommunityV2ApiController;
use App\Http\Controllers\Api\v2\EducationV2Controller;
use App\Http\Controllers\Api\v2\FederationV2Controller;
use App\Http\Controllers\Api\v2\LiveStreamingV2Controller;
use App\Http\Controllers\Api\v2\MarketplaceV2Controller;
use App\Http\Controllers\Api\v2\MusicApiController;
use App\Http\Controllers\Api\v2\Page\PageAnalyticsV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageAuditV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageContentV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageEventV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageInboxV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageModerationV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageProductV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageRegistrationV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageTeamV2ApiController;
use App\Http\Controllers\Api\v2\Page\PageV2ApiController;
use App\Http\Controllers\Api\v2\ProfileTaxonomyV2Controller;
use App\Http\Controllers\Api\v2\ProfileV2Controller;
use App\Http\Controllers\Api\v2\ProfileVerificationV2Controller;
use App\Http\Controllers\Api\v2\ReelV2ApiController;
use App\Http\Controllers\Api\v2\SocialLinkV2Controller;
use App\Http\Controllers\Api\v2\StoryV2ApiController;
use App\Http\Controllers\Api\v2\UploadApiController;
use App\Http\Controllers\Api\v2\WorkExperienceV2Controller;
use App\Http\Middleware\ApiV2Middleware;
use App\Services\GraphQL\GraphQLGatewayService;
use App\Services\Offline\OfflineSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // System Health, Observability & Monitoring
    Route::get('/health', [SystemController::class, 'health']);
    Route::get('/metrics', [InfrastructureController::class, 'metrics']);
    Route::get('/system/status', [InfrastructureController::class, 'status']);

    // Public Auth endpoints with brute-force rate limiting (প্রতি মিনিটে সর্বোচ্চ ৫ বার চেষ্টা)
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/auth/register', [RegisterController::class, 'register']);
        Route::post('/auth/login', [LoginController::class, 'login']);
        Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
        Route::post('/auth/reset-password', [ForgotPasswordController::class, 'resetPassword']);
        Route::post('/auth/resend-email', [AuthV2Controller::class, 'resendEmail']);
        Route::post('/auth/send-phone-otp', [PhoneOtpController::class, 'sendOtp']);
        Route::post('/auth/verify-phone-otp', [PhoneOtpController::class, 'verifyOtp']);
        Route::post('/auth/2fa/challenge', [TwoFactorController::class, 'verifyChallenge']);
        Route::get('/auth/webauthn/options', [WebAuthnController::class, 'options']);
        Route::post('/auth/webauthn/verify', [WebAuthnController::class, 'verify']);
    });

    // Admin Authentication (Separate Guard with dedicated rate limiting)
    Route::middleware(['throttle:admin-auth'])->group(function () {
        Route::post('/admin/login', [AdminAuthController::class, 'login']);
        Route::post('/admin/verify-2fa', [AdminAuthController::class, 'verify2fa']);
    });

    // Public Feed & Posts View
    Route::get('/feed', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show'])->whereNumber('id');
    Route::get('/posts/{id}/reactions', [ReactionController::class, 'postReactions'])->whereNumber('id');
    Route::get('/posts/{id}/comments', [CommentController::class, 'index'])->whereNumber('id');
    Route::get('/users/{username}/posts', [PostController::class, 'userPosts']);

    // Public Profile route
    Route::get('/users/{username}', [ProfileController::class, 'show']);

    // Public Presence lookup
    Route::get('/presence/{userId}', [PresenceController::class, 'show']);

    // Public Media inspection
    Route::get('/media/{id}', [MediaController::class, 'show']);

    // Application Version & Native Client Compatibility
    Route::get('/app/version', [AppVersionController::class, 'check']);

    // Signed upload handler (for local/testing fallback)
    Route::post('/media/direct-upload', [MediaController::class, 'handleLocalSignedUpload'])
        ->name('api.media.direct-upload');

    // Authenticated voice audio stream (self-authenticates via bearer, query token, cookie, or session)
    Route::get('/messages/{id}/voice', [VoiceMessageController::class, 'stream'])->whereNumber('id');

    // Authenticated API endpoints
    Route::middleware(['auth:sanctum,web'])->group(function () {
        // Auth session & token management
        Route::get('/auth/me', [LoginController::class, 'me']);
        Route::post('/auth/logout', [LoginController::class, 'logout']);
        Route::post('/auth/logout-all', [LoginController::class, 'logoutAll']);
        Route::post('/auth/refresh', [LoginController::class, 'refresh']);
        Route::put('/auth/password', [PasswordController::class, 'update'])->middleware('throttle:6,1');
        Route::put('/auth/profile', [ProfileController::class, 'update']);
        Route::get('/auth/devices', [DeviceManagementController::class, 'index']);
        Route::delete('/auth/device/{id}', [DeviceManagementController::class, 'destroy']);
        Route::get('/auth/login-history', [DeviceManagementController::class, 'history']);

        // Admin Session & Trusted Device Management
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
        Route::get('/admin/sessions', [AdminAuthController::class, 'sessions']);
        Route::delete('/admin/session/{id}', [AdminAuthController::class, 'revokeSession']);
        Route::get('/admin/trusted-devices', [AdminAuthController::class, 'trustedDevices']);
        Route::delete('/admin/trusted-device/{id}', [AdminAuthController::class, 'revokeTrustedDevice']);

        // Profile & settings
        Route::get('/profile', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::get('/profile/about', [ProfileController::class, 'myAbout']);
        Route::put('/profile/about', [ProfileController::class, 'updateAbout']);
        Route::put('/profile/username', [ProfileController::class, 'updateUsername']);
        Route::put('/profile/settings', [ProfileController::class, 'updateSettings']);
        Route::get('/profile/completion', [ProfileController::class, 'completion']);
        Route::get('/profile/analytics', [ProfileController::class, 'analytics']);
        Route::get('/profile/blocked-users', [ProfileV2Controller::class, 'getBlockedUsers']);

        // Profile Enterprise V3 Suite
        Route::prefix('profile')->group(function () {
            // Avatar & Cover History, Frames, Reposition
            Route::get('/avatar/history', [ProfileEnterpriseApiController::class, 'getAvatarHistory']);
            Route::post('/avatar/restore', [ProfileEnterpriseApiController::class, 'restoreAvatar']);
            Route::post('/avatar/frame', [ProfileEnterpriseApiController::class, 'applyAvatarFrame']);
            Route::post('/avatar/guard', [ProfileEnterpriseApiController::class, 'toggleAvatarGuard']);
            Route::get('/cover/history', [ProfileEnterpriseApiController::class, 'getCoverHistory']);
            Route::post('/cover/reposition', [ProfileEnterpriseApiController::class, 'repositionCover']);

            // About Multi-sections
            Route::put('/about/personal', [ProfileEnterpriseApiController::class, 'updatePersonal']);
            Route::put('/about/contact', [ProfileEnterpriseApiController::class, 'updateContact']);
            Route::put('/about/location', [ProfileEnterpriseApiController::class, 'updateLocation']);
            Route::put('/about/interests', [ProfileEnterpriseApiController::class, 'updateInterests']);

            // Friends Advanced Management
            Route::get('/friends/advanced', [ProfileEnterpriseApiController::class, 'getAdvancedFriends']);
            Route::post('/friends/{id}/favorite', [ProfileEnterpriseApiController::class, 'toggleFavoriteFriend']);
            Route::post('/friends/{id}/close', [ProfileEnterpriseApiController::class, 'toggleCloseFriend']);
            Route::post('/friends/{id}/restricted', [ProfileEnterpriseApiController::class, 'toggleRestrictedFriend']);
            Route::post('/friends/{id}/mute', [ProfileEnterpriseApiController::class, 'muteFriend']);
            Route::post('/friends/{id}/snooze', [ProfileEnterpriseApiController::class, 'snoozeFriend']);
            Route::get('/friends', [ProfileEnterpriseApiController::class, 'getFriends']);
            Route::get('/friends/suggestions', [ProfileEnterpriseApiController::class, 'getFriendSuggestions']);

            // Photo Albums & Items
            Route::get('/albums', [ProfileEnterpriseApiController::class, 'getAlbums']);
            Route::post('/albums', [ProfileEnterpriseApiController::class, 'createAlbum']);
            Route::put('/albums/{id}', [ProfileEnterpriseApiController::class, 'updateAlbum'])->whereNumber('id');
            Route::delete('/albums/{id}', [ProfileEnterpriseApiController::class, 'deleteAlbum'])->whereNumber('id');
            Route::post('/albums/{id}/items', [ProfileEnterpriseApiController::class, 'addAlbumItem'])->whereNumber('id');
            Route::delete('/albums/{id}/items/{itemId}', [ProfileEnterpriseApiController::class, 'removeAlbumItem'])->whereNumber('id')->whereNumber('itemId');

            // Saved Items & Collections
            Route::get('/saved-items', [ProfileEnterpriseApiController::class, 'getSavedItems']);
            Route::post('/saved-items/toggle', [ProfileEnterpriseApiController::class, 'toggleSaveItem']);

            // Story Highlights
            Route::get('/highlights', [ProfileEnterpriseApiController::class, 'getHighlights']);
            Route::post('/highlights', [ProfileEnterpriseApiController::class, 'createHighlight']);
            Route::delete('/highlights/{id}', [ProfileEnterpriseApiController::class, 'deleteHighlight']);

            // Activity Log
            Route::get('/activity-log', [ProfileEnterpriseApiController::class, 'getActivityLogs']);

            // Professional Mode & Analytics
            Route::post('/professional-mode/toggle', [ProfileEnterpriseApiController::class, 'toggleProfessionalMode']);
            Route::get('/professional-mode/analytics', [ProfileEnterpriseApiController::class, 'getProfessionalAnalytics']);

            // Profile Search
            Route::get('/search', [ProfileEnterpriseApiController::class, 'searchProfiles']);
        });

        // Two-Factor Authentication Management
        Route::post('/auth/2fa/setup', [TwoFactorController::class, 'setup']);
        Route::post('/auth/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable']);

        // Social Connections: Enterprise Friends, Social Graph & Custom Lists
        Route::get('/friends', [FriendController::class, 'index']);
        Route::get('/followers', [FriendController::class, 'followers']);
        Route::get('/following', [FriendController::class, 'following']);
        Route::get('/friends/requests', [FriendController::class, 'requests']);
        Route::get('/friends/requests/sent', [FriendController::class, 'sentRequests']);
        Route::get('/friends/suggestions', [FriendController::class, 'suggestions']);
        Route::get('/friends/birthdays', [FriendController::class, 'birthdays']);
        Route::get('/friends/counters', [FriendController::class, 'counters']);
        Route::get('/friends/mutual/{userId}', [FriendController::class, 'mutual'])->whereNumber('userId');
        Route::get('/friends/status/{targetId}', [FriendController::class, 'status'])->whereNumber('targetId');
        Route::get('/friends/online', [PresenceController::class, 'onlineFriends']);

        Route::post('/friends/{id}/request', [FriendController::class, 'send'])->whereNumber('id')->middleware('throttle:friend-requests');
        Route::post('/friends/{id}/accept', [FriendController::class, 'accept'])->whereNumber('id');
        Route::post('/friends/{id}/decline', [FriendController::class, 'decline'])->whereNumber('id');
        Route::post('/friends/{id}/reject', [FriendController::class, 'decline'])->whereNumber('id');
        Route::post('/friends/{id}/cancel', [FriendController::class, 'cancel'])->whereNumber('id');
        Route::delete('/friends/{id}', [FriendController::class, 'unfriend'])->whereNumber('id');

        Route::post('/friends/{id}/favorite', [FriendController::class, 'toggleFavorite'])->whereNumber('id');
        Route::post('/friends/{id}/close', [FriendController::class, 'toggleClose'])->whereNumber('id');
        Route::post('/friends/{id}/restricted', [FriendController::class, 'toggleRestricted'])->whereNumber('id');

        // Bulk operations
        Route::post('/friends/requests/bulk-accept', [FriendController::class, 'bulkAccept']);
        Route::post('/friends/requests/bulk-decline', [FriendController::class, 'bulkDecline']);
        Route::post('/friends/requests/bulk-delete', [FriendController::class, 'bulkDelete']);
        Route::post('/friends/requests/bulk-block', [FriendController::class, 'bulkBlock']);

        // Custom Friend Lists
        Route::get('/friends/lists', [FriendController::class, 'lists']);
        Route::post('/friends/lists', [FriendController::class, 'createList']);
        Route::put('/friends/lists/{id}', [FriendController::class, 'updateList'])->whereNumber('id');
        Route::delete('/friends/lists/{id}', [FriendController::class, 'deleteList'])->whereNumber('id');
        Route::get('/friends/lists/{id}/members', [FriendController::class, 'listMembers'])->whereNumber('id');
        Route::post('/friends/lists/{id}/members/{friendId}', [FriendController::class, 'addListMember'])->whereNumber('id')->whereNumber('friendId');
        Route::delete('/friends/lists/{id}/members/{friendId}', [FriendController::class, 'removeListMember'])->whereNumber('id')->whereNumber('friendId');

        // Follow & Block
        Route::post('/users/{id}/follow', [FriendController::class, 'follow'])->whereNumber('id');
        Route::delete('/users/{id}/follow', [FriendController::class, 'unfollow'])->whereNumber('id');
        Route::post('/friends/{id}/follow', [FriendController::class, 'follow'])->whereNumber('id');
        Route::delete('/friends/{id}/follow', [FriendController::class, 'unfollow'])->whereNumber('id');
        Route::post('/users/{id}/block', [FriendController::class, 'block'])->whereNumber('id');
        Route::delete('/users/{id}/block', [FriendController::class, 'unblock'])->whereNumber('id');
        Route::post('/friends/{id}/block', [FriendController::class, 'block'])->whereNumber('id');
        Route::delete('/friends/{id}/block', [FriendController::class, 'unblock'])->whereNumber('id');

        // Content Reporting
        Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports');

        // Real-time Presence & Typing
        Route::post('/presence/heartbeat', [PresenceController::class, 'heartbeat']);
        Route::post('/presence/offline', [PresenceController::class, 'offline']);
        Route::get('/presence/friends/active', [PresenceController::class, 'activeFriends']);
        Route::post('/presence/visibility', [PresenceController::class, 'toggleVisibility']);
        Route::post('/presence/typing', [PresenceController::class, 'typing']);
        Route::get('/presence/typing/{conversationId}', [PresenceController::class, 'getTyping'])->whereNumber('conversationId');

        // Notifications (Enterprise API-First)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
        Route::match(['put', 'patch'], '/notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::match(['put', 'post'], '/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::match(['put', 'patch'], '/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

        // Messenger & Real-Time Chat (Enterprise)
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store'])->middleware('throttle:conversations');
        Route::get('/conversations/{id}', [ConversationController::class, 'show']);
        Route::post('/conversations/{id}/pin', [ConversationController::class, 'togglePin']);
        Route::post('/conversations/{id}/archive', [ConversationController::class, 'toggleArchive']);
        Route::post('/conversations/{id}/mute', [ConversationController::class, 'mute']);
        Route::post('/conversations/{id}/unread', [ConversationController::class, 'markAsUnread']);
        Route::match(['delete', 'post'], '/conversations/{id}/clear', [ConversationController::class, 'clear']);
        Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
        Route::post('/conversations/{id}/draft', [ConversationController::class, 'saveDraft']);
        Route::post('/conversations/{id}/leave', [ConversationController::class, 'leave']);
        Route::put('/conversations/{id}/group-info', [ConversationController::class, 'updateGroup']);
        Route::post('/conversations/{id}/members', [ConversationController::class, 'addMembers']);
        Route::delete('/conversations/{id}/members/{userId}', [ConversationController::class, 'removeMember']);
        Route::put('/conversations/{id}/members/{userId}/role', [ConversationController::class, 'updateMemberRole']);
        Route::put('/conversations/{id}/members/{userId}', [ConversationController::class, 'updateMemberRole']);
        Route::post('/conversations/{id}/request', [ConversationController::class, 'handleRequest']);
        Route::get('/conversations/{id}/media', [ConversationController::class, 'media']);
        Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
        Route::post('/conversations/{id}/messages', [MessageController::class, 'store'])->middleware('throttle:messages');
        Route::post('/conversations/{id}/read', [MessageController::class, 'read']);
        Route::match(['put', 'patch'], '/messages/{id}', [MessageController::class, 'edit']);
        Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
        Route::post('/messages/{id}/delete-for-me', [MessageController::class, 'deleteForMe']);
        Route::post('/messages/{id}/delete-for-everyone', [MessageController::class, 'deleteForEveryone']);
        Route::get('/messages/{id}/reactions', [MessageController::class, 'getReactions']);
        Route::post('/messages/{id}/reactions', [MessageController::class, 'react']);
        Route::post('/messages/{id}/forward', [MessageController::class, 'forward']);
        Route::post('/messages/{id}/report', [MessageController::class, 'report']);
        Route::post('/messages/{id}/delivered', [MessageController::class, 'delivered']);
        Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/messages/attachments', [MessageController::class, 'uploadAttachment']);
        Route::get('/messages/attachments/{id}/download', [MessageController::class, 'downloadAttachment']);
        Route::get('/messages/attachments/{id}/view', [MessageController::class, 'viewAttachment']);
        Route::get('/attachments/{id}/download', [MessageController::class, 'downloadAttachment']);
        Route::get('/attachments/{id}/view', [MessageController::class, 'viewAttachment']);
        Route::get('/media/{id}/view', [MessageController::class, 'viewAttachment']);
        Route::get('/media/{id}/stream', [MessageController::class, 'viewAttachment']);
        Route::post('/conversations/{id}/call/signal', [CallSignalController::class, 'signal']);

        // Voice Messages & Audio Notes
        Route::post('/conversations/{id}/voice', [VoiceMessageController::class, 'store']);

        // Pinned Messages
        Route::get('/conversations/{id}/pins', [PinnedMessageController::class, 'index']);
        Route::post('/conversations/{id}/messages/{messageId}/pin', [PinnedMessageController::class, 'store']);
        Route::delete('/conversations/{id}/messages/{messageId}/pin', [PinnedMessageController::class, 'destroy']);

        // Saved Messages
        Route::get('/saved-messages', [SavedMessageController::class, 'index']);
        Route::post('/messages/{messageId}/save', [SavedMessageController::class, 'store']);
        Route::delete('/messages/{messageId}/save', [SavedMessageController::class, 'destroy']);

        // Enterprise Audio/Video/Group Calling & WebRTC Signaling
        Route::post('/calls', [CallController::class, 'initiate']);
        Route::get('/calls/history', [CallController::class, 'history']);
        Route::get('/calls/ice-servers', [CallController::class, 'iceServers']);
        Route::get('/calls/{id}', [CallController::class, 'show']);
        Route::get('/calls/{id}/signals', [CallController::class, 'signals']);
        Route::post('/calls/{id}/respond', [CallController::class, 'respond']);
        Route::post('/calls/{id}/leave', [CallController::class, 'leave']);
        Route::post('/calls/{id}/signal', [CallController::class, 'signal']);
        Route::post('/calls/{id}/state', [CallController::class, 'updateState']);
        Route::post('/calls/{id}/invite', [CallController::class, 'invite']);

        // Offline Synchronization & Event Replay (Mobile Apps / Multi-device)
        Route::get('/messenger/sync', [MessengerSyncController::class, 'sync']);

        // Multi-Device Management
        Route::get('/devices', [DeviceSessionController::class, 'index']);
        Route::post('/devices/register', [DeviceSessionController::class, 'register']);
        Route::delete('/devices/{id}', [DeviceSessionController::class, 'destroy']);

        // Global and In-Chat Messenger Search
        Route::get('/messenger/search', [MessengerSearchController::class, 'search']);

        // Messenger Sound Preferences & Sound Library
        Route::get('/settings/messenger-sounds', [MessengerSoundController::class, 'index']);
        Route::put('/settings/messenger-sounds', [MessengerSoundController::class, 'update']);
        Route::post('/settings/messenger-sounds/reset', [MessengerSoundController::class, 'reset']);
        Route::post('/settings/messenger-sounds/upload', [MessengerSoundController::class, 'upload']);

        // OpenAPI Documentation Endpoint
        Route::get('/docs/messenger', fn () => response()->file(public_path('docs/messenger-openapi.json'), ['Content-Type' => 'application/json']));

        // Stories (24-hour ephemeral content)
        Route::get('/stories', [StoryController::class, 'index']);
        Route::post('/stories', [StoryController::class, 'store']);
        Route::post('/stories/{id}/view', [StoryController::class, 'view']);
        Route::get('/stories/{id}/views', [StoryController::class, 'views']);
        Route::delete('/stories/{id}', [StoryController::class, 'destroy']);

        // Enterprise Groups & Community Platform
        Route::get('/groups', [GroupController::class, 'index']);
        Route::post('/groups', [GroupController::class, 'store']);
        Route::get('/groups/my', [GroupController::class, 'myGroups']);
        Route::get('/groups/{slug}', [GroupController::class, 'show']);
        Route::put('/groups/{id}', [GroupController::class, 'update']);
        Route::delete('/groups/{id}', [GroupController::class, 'destroy']);
        Route::post('/groups/{id}/join', [GroupController::class, 'join']);
        Route::post('/groups/{id}/leave', [GroupController::class, 'leave']);
        Route::get('/groups/{id}/members', [GroupController::class, 'members']);
        Route::post('/groups/{id}/approve/{userId}', [GroupController::class, 'approve']);
        Route::post('/groups/{id}/reject/{userId}', [GroupController::class, 'reject']);
        Route::delete('/groups/{id}/members/{userId}', [GroupController::class, 'removeMember']);
        Route::put('/groups/{id}/members/{userId}/role', [GroupController::class, 'updateMemberRole']);
        Route::post('/groups/{id}/members/{userId}/warn', [GroupController::class, 'warnMember']);
        Route::post('/groups/{id}/members/{userId}/mute', [GroupController::class, 'muteMember']);
        Route::post('/groups/{id}/members/{userId}/ban', [GroupController::class, 'banMember']);
        Route::post('/groups/{id}/members/{userId}/unban', [GroupController::class, 'unbanMember']);
        Route::get('/groups/{id}/posts', [GroupController::class, 'feed']);
        Route::post('/groups/{id}/posts', [GroupController::class, 'storePost']);
        Route::delete('/groups/{id}/posts/{postId}', [GroupController::class, 'destroyPost']);
        Route::get('/groups/{id}/moderation/posts', [GroupController::class, 'moderationPosts']);
        Route::post('/groups/{id}/moderation/posts/{postId}/approve', [GroupController::class, 'approvePost']);
        Route::post('/groups/{id}/moderation/posts/{postId}/reject', [GroupController::class, 'rejectPost']);
        Route::get('/groups/{id}/polls', [GroupController::class, 'polls']);
        Route::post('/groups/{id}/polls', [GroupController::class, 'storePoll']);
        Route::post('/groups/{id}/polls/{pollId}/vote', [GroupController::class, 'votePoll']);
        Route::get('/groups/{id}/events', [GroupController::class, 'events']);
        Route::post('/groups/{id}/events', [GroupController::class, 'storeEvent']);
        Route::post('/groups/{id}/events/{eventId}/rsvp', [GroupController::class, 'rsvpEvent']);
        Route::post('/groups/{id}/announcements', [GroupController::class, 'storeAnnouncement']);
        Route::get('/groups/{id}/rules', [GroupController::class, 'rules']);
        Route::post('/groups/{id}/rules', [GroupController::class, 'storeRule']);
        Route::get('/groups/{id}/questions', [GroupController::class, 'questions']);
        Route::post('/groups/{id}/questions', [GroupController::class, 'storeQuestion']);
        Route::get('/groups/{id}/files', [GroupController::class, 'files']);
        Route::post('/groups/{id}/files', [GroupController::class, 'storeFile']);
        Route::post('/groups/{id}/reports', [GroupController::class, 'storeReport']);
        Route::get('/groups/{id}/moderation/reports', [GroupController::class, 'reports']);
        Route::post('/groups/{id}/moderation/reports/{reportId}/review', [GroupController::class, 'reviewReport']);
        Route::get('/groups/{id}/analytics', [GroupController::class, 'analytics']);
        Route::get('/groups/{id}/moderation/history', [GroupController::class, 'moderationHistory']);

        // Pages & Public Entities
        Route::get('/pages', [PageController::class, 'index']);
        Route::post('/pages', [PageController::class, 'store']);
        Route::get('/pages/{slug}', [PageController::class, 'show']);
        Route::post('/pages/{id}/follow', [PageController::class, 'follow']);
        Route::get('/pages/{id}/posts', [PageController::class, 'feed']);
        Route::post('/pages/{id}/posts', [PageController::class, 'storePost']);

        // Universal Search
        Route::get('/search', [SearchController::class, 'search']);

        // Enterprise Post Composer & Operations
        Route::post('/posts/link-preview', [PostController::class, 'linkPreview']);
        Route::get('/posts/drafts', [PostController::class, 'getDrafts']);
        Route::post('/posts/drafts', [PostController::class, 'saveDraft']);
        Route::delete('/posts/drafts/{id}', [PostController::class, 'deleteDraft'])->whereNumber('id');
        Route::delete('/posts/drafts', [PostController::class, 'clearDrafts']);
        Route::get('/posts/taggable-users', [PostController::class, 'taggableUsers']);
        Route::get('/posts/user-groups', [PostController::class, 'userGroups']);
        Route::get('/posts/gifs', [PostController::class, 'getGifs']);

        Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:posts');
        Route::put('/posts/{id}', [PostController::class, 'update'])->whereNumber('id');
        Route::delete('/posts/{id}', [PostController::class, 'destroy'])->whereNumber('id');
        Route::post('/posts/{id}/pin', [PostController::class, 'togglePin'])->whereNumber('id');
        Route::post('/posts/{id}/toggle-comments', [PostController::class, 'toggleComments'])->whereNumber('id');
        Route::post('/posts/{id}/comments/toggle', [PostController::class, 'toggleComments'])->whereNumber('id');
        Route::post('/posts/{id}/share', [PostController::class, 'share'])->whereNumber('id')->middleware('throttle:shares');
        Route::post('/posts/{id}/poll/vote', [PostController::class, 'votePoll'])->whereNumber('id');
        Route::post('/posts/{id}/collaborator', [PostController::class, 'respondCollaborator'])->whereNumber('id');
        Route::post('/posts/{id}/impression', [AnalyticsController::class, 'impression'])->whereNumber('id');
        Route::get('/posts/{id}/analytics', [AnalyticsController::class, 'show'])->whereNumber('id');

        // Reactions
        Route::post('/posts/{id}/react', [ReactionController::class, 'reactPost'])->middleware('throttle:reactions');
        Route::post('/comments/{id}/react', [ReactionController::class, 'reactComment'])->middleware('throttle:reactions');

        // Comments & Replies
        Route::post('/posts/{id}/comments', [CommentController::class, 'store'])->middleware('throttle:comments');
        Route::put('/comments/{id}', [CommentController::class, 'update']);
        Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

        // Media & Object Storage Operations
        Route::prefix('media')->group(function () {
            Route::post('/signed-upload-url', [MediaController::class, 'signedUploadUrl']);
            Route::post('/confirm-upload', [MediaController::class, 'confirmUpload']);
            Route::post('/upload', [MediaController::class, 'directUpload']);
        });

        // Live Streaming API v1 Compatibility
        Route::prefix('live')->group(function () {
            Route::get('/me', [LiveStreamingV2Controller::class, 'me']);
            Route::get('/streams', [LiveStreamingV2Controller::class, 'index']);
            Route::get('/streams/{id}', [LiveStreamingV2Controller::class, 'show'])->whereNumber('id');
            Route::match(['GET', 'POST'], '/streams/{id}/viewer-token', [LiveStreamingV2Controller::class, 'viewerToken'])->whereNumber('id');
            Route::post('/streams/{id}/join', [LiveStreamingV2Controller::class, 'join'])->whereNumber('id');
            Route::post('/streams/{id}/heartbeat', [LiveStreamingV2Controller::class, 'heartbeat'])->whereNumber('id');
            Route::post('/streams/{id}/leave', [LiveStreamingV2Controller::class, 'leave'])->whereNumber('id');
            Route::get('/ice-servers', [LiveStreamingV2Controller::class, 'iceServers']);
            Route::post('/channels', [LiveStreamingV2Controller::class, 'store']);
            Route::post('/streams', [LiveStreamingV2Controller::class, 'store']);
            Route::post('/sessions', [LiveStreamingV2Controller::class, 'store']);
            Route::post('/sessions/{id}/end', [LiveStreamingV2Controller::class, 'end'])->whereNumber('id');
            Route::post('/streams/{id}/start', [LiveStreamingV2Controller::class, 'start'])->whereNumber('id');
            Route::post('/streams/{id}/end', [LiveStreamingV2Controller::class, 'end'])->whereNumber('id');
            Route::post('/streams/{id}/sfu/publish', [LiveStreamingV2Controller::class, 'confirmPublish'])->whereNumber('id');
            Route::post('/streams/{id}/signal', [LiveStreamingV2Controller::class, 'signal'])->whereNumber('id');
            Route::post('/streams/{id}/comments', [LiveStreamingV2Controller::class, 'comment'])->whereNumber('id');
            Route::delete('/streams/{id}/comments/{commentId}', [LiveStreamingV2Controller::class, 'deleteComment'])->whereNumber('id');
            Route::post('/streams/{id}/reactions', [LiveStreamingV2Controller::class, 'reaction'])->whereNumber('id');
            Route::post('/streams/{id}/gifts', [LiveStreamingV2Controller::class, 'gift'])->whereNumber('id');
            Route::post('/streams/{id}/share', [LiveStreamingV2Controller::class, 'share'])->whereNumber('id');
            Route::post('/streams/{id}/moderators', [LiveStreamingV2Controller::class, 'addModerator'])->whereNumber('id');
            Route::delete('/streams/{id}/moderators/{userId}', [LiveStreamingV2Controller::class, 'removeModerator'])->whereNumber('id');
            Route::post('/streams/{id}/report', [LiveStreamingV2Controller::class, 'report'])->whereNumber('id');
            Route::post('/streams/{id}/replay-upload', [LiveStreamingV2Controller::class, 'uploadReplay'])->whereNumber('id');
        });

        // Admin, System Metrics, Infrastructure & SRE Operations
        Route::prefix('admin')->group(function () {
            Route::get('/metrics', [SystemController::class, 'metrics']);
            Route::get('/infrastructure', [InfrastructureController::class, 'dashboard']);
            Route::post('/cache/clear', [InfrastructureController::class, 'clearCache']);
            Route::post('/queue/restart', [InfrastructureController::class, 'restartQueue']);
            Route::post('/deployment/verify', [InfrastructureController::class, 'verifyDeployment']);
            Route::get('/backups', [InfrastructureController::class, 'backups']);
            Route::post('/backups/run', [InfrastructureController::class, 'runBackup']);
            Route::get('/security/events', [InfrastructureController::class, 'securityEvents']);
            Route::get('/search/reindex', [InfrastructureController::class, 'reindexSearch']);

            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users/{id}/roles', [UserController::class, 'assignRole']);
            Route::delete('/users/{id}/roles', [UserController::class, 'removeRole']);

            Route::get('/reports', [ReportController::class, 'index']);
            Route::put('/reports/{id}', [ReportController::class, 'update']);
        });
    });
});

// =========================================================================
// JUGAJUG PHASE 11: ENTERPRISE API V2 (BANGLADESH GOV GRADE + FACEBOOK SCALE)
// =========================================================================
Route::prefix('v2')->middleware([ApiV2Middleware::class, 'throttle:api'])->group(function () {
    // Gateway Discovery
    Route::get('/', [ApiV2Controller::class, 'index']);

    // Phase 12: Production-Ready Auth API v2 Endpoints
    Route::prefix('auth')->group(function () {
        // Public Auth actions
        Route::get('/captcha', [AuthV2Controller::class, 'captcha']);
        Route::post('/register', [AuthV2Controller::class, 'register']);
        Route::post('/login', [AuthV2Controller::class, 'login']);
        Route::post('/2fa/challenge', [AuthV2Controller::class, 'verifyChallenge']);
        Route::post('/verify-email', [AuthV2Controller::class, 'verifyEmail']);
        Route::post('/verify-mobile', [AuthV2Controller::class, 'verifyMobile']);
        Route::post('/resend-email', [AuthV2Controller::class, 'resendEmail']);
        Route::get('/verification-status', [AuthV2Controller::class, 'verificationStatus']);
        Route::post('/resend-otp', [AuthV2Controller::class, 'resendOtp']);
        Route::post('/forgot-password', [AuthV2Controller::class, 'forgotPassword']);
        Route::post('/password/email', [AuthV2Controller::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthV2Controller::class, 'resetPassword']);
        Route::post('/password/reset', [AuthV2Controller::class, 'resetPassword']);

        // Authenticated Auth actions
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/me', [AuthV2Controller::class, 'me']);
            Route::post('/logout', [AuthV2Controller::class, 'logout']);
            Route::post('/logout-all', [AuthV2Controller::class, 'logoutAll']);
            Route::get('/sessions', [AuthV2Controller::class, 'sessions']);
            Route::delete('/sessions/{id}', [AuthV2Controller::class, 'revokeSession']);
            Route::post('/2fa/setup', [AuthV2Controller::class, 'setup2fa']);
            Route::post('/enable-2fa', [AuthV2Controller::class, 'enable2fa']);
            Route::post('/disable-2fa', [AuthV2Controller::class, 'disable2fa']);
        });
    });

    // Phase 7 & Step 14: Admin Authentication Management Endpoints
    Route::prefix('admin/auth')->middleware(['auth:sanctum,admin,web', 'permission:manage.users,auth.admin'])->group(function () {
        Route::get('/stats', [AuthManagementController::class, 'dashboardStats']);
        Route::get('/users', [AuthManagementController::class, 'users']);
        Route::get('/sessions', [AuthManagementController::class, 'sessions']);
        Route::delete('/sessions/{id}', [AuthManagementController::class, 'revokeSession']);
        Route::get('/email-verifications', [AuthManagementController::class, 'emailVerificationLogs']);
        Route::get('/password-resets', [AuthManagementController::class, 'passwordResetLogs']);
        Route::get('/otp-logs', [AuthManagementController::class, 'otpLogs']);
        Route::post('/users/{id}/verify-email', [AuthManagementController::class, 'verifyEmail']);
        Route::post('/users/{id}/verify-mobile', [AuthManagementController::class, 'verifyMobile']);
        Route::post('/users/{id}/activate', [AuthManagementController::class, 'activateUser']);
        Route::post('/users/{id}/suspend', [AuthManagementController::class, 'suspendUser']);
        Route::post('/users/{id}/ban', [AuthManagementController::class, 'banUser']);
        Route::delete('/users/{id}', [AuthManagementController::class, 'softDeleteUser']);
        Route::post('/users/{id}/restore', [AuthManagementController::class, 'restoreUser']);
        Route::post('/users/{id}/force-logout', [AuthManagementController::class, 'forceLogout']);
        Route::post('/users/{id}/reset-password', [AuthManagementController::class, 'resetPassword']);
        Route::post('/users/{id}/reset-2fa', [AuthManagementController::class, 'reset2fa']);
        Route::post('/users/{id}/unlock', [AuthManagementController::class, 'unlockAccount']);
        Route::get('/users/{id}/login-history', [AuthManagementController::class, 'loginHistory']);
        Route::get('/otp-history', [AuthManagementController::class, 'otpHistory']);
        Route::get('/audit-logs', [AuthManagementController::class, 'auditLogs']);
        Route::get('/failed-logins', [AuthManagementController::class, 'failedLogins']);
        Route::get('/export', [AuthManagementController::class, 'exportUsers']);
    });

    // Enterprise SMTP Email Management (RBAC protected)
    Route::prefix('admin/smtp')->middleware(['auth:sanctum,admin,web', 'role:ADMIN,SUPER_ADMIN'])->group(function () {
        Route::get('/settings', [EmailManagementController::class, 'getSettings']);
        Route::post('/settings', [EmailManagementController::class, 'updateSettings']);
        Route::post('/test', [EmailManagementController::class, 'testConnection'])->middleware('throttle:smtp-test');
        Route::post('/verify-connection', [EmailManagementController::class, 'verifyConnection'])->middleware('throttle:smtp-test');
        Route::get('/dns-check', [EmailManagementController::class, 'checkDns']);
        Route::get('/logs', [EmailManagementController::class, 'getLogs']);
        Route::post('/logs/{id}/retry', [EmailManagementController::class, 'retryLog'])->whereNumber('id');
        Route::get('/stats', [EmailManagementController::class, 'getStats']);
        Route::get('/templates', [EmailManagementController::class, 'getTemplates']);
        Route::get('/templates/{key}/preview', [EmailManagementController::class, 'previewTemplate']);
    });

    // Step 15: Admin Profile Verification Management (RBAC protected)
    Route::prefix('admin/verifications')->middleware(['auth:sanctum,admin,web', 'role:ADMIN,SUPER_ADMIN'])->group(function () {
        Route::get('/', [AdminVerificationV2Controller::class, 'index']);
        Route::get('/{id}', [AdminVerificationV2Controller::class, 'show'])->whereNumber('id');
        Route::post('/{id}/approve', [AdminVerificationV2Controller::class, 'approve'])->whereNumber('id');
        Route::post('/{id}/reject', [AdminVerificationV2Controller::class, 'reject'])->whereNumber('id');
        Route::post('/{id}/request-info', [AdminVerificationV2Controller::class, 'requestInfo'])->whereNumber('id');
        Route::post('/{id}/revoke', [AdminVerificationV2Controller::class, 'revoke'])->whereNumber('id');
    });

    // User Profile & Personal Timeline (Facebook-style UX & Privacy)
    Route::prefix('profile')->group(function () {
        Route::get('/username/check', [ProfileV2Controller::class, 'checkUsername'])->middleware('throttle:username-check');

        Route::middleware(['auth:sanctum,web'])->group(function () {
            Route::get('/', [ProfileV2Controller::class, 'currentProfile']);
            Route::get('/about', [ProfileV2Controller::class, 'currentAbout']);
            Route::put('/about', [ProfileV2Controller::class, 'updateAbout']);
            Route::put('/about/{user}', [ProfileV2Controller::class, 'updateAbout'])->whereNumber('user');
            Route::put('/username', [ProfileV2Controller::class, 'updateUsername']);
            Route::put('/username/{user}', [ProfileV2Controller::class, 'updateUsername'])->whereNumber('user');
            Route::put('/', [ProfileV2Controller::class, 'update']);
            Route::put('/{user}', [ProfileV2Controller::class, 'update'])->whereNumber('user');
            Route::put('/personal', [ProfileV2Controller::class, 'updatePersonalInfo']);
            Route::put('/personal/{user}', [ProfileV2Controller::class, 'updatePersonalInfo'])->whereNumber('user');
            Route::get('/privacy/settings', [ProfileV2Controller::class, 'getPrivacySettings']);
            Route::put('/privacy/settings', [ProfileV2Controller::class, 'updatePrivacySettings']);
            Route::post('/avatar', [ProfileV2Controller::class, 'updateAvatar']);
            Route::delete('/avatar', [ProfileV2Controller::class, 'deleteAvatar']);
            Route::post('/avatar/default', [ProfileV2Controller::class, 'restoreDefaultAvatar']);
            Route::post('/avatar/crop', [ProfileV2Controller::class, 'cropAvatar']);
            Route::post('/cover', [ProfileV2Controller::class, 'updateCover']);
            Route::delete('/cover', [ProfileV2Controller::class, 'deleteCover']);
            Route::post('/cover/default', [ProfileV2Controller::class, 'restoreDefaultCover']);
            Route::post('/cover/crop', [ProfileV2Controller::class, 'cropCover']);
            Route::post('/cover/position', [ProfileV2Controller::class, 'repositionCover']);
            Route::post('/lock', [ProfileV2Controller::class, 'toggleLock']);

            // Enterprise Profile Backup, Deactivation & Settings
            Route::get('/export', [ProfileV2Controller::class, 'exportData']);
            Route::post('/deactivate', [ProfileV2Controller::class, 'deactivateAccount']);
            Route::post('/delete', [ProfileV2Controller::class, 'deleteAccount']);
            Route::get('/notifications/settings', [ProfileV2Controller::class, 'getNotificationSettings']);
            Route::put('/notifications/settings', [ProfileV2Controller::class, 'updateNotificationSettings']);

            // Social & Safety Interactions
            Route::get('/blocked-users', [ProfileV2Controller::class, 'getBlockedUsers']);
            Route::post('/{username}/block', [ProfileV2Controller::class, 'blockUser']);
            Route::post('/{username}/unblock', [ProfileV2Controller::class, 'unblockUser']);
            Route::post('/{username}/restrict', [ProfileV2Controller::class, 'restrictUser']);
            Route::post('/{username}/report', [ProfileV2Controller::class, 'reportUser']);

            // Step 15: Profile Identity Verification
            Route::get('/verification/status', [ProfileVerificationV2Controller::class, 'status']);
            Route::post('/verification/submit', [ProfileVerificationV2Controller::class, 'submit'])->middleware('throttle:10,1');

            // Step 16: Profile Dynamic Completion
            Route::get('/completion', [ProfileV2Controller::class, 'completion']);

            // Step 17: Profile Views & Analytics
            Route::get('/analytics', [ProfileV2Controller::class, 'analytics']);

            // Profile Education Management
            Route::get('/education', [EducationV2Controller::class, 'index']);
            Route::post('/education', [EducationV2Controller::class, 'store']);
            Route::post('/education/reorder', [EducationV2Controller::class, 'reorder']);
            Route::get('/education/{id}', [EducationV2Controller::class, 'show'])->whereNumber('id');
            Route::put('/education/{id}', [EducationV2Controller::class, 'update'])->whereNumber('id');
            Route::delete('/education/{id}', [EducationV2Controller::class, 'destroy'])->whereNumber('id');

            // Profile Work Experience Management
            Route::get('/work', [WorkExperienceV2Controller::class, 'index']);
            Route::post('/work', [WorkExperienceV2Controller::class, 'store']);
            Route::post('/work/reorder', [WorkExperienceV2Controller::class, 'reorder']);
            Route::get('/work/{id}', [WorkExperienceV2Controller::class, 'show'])->whereNumber('id');
            Route::put('/work/{id}', [WorkExperienceV2Controller::class, 'update'])->whereNumber('id');
            Route::delete('/work/{id}', [WorkExperienceV2Controller::class, 'destroy'])->whereNumber('id');

            Route::get('/work-experiences', [WorkExperienceV2Controller::class, 'index']);
            Route::post('/work-experiences', [WorkExperienceV2Controller::class, 'store']);
            Route::post('/work-experiences/reorder', [WorkExperienceV2Controller::class, 'reorder']);
            Route::get('/work-experiences/{id}', [WorkExperienceV2Controller::class, 'show'])->whereNumber('id');
            Route::put('/work-experiences/{id}', [WorkExperienceV2Controller::class, 'update'])->whereNumber('id');
            Route::delete('/work-experiences/{id}', [WorkExperienceV2Controller::class, 'destroy'])->whereNumber('id');

            // Profile Skills
            Route::get('/skills', [ProfileTaxonomyV2Controller::class, 'indexSkills']);
            Route::post('/skills', [ProfileTaxonomyV2Controller::class, 'storeSkill']);
            Route::post('/skills/reorder', [ProfileTaxonomyV2Controller::class, 'reorderSkills']);
            Route::delete('/skills/{id}', [ProfileTaxonomyV2Controller::class, 'destroySkill'])->whereNumber('id');

            // Profile Interests
            Route::get('/interests', [ProfileTaxonomyV2Controller::class, 'indexInterests']);
            Route::post('/interests', [ProfileTaxonomyV2Controller::class, 'storeInterest']);
            Route::delete('/interests/{id}', [ProfileTaxonomyV2Controller::class, 'destroyInterest'])->whereNumber('id');

            // Profile Languages
            Route::get('/languages', [ProfileTaxonomyV2Controller::class, 'indexLanguages']);
            Route::post('/languages', [ProfileTaxonomyV2Controller::class, 'storeLanguage']);
            Route::put('/languages/{id}', [ProfileTaxonomyV2Controller::class, 'updateLanguage'])->whereNumber('id');
            Route::delete('/languages/{id}', [ProfileTaxonomyV2Controller::class, 'destroyLanguage'])->whereNumber('id');

            // Profile Social Links
            Route::get('/social-links', [SocialLinkV2Controller::class, 'index']);
            Route::post('/social-links', [SocialLinkV2Controller::class, 'store']);
            Route::post('/social-links/reorder', [SocialLinkV2Controller::class, 'reorder']);
            Route::get('/social-links/platforms', [SocialLinkV2Controller::class, 'platforms']);
            Route::put('/social-links/{id}', [SocialLinkV2Controller::class, 'update'])->whereNumber('id');
            Route::delete('/social-links/{id}', [SocialLinkV2Controller::class, 'destroy'])->whereNumber('id');
        });

        // Taxonomy Search
        Route::get('/skills/search', [ProfileTaxonomyV2Controller::class, 'searchSkills']);
        Route::get('/interests/search', [ProfileTaxonomyV2Controller::class, 'searchInterests']);
        Route::get('/languages/search', [ProfileTaxonomyV2Controller::class, 'searchLanguages']);
        Route::get('/social-links/platforms', [SocialLinkV2Controller::class, 'platforms']);

        Route::get('/{username}/education', [EducationV2Controller::class, 'userEducations']);
        Route::get('/{username}/work', [WorkExperienceV2Controller::class, 'userWork']);
        Route::get('/{username}/work-experiences', [WorkExperienceV2Controller::class, 'userWork']);
        Route::get('/{username}/skills', [ProfileTaxonomyV2Controller::class, 'userSkills']);
        Route::get('/{username}/interests', [ProfileTaxonomyV2Controller::class, 'userInterests']);
        Route::get('/{username}/languages', [ProfileTaxonomyV2Controller::class, 'userLanguages']);
        Route::get('/{username}/social-links', [SocialLinkV2Controller::class, 'userLinks']);
        Route::get('/{username}/qrcode', [ProfileV2Controller::class, 'getQrCode']);
        Route::get('/{username}', [ProfileV2Controller::class, 'show']);
        Route::get('/{username}/about', [ProfileV2Controller::class, 'about']);
        Route::get('/{username}/personal', [ProfileV2Controller::class, 'getPersonalInfo']);
        Route::get('/{username}/timeline', [ProfileV2Controller::class, 'timeline']);
        Route::get('/{username}/friends', [ProfileV2Controller::class, 'friends']);
        Route::get('/{username}/photos', [ProfileV2Controller::class, 'photos']);
        Route::get('/{username}/videos', [ProfileV2Controller::class, 'videos']);
        Route::post('/{username}/view', [ProfileV2Controller::class, 'recordView'])->middleware('throttle:60,1');
    });

    // Public AI Endpoints & Citizen Services
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [AIV2Controller::class, 'chat']);
        Route::post('/suggest-comment', [AIV2Controller::class, 'suggestComment']);
        Route::post('/caption', [AIV2Controller::class, 'writeCaption']);
        Route::post('/translate', [AIV2Controller::class, 'translate']);
        Route::post('/moderate', [AIV2Controller::class, 'moderate']);
        Route::post('/citizen-services', [AIV2Controller::class, 'citizenServices']);
        Route::get('/search/semantic', [AIV2Controller::class, 'semanticSearch']);
        Route::get('/usage', [AIV2Controller::class, 'usageSummary']);
    });

    // Feed & Search
    Route::get('/feed/ranked', [ApiV2Controller::class, 'rankedFeed']);
    Route::get('/search/hybrid', [ApiV2Controller::class, 'hybridSearch']);

    // Live Streaming
    Route::get('/live/me', [LiveStreamingV2Controller::class, 'me']);
    Route::get('/live/streams', [LiveStreamingV2Controller::class, 'index']);
    Route::get('/live/streams/{id}', [LiveStreamingV2Controller::class, 'show'])->whereNumber('id');
    Route::match(['GET', 'POST'], '/live/streams/{id}/viewer-token', [LiveStreamingV2Controller::class, 'viewerToken'])->whereNumber('id');
    Route::post('/live/streams/{id}/join', [LiveStreamingV2Controller::class, 'join'])->whereNumber('id');
    Route::post('/live/streams/{id}/heartbeat', [LiveStreamingV2Controller::class, 'heartbeat'])->whereNumber('id');
    Route::post('/live/streams/{id}/leave', [LiveStreamingV2Controller::class, 'leave'])->whereNumber('id');
    Route::get('/live/ice-servers', [LiveStreamingV2Controller::class, 'iceServers']);

    // Marketplace
    Route::get('/marketplace/categories', [MarketplaceV2Controller::class, 'categories']);
    Route::get('/marketplace/products', [MarketplaceV2Controller::class, 'index']);

    // ActivityPub Federation
    Route::get('/federation/actors/{username}', [FederationV2Controller::class, 'actor']);
    Route::post('/federation/inbox', [FederationV2Controller::class, 'inbox']);
    Route::get('/federation/users/{username}/outbox', [FederationV2Controller::class, 'outbox']);

    // Taxonomy Search Endpoints
    Route::get('/skills/search', [ProfileTaxonomyV2Controller::class, 'searchSkills']);
    Route::get('/interests/search', [ProfileTaxonomyV2Controller::class, 'searchInterests']);
    Route::get('/languages/search', [ProfileTaxonomyV2Controller::class, 'searchLanguages']);

    // Education & Work top-level aliases
    Route::middleware(['auth:sanctum,web'])->group(function () {
        Route::get('/education', [EducationV2Controller::class, 'index']);
        Route::post('/education', [EducationV2Controller::class, 'store']);
        Route::post('/education/reorder', [EducationV2Controller::class, 'reorder']);
        Route::get('/education/{id}', [EducationV2Controller::class, 'show'])->whereNumber('id');
        Route::put('/education/{id}', [EducationV2Controller::class, 'update'])->whereNumber('id');
        Route::delete('/education/{id}', [EducationV2Controller::class, 'destroy'])->whereNumber('id');

        Route::get('/work', [WorkExperienceV2Controller::class, 'index']);
        Route::post('/work', [WorkExperienceV2Controller::class, 'store']);
        Route::post('/work/reorder', [WorkExperienceV2Controller::class, 'reorder']);
        Route::get('/work/{id}', [WorkExperienceV2Controller::class, 'show'])->whereNumber('id');
        Route::put('/work/{id}', [WorkExperienceV2Controller::class, 'update'])->whereNumber('id');
        Route::delete('/work/{id}', [WorkExperienceV2Controller::class, 'destroy'])->whereNumber('id');

        Route::get('/social-links', [SocialLinkV2Controller::class, 'index']);
        Route::post('/social-links', [SocialLinkV2Controller::class, 'store']);
        Route::post('/social-links/reorder', [SocialLinkV2Controller::class, 'reorder']);
        Route::get('/social-links/platforms', [SocialLinkV2Controller::class, 'platforms']);
        Route::put('/social-links/{id}', [SocialLinkV2Controller::class, 'update'])->whereNumber('id');
        Route::delete('/social-links/{id}', [SocialLinkV2Controller::class, 'destroy'])->whereNumber('id');
    });

    // Authenticated API v2 Actions
    Route::middleware(['auth:sanctum,admin,web'])->group(function () {
        // Live Streaming actions
        Route::post('/live/channels', [LiveStreamingV2Controller::class, 'store']);
        Route::post('/live/streams', [LiveStreamingV2Controller::class, 'store']);
        Route::post('/live/sessions', [LiveStreamingV2Controller::class, 'store']);
        Route::post('/live/sessions/{id}/end', [LiveStreamingV2Controller::class, 'end'])->whereNumber('id');
        Route::post('/live/streams/{id}/start', [LiveStreamingV2Controller::class, 'start'])->whereNumber('id');
        Route::post('/live/streams/{id}/end', [LiveStreamingV2Controller::class, 'end'])->whereNumber('id');
        Route::post('/live/streams/{id}/sfu/publish', [LiveStreamingV2Controller::class, 'confirmPublish'])->whereNumber('id');
        Route::post('/live/streams/{id}/signal', [LiveStreamingV2Controller::class, 'signal'])->whereNumber('id');
        Route::post('/live/streams/{id}/comments', [LiveStreamingV2Controller::class, 'comment'])->whereNumber('id');
        Route::delete('/live/streams/{id}/comments/{commentId}', [LiveStreamingV2Controller::class, 'deleteComment'])->whereNumber('id');
        Route::post('/live/streams/{id}/reactions', [LiveStreamingV2Controller::class, 'reaction'])->whereNumber('id');
        Route::post('/live/streams/{id}/gifts', [LiveStreamingV2Controller::class, 'gift'])->whereNumber('id');
        Route::post('/live/streams/{id}/share', [LiveStreamingV2Controller::class, 'share'])->whereNumber('id');
        Route::post('/live/streams/{id}/moderators', [LiveStreamingV2Controller::class, 'addModerator'])->whereNumber('id');
        Route::delete('/live/streams/{id}/moderators/{userId}', [LiveStreamingV2Controller::class, 'removeModerator'])->whereNumber('id');
        Route::post('/live/streams/{id}/report', [LiveStreamingV2Controller::class, 'report'])->whereNumber('id');
        Route::post('/live/streams/{id}/replay-upload', [LiveStreamingV2Controller::class, 'uploadReplay'])->whereNumber('id');

        // Marketplace actions
        Route::post('/marketplace/products', [MarketplaceV2Controller::class, 'store']);
        Route::post('/marketplace/products/{id}/order', [MarketplaceV2Controller::class, 'order']);
        Route::post('/marketplace/orders/{id}/checkout', [MarketplaceV2Controller::class, 'checkout']);
        Route::post('/marketplace/orders/{id}/verify-payment', [MarketplaceV2Controller::class, 'verifyPayment']);
        Route::post('/marketplace/orders/{id}/release-escrow', [MarketplaceV2Controller::class, 'releaseEscrow']);

        // Offline Sync
        Route::post('/offline/sync', function (Request $request, OfflineSyncService $sync) {
            $actions = $request->input('actions', []);
            $result = $sync->syncBatch($request->user(), $actions);

            return response()->json(['status' => 'success', 'data' => $result]);
        });

        // Step 13 RBAC Protected Routes
        Route::prefix('rbac')->group(function () {
            Route::get('/creator-studio', function () {
                return response()->json(['success' => true, 'message' => 'ক্রিয়েটর স্টুডিওতে স্বাগতম!']);
            })->middleware('role:CREATOR,ADMIN,SUPER_ADMIN');

            Route::get('/moderator-panel', function () {
                return response()->json(['success' => true, 'message' => 'মডারেটর প্যানেলে স্বাগতম!']);
            })->middleware('role:MODERATOR,ADMIN,SUPER_ADMIN');

            Route::get('/manage-posts', function () {
                return response()->json(['success' => true, 'message' => 'পোস্ট ব্যবস্থাপনা অনুমোদন প্রাপ্ত।']);
            })->middleware('permission:manage.posts');

            Route::get('/manage-users', function () {
                return response()->json(['success' => true, 'message' => 'ইউজার ব্যবস্থাপনা অনুমোদন প্রাপ্ত।']);
            })->middleware('permission:manage.users');

            Route::get('/manage-settings', function () {
                return response()->json(['success' => true, 'message' => 'সিস্টেম সেটিংস পরিবর্তন অনুমোদন প্রাপ্ত।']);
            })->middleware('permission:manage.settings');
        });

        // -------------------------------------------------------------
        // Enterprise Chunked & Resumable Media Upload System
        // -------------------------------------------------------------
        Route::prefix('uploads')->group(function () {
            Route::post('/init', [UploadApiController::class, 'init']);
            Route::post('/sessions', [UploadApiController::class, 'init']);
            Route::post('/chunks', [UploadApiController::class, 'uploadChunk']);
            Route::post('/{sessionId}/chunk', [UploadApiController::class, 'uploadChunk']);
            Route::get('/sessions/{sessionId}', [UploadApiController::class, 'status']);
            Route::post('/sessions/{sessionId}/complete', [UploadApiController::class, 'complete']);
            Route::delete('/sessions/{sessionId}', [UploadApiController::class, 'cancel']);
            Route::get('/{sessionId}/resume', [UploadApiController::class, 'resume']);
            Route::post('/{sessionId}/cancel', [UploadApiController::class, 'cancel']);
            Route::get('/{sessionId}/status', [UploadApiController::class, 'status']);
        });

        // -------------------------------------------------------------
        // Enterprise Stories System v2 (Photos, Videos, Reactions, Replies)
        // -------------------------------------------------------------
        Route::prefix('stories')->group(function () {
            Route::get('/feed', [StoryV2ApiController::class, 'feed']);
            Route::post('/', [StoryV2ApiController::class, 'store']);
            Route::get('/archived', [StoryV2ApiController::class, 'archived']);
            Route::get('/{story}', [StoryV2ApiController::class, 'show'])->whereNumber('story');
            Route::post('/{story}/view', [StoryV2ApiController::class, 'recordView'])->whereNumber('story');
            Route::post('/{story}/react', [StoryV2ApiController::class, 'react'])->whereNumber('story');
            Route::post('/{story}/reply', [StoryV2ApiController::class, 'reply'])->whereNumber('story');
            Route::get('/{story}/viewers', [StoryV2ApiController::class, 'viewers'])->whereNumber('story');
            Route::post('/{story}/archive', [StoryV2ApiController::class, 'archive'])->whereNumber('story');
            Route::post('/{story}/share', [StoryV2ApiController::class, 'share'])->whereNumber('story');
            Route::post('/{story}/report', [StoryV2ApiController::class, 'report'])->whereNumber('story');
            Route::delete('/{story}', [StoryV2ApiController::class, 'destroy'])->whereNumber('story');
        });

        // -------------------------------------------------------------
        // Dedicated Reels System (Short-form 9:16 Video, Views, Reactions)
        // -------------------------------------------------------------
        Route::prefix('reels')->group(function () {
            Route::get('/feed', [ReelV2ApiController::class, 'feed']);
            Route::post('/', [ReelV2ApiController::class, 'store']);
            Route::get('/saved', [ReelV2ApiController::class, 'savedReels']);
            Route::get('/drafts', [ReelV2ApiController::class, 'drafts']);
            Route::get('/{reel}', [ReelV2ApiController::class, 'show'])->whereNumber('reel');
            Route::put('/{reel}', [ReelV2ApiController::class, 'update'])->whereNumber('reel');
            Route::post('/{reel}/publish', [ReelV2ApiController::class, 'publish'])->whereNumber('reel');
            Route::get('/users/{user}', [ReelV2ApiController::class, 'userReels'])->whereNumber('user');
            Route::post('/{reel}/view', [ReelV2ApiController::class, 'recordView'])->whereNumber('reel');
            Route::post('/{reel}/react', [ReelV2ApiController::class, 'react'])->whereNumber('reel');
            Route::get('/{reel}/comments', [ReelV2ApiController::class, 'comments'])->whereNumber('reel');
            Route::post('/{reel}/comments', [ReelV2ApiController::class, 'storeComment'])->whereNumber('reel');
            Route::delete('/comments/{comment}', [ReelV2ApiController::class, 'deleteComment'])->whereNumber('comment');
            Route::post('/comments/{comment}/like', [ReelV2ApiController::class, 'likeComment'])->whereNumber('comment');
            Route::post('/{reel}/share', [ReelV2ApiController::class, 'share'])->whereNumber('reel');
            Route::post('/{reel}/save', [ReelV2ApiController::class, 'save'])->whereNumber('reel');
            Route::post('/{reel}/report', [ReelV2ApiController::class, 'report'])->whereNumber('reel');
            Route::delete('/{reel}', [ReelV2ApiController::class, 'destroy'])->whereNumber('reel');
        });

        // -------------------------------------------------------------
        // Background Music Library & Licensing
        // -------------------------------------------------------------
        Route::prefix('music')->group(function () {
            Route::get('/', [MusicApiController::class, 'index']);
            Route::get('/genres', [MusicApiController::class, 'genres']);
            Route::get('/{id}', [MusicApiController::class, 'show'])->whereNumber('id');
            Route::post('/', [MusicApiController::class, 'store']);
            Route::delete('/{track}', [MusicApiController::class, 'destroy'])->whereNumber('track');
        });

        // -------------------------------------------------------------
        // Admin Media Management & Resource Controls
        // -------------------------------------------------------------
        Route::prefix('admin/media')->group(function () {
            Route::get('/metrics', [MediaManagementApiController::class, 'metrics']);
            Route::put('/settings', [MediaManagementApiController::class, 'updateSettings']);
            Route::put('/users/{user}/quota', [MediaManagementApiController::class, 'updateUserQuota'])->whereNumber('user');
            Route::get('/failed-jobs', [MediaManagementApiController::class, 'failedJobs']);
            Route::post('/retry-job/{id}', [MediaManagementApiController::class, 'retryJob'])->whereNumber('id');
        });
    });

    // =============================================================
    // Enterprise Social Page Operating System (API v2)
    // =============================================================
    Route::prefix('pages')->group(function () {
        // Taxonomy & Public Discovery
        Route::get('/types', [PageRegistrationV2ApiController::class, 'types']);
        Route::get('/categories', [PageRegistrationV2ApiController::class, 'categories']);
        Route::get('/categories/search', [PageRegistrationV2ApiController::class, 'searchCategories']);
        Route::get('/categories/{id}/subcategories', [PageRegistrationV2ApiController::class, 'subcategories'])->whereNumber('id');
        Route::get('/categories/{id}/fields', [PageRegistrationV2ApiController::class, 'fields'])->whereNumber('id');
        Route::get('/username/check', [PageRegistrationV2ApiController::class, 'checkUsername']);

        // Authenticated Registration, Quota & Drafts (defined before wildcard slugOrId)
        Route::middleware(['auth:sanctum,web'])->group(function () {
            Route::get('/quota', [PageRegistrationV2ApiController::class, 'quota']);
            Route::post('/register', [PageRegistrationV2ApiController::class, 'register']);
            Route::get('/drafts', [PageRegistrationV2ApiController::class, 'drafts']);
            Route::post('/drafts', [PageRegistrationV2ApiController::class, 'saveDraft']);
            Route::get('/drafts/{id}', [PageRegistrationV2ApiController::class, 'getDraft'])->whereNumber('id');
            Route::delete('/drafts/{id}', [PageRegistrationV2ApiController::class, 'deleteDraft'])->whereNumber('id');
        });

        // Public / Read Discovery
        Route::get('/', [PageV2ApiController::class, 'index']);
        Route::get('/{slugOrId}', [PageV2ApiController::class, 'show']);
        Route::get('/{page}/posts', [PageContentV2ApiController::class, 'index']);
        Route::get('/{page}/events', [PageEventV2ApiController::class, 'index']);
        Route::get('/{page}/events/{id}', [PageEventV2ApiController::class, 'show']);
        Route::get('/{page}/products', [PageProductV2ApiController::class, 'index']);
        Route::get('/{page}/products/{id}', [PageProductV2ApiController::class, 'show']);

        // Authenticated Page Management & Operations
        Route::middleware(['auth:sanctum,web'])->group(function () {
            // Branch Locations
            Route::get('/{page}/locations', [PageRegistrationV2ApiController::class, 'locations']);
            Route::post('/{page}/locations', [PageRegistrationV2ApiController::class, 'addLocation']);

            // Official Verification Request
            Route::post('/{page}/verification-request', [PageRegistrationV2ApiController::class, 'requestVerification']);

            Route::post('/', [PageV2ApiController::class, 'store']);
            Route::put('/{page}', [PageV2ApiController::class, 'update']);
            Route::delete('/{page}', [PageV2ApiController::class, 'destroy']);
            Route::post('/{id}/restore', [PageV2ApiController::class, 'restore']);
            Route::put('/{page}/settings', [PageV2ApiController::class, 'updateSettings']);
            Route::post('/{page}/transfer-ownership', [PageV2ApiController::class, 'transferOwnership']);

            // Team Management & RBAC
            Route::get('/{page}/team', [PageTeamV2ApiController::class, 'index']);
            Route::post('/{page}/team/invite', [PageTeamV2ApiController::class, 'invite']);
            Route::post('/team/accept', [PageTeamV2ApiController::class, 'accept']);
            Route::put('/{page}/team/{memberId}', [PageTeamV2ApiController::class, 'updateRole']);
            Route::delete('/{page}/team/{memberId}', [PageTeamV2ApiController::class, 'remove']);

            // Content & Scheduling & Calendar
            Route::get('/{page}/posts/drafts', [PageContentV2ApiController::class, 'drafts']);
            Route::get('/{page}/posts/scheduled', [PageContentV2ApiController::class, 'scheduled']);
            Route::get('/{page}/posts/calendar', [PageContentV2ApiController::class, 'calendar']);
            Route::post('/{page}/posts', [PageContentV2ApiController::class, 'store']);
            Route::put('/{page}/posts/{post}', [PageContentV2ApiController::class, 'update']);
            Route::delete('/{page}/posts/{post}', [PageContentV2ApiController::class, 'destroy']);
            Route::post('/{page}/posts/{post}/reschedule', [PageContentV2ApiController::class, 'reschedule']);

            // Moderation Center
            Route::get('/{page}/moderation/blocked', [PageModerationV2ApiController::class, 'blockedUsers']);
            Route::post('/{page}/moderation/block', [PageModerationV2ApiController::class, 'blockUser']);
            Route::delete('/{page}/moderation/unblock/{targetUserId}', [PageModerationV2ApiController::class, 'unblockUser']);
            Route::post('/{page}/moderation/comments', [PageModerationV2ApiController::class, 'moderateComment']);
            Route::post('/{page}/moderation/comments/bulk', [PageModerationV2ApiController::class, 'bulkModerateComments']);

            // Real Analytics & Demographics
            Route::get('/{page}/analytics/overview', [PageAnalyticsV2ApiController::class, 'overview']);
            Route::get('/{page}/analytics/audience', [PageAnalyticsV2ApiController::class, 'audience']);

            // Page Inbox & Multi-agent Messaging
            Route::get('/{page}/inbox/conversations', [PageInboxV2ApiController::class, 'index']);
            Route::get('/{page}/inbox/conversations/{id}', [PageInboxV2ApiController::class, 'show']);
            Route::post('/{page}/inbox/conversations/{id}/reply', [PageInboxV2ApiController::class, 'reply']);
            Route::post('/{page}/inbox/conversations/{id}/assign', [PageInboxV2ApiController::class, 'assignAgent']);
            Route::put('/{page}/inbox/conversations/{id}/status', [PageInboxV2ApiController::class, 'updateStatus']);
            Route::post('/{page}/inbox/conversations/{id}/notes', [PageInboxV2ApiController::class, 'addNote']);
            Route::post('/{page}/inbox/message', [PageInboxV2ApiController::class, 'visitorSendMessage']);
            Route::get('/{page}/inbox/my-thread', [PageInboxV2ApiController::class, 'visitorConversation']);

            // Events & RSVPs
            Route::post('/{page}/events', [PageEventV2ApiController::class, 'store']);
            Route::post('/{page}/events/{id}/rsvp', [PageEventV2ApiController::class, 'rsvp']);
            Route::delete('/{page}/events/{id}', [PageEventV2ApiController::class, 'destroy']);

            // Commerce & Products
            Route::post('/{page}/products', [PageProductV2ApiController::class, 'store']);
            Route::post('/{page}/products/{id}/purchase', [PageProductV2ApiController::class, 'purchase']);
            Route::put('/{page}/products/{id}', [PageProductV2ApiController::class, 'update']);
            Route::delete('/{page}/products/{id}', [PageProductV2ApiController::class, 'destroy']);

            // Audit Logs
            Route::get('/{page}/audit-logs', [PageAuditV2ApiController::class, 'index']);
        });
    });

    // Enterprise Community & Groups V2 Platform
    Route::prefix('groups')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [CommunityV2ApiController::class, 'index']);
        Route::post('/', [CommunityV2ApiController::class, 'store']);
        Route::get('/discover', [CommunityV2ApiController::class, 'discover']);
        Route::get('/{id}', [CommunityV2ApiController::class, 'show']);
        Route::get('/{id}/health', [CommunityV2ApiController::class, 'health']);

        // Discussions (Q&A mode, threaded replies, solve)
        Route::get('/{id}/discussions', [CommunityV2ApiController::class, 'discussions']);
        Route::post('/{id}/discussions', [CommunityV2ApiController::class, 'storeDiscussion']);
        Route::post('/{id}/discussions/{discussionId}/replies', [CommunityV2ApiController::class, 'replyDiscussion']);
        Route::post('/{id}/discussions/{discussionId}/solve/{replyId}', [CommunityV2ApiController::class, 'markDiscussionSolved']);

        // Badges & Contributor Recognition
        Route::get('/{id}/badges', [CommunityV2ApiController::class, 'badges']);
        Route::post('/{id}/members/{userId}/badges', [CommunityV2ApiController::class, 'assignBadge']);

        // Subscriptions & Bookmarks
        Route::post('/{id}/subscriptions/{itemType}/{itemId}', [CommunityV2ApiController::class, 'toggleSubscription']);
        Route::post('/{id}/bookmarks/{itemType}/{itemId}', [CommunityV2ApiController::class, 'toggleBookmark']);
        Route::get('/{id}/bookmarks', [CommunityV2ApiController::class, 'bookmarks']);

        // Custom Roles
        Route::get('/{id}/custom-roles', [CommunityV2ApiController::class, 'customRoles']);
        Route::post('/{id}/custom-roles', [CommunityV2ApiController::class, 'storeCustomRole']);
    });

    // GraphQL Gateway Endpoint
    Route::match(['GET', 'POST'], '/graphql', function (Request $request, GraphQLGatewayService $gql) {
        $query = $request->input('query', $request->getContent());
        $variables = $request->input('variables', []);
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?? [];
        }
        $result = $gql->execute($query, $variables, $request->user());

        return response()->json($result);
    });
});
