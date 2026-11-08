package com.connectly.android.feature.profile

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.ApiResponse
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.FriendRequestDto
import com.connectly.android.core.network.api.UserDto
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import com.connectly.android.feature.profile.presentation.friends.FriendsIntent
import com.connectly.android.feature.profile.presentation.friends.FriendsViewModel
import io.mockk.coEvery
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
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class FriendsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val profileRepository: ProfileRepository = mockk(relaxed = true)
    private val api: ConnectlyApi = mockk(relaxed = true)
    private lateinit var viewModel: FriendsViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        coEvery { api.getFriends(any(), any(), any()) } returns Response.success(
            ApiResponse(
                data = listOf(
                    UserDto(id = 201L, name = "Sadia Islam", username = "sadia_islam")
                )
            )
        )
        coEvery { api.getFriendRequests(any()) } returns Response.success(
            ApiResponse(
                data = listOf(
                    FriendRequestDto(
                        id = 1L,
                        user = UserDto(id = 101L, name = "Tania Akter", username = "tania_akter")
                    )
                )
            )
        )
        coEvery { api.getSuggestedFriends(any()) } returns Response.success(
            ApiResponse(
                data = listOf(
                    UserDto(id = 301L, name = "Imran Hasan", username = "imran_h")
                )
            )
        )
        viewModel = FriendsViewModel(profileRepository, api)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun initial_state_has_requests_and_friends() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value
        assertEquals("requests", state.selectedTab)
        assertFalse(state.isLoading)
        assertTrue(state.friendRequests.isNotEmpty())
        assertTrue(state.friendsList.isNotEmpty())
        assertTrue(state.suggestions.isNotEmpty())
    }

    @Test
    fun selectTab_updates_selectedTab_state() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        viewModel.handleIntent(FriendsIntent.SelectTab("suggestions"))
        assertEquals("suggestions", viewModel.uiState.value.selectedTab)

        viewModel.handleIntent(FriendsIntent.SelectTab("friends"))
        assertEquals("friends", viewModel.uiState.value.selectedTab)
    }

    @Test
    fun searchQueryChanged_updates_query() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        viewModel.handleIntent(FriendsIntent.SearchQueryChanged("Sadia"))
        assertEquals("Sadia", viewModel.uiState.value.searchQuery)
    }

    @Test
    fun acceptRequest_removes_from_requests_and_adds_to_friends() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
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
        testDispatcher.scheduler.advanceUntilIdle()
        val userId = 101L
        every { profileRepository.rejectFriendRequest(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.DeleteRequest(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.friendRequests.any { it.user.id == userId })
    }

    @Test
    fun addFriend_sends_request_and_removes_suggestion() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val userId = 301L
        every { profileRepository.sendFriendRequest(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.AddFriend(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.suggestions.any { it.user.id == userId })
    }

    @Test
    fun unfriend_removes_from_friends_list() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val userId = 201L
        every { profileRepository.unfriendUser(userId) } returns flowOf(Result.Success(Unit))

        viewModel.handleIntent(FriendsIntent.Unfriend(userId))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.friendsList.any { it.id == userId })
    }
}

