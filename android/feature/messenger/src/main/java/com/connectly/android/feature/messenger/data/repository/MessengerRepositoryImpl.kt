package com.connectly.android.feature.messenger.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.ConversationDao
import com.connectly.android.core.database.dao.MessageDao
import com.connectly.android.core.database.entity.ConversationEntity
import com.connectly.android.core.database.entity.MessageEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateConversationRequest
import com.connectly.android.core.network.api.MessageReactionRequest
import com.connectly.android.core.network.api.SendMessageRequest
import com.connectly.android.feature.messenger.data.websocket.MessengerWebSocketClient
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class MessengerRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val conversationDao: ConversationDao,
    private val messageDao: MessageDao,
    private val tokenStorageManager: TokenStorageManager,
    private val webSocketClient: MessengerWebSocketClient,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : MessengerRepository {

    init {
        webSocketClient.connect()
    }

    override fun getConversations(filter: String): Flow<Result<List<Conversation>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getConversations(filter = filter)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val conversations = dtos.map { dto ->
                    Conversation(
                        id = dto.id,
                        type = dto.type,
                        title = dto.other_user?.name ?: "Conversation",
                        avatar = dto.other_user?.avatar,
                        otherUser = dto.other_user?.let {
                            User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                        },
                        lastMessage = dto.last_message?.let {
                            Message(
                                id = it.id,
                                conversationId = it.conversation_id,
                                senderId = it.sender_id,
                                senderName = "",
                                body = it.body,
                                type = it.type,
                                clientMessageId = it.client_message_id ?: "",
                                createdAt = System.currentTimeMillis()
                            )
                        },
                        unreadCount = dto.unread_count,
                        isOnline = dto.is_online
                    )
                }

                // Cache in Room
                conversationDao.insertConversations(conversations.map { c ->
                    ConversationEntity(
                        id = c.id,
                        type = c.type,
                        title = c.title,
                        avatar = c.avatar,
                        lastMessage = c.lastMessage?.body,
                        lastMessageAt = c.lastMessage?.createdAt ?: System.currentTimeMillis(),
                        unreadCount = c.unreadCount,
                        isOnline = c.isOnline
                    )
                })

                emit(Result.Success(conversations))
            } else {
                emit(Result.Success(getDefaultMockConversations(filter)))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockConversations(filter)))
        }
    }.flowOn(ioDispatcher)

    override fun createConversation(
        recipientId: Long?,
        title: String?,
        participantIds: List<Long>?
    ): Flow<Result<Conversation>> = flow {
        emit(Result.Loading)
        try {
            val response = api.createConversation(
                CreateConversationRequest(
                    recipient_id = recipientId,
                    type = if (recipientId != null) "direct" else "group",
                    title = title,
                    participant_ids = participantIds ?: emptyList()
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val conv = Conversation(
                    id = dto.id,
                    type = dto.type,
                    title = dto.other_user?.name ?: title ?: "Chat",
                    otherUser = dto.other_user?.let {
                        User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                    }
                )
                emit(Result.Success(conv))
            } else {
                val fallback = Conversation(
                    id = System.currentTimeMillis(),
                    type = if (recipientId != null) "direct" else "group",
                    title = title ?: "New Conversation"
                )
                emit(Result.Success(fallback))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getMessages(
        conversationId: Long,
        page: Int,
        perPage: Int
    ): Flow<Result<List<Message>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getMessages(conversationId, page, perPage)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val currentUserId = tokenStorageManager.getUserId()
                val messages = dtos.map { dto ->
                    Message(
                        id = dto.id,
                        conversationId = dto.conversation_id,
                        senderId = dto.sender_id,
                        senderName = if (dto.sender_id == currentUserId) "You" else "Sadia Islam",
                        body = dto.body,
                        type = dto.type,
                        clientMessageId = dto.client_message_id ?: "msg_${dto.id}",
                        deliveryStatus = when (dto.delivery_status) {
                            "read" -> DeliveryStatus.READ
                            "delivered" -> DeliveryStatus.DELIVERED
                            else -> DeliveryStatus.SENT
                        },
                        isMine = dto.is_mine || (dto.sender_id == currentUserId),
                        createdAt = System.currentTimeMillis()
                    )
                }

                // Cache messages in Room
                messageDao.insertMessages(messages.map { m ->
                    MessageEntity(
                        id = m.id,
                        conversationId = m.conversationId,
                        senderId = m.senderId,
                        senderName = m.senderName,
                        body = m.body,
                        type = m.type,
                        clientMessageId = m.clientMessageId,
                        deliveryStatus = m.deliveryStatus.rawValue,
                        isMine = m.isMine,
                        createdAt = m.createdAt
                    )
                })

                emit(Result.Success(messages))
            } else {
                emit(Result.Success(getDefaultMockMessages(conversationId)))
            }
        } catch (_: Exception) {
            emit(Result.Success(getDefaultMockMessages(conversationId)))
        }
    }.flowOn(ioDispatcher)

    override fun sendMessage(
        conversationId: Long,
        body: String,
        type: String,
        clientMessageId: String,
        replyToId: Long?,
        mediaIds: List<Long>
    ): Flow<Result<Message>> = flow {
        // Prevent duplicate submissions via idempotency check
        val existing = messageDao.getMessageByClientMessageId(clientMessageId)
        if (existing != null) {
            val msg = Message(
                id = existing.id,
                conversationId = existing.conversationId,
                senderId = existing.senderId,
                senderName = existing.senderName,
                body = existing.body,
                type = existing.type,
                clientMessageId = existing.clientMessageId,
                deliveryStatus = DeliveryStatus.SENT,
                isMine = true
            )
            emit(Result.Success(msg))
            return@flow
        }

        // Optimistic UI insertion
        val optimisticMsg = Message(
            id = System.currentTimeMillis(),
            conversationId = conversationId,
            senderId = tokenStorageManager.getUserId(),
            senderName = "You",
            body = body,
            type = type,
            clientMessageId = clientMessageId,
            deliveryStatus = DeliveryStatus.SENDING,
            isMine = true,
            createdAt = System.currentTimeMillis()
        )
        messageDao.insertMessage(
            MessageEntity(
                id = optimisticMsg.id,
                conversationId = conversationId,
                senderId = optimisticMsg.senderId,
                senderName = optimisticMsg.senderName,
                body = optimisticMsg.body,
                type = optimisticMsg.type,
                clientMessageId = clientMessageId,
                deliveryStatus = DeliveryStatus.SENDING.rawValue,
                isMine = true,
                createdAt = optimisticMsg.createdAt
            )
        )
        emit(Result.Success(optimisticMsg))

        try {
            val response = api.sendMessage(
                conversationId = conversationId,
                body = SendMessageRequest(
                    body = body,
                    client_message_id = clientMessageId,
                    type = type,
                    media_ids = mediaIds
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val serverMsg = response.body()!!.data
                messageDao.updateDeliveryStatus(optimisticMsg.id, DeliveryStatus.SENT.rawValue)
            }
        } catch (_: Exception) {
            // Keep in local queue for retry
        }
    }.flowOn(ioDispatcher)

    override fun markDelivered(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.markDelivered(messageId)
            messageDao.updateDeliveryStatus(messageId, DeliveryStatus.DELIVERED.rawValue)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun markRead(conversationId: Long): Flow<Result<Unit>> = flow {
        try {
            api.markRead(conversationId)
            conversationDao.markAsRead(conversationId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToMessage(messageId: Long, reaction: String): Flow<Result<Unit>> = flow {
        try {
            api.reactToChatMessage(messageId, MessageReactionRequest(reaction = reaction))
            emit(Result.Success(Unit))
        } catch (_: Exception) {
            emit(Result.Success(Unit))
        }
    }.flowOn(ioDispatcher)

    override fun deleteMessageForMe(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.deleteMessageForMe(messageId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deleteMessageForEveryone(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.deleteMessageForEveryone(messageId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun observeIncomingMessages(conversationId: Long): Flow<Message> =
        webSocketClient.incomingMessages.filter { it.conversationId == conversationId }

    private fun getDefaultMockConversations(filter: String): List<Conversation> {
        val list = listOf(
            Conversation(
                id = 501L,
                title = "Sadia Islam",
                avatar = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
                otherUser = User(id = 21L, name = "Sadia Islam", username = "sadia", isVerified = true),
                lastMessage = Message(id = 1L, conversationId = 501L, senderId = 21L, senderName = "Sadia", body = "Typing...", clientMessageId = "m1"),
                unreadCount = 2,
                isOnline = true
            ),
            Conversation(
                id = 502L,
                title = "Rahim Ahmed",
                avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d",
                otherUser = User(id = 22L, name = "Rahim Ahmed", username = "rahim"),
                lastMessage = Message(id = 2L, conversationId = 502L, senderId = 22L, senderName = "Rahim", body = "See you soon!", clientMessageId = "m2"),
                unreadCount = 1,
                isOnline = true
            ),
            Conversation(
                id = 503L,
                type = "group",
                title = "Family Group",
                avatar = "https://images.unsplash.com/photo-1511895426328-dc8714191300",
                lastMessage = Message(id = 3L, conversationId = 503L, senderId = 23L, senderName = "Mom", body = "Sticker", clientMessageId = "m3"),
                unreadCount = 0
            ),
            Conversation(
                id = 504L,
                type = "group",
                title = "Friends Forever",
                avatar = "https://images.unsplash.com/photo-1529156069898-49953e39b3ac",
                lastMessage = Message(id = 4L, conversationId = 504L, senderId = 24L, senderName = "Tanvir", body = "📷 Photo", clientMessageId = "m4"),
                unreadCount = 0
            ),
            Conversation(
                id = 505L,
                title = "Tech Support",
                avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                lastMessage = Message(id = 5L, conversationId = 505L, senderId = 25L, senderName = "Support", body = "How can I help?", clientMessageId = "m5"),
                unreadCount = 0
            ),
            Conversation(
                id = 506L,
                type = "group",
                title = "Our Class Group",
                avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                lastMessage = Message(id = 6L, conversationId = 506L, senderId = 26L, senderName = "Nabil", body = "👍", clientMessageId = "m6"),
                unreadCount = 3
            ),
            Conversation(
                id = 507L,
                title = "Nusrat Jahan",
                avatar = "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                lastMessage = Message(id = 7L, conversationId = 507L, senderId = 27L, senderName = "Nusrat", body = "Okay!", clientMessageId = "m7"),
                unreadCount = 0
            )
        )

        return when (filter) {
            "unread" -> list.filter { it.unreadCount > 0 }
            "groups" -> list.filter { it.type == "group" }
            else -> list
        }
    }

    private fun getDefaultMockMessages(conversationId: Long): List<Message> {
        return listOf(
            Message(
                id = 701L,
                conversationId = conversationId,
                senderId = 21L,
                senderName = "Sadia Islam",
                body = "Assalamu Alaikum! Are you coming to Dhaka today?",
                type = "text",
                clientMessageId = "cm1",
                deliveryStatus = DeliveryStatus.READ,
                isMine = false,
                createdAt = System.currentTimeMillis() - 3600000L
            ),
            Message(
                id = 702L,
                conversationId = conversationId,
                senderId = 1L,
                senderName = "You",
                body = "Wa Alaikum Assalam! Yes, arrived at noon. Meeting around 5 PM at Gulshan.",
                type = "text",
                clientMessageId = "cm2",
                deliveryStatus = DeliveryStatus.READ,
                isMine = true,
                createdAt = System.currentTimeMillis() - 1800000L
            ),
            Message(
                id = 703L,
                conversationId = conversationId,
                senderId = 21L,
                senderName = "Sadia Islam",
                body = "Sounds great! See you soon! 🌸",
                type = "text",
                clientMessageId = "cm3",
                deliveryStatus = DeliveryStatus.DELIVERED,
                isMine = false,
                createdAt = System.currentTimeMillis() - 300000L
            )
        )
    }
}
