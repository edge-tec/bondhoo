package com.connectly.android.feature.messenger.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetConversationsUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(filter: String = "all"): Flow<Result<List<Conversation>>> =
        repository.getConversations(filter)
}

class CreateConversationUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(recipientId: Long? = null, title: String? = null, participantIds: List<Long>? = null): Flow<Result<Conversation>> =
        repository.createConversation(recipientId, title, participantIds)
}

class GetMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long, page: Int = 1, perPage: Int = 30): Flow<Result<List<Message>>> =
        repository.getMessages(conversationId, page, perPage)
}

class SendMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(
        conversationId: Long,
        body: String,
        type: String = "text",
        clientMessageId: String,
        replyToId: Long? = null,
        mediaIds: List<Long> = emptyList()
    ): Flow<Result<Message>> =
        repository.sendMessage(conversationId, body, type, clientMessageId, replyToId, mediaIds)
}

class MarkDeliveredUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long): Flow<Result<Unit>> =
        repository.markDelivered(messageId)
}

class MarkReadUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long): Flow<Result<Unit>> =
        repository.markRead(conversationId)
}

class ReactToMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long, reaction: String): Flow<Result<Unit>> =
        repository.reactToMessage(messageId, reaction)
}

class DeleteMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long, forEveryone: Boolean = false): Flow<Result<Unit>> =
        if (forEveryone) repository.deleteMessageForEveryone(messageId) else repository.deleteMessageForMe(messageId)
}

class ObserveIncomingMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long): Flow<Message> =
        repository.observeIncomingMessages(conversationId)
}
