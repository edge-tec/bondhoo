package com.connectly.android.feature.live.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import kotlinx.coroutines.flow.Flow

interface LiveRepository {
    fun getActiveLiveStream(streamId: Long): Flow<Result<LiveStream>>
    fun joinStream(streamId: Long): Flow<Result<LiveStream>>
    fun leaveStream(streamId: Long): Flow<Result<Unit>>
    fun sendComment(streamId: Long, comment: String): Flow<Result<LiveComment>>
    fun sendReaction(streamId: Long, reaction: String): Flow<Result<Unit>>
    fun observeLiveComments(streamId: Long): Flow<LiveComment>
}

