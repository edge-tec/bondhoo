package com.connectly.android.feature.groups.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.GroupCategoryItem
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.CreateGroupRequest
import com.connectly.android.core.network.api.GroupMemberDto
import com.connectly.android.core.network.api.GroupsHomeResponseDto
import com.connectly.android.core.network.api.PageDto
import kotlinx.coroutines.flow.Flow

interface GroupsRepository {
    fun getGroupsHome(): Flow<Result<GroupsHomeResponseDto>>
    fun getGroupCategories(): Flow<Result<List<GroupCategoryItem>>>
    fun getGroups(filter: String = "discover", category: String? = null, query: String? = null, sort: String? = null, page: Int = 1): Flow<Result<List<Group>>>
    fun getFeaturedGroup(): Flow<Result<Group>>
    fun getGroupDetails(slugOrId: String): Flow<Result<Group>>
    fun getGroupProfile(slugOrId: String): Flow<Result<com.connectly.android.core.network.api.GroupProfileResponseDto>>
    fun getGroupDashboard(groupId: Long, period: String = "30d", startDate: String? = null, endDate: String? = null): Flow<Result<com.connectly.android.core.network.api.GroupDashboardResponseDto>>
    fun getGroupRoles(groupId: Long): Flow<Result<com.connectly.android.core.network.api.GroupRolesCatalogResponseDto>>
    fun createCustomRole(groupId: Long, request: com.connectly.android.core.network.api.CreateCustomRoleRequest): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>>
    fun updateCustomRole(groupId: Long, roleId: Long, request: com.connectly.android.core.network.api.UpdateCustomRoleRequest): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>>
    fun deleteCustomRole(groupId: Long, roleId: Long): Flow<Result<Unit>>
    fun assignGroupRole(groupId: Long, request: com.connectly.android.core.network.api.AssignRoleRequest): Flow<Result<List<GroupMemberDto>>>
    fun revokeGroupRole(groupId: Long, request: com.connectly.android.core.network.api.AssignRoleRequest): Flow<Result<List<GroupMemberDto>>>
    fun getGroupRoleHistory(groupId: Long): Flow<Result<List<com.connectly.android.core.network.api.RoleHistoryItemDto>>>
    fun toggleFollowGroup(groupId: Long): Flow<Result<Boolean>>
    fun cancelGroupRequest(groupId: Long): Flow<Result<Unit>>
    fun createGroup(request: CreateGroupRequest): Flow<Result<Group>>
    fun createGroup(name: String, description: String?, privacy: String): Flow<Result<Group>>
    fun updateGroup(groupId: Long, name: String?, description: String?, privacy: String?): Flow<Result<Group>>
    fun toggleJoinGroup(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>>
    fun getGroupMembers(groupId: Long): Flow<Result<List<GroupMemberDto>>>
    fun getGroupMembersDetailed(groupId: Long, query: String? = null, role: String? = null, status: String? = null, sort: String? = null, page: Int = 1): Flow<Result<List<GroupMemberDto>>>
    fun restrictMember(groupId: Long, userId: Long, durationDays: Int = 7, reason: String): Flow<Result<GroupMemberDto>>
    fun unrestrictMember(groupId: Long, userId: Long): Flow<Result<GroupMemberDto>>
    fun getMemberActivity(groupId: Long, userId: Long): Flow<Result<com.connectly.android.core.network.api.MemberActivityResponseDto>>
    fun bulkManageMembers(groupId: Long, request: com.connectly.android.core.network.api.BulkMemberActionRequest): Flow<Result<com.connectly.android.core.network.api.BulkMemberActionResponseDto>>
    fun updateGroupMemberRole(groupId: Long, userId: Long, role: String): Flow<Result<Unit>>
    fun removeGroupMember(groupId: Long, userId: Long): Flow<Result<Unit>>
    fun createGroupAnnouncement(groupId: Long, body: String): Flow<Result<Unit>>
    fun inviteGroupMembers(groupId: Long, userIds: List<Long>): Flow<Result<Unit>>
    fun getGroupRequests(groupId: Long): Flow<Result<List<User>>>
    fun moderateGroupRequest(groupId: Long, userId: Long, approve: Boolean): Flow<Result<Unit>>
    fun getPages(filter: String = "discover", page: Int = 1): Flow<Result<List<PageDto>>>
    fun followPage(pageId: Long): Flow<Result<Unit>>
    fun createPage(name: String, category: String, bio: String?): Flow<Result<PageDto>>
    fun createPageDetailed(request: com.connectly.android.core.network.api.CreatePageRequest): Flow<Result<PageDto>>
    fun checkPageUsername(username: String): Flow<Result<com.connectly.android.core.network.api.PageUsernameCheckDto>>
    fun getPageDetails(pageIdOrSlug: String): Flow<Result<PageDto>>
    fun updatePage(pageId: Long, request: com.connectly.android.core.network.api.UpdatePageRequest): Flow<Result<PageDto>>
    fun deletePage(pageId: Long): Flow<Result<Unit>>
    fun likePage(pageId: Long): Flow<Result<Unit>>
    fun getPageAnalytics(pageId: Long, range: String = "7d"): Flow<Result<com.connectly.android.core.network.api.PageAnalyticsDetailDto>>
    fun getPageRoles(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageRoleDto>>>
    fun assignPageRole(pageId: Long, request: com.connectly.android.core.network.api.AssignPageRoleRequest): Flow<Result<com.connectly.android.core.network.api.PageRoleDto>>
    fun removePageRole(pageId: Long, roleId: Long): Flow<Result<Unit>>
    fun getPageReviews(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageReviewDto>>>
    fun createPageReview(pageId: Long, request: com.connectly.android.core.network.api.CreatePageReviewRequest): Flow<Result<com.connectly.android.core.network.api.PageReviewDto>>
    fun getPageProducts(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageProductDto>>>
    fun getPageServices(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageServiceDto>>>
    fun getPageInbox(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageInboxMessageDto>>>
    fun sendPageMessage(pageId: Long, request: com.connectly.android.core.network.api.SendPageMessageRequest): Flow<Result<com.connectly.android.core.network.api.PageInboxMessageDto>>
    fun getPageSettings(pageId: Long): Flow<Result<com.connectly.android.core.network.api.PageSettingsDto>>
    fun updatePageSettings(pageId: Long, settings: com.connectly.android.core.network.api.PageSettingsDto): Flow<Result<com.connectly.android.core.network.api.PageSettingsDto>>
    fun requestPageVerification(pageId: Long, request: com.connectly.android.core.network.api.RequestPageVerificationRequest): Flow<Result<com.connectly.android.core.network.api.PageVerificationResponseDto>>
    fun getPageMonetization(pageId: Long): Flow<Result<com.connectly.android.core.network.api.PageMonetizationStatusDto>>
    fun updatePageMonetization(pageId: Long, request: com.connectly.android.core.network.api.UpdatePageMonetizationRequest): Flow<Result<com.connectly.android.core.network.api.PageMonetizationStatusDto>>
    fun getPagePosts(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>>
    fun createPagePost(pageId: Long, request: com.connectly.android.core.network.api.CreateGroupPostRequest): Flow<Result<com.connectly.android.core.network.api.PostDto>>
    fun getGroupFeed(groupId: Long, filter: String? = null, type: String? = null, sort: String? = null, page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>>
    fun createGroupPost(groupId: Long, request: com.connectly.android.core.network.api.CreateGroupPostRequest): Flow<Result<com.connectly.android.core.network.api.PostDto>>
    fun getGroupPostDetails(groupId: Long, postId: Long): Flow<Result<com.connectly.android.core.network.api.PostDto>>
    fun updateGroupPost(groupId: Long, postId: Long, request: com.connectly.android.core.network.api.UpdateGroupPostRequest): Flow<Result<com.connectly.android.core.network.api.PostDto>>
    fun deleteGroupPost(groupId: Long, postId: Long): Flow<Result<Unit>>
    fun togglePinGroupPost(groupId: Long, postId: Long): Flow<Result<Boolean>>
    fun voteGroupPostPoll(groupId: Long, postId: Long, optionId: Int): Flow<Result<Unit>>
    fun reactGroupPost(groupId: Long, postId: Long, reactionType: String): Flow<Result<com.connectly.android.core.network.api.ReactionResponse>>
    fun reportGroupPost(groupId: Long, postId: Long, category: String, description: String?): Flow<Result<Unit>>
    fun getGroupPendingPosts(groupId: Long, page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>>
    fun approveGroupPendingPost(groupId: Long, postId: Long): Flow<Result<Unit>>
    fun rejectGroupPendingPost(groupId: Long, postId: Long, reason: String?): Flow<Result<Unit>>
    fun getGroupReports(groupId: Long, page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.GroupReportDto>>>
    fun actionGroupReport(groupId: Long, reportId: Long, status: String, decisionNote: String?, removeContent: Boolean): Flow<Result<Unit>>
}
