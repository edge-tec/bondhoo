package com.connectly.android.core.webrtc.session

import com.connectly.android.core.model.CallType
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.core.webrtc.model.WebRtcCallState
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import com.connectly.android.core.websocket.unified.NetworkConnectivityMonitor
import io.mockk.every
import io.mockk.mockk
import io.mockk.verify
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class CallSessionManagerTest {

    private val testDispatcher = StandardTestDispatcher()
    private val webRtcEngine: WebRtcEngine = mockk(relaxed = true)
    private val signalingCoordinator: WebRtcSignalingCoordinator = mockk(relaxed = true)
    private val connectivityMonitor: NetworkConnectivityMonitor = mockk(relaxed = true)

    private val isConnectedFlow = MutableStateFlow(true)
    private val callStateFlow = MutableStateFlow(WebRtcCallState())

    private lateinit var callSessionManager: CallSessionManager

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { connectivityMonitor.isConnected } returns isConnectedFlow
        every { webRtcEngine.callState } returns callStateFlow
        every { webRtcEngine.localVideoTrack } returns MutableStateFlow(null)
        every { webRtcEngine.remoteVideoTrack } returns MutableStateFlow(null)

        callSessionManager = CallSessionManager(
            webRtcEngine,
            signalingCoordinator,
            connectivityMonitor,
            testDispatcher
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `startOutgoingCall delegates correctly to WebRtcEngine`() {
        callSessionManager.startOutgoingCall(
            callId = 100L,
            conversationId = 50L,
            receiverId = 200L,
            receiverName = "Target User",
            callType = CallType.VIDEO
        )

        verify {
            webRtcEngine.startOutgoingCall(
                callId = 100L,
                conversationId = 50L,
                receiverId = 200L,
                receiverName = "Target User",
                callType = CallType.VIDEO
            )
        }
    }

    @Test
    fun `handleIncomingCall delegates correctly to WebRtcEngine`() {
        callSessionManager.handleIncomingCall(
            callId = 101L,
            conversationId = 51L,
            callerId = 300L,
            callerName = "Caller User",
            callType = CallType.AUDIO
        )

        verify {
            webRtcEngine.handleIncomingCall(
                callId = 101L,
                conversationId = 51L,
                callerId = 300L,
                callerName = "Caller User",
                callType = CallType.AUDIO
            )
        }
    }

    @Test
    fun `acceptCall delegates correctly to WebRtcEngine`() {
        callSessionManager.acceptCall(callId = 102L, conversationId = 52L)
        verify { webRtcEngine.acceptIncomingCall(102L, 52L) }
    }

    @Test
    fun `rejectCall delegates correctly to WebRtcEngine`() {
        callSessionManager.rejectCall()
        verify { webRtcEngine.rejectIncomingCall() }
    }

    @Test
    fun `endCall delegates correctly to WebRtcEngine`() {
        callSessionManager.endCall()
        verify { webRtcEngine.endCall(notifySignaling = true) }
    }

    @Test
    fun `hardware toggles delegate correctly to WebRtcEngine`() {
        every { webRtcEngine.toggleMute() } returns true
        every { webRtcEngine.toggleCamera() } returns false
        every { webRtcEngine.switchCamera() } returns false
        every { webRtcEngine.toggleSpeaker() } returns true

        assertTrue(callSessionManager.toggleMute())
        assertEquals(false, callSessionManager.toggleCamera())
        assertEquals(false, callSessionManager.switchCamera())
        assertTrue(callSessionManager.toggleSpeaker())

        verify { webRtcEngine.toggleMute() }
        verify { webRtcEngine.toggleCamera() }
        verify { webRtcEngine.switchCamera() }
        verify { webRtcEngine.toggleSpeaker() }
    }

    @Test
    fun `network reconnect during active call triggers ICE restart`() = runTest {
        callStateFlow.value = WebRtcCallState(callId = 105L)
        isConnectedFlow.value = false
        testDispatcher.scheduler.runCurrent()

        // Transition from disconnected to reconnected
        isConnectedFlow.value = true
        testDispatcher.scheduler.runCurrent()

        verify { webRtcEngine.triggerIceRestart() }
    }
}
