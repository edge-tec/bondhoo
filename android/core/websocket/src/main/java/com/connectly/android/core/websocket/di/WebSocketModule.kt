package com.connectly.android.core.websocket.di

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.websocket.ReverbWebSocketManager
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.CoroutineDispatcher
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object WebSocketModule {

    @Provides
    @Singleton
    fun provideReverbWebSocketManager(
        api: ConnectlyApi,
        tokenStorageManager: TokenStorageManager,
        @Dispatcher(ConnectlyDispatchers.IO) ioDispatcher: CoroutineDispatcher
    ): ReverbWebSocketManager {
        return ReverbWebSocketManager(api, tokenStorageManager, ioDispatcher)
    }
}
