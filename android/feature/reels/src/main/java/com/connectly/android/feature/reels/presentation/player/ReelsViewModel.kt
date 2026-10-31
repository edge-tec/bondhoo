package com.connectly.android.feature.reels.presentation.player

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Reel
import com.connectly.android.core.model.User
import com.connectly.android.feature.reels.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ReelsViewModel @Inject constructor(
    private val getReelsFeedUseCase: GetReelsFeedUseCase,
    private val reactToReelUseCase: ReactToReelUseCase,
    private val recordReelViewUseCase: RecordReelViewUseCase,
    private val saveReelUseCase: SaveReelUseCase,
    private val shareReelUseCase: ShareReelUseCase,
    private val followCreatorUseCase: FollowCreatorUseCase,
    private val getReelCommentsUseCase: GetReelCommentsUseCase,
    private val createReelCommentUseCase: CreateReelCommentUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(ReelsState())
    val uiState: StateFlow<ReelsState> = _uiState.asStateFlow()

    init {
        loadReels()
    }

    fun handleIntent(intent: ReelsIntent) {
        when (intent) {
            ReelsIntent.LoadFeed -> loadReels()
            is ReelsIntent.PageChanged -> onPageChanged(intent.index)
            is ReelsIntent.ToggleLike -> toggleLike(intent.reelId)
            is ReelsIntent.ToggleSave -> toggleSave(intent.reelId)
            is ReelsIntent.ShareReel -> shareReel(intent.reelId)
            is ReelsIntent.FollowCreator -> followCreator(intent.creatorId)
            is ReelsIntent.OpenComments -> openComments(intent.reelId)
            ReelsIntent.CloseComments -> _uiState.update { it.copy(activeCommentsReelId = null, activeComments = emptyList()) }
            is ReelsIntent.AddComment -> addComment(intent.reelId, intent.body)
        }
    }

    private fun loadReels() {
        viewModelScope.launch {
            getReelsFeedUseCase().collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, reels = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun onPageChanged(index: Int) {
        _uiState.update { it.copy(currentIndex = index) }
        val reel = _uiState.value.reels.getOrNull(index) ?: return
        viewModelScope.launch {
            recordReelViewUseCase(reel.id, 5, false).collect()
        }
    }

    private fun toggleLike(reelId: Long) {
        _uiState.update { state ->
            val updated = state.reels.map { reel ->
                if (reel.id == reelId) {
                    val newIsLiked = !reel.isLiked
                    val newCount = if (newIsLiked) reel.likesCount + 1 else maxOf(0, reel.likesCount - 1)
                    reel.copy(isLiked = newIsLiked, likesCount = newCount)
                } else reel
            }
            state.copy(reels = updated)
        }
        viewModelScope.launch {
            reactToReelUseCase(reelId, "like").collect()
        }
    }

    private fun toggleSave(reelId: Long) {
        _uiState.update { state ->
            val updated = state.reels.map { reel ->
                if (reel.id == reelId) reel.copy(isSaved = !reel.isSaved) else reel
            }
            state.copy(reels = updated)
        }
        viewModelScope.launch {
            saveReelUseCase(reelId).collect()
        }
    }

    private fun shareReel(reelId: Long) {
        _uiState.update { state ->
            val updated = state.reels.map { reel ->
                if (reel.id == reelId) reel.copy(sharesCount = reel.sharesCount + 1) else reel
            }
            state.copy(reels = updated)
        }
        viewModelScope.launch {
            shareReelUseCase(reelId).collect()
        }
    }

    private fun followCreator(creatorId: Long) {
        _uiState.update { state ->
            val updated = state.reels.map { reel ->
                if (reel.creator.id == creatorId) {
                    reel.copy(isFollowingCreator = !reel.isFollowingCreator)
                } else reel
            }
            state.copy(reels = updated)
        }
        viewModelScope.launch {
            followCreatorUseCase(creatorId).collect()
        }
    }

    private fun openComments(reelId: Long) {
        _uiState.update { it.copy(activeCommentsReelId = reelId, isCommentsLoading = true) }
        viewModelScope.launch {
            getReelCommentsUseCase(reelId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(isCommentsLoading = false, activeComments = result.data) }
                } else {
                    _uiState.update { it.copy(isCommentsLoading = false) }
                }
            }
        }
    }

    private fun addComment(reelId: Long, body: String) {
        val optimisticComment = Comment(
            id = System.currentTimeMillis(),
            postId = reelId,
            author = User(id = 1L, name = "You", username = "you"),
            body = body,
            createdAt = "Just now"
        )
        _uiState.update { state ->
            val updatedComments = listOf(optimisticComment) + state.activeComments
            val updatedReels = state.reels.map { reel ->
                if (reel.id == reelId) reel.copy(commentsCount = reel.commentsCount + 1) else reel
            }
            state.copy(activeComments = updatedComments, reels = updatedReels)
        }

        viewModelScope.launch {
            createReelCommentUseCase(reelId, body).collect()
        }
    }
}

