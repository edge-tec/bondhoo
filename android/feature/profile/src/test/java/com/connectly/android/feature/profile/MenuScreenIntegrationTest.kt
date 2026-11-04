package com.connectly.android.feature.profile

import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.settings.SettingsViewModel
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.every
import io.mockk.mockk
import io.mockk.verify
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
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
class MenuScreenIntegrationTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val userDao: UserDao = mockk(relaxed = true)
    private val tokenStorageManager: TokenStorageManager = mockk(relaxed = true)
    private lateinit var viewModel: SettingsViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { tokenStorageManager.getUserId() } returns 101L
        every { userDao.getUserById(101L) } returns flowOf(
            UserEntity(
                id = 101L,
                name = "Mizanur Rahman",
                username = "mizan2129",
                email = "mm923310@gmail.com",
                avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                isVerified = true
            )
        )
        viewModel = SettingsViewModel(api, userDao, tokenStorageManager)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testMenuProfileDataLoadedFromCacheAndApi() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals(101L, state.userId)
        assertEquals("Mizanur Rahman", state.userName)
        assertEquals("mizan2129", state.userUsername)
        assertEquals("mm923310@gmail.com", state.userEmail)
        assertTrue(state.isVerified)
        assertFalse(state.isLoggedOut)
    }

    @Test
    fun testSecureLogoutWipesTokensAndSetsLoggedOut() = runTest {
        coEvery { api.logout() } returns Response.success(Unit)

        viewModel.confirmLogout(logoutAllDevices = false)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify(exactly = 1) { api.logout() }
        verify(exactly = 1) { tokenStorageManager.clearAll() }

        val state = viewModel.uiState.value
        assertFalse(state.isLoggingOut)
        assertTrue(state.isLoggedOut)
    }

    @Test
    fun testLogoutAllDevicesCallsApiAndWipesTokens() = runTest {
        coEvery { api.logoutAllSessions() } returns Response.success(ApiResponse(data = Unit, message = "Logged out all"))

        viewModel.confirmLogout(logoutAllDevices = true)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify(exactly = 1) { api.logoutAllSessions() }
        verify(exactly = 1) { tokenStorageManager.clearAll() }

        val state = viewModel.uiState.value
        assertFalse(state.isLoggingOut)
        assertTrue(state.isLoggedOut)
    }

    @Test
    fun testOfflineOrNetworkFailureDuringOverviewLoadPreservesCache() = runTest {
        coEvery { api.getSettingsOverview() } throws Exception("Network timeout")

        viewModel.loadSettingsOverview()
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.isLoading)
        // Cached profile information remains intact
        assertEquals("Mizanur Rahman", state.userName)
        assertEquals("mizan2129", state.userUsername)
    }
}

