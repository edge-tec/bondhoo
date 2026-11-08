package com.connectly.android.feature.messenger.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.ConversationDao
import com.connectly.android.core.database.dao.MessageDao
import com.connectly.android.core.database.entity.ConversationEntity
import com.connectly.android.core.database.entity.MessageEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.MessageAttachment
import com.connectly.android.core.model.MessagePreview
import com.connectly.android.core.model.PinnedMessageInfo
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.ConversationDto
import com.connectly.android.core.network.api.CreateConversationRequest
import com.connectly.android.core.network.api.MediaUploadResponse
import com.connectly.android.core.network.api.MessageDto
import com.connectly.android.core.network.api.MessageReactionRequest
import com.connectly.android.core.network.api.ReportMarketplaceRequest
import com.connectly.android.core.network.api.SendMessageRequest
import com.connectly.android.core.network.api.UpdateGroupInfoRequest
import com.connectly.android.core.network.di.NetworkModule
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.launch
import kotlinx.serialization.builtins.ListSerializer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.booleanOrNull
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.intOrNull
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.longOrNull
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.time.Duration
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale
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

    private val backgroundScope = CoroutineScope(SupervisorJob() + ioDispatcher)
    private val lenientJson = Json {
        ignoreUnknownKeys = true
        isLenient = true
        coerceInputValues = true
    }

    init {
        reverbManager.connect()
        val currentUserId = tokenStorageManager.getUserId()
        if (currentUserId > 0) {
            reverbManager.subscribeUserChannel(currentUserId)
            reverbManager.subscribePresenceChannel()
        }
    }

    override fun getConversations(filter: String, search: String?): Flow<Result<List<Conversation>>> = flow {
        emit(Result.Loading)
        try {
            val cleanSearch = search?.trim()?.takeIf { it.isNotEmpty() }
            val response = api.getConversations(filter = filter, search = cleanSearch)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val conversations = dtos.map { dto -> mapDtoToConversation(dto) }

                runCatching {
                    conversationDao.insertConversations(conversations.map { c ->
                        ConversationEntity(
                            id = c.id,
                            type = c.type,
                            otherUserId = c.otherUser?.id,
                            otherUserName = c.displayTitle,
                            otherUserAvatar = c.displayAvatar,
                            lastMessageBody = c.lastMessage?.body,
                            lastMessageAt = c.lastMessage?.createdAt ?: System.currentTimeMillis(),
                            unreadCount = c.unreadCount,
                            isOnline = c.isOnline
                        )
                    })
                }

                emit(Result.Success(conversations))
            } else {
                emit(Result.Error(Exception("Failed to load conversations (${response.code()}).")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getConversationDetails(conversationId: Long): Flow<Result<Conversation>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getConversation(conversationId)
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(mapDtoToConversation(response.body()!!.data)))
            } else {
                emit(Result.Error(Exception("Conversation not found (${response.code()}).")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun createConversation(
        recipientId: Long?,
        title: String?,
        participantIds: List<Long>?,
        type: String?,
        description: String?
    ): Flow<Result<Conversation>> = flow {
        emit(Result.Loading)
        try {
            val resolvedType = type ?: if (recipientId != null) "direct" else "group"
            val response = api.createConversation(
                CreateConversationRequest(
                    recipient_id = recipientId,
                    type = resolvedType,
                    title = title,
                    description = description,
                    participant_ids = participantIds ?: emptyList()
                )
            )
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(mapDtoToConversation(response.body()!!.data)))
            } else {
                emit(Result.Error(Exception("Failed to start conversation.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun openSavedConversation(): Flow<Result<Conversation>> =
        createConversation(type = "saved")

    override fun deleteConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.deleteConversation(conversationId)
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to delete conversation.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun handleMessageRequest(conversationId: Long, action: String): Flow<Result<Boolean>> = flow {
        try {
            val response = api.handleMessageRequest(conversationId, mapOf("action" to action))
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to $action message request.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getFriendsForMessaging(query: String?): Flow<Result<List<ActivePresenceFriend>>> = flow {
        emit(Result.Loading)
        try {
            val cleanQuery = query?.trim()?.takeIf { it.isNotEmpty() }
            val currentUserId = tokenStorageManager.getUserId()
            val merged = LinkedHashMap<Long, ActivePresenceFriend>()

            // 1. Active presence friends endpoint (includes online status, lastSeenDisplay, conversationId)
            runCatching {
                val activeResp = api.getActiveFriends(cleanQuery)
                if (activeResp.isSuccessful && activeResp.body() != null) {
                    activeResp.body()!!.data.forEach { dto ->
                        if (dto.id != currentUserId && dto.id > 0L) {
                            merged[dto.id] = ActivePresenceFriend(
                                id = dto.id,
                                name = dto.name.ifBlank { dto.username },
                                username = dto.username,
                                avatarUrl = normalizeBackendUrl(dto.avatar_url),
                                online = dto.online,
                                lastSeenDisplay = dto.last_seen_display,
                                conversationId = dto.conversation_id,
                                canCall = dto.can_call
                            )
                        }
                    }
                }
            }

            // 2. Full friends list endpoint to guarantee all accepted friends appear
            runCatching {
                val friendsResp = api.getFriends(search = cleanQuery, perPage = 80)
                if (friendsResp.isSuccessful && friendsResp.body() != null) {
                    friendsResp.body()!!.data.forEach { dto ->
                        if (dto.id != currentUserId && dto.id > 0L && !merged.containsKey(dto.id)) {
                            merged[dto.id] = ActivePresenceFriend(
                                id = dto.id,
                                name = dto.name.ifBlank { dto.username },
                                username = dto.username,
                                avatarUrl = normalizeBackendUrl(dto.resolvedAvatar),
                                online = dto.is_online,
                                lastSeenDisplay = if (dto.is_online) "Active now" else null,
                                conversationId = null,
                                canCall = true
                            )
                        }
                    }
                }
            }

            // 3. When user types a search query, also search Bondoo users so they can message anyone allowed
            if (!cleanQuery.isNullOrBlank() && cleanQuery.length >= 2) {
                runCatching {
                    val searchResp = api.search(query = cleanQuery, type = "users", limit = 20)
                    if (searchResp.isSuccessful && searchResp.body()?.data?.results != null) {
                        searchResp.body()!!.data.results!!.users.forEach { dto ->
                            if (dto.id != currentUserId && dto.id > 0L && !merged.containsKey(dto.id)) {
                                merged[dto.id] = ActivePresenceFriend(
                                    id = dto.id,
                                    name = dto.name.ifBlank { dto.username },
                                    username = dto.username,
                                    avatarUrl = normalizeBackendUrl(dto.resolvedAvatar),
                                    online = dto.is_online,
                                    lastSeenDisplay = "@${dto.username}",
                                    conversationId = null,
                                    canCall = true
                                )
                            }
                        }
                    }
                }
            }

            val sortedList = merged.values.sortedWith(
                compareByDescending<ActivePresenceFriend> { it.online }
                    .thenBy { it.name.lowercase(Locale.getDefault()) }
            )
            emit(Result.Success(sortedList))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getCallHistoryForMessenger(): Flow<Result<List<CallHistoryItem>>> = flow {
        emit(Result.Loading)
        try {
            val currentUserId = tokenStorageManager.getUserId()
            val response = api.getCallHistory(page = 1, perPage = 40)
            if (response.isSuccessful && response.body() != null) {
                val items = response.body()!!.data.map { dto ->
                    val isOutgoing = dto.is_mine || (dto.caller?.id == currentUserId && currentUserId > 0L)
                    val isIncoming = !isOutgoing
                    val otherParticipant = dto.participants
                        .map { it.user }
                        .firstOrNull { it.id != currentUserId && it.id > 0L }
                    val resolvedOtherDto = if (isIncoming) {
                        dto.caller ?: otherParticipant
                    } else {
                        dto.receiver ?: otherParticipant ?: dto.caller
                    }
                    val callType = CallType.from(dto.call_type)
                    val groupNames = dto.participants
                        .mapNotNull { p -> p.user.name.takeIf { it.isNotBlank() && p.user.id != currentUserId } }
                    val groupTitle = if (callType.isGroup || dto.participants.size > 2) {
                        groupNames.joinToString(", ").take(42).ifBlank { "Group Call" }
                    } else {
                        null
                    }
                    val otherUser = User(
                        id = resolvedOtherDto?.id ?: 0L,
                        name = groupTitle ?: resolvedOtherDto?.name?.ifBlank { resolvedOtherDto.username } ?: "Bondoo User",
                        username = resolvedOtherDto?.username ?: "user",
                        avatar = normalizeBackendUrl(resolvedOtherDto?.resolvedAvatar)
                    )
                    val outcome = dto.outcome ?: dto.status
                    val isMissed = outcome.equals("missed", ignoreCase = true) ||
                        outcome.equals("rejected", ignoreCase = true) ||
                        dto.status.equals("missed", ignoreCase = true)
                    CallHistoryItem(
                        id = dto.id,
                        conversationId = dto.conversation_id,
                        title = groupTitle,
                        otherUser = otherUser,
                        participantCount = dto.participants.size.coerceAtLeast(2),
                        callType = callType,
                        isIncoming = isIncoming,
                        isMissed = isMissed,
                        outcome = outcome,
                        durationSeconds = dto.duration_seconds,
                        formattedTime = formatRelativeTimestamp(dto.started_at ?: dto.created_at)
                    )
                }
                emit(Result.Success(items))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getMessages(
        conversationId: Long,
        page: Int,
        perPage: Int,
        search: String?
    ): Flow<Result<List<Message>>> = flow {
        emit(Result.Loading)
        try {
            reverbManager.subscribeConversation(conversationId)

            val response = api.getMessages(
                conversationId = conversationId,
                page = page,
                perPage = perPage,
                search = search?.trim()?.takeIf { it.isNotEmpty() }
            )
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data
                val messages = dtos.map { mapDtoToMessage(it, conversationId) }
                    .sortedBy { it.id }

                runCatching {
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
                }

                emit(Result.Success(messages))
            } else {
                emit(Result.Error(Exception("Failed to load messages (${response.code()}).")))
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
        val nowMillis = System.currentTimeMillis()
        val optimisticMsg = Message(
            id = -nowMillis,
            conversationId = conversationId,
            senderId = tokenStorageManager.getUserId(),
            senderName = "You",
            body = body,
            type = type,
            clientMessageId = clientMessageId,
            deliveryStatus = DeliveryStatus.SENDING,
            isMine = true,
            replyToMessageId = replyToId,
            formattedTime = "Sending...",
            createdAt = nowMillis
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
                val serverMsg = mapDtoToMessage(response.body()!!.data, conversationId)
                runCatching {
                    messageDao.insertMessage(
                        MessageEntity(
                            id = serverMsg.id,
                            conversationId = serverMsg.conversationId,
                            senderId = serverMsg.senderId,
                            senderName = serverMsg.senderName,
                            body = serverMsg.body,
                            type = serverMsg.type,
                            clientMessageId = serverMsg.clientMessageId,
                            deliveryStatus = serverMsg.deliveryStatus.rawValue,
                            isMine = true,
                            createdAt = serverMsg.createdAt
                        )
                    )
                }
                emit(Result.Success(serverMsg))
            } else {
                emit(Result.Error(Exception("Message could not be sent (${response.code()}).")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun uploadAttachment(file: File, mimeType: String): Flow<Result<MessageAttachment>> = flow {
        emit(Result.Loading)
        try {
            val requestBody = file.asRequestBody(mimeType.toMediaTypeOrNull())
            val part = MultipartBody.Part.createFormData("file", file.name, requestBody)
            val response = api.uploadMessageAttachment(part)
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                emit(Result.Success(mapMediaDtoToAttachment(dto, fallbackName = file.name, fallbackMime = mimeType)))
            } else {
                emit(Result.Error(Exception("Attachment upload failed (${response.code()}).")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
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
            val response = api.forwardMessage(
                messageId,
                mapOf("target_conversation_ids" to targetConversationIds)
            )
            if (response.isSuccessful) {
                val element = response.body()?.data
                val parsed = if (element is JsonArray) {
                    runCatching {
                        lenientJson.decodeFromJsonElement(ListSerializer(MessageDto.serializer()), element)
                            .map { mapDtoToMessage(it) }
                    }.getOrDefault(emptyList())
                } else {
                    emptyList()
                }
                emit(Result.Success(parsed))
            } else {
                emit(Result.Error(Exception("Failed to forward message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun saveMessage(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            val resp = api.saveMessage(messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to save message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unsaveMessage(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            val resp = api.unsaveMessage(messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to unsave message.")))
            }
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

    override fun pinMessage(conversationId: Long, messageId: Long): Flow<Result<Boolean>> = flow {
        try {
            val resp = api.pinMessage(conversationId, messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Could not pin message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unpinMessage(conversationId: Long, messageId: Long): Flow<Result<Boolean>> = flow {
        try {
            val resp = api.unpinMessage(conversationId, messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Could not unpin message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getPinnedMessages(conversationId: Long): Flow<Result<List<PinnedMessageInfo>>> = flow<Result<List<PinnedMessageInfo>>> {
        try {
            val resp = api.getPinnedMessages(conversationId)
            if (resp.isSuccessful && resp.body()?.data != null) {
                val root = resp.body()!!.data
                val list = mutableListOf<PinnedMessageInfo>()
                if (root is JsonArray) {
                    root.forEach { item ->
                        if (item is JsonObject) {
                            val pinId = item["id"]?.jsonPrimitive?.longOrNull ?: 0L
                            val pinnedByName = item["pinned_by"]?.let { pb ->
                                (pb as? JsonObject)?.get("name")?.jsonPrimitive?.contentOrNull
                            }
                            val pinnedAt = item["pinned_at"]?.jsonPrimitive?.contentOrNull
                            val msgElement = item["message"]
                            if (msgElement is JsonObject) {
                                runCatching {
                                    val msgDto = lenientJson.decodeFromJsonElement(MessageDto.serializer(), msgElement)
                                    list.add(
                                        PinnedMessageInfo(
                                            pinId = pinId,
                                            message = mapDtoToMessage(msgDto, conversationId),
                                            pinnedByName = pinnedByName,
                                            pinnedAtFormatted = formatRelativeTimestamp(pinnedAt)
                                        )
                                    )
                                }
                            }
                        }
                    }
                }
                emit(Result.Success(list.toList()))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun getConversationMedia(conversationId: Long, type: String): Flow<Result<List<MessageAttachment>>> = flow {
        emit(Result.Loading)
        try {
            val resp = api.getConversationMedia(conversationId, type)
            if (resp.isSuccessful && resp.body()?.data != null) {
                val root = resp.body()!!.data
                val items = if (root is JsonArray) {
                    runCatching {
                        lenientJson.decodeFromJsonElement(ListSerializer(MediaUploadResponse.serializer()), root)
                            .map { mapMediaDtoToAttachment(it) }
                    }.getOrDefault(emptyList())
                } else {
                    emptyList()
                }
                emit(Result.Success(items))
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
            val mime = when {
                audioFile.name.endsWith(".ogg", ignoreCase = true) -> "audio/ogg"
                audioFile.name.endsWith(".mp3", ignoreCase = true) -> "audio/mpeg"
                audioFile.name.endsWith(".wav", ignoreCase = true) -> "audio/wav"
                else -> "audio/mp4"
            }
            val reqFile = audioFile.asRequestBody(mime.toMediaTypeOrNull())
            val audioPart = MultipartBody.Part.createFormData("audio", audioFile.name, reqFile)
            val safeDuration = durationSeconds.coerceAtLeast(1)
            val durationBody = safeDuration.toString().toRequestBody("text/plain".toMediaTypeOrNull())

            val response = api.sendVoiceMessage(conversationId, audioPart, durationBody, null)
            if (response.isSuccessful && response.body() != null) {
                val msg = mapDtoToMessage(response.body()!!.data, conversationId)
                emit(Result.Success(msg))
            } else {
                emit(Result.Error(Exception("Failed to send voice message (${response.code()}).")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun markDelivered(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            api.markDelivered(messageId)
            runCatching { messageDao.updateDeliveryStatus(messageId, DeliveryStatus.DELIVERED.rawValue) }
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun markRead(conversationId: Long): Flow<Result<Unit>> = flow {
        try {
            api.markRead(conversationId)
            runCatching { conversationDao.markAsRead(conversationId) }
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reactToMessage(messageId: Long, reaction: String): Flow<Result<Unit>> = flow {
        try {
            val resp = api.reactToChatMessage(messageId, MessageReactionRequest(reaction = reaction))
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to update reaction.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deleteMessageForMe(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            val resp = api.deleteMessageForMe(messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to delete message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun deleteMessageForEveryone(messageId: Long): Flow<Result<Unit>> = flow {
        try {
            val resp = api.deleteMessageForEveryone(messageId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to delete message for everyone.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun reportMessage(messageId: Long, reason: String, details: String?): Flow<Result<Boolean>> = flow {
        try {
            val resp = api.reportMessage(messageId, ReportMarketplaceRequest(reason = reason, details = details))
            if (resp.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to report message.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun togglePinConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.togglePinConversation(conversationId)
            if (response.isSuccessful) {
                val isPinned = response.body()?.data?.get("is_pinned")?.jsonPrimitive?.booleanOrNull ?: false
                emit(Result.Success(isPinned))
            } else {
                emit(Result.Error(Exception("Failed to pin conversation.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun toggleArchiveConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.toggleArchiveConversation(conversationId)
            if (response.isSuccessful) {
                val isArchived = response.body()?.data?.get("is_archived")?.jsonPrimitive?.booleanOrNull ?: false
                emit(Result.Success(isArchived))
            } else {
                emit(Result.Error(Exception("Failed to archive conversation.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun muteConversation(conversationId: Long, duration: String): Flow<Result<Boolean>> = flow {
        try {
            val response = api.muteConversation(conversationId, mapOf("duration" to duration))
            if (response.isSuccessful) {
                val isMuted = response.body()?.data?.get("is_muted")?.jsonPrimitive?.booleanOrNull
                    ?: (duration != "unmute")
                emit(Result.Success(isMuted))
            } else {
                emit(Result.Error(Exception("Failed to update mute settings.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun clearConversation(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.clearConversation(conversationId)
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to clear chat history.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun leaveGroup(conversationId: Long): Flow<Result<Boolean>> = flow {
        try {
            val response = api.leaveConversationGroup(conversationId)
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to leave group.")))
            }
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
                emit(Result.Success(mapDtoToConversation(response.body()!!.data)))
            } else {
                emit(Result.Error(Exception("Failed to update group info.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun addGroupMembers(conversationId: Long, userIds: List<Long>): Flow<Result<Unit>> = flow {
        try {
            val resp = api.addGroupMembers(conversationId, mapOf("user_ids" to userIds))
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to add group members.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun removeGroupMember(conversationId: Long, userId: Long): Flow<Result<Unit>> = flow {
        try {
            val resp = api.removeConversationGroupMember(conversationId, userId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to remove member.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun updateGroupMemberRole(conversationId: Long, userId: Long, role: String): Flow<Result<Boolean>> = flow {
        try {
            val resp = api.updateConversationMemberRole(conversationId, userId, mapOf("role" to role))
            if (resp.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to update member role.")))
            }
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
            val resp = api.markConversationUnread(conversationId)
            if (resp.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to mark conversation unread.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun sendTyping(conversationId: Long, isTyping: Boolean) {
        reverbManager.sendTyping(conversationId, isTyping)
        backgroundScope.launch {
            runCatching {
                api.presenceTyping(
                    mapOf(
                        "conversation_id" to conversationId.toString(),
                        "is_typing" to isTyping.toString()
                    )
                )
            }
        }
    }

    override suspend fun sendPresenceHeartbeat() {
        runCatching { api.presenceHeartbeat() }
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
                val msgs = response.body()!!.data.map { mapDtoToMessage(it, conversationId ?: it.conversation_id) }
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
                emit(Result.Success(data.latest_event_id))
            } else {
                emit(Result.Success(sinceId))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    private fun mapDtoToConversation(dto: ConversationDto): Conversation {
        val otherUser = dto.other_user?.let {
            User(
                id = it.id,
                name = it.name.ifBlank { it.username },
                username = it.username,
                avatar = normalizeBackendUrl(it.resolvedAvatar),
                bio = it.bio,
                isOnline = it.is_online || dto.is_online
            )
        }
        val members = dto.members.map { m ->
            User(
                id = m.id,
                name = m.name.ifBlank { m.username },
                username = m.username,
                avatar = normalizeBackendUrl(m.resolvedAvatar),
                isOnline = m.is_online
            )
        }
        val memberRoles = dto.members.associate { m -> m.id to (m.role ?: "member") }
        val settingsObj = dto.settings as? JsonObject
        val canSend = settingsObj?.get("can_send_messages")?.jsonPrimitive?.contentOrNull ?: "all"
        val canAdd = settingsObj?.get("can_add_members")?.jsonPrimitive?.contentOrNull ?: "all"
        val canEdit = settingsObj?.get("can_edit_info")?.jsonPrimitive?.contentOrNull ?: "admins"

        val lastMsg = dto.last_message?.let { mapDtoToMessage(it, dto.id) }
        val lastTimestampIso = dto.last_message_at
            ?: dto.last_message?.sent_at
            ?: dto.last_message?.created_at
            ?: dto.created_at

        return Conversation(
            id = dto.id,
            type = dto.type,
            title = when {
                dto.is_saved || dto.type.equals("saved", ignoreCase = true) -> "Saved Messages"
                !dto.title.isNullOrBlank() -> dto.title
                otherUser != null -> otherUser.name
                members.isNotEmpty() -> members.joinToString(", ") { it.name }.take(40)
                else -> "Conversation"
            },
            avatar = normalizeBackendUrl(dto.avatar_url ?: dto.other_user?.resolvedAvatar),
            description = dto.description,
            creatorId = dto.creator_id,
            isSaved = dto.is_saved || dto.type.equals("saved", ignoreCase = true),
            otherUser = otherUser,
            lastMessage = lastMsg,
            lastMessageTimeFormatted = formatRelativeTimestamp(lastTimestampIso),
            lastMessageAtIso = lastTimestampIso,
            unreadCount = dto.unread_count,
            isPinned = dto.is_pinned,
            isMuted = dto.is_muted,
            mutedUntil = dto.muted_until,
            isArchived = dto.is_archived,
            isRequest = dto.is_request,
            requestStatus = dto.request_status,
            isOnline = dto.is_online || (otherUser?.isOnline == true),
            draftMessage = dto.draft_message,
            myRole = dto.my_role,
            participantsCount = if (dto.participants_count > 0) dto.participants_count else members.size,
            members = members,
            memberRoles = memberRoles,
            canSendMessages = canSend,
            canAddMembers = canAdd,
            canEditInfo = canEdit
        )
    }

    private fun mapDtoToMessage(dto: MessageDto, fallbackConversationId: Long = 0L): Message {
        val currentUserId = tokenStorageManager.getUserId()
        val senderObj = dto.sender
        val senderNameProp = dto.sender_name
        val rawBodyProp = dto.body
        val dtoSenderId = dto.sender_id ?: 0L

        val resolvedSenderId = when {
            dtoSenderId > 0L -> dtoSenderId
            senderObj != null && senderObj.id > 0L -> senderObj.id
            else -> 0L
        }
        val isMine = dto.is_mine || (currentUserId != null && currentUserId > 0L && resolvedSenderId == currentUserId)
        val resolvedSenderName: String = when {
            isMine -> "You"
            !senderObj?.name.isNullOrBlank() -> senderObj?.name.orEmpty()
            !senderNameProp.isNullOrBlank() -> senderNameProp
            !senderObj?.username.isNullOrBlank() -> senderObj?.username.orEmpty()
            else -> "Member"
        }

        val rawMediaDtos = if (dto.media.isNotEmpty()) dto.media else dto.attachments
        val mappedAttachments = rawMediaDtos.map { mapMediaDtoToAttachment(it) }
        val mediaUrls = mappedAttachments.map { it.previewUrl ?: it.url }.filter { it.isNotBlank() }

        val metadataObj = dto.metadata as? JsonObject
        val durationSec = metadataObj?.get("duration")?.jsonPrimitive?.intOrNull
            ?: metadataObj?.get("duration_seconds")?.jsonPrimitive?.intOrNull
        val callTypeMeta = metadataObj?.get("call_type")?.jsonPrimitive?.contentOrNull
        val callOutcomeMeta = metadataObj?.get("outcome")?.jsonPrimitive?.contentOrNull
            ?: metadataObj?.get("status")?.jsonPrimitive?.contentOrNull

        val token = tokenStorageManager.getAccessToken()
        val resolvedAudioUrl = if (dto.type.equals("voice", ignoreCase = true) || metadataObj?.get("audio_url") != null) {
            val baseApi = NetworkModule.BASE_URL.trimEnd('/')
            if (!token.isNullOrBlank()) {
                "$baseApi/v1/messages/${dto.id}/voice?token=$token"
            } else {
                "$baseApi/v1/messages/${dto.id}/voice"
            }
        } else {
            mappedAttachments.firstOrNull { it.isAudio }?.url
        }

        val replyDto = dto.reply_preview ?: dto.reply_to
        val timestampIso = dto.sent_at ?: dto.created_at
        val createdMillis = parseIsoToMillis(timestampIso)

        val fallbackBody: String = when {
            dto.is_deleted -> "This message was deleted."
            !rawBodyProp.isNullOrBlank() -> rawBodyProp
            dto.type == "voice" -> "Voice message"
            dto.type == "media" -> if (mappedAttachments.any { it.isVideo }) "Shared a video" else "Shared a photo"
            dto.type == "file" -> mappedAttachments.firstOrNull()?.fileName ?: "Shared a file"
            dto.type == "call" -> "Call"
            else -> ""
        }

        return Message(
            id = dto.id,
            conversationId = if (dto.conversation_id > 0L) dto.conversation_id else fallbackConversationId,
            senderId = resolvedSenderId,
            senderName = resolvedSenderName,
            senderAvatar = normalizeBackendUrl(dto.sender?.resolvedAvatar),
            body = fallbackBody,
            type = dto.type,
            mediaUrls = mediaUrls,
            attachments = mappedAttachments,
            clientMessageId = dto.client_message_id ?: "msg_${dto.id}",
            deliveryStatus = when (dto.delivery_status.lowercase(Locale.US)) {
                "read", "seen" -> DeliveryStatus.READ
                "delivered" -> DeliveryStatus.DELIVERED
                "failed" -> DeliveryStatus.FAILED
                "sending" -> DeliveryStatus.SENDING
                else -> DeliveryStatus.SENT
            },
            isMine = isMine,
            isEdited = dto.is_edited,
            isPinned = dto.is_pinned,
            isSaved = dto.is_saved,
            isDeleted = dto.is_deleted,
            isForwarded = dto.is_forwarded,
            version = dto.version,
            replyToMessageId = dto.reply_to_message_id,
            replyPreview = replyDto?.let {
                MessagePreview(
                    id = it.id,
                    senderName = it.sender_name ?: "User",
                    body = it.body ?: "",
                    type = it.type
                )
            },
            reactions = parseReactionCounts(dto.reactions?.counts),
            myReaction = dto.reactions?.my_reaction,
            audioUrl = resolvedAudioUrl,
            audioDurationSeconds = durationSec,
            callType = callTypeMeta,
            callOutcome = callOutcomeMeta,
            callDurationSeconds = durationSec,
            formattedTime = formatMessageClockTime(timestampIso),
            createdAt = createdMillis,
            sentAt = parseIsoToMillisOrNull(dto.sent_at),
            deliveredAt = parseIsoToMillisOrNull(dto.delivered_at),
            readAt = parseIsoToMillisOrNull(dto.read_at)
        )
    }

    private fun parseReactionCounts(countsElement: JsonElement?): Map<String, Int> {
        val obj = countsElement as? JsonObject ?: return emptyMap()
        if (obj.isEmpty()) return emptyMap()
        val result = LinkedHashMap<String, Int>(obj.size)
        for ((emoji, value) in obj) {
            val count = value.jsonPrimitive.intOrNull ?: 0
            if (emoji.isNotBlank() && count > 0) {
                result[emoji] = count
            }
        }
        return result
    }

    private fun mapMediaDtoToAttachment(
        dto: MediaUploadResponse,
        fallbackName: String = "attachment",
        fallbackMime: String = "application/octet-stream"
    ): MessageAttachment {
        val primaryUrl = normalizeBackendUrl(dto.original_url ?: dto.url ?: dto.preview_url) ?: ""
        val preview = normalizeBackendUrl(dto.preview_url ?: dto.thumbnail_url ?: dto.url)
        val download = normalizeBackendUrl(dto.download_url ?: primaryUrl)
        return MessageAttachment(
            id = dto.id ?: 0L,
            url = primaryUrl,
            previewUrl = preview,
            downloadUrl = download,
            mimeType = dto.mime_type ?: fallbackMime,
            fileName = dto.filename ?: dto.name ?: fallbackName,
            fileSize = dto.size ?: 0L,
            width = dto.width,
            height = dto.height
        )
    }

    private fun normalizeBackendUrl(rawUrl: String?): String? {
        if (rawUrl.isNullOrBlank()) return null
        val trimmed = rawUrl.trim()
        val serverRoot = NetworkModule.BASE_URL
            .removeSuffix("api/")
            .removeSuffix("api")
            .trimEnd('/')
        val resolved = if (trimmed.startsWith("/")) {
            "$serverRoot$trimmed"
        } else {
            trimmed
        }
        return resolved
            .replace("http://localhost:8000", serverRoot)
            .replace("http://127.0.0.1:8000", serverRoot)
            .replace("http://10.0.2.2:8000", serverRoot)
            .replace("http://192.168.0.184:8000", serverRoot)
            .replace("http://bondhoo.com", "https://bondhoo.com")
    }

    private fun parseIsoToMillis(iso: String?): Long =
        parseIsoToMillisOrNull(iso) ?: System.currentTimeMillis()

    private fun parseIsoToMillisOrNull(iso: String?): Long? {
        if (iso.isNullOrBlank()) return null
        return runCatching { Instant.parse(iso).toEpochMilli() }.getOrNull()
    }

    private fun formatMessageClockTime(iso: String?): String {
        if (iso.isNullOrBlank()) return ""
        return runCatching {
            val instant = Instant.parse(iso)
            val local = instant.atZone(ZoneId.systemDefault())
            DateTimeFormatter.ofPattern("h:mm a", Locale.getDefault()).format(local)
        }.getOrDefault("")
    }

    private fun formatRelativeTimestamp(iso: String?): String {
        if (iso.isNullOrBlank()) return ""
        return runCatching {
            val instant = Instant.parse(iso)
            val now = Instant.now()
            val minutes = Duration.between(instant, now).toMinutes()
            val local = instant.atZone(ZoneId.systemDefault())
            val nowLocal = now.atZone(ZoneId.systemDefault())
            when {
                minutes < 1 -> "Just now"
                minutes < 60 -> "${minutes}m"
                local.toLocalDate() == nowLocal.toLocalDate() ->
                    DateTimeFormatter.ofPattern("h:mm a", Locale.getDefault()).format(local)
                local.toLocalDate() == nowLocal.toLocalDate().minusDays(1) -> "Yesterday"
                local.year == nowLocal.year ->
                    DateTimeFormatter.ofPattern("MMM d", Locale.getDefault()).format(local)
                else ->
                    DateTimeFormatter.ofPattern("MMM d, yy", Locale.getDefault()).format(local)
            }
        }.getOrDefault("")
    }
}
