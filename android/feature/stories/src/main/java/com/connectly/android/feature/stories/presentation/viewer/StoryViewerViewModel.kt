package com.connectly.android.feature.stories.presentation.viewer

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.Story
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.feature.stories.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class StoryViewerViewModel @Inject constructor(
    private val api: ConnectlyApi,
    private val tokenStorageManager: TokenStorageManager,
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
        viewModelScope.launch {
            try {
                val currentUserId = tokenStorageManager.getUserId()
                val response = api.getStory(storyId)
                if (response.isSuccessful && response.body()?.data != null) {
                    val dto = response.body()!!.data!!
                    val authorUser = User(
                        id = dto.user?.id ?: 1L,
                        name = dto.user?.name ?: "Bondhoo Creator",
                        username = dto.user?.username ?: "creator",
                        avatar = dto.user?.resolvedAvatar,
                        isVerified = dto.user?.is_verified ?: false
                    )
                    val realStory = Story(
                        id = dto.id,
                        userId = dto.user?.id ?: 1L,
                        author = authorUser,
                        mediaUrl = dto.resolvedMediaUrl.ifBlank { dto.content ?: "" },
                        mediaType = dto.resolvedMediaType,
                        viewsCount = dto.views_count,
                        privacy = dto.privacy
                    )
                    _uiState.update {
                        it.copy(
                            story = realStory,
                            isViewingOwnStory = (dto.user?.id == currentUserId)
                        )
                    }
                } else if (response.code() == 410) {
                    _uiState.update {
                        it.copy(errorMessage = "This story has expired and is no longer accessible.")
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = e.message) }
            }

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
