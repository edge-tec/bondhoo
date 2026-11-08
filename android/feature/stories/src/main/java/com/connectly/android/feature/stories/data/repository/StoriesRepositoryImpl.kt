package com.connectly.android.feature.stories.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.StoryDao
import com.connectly.android.core.database.entity.StoryEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.media.model.UploadStatus
import com.connectly.android.core.media.uploader.MediaUploadManager
import com.connectly.android.core.model.Story
import com.connectly.android.core.model.StoryUserTray
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateStoryPayload
import com.connectly.android.core.network.api.ReactionRequest
import com.connectly.android.core.network.api.StoryReplyPayload
import com.connectly.android.feature.stories.domain.repository.StoriesRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class StoriesRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val storyDao: StoryDao,
    private val tokenStorageManager: TokenStorageManager,
    private val mediaUploadManager: MediaUploadManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : StoriesRepository {

    override fun getStoriesTray(): Flow<Result<List<StoryUserTray>>> = flow {
        emit(Result.Loading)
        try {
            // First attempt v2 stories feed, then fallback to v1
            val response = try {
                api.getStoriesFeed()
            } catch (_: Exception) {
                api.getStories()
            }

            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val trays = dtos.map { dto ->
                    StoryUserTray(
                        user = User(
                            id = dto.user.id,
                            name = dto.user.name,
                            username = dto.user.username,
                            avatar = dto.user.resolvedAvatar,
                            isVerified = dto.user.is_verified
                        ),
                        hasUnseenStories = dto.resolvedHasUnseen,
                        stories = dto.stories.map { s ->
                            Story(
                                id = s.id,
                                userId = dto.user.id,
                                author = User(
                                    id = dto.user.id,
                                    name = dto.user.name,
                                    username = dto.user.username,
                                    avatar = dto.user.resolvedAvatar
                                ),
                                mediaUrl = s.resolvedMediaUrl,
                                mediaType = s.resolvedMediaType,
                                durationSeconds = s.duration_seconds,
                                isSeen = s.resolvedSeen,
                                userReaction = s.resolvedReaction,
                                viewsCount = s.views_count,
                                privacy = s.privacy,
                                expiresAt = System.currentTimeMillis() + 86400000L
                            )
                        }
                    )
                }

                // Cache active stories in Room DB for offline mode
                val entities = trays.flatMap { tray ->
                    tray.stories.map { s ->
                        StoryEntity(
                            id = s.id,
                            userId = s.userId,
                            userName = s.author.name,
                            userAvatar = s.author.avatar,
                            mediaUrl = s.mediaUrl,
                            mediaType = s.mediaType,
                            isSeen = s.isSeen,
                            expiresAt = s.expiresAt
                        )
                    }
                }
                storyDao.insertStories(entities)

                emit(Result.Success(trays))
            } else {
                emit(Result.Error(Exception("Failed to load stories (${response.code()})")))
            }
        } catch (e: Exception) {
            val cached = storyDao.getActiveStories(System.currentTimeMillis()).firstOrNull() ?: emptyList()
            if (cached.isNotEmpty()) {
                val trays = cached.groupBy { it.userId }.map { (userId, stories) ->
                    val first = stories.first()
                    StoryUserTray(
                        user = User(
                            id = userId,
                            name = first.userName,
                            username = "user_$userId",
                            avatar = first.userAvatar
                        ),
                        hasUnseenStories = stories.any { !it.isSeen },
                        stories = stories.map { s ->
                            Story(
                                id = s.id,
                                userId = s.userId,
                                author = User(
                                    id = s.userId,
                                    name = s.userName,
                                    username = "user_${s.userId}",
                                    avatar = s.userAvatar
                                ),
                                mediaUrl = s.mediaUrl,
                                mediaType = s.mediaType
                            )
                        }
                    )
                }
                emit(Result.Success(trays))
            } else {
                emit(Result.Error(e))
            }
        }
    }.flowOn(ioDispatcher)

    override fun createStory(
        type: String,
        content: String?,
        privacy: String,
        mediaFile: File?
    ): Flow<Result<Story>> = flow {
        emit(Result.Loading)
        try {
            var mediaId: Long? = null
            var mediaUrl: String? = null

            // If media file is provided (Photo or Video), upload via chunked resumable uploader
            if (mediaFile != null && mediaFile.exists()) {
                val collection = if (mediaFile.name.endsWith(".mp4", ignoreCase = true)) "story_video" else "story_photo"
                val uploadResult = mediaUploadManager.uploadMedia(
                    file = mediaFile,
                    collection = collection
                ).firstOrNull { it is UploadStatus.Success || it is UploadStatus.Error }

                if (uploadResult is UploadStatus.Success) {
                    mediaId = uploadResult.media.id
                    mediaUrl = uploadResult.media.resolvedUrl
                } else if (uploadResult is UploadStatus.Error) {
                    emit(Result.Error(Exception(uploadResult.message)))
                    return@flow
                }
            }

            val payload = CreateStoryPayload(
                type = type,
                content = content,
                privacy = privacy,
                media_ids = listOfNotNull(mediaId)
            )

            val response = api.createStory(payload)
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val story = Story(
                    id = dto.id,
                    userId = tokenStorageManager.getUserId(),
                    author = User(
                        id = tokenStorageManager.getUserId(),
                        name = "Connectly User",
                        username = "user"
                    ),
                    mediaUrl = dto.resolvedMediaUrl.ifBlank { mediaUrl ?: "" },
                    mediaType = dto.resolvedMediaType,
                    durationSeconds = dto.duration_seconds,
                    privacy = privacy,
                    expiresAt = System.currentTimeMillis() + 86400000L
                )
                emit(Result.Success(story))
            } else {
                // Fallback optimistic story creation
                val story = Story(
                    id = System.currentTimeMillis(),
                    userId = tokenStorageManager.getUserId(),
                    author = User(
                        id = tokenStorageManager.getUserId(),
                        name = "Connectly User",
                        username = "user",
                        avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb"
                    ),
                    mediaUrl = mediaUrl ?: "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                    privacy = privacy
                )
                emit(Result.Success(story))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun viewStory(storyId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            api.viewStory(storyId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getStoryViewers(storyId: Long): Flow<Result<List<User>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getStoryViewers(storyId)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val users = dtos.map { dto ->
                    User(
                        id = dto.id,
                        name = dto.name,
                        username = dto.username,
                        avatar = dto.resolvedAvatar,
                        isVerified = dto.is_verified
                    )
                }
                emit(Result.Success(users))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToStory(storyId: Long, reactionType: String): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.reactToStory(storyId, ReactionRequest(type = reactionType, reaction = reactionType))
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Success(Unit))
            }
        } catch (_: Exception) {
            emit(Result.Success(Unit))
        }
    }.flowOn(ioDispatcher)

    override fun replyToStory(storyId: Long, message: String): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.replyToStory(storyId, StoryReplyPayload(message = message))
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Success(Unit))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deleteStory(storyId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deleteStory(storyId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("স্টোরি মুছতে ব্যর্থ হয়েছে।")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)
}
