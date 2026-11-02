package com.connectly.android.feature.notifications.data.fcm

import android.util.Log
import com.connectly.android.core.model.CallType
import com.connectly.android.core.webrtc.notification.CallNotificationManager
import com.connectly.android.core.webrtc.session.CallSessionManager
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

/**
 * Connectly Firebase Cloud Messaging Handler & Token Lifecycle Service.
 * Extends FirebaseMessagingService to receive background push payloads,
 * wake up incoming call sessions, display notifications, and rotate device tokens.
 */
@AndroidEntryPoint
class ConnectlyFirebaseMessagingService : FirebaseMessagingService() {

    companion object {
        private const val TAG = "ConnectlyFCMService"
    }

    @Inject
    lateinit var channelManager: NotificationChannelManager

    @Inject
    lateinit var deviceTokenManager: DeviceTokenManager

    @Inject
    lateinit var callNotificationManager: CallNotificationManager

    @Inject
    lateinit var callSessionManager: CallSessionManager

    /**
     * Called when a new FCM registration token is generated or rotated.
     */
    override fun onNewToken(token: String) {
        Log.d(TAG, "New FCM token received (length: ${token.length})")
        deviceTokenManager.onNewToken(token)
    }

    /**
     * Called when an incoming FCM push notification is received (foreground or background).
     */
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        val data = remoteMessage.data
        val notification = remoteMessage.notification

        Log.d(TAG, "FCM message received from: ${remoteMessage.from}, data keys: ${data.keys}")

        val type = data["type"] ?: data["action"] ?: "social"
        val title = data["title"] ?: notification?.title ?: "Connectly"
        val body = data["body"] ?: data["message"] ?: notification?.body ?: "You have a new update."
        val messageId = data["message_id"]?.hashCode() ?: (System.currentTimeMillis() % 100000).toInt()

        when (type.lowercase()) {
            "call.incoming", "incoming_call" -> {
                handleIncomingCallPush(data, title)
            }

            "call.rejected", "call.ended" -> {
                Log.d(TAG, "Call ended/rejected remotely. Dismissing incoming call notification.")
                callNotificationManager.dismissIncomingCallNotification()
            }

            "call.missed", "missed_call" -> {
                callNotificationManager.dismissIncomingCallNotification()
                val callerName = data["caller_name"] ?: "Unknown"
                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_CALLS,
                    title = "Missed Call",
                    message = "You missed a call from $callerName",
                    deepLinkUri = "connectly://call/history",
                    tag = "call_missed_$messageId"
                )
            }

            "chat.message", "message", "chat" -> {
                val conversationId = data["conversation_id"] ?: ""
                val senderName = data["sender_name"] ?: title
                val deepLink = if (conversationId.isNotEmpty()) {
                    "connectly://messenger/chat/$conversationId"
                } else {
                    "connectly://messenger"
                }

                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_MESSAGES,
                    title = senderName,
                    message = body,
                    deepLinkUri = deepLink,
                    tag = "msg_${conversationId}_$messageId"
                )
            }

            "live", "stream", "live.stream" -> {
                val streamId = data["stream_id"] ?: ""
                val deepLink = if (streamId.isNotEmpty()) "connectly://live/$streamId" else "connectly://live"
                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_LIVE,
                    title = title,
                    message = body,
                    deepLinkUri = deepLink,
                    tag = "live_$messageId"
                )
            }

            "group", "event", "community" -> {
                val deepLink = data["deep_link"] ?: "connectly://groups"
                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_COMMUNITY,
                    title = title,
                    message = body,
                    deepLinkUri = deepLink,
                    tag = "community_$messageId"
                )
            }

            "security", "session", "security.alert" -> {
                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_SECURITY,
                    title = title,
                    message = body,
                    deepLinkUri = "connectly://settings/security",
                    tag = "security_$messageId"
                )
            }

            else -> {
                // Default social notifications (likes, reactions, comments, friend requests)
                val deepLink = data["deep_link"] ?: "connectly://notifications"
                channelManager.showNotification(
                    notificationId = messageId,
                    channelId = NotificationChannelManager.CHANNEL_SOCIAL,
                    title = title,
                    message = body,
                    deepLinkUri = deepLink,
                    tag = "social_$messageId"
                )
            }
        }
    }

    private fun handleIncomingCallPush(data: Map<String, String>, defaultTitle: String) {
        val callId = data["call_id"]?.toLongOrNull() ?: System.currentTimeMillis()
        val conversationId = data["conversation_id"]?.toLongOrNull()
        val callerId = data["caller_id"]?.toLongOrNull() ?: 0L
        val callerName = data["caller_name"] ?: defaultTitle
        val callTypeStr = data["call_type"] ?: "audio"
        val callType = if (callTypeStr.equals("video", ignoreCase = true)) CallType.VIDEO else CallType.AUDIO

        Log.i(TAG, "Processing incoming call push: callId=$callId, caller=$callerName, type=$callType")

        // 1. Display incoming call heads-up notification with accept/decline action intents & ringtone
        callNotificationManager.showIncomingCallNotification(
            callId = callId,
            callerName = callerName,
            callType = callType
        )

        // 2. Wake up CallSessionManager and prepare WebRTC engine for incoming peer connection
        callSessionManager.handleIncomingCall(
            callId = callId,
            conversationId = conversationId,
            callerId = callerId,
            callerName = callerName,
            callType = callType
        )
    }
}
