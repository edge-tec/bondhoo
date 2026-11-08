package com.connectly.android.feature.calling.presentation.calling

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.User
import com.connectly.android.feature.calling.data.webrtc.WebRtcCallManager
import com.connectly.android.feature.calling.domain.usecase.GetCallDetailsUseCase
import com.connectly.android.feature.calling.domain.usecase.GetFriendsToInviteUseCase
import com.connectly.android.feature.calling.domain.usecase.InitiateCallUseCase
import com.connectly.android.feature.calling.domain.usecase.InviteFriendsToCallUseCase
import com.connectly.android.feature.calling.domain.usecase.LeaveCallUseCase
import com.connectly.android.feature.calling.domain.usecase.RespondCallUseCase
import com.connectly.android.feature.calling.domain.usecase.UpdateCallStateUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CallViewModel @Inject constructor(
    private val initiateCallUseCase: InitiateCallUseCase,
    private val getCallDetailsUseCase: GetCallDetailsUseCase,
    private val inviteFriendsToCallUseCase: InviteFriendsToCallUseCase,
    private val getFriendsToInviteUseCase: GetFriendsToInviteUseCase,
    private val respondCallUseCase: RespondCallUseCase,
    private val leaveCallUseCase: LeaveCallUseCase,
    private val updateCallStateUseCase: UpdateCallStateUseCase,
    private val webRtcManager: WebRtcCallManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(CallState())
    val uiState: StateFlow<CallState> = _uiState.asStateFlow()

    private var timerJob: Job? = null
    private var webRtcJob: Job? = null
    private var inviteSearchJob: Job? = null
    private var webRtcStartedForCallId: Long = 0L

    private fun observeWebRtcStates() {
        webRtcJob?.cancel()
        webRtcJob = viewModelScope.launch {
            launch {
                webRtcManager.isMuted.collect { muted ->
                    _uiState.update { it.copy(isMuted = muted) }
                }
            }
            launch {
                webRtcManager.isCameraOff.collect { camOff ->
                    _uiState.update { it.copy(isCameraOff = camOff) }
                }
            }
            launch {
                webRtcManager.isSpeakerOn.collect { speaker ->
                    _uiState.update { it.copy(isSpeakerOn = speaker) }
                }
            }
            launch {
                webRtcManager.networkQuality.collect { quality ->
                    _uiState.update { it.copy(networkQuality = quality) }
                }
            }
            launch {
                webRtcManager.localVideoTrack?.collect { track ->
                    _uiState.update { it.copy(localVideoTrack = track, eglBaseContext = webRtcManager.eglBaseContext) }
                }
            }
            launch {
                webRtcManager.remoteVideoTrack?.collect { track ->
                    _uiState.update { it.copy(remoteVideoTrack = track, eglBaseContext = webRtcManager.eglBaseContext) }
                }
            }
            webRtcManager.signalingCoordinator?.let { coordinator ->
                launch {
                    coordinator.callAcceptedFlow.collect { ev ->
                        if (ev.callId == _uiState.value.callSession?.id) {
                            transitionToConnected()
                            syncActiveCallState()
                        }
                    }
                }
                launch {
                    coordinator.callRejectedFlow.collect { ev ->
                        if (ev.callId == _uiState.value.callSession?.id && !_uiState.value.isGroupCall) {
                            finishLocalCall(CallStatus.REJECTED)
                        } else if (ev.callId == _uiState.value.callSession?.id) {
                            syncActiveCallState()
                        }
                    }
                }
                launch {
                    coordinator.callEndedFlow.collect { ev ->
                        if (ev.callId == _uiState.value.callSession?.id) {
                            finishLocalCall(CallStatus.ENDED)
                        }
                    }
                }
            }
        }
    }

    fun handleIntent(intent: CallIntent) {
        when (intent) {
            is CallIntent.StartOutgoingCall -> startOutgoingCall(
                receiverId = intent.receiverId,
                callType = intent.callType,
                conversationId = intent.conversationId,
                receiverIds = intent.receiverIds
            )
            is CallIntent.IncomingCallReceived -> setupIncomingCall(
                callId = intent.callId,
                callType = intent.callType,
                conversationId = intent.conversationId
            )
            CallIntent.MediaPermissionsGranted -> onMediaPermissionsGranted()
            CallIntent.SyncActiveCallState -> syncActiveCallState()
            CallIntent.AcceptCall -> acceptCall()
            CallIntent.RejectCall -> rejectCall()
            CallIntent.EndCall -> endCall()
            CallIntent.ToggleMute -> toggleMute()
            CallIntent.ToggleCamera -> toggleCamera()
            CallIntent.SwitchCamera -> switchCamera()
            CallIntent.ToggleSpeaker -> toggleSpeaker()
            is CallIntent.OpenInviteSheet -> openInviteSheet(intent.show)
            is CallIntent.InviteSearchChanged -> searchFriendsForInvite(intent.query)
            is CallIntent.InviteFriends -> inviteFriends(intent.userIds)
            CallIntent.ClearMessages -> _uiState.update { it.copy(statusMessage = null, errorMessage = null) }
            CallIntent.TickTimer -> tickTimer()
        }
    }

    private fun startOutgoingCall(
        receiverId: Long,
        callType: CallType,
        conversationId: Long?,
        receiverIds: List<Long>
    ) {
        observeWebRtcStates()
        _uiState.update {
            it.copy(
                callType = callType,
                callStatus = CallStatus.DIALING,
                isIncoming = false,
                isSpeakerOn = callType.isVideo,
                errorMessage = null
            )
        }

        viewModelScope.launch {
            initiateCallUseCase(
                receiverId = receiverId.takeIf { it > 0L },
                callType = callType,
                conversationId = conversationId,
                receiverIds = receiverIds
            ).collect { result ->
                when (result) {
                    is Result.Loading -> Unit
                    is Result.Success -> {
                        val session = result.data
                        _uiState.update {
                            it.copy(
                                callSession = session,
                                callType = session.callType,
                                callStatus = if (session.status == CallStatus.CONNECTED) CallStatus.CONNECTED else CallStatus.RINGING
                            )
                        }
                        if (_uiState.value.hasMediaPermissions) {
                            maybeStartWebRtcForOutgoing(session)
                        }
                        if (session.status == CallStatus.CONNECTED) {
                            transitionToConnected()
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                errorMessage = result.exception.message ?: "Unable to start call",
                                callStatus = CallStatus.ENDED
                            )
                        }
                    }
                }
            }
        }
    }

    private fun setupIncomingCall(callId: Long, callType: CallType, conversationId: Long?) {
        observeWebRtcStates()
        val initialSession = CallSession(
            id = callId,
            channelName = "call_$callId",
            conversationId = conversationId,
            callType = callType,
            status = CallStatus.RINGING,
            caller = User(id = 0L, name = "Incoming Call...", username = ""),
            receiver = User(id = 0L, name = "You", username = ""),
            isIncoming = true
        )
        _uiState.update {
            it.copy(
                callSession = initialSession,
                callType = callType,
                callStatus = CallStatus.RINGING,
                isIncoming = true
            )
        }

        viewModelScope.launch {
            getCallDetailsUseCase(callId).collect { res ->
                if (res is Result.Success) {
                    val session = res.data.copy(isIncoming = true)
                    _uiState.update {
                        it.copy(
                            callSession = session,
                            callType = session.callType,
                            callStatus = session.status
                        )
                    }
                    runCatching {
                        webRtcManager.webRtcEngine?.handleIncomingCall(
                            callId = session.id,
                            conversationId = session.conversationId,
                            callerId = session.caller.id,
                            callerName = session.caller.name,
                            callType = session.callType.mediaCallType
                        )
                    }
                    if (session.status == CallStatus.ENDED || session.status == CallStatus.MISSED || session.status == CallStatus.REJECTED) {
                        finishLocalCall(session.status)
                    }
                }
            }
        }
    }

    private fun onMediaPermissionsGranted() {
        _uiState.update { it.copy(hasMediaPermissions = true) }
        val session = _uiState.value.callSession ?: return
        if (!_uiState.value.isIncoming) {
            maybeStartWebRtcForOutgoing(session)
        } else if (_uiState.value.callStatus == CallStatus.CONNECTED) {
            maybeStartWebRtcForAcceptedIncoming(session)
        }
    }

    private fun maybeStartWebRtcForOutgoing(session: CallSession) {
        if (webRtcStartedForCallId == session.id) return
        webRtcStartedForCallId = session.id
        runCatching {
            webRtcManager.webRtcEngine?.startOutgoingCall(
                callId = session.id,
                conversationId = session.conversationId,
                receiverId = session.receiver.id,
                receiverName = session.receiver.name,
                callType = session.callType.mediaCallType
            )
        }
    }

    private fun maybeStartWebRtcForAcceptedIncoming(session: CallSession) {
        if (webRtcStartedForCallId == session.id) return
        webRtcStartedForCallId = session.id
        runCatching {
            webRtcManager.webRtcEngine?.acceptIncomingCall(
                callId = session.id,
                conversationId = session.conversationId
            )
        }
    }

    private fun syncActiveCallState() {
        val currentSession = _uiState.value.callSession ?: return
        if (_uiState.value.isCallEnded || currentSession.id <= 0L) return

        viewModelScope.launch {
            getCallDetailsUseCase(currentSession.id).collect { res ->
                if (res is Result.Success) {
                    val updated = res.data.copy(isIncoming = _uiState.value.isIncoming)
                    val prevStatus = _uiState.value.callStatus
                    _uiState.update { state ->
                        state.copy(
                            callSession = updated,
                            callType = updated.callType
                        )
                    }
                    when (updated.status) {
                        CallStatus.CONNECTED -> {
                            if (prevStatus != CallStatus.CONNECTED) {
                                transitionToConnected()
                            }
                        }
                        CallStatus.ENDED, CallStatus.REJECTED, CallStatus.MISSED -> {
                            finishLocalCall(updated.status)
                        }
                        else -> Unit
                    }
                }
            }
        }
    }

    private fun acceptCall() {
        val callId = _uiState.value.callSession?.id ?: return
        viewModelScope.launch {
            respondCallUseCase(callId, "accept").collect { result ->
                when (result) {
                    is Result.Success -> {
                        val session = result.data.copy(isIncoming = true)
                        _uiState.update { it.copy(callSession = session, callType = session.callType) }
                        if (_uiState.value.hasMediaPermissions) {
                            maybeStartWebRtcForAcceptedIncoming(session)
                        }
                        transitionToConnected()
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Unable to accept call")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun rejectCall() {
        val session = _uiState.value.callSession
        val callId = session?.id ?: 0L
        stopTimer()
        webRtcJob?.cancel()
        webRtcJob = null
        runCatching {
            webRtcManager.webRtcEngine?.rejectIncomingCall()
            webRtcManager.signalingCoordinator?.stopSession(callId, session?.conversationId)
        }
        viewModelScope.launch {
            if (callId > 0L) {
                respondCallUseCase(callId, "reject").collect()
            }
            _uiState.update { it.copy(callStatus = CallStatus.REJECTED, isCallEnded = true) }
        }
    }

    private fun endCall() {
        val session = _uiState.value.callSession
        val callId = session?.id ?: 0L
        stopTimer()
        webRtcJob?.cancel()
        webRtcJob = null
        runCatching {
            webRtcManager.signalingCoordinator?.stopSession(callId, session?.conversationId)
            webRtcManager.endAudioSession()
        }
        viewModelScope.launch {
            if (callId > 0L) {
                leaveCallUseCase(callId).collect()
            }
            _uiState.update { it.copy(callStatus = CallStatus.ENDED, isCallEnded = true) }
        }
    }

    private fun finishLocalCall(finalStatus: CallStatus) {
        val session = _uiState.value.callSession
        stopTimer()
        webRtcJob?.cancel()
        webRtcJob = null
        runCatching {
            webRtcManager.signalingCoordinator?.stopSession(session?.id ?: 0L, session?.conversationId)
            webRtcManager.endAudioSession()
        }
        _uiState.update { it.copy(callStatus = finalStatus, isCallEnded = true) }
    }

    private fun transitionToConnected() {
        _uiState.update { it.copy(callStatus = CallStatus.CONNECTED) }
        startTimer()
    }

    private fun openInviteSheet(show: Boolean) {
        _uiState.update {
            it.copy(
                showInviteSheet = show,
                inviteSearchQuery = if (!show) "" else it.inviteSearchQuery
            )
        }
        if (show) {
            loadFriendsToInvite(null)
        }
    }

    private fun searchFriendsForInvite(query: String) {
        _uiState.update { it.copy(inviteSearchQuery = query) }
        inviteSearchJob?.cancel()
        inviteSearchJob = viewModelScope.launch {
            delay(250)
            loadFriendsToInvite(query)
        }
    }

    private fun loadFriendsToInvite(query: String?) {
        viewModelScope.launch {
            getFriendsToInviteUseCase(query).collect { res ->
                if (res is Result.Success) {
                    val activeParticipantIds = _uiState.value.participants
                        .filter { it.status == "ringing" || it.status == "connected" }
                        .map { it.user.id }
                        .toSet()
                    _uiState.update {
                        it.copy(inviteFriends = res.data.filterNot { f -> activeParticipantIds.contains(f.id) })
                    }
                }
            }
        }
    }

    private fun inviteFriends(userIds: List<Long>) {
        val callId = _uiState.value.callSession?.id ?: return
        if (userIds.isEmpty()) return
        _uiState.update { it.copy(showInviteSheet = false) }
        viewModelScope.launch {
            inviteFriendsToCallUseCase(callId, userIds).collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                callSession = res.data,
                                callType = res.data.callType,
                                statusMessage = "Invited ${userIds.size} friend(s) to call"
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = res.exception.message ?: "Failed to invite friends")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun toggleMute() {
        val isMuted = webRtcManager.toggleMute()
        _uiState.update { it.copy(isMuted = isMuted) }
        _uiState.value.callSession?.id?.takeIf { it > 0L }?.let { callId ->
            viewModelScope.launch {
                updateCallStateUseCase(callId, isMuted = isMuted, isCameraOff = null).collect()
            }
        }
    }

    private fun toggleCamera() {
        val isCameraOff = webRtcManager.toggleCamera()
        _uiState.update { it.copy(isCameraOff = isCameraOff) }
        _uiState.value.callSession?.id?.takeIf { it > 0L }?.let { callId ->
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
        val speaker = webRtcManager.toggleSpeaker()
        _uiState.update { it.copy(isSpeakerOn = speaker) }
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
        webRtcJob?.cancel()
        webRtcJob = null
        runCatching { webRtcManager.endAudioSession() }
    }
}
