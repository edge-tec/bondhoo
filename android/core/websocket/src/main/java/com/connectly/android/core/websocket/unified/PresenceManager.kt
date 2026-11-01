package com.connectly.android.core.websocket.unified

import com.connectly.android.core.websocket.ReverbWebSocketManager
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject
import javax.inject.Singleton

data class UserPresenceInfo(
    val userId: Long,
    val isOnline: Boolean,
    val lastSeen: String? = null
)

@Singleton
class PresenceManager @Inject constructor(
    private val webSocketManager: ReverbWebSocketManager
) {
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    private val _onlineUsers = MutableStateFlow<Map<Long, UserPresenceInfo>>(emptyMap())
    val onlineUsers: StateFlow<Map<Long, UserPresenceInfo>> = _onlineUsers.asStateFlow()

    init {
        scope.launch {
            webSocketManager.userPresenceFlow.collect { event ->
                _onlineUsers.update { current ->
                    val mutable = current.toMutableMap()
                    mutable[event.userId] = UserPresenceInfo(
                        userId = event.userId,
                        isOnline = event.isOnline,
                        lastSeen = event.lastSeen
                    )
                    mutable
                }
            }
        }
    }

    fun isUserOnline(userId: Long): Boolean {
        return _onlineUsers.value[userId]?.isOnline ?: false
    }

    fun getUserLastSeen(userId: Long): String? {
        return _onlineUsers.value[userId]?.lastSeen
    }

    fun startTrackingPresence() {
        webSocketManager.subscribePresenceChannel()
    }
}

