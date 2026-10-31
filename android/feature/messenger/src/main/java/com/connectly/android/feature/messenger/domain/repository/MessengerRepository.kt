package com.connectly.android.feature.messenger.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import kotlinx.coroutines.flow.Flow

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
    fun markDelivered(messageId: Long): Flow<Result<Unit>>
    fun markRead(conversationId: Long): Flow<Result<Unit>>
    fun reactToMessage(messageId: Long, reaction: String): Flow<Result<Unit>>
    fun deleteMessageForMe(messageId: Long): Flow<Result<Unit>>
    fun deleteMessageForEveryone(messageId: Long): Flow<Result<Unit>>
    fun observeIncomingMessages(conversationId: Long): Flow<Message>
}
