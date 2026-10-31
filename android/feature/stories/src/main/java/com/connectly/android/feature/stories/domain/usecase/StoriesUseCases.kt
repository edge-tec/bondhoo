package com.connectly.android.feature.stories.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Story
import com.connectly.android.core.model.StoryUserTray
import com.connectly.android.core.model.User
import com.connectly.android.feature.stories.domain.repository.StoriesRepository
import kotlinx.coroutines.flow.Flow
import java.io.File
import javax.inject.Inject

class GetStoriesTrayUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(): Flow<Result<List<StoryUserTray>>> =
        repository.getStoriesTray()
}

class CreateStoryUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(type: String, content: String?, privacy: String, mediaFile: File? = null): Flow<Result<Story>> =
        repository.createStory(type, content, privacy, mediaFile)
}

class ViewStoryUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(storyId: Long): Flow<Result<Unit>> =
        repository.viewStory(storyId)
}

class GetStoryViewersUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(storyId: Long): Flow<Result<List<User>>> =
        repository.getStoryViewers(storyId)
}

class ReactToStoryUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(storyId: Long, reactionType: String): Flow<Result<Unit>> =
        repository.reactToStory(storyId, reactionType)
}

class ReplyToStoryUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(storyId: Long, message: String): Flow<Result<Unit>> =
        repository.replyToStory(storyId, message)
}

class DeleteStoryUseCase @Inject constructor(
    private val repository: StoriesRepository
) {
    operator fun invoke(storyId: Long): Flow<Result<Unit>> =
        repository.deleteStory(storyId)
}
