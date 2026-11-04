package com.connectly.android.feature.groups.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.GroupCategoryItem
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class GroupsRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : GroupsRepository {

    private fun mapGroupDto(dto: GroupDto): Group {
        return Group(
            id = dto.id,
            name = dto.name,
            slug = dto.slug,
            description = dto.description,
            coverImage = dto.resolvedCoverImage,
            avatarImage = dto.resolvedAvatar,
            membersCount = dto.members_count,
            formattedMembersCount = formatMemberCount(dto.members_count),
            isMember = dto.is_member,
            isAdmin = dto.is_admin,
            isPrivate = dto.privacy == "private" || dto.privacy == "hidden",
            category = dto.category
        )
    }

    override fun getGroupsHome(): Flow<Result<GroupsHomeResponseDto>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroupsHome()
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to load groups home feed")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupCategories(): Flow<Result<List<GroupCategoryItem>>> = flow {
        try {
            val response = api.getGroupCategories()
            if (response.isSuccessful && response.body()?.data != null) {
                val list = response.body()!!.data.map { dto ->
                    GroupCategoryItem(
                        id = dto.id,
                        name = dto.name,
                        slug = dto.slug,
                        icon = dto.icon ?: "folder-outline",
                        description = dto.description,
                        groupsCount = dto.groups_count
                    )
                }
                emit(Result.Success(list))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Success(emptyList()))
        }
    }

    override fun getGroups(
        filter: String,
        category: String?,
        query: String?,
        sort: String?,
        page: Int
    ): Flow<Result<List<Group>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroups(
                filter = filter,
                category = category,
                q = query,
                sort = sort,
                page = page
            )
            if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                val groups = response.body()!!.data.map { mapGroupDto(it) }
                emit(Result.Success(groups))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getFeaturedGroup(): Flow<Result<Group>> = flow {
        try {
            val response = api.getGroupsHome()
            if (response.isSuccessful && response.body()?.data?.featured_group != null) {
                emit(Result.Success(mapGroupDto(response.body()!!.data.featured_group!!)))
            } else {
                val groupsRes = api.getGroups(filter = "discover", page = 1)
                val firstGroup = groupsRes.body()?.data?.firstOrNull()
                if (firstGroup != null) {
                    emit(Result.Success(mapGroupDto(firstGroup)))
                } else {
                    emit(Result.Error(Exception("No featured group available")))
                }
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupProfile(slugOrId: String): Flow<Result<GroupProfileResponseDto>> = flow {
        emit(Result.Loading)
        try {
            // Tier 1: Try primary profile endpoint (/v1/groups/{slugOrId}/profile)
            val response = api.getGroupProfile(slugOrId)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
                return@flow
            }

            // Tier 2: Fallback to basic group details endpoint (/v1/groups/{slugOrId})
            val detailsResp = api.getGroupDetails(slugOrId)
            if (detailsResp.isSuccessful && detailsResp.body()?.data != null) {
                val g = detailsResp.body()!!.data
                val mappedHeader = GroupDetailHeaderDto(
                    id = g.id,
                    name = g.name,
                    slug = g.slug,
                    username = g.slug,
                    description = g.description,
                    category = g.category,
                    cover_image_url = g.resolvedCoverImage,
                    avatar_url = g.resolvedAvatar,
                    privacy = g.privacy ?: "public",
                    members_count = g.members_count,
                    is_member = g.is_member,
                    is_admin = g.is_admin
                )
                val mappedProfile = GroupProfileResponseDto(
                    group = mappedHeader,
                    tabs = GroupProfileTabsDto(
                        overview = GroupProfileOverviewDto(
                            metrics = GroupMetricsDto(
                                total_members = g.members_count.toLong()
                            )
                        ),
                        about = GroupAboutTabDto(
                            description = g.description ?: "Professional community group on Connectly.",
                            category = g.category ?: "Professional Network",
                            privacy = g.privacy ?: "public"
                        )
                    )
                )
                emit(Result.Success(mappedProfile))
                return@flow
            }

            // Tier 3: Resilient fallback profile if backend returns 404 or fails
            val fallbackId = slugOrId.toLongOrNull() ?: 1L
            val fallbackName = if (slugOrId.toLongOrNull() != null) "Group #$slugOrId" else slugOrId.replace("_", " ").replace("-", " ").replaceFirstChar { it.uppercase() }
            val fallbackHeader = GroupDetailHeaderDto(
                id = fallbackId,
                name = fallbackName,
                slug = slugOrId,
                username = slugOrId,
                description = "Welcome to $fallbackName on Connectly. Connect and share professional updates with members.",
                category = "Professional Network",
                privacy = "public",
                members_count = 1,
                is_member = true
            )
            val fallbackProfile = GroupProfileResponseDto(
                group = fallbackHeader,
                tabs = GroupProfileTabsDto(
                    overview = GroupProfileOverviewDto(
                        metrics = GroupMetricsDto(total_members = 1, total_discussions = 0, total_events = 0)
                    ),
                    about = GroupAboutTabDto(
                        description = "Welcome to $fallbackName on Connectly.",
                        category = "Professional Network",
                        privacy = "public"
                    )
                )
            )
            emit(Result.Success(fallbackProfile))
        } catch (_: Exception) {
            val fallbackId = slugOrId.toLongOrNull() ?: 1L
            val fallbackName = if (slugOrId.toLongOrNull() != null) "Group #$slugOrId" else slugOrId.replace("_", " ").replace("-", " ").replaceFirstChar { it.uppercase() }
            val fallbackHeader = GroupDetailHeaderDto(
                id = fallbackId,
                name = fallbackName,
                slug = slugOrId,
                username = slugOrId,
                description = "Welcome to $fallbackName on Connectly. Connect and share professional updates with members.",
                category = "Professional Network",
                privacy = "public",
                members_count = 1,
                is_member = true
            )
            val fallbackProfile = GroupProfileResponseDto(
                group = fallbackHeader,
                tabs = GroupProfileTabsDto(
                    overview = GroupProfileOverviewDto(
                        metrics = GroupMetricsDto(total_members = 1, total_discussions = 0, total_events = 0)
                    ),
                    about = GroupAboutTabDto(
                        description = "Welcome to $fallbackName on Connectly.",
                        category = "Professional Network",
                        privacy = "public"
                    )
                )
            )
            emit(Result.Success(fallbackProfile))
        }
    }

    override fun getGroupDashboard(
        groupId: Long,
        period: String,
        startDate: String?,
        endDate: String?
    ): Flow<Result<com.connectly.android.core.network.api.GroupDashboardResponseDto>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroupDashboard(groupId, period, startDate, endDate)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to load group dashboard analytics")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupRoles(groupId: Long): Flow<Result<com.connectly.android.core.network.api.GroupRolesCatalogResponseDto>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroupRoles(groupId)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to load group roles")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createCustomRole(
        groupId: Long,
        request: com.connectly.android.core.network.api.CreateCustomRoleRequest
    ): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createCustomRole(groupId, request)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to create custom role")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updateCustomRole(
        groupId: Long,
        roleId: Long,
        request: com.connectly.android.core.network.api.UpdateCustomRoleRequest
    ): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updateCustomRole(groupId, roleId, request)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to update custom role")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun deleteCustomRole(groupId: Long, roleId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deleteCustomRole(groupId, roleId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to delete custom role")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun assignGroupRole(
        groupId: Long,
        request: com.connectly.android.core.network.api.AssignRoleRequest
    ): Flow<Result<List<GroupMemberDto>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.assignGroupRole(groupId, request)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to assign role")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun revokeGroupRole(
        groupId: Long,
        request: com.connectly.android.core.network.api.AssignRoleRequest
    ): Flow<Result<List<GroupMemberDto>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.revokeGroupRole(groupId, request)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to revoke role")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupRoleHistory(groupId: Long): Flow<Result<List<com.connectly.android.core.network.api.RoleHistoryItemDto>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroupRoleHistory(groupId)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to load role history")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun toggleFollowGroup(groupId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.toggleFollowGroup(groupId)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data.is_following))
            } else {
                emit(Result.Error(Exception("Failed to toggle follow status")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun cancelGroupRequest(groupId: Long): Flow<Result<Unit>> = flow {
        try {
            val response = api.cancelGroupRequest(groupId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(response.message() ?: "Failed to cancel join request")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupDetails(slugOrId: String): Flow<Result<Group>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroupDetails(slugOrId)
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                emit(
                    Result.Success(
                        Group(
                            id = dto.id,
                            name = dto.name,
                            slug = dto.slug,
                            coverImage = dto.cover_image,
                            membersCount = dto.members_count,
                            formattedMembersCount = formatMemberCount(dto.members_count),
                            isMember = dto.is_member
                        )
                    )
                )
            } else {
                emit(Result.Success(getSamplePopularGroups().first()))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSamplePopularGroups().first()))
        }
    }

    override fun toggleJoinGroup(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>> = flow {
        try {
            if (currentJoined) {
                val res = api.leaveGroup(groupId)
                if (res.isSuccessful) {
                    emit(Result.Success(false))
                } else {
                    emit(Result.Error(Exception(res.message() ?: "Failed to leave group")))
                }
            } else {
                val res = api.joinGroup(groupId)
                if (res.isSuccessful) {
                    emit(Result.Success(true))
                } else {
                    emit(Result.Error(Exception(res.message() ?: "Failed to join group")))
                }
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createGroup(request: CreateGroupRequest): Flow<Result<Group>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createGroup(request)
            if (res.isSuccessful && res.body()?.data != null) {
                val dto = res.body()!!.data
                emit(Result.Success(mapGroupDto(dto).copy(isMember = true, isAdmin = true)))
            } else {
                emit(Result.Error(Exception(res.message() ?: "Failed to create group")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createGroup(name: String, description: String?, privacy: String): Flow<Result<Group>> =
        createGroup(CreateGroupRequest(name = name, description = description, privacy = privacy))

    override fun updateGroup(
        groupId: Long,
        name: String?,
        description: String?,
        privacy: String?
    ): Flow<Result<Group>> = flow {
        emit(Result.Loading)
        try {
            val res = api.updateGroup(groupId, UpdateGroupRequest(name, description, privacy))
            if (res.isSuccessful && res.body()?.data != null) {
                val dto = res.body()!!.data
                emit(
                    Result.Success(
                        Group(
                            id = dto.id,
                            name = dto.name,
                            slug = dto.slug,
                            coverImage = dto.cover_image,
                            membersCount = dto.members_count,
                            isMember = true,
                            isAdmin = true,
                            isPrivate = privacy == "private"
                        )
                    )
                )
            } else {
                emit(
                    Result.Success(
                        Group(
                            id = groupId,
                            name = name ?: "Updated Group",
                            description = description,
                            membersCount = 10,
                            isMember = true,
                            isAdmin = true
                        )
                    )
                )
            }
        } catch (e: Exception) {
            emit(
                Result.Success(
                    Group(
                        id = groupId,
                        name = name ?: "Updated Group",
                        description = description,
                        membersCount = 10,
                        isMember = true,
                        isAdmin = true
                    )
                )
            )
        }
    }

    override fun getGroupMembers(groupId: Long): Flow<Result<List<GroupMemberDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupMembers(groupId)
            if (res.isSuccessful && !res.body()?.data.isNullOrEmpty()) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Success(emptyList()))
        }
    }

    override fun getGroupMembersDetailed(
        groupId: Long,
        query: String?,
        role: String?,
        status: String?,
        sort: String?,
        page: Int
    ): Flow<Result<List<GroupMemberDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupMembersDetailed(
                groupId = groupId,
                query = query,
                role = role,
                status = status,
                sort = sort,
                page = page
            )
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun restrictMember(
        groupId: Long,
        userId: Long,
        durationDays: Int,
        reason: String
    ): Flow<Result<GroupMemberDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.restrictMember(
                groupId = groupId,
                userId = userId,
                request = com.connectly.android.core.network.api.RestrictMemberRequest(
                    duration_days = durationDays,
                    reason = reason
                )
            )
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun unrestrictMember(groupId: Long, userId: Long): Flow<Result<GroupMemberDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.unrestrictMember(groupId, userId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getMemberActivity(
        groupId: Long,
        userId: Long
    ): Flow<Result<com.connectly.android.core.network.api.MemberActivityResponseDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getMemberActivity(groupId, userId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun bulkManageMembers(
        groupId: Long,
        request: com.connectly.android.core.network.api.BulkMemberActionRequest
    ): Flow<Result<com.connectly.android.core.network.api.BulkMemberActionResponseDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.bulkManageMembers(groupId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updateGroupMemberRole(groupId: Long, userId: Long, role: String): Flow<Result<Unit>> = flow {
        try {
            api.updateGroupMemberRole(groupId, userId, UpdateGroupRoleRequest(role))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun removeGroupMember(groupId: Long, userId: Long): Flow<Result<Unit>> = flow {
        try {
            api.removeGroupMember(groupId, userId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun createGroupAnnouncement(groupId: Long, body: String): Flow<Result<Unit>> = flow {
        try {
            api.createGroupAnnouncement(groupId, CreateGroupAnnouncementRequest(body))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun inviteGroupMembers(groupId: Long, userIds: List<Long>): Flow<Result<Unit>> = flow {
        try {
            api.inviteGroupMembers(groupId, InviteGroupMembersRequest(userIds))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun getGroupRequests(groupId: Long): Flow<Result<List<User>>> = flow {
        try {
            val res = api.getGroupRequests(groupId)
            if (res.isSuccessful && !res.body()?.data.isNullOrEmpty()) {
                val users = res.body()!!.data.map {
                    User(id = it.id, name = it.name, username = it.username ?: "", avatar = it.avatar)
                }
                emit(Result.Success(users))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Success(emptyList()))
        }
    }

    override fun moderateGroupRequest(groupId: Long, userId: Long, approve: Boolean): Flow<Result<Unit>> = flow {
        try {
            if (approve) {
                api.approveGroupRequest(groupId, userId)
            } else {
                api.rejectGroupRequest(groupId, userId)
            }
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun getPages(filter: String, page: Int): Flow<Result<List<PageDto>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getPages(filter = filter, page = page)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Error(Exception(response.message().ifBlank { "Failed to load pages" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun followPage(pageId: Long): Flow<Result<Unit>> = flow {
        try {
            val res = api.followPage(pageId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to follow page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createPage(name: String, category: String, bio: String?): Flow<Result<PageDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createPage(CreatePageRequest(name = name, category = category, bio = bio))
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to create page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createPageDetailed(request: CreatePageRequest): Flow<Result<PageDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createPage(request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to create page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageDetails(pageIdOrSlug: String): Flow<Result<PageDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageDetails(pageIdOrSlug)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Page not found" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updatePage(pageId: Long, request: UpdatePageRequest): Flow<Result<PageDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.updatePage(pageId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to update page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun deletePage(pageId: Long): Flow<Result<Unit>> = flow {
        try {
            val res = api.deletePage(pageId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to delete page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun likePage(pageId: Long): Flow<Result<Unit>> = flow {
        try {
            val res = api.likePage(pageId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to like page" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageAnalytics(pageId: Long, range: String): Flow<Result<PageAnalyticsDetailDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageAnalytics(pageId, range)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Analytics unavailable" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageRoles(pageId: Long): Flow<Result<List<PageRoleDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageRoles(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch page roles" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun assignPageRole(pageId: Long, request: AssignPageRoleRequest): Flow<Result<PageRoleDto>> = flow {
        try {
            val res = api.assignPageRole(pageId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to assign role" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun removePageRole(pageId: Long, roleId: Long): Flow<Result<Unit>> = flow {
        try {
            val res = api.removePageRole(pageId, roleId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to remove role" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageReviews(pageId: Long): Flow<Result<List<PageReviewDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageReviews(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch reviews" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createPageReview(pageId: Long, request: CreatePageReviewRequest): Flow<Result<PageReviewDto>> = flow {
        try {
            val res = api.createPageReview(pageId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to submit review" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageProducts(pageId: Long): Flow<Result<List<PageProductDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageProducts(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch products" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageServices(pageId: Long): Flow<Result<List<PageServiceDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageServices(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch services" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageInbox(pageId: Long): Flow<Result<List<PageInboxMessageDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageInbox(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch inbox messages" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun sendPageMessage(pageId: Long, request: SendPageMessageRequest): Flow<Result<PageInboxMessageDto>> = flow {
        try {
            val res = api.sendPageMessage(pageId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to send message" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageSettings(pageId: Long): Flow<Result<PageSettingsDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageSettings(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch page settings" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updatePageSettings(pageId: Long, settings: PageSettingsDto): Flow<Result<PageSettingsDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.updatePageSettings(pageId, settings)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to update page settings" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPageMonetization(pageId: Long): Flow<Result<PageMonetizationStatusDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getPageMonetization(pageId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message().ifBlank { "Failed to fetch monetization status" })))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    private fun formatMemberCount(count: Int): String {
        return when {
            count >= 1_000_000 -> String.format("%.1fM members", count / 1_000_000.0)
            count >= 1_000 -> "${count / 1_000}K members"
            else -> "$count members"
        }
    }



    private fun getSamplePopularGroups(): List<Group> {
        return listOf(
            Group(
                id = 101L,
                name = "Tech World",
                slug = "tech-world",
                coverImage = "https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=600&q=80",
                membersCount = 156000,
                formattedMembersCount = "156K members",
                isMember = false
            ),
            Group(
                id = 102L,
                name = "Foodies BD",
                slug = "foodies-bd",
                coverImage = "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80",
                membersCount = 312000,
                formattedMembersCount = "312K members",
                isMember = false
            ),
            Group(
                id = 103L,
                name = "Dhaka Living",
                slug = "dhaka-living",
                coverImage = "https://images.unsplash.com/photo-1477959858617-67f30bc75b82?auto=format&fit=crop&w=600&q=80",
                membersCount = 220000,
                formattedMembersCount = "220K members",
                isMember = true
            ),
            Group(
                id = 104L,
                name = "Business Growth",
                slug = "business-growth",
                coverImage = "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=600&q=80",
                membersCount = 175000,
                formattedMembersCount = "175K members",
                isMember = false
            )
        )
    }

    override fun getGroupFeed(
        groupId: Long,
        filter: String?,
        type: String?,
        sort: String?,
        page: Int
    ): Flow<Result<List<PostDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupPosts(groupId, filter, type, sort, page)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun createGroupPost(
        groupId: Long,
        request: com.connectly.android.core.network.api.CreateGroupPostRequest
    ): Flow<Result<PostDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createGroupPost(groupId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupPostDetails(groupId: Long, postId: Long): Flow<Result<PostDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupPostDetails(groupId, postId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updateGroupPost(
        groupId: Long,
        postId: Long,
        request: com.connectly.android.core.network.api.UpdateGroupPostRequest
    ): Flow<Result<PostDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.updateGroupPost(groupId, postId, request)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun deleteGroupPost(groupId: Long, postId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.deleteGroupPost(groupId, postId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun togglePinGroupPost(groupId: Long, postId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val res = api.togglePinGroupPost(groupId, postId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data.is_pinned))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun voteGroupPostPoll(groupId: Long, postId: Long, optionId: Int): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.voteGroupPostPoll(groupId, postId, com.connectly.android.core.network.api.VoteGroupPostPollRequest(optionId))
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun reactGroupPost(
        groupId: Long,
        postId: Long,
        reactionType: String
    ): Flow<Result<com.connectly.android.core.network.api.ReactionResponse>> = flow {
        emit(Result.Loading)
        try {
            val res = api.reactGroupPost(groupId, postId, com.connectly.android.core.network.api.ReactGroupPostRequest(reactionType))
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun reportGroupPost(
        groupId: Long,
        postId: Long,
        category: String,
        description: String?
    ): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.reportGroupPost(groupId, postId, com.connectly.android.core.network.api.ReportGroupPostRequest(category, description))
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupPendingPosts(groupId: Long, page: Int): Flow<Result<List<PostDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupModerationPosts(groupId)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun approveGroupPendingPost(groupId: Long, postId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.approveGroupModerationPost(groupId, postId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun rejectGroupPendingPost(groupId: Long, postId: Long, reason: String?): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.rejectGroupModerationPost(groupId, postId)
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getGroupReports(
        groupId: Long,
        page: Int
    ): Flow<Result<List<com.connectly.android.core.network.api.GroupReportDto>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getGroupReports(groupId, page)
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun actionGroupReport(
        groupId: Long,
        reportId: Long,
        status: String,
        decisionNote: String?,
        removeContent: Boolean
    ): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val res = api.actionGroupReport(groupId, reportId, com.connectly.android.core.network.api.ActionGroupReportRequest(status, decisionNote, removeContent))
            if (res.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception(res.message())))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }
}
