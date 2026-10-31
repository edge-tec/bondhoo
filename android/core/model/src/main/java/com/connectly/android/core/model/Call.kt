package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
enum class CallType(val rawValue: String) {
    AUDIO("audio"),
    VIDEO("video");

    companion object {
        fun from(value: String): CallType = entries.find { it.rawValue.equals(value, ignoreCase = true) } ?: AUDIO
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
        fun from(value: String): CallStatus = entries.find { it.rawValue.equals(value, ignoreCase = true) } ?: RINGING
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
data class CallSession(
    val id: Long,
    val channelName: String,
    val callType: CallType = CallType.AUDIO,
    val status: CallStatus = CallStatus.DIALING,
    val caller: User,
    val receiver: User,
    val isIncoming: Boolean = false,
    val durationSeconds: Int = 0,
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isFrontCamera: Boolean = true,
    val isSpeakerOn: Boolean = false,
    val networkQuality: NetworkQuality = NetworkQuality.EXCELLENT
)

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
    val otherUser: User,
    val callType: CallType,
    val isIncoming: Boolean,
    val isMissed: Boolean,
    val durationSeconds: Int,
    val formattedTime: String
)

