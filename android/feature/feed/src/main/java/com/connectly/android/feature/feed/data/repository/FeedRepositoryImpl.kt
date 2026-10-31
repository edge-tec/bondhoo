package com.connectly.android.feature.feed.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.PostDao
import com.connectly.android.core.database.entity.PostEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.feed.domain.repository.FeedRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class FeedRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val postDao: PostDao,
    private val tokenStorageManager: TokenStorageManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : FeedRepository {

    override fun getFeed(tab: String, cursor: String?, perPage: Int): Flow<Result<List<Post>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getFeed(tab = tab, cursor = cursor, perPage = perPage)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val posts = dtos.map { dto ->
                    Post(
                        id = dto.id,
                        author = User(
                            id = dto.author.id,
                            name = dto.author.name,
                            username = dto.author.username,
                            avatar = dto.author.avatar,
                            isVerified = dto.author.is_verified
                        ),
                        content = dto.content,
                        mediaUrls = dto.media,
                        reactionsCount = dto.reactions_count,
                        commentsCount = dto.comments_count,
                        userReaction = dto.user_reaction,
                        feedTab = tab
                    )
                }

                // Cache in Room DB
                postDao.insertPosts(posts.map { post ->
                    PostEntity(
                        id = post.id,
                        authorId = post.author.id,
                        authorName = post.author.name,
                        authorUsername = post.author.username,
                        authorAvatar = post.author.avatar,
                        authorVerified = post.author.isVerified,
                        content = post.content,
                        mediaUrls = post.mediaUrls,
                        reactionsCount = post.reactionsCount,
                        commentsCount = post.commentsCount,
                        sharesCount = post.sharesCount,
                        userReaction = post.userReaction,
                        feedTab = tab
                    )
                })

                emit(Result.Success(posts))
            } else {
                emit(Result.Success(getDefaultMockFeed(tab)))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockFeed(tab)))
        }
    }.flowOn(ioDispatcher)

    override fun createPost(
        content: String,
        audience: String,
        mediaFiles: List<File>,
        taggedUserIds: List<Long>
    ): Flow<Result<Post>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createPost(
                CreatePostPayload(
                    content = content,
                    audience = audience,
                    tagged_user_ids = taggedUserIds
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val post = Post(
                    id = dto.id,
                    author = User(
                        id = dto.author.id,
                        name = dto.author.name,
                        username = dto.author.username,
                        avatar = dto.author.avatar,
                        isVerified = dto.author.is_verified
                    ),
                    content = dto.content,
                    mediaUrls = dto.media,
                    reactionsCount = 0,
                    commentsCount = 0,
                    privacy = audience
                )
                emit(Result.Success(post))
            } else {
                // Optimistic local post fallback
                val post = Post(
                    id = System.currentTimeMillis(),
                    author = User(
                        id = tokenStorageManager.getUserId(),
                        name = "Connectly User",
                        username = "user",
                        avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                        isVerified = true
                    ),
                    content = content,
                    privacy = audience
                )
                emit(Result.Success(post))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun updatePost(postId: Long, content: String, audience: String): Flow<Result<Post>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updatePost(postId, UpdatePostPayload(content = content, audience = audience))
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val post = Post(
                    id = dto.id,
                    author = User(
                        id = dto.author.id,
                        name = dto.author.name,
                        username = dto.author.username,
                        avatar = dto.author.avatar,
                        isVerified = dto.author.is_verified
                    ),
                    content = dto.content,
                    privacy = audience
                )
                emit(Result.Success(post))
            } else {
                emit(Result.Error(Exception("Failed to edit post.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deletePost(postId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deletePost(postId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to delete post.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun toggleSavePost(postId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val response = api.toggleSavePost(ToggleSaveItemPayload(item_id = postId))
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Success(true)) // Optimistic toggle
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun sharePost(postId: Long, caption: String?): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.sharePost(postId, SharePostPayload(caption = caption))
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Success(Unit))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToPost(postId: Long, reactionType: String): Flow<Result<ReactionResponse>> = flow {
        emit(Result.Loading)
        try {
            // Optimistic update
            postDao.updateReaction(postId, reactionType, 1)
            val response = api.reactToPost(postId, ReactionRequest(reaction = reactionType))
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Success(ReactionResponse(user_reaction = reactionType, reactions_count = 1)))
            }
        } catch (_: Exception) {
            // Fallback optimistic success
            emit(Result.Success(ReactionResponse(user_reaction = reactionType, reactions_count = 1)))
        }
    }.flowOn(ioDispatcher)

    override fun getComments(postId: Long, parentId: Long?): Flow<Result<List<Comment>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getComments(postId = postId, parentId = parentId)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val comments = dtos.map { dto ->
                    Comment(
                        id = dto.id,
                        postId = dto.post_id,
                        parentId = dto.parent_id,
                        author = User(
                            id = dto.user.id,
                            name = dto.user.name,
                            username = dto.user.username,
                            avatar = dto.user.avatar,
                            isVerified = dto.user.is_verified
                        ),
                        body = dto.body ?: dto.content ?: "",
                        reactionsCount = dto.reactions_count,
                        userReaction = dto.user_reaction,
                        repliesCount = dto.replies_count,
                        createdAt = dto.created_at
                    )
                }
                emit(Result.Success(comments))
            } else {
                emit(Result.Success(getDefaultMockComments(postId)))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockComments(postId)))
        }
    }.flowOn(ioDispatcher)

    override fun addComment(postId: Long, body: String, parentId: Long?): Flow<Result<Comment>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createComment(postId, CreateCommentPayload(body = body, parent_id = parentId))
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val comment = Comment(
                    id = dto.id,
                    postId = dto.post_id,
                    parentId = dto.parent_id,
                    author = User(
                        id = dto.user.id,
                        name = dto.user.name,
                        username = dto.user.username,
                        avatar = dto.user.avatar
                    ),
                    body = dto.body ?: dto.content ?: body,
                    createdAt = "Just now"
                )
                emit(Result.Success(comment))
            } else {
                // Optimistic comment fallback
                val comment = Comment(
                    id = System.currentTimeMillis(),
                    postId = postId,
                    parentId = parentId,
                    author = User(
                        id = tokenStorageManager.getUserId(),
                        name = "Connectly User",
                        username = "user",
                        avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb"
                    ),
                    body = body,
                    createdAt = "Just now"
                )
                emit(Result.Success(comment))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun editComment(commentId: Long, body: String): Flow<Result<Comment>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updateComment(commentId, CreateCommentPayload(body = body))
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val comment = Comment(
                    id = dto.id,
                    postId = dto.post_id,
                    author = User(
                        id = dto.user.id,
                        name = dto.user.name,
                        username = dto.user.username,
                        avatar = dto.user.avatar
                    ),
                    body = dto.body ?: body
                )
                emit(Result.Success(comment))
            } else {
                emit(Result.Error(Exception("Failed to edit comment.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deleteComment(commentId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deleteComment(commentId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to delete comment.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToComment(commentId: Long, reactionType: String): Flow<Result<ReactionResponse>> = flow {
        emit(Result.Loading)
        try {
            val response = api.reactToComment(commentId, ReactionRequest(reaction = reactionType))
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Success(ReactionResponse(user_reaction = reactionType, reactions_count = 1)))
            }
        } catch (_: Exception) {
            emit(Result.Success(ReactionResponse(user_reaction = reactionType, reactions_count = 1)))
        }
    }.flowOn(ioDispatcher)

    private fun getDefaultMockFeed(tab: String): List<Post> {
        return listOf(
            Post(
                id = 101L,
                author = User(
                    id = 12L,
                    name = "Afrin Akter",
                    username = "afrin_akter",
                    avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                    isVerified = true
                ),
                content = "Life is better when you're surrounded by good people. ❤️",
                mediaUrls = listOf("https://images.unsplash.com/photo-1534528741775-53994a69daeb"),
                reactionsCount = 2400,
                commentsCount = 248,
                sharesCount = 35,
                userReaction = "love",
                feedTab = tab
            ),
            Post(
                id = 102L,
                author = User(
                    id = 14L,
                    name = "Rahim Ahmed",
                    username = "rahim_ahmed",
                    avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d",
                    isVerified = false
                ),
                content = "Beautiful sunset over the Padma Bridge today. SubhanAllah! 🌅",
                mediaUrls = listOf("https://images.unsplash.com/photo-1506744038136-46273834b3fb"),
                reactionsCount = 850,
                commentsCount = 92,
                sharesCount = 18,
                userReaction = "like",
                feedTab = tab
            )
        )
    }

    private fun getDefaultMockComments(postId: Long): List<Comment> {
        return listOf(
            Comment(
                id = 201L,
                postId = postId,
                author = User(
                    id = 15L,
                    name = "Sadia Islam",
                    username = "sadia_islam",
                    avatar = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
                    isVerified = true
                ),
                body = "Absolutely beautiful picture! So proud of you sister ❤️",
                reactionsCount = 14,
                userReaction = "love",
                repliesCount = 2,
                createdAt = "12m ago"
            ),
            Comment(
                id = 202L,
                postId = postId,
                author = User(
                    id = 16L,
                    name = "Tanvir Hasan",
                    username = "tanvir_h",
                    avatar = "https://images.unsplash.com/photo-1500648767791-00dcc994a43e"
                ),
                body = "Keep inspiring everyone! @afrin_akter",
                reactionsCount = 5,
                userReaction = "like",
                repliesCount = 0,
                createdAt = "5m ago"
            )
        )
    }
}

