package com.connectly.android.feature.calling.di

import com.connectly.android.feature.calling.data.repository.CallingRepositoryImpl
import com.connectly.android.feature.calling.domain.repository.CallingRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class CallingModule {

    @Binds
    @Singleton
    abstract fun bindCallingRepository(
        impl: CallingRepositoryImpl
    ): CallingRepository
}

