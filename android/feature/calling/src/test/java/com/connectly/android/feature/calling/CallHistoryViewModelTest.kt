package com.connectly.android.feature.calling

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.User
import com.connectly.android.feature.calling.domain.usecase.GetCallHistoryUseCase
import com.connectly.android.feature.calling.presentation.history.CallHistoryViewModel
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
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class CallHistoryViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getCallHistoryUseCase: GetCallHistoryUseCase = mockk()

    private lateinit var viewModel: CallHistoryViewModel

    private val sampleItem = CallHistoryItem(
        id = 101L,
        otherUser = User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
        callType = CallType.VIDEO,
        isIncoming = true,
        isMissed = false,
        durationSeconds = 345,
        formattedTime = "Today, 11:30 AM"
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getCallHistoryUseCase() } returns flowOf(
            Result.Loading,
            Result.Success(listOf(sampleItem))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, loads call history items successfully`() = runTest {
        viewModel = CallHistoryViewModel(getCallHistoryUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.callLogs.size)
            assertEquals("Sadia Islam", state.callLogs.first().otherUser.name)
            assertEquals(CallType.VIDEO, state.callLogs.first().callType)
        }
    }
}
