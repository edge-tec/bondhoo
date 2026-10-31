package com.connectly.android.core.database

import androidx.room.Database
import androidx.room.RoomDatabase
import androidx.room.TypeConverters
import com.connectly.android.core.database.converters.ConnectlyTypeConverters
import com.connectly.android.core.database.dao.*
import com.connectly.android.core.database.entity.*

@Database(
    entities = [
        UserEntity::class,
        PostEntity::class,
        StoryEntity::class,
        ReelEntity::class,
        ConversationEntity::class,
        MessageEntity::class,
        GroupEntity::class,
        EventEntity::class,
        SyncPendingActionEntity::class
    ],
    version = 1,
    exportSchema = false
)
@TypeConverters(ConnectlyTypeConverters::class)
abstract class ConnectlyDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun postDao(): PostDao
    abstract fun storyDao(): StoryDao
    abstract fun reelDao(): ReelDao
    abstract fun conversationDao(): ConversationDao
    abstract fun messageDao(): MessageDao
    abstract fun groupDao(): GroupDao
    abstract fun eventDao(): EventDao
    abstract fun syncPendingActionDao(): SyncPendingActionDao
}
