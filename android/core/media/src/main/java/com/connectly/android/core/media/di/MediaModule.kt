package com.connectly.android.core.media.di

import android.content.Context
import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.media.player.ConnectlyMedia3PlayerManager
import com.connectly.android.core.media.uploader.MediaUploadManager
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.CoroutineDispatcher
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object MediaModule {

    @Provides
    @Singleton
    fun provideMediaUploadManager(
        api: ConnectlyApi,
        @Dispatcher(ConnectlyDispatchers.IO) ioDispatcher: CoroutineDispatcher
    ): MediaUploadManager = MediaUploadManager(api, ioDispatcher)

    @Provides
    @Singleton
    fun provideConnectlyMedia3PlayerManager(
        @ApplicationContext context: Context
    ): ConnectlyMedia3PlayerManager = ConnectlyMedia3PlayerManager(context)
}
