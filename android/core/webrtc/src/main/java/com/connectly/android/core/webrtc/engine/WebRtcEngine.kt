package com.connectly.android.core.webrtc.engine

import android.content.Context
import android.util.Log
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.webrtc.audio.CallAudioManager
import com.connectly.android.core.webrtc.model.CallParticipantState
import com.connectly.android.core.webrtc.model.IceCandidateModel
import com.connectly.android.core.webrtc.model.IceConnectionState
import com.connectly.android.core.webrtc.model.PeerConnectionState
import com.connectly.android.core.webrtc.model.SessionDescriptionModel
import com.connectly.android.core.webrtc.model.WebRtcCallState
import com.connectly.android.core.webrtc.notification.CallNotificationManager
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import dagger.hilt.android.qualifiers.ApplicationContext
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
import org.webrtc.AudioSource
import org.webrtc.AudioTrack
import org.webrtc.Camera2Enumerator
import org.webrtc.CameraVideoCapturer
import org.webrtc.DataChannel
import org.webrtc.DefaultVideoDecoderFactory
import org.webrtc.DefaultVideoEncoderFactory
import org.webrtc.EglBase
import org.webrtc.IceCandidate
import org.webrtc.MediaConstraints
import org.webrtc.MediaStream
import org.webrtc.PeerConnection
import org.webrtc.PeerConnectionFactory
import org.webrtc.RtpReceiver
import org.webrtc.RtpTransceiver
import org.webrtc.SdpObserver
import org.webrtc.SessionDescription
import org.webrtc.SurfaceTextureHelper
import org.webrtc.VideoCapturer
import org.webrtc.VideoSource
import org.webrtc.VideoTrack
import java.util.concurrent.CopyOnWriteArrayList
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebRtcEngine @Inject constructor(
    @ApplicationContext private val context: Context,
    private val signalingCoordinator: WebRtcSignalingCoordinator,
    private val audioManager: CallAudioManager,
    private val notificationManager: CallNotificationManager,
    private val api: ConnectlyApi
) {
    private val TAG = "WebRtcEngine"
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    // Hardware OpenGL ES EglBase Context
    val eglBase: EglBase = EglBase.create()
    val eglBaseContext: EglBase.Context get() = eglBase.eglBaseContext

    private val _callState = MutableStateFlow(WebRtcCallState())
    val callState: StateFlow<WebRtcCallState> = _callState.asStateFlow()

    private val _networkQuality = MutableStateFlow(NetworkQuality.EXCELLENT)
    val networkQuality: StateFlow<NetworkQuality> = _networkQuality.asStateFlow()

    private val _localVideoTrack = MutableStateFlow<VideoTrack?>(null)
    val localVideoTrack: StateFlow<VideoTrack?> = _localVideoTrack.asStateFlow()

    private val _remoteVideoTrack = MutableStateFlow<VideoTrack?>(null)
    val remoteVideoTrack: StateFlow<VideoTrack?> = _remoteVideoTrack.asStateFlow()

    // Native WebRTC PeerConnectionFactory & PeerConnection
    private var peerConnectionFactory: PeerConnectionFactory? = null
    private var peerConnection: PeerConnection? = null

    // Native Media Components
    private var audioSource: AudioSource? = null
    private var localAudioTrack: AudioTrack? = null
    private var videoSource: VideoSource? = null
    private var surfaceTextureHelper: SurfaceTextureHelper? = null
    private var videoCapturer: VideoCapturer? = null

    // ICE Candidate Buffering
    private val pendingIceCandidates = CopyOnWriteArrayList<IceCandidate>()
    @Volatile private var hasRemoteDescription = false
    private var remoteOfferSdp: SessionDescription? = null

    private var durationTimerJob: Job? = null
    private var signalingObserversJob: Job? = null

    init {
        initializePeerConnectionFactory()
        observeSignalingEvents()
    }

    private fun initializePeerConnectionFactory() {
        try {
            val initOptions = PeerConnectionFactory.InitializationOptions.builder(context)
                .setEnableInternalTracer(false)
                .createInitializationOptions()
            PeerConnectionFactory.initialize(initOptions)

            val encoderFactory = DefaultVideoEncoderFactory(eglBase.eglBaseContext, true, true)
            val decoderFactory = DefaultVideoDecoderFactory(eglBase.eglBaseContext)

            peerConnectionFactory = PeerConnectionFactory.builder()
                .setVideoEncoderFactory(encoderFactory)
                .setVideoDecoderFactory(decoderFactory)
                .setOptions(PeerConnectionFactory.Options())
                .createPeerConnectionFactory()

            Log.i(TAG, "Native PeerConnectionFactory initialized successfully.")
        } catch (e: Exception) {
            Log.e(TAG, "Failed to initialize native PeerConnectionFactory", e)
        }
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

        scope.launch {
            val iceServers = fetchIceServers()
            setupMediaTracks(callType)
            createPeerConnection(iceServers, callId, receiverId)
            createAndSendOffer(callId, receiverId, callType)
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
            val iceServers = fetchIceServers()
            setupMediaTracks(currentState.callType)
            createPeerConnection(iceServers, callId, callerId)

            // If remote offer arrived before acceptance, set it now and answer
            remoteOfferSdp?.let { offer ->
                setRemoteDescriptionAndAnswer(callId, callerId, offer, currentState.callType)
            }
        }
    }

    fun rejectIncomingCall() {
        notificationManager.dismissIncomingCallNotification()
        endCall(notifySignaling = false)
    }

    private suspend fun fetchIceServers(): List<PeerConnection.IceServer> {
        val fallback = listOf(
            PeerConnection.IceServer.builder("stun:stun.l.google.com:19302").createIceServer(),
            PeerConnection.IceServer.builder("stun:stun1.l.google.com:19302").createIceServer(),
            PeerConnection.IceServer.builder("turn:openrelay.metered.ca:80")
                .setUsername("openrelayproject")
                .setPassword("openrelayproject")
                .createIceServer(),
            PeerConnection.IceServer.builder("turn:openrelay.metered.ca:443?transport=tcp")
                .setUsername("openrelayproject")
                .setPassword("openrelayproject")
                .createIceServer()
        )

        return try {
            val response = api.getIceServers()
            if (response.isSuccessful && response.body()?.data?.ice_servers != null) {
                val dtoList = response.body()!!.data.ice_servers
                if (dtoList.isNotEmpty()) {
                    dtoList.mapNotNull { dto ->
                        if (dto.urls.isNotEmpty()) {
                            val builder = PeerConnection.IceServer.builder(dto.urls)
                            if (!dto.username.isNullOrEmpty()) builder.setUsername(dto.username)
                            if (!dto.credential.isNullOrEmpty()) builder.setPassword(dto.credential)
                            builder.createIceServer()
                        } else null
                    }
                } else fallback
            } else fallback
        } catch (e: Exception) {
            Log.w(TAG, "Using fallback STUN/TURN servers due to API error: ${e.message}")
            fallback
        }
    }

    private fun setupMediaTracks(callType: CallType) {
        val factory = peerConnectionFactory ?: return

        // 1. Audio Track
        val audioConstraints = MediaConstraints().apply {
            mandatory.add(MediaConstraints.KeyValuePair("googEchoCancellation", "true"))
            mandatory.add(MediaConstraints.KeyValuePair("googAutoGainControl", "true"))
            mandatory.add(MediaConstraints.KeyValuePair("googHighpassFilter", "true"))
            mandatory.add(MediaConstraints.KeyValuePair("googNoiseSuppression", "true"))
        }
        audioSource = factory.createAudioSource(audioConstraints)
        localAudioTrack = factory.createAudioTrack("ARDAMSa0", audioSource)
        localAudioTrack?.setEnabled(true)

        // 2. Video Track (if VIDEO call)
        if (callType == CallType.VIDEO) {
            try {
                surfaceTextureHelper = SurfaceTextureHelper.create("CaptureThread", eglBase.eglBaseContext)
                val enumerator = Camera2Enumerator(context)
                val deviceName = enumerator.deviceNames.firstOrNull { enumerator.isFrontFacing(it) }
                    ?: enumerator.deviceNames.firstOrNull()

                if (deviceName != null) {
                    val capturer = enumerator.createCapturer(deviceName, null)
                    videoCapturer = capturer
                    videoSource = factory.createVideoSource(false)
                    capturer.initialize(surfaceTextureHelper, context, videoSource?.capturerObserver)
                    capturer.startCapture(1280, 720, 30)

                    val videoTrack = factory.createVideoTrack("ARDAMSv0", videoSource)
                    videoTrack.setEnabled(true)
                    _localVideoTrack.value = videoTrack
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error starting camera capturer", e)
            }
        }
    }

    /**
     * Start local camera and audio media for Live Streaming broadcast.
     */
    fun startBroadcastMedia() {
        if (peerConnectionFactory == null) {
            initializePeerConnectionFactory()
        }
        setupMediaTracks(CallType.VIDEO)
    }

    /**
     * Stop and dispose broadcast camera and audio resources.
     */
    fun stopBroadcastMedia() {
        try { videoCapturer?.stopCapture() } catch (_: Exception) {}
        try { videoCapturer?.dispose() } catch (_: Exception) {}
        videoCapturer = null

        try { surfaceTextureHelper?.dispose() } catch (_: Exception) {}
        surfaceTextureHelper = null

        try { _localVideoTrack.value?.dispose() } catch (_: Exception) {}
        _localVideoTrack.value = null

        try { videoSource?.dispose() } catch (_: Exception) {}
        videoSource = null

        try { localAudioTrack?.dispose() } catch (_: Exception) {}
        localAudioTrack = null

        try { audioSource?.dispose() } catch (_: Exception) {}
        audioSource = null
    }

    private fun createPeerConnection(
        iceServers: List<PeerConnection.IceServer>,
        callId: Long,
        targetUserId: Long
    ) {
        val factory = peerConnectionFactory ?: return

        val rtcConfig = PeerConnection.RTCConfiguration(iceServers).apply {
            tcpCandidatePolicy = PeerConnection.TcpCandidatePolicy.ENABLED
            bundlePolicy = PeerConnection.BundlePolicy.MAXBUNDLE
            rtcpMuxPolicy = PeerConnection.RtcpMuxPolicy.REQUIRE
            continualGatheringPolicy = PeerConnection.ContinualGatheringPolicy.GATHER_CONTINUALLY
            keyType = PeerConnection.KeyType.ECDSA
            sdpSemantics = PeerConnection.SdpSemantics.UNIFIED_PLAN
        }

        peerConnection = factory.createPeerConnection(rtcConfig, object : PeerConnection.Observer {
            override fun onSignalingChange(state: PeerConnection.SignalingState) {
                Log.d(TAG, "SignalingState changed: $state")
            }

            override fun onIceConnectionChange(state: PeerConnection.IceConnectionState) {
                Log.i(TAG, "IceConnectionState changed: $state")
                when (state) {
                    PeerConnection.IceConnectionState.CHECKING -> {
                        _callState.update { it.copy(iceState = IceConnectionState.CHECKING) }
                    }
                    PeerConnection.IceConnectionState.CONNECTED -> {
                        _callState.update {
                            it.copy(
                                peerState = PeerConnectionState.CONNECTED,
                                iceState = IceConnectionState.CONNECTED
                            )
                        }
                        onConnectionEstablished()
                    }
                    PeerConnection.IceConnectionState.COMPLETED -> {
                        _callState.update { it.copy(iceState = IceConnectionState.COMPLETED) }
                    }
                    PeerConnection.IceConnectionState.FAILED -> {
                        _callState.update {
                            it.copy(
                                peerState = PeerConnectionState.FAILED,
                                iceState = IceConnectionState.FAILED
                            )
                        }
                        triggerIceRestart()
                    }
                    PeerConnection.IceConnectionState.DISCONNECTED -> {
                        _callState.update {
                            it.copy(
                                peerState = PeerConnectionState.DISCONNECTED,
                                iceState = IceConnectionState.DISCONNECTED
                            )
                        }
                    }
                    PeerConnection.IceConnectionState.CLOSED -> {
                        _callState.update {
                            it.copy(
                                peerState = PeerConnectionState.CLOSED,
                                iceState = IceConnectionState.CLOSED
                            )
                        }
                    }
                    else -> {}
                }
            }

            override fun onIceConnectionReceivingChange(receiving: Boolean) {
                Log.d(TAG, "IceConnectionReceivingChange: $receiving")
            }

            override fun onIceGatheringChange(state: PeerConnection.IceGatheringState) {
                Log.d(TAG, "IceGatheringState changed: $state")
            }

            override fun onIceCandidate(candidate: IceCandidate) {
                Log.d(TAG, "Local IceCandidate gathered: ${candidate.sdp}")
                scope.launch {
                    signalingCoordinator.sendIceCandidate(
                        callId = callId,
                        candidate = candidate.sdp,
                        sdpMid = candidate.sdpMid,
                        sdpMLineIndex = candidate.sdpMLineIndex,
                        targetUserId = targetUserId
                    )
                }
            }

            override fun onIceCandidatesRemoved(candidates: Array<out IceCandidate>) {
                Log.d(TAG, "IceCandidates removed: ${candidates.size}")
            }

            override fun onAddStream(stream: MediaStream) {
                Log.i(TAG, "onAddStream: audio=${stream.audioTracks.size}, video=${stream.videoTracks.size}")
                if (stream.videoTracks.isNotEmpty()) {
                    _remoteVideoTrack.value = stream.videoTracks[0]
                }
            }

            override fun onRemoveStream(stream: MediaStream) {
                Log.i(TAG, "onRemoveStream")
                _remoteVideoTrack.value = null
            }

            override fun onDataChannel(dc: DataChannel) {}

            override fun onRenegotiationNeeded() {
                Log.d(TAG, "onRenegotiationNeeded")
            }

            override fun onAddTrack(receiver: RtpReceiver, mediaStreams: Array<out MediaStream>) {
                Log.i(TAG, "onAddTrack")
                val track = receiver.track()
                if (track is VideoTrack) {
                    _remoteVideoTrack.value = track
                }
            }

            override fun onTrack(transceiver: RtpTransceiver) {
                val track = transceiver.receiver.track()
                if (track is VideoTrack) {
                    _remoteVideoTrack.value = track
                }
            }
        })

        // Attach local tracks
        localAudioTrack?.let { audioTrack ->
            peerConnection?.addTrack(audioTrack, listOf("ARDAMS"))
        }
        _localVideoTrack.value?.let { videoTrack ->
            peerConnection?.addTrack(videoTrack, listOf("ARDAMS"))
        }
    }

    private fun createAndSendOffer(callId: Long, receiverId: Long, callType: CallType) {
        val constraints = MediaConstraints().apply {
            mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveAudio", "true"))
            if (callType == CallType.VIDEO) {
                mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveVideo", "true"))
            }
        }

        peerConnection?.createOffer(object : SimpleSdpObserver() {
            override fun onCreateSuccess(desc: SessionDescription) {
                peerConnection?.setLocalDescription(object : SimpleSdpObserver() {
                    override fun onSetSuccess() {
                        Log.i(TAG, "Local SDP offer set successfully. Sending to signaling coordinator.")
                        scope.launch {
                            signalingCoordinator.sendOffer(callId, desc.description, receiverId)
                        }
                    }
                }, desc)
            }

            override fun onCreateFailure(error: String) {
                Log.e(TAG, "Failed to create SDP offer: $error")
            }
        }, constraints)
    }

    private fun handleRemoteOffer(senderId: Long, offer: SessionDescriptionModel) {
        Log.i(TAG, "handleRemoteOffer from $senderId")
        val sdp = SessionDescription(SessionDescription.Type.OFFER, offer.sdp)
        remoteOfferSdp = sdp

        if (peerConnection != null) {
            setRemoteDescriptionAndAnswer(_callState.value.callId, senderId, sdp, _callState.value.callType)
        }
    }

    private fun setRemoteDescriptionAndAnswer(
        callId: Long,
        callerId: Long,
        offer: SessionDescription,
        callType: CallType
    ) {
        peerConnection?.setRemoteDescription(object : SimpleSdpObserver() {
            override fun onSetSuccess() {
                Log.i(TAG, "Remote description set successfully (OFFER). Draining candidates.")
                hasRemoteDescription = true
                drainPendingIceCandidates()

                val constraints = MediaConstraints().apply {
                    mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveAudio", "true"))
                    if (callType == CallType.VIDEO) {
                        mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveVideo", "true"))
                    }
                }

                peerConnection?.createAnswer(object : SimpleSdpObserver() {
                    override fun onCreateSuccess(answerDesc: SessionDescription) {
                        peerConnection?.setLocalDescription(object : SimpleSdpObserver() {
                            override fun onSetSuccess() {
                                Log.i(TAG, "Local answer set successfully. Sending to signaling coordinator.")
                                scope.launch {
                                    signalingCoordinator.sendAnswer(callId, answerDesc.description, callerId)
                                }
                            }
                        }, answerDesc)
                    }

                    override fun onCreateFailure(error: String) {
                        Log.e(TAG, "Failed to create SDP answer: $error")
                    }
                }, constraints)
            }

            override fun onSetFailure(error: String) {
                Log.e(TAG, "Failed to set remote description: $error")
            }
        }, offer)
    }

    private fun handleRemoteAnswer(senderId: Long, answer: SessionDescriptionModel) {
        Log.i(TAG, "handleRemoteAnswer from $senderId")
        val sdp = SessionDescription(SessionDescription.Type.ANSWER, answer.sdp)
        peerConnection?.setRemoteDescription(object : SimpleSdpObserver() {
            override fun onSetSuccess() {
                Log.i(TAG, "Remote description set successfully (ANSWER). Draining candidates.")
                hasRemoteDescription = true
                drainPendingIceCandidates()
                onConnectionEstablished()
            }

            override fun onSetFailure(error: String) {
                Log.e(TAG, "Failed to set remote description answer: $error")
            }
        }, sdp)
    }

    private fun handleRemoteCandidate(candidateModel: IceCandidateModel) {
        val rtcCandidate = IceCandidate(
            candidateModel.sdpMid,
            candidateModel.sdpMLineIndex,
            candidateModel.candidate
        )

        if (hasRemoteDescription && peerConnection != null) {
            peerConnection?.addIceCandidate(rtcCandidate)
        } else {
            pendingIceCandidates.add(rtcCandidate)
            Log.d(TAG, "Buffered remote ICE candidate (count=${pendingIceCandidates.size})")
        }
    }

    private fun drainPendingIceCandidates() {
        val candidates = pendingIceCandidates.toList()
        pendingIceCandidates.clear()
        candidates.forEach { candidate ->
            peerConnection?.addIceCandidate(candidate)
        }
        Log.d(TAG, "Drained ${candidates.size} remote ICE candidates.")
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
        localAudioTrack?.setEnabled(!isMuted)
        _callState.update { it.copy(isLocalAudioEnabled = !isMuted) }
        return isMuted
    }

    fun toggleCamera(): Boolean {
        val newState = !_callState.value.isLocalVideoEnabled
        _localVideoTrack.value?.setEnabled(newState)
        if (newState) {
            try {
                videoCapturer?.startCapture(1280, 720, 30)
            } catch (e: Exception) {
                Log.e(TAG, "Failed to start capture", e)
            }
        } else {
            try {
                videoCapturer?.stopCapture()
            } catch (e: Exception) {
                Log.e(TAG, "Failed to stop capture", e)
            }
        }
        _callState.update { it.copy(isLocalVideoEnabled = newState) }
        return newState
    }

    fun switchCamera(): Boolean {
        val capturer = videoCapturer
        val newFrontState = !_callState.value.isFrontCamera
        if (capturer is CameraVideoCapturer) {
            capturer.switchCamera(object : CameraVideoCapturer.CameraSwitchHandler {
                override fun onCameraSwitchDone(isFrontCamera: Boolean) {
                    _callState.update { it.copy(isFrontCamera = isFrontCamera) }
                    Log.i(TAG, "Camera switched. IsFrontCamera: $isFrontCamera")
                }

                override fun onCameraSwitchError(errorDescription: String?) {
                    Log.e(TAG, "Failed to switch camera: $errorDescription")
                }
            })
        }
        return newFrontState
    }

    fun toggleSpeaker(): Boolean {
        val isSpeaker = audioManager.toggleSpeaker()
        _callState.update { it.copy(isSpeakerOn = isSpeaker) }
        return isSpeaker
    }

    fun triggerIceRestart() {
        val callId = _callState.value.callId
        if (callId > 0 && peerConnection != null) {
            Log.i(TAG, "Initiating ICE Restart for call: $callId")
            val restartConstraints = MediaConstraints().apply {
                mandatory.add(MediaConstraints.KeyValuePair("IceRestart", "true"))
                mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveAudio", "true"))
                if (_callState.value.callType == CallType.VIDEO) {
                    mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveVideo", "true"))
                }
            }

            peerConnection?.createOffer(object : SimpleSdpObserver() {
                override fun onCreateSuccess(desc: SessionDescription) {
                    peerConnection?.setLocalDescription(object : SimpleSdpObserver() {
                        override fun onSetSuccess() {
                            Log.i(TAG, "ICE restart local offer set. Transmitting.")
                            scope.launch {
                                val targetUserId = _callState.value.remoteParticipants.keys.firstOrNull()
                                signalingCoordinator.sendOffer(callId, desc.description, targetUserId)
                            }
                        }
                    }, desc)
                }
            }, restartConstraints)
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
        remoteOfferSdp = null
        pendingIceCandidates.clear()

        audioManager.stop()
        notificationManager.dismissAllCallNotifications()

        val currentCallId = _callState.value.callId
        if (notifySignaling && currentCallId > 0) {
            signalingCoordinator.stopSession(currentCallId, null)
        }

        // Native resource cleanup
        try { videoCapturer?.stopCapture() } catch (_: Exception) {}
        try { videoCapturer?.dispose() } catch (_: Exception) {}
        videoCapturer = null

        try { surfaceTextureHelper?.dispose() } catch (_: Exception) {}
        surfaceTextureHelper = null

        try { _localVideoTrack.value?.dispose() } catch (_: Exception) {}
        _localVideoTrack.value = null

        try { videoSource?.dispose() } catch (_: Exception) {}
        videoSource = null

        try { localAudioTrack?.dispose() } catch (_: Exception) {}
        localAudioTrack = null

        try { audioSource?.dispose() } catch (_: Exception) {}
        audioSource = null

        try { peerConnection?.close() } catch (_: Exception) {}
        try { peerConnection?.dispose() } catch (_: Exception) {}
        peerConnection = null

        _remoteVideoTrack.value = null

        _callState.update {
            it.copy(
                peerState = PeerConnectionState.CLOSED,
                iceState = IceConnectionState.CLOSED
            )
        }

        Log.i(TAG, "Native WebRTC call ended and resources cleaned up.")
    }
}

/**
 * Reusable abstract implementation of WebRTC SdpObserver with default no-op callbacks.
 */
open class SimpleSdpObserver : SdpObserver {
    override fun onCreateSuccess(desc: SessionDescription) {}
    override fun onSetSuccess() {}
    override fun onCreateFailure(error: String) {}
    override fun onSetFailure(error: String) {}
}
