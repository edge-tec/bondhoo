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
import kotlinx.coroutines.flow.collect
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
    private val reportPostUseCase: ReportPostUseCase,
    private val getCommentsUseCase: GetCommentsUseCase,
    private val addCommentUseCase: AddCommentUseCase,
    private val reactToCommentUseCase: ReactToCommentUseCase,
    private val getSuggestedFriendsUseCase: GetSuggestedFriendsUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(FeedState())
    val uiState: StateFlow<FeedState> = _uiState.asStateFlow()

    init {
        loadFeed("for_you")
        loadSuggestedFriends()
    }

    fun handleIntent(intent: FeedIntent) {
        when (intent) {
            is FeedIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab, nextCursor = null, hasMore = false) }
                loadFeed(intent.tab)
            }
            FeedIntent.RefreshFeed -> loadFeed(_uiState.value.selectedTab, isRefresh = true)
            FeedIntent.LoadMorePosts -> loadMorePosts()
            is FeedIntent.ReactToPost -> reactToPost(intent.postId, intent.reaction)
            is FeedIntent.SavePost -> savePost(intent.postId)
            is FeedIntent.SharePost -> sharePost(intent.postId, intent.caption)
            is FeedIntent.DeletePost -> deletePost(intent.postId)
            is FeedIntent.OpenComments -> openComments(intent.postId)
            FeedIntent.CloseComments -> _uiState.update {
                it.copy(activeCommentsPostId = null, activeComments = emptyList(), activeParentCommentId = null, replyingToUser = null)
            }
            is FeedIntent.StartReply -> _uiState.update {
                it.copy(activeParentCommentId = intent.parentCommentId, replyingToUser = intent.authorUsername)
            }
            FeedIntent.CancelReply -> _uiState.update {
                it.copy(activeParentCommentId = null, replyingToUser = null)
            }
            is FeedIntent.AddComment -> addComment(intent.postId, intent.body, intent.parentId)
            is FeedIntent.ReactToComment -> reactToComment(intent.commentId, intent.reaction)
            is FeedIntent.OpenReportDialog -> _uiState.update { it.copy(reportingPostId = intent.postId) }
            FeedIntent.CloseReportDialog -> _uiState.update { it.copy(reportingPostId = null) }
            is FeedIntent.SubmitReport -> submitReport(intent.postId, intent.reason, intent.details)
            FeedIntent.DismissMessages -> _uiState.update { it.copy(errorMessage = null, reportSuccessMessage = null) }
        }
    }

    private fun loadFeed(tab: String, isRefresh: Boolean = false) {
        viewModelScope.launch {
            getFeedUseCase(tab = tab, cursor = null).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = !isRefresh, isRefreshing = isRefresh) }
                    is Result.Success -> _uiState.update {
                        it.copy(
                            isLoading = false,
                            isRefreshing = false,
                            posts = result.data.posts,
                            nextCursor = result.data.nextCursor,
                            hasMore = result.data.hasMore
                        )
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(isLoading = false, isRefreshing = false, errorMessage = result.exception.message)
                    }
                }
            }
        }
    }

    private fun loadMorePosts() {
        val state = _uiState.value
        if (state.isPaginating || !state.hasMore || state.nextCursor == null) return

        _uiState.update { it.copy(isPaginating = true) }
        viewModelScope.launch {
            getFeedUseCase(tab = state.selectedTab, cursor = state.nextCursor).collect { result ->
                when (result) {
                    is Result.Loading -> {}
                    is Result.Success -> {
                        val existingIds = state.posts.map { it.id }.toSet()
                        val newPosts = result.data.posts.filter { it.id !in existingIds }
                        _uiState.update {
                            it.copy(
                                isPaginating = false,
                                posts = it.posts + newPosts,
                                nextCursor = result.data.nextCursor,
                                hasMore = result.data.hasMore
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isPaginating = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }

    private fun loadSuggestedFriends() {
        viewModelScope.launch {
            getSuggestedFriendsUseCase().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(suggestedFriends = result.data) }
                }
            }
        }
    }

    private fun reactToPost(postId: Long, reaction: String) {
        val originalPost = _uiState.value.posts.find { it.id == postId } ?: return

        // 1. Optimistic update
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

        // 2. Network call with rollback on error
        viewModelScope.launch {
            reactToPostUseCase(postId, reaction).collect { result ->
                when (result) {
                    is Result.Loading -> {}
                    is Result.Success -> {
                        // Confirm with server response
                        _uiState.update { state ->
                            val updated = state.posts.map { post ->
                                if (post.id == postId) {
                                    post.copy(
                                        userReaction = result.data.resolvedReaction,
                                        reactionsCount = result.data.resolvedCount
                                    )
                                } else post
                            }
                            state.copy(posts = updated)
                        }
                    }
                    is Result.Error -> {
                        // Rollback to original state
                        _uiState.update { state ->
                            val reverted = state.posts.map { if (it.id == postId) originalPost else it }
                            state.copy(posts = reverted, errorMessage = "প্রতিক্রিয়া দিতে সমস্যা হয়েছে। আবার চেষ্টা করুন।")
                        }
                    }
                }
            }
        }
    }

    private fun savePost(postId: Long) {
        val originalPost = _uiState.value.posts.find { it.id == postId } ?: return

        _uiState.update { state ->
            val updated = state.posts.map { post ->
                if (post.id == postId) post.copy(isSaved = !post.isSaved) else post
            }
            state.copy(posts = updated)
        }

        viewModelScope.launch {
            toggleSavePostUseCase(postId).collect { result ->
                if (result is Result.Error) {
                    // Rollback
                    _uiState.update { state ->
                        val reverted = state.posts.map { if (it.id == postId) originalPost else it }
                        state.copy(posts = reverted, errorMessage = "পোস্ট সংরক্ষণ করতে সমস্যা হয়েছে।")
                    }
                }
            }
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
        val previousPosts = _uiState.value.posts
        // 1. Optimistic removal
        _uiState.update { state ->
            state.copy(posts = state.posts.filter { it.id != postId })
        }

        // 2. Network call with rollback
        viewModelScope.launch {
            deletePostUseCase(postId).collect { result ->
                if (result is Result.Error) {
                    _uiState.update { state ->
                        state.copy(posts = previousPosts, errorMessage = "পোস্ট মুছতে ব্যর্থ হয়েছে।")
                    }
                }
            }
        }
    }

    private fun openComments(postId: Long) {
        _uiState.update { it.copy(activeCommentsPostId = postId, isCommentsLoading = true, activeParentCommentId = null, replyingToUser = null) }
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
        val tempId = System.currentTimeMillis()
        val optimisticComment = Comment(
            id = tempId,
            postId = postId,
            parentId = parentId,
            author = User(id = 1L, name = "You", username = "you"),
            body = body,
            createdAt = "এখন মাত্র"
        )

        val previousComments = _uiState.value.activeComments
        val previousPosts = _uiState.value.posts

        // 1. Optimistic addition
        _uiState.update { state ->
            val updatedComments = listOf(optimisticComment) + state.activeComments
            val updatedPosts = state.posts.map { post ->
                if (post.id == postId) post.copy(commentsCount = post.commentsCount + 1) else post
            }
            state.copy(activeComments = updatedComments, posts = updatedPosts, activeParentCommentId = null, replyingToUser = null)
        }

        // 2. Network call with rollback
        viewModelScope.launch {
            addCommentUseCase(postId, body, parentId).collect { result ->
                when (result) {
                    is Result.Loading -> {}
                    is Result.Success -> {
                        // Replace temp comment with confirmed server comment
                        _uiState.update { state ->
                            val finalizedComments = state.activeComments.map { comment ->
                                if (comment.id == tempId) result.data else comment
                            }
                            state.copy(activeComments = finalizedComments)
                        }
                    }
                    is Result.Error -> {
                        // Rollback
                        _uiState.update { state ->
                            state.copy(
                                activeComments = previousComments,
                                posts = previousPosts,
                                errorMessage = "মন্তব্য যোগ করা সম্ভব হয়নি।"
                            )
                        }
                    }
                }
            }
        }
    }

    private fun reactToComment(commentId: Long, reaction: String) {
        val originalComment = _uiState.value.activeComments.find { it.id == commentId } ?: return

        // 1. Optimistic update
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

        // 2. Network call with rollback
        viewModelScope.launch {
            reactToCommentUseCase(commentId, reaction).collect { result ->
                if (result is Result.Error) {
                    _uiState.update { state ->
                        val reverted = state.activeComments.map { if (it.id == commentId) originalComment else it }
                        state.copy(activeComments = reverted, errorMessage = "মন্তব্যে প্রতিক্রিয়া দিতে সমস্যা হয়েছে।")
                    }
                }
            }
        }
    }

    private fun submitReport(postId: Long, reason: String, details: String?) {
        viewModelScope.launch {
            reportPostUseCase(postId, reason, details).collect { result ->
                when (result) {
                    is Result.Loading -> {}
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                reportingPostId = null,
                                reportSuccessMessage = "রিপোর্টটি সফলভাবে জমা দেওয়া হয়েছে। আমাদের টিম পর্যালোচনা করবে।"
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                reportingPostId = null,
                                errorMessage = "রিপোর্ট জমা দেওয়া যায়নি। অনুগ্রহ করে পুনরায় চেষ্টা করুন।"
                            )
                        }
                    }
                }
            }
        }
    }
}
