package com.connectly.android.feature.messenger.data.websocket

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.launch
import okhttp3.*
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class MessengerWebSocketClient @Inject constructor(
    private val tokenStorageManager: TokenStorageManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) {
    private val scope = CoroutineScope(ioDispatcher)
    private var webSocket: WebSocket? = null
    private val client = OkHttpClient.Builder().build()

    private val _incomingMessages = MutableSharedFlow<Message>(extraBufferCapacity = 64)
    val incomingMessages: SharedFlow<Message> = _incomingMessages.asSharedFlow()

    private val _typingEvents = MutableSharedFlow<Pair<Long, Boolean>>(extraBufferCapacity = 16)
    val typingEvents: SharedFlow<Pair<Long, Boolean>> = _typingEvents.asSharedFlow()

    private var isConnected = false

    fun connect() {
        if (isConnected) return

        val token = tokenStorageManager.getAccessToken() ?: return
        val request = Request.Builder()
            .url("wss://bondhoo.edge-tec.com/app/connectly_reverb_key?protocol=7&client=android&version=1.0.0")
            .header("Authorization", "Bearer $token")
            .build()

        webSocket = client.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(webSocket: WebSocket, response: Response) {
                isConnected = true
            }

            override fun onMessage(webSocket: WebSocket, text: String) {
                handleIncomingFrame(text)
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                isConnected = false
                scheduleReconnect()
            }

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                isConnected = false
                scheduleReconnect()
            }
        })
    }

    private fun handleIncomingFrame(payload: String) {
        try {
            val json = JSONObject(payload)
            val event = json.optString("event")
            val dataStr = json.optString("data")

            if (event == "message.created" && dataStr.isNotBlank()) {
                val data = JSONObject(dataStr)
                val msg = Message(
                    id = data.optLong("id", System.currentTimeMillis()),
                    conversationId = data.optLong("conversation_id"),
                    senderId = data.optLong("sender_id"),
                    senderName = data.optString("sender_name", "User"),
                    body = data.optString("body", ""),
                    type = data.optString("type", "text"),
                    clientMessageId = data.optString("client_message_id", ""),
                    deliveryStatus = DeliveryStatus.DELIVERED,
                    isMine = false,
                    createdAt = System.currentTimeMillis()
                )
                scope.launch {
                    _incomingMessages.emit(msg)
                }
            } else if (event == "client-typing" && dataStr.isNotBlank()) {
                val data = JSONObject(dataStr)
                val convId = data.optLong("conversation_id")
                val isTyping = data.optBoolean("is_typing", true)
                scope.launch {
                    _typingEvents.emit(convId to isTyping)
                }
            }
        } catch (_: Exception) {
            // Ignore malformed ping/pong frames
        }
    }

    fun sendTyping(conversationId: Long, isTyping: Boolean) {
        if (!isConnected || webSocket == null) return
        val payload = JSONObject().apply {
            put("event", "client-typing")
            put("channel", "private-conversation.$conversationId")
            put("data", JSONObject().apply {
                put("conversation_id", conversationId)
                put("is_typing", isTyping)
            }.toString())
        }
        webSocket?.send(payload.toString())
    }

    private fun scheduleReconnect() {
        scope.launch {
            delay(3000L) // 3 second backoff
            connect()
        }
    }

    fun disconnect() {
        webSocket?.close(1000, "App closed")
        isConnected = false
    }
}

