package com.connectly.android.feature.parental.agent

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.Build
import android.os.IBinder
import androidx.core.app.NotificationCompat

/**
 * ChildAgentService
 *
 * Foreground Android Service maintaining transparent Child Device supervision.
 * Shows a persistent non-dismissible notification to ensure zero covert surveillance.
 */
class ChildAgentService : Service() {

    companion object {
        const val CHANNEL_ID = "connectly_parental_supervision_channel"
        const val NOTIFICATION_ID = 9001
    }

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val notification: Notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("Connectly Family Safety Active")
            .setContentText("This device is protected and supervised by family safety policies.")
            .setSmallIcon(android.R.drawable.ic_lock_idle_lock)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()

        startForeground(NOTIFICATION_ID, notification)

        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Family Safety Supervision",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Notifies child when device protection is actively running."
            }
            val manager = getSystemService(NotificationManager::class.java)
            manager?.createNotificationChannel(channel)
        }
    }
}

