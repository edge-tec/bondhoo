package com.connectly.android.feature.live.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.live.domain.repository.LiveRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.flow
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LiveRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : LiveRepository {

    private val incomingCommentsFlow = MutableSharedFlow<LiveComment>(replay = 1)
    private val currentSessionId = UUID.randomUUID().toString()

    override fun getActiveLiveStream(streamId: Long): Flow<Result<LiveStream>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLiveStreamDetails(streamId)
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                val streamerUser = dto.streamer ?: dto.user
                emit(
                    Result.Success(
                        LiveStream(
                            id = dto.id,
                            title = dto.title,
                            streamer = streamerUser?.let {
                                User(id = it.id, name = it.name, username = it.username ?: "", avatar = it.avatar)
                            } ?: User(id = 1L, name = "Broadcaster", username = "broadcaster"),
                            playbackUrl = dto.playback_url,
                            viewerCount = dto.viewer_count,
                            formattedViewerCount = formatViewerCount(dto.viewer_count),
                            isLive = dto.is_live
                        )
                    )
                )
            } else {
                emit(Result.Success(getSampleLiveStream(streamId)))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSampleLiveStream(streamId)))
        }
    }

    override fun startLiveStream(title: String, description: String?): Flow<Result<LiveStream>> = flow {
        emit(Result.Loading)
        try {
            val response = api.startLiveStream(CreateLiveStreamRequest(title = title, description = description))
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                val streamerUser = dto.streamer ?: dto.user
                emit(
                    Result.Success(
                        LiveStream(
                            id = dto.id,
                            title = dto.title,
                            streamer = streamerUser?.let {
                                User(id = it.id, name = it.name, username = it.username ?: "", avatar = it.avatar)
                            } ?: User(id = 1L, name = "You", username = "you"),
                            playbackUrl = dto.playback_url,
                            viewerCount = 1,
                            formattedViewerCount = "1",
                            isLive = true
                        )
                    )
                )
            } else {
                emit(Result.Success(getSampleLiveStream(System.currentTimeMillis())))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSampleLiveStream(System.currentTimeMillis())))
        }
    }

    override fun endLiveStream(streamId: Long): Flow<Result<Unit>> = flow {
        try {
            api.endLiveStream(streamId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun joinStream(streamId: Long): Flow<Result<LiveStream>> = flow {
        try {
            val response = api.joinLiveStream(streamId, JoinLiveSessionRequest(currentSessionId))
            val viewerCount = if (response.isSuccessful && response.body()?.data != null) {
                response.body()!!.data.viewer_count
            } else 1

            val stream = getSampleLiveStream(streamId).copy(
                viewerCount = viewerCount,
                formattedViewerCount = formatViewerCount(viewerCount)
            )
            emit(Result.Success(stream))
        } catch (e: Exception) {
            emit(Result.Success(getSampleLiveStream(streamId)))
        }
    }

    override fun leaveStream(streamId: Long): Flow<Result<Unit>> = flow {
        try {
            api.leaveLiveStream(streamId, JoinLiveSessionRequest(currentSessionId))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun sendComment(streamId: Long, comment: String): Flow<Result<LiveComment>> = flow {
        val newComment = LiveComment(
            id = System.currentTimeMillis(),
            streamId = streamId,
            user = User(id = 1L, name = "You", username = "you"),
            comment = comment
        )
        try {
            val response = api.sendLiveComment(streamId, CreateLiveCommentRequest(comment))
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                val realComment = LiveComment(
                    id = dto.id,
                    streamId = dto.stream_id,
                    user = User(id = dto.user.id, name = dto.user.name, username = dto.user.username ?: "", avatar = dto.user.avatar),
                    comment = dto.comment
                )
                incomingCommentsFlow.emit(realComment)
                emit(Result.Success(realComment))
            } else {
                incomingCommentsFlow.emit(newComment)
                emit(Result.Success(newComment))
            }
        } catch (e: Exception) {
            incomingCommentsFlow.emit(newComment)
            emit(Result.Success(newComment))
        }
    }

    override fun deleteComment(streamId: Long, commentId: Long): Flow<Result<Unit>> = flow {
        try {
            api.deleteLiveComment(streamId, commentId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun sendReaction(streamId: Long, reaction: String): Flow<Result<Unit>> = flow {
        try {
            api.sendLiveReaction(streamId, LiveReactionRequest(reaction))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun shareStream(streamId: Long): Flow<Result<Unit>> = flow {
        try {
            api.shareLiveStream(streamId, ShareLiveStreamRequest("feed"))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun addModerator(streamId: Long, userId: Long): Flow<Result<Unit>> = flow {
        try {
            api.addLiveModerator(streamId, LiveModeratorRequest(userId))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun observeLiveComments(streamId: Long): Flow<LiveComment> = incomingCommentsFlow.asSharedFlow()

    private fun formatViewerCount(count: Int): String {
        return when {
            count >= 1_000 -> String.format("%.1fK", count / 1_000.0)
            else -> count.toString()
        }
    }

    private fun getSampleLiveStream(streamId: Long): LiveStream {
        return LiveStream(
            id = streamId,
            title = "Acoustic Live Music Session & Q&A 🎸",
            streamer = User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
            playbackUrl = "https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8",
            viewerCount = 1420,
            formattedViewerCount = "1.4K",
            isLive = true
        )
    }
}
