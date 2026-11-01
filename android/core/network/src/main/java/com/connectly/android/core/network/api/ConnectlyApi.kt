package com.connectly.android.core.network.api

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.jsonPrimitive
import okhttp3.MultipartBody
import retrofit2.Response
import retrofit2.http.*

interface ConnectlyApi {

    // -------------------------------------------------------------
    // Authentication & Account Recovery
    // -------------------------------------------------------------
    @POST("v1/auth/login")
    suspend fun login(@Body body: LoginRequest): Response<AuthResponse>

    @POST("v1/auth/register")
    suspend fun register(@Body body: RegisterRequest): Response<AuthResponse>

    @POST("v1/auth/send-phone-otp")
    suspend fun sendPhoneOtp(@Body body: SendPhoneOtpRequest): Response<ApiResponse<OtpResponse>>

    @POST("v1/auth/verify-phone-otp")
    suspend fun verifyPhoneOtp(@Body body: VerifyPhoneOtpRequest): Response<ApiResponse<VerifyPhoneOtpResponse>>

    @POST("v1/auth/forgot-password")
    suspend fun forgotPassword(@Body body: ForgotPasswordRequest): Response<ApiResponse<Unit>>

    @POST("v1/auth/logout")
    suspend fun logout(): Response<Unit>

    @GET("v1/auth/me")
    suspend fun getMe(): Response<ApiResponse<UserDto>>

    @PUT("v1/auth/profile")
    suspend fun updateProfile(@Body body: UpdateProfileRequest): Response<ApiResponse<UserDto>>

    // -------------------------------------------------------------
    // Profile & Media Upload
    // -------------------------------------------------------------
    @Multipart
    @POST("v1/profile/avatar")
    suspend fun uploadAvatar(@Part file: MultipartBody.Part): Response<ApiResponse<MediaUploadResponse>>

    @Multipart
    @POST("v1/profile/cover")
    suspend fun uploadCover(@Part file: MultipartBody.Part): Response<ApiResponse<MediaUploadResponse>>

    // -------------------------------------------------------------
    // Friends, Following & Moderation
    // -------------------------------------------------------------
    @POST("v1/friends/{id}/request")
    suspend fun sendFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/friends/{id}/accept")
    suspend fun acceptFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/friends/{id}/reject")
    suspend fun rejectFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/friends/{id}/follow")
    suspend fun followUser(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/friends/{id}/follow")
    suspend fun unfollowUser(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/friends/{id}/block")
    suspend fun blockUser(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/friends/{id}/block")
    suspend fun unblockUser(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Profile Privacy Settings
    // -------------------------------------------------------------
    @GET("v2/profile/privacy/settings")
    suspend fun getPrivacySettings(): Response<ApiResponse<PrivacySettingsDto>>

    @PUT("v2/profile/privacy/settings")
    suspend fun updatePrivacySettings(@Body body: UpdatePrivacySettingsRequest): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Home Feed & Stories (Screen 1 & 2)
    // -------------------------------------------------------------
    @GET("v1/feed")
    suspend fun getFeed(
        @Query("tab") tab: String = "for_you",
        @Query("cursor") cursor: String? = null,
        @Query("per_page") perPage: Int = 15
    ): Response<ApiResponse<List<PostDto>>>

    @POST("v1/posts")
    suspend fun createPost(@Body body: CreatePostPayload): Response<ApiResponse<PostDto>>

    @GET("v1/posts/{id}")
    suspend fun getPost(@Path("id") id: Long): Response<ApiResponse<PostDto>>

    @PUT("v1/posts/{id}")
    suspend fun updatePost(
        @Path("id") id: Long,
        @Body body: UpdatePostPayload
    ): Response<ApiResponse<PostDto>>

    @DELETE("v1/posts/{id}")
    suspend fun deletePost(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @POST("v1/profile/saved-items/toggle")
    suspend fun toggleSavePost(@Body body: ToggleSaveItemPayload): Response<ApiResponse<Unit>>

    @POST("v1/posts/{id}/share")
    suspend fun sharePost(
        @Path("id") id: Long,
        @Body body: SharePostPayload
    ): Response<ApiResponse<Unit>>

    @POST("v1/posts/{id}/react")
    suspend fun reactToPost(
        @Path("id") postId: Long,
        @Body body: ReactionRequest
    ): Response<ApiResponse<ReactionResponse>>

    // -------------------------------------------------------------
    // Comments System
    // -------------------------------------------------------------
    @GET("v1/posts/{id}/comments")
    suspend fun getComments(
        @Path("id") postId: Long,
        @Query("page") page: Int = 1,
        @Query("parent_id") parentId: Long? = null
    ): Response<ApiResponse<List<CommentDto>>>

    @POST("v1/posts/{id}/comments")
    suspend fun createComment(
        @Path("id") postId: Long,
        @Body body: CreateCommentPayload
    ): Response<ApiResponse<CommentDto>>

    @PUT("v1/comments/{id}")
    suspend fun updateComment(
        @Path("id") id: Long,
        @Body body: CreateCommentPayload
    ): Response<ApiResponse<CommentDto>>

    @DELETE("v1/comments/{id}")
    suspend fun deleteComment(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @POST("v1/comments/{id}/react")
    suspend fun reactToComment(
        @Path("id") commentId: Long,
        @Body body: ReactionRequest
    ): Response<ApiResponse<ReactionResponse>>

    // -------------------------------------------------------------
    // Stories System (Screen 1 & 2)
    // -------------------------------------------------------------
    @GET("v2/stories/feed")
    suspend fun getStoriesFeed(): Response<ApiResponse<List<StoryUserDto>>>

    @GET("v1/stories")
    suspend fun getStories(): Response<ApiResponse<List<StoryUserDto>>>

    @POST("v2/stories")
    suspend fun createStory(@Body body: CreateStoryPayload): Response<ApiResponse<StoryDto>>

    @DELETE("v2/stories/{id}")
    suspend fun deleteStory(@Path("id") storyId: Long): Response<ApiResponse<Unit>>

    @POST("v2/stories/{id}/view")
    suspend fun viewStory(@Path("id") storyId: Long): Response<Unit>

    @GET("v2/stories/{id}/viewers")
    suspend fun getStoryViewers(@Path("id") storyId: Long): Response<ApiResponse<List<UserDto>>>

    @POST("v2/stories/{id}/react")
    suspend fun reactToStory(
        @Path("id") storyId: Long,
        @Body body: ReactionRequest
    ): Response<ApiResponse<Unit>>

    @POST("v2/stories/{id}/reply")
    suspend fun replyToStory(
        @Path("id") storyId: Long,
        @Body body: StoryReplyPayload
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Reels (Screen 3)
    // -------------------------------------------------------------
    @GET("v2/reels/feed")
    suspend fun getReelsFeed(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 10,
        @Query("tab") tab: String = "for_you"
    ): Response<ApiResponse<List<ReelDto>>>

    @POST("v2/reels")
    suspend fun createReel(@Body body: CreateReelPayload): Response<ApiResponse<ReelDto>>

    @GET("v2/reels/{id}")
    suspend fun getReel(@Path("id") reelId: Long): Response<ApiResponse<ReelDto>>

    @POST("v2/reels/{id}/react")
    suspend fun reactToReel(
        @Path("id") reelId: Long,
        @Body body: ReactionRequest
    ): Response<ApiResponse<Unit>>

    @POST("v2/reels/{id}/view")
    suspend fun recordReelView(
        @Path("id") reelId: Long,
        @Body body: ReelViewRequest
    ): Response<Unit>

    @POST("v2/reels/{id}/save")
    suspend fun saveReel(@Path("id") reelId: Long): Response<ApiResponse<Unit>>

    @POST("v2/reels/{id}/share")
    suspend fun shareReel(@Path("id") reelId: Long): Response<ApiResponse<Unit>>

    @GET("v2/reels/{id}/comments")
    suspend fun getReelComments(
        @Path("id") reelId: Long,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<CommentDto>>>

    @POST("v2/reels/{id}/comments")
    suspend fun createReelComment(
        @Path("id") reelId: Long,
        @Body body: CreateCommentPayload
    ): Response<ApiResponse<CommentDto>>

    @DELETE("v2/reels/{id}")
    suspend fun deleteReel(@Path("id") reelId: Long): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Enterprise Chunked & Resumable Media Uploads
    // -------------------------------------------------------------
    @POST("v2/uploads/init")
    suspend fun initChunkedUpload(
        @Body body: InitUploadRequest
    ): Response<ApiResponse<UploadSessionDto>>

    @Multipart
    @POST("v2/uploads/chunks")
    suspend fun uploadChunk(
        @Part chunk: MultipartBody.Part,
        @Part("session_id") sessionId: okhttp3.RequestBody,
        @Part("chunk_number") chunkNumber: okhttp3.RequestBody,
        @Part("checksum") checksum: okhttp3.RequestBody? = null
    ): Response<ApiResponse<UploadChunkResponseDto>>

    @POST("v2/uploads/sessions/{sessionId}/complete")
    suspend fun completeChunkedUpload(
        @Path("sessionId") sessionId: String
    ): Response<ApiResponse<CompleteUploadResponseDto>>

    @GET("v2/uploads/{sessionId}/resume")
    suspend fun resumeUpload(
        @Path("sessionId") sessionId: String
    ): Response<ApiResponse<ResumeUploadDto>>

    @DELETE("v2/uploads/sessions/{sessionId}")
    suspend fun cancelUpload(
        @Path("sessionId") sessionId: String
    ): Response<ApiResponse<Unit>>

    @Multipart
    @POST("v2/uploads/direct/complete")
    suspend fun uploadVideo(
        @Part file: MultipartBody.Part
    ): Response<ApiResponse<MediaUploadResponse>>

    // -------------------------------------------------------------
    // Groups Hub (Screen 4)
    // -------------------------------------------------------------
    @GET("v1/groups")
    suspend fun getGroups(
        @Query("filter") filter: String = "popular",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<GroupDto>>>

    @POST("v1/groups/{id}/join")
    suspend fun joinGroup(@Path("id") groupId: Long): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Messenger & Conversations (Screen 5)
    // -------------------------------------------------------------
    @GET("v1/conversations")
    suspend fun getConversations(
        @Query("filter") filter: String = "all"
    ): Response<ApiResponse<List<ConversationDto>>>

    @POST("v1/conversations")
    suspend fun createConversation(
        @Body body: CreateConversationRequest
    ): Response<ApiResponse<ConversationDto>>

    @GET("v1/conversations/{id}/messages")
    suspend fun getMessages(
        @Path("id") conversationId: Long,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30
    ): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/conversations/{id}/messages")
    suspend fun sendMessage(
        @Path("id") conversationId: Long,
        @Body body: SendMessageRequest
    ): Response<ApiResponse<MessageDto>>

    @POST("v1/messages/{id}/delivered")
    suspend fun markDelivered(@Path("id") messageId: Long): Response<Unit>

    @POST("v1/conversations/{id}/read")
    suspend fun markRead(@Path("id") conversationId: Long): Response<Unit>

    @POST("v1/messages/{id}/reactions")
    suspend fun reactToChatMessage(
        @Path("id") messageId: Long,
        @Body body: MessageReactionRequest
    ): Response<ApiResponse<Unit>>

    @POST("v1/messages/{id}/delete-for-me")
    suspend fun deleteMessageForMe(@Path("id") messageId: Long): Response<ApiResponse<Unit>>

    @POST("v1/messages/{id}/delete-for-everyone")
    suspend fun deleteMessageForEveryone(@Path("id") messageId: Long): Response<ApiResponse<Unit>>

    @Multipart
    @POST("v1/messages/attachments")
    suspend fun uploadMessageAttachment(
        @Part file: MultipartBody.Part
    ): Response<ApiResponse<MediaUploadResponse>>

    @POST("v1/broadcasting/auth")
    suspend fun broadcastingAuth(
        @Body body: Map<String, String>
    ): Response<BroadcastAuthResponse>

    @GET("v1/messages/unread-count")
    suspend fun getUnreadMessagesCount(): Response<ApiResponse<UnreadCountDto>>

    @PUT("v1/messages/{id}")
    suspend fun editMessage(
        @Path("id") messageId: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<MessageDto>>

    @POST("v1/messages/{id}/forward")
    suspend fun forwardMessage(
        @Path("id") messageId: Long,
        @Body body: Map<String, List<Long>>
    ): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/messages/{id}/save")
    suspend fun saveMessage(@Path("id") messageId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/messages/{id}/save")
    suspend fun unsaveMessage(@Path("id") messageId: Long): Response<ApiResponse<Unit>>

    @GET("v1/saved-messages")
    suspend fun getSavedMessages(): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/conversations/{id}/pin")
    suspend fun togglePinConversation(@Path("id") conversationId: Long): Response<ApiResponse<Map<String, Boolean>>>

    @POST("v1/conversations/{id}/archive")
    suspend fun toggleArchiveConversation(@Path("id") conversationId: Long): Response<ApiResponse<Map<String, Boolean>>>

    @POST("v1/conversations/{id}/mute")
    suspend fun muteConversation(
        @Path("id") conversationId: Long,
        @Body body: Map<String, String> = emptyMap()
    ): Response<ApiResponse<Map<String, Any>>>

    @POST("v1/conversations/{id}/clear")
    suspend fun clearConversation(@Path("id") conversationId: Long): Response<ApiResponse<Map<String, Boolean>>>

    @POST("v1/conversations/{id}/leave")
    suspend fun leaveGroup(@Path("id") conversationId: Long): Response<ApiResponse<Map<String, Boolean>>>

    @PUT("v1/conversations/{id}/group-info")
    suspend fun updateGroupInfo(
        @Path("id") conversationId: Long,
        @Body body: UpdateGroupInfoRequest
    ): Response<ApiResponse<ConversationDto>>

    @POST("v1/conversations/{id}/members")
    suspend fun addGroupMembers(
        @Path("id") conversationId: Long,
        @Body body: Map<String, List<Long>>
    ): Response<ApiResponse<Map<String, Any>>>

    @DELETE("v1/conversations/{id}/members/{userId}")
    suspend fun removeGroupMember(
        @Path("id") conversationId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<Map<String, Any>>>

    @POST("v1/conversations/{id}/draft")
    suspend fun saveDraft(
        @Path("id") conversationId: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<Map<String, Any>>>

    @POST("v1/conversations/{id}/unread")
    suspend fun markConversationUnread(@Path("id") conversationId: Long): Response<ApiResponse<Map<String, Boolean>>>

    @Multipart
    @POST("v1/conversations/{id}/voice")
    suspend fun sendVoiceMessage(
        @Path("id") conversationId: Long,
        @Part audio: MultipartBody.Part,
        @Part("duration") duration: okhttp3.RequestBody,
        @Part("waveform") waveform: okhttp3.RequestBody? = null
    ): Response<ApiResponse<MessageDto>>

    @GET("v1/messenger/sync")
    suspend fun syncMessengerEvents(
        @Query("since_id") sinceId: Long,
        @Query("limit") limit: Int = 100
    ): Response<ApiResponse<SyncEventsResponseDto>>

    @GET("v1/messenger/search")
    suspend fun searchMessenger(
        @Query("q") query: String,
        @Query("conversation_id") conversationId: Long? = null,
        @Query("type") type: String = "all",
        @Query("per_page") perPage: Int = 20
    ): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/presence/heartbeat")
    suspend fun presenceHeartbeat(
        @Body body: Map<String, String> = emptyMap()
    ): Response<ApiResponse<Map<String, Any>>>

    @POST("v1/presence/offline")
    suspend fun presenceOffline(
        @Body body: Map<String, String> = emptyMap()
    ): Response<ApiResponse<Map<String, Any>>>

    @POST("v1/presence/typing")
    suspend fun presenceTyping(
        @Body body: Map<String, Any>
    ): Response<ApiResponse<Map<String, Any>>>

    @GET("v1/presence/friends/active")
    suspend fun getActiveFriends(): Response<ApiResponse<List<ActivePresenceFriendDto>>>

    // -------------------------------------------------------------
    // Real-Time Audio & Video Calling (WebRTC)
    // -------------------------------------------------------------
    @POST("v1/calls")
    suspend fun initiateCall(@Body body: InitiateCallRequest): Response<ApiResponse<CallSessionDto>>

    @POST("v1/calls/{id}/respond")
    suspend fun respondToCall(
        @Path("id") callId: Long,
        @Body body: RespondCallRequest
    ): Response<ApiResponse<CallSessionDto>>

    @POST("v1/calls/{id}/leave")
    suspend fun leaveCall(@Path("id") callId: Long): Response<ApiResponse<CallSessionDto>>

    @POST("v1/calls/{id}/signal")
    suspend fun sendSignal(
        @Path("id") callId: Long,
        @Body body: CallSignalRequest
    ): Response<ApiResponse<Map<String, String>>>

    @GET("v1/calls/{id}/signals")
    suspend fun getSignals(@Path("id") callId: Long): Response<ApiResponse<List<Map<String, String>>>>

    @GET("v1/calls/ice-servers")
    suspend fun getIceServers(): Response<ApiResponse<IceServersResponse>>

    @GET("v1/calls/active")
    suspend fun getActiveCall(): Response<ApiResponse<CallSessionDto?>>

    @GET("v1/calls/history")
    suspend fun getCallHistory(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): Response<ApiResponse<List<CallSessionDto>>>

    @POST("v1/calls/{id}/state")
    suspend fun updateCallState(
        @Path("id") callId: Long,
        @Body body: UpdateCallStateRequest
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Groups & Community (Screen 4)
    // -------------------------------------------------------------
    @GET("v1/groups")
    suspend fun getGroups(
        @Query("filter") filter: String = "discover",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<GroupDto>>>

    @POST("v1/groups")
    suspend fun createGroup(@Body body: CreateGroupRequest): Response<ApiResponse<GroupDto>>

    @GET("v1/groups/{slugOrId}")
    suspend fun getGroupDetails(@Path("slugOrId") slugOrId: String): Response<ApiResponse<GroupDto>>

    @PUT("v1/groups/{id}")
    suspend fun updateGroup(
        @Path("id") id: Long,
        @Body body: UpdateGroupRequest
    ): Response<ApiResponse<GroupDto>>

    @POST("v1/groups/{id}/join")
    suspend fun joinGroup(@Path("id") groupId: Long): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/leave")
    suspend fun leaveGroup(@Path("id") groupId: Long): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/members")
    suspend fun getGroupMembers(@Path("id") groupId: Long): Response<ApiResponse<List<GroupMemberDto>>>

    @PUT("v1/groups/{id}/members/{userId}/role")
    suspend fun updateGroupMemberRole(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long,
        @Body body: UpdateGroupRoleRequest
    ): Response<ApiResponse<Unit>>

    @DELETE("v1/groups/{id}/members/{userId}")
    suspend fun removeGroupMember(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/posts")
    suspend fun getGroupPosts(@Path("id") groupId: Long): Response<ApiResponse<List<PostDto>>>

    @POST("v1/groups/{id}/posts")
    suspend fun createGroupPost(
        @Path("id") groupId: Long,
        @Body body: CreateGroupPostRequest
    ): Response<ApiResponse<PostDto>>

    @POST("v1/groups/{id}/announcements")
    suspend fun createGroupAnnouncement(
        @Path("id") groupId: Long,
        @Body body: CreateGroupAnnouncementRequest
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/invitations")
    suspend fun inviteGroupMembers(
        @Path("id") groupId: Long,
        @Body body: InviteGroupMembersRequest
    ): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/requests")
    suspend fun getGroupRequests(@Path("id") groupId: Long): Response<ApiResponse<List<UserDto>>>

    @POST("v1/groups/{id}/requests/{userId}/approve")
    suspend fun approveGroupRequest(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/requests/{userId}/reject")
    suspend fun rejectGroupRequest(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/moderation/posts")
    suspend fun getGroupModerationPosts(@Path("id") groupId: Long): Response<ApiResponse<List<PostDto>>>

    @POST("v1/groups/{id}/moderation/posts/{postId}/approve")
    suspend fun approveGroupModerationPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/moderation/posts/{postId}/reject")
    suspend fun rejectGroupModerationPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Pages (Screen 6)
    // -------------------------------------------------------------
    @GET("v1/pages")
    suspend fun getPages(
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<PageDto>>>

    @POST("v1/pages")
    suspend fun createPage(@Body body: CreatePageRequest): Response<ApiResponse<PageDto>>

    @GET("v1/pages/{slug}")
    suspend fun getPageDetails(@Path("slug") slug: String): Response<ApiResponse<PageDto>>

    @POST("v1/pages/{id}/follow")
    suspend fun followPage(@Path("id") pageId: Long): Response<ApiResponse<Unit>>

    @GET("v1/pages/{id}/posts")
    suspend fun getPagePosts(@Path("id") pageId: Long): Response<ApiResponse<List<PostDto>>>

    @POST("v1/pages/{id}/posts")
    suspend fun createPagePost(
        @Path("id") pageId: Long,
        @Body body: CreateGroupPostRequest
    ): Response<ApiResponse<PostDto>>

    @PUT("v2/pages/{pageId}")
    suspend fun updatePage(
        @Path("pageId") pageId: Long,
        @Body body: UpdatePageRequest
    ): Response<ApiResponse<PageDto>>

    @GET("v2/pages/{pageId}/analytics/overview")
    suspend fun getPageAnalytics(
        @Path("pageId") pageId: Long
    ): Response<ApiResponse<PageAnalyticsDto>>

    // -------------------------------------------------------------
    // Events & RSVP (Screen 7)
    // -------------------------------------------------------------
    @GET("v1/events")
    suspend fun getEvents(
        @Query("filter") filter: String = "discover",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<EventDto>>>

    @POST("v1/events")
    suspend fun createEvent(@Body body: CreateEventRequest): Response<ApiResponse<EventDto>>

    @GET("v1/events/{id}")
    suspend fun getEventDetails(@Path("id") eventId: Long): Response<ApiResponse<EventDto>>

    @POST("v1/events/{id}/rsvp")
    suspend fun rsvpEvent(
        @Path("id") eventId: Long,
        @Body body: RsvpRequest
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Live Streaming (Screen 8)
    // -------------------------------------------------------------
    @GET("v1/live/streams")
    suspend fun getLiveStreams(
        @Query("status") status: String = "live",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<LiveStreamDto>>>

    @POST("v1/live/streams")
    suspend fun startLiveStream(@Body body: CreateLiveStreamRequest): Response<ApiResponse<LiveStreamDto>>

    @GET("v1/live/streams/{id}")
    suspend fun getLiveStreamDetails(@Path("id") streamId: Long): Response<ApiResponse<LiveStreamDto>>

    @POST("v1/live/streams/{id}/end")
    suspend fun endLiveStream(@Path("id") streamId: Long): Response<ApiResponse<Unit>>

    @POST("v1/live/streams/{id}/join")
    suspend fun joinLiveStream(
        @Path("id") streamId: Long,
        @Body body: JoinLiveSessionRequest
    ): Response<ApiResponse<LiveViewerCountDto>>

    @POST("v1/live/streams/{id}/leave")
    suspend fun leaveLiveStream(
        @Path("id") streamId: Long,
        @Body body: JoinLiveSessionRequest
    ): Response<ApiResponse<LiveViewerCountDto>>

    @POST("v1/live/streams/{id}/comments")
    suspend fun sendLiveComment(
        @Path("id") streamId: Long,
        @Body body: CreateLiveCommentRequest
    ): Response<ApiResponse<LiveCommentDto>>

    @DELETE("v1/live/streams/{id}/comments/{commentId}")
    suspend fun deleteLiveComment(
        @Path("id") streamId: Long,
        @Path("commentId") commentId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/live/streams/{id}/reactions")
    suspend fun sendLiveReaction(
        @Path("id") streamId: Long,
        @Body body: LiveReactionRequest
    ): Response<ApiResponse<Unit>>

    @POST("v1/live/streams/{id}/share")
    suspend fun shareLiveStream(
        @Path("id") streamId: Long,
        @Body body: ShareLiveStreamRequest
    ): Response<ApiResponse<Unit>>

    @POST("v1/live/streams/{id}/moderators")
    suspend fun addLiveModerator(
        @Path("id") streamId: Long,
        @Body body: LiveModeratorRequest
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Notifications (Screen 9)
    // -------------------------------------------------------------
    @GET("v1/notifications")
    suspend fun getNotifications(
        @Query("filter") filter: String = "all",
        @Query("category") category: String = "all",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<NotificationDto>>>

    @GET("v1/notifications/unread-count")
    suspend fun getUnreadNotificationCount(): Response<ApiResponse<Map<String, Int>>>

    @PATCH("v1/notifications/{id}/read")
    suspend fun markNotificationAsRead(@Path("id") id: String): Response<ApiResponse<Unit>>

    @POST("v1/notifications/read-all")
    suspend fun markAllNotificationsAsRead(): Response<ApiResponse<Unit>>

    @GET("v1/notifications/preferences")
    suspend fun getNotificationPreferences(): Response<ApiResponse<NotificationPreferencesDto>>

    @PUT("v1/notifications/preferences")
    suspend fun updateNotificationPreferences(
        @Body body: NotificationPreferencesRequest
    ): Response<ApiResponse<NotificationPreferencesDto>>

    // -------------------------------------------------------------
    // Global Search & Discovery
    // -------------------------------------------------------------
    @GET("v1/search")
    suspend fun search(
        @Query("q") query: String,
        @Query("type") type: String = "all",
        @Query("limit") limit: Int = 15,
        @Query("suggest") suggest: Boolean = false
    ): Response<ApiResponse<SearchResponseDto>>

    @GET("v1/friends/suggestions")
    suspend fun getSuggestedFriends(
        @Query("limit") limit: Int = 10
    ): Response<ApiResponse<List<UserDto>>>

    // -------------------------------------------------------------
    // Device & FCM Push Token Registration
    // -------------------------------------------------------------
    @POST("v1/devices/register")
    suspend fun registerDevice(
        @Body body: RegisterDeviceRequest
    ): Response<ApiResponse<DeviceSessionDto>>

    // -------------------------------------------------------------
    // Content Moderation & Reports
    // -------------------------------------------------------------
    @POST("v1/reports")
    suspend fun reportContent(
        @Body body: ReportContentRequest
    ): Response<ApiResponse<Unit>>
}

// -------------------------------------------------------------
// DTOs & Models
// -------------------------------------------------------------
@Serializable
data class ApiResponse<T>(
    val data: T,
    val message: String? = null,
    val meta: MetaDto? = null
)

@Serializable
data class MetaDto(
    val per_page: Int? = null,
    val has_more: Boolean? = null,
    val next_cursor: String? = null,
    val prev_cursor: String? = null,
    val current_page: Int? = null,
    val last_page: Int? = null,
    val total: Int? = null
)

@Serializable
data class AuthData(
    val user: UserDto,
    val token: String,
    val token_type: String = "Bearer"
)

@Serializable
data class AuthResponse(
    val data: AuthData? = null,
    val user: UserDto? = null,
    val token: String? = null,
    val message: String? = null,
    val success: Boolean = true
) {
    val resolvedUser: UserDto get() = data?.user ?: user ?: UserDto(id = 0L, name = "", username = "")
    val resolvedToken: String get() = data?.token ?: token ?: ""
}


@Serializable
data class UserDto(
    val id: Long,
    val name: String,
    val username: String,
    val email: String? = null,
    val avatar: String? = null,
    val avatar_url: String? = null,
    val bio: String? = null,
    val is_verified: Boolean = false,
    val friends_count: Int = 0,
    val followers_count: Int = 0,
    val following_count: Int = 0
) {
    val resolvedAvatar: String? get() = avatar_url ?: avatar
}

@Serializable
data class PostCountersDto(
    val likes: Int = 0,
    val comments: Int = 0,
    val shares: Int = 0
)

@Serializable
data class PostDto(
    val id: Long,
    val content: String? = null,
    val author: UserDto? = null,
    val user: UserDto? = null,
    val media: List<JsonElement> = emptyList(),
    val counters: PostCountersDto? = null,
    val reactions_count: Int = 0,
    val comments_count: Int = 0,
    val shares_count: Int = 0,
    val audience: String? = "public",
    val viewer_reaction: String? = null,
    val user_reaction: String? = null,
    val is_pinned: Boolean = false,
    val is_saved: Boolean = false,
    val created_at: String? = null,
    val updated_at: String? = null
) {
    val resolvedAuthor: UserDto get() = author ?: user ?: UserDto(id = 0L, name = "Connectly User", username = "user")
    val resolvedReactionsCount: Int get() = if (reactions_count > 0) reactions_count else (counters?.likes ?: 0)
    val resolvedCommentsCount: Int get() = if (comments_count > 0) comments_count else (counters?.comments ?: 0)
    val resolvedSharesCount: Int get() = if (shares_count > 0) shares_count else (counters?.shares ?: 0)
    val resolvedUserReaction: String? get() = viewer_reaction ?: user_reaction
    val resolvedMediaUrls: List<String> get() = media.mapNotNull { element ->
        when (element) {
            is JsonPrimitive -> if (element.isString) element.content else null
            is JsonObject -> element["url"]?.jsonPrimitive?.contentOrNull
                ?: element["original_url"]?.jsonPrimitive?.contentOrNull
                ?: element["preview_url"]?.jsonPrimitive?.contentOrNull
            else -> null
        }
    }
}

@Serializable
data class StoryDto(
    val id: Long,
    val media_url: String? = null,
    val media_type: String? = null,
    val media: List<JsonElement> = emptyList(),
    val type: String = "media",
    val content: String? = null,
    val background_color: String? = null,
    val duration_seconds: Int = 5,
    val is_seen: Boolean = false,
    val has_viewed: Boolean = false,
    val user_reaction: String? = null,
    val my_reaction: String? = null,
    val views_count: Int = 0,
    val reactions_count: Int = 0,
    val replies_count: Int = 0,
    val privacy: String = "public",
    val expires_at: String? = null
) {
    val resolvedSeen: Boolean get() = is_seen || has_viewed
    val resolvedReaction: String? get() = my_reaction ?: user_reaction
    val resolvedMediaUrl: String get() {
        if (!media_url.isNullOrBlank()) return media_url
        val firstMedia = media.firstOrNull()
        return when (firstMedia) {
            is JsonPrimitive -> if (firstMedia.isString) firstMedia.content else ""
            is JsonObject -> firstMedia["url"]?.jsonPrimitive?.contentOrNull
                ?: firstMedia["original_url"]?.jsonPrimitive?.contentOrNull
                ?: firstMedia["preview_url"]?.jsonPrimitive?.contentOrNull
                ?: ""
            else -> ""
        }
    }
    val resolvedMediaType: String get() = media_type ?: if (type == "video" || resolvedMediaUrl.contains(".mp4")) "video" else "image"
}

@Serializable
data class StoryUserDto(
    val user: UserDto,
    val has_unseen: Boolean = false,
    val all_viewed: Boolean = false,
    val stories_count: Int = 0,
    val stories: List<StoryDto> = emptyList()
) {
    val resolvedHasUnseen: Boolean get() = has_unseen || (!all_viewed && stories.any { !it.resolvedSeen })
}

@Serializable
data class ReelDto(
    val id: Long,
    val creator: UserDto? = null,
    val user: UserDto? = null,
    val caption: String? = null,
    val audio_title: String? = "Original Audio",
    val audio_artist: String? = null,
    val video_url: String? = null,
    val thumbnail_url: String? = null,
    val cover_url: String? = null,
    val duration: Double? = null,
    val duration_ms: Long? = null,
    val views_count: Int = 0,
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val shares_count: Int = 0,
    val saves_count: Int = 0,
    val has_liked: Boolean = false,
    val is_liked: Boolean = false,
    val has_saved: Boolean = false,
    val is_saved: Boolean = false,
    val my_reaction: String? = null
) {
    val resolvedCreator: UserDto get() = creator ?: user ?: UserDto(id = 0L, name = "Creator", username = "creator")
    val resolvedCaption: String get() = caption ?: ""
    val resolvedVideoUrl: String get() = video_url ?: ""
    val resolvedThumbnailUrl: String? get() = thumbnail_url ?: cover_url
    val resolvedLiked: Boolean get() = is_liked || has_liked
    val resolvedSaved: Boolean get() = is_saved || has_saved
}

@Serializable
data class ConversationDto(
    val id: Long,
    val type: String = "direct",
    val title: String? = null,
    val avatar_url: String? = null,
    val other_user: UserDto? = null,
    val last_message: MessageDto? = null,
    val unread_count: Int = 0,
    val is_pinned: Boolean = false,
    val is_archived: Boolean = false,
    val is_muted: Boolean = false,
    val is_online: Boolean = false,
    val draft_message: String? = null,
    val my_role: String = "member",
    val members: List<UserDto> = emptyList()
)

@Serializable
data class MessageDto(
    val id: Long,
    val conversation_id: Long,
    val sender_id: Long = 0,
    val body: String = "",
    val type: String = "text",
    val client_message_id: String? = null,
    val delivery_status: String = "sent",
    val is_mine: Boolean = false,
    val is_edited: Boolean = false,
    val is_pinned: Boolean = false,
    val is_saved: Boolean = false,
    val is_deleted: Boolean = false,
    val is_forwarded: Boolean = false,
    val reply_to_message_id: Long? = null,
    val reply_preview: MessagePreviewDto? = null,
    val reactions: MessageReactionsSummaryDto? = null,
    val metadata: Map<String, kotlinx.serialization.json.JsonElement>? = null,
    val created_at: String = ""
)

@Serializable
data class MessagePreviewDto(
    val id: Long,
    val sender_name: String? = null,
    val body: String? = null,
    val type: String = "text"
)

@Serializable
data class MessageReactionsSummaryDto(
    val counts: Map<String, Int> = emptyMap(),
    val total: Int = 0,
    val my_reaction: String? = null
)

@Serializable
data class BroadcastAuthResponse(
    val auth: String,
    val channel_data: String? = null
)

@Serializable
data class SyncEventsResponseDto(
    val events: List<SyncEventDto> = emptyList(),
    val latest_event_id: Long = 0,
    val has_more: Boolean = false
)

@Serializable
data class SyncEventDto(
    val id: Long,
    val event_uuid: String? = null,
    val event_type: String,
    val conversation_id: Long? = null,
    val user_id: Long? = null,
    val payload: kotlinx.serialization.json.JsonObject? = null,
    val timestamp: String? = null
)

@Serializable
data class ActivePresenceFriendDto(
    val id: Long,
    val name: String,
    val username: String,
    val avatar_url: String? = null,
    val online: Boolean = false,
    val last_seen: String? = null,
    val last_seen_display: String? = null,
    val conversation_id: Long? = null,
    val can_call: Boolean = true
)

@Serializable
data class UpdateGroupInfoRequest(
    val title: String? = null,
    val description: String? = null,
    val avatar_url: String? = null
)

@Serializable
data class UnreadCountDto(
    val unread_count: Int = 0
)

@Serializable
data class GroupDto(
    val id: Long,
    val name: String,
    val slug: String,
    val cover_image: String? = null,
    val members_count: Int = 0,
    val is_member: Boolean = false
)

@Serializable
data class EventDto(
    val id: Long,
    val title: String,
    val banner: String? = null,
    val location: String,
    val start_time: String,
    val interested_count: Int = 0,
    val going_count: Int = 0
)

@Serializable
data class ReactionResponse(
    val reacted: Boolean? = null,
    val type: String? = null,
    val user_reaction: String? = null,
    val reactions_count: Int = 0,
    val total_reactions: Int? = null,
    val likes_count: Int? = null,
    val breakdown: Map<String, Int> = emptyMap()
) {
    val resolvedReaction: String? get() = type ?: user_reaction
    val resolvedCount: Int get() = if (reactions_count > 0) reactions_count else (total_reactions ?: likes_count ?: 0)
}

@Serializable
data class LoginRequest(
    val identifier: String,
    val password: String,
    val device_name: String = "Android Device"
)

@Serializable
data class RegisterRequest(
    val name: String,
    val username: String,
    val email: String? = null,
    val phone: String? = null,
    val password: String,
    val device_name: String = "Android Device"
)

@Serializable
data class SendMessageRequest(
    val body: String,
    val client_message_id: String,
    val media_ids: List<Long> = emptyList(),
    val type: String = "text",
    val reply_to_id: Long? = null,
    val reply_to_message_id: Long? = null,
    val metadata: Map<String, kotlinx.serialization.json.JsonElement>? = null
)

@Serializable
data class ReactionRequest(
    val type: String? = null,
    val reaction: String? = null
) {
    constructor(actionType: String) : this(type = actionType, reaction = actionType)
}

@Serializable
data class ReelViewRequest(val duration_seconds: Int, val completed: Boolean)

@Serializable
data class RsvpRequest(val status: String)

@Serializable
data class UpdateProfileRequest(
    val bio: String? = null,
    val occupation: String? = null,
    val location: String? = null
)

@Serializable
data class MediaUploadResponse(
    val id: Long? = null,
    val url: String? = null,
    val path: String? = null,
    val preview_url: String? = null,
    val original_url: String? = null,
    val thumbnail_url: String? = null,
    val mime_type: String? = null,
    val size: Long? = null,
    val width: Int? = null,
    val height: Int? = null,
    val duration: Double? = null
) {
    val resolvedUrl: String get() = preview_url ?: original_url ?: url ?: ""
}

@Serializable
data class InitUploadRequest(
    val filename: String,
    val file_size: Long,
    val mime_type: String,
    val collection: String = "general",
    val chunk_size: Int? = null,
    val checksum: String? = null
)

@Serializable
data class UploadSessionDto(
    val session_id: String,
    val chunk_size: Int,
    val total_chunks: Int,
    val filename: String,
    val expires_at: String
)

@Serializable
data class UploadChunkResponseDto(
    val session_id: String,
    val chunk_number: Int,
    val total_chunks: Int,
    val is_completed: Boolean = false,
    val progress: Double = 0.0
)

@Serializable
data class CompleteUploadResponseDto(
    val session_id: String,
    val media: MediaUploadResponse
)

@Serializable
data class ResumeUploadDto(
    val session_id: String,
    val total_chunks: Int,
    val uploaded_chunks: List<Int> = emptyList(),
    val missing_chunks: List<Int> = emptyList()
)

@Serializable
data class CreateReelPayload(
    val media_id: Long,
    val caption: String? = null,
    val privacy: String = "public",
    val audio_title: String? = null,
    val audio_artist: String? = null,
    val allow_comments: Boolean = true
)

@Serializable
data class SendPhoneOtpRequest(
    val phone: String
)

@Serializable
data class OtpResponse(
    val message: String? = null,
    val expires_in: Int = 300
)

@Serializable
data class VerifyPhoneOtpRequest(
    val phone: String,
    val otp: String,
    val purpose: String = "phone_verification"
)

@Serializable
data class VerifyPhoneOtpResponse(
    val verified: Boolean = false,
    val token: String? = null,
    val user_id: Long? = null
)


@Serializable
data class ForgotPasswordRequest(
    val email: String
)

@Serializable
data class PrivacySettingsDto(
    val profile_visibility: String = "public",
    val avatar_privacy: String = "public",
    val bio_privacy: String = "public"
)

@Serializable
data class UpdatePrivacySettingsRequest(
    val profile_visibility: String? = null,
    val avatar_privacy: String? = null,
    val bio_privacy: String? = null
)

@Serializable
data class CreatePostPayload(
    val content: String? = null,
    val audience: String = "public",
    val type: String = "text",
    val media_ids: List<Long> = emptyList(),
    val tagged_user_ids: List<Long> = emptyList()
)

@Serializable
data class UpdatePostPayload(
    val content: String,
    val audience: String = "public"
)

@Serializable
data class ToggleSaveItemPayload(
    val item_type: String = "post",
    val item_id: Long
)

@Serializable
data class SharePostPayload(
    val caption: String? = null
)

@Serializable
data class CreateCommentPayload(
    val body: String,
    val parent_id: Long? = null
)

@Serializable
data class CommentCountersDto(
    val likes: Int = 0,
    val replies: Int = 0
)

@Serializable
data class CommentDto(
    val id: Long,
    val post_id: Long,
    val parent_id: Long? = null,
    val user: UserDto? = null,
    val author: UserDto? = null,
    val content: String? = null,
    val body: String? = null,
    val counters: CommentCountersDto? = null,
    val reactions_count: Int = 0,
    val viewer_reaction: String? = null,
    val user_reaction: String? = null,
    val replies_count: Int = 0,
    val replies: List<CommentDto> = emptyList(),
    val created_at: String = "Just now"
) {
    val resolvedAuthor: UserDto get() = author ?: user ?: UserDto(id = 0L, name = "Connectly User", username = "user")
    val resolvedBody: String get() = body ?: content ?: ""
    val resolvedReactionsCount: Int get() = if (reactions_count > 0) reactions_count else (counters?.likes ?: 0)
    val resolvedRepliesCount: Int get() = if (replies_count > 0) replies_count else (counters?.replies ?: 0)
    val resolvedUserReaction: String? get() = viewer_reaction ?: user_reaction
}

@Serializable
data class ReportContentRequest(
    val reportable_type: String, // "post", "comment", "user", "group"
    val reportable_id: Long,
    val reason: String, // "spam", "harassment", "hate_speech", "false_information", "violence", "other"
    val details: String? = null
)

@Serializable
data class CreateStoryPayload(
    val type: String = "media",
    val content: String? = null,
    val privacy: String = "public",
    val media_ids: List<Long> = emptyList(),
    val background_color: String? = null
)

@Serializable
data class StoryReplyPayload(
    val message: String
)

@Serializable
data class CreateConversationRequest(
    val recipient_id: Long? = null,
    val type: String = "direct",
    val title: String? = null,
    val participant_ids: List<Long> = emptyList()
)

@Serializable
data class MessageReactionRequest(
    val reaction: String
)

@Serializable
data class InitiateCallRequest(
    val conversation_id: Long? = null,
    val receiver_id: Long? = null,
    val call_type: String
)

@Serializable
data class RespondCallRequest(
    val action: String // "accept", "reject", "busy"
)

@Serializable
data class CallSignalRequest(
    val signal_type: String, // "offer", "answer", "candidate", "screen_share", "renegotiate"
    val payload: Map<String, String>,
    val target_user_id: Long? = null
)

@Serializable
data class IceServersResponse(
    val ice_servers: List<IceServerDto> = emptyList()
)

@Serializable
data class IceServerDto(
    val urls: List<String> = emptyList(),
    val username: String? = null,
    val credential: String? = null
)

@Serializable
data class UpdateCallStateRequest(
    val is_muted: Boolean? = null,
    val is_camera_off: Boolean? = null,
    val is_screen_sharing: Boolean? = null
)

@Serializable
data class CallSessionDto(
    val id: Long,
    val channel_name: String? = null,
    val call_type: String = "audio",
    val status: String = "ringing",
    val caller: UserDto? = null,
    val receiver: UserDto? = null,
    val participants: List<CallParticipantDto> = emptyList(),
    val started_at: String? = null,
    val ended_at: String? = null,
    val duration_seconds: Int = 0
)

@Serializable
data class CallParticipantDto(
    val id: Long,
    val user: UserDto,
    val status: String = "joined",
    val is_muted: Boolean = false,
    val is_camera_off: Boolean = false
)

@Serializable
data class CreateGroupRequest(
    val name: String,
    val description: String? = null,
    val privacy: String = "public"
)

@Serializable
data class PageDto(
    val id: Long,
    val name: String,
    val username: String? = null,
    val category: String = "Business",
    val bio: String? = null,
    val avatar: String? = null,
    val cover: String? = null,
    val followers_count: Int = 0,
    val is_following: Boolean = false,
    val is_verified: Boolean = false
)

@Serializable
data class CreatePageRequest(
    val name: String,
    val category: String = "Business",
    val bio: String? = null
)

@Serializable
data class CreateEventRequest(
    val title: String,
    val description: String? = null,
    val location: String,
    val start_time: String,
    val end_time: String? = null
)

@Serializable
data class LiveStreamDto(
    val id: Long,
    val title: String,
    val streamer: UserDto? = null,
    val user: UserDto? = null,
    val playback_url: String? = null,
    val viewer_count: Int = 0,
    val is_live: Boolean = true,
    val started_at: String? = null
)

@Serializable
data class UpdateGroupRequest(
    val name: String? = null,
    val description: String? = null,
    val privacy: String? = null
)

@Serializable
data class GroupMemberDto(
    val id: Long = 0L,
    val user: UserDto,
    val role: String = "member",
    val joined_at: String? = null
)

@Serializable
data class UpdateGroupRoleRequest(
    val role: String // "admin", "moderator", "member"
)

@Serializable
data class CreateGroupPostRequest(
    val body: String,
    val media_urls: List<String> = emptyList()
)

@Serializable
data class CreateGroupAnnouncementRequest(
    val body: String
)

@Serializable
data class InviteGroupMembersRequest(
    val user_ids: List<Long>
)

@Serializable
data class UpdatePageRequest(
    val name: String? = null,
    val bio: String? = null,
    val category: String? = null
)

@Serializable
data class PageAnalyticsDto(
    val page_views: Int = 0,
    val total_followers: Int = 0,
    val post_reach: Int = 0,
    val engagement_rate: Double = 0.0
)

@Serializable
data class JoinLiveSessionRequest(
    val session_id: String
)

@Serializable
data class LiveViewerCountDto(
    val viewer_count: Int = 0,
    val total_views: Int = 0
)

@Serializable
data class ShareLiveStreamRequest(
    val destination: String = "feed"
)

@Serializable
data class LiveModeratorRequest(
    val user_id: Long
)

@Serializable
data class CreateLiveStreamRequest(
    val title: String,
    val description: String? = null
)

@Serializable
data class LiveCommentDto(
    val id: Long,
    val stream_id: Long,
    val user: UserDto,
    val comment: String,
    val created_at: String = "Just now"
)

@Serializable
data class CreateLiveCommentRequest(
    val comment: String
)

@Serializable
data class LiveReactionRequest(
    val reaction: String // "heart", "like", "star", "fire"
)

@Serializable
data class NotificationDto(
    val id: String,
    val type: String,
    val data: NotificationDataDto? = null,
    val read_at: String? = null,
    val created_at: String = "Just now"
)

@Serializable
data class NotificationDataDto(
    val title: String? = null,
    val message: String? = null,
    val actor_id: Long? = null,
    val actor_name: String? = null,
    val actor_avatar: String? = null,
    val action_url: String? = null
)

@Serializable
data class RegisterDeviceRequest(
    val device_id: String,
    val device_name: String? = "Android Device",
    val platform: String = "android",
    val app_version: String? = "1.0.0",
    val push_token: String? = null
)

@Serializable
data class DeviceSessionDto(
    val id: Long? = null,
    val device_id: String,
    val platform: String,
    val push_token: String? = null,
    val last_active_at: String? = null
)

@Serializable
data class SearchResponseDto(
    val query: String = "",
    val type: String = "all",
    val is_suggestion: Boolean = false,
    val results: SearchResultsMapDto? = null
)

@Serializable
data class SearchResultsMapDto(
    val users: List<UserDto> = emptyList(),
    val posts: List<PostDto> = emptyList(),
    val groups: List<GroupDto> = emptyList(),
    val pages: List<PageDto> = emptyList()
)

@Serializable
data class NotificationPreferencesDto(
    val email_notifications: Boolean = true,
    val sms_notifications: Boolean = false,
    val push_notifications: Boolean = true,
    val friend_request_alerts: Boolean = true,
    val friend_accepted_alerts: Boolean = true,
    val comment_alerts: Boolean = true,
    val mention_alerts: Boolean = true,
    val message_alerts: Boolean = true,
    val like_alerts: Boolean = true,
    val share_alerts: Boolean = true,
    val follower_alerts: Boolean = true,
    val page_alerts: Boolean = true,
    val group_alerts: Boolean = true,
    val security_alerts: Boolean = true
)

@Serializable
data class NotificationPreferencesRequest(
    val email_notifications: Boolean? = null,
    val sms_notifications: Boolean? = null,
    val push_notifications: Boolean? = null,
    val friend_request_alerts: Boolean? = null,
    val friend_accepted_alerts: Boolean? = null,
    val comment_alerts: Boolean? = null,
    val mention_alerts: Boolean? = null,
    val message_alerts: Boolean? = null,
    val like_alerts: Boolean? = null,
    val share_alerts: Boolean? = null,
    val follower_alerts: Boolean? = null,
    val page_alerts: Boolean? = null,
    val group_alerts: Boolean? = null,
    val security_alerts: Boolean? = null
)


