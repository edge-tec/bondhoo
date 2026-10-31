package com.connectly.android.feature.feed.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.network.api.ReactionResponse
import kotlinx.coroutines.flow.Flow
import java.io.File

interface FeedRepository {
    fun getFeed(tab: String, cursor: String?, perPage: Int): Flow<Result<List<Post>>>
    fun createPost(content: String, audience: String, mediaFiles: List<File>, taggedUserIds: List<Long>): Flow<Result<Post>>
    fun updatePost(postId: Long, content: String, audience: String): Flow<Result<Post>>
    fun deletePost(postId: Long): Flow<Result<Unit>>
    fun toggleSavePost(postId: Long): Flow<Result<Boolean>>
    fun sharePost(postId: Long, caption: String?): Flow<Result<Unit>>
    fun reactToPost(postId: Long, reactionType: String): Flow<Result<ReactionResponse>>
    
    // Comments
    fun getComments(postId: Long, parentId: Long?): Flow<Result<List<Comment>>>
    fun addComment(postId: Long, body: String, parentId: Long?): Flow<Result<Comment>>
    fun editComment(commentId: Long, body: String): Flow<Result<Comment>>
    fun deleteComment(commentId: Long): Flow<Result<Unit>>
    fun reactToComment(commentId: Long, reactionType: String): Flow<Result<ReactionResponse>>
}
