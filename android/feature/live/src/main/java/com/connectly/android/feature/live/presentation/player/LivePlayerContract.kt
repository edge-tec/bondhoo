package com.connectly.android.feature.live.presentation.player

import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import org.webrtc.VideoTrack

data class LivePlayerState(
    val liveStream: LiveStream? = null,
    val comments: List<LiveComment> = emptyList(),
    val floatingReactions: List<String> = emptyList(),
    val isGiftSheetOpen: Boolean = false,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val isBroadcaster: Boolean = false,
    val localVideoTrack: VideoTrack? = null,
    val isFrontCamera: Boolean = true,
    val isMuted: Boolean = false,
    val isCameraOff: Boolean = false,
    val streamEnded: Boolean = false
)

sealed interface LivePlayerIntent {
    data class LoadStream(val streamId: Long) : LivePlayerIntent
    data class SendComment(val text: String) : LivePlayerIntent
    data class SendReaction(val type: String) : LivePlayerIntent
    data object ToggleGiftSheet : LivePlayerIntent
    data object ShareStream : LivePlayerIntent
    data object ToggleMute : LivePlayerIntent
    data object SwitchCamera : LivePlayerIntent
    data object ToggleCamera : LivePlayerIntent
    data object EndStream : LivePlayerIntent
}


