package com.connectly.android.feature.groups.presentation.profile

import com.connectly.android.core.network.api.GroupProfileResponseDto

data class GroupProfileState(
    val slugOrId: String = "",
    val selectedTab: Int = 0, // 0 to 8 (Overview, Discussion, Announcements, Members, Events, Media, Files, About, Dashboard)
    val profileData: GroupProfileResponseDto? = null,
    val isLoading: Boolean = false,
    val isActionLoading: Boolean = false,
    val showInviteDialog: Boolean = false,
    val showReportDialog: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null
)

sealed interface GroupProfileIntent {
    data class LoadProfile(val slugOrId: String) : GroupProfileIntent
    data class SelectTab(val tabIndex: Int) : GroupProfileIntent
    data object JoinGroup : GroupProfileIntent
    data object CancelRequest : GroupProfileIntent
    data object LeaveGroup : GroupProfileIntent
    data object ToggleFollow : GroupProfileIntent
    data class InviteUsers(val userIds: List<Long>) : GroupProfileIntent
    data class ReportGroup(val reason: String, val description: String) : GroupProfileIntent
    data class ShowInviteDialog(val show: Boolean) : GroupProfileIntent
    data class ShowReportDialog(val show: Boolean) : GroupProfileIntent
    data object Refresh : GroupProfileIntent
}

