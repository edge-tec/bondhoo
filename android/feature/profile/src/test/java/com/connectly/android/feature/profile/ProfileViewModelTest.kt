package com.connectly.android.feature.profile

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import com.connectly.android.feature.profile.domain.usecase.FollowUserUseCase
import com.connectly.android.feature.profile.domain.usecase.GetProfileUseCase
import com.connectly.android.feature.profile.domain.usecase.SendFriendRequestUseCase
import com.connectly.android.feature.profile.domain.usecase.UnfollowUserUseCase
import com.connectly.android.feature.profile.presentation.profile.ProfileIntent
import com.connectly.android.feature.profile.presentation.profile.ProfileViewModel
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
import org.junit.Assert.assertNotNull
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class ProfileViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getProfileUseCase: GetProfileUseCase = mockk()
    private val followUserUseCase: FollowUserUseCase = mockk()
    private val unfollowUserUseCase: UnfollowUserUseCase = mockk()
    private val sendFriendRequestUseCase: SendFriendRequestUseCase = mockk()

    private lateinit var viewModel: ProfileViewModel

    private val testUser = User(
        id = 1L,
        name = "Mizanur Rahman",
        username = "mizanur_rahman",
        bio = "Dream Big • Work Hard • Be Happy",
        occupation = "Lead Engineer",
        location = "Dhaka, Bangladesh",
        isVerified = true,
        friendsCount = 2500,
        followersCount = 865,
        followingCount = 128
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getProfileUseCase("me") } returns flowOf(
            Result.Loading,
            Result.Success(testUser)
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, profile data is loaded successfully`() = runTest {
        viewModel = ProfileViewModel(
            getProfileUseCase,
            followUserUseCase,
            unfollowUserUseCase,
            sendFriendRequestUseCase
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertNotNull(state.user)
            assertEquals("Mizanur Rahman", state.user?.name)
            assertEquals(2500, state.user?.friendsCount)
            assertEquals(865, state.user?.followersCount)
        }
    }

    @Test
    fun `when tab selected, state updates tab index correctly`() = runTest {
        viewModel = ProfileViewModel(
            getProfileUseCase,
            followUserUseCase,
            unfollowUserUseCase,
            sendFriendRequestUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.SelectTab(2))

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.selectedTab)
        }
    }

    @Test
    fun `when follow toggled from unfollowed, state sets isFollowing to true`() = runTest {
        every { followUserUseCase(1L) } returns flowOf(Result.Success(true))

        viewModel = ProfileViewModel(
            getProfileUseCase,
            followUserUseCase,
            unfollowUserUseCase,
            sendFriendRequestUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.ToggleFollow)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(true, state.isFollowing)
        }
    }
}
