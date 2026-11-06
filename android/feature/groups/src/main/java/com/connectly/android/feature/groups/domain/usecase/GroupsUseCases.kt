package com.connectly.android.feature.groups.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.core.network.api.GroupMemberDto
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetGroupProfileUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(slugOrId: String): Flow<Result<com.connectly.android.core.network.api.GroupProfileResponseDto>> {
        return repository.getGroupProfile(slugOrId)
    }
}

class GetGroupDashboardUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        period: String = "30d",
        startDate: String? = null,
        endDate: String? = null
    ): Flow<Result<com.connectly.android.core.network.api.GroupDashboardResponseDto>> {
        return repository.getGroupDashboard(groupId, period, startDate, endDate)
    }
}

class GetGroupRolesUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long): Flow<Result<com.connectly.android.core.network.api.GroupRolesCatalogResponseDto>> {
        return repository.getGroupRoles(groupId)
    }
}

class CreateCustomRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        request: com.connectly.android.core.network.api.CreateCustomRoleRequest
    ): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>> {
        return repository.createCustomRole(groupId, request)
    }
}

class UpdateCustomRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        roleId: Long,
        request: com.connectly.android.core.network.api.UpdateCustomRoleRequest
    ): Flow<Result<com.connectly.android.core.network.api.GroupRoleDto>> {
        return repository.updateCustomRole(groupId, roleId, request)
    }
}

class DeleteCustomRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, roleId: Long): Flow<Result<Unit>> {
        return repository.deleteCustomRole(groupId, roleId)
    }
}

class AssignGroupRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        request: com.connectly.android.core.network.api.AssignRoleRequest
    ): Flow<Result<List<com.connectly.android.core.network.api.GroupMemberDto>>> {
        return repository.assignGroupRole(groupId, request)
    }
}

class RevokeGroupRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        request: com.connectly.android.core.network.api.AssignRoleRequest
    ): Flow<Result<List<com.connectly.android.core.network.api.GroupMemberDto>>> {
        return repository.revokeGroupRole(groupId, request)
    }
}

class GetGroupRoleHistoryUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long): Flow<Result<List<com.connectly.android.core.network.api.RoleHistoryItemDto>>> {
        return repository.getGroupRoleHistory(groupId)
    }
}

class ToggleFollowGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long): Flow<Result<Boolean>> {
        return repository.toggleFollowGroup(groupId)
    }
}

class CancelGroupRequestUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long): Flow<Result<Unit>> {
        return repository.cancelGroupRequest(groupId)
    }
}

class InviteGroupMembersUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, userIds: List<Long>): Flow<Result<Unit>> {
        return repository.inviteGroupMembers(groupId, userIds)
    }
}

class GetGroupsHomeUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(): Flow<Result<com.connectly.android.core.network.api.GroupsHomeResponseDto>> {
        return repository.getGroupsHome()
    }
}

class GetGroupCategoriesUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(): Flow<Result<List<com.connectly.android.core.model.GroupCategoryItem>>> {
        return repository.getGroupCategories()
    }
}

class GetGroupsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        filter: String = "discover",
        category: String? = null,
        query: String? = null,
        sort: String? = null,
        page: Int = 1
    ): Flow<Result<List<Group>>> {
        return repository.getGroups(filter, category, query, sort, page)
    }
}

class GetFeaturedGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(): Flow<Result<Group>> {
        return repository.getFeaturedGroup()
    }
}

class ToggleJoinGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>> {
        return repository.toggleJoinGroup(groupId, currentJoined)
    }
}

class CreateGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(request: com.connectly.android.core.network.api.CreateGroupRequest): Flow<Result<Group>> {
        return repository.createGroup(request)
    }

    operator fun invoke(name: String, description: String?, privacy: String): Flow<Result<Group>> {
        return repository.createGroup(name, description, privacy)
    }
}

class GetPagesUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(filter: String = "discover", page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.PageDto>>> {
        return repository.getPages(filter, page)
    }
}

class FollowPageUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<Unit>> {
        return repository.followPage(pageId)
    }
}

class CreatePageUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(name: String, category: String, bio: String?): Flow<Result<com.connectly.android.core.network.api.PageDto>> {
        return repository.createPage(name, category, bio)
    }
}

class CreatePageDetailedUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(request: com.connectly.android.core.network.api.CreatePageRequest): Flow<Result<com.connectly.android.core.network.api.PageDto>> {
        return repository.createPageDetailed(request)
    }
}

class GetPageDetailsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageIdOrSlug: String): Flow<Result<com.connectly.android.core.network.api.PageDto>> {
        return repository.getPageDetails(pageIdOrSlug)
    }
}

class UpdatePageUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, request: com.connectly.android.core.network.api.UpdatePageRequest): Flow<Result<com.connectly.android.core.network.api.PageDto>> {
        return repository.updatePage(pageId, request)
    }
}

class CheckPageUsernameUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(username: String): Flow<Result<com.connectly.android.core.network.api.PageUsernameCheckDto>> {
        return repository.checkPageUsername(username)
    }
}

class GetPageAnalyticsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, range: String = "7d"): Flow<Result<com.connectly.android.core.network.api.PageAnalyticsDetailDto>> {
        return repository.getPageAnalytics(pageId, range)
    }
}

class GetPageRolesUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageRoleDto>>> {
        return repository.getPageRoles(pageId)
    }
}

class AssignPageRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, request: com.connectly.android.core.network.api.AssignPageRoleRequest): Flow<Result<com.connectly.android.core.network.api.PageRoleDto>> {
        return repository.assignPageRole(pageId, request)
    }
}

class RemovePageRoleUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, roleId: Long): Flow<Result<Unit>> {
        return repository.removePageRole(pageId, roleId)
    }
}

class GetPageReviewsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageReviewDto>>> {
        return repository.getPageReviews(pageId)
    }
}

class CreatePageReviewUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, request: com.connectly.android.core.network.api.CreatePageReviewRequest): Flow<Result<com.connectly.android.core.network.api.PageReviewDto>> {
        return repository.createPageReview(pageId, request)
    }
}

class GetPageProductsServicesUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    fun getProducts(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageProductDto>>> = repository.getPageProducts(pageId)
    fun getServices(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageServiceDto>>> = repository.getPageServices(pageId)
}

class GetPageInboxUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PageInboxMessageDto>>> {
        return repository.getPageInbox(pageId)
    }
}

class SendPageMessageUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, request: com.connectly.android.core.network.api.SendPageMessageRequest): Flow<Result<com.connectly.android.core.network.api.PageInboxMessageDto>> {
        return repository.sendPageMessage(pageId, request)
    }
}

class GetPageSettingsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<com.connectly.android.core.network.api.PageSettingsDto>> {
        return repository.getPageSettings(pageId)
    }
}

class UpdatePageSettingsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, settings: com.connectly.android.core.network.api.PageSettingsDto): Flow<Result<com.connectly.android.core.network.api.PageSettingsDto>> {
        return repository.updatePageSettings(pageId, settings)
    }
}

class DeletePageUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<Unit>> {
        return repository.deletePage(pageId)
    }
}

class RequestPageVerificationUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        pageId: Long,
        request: com.connectly.android.core.network.api.RequestPageVerificationRequest
    ): Flow<Result<com.connectly.android.core.network.api.PageVerificationResponseDto>> {
        return repository.requestPageVerification(pageId, request)
    }
}

class GetPageMonetizationUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<com.connectly.android.core.network.api.PageMonetizationStatusDto>> {
        return repository.getPageMonetization(pageId)
    }
}

class UpdatePageMonetizationUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        pageId: Long,
        request: com.connectly.android.core.network.api.UpdatePageMonetizationRequest
    ): Flow<Result<com.connectly.android.core.network.api.PageMonetizationStatusDto>> {
        return repository.updatePageMonetization(pageId, request)
    }
}

class GetPagePostsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>> {
        return repository.getPagePosts(pageId)
    }
}

class CreatePagePostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(pageId: Long, request: com.connectly.android.core.network.api.CreateGroupPostRequest): Flow<Result<com.connectly.android.core.network.api.PostDto>> {
        return repository.createPagePost(pageId, request)
    }
}

class GetGroupMembersDetailedUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        query: String? = null,
        role: String? = null,
        status: String? = null,
        sort: String? = null,
        page: Int = 1
    ): Flow<Result<List<GroupMemberDto>>> {
        return repository.getGroupMembersDetailed(groupId, query, role, status, sort, page)
    }
}

class RestrictGroupMemberUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, userId: Long, durationDays: Int = 7, reason: String = "Policy violation"): Flow<Result<GroupMemberDto>> {
        return repository.restrictMember(groupId, userId, durationDays, reason)
    }
}

class UnrestrictGroupMemberUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, userId: Long): Flow<Result<GroupMemberDto>> {
        return repository.unrestrictMember(groupId, userId)
    }
}

class GetMemberActivityUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, userId: Long): Flow<Result<com.connectly.android.core.network.api.MemberActivityResponseDto>> {
        return repository.getMemberActivity(groupId, userId)
    }
}

class BulkManageMembersUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        request: com.connectly.android.core.network.api.BulkMemberActionRequest
    ): Flow<Result<com.connectly.android.core.network.api.BulkMemberActionResponseDto>> {
        return repository.bulkManageMembers(groupId, request)
    }
}

class GetGroupFeedUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        filter: String? = null,
        type: String? = null,
        sort: String? = null,
        page: Int = 1
    ): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>> {
        return repository.getGroupFeed(groupId, filter, type, sort, page)
    }
}

class CreateGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        request: com.connectly.android.core.network.api.CreateGroupPostRequest
    ): Flow<Result<com.connectly.android.core.network.api.PostDto>> {
        return repository.createGroupPost(groupId, request)
    }
}

class GetGroupPostDetailsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long): Flow<Result<com.connectly.android.core.network.api.PostDto>> {
        return repository.getGroupPostDetails(groupId, postId)
    }
}

class UpdateGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        postId: Long,
        request: com.connectly.android.core.network.api.UpdateGroupPostRequest
    ): Flow<Result<com.connectly.android.core.network.api.PostDto>> {
        return repository.updateGroupPost(groupId, postId, request)
    }
}

class DeleteGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long): Flow<Result<Unit>> {
        return repository.deleteGroupPost(groupId, postId)
    }
}

class TogglePinGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long): Flow<Result<Boolean>> {
        return repository.togglePinGroupPost(groupId, postId)
    }
}

class VoteGroupPostPollUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long, optionId: Int): Flow<Result<Unit>> {
        return repository.voteGroupPostPoll(groupId, postId, optionId)
    }
}

class ReactGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long, reactionType: String): Flow<Result<com.connectly.android.core.network.api.ReactionResponse>> {
        return repository.reactGroupPost(groupId, postId, reactionType)
    }
}

class ReportGroupPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long, category: String, description: String?): Flow<Result<Unit>> {
        return repository.reportGroupPost(groupId, postId, category, description)
    }
}

class GetGroupPendingPostsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.PostDto>>> {
        return repository.getGroupPendingPosts(groupId, page)
    }
}

class ApproveGroupPendingPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long): Flow<Result<Unit>> {
        return repository.approveGroupPendingPost(groupId, postId)
    }
}

class RejectGroupPendingPostUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, postId: Long, reason: String? = null): Flow<Result<Unit>> {
        return repository.rejectGroupPendingPost(groupId, postId, reason)
    }
}

class GetGroupReportsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, page: Int = 1): Flow<Result<List<com.connectly.android.core.network.api.GroupReportDto>>> {
        return repository.getGroupReports(groupId, page)
    }
}

class ActionGroupReportUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(
        groupId: Long,
        reportId: Long,
        status: String,
        decisionNote: String? = null,
        removeContent: Boolean = false
    ): Flow<Result<Unit>> {
        return repository.actionGroupReport(groupId, reportId, status, decisionNote, removeContent)
    }
}



