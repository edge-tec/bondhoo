package com.connectly.android.feature.calling

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.User
import com.connectly.android.feature.calling.data.webrtc.WebRtcCallManager
import com.connectly.android.feature.calling.domain.usecase.*
import com.connectly.android.feature.calling.presentation.calling.CallIntent
import com.connectly.android.feature.calling.presentation.calling.CallViewModel
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.flowOf
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
class CallViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val initiateCallUseCase: InitiateCallUseCase = mockk()
    private val respondCallUseCase: RespondCallUseCase = mockk()
    private val leaveCallUseCase: LeaveCallUseCase = mockk()
    private val updateCallStateUseCase: UpdateCallStateUseCase = mockk()
    private val webRtcManager: WebRtcCallManager = mockk(relaxed = true)

    private lateinit var viewModel: CallViewModel

    private val testSession = CallSession(
        id = 301L,
        channelName = "channel_301",
        callType = CallType.AUDIO,
        status = CallStatus.RINGING,
        caller = User(id = 1L, name = "You", username = "you"),
        receiver = User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
        isIncoming = false
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { webRtcManager.isMuted } returns MutableStateFlow(false)
        every { webRtcManager.isCameraOff } returns MutableStateFlow(false)
        every { webRtcManager.isSpeakerOn } returns MutableStateFlow(false)
        every { webRtcManager.networkQuality } returns MutableStateFlow(com.connectly.android.core.model.NetworkQuality.EXCELLENT)
        every { webRtcManager.localVideoTrack } returns MutableStateFlow(null)
        every { webRtcManager.remoteVideoTrack } returns MutableStateFlow(null)
        every { webRtcManager.eglBaseContext } returns null
        every { leaveCallUseCase(any()) } returns flowOf(Result.Success(testSession.copy(status = CallStatus.ENDED)))

        viewModel = CallViewModel(
            initiateCallUseCase,
            respondCallUseCase,
            leaveCallUseCase,
            updateCallStateUseCase,
            webRtcManager
        )
    }

    @After
    fun tearDown() {
        viewModel.handleIntent(CallIntent.EndCall)
        testDispatcher.scheduler.runCurrent()
        Dispatchers.resetMain()
    }

    @Test
    fun `when startOutgoingCall dispatched, updates state and initiates call`() = runTest {
        every { initiateCallUseCase(51L, CallType.AUDIO) } returns flowOf(
            Result.Loading,
            Result.Success(testSession)
        )

        viewModel.handleIntent(CallIntent.StartOutgoingCall(51L, CallType.AUDIO))
        testDispatcher.scheduler.runCurrent()

        val state = viewModel.uiState.value
        assertEquals(CallType.AUDIO, state.callType)
        assertEquals("Sadia Islam", state.callSession?.receiver?.name)

        viewModel.handleIntent(CallIntent.EndCall)
        testDispatcher.scheduler.runCurrent()
    }

    @Test
    fun `when acceptCall dispatched, responds with accept and transitions to connected`() = runTest {
        every { initiateCallUseCase(51L, CallType.AUDIO) } returns flowOf(Result.Success(testSession))
        every { respondCallUseCase(301L, "accept") } returns flowOf(
            Result.Success(testSession.copy(status = CallStatus.CONNECTED))
        )

        viewModel.handleIntent(CallIntent.StartOutgoingCall(51L, CallType.AUDIO))
        testDispatcher.scheduler.runCurrent()

        viewModel.handleIntent(CallIntent.AcceptCall)
        testDispatcher.scheduler.runCurrent()

        assertEquals(CallStatus.CONNECTED, viewModel.uiState.value.callStatus)

        viewModel.handleIntent(CallIntent.EndCall)
        testDispatcher.scheduler.runCurrent()
    }

    @Test
    fun `when endCall dispatched, leaves call and marks isCallEnded true`() = runTest {
        every { initiateCallUseCase(51L, CallType.AUDIO) } returns flowOf(Result.Success(testSession))
        every { leaveCallUseCase(301L) } returns flowOf(
            Result.Success(testSession.copy(status = CallStatus.ENDED))
        )

        viewModel.handleIntent(CallIntent.StartOutgoingCall(51L, CallType.AUDIO))
        testDispatcher.scheduler.runCurrent()

        viewModel.handleIntent(CallIntent.EndCall)
        testDispatcher.scheduler.runCurrent()

        val state = viewModel.uiState.value
        assertTrue(state.isCallEnded)
        assertEquals(CallStatus.ENDED, state.callStatus)
    }

    @Test
    fun `when toggleMute dispatched, updates manager and invokes updateCallState`() = runTest {
        every { webRtcManager.toggleMute() } returns true
        every { updateCallStateUseCase(301L, true, null) } returns flowOf(Result.Success(Unit))

        every { initiateCallUseCase(51L, CallType.AUDIO) } returns flowOf(Result.Success(testSession))
        viewModel.handleIntent(CallIntent.StartOutgoingCall(51L, CallType.AUDIO))
        testDispatcher.scheduler.runCurrent()

        viewModel.handleIntent(CallIntent.ToggleMute)
        testDispatcher.scheduler.runCurrent()

        viewModel.handleIntent(CallIntent.EndCall)
        testDispatcher.scheduler.runCurrent()
    }
}
