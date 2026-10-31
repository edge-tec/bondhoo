package com.connectly.android.core.network.api

import kotlinx.serialization.Serializable
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
    // Stories System
    // -------------------------------------------------------------
    @GET("v1/stories")
    suspend fun getStories(): Response<ApiResponse<List<StoryUserDto>>>

    @POST("v1/stories")
    suspend fun createStory(@Body body: CreateStoryPayload): Response<ApiResponse<StoryDto>>

    @DELETE("v1/stories/{id}")
    suspend fun deleteStory(@Path("id") storyId: Long): Response<ApiResponse<Unit>>

    @POST("v1/stories/{id}/view")
    suspend fun viewStory(@Path("id") storyId: Long): Response<Unit>

    @GET("v1/stories/{id}/views")
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
        @Query("per_page") perPage: Int = 10
    ): Response<ApiResponse<List<ReelDto>>>

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

    // -------------------------------------------------------------
    // Events & RSVP (Screen 7)
    // -------------------------------------------------------------
    @GET("v1/events")
    suspend fun getEvents(
        @Query("filter") filter: String = "discover",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<EventDto>>>

    @POST("v1/events/{id}/rsvp")
    suspend fun rsvpEvent(
        @Path("id") eventId: Long,
        @Body body: RsvpRequest
    ): Response<ApiResponse<Unit>>
}

// -------------------------------------------------------------
// DTOs & Models
// -------------------------------------------------------------
@Serializable
data class ApiResponse<T>(
    val data: T,
    val message: String? = null
)

@Serializable
data class AuthResponse(
    val user: UserDto,
    val token: String
)

@Serializable
data class UserDto(
    val id: Long,
    val name: String,
    val username: String,
    val email: String? = null,
    val avatar: String? = null,
    val bio: String? = null,
    val is_verified: Boolean = false,
    val friends_count: Int = 0,
    val followers_count: Int = 0,
    val following_count: Int = 0
)

@Serializable
data class PostDto(
    val id: Long,
    val content: String,
    val author: UserDto,
    val media: List<String> = emptyList(),
    val reactions_count: Int = 0,
    val comments_count: Int = 0,
    val user_reaction: String? = null,
    val created_at: String
)

@Serializable
data class StoryUserDto(
    val user: UserDto,
    val has_unseen: Boolean = false,
    val stories: List<StoryDto> = emptyList()
)

@Serializable
data class StoryDto(
    val id: Long,
    val media_url: String,
    val media_type: String = "image",
    val duration_seconds: Int = 5,
    val is_seen: Boolean = false,
    val expires_at: String
)

@Serializable
data class ReelDto(
    val id: Long,
    val creator: UserDto,
    val caption: String,
    val video_url: String,
    val thumbnail_url: String? = null,
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val is_liked: Boolean = false
)

@Serializable
data class ConversationDto(
    val id: Long,
    val type: String = "direct",
    val other_user: UserDto? = null,
    val last_message: MessageDto? = null,
    val unread_count: Int = 0,
    val is_online: Boolean = false
)

@Serializable
data class MessageDto(
    val id: Long,
    val conversation_id: Long,
    val sender_id: Long,
    val body: String,
    val type: String = "text",
    val client_message_id: String? = null,
    val delivery_status: String = "sent",
    val is_mine: Boolean = false,
    val created_at: String
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
    val user_reaction: String?,
    val reactions_count: Int
)

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
    val type: String = "text"
)

@Serializable
data class ReactionRequest(val reaction: String)

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
    val url: String,
    val id: Long? = null
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
    val token: String? = null
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
data class CommentDto(
    val id: Long,
    val post_id: Long,
    val parent_id: Long? = null,
    val user: UserDto,
    val content: String? = null,
    val body: String? = null,
    val reactions_count: Int = 0,
    val user_reaction: String? = null,
    val replies_count: Int = 0,
    val replies: List<CommentDto> = emptyList(),
    val created_at: String = "Just now"
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


