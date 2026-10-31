package com.connectly.android.feature.events.di

import com.connectly.android.feature.events.data.repository.EventsRepositoryImpl
import com.connectly.android.feature.events.domain.repository.EventsRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class EventsModule {

    @Binds
    @Singleton
    abstract fun bindEventsRepository(
        impl: EventsRepositoryImpl
    ): EventsRepository
}

