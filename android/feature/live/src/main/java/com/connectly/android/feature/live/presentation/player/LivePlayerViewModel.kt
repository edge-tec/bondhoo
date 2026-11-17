package com.connectly.android.feature.live.presentation.player

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.feature.live.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import org.webrtc.EglBase
import javax.inject.Inject

@HiltViewModel
class LivePlayerViewModel @Inject constructor(
    private val getActiveLiveStreamUseCase: GetActiveLiveStreamUseCase,
    private val startLiveStreamUseCase: StartLiveStreamUseCase,
    private val endLiveStreamUseCase: EndLiveStreamUseCase,
    private val sendLiveCommentUseCase: SendLiveCommentUseCase,
    private val sendLiveReactionUseCase: SendLiveReactionUseCase,
    private val observeLiveCommentsUseCase: ObserveLiveCommentsUseCase,
    private val webRtcEngine: WebRtcEngine,
    private val api: ConnectlyApi
) : ViewModel() {

    val eglBaseContext: EglBase.Context get() = webRtcEngine.eglBaseContext

    private val _uiState = MutableStateFlow(LivePlayerState())
    val uiState: StateFlow<LivePlayerState> = _uiState.asStateFlow()

    private var heartbeatJob: Job? = null
    private var durationTimerJob: Job? = null

    init {
        viewModelScope.launch {
            webRtcEngine.localVideoTrack.collect { track ->
                _uiState.update { it.copy(localVideoTrack = track) }
            }
        }
    }

    fun handleIntent(intent: LivePlayerIntent) {
        when (intent) {
            is LivePlayerIntent.LoadStream -> loadStream(intent.streamId)
            is LivePlayerIntent.SendComment -> sendComment(intent.text)
            is LivePlayerIntent.SendReaction -> sendReaction(intent.type)
            LivePlayerIntent.ToggleGiftSheet -> {
                _uiState.update { it.copy(isGiftSheetOpen = !it.isGiftSheetOpen) }
            }
            LivePlayerIntent.ShareStream -> { /* Share action */ }
            LivePlayerIntent.ToggleMute -> toggleMute()
            LivePlayerIntent.SwitchCamera -> switchCamera()
            LivePlayerIntent.ToggleCamera -> toggleCamera()
            LivePlayerIntent.EndStream -> endStream()
        }
    }

    private fun loadStream(streamId: Long) {
        if (streamId <= 1L) {
            // Broadcaster mode: User wants to Go Live in Live Studio
            webRtcEngine.startBroadcastMedia()
            _uiState.update { it.copy(isBroadcaster = true, isLoading = true) }

            viewModelScope.launch {
                startLiveStreamUseCase("Bondhoo Live Studio Broadcast").collect { result ->
                    when (result) {
                        is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                        is Result.Success -> {
                            val stream = result.data
                            _uiState.update { it.copy(isLoading = false, liveStream = stream) }
                            startDurationTimer()
                            startHeartbeat(stream.id)
                            observeComments(stream.id)
                        }
                        is Result.Error -> {
                            _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                        }
                    }
                }
            }
        } else {
            // Viewer mode: User opened a specific live stream to watch
            _uiState.update { it.copy(isBroadcaster = false, isLoading = true) }

            viewModelScope.launch {
                getActiveLiveStreamUseCase(streamId).collect { result ->
                    when (result) {
                        is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                        is Result.Success -> {
                            _uiState.update { it.copy(isLoading = false, liveStream = result.data) }
                            startDurationTimer()
                            observeComments(streamId)
                        }
                        is Result.Error -> {
                            _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                        }
                    }
                }
            }
        }
    }

    private fun startDurationTimer() {
        durationTimerJob?.cancel()
        durationTimerJob = viewModelScope.launch {
            while (isActive) {
                delay(1000L)
                _uiState.update { it.copy(durationSeconds = it.durationSeconds + 1) }
            }
        }
    }

    private fun startHeartbeat(streamId: Long) {
        heartbeatJob?.cancel()
        heartbeatJob = viewModelScope.launch {
            while (isActive) {
                delay(20000L)
                try {
                    api.sendLiveHeartbeat(streamId)
                } catch (_: Exception) {}
            }
        }
    }

    private fun observeComments(streamId: Long) {
        viewModelScope.launch {
            observeLiveCommentsUseCase(streamId).collect { newComment ->
                _uiState.update { it.copy(comments = it.comments + newComment) }
            }
        }
    }

    private fun toggleMute() {
        val isMuted = webRtcEngine.toggleMute()
        _uiState.update { it.copy(isMuted = isMuted) }
    }

    private fun switchCamera() {
        webRtcEngine.switchCamera()
        _uiState.update { it.copy(isFrontCamera = !it.isFrontCamera) }
    }

    private fun toggleCamera() {
        val isCameraOff = webRtcEngine.toggleCamera()
        _uiState.update { it.copy(isCameraOff = isCameraOff) }
    }

    private fun endStream() {
        val streamId = _uiState.value.liveStream?.id
        heartbeatJob?.cancel()
        heartbeatJob = null
        durationTimerJob?.cancel()
        durationTimerJob = null
        webRtcEngine.stopBroadcastMedia()

        viewModelScope.launch {
            if (streamId != null) {
                try {
                    api.endLiveStream(streamId)
                } catch (_: Exception) {}
            }
            _uiState.update { it.copy(streamEnded = true) }
        }
    }

    override fun onCleared() {
        super.onCleared()
        heartbeatJob?.cancel()
        durationTimerJob?.cancel()
        webRtcEngine.stopBroadcastMedia()

        val streamId = _uiState.value.liveStream?.id
        if (streamId != null && _uiState.value.isBroadcaster && !_uiState.value.streamEnded) {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    api.endLiveStream(streamId)
                } catch (_: Exception) {}
            }
        }
    }

    private fun sendComment(text: String) {
        val streamId = _uiState.value.liveStream?.id ?: 1L
        val localComment = LiveComment(
            id = System.currentTimeMillis(),
            streamId = streamId,
            user = User(id = 1L, name = "You", username = "you"),
            comment = text
        )
        _uiState.update { it.copy(comments = it.comments + localComment) }

        viewModelScope.launch {
            sendLiveCommentUseCase(streamId, text).collect()
        }
    }

    private fun sendReaction(type: String) {
        val streamId = _uiState.value.liveStream?.id ?: 1L
        _uiState.update { it.copy(floatingReactions = it.floatingReactions + type) }

        viewModelScope.launch {
            sendLiveReactionUseCase(streamId, type).collect()
        }
    }
}
