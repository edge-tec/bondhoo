package com.connectly.android.feature.notifications.data.fcm

import android.content.Context
import android.os.Build
import android.provider.Settings
import android.util.Log
import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.RegisterDeviceRequest
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class DeviceTokenManager @Inject constructor(
    @ApplicationContext private val context: Context,
    private val api: ConnectlyApi,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher = Dispatchers.IO
) {
    companion object {
        private const val TAG = "DeviceTokenManager"
        private const val PREFS_NAME = "connectly_device_prefs"
        private const val KEY_DEVICE_ID = "device_id"
        private const val KEY_PUSH_TOKEN = "fcm_push_token"
        private const val KEY_SESSION_ID = "backend_session_id"
    }

    private val scope = CoroutineScope(SupervisorJob() + ioDispatcher)
    private val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    val deviceId: String
        get() {
            var id = prefs.getString(KEY_DEVICE_ID, null)
            if (id.isNullOrEmpty()) {
                val androidId = try {
                    Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID)
                } catch (_: Exception) {
                    null
                }
                id = androidId ?: "android_${UUID.randomUUID()}"
                prefs.edit().putString(KEY_DEVICE_ID, id).apply()
            }
            return id
        }

    var currentPushToken: String?
        get() = prefs.getString(KEY_PUSH_TOKEN, null)
        private set(value) {
            prefs.edit().putString(KEY_PUSH_TOKEN, value).apply()
        }

    var backendSessionId: Long?
        get() {
            val id = prefs.getLong(KEY_SESSION_ID, -1L)
            return if (id > 0) id else null
        }
        private set(value) {
            if (value != null && value > 0) {
                prefs.edit().putLong(KEY_SESSION_ID, value).apply()
            } else {
                prefs.edit().remove(KEY_SESSION_ID).apply()
            }
        }

    /**
     * Initialize FCM token retrieval on application start.
     */
    fun initializeToken() {
        try {
            FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
                if (task.isSuccessful && !task.result.isNullOrEmpty()) {
                    val token = task.result
                    Log.d(TAG, "FCM token retrieved successfully (length: ${token.length})")
                    onNewToken(token)
                } else {
                    Log.w(TAG, "FCM token retrieval failed or returned null", task.exception)
                }
            }
        } catch (e: Exception) {
            Log.w(TAG, "FirebaseMessaging unavailable in current environment: ${e.message}")
        }
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
                val response = api.registerDevice(
                    RegisterDeviceRequest(
                        device_id = deviceId,
                        device_name = "${Build.MANUFACTURER} ${Build.MODEL}",
                        platform = "android",
                        app_version = "1.0.0",
                        push_token = token
                    )
                )
                if (response.isSuccessful) {
                    val sessionDto = response.body()?.data
                    sessionDto?.id?.let { id ->
                        backendSessionId = id
                        Log.d(TAG, "Device registered successfully with session ID $id")
                    }
                } else {
                    Log.w(TAG, "Failed to register device: HTTP ${response.code()}")
                }
            } catch (e: Exception) {
                Log.w(TAG, "Failed to sync token with backend: ${e.message}")
            }
        }
    }

    /**
     * Revoke push token and device registration on user logout.
     */
    fun revokeToken(onComplete: (() -> Unit)? = null) {
        scope.launch {
            val sessionId = backendSessionId
            try {
                if (sessionId != null) {
                    api.revokeDeviceSession(sessionId)
                } else {
                    // Fallback: register null token to stop push dispatch
                    api.registerDevice(
                        RegisterDeviceRequest(
                            device_id = deviceId,
                            device_name = "${Build.MANUFACTURER} ${Build.MODEL}",
                            platform = "android",
                            app_version = "1.0.0",
                            push_token = null
                        )
                    )
                }
            } catch (e: Exception) {
                Log.w(TAG, "Error revoking backend device session: ${e.message}")
            }

            try {
                withContext(ioDispatcher) {
                    FirebaseMessaging.getInstance().deleteToken()
                }
            } catch (e: Exception) {
                Log.w(TAG, "Error deleting FCM token: ${e.message}")
            }

            currentPushToken = null
            backendSessionId = null
            onComplete?.invoke()
        }
    }
}
