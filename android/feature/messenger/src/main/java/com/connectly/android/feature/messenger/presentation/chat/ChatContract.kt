package com.connectly.android.feature.messenger.presentation.chat

import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.MessageAttachment
import com.connectly.android.core.model.PinnedMessageInfo
import java.io.File

data class ChatState(
    val conversationId: Long = 0L,
    val conversation: Conversation? = null,
    val participantName: String = "Loading...",
    val participantAvatar: String? = null,
    val isOnline: Boolean = false,
    val isTyping: Boolean = false,
    val typingUserName: String? = null,
    val messages: List<Message> = emptyList(),
    val pinnedMessages: List<PinnedMessageInfo> = emptyList(),
    val isSearchMode: Boolean = false,
    val searchQuery: String = "",
    val searchResults: List<Message>? = null,
    val replyingToMessage: Message? = null,
    val editingMessage: Message? = null,
    val forwardingMessage: Message? = null,
    val forwardTargets: List<Conversation> = emptyList(),
    val forwardFriends: List<ActivePresenceFriend> = emptyList(),
    val isUploadingAttachment: Boolean = false,
    val isRecordingVoice: Boolean = false,
    val recordingDurationSeconds: Int = 0,
    val playingMessageId: Long? = null,
    val isAudioPlaying: Boolean = false,
    val showChatInfoSheet: Boolean = false,
    val sharedMedia: List<MessageAttachment> = emptyList(),
    val sharedFiles: List<MessageAttachment> = emptyList(),
    val isLoadingSharedMedia: Boolean = false,
    val availableFriendsForGroup: List<ActivePresenceFriend> = emptyList(),
    val isLoading: Boolean = false,
    val statusMessage: String? = null,
    val errorMessage: String? = null
) {
    val displayedMessages: List<Message>
        get() = if (isSearchMode && searchQuery.isNotBlank()) {
            searchResults ?: messages.filter {
                it.body.contains(searchQuery, ignoreCase = true) ||
                    it.senderName.contains(searchQuery, ignoreCase = true)
            }
        } else {
            messages
        }

    val isGroup: Boolean get() = conversation?.isGroup == true
    val isSaved: Boolean get() = conversation?.isSaved == true || conversation?.type == "saved"
    val isPendingRequest: Boolean
        get() = conversation?.isRequest == true &&
            conversation.requestStatus.equals("pending", ignoreCase = true)
}

sealed interface ChatEffect {
    data object NavigateBack : ChatEffect
    data class OpenOtherChat(val conversationId: Long) : ChatEffect
    data class StartCall(
        val conversationId: Long?,
        val receiverId: Long?,
        val receiverIds: List<Long> = emptyList(),
        val callType: CallType
    ) : ChatEffect
}

sealed interface ChatIntent {
    data class LoadChat(val conversationId: Long) : ChatIntent
    data object RefreshChat : ChatIntent
    data class SendTextMessage(val body: String) : ChatIntent
    data class RetryMessage(val message: Message) : ChatIntent
    data class SendAttachmentFile(
        val file: File,
        val mimeType: String,
        val caption: String = ""
    ) : ChatIntent
    data class SendVoiceFile(
        val audioFile: File,
        val durationSeconds: Int
    ) : ChatIntent
    data class TogglePlayVoice(val message: Message) : ChatIntent
    data class SetReplyTo(val message: Message?) : ChatIntent
    data class StartEditMessage(val message: Message?) : ChatIntent
    data class SubmitEditMessage(val messageId: Long, val newBody: String) : ChatIntent
    data class OpenForwardSheet(val message: Message?) : ChatIntent
    data class ForwardToConversation(val messageId: Long, val targetConversationId: Long) : ChatIntent
    data class ForwardToFriend(val messageId: Long, val friendId: Long) : ChatIntent
    data class ReactMessage(val messageId: Long, val reaction: String) : ChatIntent
    data class TogglePinMessage(val message: Message) : ChatIntent
    data class ToggleSaveMessage(val message: Message) : ChatIntent
    data class DeleteMessage(val messageId: Long, val forEveryone: Boolean) : ChatIntent
    data class ReportMessage(val messageId: Long, val reason: String, val details: String? = null) : ChatIntent
    data class TypingChanged(val isTyping: Boolean) : ChatIntent
    data class ToggleSearchMode(val enabled: Boolean) : ChatIntent
    data class SearchQueryChanged(val query: String) : ChatIntent
    data class HandleMessageRequest(val action: String) : ChatIntent
    data class ToggleChatInfoSheet(val show: Boolean) : ChatIntent
    data object ToggleMuteConversation : ChatIntent
    data object TogglePinConversation : ChatIntent
    data object ToggleArchiveConversation : ChatIntent
    data object ClearConversationHistory : ChatIntent
    data object DeleteConversation : ChatIntent
    data object LeaveGroup : ChatIntent
    data class UpdateGroupInfo(val title: String, val description: String?) : ChatIntent
    data object LoadFriendsForGroup : ChatIntent
    data class AddGroupMembers(val userIds: List<Long>) : ChatIntent
    data class RemoveGroupMember(val userId: Long) : ChatIntent
    data class UpdateMemberRole(val userId: Long, val role: String) : ChatIntent
    data class MessageMemberDirectly(val userId: Long) : ChatIntent
    data class StartCallFromChat(val isVideo: Boolean) : ChatIntent
    data object ClearStatusMessages : ChatIntent
}
