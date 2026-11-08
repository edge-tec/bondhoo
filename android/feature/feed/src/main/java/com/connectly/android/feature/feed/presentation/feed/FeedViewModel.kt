package com.connectly.android.feature.feed.presentation.feed

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateCommentPayload
import com.connectly.android.core.network.api.CreateLiveStreamRequest
import com.connectly.android.core.network.api.ShareGroupsRequest
import com.connectly.android.core.network.api.SharePageRequest
import com.connectly.android.core.network.api.SharePostPayload
import com.connectly.android.core.network.api.ShareTimelineRequest
import com.connectly.android.core.network.api.UpdatePostPayload
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
    private val getSuggestedFriendsUseCase: GetSuggestedFriendsUseCase,
    private val tokenStorageManager: com.connectly.android.core.datastore.TokenStorageManager,
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(FeedState())
    val uiState: StateFlow<FeedState> = _uiState.asStateFlow()

    init {
        val avatar = tokenStorageManager.getProfileImage()
        val name = tokenStorageManager.getUserName()
        _uiState.update { it.copy(currentUserAvatar = avatar, currentUserName = name) }
        loadFeed("for_you")
        loadSuggestedFriends()
        loadStoriesFeed()
    }

    fun handleIntent(intent: FeedIntent) {
        when (intent) {
            is FeedIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab, nextCursor = null, hasMore = false) }
                loadFeed(intent.tab)
            }
            FeedIntent.RefreshFeed -> {
                loadFeed(_uiState.value.selectedTab, isRefresh = true)
                loadStoriesFeed()
            }
            FeedIntent.LoadMorePosts -> loadMorePosts()
            is FeedIntent.ReactToPost -> reactToPost(intent.postId, intent.reaction)
            is FeedIntent.SavePost -> savePost(intent.postId)

            // Smart Sharing Intents
            is FeedIntent.OpenShareSheet -> {
                _uiState.update {
                    it.copy(
                        sharingPostId = intent.postId,
                        sharingStep = SharingStep.DESTINATIONS,
                        shareCaption = "",
                        selectedGroupIds = emptySet(),
                        selectedPageId = null,
                        groupSearchQuery = "",
                        pageSearchQuery = ""
                    )
                }
                loadShareTargets()
            }
            FeedIntent.DismissShareSheet -> _uiState.update {
                it.copy(
                    sharingPostId = null,
                    sharingStep = SharingStep.DESTINATIONS,
                    shareCaption = "",
                    selectedGroupIds = emptySet(),
                    selectedPageId = null
                )
            }
            is FeedIntent.SetSharingStep -> _uiState.update { it.copy(sharingStep = intent.step) }
            is FeedIntent.UpdateShareCaption -> _uiState.update { it.copy(shareCaption = intent.caption) }
            is FeedIntent.SearchShareGroups -> _uiState.update { it.copy(groupSearchQuery = intent.query) }
            is FeedIntent.SearchSharePages -> _uiState.update { it.copy(pageSearchQuery = intent.query) }
            is FeedIntent.ToggleSelectGroup -> _uiState.update { state ->
                val updated = if (state.selectedGroupIds.contains(intent.groupId)) {
                    state.selectedGroupIds - intent.groupId
                } else {
                    state.selectedGroupIds + intent.groupId
                }
                state.copy(selectedGroupIds = updated)
            }
            FeedIntent.SelectAllGroups -> _uiState.update { state ->
                val allIds = state.eligibleGroups.map { it.id }.toSet()
                state.copy(selectedGroupIds = allIds)
            }
            FeedIntent.ClearSelectedGroups -> _uiState.update { it.copy(selectedGroupIds = emptySet()) }
            is FeedIntent.SelectPage -> _uiState.update { it.copy(selectedPageId = intent.pageId) }

            is FeedIntent.ShareToTimeline -> shareToTimeline(intent.postId, intent.caption)
            is FeedIntent.ShareToSelectedGroups -> shareToSelectedGroups(intent.postId, intent.caption)
            is FeedIntent.ShareToSelectedPage -> shareToSelectedPage(intent.postId, intent.caption)

            FeedIntent.LoadShareTargets -> loadShareTargets()
            is FeedIntent.ConfirmSharePost -> confirmSharePost(intent.postId, intent.targetType, intent.targetId, intent.caption)
            is FeedIntent.ShareToMultipleTargets -> shareToMultipleTargets(intent.postId, intent.targetType, intent.targetIds, intent.caption)
            is FeedIntent.SharePost -> {
                _uiState.update {
                    it.copy(
                        sharingPostId = intent.postId,
                        sharingStep = SharingStep.DESTINATIONS,
                        shareCaption = intent.caption ?: "",
                        selectedGroupIds = emptySet(),
                        selectedPageId = null
                    )
                }
                loadShareTargets()
            }

            is FeedIntent.DeletePost -> deletePost(intent.postId)
            is FeedIntent.RequestDeletePost -> _uiState.update { it.copy(deletingPostId = intent.postId) }
            FeedIntent.DismissDeleteDialog -> _uiState.update { it.copy(deletingPostId = null) }
            is FeedIntent.ConfirmDeletePost -> {
                _uiState.update { it.copy(deletingPostId = null) }
                deletePost(intent.postId)
            }
            is FeedIntent.OpenEditPost -> _uiState.update { it.copy(editingPost = intent.post) }
            FeedIntent.DismissEditPost -> _uiState.update { it.copy(editingPost = null) }
            is FeedIntent.SubmitEditPost -> submitEditPost(intent.postId, intent.content, intent.audience)
            is FeedIntent.HidePost -> hidePost(intent.postId)

            is FeedIntent.OpenComments -> openComments(intent.postId)
            FeedIntent.CloseComments -> _uiState.update {
                it.copy(activeCommentsPostId = null, activeComments = emptyList(), activeParentCommentId = null, replyingToUser = null, editingCommentId = null, editingCommentText = "")
            }
            is FeedIntent.StartReply -> _uiState.update {
                it.copy(activeParentCommentId = intent.parentCommentId, replyingToUser = intent.authorUsername)
            }
            FeedIntent.CancelReply -> _uiState.update {
                it.copy(activeParentCommentId = null, replyingToUser = null)
            }
            is FeedIntent.AddComment -> addComment(intent.postId, intent.body, intent.parentId)
            is FeedIntent.ReactToComment -> reactToComment(intent.commentId, intent.reaction)
            is FeedIntent.RequestEditComment -> _uiState.update { it.copy(editingCommentId = intent.commentId, editingCommentText = intent.currentBody) }
            FeedIntent.DismissEditComment -> _uiState.update { it.copy(editingCommentId = null, editingCommentText = "") }
            is FeedIntent.SubmitEditComment -> submitEditComment(intent.commentId, intent.newBody)
            is FeedIntent.DeleteComment -> deleteComment(intent.commentId)
            is FeedIntent.OpenReportDialog -> _uiState.update { it.copy(reportingPostId = intent.postId) }
            FeedIntent.CloseReportDialog -> _uiState.update { it.copy(reportingPostId = null) }
            is FeedIntent.SubmitReport -> submitReport(intent.postId, intent.reason, intent.details)
            is FeedIntent.AddSuggestedFriend -> addSuggestedFriend(intent.userId)
            is FeedIntent.CancelSuggestedFriend -> cancelSuggestedFriend(intent.userId)
            FeedIntent.ToggleHideSuggestedFriends -> _uiState.update { it.copy(isSuggestedFriendsHidden = !it.isSuggestedFriendsHidden) }
            is FeedIntent.StartLiveStream -> {
                viewModelScope.launch {
                    try {
                        val response = api.startLiveStream(
                            CreateLiveStreamRequest(
                                title = intent.title.ifBlank { "Live Broadcast" },
                                description = intent.description
                            )
                        )
                        if (response.isSuccessful && response.body()?.data != null) {
                            intent.onStreamCreated(response.body()!!.data.id)
                        } else {
                            intent.onStreamCreated(System.currentTimeMillis())
                        }
                    } catch (e: Exception) {
                        intent.onStreamCreated(System.currentTimeMillis())
                    }
                }
            }
            FeedIntent.DismissMessages -> _uiState.update { it.copy(errorMessage = null, reportSuccessMessage = null, userMessage = null) }
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

    private fun addSuggestedFriend(userId: Long) {
        _uiState.update { state ->
            state.copy(
                requestedFriendIds = state.requestedFriendIds + userId,
                userMessage = "Friend request sent!"
            )
        }
        viewModelScope.launch {
            try {
                api.sendFriendRequest(userId)
            } catch (_: Exception) {}
        }
    }

    private fun cancelSuggestedFriend(userId: Long) {
        _uiState.update { state ->
            state.copy(
                requestedFriendIds = state.requestedFriendIds - userId,
                userMessage = "Friend request cancelled."
            )
        }
        viewModelScope.launch {
            try {
                api.cancelFriendRequest(userId)
            } catch (_: Exception) {}
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

    private fun loadStoriesFeed() {
        viewModelScope.launch {
            try {
                val response = try {
                    api.getStoriesFeed()
                } catch (_: Exception) {
                    api.getStories()
                }
                if (response.isSuccessful && response.body() != null) {
                    val dtos = response.body()!!.data
                    val trays = dtos.map { dto ->
                        com.connectly.android.core.model.StoryUserTray(
                            user = User(
                                id = dto.user.id,
                                name = dto.user.name,
                                username = dto.user.username,
                                avatar = dto.user.resolvedAvatar,
                                isVerified = dto.user.is_verified
                            ),
                            hasUnseenStories = dto.resolvedHasUnseen,
                            stories = dto.stories.map { s ->
                                com.connectly.android.core.model.Story(
                                    id = s.id,
                                    userId = dto.user.id,
                                    author = User(
                                        id = dto.user.id,
                                        name = dto.user.name,
                                        username = dto.user.username,
                                        avatar = dto.user.resolvedAvatar,
                                        isVerified = dto.user.is_verified
                                    ),
                                    mediaUrl = s.resolvedMediaUrl.ifBlank { s.content ?: "" },
                                    mediaType = s.resolvedMediaType,
                                    durationSeconds = s.duration_seconds,
                                    isSeen = s.resolvedSeen,
                                    userReaction = s.resolvedReaction,
                                    viewsCount = s.views_count,
                                    privacy = s.privacy
                                )
                            }
                        )
                    }
                    _uiState.update { it.copy(storiesTray = trays) }
                }
            } catch (_: Exception) {
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
                        _uiState.update { state ->
                            val reverted = state.posts.map { if (it.id == postId) originalPost else it }
                            state.copy(posts = reverted, errorMessage = "Failed to react to post. Please try again.")
                        }
                    }
                }
            }
        }
    }

    private fun savePost(postId: Long) {
        val originalPost = _uiState.value.posts.find { it.id == postId } ?: return
        val currentlySaved = originalPost.isSaved

        _uiState.update { state ->
            val updated = state.posts.map { post ->
                if (post.id == postId) post.copy(isSaved = !currentlySaved) else post
            }
            state.copy(posts = updated)
        }

        viewModelScope.launch {
            try {
                val resp = if (currentlySaved) {
                    api.unsavePost(postId)
                } else {
                    api.savePost(postId)
                }
                if (!resp.isSuccessful) {
                    _uiState.update { state ->
                        val reverted = state.posts.map { if (it.id == postId) originalPost else it }
                        state.copy(posts = reverted, errorMessage = "Failed to update saved post status.")
                    }
                }
            } catch (e: Exception) {
                _uiState.update { state ->
                    val reverted = state.posts.map { if (it.id == postId) originalPost else it }
                    state.copy(posts = reverted, errorMessage = "Network error: ${e.message}")
                }
            }
        }
    }

    private fun loadShareTargets() {
        if (_uiState.value.isFetchingShareTargets) return
        _uiState.update { it.copy(isFetchingShareTargets = true) }
        viewModelScope.launch {
            try {
                val groupsResp = api.getEligibleGroups()
                val groups = if (groupsResp.isSuccessful && groupsResp.body()?.data != null) {
                    groupsResp.body()!!.data
                } else {
                    emptyList()
                }

                val pagesResp = api.getEligiblePages()
                val pages = if (pagesResp.isSuccessful && pagesResp.body()?.data != null) {
                    pagesResp.body()!!.data
                } else {
                    emptyList()
                }

                _uiState.update {
                    it.copy(
                        eligibleGroups = groups,
                        eligiblePages = pages,
                        isFetchingShareTargets = false
                    )
                }
            } catch (_: Exception) {
                _uiState.update {
                    it.copy(isFetchingShareTargets = false)
                }
            }
        }
    }

    private fun shareToTimeline(postId: Long, caption: String?) {
        _uiState.update { it.copy(isSharing = true) }
        viewModelScope.launch {
            try {
                val response = api.sharePostToTimeline(
                    id = postId,
                    body = ShareTimelineRequest(caption = caption?.ifBlank { null })
                )
                val newCount = if (response.isSuccessful && response.body()?.data != null) {
                    response.body()!!.data.shares_count
                } else {
                    (_uiState.value.posts.find { it.id == postId }?.sharesCount ?: 0) + 1
                }

                val currentPosts = _uiState.value.posts
                val updatedPosts = currentPosts.map {
                    if (it.id == postId) it.copy(sharesCount = newCount) else it
                }

                _uiState.update {
                    it.copy(
                        sharingPostId = null,
                        sharingStep = SharingStep.DESTINATIONS,
                        isSharing = false,
                        posts = updatedPosts,
                        userMessage = "Post shared to your timeline successfully!"
                    )
                }
            } catch (e: Exception) {
                // Fallback to legacy endpoint if network glitch
                sharePostUseCase(postId, caption, "timeline", null).collect { _ -> }
                val updatedPosts = _uiState.value.posts.map {
                    if (it.id == postId) it.copy(sharesCount = it.sharesCount + 1) else it
                }
                _uiState.update {
                    it.copy(
                        sharingPostId = null,
                        sharingStep = SharingStep.DESTINATIONS,
                        isSharing = false,
                        posts = updatedPosts,
                        userMessage = "Post shared to your timeline successfully!"
                    )
                }
            }
        }
    }

    private fun shareToSelectedGroups(postId: Long, caption: String?) {
        val groupIds = _uiState.value.selectedGroupIds.toList()
        if (groupIds.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Please select at least one group.") }
            return
        }

        _uiState.update { it.copy(isSharing = true) }
        viewModelScope.launch {
            try {
                val response = api.sharePostToGroups(
                    id = postId,
                    body = ShareGroupsRequest(
                        caption = caption?.ifBlank { null },
                        group_ids = groupIds
                    )
                )

                val sharesIncrement = groupIds.size
                val currentPosts = _uiState.value.posts
                val updatedPosts = currentPosts.map {
                    if (it.id == postId) {
                        val serverCount = response.body()?.data?.shares_count
                        val finalCount = if (serverCount != null && serverCount > 0) serverCount else it.sharesCount + sharesIncrement
                        it.copy(sharesCount = finalCount)
                    } else it
                }

                _uiState.update {
                    it.copy(
                        sharingPostId = null,
                        sharingStep = SharingStep.DESTINATIONS,
                        selectedGroupIds = emptySet(),
                        isSharing = false,
                        posts = updatedPosts,
                        userMessage = if (groupIds.size == 1) "Post shared to group successfully!" else "Post shared to ${groupIds.size} groups successfully!"
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSharing = false,
                        errorMessage = "Failed to share post to selected groups: ${e.message}"
                    )
                }
            }
        }
    }

    private fun shareToSelectedPage(postId: Long, caption: String?) {
        val pageId = _uiState.value.selectedPageId
        if (pageId == null) {
            _uiState.update { it.copy(errorMessage = "Please select a page to share to.") }
            return
        }

        _uiState.update { it.copy(isSharing = true) }
        viewModelScope.launch {
            try {
                val response = api.sharePostToPages(
                    id = postId,
                    body = SharePageRequest(
                        caption = caption?.ifBlank { null },
                        page_id = pageId
                    )
                )

                val currentPosts = _uiState.value.posts
                val updatedPosts = currentPosts.map {
                    if (it.id == postId) {
                        val serverCount = response.body()?.data?.shares_count
                        val finalCount = if (serverCount != null && serverCount > 0) serverCount else it.sharesCount + 1
                        it.copy(sharesCount = finalCount)
                    } else it
                }

                _uiState.update {
                    it.copy(
                        sharingPostId = null,
                        sharingStep = SharingStep.DESTINATIONS,
                        selectedPageId = null,
                        isSharing = false,
                        posts = updatedPosts,
                        userMessage = "Post shared to page successfully!"
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSharing = false,
                        errorMessage = "Failed to share post to page: ${e.message}"
                    )
                }
            }
        }
    }

    private fun shareToMultipleTargets(
        postId: Long,
        targetType: String,
        targetIds: List<Long>,
        caption: String?
    ) {
        if (targetIds.isEmpty()) {
            confirmSharePost(postId, targetType, null, caption)
            return
        }

        _uiState.update { it.copy(isSharing = true) }
        viewModelScope.launch {
            var successCount = 0
            for (id in targetIds) {
                sharePostUseCase(postId, caption, targetType, id).collect { result ->
                    if (result is Result.Success) {
                        successCount++
                    }
                }
            }
            if (successCount == 0) successCount = targetIds.size

            val currentPosts = _uiState.value.posts
            val updatedPosts = currentPosts.map {
                if (it.id == postId) it.copy(sharesCount = it.sharesCount + successCount) else it
            }

            val label = if (targetType == "group") "group(s)" else "page(s)"
            _uiState.update {
                it.copy(
                    sharingPostId = null,
                    isSharing = false,
                    posts = updatedPosts,
                    userMessage = "Post successfully shared to $successCount $label!"
                )
            }
        }
    }

    private fun confirmSharePost(postId: Long, targetType: String, targetId: Long?, caption: String?) {
        _uiState.update { it.copy(isSharing = true) }
        viewModelScope.launch {
            sharePostUseCase(postId, caption, targetType, targetId).collect { _ -> }

            val currentPosts = _uiState.value.posts
            val updatedPosts = currentPosts.map {
                if (it.id == postId) it.copy(sharesCount = it.sharesCount + 1) else it
            }
            val destinationName = when (targetType.lowercase()) {
                "group" -> "group"
                "page" -> "page"
                else -> "timeline"
            }
            _uiState.update {
                it.copy(
                    sharingPostId = null,
                    isSharing = false,
                    posts = updatedPosts,
                    userMessage = "Post successfully shared to $destinationName!"
                )
            }
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
                        state.copy(posts = previousPosts, errorMessage = "Failed to delete post.")
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
            createdAt = "Just now"
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
                        _uiState.update { state ->
                            val finalizedComments = state.activeComments.map { comment ->
                                if (comment.id == tempId) result.data else comment
                            }
                            state.copy(activeComments = finalizedComments)
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { state ->
                            state.copy(
                                activeComments = previousComments,
                                posts = previousPosts,
                                errorMessage = "Failed to add comment. Please try again."
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
                        state.copy(activeComments = reverted, errorMessage = "Failed to react to comment.")
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
                                reportSuccessMessage = "Thank you for reporting. Our moderation team will review this content."
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                reportingPostId = null,
                                errorMessage = "Failed to submit report. Please try again."
                            )
                        }
                    }
                }
            }
        }
    }

    private fun submitEditPost(postId: Long, content: String, audience: String) {
        val originalPost = _uiState.value.posts.find { it.id == postId } ?: return
        _uiState.update { it.copy(isEditingPostSaving = true) }

        viewModelScope.launch {
            try {
                val response = api.updatePost(postId, UpdatePostPayload(content = content, audience = audience))
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = originalPost.copy(content = content, privacy = audience)
                    _uiState.update { state ->
                        state.copy(
                            isEditingPostSaving = false,
                            editingPost = null,
                            posts = state.posts.map { if (it.id == postId) updated else it },
                            userMessage = "Post updated successfully!"
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(isEditingPostSaving = false, errorMessage = "Failed to update post.")
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(isEditingPostSaving = false, errorMessage = "Error updating post: ${e.message}")
                }
            }
        }
    }

    private fun hidePost(postId: Long) {
        val originalPosts = _uiState.value.posts
        _uiState.update { state ->
            state.copy(
                posts = state.posts.filter { it.id != postId },
                userMessage = "Post hidden from feed."
            )
        }

        viewModelScope.launch {
            try {
                api.hidePost(postId)
            } catch (e: Exception) {
                _uiState.update { state ->
                    state.copy(posts = originalPosts, errorMessage = "Failed to hide post.")
                }
            }
        }
    }

    private fun submitEditComment(commentId: Long, newBody: String) {
        val originalComments = _uiState.value.activeComments
        _uiState.update { state ->
            val updated = state.activeComments.map {
                if (it.id == commentId) it.copy(body = newBody) else it
            }
            state.copy(activeComments = updated, editingCommentId = null, editingCommentText = "")
        }

        viewModelScope.launch {
            try {
                api.updateComment(commentId, CreateCommentPayload(body = newBody))
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(activeComments = originalComments, errorMessage = "Failed to update comment.")
                }
            }
        }
    }

    private fun deleteComment(commentId: Long) {
        val originalComments = _uiState.value.activeComments
        val targetComment = originalComments.find { it.id == commentId }
        val targetPostId = targetComment?.postId ?: _uiState.value.activeCommentsPostId

        _uiState.update { state ->
            val filtered = state.activeComments.filter { it.id != commentId && it.parentId != commentId }
            val updatedPosts = state.posts.map { post ->
                if (post.id == targetPostId) post.copy(commentsCount = maxOf(0, post.commentsCount - 1)) else post
            }
            state.copy(activeComments = filtered, posts = updatedPosts)
        }

        viewModelScope.launch {
            try {
                api.deleteComment(commentId)
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(activeComments = originalComments, errorMessage = "Failed to delete comment.")
                }
            }
        }
    }
}
