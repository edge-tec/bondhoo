package com.connectly.android.feature.messenger

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.User
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.messenger.domain.usecase.*
import com.connectly.android.feature.messenger.presentation.chat.ChatIntent
import com.connectly.android.feature.messenger.presentation.chat.ChatViewModel
import io.mockk.coEvery
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableSharedFlow
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
    private val getConversationDetailsUseCase: GetConversationDetailsUseCase = mockk()
    private val getConversationsUseCase: GetConversationsUseCase = mockk(relaxed = true)
    private val createConversationUseCase: CreateConversationUseCase = mockk(relaxed = true)
    private val getFriendsUseCase: GetFriendsForMessagingUseCase = mockk(relaxed = true)
    private val getMessagesUseCase: GetMessagesUseCase = mockk()
    private val sendMessageUseCase: SendMessageUseCase = mockk()
    private val uploadAttachmentUseCase: UploadMessageAttachmentUseCase = mockk(relaxed = true)
    private val sendVoiceMessageUseCase: SendVoiceMessageUseCase = mockk(relaxed = true)
    private val editMessageUseCase: EditMessageUseCase = mockk()
    private val forwardMessageUseCase: ForwardMessageUseCase = mockk(relaxed = true)
    private val pinMessageUseCase: PinMessageUseCase = mockk()
    private val saveMessageUseCase: SaveMessageUseCase = mockk(relaxed = true)
    private val markReadUseCase: MarkReadUseCase = mockk()
    private val reactToMessageUseCase: ReactToMessageUseCase = mockk()
    private val deleteMessageUseCase: DeleteMessageUseCase = mockk()
    private val reportMessageUseCase: ReportMessageUseCase = mockk(relaxed = true)
    private val manageConversationUseCase: ManageConversationUseCase = mockk(relaxed = true)
    private val sendTypingUseCase: SendTypingUseCase = mockk(relaxed = true)
    private val observeIncomingMessagesUseCase: ObserveIncomingMessagesUseCase = mockk()
    private val observeTypingUseCase: ObserveTypingUseCase = mockk()
    private val observePresenceUseCase: ObservePresenceUseCase = mockk()
    private val webSocketManager: ReverbWebSocketManager = mockk(relaxed = true)
    private val tokenStorageManager: TokenStorageManager = mockk(relaxed = true)

    private lateinit var viewModel: ChatViewModel

    private val testConv = Conversation(
        id = 501L,
        type = "direct",
        title = "Tanvir Hasan",
        otherUser = User(id = 51L, name = "Tanvir Hasan", username = "tanvir", isOnline = true),
        isOnline = true
    )

    private val testMessage = Message(
        id = 901L,
        conversationId = 501L,
        senderId = 51L,
        senderName = "Tanvir Hasan",
        body = "Hello! Are we still meeting?",
        type = "text",
        clientMessageId = "client_msg_901",
        deliveryStatus = DeliveryStatus.READ,
        isMine = false,
        createdAt = 1000L
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getConversationDetailsUseCase(501L) } returns flowOf(Result.Success(testConv))
        every { getMessagesUseCase(501L) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testMessage))
        )
        every { pinMessageUseCase.getPinned(501L) } returns flowOf(Result.Success(emptyList()))
        every { markReadUseCase(501L) } returns flowOf(Result.Success(Unit))
        every { observeIncomingMessagesUseCase(501L) } returns emptyFlow()
        every { observeTypingUseCase(501L) } returns emptyFlow()
        every { observePresenceUseCase() } returns emptyFlow()
        every { webSocketManager.messageUpdatedFlow } returns MutableSharedFlow()
        every { webSocketManager.messageDeletedFlow } returns MutableSharedFlow()
        every { webSocketManager.messageReceiptFlow } returns MutableSharedFlow()
        every { webSocketManager.conversationClearedFlow } returns MutableSharedFlow()

        viewModel = ChatViewModel(
            getConversationDetailsUseCase = getConversationDetailsUseCase,
            getConversationsUseCase = getConversationsUseCase,
            createConversationUseCase = createConversationUseCase,
            getFriendsUseCase = getFriendsUseCase,
            getMessagesUseCase = getMessagesUseCase,
            sendMessageUseCase = sendMessageUseCase,
            uploadAttachmentUseCase = uploadAttachmentUseCase,
            sendVoiceMessageUseCase = sendVoiceMessageUseCase,
            editMessageUseCase = editMessageUseCase,
            forwardMessageUseCase = forwardMessageUseCase,
            pinMessageUseCase = pinMessageUseCase,
            saveMessageUseCase = saveMessageUseCase,
            markReadUseCase = markReadUseCase,
            reactToMessageUseCase = reactToMessageUseCase,
            deleteMessageUseCase = deleteMessageUseCase,
            reportMessageUseCase = reportMessageUseCase,
            manageConversationUseCase = manageConversationUseCase,
            sendTypingUseCase = sendTypingUseCase,
            observeIncomingMessagesUseCase = observeIncomingMessagesUseCase,
            observeTypingUseCase = observeTypingUseCase,
            observePresenceUseCase = observePresenceUseCase,
            webSocketManager = webSocketManager,
            tokenStorageManager = tokenStorageManager
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when loadChat intent dispatched, loads conversation metadata and messages`() = runTest {
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(501L, state.conversationId)
            assertEquals("Tanvir Hasan", state.participantName)
            assertEquals(1, state.messages.size)
            assertEquals("Hello! Are we still meeting?", state.messages.first().body)
        }
    }

    @Test
    fun `when sendTextMessage dispatched, replaces optimistic message with server message`() = runTest {
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        coEvery {
            sendMessageUseCase(
                conversationId = 501L,
                body = "Yes, see you at 5!",
                type = "text",
                clientMessageId = any(),
                replyToId = any(),
                mediaIds = any()
            )
        } returns flowOf(
            Result.Success(
                testMessage.copy(
                    id = 902L,
                    body = "Yes, see you at 5!",
                    deliveryStatus = DeliveryStatus.SENT,
                    isMine = true,
                    createdAt = 2000L
                )
            )
        )

        viewModel.handleIntent(ChatIntent.SendTextMessage("Yes, see you at 5!"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.messages.size)
            assertTrue(state.messages.any { it.id == 902L && it.body == "Yes, see you at 5!" })
        }
    }

    @Test
    fun `when reactMessage dispatched, toggles reaction optimistically`() = runTest {
        every { reactToMessageUseCase(901L, "❤️") } returns flowOf(Result.Success(Unit))
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ChatIntent.ReactMessage(901L, "❤️"))
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(1, viewModel.uiState.value.messages.first().reactions["❤️"])
        assertEquals("❤️", viewModel.uiState.value.messages.first().myReaction)
    }

    @Test
    fun `when deleteMessage for everyone dispatched, marks message deleted`() = runTest {
        every { deleteMessageUseCase(901L, true) } returns flowOf(Result.Success(Unit))
        viewModel.handleIntent(ChatIntent.LoadChat(501L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ChatIntent.DeleteMessage(901L, forEveryone = true))
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.messages.first().isDeleted)
    }
}
