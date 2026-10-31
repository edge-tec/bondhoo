package com.connectly.android.feature.reels.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Reel
import kotlinx.coroutines.flow.Flow

interface ReelsRepository {
    fun getReelsFeed(page: Int = 1, perPage: Int = 10): Flow<Result<List<Reel>>>
    fun reactToReel(reelId: Long, reaction: String): Flow<Result<Unit>>
    fun recordReelView(reelId: Long, durationSeconds: Int, completed: Boolean): Flow<Result<Unit>>
    fun saveReel(reelId: Long): Flow<Result<Boolean>>
    fun shareReel(reelId: Long): Flow<Result<Unit>>
    fun followCreator(creatorId: Long): Flow<Result<Boolean>>
    fun getReelComments(reelId: Long): Flow<Result<List<Comment>>>
    fun createReelComment(reelId: Long, body: String): Flow<Result<Comment>>
}

