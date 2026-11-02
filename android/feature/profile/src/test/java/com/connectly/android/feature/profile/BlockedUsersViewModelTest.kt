package com.connectly.android.feature.profile

import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.blocking.BlockedTab
import com.connectly.android.feature.profile.presentation.blocking.BlockedUsersIntent
import com.connectly.android.feature.profile.presentation.blocking.BlockedUsersViewModel
import com.connectly.android.feature.profile.presentation.blocking.RestrictionActionType
import io.mockk.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class BlockedUsersViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private lateinit var viewModel: BlockedUsersViewModel

    private val blockedUser = RestrictedUserDto(id = 101L, name = "Bad Actor", username = "badactor")
    private val restrictedUser = RestrictedUserDto(id = 202L, name = "Restricted Friend", username = "restricted1")
    private val mutedUser = RestrictedUserDto(id = 303L, name = "Muted Person", username = "mutedguy")

    private val dummyResponse = BlockedUsersResponseDto(
        blocked_users = listOf(blockedUser),
        restricted_users = listOf(restrictedUser),
        muted_users = listOf(mutedUser),
        total = 1
    )

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        coEvery { api.getBlockedAndRestrictedUsers() } returns Response.success(ApiResponse(success = true, data = dummyResponse))
        coEvery { api.unblockUser(any()) } returns Response.success(ApiResponse(success = true, data = Unit))
        coEvery { api.unrestrictUser(any()) } returns Response.success(ApiResponse(success = true, data = Unit))
        coEvery { api.unmuteUser(any()) } returns Response.success(ApiResponse(success = true, data = Unit))

        viewModel = BlockedUsersViewModel(api)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialBlockedUsersLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals(1, state.blockedUsers.size)
        assertEquals(1, state.restrictedUsers.size)
        assertEquals(1, state.mutedUsers.size)
        assertEquals(BlockedTab.BLOCKED, state.selectedTab)
        assertEquals(1, state.filteredList.size)
        assertEquals("Bad Actor", state.filteredList.first().name)
    }

    @Test
    fun testTabSwitchingAndSearchFiltering() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        // Switch to RESTRICTED
        viewModel.handleIntent(BlockedUsersIntent.SelectTab(BlockedTab.RESTRICTED))
        assertEquals(BlockedTab.RESTRICTED, viewModel.uiState.value.selectedTab)
        assertEquals(1, viewModel.uiState.value.filteredList.size)
        assertEquals("Restricted Friend", viewModel.uiState.value.filteredList.first().name)

        // Search with match
        viewModel.handleIntent(BlockedUsersIntent.SearchQueryChanged("friend"))
        assertEquals(1, viewModel.uiState.value.filteredList.size)

        // Search with no match
        viewModel.handleIntent(BlockedUsersIntent.SearchQueryChanged("unknown_person"))
        assertEquals(0, viewModel.uiState.value.filteredList.size)
    }

    @Test
    fun testUnblockActionFlow() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        // 1. Request unblock dialog
        viewModel.handleIntent(BlockedUsersIntent.RequestAction(blockedUser, RestrictionActionType.UNBLOCK))
        assertEquals(blockedUser, viewModel.uiState.value.pendingActionUser)
        assertEquals(RestrictionActionType.UNBLOCK, viewModel.uiState.value.pendingActionType)

        // 2. Execute confirmed action
        viewModel.handleIntent(BlockedUsersIntent.ExecuteConfirmedAction)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.unblockUser(101L) }
        assertTrue(viewModel.uiState.value.blockedUsers.isEmpty())
        assertNull(viewModel.uiState.value.pendingActionUser)
        assertEquals("Bad Actor has been unblocked.", viewModel.uiState.value.successMessage)
    }

    @Test
    fun testUnrestrictActionFlow() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(BlockedUsersIntent.RequestAction(restrictedUser, RestrictionActionType.UNRESTRICT))
        viewModel.handleIntent(BlockedUsersIntent.ExecuteConfirmedAction)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.unrestrictUser(202L) }
        assertTrue(viewModel.uiState.value.restrictedUsers.isEmpty())
        assertEquals("Restricted Friend is no longer restricted.", viewModel.uiState.value.successMessage)
    }

    @Test
    fun testUnmuteActionFlow() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(BlockedUsersIntent.RequestAction(mutedUser, RestrictionActionType.UNMUTE))
        viewModel.handleIntent(BlockedUsersIntent.ExecuteConfirmedAction)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.unmuteUser(303L) }
        assertTrue(viewModel.uiState.value.mutedUsers.isEmpty())
        assertEquals("Muted Person has been unmuted.", viewModel.uiState.value.successMessage)
    }
}

