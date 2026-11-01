package com.connectly.android.feature.feed.presentation.feed

import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.StoryUserTray
import com.connectly.android.core.model.User

data class FeedState(
    val selectedTab: String = "for_you", // "for_you", "following", "groups", "pages"
    val posts: List<Post> = emptyList(),
    val storiesTray: List<StoryUserTray> = emptyList(),
    val suggestedFriends: List<User> = emptyList(),
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
    val reportingPostId: Long? = null,
    val reportSuccessMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface FeedIntent {
    data class SelectTab(val tab: String) : FeedIntent
    object RefreshFeed : FeedIntent
    object LoadMorePosts : FeedIntent
    data class ReactToPost(val postId: Long, val reaction: String) : FeedIntent
    data class SavePost(val postId: Long) : FeedIntent
    data class SharePost(val postId: Long, val caption: String? = null) : FeedIntent
    data class DeletePost(val postId: Long) : FeedIntent
    data class OpenComments(val postId: Long) : FeedIntent
    object CloseComments : FeedIntent
    data class StartReply(val parentCommentId: Long, val authorUsername: String) : FeedIntent
    object CancelReply : FeedIntent
    data class AddComment(val postId: Long, val body: String, val parentId: Long? = null) : FeedIntent
    data class ReactToComment(val commentId: Long, val reaction: String) : FeedIntent
    data class OpenReportDialog(val postId: Long) : FeedIntent
    object CloseReportDialog : FeedIntent
    data class SubmitReport(val postId: Long, val reason: String, val details: String? = null) : FeedIntent
    object DismissMessages : FeedIntent
}
