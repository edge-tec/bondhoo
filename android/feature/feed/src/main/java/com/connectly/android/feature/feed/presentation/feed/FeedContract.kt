package com.connectly.android.feature.feed.presentation.feed

import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.StoryUserTray
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.EligibleGroupDto
import com.connectly.android.core.network.api.EligiblePageDto

enum class SharingStep {
    DESTINATIONS,
    GROUPS,
    PAGES
}

data class FeedState(
    val selectedTab: String = "for_you", // "for_you", "following", "groups", "pages"
    val posts: List<Post> = emptyList(),
    val storiesTray: List<StoryUserTray> = emptyList(),
    val suggestedFriends: List<User> = emptyList(),
    val requestedFriendIds: Set<Long> = emptySet(),
    val trendingTopics: List<String> = listOf("#Bangladesh", "#Dhaka", "#Connectly", "#Innovation", "#Technology"),
    val nextCursor: String? = null,
    val hasMore: Boolean = false,
    val isLoading: Boolean = false,
    val isRefreshing: Boolean = false,
    val isPaginating: Boolean = false,
    val activeCommentsPostId: Long? = null,
    val activeComments: List<Comment> = emptyList(),
    val activeParentCommentId: Long? = null,
    val replyingToUser: String? = null,
    val isCommentsLoading: Boolean = false,
    val isSuggestedFriendsHidden: Boolean = false,
    val reportingPostId: Long? = null,
    val reportSuccessMessage: String? = null,
    val errorMessage: String? = null,
    val userMessage: String? = null,
    // Smart Sharing System
    val sharingPostId: Long? = null,
    val sharingStep: SharingStep = SharingStep.DESTINATIONS,
    val shareCaption: String = "",
    val eligibleGroups: List<EligibleGroupDto> = emptyList(),
    val selectedGroupIds: Set<Long> = emptySet(),
    val groupSearchQuery: String = "",
    val eligiblePages: List<EligiblePageDto> = emptyList(),
    val selectedPageId: Long? = null,
    val pageSearchQuery: String = "",
    val currentUserAvatar: String? = null,
    val currentUserName: String? = null,
    val isFetchingShareTargets: Boolean = false,
    val isSharing: Boolean = false,
    val myGroups: List<com.connectly.android.core.network.api.GroupDto> = emptyList(),
    val myPages: List<com.connectly.android.core.network.api.PageDto> = emptyList(),
    // Post Management Dialogs
    val editingPost: Post? = null,
    val deletingPostId: Long? = null,
    val isEditingPostSaving: Boolean = false,
    // Comment Management
    val editingCommentId: Long? = null,
    val editingCommentText: String = ""
)

sealed interface FeedIntent {
    data class SelectTab(val tab: String) : FeedIntent
    object RefreshFeed : FeedIntent
    object LoadMorePosts : FeedIntent
    data class ReactToPost(val postId: Long, val reaction: String) : FeedIntent
    data class SavePost(val postId: Long) : FeedIntent

    // Sharing intents
    data class OpenShareSheet(val postId: Long) : FeedIntent
    object DismissShareSheet : FeedIntent
    data class SetSharingStep(val step: SharingStep) : FeedIntent
    data class UpdateShareCaption(val caption: String) : FeedIntent
    data class SearchShareGroups(val query: String) : FeedIntent
    data class SearchSharePages(val query: String) : FeedIntent
    data class ToggleSelectGroup(val groupId: Long) : FeedIntent
    object SelectAllGroups : FeedIntent
    object ClearSelectedGroups : FeedIntent
    data class SelectPage(val pageId: Long) : FeedIntent
    data class ShareToTimeline(val postId: Long, val caption: String? = null) : FeedIntent
    data class ShareToSelectedGroups(val postId: Long, val caption: String? = null) : FeedIntent
    data class ShareToSelectedPage(val postId: Long, val caption: String? = null) : FeedIntent
    object LoadShareTargets : FeedIntent
    data class ConfirmSharePost(val postId: Long, val targetType: String, val targetId: Long? = null, val caption: String? = null) : FeedIntent
    data class ShareToMultipleTargets(val postId: Long, val targetType: String, val targetIds: List<Long>, val caption: String? = null) : FeedIntent
    data class SharePost(val postId: Long, val caption: String? = null) : FeedIntent

    data class DeletePost(val postId: Long) : FeedIntent
    data class RequestDeletePost(val postId: Long) : FeedIntent
    object DismissDeleteDialog : FeedIntent
    data class ConfirmDeletePost(val postId: Long) : FeedIntent
    data class OpenEditPost(val post: Post) : FeedIntent
    object DismissEditPost : FeedIntent
    data class SubmitEditPost(val postId: Long, val content: String, val audience: String) : FeedIntent
    data class HidePost(val postId: Long) : FeedIntent

    data class OpenComments(val postId: Long) : FeedIntent
    object CloseComments : FeedIntent
    data class StartReply(val parentCommentId: Long, val authorUsername: String) : FeedIntent
    object CancelReply : FeedIntent
    data class AddComment(val postId: Long, val body: String, val parentId: Long? = null) : FeedIntent
    data class ReactToComment(val commentId: Long, val reaction: String) : FeedIntent
    data class RequestEditComment(val commentId: Long, val currentBody: String) : FeedIntent
    object DismissEditComment : FeedIntent
    data class SubmitEditComment(val commentId: Long, val newBody: String) : FeedIntent
    data class DeleteComment(val commentId: Long) : FeedIntent
    data class OpenReportDialog(val postId: Long) : FeedIntent
    object CloseReportDialog : FeedIntent
    data class SubmitReport(val postId: Long, val reason: String, val details: String? = null) : FeedIntent
    data class AddSuggestedFriend(val userId: Long) : FeedIntent
    data class CancelSuggestedFriend(val userId: Long) : FeedIntent
    object ToggleHideSuggestedFriends : FeedIntent
    data class StartLiveStream(val title: String, val description: String? = null, val onStreamCreated: (Long) -> Unit) : FeedIntent
    object DismissMessages : FeedIntent
}
