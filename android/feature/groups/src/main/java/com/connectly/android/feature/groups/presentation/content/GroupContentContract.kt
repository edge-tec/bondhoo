package com.connectly.android.feature.groups.presentation.content

import com.connectly.android.core.network.api.GroupAnnouncementDto
import com.connectly.android.core.network.api.GroupReportDto
import com.connectly.android.core.network.api.PostDto

data class GroupContentState(
    val groupId: Long = 0L,
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val feedPosts: List<PostDto> = emptyList(),
    val pendingPosts: List<PostDto> = emptyList(),
    val announcements: List<GroupAnnouncementDto> = emptyList(),
    val reports: List<GroupReportDto> = emptyList(),
    val selectedPostDetails: PostDto? = null,
    val selectedFilter: String = "all", // "all", "pinned", "media", "polls", "my_posts"
    val selectedSort: String = "newest", // "newest", "most_liked", "most_commented"
    val actionMessage: String? = null,
    val errorMessage: String? = null,
    // Composer fields
    val composerContent: String = "",
    val composerType: String = "text", // "text", "media", "poll"
    val composerMediaUrls: List<String> = emptyList(),
    val composerPollQuestion: String = "",
    val composerPollOptions: List<String> = listOf("", ""),
    val composerPollDurationDays: Int = 7,
    val composerIsPinned: Boolean = false,
    val composerScheduledAt: String? = null,
    // Dialog triggers
    val reportDialogPostId: Long? = null,
    val rejectDialogPostId: Long? = null,
    val deleteConfirmPostId: Long? = null,
    val reportCategory: String = "spam",
    val reportDescription: String = "",
    val rejectReason: String = ""
)

sealed interface GroupContentIntent {
    data class LoadFeed(val groupId: Long, val filter: String? = null, val sort: String? = null) : GroupContentIntent
    data class LoadPendingPosts(val groupId: Long) : GroupContentIntent
    data class LoadAnnouncements(val groupId: Long) : GroupContentIntent
    data class LoadReports(val groupId: Long) : GroupContentIntent
    data class LoadPostDetails(val groupId: Long, val postId: Long) : GroupContentIntent
    data class ChangeFilter(val filter: String) : GroupContentIntent
    data class ChangeSort(val sort: String) : GroupContentIntent
    // Composer actions
    data class UpdateComposerContent(val content: String) : GroupContentIntent
    data class UpdateComposerType(val type: String) : GroupContentIntent
    data class AddComposerMediaUrl(val url: String) : GroupContentIntent
    data class RemoveComposerMediaUrl(val index: Int) : GroupContentIntent
    data class UpdatePollQuestion(val question: String) : GroupContentIntent
    data class UpdatePollOption(val index: Int, val text: String) : GroupContentIntent
    object AddPollOption : GroupContentIntent
    data class RemovePollOption(val index: Int) : GroupContentIntent
    data class UpdatePollDuration(val days: Int) : GroupContentIntent
    data class UpdateScheduleTime(val scheduledAt: String?) : GroupContentIntent
    data class ToggleComposerPin(val pin: Boolean) : GroupContentIntent
    data class SubmitPost(val isDraft: Boolean = false) : GroupContentIntent
    // Post actions
    data class TogglePinPost(val postId: Long) : GroupContentIntent
    data class DeletePost(val postId: Long) : GroupContentIntent
    data class VotePoll(val postId: Long, val optionId: Int) : GroupContentIntent
    data class ReactPost(val postId: Long, val reactionType: String) : GroupContentIntent
    // Moderation & Approval
    data class ApprovePendingPost(val postId: Long) : GroupContentIntent
    data class RejectPendingPost(val postId: Long, val reason: String) : GroupContentIntent
    data class ReportPost(val postId: Long, val category: String, val description: String?) : GroupContentIntent
    data class ActionReport(val reportId: Long, val status: String, val decisionNote: String?, val removeContent: Boolean) : GroupContentIntent
    // Announcement actions
    data class CreateAnnouncement(val title: String, val content: String, val isPinned: Boolean, val expiresAt: String?) : GroupContentIntent
    // Dialog triggers
    data class OpenReportDialog(val postId: Long) : GroupContentIntent
    data class OpenRejectDialog(val postId: Long) : GroupContentIntent
    data class OpenDeleteDialog(val postId: Long) : GroupContentIntent
    object DismissDialogs : GroupContentIntent
    object DismissMessages : GroupContentIntent
}

