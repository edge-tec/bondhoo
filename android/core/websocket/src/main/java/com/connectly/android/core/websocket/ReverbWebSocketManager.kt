package com.connectly.android.core.websocket

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.websocket.model.*
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.*
import okhttp3.*
import org.json.JSONObject
import java.util.Collections
import java.util.concurrent.ConcurrentHashMap
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReverbWebSocketManager @Inject constructor(
    private val api: ConnectlyApi,
    private val tokenStorageManager: TokenStorageManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) {
    private val scope = CoroutineScope(ioDispatcher + SupervisorJob())
    private val okHttpClient = OkHttpClient.Builder().build()
    private var webSocket: WebSocket? = null

    private val _connectionState = MutableStateFlow<ConnectionState>(ConnectionState.Disconnected)
    val connectionState: StateFlow<ConnectionState> = _connectionState.asStateFlow()

    // Real-time Event Streams
    private val _messageNewFlow = MutableSharedFlow<MessageNewEvent>(extraBufferCapacity = 64)
    val messageNewFlow: SharedFlow<MessageNewEvent> = _messageNewFlow.asSharedFlow()

    private val _messageUpdatedFlow = MutableSharedFlow<MessageUpdatedEvent>(extraBufferCapacity = 32)
    val messageUpdatedFlow: SharedFlow<MessageUpdatedEvent> = _messageUpdatedFlow.asSharedFlow()

    private val _messageDeletedFlow = MutableSharedFlow<MessageDeletedEvent>(extraBufferCapacity = 32)
    val messageDeletedFlow: SharedFlow<MessageDeletedEvent> = _messageDeletedFlow.asSharedFlow()

    private val _messageReceiptFlow = MutableSharedFlow<MessageReceiptEvent>(extraBufferCapacity = 64)
    val messageReceiptFlow: SharedFlow<MessageReceiptEvent> = _messageReceiptFlow.asSharedFlow()

    private val _messageReactionFlow = MutableSharedFlow<MessageReactionEvent>(extraBufferCapacity = 32)
    val messageReactionFlow: SharedFlow<MessageReactionEvent> = _messageReactionFlow.asSharedFlow()

    private val _userTypingFlow = MutableSharedFlow<UserTypingEvent>(extraBufferCapacity = 32)
    val userTypingFlow: SharedFlow<UserTypingEvent> = _userTypingFlow.asSharedFlow()

    private val _userPresenceFlow = MutableSharedFlow<UserPresenceEvent>(extraBufferCapacity = 64)
    val userPresenceFlow: SharedFlow<UserPresenceEvent> = _userPresenceFlow.asSharedFlow()

    private val _conversationClearedFlow = MutableSharedFlow<ConversationClearedEvent>(extraBufferCapacity = 16)
    val conversationClearedFlow: SharedFlow<ConversationClearedEvent> = _conversationClearedFlow.asSharedFlow()

    private val _callIncomingFlow = MutableSharedFlow<CallIncomingEvent>(extraBufferCapacity = 16)
    val callIncomingFlow: SharedFlow<CallIncomingEvent> = _callIncomingFlow.asSharedFlow()

    private val _callAcceptedFlow = MutableSharedFlow<CallAcceptedEvent>(extraBufferCapacity = 16)
    val callAcceptedFlow: SharedFlow<CallAcceptedEvent> = _callAcceptedFlow.asSharedFlow()

    private val _callRejectedFlow = MutableSharedFlow<CallRejectedEvent>(extraBufferCapacity = 16)
    val callRejectedFlow: SharedFlow<CallRejectedEvent> = _callRejectedFlow.asSharedFlow()

    private val _callEndedFlow = MutableSharedFlow<CallEndedEvent>(extraBufferCapacity = 16)
    val callEndedFlow: SharedFlow<CallEndedEvent> = _callEndedFlow.asSharedFlow()

    private val _callSignalFlow = MutableSharedFlow<CallSignalEvent>(extraBufferCapacity = 64)
    val callSignalFlow: SharedFlow<CallSignalEvent> = _callSignalFlow.asSharedFlow()

    private val _callParticipantStateFlow = MutableSharedFlow<CallParticipantStateEvent>(extraBufferCapacity = 32)
    val callParticipantStateFlow: SharedFlow<CallParticipantStateEvent> = _callParticipantStateFlow.asSharedFlow()

    // Subscribed Channels Set for Auto-Recovery on Reconnect
    private val subscribedChannels = Collections.newSetFromMap(ConcurrentHashMap<String, Boolean>())

    // Duplicate Prevention Cache (LRU Set of seen IDs / UUIDs)
    private val processedEventKeys = Collections.newSetFromMap(
        object : java.util.LinkedHashMap<String, Boolean>(1000, 0.75f, true) {
            override fun removeEldestEntry(eldest: MutableMap.MutableEntry<String, Boolean>?): Boolean {
                return size > 1000
            }
        }
    )

    private var reconnectAttempt = 0
    private var isIntentionalClose = false
    private var currentSocketId: String? = null

    fun connect() {
        if (_connectionState.value is ConnectionState.Connected || _connectionState.value is ConnectionState.Connecting) {
            return
        }

        isIntentionalClose = false
        _connectionState.value = ConnectionState.Connecting

        val token = tokenStorageManager.getAccessToken()
        val request = Request.Builder()
            .url("wss://bondhoo.edge-tec.com/app/connectly_reverb_key?protocol=7&client=android&version=1.0.0")
            .apply {
                if (!token.isNullOrBlank()) {
                    header("Authorization", "Bearer $token")
                }
            }
            .build()

        webSocket = okHttpClient.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(webSocket: WebSocket, response: Response) {
                reconnectAttempt = 0
            }

            override fun onMessage(webSocket: WebSocket, text: String) {
                handleIncomingMessage(text)
            }

            override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                _connectionState.value = ConnectionState.Disconnected
                if (!isIntentionalClose) {
                    scheduleReconnect()
                }
            }

            override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                _connectionState.value = ConnectionState.Disconnected
                if (!isIntentionalClose) {
                    scheduleReconnect()
                }
            }
        })
    }

    private fun handleIncomingMessage(text: String) {
        try {
            val json = JSONObject(text)
            val event = json.optString("event")
            val dataStr = json.optString("data")

            when (event) {
                "pusher:connection_established" -> {
                    val data = JSONObject(dataStr)
                    val socketId = data.optString("socket_id")
                    currentSocketId = socketId
                    _connectionState.value = ConnectionState.Connected(socketId)

                    // Re-subscribe to all active channels
                    resubscribeChannels()
                }

                "pusher:ping" -> {
                    // Send Pusher Pong reply
                    val pong = JSONObject().apply {
                        put("event", "pusher:pong")
                        put("data", JSONObject())
                    }
                    webSocket?.send(pong.toString())
                }

                "message.new" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgJson = data.optJSONObject("message") ?: data
                        val msgId = msgJson.optLong("id")
                        val clientUuid = msgJson.optString("client_message_id", "msg_$msgId")

                        if (isDuplicate("new_$msgId") || isDuplicate(clientUuid)) return

                        val currentUserId = tokenStorageManager.getUserId()
                        val senderId = msgJson.optLong("sender_id")
                        val senderJson = msgJson.optJSONObject("sender")
                        val senderName = senderJson?.optString("name") ?: msgJson.optString("sender_name", "User")

                        val message = Message(
                            id = msgId,
                            conversationId = convId,
                            senderId = senderId,
                            senderName = senderName,
                            body = msgJson.optString("body", ""),
                            type = msgJson.optString("type", "text"),
                            clientMessageId = clientUuid,
                            deliveryStatus = DeliveryStatus.DELIVERED,
                            isMine = senderId == currentUserId,
                            createdAt = System.currentTimeMillis()
                        )
                        scope.launch { _messageNewFlow.emit(MessageNewEvent(convId, message)) }
                    }
                }

                "message.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgId = data.optLong("message_id")
                        val body = data.optString("body", "")
                        val isEdited = data.optBoolean("is_edited", true)
                        val editedAt = data.optString("edited_at", "")

                        scope.launch {
                            _messageUpdatedFlow.emit(
                                MessageUpdatedEvent(convId, msgId, body, isEdited, editedAt)
                            )
                        }
                    }
                }

                "message.deleted" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgId = data.optLong("message_id")
                        val forEveryone = data.optBoolean("for_everyone", true)

                        scope.launch {
                            _messageDeletedFlow.emit(
                                MessageDeletedEvent(convId, msgId, forEveryone)
                            )
                        }
                    }
                }

                "message.delivered" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgId = data.optLong("message_id")
                        val deliveredAt = data.optString("delivered_at", "")

                        scope.launch {
                            _messageReceiptFlow.emit(
                                MessageReceiptEvent(convId, msgId, "delivered", deliveredAt)
                            )
                        }
                    }
                }

                "message.read" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val lastReadMsgId = if (data.has("last_read_message_id") && !data.isNull("last_read_message_id")) {
                            data.optLong("last_read_message_id")
                        } else null
                        val readAt = data.optString("read_at", "")

                        scope.launch {
                            _messageReceiptFlow.emit(
                                MessageReceiptEvent(convId, lastReadMsgId, "read", readAt)
                            )
                        }
                    }
                }

                "message.reaction" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgId = data.optLong("message_id")
                        val userId = data.optLong("user_id")
                        val userName = data.optString("user_name", "")
                        val reaction = data.optString("reaction", "")
                        val action = data.optString("action", "added")

                        scope.launch {
                            _messageReactionFlow.emit(
                                MessageReactionEvent(convId, msgId, userId, userName, reaction, action)
                            )
                        }
                    }
                }

                "user.typing" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val userId = data.optLong("user_id")
                        val userName = data.optString("user_name", "")
                        val isTyping = data.optBoolean("is_typing", true)

                        scope.launch {
                            _userTypingFlow.emit(
                                UserTypingEvent(convId, userId, userName, isTyping)
                            )
                        }
                    }
                }

                "user.presence" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id")
                        val isOnline = data.optBoolean("is_online", true)
                        val lastSeen = data.optString("last_seen", null)

                        scope.launch {
                            _userPresenceFlow.emit(
                                UserPresenceEvent(userId, isOnline, lastSeen)
                            )
                        }
                    }
                }

                "conversation.cleared" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        scope.launch {
                            _conversationClearedFlow.emit(ConversationClearedEvent(convId))
                        }
                    }
                }

                "call.incoming" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val callObj = data.optJSONObject("call")
                        val convId = data.optLong("conversation_id", callObj?.optLong("conversation_id") ?: 0L)
                        val callType = data.optString("call_type", callObj?.optString("call_type") ?: "audio")
                        val callerObj = data.optJSONObject("caller") ?: callObj?.optJSONObject("caller")
                        val callerId = callerObj?.optLong("id") ?: data.optLong("caller_id")
                        val callerName = callerObj?.optString("name") ?: "Caller"
                        val callerAvatar = callerObj?.optString("avatar", null)

                        scope.launch {
                            _callIncomingFlow.emit(
                                CallIncomingEvent(
                                    callId = callId,
                                    conversationId = convId,
                                    callType = callType,
                                    callerId = callerId,
                                    callerName = callerName,
                                    callerAvatar = callerAvatar
                                )
                            )
                        }
                    }
                }

                "call.accepted" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val userId = data.optLong("user_id")
                        val status = data.optString("status", "accepted")

                        scope.launch {
                            _callAcceptedFlow.emit(CallAcceptedEvent(callId, userId, status))
                        }
                    }
                }

                "call.rejected" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val userId = data.optLong("user_id")
                        val status = data.optString("status", "rejected")

                        scope.launch {
                            _callRejectedFlow.emit(CallRejectedEvent(callId, userId, status))
                        }
                    }
                }

                "call.ended" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val status = data.optString("status", "ended")
                        val duration = data.optInt("duration", 0)

                        scope.launch {
                            _callEndedFlow.emit(CallEndedEvent(callId, status, duration))
                        }
                    }
                }

                "call.signal" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val signalType = data.optString("signal_type")
                        val senderId = data.optLong("sender_id")
                        val senderName = data.optString("sender_name", null)
                        val targetUserId = if (data.has("target_user_id") && !data.isNull("target_user_id")) {
                            data.optLong("target_user_id")
                        } else null

                        val payloadObj = data.optJSONObject("payload")
                        val payloadMap = mutableMapOf<String, String>()
                        payloadObj?.keys()?.forEach { key ->
                            payloadMap[key] = payloadObj.optString(key, "")
                        }

                        scope.launch {
                            _callSignalFlow.emit(
                                CallSignalEvent(
                                    callId = callId,
                                    signalType = signalType,
                                    senderId = senderId,
                                    senderName = senderName,
                                    targetUserId = targetUserId,
                                    payload = payloadMap
                                )
                            )
                        }
                    }
                }

                "call.participant_state" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val callId = data.optLong("call_id")
                        val userId = data.optLong("user_id")
                        val isMuted = if (data.has("is_muted")) data.optBoolean("is_muted") else null
                        val isCameraOff = if (data.has("is_camera_off")) data.optBoolean("is_camera_off") else null
                        val isScreenSharing = if (data.has("is_screen_sharing")) data.optBoolean("is_screen_sharing") else null

                        scope.launch {
                            _callParticipantStateFlow.emit(
                                CallParticipantStateEvent(
                                    callId = callId,
                                    userId = userId,
                                    isMuted = isMuted,
                                    isCameraOff = isCameraOff,
                                    isScreenSharing = isScreenSharing
                                )
                            )
                        }
                    }
                }
            }
        } catch (_: Exception) {
            // Malformed frame ignored
        }
    }

    /**
     * Subscribe to a conversation private channel: private-conversation.{id}
     */
    fun subscribeConversation(conversationId: Long) {
        val channel = "private-conversation.$conversationId"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    /**
     * Unsubscribe from conversation private channel
     */
    fun unsubscribeConversation(conversationId: Long) {
        val channel = "private-conversation.$conversationId"
        subscribedChannels.remove(channel)
        val payload = JSONObject().apply {
            put("event", "pusher:unsubscribe")
            put("data", JSONObject().apply { put("channel", channel) })
        }
        webSocket?.send(payload.toString())
    }

    /**
     * Subscribe to the user's private notification channel: private-user.{id}
     */
    fun subscribeUserChannel(userId: Long) {
        val channel = "private-user.$userId"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    /**
     * Subscribe to presence channel for active friends presence
     */
    fun subscribePresenceChannel() {
        val channel = "presence-users"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    private fun resubscribeChannels() {
        subscribedChannels.forEach { channel ->
            authorizeAndSubscribe(channel)
        }
    }

    private fun authorizeAndSubscribe(channel: String) {
        val socketId = currentSocketId ?: return
        scope.launch {
            try {
                val authRes = api.broadcastingAuth(
                    mapOf("socket_id" to socketId, "channel_name" to channel)
                )
                if (authRes.isSuccessful && authRes.body() != null) {
                    val authBody = authRes.body()!!
                    val payload = JSONObject().apply {
                        put("event", "pusher:subscribe")
                        put("data", JSONObject().apply {
                            put("channel", channel)
                            put("auth", authBody.auth)
                            if (authBody.channel_data != null) {
                                put("channel_data", authBody.channel_data)
                            }
                        })
                    }
                    webSocket?.send(payload.toString())
                }
            } catch (_: Exception) {
                // If offline or network dropped, will re-subscribe upon next connection established
            }
        }
    }

    fun sendTyping(conversationId: Long, isTyping: Boolean) {
        scope.launch {
            try {
                api.presenceTyping(
                    mapOf(
                        "conversation_id" to conversationId,
                        "is_typing" to isTyping
                    )
                )
            } catch (_: Exception) {
            }
        }
    }

    private fun isDuplicate(key: String): Boolean {
        synchronized(processedEventKeys) {
            if (processedEventKeys.contains(key)) return true
            processedEventKeys.add(key)
            return false
        }
    }

    private fun scheduleReconnect() {
        reconnectAttempt++
        val backoffMillis = (1000L * (1 shl minOf(reconnectAttempt, 5))).coerceAtMost(30000L)
        _connectionState.value = ConnectionState.Reconnecting(reconnectAttempt)

        scope.launch {
            delay(backoffMillis)
            connect()
        }
    }

    fun disconnect() {
        isIntentionalClose = true
        webSocket?.close(1000, "Normal closure")
        _connectionState.value = ConnectionState.Disconnected
        currentSocketId = null
    }
}

