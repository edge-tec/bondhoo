package com.connectly.android.feature.groups

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageDto
import com.connectly.android.feature.groups.domain.usecase.CreatePageDetailedUseCase
import com.connectly.android.feature.groups.domain.usecase.CreatePageUseCase
import com.connectly.android.feature.groups.domain.usecase.FollowPageUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPagesUseCase
import com.connectly.android.feature.groups.presentation.pages.PagesIntent
import com.connectly.android.feature.groups.presentation.pages.PagesViewModel
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
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class PagesViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getPagesUseCase: GetPagesUseCase = mockk()
    private val followPageUseCase: FollowPageUseCase = mockk()
    private val createPageUseCase: CreatePageUseCase = mockk()
    private val createPageDetailedUseCase: CreatePageDetailedUseCase = mockk()

    private lateinit var viewModel: PagesViewModel

    private val samplePageDto = PageDto(
        id = 1L,
        name = "Tech World BD",
        category = "Technology & Media",
        avatar = "https://images.unsplash.com/photo-1518770660439-4636190af475",
        cover = "https://images.unsplash.com/photo-1519389950473-47ba0277781c",
        followers_count = 186000,
        is_following = false,
        is_verified = true
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getPagesUseCase("discover", 1) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(samplePageDto))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, loads pages and sets featured`() = runTest {
        viewModel = PagesViewModel(getPagesUseCase, followPageUseCase, createPageUseCase, createPageDetailedUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.pages.size)
            assertEquals("Tech World BD", state.pages.first().name)
            assertEquals("Tech World BD", state.featuredPage?.name)
        }
    }

    @Test
    fun `when toggle follow dispatched, updates follow state optimistically`() = runTest {
        every { followPageUseCase(1L) } returns flowOf(Result.Success(Unit))

        viewModel = PagesViewModel(getPagesUseCase, followPageUseCase, createPageUseCase, createPageDetailedUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(PagesIntent.ToggleFollowPage(1L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.pages.first().isFollowed)
            assertEquals(186001, state.pages.first().followersCount)
        }
    }
}
