package com.connectly.android.feature.profile.presentation.blocking

import com.connectly.android.core.network.api.RestrictedUserDto

enum class BlockedTab {
    BLOCKED,
    RESTRICTED,
    MUTED
}

enum class RestrictionActionType {
    UNBLOCK,
    UNRESTRICT,
    UNMUTE
}

data class BlockedUsersState(
    val selectedTab: BlockedTab = BlockedTab.BLOCKED,
    val searchQuery: String = "",
    val blockedUsers: List<RestrictedUserDto> = emptyList(),
    val restrictedUsers: List<RestrictedUserDto> = emptyList(),
    val mutedUsers: List<RestrictedUserDto> = emptyList(),
    val isLoading: Boolean = false,
    val isActionInProgress: Boolean = false,
    val pendingActionUser: RestrictedUserDto? = null,
    val pendingActionType: RestrictionActionType? = null,
    val successMessage: String? = null,
    val errorMessage: String? = null
) {
    val filteredList: List<RestrictedUserDto>
        get() {
            val list = when (selectedTab) {
                BlockedTab.BLOCKED -> blockedUsers
                BlockedTab.RESTRICTED -> restrictedUsers
                BlockedTab.MUTED -> mutedUsers
            }
            if (searchQuery.isBlank()) return list
            return list.filter {
                it.name.contains(searchQuery, ignoreCase = true) ||
                        it.username.contains(searchQuery, ignoreCase = true)
            }
        }
}

sealed interface BlockedUsersIntent {
    object LoadData : BlockedUsersIntent
    data class SelectTab(val tab: BlockedTab) : BlockedUsersIntent
    data class SearchQueryChanged(val query: String) : BlockedUsersIntent
    data class RequestAction(val user: RestrictedUserDto, val type: RestrictionActionType) : BlockedUsersIntent
    object DismissActionDialog : BlockedUsersIntent
    object ExecuteConfirmedAction : BlockedUsersIntent
    object DismissFeedback : BlockedUsersIntent
}

