package com.connectly.android.feature.notifications.data.fcm

import android.content.Context
import android.os.Build
import android.provider.Settings
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.RegisterDeviceRequest
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class DeviceTokenManager @Inject constructor(
    @ApplicationContext private val context: Context,
    private val api: ConnectlyApi
) {
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private val prefs = context.getSharedPreferences("connectly_device_prefs", Context.MODE_PRIVATE)

    val deviceId: String
        get() {
            var id = prefs.getString("device_id", null)
            if (id.isNullOrEmpty()) {
                val androidId = Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID)
                id = androidId ?: "android_${UUID.randomUUID()}"
                prefs.edit().putString("device_id", id).apply()
            }
            return id
        }

    var currentPushToken: String?
        get() = prefs.getString("fcm_push_token", null)
        private set(value) {
            prefs.edit().putString("fcm_push_token", value).apply()
        }

    /**
     * Called whenever a new FCM token is generated or rotated.
     */
    fun onNewToken(token: String) {
        currentPushToken = token
        syncTokenWithBackend(token)
    }

    /**
     * Register current device and token with backend.
     */
    fun syncTokenWithBackend(token: String? = currentPushToken) {
        scope.launch {
            try {
                api.registerDevice(
                    RegisterDeviceRequest(
                        device_id = deviceId,
                        device_name = "${Build.MANUFACTURER} ${Build.MODEL}",
                        platform = "android",
                        app_version = "1.0.0",
                        push_token = token
                    )
                )
            } catch (_: Exception) {
                // Scheduled retry on next connectivity change
            }
        }
    }
}

