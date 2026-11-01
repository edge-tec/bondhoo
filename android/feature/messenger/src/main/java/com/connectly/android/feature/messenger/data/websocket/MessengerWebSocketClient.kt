package com.connectly.android.feature.messenger.data.websocket

import com.connectly.android.core.model.Message
import com.connectly.android.core.websocket.ReverbWebSocketManager
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Unified Messenger WebSocket Client.
 * Delegates directly to the singleton ReverbWebSocketManager to maintain
 * a single shared WebSocket connection across the entire application.
 */
@Singleton
class MessengerWebSocketClient @Inject constructor(
    private val reverbWebSocketManager: ReverbWebSocketManager
) {
    val incomingMessages: Flow<Message> = reverbWebSocketManager.messageNewFlow.map { it.message }

    val typingEvents: Flow<Pair<Long, Boolean>> = reverbWebSocketManager.userTypingFlow.map {
        it.conversationId to it.isTyping
    }

    fun connect() {
        reverbWebSocketManager.connect()
    }

    fun subscribeConversation(conversationId: Long) {
        reverbWebSocketManager.subscribeConversation(conversationId)
    }

    fun unsubscribeConversation(conversationId: Long) {
        reverbWebSocketManager.unsubscribeConversation(conversationId)
    }

    fun sendTyping(conversationId: Long, isTyping: Boolean) {
        reverbWebSocketManager.sendTyping(conversationId, isTyping)
    }
}
