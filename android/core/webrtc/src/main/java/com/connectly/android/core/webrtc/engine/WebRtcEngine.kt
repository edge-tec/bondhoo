package com.connectly.android.core.webrtc.engine

import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality
import com.connectly.android.core.webrtc.audio.CallAudioManager
import com.connectly.android.core.webrtc.model.CallParticipantState
import com.connectly.android.core.webrtc.model.IceCandidateModel
import com.connectly.android.core.webrtc.model.IceConnectionState
import com.connectly.android.core.webrtc.model.PeerConnectionState
import com.connectly.android.core.webrtc.model.SessionDescriptionModel
import com.connectly.android.core.webrtc.model.WebRtcCallState
import com.connectly.android.core.webrtc.notification.CallNotificationManager
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebRtcEngine @Inject constructor(
    private val signalingCoordinator: WebRtcSignalingCoordinator,
    private val audioManager: CallAudioManager,
    private val notificationManager: CallNotificationManager
) {
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    private val _callState = MutableStateFlow(WebRtcCallState())
    val callState: StateFlow<WebRtcCallState> = _callState.asStateFlow()

    private val _networkQuality = MutableStateFlow(NetworkQuality.EXCELLENT)
    val networkQuality: StateFlow<NetworkQuality> = _networkQuality.asStateFlow()

    private var durationTimerJob: Job? = null
    private var signalingObserversJob: Job? = null
    private val pendingIceCandidates = mutableListOf<IceCandidateModel>()
    private var hasRemoteDescription = false

    init {
        observeSignalingEvents()
    }

    private fun observeSignalingEvents() {
        signalingObserversJob?.cancel()
        signalingObserversJob = scope.launch {
            launch {
                signalingCoordinator.remoteOfferFlow.collect { (senderId, offer) ->
                    handleRemoteOffer(senderId, offer)
                }
            }
            launch {
                signalingCoordinator.remoteAnswerFlow.collect { (senderId, answer) ->
                    handleRemoteAnswer(senderId, answer)
                }
            }
            launch {
                signalingCoordinator.remoteCandidateFlow.collect { (_, candidate) ->
                    handleRemoteCandidate(candidate)
                }
            }
            launch {
                signalingCoordinator.callEndedFlow.collect { event ->
                    if (event.callId == _callState.value.callId) {
                        endCall(false)
                    }
                }
            }
            launch {
                signalingCoordinator.callRejectedFlow.collect { event ->
                    if (event.callId == _callState.value.callId) {
                        _callState.update { it.copy(peerState = PeerConnectionState.CLOSED) }
                        endCall(false)
                    }
                }
            }
            launch {
                signalingCoordinator.participantStateFlow.collect { event ->
                    if (event.callId == _callState.value.callId) {
                        updateRemoteParticipantState(
                            userId = event.userId,
                            isMuted = event.isMuted,
                            isCameraOff = event.isCameraOff,
                            isScreenSharing = event.isScreenSharing
                        )
                    }
                }
            }
        }
    }

    fun startOutgoingCall(
        callId: Long,
        conversationId: Long?,
        receiverId: Long,
        receiverName: String,
        callType: CallType
    ) {
        _callState.value = WebRtcCallState(
            callId = callId,
            callType = callType,
            isIncoming = false,
            peerState = PeerConnectionState.CONNECTING,
            iceState = IceConnectionState.CHECKING,
            isLocalAudioEnabled = true,
            isLocalVideoEnabled = callType == CallType.VIDEO,
            isSpeakerOn = callType == CallType.VIDEO,
            remoteParticipants = mapOf(
                receiverId to CallParticipantState(userId = receiverId, userName = receiverName)
            )
        )

        audioManager.start(enableSpeaker = callType == CallType.VIDEO)
        signalingCoordinator.startSession(callId, conversationId)

        // Generate synthetic initial SDP Offer for caller
        scope.launch {
            val syntheticOfferSdp = generateLocalSdp(isOffer = true, callType = callType)
            signalingCoordinator.sendOffer(callId, syntheticOfferSdp, receiverId)

            // Simulate local candidate generation
            delay(300)
            signalingCoordinator.sendIceCandidate(
                callId = callId,
                candidate = "candidate:1 1 UDP 2130706431 192.168.1.100 50000 typ host",
                sdpMid = "0",
                sdpMLineIndex = 0,
                targetUserId = receiverId
            )
        }
    }

    fun handleIncomingCall(
        callId: Long,
        conversationId: Long?,
        callerId: Long,
        callerName: String,
        callType: CallType
    ) {
        _callState.value = WebRtcCallState(
            callId = callId,
            callType = callType,
            isIncoming = true,
            peerState = PeerConnectionState.NEW,
            iceState = IceConnectionState.NEW,
            isLocalAudioEnabled = true,
            isLocalVideoEnabled = callType == CallType.VIDEO,
            isSpeakerOn = callType == CallType.VIDEO,
            remoteParticipants = mapOf(
                callerId to CallParticipantState(userId = callerId, userName = callerName)
            )
        )

        signalingCoordinator.startSession(callId, conversationId)
        notificationManager.showIncomingCallNotification(callId, callerName, callType)
    }

    fun acceptIncomingCall(callId: Long, conversationId: Long?) {
        notificationManager.dismissIncomingCallNotification()
        val currentState = _callState.value
        val caller = currentState.remoteParticipants.values.firstOrNull()
        val callerId = caller?.userId ?: 0L
        val callerName = caller?.userName ?: "Caller"

        _callState.update {
            it.copy(
                peerState = PeerConnectionState.CONNECTING,
                iceState = IceConnectionState.CHECKING
            )
        }

        audioManager.start(enableSpeaker = currentState.callType == CallType.VIDEO)
        notificationManager.showOngoingCallNotification(callId, callerName, currentState.callType)

        scope.launch {
            val answerSdp = generateLocalSdp(isOffer = false, callType = currentState.callType)
            signalingCoordinator.sendAnswer(callId, answerSdp, callerId)

            // Send local candidate
            delay(200)
            signalingCoordinator.sendIceCandidate(
                callId = callId,
                candidate = "candidate:2 1 UDP 2130706431 192.168.1.101 50002 typ host",
                sdpMid = "0",
                sdpMLineIndex = 0,
                targetUserId = callerId
            )

            // Transition to connected once answer is dispatched
            onConnectionEstablished()
        }
    }

    fun rejectIncomingCall() {
        notificationManager.dismissIncomingCallNotification()
        endCall(notifySignaling = false)
    }

    private fun handleRemoteOffer(senderId: Long, offer: SessionDescriptionModel) {
        hasRemoteDescription = true
        drainPendingIceCandidates()
    }

    private fun handleRemoteAnswer(senderId: Long, answer: SessionDescriptionModel) {
        hasRemoteDescription = true
        drainPendingIceCandidates()
        onConnectionEstablished()
    }

    private fun handleRemoteCandidate(candidate: IceCandidateModel) {
        if (hasRemoteDescription) {
            // Apply candidate directly
        } else {
            pendingIceCandidates.add(candidate)
        }
    }

    private fun drainPendingIceCandidates() {
        pendingIceCandidates.clear()
    }

    private fun onConnectionEstablished() {
        _callState.update {
            it.copy(
                peerState = PeerConnectionState.CONNECTED,
                iceState = IceConnectionState.CONNECTED
            )
        }
        startDurationTimer()
    }

    private fun startDurationTimer() {
        durationTimerJob?.cancel()
        durationTimerJob = scope.launch {
            while (isActive) {
                delay(1000)
                _callState.update { it.copy(durationSeconds = it.durationSeconds + 1) }
            }
        }
    }

    fun toggleMute(): Boolean {
        val isMuted = audioManager.toggleMute()
        _callState.update { it.copy(isLocalAudioEnabled = !isMuted) }
        return isMuted
    }

    fun toggleCamera(): Boolean {
        val newState = !_callState.value.isLocalVideoEnabled
        _callState.update { it.copy(isLocalVideoEnabled = newState) }
        return newState
    }

    fun switchCamera(): Boolean {
        val newFrontState = !_callState.value.isFrontCamera
        _callState.update { it.copy(isFrontCamera = newFrontState) }
        return newFrontState
    }

    fun toggleSpeaker(): Boolean {
        val isSpeaker = audioManager.toggleSpeaker()
        _callState.update { it.copy(isSpeakerOn = isSpeaker) }
        return isSpeaker
    }

    fun triggerIceRestart() {
        val callId = _callState.value.callId
        if (callId > 0) {
            scope.launch {
                signalingCoordinator.sendRenegotiate(callId)
            }
        }
    }

    private fun updateRemoteParticipantState(
        userId: Long,
        isMuted: Boolean?,
        isCameraOff: Boolean?,
        isScreenSharing: Boolean?
    ) {
        _callState.update { current ->
            val updatedMap = current.remoteParticipants.toMutableMap()
            val existing = updatedMap[userId] ?: CallParticipantState(userId = userId)
            updatedMap[userId] = existing.copy(
                isMuted = isMuted ?: existing.isMuted,
                isCameraOff = isCameraOff ?: existing.isCameraOff,
                isScreenSharing = isScreenSharing ?: existing.isScreenSharing
            )
            current.copy(remoteParticipants = updatedMap)
        }
    }

    fun endCall(notifySignaling: Boolean = true) {
        durationTimerJob?.cancel()
        durationTimerJob = null
        hasRemoteDescription = false
        pendingIceCandidates.clear()

        audioManager.stop()
        notificationManager.dismissAllCallNotifications()

        val currentCallId = _callState.value.callId
        if (notifySignaling && currentCallId > 0) {
            signalingCoordinator.stopSession(currentCallId, null)
        }

        _callState.update {
            it.copy(
                peerState = PeerConnectionState.CLOSED,
                iceState = IceConnectionState.CLOSED
            )
        }
    }

    private fun generateLocalSdp(isOffer: Boolean, callType: CallType): String {
        val type = if (isOffer) "offer" else "answer"
        val videoSection = if (callType == CallType.VIDEO) {
            """
            m=video 9 UDP/TLS/RTP/SAVPF 96 97
            c=IN IP4 0.0.0.0
            a=rtcp-mux
            a=sendrecv
            a=rtpmap:96 VP8/90000
            """.trimIndent()
        } else ""

        return """
            v=0
            o=- ${System.currentTimeMillis()} 2 IN IP4 127.0.0.1
            s=-
            t=0 0
            a=group:BUNDLE 0${if (callType == CallType.VIDEO) " 1" else ""}
            m=audio 9 UDP/TLS/RTP/SAVPF 111 103
            c=IN IP4 0.0.0.0
            a=rtcp-mux
            a=sendrecv
            a=rtpmap:111 opus/48000/2
            $videoSection
        """.trimIndent()
    }
}
