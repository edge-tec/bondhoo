package com.connectly.android.feature.stories.presentation.viewer

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Story
import com.connectly.android.core.model.User
import com.connectly.android.feature.stories.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class StoryViewerViewModel @Inject constructor(
    private val viewStoryUseCase: ViewStoryUseCase,
    private val reactToStoryUseCase: ReactToStoryUseCase,
    private val replyToStoryUseCase: ReplyToStoryUseCase,
    private val getStoryViewersUseCase: GetStoryViewersUseCase,
    private val deleteStoryUseCase: DeleteStoryUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(StoryViewerState())
    val uiState: StateFlow<StoryViewerState> = _uiState.asStateFlow()

    fun handleIntent(intent: StoryViewerIntent) {
        when (intent) {
            is StoryViewerIntent.LoadStory -> loadStory(intent.storyId)
            is StoryViewerIntent.ReactStory -> reactToStory(intent.reaction)
            is StoryViewerIntent.SendReply -> sendReply(intent.message)
            StoryViewerIntent.ToggleViewersDrawer -> _uiState.update { it.copy(showViewersDrawer = !it.showViewersDrawer) }
            StoryViewerIntent.DeleteStory -> deleteStory()
        }
    }

    private fun loadStory(storyId: Long) {
        val sadia = User(
            id = 21L,
            name = "Sadia Islam",
            username = "sadia_islam",
            avatar = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
            isVerified = true
        )
        val defaultStory = Story(
            id = storyId,
            userId = sadia.id,
            author = sadia,
            mediaUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
            viewsCount = 142
        )
        _uiState.update { it.copy(story = defaultStory) }

        viewModelScope.launch {
            viewStoryUseCase(storyId).collect()
        }
    }

    private fun reactToStory(reaction: String) {
        val story = _uiState.value.story ?: return
        viewModelScope.launch {
            reactToStoryUseCase(story.id, reaction).collect()
        }
    }

    private fun sendReply(message: String) {
        val story = _uiState.value.story ?: return
        viewModelScope.launch {
            replyToStoryUseCase(story.id, message).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(isReplied = true, replyMessage = "") }
                }
            }
        }
    }

    private fun deleteStory() {
        val story = _uiState.value.story ?: return
        viewModelScope.launch {
            deleteStoryUseCase(story.id).collect()
        }
    }
}

