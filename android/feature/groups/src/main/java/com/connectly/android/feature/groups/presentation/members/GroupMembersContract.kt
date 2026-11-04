package com.connectly.android.feature.groups.presentation.members

import com.connectly.android.core.network.api.GroupMemberDto
import com.connectly.android.core.network.api.MemberActivityResponseDto

data class GroupMembersState(
    val groupId: Long = 0L,
    val isLoading: Boolean = false,
    val members: List<GroupMemberDto> = emptyList(),
    val searchQuery: String = "",
    val selectedRole: String? = null,
    val selectedStatus: String? = null,
    val selectedSort: String = "join_date_desc",
    val selectedMemberIds: Set<Long> = emptySet(),
    val inspectingMemberActivity: MemberActivityResponseDto? = null,
    val isLoadingActivity: Boolean = false,
    val actionMessage: String? = null,
    val errorMessage: String? = null,
    val showRoleDialogForUser: GroupMemberDto? = null,
    val showRestrictDialogForUser: GroupMemberDto? = null,
    val showBanConfirmDialogForUser: GroupMemberDto? = null,
    val showRemoveConfirmDialogForUser: GroupMemberDto? = null,
    val showBulkActionDialog: String? = null // "role", "restrict", "ban", "remove"
)

sealed interface GroupMembersIntent {
    data class LoadMembers(val groupId: Long) : GroupMembersIntent
    data class SearchQueryChanged(val query: String) : GroupMembersIntent
    data class RoleFilterChanged(val role: String?) : GroupMembersIntent
    data class StatusFilterChanged(val status: String?) : GroupMembersIntent
    data class SortChanged(val sort: String) : GroupMembersIntent
    data class ToggleSelectMember(val userId: Long) : GroupMembersIntent
    object SelectAllMembers : GroupMembersIntent
    object ClearSelection : GroupMembersIntent
    data class InspectMemberActivity(val userId: Long) : GroupMembersIntent
    object DismissMemberActivity : GroupMembersIntent
    data class ChangeMemberRole(val userId: Long, val newRole: String) : GroupMembersIntent
    data class RestrictMember(val userId: Long, val durationDays: Int, val reason: String) : GroupMembersIntent
    data class UnrestrictMember(val userId: Long) : GroupMembersIntent
    data class RemoveMember(val userId: Long) : GroupMembersIntent
    data class BanMember(val userId: Long, val reason: String) : GroupMembersIntent
    data class ExecuteBulkAction(
        val action: String, // "assign_role", "restrict", "ban", "remove"
        val role: String? = null,
        val reason: String? = null,
        val durationDays: Int = 7
    ) : GroupMembersIntent
    data class OpenRoleDialog(val member: GroupMemberDto) : GroupMembersIntent
    data class OpenRestrictDialog(val member: GroupMemberDto) : GroupMembersIntent
    data class OpenBanDialog(val member: GroupMemberDto) : GroupMembersIntent
    data class OpenRemoveDialog(val member: GroupMemberDto) : GroupMembersIntent
    data class OpenBulkDialog(val actionType: String) : GroupMembersIntent
    object DismissDialogs : GroupMembersIntent
    object DismissMessage : GroupMembersIntent
}

