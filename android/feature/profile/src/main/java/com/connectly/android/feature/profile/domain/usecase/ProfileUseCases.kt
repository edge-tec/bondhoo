package com.connectly.android.feature.profile.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import kotlinx.coroutines.flow.Flow
import java.io.File
import javax.inject.Inject

class GetProfileUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(username: String): Flow<Result<User>> =
        repository.getUserProfile(username)
}

class UpdateProfileUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(bio: String, occupation: String, location: String): Flow<Result<User>> =
        repository.updateProfile(bio, occupation, location)
}

class UploadAvatarUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(file: File): Flow<Result<String>> =
        repository.uploadAvatar(file)
}

class UploadCoverUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(file: File): Flow<Result<String>> =
        repository.uploadCover(file)
}

class SendFriendRequestUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.sendFriendRequest(userId)
}

class CancelFriendRequestUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.cancelFriendRequest(userId)
}

class AcceptFriendRequestUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.acceptFriendRequest(userId)
}

class RejectFriendRequestUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.rejectFriendRequest(userId)
}

class UnfriendUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.unfriendUser(userId)
}

class FollowUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Boolean>> =
        repository.followUser(userId)
}

class UnfollowUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Boolean>> =
        repository.unfollowUser(userId)
}

class BlockUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.blockUser(userId)
}

class UnblockUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long): Flow<Result<Unit>> =
        repository.unblockUser(userId)
}

class ReportUserUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(userId: Long, reason: String, details: String? = null): Flow<Result<Unit>> =
        repository.reportUser(userId, reason, details)
}

class UpdatePrivacySettingsUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(visibility: String): Flow<Result<Unit>> =
        repository.updatePrivacySettings(visibility)
}

class GetUserPostsUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(username: String): Flow<Result<List<com.connectly.android.core.model.Post>>> =
        repository.getUserPosts(username)
}

class GetProfileAnalyticsUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(): Flow<Result<com.connectly.android.core.network.api.ProfileAnalyticsDto>> =
        repository.getProfileAnalytics()
}

class GetProfileAboutUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(): Flow<Result<com.connectly.android.core.network.api.ProfileAboutDto>> =
        repository.getProfileAbout()
}

class ReactToPostUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(postId: Long, type: String = "like"): Flow<Result<Unit>> =
        repository.reactToPost(postId, type)
}

class GetPostCommentsUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(postId: Long): Flow<Result<List<com.connectly.android.core.network.api.CommentDto>>> =
        repository.getPostComments(postId)
}

class AddPostCommentUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(postId: Long, content: String): Flow<Result<com.connectly.android.core.network.api.CommentDto>> =
        repository.addPostComment(postId, content)
}

class SharePostUseCase @Inject constructor(
    private val repository: ProfileRepository
) {
    operator fun invoke(postId: Long, caption: String? = null, targetType: String = "timeline", targetId: Long? = null): Flow<Result<Unit>> =
        repository.sharePost(postId, caption, targetType, targetId)
}


