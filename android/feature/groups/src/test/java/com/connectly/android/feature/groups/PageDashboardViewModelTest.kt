package com.connectly.android.feature.groups

import androidx.lifecycle.SavedStateHandle
import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageAnalyticsDetailDto
import com.connectly.android.core.network.api.PageAnalyticsSummaryDto
import com.connectly.android.core.network.api.PageDto
import com.connectly.android.feature.groups.domain.usecase.GetPageAnalyticsUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageDetailsUseCase
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardIntent
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardViewModel
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
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class PageDashboardViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getPageDetailsUseCase: GetPageDetailsUseCase = mockk()
    private val getPageAnalyticsUseCase: GetPageAnalyticsUseCase = mockk()

    private val samplePage = PageDto(
        id = 42L,
        name = "Bondhoo Enterprise BD",
        category = "Technology",
        followers_count = 12000,
        is_verified = true
    )

    private val sampleAnalytics = PageAnalyticsDetailDto(
        page_id = 42L,
        summary = PageAnalyticsSummaryDto(
            total_followers = 12000,
            new_followers = 450,
            impressions = 65000,
            reach = 48000,
            engagement_rate = 6.4,
            reactions = 2400
        )
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getPageDetailsUseCase("42") } returns flowOf(
            Result.Loading,
            Result.Success(samplePage)
        )
        every { getPageAnalyticsUseCase(42L, any()) } returns flowOf(
            Result.Loading,
            Result.Success(sampleAnalytics)
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when pageId is passed as Long in SavedStateHandle, does not throw ClassCastException and loads dashboard`() = runTest {
        // This explicitly reproduces and verifies the fix for the reported crash:
        // java.lang.ClassCastException: java.lang.Long cannot be cast to java.lang.String
        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 42L))
        val viewModel = PageDashboardViewModel(getPageDetailsUseCase, getPageAnalyticsUseCase, savedStateHandle)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(42L, state.pageId)
            assertEquals("Bondhoo Enterprise BD", state.page?.name)
            assertNotNull(state.analytics)
            assertEquals(48000, state.analytics?.resolvedReach)
            assertEquals(6.4, state.analytics?.resolvedEngagementRate ?: 0.0, 0.01)
            assertFalse(state.isLoading)
        }
    }

    @Test
    fun `when pageId is passed as String in SavedStateHandle, parses cleanly`() = runTest {
        val savedStateHandle = SavedStateHandle(mapOf("pageId" to "42"))
        val viewModel = PageDashboardViewModel(getPageDetailsUseCase, getPageAnalyticsUseCase, savedStateHandle)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(42L, state.pageId)
            assertEquals("Bondhoo Enterprise BD", state.page?.name)
        }
    }

    @Test
    fun `when range is changed, triggers new analytics fetch with selected range`() = runTest {
        every { getPageAnalyticsUseCase(42L, "90d") } returns flowOf(
            Result.Success(sampleAnalytics)
        )

        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 42L))
        val viewModel = PageDashboardViewModel(getPageDetailsUseCase, getPageAnalyticsUseCase, savedStateHandle)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(PageDashboardIntent.SelectRange("90d"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("90d", state.selectedRange)
        }
    }
}

