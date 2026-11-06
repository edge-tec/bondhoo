package com.connectly.android.feature.groups.presentation.roles

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.groups.domain.usecase.*
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupRolesViewModel @Inject constructor(
    private val getGroupRolesUseCase: GetGroupRolesUseCase,
    private val createCustomRoleUseCase: CreateCustomRoleUseCase,
    private val updateCustomRoleUseCase: UpdateCustomRoleUseCase,
    private val deleteCustomRoleUseCase: DeleteCustomRoleUseCase,
    private val assignGroupRoleUseCase: AssignGroupRoleUseCase,
    private val revokeGroupRoleUseCase: RevokeGroupRoleUseCase,
    private val getGroupRoleHistoryUseCase: GetGroupRoleHistoryUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupRolesState())
    val uiState: StateFlow<GroupRolesState> = _uiState.asStateFlow()

    init {
        val parsedId = savedStateHandle.getLongOrNull("groupId", "id") ?: 0L
        if (parsedId > 0) {
            _uiState.update { it.copy(groupId = parsedId) }
            loadRoles(parsedId)
        }
    }

    fun handleIntent(intent: GroupRolesIntent) {
        when (intent) {
            is GroupRolesIntent.LoadRoles -> {
                _uiState.update { it.copy(groupId = intent.groupId) }
                loadRoles(intent.groupId)
            }
            is GroupRolesIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tabIndex) }
                if (intent.tabIndex == 1 && _uiState.value.roleHistory.isEmpty()) {
                    loadRoleHistory(_uiState.value.groupId)
                }
            }
            is GroupRolesIntent.CreateRole -> createRole(intent.name, intent.displayName, intent.description, intent.color, intent.permissions)
            is GroupRolesIntent.UpdateRole -> updateRole(intent.roleId, intent.displayName, intent.description, intent.color, intent.permissions)
            is GroupRolesIntent.DeleteRole -> deleteRole(intent.roleId)
            is GroupRolesIntent.AssignRole -> assignRole(intent.role, intent.userIds, intent.permissions)
            is GroupRolesIntent.RevokeRole -> revokeRole(intent.userIds)
            is GroupRolesIntent.ShowCreateDialog -> _uiState.update { it.copy(showCreateRoleDialog = intent.show, editingRole = null) }
            is GroupRolesIntent.EditRole -> _uiState.update { it.copy(editingRole = intent.role, showCreateRoleDialog = intent.role != null) }
            is GroupRolesIntent.ShowAssignDialog -> _uiState.update { it.copy(showAssignRoleDialog = intent.show, targetRoleForAssignment = intent.roleName) }
            GroupRolesIntent.Refresh -> {
                loadRoles(_uiState.value.groupId)
                if (_uiState.value.selectedTab == 1) {
                    loadRoleHistory(_uiState.value.groupId)
                }
            }
        }
    }

    private fun loadRoles(groupId: Long) {
        if (groupId <= 0) return
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getGroupRolesUseCase(groupId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                defaultRoles = result.data.default_roles,
                                customRoles = result.data.custom_roles,
                                availablePermissions = result.data.available_permissions,
                                canManageRoles = result.data.can_manage_roles,
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = result.exception.message ?: "Failed to load roles"
                            )
                        }
                    }
                    Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                }
            }
        }
    }

    private fun loadRoleHistory(groupId: Long) {
        if (groupId <= 0) return
        viewModelScope.launch {
            getGroupRoleHistoryUseCase(groupId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(roleHistory = result.data) }
                }
            }
        }
    }

    private fun createRole(name: String, displayName: String, description: String, color: String, permissions: List<String>) {
        val groupId = _uiState.value.groupId
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            val request = CreateCustomRoleRequest(
                name = name.trim().lowercase().replace(" ", "_"),
                display_name = displayName.trim().ifBlank { null },
                description = description.trim().ifBlank { null },
                color = color,
                permissions = permissions
            )
            createCustomRoleUseCase(groupId, request).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                showCreateRoleDialog = false,
                                successMessage = "Custom role '${result.data.display_name}' created successfully"
                            )
                        }
                        loadRoles(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to create custom role"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun updateRole(roleId: Long, displayName: String, description: String, color: String, permissions: List<String>) {
        val groupId = _uiState.value.groupId
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            val request = UpdateCustomRoleRequest(
                display_name = displayName.trim().ifBlank { null },
                description = description.trim().ifBlank { null },
                color = color,
                permissions = permissions
            )
            updateCustomRoleUseCase(groupId, roleId, request).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                showCreateRoleDialog = false,
                                editingRole = null,
                                successMessage = "Role updated successfully"
                            )
                        }
                        loadRoles(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to update role"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun deleteRole(roleId: Long) {
        val groupId = _uiState.value.groupId
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            deleteCustomRoleUseCase(groupId, roleId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                successMessage = "Custom role deleted successfully. Assigned members reverted to standard Member role."
                            )
                        }
                        loadRoles(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to delete role"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun assignRole(role: String, userIds: List<Long>, permissions: List<String>?) {
        val groupId = _uiState.value.groupId
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            val request = AssignRoleRequest(
                role = role,
                user_ids = userIds,
                permissions = permissions
            )
            assignGroupRoleUseCase(groupId, request).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                showAssignRoleDialog = false,
                                successMessage = "Role '$role' assigned successfully to ${userIds.size} member(s)"
                            )
                        }
                        loadRoles(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to assign role"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun revokeRole(userIds: List<Long>) {
        val groupId = _uiState.value.groupId
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            val request = AssignRoleRequest(
                role = "member",
                user_ids = userIds
            )
            revokeGroupRoleUseCase(groupId, request).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                successMessage = "Role revoked. User(s) reset to Member."
                            )
                        }
                        loadRoles(groupId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isActionLoading = false,
                                errorMessage = result.exception.message ?: "Failed to revoke role"
                            )
                        }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }
}

