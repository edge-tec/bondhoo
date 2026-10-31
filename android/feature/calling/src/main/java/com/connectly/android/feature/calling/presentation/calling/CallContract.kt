package com.connectly.android.feature.calling.presentation.calling

import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality

data class CallState(
    val callSession: CallSession? = null,
    val callType: CallType = CallType.AUDIO,
    val callStatus: CallStatus = CallStatus.DIALING,
    val isIncoming: Boolean = false,
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isFrontCamera: Boolean = true,
    val isSpeakerOn: Boolean = false,
    val durationSeconds: Int = 0,
    val networkQuality: NetworkQuality = NetworkQuality.EXCELLENT,
    val errorMessage: String? = null,
    val isCallEnded: Boolean = false
) {
    val formattedDuration: String
        get() {
            val minutes = durationSeconds / 60
            val seconds = durationSeconds % 60
            return String.format("%02d:%02d", minutes, seconds)
        }
}

sealed interface CallIntent {
    data class StartOutgoingCall(val receiverId: Long, val callType: CallType) : CallIntent
    data class IncomingCallReceived(val callId: Long, val callType: CallType) : CallIntent
    data object AcceptCall : CallIntent
    data object RejectCall : CallIntent
    data object EndCall : CallIntent
    data object ToggleMute : CallIntent
    data object ToggleCamera : CallIntent
    data object SwitchCamera : CallIntent
    data object ToggleSpeaker : CallIntent
    data object TickTimer : CallIntent
}
