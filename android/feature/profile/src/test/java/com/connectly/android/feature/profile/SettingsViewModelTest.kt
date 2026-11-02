package com.connectly.android.feature.profile

import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.settings.SettingsDialogType
import com.connectly.android.feature.profile.presentation.settings.SettingsViewModel
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
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class SettingsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val userDao: UserDao = mockk(relaxed = true)
    private val tokenStorageManager: TokenStorageManager = mockk(relaxed = true)
    private lateinit var viewModel: SettingsViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { tokenStorageManager.getUserId() } returns 10L
        every { userDao.getUserById(10L) } returns flowOf(
            UserEntity(
                id = 10L,
                name = "Mizanur Rahman",
                username = "mizan_coder",
                email = "mm923310@gmail.com",
                avatar = "https://bondhoo.com/storage/avatars/user.jpg",
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
    fun testInitialUserDataLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value
        assertEquals("Mizanur Rahman", state.userName)
        assertEquals("mizan_coder", state.userUsername)
        assertEquals("mm923310@gmail.com", state.userEmail)
        assertTrue(state.isVerified)
    }

    @Test
    fun testSearchQueryFilter() = runTest {
        viewModel.onSearchQueryChanged("Privacy")
        assertEquals("Privacy", viewModel.uiState.value.searchQuery)
    }

    @Test
    fun testDialogOpenAndDismiss() = runTest {
        viewModel.openDialog(SettingsDialogType.THEME_PICKER)
        assertEquals(SettingsDialogType.THEME_PICKER, viewModel.uiState.value.activeDialog)

        viewModel.dismissDialog()
        assertNull(viewModel.uiState.value.activeDialog)
    }

    @Test
    fun testThemeSelection() = runTest {
        viewModel.setTheme("Dark")
        assertEquals("Dark", viewModel.uiState.value.selectedTheme)
        assertNull(viewModel.uiState.value.activeDialog)
    }

    @Test
    fun testLanguageSelection() = runTest {
        viewModel.setLanguage("বাংলা")
        assertEquals("বাংলা", viewModel.uiState.value.selectedLanguage)
        assertNull(viewModel.uiState.value.activeDialog)
    }

    @Test
    fun testAccessibilityToggles() = runTest {
        viewModel.toggleLargeText()
        assertTrue(viewModel.uiState.value.largeTextEnabled)

        viewModel.toggleHighContrast()
        assertTrue(viewModel.uiState.value.highContrastEnabled)

        viewModel.toggleReduceMotion()
        assertTrue(viewModel.uiState.value.reduceMotionEnabled)
    }

    @Test
    fun testLogoutClearsCredentials() = runTest {
        coEvery { api.logout() } returns Response.success(ApiResponse(status = "success", message = "Logged out"))

        viewModel.confirmLogout()
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.isLoggedOut)
        assertFalse(viewModel.uiState.value.isLoggingOut)
    }
}

