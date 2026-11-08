package com.connectly.android.feature.reels

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.MusicTrack
import com.connectly.android.core.model.Reel
import com.connectly.android.core.model.User
import com.connectly.android.feature.reels.domain.usecase.*
import com.connectly.android.feature.reels.presentation.player.ReelsIntent
import com.connectly.android.feature.reels.presentation.player.ReelsViewModel
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class ReelsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getReelsFeedUseCase: GetReelsFeedUseCase = mockk()
    private val reactToReelUseCase: ReactToReelUseCase = mockk()
    private val recordReelViewUseCase: RecordReelViewUseCase = mockk()
    private val saveReelUseCase: SaveReelUseCase = mockk()
    private val shareReelUseCase: ShareReelUseCase = mockk()
    private val followCreatorUseCase: FollowCreatorUseCase = mockk()
    private val getReelCommentsUseCase: GetReelCommentsUseCase = mockk()
    private val createReelCommentUseCase: CreateReelCommentUseCase = mockk()

    private lateinit var viewModel: ReelsViewModel

    private val testReel = Reel(
        id = 401L,
        creator = User(id = 41L, name = "Music Vibes", username = "music_vibes"),
        caption = "Good Music, Good Vibes ❤️",
        videoUrl = "https://example.com/video.mp4",
        music = MusicTrack(id = 1L, title = "Original Audio", artist = "Music Vibes"),
        likesCount = 12500,
        commentsCount = 567,
        sharesCount = 1200,
        isLiked = false,
        isFollowingCreator = false
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getReelsFeedUseCase(any(), any(), any()) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testReel))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, reels feed loads successfully`() = runTest {
        viewModel = ReelsViewModel(
            getReelsFeedUseCase,
            reactToReelUseCase,
            recordReelViewUseCase,
            saveReelUseCase,
            shareReelUseCase,
            followCreatorUseCase,
            getReelCommentsUseCase,
            createReelCommentUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.reels.size)
            assertEquals("Music Vibes", state.reels.first().creator.name)
            assertEquals(12500, state.reels.first().likesCount)
        }
    }

    @Test
    fun `when like toggled, optimistic update increments like count and updates state`() = runTest {
        every { reactToReelUseCase(401L, "like") } returns flowOf(Result.Success(Unit))

        viewModel = ReelsViewModel(
            getReelsFeedUseCase,
            reactToReelUseCase,
            recordReelViewUseCase,
            saveReelUseCase,
            shareReelUseCase,
            followCreatorUseCase,
            getReelCommentsUseCase,
            createReelCommentUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ReelsIntent.ToggleLike(401L))

        viewModel.uiState.test {
            val state = awaitItem()
            val r = state.reels.first()
            assertEquals(true, r.isLiked)
            assertEquals(12501, r.likesCount)
        }
    }

    @Test
    fun `when follow creator clicked, creator follow state is toggled`() = runTest {
        every { followCreatorUseCase(41L) } returns flowOf(Result.Success(true))

        viewModel = ReelsViewModel(
            getReelsFeedUseCase,
            reactToReelUseCase,
            recordReelViewUseCase,
            saveReelUseCase,
            shareReelUseCase,
            followCreatorUseCase,
            getReelCommentsUseCase,
            createReelCommentUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ReelsIntent.FollowCreator(41L))

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(true, state.reels.first().isFollowingCreator)
        }
    }
}

