package com.connectly.android.core.webrtc.session

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.model.CallType
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.core.webrtc.model.WebRtcCallState
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import com.connectly.android.core.websocket.unified.NetworkConnectivityMonitor
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import org.webrtc.EglBase
import org.webrtc.VideoTrack
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class CallSessionManager @Inject constructor(
    private val webRtcEngine: WebRtcEngine,
    private val signalingCoordinator: WebRtcSignalingCoordinator,
    private val connectivityMonitor: NetworkConnectivityMonitor,
    @Dispatcher(ConnectlyDispatchers.Default) defaultDispatcher: CoroutineDispatcher = Dispatchers.Default
) {
    private val scope = CoroutineScope(SupervisorJob() + defaultDispatcher)

    val localVideoTrack: StateFlow<VideoTrack?> = webRtcEngine.localVideoTrack
    val remoteVideoTrack: StateFlow<VideoTrack?> = webRtcEngine.remoteVideoTrack
    val eglBaseContext: EglBase.Context get() = webRtcEngine.eglBaseContext
    val callState: StateFlow<WebRtcCallState> = webRtcEngine.callState

    init {
        observeNetworkChanges()
    }

    private fun observeNetworkChanges() {
        scope.launch {
            connectivityMonitor.isConnected.collect { connected ->
                if (connected && webRtcEngine.callState.value.callId > 0) {
                    // Trigger ICE restart on network recovery / WiFi to cellular handover
                    webRtcEngine.triggerIceRestart()
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
        webRtcEngine.startOutgoingCall(
            callId = callId,
            conversationId = conversationId,
            receiverId = receiverId,
            receiverName = receiverName,
            callType = callType
        )
    }

    fun handleIncomingCall(
        callId: Long,
        conversationId: Long?,
        callerId: Long,
        callerName: String,
        callType: CallType
    ) {
        webRtcEngine.handleIncomingCall(
            callId = callId,
            conversationId = conversationId,
            callerId = callerId,
            callerName = callerName,
            callType = callType
        )
    }

    fun acceptCall(callId: Long, conversationId: Long?) {
        webRtcEngine.acceptIncomingCall(callId, conversationId)
    }

    fun rejectCall() {
        webRtcEngine.rejectIncomingCall()
    }

    fun endCall() {
        webRtcEngine.endCall(notifySignaling = true)
    }

    fun toggleMute(): Boolean = webRtcEngine.toggleMute()
    fun toggleCamera(): Boolean = webRtcEngine.toggleCamera()
    fun switchCamera(): Boolean = webRtcEngine.switchCamera()
    fun toggleSpeaker(): Boolean = webRtcEngine.toggleSpeaker()
}

