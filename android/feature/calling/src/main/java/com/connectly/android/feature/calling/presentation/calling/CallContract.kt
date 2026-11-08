package com.connectly.android.feature.calling.presentation.calling

import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallParticipant
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality
import java.util.Locale

data class CallState(
    val callSession: CallSession? = null,
    val callType: CallType = CallType.AUDIO,
    val callStatus: CallStatus = CallStatus.DIALING,
    val isIncoming: Boolean = false,
    val hasMediaPermissions: Boolean = false,
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val isFrontCamera: Boolean = true,
    val isSpeakerOn: Boolean = false,
    val durationSeconds: Int = 0,
    val networkQuality: NetworkQuality = NetworkQuality.EXCELLENT,
    val localVideoTrack: org.webrtc.VideoTrack? = null,
    val remoteVideoTrack: org.webrtc.VideoTrack? = null,
    val eglBaseContext: org.webrtc.EglBase.Context? = null,
    val showInviteSheet: Boolean = false,
    val inviteSearchQuery: String = "",
    val inviteFriends: List<ActivePresenceFriend> = emptyList(),
    val statusMessage: String? = null,
    val errorMessage: String? = null,
    val isCallEnded: Boolean = false
) {
    val formattedDuration: String
        get() {
            val minutes = durationSeconds / 60
            val seconds = durationSeconds % 60
            return String.format(Locale.US, "%02d:%02d", minutes, seconds)
        }

    val isGroupCall: Boolean
        get() = callType.isGroup || callSession?.isGroupCall == true

    val displayTitle: String
        get() = when {
            !callSession?.title.isNullOrBlank() -> callSession?.title.orEmpty()
            isIncoming -> callSession?.caller?.name?.takeIf { it.isNotBlank() } ?: "Incoming Call"
            else -> callSession?.receiver?.name?.takeIf { it.isNotBlank() } ?: "Calling..."
        }

    val displayAvatar: String?
        get() = if (isIncoming) {
            callSession?.caller?.avatar
        } else {
            callSession?.receiver?.avatar
        }

    val participants: List<CallParticipant>
        get() = callSession?.participants ?: emptyList()
}

sealed interface CallIntent {
    data class StartOutgoingCall(
        val receiverId: Long = 0L,
        val callType: CallType,
        val conversationId: Long? = null,
        val receiverIds: List<Long> = emptyList()
    ) : CallIntent

    data class IncomingCallReceived(
        val callId: Long,
        val callType: CallType,
        val conversationId: Long? = null
    ) : CallIntent

    data object MediaPermissionsGranted : CallIntent
    data object SyncActiveCallState : CallIntent
    data object AcceptCall : CallIntent
    data object RejectCall : CallIntent
    data object EndCall : CallIntent
    data object ToggleMute : CallIntent
    data object ToggleCamera : CallIntent
    data object SwitchCamera : CallIntent
    data object ToggleSpeaker : CallIntent
    data class OpenInviteSheet(val show: Boolean) : CallIntent
    data class InviteSearchChanged(val query: String) : CallIntent
    data class InviteFriends(val userIds: List<Long>) : CallIntent
    data object ClearMessages : CallIntent
    data object TickTimer : CallIntent
}
