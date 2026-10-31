package com.connectly.android.feature.stories.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Story
import com.connectly.android.core.model.StoryUserTray
import com.connectly.android.core.model.User
import kotlinx.coroutines.flow.Flow
import java.io.File

interface StoriesRepository {
    fun getStoriesTray(): Flow<Result<List<StoryUserTray>>>
    fun createStory(type: String, content: String?, privacy: String, mediaFile: File?): Flow<Result<Story>>
    fun viewStory(storyId: Long): Flow<Result<Unit>>
    fun getStoryViewers(storyId: Long): Flow<Result<List<User>>>
    fun reactToStory(storyId: Long, reactionType: String): Flow<Result<Unit>>
    fun replyToStory(storyId: Long, message: String): Flow<Result<Unit>>
    fun deleteStory(storyId: Long): Flow<Result<Unit>>
}
