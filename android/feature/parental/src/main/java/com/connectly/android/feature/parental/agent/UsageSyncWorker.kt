package com.connectly.android.feature.parental.agent

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.connectly.android.feature.parental.data.ParentalApiService

/**
 * UsageSyncWorker
 *
 * Periodic WorkManager worker extracting daily foreground usage stats and syncing to Laravel backend.
 * Uses exponential backoff on network failures.
 */
class UsageSyncWorker(
    appContext: Context,
    workerParams: WorkerParameters,
    private val apiService: ParentalApiService,
    private val statsCollector: UsageStatsCollector
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        val bearerToken = inputData.getString("DEVICE_BEARER_TOKEN") ?: return Result.failure()

        return try {
            val stats = statsCollector.queryDailyUsage()
            if (stats.isEmpty()) {
                return Result.success()
            }

            val events = stats.map {
                mapOf(
                    "application_package" to it.applicationPackage,
                    "duration_seconds" to it.durationSeconds,
                    "event_started_at" to it.eventStartedAt,
                    "event_ended_at" to it.eventEndedAt
                )
            }

            val response = apiService.syncUsage(
                bearerToken = "Bearer $bearerToken",
                request = mapOf("events" to events)
            )

            if (response.isSuccessful) {
                Result.success()
            } else if (response.code() in 500..599) {
                Result.retry()
            } else {
                Result.failure()
            }
        } catch (e: Exception) {
            Result.retry()
        }
    }
}

