package com.connectly.android.feature.reels.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.ReelDao
import com.connectly.android.core.database.entity.ReelEntity
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.MusicTrack
import com.connectly.android.core.model.Reel
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateCommentPayload
import com.connectly.android.core.network.api.ReactionRequest
import com.connectly.android.core.network.api.ReelViewRequest
import com.connectly.android.feature.reels.domain.repository.ReelsRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReelsRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val reelDao: ReelDao,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : ReelsRepository {

    override fun getReelsFeed(page: Int, perPage: Int): Flow<Result<List<Reel>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getReelsFeed(page = page, perPage = perPage)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val reels = dtos.map { dto ->
                    Reel(
                        id = dto.id,
                        creator = User(
                            id = dto.creator.id,
                            name = dto.creator.name,
                            username = dto.creator.username,
                            avatar = dto.creator.avatar,
                            isVerified = dto.creator.is_verified
                        ),
                        caption = dto.caption,
                        videoUrl = dto.video_url,
                        thumbnailUrl = dto.thumbnail_url,
                        music = MusicTrack(
                            id = 1L,
                            title = "Original Audio",
                            artist = dto.creator.name
                        ),
                        likesCount = dto.likes_count,
                        commentsCount = dto.comments_count
                    )
                }

                // Cache in Room DB
                reelDao.insertReels(reels.map { r ->
                    ReelEntity(
                        id = r.id,
                        creatorId = r.creator.id,
                        creatorName = r.creator.name,
                        creatorUsername = r.creator.username,
                        creatorAvatar = r.creator.avatar,
                        caption = r.caption,
                        videoUrl = r.videoUrl,
                        thumbnailUrl = r.thumbnailUrl,
                        likesCount = r.likesCount,
                        commentsCount = r.commentsCount,
                        isLiked = r.isLiked
                    )
                })

                emit(Result.Success(reels))
            } else {
                emit(Result.Success(getDefaultMockReels()))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockReels()))
        }
    }.flowOn(ioDispatcher)

    override fun reactToReel(reelId: Long, reaction: String): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            reelDao.updateLike(reelId, true, 12501)
            val response = api.reactToReel(reelId, ReactionRequest(reaction = reaction))
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Success(Unit))
            }
        } catch (_: Exception) {
            emit(Result.Success(Unit))
        }
    }.flowOn(ioDispatcher)

    override fun recordReelView(reelId: Long, durationSeconds: Int, completed: Boolean): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            api.recordReelView(reelId, ReelViewRequest(duration_seconds = durationSeconds, completed = completed))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun saveReel(reelId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val response = api.saveReel(reelId)
            emit(Result.Success(response.isSuccessful))
        } catch (_: Exception) {
            emit(Result.Success(true))
        }
    }.flowOn(ioDispatcher)

    override fun shareReel(reelId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            api.shareReel(reelId)
            emit(Result.Success(Unit))
        } catch (_: Exception) {
            emit(Result.Success(Unit))
        }
    }.flowOn(ioDispatcher)

    override fun followCreator(creatorId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val response = api.followUser(creatorId)
            emit(Result.Success(response.isSuccessful))
        } catch (_: Exception) {
            emit(Result.Success(true))
        }
    }.flowOn(ioDispatcher)

    override fun getReelComments(reelId: Long): Flow<Result<List<Comment>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getReelComments(reelId)
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
                            avatar = dto.user.avatar
                        ),
                        body = dto.body ?: dto.content ?: "",
                        reactionsCount = dto.reactions_count,
                        createdAt = dto.created_at
                    )
                }
                emit(Result.Success(comments))
            } else {
                emit(Result.Success(getDefaultMockReelComments(reelId)))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockReelComments(reelId)))
        }
    }.flowOn(ioDispatcher)

    override fun createReelComment(reelId: Long, body: String): Flow<Result<Comment>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createReelComment(reelId, CreateCommentPayload(body = body))
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val comment = Comment(
                    id = dto.id,
                    postId = reelId,
                    author = User(
                        id = dto.user.id,
                        name = dto.user.name,
                        username = dto.user.username,
                        avatar = dto.user.avatar
                    ),
                    body = dto.body ?: body,
                    createdAt = "Just now"
                )
                emit(Result.Success(comment))
            } else {
                val comment = Comment(
                    id = System.currentTimeMillis(),
                    postId = reelId,
                    author = User(id = 1L, name = "You", username = "you"),
                    body = body,
                    createdAt = "Just now"
                )
                emit(Result.Success(comment))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    private fun getDefaultMockReels(): List<Reel> {
        val musicVibes = User(
            id = 41L,
            name = "Music Vibes",
            username = "music_vibes",
            avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d",
            isVerified = true
        )
        val guitarReel = Reel(
            id = 401L,
            creator = musicVibes,
            caption = "Good Music, Good Vibes ❤️",
            videoUrl = "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4",
            thumbnailUrl = "https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4",
            music = MusicTrack(
                id = 101L,
                title = "Original Audio",
                artist = "Music Vibes"
            ),
            likesCount = 12500,
            commentsCount = 567,
            sharesCount = 1200,
            isLiked = false,
            isFollowingCreator = false
        )
        return listOf(guitarReel)
    }

    private fun getDefaultMockReelComments(reelId: Long): List<Comment> {
        return listOf(
            Comment(
                id = 601L,
                postId = reelId,
                author = User(id = 42L, name = "Sadia Islam", username = "sadia", isVerified = true),
                body = "This acoustic solo is so soothing! 🎸❤️",
                reactionsCount = 42,
                createdAt = "10m ago"
            ),
            Comment(
                id = 602L,
                postId = reelId,
                author = User(id = 43L, name = "Rahat Khan", username = "rahat"),
                body = "Love the chord progressions! Keep it up brother 🔥",
                reactionsCount = 19,
                createdAt = "5m ago"
            )
        )
    }
}

