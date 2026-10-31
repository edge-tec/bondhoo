package com.connectly.android

import android.app.Application
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class ConnectlyApp : Application() {
    override fun onCreate() {
        super.onCreate()
    }
}

