package com.connectly.android.feature.notifications.presentation.notifications

import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationPreferences

data class NotificationsState(
    val selectedFilter: String = "all", // "all", "unread"
    val selectedCategory: String = "all", // "all", "likes", "comments", "follows"
    val notifications: List<NotificationItem> = emptyList(),
    val preferences: NotificationPreferences = NotificationPreferences(),
    val unreadCount: Int = 0,
    val isLoading: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null
)

sealed interface NotificationsIntent {
    data class SelectFilter(val filter: String) : NotificationsIntent
    data class SelectCategory(val category: String) : NotificationsIntent
    data class MarkRead(val notificationId: String) : NotificationsIntent
    data class DeleteNotification(val notificationId: String) : NotificationsIntent
    data class RespondFriendRequest(val notificationId: String, val userId: Long, val accept: Boolean) : NotificationsIntent
    data class UpdatePreferences(val preferences: NotificationPreferences) : NotificationsIntent
    data object LoadPreferences : NotificationsIntent
    data object MarkAllRead : NotificationsIntent
    data object Refresh : NotificationsIntent
}

