package com.connectly.android.feature.feed.presentation.feed

import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.StoryUserTray

data class FeedState(
    val selectedTab: String = "for_you", // "for_you", "following", "groups", "pages"
    val posts: List<Post> = emptyList(),
    val storiesTray: List<StoryUserTray> = emptyList(),
    val isLoading: Boolean = false,
    val isRefreshing: Boolean = false,
    val activeCommentsPostId: Long? = null,
    val activeComments: List<Comment> = emptyList(),
    val isCommentsLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface FeedIntent {
    data class SelectTab(val tab: String) : FeedIntent
    object RefreshFeed : FeedIntent
    data class ReactToPost(val postId: Long, val reaction: String) : FeedIntent
    data class SavePost(val postId: Long) : FeedIntent
    data class SharePost(val postId: Long, val caption: String? = null) : FeedIntent
    data class DeletePost(val postId: Long) : FeedIntent
    data class OpenComments(val postId: Long) : FeedIntent
    object CloseComments : FeedIntent
    data class AddComment(val postId: Long, val body: String, val parentId: Long? = null) : FeedIntent
    data class ReactToComment(val commentId: Long, val reaction: String) : FeedIntent
}

