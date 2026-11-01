package com.connectly.android.feature.live.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import kotlinx.coroutines.flow.Flow

interface LiveRepository {
    fun getActiveLiveStream(streamId: Long): Flow<Result<LiveStream>>
    fun startLiveStream(title: String, description: String? = null): Flow<Result<LiveStream>>
    fun endLiveStream(streamId: Long): Flow<Result<Unit>>
    fun joinStream(streamId: Long): Flow<Result<LiveStream>>
    fun leaveStream(streamId: Long): Flow<Result<Unit>>
    fun sendComment(streamId: Long, comment: String): Flow<Result<LiveComment>>
    fun deleteComment(streamId: Long, commentId: Long): Flow<Result<Unit>>
    fun sendReaction(streamId: Long, reaction: String): Flow<Result<Unit>>
    fun shareStream(streamId: Long): Flow<Result<Unit>>
    fun addModerator(streamId: Long, userId: Long): Flow<Result<Unit>>
    fun observeLiveComments(streamId: Long): Flow<LiveComment>
}
