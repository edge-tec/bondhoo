package com.connectly.android.feature.feed.presentation.feed

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.feature.feed.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class FeedViewModel @Inject constructor(
    private val getFeedUseCase: GetFeedUseCase,
    private val reactToPostUseCase: ReactToPostUseCase,
    private val toggleSavePostUseCase: ToggleSavePostUseCase,
    private val sharePostUseCase: SharePostUseCase,
    private val deletePostUseCase: DeletePostUseCase,
    private val getCommentsUseCase: GetCommentsUseCase,
    private val addCommentUseCase: AddCommentUseCase,
    private val reactToCommentUseCase: ReactToCommentUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(FeedState())
    val uiState: StateFlow<FeedState> = _uiState.asStateFlow()

    init {
        loadFeed("for_you")
    }

    fun handleIntent(intent: FeedIntent) {
        when (intent) {
            is FeedIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab) }
                loadFeed(intent.tab)
            }
            FeedIntent.RefreshFeed -> loadFeed(_uiState.value.selectedTab, isRefresh = true)
            is FeedIntent.ReactToPost -> reactToPost(intent.postId, intent.reaction)
            is FeedIntent.SavePost -> savePost(intent.postId)
            is FeedIntent.SharePost -> sharePost(intent.postId, intent.caption)
            is FeedIntent.DeletePost -> deletePost(intent.postId)
            is FeedIntent.OpenComments -> openComments(intent.postId)
            FeedIntent.CloseComments -> _uiState.update { it.copy(activeCommentsPostId = null, activeComments = emptyList()) }
            is FeedIntent.AddComment -> addComment(intent.postId, intent.body, intent.parentId)
            is FeedIntent.ReactToComment -> reactToComment(intent.commentId, intent.reaction)
        }
    }

    private fun loadFeed(tab: String, isRefresh: Boolean = false) {
        viewModelScope.launch {
            getFeedUseCase(tab).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = !isRefresh, isRefreshing = isRefresh) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, isRefreshing = false, posts = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, isRefreshing = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun reactToPost(postId: Long, reaction: String) {
        // Optimistic UI update
        _uiState.update { state ->
            val updated = state.posts.map { post ->
                if (post.id == postId) {
                    val isRemoving = post.userReaction == reaction
                    val newReaction = if (isRemoving) null else reaction
                    val countChange = if (isRemoving) -1 else if (post.userReaction == null) 1 else 0
                    post.copy(
                        userReaction = newReaction,
                        reactionsCount = maxOf(0, post.reactionsCount + countChange)
                    )
                } else post
            }
            state.copy(posts = updated)
        }

        viewModelScope.launch {
            reactToPostUseCase(postId, reaction).collect()
        }
    }

    private fun savePost(postId: Long) {
        _uiState.update { state ->
            val updated = state.posts.map { post ->
                if (post.id == postId) post.copy(isSaved = !post.isSaved) else post
            }
            state.copy(posts = updated)
        }
        viewModelScope.launch {
            toggleSavePostUseCase(postId).collect()
        }
    }

    private fun sharePost(postId: Long, caption: String?) {
        _uiState.update { state ->
            val updated = state.posts.map { post ->
                if (post.id == postId) post.copy(sharesCount = post.sharesCount + 1) else post
            }
            state.copy(posts = updated)
        }
        viewModelScope.launch {
            sharePostUseCase(postId, caption).collect()
        }
    }

    private fun deletePost(postId: Long) {
        _uiState.update { state ->
            state.copy(posts = state.posts.filter { it.id != postId })
        }
        viewModelScope.launch {
            deletePostUseCase(postId).collect()
        }
    }

    private fun openComments(postId: Long) {
        _uiState.update { it.copy(activeCommentsPostId = postId, isCommentsLoading = true) }
        viewModelScope.launch {
            getCommentsUseCase(postId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(isCommentsLoading = false, activeComments = result.data) }
                } else {
                    _uiState.update { it.copy(isCommentsLoading = false) }
                }
            }
        }
    }

    private fun addComment(postId: Long, body: String, parentId: Long?) {
        val optimisticComment = Comment(
            id = System.currentTimeMillis(),
            postId = postId,
            parentId = parentId,
            author = User(id = 1L, name = "You", username = "you"),
            body = body,
            createdAt = "Just now"
        )
        _uiState.update { state ->
            val updatedComments = listOf(optimisticComment) + state.activeComments
            val updatedPosts = state.posts.map { post ->
                if (post.id == postId) post.copy(commentsCount = post.commentsCount + 1) else post
            }
            state.copy(activeComments = updatedComments, posts = updatedPosts)
        }

        viewModelScope.launch {
            addCommentUseCase(postId, body, parentId).collect()
        }
    }

    private fun reactToComment(commentId: Long, reaction: String) {
        _uiState.update { state ->
            val updated = state.activeComments.map { comment ->
                if (comment.id == commentId) {
                    val isRemoving = comment.userReaction == reaction
                    comment.copy(
                        userReaction = if (isRemoving) null else reaction,
                        reactionsCount = maxOf(0, comment.reactionsCount + (if (isRemoving) -1 else 1))
                    )
                } else comment
            }
            state.copy(activeComments = updated)
        }
        viewModelScope.launch {
            reactToCommentUseCase(commentId, reaction).collect()
        }
    }
}

