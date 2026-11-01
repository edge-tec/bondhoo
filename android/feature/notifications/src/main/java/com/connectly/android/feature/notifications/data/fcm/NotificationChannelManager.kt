package com.connectly.android.feature.notifications.data.fcm

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.net.Uri
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationChannelManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    companion object {
        const val CHANNEL_MESSAGES = "channel_connectly_messages"
        const val CHANNEL_CALLS = "channel_connectly_calls"
        const val CHANNEL_SOCIAL = "channel_connectly_social"
        const val CHANNEL_COMMUNITY = "channel_connectly_community"
        const val CHANNEL_LIVE = "channel_connectly_live"
        const val CHANNEL_SECURITY = "channel_connectly_security"
    }

    private val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

    init {
        createNotificationChannels()
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val audioAttributes = AudioAttributes.Builder()
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .setUsage(AudioAttributes.USAGE_NOTIFICATION_COMMUNICATION_INSTANT)
                .build()

            val callSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_RINGTONE)

            val channels = listOf(
                NotificationChannel(
                    CHANNEL_MESSAGES,
                    "Messages & Chats",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "Direct and group chat message alerts"
                    enableVibration(true)
                },
                NotificationChannel(
                    CHANNEL_CALLS,
                    "Voice & Video Calls",
                    NotificationManager.IMPORTANCE_MAX
                ).apply {
                    description = "Incoming audio and video call ringers"
                    enableVibration(true)
                    setSound(callSoundUri, audioAttributes)
                },
                NotificationChannel(
                    CHANNEL_SOCIAL,
                    "Likes, Reactions & Comments",
                    NotificationManager.IMPORTANCE_DEFAULT
                ).apply {
                    description = "Reactions, comments, mentions and friend requests"
                },
                NotificationChannel(
                    CHANNEL_COMMUNITY,
                    "Groups & Events",
                    NotificationManager.IMPORTANCE_DEFAULT
                ).apply {
                    description = "Community invitations and upcoming event reminders"
                },
                NotificationChannel(
                    CHANNEL_LIVE,
                    "Live Streaming",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "Alerts when creators go live"
                    enableVibration(true)
                },
                NotificationChannel(
                    CHANNEL_SECURITY,
                    "Security & Sessions",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "New login and security alerts"
                }
            )

            channels.forEach { notificationManager.createNotificationChannel(it) }
        }
    }

    /**
     * Dispatch an Android notification with deep link and deduplication.
     */
    fun showNotification(
        notificationId: Int,
        channelId: String,
        title: String,
        message: String,
        deepLinkUri: String? = null,
        tag: String? = null
    ) {
        val intent = if (!deepLinkUri.isNullOrEmpty()) {
            Intent(Intent.ACTION_VIEW, Uri.parse(deepLinkUri)).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            }
        } else {
            context.packageManager.getLaunchIntentForPackage(context.packageName)?.apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            } ?: Intent()
        }

        val pendingIntent = PendingIntent.getActivity(
            context,
            notificationId,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or (if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) PendingIntent.FLAG_IMMUTABLE else 0)
        )

        val builder = NotificationCompat.Builder(context, channelId)
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setContentTitle(title)
            .setContentText(message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)

        try {
            NotificationManagerCompat.from(context).notify(tag, notificationId, builder.build())
        } catch (_: SecurityException) {
            // Handled when user has disabled notifications on Android 13+
        }
    }

    fun cancelNotification(tag: String? = null, notificationId: Int) {
        notificationManager.cancel(tag, notificationId)
    }
}

