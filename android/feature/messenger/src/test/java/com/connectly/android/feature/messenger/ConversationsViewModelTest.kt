package com.connectly.android.feature.messenger

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.User
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import com.connectly.android.feature.messenger.domain.usecase.CreateConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetConversationsUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetFriendsForMessagingUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetMessengerCallHistoryUseCase
import com.connectly.android.feature.messenger.domain.usecase.ManageConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.MarkReadUseCase
import com.connectly.android.feature.messenger.domain.usecase.ObservePresenceUseCase
import com.connectly.android.feature.messenger.domain.usecase.SearchMessagesUseCase
import com.connectly.android.feature.messenger.presentation.conversations.ConversationsEffect
import com.connectly.android.feature.messenger.presentation.conversations.ConversationsIntent
import com.connectly.android.feature.messenger.presentation.conversations.ConversationsViewModel
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
class ConversationsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getConversationsUseCase: GetConversationsUseCase = mockk()
    private val createConversationUseCase: CreateConversationUseCase = mockk()
    private val getFriendsUseCase: GetFriendsForMessagingUseCase = mockk()
    private val getCallHistoryUseCase: GetMessengerCallHistoryUseCase = mockk()
    private val manageConversationUseCase: ManageConversationUseCase = mockk(relaxed = true)
    private val markReadUseCase: MarkReadUseCase = mockk(relaxed = true)
    private val searchMessagesUseCase: SearchMessagesUseCase = mockk(relaxed = true)
    private val observePresenceUseCase: ObservePresenceUseCase = mockk()
    private val repository: MessengerRepository = mockk(relaxed = true)
    private val reverbWebSocketManager: com.connectly.android.core.websocket.ReverbWebSocketManager = mockk(relaxed = true)

    private lateinit var viewModel: ConversationsViewModel

    private val testConversation = Conversation(
        id = 501L,
        type = "direct",
        title = "Tanvir Hasan",
        otherUser = User(id = 51L, name = "Tanvir Hasan", username = "tanvir", isOnline = true),
        lastMessage = Message(
            id = 5001L,
            conversationId = 501L,
            senderId = 51L,
            senderName = "Tanvir Hasan",
            body = "Assalamu Alaikum!",
            type = "text",
            clientMessageId = "msg_5001"
        ),
        unreadCount = 2
    )

    private val testFriend = ActivePresenceFriend(
        id = 52L,
        name = "Arif Hossain",
        username = "arif",
        online = true
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { reverbWebSocketManager.conversationUpdatedFlow } returns kotlinx.coroutines.flow.MutableSharedFlow()
        every { reverbWebSocketManager.messageNewFlow } returns kotlinx.coroutines.flow.MutableSharedFlow()
        every { reverbWebSocketManager.conversationClearedFlow } returns kotlinx.coroutines.flow.MutableSharedFlow()
        every { getConversationsUseCase("all", null) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testConversation))
        )
        every { getFriendsUseCase(any()) } returns flowOf(Result.Success(listOf(testFriend)))
        every { observePresenceUseCase() } returns emptyFlow()
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    private fun buildViewModel(): ConversationsViewModel {
        return ConversationsViewModel(
            getConversationsUseCase = getConversationsUseCase,
            createConversationUseCase = createConversationUseCase,
            getFriendsUseCase = getFriendsUseCase,
            getCallHistoryUseCase = getCallHistoryUseCase,
            manageConversationUseCase = manageConversationUseCase,
            markReadUseCase = markReadUseCase,
            searchMessagesUseCase = searchMessagesUseCase,
            observePresenceUseCase = observePresenceUseCase,
            repository = repository,
            reverbWebSocketManager = reverbWebSocketManager
        )
    }

    @Test
    fun `when viewmodel initialized, conversations and active friends load successfully`() = runTest {
        viewModel = buildViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.conversations.size)
            assertEquals("Tanvir Hasan", state.conversations.first().displayTitle)
            assertEquals(2, state.conversations.first().unreadCount)
            assertEquals(1, state.activeFriends.size)
        }
    }

    @Test
    fun `when filter selected, updates selectedFilter and loads filtered conversations`() = runTest {
        every { getConversationsUseCase("unread", null) } returns flowOf(
            Result.Success(listOf(testConversation))
        )

        viewModel = buildViewModel()
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
        viewModel = buildViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ConversationsIntent.ToggleSearch)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.isSearchVisible)
        }
    }

    @Test
    fun `when openDirectChatWithFriend creates backend conversation, emits OpenChat effect`() = runTest {
        val createdConv = Conversation(
            id = 602L,
            type = "direct",
            title = "Arif Hossain",
            otherUser = User(id = 52L, name = "Arif Hossain", username = "arif")
        )
        every { createConversationUseCase(recipientId = 52L) } returns flowOf(Result.Success(createdConv))

        viewModel = buildViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.effects.test {
            viewModel.handleIntent(ConversationsIntent.OpenOrCreateDirectChat(friendId = testFriend.id))
            testDispatcher.scheduler.advanceUntilIdle()

            val effect = awaitItem()
            assertEquals(ConversationsEffect.OpenChat(602L), effect)
        }
    }

    @Test
    fun `when startGroupCallWithSelected dispatched, emits group call effect with selected friends`() = runTest {
        viewModel = buildViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ConversationsIntent.ToggleGroupMember(51L))
        viewModel.handleIntent(ConversationsIntent.ToggleGroupMember(52L))

        viewModel.effects.test {
            viewModel.handleIntent(ConversationsIntent.StartGroupCallFromSelection(isVideo = true))
            testDispatcher.scheduler.advanceUntilIdle()

            val effect = awaitItem() as ConversationsEffect.StartCall
            assertEquals(CallType.GROUP_VIDEO, effect.callType)
            assertEquals(listOf(51L, 52L), effect.receiverIds)
        }
    }
}
