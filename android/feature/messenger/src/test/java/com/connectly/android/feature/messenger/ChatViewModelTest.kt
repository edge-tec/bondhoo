package com.connectly.android.feature.messenger

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.feature.messenger.domain.usecase.*
import com.connectly.android.feature.messenger.presentation.chat.ChatIntent
import com.connectly.android.feature.messenger.presentation.chat.ChatViewModel
import io.mockk.coEvery
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.emptyFlow
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
class ChatViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getMessagesUseCase: GetMessagesUseCase = mockk()
    private val sendMessageUseCase: SendMessageUseCase = mockk()
    private val markReadUseCase: MarkReadUseCase = mockk()
    private val reactToMessageUseCase: ReactToMessageUseCase = mockk()
    private val deleteMessageUseCase: DeleteMessageUseCase = mockk()
    private val observeIncomingMessagesUseCase: ObserveIncomingMessagesUseCase = mockk()

    private lateinit var viewModel: ChatViewModel

    private val testMessage = Message(
        id = 901L,
        conversationId = 501L,
        senderId = 51L,
        senderName = "Sadia Islam",
        body = "Hello! Are we still meeting?",
        type = "text",
        clientMessageId = "client_msg_901",
        deliveryStatus = DeliveryStatus.READ,
        isMine = false
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getMessagesUseCase(501L) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testMessage))
        )
        every { markReadUseCase(501L) } returns flowOf(Result.Success(Unit))
        every { observeIncomingMessagesUseCase(501L) } returns emptyFlow()

        viewModel = ChatViewModel(
            getMessagesUseCase,
            sendMessageUseCase,
            markReadUseCase,
            reactToMessageUseCase,
            deleteMessageUseCase,
            observeIncomingMessagesUseCase
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when loadChat intent dispatched, loads messages and marks conversation read`() = runTest {
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(501L, state.conversationId)
            assertEquals(1, state.messages.size)
            assertEquals("Hello! Are we still meeting?", state.messages.first().body)
        }
    }

    @Test
    fun `when sendTextMessage dispatched, adds optimistic message and updates status upon success`() = runTest {
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        coEvery {
            sendMessageUseCase(
                conversationId = 501L,
                body = "Yes, see you at 5!",
                type = "text",
                clientMessageId = any()
            )
        } returns flowOf(
            Result.Success(
                testMessage.copy(
                    id = 902L,
                    body = "Yes, see you at 5!",
                    deliveryStatus = DeliveryStatus.SENT,
                    isMine = true
                )
            )
        )

        viewModel.handleIntent(ChatIntent.SendTextMessage("Yes, see you at 5!"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.messages.size)
            assertTrue(state.messages.any { it.body == "Yes, see you at 5!" })
        }
    }
}

