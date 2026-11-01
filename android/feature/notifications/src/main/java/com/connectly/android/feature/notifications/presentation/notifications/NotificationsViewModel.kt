package com.connectly.android.feature.notifications.presentation.notifications

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.notifications.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val getNotificationsUseCase: GetNotificationsUseCase,
    private val getUnreadNotificationCountUseCase: GetUnreadNotificationCountUseCase,
    private val markNotificationAsReadUseCase: MarkNotificationAsReadUseCase,
    private val markAllNotificationsAsReadUseCase: MarkAllNotificationsAsReadUseCase,
    private val respondToFriendRequestUseCase: RespondToFriendRequestUseCase,
    private val getNotificationPreferencesUseCase: GetNotificationPreferencesUseCase? = null,
    private val updateNotificationPreferencesUseCase: UpdateNotificationPreferencesUseCase? = null
) : ViewModel() {

    private val _uiState = MutableStateFlow(NotificationsState())
    val uiState: StateFlow<NotificationsState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: NotificationsIntent) {
        when (intent) {
            is NotificationsIntent.SelectCategory -> {
                _uiState.update { it.copy(selectedCategory = intent.category) }
                loadNotifications(intent.category)
            }
            is NotificationsIntent.MarkRead -> markRead(intent.notificationId)
            is NotificationsIntent.RespondFriendRequest -> {
                respondFriendRequest(intent.notificationId, intent.userId, intent.accept)
            }
            NotificationsIntent.MarkAllRead -> markAllRead()
            NotificationsIntent.LoadPreferences -> loadPreferences()
            is NotificationsIntent.UpdatePreferences -> updatePreferences(intent.preferences)
            NotificationsIntent.Refresh -> loadData()
        }
    }

    private fun loadData() {
        loadNotifications(_uiState.value.selectedCategory)
        viewModelScope.launch {
            getUnreadNotificationCountUseCase().collect { count ->
                _uiState.update { it.copy(unreadCount = count) }
            }
        }
    }

    private fun loadNotifications(category: String) {
        viewModelScope.launch {
            getNotificationsUseCase(category = category).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, notifications = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun markRead(id: String) {
        _uiState.update { state ->
            state.copy(
                notifications = state.notifications.map {
                    if (it.id == id) it.copy(isRead = true) else it
                }
            )
        }
        viewModelScope.launch {
            markNotificationAsReadUseCase(id).collect()
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
        viewModelScope.launch {
            getNotificationPreferencesUseCase?.invoke()?.collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(preferences = result.data) }
                }
            }
        }
    }

    private fun updatePreferences(prefs: com.connectly.android.core.model.NotificationPreferences) {
        _uiState.update { it.copy(preferences = prefs) }
        viewModelScope.launch {
            updateNotificationPreferencesUseCase?.invoke(prefs)?.collect()
        }
    }
}

