package com.connectly.android.feature.live.presentation.player

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.User
import com.connectly.android.feature.live.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LivePlayerViewModel @Inject constructor(
    private val getActiveLiveStreamUseCase: GetActiveLiveStreamUseCase,
    private val sendLiveCommentUseCase: SendLiveCommentUseCase,
    private val sendLiveReactionUseCase: SendLiveReactionUseCase,
    private val observeLiveCommentsUseCase: ObserveLiveCommentsUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(
        LivePlayerState(
            comments = getInitialScreen8Comments()
        )
    )
    val uiState: StateFlow<LivePlayerState> = _uiState.asStateFlow()

    fun handleIntent(intent: LivePlayerIntent) {
        when (intent) {
            is LivePlayerIntent.LoadStream -> loadStream(intent.streamId)
            is LivePlayerIntent.SendComment -> sendComment(intent.text)
            is LivePlayerIntent.SendReaction -> sendReaction(intent.type)
            LivePlayerIntent.ToggleGiftSheet -> {
                _uiState.update { it.copy(isGiftSheetOpen = !it.isGiftSheetOpen) }
            }
            LivePlayerIntent.ShareStream -> { /* Share action */ }
        }
    }

    private fun loadStream(streamId: Long) {
        viewModelScope.launch {
            getActiveLiveStreamUseCase(streamId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, liveStream = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }

        viewModelScope.launch {
            observeLiveCommentsUseCase(streamId).collect { newComment ->
                _uiState.update { it.copy(comments = it.comments + newComment) }
            }
        }
    }

    private fun sendComment(text: String) {
        val streamId = _uiState.value.liveStream?.id ?: 1L
        val localComment = LiveComment(
            id = System.currentTimeMillis(),
            streamId = streamId,
            user = User(id = 1L, name = "You", username = "you"),
            comment = text
        )
        _uiState.update { it.copy(comments = it.comments + localComment) }

        viewModelScope.launch {
            sendLiveCommentUseCase(streamId, text).collect()
        }
    }

    private fun sendReaction(type: String) {
        val streamId = _uiState.value.liveStream?.id ?: 1L
        _uiState.update { it.copy(floatingReactions = it.floatingReactions + type) }

        viewModelScope.launch {
            sendLiveReactionUseCase(streamId, type).collect()
        }
    }

    private fun getInitialScreen8Comments(): List<LiveComment> {
        return listOf(
            LiveComment(
                id = 1L,
                streamId = 1L,
                user = User(id = 81L, name = "Rahat Khan", username = "rahat_khan"),
                comment = "So beautiful! ❤️"
            ),
            LiveComment(
                id = 2L,
                streamId = 1L,
                user = User(id = 82L, name = "Tania Akter", username = "tania_akter"),
                comment = "Amazing! 👏"
            ),
            LiveComment(
                id = 3L,
                streamId = 1L,
                user = User(id = 83L, name = "Imran Hasan", username = "imran_hasan"),
                comment = "Great session!"
            )
        )
    }
}
