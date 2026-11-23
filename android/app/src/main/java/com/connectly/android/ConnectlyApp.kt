package com.connectly.android

import android.app.Application
import com.connectly.android.feature.notifications.data.fcm.DeviceTokenManager
import com.google.firebase.crashlytics.FirebaseCrashlytics
import dagger.hilt.android.HiltAndroidApp
import javax.inject.Inject

@HiltAndroidApp
class ConnectlyApp : Application() {

    @Inject
    lateinit var deviceTokenManager: DeviceTokenManager

    override fun onCreate() {
        super.onCreate()
        configureCrashlytics()
        deviceTokenManager.initializeToken()
    }

    private fun configureCrashlytics() {
        runCatching {
            val crashlytics = FirebaseCrashlytics.getInstance()
            crashlytics.setCrashlyticsCollectionEnabled(!BuildConfig.DEBUG)
            crashlytics.setCustomKey("app_version_name", BuildConfig.VERSION_NAME)
            crashlytics.setCustomKey("app_version_code", BuildConfig.VERSION_CODE)
            crashlytics.setCustomKey("application_id", BuildConfig.APPLICATION_ID)
        }
    }
}

