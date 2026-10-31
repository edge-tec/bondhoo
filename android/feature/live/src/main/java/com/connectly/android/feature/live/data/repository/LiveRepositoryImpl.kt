package com.connectly.android.feature.live.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateLiveCommentRequest
import com.connectly.android.core.network.api.LiveReactionRequest
import com.connectly.android.feature.live.domain.repository.LiveRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LiveRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : LiveRepository {

    private val incomingCommentsFlow = MutableSharedFlow<LiveComment>(replay = 1)

    override fun getActiveLiveStream(streamId: Long): Flow<Result<LiveStream>> = flow {
        emit(Result.Loading)
        try {
            val response = api.joinLiveStream(streamId)
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                emit(
                    Result.Success(
                        LiveStream(
                            id = dto.id,
                            title = dto.title,
                            streamer = User(id = dto.streamer.id, name = dto.streamer.name, username = dto.streamer.username),
                            playbackUrl = dto.playback_url,
                            viewerCount = dto.viewer_count,
                            formattedViewerCount = formatViewerCount(dto.viewer_count)
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

    override fun joinStream(streamId: Long): Flow<Result<LiveStream>> = flow {
        emit(Result.Success(getSampleLiveStream(streamId)))
    }

    override fun leaveStream(streamId: Long): Flow<Result<Unit>> = flow {
        try {
            api.leaveLiveStream(streamId)
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
            api.sendLiveComment(streamId, CreateLiveCommentRequest(comment))
            emit(Result.Success(newComment))
        } catch (e: Exception) {
            emit(Result.Success(newComment))
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
            streamer = User(
                id = 41L,
                name = "Sadia Islam",
                username = "sadia_islam",
                avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80"
            ),
            playbackUrl = "https://example.com/live/stream.m3u8",
            viewerCount = 4800,
            formattedViewerCount = "4.8K"
        )
    }
}
