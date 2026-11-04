package com.connectly.android.feature.messenger

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.User
import com.connectly.android.feature.messenger.domain.usecase.GetConversationsUseCase
import com.connectly.android.feature.messenger.presentation.conversations.ConversationsIntent
import com.connectly.android.feature.messenger.presentation.conversations.ConversationsViewModel
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
class ConversationsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getConversationsUseCase: GetConversationsUseCase = mockk()

    private lateinit var viewModel: ConversationsViewModel

    private val testConversation = Conversation(
        id = 501L,
        type = "direct",
        title = "Sadia Islam",
        otherUser = User(id = 51L, name = "Sadia Islam", username = "sadia_islam", isOnline = true),
        lastMessage = Message(
            id = 5001L,
            conversationId = 501L,
            senderId = 51L,
            senderName = "Sadia Islam",
            body = "Typing...",
            type = "text",
            clientMessageId = "msg_5001"
        ),
        unreadCount = 2
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getConversationsUseCase("all") } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testConversation))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, conversations load successfully`() = runTest {
        viewModel = ConversationsViewModel(getConversationsUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.conversations.size)
            assertEquals("Sadia Islam", state.conversations.first().title)
            assertEquals(2, state.conversations.first().unreadCount)
        }
    }

    @Test
    fun `when filter selected, updates selectedFilter and loads filtered conversations`() = runTest {
        every { getConversationsUseCase("unread") } returns flowOf(
            Result.Success(listOf(testConversation))
        )

        viewModel = ConversationsViewModel(getConversationsUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ConversationsIntent.SelectFilter("unread"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("unread", state.selectedFilter)
            assertEquals(1, state.conversations.size)
        }
    }

    @Test
    fun `when toggleSearch dispatched, flips isSearchVisible`() = runTest {
        viewModel = ConversationsViewModel(getConversationsUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ConversationsIntent.ToggleSearch)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.isSearchVisible)
        }
    }

    @Test
    fun `when startNewChat dispatched, prepends new conversation and closes dialog`() = runTest {
        viewModel = ConversationsViewModel(getConversationsUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ConversationsIntent.StartNewChat("Tariq Islam"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.conversations.size)
            assertEquals("Tariq Islam", state.conversations.first().title)
        }
    }
}
