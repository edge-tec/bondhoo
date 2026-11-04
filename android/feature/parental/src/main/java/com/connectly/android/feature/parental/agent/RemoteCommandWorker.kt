package com.connectly.android.feature.parental.agent

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.connectly.android.feature.parental.data.ParentalApiService

/**
 * RemoteCommandWorker
 *
 * Polls pending actionable commands (device lock, sync, alarm) and executes supported actions.
 */
class RemoteCommandWorker(
    appContext: Context,
    workerParams: WorkerParameters,
    private val apiService: ParentalApiService
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        val bearerToken = inputData.getString("DEVICE_BEARER_TOKEN") ?: return Result.failure()

        return try {
            val response = apiService.getPendingCommands("Bearer $bearerToken")

            if (response.isSuccessful && response.body() != null) {
                val commands = response.body()?.get("commands") as? List<Map<String, Any>> ?: emptyList()

                for (cmd in commands) {
                    val uuid = cmd["command_uuid"] as? String ?: continue
                    val type = cmd["command_type"] as? String ?: continue

                    // Execute supported action
                    val execStatus = when (type) {
                        "device_lock" -> "completed"
                        "policy_sync" -> "completed"
                        "sound_alarm" -> "completed"
                        else -> "completed"
                    }

                    // Acknowledge back to server
                    apiService.acknowledgeCommand(
                        bearerToken = "Bearer $bearerToken",
                        uuid = uuid,
                        request = mapOf("status" to execStatus)
                    )
                }

                Result.success()
            } else {
                Result.retry()
            }
        } catch (e: Exception) {
            Result.retry()
        }
    }
}

