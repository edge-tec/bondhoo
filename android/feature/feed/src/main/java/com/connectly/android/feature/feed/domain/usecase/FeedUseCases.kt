package com.connectly.android.feature.feed.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.network.api.ReactionResponse
import com.connectly.android.feature.feed.domain.repository.FeedRepository
import kotlinx.coroutines.flow.Flow
import java.io.File
import javax.inject.Inject

class GetFeedUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(tab: String, cursor: String? = null, perPage: Int = 15): Flow<Result<List<Post>>> =
        repository.getFeed(tab, cursor, perPage)
}

class CreatePostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(
        content: String,
        audience: String = "public",
        mediaFiles: List<File> = emptyList(),
        taggedUserIds: List<Long> = emptyList()
    ): Flow<Result<Post>> =
        repository.createPost(content, audience, mediaFiles, taggedUserIds)
}

class UpdatePostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long, content: String, audience: String): Flow<Result<Post>> =
        repository.updatePost(postId, content, audience)
}

class DeletePostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long): Flow<Result<Unit>> =
        repository.deletePost(postId)
}

class ToggleSavePostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long): Flow<Result<Boolean>> =
        repository.toggleSavePost(postId)
}

class SharePostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long, caption: String? = null): Flow<Result<Unit>> =
        repository.sharePost(postId, caption)
}

class ReactToPostUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long, reactionType: String): Flow<Result<ReactionResponse>> =
        repository.reactToPost(postId, reactionType)
}

class GetCommentsUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long, parentId: Long? = null): Flow<Result<List<Comment>>> =
        repository.getComments(postId, parentId)
}

class AddCommentUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(postId: Long, body: String, parentId: Long? = null): Flow<Result<Comment>> =
        repository.addComment(postId, body, parentId)
}

class DeleteCommentUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(commentId: Long): Flow<Result<Unit>> =
        repository.deleteComment(commentId)
}

class ReactToCommentUseCase @Inject constructor(
    private val repository: FeedRepository
) {
    operator fun invoke(commentId: Long, reactionType: String): Flow<Result<ReactionResponse>> =
        repository.reactToComment(commentId, reactionType)
}

