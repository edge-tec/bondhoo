package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
enum class CallType(val rawValue: String) {
    AUDIO("audio"),
    VIDEO("video"),
    GROUP_AUDIO("group_audio"),
    GROUP_VIDEO("group_video");

    val isVideo: Boolean
        get() = this == VIDEO || this == GROUP_VIDEO

    val isGroup: Boolean
        get() = this == GROUP_AUDIO || this == GROUP_VIDEO

    val mediaCallType: CallType
        get() = if (isVideo) VIDEO else AUDIO

    companion object {
        fun from(value: String): CallType =
            entries.find { it.rawValue.equals(value, ignoreCase = true) } ?: AUDIO
    }
}

@Serializable
enum class CallStatus(val rawValue: String) {
    DIALING("dialing"),
    RINGING("ringing"),
    CONNECTING("connecting"),
    CONNECTED("connected"),
    RECONNECTING("reconnecting"),
    ENDED("ended"),
    REJECTED("rejected"),
    MISSED("missed"),
    BUSY("busy");

    companion object {
        fun from(value: String): CallStatus {
            if (value.equals("active", ignoreCase = true)) {
                return CONNECTED
            }
            if (value.equals("failed", ignoreCase = true)) {
                return ENDED
            }
            return entries.find { it.rawValue.equals(value, ignoreCase = true) } ?: RINGING
        }
    }
}

@Serializable
enum class NetworkQuality {
    EXCELLENT,
    GOOD,
    POOR,
    RECONNECTING
}

@Serializable
data class CallParticipant(
    val id: Long = 0L,
    val user: User,
    val role: String = "participant",
    val status: String = "ringing",
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isScreenSharing: Boolean = false
)

@Serializable
data class CallSession(
    val id: Long,
    val channelName: String,
    val conversationId: Long? = null,
    val title: String? = null,
    val callType: CallType = CallType.AUDIO,
    val status: CallStatus = CallStatus.DIALING,
    val caller: User,
    val receiver: User,
    val participants: List<CallParticipant> = emptyList(),
    val isIncoming: Boolean = false,
    val durationSeconds: Int = 0,
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isFrontCamera: Boolean = true,
    val isSpeakerOn: Boolean = false,
    val networkQuality: NetworkQuality = NetworkQuality.EXCELLENT
) {
    val isGroupCall: Boolean
        get() = callType.isGroup || participants.size > 2
}

@Serializable
data class IceServer(
    val urls: List<String>,
    val username: String? = null,
    val credential: String? = null
)

@Serializable
data class WebRtcSignal(
    val signalType: String,
    val payload: Map<String, String>,
    val targetUserId: Long? = null
)

@Serializable
data class CallHistoryItem(
    val id: Long,
    val conversationId: Long? = null,
    val title: String? = null,
    val otherUser: User,
    val participantCount: Int = 2,
    val callType: CallType,
    val isIncoming: Boolean,
    val isMissed: Boolean,
    val outcome: String = "",
    val durationSeconds: Int,
    val formattedTime: String
)
