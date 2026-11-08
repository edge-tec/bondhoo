package com.connectly.android.core.network.api

import kotlinx.serialization.KSerializer
import kotlinx.serialization.Serializable
import kotlinx.serialization.builtins.ListSerializer
import kotlinx.serialization.descriptors.PrimitiveKind
import kotlinx.serialization.descriptors.PrimitiveSerialDescriptor
import kotlinx.serialization.descriptors.SerialDescriptor
import kotlinx.serialization.encoding.Decoder
import kotlinx.serialization.encoding.Encoder
import kotlinx.serialization.json.*
import okhttp3.MultipartBody
import okhttp3.RequestBody
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

    @POST("v1/auth/verify-email")
    suspend fun verifyEmail(@Body body: VerifyEmailRequest): Response<AuthResponse>

    @POST("v1/auth/resend-email")
    suspend fun resendEmail(@Body body: ResendEmailRequest): Response<ApiResponse<Unit>>

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

    @GET("v1/profile/{username}")
    suspend fun getUserProfile(
        @Path("username") username: String
    ): Response<ApiResponse<UserDto>>

    @GET("v1/profile/about")
    suspend fun getProfileAbout(): Response<ApiResponse<ProfileAboutDto>>

    @GET("v1/profile/analytics")
    suspend fun getProfileAnalytics(): Response<ApiResponse<ProfileAnalyticsDto>>

    @GET("v1/users/{username}/posts")
    suspend fun getUserPosts(
        @Path("username") username: String
    ): Response<ApiResponse<List<PostDto>>>

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

    @Multipart
    @POST("v1/media/upload")
    suspend fun uploadMedia(
        @Part file: MultipartBody.Part,
        @Part("collection") collection: RequestBody? = null
    ): Response<ApiResponse<MediaUploadResponse>>

    // -------------------------------------------------------------
    // Friends, Following & Moderation
    // -------------------------------------------------------------
    @GET("v1/friends")
    suspend fun getFriends(
        @Query("search") search: String? = null,
        @Query("filter") filter: String = "all",
        @Query("per_page") perPage: Int = 50
    ): Response<ApiResponse<List<UserDto>>>

    @GET("v1/friends/requests")
    suspend fun getFriendRequests(
        @Query("per_page") perPage: Int = 50
    ): Response<ApiResponse<List<FriendRequestDto>>>

    @POST("v1/friends/{id}/request")
    suspend fun sendFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/friends/{id}/cancel")
    suspend fun cancelFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/friends/{id}/accept")
    suspend fun acceptFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/friends/{id}/reject")
    suspend fun rejectFriendRequest(@Path("id") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/friends/{id}")
    suspend fun unfriendUser(@Path("id") userId: Long): Response<ApiResponse<Unit>>

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

    @GET("v1/settings/overview")
    suspend fun getSettingsOverview(): Response<ApiResponse<SettingsOverviewDto>>

    @GET("v1/settings/account")
    suspend fun getAccountSettings(): Response<ApiResponse<SettingsAccountDto>>

    @PUT("v1/settings/account")
    suspend fun updateAccountSettings(@Body body: UpdateAccountRequest): Response<ApiResponse<SettingsAccountDto>>

    @PUT("v1/settings/password")
    suspend fun updatePassword(@Body body: UpdatePasswordRequest): Response<ApiResponse<Unit>>

    @POST("v2/profile/deactivate")
    suspend fun deactivateAccount(@Body body: DeactivateAccountRequest): Response<ApiResponse<Unit>>

    @POST("v2/profile/delete")
    suspend fun deleteAccount(@Body body: DeleteAccountRequest): Response<ApiResponse<Unit>>

    @GET("v1/settings/privacy")
    suspend fun getSettingsPrivacy(): Response<ApiResponse<SettingsPrivacyDto>>

    @PUT("v1/settings/privacy")
    suspend fun updateSettingsPrivacy(@Body body: UpdateSettingsPrivacyRequest): Response<ApiResponse<SettingsPrivacyDto>>

    @POST("v1/settings/2fa/toggle")
    suspend fun toggleTwoFactor(@Body body: Toggle2faRequest): Response<ApiResponse<Toggle2faDto>>

    @GET("v2/auth/sessions")
    suspend fun getActiveSessions(): Response<ApiResponse<List<UserSessionDto>>>

    @DELETE("v2/auth/sessions/{id}")
    suspend fun revokeSession(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @POST("v2/auth/logout-all")
    suspend fun logoutAllSessions(): Response<ApiResponse<Unit>>

    @GET("v1/settings/messenger-sounds")
    suspend fun getMessengerSoundSettings(): Response<ApiResponse<MessengerSoundsDto>>

    @PUT("v1/settings/messenger-sounds")
    suspend fun updateMessengerSoundSettings(@Body body: UpdateMessengerSoundsRequest): Response<ApiResponse<MessengerSoundsDto>>

    @GET("v1/settings/blocking")
    suspend fun getBlockedAndRestrictedUsers(): Response<ApiResponse<BlockedUsersResponseDto>>

    @POST("v1/settings/blocking/{userId}")
    suspend fun blockUserInSettings(@Path("userId") userId: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/settings/blocking/{userId}")
    suspend fun unblockUserInSettings(@Path("userId") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/settings/blocking/{userId}/unrestrict")
    suspend fun unrestrictUser(@Path("userId") userId: Long): Response<ApiResponse<Unit>>

    @POST("v1/settings/blocking/{userId}/unmute")
    suspend fun unmuteUser(@Path("userId") userId: Long): Response<ApiResponse<Unit>>

    @GET("v2/profile/export")
    suspend fun exportUserData(): Response<ApiResponse<Map<String, JsonElement>>>

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

    @POST("v1/posts/{id}/save")
    suspend fun savePost(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @DELETE("v1/posts/{id}/save")
    suspend fun unsavePost(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @POST("v1/posts/{id}/hide")
    suspend fun hidePost(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @POST("v1/posts/{id}/unhide")
    suspend fun unhidePost(@Path("id") id: Long): Response<ApiResponse<Unit>>

    @GET("v1/marketplace/categories")
    suspend fun getMarketplaceCategories(): Response<ApiResponse<List<MarketplaceCategoryDto>>>

    @GET("v1/marketplace/products")
    suspend fun getMarketplaceProducts(
        @Query("category") category: String? = null,
        @Query("subcategory") subcategory: String? = null,
        @Query("q") query: String? = null,
        @Query("min_price") minPrice: Double? = null,
        @Query("max_price") maxPrice: Double? = null,
        @Query("condition") condition: String? = null,
        @Query("sort") sort: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<MarketplaceProductDto>>>

    @Multipart
    @POST("v1/marketplace/media/upload")
    suspend fun uploadMarketplaceMedia(
        @Part file: MultipartBody.Part
    ): Response<ApiResponse<MediaUploadResponse>>

    @POST("v1/marketplace/products")
    suspend fun createMarketplaceProduct(
        @Body body: CreateMarketplaceProductRequest
    ): Response<ApiResponse<MarketplaceProductDto>>

    @GET("v1/marketplace/products/{id}")
    suspend fun getMarketplaceProductDetails(
        @Path("id") id: Long
    ): Response<ApiResponse<MarketplaceProductDto>>

    @PUT("v1/marketplace/products/{id}")
    suspend fun updateMarketplaceProduct(
        @Path("id") id: Long,
        @Body body: UpdateMarketplaceProductRequest
    ): Response<ApiResponse<MarketplaceProductDto>>

    @PATCH("v1/marketplace/products/{id}/status")
    suspend fun updateMarketplaceProductStatus(
        @Path("id") id: Long,
        @Body body: UpdateMarketplaceStatusRequest
    ): Response<ApiResponse<MarketplaceProductDto>>

    @POST("v1/marketplace/products/{id}/favorite")
    suspend fun toggleFavoriteMarketplaceProduct(
        @Path("id") id: Long
    ): Response<ToggleFavoriteResponse>

    @GET("v1/marketplace/favorites")
    suspend fun getFavoriteMarketplaceProducts(): Response<ApiResponse<List<MarketplaceProductDto>>>

    @POST("v1/marketplace/products/{id}/contact")
    suspend fun contactMarketplaceSeller(
        @Path("id") id: Long,
        @Body body: ContactSellerRequest
    ): Response<ApiResponse<ContactSellerResponseDto>>

    @POST("v1/marketplace/products/{id}/order")
    suspend fun orderMarketplaceProduct(
        @Path("id") id: Long,
        @Body body: OrderMarketplaceProductRequest
    ): Response<ApiResponse<MarketplaceOrderDto>>

    @GET("v1/marketplace/my-listings")
    suspend fun getMyMarketplaceListings(): Response<ApiResponse<List<MarketplaceProductDto>>>

    @DELETE("v1/marketplace/products/{id}")
    suspend fun deleteMarketplaceProduct(
        @Path("id") id: Long
    ): Response<MarketplaceDeleteResponse>

    @POST("v1/marketplace/products/{id}/offers")
    suspend fun submitMarketplaceOffer(
        @Path("id") id: Long,
        @Body body: CreateOfferRequest
    ): Response<ApiResponse<MarketplaceOfferDto>>

    @GET("v1/marketplace/offers/my-offers")
    suspend fun getMyMarketplaceOffers(): Response<ApiResponse<List<MarketplaceOfferDto>>>

    @GET("v1/marketplace/offers/received")
    suspend fun getReceivedMarketplaceOffers(): Response<ApiResponse<List<MarketplaceOfferDto>>>

    @POST("v1/marketplace/offers/{id}/accept")
    suspend fun acceptMarketplaceOffer(
        @Path("id") id: Long
    ): Response<ApiResponse<MarketplaceOfferDto>>

    @POST("v1/marketplace/offers/{id}/reject")
    suspend fun rejectMarketplaceOffer(
        @Path("id") id: Long
    ): Response<ApiResponse<MarketplaceOfferDto>>

    @POST("v1/marketplace/offers/{id}/counter")
    suspend fun counterMarketplaceOffer(
        @Path("id") id: Long,
        @Body body: CounterOfferRequest
    ): Response<ApiResponse<MarketplaceOfferDto>>

    @POST("v1/marketplace/offers/{id}/cancel")
    suspend fun cancelMarketplaceOffer(
        @Path("id") id: Long
    ): Response<ApiResponse<MarketplaceOfferDto>>

    @GET("v1/marketplace/orders")
    suspend fun getMarketplaceOrders(
        @Query("role") role: String = "buyer"
    ): Response<MarketplaceOrdersResponse>

    @POST("v1/marketplace/orders/{id}/ship")
    suspend fun shipMarketplaceOrder(
        @Path("id") id: Long,
        @Body body: ShipMarketplaceOrderRequest
    ): Response<ApiResponse<MarketplaceOrderDto>>

    @POST("v1/marketplace/orders/{id}/confirm-delivery")
    suspend fun confirmMarketplaceOrderDelivery(
        @Path("id") id: Long
    ): Response<ApiResponse<MarketplaceOrderDto>>

    @POST("v1/marketplace/orders/{id}/cancel")
    suspend fun cancelMarketplaceOrder(
        @Path("id") id: Long,
        @Body body: CancelMarketplaceOrderRequest
    ): Response<ApiResponse<MarketplaceOrderDto>>

    @GET("v1/marketplace/seller/dashboard")
    suspend fun getMarketplaceSellerDashboard(): Response<ApiResponse<MarketplaceSellerSummaryDto>>

    @GET("v1/marketplace/buyer/dashboard")
    suspend fun getMarketplaceBuyerDashboard(): Response<ApiResponse<MarketplaceBuyerSummaryDto>>

    @POST("v1/marketplace/products/{id}/report")
    suspend fun reportMarketplaceProduct(
        @Path("id") id: Long,
        @Body body: ReportMarketplaceRequest
    ): Response<MarketplaceDeleteResponse>

    @GET("v1/profile/saved-items")
    suspend fun getSavedItems(
        @Query("collection") collection: String? = null
    ): Response<ApiResponse<List<SavedItemDto>>>

    @GET("v1/memories")
    suspend fun getMemories(): Response<ApiResponse<List<MemoryItemDto>>>

    @POST("v1/posts/{id}/share")
    suspend fun sharePost(
        @Path("id") id: Long,
        @Body body: SharePostPayload
    ): Response<ApiResponse<Unit>>

    @GET("v1/sharing/eligible-groups")
    suspend fun getEligibleGroups(
        @Query("q") query: String? = null
    ): Response<ApiResponse<List<EligibleGroupDto>>>

    @GET("v1/sharing/eligible-pages")
    suspend fun getEligiblePages(
        @Query("q") query: String? = null
    ): Response<ApiResponse<List<EligiblePageDto>>>

    @POST("v1/posts/{id}/share/timeline")
    suspend fun sharePostToTimeline(
        @Path("id") id: Long,
        @Body body: ShareTimelineRequest
    ): Response<ApiResponse<ShareResultDto>>

    @POST("v1/posts/{id}/share/groups")
    suspend fun sharePostToGroups(
        @Path("id") id: Long,
        @Body body: ShareGroupsRequest
    ): Response<ApiResponse<ShareResultDto>>

    @POST("v1/posts/{id}/share/pages")
    suspend fun sharePostToPages(
        @Path("id") id: Long,
        @Body body: SharePageRequest
    ): Response<ApiResponse<ShareResultDto>>

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

    @GET("v2/stories/{id}")
    suspend fun getStory(@Path("id") storyId: Long): Response<ApiResponse<StoryDto>>

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
    // Messenger & Conversations (Screen 5)
    // -------------------------------------------------------------
    @GET("v1/conversations")
    suspend fun getConversations(
        @Query("filter") filter: String = "all",
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 50
    ): Response<ApiResponse<List<ConversationDto>>>

    @GET("v1/conversations/{id}")
    suspend fun getConversation(
        @Path("id") conversationId: Long
    ): Response<ApiResponse<ConversationDto>>

    @POST("v1/conversations")
    suspend fun createConversation(
        @Body body: CreateConversationRequest
    ): Response<ApiResponse<ConversationDto>>

    @DELETE("v1/conversations/{id}")
    suspend fun deleteConversation(
        @Path("id") conversationId: Long
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/conversations/{id}/request")
    suspend fun handleMessageRequest(
        @Path("id") conversationId: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<JsonElement?>>

    @GET("v1/conversations/{id}/media")
    suspend fun getConversationMedia(
        @Path("id") conversationId: Long,
        @Query("type") type: String = "media",
        @Query("per_page") perPage: Int = 30
    ): Response<ApiResponse<JsonElement?>>

    @GET("v1/conversations/{id}/pins")
    suspend fun getPinnedMessages(
        @Path("id") conversationId: Long
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/conversations/{id}/messages/{messageId}/pin")
    suspend fun pinMessage(
        @Path("id") conversationId: Long,
        @Path("messageId") messageId: Long
    ): Response<ApiResponse<JsonElement?>>

    @DELETE("v1/conversations/{id}/messages/{messageId}/pin")
    suspend fun unpinMessage(
        @Path("id") conversationId: Long,
        @Path("messageId") messageId: Long
    ): Response<ApiResponse<JsonElement?>>

    @GET("v1/conversations/{id}/messages")
    suspend fun getMessages(
        @Path("id") conversationId: Long,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 50,
        @Query("search") search: String? = null
    ): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/conversations/{id}/messages")
    suspend fun sendMessage(
        @Path("id") conversationId: Long,
        @Body body: SendMessageRequest
    ): Response<ApiResponse<MessageDto>>

    @POST("v1/messages/{id}/delivered")
    suspend fun markDelivered(@Path("id") messageId: Long): Response<JsonElement?>

    @POST("v1/conversations/{id}/read")
    suspend fun markRead(@Path("id") conversationId: Long): Response<JsonElement?>

    @POST("v1/messages/{id}/reactions")
    suspend fun reactToChatMessage(
        @Path("id") messageId: Long,
        @Body body: MessageReactionRequest
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/messages/{id}/delete-for-me")
    suspend fun deleteMessageForMe(@Path("id") messageId: Long): Response<ApiResponse<JsonElement?>>

    @POST("v1/messages/{id}/delete-for-everyone")
    suspend fun deleteMessageForEveryone(@Path("id") messageId: Long): Response<ApiResponse<JsonElement?>>

    @POST("v1/messages/{id}/report")
    suspend fun reportMessage(
        @Path("id") messageId: Long,
        @Body body: ReportMarketplaceRequest
    ): Response<ApiResponse<JsonElement?>>

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
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/messages/{id}/save")
    suspend fun saveMessage(@Path("id") messageId: Long): Response<ApiResponse<JsonElement?>>

    @DELETE("v1/messages/{id}/save")
    suspend fun unsaveMessage(@Path("id") messageId: Long): Response<ApiResponse<JsonElement?>>

    @GET("v1/saved-messages")
    suspend fun getSavedMessages(): Response<ApiResponse<List<MessageDto>>>

    @POST("v1/conversations/{id}/pin")
    suspend fun togglePinConversation(@Path("id") conversationId: Long): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/archive")
    suspend fun toggleArchiveConversation(@Path("id") conversationId: Long): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/mute")
    suspend fun muteConversation(
        @Path("id") conversationId: Long,
        @Body body: Map<String, String> = emptyMap()
    ): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/clear")
    suspend fun clearConversation(@Path("id") conversationId: Long): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/leave")
    suspend fun leaveConversationGroup(@Path("id") conversationId: Long): Response<ApiResponse<JsonObject>>

    @PUT("v1/conversations/{id}/group-info")
    suspend fun updateGroupInfo(
        @Path("id") conversationId: Long,
        @Body body: UpdateGroupInfoRequest
    ): Response<ApiResponse<ConversationDto>>

    @POST("v1/conversations/{id}/members")
    suspend fun addGroupMembers(
        @Path("id") conversationId: Long,
        @Body body: Map<String, List<Long>>
    ): Response<ApiResponse<JsonObject>>

    @DELETE("v1/conversations/{id}/members/{userId}")
    suspend fun removeConversationGroupMember(
        @Path("id") conversationId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<JsonObject>>

    @PUT("v1/conversations/{id}/members/{userId}/role")
    suspend fun updateConversationMemberRole(
        @Path("id") conversationId: Long,
        @Path("userId") userId: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/draft")
    suspend fun saveDraft(
        @Path("id") conversationId: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<JsonObject>>

    @POST("v1/conversations/{id}/unread")
    suspend fun markConversationUnread(@Path("id") conversationId: Long): Response<ApiResponse<JsonObject>>

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
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/presence/offline")
    suspend fun presenceOffline(
        @Body body: Map<String, String> = emptyMap()
    ): Response<ApiResponse<JsonElement?>>

    @POST("v1/presence/typing")
    suspend fun presenceTyping(
        @Body body: Map<String, String>
    ): Response<ApiResponse<JsonElement?>>

    @GET("v1/presence/friends/active")
    suspend fun getActiveFriends(
        @Query("q") query: String? = null
    ): Response<ApiResponse<List<ActivePresenceFriendDto>>>

    // -------------------------------------------------------------
    // Real-Time Audio & Video Calling (WebRTC)
    // -------------------------------------------------------------
    @POST("v1/calls")
    suspend fun initiateCall(@Body body: InitiateCallRequest): Response<ApiResponse<CallSessionDto>>

    @GET("v1/calls/{id}")
    suspend fun getCallDetails(@Path("id") callId: Long): Response<ApiResponse<CallSessionDto>>

    @POST("v1/calls/{id}/invite")
    suspend fun inviteToCall(
        @Path("id") callId: Long,
        @Body body: InviteToCallRequest
    ): Response<ApiResponse<JsonElement?>>

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
    ): Response<ApiResponse<JsonElement?>>

    @GET("v1/calls/{id}/signals")
    suspend fun getSignals(@Path("id") callId: Long): Response<ApiResponse<List<JsonObject>>>

    @GET("v1/calls/ice-servers")
    suspend fun getIceServers(): Response<ApiResponse<IceServersResponse>>

    @GET("v1/calls/active")
    suspend fun getActiveCall(): Response<ApiResponse<CallSessionDto?>>

    @GET("v1/calls/history")
    suspend fun getCallHistory(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30
    ): Response<ApiResponse<List<CallSessionDto>>>

    @POST("v1/calls/{id}/state")
    suspend fun updateCallState(
        @Path("id") callId: Long,
        @Body body: UpdateCallStateRequest
    ): Response<ApiResponse<JsonElement?>>

    // -------------------------------------------------------------
    // Groups & Community (Screen 4)
    // -------------------------------------------------------------
    @GET("v1/groups/home")
    suspend fun getGroupsHome(): Response<ApiResponse<GroupsHomeResponseDto>>

    @GET("v1/groups/categories")
    suspend fun getGroupCategories(): Response<ApiResponse<List<GroupCategoryDto>>>

    @GET("v1/groups")
    suspend fun getGroups(
        @Query("filter") filter: String = "discover",
        @Query("category") category: String? = null,
        @Query("q") q: String? = null,
        @Query("sort") sort: String? = null,
        @Query("location") location: String? = null,
        @Query("privacy") privacy: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<GroupDto>>>

    @POST("v1/groups")
    suspend fun createGroup(@Body body: CreateGroupRequest): Response<ApiResponse<GroupDto>>

    @GET("v1/groups/{slugOrId}")
    suspend fun getGroupDetails(@Path("slugOrId") slugOrId: String): Response<ApiResponse<GroupDto>>

    @GET("v1/groups/{slugOrId}/profile")
    suspend fun getGroupProfile(@Path("slugOrId") slugOrId: String): Response<ApiResponse<GroupProfileResponseDto>>

    @GET("v1/groups/{id}/dashboard")
    suspend fun getGroupDashboard(
        @Path("id") groupId: Long,
        @Query("period") period: String? = null,
        @Query("start_date") startDate: String? = null,
        @Query("end_date") endDate: String? = null
    ): Response<ApiResponse<GroupDashboardResponseDto>>

    @GET("v1/groups/{id}/announcements")
    suspend fun getGroupAnnouncements(@Path("id") groupId: Long): Response<ApiResponse<List<GroupAnnouncementDto>>>

    @GET("v1/groups/{id}/media")
    suspend fun getGroupMedia(@Path("id") groupId: Long): Response<ApiResponse<List<GroupMediaItemDto>>>

    @POST("v1/groups/{id}/follow")
    suspend fun toggleFollowGroup(@Path("id") groupId: Long): Response<ApiResponse<ToggleFollowGroupResponseDto>>

    @POST("v1/groups/{id}/cancel-request")
    suspend fun cancelGroupRequest(@Path("id") groupId: Long): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/roles")
    suspend fun getGroupRoles(@Path("id") groupId: Long): Response<ApiResponse<GroupRolesCatalogResponseDto>>

    @POST("v1/groups/{id}/roles")
    suspend fun createCustomRole(
        @Path("id") groupId: Long,
        @Body request: CreateCustomRoleRequest
    ): Response<ApiResponse<GroupRoleDto>>

    @PUT("v1/groups/{id}/roles/{roleId}")
    suspend fun updateCustomRole(
        @Path("id") groupId: Long,
        @Path("roleId") roleId: Long,
        @Body request: UpdateCustomRoleRequest
    ): Response<ApiResponse<GroupRoleDto>>

    @DELETE("v1/groups/{id}/roles/{roleId}")
    suspend fun deleteCustomRole(
        @Path("id") groupId: Long,
        @Path("roleId") roleId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/roles/assign")
    suspend fun assignGroupRole(
        @Path("id") groupId: Long,
        @Body request: AssignRoleRequest
    ): Response<ApiResponse<List<GroupMemberDto>>>

    @POST("v1/groups/{id}/roles/revoke")
    suspend fun revokeGroupRole(
        @Path("id") groupId: Long,
        @Body request: AssignRoleRequest
    ): Response<ApiResponse<List<GroupMemberDto>>>

    @GET("v1/groups/{id}/roles/history")
    suspend fun getGroupRoleHistory(@Path("id") groupId: Long): Response<ApiResponse<List<RoleHistoryItemDto>>>

    @GET("v1/groups/{id}/members")
    suspend fun getGroupMembersDetailed(
        @Path("id") groupId: Long,
        @Query("q") query: String? = null,
        @Query("role") role: String? = null,
        @Query("status") status: String? = null,
        @Query("sort") sort: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): Response<ApiResponse<List<GroupMemberDto>>>

    @POST("v1/groups/{id}/members/{userId}/restrict")
    suspend fun restrictMember(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long,
        @Body request: RestrictMemberRequest
    ): Response<ApiResponse<GroupMemberDto>>

    @POST("v1/groups/{id}/members/{userId}/unrestrict")
    suspend fun unrestrictMember(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<GroupMemberDto>>

    @GET("v1/groups/{id}/members/{userId}/activity")
    suspend fun getMemberActivity(
        @Path("id") groupId: Long,
        @Path("userId") userId: Long
    ): Response<ApiResponse<MemberActivityResponseDto>>

    @POST("v1/groups/{id}/members/bulk-action")
    suspend fun bulkManageMembers(
        @Path("id") groupId: Long,
        @Body request: BulkMemberActionRequest
    ): Response<ApiResponse<BulkMemberActionResponseDto>>

    @PUT("v1/groups/{id}")
    suspend fun updateGroup(
        @Path("id") id: Long,
        @Body body: UpdateGroupRequest
    ): Response<ApiResponse<GroupDto>>

    @POST("v1/groups/{id}/join")
    suspend fun joinGroup(@Path("id") groupId: Long): Response<ApiResponse<GroupJoinResponseDto>>

    @POST("v1/groups/{id}/leave")
    suspend fun leaveGroup(@Path("id") groupId: Long): Response<ApiResponse<GroupActionResponseDto?>>

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
    suspend fun getGroupPosts(
        @Path("id") groupId: Long,
        @Query("filter") filter: String? = null,
        @Query("type") type: String? = null,
        @Query("sort") sort: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<PostDto>>>

    @GET("v1/groups/{id}/posts/{postId}")
    suspend fun getGroupPostDetails(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long
    ): Response<ApiResponse<PostDto>>

    @PUT("v1/groups/{id}/posts/{postId}")
    suspend fun updateGroupPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long,
        @Body body: UpdateGroupPostRequest
    ): Response<ApiResponse<PostDto>>

    @DELETE("v1/groups/{id}/posts/{postId}")
    suspend fun deleteGroupPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/{id}/posts/{postId}/pin")
    suspend fun togglePinGroupPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long
    ): Response<ApiResponse<TogglePinResponseDto>>

    @POST("v1/groups/{id}/posts/{postId}/vote")
    suspend fun voteGroupPostPoll(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long,
        @Body body: VoteGroupPostPollRequest
    ): Response<ApiResponse<JsonElement>>

    @POST("v1/groups/{id}/posts/{postId}/react")
    suspend fun reactGroupPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long,
        @Body body: ReactGroupPostRequest
    ): Response<ApiResponse<ReactionResponse>>

    @POST("v1/groups/{id}/posts/{postId}/report")
    suspend fun reportGroupPost(
        @Path("id") groupId: Long,
        @Path("postId") postId: Long,
        @Body body: ReportGroupPostRequest
    ): Response<ApiResponse<Unit>>

    @GET("v1/groups/{id}/moderation/reports")
    suspend fun getGroupReports(
        @Path("id") groupId: Long,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<GroupReportDto>>>

    @POST("v1/groups/{id}/moderation/reports/{reportId}/action")
    suspend fun actionGroupReport(
        @Path("id") groupId: Long,
        @Path("reportId") reportId: Long,
        @Body body: ActionGroupReportRequest
    ): Response<ApiResponse<Unit>>

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

    @POST("v1/groups/invitations/{invitationId}/accept")
    suspend fun acceptGroupInvitation(
        @Path("invitationId") invitationId: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/groups/invitations/{invitationId}/decline")
    suspend fun declineGroupInvitation(
        @Path("invitationId") invitationId: Long
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
    // Pages Management & Professional System
    // -------------------------------------------------------------
    @GET("v1/pages")
    suspend fun getPages(
        @Query("filter") filter: String = "discover",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<PageDto>>>

    @POST("v1/pages")
    suspend fun createPage(@Body body: CreatePageRequest): Response<ApiResponse<PageDto>>

    @POST("v2/pages/register")
    suspend fun registerPage(@Body body: CreatePageRequest): Response<ApiResponse<PageDto>>

    @GET("v2/pages/username/check")
    suspend fun checkPageUsername(@Query("username") username: String): Response<ApiResponse<PageUsernameCheckDto>>

    @GET("v1/pages/{slug}")
    suspend fun getPageDetails(@Path("slug") slug: String): Response<ApiResponse<PageDto>>

    @POST("v1/pages/{id}/follow")
    suspend fun followPage(@Path("id") pageId: Long): Response<ApiResponse<PageFollowResponseDto>>

    @POST("v1/pages/{id}/like")
    suspend fun likePage(@Path("id") pageId: Long): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>

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

    @DELETE("v2/pages/{pageId}")
    suspend fun deletePage(@Path("pageId") pageId: Long): Response<ApiResponse<Unit>>

    @GET("v2/pages/{pageId}/analytics/overview")
    suspend fun getPageAnalytics(
        @Path("pageId") pageId: Long,
        @Query("range") range: String = "7d"
    ): Response<ApiResponse<PageAnalyticsDetailDto>>

    @GET("v2/pages/{pageId}/roles")
    suspend fun getPageRoles(@Path("pageId") pageId: Long): Response<ApiResponse<List<PageRoleDto>>>

    @POST("v2/pages/{pageId}/roles")
    suspend fun assignPageRole(
        @Path("pageId") pageId: Long,
        @Body body: AssignPageRoleRequest
    ): Response<ApiResponse<PageRoleDto>>

    @DELETE("v2/pages/{pageId}/roles/{roleId}")
    suspend fun removePageRole(
        @Path("pageId") pageId: Long,
        @Path("roleId") roleId: Long
    ): Response<ApiResponse<Unit>>

    @GET("v2/pages/{pageId}/reviews")
    suspend fun getPageReviews(@Path("pageId") pageId: Long): Response<ApiResponse<List<PageReviewDto>>>

    @POST("v2/pages/{pageId}/reviews")
    suspend fun createPageReview(
        @Path("pageId") pageId: Long,
        @Body body: CreatePageReviewRequest
    ): Response<ApiResponse<PageReviewDto>>

    @GET("v2/pages/{pageId}/products")
    suspend fun getPageProducts(@Path("pageId") pageId: Long): Response<ApiResponse<List<PageProductDto>>>

    @GET("v2/pages/{pageId}/services")
    suspend fun getPageServices(@Path("pageId") pageId: Long): Response<ApiResponse<List<PageServiceDto>>>

    @GET("v2/pages/{pageId}/inbox")
    suspend fun getPageInbox(@Path("pageId") pageId: Long): Response<ApiResponse<List<PageInboxMessageDto>>>

    @POST("v2/pages/{pageId}/inbox/send")
    suspend fun sendPageMessage(
        @Path("pageId") pageId: Long,
        @Body body: SendPageMessageRequest
    ): Response<ApiResponse<PageInboxMessageDto>>

    @GET("v2/pages/{pageId}/settings")
    suspend fun getPageSettings(@Path("pageId") pageId: Long): Response<ApiResponse<PageSettingsDto>>

    @PUT("v2/pages/{pageId}/settings")
    suspend fun updatePageSettings(
        @Path("pageId") pageId: Long,
        @Body body: PageSettingsDto
    ): Response<ApiResponse<PageSettingsDto>>

    @POST("v2/pages/{pageId}/verification-request")
    suspend fun requestPageVerification(
        @Path("pageId") pageId: Long,
        @Body body: RequestPageVerificationRequest
    ): Response<ApiResponse<PageVerificationResponseDto>>

    @GET("v2/pages/{pageId}/monetization")
    suspend fun getPageMonetization(@Path("pageId") pageId: Long): Response<ApiResponse<PageMonetizationStatusDto>>

    @PUT("v2/pages/{pageId}/monetization")
    suspend fun updatePageMonetization(
        @Path("pageId") pageId: Long,
        @Body body: UpdatePageMonetizationRequest
    ): Response<ApiResponse<PageMonetizationStatusDto>>

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

    @DELETE("v1/notifications/{id}")
    suspend fun deleteNotification(@Path("id") id: String): Response<ApiResponse<Unit>>

    @DELETE("v1/notifications")
    suspend fun deleteAllNotifications(@Query("only_read") onlyRead: Boolean = false): Response<ApiResponse<Unit>>

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

    @GET("v1/search/history")
    suspend fun getSearchHistory(): Response<ApiResponse<List<String>>>

    @DELETE("v1/search/history/{term}")
    suspend fun deleteSearchHistoryItem(
        @Path("term") term: String
    ): Response<ApiResponse<List<String>>>

    @DELETE("v1/search/history")
    suspend fun clearSearchHistory(): Response<ApiResponse<List<String>>>

    @GET("v1/friends/suggestions")
    suspend fun getSuggestedFriends(
        @Query("limit") limit: Int = 10
    ): Response<ApiResponse<List<UserDto>>>

    // -------------------------------------------------------------
    // Device & FCM Push Token Registration & Lifecycle
    // -------------------------------------------------------------
    @POST("v1/devices/register")
    suspend fun registerDevice(
        @Body body: RegisterDeviceRequest
    ): Response<ApiResponse<DeviceSessionDto>>

    @DELETE("v1/devices/{id}")
    suspend fun revokeDeviceSession(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, Boolean>>>

    // -------------------------------------------------------------
    // Content Moderation & Reports
    // -------------------------------------------------------------
    @POST("v1/reports")
    suspend fun reportContent(
        @Body body: ReportContentRequest
    ): Response<ApiResponse<Unit>>

    // -------------------------------------------------------------
    // Job Portal & Recruitment Management (Phase 12)
    // -------------------------------------------------------------
    @GET("v1/jobs")
    suspend fun getJobs(
        @Query("q") query: String? = null,
        @Query("category_id") categoryId: Long? = null,
        @Query("employment_type") employmentType: String? = null,
        @Query("workplace_type") workplaceType: String? = null,
        @Query("location") location: String? = null,
        @Query("salary_min") salaryMin: Int? = null,
        @Query("salary_max") salaryMax: Int? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 15
    ): Response<ApiResponse<Map<String, List<JobDto>>>>

    @GET("v1/jobs/{id}")
    suspend fun getJobDetails(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs")
    suspend fun createJob(
        @Body body: CreateJobApiRequest
    ): Response<ApiResponse<Map<String, JobDto>>>

    @PUT("v1/jobs/{id}")
    suspend fun updateJob(
        @Path("id") id: Long,
        @Body body: CreateJobApiRequest
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs/{id}/publish")
    suspend fun publishJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs/{id}/pause")
    suspend fun pauseJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs/{id}/close")
    suspend fun closeJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs/{id}/duplicate")
    suspend fun duplicateJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, JobDto>>>

    @POST("v1/jobs/{id}/apply")
    suspend fun applyForJob(
        @Path("id") id: Long,
        @Body body: ApplyJobRequest
    ): Response<ApiResponse<Map<String, JobApplicationDto>>>

    @GET("v1/jobs/my/applications")
    suspend fun getMyApplications(): Response<ApiResponse<List<JobApplicationDto>>>

    @DELETE("v1/jobs/{id}")
    suspend fun deleteJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Unit>>

    @POST("v1/jobs/{id}/save")
    suspend fun saveJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Unit>>

    @DELETE("v1/jobs/{id}/save")
    suspend fun unsaveJob(
        @Path("id") id: Long
    ): Response<ApiResponse<Unit>>

    @GET("v1/candidate/saved-jobs")
    suspend fun getSavedJobs(): Response<ApiResponse<Map<String, List<JobDto>>>>

    @GET("v1/candidate/profile")
    suspend fun getCandidateProfile(): Response<ApiResponse<Map<String, CandidateProfileDto>>>

    @PUT("v1/candidate/profile")
    suspend fun updateCandidateProfile(
        @Body body: UpdateCandidateProfileRequest
    ): Response<ApiResponse<Map<String, CandidateProfileDto>>>

    @GET("v1/candidate/resumes")
    suspend fun getCandidateResumes(): Response<ApiResponse<Map<String, List<CandidateResumeDto>>>>

    @DELETE("v1/candidate/resumes/{id}")
    suspend fun deleteResume(
        @Path("id") id: Long
    ): Response<ApiResponse<Unit>>

    @GET("v1/candidate/job-alerts")
    suspend fun getJobAlerts(): Response<ApiResponse<Map<String, List<JobAlertDto>>>>

    @POST("v1/candidate/job-alerts")
    suspend fun createJobAlert(
        @Body body: CreateJobAlertRequest
    ): Response<ApiResponse<Map<String, JobAlertDto>>>

    @DELETE("v1/candidate/job-alerts/{id}")
    suspend fun deleteJobAlert(
        @Path("id") id: Long
    ): Response<ApiResponse<Unit>>

    @GET("v1/companies")
    suspend fun getCompanies(
        @Query("mine") mine: Boolean? = null
    ): Response<ApiResponse<Map<String, List<CompanyDto>>>>

    @POST("v1/companies")
    suspend fun createCompany(
        @Body body: CreateCompanyRequest
    ): Response<ApiResponse<Map<String, CompanyDto>>>

    @GET("v1/companies/{id}")
    suspend fun getCompanyDetails(
        @Path("id") id: Long
    ): Response<ApiResponse<Map<String, CompanyDto>>>

    @GET("v1/companies/{id}/dashboard")
    suspend fun getCompanyDashboard(
        @Path("id") id: Long
    ): Response<ApiResponse<EmployerDashboardDto>>

    @GET("v1/companies/{id}/recruitment/pipeline")
    suspend fun getAtsPipeline(
        @Path("id") id: Long,
        @Query("job_id") jobId: Long? = null
    ): Response<ApiResponse<AtsPipelineDto>>

    @PATCH("v1/applications/{id}/stage")
    suspend fun updateAtsStage(
        @Path("id") id: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<Map<String, JobApplicationDto>>>

    @POST("v1/applications/{id}/notes")
    suspend fun addApplicationNote(
        @Path("id") id: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<Unit>>

    @GET("v1/interviews")
    suspend fun getInterviews(): Response<ApiResponse<Map<String, List<InterviewDto>>>>

    @POST("v1/applications/{id}/interviews")
    suspend fun scheduleInterview(
        @Path("id") applicationId: Long,
        @Body body: ScheduleInterviewRequest
    ): Response<ApiResponse<Map<String, InterviewDto>>>

    @POST("v1/interviews/{id}/response")
    suspend fun respondInterview(
        @Path("id") id: Long,
        @Body body: Map<String, String>
    ): Response<ApiResponse<Map<String, InterviewDto>>>

    // -------------------------------------------------------------
    // Phase 13: Professional Learning & Development Platform (LMS)
    // -------------------------------------------------------------
    @GET("v1/learning/categories")
    suspend fun getLearningCategories(): Response<ApiResponse<List<LearningCategoryDto>>>

    @GET("v1/learning/courses")
    suspend fun getLearningCourses(
        @Query("q") query: String? = null,
        @Query("category_id") categoryId: Long? = null,
        @Query("level") level: String? = null,
        @Query("type") type: String? = null,
        @Query("skill") skill: String? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 15
    ): Response<ApiResponse<List<LearningCourseDto>>>

    @GET("v1/learning/courses/{slug}")
    suspend fun getLearningCourseDetails(
        @Path("slug") slug: String
    ): Response<ApiResponse<LearningCourseDetailsResponseDto>>

    @POST("v1/learning/courses")
    suspend fun createLearningCourse(
        @Body body: kotlinx.serialization.json.JsonObject
    ): Response<ApiResponse<LearningCourseDto>>

    @GET("v1/learning/courses/{id}/curriculum")
    suspend fun getLearningCurriculum(
        @Path("id") courseId: Long
    ): Response<ApiResponse<List<LearningSectionDto>>>

    @POST("v1/learning/courses/{id}/enroll")
    suspend fun enrollInCourse(
        @Path("id") courseId: Long,
        @Body body: EnrollCourseRequest = EnrollCourseRequest()
    ): Response<ApiResponse<LearningEnrollmentDto>>

    @GET("v1/learning/my-learning")
    suspend fun getMyLearning(
        @Query("status") status: String? = null
    ): Response<LearningMyLearningResponse>

    @GET("v1/learning/lessons/{id}")
    suspend fun getLearningLesson(
        @Path("id") lessonId: Long
    ): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>

    @POST("v1/learning/lessons/{id}/progress")
    suspend fun updateLessonProgress(
        @Path("id") lessonId: Long,
        @Body body: UpdateLessonProgressRequest
    ): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>

    @GET("v1/learning/quizzes/{id}")
    suspend fun getLearningQuiz(
        @Path("id") quizId: Long
    ): Response<ApiResponse<LearningQuizDto>>

    @POST("v1/learning/quizzes/{id}/attempt")
    suspend fun startQuizAttempt(
        @Path("id") quizId: Long
    ): Response<ApiResponse<LearningQuizAttemptDto>>

    @POST("v1/learning/quizzes/attempts/{attemptId}/submit")
    suspend fun submitQuizAttempt(
        @Path("attemptId") attemptId: Long,
        @Body body: kotlinx.serialization.json.JsonObject
    ): Response<ApiResponse<LearningQuizAttemptDto>>

    @POST("v1/learning/courses/{id}/certificate")
    suspend fun claimCertificate(
        @Path("id") courseId: Long
    ): Response<ApiResponse<LearningCertificateDto>>

    @GET("v1/learning/my-certificates")
    suspend fun getMyCertificates(): Response<ApiResponse<List<LearningCertificateDto>>>

    @GET("v1/learning/certificates/{code}")
    suspend fun verifyCertificate(
        @Path("code") code: String
    ): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>

    @GET("v1/learning/courses/{id}/reviews")
    suspend fun getCourseReviews(
        @Path("id") courseId: Long
    ): Response<ApiResponse<List<LearningReviewDto>>>

    @POST("v1/learning/courses/{id}/reviews")
    suspend fun submitCourseReview(
        @Path("id") courseId: Long,
        @Body body: kotlinx.serialization.json.JsonObject
    ): Response<ApiResponse<LearningReviewDto>>

    @POST("v1/learning/instructors/apply")
    suspend fun applyAsInstructor(
        @Body body: kotlinx.serialization.json.JsonObject
    ): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>

    @GET("v1/learning/instructors/dashboard")
    suspend fun getInstructorDashboard(): Response<ApiResponse<kotlinx.serialization.json.JsonElement?>>
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
    val user: UserDto? = null,
    val token: String? = null,
    val token_type: String = "Bearer",
    val requires_email_verification: Boolean = false,
    val requires_verification: Boolean = false,
    val email_sent: Boolean = false,
    val email_error: String? = null,
    val otp: String? = null,
    val dev_otp: String? = null,
    val email: String? = null
)

@Serializable
data class AuthResponse(
    val data: AuthData? = null,
    val user: UserDto? = null,
    val token: String? = null,
    val message: String? = null,
    val success: Boolean = true,
    val requires_email_verification: Boolean = false,
    val requires_verification: Boolean = false,
    val email_sent: Boolean = false,
    val email: String? = null
) {
    val resolvedUser: UserDto get() = data?.user ?: user ?: UserDto(id = 0L, name = "", username = "")
    val resolvedToken: String get() = data?.token ?: token ?: ""
    val resolvedRequiresEmailVerification: Boolean get() = requires_email_verification || requires_verification || (data?.requires_email_verification ?: false) || (data?.requires_verification ?: false)
    val resolvedEmail: String? get() = email ?: data?.email ?: resolvedUser.email
}

@Serializable
data class VerifyEmailRequest(
    val identifier: String,
    val otp: String,
    val token: String? = null
)

@Serializable
data class ResendEmailRequest(
    val email: String
)


@Serializable
data class FriendProfileDto(
    val avatar_url: String? = null,
    val bio: String? = null,
    val city: String? = null,
    val country: String? = null
)

@Serializable
data class FriendRequestDto(
    val id: Long? = null,
    val user_id: Long? = null,
    val friend_id: Long? = null,
    val status: String? = null,
    val requested_at: String? = null,
    val user: UserDto? = null
)

@Serializable
data class UserDto(
    val id: Long = 0L,
    val name: String = "",
    val username: String = "",
    val email: String? = null,
    val avatar: String? = null,
    val avatar_url: String? = null,
    val bio: String? = null,
    val role: String? = null,
    val is_online: Boolean = false,
    val is_verified: Boolean = false,
    val friends_count: Int = 0,
    val followers_count: Int = 0,
    val following_count: Int = 0,
    val profile: FriendProfileDto? = null
) {
    val resolvedAvatar: String? get() {
        val raw = avatar_url ?: avatar ?: profile?.avatar_url
        return com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(raw)
    }
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
        val raw = when (element) {
            is JsonPrimitive -> if (element.isString) element.content else null
            is JsonObject -> element["url"]?.jsonPrimitive?.contentOrNull
                ?: element["original_url"]?.jsonPrimitive?.contentOrNull
                ?: element["preview_url"]?.jsonPrimitive?.contentOrNull
            else -> null
        }
        com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(raw)
    }
    val type: String get() = if (resolvedMediaUrls.isNotEmpty()) "Media" else "Post"
    val likes_count: Int get() = resolvedReactionsCount
}

@Serializable
data class StoryDto(
    val id: Long,
    val user: UserDto? = null,
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
        if (!media_url.isNullOrBlank()) return com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(media_url) ?: media_url
        val firstMedia = media.firstOrNull()
        val raw = when (firstMedia) {
            is JsonPrimitive -> if (firstMedia.isString) firstMedia.content else ""
            is JsonObject -> firstMedia["url"]?.jsonPrimitive?.contentOrNull
                ?: firstMedia["original_url"]?.jsonPrimitive?.contentOrNull
                ?: firstMedia["preview_url"]?.jsonPrimitive?.contentOrNull
                ?: ""
            else -> ""
        }
        return com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(raw) ?: raw
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
    val resolvedVideoUrl: String get() = com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(video_url) ?: (video_url ?: "")
    val resolvedThumbnailUrl: String? get() = com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(thumbnail_url ?: cover_url)
    val resolvedLiked: Boolean get() = is_liked || has_liked
    val resolvedSaved: Boolean get() = is_saved || has_saved
}

@Serializable
data class ConversationDto(
    val id: Long,
    val type: String = "direct",
    val creator_id: Long? = null,
    val is_saved: Boolean = false,
    val title: String? = null,
    val avatar_url: String? = null,
    val description: String? = null,
    val settings: JsonElement? = null,
    val other_user: UserDto? = null,
    val last_message: MessageDto? = null,
    val last_message_at: String? = null,
    val unread_count: Int = 0,
    val is_pinned: Boolean = false,
    val is_archived: Boolean = false,
    val is_muted: Boolean = false,
    val muted_until: String? = null,
    val is_request: Boolean = false,
    val request_status: String = "accepted",
    val is_online: Boolean = false,
    val draft_message: String? = null,
    val my_role: String = "member",
    val participants_count: Int = 0,
    val members: List<UserDto> = emptyList(),
    val created_at: String? = null
)

@Serializable
data class MessageDto(
    val id: Long = 0L,
    val conversation_id: Long = 0L,
    val sender_id: Long? = null,
    val sender_name: String? = null,
    val sender: UserDto? = null,
    val body: String? = null,
    val type: String = "text",
    val client_message_id: String? = null,
    val delivery_status: String = "sent",
    val is_mine: Boolean = false,
    val is_edited: Boolean = false,
    val is_pinned: Boolean = false,
    val is_saved: Boolean = false,
    val is_deleted: Boolean = false,
    val is_forwarded: Boolean = false,
    val version: Int = 1,
    val reply_to_message_id: Long? = null,
    val reply_preview: MessagePreviewDto? = null,
    val reply_to: MessagePreviewDto? = null,
    val reactions: MessageReactionsSummaryDto? = null,
    val metadata: JsonElement? = null,
    val media: List<MediaUploadResponse> = emptyList(),
    val attachments: List<MediaUploadResponse> = emptyList(),
    val sent_at: String? = null,
    val delivered_at: String? = null,
    val read_at: String? = null,
    val created_at: String? = ""
)

@Serializable
data class MessagePreviewDto(
    val id: Long = 0L,
    val sender_name: String? = null,
    val body: String? = null,
    val type: String = "text"
)

@Serializable
data class MessageReactionsSummaryDto(
    val counts: JsonElement? = null,
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
    val slug: String = "",
    val cover_image: String? = null,
    val cover_image_url: String? = null,
    val avatar_url: String? = null,
    val description: String? = null,
    val category: String? = null,
    val privacy: String? = "public",
    val members_count: Int = 0,
    val is_member: Boolean = false,
    val is_admin: Boolean = false
) {
    val resolvedCoverImage: String? get() = cover_image_url ?: cover_image
    val resolvedAvatar: String? get() = avatar_url
}

@Serializable
data class GroupCategoryDto(
    val id: Long? = null,
    val name: String,
    val slug: String,
    val icon: String? = "folder-outline",
    val description: String? = null,
    val groups_count: Int = 0
)

@Serializable
data class GroupInvitationDto(
    val id: Long,
    val group_id: Long,
    val group_name: String? = null,
    val group_avatar: String? = null,
    val group_members_count: Int = 0,
    val inviter_name: String? = null,
    val role: String? = null,
    val created_at: String? = null
)

@Serializable
data class GroupFeedAuthorDto(
    val id: Long? = null,
    val name: String? = null,
    val avatar: String? = null
)

@Serializable
data class GroupFeedGroupSummaryDto(
    val id: Long? = null,
    val name: String? = null,
    val avatar: String? = null
)

@Serializable
data class GroupFeedItemDto(
    val id: Long,
    val content: String? = null,
    val type: String? = null,
    val created_at: String? = null,
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val author: GroupFeedAuthorDto? = null,
    val group: GroupFeedGroupSummaryDto? = null
)

@Serializable
data class GroupsHomeResponseDto(
    val featured_group: GroupDto? = null,
    val my_groups: List<GroupDto> = emptyList(),
    val groups_you_manage: List<GroupDto> = emptyList(),
    val suggested_groups: List<GroupDto> = emptyList(),
    val discover_communities: List<GroupDto> = emptyList(),
    val trending_groups: List<GroupDto> = emptyList(),
    val recently_active_groups: List<GroupDto> = emptyList(),
    val groups_you_may_like: List<GroupDto> = emptyList(),
    val categories: List<GroupCategoryDto> = emptyList(),
    val pending_invitations: List<GroupInvitationDto> = emptyList(),
    val activity_feed: List<GroupFeedItemDto> = emptyList()
)

@Serializable
data class GroupAnnouncementDto(
    val id: Long = 0L,
    val group_id: Long = 0L,
    val title: String = "",
    val content: String = "",
    val cta_text: String? = null,
    val cta_url: String? = null,
    val is_pinned: Boolean = false,
    val created_at: String? = null,
    val author_name: String? = null
) {
    val body: String get() = content
}

@Serializable
data class GroupMediaItemDto(
    val id: String,
    val url: String,
    val thumbnail: String? = null,
    val type: String = "image",
    val title: String? = null,
    val created_at: String? = null
)

@Serializable
data class ToggleFollowGroupResponseDto(
    val group_id: Long,
    val is_following: Boolean
)

@Serializable
data class GroupHeaderActionsDto(
    val can_join: Boolean = false,
    val can_request: Boolean = false,
    val can_cancel_request: Boolean = false,
    val can_accept_invitation: Boolean = false,
    val can_invite: Boolean = false,
    val can_share: Boolean = true,
    val can_follow: Boolean = true,
    val is_following: Boolean = false,
    val can_leave: Boolean = false,
    val can_report: Boolean = true,
    val can_manage: Boolean = false
)

@Serializable
data class GroupDiscussionDto(
    val id: Long = 0L,
    val title: String? = null,
    val content: String? = null,
    val type: String = "discussion",
    val views_count: Int = 0,
    val replies_count: Int = 0,
    val created_at: String? = null,
    val author_name: String? = null,
    val comments_count: Int = 0
) {
    val resolvedTitle: String get() = title ?: "Untitled Discussion"
    val resolvedAuthorName: String get() = author_name ?: "Member"
    val resolvedCommentsCount: Int get() = if (comments_count > 0) comments_count else replies_count
}

@Serializable
data class GroupFileDto(
    val id: Long = 0L,
    val name: String = "",
    val file_url: String? = null,
    val file_type: String? = null,
    val file_size: Long = 0,
    val downloads_count: Int = 0,
    val created_at: String? = null
) {
    val size: String get() = if (file_size > 1024) "${file_size / 1024} KB" else "$file_size B"
}

@Serializable
data class GroupDetailHeaderDto(
    val id: Long = 0L,
    val name: String = "",
    val slug: String = "",
    val username: String? = null,
    val description: String? = null,
    val category: String? = null,
    val industry: String? = null,
    val location: String? = null,
    val website: String? = null,
    val cover_image_url: String? = null,
    val avatar_url: String? = null,
    val privacy: String = "public",
    val is_verified: Boolean = false,
    val verification_status: String = "unverified",
    val members_count: Int = 0,
    val active_members_count: Int = 0,
    val online_members_count: Int = 0,
    val activity_status: String = "Active",
    val is_owner: Boolean = false,
    val is_admin: Boolean = false,
    val is_moderator: Boolean = false,
    val is_member: Boolean = false,
    val is_pending: Boolean = false,
    val is_following: Boolean = false,
    val has_pending_invitation: Boolean = false,
    val actions: GroupHeaderActionsDto? = null,
    val created_at: String? = null,
    val dashboard_url: String? = null
) {
    val cover_image: String? get() = cover_image_url
}

@Serializable
data class GroupMetricsDto(
    val total_members: Long = 0,
    val total_discussions: Long = 0,
    val total_events: Long = 0
)

@Serializable
data class GroupProfileOverviewDto(
    val pinned_announcement: GroupAnnouncementDto? = null,
    val recent_discussions: List<GroupDiscussionDto> = emptyList(),
    val recent_posts: List<GroupFeedItemDto> = emptyList(),
    val upcoming_events: List<EventDto> = emptyList(),
    val recent_media: List<GroupMediaItemDto> = emptyList(),
    val key_members: List<GroupMemberDto> = emptyList(),
    val metrics: GroupMetricsDto? = null
)

@Serializable
data class GroupDiscussionSummaryDto(
    val discussions: List<GroupDiscussionDto> = emptyList(),
    val posts: List<GroupFeedItemDto> = emptyList()
)

@Serializable
data class GroupAboutTabDto(
    val description: String? = null,
    val category: String? = null,
    val industry: String? = null,
    val location: String? = null,
    val website: String? = null,
    val contact_email: String? = null,
    val contact_phone: String? = null,
    val privacy: String = "public",
    val membership_approval_mode: String = "anyone",
    val rules: List<CreateGroupRuleItem> = emptyList(),
    val created_at: String? = null,
    val health_score: Double = 100.0
) {
    val creation_date: String? get() = created_at
}

@Serializable
data class GroupProfileTabsDto(
    val overview: GroupProfileOverviewDto? = null,
    val discussion: GroupDiscussionSummaryDto? = null,
    val announcements: List<GroupAnnouncementDto> = emptyList(),
    val members: List<GroupMemberDto> = emptyList(),
    val events: List<EventDto> = emptyList(),
    val media: List<GroupMediaItemDto> = emptyList(),
    val files: List<GroupFileDto> = emptyList(),
    val about: GroupAboutTabDto? = null,
    val dashboard_access: Boolean = false
)

@Serializable
data class GroupProfileResponseDto(
    val group: GroupDetailHeaderDto,
    val tabs: GroupProfileTabsDto
)

@Serializable
data class DashboardOverviewDto(
    val total_members: Long = 0,
    val new_members: Long = 0,
    val active_members: Long = 0,
    val online_members: Long = 0,
    val pending_join_requests: Long = 0,
    val total_posts: Long = 0,
    val total_comments: Long = 0,
    val total_reactions: Long = 0,
    val total_shares: Long = 0,
    val total_events: Long = 0,
    val pending_posts: Long = 0,
    val reported_content: Long = 0,
    val engagement_rate: Double = 0.0,
    val member_growth: Double = 0.0
)

@Serializable
data class ChartDataPointDto(
    val date: String,
    val count: Long = 0
)

@Serializable
data class EngagementTrendPointDto(
    val date: String,
    val posts: Long = 0,
    val comments: Long = 0,
    val total: Long = 0
)

@Serializable
data class ActiveMembersStatsDto(
    val active_count: Long = 0,
    val inactive_count: Long = 0,
    val active_percentage: Double = 0.0
)

@Serializable
data class TopPerformingPostAuthorDto(
    val id: Long? = null,
    val name: String? = null,
    val avatar_url: String? = null
)

@Serializable
data class TopPerformingPostDto(
    val id: Long,
    val content: String,
    val author: TopPerformingPostAuthorDto? = null,
    val likes_count: Long = 0,
    val comments_count: Long = 0,
    val shares_count: Long = 0,
    val total_engagement: Long = 0,
    val created_at: String? = null
)

@Serializable
data class ActiveMemberItemDto(
    val user_id: Long,
    val name: String,
    val username: String? = null,
    val avatar_url: String? = null,
    val role: String = "member",
    val posts_count: Long = 0,
    val joined_at: String? = null
)

@Serializable
data class MemberRetentionDto(
    val retention_rate: Double = 0.0,
    val retained_members: Long = 0,
    val inactive_members: Long = 0
)

@Serializable
data class DashboardChartsDto(
    val daily_member_growth: List<ChartDataPointDto> = emptyList(),
    val post_activity: List<ChartDataPointDto> = emptyList(),
    val comment_activity: List<ChartDataPointDto> = emptyList(),
    val engagement_trends: List<EngagementTrendPointDto> = emptyList(),
    val active_members_stats: ActiveMembersStatsDto? = null,
    val top_performing_posts: List<TopPerformingPostDto> = emptyList(),
    val most_active_members: List<ActiveMemberItemDto> = emptyList(),
    val member_retention: MemberRetentionDto? = null
)

@Serializable
data class DashboardQuickActionDto(
    val id: String,
    val title: String,
    val description: String,
    val icon: String,
    val route: String,
    val badge: String? = null
)

@Serializable
data class DashboardFilterDto(
    val period: String,
    val label: String,
    val days: Int = 30,
    val start_date: String? = null,
    val end_date: String? = null
)

@Serializable
data class GroupDashboardResponseDto(
    val overview: DashboardOverviewDto,
    val charts: DashboardChartsDto,
    val quick_actions: List<DashboardQuickActionDto> = emptyList(),
    val filter: DashboardFilterDto? = null
)

@Serializable
data class PermissionItemDto(
    val key: String,
    val label: String,
    val description: String
)

@Serializable
data class PermissionCategoryDto(
    val category: String,
    val permissions: List<PermissionItemDto> = emptyList()
)

@Serializable
data class GroupRoleDto(
    val id: Long? = null,
    val group_id: Long? = null,
    val name: String,
    val display_name: String,
    val description: String? = null,
    val color: String = "#6366f1",
    val permissions: List<String> = emptyList(),
    val is_system: Boolean = false,
    val sort_order: Int = 0,
    val members_count: Long = 0,
    val created_at: String? = null
)

@Serializable
data class GroupRolesCatalogResponseDto(
    val default_roles: List<GroupRoleDto> = emptyList(),
    val custom_roles: List<GroupRoleDto> = emptyList(),
    val available_permissions: List<PermissionCategoryDto> = emptyList(),
    val can_manage_roles: Boolean = false
)

@Serializable
data class CreateCustomRoleRequest(
    val name: String,
    val display_name: String? = null,
    val description: String? = null,
    val color: String = "#6366f1",
    val permissions: List<String> = emptyList()
)

@Serializable
data class UpdateCustomRoleRequest(
    val display_name: String? = null,
    val description: String? = null,
    val color: String? = null,
    val permissions: List<String>? = null
)

@Serializable
data class AssignRoleRequest(
    val role: String,
    val user_ids: List<Long>? = null,
    val user_id: Long? = null,
    val permissions: List<String>? = null
)

@Serializable
data class RoleHistoryItemDto(
    val id: Long = 0L,
    val action: String = "",
    val reason: String? = null,
    val moderator: TopPerformingPostAuthorDto? = null,
    val target_user: TopPerformingPostAuthorDto? = null,
    val created_at: String? = null
) {
    val action_type: String get() = action
    val moderator_name: String get() = moderator?.name ?: "Moderator"
}

@Serializable
data class EventDto(
    val id: Long = 0L,
    val title: String = "",
    val banner: String? = null,
    val location: String = "",
    val start_time: String = "",
    val interested_count: Int = 0,
    val going_count: Int = 0
) {
    val starts_at: String? get() = start_time.ifBlank { null }
    val attendees_count: Int get() = going_count
}

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
    val username: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val birth_date: String? = null,
    val gender: String? = null,
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
data class ProfileAnalyticsDto(
    val total_views: Long = 0,
    val views_today: Long = 0,
    val views_this_week: Long = 0,
    val unique_viewers_count: Long = 0,
    val timeframe: String = "30d"
)

@Serializable
data class ProfileAboutDto(
    val user_id: Long = 0,
    val username: String = "",
    val name: String = "",
    val bio: String? = null,
    val headline: String? = null,
    val intro: String? = null,
    val about: String? = null,
    val is_profile_locked: Boolean = false
)

@Serializable
data class MediaUploadResponse(
    val id: Long? = null,
    val url: String? = null,
    val path: String? = null,
    val preview_url: String? = null,
    val original_url: String? = null,
    val thumbnail_url: String? = null,
    val download_url: String? = null,
    val view_url: String? = null,
    val name: String? = null,
    val filename: String? = null,
    val mime_type: String? = null,
    val size: Long? = null,
    val width: Int? = null,
    val height: Int? = null,
    val duration: Double? = null,
    val metadata: JsonElement? = null
) {
    val resolvedUrl: String get() {
        val raw = preview_url ?: original_url ?: url ?: ""
        return com.connectly.android.core.network.di.NetworkModule.resolveMediaUrl(raw) ?: raw
    }
    val resolvedFileName: String get() = filename ?: name ?: "attachment"
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
data class SettingsOverviewDto(
    val account: SettingsAccountDto? = null,
    val privacy: SettingsPrivacyDto? = null,
    val security: SettingsSecurityDto? = null,
    val blocking: SettingsBlockingDto? = null
)

@Serializable
data class SettingsAccountDto(
    val id: Long = 0L,
    val name: String = "",
    val username: String = "",
    val email: String? = null,
    val phone: String? = null,
    val gender: String? = null,
    val birth_date: String? = null,
    val avatar: String? = null,
    val bio: String? = null,
    val created_at: String? = null
)

@Serializable
data class SettingsPrivacyDto(
    val who_can_see_posts: String = "public",
    val who_can_send_friend_requests: String = "everyone",
    val who_can_follow: String = "everyone",
    val who_can_message: String = "everyone",
    val who_can_see_friends: String = "public",
    val story_visibility: String = "public",
    val is_profile_locked: Boolean = false,
    val has_avatar_guard: Boolean = false,
    val find_by_email: String = "everyone",
    val find_by_phone: String = "everyone",
    val profile_visibility: String = "public",
    val search_engine_indexing: Boolean = true,
    val show_online_status: Boolean = true,
    val show_last_seen: String = "everyone",
    val read_receipts_enabled: Boolean = true,
    val two_factor_enabled: Boolean = false,
    val dark_mode: Boolean = false,
    val language: String = "en"
)

@Serializable
data class UpdateSettingsPrivacyRequest(
    val who_can_see_posts: String? = null,
    val who_can_send_friend_requests: String? = null,
    val who_can_follow: String? = null,
    val who_can_message: String? = null,
    val who_can_see_friends: String? = null,
    val story_visibility: String? = null,
    val is_profile_locked: Boolean? = null,
    val has_avatar_guard: Boolean? = null,
    val find_by_email: String? = null,
    val find_by_phone: String? = null,
    val profile_visibility: String? = null,
    val search_engine_indexing: Boolean? = null,
    val show_online_status: Boolean? = null,
    val show_last_seen: String? = null,
    val read_receipts_enabled: Boolean? = null,
    val two_factor_enabled: Boolean? = null
)

@Serializable
data class Toggle2faRequest(
    val enabled: Boolean
)

@Serializable
data class Toggle2faDto(
    val two_factor_enabled: Boolean = false
)

@Serializable
data class UserSessionDto(
    val id: Long,
    val user_id: Long = 0L,
    val device_name: String? = null,
    val browser: String? = null,
    val os: String? = null,
    val ip_address: String? = null,
    val location: String? = null,
    val is_current: Boolean = false,
    val last_active_at: String? = null
)

@Serializable
data class SettingsSecurityDto(
    val two_factor_enabled: Boolean = false,
    val two_factor_type: String = "totp",
    val email_verified: Boolean = false,
    val phone_verified: Boolean = false
)

@Serializable
data class SettingsBlockingDto(
    val blocked_count: Int = 0
)

@Serializable
data class BlockedUsersResponseDto(
    val blocked_users: List<RestrictedUserDto> = emptyList(),
    val restricted_users: List<RestrictedUserDto> = emptyList(),
    val muted_users: List<RestrictedUserDto> = emptyList(),
    val total: Int = 0
)

@Serializable
data class RestrictedUserDto(
    val id: Long,
    val name: String = "",
    val username: String = "",
    val avatar: String? = null,
    val is_verified: Boolean = false
)

@Serializable
data class UpdateAccountRequest(
    val name: String? = null,
    val username: String? = null,
    val phone: String? = null,
    val gender: String? = null,
    val birth_date: String? = null,
    val bio: String? = null
)

@Serializable
data class UpdatePasswordRequest(
    val current_password: String,
    val password: String,
    val password_confirmation: String
)

@Serializable
data class DeactivateAccountRequest(
    val password: String? = null,
    val reason: String? = null
)

@Serializable
data class DeleteAccountRequest(
    val password: String,
    val confirmation: String = "DELETE",
    val reason: String? = null
)

@Serializable
data class CreatePostPayload(
    val content: String? = null,
    val audience: String = "public",
    val type: String = "text",
    val media_ids: List<Long> = emptyList(),
    val tagged_user_ids: List<Long> = emptyList(),
    val feeling_activity: String? = null,
    val location: String? = null
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
    val caption: String? = null,
    val target_type: String? = "timeline",
    val target_id: Long? = null
)

@Serializable
data class EligibleGroupDto(
    val id: Long,
    val name: String,
    val slug: String? = null,
    val avatar_url: String? = null,
    val cover_url: String? = null,
    val privacy: String = "public",
    val members_count: Int = 0,
    val user_role: String = "member",
    val can_post: Boolean = true
)

@Serializable
data class EligiblePageDto(
    val id: Long,
    val name: String,
    val slug: String? = null,
    val avatar_url: String? = null,
    val category: String? = null,
    val user_role: String = "owner",
    val can_post: Boolean = true
)

@Serializable
data class ShareTimelineRequest(
    val caption: String? = null
)

@Serializable
data class ShareGroupsRequest(
    val caption: String? = null,
    val group_ids: List<Long>
)

@Serializable
data class SharePageRequest(
    val caption: String? = null,
    val page_id: Long? = null,
    val page_ids: List<Long>? = null
)

@Serializable
data class ShareResultDto(
    val shares_count: Int = 0,
    val shared_count: Int = 1,
    val group_ids: List<Long> = emptyList(),
    val page_ids: List<Long> = emptyList()
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
    val background_color: String? = null,
    val font_family: String? = null
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
    val description: String? = null,
    val avatar_url: String? = null,
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
    val receiver_ids: List<Long>? = null,
    val call_type: String
)

@Serializable
data class InviteToCallRequest(
    val friend_id: Long? = null,
    val friend_ids: List<Long>? = null
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
    val uuid: String? = null,
    val room_id: String? = null,
    val channel_name: String? = null,
    val conversation_id: Long? = null,
    val call_type: String = "audio",
    val status: String = "ringing",
    val outcome: String? = null,
    val is_mine: Boolean = false,
    val caller: UserDto? = null,
    val receiver: UserDto? = null,
    val participants: List<CallParticipantDto> = emptyList(),
    val metadata: JsonElement? = null,
    val started_at: String? = null,
    val ended_at: String? = null,
    val created_at: String? = null,
    val duration_seconds: Int = 0
)

@Serializable
data class CallParticipantDto(
    val id: Long = 0L,
    val call_id: Long? = null,
    val user: UserDto = UserDto(),
    val role: String = "participant",
    val status: String = "ringing",
    val joined_at: String? = null,
    val left_at: String? = null,
    val duration_seconds: Int = 0,
    val is_muted: Boolean = false,
    val is_camera_off: Boolean = false,
    val is_screen_sharing: Boolean = false
)

@Serializable
data class CreateGroupRuleItem(
    val title: String,
    val description: String = "",
    val enforcement_level: String = "warning"
)

@Serializable
data class CreateGroupQuestionItem(
    val question: String,
    val type: String = "text"
)

@Serializable
data class CreateGroupRequest(
    val name: String,
    val username: String? = null,
    val description: String? = null,
    val category: String = "General Community",
    val industry: String? = null,
    val website: String? = null,
    val location: String? = null,
    val contact_email: String? = null,
    val contact_phone: String? = null,
    val privacy: String = "public",
    val membership_approval_mode: String = "anyone",
    val allow_member_invitations: Boolean = true,
    val allow_member_recommendations: Boolean = true,
    val enable_membership_screening: Boolean = false,
    val max_members_limit: Int? = null,
    val auto_approve_trusted: Boolean = false,
    val who_can_post: String = "anyone_in_group",
    val who_can_comment: String = "anyone_in_group",
    val who_can_upload_media: String = "anyone_in_group",
    val who_can_create_polls: String = "anyone_in_group",
    val who_can_create_events: String = "anyone_in_group",
    val who_can_create_announcements: String = "admins_moderators_only",
    val post_approval_mode: String = "auto",
    val enable_comment_moderation: Boolean = false,
    val allow_anonymous_posts: Boolean = false,
    val allow_scheduled_posts: Boolean = true,
    val cover_image_url: String? = null,
    val avatar_url: String? = null,
    val rules: List<CreateGroupRuleItem> = emptyList(),
    val questions: List<CreateGroupQuestionItem> = emptyList()
)

@Serializable
data class PageSocialLinkDto(
    val platform: String = "",
    val url: String = "",
    val is_visible: Boolean = true
)

object PageSocialLinksSerializer : KSerializer<List<PageSocialLinkDto>?> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("PageSocialLinksSerializer", PrimitiveKind.STRING)

    override fun deserialize(decoder: Decoder): List<PageSocialLinkDto> {
        val jsonDecoder = decoder as? JsonDecoder ?: return emptyList()
        return when (val element = jsonDecoder.decodeJsonElement()) {
            is JsonNull -> emptyList()
            is JsonArray -> {
                element.mapNotNull { item ->
                    when (item) {
                        is JsonPrimitive -> {
                            val str = item.contentOrNull
                            if (!str.isNullOrBlank()) PageSocialLinkDto(platform = "website", url = str) else null
                        }
                        is JsonObject -> {
                            val platform = item["platform"]?.jsonPrimitive?.contentOrNull
                                ?: item["name"]?.jsonPrimitive?.contentOrNull
                                ?: "link"
                            val url = item["url"]?.jsonPrimitive?.contentOrNull
                                ?: item["link"]?.jsonPrimitive?.contentOrNull
                                ?: ""
                            val isVisible = item["is_visible"]?.jsonPrimitive?.booleanOrNull ?: true
                            if (url.isNotBlank()) PageSocialLinkDto(platform, url, isVisible) else null
                        }
                        else -> null
                    }
                }
            }
            is JsonObject -> {
                element.mapNotNull { (key, value) ->
                    val url = when (value) {
                        is JsonPrimitive -> value.contentOrNull
                        is JsonObject -> value["url"]?.jsonPrimitive?.contentOrNull
                        else -> null
                    }
                    if (!url.isNullOrBlank()) PageSocialLinkDto(platform = key, url = url) else null
                }
            }
            is JsonPrimitive -> {
                val str = element.contentOrNull
                if (!str.isNullOrBlank()) listOf(PageSocialLinkDto(platform = "link", url = str)) else emptyList()
            }
        }
    }

    override fun serialize(encoder: Encoder, value: List<PageSocialLinkDto>?) {
        val jsonEncoder = encoder as? JsonEncoder
        val list = value ?: emptyList()
        if (jsonEncoder != null) {
            val array = buildJsonArray {
                list.forEach { item ->
                    add(buildJsonObject {
                        put("platform", item.platform)
                        put("url", item.url)
                        put("is_visible", item.is_visible)
                    })
                }
            }
            jsonEncoder.encodeJsonElement(array)
        } else {
            ListSerializer(PageSocialLinkDto.serializer()).serialize(encoder, list)
        }
    }
}

@Serializable
data class BusinessDayHoursDto(
    val mode: String = "open",
    val open: String? = null,
    val close: String? = null
)

@Serializable
data class BusinessHoursDto(
    val monday: BusinessDayHoursDto? = null,
    val tuesday: BusinessDayHoursDto? = null,
    val wednesday: BusinessDayHoursDto? = null,
    val thursday: BusinessDayHoursDto? = null,
    val friday: BusinessDayHoursDto? = null,
    val saturday: BusinessDayHoursDto? = null,
    val sunday: BusinessDayHoursDto? = null,
    val raw_summary: String? = null
)

object BusinessHoursSerializer : KSerializer<BusinessHoursDto?> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("BusinessHoursSerializer", PrimitiveKind.STRING)

    override fun deserialize(decoder: Decoder): BusinessHoursDto? {
        val jsonDecoder = decoder as? JsonDecoder ?: return null
        return when (val element = jsonDecoder.decodeJsonElement()) {
            is JsonNull -> null
            is JsonPrimitive -> BusinessHoursDto(raw_summary = element.contentOrNull)
            is JsonObject -> {
                fun parseDay(name: String): BusinessDayHoursDto? {
                    val dayObj = element[name] as? JsonObject ?: return null
                    return BusinessDayHoursDto(
                        mode = dayObj["mode"]?.jsonPrimitive?.contentOrNull ?: "open",
                        open = dayObj["open"]?.jsonPrimitive?.contentOrNull,
                        close = dayObj["close"]?.jsonPrimitive?.contentOrNull
                    )
                }
                BusinessHoursDto(
                    monday = parseDay("monday"),
                    tuesday = parseDay("tuesday"),
                    wednesday = parseDay("wednesday"),
                    thursday = parseDay("thursday"),
                    friday = parseDay("friday"),
                    saturday = parseDay("saturday"),
                    sunday = parseDay("sunday"),
                    raw_summary = element["summary"]?.jsonPrimitive?.contentOrNull
                )
            }
            else -> null
        }
    }

    @OptIn(kotlinx.serialization.ExperimentalSerializationApi::class)
    override fun serialize(encoder: Encoder, value: BusinessHoursDto?) {
        val jsonEncoder = encoder as? JsonEncoder
        if (value == null) {
            jsonEncoder?.encodeJsonElement(JsonNull) ?: encoder.encodeNull()
            return
        }
        val obj = buildJsonObject {
            fun addDay(name: String, day: BusinessDayHoursDto?) {
                if (day != null) {
                    put(name, buildJsonObject {
                        put("mode", day.mode)
                        day.open?.let { put("open", it) }
                        day.close?.let { put("close", it) }
                    })
                }
            }
            addDay("monday", value.monday)
            addDay("tuesday", value.tuesday)
            addDay("wednesday", value.wednesday)
            addDay("thursday", value.thursday)
            addDay("friday", value.friday)
            addDay("saturday", value.saturday)
            addDay("sunday", value.sunday)
            value.raw_summary?.let { put("summary", it) }
        }
        jsonEncoder?.encodeJsonElement(obj)
    }
}

@Serializable
data class PageFollowResponseDto(
    val page_id: Long? = null,
    val is_following: Boolean = false,
    val followers_count: Int = 0
)

@Serializable
data class GroupJoinResponseDto(
    val group_id: Long? = null,
    val status: String = "member",
    val members_count: Int = 0
)

@Serializable
data class GroupActionResponseDto(
    val success: Boolean = true,
    val message: String? = null
)

@Serializable
data class PageDto(
    val id: Long = 0L,
    val name: String = "",
    val username: String? = null,
    val slug: String? = null,
    val category: String = "Business",
    val business_category: String? = null,
    val bio: String? = null,
    val description: String? = null,
    val avatar: String? = null,
    val avatar_url: String? = null,
    val cover: String? = null,
    val cover_url: String? = null,
    val cover_image_url: String? = null,
    val website: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val whatsapp: String? = null,
    val address: String? = null,
    val country: String? = null,
    val city: String? = null,
    val opening_hours: String? = null,
    @Serializable(with = BusinessHoursSerializer::class)
    val business_hours: BusinessHoursDto? = null,
    @Serializable(with = PageSocialLinksSerializer::class)
    val social_links: List<PageSocialLinkDto>? = emptyList(),
    val services_summary: String? = null,
    val followers_count: Int = 0,
    val following_count: Int = 0,
    val total_posts: Int = 0,
    val total_videos: Int = 0,
    val total_reels: Int = 0,
    val rating: Double = 4.8,
    val is_following: Boolean = false,
    val is_liked: Boolean = false,
    val is_verified: Boolean = false,
    val publishing_status: String = "published",
    val is_owner: Boolean = false,
    val is_admin: Boolean = false,
    val user_role: String? = null,
    val cta_text: String? = null,
    val cta_type: String? = null,
    val cta_url: String? = null
) {
    val resolvedAvatar: String? get() = avatar_url ?: avatar
    val resolvedCover: String? get() = cover_url ?: cover ?: cover_image_url
    val resolvedUsername: String get() = username ?: slug ?: "page_$id"
    val resolvedSocialLinks: List<PageSocialLinkDto> get() = social_links ?: emptyList()
}

@Serializable
data class PageUsernameCheckDto(
    val available: Boolean = false,
    val normalized: String? = null,
    val message: String? = null,
    val suggestions: List<String> = emptyList()
)

@Serializable
data class CreatePageRequest(
    val name: String,
    val username: String? = null,
    val category: String = "Business",
    val business_category: String? = null,
    val bio: String? = null,
    val short_description: String? = null,
    val description: String? = null,
    val website: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val whatsapp: String? = null,
    val address: String? = null,
    val country: String? = null,
    val city: String? = null,
    val state: String? = null,
    val zip_code: String? = null,
    val opening_hours: String? = null,
    val cta_type: String? = null,
    val cta_url: String? = null,
    val avatar_url: String? = null,
    val cover_image_url: String? = null,
    val visibility: String = "public",
    val publishing_status: String = "published"
)

@Serializable
data class UpdatePageRequest(
    val name: String? = null,
    val username: String? = null,
    val category: String? = null,
    val business_category: String? = null,
    val bio: String? = null,
    val description: String? = null,
    val website: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val whatsapp: String? = null,
    val address: String? = null,
    val country: String? = null,
    val city: String? = null,
    val opening_hours: String? = null,
    val cta_text: String? = null,
    val cta_url: String? = null,
    val publishing_status: String? = null
)

@Serializable
data class PageRoleDto(
    val id: Long = 0L,
    val user_id: Long = 0L,
    val user_name: String = "",
    val user_username: String = "",
    val user_avatar: String? = null,
    val role: String = "editor",
    val permissions: List<String> = emptyList(),
    val assigned_at: String? = null
)

@Serializable
data class AssignPageRoleRequest(
    val user_id: Long,
    val role: String = "editor",
    val permissions: List<String> = emptyList()
)

object StringOrDoubleSerializer : KSerializer<Double> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("StringOrDoubleSerializer", PrimitiveKind.DOUBLE)

    override fun deserialize(decoder: Decoder): Double {
        if (decoder is JsonDecoder) {
            val element = decoder.decodeJsonElement()
            if (element is JsonPrimitive) {
                element.doubleOrNull?.let { return it }
                val content = element.content.replace("%", "").trim()
                return content.toDoubleOrNull() ?: 0.0
            }
        }
        return try {
            decoder.decodeDouble()
        } catch (_: Exception) {
            0.0
        }
    }

    override fun serialize(encoder: Encoder, value: Double) {
        encoder.encodeDouble(value)
    }
}

@Serializable
data class PageAnalyticsSummaryDto(
    val total_followers: Int = 0,
    val new_followers: Int = 0,
    val unfollowers: Int = 0,
    val net_follower_growth: Int = 0,
    val total_posts: Int = 0,
    val posts_in_period: Int = 0,
    val scheduled_posts: Int = 0,
    val draft_posts: Int = 0,
    val total_likes: Int = 0,
    val total_comments: Int = 0,
    val total_shares: Int = 0,
    val total_engagements: Int = 0,
    @Serializable(with = StringOrDoubleSerializer::class)
    val engagement_rate: Double = 0.0,
    val impressions: Int = 0,
    val reach: Int = 0,
    val clicks: Int = 0,
    val link_clicks: Int = 0,
    val reactions: Int = 0,
    val comments: Int = 0,
    val shares: Int = 0,
    val video_views: Int = 0,
    val reel_views: Int = 0,
    val story_views: Int = 0
)

@Serializable
data class PageTopContentDto(
    val id: Long = 0L,
    val content: String? = null,
    val type: String = "text",
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val shares_count: Int = 0,
    val impressions: Int = 0,
    val reach: Int = 0,
    val clicks: Int = 0,
    val created_at: String? = null
)

@Serializable
data class PageAuditActivityActorDto(
    val id: Long? = null,
    val name: String? = null,
    val username: String? = null,
    val avatar_url: String? = null
)

@Serializable
data class PageAuditActivityDto(
    val id: Long = 0L,
    val action: String = "",
    val category: String = "activity",
    val actor: PageAuditActivityActorDto? = null,
    val description: String = "",
    val time_ago: String = "Recently",
    val timestamp: String = "",
    val icon: String = "activity"
)

@Serializable
data class PageAnalyticsDetailDto(
    val page_id: Long = 0L,
    val slug: String? = null,
    val period_days: Int = 7,
    val summary: PageAnalyticsSummaryDto? = null,
    val total_followers: Int = 0,
    val new_followers: Int = 0,
    val unfollowers: Int = 0,
    val total_reach: Int = 0,
    val total_impressions: Int = 0,
    val total_page_views: Int = 0,
    val profile_visits: Int = 0,
    val post_engagement: Int = 0,
    val total_reactions: Int = 0,
    val total_comments: Int = 0,
    val total_shares: Int = 0,
    val video_views: Int = 0,
    val reels_views: Int = 0,
    val story_views: Int = 0,
    val live_views: Int = 0,
    val link_clicks: Int = 0,
    @Serializable(with = StringOrDoubleSerializer::class)
    val engagement_rate: Double = 0.0,
    val growth_rate: Double = 0.0,
    val top_content: List<PageTopContentDto> = emptyList(),
    val recent_activity: List<PageAuditActivityDto> = emptyList()
) {
    val resolvedReach: Int get() = summary?.reach ?: if (total_reach > 0) total_reach else summary?.impressions ?: 0
    val resolvedImpressions: Int get() = summary?.impressions ?: total_impressions
    val resolvedFollowers: Int get() = summary?.total_followers ?: total_followers
    val resolvedNewFollowers: Int get() = summary?.new_followers ?: new_followers
    val resolvedEngagementRate: Double get() = summary?.engagement_rate ?: engagement_rate
    val resolvedReactions: Int get() = summary?.reactions ?: summary?.total_likes ?: total_reactions
    val resolvedPageViews: Int get() = summary?.impressions ?: total_page_views
}

@Serializable
data class PageReviewDto(
    val id: Long = 0L,
    val user_id: Long = 0L,
    val author_name: String = "",
    val author_avatar: String? = null,
    val rating: Int = 5,
    val review_text: String = "",
    val created_at: String? = null,
    val reply_text: String? = null,
    val helpful_count: Int = 0
)

@Serializable
data class CreatePageReviewRequest(
    val rating: Int,
    val review_text: String
)

@Serializable
data class PageProductDto(
    val id: Long = 0L,
    val name: String = "",
    val description: String = "",
    val price: Double = 0.0,
    val discount_price: Double? = null,
    val image_url: String? = null,
    val category: String = "General",
    val product_url: String? = null,
    val is_available: Boolean = true
)

@Serializable
data class PageServiceDto(
    val id: Long = 0L,
    val name: String = "",
    val description: String = "",
    val price_info: String = "Contact for pricing",
    val image_url: String? = null,
    val booking_url: String? = null
)

@Serializable
data class PageInboxMessageDto(
    val id: Long = 0L,
    val conversation_id: Long = 0L,
    val sender_id: Long = 0L,
    val sender_name: String = "",
    val sender_avatar: String? = null,
    val text: String = "",
    val timestamp: String = "",
    val is_read: Boolean = false,
    val assigned_team_member: String? = null
) {
    val last_message_preview: String get() = text
    val last_message_at: String get() = timestamp
}

@Serializable
data class SendPageMessageRequest(
    val conversation_id: Long,
    val text: String
)

@Serializable
data class PageSettingsDto(
    val is_public: Boolean = true,
    val page_visibility: String = "public",
    val allow_reviews: Boolean = true,
    val allow_messages: Boolean = true,
    val allow_tagging: Boolean = true,
    val comment_moderation: Boolean = false,
    val age_restriction: String = "everyone",
    val country_restrictions: List<String> = emptyList(),
    val blocked_words: List<String> = emptyList(),
    val verification_status: String = "unverified"
)

@Serializable
data class RequestPageVerificationRequest(
    val verification_type: String = "business",
    val legal_name: String? = null,
    val registration_number: String? = null,
    val tax_id: String? = null,
    val document_url: String? = null,
    val additional_info: String? = null
)

@Serializable
data class PageVerificationResponseDto(
    val id: Long = 0L,
    val page_id: Long = 0L,
    val status: String = "pending",
    val verification_type: String = "business",
    val legal_name: String? = null,
    val document_url: String? = null,
    val additional_info: String? = null
)

@Serializable
data class PageMonetizationStatusDto(
    val is_eligible: Boolean = false,
    val status: String = "In Review",
    val stars_enabled: Boolean = false,
    val in_stream_ads_enabled: Boolean = false,
    val subscriptions_enabled: Boolean = false,
    val brand_collabs_enabled: Boolean = false,
    val current_followers: Int = 0,
    val required_followers: Int = 10000,
    val current_watch_minutes: Long = 0L,
    val required_watch_minutes: Long = 600000L,
    val payout_status: String = "Connected",
    val payout_date: String = "Nov 15",
    val payout_account_name: String? = null,
    val payout_account_number: String? = null,
    val payout_method: String? = null,
    val bank_name: String? = null,
    val swift_code: String? = null,
    val estimated_earnings: Double = 0.0,
    val net_payout: Double = 0.0,
    val policy_status: String = "Good Standing",
    val in_stream_revenue: Double = 842.20,
    val brand_collabs_revenue: Double = 250.00,
    val subscriptions_revenue: Double = 118.30,
    val stars_revenue: Double = 30.00,
    val monetizable_views: Long = 284500L,
    val average_cpm: Double = 3.45,
    val average_rpm: Double = 1.82
)

@Serializable
data class UpdatePageMonetizationRequest(
    val in_stream_ads_enabled: Boolean? = null,
    val brand_collabs_enabled: Boolean? = null,
    val subscriptions_enabled: Boolean? = null,
    val stars_enabled: Boolean? = null,
    val is_eligible: Boolean? = null,
    val payout_account_name: String? = null,
    val payout_account_number: String? = null,
    val payout_method: String? = null,
    val bank_name: String? = null,
    val swift_code: String? = null
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
    val user: UserDto = UserDto(),
    val role: String = "member",
    val status: String = "active",
    val permissions: List<String> = emptyList(),
    val strikes_count: Int = 0,
    val is_muted: Boolean = false,
    val muted_until: String? = null,
    val is_restricted: Boolean = false,
    val restricted_until: String? = null,
    val is_banned: Boolean = false,
    val banned_at: String? = null,
    val ban_reason: String? = null,
    val posts_published: Long = 0,
    val comments_count: Long = 0,
    val last_activity_at: String? = null,
    val account_status: String = "active",
    val joined_at: String? = null
) {
    val name: String get() = user.name
    val username: String get() = user.username
    val avatar_url: String? get() = user.resolvedAvatar
    val posts_count: Long get() = posts_published
}

@Serializable
data class MemberActivitySummaryDto(
    val posts_count: Long = 0,
    val comments_count: Long = 0,
    val warnings_count: Long = 0
)

@Serializable
data class MemberActivityResponseDto(
    val member: GroupMemberDto,
    val activity_summary: MemberActivitySummaryDto = MemberActivitySummaryDto(),
    val recent_posts: List<TopPerformingPostDto> = emptyList(),
    val strikes_and_warnings: List<RoleHistoryItemDto> = emptyList(),
    val audit_history: List<RoleHistoryItemDto> = emptyList(),
    val recent_moderation_actions: List<RoleHistoryItemDto> = emptyList()
)

@Serializable
data class RestrictMemberRequest(
    val duration_days: Int = 7,
    val reason: String
)

@Serializable
data class BulkMemberActionRequest(
    val action: String,
    val user_ids: List<Long>,
    val role: String? = null,
    val duration_days: Int? = null,
    val reason: String? = null
)

@Serializable
data class BulkMemberActionResponseDto(
    val action: String,
    val processed_count: Int = 0,
    val successful_user_ids: List<Long> = emptyList()
)

@Serializable
data class UpdateGroupRoleRequest(
    val role: String // "admin", "moderator", "member"
)

@Serializable
data class GroupPollInput(
    val question: String = "",
    val options: List<String> = emptyList(),
    val duration_days: Int = 7
)

@Serializable
data class CreateGroupPostRequest(
    val content: String? = null,
    val body: String? = null,
    val type: String? = "text",
    val media_ids: List<Long> = emptyList(),
    val media_urls: List<String> = emptyList(),
    val poll_data: GroupPollInput? = null,
    val is_pinned: Boolean = false,
    val status: String = "published",
    val scheduled_at: String? = null
)

@Serializable
data class UpdateGroupPostRequest(
    val content: String? = null,
    val is_pinned: Boolean? = null
)

@Serializable
data class VoteGroupPostPollRequest(
    val option_id: Int
)

@Serializable
data class ReactGroupPostRequest(
    val type: String = "like"
)

@Serializable
data class ReportGroupPostRequest(
    val reason_category: String,
    val description: String? = null
)

@Serializable
data class TogglePinResponseDto(
    val is_pinned: Boolean
)

@Serializable
data class GroupReportDto(
    val id: Long = 0L,
    val group_id: Long = 0L,
    val reporter: UserDto? = null,
    val reportable_type: String = "",
    val reportable_id: Long = 0L,
    val reason_category: String = "",
    val description: String? = null,
    val status: String = "pending",
    val decision_note: String? = null,
    val created_at: String? = null
)

@Serializable
data class ActionGroupReportRequest(
    val status: String,
    val decision_note: String? = null,
    val remove_content: Boolean = false
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
data class MessengerSoundsDto(
    val messenger_sounds_enabled: Boolean = true,
    val incoming_call_sound_enabled: Boolean = true,
    val incoming_call_sound: String = "bondhoo_ring",
    val incoming_call_volume: Int = 80,
    val incoming_message_sound_enabled: Boolean = true,
    val incoming_message_sound: String = "bubble_pop",
    val incoming_message_volume: Int = 80,
    val outgoing_message_sound_enabled: Boolean = true,
    val outgoing_message_sound: String = "subtle_sent",
    val outgoing_message_volume: Int = 80
)

@Serializable
data class UpdateMessengerSoundsRequest(
    val messenger_sounds_enabled: Boolean? = null,
    val incoming_call_sound_enabled: Boolean? = null,
    val incoming_call_sound: String? = null,
    val incoming_call_volume: Int? = null,
    val incoming_message_sound_enabled: Boolean? = null,
    val incoming_message_sound: String? = null,
    val incoming_message_volume: Int? = null,
    val outgoing_message_sound_enabled: Boolean? = null,
    val outgoing_message_sound: String? = null,
    val outgoing_message_volume: Int? = null
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

@Serializable
data class MarketplaceCategoryDto(
    val id: Long,
    val name: String,
    val slug: String,
    val icon: String? = null,
    val display_order: Int? = null,
    val products_count: Int? = null,
    val subcategories: List<MarketplaceSubcategoryDto> = emptyList()
)

@Serializable
data class MarketplaceSubcategoryDto(
    val id: Long,
    val name: String,
    val slug: String,
    val icon: String? = null,
    val products_count: Int? = null
)

@Serializable
data class MarketplaceSellerDto(
    val id: Long? = null,
    val name: String? = null,
    val username: String? = null,
    val avatar_url: String? = null,
    val is_verified: Boolean = false,
    val rating: Double = 0.0,
    val reviews_count: Int = 0,
    val member_since: String? = null
)

@Serializable
data class MarketplaceProductDto(
    val id: Long,
    val title: String,
    val price: Double = 0.0,
    val stock: Int = 1,
    val original_price: Double? = null,
    val discount_percent: Int? = null,
    val currency: String = "BDT",
    val description: String? = null,
    val location: String? = null,
    val condition: String = "used",
    val brand: String? = null,
    val is_negotiable: Boolean = false,
    val delivery_type: String = "pickup",
    val delivery_charge: Double = 0.0,
    val primary_image_url: String? = null,
    val primary_image: String? = null,
    val status: String = "active",
    val seller_name: String? = null,
    val images: List<String> = emptyList(),
    val is_favorited: Boolean = false,
    val is_owner: Boolean = false,
    val views_count: Int = 0,
    val created_at: String? = null,
    val category_id: Long? = null,
    val subcategory_id: Long? = null,
    val category: MarketplaceCategoryDto? = null,
    val subcategory: MarketplaceSubcategoryDto? = null,
    val seller: MarketplaceSellerDto? = null
)

@Serializable
data class CreateMarketplaceProductRequest(
    val category_id: Long,
    val subcategory_id: Long? = null,
    val title: String,
    val description: String? = null,
    val price: Double,
    val original_price: Double? = null,
    val currency: String = "BDT",
    val condition: String = "used",
    val brand: String? = null,
    val location: String? = null,
    val delivery_type: String = "pickup",
    val delivery_charge: Double = 0.0,
    val is_negotiable: Boolean = false,
    val images: List<String> = emptyList()
)

@Serializable
data class UpdateMarketplaceProductRequest(
    val category_id: Long? = null,
    val subcategory_id: Long? = null,
    val title: String? = null,
    val description: String? = null,
    val price: Double? = null,
    val original_price: Double? = null,
    val condition: String? = null,
    val brand: String? = null,
    val location: String? = null,
    val delivery_type: String? = null,
    val delivery_charge: Double? = null,
    val is_negotiable: Boolean? = null,
    val images: List<String>? = null
)

@Serializable
data class UpdateMarketplaceStatusRequest(
    val status: String
)

@Serializable
data class MarketplaceOfferDto(
    val id: Long,
    val product_id: Long,
    val product_title: String? = null,
    val product_primary_image: String? = null,
    val product_price: Double = 0.0,
    val buyer_id: Long? = null,
    val buyer_name: String? = null,
    val buyer_avatar: String? = null,
    val seller_id: Long? = null,
    val seller_name: String? = null,
    val offer_amount: Double,
    val original_price: Double = 0.0,
    val counter_amount: Double? = null,
    val currency: String = "BDT",
    val currency_symbol: String = "৳",
    val status: String = "pending", // pending, accepted, rejected, countered, cancelled, expired
    val message: String? = null,
    val counter_message: String? = null,
    val is_buyer: Boolean = false,
    val is_seller: Boolean = false,
    val created_at: String? = null,
    val updated_at: String? = null
)

@Serializable
data class CreateOfferRequest(
    val offer_amount: Double,
    val message: String? = null
)

@Serializable
data class CounterOfferRequest(
    val counter_amount: Double,
    val counter_message: String? = null
)

@Serializable
data class ReportMarketplaceRequest(
    val reason: String,
    val details: String? = null
)

@Serializable
data class MarketplaceOrderDto(
    val id: Long,
    val order_number: String? = null,
    val product_id: Long? = null,
    val product_title: String? = null,
    val product_image: String? = null,
    val buyer_id: Long? = null,
    val seller_id: Long? = null,
    val quantity: Int = 1,
    val amount: Double = 0.0,
    val total_amount: Double? = null,
    val currency: String = "BDT",
    val status: String = "pending", // pending, confirmed, processing, shipped, delivered, completed, cancelled, disputed, refunded
    val escrow_status: String = "pending",
    val payment_method: String = "sandbox",
    val shipping_name: String? = null,
    val shipping_address: String? = null,
    val shipping_phone: String? = null,
    val tracking_carrier: String? = null,
    val tracking_number: String? = null,
    val created_at: String? = null,
    val product: MarketplaceProductDto? = null
)

@Serializable
data class MarketplaceOrdersResponse(
    val status: String? = null,
    val success: Boolean = true,
    val message: String? = null,
    val items: List<MarketplaceOrderDto>? = null,
    val data: kotlinx.serialization.json.JsonElement? = null,
    val summary: MarketplaceSellerSummaryDto? = null
) {
    fun resolvedOrders(): List<MarketplaceOrderDto> {
        if (!items.isNullOrEmpty()) return items
        val element = data ?: return emptyList()
        val parser = Json { ignoreUnknownKeys = true; coerceInputValues = true }
        return try {
            when (element) {
                is JsonArray -> parser.decodeFromJsonElement(ListSerializer(MarketplaceOrderDto.serializer()), element)
                is JsonObject -> {
                    val nested = element["data"]
                    if (nested is JsonArray) {
                        parser.decodeFromJsonElement(ListSerializer(MarketplaceOrderDto.serializer()), nested)
                    } else {
                        emptyList()
                    }
                }
                else -> emptyList()
            }
        } catch (_: Exception) {
            emptyList()
        }
    }
}

@Serializable
data class ShipMarketplaceOrderRequest(
    val carrier: String? = null,
    val tracking_number: String? = null
)

@Serializable
data class CancelMarketplaceOrderRequest(
    val reason: String
)

@Serializable
data class ContactSellerResponseDto(
    val conversation_id: Long? = null,
    val seller_id: Long? = null,
    val message_sent: Boolean = false
)

@Serializable
data class MarketplaceSellerSummaryDto(
    val total_listings: Int = 0,
    val active_listings: Int = 0,
    val sold_listings: Int = 0,
    val pending_offers_count: Int = 0,
    val total_orders: Int = 0,
    val active_orders: Int = 0,
    val completed_orders: Int = 0,
    val cancelled_orders: Int = 0,
    val disputed_orders: Int = 0,
    val gross_sales: Double = 0.0,
    val net_earnings: Double = 0.0,
    val pending_settlements: Double = 0.0,
    val currency: String = "BDT"
)

@Serializable
data class MarketplaceBuyerSummaryDto(
    val total_orders: Int = 0,
    val active_orders: Int = 0,
    val completed_orders: Int = 0,
    val cancelled_orders: Int = 0,
    val active_offers_count: Int = 0,
    val saved_products_count: Int = 0
)

@Serializable
data class ContactSellerRequest(
    val message: String
)

@Serializable
data class OrderMarketplaceProductRequest(
    val shipping_address: String,
    val shipping_name: String,
    val shipping_phone: String,
    val delivery_type: String = "standard",
    val quantity: Int = 1,
    val payment_method: String = "sandbox",
    val idempotency_key: String? = null
)

@Serializable
data class ToggleFavoriteResponse(
    val success: Boolean = true,
    val is_favorited: Boolean = false,
    val message: String? = null
)

@Serializable
data class MarketplaceDeleteResponse(
    val success: Boolean = true,
    val status: String? = null,
    val message: String? = null,
    val data: kotlinx.serialization.json.JsonElement? = null
) {
    companion object {
        fun of(success: Boolean = true, status: String? = null, message: String? = null): MarketplaceDeleteResponse =
            MarketplaceDeleteResponse(success = success, status = status, message = message, data = null)
    }
}

@Serializable
data class SavedItemDto(
    val id: Long,
    val item_type: String = "post",
    val item_id: Long,
    val collection_name: String? = null,
    val created_at: String? = null,
    val post: SavedPostPreviewDto? = null
)

@Serializable
data class SavedPostPreviewDto(
    val id: Long,
    val content: String? = null,
    val author_name: String? = null,
    val author_avatar: String? = null,
    val media_url: String? = null,
    val created_at: String? = null
)

@Serializable
data class MemoryItemDto(
    val id: Long,
    val content: String = "",
    val created_at: String? = null,
    val created_at_formatted: String? = null,
    val years_ago: Int = 1,
    val years_ago_label: String = "1 year ago",
    val media_url: String? = null,
    val author_name: String? = null,
    val author_avatar: String? = null
)

// -------------------------------------------------------------
// Recruitment & Jobs DTOs (Phase 12)
// -------------------------------------------------------------
@Serializable
data class JobDto(
    val id: Long,
    val company_id: Long? = null,
    val category_id: Long? = null,
    val title: String,
    val slug: String? = null,
    val company_name: String = "",
    val company_logo: String? = null,
    val location: String = "",
    val country: String? = null,
    val city: String? = null,
    val location_type: String = "onsite",
    val location_type_label: String = "",
    val workplace_type: String = "onsite",
    val employment_type: String = "full_time",
    val employment_type_label: String = "",
    val experience_level: String = "mid",
    val salary_formatted: String = "আলোচনা সাপেক্ষে",
    val salary_min: Int? = null,
    val salary_max: Int? = null,
    val salary_currency: String = "BDT",
    val salary_negotiable: Boolean = false,
    val vacancies: Int = 1,
    val description: String = "",
    val responsibilities: String? = null,
    val requirements: String? = null,
    val qualifications: String? = null,
    val benefits: String? = null,
    val deadline: String? = null,
    val deadline_formatted: String? = null,
    val is_expired: Boolean = false,
    val status: String = "active",
    val applications_count: Int = 0,
    val views_count: Int = 0,
    val has_applied: Boolean = false,
    val is_saved: Boolean = false,
    val skills: List<JobSkillDto> = emptyList(),
    val company: CompanySummaryDto? = null
)

@Serializable
data class JobSkillDto(
    val id: Long,
    val name: String,
    val slug: String? = null
)

@Serializable
data class CompanySummaryDto(
    val id: Long,
    val name: String,
    val slug: String? = null,
    val logo: String? = null,
    val industry: String? = null,
    val is_verified: Boolean = false
)

@Serializable
data class CompanyDto(
    val id: Long,
    val owner_user_id: Long = 0,
    val name: String,
    val slug: String? = null,
    val logo: String? = null,
    val cover_image: String? = null,
    val description: String? = null,
    val industry: String? = null,
    val company_size: String? = null,
    val founded_year: Int? = null,
    val website: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val headquarters: String? = null,
    val verification_status: String = "unverified",
    val is_verified: Boolean = false,
    val visibility: String = "public",
    val viewer_role: String? = null,
    val active_jobs_count: Int = 0,
    val total_jobs_count: Int = 0
)

@Serializable
data class JobApplicationDto(
    val id: Long,
    val job_posting_id: Long = 0,
    val company_id: Long? = null,
    val user_id: Long = 0,
    val resume_id: Long? = null,
    val full_name: String = "",
    val email: String = "",
    val phone: String = "",
    val cover_letter: String? = null,
    val resume_url: String? = null,
    val resume_title: String? = null,
    val expected_salary: Int? = null,
    val status: String = "applied",
    val status_label: String = "Applied",
    val status_color: String = "#0284c7",
    val status_bg: String = "#e0f2fe",
    val created_at: String? = null,
    val applied_at: String? = null,
    val applied_at_human: String? = null,
    val job_title: String? = null,
    val company_name: String? = null
)

@Serializable
data class CandidateProfileDto(
    val id: Long = 0,
    val user_id: Long = 0,
    val professional_title: String? = null,
    val professional_summary: String? = null,
    val experience_years: Int = 0,
    val current_company: String? = null,
    val current_position: String? = null,
    val expected_salary: Int? = null,
    val salary_currency: String = "BDT",
    val availability_status: String = "open_to_work",
    val profile_visibility: String = "public"
)

@Serializable
data class CandidateResumeDto(
    val id: Long,
    val user_id: Long = 0,
    val title: String,
    val original_filename: String = "",
    val mime_type: String = "",
    val file_size: Long = 0,
    val file_size_formatted: String = "",
    val is_default: Boolean = false
)

@Serializable
data class InterviewDto(
    val id: Long,
    val application_id: Long = 0,
    val company_id: Long? = null,
    val company_name: String? = null,
    val interview_type: String = "video",
    val scheduled_at: String = "",
    val scheduled_at_formatted: String = "",
    val duration_minutes: Int = 45,
    val timezone: String = "Asia/Dhaka",
    val meeting_url: String? = null,
    val location: String? = null,
    val instructions: String? = null,
    val status: String = "scheduled",
    val job_title: String? = null,
    val candidate_name: String? = null
)

@Serializable
data class JobAlertDto(
    val id: Long,
    val user_id: Long = 0,
    val title: String,
    val keywords: String? = null,
    val frequency: String = "daily",
    val is_active: Boolean = true
)

@Serializable
data class ApplyJobRequest(
    val full_name: String,
    val email: String,
    val phone: String,
    val resume_id: Long? = null,
    val resume_url: String? = null,
    val portfolio_url: String? = null,
    val cover_letter: String? = null,
    val expected_salary: Int? = null
)

@Serializable
data class CreateJobApiRequest(
    val title: String,
    val company_id: Long? = null,
    val category_id: Long? = null,
    val workplace_type: String? = null,
    val location_type: String? = null,
    val employment_type: String? = null,
    val experience_level: String? = null,
    val location: String? = null,
    val salary_min: Int? = null,
    val salary_max: Int? = null,
    val salary_currency: String? = "BDT",
    val vacancies: Int? = 1,
    val description: String = "",
    val requirements: String? = null,
    val skills: List<String> = emptyList(),
    val deadline: String? = null
)

@Serializable
data class UpdateCandidateProfileRequest(
    val headline: String? = null,
    val summary: String? = null,
    val years_of_experience: Int? = null,
    val current_company: String? = null,
    val expected_salary_min: Int? = null,
    val expected_salary_max: Int? = null,
    val preferred_workplace_type: String? = null,
    val preferred_employment_type: String? = null,
    val open_to_work: Boolean = true,
    val skills: List<String> = emptyList()
)

@Serializable
data class CreateJobAlertRequest(
    val keyword: String? = null,
    val category_id: Long? = null,
    val location: String? = null,
    val workplace_type: String? = null,
    val min_salary: Int? = null,
    val frequency: String = "daily"
)

@Serializable
data class CreateCompanyRequest(
    val name: String,
    val legal_name: String? = null,
    val industry: String? = null,
    val website: String? = null,
    val description: String? = null,
    val location: String? = null,
    val company_size: String? = null,
    val logo_url: String? = null
)

@Serializable
data class ScheduleInterviewRequest(
    val scheduled_at: String,
    val interview_type: String = "video",
    val meeting_url: String? = null,
    val meeting_link: String? = null,
    val location: String? = null,
    val instructions: String? = null,
    val interviewer_notes: String? = null,
    val duration_minutes: Int = 45,
    val timezone: String = "Asia/Dhaka"
)

@Serializable
data class AtsColumnDto(
    val stage_key: String,
    val title: String,
    val color: String = "#0284c7",
    val count: Int = 0,
    val candidates: List<AtsCandidateDto> = emptyList()
)

@Serializable
data class AtsCandidateDto(
    val id: Long,
    val job_id: Long = 0,
    val job_title: String? = null,
    val candidate_name: String = "",
    val candidate_email: String = "",
    val avatar: String? = null,
    val applied_at: String? = null,
    val expected_salary: Int? = null
)

@Serializable
data class AtsPipelineDto(
    val company_id: Long = 0,
    val company_name: String = "",
    val total_active_candidates: Int = 0,
    val columns: List<AtsColumnDto> = emptyList()
)

@Serializable
data class EmployerDashboardDto(
    val company: CompanyDto? = null,
    val metrics: EmployerMetricsDto = EmployerMetricsDto(),
    val recent_applications: List<JobApplicationDto> = emptyList(),
    val upcoming_interviews: List<InterviewDto> = emptyList()
)

@Serializable
data class EmployerMetricsDto(
    val total_jobs: Int = 0,
    val active_jobs: Int = 0,
    val draft_jobs: Int = 0,
    val paused_jobs: Int = 0,
    val closed_jobs: Int = 0,
    val total_applications: Int = 0,
    val new_applications: Int = 0,
    val shortlisted_candidates: Int = 0,
    val interviews_scheduled: Int = 0,
    val offers_sent: Int = 0,
    val hired_candidates: Int = 0
)

// -------------------------------------------------------------
// Phase 13: LMS DTOs
// -------------------------------------------------------------

@Serializable
data class LearningCategoryDto(
    val id: Long = 0,
    val parent_id: Long? = null,
    val name: String = "",
    val slug: String = "",
    val icon: String? = null,
    val description: String? = null,
    val courses_count: Int = 0,
    val children: List<LearningCategoryDto> = emptyList()
)

@Serializable
data class LearningCourseDto(
    val id: Long = 0,
    val instructor_id: Long = 0,
    val category_id: Long? = null,
    val title: String = "",
    val slug: String = "",
    val subtitle: String? = null,
    val description: String = "",
    val level: String = "all_levels",
    val language: String = "en",
    val thumbnail_url: String? = null,
    val promotional_video_url: String? = null,
    val type: String = "free",
    val price: Double = 0.0,
    val discounted_price: Double? = null,
    val currency: String = "USD",
    val status: String = "draft",
    val duration_minutes: Int = 0,
    val total_sections: Int = 0,
    val total_lessons: Int = 0,
    val enrollments_count: Int = 0,
    val rating_avg: Double = 0.0,
    val rating_count: Int = 0,
    val is_featured: Boolean = false,
    val is_enrolled: Boolean = false,
    val published_at: String? = null,
    val instructor: UserDto? = null,
    val category: LearningCategoryDto? = null
)

@Serializable
data class LearningCourseDetailsResponseDto(
    val course: LearningCourseDto? = null,
    val is_enrolled: Boolean = false
)

@Serializable
data class LearningSectionDto(
    val id: Long = 0,
    val course_id: Long = 0,
    val title: String = "",
    val description: String? = null,
    val sort_order: Int = 0,
    val lessons: List<LearningLessonDto> = emptyList()
)

@Serializable
data class LearningLessonDto(
    val id: Long = 0,
    val section_id: Long = 0,
    val course_id: Long = 0,
    val title: String = "",
    val slug: String = "",
    val type: String = "video",
    val content: String? = null,
    val video_url: String? = null,
    val video_duration_seconds: Int = 0,
    val sort_order: Int = 0,
    val is_preview: Boolean = false,
    val is_mandatory: Boolean = true,
    val is_published: Boolean = true,
    val is_completed: Boolean = false,
    val last_position_seconds: Int = 0
)

@Serializable
data class EnrollCourseRequest(
    val coupon_code: String? = null
)

@Serializable
data class UpdateLessonProgressRequest(
    val position_seconds: Int = 0,
    val mark_completed: Boolean = false
)

@Serializable
data class LearningDashboardMetaDto(
    val enrolled_count: Int = 0,
    val in_progress_count: Int = 0,
    val completed_count: Int = 0,
    val certificates_count: Int = 0
)

@Serializable
data class LearningMyLearningResponse(
    val success: Boolean = true,
    val message: String? = null,
    val data: List<LearningEnrollmentDto> = emptyList(),
    val meta: LearningDashboardMetaDto? = null
)

@Serializable
data class LearningEnrollmentDto(
    val id: Long = 0,
    val course_id: Long = 0,
    val user_id: Long = 0,
    val status: String = "active",
    val price_paid: Double = 0.0,
    val currency: String = "USD",
    val enrolled_at: String? = null,
    val completed_at: String? = null,
    val last_accessed_at: String? = null,
    val course: LearningCourseDto? = null,
    val course_progress: LearningCourseProgressDto? = null
)

@Serializable
data class LearningCourseProgressDto(
    val id: Long = 0,
    val enrollment_id: Long = 0,
    val completed_lessons_count: Int = 0,
    val total_lessons_count: Int = 0,
    val percentage: Double = 0.0,
    val is_completed: Boolean = false
)

@Serializable
data class LearningQuizDto(
    val id: Long = 0,
    val lesson_id: Long = 0,
    val title: String = "",
    val instructions: String? = null,
    val time_limit_minutes: Int = 0,
    val passing_score: Int = 70,
    val max_attempts: Int = 0,
    val total_questions: Int = 0,
    val questions: List<LearningQuizQuestionDto> = emptyList()
)

@Serializable
data class LearningQuizQuestionDto(
    val id: Long = 0,
    val question: String = "",
    val type: String = "multiple_choice",
    val points: Int = 1,
    val options: List<LearningQuizOptionDto> = emptyList()
)

@Serializable
data class LearningQuizOptionDto(
    val id: Long = 0,
    val option_text: String = ""
)

@Serializable
data class LearningQuizAttemptDto(
    val id: Long = 0,
    val quiz_id: Long = 0,
    val user_id: Long = 0,
    val attempt_number: Int = 1,
    val score: Double = 0.0,
    val total_points: Int = 0,
    val earned_points: Int = 0,
    val passed: Boolean = false,
    val started_at: String? = null,
    val submitted_at: String? = null
)

@Serializable
data class LearningCertificateDto(
    val id: Long = 0,
    val course_id: Long = 0,
    val certificate_code: String = "",
    val verification_token: String = "",
    val student_name: String = "",
    val course_title: String = "",
    val instructor_name: String = "",
    val pdf_url: String? = null,
    val qr_code_url: String? = null,
    val issued_at: String? = null,
    val is_revoked: Boolean = false,
    val course: LearningCourseDto? = null
)

@Serializable
data class LearningReviewDto(
    val id: Long = 0,
    val course_id: Long = 0,
    val rating: Int = 5,
    val content: String? = null,
    val instructor_reply: String? = null,
    val created_at: String? = null,
    val user: UserDto? = null
)

object ApiErrorParser {
    private val jsonParser = Json { ignoreUnknownKeys = true; isLenient = true }

    fun extractMessage(rawJson: String?, fallback: String): String {
        if (rawJson.isNullOrBlank()) return fallback
        return try {
            val obj = jsonParser.parseToJsonElement(rawJson)
            if (obj is JsonObject) {
                val msg = obj["message"]
                if (msg is kotlinx.serialization.json.JsonPrimitive && msg.isString) {
                    msg.content.takeIf { it.isNotBlank() } ?: fallback
                } else {
                    fallback
                }
            } else {
                fallback
            }
        } catch (_: Exception) {
            "\"message\"\\s*:\\s*\"([^\"]+)\"".toRegex().find(rawJson)?.groupValues?.getOrNull(1) ?: fallback
        }
    }
}





