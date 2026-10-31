package com.connectly.android.feature.live.di

import com.connectly.android.feature.live.data.repository.LiveRepositoryImpl
import com.connectly.android.feature.live.domain.repository.LiveRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class LiveModule {

    @Binds
    @Singleton
    abstract fun bindLiveRepository(
        impl: LiveRepositoryImpl
    ): LiveRepository
}

