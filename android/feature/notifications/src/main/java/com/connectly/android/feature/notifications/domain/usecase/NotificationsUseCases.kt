package com.connectly.android.feature.notifications.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DiscoveryData
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.SearchResult
import com.connectly.android.core.model.User
import com.connectly.android.feature.notifications.domain.repository.NotificationsRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetNotificationsUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(filter: String = "all", category: String = "all", page: Int = 1): Flow<Result<List<NotificationItem>>> {
        return repository.getNotifications(filter, category, page)
    }
}

class GetUnreadNotificationCountUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(): Flow<Int> {
        return repository.getUnreadCount()
    }
}

class MarkNotificationAsReadUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(notificationId: String): Flow<Result<Unit>> {
        return repository.markAsRead(notificationId)
    }
}

class MarkAllNotificationsAsReadUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(): Flow<Result<Unit>> {
        return repository.markAllAsRead()
    }
}

class RespondToFriendRequestUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(userId: Long, accept: Boolean): Flow<Result<Unit>> {
        return repository.respondToFriendRequest(userId, accept)
    }
}

class SearchUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(query: String, type: String = "all"): Flow<Result<SearchResult>> {
        return repository.search(query, type)
    }
}

class GetDiscoveryDataUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(): Flow<Result<DiscoveryData>> {
        return repository.getDiscoveryData()
    }
}

class RegisterDeviceTokenUseCase @Inject constructor(
    private val repository: NotificationsRepository
) {
    operator fun invoke(pushToken: String): Flow<Result<Unit>> {
        return repository.registerDeviceToken(pushToken)
    }
}
