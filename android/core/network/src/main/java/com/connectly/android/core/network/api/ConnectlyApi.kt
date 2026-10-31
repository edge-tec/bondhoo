package com.connectly.android.core.network.api

import kotlinx.serialization.Serializable
import retrofit2.Response
import retrofit2.http.*

interface ConnectlyApi {

    // -------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------
    @POST("v1/auth/login")
    suspend fun login(@Body body: LoginRequest): Response<AuthResponse>

    @POST("v1/auth/register")
    suspend fun register(@Body body: RegisterRequest): Response<AuthResponse>

    @POST("v1/auth/logout")
    suspend fun logout(): Response<Unit>

    @GET("v1/auth/me")
    suspend fun getMe(): Response<ApiResponse<UserDto>>

    // -------------------------------------------------------------
    // Home Feed & Stories (Screen 1 & 2)
    // -------------------------------------------------------------
    @GET("v1/feed")
    suspend fun getFeed(
        @Query("tab") tab: String = "for_you",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<PostDto>>>

    @GET("v1/stories")
    suspend fun getStories(): Response<ApiResponse<List<StoryUserDto>>>

    @POST("v1/stories/{id}/view")
    suspend fun viewStory(@Path("id") storyId: Long): Response<Unit>

    @POST("v1/stories/{id}/react")
    suspend fun reactToStory(
        @Path("id") storyId: Long,
        @Body body: ReactionRequest
    ): Response<Unit>

    @POST("v1/posts/{id}/react")
    suspend fun reactToPost(
        @Path("id") postId: Long,
        @Body body: ReactionRequest
    ): Response<ApiResponse<ReactionResponse>>

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
    val login: String,
    val password: String,
    val device_name: String = "Android Device"
)

@Serializable
data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val password_confirmation: String
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

