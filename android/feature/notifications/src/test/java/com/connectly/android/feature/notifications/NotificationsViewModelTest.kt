package com.connectly.android.feature.notifications

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationType
import com.connectly.android.core.model.User
import com.connectly.android.feature.notifications.domain.usecase.*
import com.connectly.android.feature.notifications.presentation.notifications.NotificationsIntent
import com.connectly.android.feature.notifications.presentation.notifications.NotificationsViewModel
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
class NotificationsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getNotificationsUseCase: GetNotificationsUseCase = mockk()
    private val getUnreadNotificationCountUseCase: GetUnreadNotificationCountUseCase = mockk()
    private val markNotificationAsReadUseCase: MarkNotificationAsReadUseCase = mockk()
    private val markAllNotificationsAsReadUseCase: MarkAllNotificationsAsReadUseCase = mockk()
    private val respondToFriendRequestUseCase: RespondToFriendRequestUseCase = mockk()

    private lateinit var viewModel: NotificationsViewModel

    private val sampleNotification = NotificationItem(
        id = "notif_1",
        type = NotificationType.REACTION,
        actor = User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
        message = "Sadia Islam reacted to your photo. 1m",
        isRead = false
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getNotificationsUseCase("all", "all", 1) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(sampleNotification))
        )
        every { getUnreadNotificationCountUseCase() } returns flowOf(3)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, notifications and unread count load successfully`() = runTest {
        viewModel = NotificationsViewModel(
            getNotificationsUseCase,
            getUnreadNotificationCountUseCase,
            markNotificationAsReadUseCase,
            markAllNotificationsAsReadUseCase,
            respondToFriendRequestUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.notifications.size)
            assertEquals(3, state.unreadCount)
            assertTrue(state.notifications.first().message.contains("Sadia Islam"))
        }
    }

    @Test
    fun `when category selected, updates selectedCategory and reloads notifications`() = runTest {
        every { getNotificationsUseCase("all", "likes", 1) } returns flowOf(
            Result.Success(listOf(sampleNotification))
        )

        viewModel = NotificationsViewModel(
            getNotificationsUseCase,
            getUnreadNotificationCountUseCase,
            markNotificationAsReadUseCase,
            markAllNotificationsAsReadUseCase,
            respondToFriendRequestUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(NotificationsIntent.SelectCategory("likes"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("likes", state.selectedCategory)
        }
    }

    @Test
    fun `when markRead dispatched, marks notification as read optimistically`() = runTest {
        every { markNotificationAsReadUseCase("notif_1") } returns flowOf(Result.Success(Unit))

        viewModel = NotificationsViewModel(
            getNotificationsUseCase,
            getUnreadNotificationCountUseCase,
            markNotificationAsReadUseCase,
            markAllNotificationsAsReadUseCase,
            respondToFriendRequestUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(NotificationsIntent.MarkRead("notif_1"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.notifications.first().isRead)
        }
    }
}

