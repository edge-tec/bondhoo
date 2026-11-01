package com.connectly.android.core.webrtc.di

import android.content.Context
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.webrtc.audio.CallAudioManager
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.core.webrtc.notification.CallNotificationManager
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import com.connectly.android.core.websocket.ReverbWebSocketManager
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object WebRtcModule {

    @Provides
    @Singleton
    fun provideCallAudioManager(
        @ApplicationContext context: Context
    ): CallAudioManager {
        return CallAudioManager(context)
    }

    @Provides
    @Singleton
    fun provideCallNotificationManager(
        @ApplicationContext context: Context
    ): CallNotificationManager {
        return CallNotificationManager(context)
    }

    @Provides
    @Singleton
    fun provideWebRtcSignalingCoordinator(
        api: ConnectlyApi,
        webSocketManager: ReverbWebSocketManager,
        tokenStorage: TokenStorageManager
    ): WebRtcSignalingCoordinator {
        return WebRtcSignalingCoordinator(api, webSocketManager, tokenStorage)
    }

    @Provides
    @Singleton
    fun provideWebRtcEngine(
        signalingCoordinator: WebRtcSignalingCoordinator,
        audioManager: CallAudioManager,
        notificationManager: CallNotificationManager
    ): WebRtcEngine {
        return WebRtcEngine(signalingCoordinator, audioManager, notificationManager)
    }
}
