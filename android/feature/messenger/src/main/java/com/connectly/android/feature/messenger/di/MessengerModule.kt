package com.connectly.android.feature.messenger.di

import com.connectly.android.feature.messenger.data.repository.MessengerRepositoryImpl
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class MessengerModule {

    @Binds
    @Singleton
    abstract fun bindMessengerRepository(
        messengerRepositoryImpl: MessengerRepositoryImpl
    ): MessengerRepository
}
