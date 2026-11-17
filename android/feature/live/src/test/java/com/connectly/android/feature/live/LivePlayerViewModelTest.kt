package com.connectly.android.feature.live

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.model.LiveStream
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.feature.live.domain.usecase.*
import com.connectly.android.feature.live.presentation.player.LivePlayerIntent
import com.connectly.android.feature.live.presentation.player.LivePlayerViewModel
import io.mockk.coEvery
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
class LivePlayerViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getActiveLiveStreamUseCase: GetActiveLiveStreamUseCase = mockk()
    private val startLiveStreamUseCase: StartLiveStreamUseCase = mockk()
    private val endLiveStreamUseCase: EndLiveStreamUseCase = mockk()
    private val sendLiveCommentUseCase: SendLiveCommentUseCase = mockk()
    private val sendLiveReactionUseCase: SendLiveReactionUseCase = mockk()
    private val observeLiveCommentsUseCase: ObserveLiveCommentsUseCase = mockk()
    private val webRtcEngine: WebRtcEngine = mockk(relaxed = true)
    private val api: ConnectlyApi = mockk(relaxed = true)

    private lateinit var viewModel: LivePlayerViewModel

    private val sampleLiveStream = LiveStream(
        id = 101L,
        title = "Acoustic Live Music Session 🎸",
        streamer = User(id = 41L, name = "Sadia Islam", username = "sadia_islam"),
        viewerCount = 4800,
        formattedViewerCount = "4.8K"
    )

    private val sampleBroadcastStream = LiveStream(
        id = 205L,
        title = "Bondhoo Live Studio Broadcast",
        streamer = User(id = 1L, name = "You", username = "you"),
        viewerCount = 1,
        formattedViewerCount = "1",
        isLive = true
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { webRtcEngine.localVideoTrack } returns MutableStateFlow(null)
        every { getActiveLiveStreamUseCase(101L) } returns flowOf(
            Result.Loading,
            Result.Success(sampleLiveStream)
        )
        every { startLiveStreamUseCase("Bondhoo Live Studio Broadcast") } returns flowOf(
            Result.Loading,
            Result.Success(sampleBroadcastStream)
        )
        every { observeLiveCommentsUseCase(any()) } returns flowOf(
            LiveComment(
                id = 1L,
                streamId = 101L,
                user = User(id = 10L, name = "Rahat Khan", username = "rahat"),
                comment = "Great session!"
            )
        )

        viewModel = LivePlayerViewModel(
            getActiveLiveStreamUseCase,
            startLiveStreamUseCase,
            endLiveStreamUseCase,
            sendLiveCommentUseCase,
            sendLiveReactionUseCase,
            observeLiveCommentsUseCase,
            webRtcEngine,
            api
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when loadStream dispatched for viewer, loads stream details and retains comments`() = runTest {
        viewModel.handleIntent(LivePlayerIntent.LoadStream(101L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("4.8K", state.liveStream?.formattedViewerCount)
            assertEquals("Sadia Islam", state.liveStream?.streamer?.name)
            assertTrue(state.comments.any { it.user.name == "Rahat Khan" })
        }
    }

    @Test
    fun `when loadStream dispatched for broadcaster, creates live stream and sets broadcaster flag`() = runTest {
        viewModel.handleIntent(LivePlayerIntent.LoadStream(1L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.isBroadcaster)
            assertEquals(205L, state.liveStream?.id)
            assertEquals("Bondhoo Live Studio Broadcast", state.liveStream?.title)
        }
    }

    @Test
    fun `when endStream dispatched, stops media and marks streamEnded`() = runTest {
        viewModel.handleIntent(LivePlayerIntent.LoadStream(1L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(LivePlayerIntent.EndStream)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.streamEnded)
        }
    }

    @Test
    fun `when sendComment dispatched, appends local comment and calls use case`() = runTest {
        every { sendLiveCommentUseCase(101L, "Loved the song!") } returns flowOf(
            Result.Success(
                LiveComment(
                    id = 999L,
                    streamId = 101L,
                    user = User(id = 1L, name = "You", username = "you"),
                    comment = "Loved the song!"
                )
            )
        )

        viewModel.handleIntent(LivePlayerIntent.LoadStream(101L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(LivePlayerIntent.SendComment("Loved the song!"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.comments.any { it.comment == "Loved the song!" })
        }
    }
}
