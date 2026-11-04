package com.connectly.android.feature.parental.agent

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.connectly.android.feature.parental.data.ParentalApiService

/**
 * PolicySyncWorker
 *
 * WorkManager task to pull latest policy version, bedtime curfews, and app blacklists.
 */
class PolicySyncWorker(
    appContext: Context,
    workerParams: WorkerParameters,
    private val apiService: ParentalApiService
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        val bearerToken = inputData.getString("DEVICE_BEARER_TOKEN") ?: return Result.failure()

        return try {
            val response = apiService.getPolicies("Bearer $bearerToken")

            if (response.isSuccessful && response.body() != null) {
                // Securely cache latest policy in local DataStore or EncryptedSharedPreferences
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

