package com.connectly.android.feature.stories.presentation.viewer

import com.connectly.android.core.model.Story
import com.connectly.android.core.model.User

data class StoryViewerState(
    val story: Story? = null,
    val viewers: List<User> = emptyList(),
    val isViewingOwnStory: Boolean = false,
    val showViewersDrawer: Boolean = false,
    val isReplied: Boolean = false,
    val replyMessage: String = "",
    val errorMessage: String? = null
)

sealed interface StoryViewerIntent {
    data class LoadStory(val storyId: Long) : StoryViewerIntent
    data class ReactStory(val reaction: String) : StoryViewerIntent
    data class SendReply(val message: String) : StoryViewerIntent
    object ToggleViewersDrawer : StoryViewerIntent
    object DeleteStory : StoryViewerIntent
}
