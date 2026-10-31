package com.connectly.android.feature.calling.presentation.calling

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.User
import com.connectly.android.feature.calling.data.webrtc.WebRtcCallManager
import com.connectly.android.feature.calling.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CallViewModel @Inject constructor(
    private val initiateCallUseCase: InitiateCallUseCase,
    private val respondCallUseCase: RespondCallUseCase,
    private val leaveCallUseCase: LeaveCallUseCase,
    private val updateCallStateUseCase: UpdateCallStateUseCase,
    private val webRtcManager: WebRtcCallManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(CallState())
    val uiState: StateFlow<CallState> = _uiState.asStateFlow()

    private var timerJob: Job? = null

    init {
        // Observe WebRTC manager states
        viewModelScope.launch {
            webRtcManager.isMuted.collect { muted ->
                _uiState.update { it.copy(isMuted = muted) }
            }
        }
        viewModelScope.launch {
            webRtcManager.isCameraOff.collect { camOff ->
                _uiState.update { it.copy(isCameraOff = camOff) }
            }
        }
        viewModelScope.launch {
            webRtcManager.isSpeakerOn.collect { speaker ->
                _uiState.update { it.copy(isSpeakerOn = speaker) }
            }
        }
        viewModelScope.launch {
            webRtcManager.networkQuality.collect { quality ->
                _uiState.update { it.copy(networkQuality = quality) }
            }
        }
    }

    fun handleIntent(intent: CallIntent) {
        when (intent) {
            is CallIntent.StartOutgoingCall -> startOutgoingCall(intent.receiverId, intent.callType)
            is CallIntent.IncomingCallReceived -> setupIncomingCall(intent.callId, intent.callType)
            CallIntent.AcceptCall -> acceptCall()
            CallIntent.RejectCall -> rejectCall()
            CallIntent.EndCall -> endCall()
            CallIntent.ToggleMute -> toggleMute()
            CallIntent.ToggleCamera -> toggleCamera()
            CallIntent.SwitchCamera -> switchCamera()
            CallIntent.ToggleSpeaker -> toggleSpeaker()
            CallIntent.TickTimer -> tickTimer()
        }
    }

    private fun startOutgoingCall(receiverId: Long, callType: CallType) {
        _uiState.update {
            it.copy(
                callType = callType,
                callStatus = CallStatus.DIALING,
                isIncoming = false,
                isSpeakerOn = callType == CallType.VIDEO
            )
        }

        viewModelScope.launch {
            initiateCallUseCase(receiverId, callType).collect { result ->
                when (result) {
                    is Result.Loading -> {}
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                callSession = result.data,
                                callStatus = CallStatus.RINGING
                            )
                        }
                        // Simulate call pickup after 2.5s for demonstration
                        delay(2500)
                        transitionToConnected()
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }

    private fun setupIncomingCall(callId: Long, callType: CallType) {
        val caller = User(id = 51L, name = "Sadia Islam", username = "sadia_islam")
        val session = CallSession(
            id = callId,
            channelName = "channel_$callId",
            callType = callType,
            status = CallStatus.RINGING,
            caller = caller,
            receiver = User(id = 1L, name = "You", username = "you"),
            isIncoming = true
        )
        _uiState.update {
            it.copy(
                callSession = session,
                callType = callType,
                callStatus = CallStatus.RINGING,
                isIncoming = true
            )
        }
    }

    private fun acceptCall() {
        val callId = _uiState.value.callSession?.id ?: 1L
        viewModelScope.launch {
            respondCallUseCase(callId, "accept").collect { result ->
                if (result is Result.Success) {
                    transitionToConnected()
                }
            }
        }
    }

    private fun rejectCall() {
        val callId = _uiState.value.callSession?.id ?: 1L
        stopTimer()
        viewModelScope.launch {
            respondCallUseCase(callId, "reject").collect()
            _uiState.update { it.copy(callStatus = CallStatus.REJECTED, isCallEnded = true) }
        }
    }

    private fun endCall() {
        val callId = _uiState.value.callSession?.id ?: 1L
        stopTimer()
        viewModelScope.launch {
            leaveCallUseCase(callId).collect()
            _uiState.update { it.copy(callStatus = CallStatus.ENDED, isCallEnded = true) }
        }
    }

    private fun transitionToConnected() {
        _uiState.update { it.copy(callStatus = CallStatus.CONNECTED) }
        startTimer()
    }

    private fun toggleMute() {
        val isMuted = webRtcManager.toggleMute()
        _uiState.value.callSession?.id?.let { callId ->
            viewModelScope.launch {
                updateCallStateUseCase(callId, isMuted = isMuted, isCameraOff = null).collect()
            }
        }
    }

    private fun toggleCamera() {
        val isCameraOff = webRtcManager.toggleCamera()
        _uiState.value.callSession?.id?.let { callId ->
            viewModelScope.launch {
                updateCallStateUseCase(callId, isMuted = null, isCameraOff = isCameraOff).collect()
            }
        }
    }

    private fun switchCamera() {
        val isFront = webRtcManager.switchCamera()
        _uiState.update { it.copy(isFrontCamera = isFront) }
    }

    private fun toggleSpeaker() {
        webRtcManager.toggleSpeaker()
    }

    private fun startTimer() {
        timerJob?.cancel()
        timerJob = viewModelScope.launch {
            while (isActive) {
                delay(1000)
                tickTimer()
            }
        }
    }

    private fun tickTimer() {
        _uiState.update { it.copy(durationSeconds = it.durationSeconds + 1) }
    }

    private fun stopTimer() {
        timerJob?.cancel()
        timerJob = null
    }

    override fun onCleared() {
        super.onCleared()
        stopTimer()
        webRtcManager.endAudioSession()
    }
}
