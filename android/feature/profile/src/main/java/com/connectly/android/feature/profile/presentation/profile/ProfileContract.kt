package com.connectly.android.feature.profile.presentation.profile

import com.connectly.android.core.model.FriendshipStatus
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.EligibleGroupDto
import com.connectly.android.core.network.api.EligiblePageDto

enum class ProfileSharingStep {
    DESTINATIONS,
    GROUPS,
    PAGES
}

data class ProfileState(
    val user: User? = null,
    val isFriend: Boolean = false,
    val isFollowing: Boolean = false,
    val friendshipStatus: FriendshipStatus = FriendshipStatus.NONE,
    val isOwner: Boolean = false,
    val posts: List<Post> = emptyList(),
    val photos: List<String> = emptyList(),
    val videos: List<String> = emptyList(),
    val reels: List<String> = emptyList(),
    val isOwnProfile: Boolean = false,
    val selectedTab: Int = 0,
    val isLoading: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null,
    val userMessage: String? = null,
    val showBlockDialog: Boolean = false,
    val showReportDialog: Boolean = false,
    val showUnfriendDialog: Boolean = false,
    val showDashboardSheet: Boolean = false,
    val showManagePostsSheet: Boolean = false,
    val analyticsTotalViews: Long = 0,
    val analyticsViewsToday: Long = 0,
    val analyticsViewsWeek: Long = 0,
    val postFilter: String = "all",
    val commentsPostId: Long? = null,
    val comments: List<com.connectly.android.core.network.api.CommentDto> = emptyList(),
    val isCommentsLoading: Boolean = false,
    // Smart Sharing
    val sharingPostId: Long? = null,
    val sharingStep: ProfileSharingStep = ProfileSharingStep.DESTINATIONS,
    val shareCaption: String = "",
    val eligibleGroups: List<EligibleGroupDto> = emptyList(),
    val selectedGroupIds: Set<Long> = emptySet(),
    val groupSearchQuery: String = "",
    val eligiblePages: List<EligiblePageDto> = emptyList(),
    val selectedPageId: Long? = null,
    val pageSearchQuery: String = "",
    val isFetchingShareTargets: Boolean = false,
    val isSharing: Boolean = false,
    val myGroups: List<com.connectly.android.core.network.api.GroupDto> = emptyList(),
    val myPages: List<com.connectly.android.core.network.api.PageDto> = emptyList(),
    val previewFriends: List<User> = emptyList()
)

sealed interface ProfileIntent {
    data class LoadProfile(val username: String = "me") : ProfileIntent
    object RefreshProfile : ProfileIntent
    data class FilterPosts(val filter: String) : ProfileIntent
    data class SelectTab(val tabIndex: Int) : ProfileIntent
    data class ToggleLikePost(val postId: Long) : ProfileIntent
    data class OpenComments(val postId: Long) : ProfileIntent
    object DismissComments : ProfileIntent
    data class AddComment(val postId: Long, val content: String) : ProfileIntent

    // Sharing intents
    data class OpenShareSheet(val postId: Long) : ProfileIntent
    object DismissShareSheet : ProfileIntent
    data class SetSharingStep(val step: ProfileSharingStep) : ProfileIntent
    data class UpdateShareCaption(val caption: String) : ProfileIntent
    data class SearchShareGroups(val query: String) : ProfileIntent
    data class SearchSharePages(val query: String) : ProfileIntent
    data class ToggleSelectGroup(val groupId: Long) : ProfileIntent
    object SelectAllGroups : ProfileIntent
    object ClearSelectedGroups : ProfileIntent
    data class SelectPage(val pageId: Long) : ProfileIntent
    data class ShareToTimeline(val postId: Long, val caption: String? = null) : ProfileIntent
    data class ShareToSelectedGroups(val postId: Long, val caption: String? = null) : ProfileIntent
    data class ShareToSelectedPage(val postId: Long, val caption: String? = null) : ProfileIntent
    object LoadShareTargets : ProfileIntent
    data class ConfirmSharePost(val postId: Long, val targetType: String, val targetId: Long? = null, val caption: String? = null) : ProfileIntent
    data class ShareToMultipleTargets(val postId: Long, val targetType: String, val targetIds: List<Long>, val caption: String? = null) : ProfileIntent
    data class SharePost(val postId: Long) : ProfileIntent

    object ToggleFollow : ProfileIntent
    object SendFriendRequest : ProfileIntent
    object CancelFriendRequest : ProfileIntent
    object AcceptFriendRequest : ProfileIntent
    object UnfriendUser : ProfileIntent
    object ShareProfile : ProfileIntent
    object CopyProfileLink : ProfileIntent
    object ShowBlockDialog : ProfileIntent
    object DismissBlockDialog : ProfileIntent
    object ConfirmBlockUser : ProfileIntent
    object ShowReportDialog : ProfileIntent
    object DismissReportDialog : ProfileIntent
    data class ConfirmReportUser(val reason: String, val details: String? = null) : ProfileIntent
    object ShowUnfriendDialog : ProfileIntent
    object DismissUnfriendDialog : ProfileIntent
    object ShowDashboardSheet : ProfileIntent
    object DismissDashboardSheet : ProfileIntent
    object ShowManagePostsSheet : ProfileIntent
    object DismissManagePostsSheet : ProfileIntent
    object DismissMessage : ProfileIntent
}
