package com.connectly.android.feature.messenger.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.MessageAttachment
import com.connectly.android.core.model.PinnedMessageInfo
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import kotlinx.coroutines.flow.Flow
import java.io.File
import javax.inject.Inject

class GetConversationsUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(filter: String = "all", search: String? = null): Flow<Result<List<Conversation>>> =
        repository.getConversations(filter, search)
}

class GetConversationDetailsUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long): Flow<Result<Conversation>> =
        repository.getConversationDetails(conversationId)
}

class CreateConversationUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(
        recipientId: Long? = null,
        title: String? = null,
        participantIds: List<Long>? = null,
        type: String? = null,
        description: String? = null
    ): Flow<Result<Conversation>> =
        repository.createConversation(recipientId, title, participantIds, type, description)

    fun openSaved(): Flow<Result<Conversation>> = repository.openSavedConversation()
}

class GetFriendsForMessagingUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(query: String? = null): Flow<Result<List<ActivePresenceFriend>>> =
        repository.getFriendsForMessaging(query)
}

class GetMessengerCallHistoryUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(): Flow<Result<List<CallHistoryItem>>> =
        repository.getCallHistoryForMessenger()
}

class GetMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long, page: Int = 1, perPage: Int = 50, search: String? = null): Flow<Result<List<Message>>> =
        repository.getMessages(conversationId, page, perPage, search)
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

class UploadMessageAttachmentUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(file: File, mimeType: String): Flow<Result<MessageAttachment>> =
        repository.uploadAttachment(file, mimeType)
}

class EditMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long, newBody: String): Flow<Result<Message>> =
        repository.editMessage(messageId, newBody)
}

class ForwardMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long, targetConversationIds: List<Long>): Flow<Result<List<Message>>> =
        repository.forwardMessage(messageId, targetConversationIds)
}

class SaveMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    fun save(messageId: Long): Flow<Result<Unit>> = repository.saveMessage(messageId)
    fun unsave(messageId: Long): Flow<Result<Unit>> = repository.unsaveMessage(messageId)
    fun getSaved(): Flow<Result<List<Message>>> = repository.getSavedMessages()
}

class PinMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    fun pin(conversationId: Long, messageId: Long): Flow<Result<Boolean>> =
        repository.pinMessage(conversationId, messageId)

    fun unpin(conversationId: Long, messageId: Long): Flow<Result<Boolean>> =
        repository.unpinMessage(conversationId, messageId)

    fun getPinned(conversationId: Long): Flow<Result<List<PinnedMessageInfo>>> =
        repository.getPinnedMessages(conversationId)
}

class SendVoiceMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long, audioFile: File, durationSeconds: Int, waveform: List<Float>? = null): Flow<Result<Message>> =
        repository.sendVoiceMessage(conversationId, audioFile, durationSeconds, waveform)
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

class ReportMessageUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(messageId: Long, reason: String, details: String? = null): Flow<Result<Boolean>> =
        repository.reportMessage(messageId, reason, details)
}

class ObserveIncomingMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long): Flow<Message> =
        repository.observeIncomingMessages(conversationId)
}

class ManageConversationUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    fun togglePin(conversationId: Long): Flow<Result<Boolean>> = repository.togglePinConversation(conversationId)
    fun toggleArchive(conversationId: Long): Flow<Result<Boolean>> = repository.toggleArchiveConversation(conversationId)
    fun mute(conversationId: Long, duration: String = "forever"): Flow<Result<Boolean>> = repository.muteConversation(conversationId, duration)
    fun clear(conversationId: Long): Flow<Result<Boolean>> = repository.clearConversation(conversationId)
    fun leave(conversationId: Long): Flow<Result<Boolean>> = repository.leaveGroup(conversationId)
    fun deleteConversation(conversationId: Long): Flow<Result<Boolean>> = repository.deleteConversation(conversationId)
    fun handleRequest(conversationId: Long, action: String): Flow<Result<Boolean>> = repository.handleMessageRequest(conversationId, action)
    fun updateGroupInfo(conversationId: Long, title: String?, description: String?, avatarUrl: String? = null): Flow<Result<Conversation>> =
        repository.updateGroupInfo(conversationId, title, description, avatarUrl)
    fun addMembers(conversationId: Long, userIds: List<Long>): Flow<Result<Unit>> = repository.addGroupMembers(conversationId, userIds)
    fun removeMember(conversationId: Long, userId: Long): Flow<Result<Unit>> = repository.removeGroupMember(conversationId, userId)
    fun updateMemberRole(conversationId: Long, userId: Long, role: String): Flow<Result<Boolean>> =
        repository.updateGroupMemberRole(conversationId, userId, role)
    fun getMedia(conversationId: Long, type: String = "media"): Flow<Result<List<MessageAttachment>>> =
        repository.getConversationMedia(conversationId, type)
    fun saveDraft(conversationId: Long, draftText: String): Flow<Result<Unit>> = repository.saveDraft(conversationId, draftText)
    fun markUnread(conversationId: Long): Flow<Result<Unit>> = repository.markConversationUnread(conversationId)
}

class SearchMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(query: String, conversationId: Long? = null): Flow<Result<List<Message>>> =
        repository.searchMessages(query, conversationId)
}

class SyncOfflineMessagesUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(sinceId: Long): Flow<Result<Long>> =
        repository.syncOfflineEvents(sinceId)
}

class SendTypingUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long, isTyping: Boolean) =
        repository.sendTyping(conversationId, isTyping)
}

class ObserveTypingUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(conversationId: Long): Flow<Pair<Long, Boolean>> =
        repository.observeTyping(conversationId)
}

class ObservePresenceUseCase @Inject constructor(
    private val repository: MessengerRepository
) {
    operator fun invoke(): Flow<Pair<Long, Boolean>> =
        repository.observePresence()
}
