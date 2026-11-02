package com.connectly.android.feature.profile

import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.privacy.PrivacySettingsIntent
import com.connectly.android.feature.profile.presentation.privacy.PrivacySettingsViewModel
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.every
import io.mockk.mockk
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
class PrivacySettingsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val tokenStorageManager: TokenStorageManager = mockk(relaxed = true)
    private lateinit var viewModel: PrivacySettingsViewModel

    private val dummyPrivacy = SettingsPrivacyDto(
        who_can_see_posts = "friends",
        who_can_send_friend_requests = "friends_of_friends",
        who_can_follow = "friends",
        who_can_message = "friends",
        who_can_see_friends = "only_me",
        story_visibility = "friends",
        is_profile_locked = true,
        has_avatar_guard = true,
        find_by_email = "friends",
        find_by_phone = "friends",
        profile_visibility = "friends",
        search_engine_indexing = false,
        show_online_status = true,
        show_last_seen = "friends",
        read_receipts_enabled = true,
        two_factor_enabled = true
    )

    private val dummySessions = listOf(
        UserSessionDto(id = 1L, device_name = "Pixel 8 Pro", os = "Android 15", is_current = true),
        UserSessionDto(id = 2L, device_name = "Chrome on macOS", os = "macOS", is_current = false)
    )

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        coEvery { api.getSettingsPrivacy() } returns Response.success(ApiResponse(success = true, data = dummyPrivacy))
        coEvery { api.getActiveSessions() } returns Response.success(ApiResponse(success = true, data = dummySessions))
        coEvery { api.updateSettingsPrivacy(any()) } returns Response.success(ApiResponse(success = true, data = dummyPrivacy))
        coEvery { api.toggleTwoFactor(any()) } returns Response.success(ApiResponse(success = true, data = Toggle2faDto(true)))

        viewModel = PrivacySettingsViewModel(api, tokenStorageManager)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialPrivacyAndSessionsLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals("friends", state.profileVisibility)
        assertTrue(state.isProfileLocked)
        assertTrue(state.hasAvatarGuard)
        assertEquals("friends", state.whoCanSeePosts)
        assertEquals("only_me", state.whoCanSeeFriends)
        assertTrue(state.twoFactorEnabled)
        assertEquals(2, state.sessions.size)
    }

    @Test
    fun testProfilePrivacyToggles() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(PrivacySettingsIntent.ProfileLockedToggled(false))
        assertFalse(viewModel.uiState.value.isProfileLocked)

        viewModel.handleIntent(PrivacySettingsIntent.AvatarGuardToggled(false))
        assertFalse(viewModel.uiState.value.hasAvatarGuard)

        viewModel.handleIntent(PrivacySettingsIntent.WhoCanSeePostsChanged("public"))
        assertEquals("public", viewModel.uiState.value.whoCanSeePosts)
    }

    @Test
    fun testTwoFactorToggle() = runTest {
        coEvery { api.toggleTwoFactor(Toggle2faRequest(false)) } returns Response.success(ApiResponse(success = true, data = Toggle2faDto(false)))

        viewModel.handleIntent(PrivacySettingsIntent.TwoFactorToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()

        assertFalse(viewModel.uiState.value.twoFactorEnabled)
        assertEquals("Two-factor authentication disabled.", viewModel.uiState.value.successMessage)
    }

    @Test
    fun testRevokeSession() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val sessionToRevoke = dummySessions[1] // Chrome on macOS

        coEvery { api.revokeSession(sessionToRevoke.id) } returns Response.success(ApiResponse(success = true))

        viewModel.handleIntent(PrivacySettingsIntent.ConfirmRevokeSession(sessionToRevoke))
        assertEquals(sessionToRevoke, viewModel.uiState.value.sessionToRevoke)

        viewModel.handleIntent(PrivacySettingsIntent.ExecuteRevokeSession)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.revokeSession(sessionToRevoke.id) }
        assertEquals(1, viewModel.uiState.value.sessions.size)
        assertTrue(viewModel.uiState.value.sessions.all { it.id != sessionToRevoke.id })
    }

    @Test
    fun testLogoutOtherSessions() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        coEvery { api.logoutAllSessions() } returns Response.success(ApiResponse(success = true))

        viewModel.handleIntent(PrivacySettingsIntent.ConfirmLogoutOthers)
        assertTrue(viewModel.uiState.value.showLogoutOthersDialog)

        viewModel.handleIntent(PrivacySettingsIntent.ExecuteLogoutOthers)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.logoutAllSessions() }
        assertEquals(1, viewModel.uiState.value.sessions.size)
        assertTrue(viewModel.uiState.value.sessions.first().is_current)
    }

    @Test
    fun testChangePasswordValidation() = runTest {
        // Empty current password
        viewModel.handleIntent(PrivacySettingsIntent.ChangePassword("", "newPass123", "newPass123"))
        assertEquals("Current password is required.", viewModel.uiState.value.errorMessage)

        // Short password
        viewModel.handleIntent(PrivacySettingsIntent.ChangePassword("oldPass123", "short", "short"))
        assertEquals("New password must be at least 8 characters.", viewModel.uiState.value.errorMessage)

        // Password mismatch
        viewModel.handleIntent(PrivacySettingsIntent.ChangePassword("oldPass123", "newPass123", "different"))
        assertEquals("New passwords do not match.", viewModel.uiState.value.errorMessage)
    }
}
