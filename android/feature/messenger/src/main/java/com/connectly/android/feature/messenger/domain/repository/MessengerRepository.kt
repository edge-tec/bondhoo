package com.connectly.android.feature.messenger.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import kotlinx.coroutines.flow.Flow
import java.io.File

interface MessengerRepository {
    fun getConversations(filter: String = "all"): Flow<Result<List<Conversation>>>
    fun createConversation(recipientId: Long? = null, title: String? = null, participantIds: List<Long>? = null): Flow<Result<Conversation>>
    fun getMessages(conversationId: Long, page: Int = 1, perPage: Int = 30): Flow<Result<List<Message>>>
    fun sendMessage(
        conversationId: Long,
        body: String,
        type: String = "text",
        clientMessageId: String,
        replyToId: Long? = null,
        mediaIds: List<Long> = emptyList()
    ): Flow<Result<Message>>
    fun editMessage(messageId: Long, newBody: String): Flow<Result<Message>>
    fun forwardMessage(messageId: Long, targetConversationIds: List<Long>): Flow<Result<List<Message>>>
    fun saveMessage(messageId: Long): Flow<Result<Unit>>
    fun unsaveMessage(messageId: Long): Flow<Result<Unit>>
    fun getSavedMessages(): Flow<Result<List<Message>>>
    fun sendVoiceMessage(conversationId: Long, audioFile: File, durationSeconds: Int, waveform: List<Float>? = null): Flow<Result<Message>>
    fun markDelivered(messageId: Long): Flow<Result<Unit>>
    fun markRead(conversationId: Long): Flow<Result<Unit>>
    fun reactToMessage(messageId: Long, reaction: String): Flow<Result<Unit>>
    fun deleteMessageForMe(messageId: Long): Flow<Result<Unit>>
    fun deleteMessageForEveryone(messageId: Long): Flow<Result<Unit>>
    fun togglePinConversation(conversationId: Long): Flow<Result<Boolean>>
    fun toggleArchiveConversation(conversationId: Long): Flow<Result<Boolean>>
    fun muteConversation(conversationId: Long, duration: String = "forever"): Flow<Result<Boolean>>
    fun clearConversation(conversationId: Long): Flow<Result<Boolean>>
    fun leaveGroup(conversationId: Long): Flow<Result<Boolean>>
    fun updateGroupInfo(conversationId: Long, title: String?, description: String?, avatarUrl: String?): Flow<Result<Conversation>>
    fun addGroupMembers(conversationId: Long, userIds: List<Long>): Flow<Result<Unit>>
    fun removeGroupMember(conversationId: Long, userId: Long): Flow<Result<Unit>>
    fun saveDraft(conversationId: Long, draftText: String): Flow<Result<Unit>>
    fun markConversationUnread(conversationId: Long): Flow<Result<Unit>>
    fun sendTyping(conversationId: Long, isTyping: Boolean)
    fun observeIncomingMessages(conversationId: Long): Flow<Message>
    fun observeTyping(conversationId: Long): Flow<Pair<Long, Boolean>>
    fun observePresence(): Flow<Pair<Long, Boolean>>
    fun searchMessages(query: String, conversationId: Long? = null): Flow<Result<List<Message>>>
    fun syncOfflineEvents(sinceId: Long): Flow<Result<Long>>
}
