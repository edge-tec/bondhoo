package com.connectly.android

import android.app.Application
import com.connectly.android.feature.notifications.data.fcm.DeviceTokenManager
import dagger.hilt.android.HiltAndroidApp
import javax.inject.Inject

@HiltAndroidApp
class ConnectlyApp : Application() {

    @Inject
    lateinit var deviceTokenManager: DeviceTokenManager

    override fun onCreate() {
        super.onCreate()
        deviceTokenManager.initializeToken()
    }
}

