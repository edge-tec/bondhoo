package com.connectly.android.feature.jobs.di

import com.connectly.android.feature.jobs.data.repository.JobRepositoryImpl
import com.connectly.android.feature.jobs.domain.repository.JobRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class JobsModule {

    @Binds
    @Singleton
    abstract fun bindJobRepository(
        impl: JobRepositoryImpl
    ): JobRepository
}

