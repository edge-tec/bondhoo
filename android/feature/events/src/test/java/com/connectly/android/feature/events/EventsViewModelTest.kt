package com.connectly.android.feature.events

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus
import com.connectly.android.feature.events.domain.usecase.GetEventsUseCase
import com.connectly.android.feature.events.domain.usecase.RsvpEventUseCase
import com.connectly.android.feature.events.presentation.events.EventsIntent
import com.connectly.android.feature.events.presentation.events.EventsViewModel
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
class EventsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getEventsUseCase: GetEventsUseCase = mockk()
    private val rsvpEventUseCase: RsvpEventUseCase = mockk()

    private lateinit var viewModel: EventsViewModel

    private val sampleEvent = Event(
        id = 201L,
        title = "Music Festival 2026",
        location = "Dhaka Arena, Dhaka",
        dateDay = "15",
        dateMonth = "NOV",
        myRsvp = RsvpStatus.GOING
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getEventsUseCase("discover", 1) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(sampleEvent))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, loads events successfully`() = runTest {
        viewModel = EventsViewModel(getEventsUseCase, rsvpEventUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.events.size)
            assertEquals("Music Festival 2026", state.events.first().title)
            assertEquals(RsvpStatus.GOING, state.events.first().myRsvp)
        }
    }

    @Test
    fun `when toggle rsvp dispatched, updates event rsvp state optimistically`() = runTest {
        every { rsvpEventUseCase(201L, RsvpStatus.INTERESTED) } returns flowOf(Result.Success(RsvpStatus.INTERESTED))

        viewModel = EventsViewModel(getEventsUseCase, rsvpEventUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(EventsIntent.ToggleRsvp(201L, RsvpStatus.INTERESTED))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(RsvpStatus.INTERESTED, state.events.first().myRsvp)
        }
    }
}

