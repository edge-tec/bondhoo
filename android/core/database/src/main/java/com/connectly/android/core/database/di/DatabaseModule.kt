package com.connectly.android.core.database.di

import android.content.Context
import androidx.room.Room
import com.connectly.android.core.database.ConnectlyDatabase
import com.connectly.android.core.database.dao.*
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {

    @Provides
    @Singleton
    fun providesConnectlyDatabase(
        @ApplicationContext context: Context
    ): ConnectlyDatabase {
        return Room.databaseBuilder(
            context,
            ConnectlyDatabase::class.java,
            "connectly_database.db"
        ).fallbackToDestructiveMigration().build()
    }

    @Provides
    fun providesUserDao(db: ConnectlyDatabase): UserDao = db.userDao()

    @Provides
    fun providesPostDao(db: ConnectlyDatabase): PostDao = db.postDao()

    @Provides
    fun providesStoryDao(db: ConnectlyDatabase): StoryDao = db.storyDao()

    @Provides
    fun providesReelDao(db: ConnectlyDatabase): ReelDao = db.reelDao()

    @Provides
    fun providesConversationDao(db: ConnectlyDatabase): ConversationDao = db.conversationDao()

    @Provides
    fun providesMessageDao(db: ConnectlyDatabase): MessageDao = db.messageDao()

    @Provides
    fun providesGroupDao(db: ConnectlyDatabase): GroupDao = db.groupDao()

    @Provides
    fun providesEventDao(db: ConnectlyDatabase): EventDao = db.eventDao()

    @Provides
    fun providesSyncPendingActionDao(db: ConnectlyDatabase): SyncPendingActionDao = db.syncPendingActionDao()
}
