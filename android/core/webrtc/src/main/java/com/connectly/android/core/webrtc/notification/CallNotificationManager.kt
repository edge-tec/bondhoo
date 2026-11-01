package com.connectly.android.core.webrtc.notification

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.os.Build
import androidx.core.app.NotificationCompat
import com.connectly.android.core.model.CallType
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class CallNotificationManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val notificationManager =
        context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

    companion object {
        const val CHANNEL_ID_INCOMING_CALLS = "connectly_incoming_calls"
        const val CHANNEL_ID_ONGOING_CALLS = "connectly_ongoing_calls"
        const val INCOMING_CALL_NOTIFICATION_ID = 2001
        const val ONGOING_CALL_NOTIFICATION_ID = 2002

        const val ACTION_ACCEPT_CALL = "com.connectly.android.ACTION_ACCEPT_CALL"
        const val ACTION_REJECT_CALL = "com.connectly.android.ACTION_REJECT_CALL"
        const val ACTION_END_CALL = "com.connectly.android.ACTION_END_CALL"
        const val EXTRA_CALL_ID = "extra_call_id"
    }

    init {
        createNotificationChannels()
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val ringtoneUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_RINGTONE)
            val audioAttributes = AudioAttributes.Builder()
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .setUsage(AudioAttributes.USAGE_NOTIFICATION_RINGTONE)
                .build()

            // Incoming calls channel: High importance, ringtone, vibration
            val incomingChannel = NotificationChannel(
                CHANNEL_ID_INCOMING_CALLS,
                "Incoming Calls",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Incoming audio and video call alerts"
                setSound(ringtoneUri, audioAttributes)
                enableVibration(true)
                vibrationPattern = longArrayOf(0, 1000, 500, 1000)
                lockscreenVisibility = NotificationCompat.VISIBILITY_PUBLIC
            }

            // Ongoing calls channel: Low importance (silent, persistent)
            val ongoingChannel = NotificationChannel(
                CHANNEL_ID_ONGOING_CALLS,
                "Ongoing Calls",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Active call status"
                setSound(null, null)
                enableVibration(false)
            }

            notificationManager.createNotificationChannel(incomingChannel)
            notificationManager.createNotificationChannel(ongoingChannel)
        }
    }

    fun showIncomingCallNotification(
        callId: Long,
        callerName: String,
        callType: CallType
    ) {
        val launchIntent = context.packageManager.getLaunchIntentForPackage(context.packageName)?.apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(EXTRA_CALL_ID, callId)
        }

        val fullScreenPendingIntent = PendingIntent.getActivity(
            context,
            callId.toInt(),
            launchIntent ?: Intent(),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val acceptIntent = Intent(ACTION_ACCEPT_CALL).apply {
            setPackage(context.packageName)
            putExtra(EXTRA_CALL_ID, callId)
        }
        val acceptPendingIntent = PendingIntent.getBroadcast(
            context,
            (callId + 10).toInt(),
            acceptIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val rejectIntent = Intent(ACTION_REJECT_CALL).apply {
            setPackage(context.packageName)
            putExtra(EXTRA_CALL_ID, callId)
        }
        val rejectPendingIntent = PendingIntent.getBroadcast(
            context,
            (callId + 20).toInt(),
            rejectIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val typeText = if (callType == CallType.VIDEO) "Incoming Video Call" else "Incoming Audio Call"

        val notification = NotificationCompat.Builder(context, CHANNEL_ID_INCOMING_CALLS)
            .setSmallIcon(android.R.drawable.stat_sys_phone_call)
            .setContentTitle(callerName)
            .setContentText(typeText)
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_CALL)
            .setAutoCancel(false)
            .setOngoing(true)
            .setFullScreenIntent(fullScreenPendingIntent, true)
            .setContentIntent(fullScreenPendingIntent)
            .addAction(android.R.drawable.ic_menu_call, "Accept", acceptPendingIntent)
            .addAction(android.R.drawable.ic_menu_close_clear_cancel, "Decline", rejectPendingIntent)
            .build()

        notificationManager.notify(INCOMING_CALL_NOTIFICATION_ID, notification)
    }

    fun showOngoingCallNotification(
        callId: Long,
        peerName: String,
        callType: CallType
    ) {
        val launchIntent = context.packageManager.getLaunchIntentForPackage(context.packageName)?.apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(EXTRA_CALL_ID, callId)
        }
        val contentPendingIntent = PendingIntent.getActivity(
            context,
            callId.toInt(),
            launchIntent ?: Intent(),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val endIntent = Intent(ACTION_END_CALL).apply {
            setPackage(context.packageName)
            putExtra(EXTRA_CALL_ID, callId)
        }
        val endPendingIntent = PendingIntent.getBroadcast(
            context,
            (callId + 30).toInt(),
            endIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val typeText = if (callType == CallType.VIDEO) "Connected Video Call" else "Connected Audio Call"

        val notification = NotificationCompat.Builder(context, CHANNEL_ID_ONGOING_CALLS)
            .setSmallIcon(android.R.drawable.stat_sys_phone_call)
            .setContentTitle(peerName)
            .setContentText(typeText)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .setCategory(NotificationCompat.CATEGORY_CALL)
            .setOngoing(true)
            .setUsesChronometer(true)
            .setContentIntent(contentPendingIntent)
            .addAction(android.R.drawable.ic_menu_close_clear_cancel, "End Call", endPendingIntent)
            .build()

        notificationManager.notify(ONGOING_CALL_NOTIFICATION_ID, notification)
    }

    fun dismissIncomingCallNotification() {
        notificationManager.cancel(INCOMING_CALL_NOTIFICATION_ID)
    }

    fun dismissOngoingCallNotification() {
        notificationManager.cancel(ONGOING_CALL_NOTIFICATION_ID)
    }

    fun dismissAllCallNotifications() {
        notificationManager.cancel(INCOMING_CALL_NOTIFICATION_ID)
        notificationManager.cancel(ONGOING_CALL_NOTIFICATION_ID)
    }
}
