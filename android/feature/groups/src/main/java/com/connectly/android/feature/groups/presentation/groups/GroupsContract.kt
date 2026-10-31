package com.connectly.android.feature.groups.presentation.groups

import com.connectly.android.core.model.Group

data class GroupsState(
    val selectedTab: String = "discover", // "discover", "your_groups", "manage"
    val featuredGroup: Group? = null,
    val popularGroups: List<Group> = emptyList(),
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface GroupsIntent {
    data class SelectTab(val tab: String) : GroupsIntent
    data class ToggleJoin(val group: Group) : GroupsIntent
    data object Refresh : GroupsIntent
}
