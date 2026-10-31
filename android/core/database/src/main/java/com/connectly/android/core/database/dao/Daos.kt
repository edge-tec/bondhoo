package com.connectly.android.core.database.dao

import androidx.room.*
import com.connectly.android.core.database.entity.*
import kotlinx.coroutines.flow.Flow

@Dao
interface UserDao {
    @Query("SELECT * FROM users WHERE id = :userId")
    fun getUserById(userId: Long): Flow<UserEntity?>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertUser(user: UserEntity)
}

@Dao
interface PostDao {
    @Query("SELECT * FROM posts WHERE feedTab = :tab ORDER BY createdAt DESC")
    fun getPostsByTab(tab: String): Flow<List<PostEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertPosts(posts: List<PostEntity>)

    @Query("UPDATE posts SET userReaction = :reaction, reactionsCount = :count WHERE id = :postId")
    suspend fun updateReaction(postId: Long, reaction: String?, count: Int)

    @Query("DELETE FROM posts WHERE feedTab = :tab")
    suspend fun clearPostsByTab(tab: String)
}

@Dao
interface StoryDao {
    @Query("SELECT * FROM stories WHERE expiresAt > :currentTime ORDER BY createdAt ASC")
    fun getActiveStories(currentTime: Long): Flow<List<StoryEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertStories(stories: List<StoryEntity>)

    @Query("DELETE FROM stories WHERE expiresAt <= :currentTime")
    suspend fun deleteExpiredStories(currentTime: Long)
}

@Dao
interface ReelDao {
    @Query("SELECT * FROM reels ORDER BY createdAt DESC")
    fun getReelsFeed(): Flow<List<ReelEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertReels(reels: List<ReelEntity>)

    @Query("UPDATE reels SET isLiked = :isLiked, likesCount = :likesCount WHERE id = :reelId")
    suspend fun updateLike(reelId: Long, isLiked: Boolean, likesCount: Int)
}

@Dao
interface ConversationDao {
    @Query("SELECT * FROM conversations ORDER BY lastMessageAt DESC")
    fun getConversations(): Flow<List<ConversationEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertConversations(conversations: List<ConversationEntity>)

    @Query("UPDATE conversations SET unreadCount = 0 WHERE id = :conversationId")
    suspend fun markAsRead(conversationId: Long)
}

@Dao
interface MessageDao {
    @Query("SELECT * FROM messages WHERE conversationId = :convId ORDER BY createdAt ASC")
    fun getMessages(convId: Long): Flow<List<MessageEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertMessage(message: MessageEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertMessages(messages: List<MessageEntity>)

    @Query("UPDATE messages SET deliveryStatus = :status WHERE id = :messageId")
    suspend fun updateDeliveryStatus(messageId: Long, status: String)

    @Query("SELECT * FROM messages WHERE clientMessageId = :clientMsgId LIMIT 1")
    suspend fun getMessageByClientMessageId(clientMsgId: String): MessageEntity?
}

@Dao
interface GroupDao {
    @Query("SELECT * FROM groups ORDER BY membersCount DESC")
    fun getGroups(): Flow<List<GroupEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertGroups(groups: List<GroupEntity>)

    @Query("UPDATE groups SET isMember = :isMember, membersCount = membersCount + 1 WHERE id = :groupId")
    suspend fun updateMembership(groupId: Long, isMember: Boolean)
}

@Dao
interface EventDao {
    @Query("SELECT * FROM events ORDER BY startTime ASC")
    fun getEvents(): Flow<List<EventEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertEvents(events: List<EventEntity>)

    @Query("UPDATE events SET myRsvp = :status WHERE id = :eventId")
    suspend fun updateRsvp(eventId: Long, status: String)
}

@Dao
interface SyncPendingActionDao {
    @Query("SELECT * FROM sync_pending_actions WHERE status = 'PENDING' ORDER BY createdAt ASC")
    suspend fun getPendingActions(): List<SyncPendingActionEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAction(action: SyncPendingActionEntity)

    @Query("DELETE FROM sync_pending_actions WHERE id = :id")
    suspend fun deleteAction(id: Long)
}

