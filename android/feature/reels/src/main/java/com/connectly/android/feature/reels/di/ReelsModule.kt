package com.connectly.android.feature.reels.di

import com.connectly.android.feature.reels.data.repository.ReelsRepositoryImpl
import com.connectly.android.feature.reels.domain.repository.ReelsRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class ReelsModule {

    @Binds
    @Singleton
    abstract fun bindReelsRepository(
        reelsRepositoryImpl: ReelsRepositoryImpl
    ): ReelsRepository
}

