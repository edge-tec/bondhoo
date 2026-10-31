package com.connectly.android.feature.reels.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Reel
import com.connectly.android.feature.reels.domain.repository.ReelsRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetReelsFeedUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(page: Int = 1, perPage: Int = 10): Flow<Result<List<Reel>>> =
        repository.getReelsFeed(page, perPage)
}

class ReactToReelUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long, reaction: String = "like"): Flow<Result<Unit>> =
        repository.reactToReel(reelId, reaction)
}

class RecordReelViewUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long, durationSeconds: Int, completed: Boolean): Flow<Result<Unit>> =
        repository.recordReelView(reelId, durationSeconds, completed)
}

class SaveReelUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long): Flow<Result<Boolean>> =
        repository.saveReel(reelId)
}

class ShareReelUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long): Flow<Result<Unit>> =
        repository.shareReel(reelId)
}

class FollowCreatorUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(creatorId: Long): Flow<Result<Boolean>> =
        repository.followCreator(creatorId)
}

class GetReelCommentsUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long): Flow<Result<List<Comment>>> =
        repository.getReelComments(reelId)
}

class CreateReelCommentUseCase @Inject constructor(
    private val repository: ReelsRepository
) {
    operator fun invoke(reelId: Long, body: String): Flow<Result<Comment>> =
        repository.createReelComment(reelId, body)
}

