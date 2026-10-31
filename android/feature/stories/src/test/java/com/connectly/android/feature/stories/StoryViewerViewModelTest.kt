package com.connectly.android.feature.stories

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.stories.domain.usecase.*
import com.connectly.android.feature.stories.presentation.viewer.StoryViewerIntent
import com.connectly.android.feature.stories.presentation.viewer.StoryViewerViewModel
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
class StoryViewerViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val viewStoryUseCase: ViewStoryUseCase = mockk()
    private val reactToStoryUseCase: ReactToStoryUseCase = mockk()
    private val replyToStoryUseCase: ReplyToStoryUseCase = mockk()
    private val getStoryViewersUseCase: GetStoryViewersUseCase = mockk()
    private val deleteStoryUseCase: DeleteStoryUseCase = mockk()

    private lateinit var viewModel: StoryViewerViewModel

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { viewStoryUseCase(301L) } returns flowOf(Result.Success(Unit))
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when story loaded, story viewer state contains active story and marks view`() = runTest {
        viewModel = StoryViewerViewModel(
            viewStoryUseCase,
            reactToStoryUseCase,
            replyToStoryUseCase,
            getStoryViewersUseCase,
            deleteStoryUseCase
        )

        viewModel.handleIntent(StoryViewerIntent.LoadStory(301L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertNotNull(state.story)
            assertEquals("Sadia Islam", state.story?.author?.name)
            assertEquals(142, state.story?.viewsCount)
        }
    }

    @Test
    fun `when reply sent, state sets isReplied to true`() = runTest {
        every { replyToStoryUseCase(301L, "Looking great!") } returns flowOf(Result.Success(Unit))

        viewModel = StoryViewerViewModel(
            viewStoryUseCase,
            reactToStoryUseCase,
            replyToStoryUseCase,
            getStoryViewersUseCase,
            deleteStoryUseCase
        )
        viewModel.handleIntent(StoryViewerIntent.LoadStory(301L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(StoryViewerIntent.SendReply("Looking great!"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(true, state.isReplied)
        }
    }
}

