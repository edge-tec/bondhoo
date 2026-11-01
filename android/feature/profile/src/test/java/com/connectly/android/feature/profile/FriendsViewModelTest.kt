package com.connectly.android.feature.profile

import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import com.connectly.android.feature.profile.presentation.friends.FriendsIntent
import com.connectly.android.feature.profile.presentation.friends.FriendsViewModel
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
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class FriendsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val profileRepository: ProfileRepository = mockk()
    private lateinit var viewModel: FriendsViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        viewModel = FriendsViewModel(profileRepository)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun initial_state_has_requests_and_friends() = runTest {
        val state = viewModel.uiState.value
        assertEquals("requests", state.selectedTab)
        assertFalse(state.isLoading)
        assertTrue(state.friendRequests.isNotEmpty())
        assertTrue(state.friendsList.isNotEmpty())
        assertTrue(state.suggestions.isNotEmpty())
    }

    @Test
    fun selectTab_updates_selectedTab_state() = runTest {
        viewModel.handleIntent(FriendsIntent.SelectTab("suggestions"))
        assertEquals("suggestions", viewModel.uiState.value.selectedTab)

        viewModel.handleIntent(FriendsIntent.SelectTab("friends"))
        assertEquals("friends", viewModel.uiState.value.selectedTab)
    }

    @Test
    fun searchQueryChanged_updates_query() = runTest {
        viewModel.handleIntent(FriendsIntent.SearchQueryChanged("Sadia"))
        assertEquals("Sadia", viewModel.uiState.value.searchQuery)
    }

    @Test
    fun acceptRequest_removes_from_requests_and_adds_to_friends() = runTest {
        val userId = 101L
        every { profileRepository.acceptFriendRequest(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.AcceptRequest(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.friendRequests.any { it.user.id == userId })
        assertTrue(state.friendsList.any { it.id == userId })
    }

    @Test
    fun deleteRequest_removes_from_requests() = runTest {
        val userId = 102L
        every { profileRepository.rejectFriendRequest(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.DeleteRequest(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.friendRequests.any { it.user.id == userId })
    }

    @Test
    fun addFriend_sends_request_and_removes_suggestion() = runTest {
        val userId = 301L
        every { profileRepository.sendFriendRequest(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.AddFriend(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.suggestions.any { it.user.id == userId })
    }

    @Test
    fun unfriend_removes_from_friends_list() = runTest {
        val userId = 201L
        viewModel.handleIntent(FriendsIntent.Unfriend(userId))

        val state = viewModel.uiState.value
        assertFalse(state.friendsList.any { it.id == userId })
    }
}
