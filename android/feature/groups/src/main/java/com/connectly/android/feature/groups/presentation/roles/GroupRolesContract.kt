package com.connectly.android.feature.groups.presentation.roles

import com.connectly.android.core.network.api.*

data class GroupRolesState(
    val groupId: Long = 0,
    val selectedTab: Int = 0, // 0: Roles Catalog, 1: Audit History
    val defaultRoles: List<GroupRoleDto> = emptyList(),
    val customRoles: List<GroupRoleDto> = emptyList(),
    val availablePermissions: List<PermissionCategoryDto> = emptyList(),
    val canManageRoles: Boolean = false,
    val roleHistory: List<RoleHistoryItemDto> = emptyList(),
    val isLoading: Boolean = false,
    val isActionLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val showCreateRoleDialog: Boolean = false,
    val editingRole: GroupRoleDto? = null,
    val showAssignRoleDialog: Boolean = false,
    val targetRoleForAssignment: String? = null
)

sealed interface GroupRolesIntent {
    data class LoadRoles(val groupId: Long) : GroupRolesIntent
    data class SelectTab(val tabIndex: Int) : GroupRolesIntent
    data class CreateRole(val name: String, val displayName: String, val description: String, val color: String, val permissions: List<String>) : GroupRolesIntent
    data class UpdateRole(val roleId: Long, val displayName: String, val description: String, val color: String, val permissions: List<String>) : GroupRolesIntent
    data class DeleteRole(val roleId: Long) : GroupRolesIntent
    data class AssignRole(val role: String, val userIds: List<Long>, val permissions: List<String>? = null) : GroupRolesIntent
    data class RevokeRole(val userIds: List<Long>) : GroupRolesIntent
    data class ShowCreateDialog(val show: Boolean) : GroupRolesIntent
    data class EditRole(val role: GroupRoleDto?) : GroupRolesIntent
    data class ShowAssignDialog(val show: Boolean, val roleName: String? = null) : GroupRolesIntent
    data object Refresh : GroupRolesIntent
}

