package com.connectly.android.feature.groups.presentation.content

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.CreateGroupPostRequest
import com.connectly.android.core.network.api.GroupPollInput
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import com.connectly.android.feature.groups.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupContentViewModel @Inject constructor(
    private val groupsRepository: GroupsRepository,
    private val getGroupFeedUseCase: GetGroupFeedUseCase,
    private val createGroupPostUseCase: CreateGroupPostUseCase,
    private val getGroupPostDetailsUseCase: GetGroupPostDetailsUseCase,
    private val deleteGroupPostUseCase: DeleteGroupPostUseCase,
    private val togglePinGroupPostUseCase: TogglePinGroupPostUseCase,
    private val voteGroupPostPollUseCase: VoteGroupPostPollUseCase,
    private val reactGroupPostUseCase: ReactGroupPostUseCase,
    private val reportGroupPostUseCase: ReportGroupPostUseCase,
    private val getGroupPendingPostsUseCase: GetGroupPendingPostsUseCase,
    private val approveGroupPendingPostUseCase: ApproveGroupPendingPostUseCase,
    private val rejectGroupPendingPostUseCase: RejectGroupPendingPostUseCase,
    private val getGroupReportsUseCase: GetGroupReportsUseCase,
    private val actionGroupReportUseCase: ActionGroupReportUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupContentState())
    val uiState: StateFlow<GroupContentState> = _uiState.asStateFlow()

    init {
        val groupIdString: String? = savedStateHandle["groupId"]
        val initialGroupId = groupIdString?.toLongOrNull() ?: 0L
        if (initialGroupId > 0L) {
            _uiState.update { it.copy(groupId = initialGroupId) }
            loadFeed(initialGroupId)
            loadPendingPosts(initialGroupId)
            loadAnnouncements(initialGroupId)
        }
    }

    fun handleIntent(intent: GroupContentIntent) {
        when (intent) {
            is GroupContentIntent.LoadFeed -> loadFeed(intent.groupId, intent.filter, intent.sort)
            is GroupContentIntent.LoadPendingPosts -> loadPendingPosts(intent.groupId)
            is GroupContentIntent.LoadAnnouncements -> loadAnnouncements(intent.groupId)
            is GroupContentIntent.LoadReports -> loadReports(intent.groupId)
            is GroupContentIntent.LoadPostDetails -> loadPostDetails(intent.groupId, intent.postId)
            is GroupContentIntent.ChangeFilter -> {
                _uiState.update { it.copy(selectedFilter = intent.filter) }
                loadFeed(_uiState.value.groupId, intent.filter, _uiState.value.selectedSort)
            }
            is GroupContentIntent.ChangeSort -> {
                _uiState.update { it.copy(selectedSort = intent.sort) }
                loadFeed(_uiState.value.groupId, _uiState.value.selectedFilter, intent.sort)
            }
            // Composer intents
            is GroupContentIntent.UpdateComposerContent -> _uiState.update { it.copy(composerContent = intent.content) }
            is GroupContentIntent.UpdateComposerType -> _uiState.update { it.copy(composerType = intent.type) }
            is GroupContentIntent.AddComposerMediaUrl -> {
                val current = _uiState.value.composerMediaUrls + intent.url
                _uiState.update { it.copy(composerMediaUrls = current) }
            }
            is GroupContentIntent.RemoveComposerMediaUrl -> {
                val current = _uiState.value.composerMediaUrls.toMutableList()
                if (intent.index in current.indices) {
                    current.removeAt(intent.index)
                    _uiState.update { it.copy(composerMediaUrls = current) }
                }
            }
            is GroupContentIntent.UpdatePollQuestion -> _uiState.update { it.copy(composerPollQuestion = intent.question) }
            is GroupContentIntent.UpdatePollOption -> {
                val current = _uiState.value.composerPollOptions.toMutableList()
                if (intent.index in current.indices) {
                    current[intent.index] = intent.text
                    _uiState.update { it.copy(composerPollOptions = current) }
                }
            }
            is GroupContentIntent.AddPollOption -> {
                if (_uiState.value.composerPollOptions.size < 6) {
                    _uiState.update { it.copy(composerPollOptions = it.composerPollOptions + "") }
                }
            }
            is GroupContentIntent.RemovePollOption -> {
                val current = _uiState.value.composerPollOptions.toMutableList()
                if (current.size > 2 && intent.index in current.indices) {
                    current.removeAt(intent.index)
                    _uiState.update { it.copy(composerPollOptions = current) }
                }
            }
            is GroupContentIntent.UpdatePollDuration -> _uiState.update { it.copy(composerPollDurationDays = intent.days) }
            is GroupContentIntent.UpdateScheduleTime -> _uiState.update { it.copy(composerScheduledAt = intent.scheduledAt) }
            is GroupContentIntent.ToggleComposerPin -> _uiState.update { it.copy(composerIsPinned = intent.pin) }
            is GroupContentIntent.SubmitPost -> submitPost(intent.isDraft)
            // Post interactions
            is GroupContentIntent.TogglePinPost -> togglePin(intent.postId)
            is GroupContentIntent.DeletePost -> deletePost(intent.postId)
            is GroupContentIntent.VotePoll -> votePoll(intent.postId, intent.optionId)
            is GroupContentIntent.ReactPost -> reactPost(intent.postId, intent.reactionType)
            // Approvals & Moderation
            is GroupContentIntent.ApprovePendingPost -> approvePendingPost(intent.postId)
            is GroupContentIntent.RejectPendingPost -> rejectPendingPost(intent.postId, intent.reason)
            is GroupContentIntent.ReportPost -> reportPost(intent.postId, intent.category, intent.description)
            is GroupContentIntent.ActionReport -> actionReport(intent.reportId, intent.status, intent.decisionNote, intent.removeContent)
            is GroupContentIntent.CreateAnnouncement -> createAnnouncement(intent.title, intent.content, intent.isPinned, intent.expiresAt)
            // Dialogs
            is GroupContentIntent.OpenReportDialog -> _uiState.update { it.copy(reportDialogPostId = intent.postId) }
            is GroupContentIntent.OpenRejectDialog -> _uiState.update { it.copy(rejectDialogPostId = intent.postId) }
            is GroupContentIntent.OpenDeleteDialog -> _uiState.update { it.copy(deleteConfirmPostId = intent.postId) }
            is GroupContentIntent.DismissDialogs -> _uiState.update {
                it.copy(reportDialogPostId = null, rejectDialogPostId = null, deleteConfirmPostId = null)
            }
            is GroupContentIntent.DismissMessages -> _uiState.update {
                it.copy(actionMessage = null, errorMessage = null)
            }
        }
    }

    private fun loadFeed(groupId: Long, filter: String? = null, sort: String? = null) {
        if (groupId <= 0L) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            getGroupFeedUseCase(groupId, filter = filter, sort = sort).collect { result ->
                when (result) {
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, feedPosts = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                    else -> {}
                }
            }
        }
    }

    private fun loadPendingPosts(groupId: Long) {
        if (groupId <= 0L) return
        viewModelScope.launch {
            getGroupPendingPostsUseCase(groupId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(pendingPosts = result.data) }
                }
            }
        }
    }

    private fun loadAnnouncements(groupId: Long) {
        if (groupId <= 0L) return
        viewModelScope.launch {
            groupsRepository.getGroupProfile(groupId.toString()).collect { result ->
                if (result is Result.Success) {
                    val anns = result.data.tabs.announcements
                    _uiState.update { it.copy(announcements = anns) }
                }
            }
        }
    }

    private fun loadReports(groupId: Long) {
        if (groupId <= 0L) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getGroupReportsUseCase(groupId).collect { result ->
                when (result) {
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, reports = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                    else -> {}
                }
            }
        }
    }

    private fun loadPostDetails(groupId: Long, postId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getGroupPostDetailsUseCase(groupId, postId).collect { result ->
                when (result) {
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, selectedPostDetails = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                    else -> {}
                }
            }
        }
    }

    private fun submitPost(isDraft: Boolean) {
        val s = _uiState.value
        val pollData = if (s.composerType == "poll" && s.composerPollQuestion.isNotBlank()) {
            GroupPollInput(
                question = s.composerPollQuestion,
                options = s.composerPollOptions.filter { it.isNotBlank() },
                duration_days = s.composerPollDurationDays
            )
        } else null

        val status = if (isDraft) "draft" else if (s.composerScheduledAt != null) "scheduled" else "published"

        val request = CreateGroupPostRequest(
            content = s.composerContent,
            body = s.composerContent,
            type = s.composerType,
            media_urls = s.composerMediaUrls,
            poll_data = pollData,
            is_pinned = s.composerIsPinned,
            status = status,
            scheduled_at = s.composerScheduledAt
        )

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            createGroupPostUseCase(s.groupId, request).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isSubmitting = false,
                                composerContent = "",
                                composerMediaUrls = emptyList(),
                                composerPollQuestion = "",
                                composerPollOptions = listOf("", ""),
                                composerScheduledAt = null,
                                actionMessage = if (status == "draft") "Draft saved" else "Post created successfully"
                            )
                        }
                        loadFeed(s.groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(isSubmitting = false, errorMessage = result.exception.message ?: "Failed to create post")
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun togglePin(postId: Long) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            togglePinGroupPostUseCase(groupId, postId).collect { result ->
                if (result is Result.Success) {
                    loadFeed(groupId)
                }
            }
        }
    }

    private fun deletePost(postId: Long) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            deleteGroupPostUseCase(groupId, postId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(deleteConfirmPostId = null, actionMessage = "Post deleted") }
                    loadFeed(groupId)
                }
            }
        }
    }

    private fun votePoll(postId: Long, optionId: Int) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            voteGroupPostPollUseCase(groupId, postId, optionId).collect { result ->
                if (result is Result.Success) {
                    loadFeed(groupId)
                }
            }
        }
    }

    private fun reactPost(postId: Long, reactionType: String) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            reactGroupPostUseCase(groupId, postId, reactionType).collect { result ->
                if (result is Result.Success) {
                    loadFeed(groupId)
                }
            }
        }
    }

    private fun approvePendingPost(postId: Long) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            approveGroupPendingPostUseCase(groupId, postId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(actionMessage = "Post approved and published") }
                    loadPendingPosts(groupId)
                    loadFeed(groupId)
                }
            }
        }
    }

    private fun rejectPendingPost(postId: Long, reason: String) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            rejectGroupPendingPostUseCase(groupId, postId, reason).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(rejectDialogPostId = null, actionMessage = "Post rejected") }
                    loadPendingPosts(groupId)
                }
            }
        }
    }

    private fun reportPost(postId: Long, category: String, description: String?) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            reportGroupPostUseCase(groupId, postId, category, description).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(reportDialogPostId = null, actionMessage = "Report submitted to moderators") }
                }
            }
        }
    }

    private fun actionReport(reportId: Long, status: String, decisionNote: String?, removeContent: Boolean) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            actionGroupReportUseCase(groupId, reportId, status, decisionNote, removeContent).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(actionMessage = "Report actioned successfully") }
                    loadReports(groupId)
                    if (removeContent) loadFeed(groupId)
                }
            }
        }
    }

    private fun createAnnouncement(title: String, content: String, isPinned: Boolean, expiresAt: String?) {
        val groupId = _uiState.value.groupId
        viewModelScope.launch {
            groupsRepository.createGroupAnnouncement(groupId, "$title: $content").collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(actionMessage = "Announcement published") }
                    loadAnnouncements(groupId)
                }
            }
        }
    }
}

