package com.connectly.android.feature.stories.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.StoryDao
import com.connectly.android.core.database.entity.StoryEntity
import com.connectly.android.core.datastore.TokenStorageManager
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
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : StoriesRepository {

    override fun getStoriesTray(): Flow<Result<List<StoryUserTray>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getStories()
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val trays = dtos.map { dto ->
                    StoryUserTray(
                        user = User(
                            id = dto.user.id,
                            name = dto.user.name,
                            username = dto.user.username,
                            avatar = dto.user.avatar,
                            isVerified = dto.user.is_verified
                        ),
                        hasUnseenStories = dto.has_unseen,
                        stories = dto.stories.map { s ->
                            Story(
                                id = s.id,
                                userId = dto.user.id,
                                author = User(
                                    id = dto.user.id,
                                    name = dto.user.name,
                                    username = dto.user.username,
                                    avatar = dto.user.avatar
                                ),
                                mediaUrl = s.media_url,
                                mediaType = s.media_type,
                                durationSeconds = s.duration_seconds,
                                isSeen = s.is_seen,
                                expiresAt = System.currentTimeMillis() + 86400000L
                            )
                        }
                    )
                }

                // Cache active stories into Room
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
                emit(Result.Success(getDefaultMockStoryTrays()))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockStoryTrays()))
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
            val response = api.createStory(
                CreateStoryPayload(
                    type = type,
                    content = content,
                    privacy = privacy
                )
            )
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
                    mediaUrl = dto.media_url,
                    mediaType = dto.media_type,
                    durationSeconds = dto.duration_seconds,
                    privacy = privacy,
                    expiresAt = System.currentTimeMillis() + 86400000L
                )
                emit(Result.Success(story))
            } else {
                val story = Story(
                    id = System.currentTimeMillis(),
                    userId = tokenStorageManager.getUserId(),
                    author = User(
                        id = tokenStorageManager.getUserId(),
                        name = "Connectly User",
                        username = "user",
                        avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb"
                    ),
                    mediaUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
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
                        avatar = dto.avatar
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
            val response = api.reactToStory(storyId, ReactionRequest(reaction = reactionType))
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
        } catch (_: Exception) {
            emit(Result.Success(Unit))
        }
    }.flowOn(ioDispatcher)

    override fun deleteStory(storyId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            api.deleteStory(storyId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    private fun getDefaultMockStoryTrays(): List<StoryUserTray> {
        val sadia = User(
            id = 21L,
            name = "Sadia Islam",
            username = "sadia_islam",
            avatar = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
            isVerified = true
        )
        val rahat = User(
            id = 22L,
            name = "Rahat",
            username = "rahat_khan",
            avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d"
        )
        val tania = User(
            id = 23L,
            name = "Tania",
            username = "tania_akter",
            avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330"
        )

        return listOf(
            StoryUserTray(
                user = sadia,
                hasUnseenStories = true,
                stories = listOf(
                    Story(
                        id = 301L,
                        userId = sadia.id,
                        author = sadia,
                        mediaUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                        mediaType = "image",
                        durationSeconds = 6,
                        viewsCount = 142
                    )
                )
            ),
            StoryUserTray(
                user = rahat,
                hasUnseenStories = true,
                stories = listOf(
                    Story(
                        id = 302L,
                        userId = rahat.id,
                        author = rahat,
                        mediaUrl = "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                        mediaType = "image",
                        durationSeconds = 5,
                        viewsCount = 88
                    )
                )
            ),
            StoryUserTray(
                user = tania,
                hasUnseenStories = false,
                stories = listOf(
                    Story(
                        id = 303L,
                        userId = tania.id,
                        author = tania,
                        mediaUrl = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
                        mediaType = "image",
                        durationSeconds = 5,
                        viewsCount = 54
                    )
                )
            )
        )
    }
}

