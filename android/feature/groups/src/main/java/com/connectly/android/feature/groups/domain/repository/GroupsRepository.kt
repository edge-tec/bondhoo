package com.connectly.android.feature.groups.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.GroupMemberDto
import com.connectly.android.core.network.api.PageDto
import kotlinx.coroutines.flow.Flow

interface GroupsRepository {
    fun getGroups(filter: String, page: Int = 1): Flow<Result<List<Group>>>
    fun getFeaturedGroup(): Flow<Result<Group>>
    fun getGroupDetails(slugOrId: String): Flow<Result<Group>>
    fun createGroup(name: String, description: String?, privacy: String): Flow<Result<Group>>
    fun updateGroup(groupId: Long, name: String?, description: String?, privacy: String?): Flow<Result<Group>>
    fun toggleJoinGroup(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>>
    fun getGroupMembers(groupId: Long): Flow<Result<List<GroupMemberDto>>>
    fun updateGroupMemberRole(groupId: Long, userId: Long, role: String): Flow<Result<Unit>>
    fun removeGroupMember(groupId: Long, userId: Long): Flow<Result<Unit>>
    fun createGroupAnnouncement(groupId: Long, body: String): Flow<Result<Unit>>
    fun inviteGroupMembers(groupId: Long, userIds: List<Long>): Flow<Result<Unit>>
    fun getGroupRequests(groupId: Long): Flow<Result<List<User>>>
    fun moderateGroupRequest(groupId: Long, userId: Long, approve: Boolean): Flow<Result<Unit>>
    fun followPage(pageId: Long): Flow<Result<Unit>>
    fun createPage(name: String, category: String, bio: String?): Flow<Result<PageDto>>
}
