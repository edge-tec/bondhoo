package com.connectly.android.feature.groups.presentation.groups

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.GroupCategoryItem
import com.connectly.android.core.model.GroupFeedItem
import com.connectly.android.core.model.GroupInvitationItem
import com.connectly.android.core.network.api.GroupCategoryDto
import com.connectly.android.core.network.api.GroupDto
import com.connectly.android.core.network.api.GroupFeedItemDto
import com.connectly.android.core.network.api.GroupInvitationDto
import com.connectly.android.feature.groups.domain.usecase.CreateGroupUseCase
import com.connectly.android.feature.groups.domain.usecase.GetFeaturedGroupUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupCategoriesUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupsHomeUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupsUseCase
import com.connectly.android.feature.groups.domain.usecase.ToggleJoinGroupUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupsViewModel @Inject constructor(
    private val getGroupsUseCase: GetGroupsUseCase,
    private val getFeaturedGroupUseCase: GetFeaturedGroupUseCase,
    private val toggleJoinGroupUseCase: ToggleJoinGroupUseCase,
    private val createGroupUseCase: CreateGroupUseCase,
    private val getGroupsHomeUseCase: GetGroupsHomeUseCase? = null,
    private val getGroupCategoriesUseCase: GetGroupCategoriesUseCase? = null
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupsState())
    val uiState: StateFlow<GroupsState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: GroupsIntent) {
        when (intent) {
            is GroupsIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab) }
                performFilteredSearch()
            }
            is GroupsIntent.SelectCategory -> {
                _uiState.update { it.copy(selectedCategory = intent.category) }
                performFilteredSearch()
            }
            is GroupsIntent.SelectSort -> {
                _uiState.update { it.copy(selectedSort = intent.sort) }
                performFilteredSearch()
            }
            is GroupsIntent.SelectPrivacy -> {
                _uiState.update { it.copy(selectedPrivacyFilter = intent.privacy) }
                performFilteredSearch()
            }
            is GroupsIntent.ToggleJoin -> toggleJoin(intent.group)
            is GroupsIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
                performFilteredSearch()
            }
            GroupsIntent.ToggleSearch -> {
                _uiState.update {
                    it.copy(
                        isSearchVisible = !it.isSearchVisible,
                        searchQuery = if (it.isSearchVisible) "" else it.searchQuery
                    )
                }
                if (!_uiState.value.isSearchVisible) {
                    performFilteredSearch()
                }
            }
            is GroupsIntent.ShowCreateDialog -> {
                _uiState.update { it.copy(showCreateDialog = intent.show) }
            }
            is GroupsIntent.CreateGroup -> {
                createNewGroup(intent.name, intent.description, intent.privacy)
            }
            is GroupsIntent.SubmitCreateGroup -> {
                submitNewGroup(intent.request)
            }
            is GroupsIntent.AcceptInvitation -> {
                _uiState.update { state ->
                    state.copy(pendingInvitations = state.pendingInvitations.filter { it.id != intent.invitationId })
                }
            }
            is GroupsIntent.DeclineInvitation -> {
                _uiState.update { state ->
                    state.copy(pendingInvitations = state.pendingInvitations.filter { it.id != intent.invitationId })
                }
            }
            GroupsIntent.Refresh -> loadData()
        }
    }

    private fun loadData() {
        loadHomeFeed()
        loadCategories()
        loadFeatured()
        performFilteredSearch()
    }

    private fun loadHomeFeed() {
        getGroupsHomeUseCase?.let { useCase ->
            viewModelScope.launch {
                useCase().collect { result ->
                    if (result is Result.Success) {
                        val data = result.data
                        _uiState.update { state ->
                            state.copy(
                                featuredGroup = data.featured_group?.let { mapDtoToGroup(it) } ?: state.featuredGroup,
                                myGroups = data.my_groups.map { mapDtoToGroup(it) },
                                groupsYouManage = data.groups_you_manage.map { mapDtoToGroup(it) },
                                suggestedGroups = data.suggested_groups.map { mapDtoToGroup(it) },
                                discoverCommunities = data.discover_communities.map { mapDtoToGroup(it) },
                                trendingGroups = data.trending_groups.map { mapDtoToGroup(it) },
                                recentlyActiveGroups = data.recently_active_groups.map { mapDtoToGroup(it) },
                                groupsYouMayLike = data.groups_you_may_like.map { mapDtoToGroup(it) },
                                categories = if (data.categories.isNotEmpty()) data.categories.map { mapCategoryDto(it) } else state.categories,
                                pendingInvitations = data.pending_invitations.map { mapInvitationDto(it) },
                                activityFeed = data.activity_feed.map { mapFeedItemDto(it) }
                            )
                        }
                    }
                }
            }
        }
    }

    private fun loadCategories() {
        getGroupCategoriesUseCase?.let { useCase ->
            viewModelScope.launch {
                useCase().collect { result ->
                    if (result is Result.Success && result.data.isNotEmpty()) {
                        _uiState.update { it.copy(categories = result.data) }
                    }
                }
            }
        }
    }

    private fun loadFeatured() {
        viewModelScope.launch {
            getFeaturedGroupUseCase().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(featuredGroup = result.data) }
                }
            }
        }
    }

    private fun performFilteredSearch() {
        val state = _uiState.value
        val tab = state.selectedTab
        val category = state.selectedCategory
        val query = state.searchQuery.ifBlank { null }
        val sort = state.selectedSort

        viewModelScope.launch {
            getGroupsUseCase(
                filter = tab,
                category = category,
                query = query,
                sort = sort,
                page = 1
            ).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update {
                        when (tab) {
                            "your_groups" -> it.copy(
                                isLoading = false,
                                myGroups = result.data,
                                popularGroups = result.data,
                                searchResults = result.data
                            )
                            "manage" -> it.copy(
                                isLoading = false,
                                groupsYouManage = result.data,
                                popularGroups = result.data,
                                searchResults = result.data
                            )
                            else -> it.copy(
                                isLoading = false,
                                popularGroups = result.data,
                                searchResults = result.data
                            )
                        }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(isLoading = false, errorMessage = result.exception.message)
                    }
                }
            }
        }
    }

    private fun toggleJoin(group: Group) {
        val newJoined = !group.isMember
        val memberDelta = if (newJoined) 1 else -1
        val newCount = maxOf(0, group.membersCount + memberDelta)
        val newFormatted = "$newCount members"

        _uiState.update { state ->
            val updateGroupFn: (Group) -> Group = { g ->
                if (g.id == group.id) {
                    g.copy(
                        isMember = newJoined,
                        membersCount = newCount,
                        formattedMembersCount = newFormatted
                    )
                } else g
            }

            val updatedFeatured = state.featuredGroup?.let(updateGroupFn)
            val updatedPopular = state.popularGroups.map(updateGroupFn)
            val updatedSuggested = state.suggestedGroups.map(updateGroupFn)
            val updatedDiscover = state.discoverCommunities.map(updateGroupFn)
            val updatedTrending = state.trendingGroups.map(updateGroupFn)
            val updatedRecentlyActive = state.recentlyActiveGroups.map(updateGroupFn)
            val updatedGroupsYouManage = state.groupsYouManage.map(updateGroupFn)
            val updatedSearchResults = state.searchResults.map(updateGroupFn)

            val updatedMyGroups = if (newJoined) {
                if (state.myGroups.none { it.id == group.id }) {
                    listOf(group.copy(isMember = true, membersCount = newCount, formattedMembersCount = newFormatted)) + state.myGroups
                } else state.myGroups.map(updateGroupFn)
            } else {
                state.myGroups.filter { it.id != group.id }
            }

            state.copy(
                featuredGroup = updatedFeatured,
                popularGroups = updatedPopular,
                suggestedGroups = updatedSuggested,
                discoverCommunities = updatedDiscover,
                trendingGroups = updatedTrending,
                recentlyActiveGroups = updatedRecentlyActive,
                groupsYouManage = updatedGroupsYouManage,
                searchResults = updatedSearchResults,
                myGroups = updatedMyGroups
            )
        }

        viewModelScope.launch {
            toggleJoinGroupUseCase(group.id, group.isMember).collect { result ->
                if (result is Result.Error) {
                    _uiState.update { state ->
                        val revertGroupFn: (Group) -> Group = { g ->
                            if (g.id == group.id) {
                                g.copy(
                                    isMember = group.isMember,
                                    membersCount = group.membersCount,
                                    formattedMembersCount = group.formattedMembersCount
                                )
                            } else g
                        }
                        state.copy(
                            featuredGroup = state.featuredGroup?.let(revertGroupFn),
                            popularGroups = state.popularGroups.map(revertGroupFn),
                            suggestedGroups = state.suggestedGroups.map(revertGroupFn),
                            discoverCommunities = state.discoverCommunities.map(revertGroupFn),
                            trendingGroups = state.trendingGroups.map(revertGroupFn),
                            recentlyActiveGroups = state.recentlyActiveGroups.map(revertGroupFn),
                            groupsYouManage = state.groupsYouManage.map(revertGroupFn),
                            searchResults = state.searchResults.map(revertGroupFn),
                            myGroups = if (group.isMember) {
                                if (state.myGroups.none { it.id == group.id }) state.myGroups + group else state.myGroups
                            } else {
                                state.myGroups.filter { it.id != group.id }
                            }
                        )
                    }
                }
            }
        }
    }

    private fun createNewGroup(name: String, description: String, privacy: String) {
        viewModelScope.launch {
            createGroupUseCase(name, description, privacy).collect { result ->
                if (result is Result.Success) {
                    val created = result.data.copy(isMember = true, isAdmin = true)
                    _uiState.update {
                        it.copy(
                            myGroups = listOf(created) + it.myGroups,
                            groupsYouManage = listOf(created) + it.groupsYouManage,
                            popularGroups = listOf(created) + it.popularGroups,
                            showCreateDialog = false
                        )
                    }
                }
            }
        }
    }

    private fun submitNewGroup(request: com.connectly.android.core.network.api.CreateGroupRequest) {
        viewModelScope.launch {
            createGroupUseCase(request).collect { result ->
                if (result is Result.Success) {
                    val created = result.data.copy(isMember = true, isAdmin = true)
                    _uiState.update {
                        it.copy(
                            myGroups = listOf(created) + it.myGroups,
                            groupsYouManage = listOf(created) + it.groupsYouManage,
                            popularGroups = listOf(created) + it.popularGroups,
                            showCreateDialog = false
                        )
                    }
                }
            }
        }
    }

    private fun mapDtoToGroup(dto: GroupDto): Group {
        return Group(
            id = dto.id,
            name = dto.name,
            slug = dto.slug,
            description = dto.description,
            coverImage = dto.resolvedCoverImage,
            avatarImage = dto.resolvedAvatar,
            membersCount = dto.members_count,
            formattedMembersCount = "${dto.members_count} members",
            isMember = dto.is_member,
            isAdmin = dto.is_admin,
            isPrivate = dto.privacy == "private" || dto.privacy == "hidden",
            category = dto.category
        )
    }

    private fun mapCategoryDto(dto: GroupCategoryDto): GroupCategoryItem {
        return GroupCategoryItem(
            id = dto.id,
            name = dto.name,
            slug = dto.slug,
            icon = dto.icon ?: "folder-outline",
            description = dto.description,
            groupsCount = dto.groups_count
        )
    }

    private fun mapInvitationDto(dto: GroupInvitationDto): GroupInvitationItem {
        return GroupInvitationItem(
            id = dto.id,
            groupId = dto.group_id,
            groupName = dto.group_name ?: "",
            groupAvatar = dto.group_avatar,
            groupMembersCount = dto.group_members_count,
            inviterName = dto.inviter_name ?: "",
            role = dto.role ?: "member",
            createdAt = dto.created_at
        )
    }

    private fun mapFeedItemDto(dto: GroupFeedItemDto): GroupFeedItem {
        return GroupFeedItem(
            id = dto.id,
            content = dto.content ?: "",
            type = dto.type ?: "post",
            createdAt = dto.created_at,
            likesCount = dto.likes_count,
            commentsCount = dto.comments_count,
            authorName = dto.author?.name ?: "",
            authorAvatar = dto.author?.avatar,
            groupName = dto.group?.name ?: "",
            groupAvatar = dto.group?.avatar,
            groupId = dto.group?.id
        )
    }
}
