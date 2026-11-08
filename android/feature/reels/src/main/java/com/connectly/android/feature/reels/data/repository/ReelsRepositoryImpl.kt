package com.connectly.android.feature.reels.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.ReelDao
import com.connectly.android.core.database.entity.ReelEntity
import com.connectly.android.core.media.model.UploadStatus
import com.connectly.android.core.media.uploader.MediaUploadManager
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.MusicTrack
import com.connectly.android.core.model.Reel
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.reels.domain.repository.ReelsRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReelsRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val reelDao: ReelDao,
    private val mediaUploadManager: MediaUploadManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : ReelsRepository {

    override fun getReelsFeed(page: Int, perPage: Int, tab: String): Flow<Result<List<Reel>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getReelsFeed(page = page, perPage = perPage, tab = tab)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val reels = dtos.map { dto ->
                    val creatorDto = dto.resolvedCreator
                    Reel(
                        id = dto.id,
                        creator = User(
                            id = creatorDto.id,
                            name = creatorDto.name,
                            username = creatorDto.username,
                            avatar = creatorDto.resolvedAvatar,
                            isVerified = creatorDto.is_verified
                        ),
                        caption = dto.resolvedCaption,
                        videoUrl = dto.resolvedVideoUrl,
                        thumbnailUrl = dto.resolvedThumbnailUrl,
                        music = MusicTrack(
                            id = 1L,
                            title = dto.audio_title ?: "Original Audio",
                            artist = dto.audio_artist ?: creatorDto.name
                        ),
                        likesCount = dto.likes_count,
                        commentsCount = dto.comments_count,
                        sharesCount = dto.shares_count,
                        isLiked = dto.resolvedLiked,
                        isSaved = dto.resolvedSaved
                    )
                }

                // Cache into Room DB
                reelDao.insertReels(reels.map { r ->
                    ReelEntity(
                        id = r.id,
                        creatorId = r.creator.id,
                        creatorName = r.creator.name,
                        creatorAvatar = r.creator.avatar,
                        caption = r.caption,
                        videoUrl = r.videoUrl,
                        thumbnailUrl = r.thumbnailUrl,
                        likesCount = r.likesCount,
                        commentsCount = r.commentsCount,
                        isLiked = r.isLiked
                    )
                })

                if (reels.isNotEmpty()) {
                    emit(Result.Success(reels))
                } else {
                    val cached = reelDao.getReelsFeed().firstOrNull() ?: emptyList()
                    if (cached.isNotEmpty()) {
                        emit(Result.Success(cached.map { entity ->
                            Reel(
                                id = entity.id,
                                creator = User(
                                    id = entity.creatorId,
                                    name = entity.creatorName,
                                    username = "creator",
                                    avatar = entity.creatorAvatar
                                ),
                                caption = entity.caption,
                                videoUrl = entity.videoUrl,
                                thumbnailUrl = entity.thumbnailUrl,
                                likesCount = entity.likesCount,
                                commentsCount = entity.commentsCount,
                                isLiked = entity.isLiked
                            )
                        }))
                    } else {
                        emit(Result.Success(emptyList()))
                    }
                }
            } else {
                val cached = reelDao.getReelsFeed().firstOrNull() ?: emptyList()
                if (cached.isNotEmpty()) {
                    emit(Result.Success(cached.map { entity ->
                        Reel(
                            id = entity.id,
                            creator = User(
                                id = entity.creatorId,
                                name = entity.creatorName,
                                username = "creator",
                                avatar = entity.creatorAvatar
                            ),
                            caption = entity.caption,
                            videoUrl = entity.videoUrl,
                            thumbnailUrl = entity.thumbnailUrl,
                            likesCount = entity.likesCount,
                            commentsCount = entity.commentsCount,
                            isLiked = entity.isLiked
                        )
                    }))
                } else {
                    emit(Result.Error(Exception("Failed to load reels (${response.code()})")))
                }
            }
        } catch (e: Exception) {
            val cached = reelDao.getReelsFeed().firstOrNull() ?: emptyList()
            if (cached.isNotEmpty()) {
                emit(Result.Success(cached.map { entity ->
                    Reel(
                        id = entity.id,
                        creator = User(
                            id = entity.creatorId,
                            name = entity.creatorName,
                            username = "creator",
                            avatar = entity.creatorAvatar
                        ),
                        caption = entity.caption,
                        videoUrl = entity.videoUrl,
                        thumbnailUrl = entity.thumbnailUrl,
                        likesCount = entity.likesCount,
                        commentsCount = entity.commentsCount,
                        isLiked = entity.isLiked
                    )
                }))
            } else {
                emit(Result.Error(e))
            }
        }
    }.flowOn(ioDispatcher)

    override fun createReel(videoFile: File, caption: String?, audioTitle: String?): Flow<Result<Reel>> = flow {
        emit(Result.Loading)
        try {
            // 1. Upload video via chunked upload manager
            val uploadResult = mediaUploadManager.uploadMedia(
                file = videoFile,
                collection = "reel",
                mimeType = "video/mp4"
            ).firstOrNull { it is UploadStatus.Success || it is UploadStatus.Error }

            if (uploadResult is UploadStatus.Error) {
                emit(Result.Error(Exception(uploadResult.message)))
                return@flow
            }

            val mediaId = (uploadResult as? UploadStatus.Success)?.media?.id
                ?: throw Exception("Video upload could not be completed.")

            // 2. Publish reel
            val payload = CreateReelPayload(
                media_id = mediaId,
                caption = caption,
                privacy = "public",
                audio_title = audioTitle
            )

            val response = api.createReel(payload)
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val creatorDto = dto.resolvedCreator
                val reel = Reel(
                    id = dto.id,
                    creator = User(
                        id = creatorDto.id,
                        name = creatorDto.name,
                        username = creatorDto.username,
                        avatar = creatorDto.resolvedAvatar,
                        isVerified = creatorDto.is_verified
                    ),
                    caption = dto.resolvedCaption,
                    videoUrl = dto.resolvedVideoUrl,
                    thumbnailUrl = dto.resolvedThumbnailUrl,
                    music = MusicTrack(
                        id = 1L,
                        title = dto.audio_title ?: (audioTitle ?: "Original Audio"),
                        artist = dto.audio_artist ?: creatorDto.name
                    ),
                    likesCount = 0,
                    commentsCount = 0
                )
                emit(Result.Success(reel))
            } else {
                emit(Result.Error(Exception("Failed to publish reel.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToReel(reelId: Long, reaction: String): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            reelDao.updateLike(reelId, true, 12501)
            val response = api.reactToReel(reelId, ReactionRequest(type = reaction, reaction = reaction))
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
                    val authorDto = dto.resolvedAuthor
                    Comment(
                        id = dto.id,
                        postId = dto.post_id,
                        parentId = dto.parent_id,
                        author = User(
                            id = authorDto.id,
                            name = authorDto.name,
                            username = authorDto.username,
                            avatar = authorDto.resolvedAvatar,
                            isVerified = authorDto.is_verified
                        ),
                        body = dto.resolvedBody,
                        reactionsCount = dto.resolvedReactionsCount,
                        createdAt = dto.created_at
                    )
                }
                emit(Result.Success(comments))
            } else {
                emit(Result.Error(Exception("Failed to load comments (${response.code()})")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun createReelComment(reelId: Long, body: String): Flow<Result<Comment>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createReelComment(reelId, CreateCommentPayload(body = body))
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val authorDto = dto.resolvedAuthor
                val comment = Comment(
                    id = dto.id,
                    postId = reelId,
                    author = User(
                        id = authorDto.id,
                        name = authorDto.name,
                        username = authorDto.username,
                        avatar = authorDto.resolvedAvatar
                    ),
                    body = dto.resolvedBody,
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

    override fun deleteReel(reelId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deleteReel(reelId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to delete reel.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)
}
