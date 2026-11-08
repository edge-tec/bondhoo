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

    // -------------------------------------------------------------------------
    // ENGINE 1 — MESSAGING FLOWS
    // -------------------------------------------------------------------------
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

    private val _conversationUpdatedFlow = MutableSharedFlow<ConversationUpdatedEvent>(extraBufferCapacity = 16)
    val conversationUpdatedFlow: SharedFlow<ConversationUpdatedEvent> = _conversationUpdatedFlow.asSharedFlow()

    // -------------------------------------------------------------------------
    // ENGINE 2 — CALLING FLOWS
    // -------------------------------------------------------------------------
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

    // -------------------------------------------------------------------------
    // ENGINE 3 — NOTIFICATION FLOWS
    // -------------------------------------------------------------------------
    private val _socialNotificationFlow = MutableSharedFlow<SocialNotificationEvent>(extraBufferCapacity = 32)
    val socialNotificationFlow: SharedFlow<SocialNotificationEvent> = _socialNotificationFlow.asSharedFlow()

    private val _notificationReadFlow = MutableSharedFlow<NotificationReadEvent>(extraBufferCapacity = 32)
    val notificationReadFlow: SharedFlow<NotificationReadEvent> = _notificationReadFlow.asSharedFlow()

    private val _notificationReadAllFlow = MutableSharedFlow<NotificationReadAllEvent>(extraBufferCapacity = 16)
    val notificationReadAllFlow: SharedFlow<NotificationReadAllEvent> = _notificationReadAllFlow.asSharedFlow()

    private val _notificationDeletedFlow = MutableSharedFlow<NotificationDeletedEvent>(extraBufferCapacity = 16)
    val notificationDeletedFlow: SharedFlow<NotificationDeletedEvent> = _notificationDeletedFlow.asSharedFlow()

    // -------------------------------------------------------------------------
    // ENGINE 4 — SOCIAL SYNCHRONIZATION FLOWS
    // -------------------------------------------------------------------------
    private val _postReactionFlow = MutableSharedFlow<PostReactionUpdatedEvent>(extraBufferCapacity = 32)
    val postReactionFlow: SharedFlow<PostReactionUpdatedEvent> = _postReactionFlow.asSharedFlow()

    private val _postCommentFlow = MutableSharedFlow<PostCommentUpdatedEvent>(extraBufferCapacity = 32)
    val postCommentFlow: SharedFlow<PostCommentUpdatedEvent> = _postCommentFlow.asSharedFlow()

    private val _pagePostPublishedFlow = MutableSharedFlow<PagePostPublishedEvent>(extraBufferCapacity = 16)
    val pagePostPublishedFlow: SharedFlow<PagePostPublishedEvent> = _pagePostPublishedFlow.asSharedFlow()

    private val _friendshipFlow = MutableSharedFlow<FriendshipEvent>(extraBufferCapacity = 32)
    val friendshipFlow: SharedFlow<FriendshipEvent> = _friendshipFlow.asSharedFlow()

    private val _storyFlow = MutableSharedFlow<StoryEvent>(extraBufferCapacity = 16)
    val storyFlow: SharedFlow<StoryEvent> = _storyFlow.asSharedFlow()

    private val _reelFlow = MutableSharedFlow<ReelEvent>(extraBufferCapacity = 32)
    val reelFlow: SharedFlow<ReelEvent> = _reelFlow.asSharedFlow()

    private val _liveStreamFlow = MutableSharedFlow<LiveStreamEvent>(extraBufferCapacity = 64)
    val liveStreamFlow: SharedFlow<LiveStreamEvent> = _liveStreamFlow.asSharedFlow()

    private val _groupFlow = MutableSharedFlow<GroupEvent>(extraBufferCapacity = 16)
    val groupFlow: SharedFlow<GroupEvent> = _groupFlow.asSharedFlow()

    private val _profileUpdatedFlow = MutableSharedFlow<ProfileUpdatedEvent>(extraBufferCapacity = 16)
    val profileUpdatedFlow: SharedFlow<ProfileUpdatedEvent> = _profileUpdatedFlow.asSharedFlow()

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
        val wsUrl = if (com.connectly.android.core.network.BuildConfig.DEBUG) {
            "ws://10.0.2.2:8080/app/connectly_reverb_key?protocol=7&client=android&version=1.0.0"
        } else {
            "wss://bondhoo.com/app/connectly_reverb_key?protocol=7&client=android&version=1.0.0"
        }
        val request = Request.Builder()
            .url(wsUrl)
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
                    val pong = JSONObject().apply {
                        put("event", "pusher:pong")
                        put("data", JSONObject())
                    }
                    webSocket?.send(pong.toString())
                }

                // =============================================================
                // ENGINE 1 — MESSAGING
                // =============================================================
                "message.new", "message.created" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgJson = data.optJSONObject("message") ?: data
                        val msgId = msgJson.optLong("id")
                        val clientUuid = msgJson.optString("client_message_id", "msg_$msgId")

                        if (isDuplicate("new_$msgId") || isDuplicate(clientUuid)) return

                        val currentUserId = tokenStorageManager.getUserId()
                        val senderJson = msgJson.optJSONObject("sender")
                        val senderId = msgJson.optLong("sender_id").takeIf { it > 0L }
                            ?: senderJson?.optLong("id")
                            ?: 0L
                        val senderName = senderJson?.optString("name")?.takeIf { it.isNotBlank() }
                            ?: msgJson.optString("sender_name", "User")
                        val senderAvatar = senderJson?.optString("avatar_url")?.takeIf { it.isNotBlank() }

                        val message = Message(
                            id = msgId,
                            conversationId = convId,
                            senderId = senderId,
                            senderName = senderName,
                            senderAvatar = senderAvatar,
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

                "message.deleted", "message.deleted_for_me" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val msgId = data.optLong("message_id")
                        val forEveryone = event == "message.deleted" || data.optBoolean("for_everyone", true)

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

                "user.typing", "client-typing" -> {
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
                        val lastSeen = if (data.has("last_seen") && !data.isNull("last_seen")) data.getString("last_seen") else null

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

                "conversation.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val convId = data.optLong("conversation_id")
                        val action = data.optString("action", "updated")
                        val payloadMap = mutableMapOf<String, String>()
                        data.keys().forEach { k -> payloadMap[k] = data.optString(k, "") }

                        scope.launch {
                            _conversationUpdatedFlow.emit(ConversationUpdatedEvent(convId, action, payloadMap))
                        }
                    }
                }

                // =============================================================
                // ENGINE 2 — WEBRTC CALLING
                // =============================================================
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
                        val callerAvatar = if (callerObj != null && callerObj.has("avatar") && !callerObj.isNull("avatar")) callerObj.getString("avatar") else null

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

                "call.ended", "call.left" -> {
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
                        val senderName = if (data.has("sender_name") && !data.isNull("sender_name")) data.getString("sender_name") else null
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

                // =============================================================
                // ENGINE 3 — NOTIFICATIONS
                // =============================================================
                "notification.new" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val notifObj = data.optJSONObject("notification") ?: data
                        val notifId = notifObj.optString("id", "")
                        val userId = data.optLong("user_id", tokenStorageManager.getUserId() ?: 0L)
                        val innerData = notifObj.optJSONObject("data")
                        val title = innerData?.optString("title") ?: notifObj.optString("title", "New Notification")
                        val message = innerData?.optString("message") ?: notifObj.optString("message", "")
                        val type = notifObj.optString("type", "general")
                        val actionUrl = if (innerData != null && innerData.has("action_url") && !innerData.isNull("action_url")) {
                            innerData.getString("action_url")
                        } else if (notifObj.has("action_url") && !notifObj.isNull("action_url")) {
                            notifObj.getString("action_url")
                        } else null
                        val createdAt = notifObj.optString("created_at", "")

                        if (isDuplicate("notif_$notifId")) return

                        scope.launch {
                            _socialNotificationFlow.emit(
                                SocialNotificationEvent(
                                    userId = userId,
                                    notificationId = notifId,
                                    title = title,
                                    message = message,
                                    type = type,
                                    actionUrl = actionUrl,
                                    createdAt = createdAt
                                )
                            )
                        }
                    }
                }

                "notification.read" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id")
                        val notifId = if (data.has("notification_id") && !data.isNull("notification_id")) data.getString("notification_id") else null
                        val unreadCount = data.optInt("unread_count", 0)

                        scope.launch {
                            _notificationReadFlow.emit(NotificationReadEvent(userId, notifId, unreadCount))
                        }
                    }
                }

                "notification.read.all" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id")
                        scope.launch {
                            _notificationReadAllFlow.emit(NotificationReadAllEvent(userId))
                        }
                    }
                }

                "notification.deleted" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id")
                        val notifId = data.optString("notification_id", "")
                        val unreadCount = data.optInt("unread_count", 0)

                        scope.launch {
                            _notificationDeletedFlow.emit(NotificationDeletedEvent(userId, notifId, unreadCount))
                        }
                    }
                }

                // =============================================================
                // ENGINE 4 — SOCIAL REAL-TIME SYNCHRONIZATION
                // =============================================================
                "post.reaction.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val postId = data.optLong("post_id")
                        val total = data.optInt("total_reactions")
                        val breakdownObj = data.optJSONObject("breakdown")
                        val breakdownMap = mutableMapOf<String, Int>()
                        breakdownObj?.keys()?.forEach { k ->
                            breakdownMap[k] = breakdownObj.optInt(k, 0)
                        }

                        scope.launch {
                            _postReactionFlow.emit(PostReactionUpdatedEvent(postId, total, breakdownMap))
                        }
                    }
                }

                "post.comment.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val postId = data.optLong("post_id")
                        val pageId = if (data.has("page_id") && !data.isNull("page_id")) data.optLong("page_id") else null
                        val commentId = data.optLong("comment_id")
                        val action = data.optString("action", "created")
                        val count = data.optInt("comments_count")
                        val commentObj = data.optJSONObject("comment")
                        val commentBody = commentObj?.optString("body")
                        val authorName = commentObj?.optJSONObject("user")?.optString("name")

                        scope.launch {
                            _postCommentFlow.emit(
                                PostCommentUpdatedEvent(
                                    postId = postId,
                                    pageId = pageId,
                                    commentId = commentId,
                                    action = action,
                                    commentsCount = count,
                                    commentBody = commentBody,
                                    authorName = authorName
                                )
                            )
                        }
                    }
                }

                "page.post.published" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val pageId = data.optLong("page_id")
                        val postObj = data.optJSONObject("post")
                        val postId = postObj?.optLong("id") ?: data.optLong("post_id")
                        val content = postObj?.optString("content")
                        val authorName = postObj?.optJSONObject("user")?.optString("name")

                        scope.launch {
                            _pagePostPublishedFlow.emit(
                                PagePostPublishedEvent(
                                    pageId = pageId,
                                    postId = postId,
                                    content = content,
                                    authorName = authorName
                                )
                            )
                        }
                    }
                }

                // Friendship lifecycle events
                "friend.request.sent", "friend.request.received", "friend.request.accepted",
                "friend.request.rejected", "friend.request.cancelled", "friend.removed",
                "friend.followed", "friend.unfollowed", "friend.counters.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id", tokenStorageManager.getUserId() ?: 0L)
                        val friendId = data.optLong("friend_id", data.optLong("target_id", 0L))

                        val countersMap = mutableMapOf<String, Int>()
                        val countersObj = data.optJSONObject("counters")
                        countersObj?.keys()?.forEach { k -> countersMap[k] = countersObj.optInt(k, 0) }

                        scope.launch {
                            _friendshipFlow.emit(
                                FriendshipEvent(
                                    userId = userId,
                                    targetId = friendId,
                                    action = event.removePrefix("friend."),
                                    counters = countersMap
                                )
                            )
                        }
                    }
                }

                // Ephemeral Story events
                "story.created", "story.deleted", "story.expired" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val storyId = data.optLong("story_id", data.optLong("id"))
                        val action = event.removePrefix("story.")

                        scope.launch {
                            _storyFlow.emit(StoryEvent(storyId = storyId, action = action))
                        }
                    }
                }

                // Reel events
                "reel.created", "reel.reaction.updated", "reel.comment.created",
                "reel.deleted", "reel.expired" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val reelId = data.optLong("reel_id", data.optLong("id"))
                        val likes = data.optInt("likes_count")
                        val comments = data.optInt("comments_count")
                        val action = event.removePrefix("reel.")

                        scope.launch {
                            _reelFlow.emit(ReelEvent(reelId, action, likes, comments))
                        }
                    }
                }

                // Live Streaming events
                "live.started", "live.ended", "live.comment.received", "live.reaction.created", "gift.received" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val channelId = data.optString("channel_id", "")
                        val senderName = data.optString("sender_name", data.optString("user_name"))
                        val msg = data.optString("comment", data.optString("gift_type"))

                        val payload = mutableMapOf<String, Any?>()
                        data.keys().forEach { k -> payload[k] = data.opt(k) }

                        scope.launch {
                            _liveStreamFlow.emit(
                                LiveStreamEvent(
                                    channelId = channelId,
                                    action = event,
                                    senderName = senderName,
                                    message = msg
                                )
                            )
                        }
                    }
                }

                // Group events
                "group.post.created", "group.announcement.published", "group.poll.voted", "group.member.joined" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val groupId = data.optLong("group_id")
                        val title = data.optString("title", data.optString("content"))

                        scope.launch {
                            _groupFlow.emit(
                                GroupEvent(
                                    groupId = groupId,
                                    action = event.removePrefix("group."),
                                    title = title
                                )
                            )
                        }
                    }
                }

                // Profile updates (bio, details, avatar, cover)
                "profile.updated", "profile.avatar.updated", "profile.cover.updated" -> {
                    if (dataStr.isNotBlank()) {
                        val data = JSONObject(dataStr)
                        val userId = data.optLong("user_id", data.optLong("id"))
                        val username = data.optString("username", "")
                        val avatarUrl = if (data.has("avatar_url") && !data.isNull("avatar_url")) data.getString("avatar_url") else null
                        val coverUrl = if (data.has("cover_url") && !data.isNull("cover_url")) data.getString("cover_url") else null
                        val name = if (data.has("name") && !data.isNull("name")) data.getString("name") else null

                        scope.launch {
                            _profileUpdatedFlow.emit(
                                ProfileUpdatedEvent(
                                    userId = userId,
                                    username = username,
                                    avatarUrl = avatarUrl,
                                    coverUrl = coverUrl,
                                    name = name
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

    // -------------------------------------------------------------------------
    // CHANNEL SUBSCRIPTIONS
    // -------------------------------------------------------------------------

    fun subscribeConversation(conversationId: Long) {
        val channel = "private-conversation.$conversationId"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    fun unsubscribeConversation(conversationId: Long) {
        val channel = "private-conversation.$conversationId"
        subscribedChannels.remove(channel)
        sendUnsubscribe(channel)
    }

    fun subscribeUserChannel(userId: Long) {
        val channel = "private-user.$userId"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    fun unsubscribeUserChannel(userId: Long) {
        val channel = "private-user.$userId"
        subscribedChannels.remove(channel)
        sendUnsubscribe(channel)
    }

    fun subscribePresenceChannel() {
        val channel = "presence-users"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    fun subscribePostChannel(postId: Long) {
        val channel = "post.$postId"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun unsubscribePostChannel(postId: Long) {
        val channel = "post.$postId"
        subscribedChannels.remove(channel)
        sendUnsubscribe(channel)
    }

    fun subscribeStoriesChannel() {
        val channel = "stories"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun subscribeReelsChannel() {
        val channel = "reels"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun subscribeReelChannel(reelId: Long) {
        val channel = "reel.$reelId"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun subscribeLiveChannel(channelId: String) {
        val channel = "live.$channelId"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun subscribeGroupChannel(groupId: Long) {
        val channel = "group.$groupId"
        subscribedChannels.add(channel)
        authorizeAndSubscribe(channel)
    }

    fun subscribeProfileChannel(username: String) {
        val channel = "profile.$username"
        subscribedChannels.add(channel)
        sendPublicSubscribe(channel)
    }

    fun unsubscribeProfileChannel(username: String) {
        val channel = "profile.$username"
        subscribedChannels.remove(channel)
        sendUnsubscribe(channel)
    }

    private fun sendPublicSubscribe(channel: String) {
        val payload = JSONObject().apply {
            put("event", "pusher:subscribe")
            put("data", JSONObject().apply {
                put("channel", channel)
            })
        }
        webSocket?.send(payload.toString())
    }

    private fun sendUnsubscribe(channel: String) {
        val payload = JSONObject().apply {
            put("event", "pusher:unsubscribe")
            put("data", JSONObject().apply {
                put("channel", channel)
            })
        }
        webSocket?.send(payload.toString())
    }

    fun resubscribeChannels() {
        subscribedChannels.forEach { channel ->
            if (channel.startsWith("private-") || channel.startsWith("presence-")) {
                authorizeAndSubscribe(channel)
            } else {
                sendPublicSubscribe(channel)
            }
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
                // Will re-attempt upon next connection established
            }
        }
    }

    fun sendTyping(conversationId: Long, isTyping: Boolean) {
        scope.launch {
            try {
                api.presenceTyping(
                    mapOf(
                        "conversation_id" to conversationId.toString(),
                        "is_typing" to isTyping.toString()
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
