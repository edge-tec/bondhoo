package com.connectly.android.feature.notifications.data.fcm

import android.app.Service
import android.content.Intent
import android.os.IBinder
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

/**
 * Connectly Firebase Cloud Messaging Handler & Token Lifecycle Service.
 * Manages foreground/background notification dispatch, token rotation, and deep link routing.
 */
@AndroidEntryPoint
class ConnectlyFirebaseMessagingService : Service() {

    @Inject
    lateinit var channelManager: NotificationChannelManager

    @Inject
    lateinit var deviceTokenManager: DeviceTokenManager

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        intent?.extras?.let { bundle ->
            val type = bundle.getString("type") ?: "system"
            val title = bundle.getString("title") ?: "Connectly"
            val body = bundle.getString("body") ?: "You have a new update."
            val deepLink = bundle.getString("deep_link")
            val messageId = bundle.getString("message_id")?.hashCode() ?: (System.currentTimeMillis() % 100000).toInt()

            val channelId = when (type.lowercase()) {
                "message", "chat" -> NotificationChannelManager.CHANNEL_MESSAGES
                "call", "incoming_call" -> NotificationChannelManager.CHANNEL_CALLS
                "live", "stream" -> NotificationChannelManager.CHANNEL_LIVE
                "group", "event" -> NotificationChannelManager.CHANNEL_COMMUNITY
                "security" -> NotificationChannelManager.CHANNEL_SECURITY
                else -> NotificationChannelManager.CHANNEL_SOCIAL
            }

            channelManager.showNotification(
                notificationId = messageId,
                channelId = channelId,
                title = title,
                message = body,
                deepLinkUri = deepLink,
                tag = "connectly_$type"
            )
        }
        return START_NOT_STICKY
    }

    fun handleNewToken(token: String) {
        deviceTokenManager.onNewToken(token)
    }
}
