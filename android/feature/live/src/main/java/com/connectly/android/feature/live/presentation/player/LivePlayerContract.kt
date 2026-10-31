package com.connectly.android.feature.live.presentation.player

import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream

data class LivePlayerState(
    val liveStream: LiveStream? = null,
    val comments: List<LiveComment> = emptyList(),
    val floatingReactions: List<String> = emptyList(),
    val isGiftSheetOpen: Boolean = false,
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface LivePlayerIntent {
    data class LoadStream(val streamId: Long) : LivePlayerIntent
    data class SendComment(val text: String) : LivePlayerIntent
    data class SendReaction(val type: String) : LivePlayerIntent
    data object ToggleGiftSheet : LivePlayerIntent
    data object ShareStream : LivePlayerIntent
}

