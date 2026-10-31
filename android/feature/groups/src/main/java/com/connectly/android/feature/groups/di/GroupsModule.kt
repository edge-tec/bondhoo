package com.connectly.android.feature.groups.di

import com.connectly.android.feature.groups.data.repository.GroupsRepositoryImpl
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class GroupsModule {

    @Binds
    @Singleton
    abstract fun bindGroupsRepository(
        impl: GroupsRepositoryImpl
    ): GroupsRepository
}

