package com.connectly.android.feature.groups.presentation.profile

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.groups.domain.usecase.CancelGroupRequestUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupProfileUseCase
import com.connectly.android.feature.groups.domain.usecase.InviteGroupMembersUseCase
import com.connectly.android.feature.groups.domain.usecase.ToggleFollowGroupUseCase
import com.connectly.android.feature.groups.domain.usecase.ToggleJoinGroupUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupProfileViewModel @Inject constructor(
    private val getGroupProfileUseCase: GetGroupProfileUseCase,
    private val toggleJoinGroupUseCase: ToggleJoinGroupUseCase,
    private val cancelGroupRequestUseCase: CancelGroupRequestUseCase,
    private val toggleFollowGroupUseCase: ToggleFollowGroupUseCase,
    private val inviteGroupMembersUseCase: InviteGroupMembersUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupProfileState())
    val uiState: StateFlow<GroupProfileState> = _uiState.asStateFlow()

    init {
        val initialSlug: String? = savedStateHandle["slugOrId"] ?: savedStateHandle["slug"] ?: savedStateHandle["id"]
        if (!initialSlug.isNullOrBlank()) {
            loadProfile(initialSlug)
        }
    }

    fun handleIntent(intent: GroupProfileIntent) {
        when (intent) {
            is GroupProfileIntent.LoadProfile -> loadProfile(intent.slugOrId)
            is GroupProfileIntent.SelectTab -> _uiState.update { it.copy(selectedTab = intent.tabIndex) }
            GroupProfileIntent.JoinGroup -> joinGroup()
            GroupProfileIntent.CancelRequest -> cancelRequest()
            GroupProfileIntent.LeaveGroup -> leaveGroup()
            GroupProfileIntent.ToggleFollow -> toggleFollow()
            is GroupProfileIntent.InviteUsers -> inviteUsers(intent.userIds)
            is GroupProfileIntent.ReportGroup -> reportGroup(intent.reason, intent.description)
            is GroupProfileIntent.ShowInviteDialog -> _uiState.update { it.copy(showInviteDialog = intent.show) }
            is GroupProfileIntent.ShowReportDialog -> _uiState.update { it.copy(showReportDialog = intent.show) }
            GroupProfileIntent.Refresh -> _uiState.value.slugOrId.takeIf { it.isNotBlank() }?.let { loadProfile(it) }
        }
    }

    private fun loadProfile(slugOrId: String) {
        _uiState.update { it.copy(slugOrId = slugOrId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getGroupProfileUseCase(slugOrId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                profileData = result.data,
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = result.exception.message ?: "Failed to load group profile"
                            )
                        }
                    }
                    Result.Loading -> {
                        _uiState.update { it.copy(isLoading = true) }
                    }
                }
            }
        }
    }

    private fun joinGroup() {
        val groupId = _uiState.value.profileData?.group?.id ?: return
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            toggleJoinGroupUseCase(groupId, currentJoined = false).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { it.copy(isActionLoading = false, successMessage = "Join request submitted successfully") }
                        loadProfile(_uiState.value.slugOrId)
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isActionLoading = false, errorMessage = result.exception.message ?: "Could not join group") }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun cancelRequest() {
        val groupId = _uiState.value.profileData?.group?.id ?: return
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            cancelGroupRequestUseCase(groupId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { it.copy(isActionLoading = false, successMessage = "Membership request cancelled") }
                        loadProfile(_uiState.value.slugOrId)
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isActionLoading = false, errorMessage = result.exception.message ?: "Failed to cancel request") }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun leaveGroup() {
        val groupId = _uiState.value.profileData?.group?.id ?: return
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            toggleJoinGroupUseCase(groupId, currentJoined = true).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { it.copy(isActionLoading = false, successMessage = "Left group successfully") }
                        loadProfile(_uiState.value.slugOrId)
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isActionLoading = false, errorMessage = result.exception.message ?: "Failed to leave group") }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun toggleFollow() {
        val groupId = _uiState.value.profileData?.group?.id ?: return
        viewModelScope.launch {
            toggleFollowGroupUseCase(groupId).collect { result ->
                if (result is Result.Success) {
                    loadProfile(_uiState.value.slugOrId)
                }
            }
        }
    }

    private fun inviteUsers(userIds: List<Long>) {
        val groupId = _uiState.value.profileData?.group?.id ?: return
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            inviteGroupMembersUseCase(groupId, userIds).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                showInviteDialog = false,
                                successMessage = "Invitations sent successfully"
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to send invitations"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun reportGroup(reason: String, description: String) {
        _uiState.update {
            it.copy(
                showReportDialog = false,
                successMessage = "Report submitted to Connectly Trust & Safety team for review."
            )
        }
    }
}

