package com.connectly.android.feature.profile.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import kotlinx.coroutines.flow.Flow
import java.io.File

interface ProfileRepository {
    fun getUserProfile(username: String): Flow<Result<User>>
    fun updateProfile(bio: String, occupation: String, location: String): Flow<Result<User>>
    fun uploadAvatar(file: File): Flow<Result<String>>
    fun uploadCover(file: File): Flow<Result<String>>
    fun sendFriendRequest(userId: Long): Flow<Result<Unit>>
    fun cancelFriendRequest(userId: Long): Flow<Result<Unit>>
    fun acceptFriendRequest(userId: Long): Flow<Result<Unit>>
    fun rejectFriendRequest(userId: Long): Flow<Result<Unit>>
    fun unfriendUser(userId: Long): Flow<Result<Unit>>
    fun followUser(userId: Long): Flow<Result<Boolean>>
    fun unfollowUser(userId: Long): Flow<Result<Boolean>>
    fun blockUser(userId: Long): Flow<Result<Unit>>
    fun unblockUser(userId: Long): Flow<Result<Unit>>
    fun reportUser(userId: Long, reason: String, details: String? = null): Flow<Result<Unit>>
    fun updatePrivacySettings(visibility: String): Flow<Result<Unit>>
    fun getUserPosts(username: String): Flow<Result<List<com.connectly.android.core.model.Post>>>
    fun getProfileAnalytics(): Flow<Result<com.connectly.android.core.network.api.ProfileAnalyticsDto>>
    fun getProfileAbout(): Flow<Result<com.connectly.android.core.network.api.ProfileAboutDto>>
    fun reactToPost(postId: Long, type: String = "like"): Flow<Result<Unit>>
    fun getPostComments(postId: Long): Flow<Result<List<com.connectly.android.core.network.api.CommentDto>>>
    fun addPostComment(postId: Long, content: String): Flow<Result<com.connectly.android.core.network.api.CommentDto>>
    fun sharePost(postId: Long, caption: String? = null, targetType: String = "timeline", targetId: Long? = null): Flow<Result<Unit>>
}

