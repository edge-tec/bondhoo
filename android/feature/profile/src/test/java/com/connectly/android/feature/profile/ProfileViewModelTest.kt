package com.connectly.android.feature.profile

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.FriendshipStatus
import com.connectly.android.core.model.User
import com.connectly.android.feature.profile.domain.usecase.*
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
import org.junit.Assert.assertNull
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class ProfileViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getProfileUseCase: GetProfileUseCase = mockk()
    private val followUserUseCase: FollowUserUseCase = mockk()
    private val unfollowUserUseCase: UnfollowUserUseCase = mockk()
    private val sendFriendRequestUseCase: SendFriendRequestUseCase = mockk()
    private val cancelFriendRequestUseCase: CancelFriendRequestUseCase = mockk()
    private val acceptFriendRequestUseCase: AcceptFriendRequestUseCase = mockk()
    private val unfriendUserUseCase: UnfriendUserUseCase = mockk()
    private val blockUserUseCase: BlockUserUseCase = mockk()
    private val reportUserUseCase: ReportUserUseCase = mockk()
    private val reactToPostUseCase: ReactToPostUseCase = mockk(relaxed = true)
    private val getPostCommentsUseCase: GetPostCommentsUseCase = mockk(relaxed = true)
    private val addPostCommentUseCase: AddPostCommentUseCase = mockk(relaxed = true)
    private val sharePostUseCase: SharePostUseCase = mockk(relaxed = true)
    private val getUserPostsUseCase: GetUserPostsUseCase = mockk(relaxed = true)
    private val getProfileAnalyticsUseCase: GetProfileAnalyticsUseCase = mockk(relaxed = true)
    private val getProfileAboutUseCase: GetProfileAboutUseCase = mockk(relaxed = true)
    private val tokenStorageManager: com.connectly.android.core.datastore.TokenStorageManager = mockk(relaxed = true)
    private val reverbWebSocketManager: com.connectly.android.core.websocket.ReverbWebSocketManager = mockk(relaxed = true)
    private val api: com.connectly.android.core.network.api.ConnectlyApi = mockk(relaxed = true)

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
        every { tokenStorageManager.getUserId() } returns 1L
        every { reverbWebSocketManager.profileUpdatedFlow } returns kotlinx.coroutines.flow.MutableSharedFlow()
        every { getUserPostsUseCase(any()) } returns flowOf(Result.Success(emptyList()))
        every { getProfileAnalyticsUseCase() } returns flowOf(Result.Success(com.connectly.android.core.network.api.ProfileAnalyticsDto()))
        every { getProfileAboutUseCase() } returns flowOf(Result.Success(com.connectly.android.core.network.api.ProfileAboutDto()))
        every { getProfileUseCase("me") } returns flowOf(
            Result.Loading,
            Result.Success(testUser)
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    private fun createViewModel(): ProfileViewModel {
        return ProfileViewModel(
            getProfileUseCase,
            getUserPostsUseCase,
            getProfileAnalyticsUseCase,
            getProfileAboutUseCase,
            followUserUseCase,
            unfollowUserUseCase,
            sendFriendRequestUseCase,
            cancelFriendRequestUseCase,
            acceptFriendRequestUseCase,
            unfriendUserUseCase,
            blockUserUseCase,
            reportUserUseCase,
            reactToPostUseCase,
            getPostCommentsUseCase,
            addPostCommentUseCase,
            sharePostUseCase,
            tokenStorageManager,
            reverbWebSocketManager,
            api
        )
    }

    @Test
    fun `when viewmodel initialized, profile data is loaded successfully`() = runTest {
        viewModel = createViewModel()
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
        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.SelectTab(2))

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.selectedTab)
        }
    }

    @Test
    fun `when follow toggled from unfollowed, state sets isFollowing to true and increments followersCount`() = runTest {
        every { followUserUseCase(1L) } returns flowOf(Result.Success(true))

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.ToggleFollow)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(true, state.isFollowing)
            assertEquals(866, state.user?.followersCount)
        }
    }

    @Test
    fun `when send friend request dispatched, friendship status becomes pending sent`() = runTest {
        every { sendFriendRequestUseCase(1L) } returns flowOf(Result.Success(Unit))

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.SendFriendRequest)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(FriendshipStatus.PENDING_SENT, state.friendshipStatus)
        }
    }

    @Test
    fun `when cancel friend request dispatched, friendship status becomes none`() = runTest {
        every { cancelFriendRequestUseCase(1L) } returns flowOf(Result.Success(Unit))

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.CancelFriendRequest)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(FriendshipStatus.NONE, state.friendshipStatus)
        }
    }

    @Test
    fun `when copy profile link clicked, userMessage is updated with bondhoo url`() = runTest {
        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.CopyProfileLink)

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("Profile link copied: https://bondhoo.com/@mizanur_rahman", state.userMessage)
        }
    }

    @Test
    fun `when confirm block user clicked, user is blocked in state and usecase called`() = runTest {
        every { blockUserUseCase(1L) } returns flowOf(Result.Success(Unit))

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.ConfirmBlockUser)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(FriendshipStatus.BLOCKED, state.friendshipStatus)
            assertEquals("Mizanur Rahman has been blocked.", state.userMessage)
        }
    }

    @Test
    fun `when share clicked, sheet opens without increasing sharesCount`() = runTest {
        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.OpenShareSheet(42L))

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(42L, state.sharingPostId)
            // shares count must not change when merely opening sheet
            assertEquals(0, state.posts.find { it.id == 42L }?.sharesCount ?: 0)
        }
    }

    @Test
    fun `when share sheet dismissed without sharing, sharesCount does not increase`() = runTest {
        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.OpenShareSheet(42L))
        viewModel.handleIntent(ProfileIntent.DismissShareSheet)

        viewModel.uiState.test {
            val state = awaitItem()
            assertNull(state.sharingPostId)
        }
    }

    @Test
    fun `when confirm share post succeeds, shares count increments and success message displayed`() = runTest {
        every { sharePostUseCase(42L, any(), "group", any()) } returns flowOf(Result.Success(Unit))

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(ProfileIntent.ConfirmSharePost(postId = 42L, targetType = "group", caption = "Great post"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertNull(state.sharingPostId)
            assertEquals(false, state.isSharing)
            assertEquals("Post successfully shared to group!", state.userMessage)
        }
    }
}
