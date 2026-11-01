package com.connectly.android.feature.notifications.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DiscoveryData
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationPreferences
import com.connectly.android.core.model.SearchResult
import com.connectly.android.core.model.User
import kotlinx.coroutines.flow.Flow

interface NotificationsRepository {
    fun getNotifications(filter: String = "all", category: String = "all", page: Int = 1): Flow<Result<List<NotificationItem>>>
    fun getUnreadCount(): Flow<Int>
    fun markAsRead(notificationId: String): Flow<Result<Unit>>
    fun markAllAsRead(): Flow<Result<Unit>>
    fun respondToFriendRequest(userId: Long, accept: Boolean): Flow<Result<Unit>>
    fun search(query: String, type: String = "all"): Flow<Result<SearchResult>>
    fun getDiscoveryData(): Flow<Result<DiscoveryData>>
    fun getSuggestedFriends(): Flow<Result<List<User>>>
    fun registerDeviceToken(pushToken: String): Flow<Result<Unit>>
    fun getPreferences(): Flow<Result<NotificationPreferences>>
    fun updatePreferences(preferences: NotificationPreferences): Flow<Result<NotificationPreferences>>
}
