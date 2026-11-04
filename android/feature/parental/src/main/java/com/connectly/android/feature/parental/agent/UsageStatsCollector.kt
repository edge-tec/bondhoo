package com.connectly.android.feature.parental.agent

import android.app.usage.UsageStatsManager
import android.content.Context
import java.util.Calendar

/**
 * UsageStatsCollector
 *
 * Interacts with Android UsageStatsManager to extract foreground app usage intervals safely.
 * Requires user to have granted PACKAGE_USAGE_STATS permission in Settings.
 */
class UsageStatsCollector(private val context: Context) {

    fun queryDailyUsage(): List<AppUsagePayload> {
        val usageStatsManager = context.getSystemService(Context.USAGE_STATS_SERVICE) as? UsageStatsManager
            ?: return emptyList()

        val calendar = Calendar.getInstance().apply {
            set(Calendar.HOUR_OF_DAY, 0)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
            set(Calendar.MILLISECOND, 0)
        }

        val startTime = calendar.timeInMillis
        val endTime = System.currentTimeMillis()

        val stats = usageStatsManager.queryUsageStats(
            UsageStatsManager.INTERVAL_DAILY,
            startTime,
            endTime
        ) ?: return emptyList()

        return stats.filter { it.totalTimeInForeground > 1000 }.map {
            AppUsagePayload(
                applicationPackage = it.packageName,
                durationSeconds = (it.totalTimeInForeground / 1000).toInt(),
                eventStartedAt = java.time.Instant.ofEpochMilli(it.firstTimeStamp).toString(),
                eventEndedAt = java.time.Instant.ofEpochMilli(it.lastTimeStamp).toString()
            )
        }
    }
}

data class AppUsagePayload(
    val applicationPackage: String,
    val durationSeconds: Int,
    val eventStartedAt: String,
    val eventEndedAt: String
)

