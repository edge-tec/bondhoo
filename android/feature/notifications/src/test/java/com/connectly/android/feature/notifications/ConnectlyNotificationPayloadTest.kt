package com.connectly.android.feature.notifications

import com.connectly.android.core.model.CallType
import com.connectly.android.core.webrtc.notification.CallNotificationManager
import com.connectly.android.core.webrtc.session.CallSessionManager
import com.connectly.android.feature.notifications.data.fcm.ConnectlyFirebaseMessagingService
import com.connectly.android.feature.notifications.data.fcm.DeviceTokenManager
import com.connectly.android.feature.notifications.data.fcm.NotificationChannelManager
import com.google.firebase.messaging.RemoteMessage
import io.mockk.every
import io.mockk.mockk
import io.mockk.verify
import org.junit.Before
import org.junit.Test

class ConnectlyNotificationPayloadTest {

    private val channelManager: NotificationChannelManager = mockk(relaxed = true)
    private val deviceTokenManager: DeviceTokenManager = mockk(relaxed = true)
    private val callNotificationManager: CallNotificationManager = mockk(relaxed = true)
    private val callSessionManager: CallSessionManager = mockk(relaxed = true)

    private lateinit var service: ConnectlyFirebaseMessagingService

    @Before
    fun setUp() {
        service = ConnectlyFirebaseMessagingService().apply {
            this.channelManager = this@ConnectlyNotificationPayloadTest.channelManager
            this.deviceTokenManager = this@ConnectlyNotificationPayloadTest.deviceTokenManager
            this.callNotificationManager = this@ConnectlyNotificationPayloadTest.callNotificationManager
            this.callSessionManager = this@ConnectlyNotificationPayloadTest.callSessionManager
        }
    }

    @Test
    fun `onNewToken delegates token to DeviceTokenManager`() {
        val testToken = "test_fcm_token_rotation_456"
        service.onNewToken(testToken)

        verify {
            deviceTokenManager.onNewToken(testToken)
        }
    }

    @Test
    fun `onMessageReceived routes incoming call payload to call managers`() {
        val remoteMessage = mockk<RemoteMessage>()
        val payloadData = mapOf(
            "type" to "call.incoming",
            "call_id" to "888",
            "conversation_id" to "12",
            "caller_id" to "42",
            "caller_name" to "Tania Rahman",
            "call_type" to "video"
        )
        every { remoteMessage.data } returns payloadData
        every { remoteMessage.notification } returns null
        every { remoteMessage.from } returns "fcm"

        service.onMessageReceived(remoteMessage)

        verify {
            callNotificationManager.showIncomingCallNotification(
                callId = 888L,
                callerName = "Tania Rahman",
                callType = CallType.VIDEO
            )
            callSessionManager.handleIncomingCall(
                callId = 888L,
                conversationId = 12L,
                callerId = 42L,
                callerName = "Tania Rahman",
                callType = CallType.VIDEO
            )
        }
    }

    @Test
    fun `onMessageReceived routes chat message payload to messages channel`() {
        val remoteMessage = mockk<RemoteMessage>()
        val payloadData = mapOf(
            "type" to "chat.message",
            "conversation_id" to "105",
            "message_id" to "999",
            "sender_name" to "Kamrul Hasan",
            "message" to "Hey, are you free to talk?"
        )
        every { remoteMessage.data } returns payloadData
        every { remoteMessage.notification } returns null
        every { remoteMessage.from } returns "fcm"

        service.onMessageReceived(remoteMessage)

        verify {
            channelManager.showNotification(
                notificationId = any(),
                channelId = NotificationChannelManager.CHANNEL_MESSAGES,
                title = "Kamrul Hasan",
                message = "Hey, are you free to talk?",
                deepLinkUri = "connectly://messenger/chat/105",
                tag = match { it.startsWith("msg_105_") }
            )
        }
    }

    @Test
    fun `onMessageReceived routes call ended payload to dismiss notification`() {
        val remoteMessage = mockk<RemoteMessage>()
        val payloadData = mapOf("type" to "call.ended")
        every { remoteMessage.data } returns payloadData
        every { remoteMessage.notification } returns null
        every { remoteMessage.from } returns "fcm"

        service.onMessageReceived(remoteMessage)

        verify {
            callNotificationManager.dismissIncomingCallNotification()
        }
    }
}

