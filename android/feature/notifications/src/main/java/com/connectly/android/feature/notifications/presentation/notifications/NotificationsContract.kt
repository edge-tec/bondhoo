package com.connectly.android.feature.notifications.presentation.notifications

import com.connectly.android.core.model.NotificationItem

data class NotificationsState(
    val selectedCategory: String = "all", // "all", "likes", "comments", "follows"
    val notifications: List<NotificationItem> = emptyList(),
    val unreadCount: Int = 0,
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface NotificationsIntent {
    data class SelectCategory(val category: String) : NotificationsIntent
    data class MarkRead(val notificationId: String) : NotificationsIntent
    data class RespondFriendRequest(val notificationId: String, val userId: Long, val accept: Boolean) : NotificationsIntent
    data object MarkAllRead : NotificationsIntent
    data object Refresh : NotificationsIntent
}

