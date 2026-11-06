package com.connectly.android.feature.groups

import androidx.lifecycle.SavedStateHandle
import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageMonetizationStatusDto
import com.connectly.android.core.network.api.UpdatePageMonetizationRequest
import com.connectly.android.feature.groups.domain.usecase.GetPageMonetizationUseCase
import com.connectly.android.feature.groups.domain.usecase.UpdatePageMonetizationUseCase
import com.connectly.android.feature.groups.presentation.pages.monetization.MonetizationDialogType
import com.connectly.android.feature.groups.presentation.pages.monetization.PageMonetizationViewModel
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
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class PageMonetizationViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getPageMonetizationUseCase: GetPageMonetizationUseCase = mockk()
    private val updatePageMonetizationUseCase: UpdatePageMonetizationUseCase = mockk()

    private val sampleMonetization = PageMonetizationStatusDto(
        is_eligible = true,
        status = "Eligible",
        stars_enabled = false,
        in_stream_ads_enabled = true,
        subscriptions_enabled = false,
        brand_collabs_enabled = true,
        current_followers = 12500,
        required_followers = 10000,
        current_watch_minutes = 680000L,
        required_watch_minutes = 600000L,
        payout_status = "Connected",
        payout_date = "Nov 15",
        payout_account_name = "TechPulse Media Ltd",
        payout_account_number = "BD02SCBL00001234567890",
        payout_method = "Bank Wire (SWIFT)",
        bank_name = "Standard Chartered Bank PLC",
        swift_code = "SCBLBDDX",
        estimated_earnings = 1240.50,
        net_payout = 980.00,
        policy_status = "Good Standing"
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getPageMonetizationUseCase(1L) } returns flowOf(
            Result.Loading,
            Result.Success(sampleMonetization)
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `loadMonetization loads data and updates uiState`() = runTest {
        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 1L))
        val viewModel = PageMonetizationViewModel(
            getPageMonetizationUseCase = getPageMonetizationUseCase,
            updatePageMonetizationUseCase = updatePageMonetizationUseCase,
            savedStateHandle = savedStateHandle
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1L, state.pageId)
            assertFalse(state.isLoading)
            assertTrue(state.monetization.is_eligible)
            assertEquals(1240.50, state.monetization.estimated_earnings, 0.01)
            assertEquals(980.00, state.monetization.net_payout, 0.01)
            assertEquals("Bank Wire (SWIFT)", state.monetization.payout_method)
            assertEquals("Good Standing", state.monetization.policy_status)
        }
    }

    @Test
    fun `openDialog and closeDialog properly update activeDialog`() = runTest {
        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 1L))
        val viewModel = PageMonetizationViewModel(
            getPageMonetizationUseCase = getPageMonetizationUseCase,
            updatePageMonetizationUseCase = updatePageMonetizationUseCase,
            savedStateHandle = savedStateHandle
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.openDialog(MonetizationDialogType.EARNINGS_BREAKDOWN)
        assertEquals(MonetizationDialogType.EARNINGS_BREAKDOWN, viewModel.uiState.value.activeDialog)

        viewModel.openDialog(MonetizationDialogType.PAYOUT_SCHEDULE)
        assertEquals(MonetizationDialogType.PAYOUT_SCHEDULE, viewModel.uiState.value.activeDialog)

        viewModel.openDialog(MonetizationDialogType.IN_STREAM_ADS)
        assertEquals(MonetizationDialogType.IN_STREAM_ADS, viewModel.uiState.value.activeDialog)

        viewModel.closeDialog()
        assertNull(viewModel.uiState.value.activeDialog)
    }

    @Test
    fun `toggleSubscriptions updates state and calls usecase`() = runTest {
        val updated = sampleMonetization.copy(subscriptions_enabled = true, stars_enabled = true)
        val expectedReq = UpdatePageMonetizationRequest(subscriptions_enabled = true, stars_enabled = true)

        every { updatePageMonetizationUseCase(1L, expectedReq) } returns flowOf(
            Result.Loading,
            Result.Success(updated)
        )

        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 1L))
        val viewModel = PageMonetizationViewModel(
            getPageMonetizationUseCase = getPageMonetizationUseCase,
            updatePageMonetizationUseCase = updatePageMonetizationUseCase,
            savedStateHandle = savedStateHandle
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.toggleSubscriptions(subscriptionsEnabled = true, starsEnabled = true)
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertTrue(state.monetization.subscriptions_enabled)
        assertTrue(state.monetization.stars_enabled)
        assertNotNull(state.successMessage)
        assertNull(state.activeDialog)
    }

    @Test
    fun `configurePayout updates payout account and calls usecase`() = runTest {
        val updated = sampleMonetization.copy(
            payout_method = "bKash",
            payout_account_name = "Mizanur Rahman",
            payout_account_number = "0172129196"
        )
        val expectedReq = UpdatePageMonetizationRequest(
            payout_method = "bKash",
            payout_account_name = "Mizanur Rahman",
            payout_account_number = "0172129196",
            bank_name = null,
            swift_code = null
        )

        every { updatePageMonetizationUseCase(1L, expectedReq) } returns flowOf(
            Result.Loading,
            Result.Success(updated)
        )

        val savedStateHandle = SavedStateHandle(mapOf("pageId" to 1L))
        val viewModel = PageMonetizationViewModel(
            getPageMonetizationUseCase = getPageMonetizationUseCase,
            updatePageMonetizationUseCase = updatePageMonetizationUseCase,
            savedStateHandle = savedStateHandle
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.configurePayout(
            method = "bKash",
            accountName = "Mizanur Rahman",
            accountNumber = "0172129196"
        )
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("bKash", state.monetization.payout_method)
        assertEquals("Mizanur Rahman", state.monetization.payout_account_name)
        assertNotNull(state.successMessage)
    }
}

