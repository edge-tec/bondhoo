package com.connectly.android.feature.notifications.presentation.notifications

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationPreferences
import com.connectly.android.core.model.NotificationType
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.notifications.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val getNotificationsUseCase: GetNotificationsUseCase,
    private val getUnreadNotificationCountUseCase: GetUnreadNotificationCountUseCase,
    private val markNotificationAsReadUseCase: MarkNotificationAsReadUseCase,
    private val markAllNotificationsAsReadUseCase: MarkAllNotificationsAsReadUseCase,
    private val deleteNotificationUseCase: DeleteNotificationUseCase,
    private val respondToFriendRequestUseCase: RespondToFriendRequestUseCase,
    private val getNotificationPreferencesUseCase: GetNotificationPreferencesUseCase? = null,
    private val updateNotificationPreferencesUseCase: UpdateNotificationPreferencesUseCase? = null,
    private val webSocketManager: ReverbWebSocketManager? = null
) : ViewModel() {

    private val _uiState = MutableStateFlow(NotificationsState())
    val uiState: StateFlow<NotificationsState> = _uiState.asStateFlow()

    init {
        loadData()
        observeRealtimeWebSocketEvents()
    }

    fun handleIntent(intent: NotificationsIntent) {
        when (intent) {
            is NotificationsIntent.SelectFilter -> {
                _uiState.update { it.copy(selectedFilter = intent.filter) }
                loadNotifications(category = _uiState.value.selectedCategory, filter = intent.filter)
            }
            is NotificationsIntent.SelectCategory -> {
                _uiState.update { it.copy(selectedCategory = intent.category) }
                loadNotifications(category = intent.category, filter = _uiState.value.selectedFilter)
            }
            is NotificationsIntent.MarkRead -> markRead(intent.notificationId)
            is NotificationsIntent.DeleteNotification -> deleteNotification(intent.notificationId)
            is NotificationsIntent.RespondFriendRequest -> {
                respondFriendRequest(intent.notificationId, intent.userId, intent.accept)
            }
            NotificationsIntent.MarkAllRead -> markAllRead()
            NotificationsIntent.LoadPreferences -> loadPreferences()
            is NotificationsIntent.UpdatePreferences -> updatePreferences(intent.preferences)
            NotificationsIntent.Refresh -> loadData()
            else -> {}
        }
    }

    private fun loadData() {
        loadNotifications(_uiState.value.selectedCategory, _uiState.value.selectedFilter)
        viewModelScope.launch {
            getUnreadNotificationCountUseCase().collect { count ->
                _uiState.update { it.copy(unreadCount = count) }
            }
        }
    }

    private fun loadNotifications(category: String, filter: String = "all") {
        viewModelScope.launch {
            getNotificationsUseCase(filter = filter, category = category).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, notifications = result.data, errorMessage = null) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun markRead(id: String) {
        _uiState.update { state ->
            val wasUnread = state.notifications.find { it.id == id }?.isRead == false
            state.copy(
                notifications = state.notifications.map {
                    if (it.id == id) it.copy(isRead = true) else it
                },
                unreadCount = if (wasUnread && state.unreadCount > 0) state.unreadCount - 1 else state.unreadCount
            )
        }
        viewModelScope.launch {
            markNotificationAsReadUseCase(id).collect()
        }
    }

    private fun deleteNotification(id: String) {
        _uiState.update { state ->
            val wasUnread = state.notifications.find { it.id == id }?.isRead == false
            state.copy(
                notifications = state.notifications.filterNot { it.id == id },
                unreadCount = if (wasUnread && state.unreadCount > 0) state.unreadCount - 1 else state.unreadCount
            )
        }
        viewModelScope.launch {
            deleteNotificationUseCase(id).collect()
        }
    }

    private fun markAllRead() {
        _uiState.update { state ->
            state.copy(
                notifications = state.notifications.map { it.copy(isRead = true) },
                unreadCount = 0
            )
        }
        viewModelScope.launch {
            markAllNotificationsAsReadUseCase().collect()
        }
    }

    private fun respondFriendRequest(notifId: String, userId: Long, accept: Boolean) {
        _uiState.update { state ->
            state.copy(
                notifications = state.notifications.filterNot { it.id == notifId }
            )
        }
        viewModelScope.launch {
            respondToFriendRequestUseCase(userId, accept).collect()
        }
    }

    private fun loadPreferences() {
        if (getNotificationPreferencesUseCase == null) return
        viewModelScope.launch {
            getNotificationPreferencesUseCase.invoke().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(preferences = result.data) }
                }
            }
        }
    }

    private fun updatePreferences(preferences: NotificationPreferences) {
        if (updateNotificationPreferencesUseCase == null) return
        _uiState.update { it.copy(preferences = preferences) }
        viewModelScope.launch {
            updateNotificationPreferencesUseCase.invoke(preferences).collect()
        }
    }

    private fun observeRealtimeWebSocketEvents() {
        if (webSocketManager == null) return

        // 1. New notification arrival in real-time
        viewModelScope.launch {
            webSocketManager.socialNotificationFlow.collect { event ->
                _uiState.update { state ->
                    // Prevent duplicate notifications
                    if (state.notifications.any { it.id == event.notificationId }) {
                        state
                    } else {
                        val newItem = NotificationItem(
                            id = event.notificationId,
                            type = NotificationType.from(event.type),
                            title = event.title,
                            message = event.message,
                            timeAgo = "Just now",
                            isRead = false,
                            targetUrl = event.actionUrl
                        )
                        state.copy(
                            notifications = listOf(newItem) + state.notifications,
                            unreadCount = state.unreadCount + 1
                        )
                    }
                }
            }
        }

        // 2. Notification read event
        viewModelScope.launch {
            webSocketManager.notificationReadFlow.collect { event ->
                _uiState.update { state ->
                    state.copy(
                        notifications = state.notifications.map {
                            if (it.id == event.notificationId) it.copy(isRead = true) else it
                        },
                        unreadCount = event.unreadCount
                    )
                }
            }
        }

        // 3. Mark all read event
        viewModelScope.launch {
            webSocketManager.notificationReadAllFlow.collect {
                _uiState.update { state ->
                    state.copy(
                        notifications = state.notifications.map { it.copy(isRead = true) },
                        unreadCount = 0
                    )
                }
            }
        }

        // 4. Notification deleted event
        viewModelScope.launch {
            webSocketManager.notificationDeletedFlow.collect { event ->
                _uiState.update { state ->
                    state.copy(
                        notifications = state.notifications.filterNot { it.id == event.notificationId },
                        unreadCount = event.unreadCount
                    )
                }
            }
        }
    }
}
