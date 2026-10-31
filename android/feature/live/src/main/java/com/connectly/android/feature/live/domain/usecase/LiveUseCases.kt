package com.connectly.android.feature.live.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import com.connectly.android.feature.live.domain.repository.LiveRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetActiveLiveStreamUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long): Flow<Result<LiveStream>> {
        return repository.getActiveLiveStream(streamId)
    }
}

class JoinLiveStreamUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long): Flow<Result<LiveStream>> {
        return repository.joinStream(streamId)
    }
}

class LeaveLiveStreamUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long): Flow<Result<Unit>> {
        return repository.leaveStream(streamId)
    }
}

class SendLiveCommentUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long, comment: String): Flow<Result<LiveComment>> {
        return repository.sendComment(streamId, comment)
    }
}

class SendLiveReactionUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long, reaction: String): Flow<Result<Unit>> {
        return repository.sendReaction(streamId, reaction)
    }
}

class ObserveLiveCommentsUseCase @Inject constructor(
    private val repository: LiveRepository
) {
    operator fun invoke(streamId: Long): Flow<LiveComment> {
        return repository.observeLiveComments(streamId)
    }
}

