package com.connectly.android.feature.parental.data

import android.content.Context
import android.content.SharedPreferences
import com.connectly.android.feature.parental.model.ChildDevice
import java.util.UUID

/**
 * DeviceRegistrationManager
 *
 * Manages device UUID generation and pairing code redemption.
 */
class DeviceRegistrationManager(
    private val context: Context,
    private val apiService: ParentalApiService
) {
    private val prefs: SharedPreferences = context.getSharedPreferences("connectly_device_prefs", Context.MODE_PRIVATE)

    fun getOrCreateDeviceUuid(): String {
        var uuid = prefs.getString("DEVICE_UUID", null)
        if (uuid == null) {
            uuid = UUID.randomUUID().toString()
            prefs.edit().putString("DEVICE_UUID", uuid).apply()
        }
        return uuid
    }

    suspend fun pairDevice(
        pairingCode: String,
        deviceName: String,
        androidVersion: String = android.os.Build.VERSION.RELEASE,
        appVersion: String = "1.0.0"
    ): Result<String> {
        val uuid = getOrCreateDeviceUuid()

        return try {
            val response = apiService.pairDevice(
                mapOf(
                    "pairing_code" to pairingCode,
                    "device_uuid" to uuid,
                    "device_name" to deviceName,
                    "platform" to "android",
                    "android_version" to androidVersion,
                    "app_version" to appVersion
                )
            )

            if (response.isSuccessful && response.body() != null) {
                val token = response.body()?.get("device_token") as? String
                if (token != null) {
                    prefs.edit().putString("DEVICE_BEARER_TOKEN", token).apply()
                    Result.success(token)
                } else {
                    Result.failure(Exception("Token missing in response."))
                }
            } else {
                Result.failure(Exception(response.errorBody()?.string() ?: "Pairing failed."))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    fun getDeviceToken(): String? {
        return prefs.getString("DEVICE_BEARER_TOKEN", null)
    }

    fun isPaired(): Boolean {
        return !getDeviceToken().isNullOrBlank()
    }
}

