package com.connectly.android.core.webrtc.signaling

import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.CallSignalRequest
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.webrtc.model.IceCandidateModel
import com.connectly.android.core.webrtc.model.SessionDescriptionModel
import com.connectly.android.core.webrtc.model.SignalingConnectionState
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.core.websocket.model.CallAcceptedEvent
import com.connectly.android.core.websocket.model.CallEndedEvent
import com.connectly.android.core.websocket.model.CallIncomingEvent
import com.connectly.android.core.websocket.model.CallParticipantStateEvent
import com.connectly.android.core.websocket.model.CallRejectedEvent
import com.connectly.android.core.websocket.model.CallSignalEvent
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.longOrNull
import java.util.Collections
import java.util.concurrent.ConcurrentHashMap
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebRtcSignalingCoordinator @Inject constructor(
    private val api: ConnectlyApi,
    private val webSocketManager: ReverbWebSocketManager,
    private val tokenStorage: TokenStorageManager
) {
    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private var pollingJob: Job? = null

    private val _signalingState = MutableStateFlow(SignalingConnectionState.DISCONNECTED)
    val signalingState: StateFlow<SignalingConnectionState> = _signalingState.asStateFlow()

    private val _remoteOfferFlow = MutableSharedFlow<Pair<Long, SessionDescriptionModel>>(extraBufferCapacity = 16)
    val remoteOfferFlow: SharedFlow<Pair<Long, SessionDescriptionModel>> = _remoteOfferFlow.asSharedFlow()

    private val _remoteAnswerFlow = MutableSharedFlow<Pair<Long, SessionDescriptionModel>>(extraBufferCapacity = 16)
    val remoteAnswerFlow: SharedFlow<Pair<Long, SessionDescriptionModel>> = _remoteAnswerFlow.asSharedFlow()

    private val _remoteCandidateFlow = MutableSharedFlow<Pair<Long, IceCandidateModel>>(extraBufferCapacity = 64)
    val remoteCandidateFlow: SharedFlow<Pair<Long, IceCandidateModel>> = _remoteCandidateFlow.asSharedFlow()

    private val _incomingCallFlow = MutableSharedFlow<CallIncomingEvent>(extraBufferCapacity = 16)
    val incomingCallFlow: SharedFlow<CallIncomingEvent> = _incomingCallFlow.asSharedFlow()

    private val _callAcceptedFlow = MutableSharedFlow<CallAcceptedEvent>(extraBufferCapacity = 16)
    val callAcceptedFlow: SharedFlow<CallAcceptedEvent> = _callAcceptedFlow.asSharedFlow()

    private val _callRejectedFlow = MutableSharedFlow<CallRejectedEvent>(extraBufferCapacity = 16)
    val callRejectedFlow: SharedFlow<CallRejectedEvent> = _callRejectedFlow.asSharedFlow()

    private val _callEndedFlow = MutableSharedFlow<CallEndedEvent>(extraBufferCapacity = 16)
    val callEndedFlow: SharedFlow<CallEndedEvent> = _callEndedFlow.asSharedFlow()

    private val _reconnectRequestFlow = MutableSharedFlow<Long>(extraBufferCapacity = 8)
    val reconnectRequestFlow: SharedFlow<Long> = _reconnectRequestFlow.asSharedFlow()

    private val _participantStateFlow = MutableSharedFlow<CallParticipantStateEvent>(extraBufferCapacity = 32)
    val participantStateFlow: SharedFlow<CallParticipantStateEvent> = _participantStateFlow.asSharedFlow()

    // Deduplication cache for signals (LRU Set)
    private val seenSignalKeys = Collections.newSetFromMap(
        object : java.util.LinkedHashMap<String, Boolean>(500, 0.75f, true) {
            override fun removeEldestEntry(eldest: MutableMap.MutableEntry<String, Boolean>?): Boolean {
                return size > 500
            }
        }
    )

    init {
        observeWebSocketEvents()
    }

    private fun observeWebSocketEvents() {
        scope.launch {
            webSocketManager.callIncomingFlow.collect { event ->
                _incomingCallFlow.emit(event)
            }
        }

        scope.launch {
            webSocketManager.callAcceptedFlow.collect { event ->
                _callAcceptedFlow.emit(event)
            }
        }

        scope.launch {
            webSocketManager.callRejectedFlow.collect { event ->
                _callRejectedFlow.emit(event)
            }
        }

        scope.launch {
            webSocketManager.callEndedFlow.collect { event ->
                _callEndedFlow.emit(event)
            }
        }

        scope.launch {
            webSocketManager.callParticipantStateFlow.collect { event ->
                _participantStateFlow.emit(event)
            }
        }

        scope.launch {
            webSocketManager.callSignalFlow.collect { signal ->
                processSignal(signal)
            }
        }
    }

    fun startSession(callId: Long, conversationId: Long?) {
        _signalingState.value = SignalingConnectionState.CONNECTED
        if (conversationId != null && conversationId > 0) {
            webSocketManager.subscribeConversation(conversationId)
        }
        val currentUserId = tokenStorage.getUserId()
        if (currentUserId != null && currentUserId > 0) {
            webSocketManager.subscribeUserChannel(currentUserId)
        }
        startPollingFallback(callId)
    }

    fun stopSession(callId: Long, conversationId: Long?) {
        pollingJob?.cancel()
        pollingJob = null
        if (conversationId != null && conversationId > 0) {
            webSocketManager.unsubscribeConversation(conversationId)
        }
        _signalingState.value = SignalingConnectionState.DISCONNECTED
    }

    private fun startPollingFallback(callId: Long) {
        pollingJob?.cancel()
        pollingJob = scope.launch {
            while (isActive) {
                delay(1500)
                try {
                    val response = api.getSignals(callId)
                    if (response.isSuccessful && response.body()?.data != null) {
                        val signals = response.body()!!.data
                        signals.forEach { signalObj: JsonObject ->
                            val sigType = signalObj["signal_type"]?.jsonPrimitive?.contentOrNull ?: ""
                            val senderId = signalObj["sender_id"]?.jsonPrimitive?.longOrNull ?: 0L
                            val targetId = signalObj["target_user_id"]?.jsonPrimitive?.longOrNull
                            val senderName = signalObj["sender_name"]?.jsonPrimitive?.contentOrNull

                            val payloadObj = try {
                                signalObj["payload"]?.jsonObject
                            } catch (_: Exception) {
                                null
                            }
                            val payload = payloadObj?.entries?.associate { (key, elem) ->
                                key to (runCatching { elem.jsonPrimitive.content }.getOrDefault(elem.toString()))
                            } ?: emptyMap()

                            val currentUserId = tokenStorage.getUserId()
                            if (senderId > 0L && senderId != currentUserId) {
                                processSignal(
                                    CallSignalEvent(
                                        callId = callId,
                                        signalType = sigType,
                                        senderId = senderId,
                                        senderName = senderName,
                                        targetUserId = targetId,
                                        payload = payload
                                    )
                                )
                            }
                        }
                    }
                } catch (_: Exception) {
                    // Polling network drop handled gracefully
                }
            }
        }
    }

    private suspend fun processSignal(signal: CallSignalEvent) {
        val currentUserId = tokenStorage.getUserId()
        if (signal.senderId == currentUserId) return

        val signalKey = "${signal.callId}:${signal.signalType}:${signal.senderId}:${signal.payload.hashCode()}"
        if (!seenSignalKeys.add(signalKey)) return

        when (signal.signalType) {
            "offer" -> {
                val sdp = signal.payload["sdp"] ?: return
                _remoteOfferFlow.emit(signal.senderId to SessionDescriptionModel("offer", sdp))
            }
            "answer" -> {
                val sdp = signal.payload["sdp"] ?: return
                _remoteAnswerFlow.emit(signal.senderId to SessionDescriptionModel("answer", sdp))
            }
            "candidate" -> {
                val candidate = signal.payload["candidate"] ?: return
                val sdpMid = signal.payload["sdpMid"]
                val sdpMLineIndex = signal.payload["sdpMLineIndex"]?.toIntOrNull() ?: 0
                _remoteCandidateFlow.emit(
                    signal.senderId to IceCandidateModel(
                        candidate = candidate,
                        sdpMid = sdpMid,
                        sdpMLineIndex = sdpMLineIndex
                    )
                )
            }
            "renegotiate", "restart-ice" -> {
                _reconnectRequestFlow.emit(signal.callId)
            }
        }
    }

    suspend fun triggerIceRestart(callId: Long, targetUserId: Long? = null): Boolean {
        _reconnectRequestFlow.emit(callId)
        return sendRenegotiate(callId, targetUserId)
    }

    suspend fun sendOffer(callId: Long, sdp: String, targetUserId: Long? = null): Boolean {
        return try {
            val response = api.sendSignal(
                callId = callId,
                body = CallSignalRequest(
                    signal_type = "offer",
                    payload = mapOf("type" to "offer", "sdp" to sdp),
                    target_user_id = targetUserId
                )
            )
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }

    suspend fun sendAnswer(callId: Long, sdp: String, targetUserId: Long? = null): Boolean {
        return try {
            val response = api.sendSignal(
                callId = callId,
                body = CallSignalRequest(
                    signal_type = "answer",
                    payload = mapOf("type" to "answer", "sdp" to sdp),
                    target_user_id = targetUserId
                )
            )
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }

    suspend fun sendIceCandidate(
        callId: Long,
        candidate: String,
        sdpMid: String? = null,
        sdpMLineIndex: Int = 0,
        targetUserId: Long? = null
    ): Boolean {
        return try {
            val payload = mutableMapOf(
                "candidate" to candidate,
                "sdpMLineIndex" to sdpMLineIndex.toString()
            )
            if (sdpMid != null) {
                payload["sdpMid"] = sdpMid
            }

            val response = api.sendSignal(
                callId = callId,
                body = CallSignalRequest(
                    signal_type = "candidate",
                    payload = payload,
                    target_user_id = targetUserId
                )
            )
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }

    suspend fun sendRenegotiate(callId: Long, targetUserId: Long? = null): Boolean {
        return try {
            val response = api.sendSignal(
                callId = callId,
                body = CallSignalRequest(
                    signal_type = "renegotiate",
                    payload = mapOf("type" to "renegotiate"),
                    target_user_id = targetUserId
                )
            )
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }
}
