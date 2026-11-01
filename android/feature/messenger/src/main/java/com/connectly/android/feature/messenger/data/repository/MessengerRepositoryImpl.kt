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
import com.connectly.android.core.model.MessagePreview
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateConversationRequest
import com.connectly.android.core.network.api.MessageDto
import com.connectly.android.core.network.api.MessageReactionRequest
import com.connectly.android.core.network.api.SendMessageRequest
import com.connectly.android.core.network.api.UpdateGroupInfoRequest
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.map
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class MessengerRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val conversationDao: ConversationDao,
    private val messageDao: MessageDao,
    private val tokenStorageManager: TokenStorageManager,
    private val reverbManager: ReverbWebSocketManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : MessengerRepository {

    init {
        reverbManager.connect()
        val currentUserId = tokenStorageManager.getUserId()
        if (currentUserId > 0) {
            reverbManager.subscribeUserChannel(currentUserId)
            reverbManager.subscribePresenceChannel()
        }
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
                        title = dto.title ?: dto.other_user?.name ?: "Conversation",
                        avatar = dto.avatar_url ?: dto.other_user?.avatar,
                        otherUser = dto.other_user?.let {
                            User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                        },
                        lastMessage = dto.last_message?.let { mapDtoToMessage(it) },
                        unreadCount = dto.unread_count,
                        isPinned = dto.is_pinned,
                        isArchived = dto.is_archived,
                        isMuted = dto.is_muted,
                        isOnline = dto.is_online,
                        draftMessage = dto.draft_message,
                        myRole = dto.my_role
                    )
                }

                // Cache in Room
                conversationDao.insertConversations(conversations.map { c ->
                    ConversationEntity(
                        id = c.id,
                        type = c.type,
                        otherUserId = c.otherUser?.id,
                        otherUserName = c.otherUser?.name,
                        otherUserAvatar = c.otherUser?.avatar,
                        lastMessageBody = c.lastMessage?.body,
                        lastMessageAt = c.lastMessage?.createdAt ?: System.currentTimeMillis(),
                        unreadCount = c.unreadCount,
                        isOnline = c.isOnline
                    )
                })

                emit(Result.Success(conversations))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
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
                    title = dto.title ?: dto.other_user?.name ?: title ?: "Chat",
                    avatar = dto.avatar_url ?: dto.other_user?.avatar,
                    otherUser = dto.other_user?.let {
                        User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                    },
                    myRole = dto.my_role
                )
                emit(Result.Success(conv))
            } else {
                emit(Result.Error(Exception("Failed to create conversation.")))
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
            // Subscribe to real-time events for this conversation
            reverbManager.subscribeConversation(conversationId)

            val response = api.getMessages(conversationId, page, perPage)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val messages = dtos.map { mapDtoToMessage(it) }

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
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
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
            replyToMessageId = replyToId,
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
                    media_ids = mediaIds,
                    reply_to_id = replyToId,
                    reply_to_message_id = replyToId
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val serverMsg = mapDtoToMessage(response.body()!!.data)
                messageDao.updateDeliveryStatus(optimisticMsg.id, DeliveryStatus.SENT.rawValue)
            }
        } catch (_: Exception) {
            // Keep in local queue with SENDING/FAILED for background worker retry
        }
    }.flowOn(ioDispatcher)

    override fun editMessage(messageId: Long, newBody: String): Flow<Result<Message>> = flow {
        emit(Result.Loading)
        try {
            val response = api.editMessage(messageId, mapOf("body" to newBody))
            if (response.isSuccessful && response.body() != null) {
                val msg = mapDtoToMessage(response.body()!!.data)
                emit(Result.Success(msg))
            } else {
                emit(Result.Error(Exception("Failed to edit message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun forwardMessage(
        messageId: Long,
        targetConversationIds: List<Long>
    ): Flow<Result<List<Message>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.forwardMessage(messageId, mapOf("conversation_ids" to targetConversationIds))
            if (response.isSuccessful && response.body() != null) {
                val msgs = response.body()!!.data.map { mapDtoToMessage(it) }
                emit(Result.Success(msgs))
            } else {
                emit(Result.Error(Exception("Failed to forward message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun saveMessage(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.saveMessage(messageId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unsaveMessage(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.unsaveMessage(messageId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getSavedMessages(): Flow<Result<List<Message>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getSavedMessages()
            if (response.isSuccessful && response.body() != null) {
                val msgs = response.body()!!.data.map { mapDtoToMessage(it) }
                emit(Result.Success(msgs))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun sendVoiceMessage(
        conversationId: Long,
        audioFile: File,
        durationSeconds: Int,
        waveform: List<Float>?
    ): Flow<Result<Message>> = flow {
        emit(Result.Loading)
        try {
            val reqFile = audioFile.asRequestBody("audio/mp4".toMediaTypeOrNull())
            val audioPart = MultipartBody.Part.createFormData("audio", audioFile.name, reqFile)
            val durationBody = durationSeconds.toString().toRequestBody("text/plain".toMediaTypeOrNull())
            val waveformBody = waveform?.let {
                it.joinToString(",", "[", "]").toRequestBody("text/plain".toMediaTypeOrNull())
            }

            val response = api.sendVoiceMessage(conversationId, audioPart, durationBody, waveformBody)
            if (response.isSuccessful && response.body() != null) {
                val msg = mapDtoToMessage(response.body()!!.data)
                emit(Result.Success(msg))
            } else {
                emit(Result.Error(Exception("Failed to send voice message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
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
        } catch (e: Exception) {
            emit(Result.Error(e))
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

    override fun togglePinConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.togglePinConversation(conversationId)
            val isPinned = response.body()?.data?.get("is_pinned") ?: false
            emit(Result.Success(isPinned))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun toggleArchiveConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.toggleArchiveConversation(conversationId)
            val isArchived = response.body()?.data?.get("is_archived") ?: false
            emit(Result.Success(isArchived))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun muteConversation(conversationId: Long, duration: String): Flow<Result<Boolean>> = flow {
        try {
            val response = api.muteConversation(conversationId, mapOf("duration" to duration))
            val isMuted = response.body()?.data?.get("is_muted") as? Boolean ?: true
            emit(Result.Success(isMuted))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun clearConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.clearConversation(conversationId)
            val cleared = response.body()?.data?.get("cleared") ?: true
            emit(Result.Success(cleared))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun leaveGroup(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.leaveGroup(conversationId)
            val left = response.body()?.data?.get("left") ?: true
            emit(Result.Success(left))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun updateGroupInfo(
        conversationId: Long,
        title: String?,
        description: String?,
        avatarUrl: String?
    ): Flow<Result<Conversation>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updateGroupInfo(
                conversationId,
                UpdateGroupInfoRequest(title = title, description = description, avatar_url = avatarUrl)
            )
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val conv = Conversation(
                    id = dto.id,
                    type = dto.type,
                    title = dto.title,
                    avatar = dto.avatar_url,
                    myRole = dto.my_role
                )
                emit(Result.Success(conv))
            } else {
                emit(Result.Error(Exception("Failed to update group.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun addGroupMembers(conversationId: Long, userIds: List<Long>): Flow<Result<Unit>> = flow {
        try {
            api.addGroupMembers(conversationId, mapOf("user_ids" to userIds))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun removeGroupMember(conversationId: Long, userId: Long): Flow<Result<Unit>> = flow {
        try {
            api.removeGroupMember(conversationId, userId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun saveDraft(conversationId: Long, draftText: String): Flow<Result<Unit>> = flow {
        try {
            api.saveDraft(conversationId, mapOf("draft" to draftText))
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun markConversationUnread(conversationId: Long): Flow<Result<Unit>> = flow {
        try {
            api.markConversationUnread(conversationId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun sendTyping(conversationId: Long, isTyping: Boolean) {
        reverbManager.sendTyping(conversationId, isTyping)
    }

    override fun observeIncomingMessages(conversationId: Long): Flow<Message> =
        reverbManager.messageNewFlow
            .filter { it.conversationId == conversationId }
            .map { it.message }

    override fun observeTyping(conversationId: Long): Flow<Pair<Long, Boolean>> =
        reverbManager.userTypingFlow
            .filter { it.conversationId == conversationId }
            .map { it.userId to it.isTyping }

    override fun observePresence(): Flow<Pair<Long, Boolean>> =
        reverbManager.userPresenceFlow
            .map { it.userId to it.isOnline }

    override fun searchMessages(query: String, conversationId: Long?): Flow<Result<List<Message>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.searchMessenger(query = query, conversationId = conversationId)
            if (response.isSuccessful && response.body() != null) {
                val msgs = response.body()!!.data.map { mapDtoToMessage(it) }
                emit(Result.Success(msgs))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun syncOfflineEvents(sinceId: Long): Flow<Result<Long>> = flow {
        emit(Result.Loading)
        try {
            val response = api.syncMessengerEvents(sinceId = sinceId)
            if (response.isSuccessful && response.body() != null) {
                val data = response.body()!!.data
                val latestId = data.latest_event_id
                emit(Result.Success(latestId))
            } else {
                emit(Result.Success(sinceId))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    private fun mapDtoToMessage(dto: MessageDto): Message {
        val currentUserId = tokenStorageManager.getUserId()
        return Message(
            id = dto.id,
            conversationId = dto.conversation_id,
            senderId = dto.sender_id,
            senderName = if (dto.sender_id == currentUserId) "You" else "User",
            body = dto.body,
            type = dto.type,
            clientMessageId = dto.client_message_id ?: "msg_${dto.id}",
            deliveryStatus = when (dto.delivery_status) {
                "read" -> DeliveryStatus.READ
                "delivered" -> DeliveryStatus.DELIVERED
                else -> DeliveryStatus.SENT
            },
            isMine = dto.is_mine || (dto.sender_id == currentUserId),
            isEdited = dto.is_edited,
            isPinned = dto.is_pinned,
            isSaved = dto.is_saved,
            isDeleted = dto.is_deleted,
            isForwarded = dto.is_forwarded,
            replyToMessageId = dto.reply_to_message_id,
            replyPreview = dto.reply_preview?.let {
                MessagePreview(id = it.id, senderName = it.sender_name ?: "User", body = it.body ?: "", type = it.type)
            },
            reactions = dto.reactions?.counts ?: emptyMap(),
            myReaction = dto.reactions?.my_reaction,
            createdAt = System.currentTimeMillis()
        )
    }
}
