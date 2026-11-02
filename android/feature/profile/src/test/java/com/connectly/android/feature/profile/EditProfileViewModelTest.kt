package com.connectly.android.feature.profile

import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.edit.AccountDialogType
import com.connectly.android.feature.profile.presentation.edit.EditProfileIntent
import com.connectly.android.feature.profile.presentation.edit.EditProfileViewModel
import io.mockk.coEvery
import io.mockk.coVerify
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
class EditProfileViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val userDao: UserDao = mockk(relaxed = true)
    private val tokenStorageManager: TokenStorageManager = mockk(relaxed = true)
    private lateinit var viewModel: EditProfileViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { tokenStorageManager.getUserId() } returns 101L
        every { userDao.getUserById(101L) } returns flowOf(
            UserEntity(
                id = 101L,
                name = "John Doe",
                username = "johndoe",
                email = "john@example.com",
                bio = "Software Architect",
                avatar = "https://example.com/avatar.jpg",
                isVerified = true
            )
        )
        viewModel = EditProfileViewModel(api, userDao, tokenStorageManager)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialProfileLoadFromRoom() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals("John Doe", state.name)
        assertEquals("johndoe", state.username)
        assertEquals("john@example.com", state.email)
        assertEquals("Software Architect", state.bio)
        assertTrue(state.isIdentityVerified)
    }

    @Test
    fun testValidationForEmptyName() = runTest {
        viewModel.handleIntent(EditProfileIntent.NameChanged(""))
        viewModel.handleIntent(EditProfileIntent.SaveProfile)
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Name cannot be empty.", state.errorMessage)
    }

    @Test
    fun testValidationForInvalidUsername() = runTest {
        viewModel.handleIntent(EditProfileIntent.NameChanged("Valid Name"))
        viewModel.handleIntent(EditProfileIntent.UsernameChanged("invalid username with spaces!"))
        viewModel.handleIntent(EditProfileIntent.SaveProfile)
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Username can only contain letters, numbers, dashes and underscores.", state.errorMessage)
    }

    @Test
    fun testChangePasswordValidationAndSuccess() = runTest {
        // Validation: mismatch
        viewModel.handleIntent(EditProfileIntent.ChangePassword("oldpass", "newpass123", "different"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("New passwords do not match.", viewModel.uiState.value.errorMessage)

        // Success call
        coEvery { api.updatePassword(any()) } returns Response.success(ApiResponse(success = true, message = "Password updated"))

        viewModel.handleIntent(EditProfileIntent.ChangePassword("oldpass", "newpass123", "newpass123"))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Password updated successfully.", state.successMessage)
        assertNull(state.activeDialog)
    }

    @Test
    fun testDeactivateAccountSuccess() = runTest {
        coEvery { api.deactivateAccount(any()) } returns Response.success(ApiResponse(success = true, message = "Deactivated"))

        viewModel.handleIntent(EditProfileIntent.DeactivateAccount("secretpassword", "Taking a break"))
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { tokenStorageManager.clearAll() }
        assertTrue(viewModel.uiState.value.isTerminated)
    }

    @Test
    fun testDeleteAccountValidationAndSuccess() = runTest {
        // Validation: confirmation must be DELETE
        viewModel.handleIntent(EditProfileIntent.DeleteAccount("secretpassword", "NO", "Goodbye"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("Please type DELETE to confirm permanent deletion.", viewModel.uiState.value.errorMessage)

        // Success
        coEvery { api.deleteAccount(any()) } returns Response.success(ApiResponse(success = true, message = "Deleted"))

        viewModel.handleIntent(EditProfileIntent.DeleteAccount("secretpassword", "DELETE", "Goodbye"))
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { tokenStorageManager.clearAll() }
        assertTrue(viewModel.uiState.value.isTerminated)
    }

    @Test
    fun testDialogStateLifecycle() = runTest {
        viewModel.handleIntent(EditProfileIntent.OpenDialog(AccountDialogType.CHANGE_PASSWORD))
        assertEquals(AccountDialogType.CHANGE_PASSWORD, viewModel.uiState.value.activeDialog)

        viewModel.handleIntent(EditProfileIntent.DismissDialog)
        assertNull(viewModel.uiState.value.activeDialog)
    }
}

