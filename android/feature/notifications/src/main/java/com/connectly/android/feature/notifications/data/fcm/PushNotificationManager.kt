package com.connectly.android.feature.notifications.data.fcm

import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PushNotificationManager @Inject constructor(
    private val deviceTokenManager: DeviceTokenManager,
    private val notificationChannelManager: NotificationChannelManager
) {
    val deviceId: String
        get() = deviceTokenManager.deviceId

    val pushToken: String?
        get() = deviceTokenManager.currentPushToken

    fun onTokenRefresh(token: String) {
        deviceTokenManager.onNewToken(token)
    }

    fun syncDeviceSession() {
        deviceTokenManager.syncTokenWithBackend()
    }

    fun showMessageNotification(
        notificationId: Int,
        senderName: String,
        message: String,
        conversationId: Long
    ) {
        notificationChannelManager.showNotification(
            notificationId = notificationId,
            channelId = NotificationChannelManager.CHANNEL_MESSAGES,
            title = senderName,
            message = message,
            deepLinkUri = "connectly://messenger/chat/$conversationId",
            tag = "msg_$conversationId"
        )
    }

    fun showSocialNotification(
        notificationId: Int,
        title: String,
        message: String,
        targetUrl: String?
    ) {
        notificationChannelManager.showNotification(
            notificationId = notificationId,
            channelId = NotificationChannelManager.CHANNEL_SOCIAL,
            title = title,
            message = message,
            deepLinkUri = targetUrl ?: "connectly://notifications",
            tag = "social_$notificationId"
        )
    }

    fun cancelNotification(notificationId: Int, tag: String? = null) {
        notificationChannelManager.cancelNotification(tag, notificationId)
    }
}

