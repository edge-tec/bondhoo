package com.connectly.android.feature.groups.presentation.groups

import com.connectly.android.core.model.Group
import com.connectly.android.core.model.GroupCategoryItem
import com.connectly.android.core.model.GroupFeedItem
import com.connectly.android.core.model.GroupInvitationItem

data class GroupsState(
    val selectedTab: String = "discover", // "discover", "your_groups", "manage"
    val selectedCategory: String? = null,
    val selectedSort: String = "trending",
    val selectedPrivacyFilter: String = "all",
    val featuredGroup: Group? = null,
    val myGroups: List<Group> = emptyList(),
    val groupsYouManage: List<Group> = emptyList(),
    val suggestedGroups: List<Group> = emptyList(),
    val discoverCommunities: List<Group> = emptyList(),
    val trendingGroups: List<Group> = emptyList(),
    val recentlyActiveGroups: List<Group> = emptyList(),
    val groupsYouMayLike: List<Group> = emptyList(),
    val categories: List<GroupCategoryItem> = emptyList(),
    val pendingInvitations: List<GroupInvitationItem> = emptyList(),
    val activityFeed: List<GroupFeedItem> = emptyList(),
    val searchResults: List<Group> = emptyList(),
    val popularGroups: List<Group> = emptyList(),
    val searchQuery: String = "",
    val isSearchVisible: Boolean = false,
    val isLoading: Boolean = false,
    val showCreateDialog: Boolean = false,
    val errorMessage: String? = null
)

sealed interface GroupsIntent {
    data class SelectTab(val tab: String) : GroupsIntent
    data class SelectCategory(val category: String?) : GroupsIntent
    data class SelectSort(val sort: String) : GroupsIntent
    data class SelectPrivacy(val privacy: String) : GroupsIntent
    data class ToggleJoin(val group: Group) : GroupsIntent
    data class SearchQueryChanged(val query: String) : GroupsIntent
    data object ToggleSearch : GroupsIntent
    data class CreateGroup(val name: String, val description: String, val privacy: String) : GroupsIntent
    data class SubmitCreateGroup(val request: com.connectly.android.core.network.api.CreateGroupRequest) : GroupsIntent
    data class ShowCreateDialog(val show: Boolean) : GroupsIntent
    data class AcceptInvitation(val invitationId: Long) : GroupsIntent
    data class DeclineInvitation(val invitationId: Long) : GroupsIntent
    data object Refresh : GroupsIntent
}
