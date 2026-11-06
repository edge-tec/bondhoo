package com.connectly.android.feature.groups.presentation.members

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.BulkMemberActionRequest
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import com.connectly.android.feature.groups.domain.usecase.*
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupMembersViewModel @Inject constructor(
    private val repository: GroupsRepository,
    private val getGroupMembersDetailedUseCase: GetGroupMembersDetailedUseCase,
    private val restrictGroupMemberUseCase: RestrictGroupMemberUseCase,
    private val unrestrictGroupMemberUseCase: UnrestrictGroupMemberUseCase,
    private val getMemberActivityUseCase: GetMemberActivityUseCase,
    private val bulkManageMembersUseCase: BulkManageMembersUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupMembersState())
    val uiState: StateFlow<GroupMembersState> = _uiState.asStateFlow()

    private var searchDebounceJob: Job? = null

    init {
        val initialGroupId = savedStateHandle.getLongOrNull("groupId", "id") ?: 0L
        if (initialGroupId > 0L) {
            _uiState.update { it.copy(groupId = initialGroupId) }
            fetchMembers(initialGroupId)
        }
    }

    fun handleIntent(intent: GroupMembersIntent) {
        when (intent) {
            is GroupMembersIntent.LoadMembers -> {
                _uiState.update { it.copy(groupId = intent.groupId) }
                fetchMembers(intent.groupId)
            }
            is GroupMembersIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
                searchDebounceJob?.cancel()
                searchDebounceJob = viewModelScope.launch {
                    delay(300)
                    fetchMembers(_uiState.value.groupId)
                }
            }
            is GroupMembersIntent.RoleFilterChanged -> {
                _uiState.update { it.copy(selectedRole = intent.role) }
                fetchMembers(_uiState.value.groupId)
            }
            is GroupMembersIntent.StatusFilterChanged -> {
                _uiState.update { it.copy(selectedStatus = intent.status) }
                fetchMembers(_uiState.value.groupId)
            }
            is GroupMembersIntent.SortChanged -> {
                _uiState.update { it.copy(selectedSort = intent.sort) }
                fetchMembers(_uiState.value.groupId)
            }
            is GroupMembersIntent.ToggleSelectMember -> {
                val current = _uiState.value.selectedMemberIds.toMutableSet()
                if (current.contains(intent.userId)) {
                    current.remove(intent.userId)
                } else {
                    current.add(intent.userId)
                }
                _uiState.update { it.copy(selectedMemberIds = current) }
            }
            is GroupMembersIntent.SelectAllMembers -> {
                val allIds = _uiState.value.members.map { it.id }.toSet()
                _uiState.update { it.copy(selectedMemberIds = allIds) }
            }
            is GroupMembersIntent.ClearSelection -> {
                _uiState.update { it.copy(selectedMemberIds = emptySet()) }
            }
            is GroupMembersIntent.InspectMemberActivity -> {
                fetchMemberActivity(_uiState.value.groupId, intent.userId)
            }
            is GroupMembersIntent.DismissMemberActivity -> {
                _uiState.update { it.copy(inspectingMemberActivity = null) }
            }
            is GroupMembersIntent.ChangeMemberRole -> {
                changeRole(_uiState.value.groupId, intent.userId, intent.newRole)
            }
            is GroupMembersIntent.RestrictMember -> {
                restrictMember(_uiState.value.groupId, intent.userId, intent.durationDays, intent.reason)
            }
            is GroupMembersIntent.UnrestrictMember -> {
                unrestrictMember(_uiState.value.groupId, intent.userId)
            }
            is GroupMembersIntent.RemoveMember -> {
                removeMember(_uiState.value.groupId, intent.userId)
            }
            is GroupMembersIntent.BanMember -> {
                banMember(_uiState.value.groupId, intent.userId, intent.reason)
            }
            is GroupMembersIntent.ExecuteBulkAction -> {
                executeBulkAction(intent.action, intent.role, intent.reason, intent.durationDays)
            }
            is GroupMembersIntent.OpenRoleDialog -> {
                _uiState.update { it.copy(showRoleDialogForUser = intent.member) }
            }
            is GroupMembersIntent.OpenRestrictDialog -> {
                _uiState.update { it.copy(showRestrictDialogForUser = intent.member) }
            }
            is GroupMembersIntent.OpenBanDialog -> {
                _uiState.update { it.copy(showBanConfirmDialogForUser = intent.member) }
            }
            is GroupMembersIntent.OpenRemoveDialog -> {
                _uiState.update { it.copy(showRemoveConfirmDialogForUser = intent.member) }
            }
            is GroupMembersIntent.OpenBulkDialog -> {
                _uiState.update { it.copy(showBulkActionDialog = intent.actionType) }
            }
            is GroupMembersIntent.DismissDialogs -> {
                _uiState.update {
                    it.copy(
                        showRoleDialogForUser = null,
                        showRestrictDialogForUser = null,
                        showBanConfirmDialogForUser = null,
                        showRemoveConfirmDialogForUser = null,
                        showBulkActionDialog = null
                    )
                }
            }
            is GroupMembersIntent.DismissMessage -> {
                _uiState.update { it.copy(actionMessage = null, errorMessage = null) }
            }
        }
    }

    private fun fetchMembers(groupId: Long) {
        if (groupId <= 0L) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            getGroupMembersDetailedUseCase(
                groupId = groupId,
                query = _uiState.value.searchQuery.ifBlank { null },
                role = _uiState.value.selectedRole,
                status = _uiState.value.selectedStatus,
                sort = _uiState.value.selectedSort
            ).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                members = result.data,
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = result.exception.message ?: "Failed to load members"
                            )
                        }
                    }
                }
            }
        }
    }

    private fun fetchMemberActivity(groupId: Long, userId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingActivity = true) }
            getMemberActivityUseCase(groupId, userId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoadingActivity = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoadingActivity = false,
                                inspectingMemberActivity = result.data
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoadingActivity = false,
                                errorMessage = result.exception.message ?: "Failed to load member activity"
                            )
                        }
                    }
                }
            }
        }
    }

    private fun changeRole(groupId: Long, userId: Long, newRole: String) {
        viewModelScope.launch {
            repository.updateGroupMemberRole(groupId, userId, newRole).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                showRoleDialogForUser = null,
                                actionMessage = "Role updated to $newRole successfully"
                            )
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                errorMessage = result.exception.message ?: "Failed to update role"
                            )
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun restrictMember(groupId: Long, userId: Long, durationDays: Int, reason: String) {
        viewModelScope.launch {
            restrictGroupMemberUseCase(groupId, userId, durationDays, reason).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                showRestrictDialogForUser = null,
                                actionMessage = "Member restricted for $durationDays days"
                            )
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to restrict member")
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun unrestrictMember(groupId: Long, userId: Long) {
        viewModelScope.launch {
            unrestrictGroupMemberUseCase(groupId, userId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(actionMessage = "Restriction removed successfully")
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to unrestrict member")
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun removeMember(groupId: Long, userId: Long) {
        viewModelScope.launch {
            repository.removeGroupMember(groupId, userId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                showRemoveConfirmDialogForUser = null,
                                actionMessage = "Member removed from group"
                            )
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to remove member")
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun banMember(groupId: Long, userId: Long, reason: String) {
        viewModelScope.launch {
            // Using bulk manage with single member to ban
            bulkManageMembersUseCase(
                groupId = groupId,
                request = BulkMemberActionRequest(
                    action = "ban",
                    user_ids = listOf(userId),
                    reason = reason
                )
            ).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                showBanConfirmDialogForUser = null,
                                actionMessage = "Member banned from group"
                            )
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to ban member")
                        }
                    }
                    else -> {}
                }
            }
        }
    }

    private fun executeBulkAction(action: String, role: String?, reason: String?, durationDays: Int) {
        val selected = _uiState.value.selectedMemberIds.toList()
        if (selected.isEmpty()) return
        val groupId = _uiState.value.groupId

        viewModelScope.launch {
            bulkManageMembersUseCase(
                groupId = groupId,
                request = BulkMemberActionRequest(
                    action = action,
                    user_ids = selected,
                    role = role,
                    reason = reason ?: "Bulk administrative action",
                    duration_days = durationDays
                )
            ).collect { result ->
                when (result) {
                    is Result.Success -> {
                        val count = result.data.processed_count
                        _uiState.update {
                            it.copy(
                                selectedMemberIds = emptySet(),
                                showBulkActionDialog = null,
                                actionMessage = "Bulk $action applied to $count members"
                            )
                        }
                        fetchMembers(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Bulk operation failed")
                        }
                    }
                    else -> {}
                }
            }
        }
    }
}

