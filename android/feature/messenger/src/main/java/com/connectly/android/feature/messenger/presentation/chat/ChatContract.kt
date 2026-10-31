package com.connectly.android.feature.messenger.presentation.chat

import com.connectly.android.core.model.Message

data class ChatState(
    val conversationId: Long = 0L,
    val participantName: String = "Sadia Islam",
    val participantAvatar: String? = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
    val isOnline: Boolean = true,
    val isTyping: Boolean = false,
    val messages: List<Message> = emptyList(),
    val isRecordingVoice: Boolean = false,
    val recordingDurationSeconds: Int = 0,
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface ChatIntent {
    data class LoadChat(val conversationId: Long) : ChatIntent
    data class SendTextMessage(val body: String) : ChatIntent
    data class SendVoiceMessage(val durationSeconds: Int) : ChatIntent
    data class ReactMessage(val messageId: Long, val reaction: String) : ChatIntent
    data class DeleteMessage(val messageId: Long, val forEveryone: Boolean) : ChatIntent
    data class TypingChanged(val isTyping: Boolean) : ChatIntent
}
