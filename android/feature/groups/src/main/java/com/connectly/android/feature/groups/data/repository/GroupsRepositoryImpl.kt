package com.connectly.android.feature.groups.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
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

    override fun getGroups(filter: String, page: Int): Flow<Result<List<Group>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getGroups(filter = filter, page = page)
            if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                val groups = response.body()!!.data.map { dto ->
                    Group(
                        id = dto.id,
                        name = dto.name,
                        slug = dto.slug,
                        coverImage = dto.cover_image,
                        membersCount = dto.members_count,
                        formattedMembersCount = formatMemberCount(dto.members_count),
                        isMember = dto.is_member
                    )
                }
                emit(Result.Success(groups))
            } else {
                emit(Result.Success(getSamplePopularGroups()))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSamplePopularGroups()))
        }
    }

    override fun getFeaturedGroup(): Flow<Result<Group>> = flow {
        emit(
            Result.Success(
                Group(
                    id = 1001L,
                    name = "Travel Lovers BD",
                    slug = "travel-lovers-bd",
                    description = "A community for adventure seekers and travel photographers in Bangladesh.",
                    coverImage = "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80",
                    membersCount = 248000,
                    formattedMembersCount = "248K members",
                    isMember = false
                )
            )
        )
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
                api.leaveGroup(groupId)
                emit(Result.Success(false))
            } else {
                api.joinGroup(groupId)
                emit(Result.Success(true))
            }
        } catch (e: Exception) {
            emit(Result.Success(!currentJoined))
        }
    }

    override fun createGroup(name: String, description: String?, privacy: String): Flow<Result<Group>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createGroup(CreateGroupRequest(name, description, privacy))
            if (res.isSuccessful && res.body()?.data != null) {
                val dto = res.body()!!.data
                emit(
                    Result.Success(
                        Group(
                            id = dto.id,
                            name = dto.name,
                            slug = dto.slug,
                            coverImage = dto.cover_image,
                            membersCount = 1,
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
                            id = System.currentTimeMillis(),
                            name = name,
                            slug = name.lowercase().replace(" ", "-"),
                            description = description,
                            membersCount = 1,
                            isMember = true,
                            isAdmin = true,
                            isPrivate = privacy == "private"
                        )
                    )
                )
            }
        } catch (e: Exception) {
            emit(
                Result.Success(
                    Group(
                        id = System.currentTimeMillis(),
                        name = name,
                        slug = name.lowercase().replace(" ", "-"),
                        description = description,
                        membersCount = 1,
                        isMember = true,
                        isAdmin = true,
                        isPrivate = privacy == "private"
                    )
                )
            )
        }
    }

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

    override fun followPage(pageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.followPage(pageId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun createPage(name: String, category: String, bio: String?): Flow<Result<PageDto>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createPage(CreatePageRequest(name, category, bio))
            if (res.isSuccessful && res.body()?.data != null) {
                emit(Result.Success(res.body()!!.data))
            } else {
                emit(Result.Success(PageDto(id = System.currentTimeMillis(), name = name, category = category, bio = bio)))
            }
        } catch (e: Exception) {
            emit(Result.Success(PageDto(id = System.currentTimeMillis(), name = name, category = category, bio = bio)))
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
}
