package com.connectly.android.feature.stories.di

import com.connectly.android.feature.stories.data.repository.StoriesRepositoryImpl
import com.connectly.android.feature.stories.domain.repository.StoriesRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class StoriesModule {

    @Binds
    @Singleton
    abstract fun bindStoriesRepository(
        storiesRepositoryImpl: StoriesRepositoryImpl
    ): StoriesRepository
}

