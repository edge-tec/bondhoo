package com.connectly.android.core.webrtc.model

import com.connectly.android.core.model.CallType
import kotlinx.serialization.Serializable

@Serializable
data class SessionDescriptionModel(
    val type: String, // "offer" | "answer" | "pranswer" | "rollback"
    val sdp: String
)

@Serializable
data class IceCandidateModel(
    val candidate: String,
    val sdpMid: String? = null,
    val sdpMLineIndex: Int = 0
)

data class CallParticipantState(
    val userId: Long,
    val userName: String = "",
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isScreenSharing: Boolean = false,
    val isSpeaking: Boolean = false
)

enum class SignalingConnectionState {
    DISCONNECTED,
    CONNECTING,
    CONNECTED,
    RECONNECTING,
    FAILED
}

enum class PeerConnectionState {
    NEW,
    CONNECTING,
    CONNECTED,
    DISCONNECTED,
    FAILED,
    CLOSED
}

enum class IceConnectionState {
    NEW,
    CHECKING,
    CONNECTED,
    COMPLETED,
    FAILED,
    DISCONNECTED,
    CLOSED
}

data class WebRtcCallState(
    val callId: Long = 0L,
    val callType: CallType = CallType.AUDIO,
    val isIncoming: Boolean = false,
    val peerState: PeerConnectionState = PeerConnectionState.NEW,
    val iceState: IceConnectionState = IceConnectionState.NEW,
    val isLocalAudioEnabled: Boolean = true,
    val isLocalVideoEnabled: Boolean = true,
    val isFrontCamera: Boolean = true,
    val isSpeakerOn: Boolean = false,
    val durationSeconds: Int = 0,
    val remoteParticipants: Map<Long, CallParticipantState> = emptyMap(),
    val errorMessage: String? = null
)
