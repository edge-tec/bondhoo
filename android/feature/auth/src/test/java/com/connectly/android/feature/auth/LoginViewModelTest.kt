package com.connectly.android.feature.auth

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import com.connectly.android.feature.auth.domain.usecase.CheckAuthStatusUseCase
import com.connectly.android.feature.auth.domain.usecase.LoginUseCase
import com.connectly.android.feature.auth.presentation.login.*
import io.mockk.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.*
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val loginUseCase: LoginUseCase = mockk()
    private val checkAuthStatusUseCase: CheckAuthStatusUseCase = mockk()

    private lateinit var viewModel: LoginViewModel

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { checkAuthStatusUseCase() } returns false
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when login successful, state transitions to Success and dispatches NavigateToHome effect`() = runTest {
        val testUser = User(
            id = 101L,
            name = "Mizanur Rahman",
            username = "mizanur",
            email = "mizan@example.com"
        )
        every { loginUseCase("mizanur", "password123") } returns flowOf(
            Result.Loading,
            Result.Success(testUser)
        )

        viewModel = LoginViewModel(loginUseCase, checkAuthStatusUseCase)

        viewModel.effect.test {
            viewModel.handleIntent(LoginUiIntent.Login("mizanur", "password123"))
            testDispatcher.scheduler.advanceUntilIdle()

            val effect = awaitItem()
            assertEquals(LoginUiEffect.NavigateToHome, effect)
        }

        assertEquals(LoginUiState.Success(testUser), viewModel.uiState.value)
    }

    @Test
    fun `when login credentials empty, state transitions to Error`() = runTest {
        viewModel = LoginViewModel(loginUseCase, checkAuthStatusUseCase)

        viewModel.handleIntent(LoginUiIntent.Login("", ""))
        testDispatcher.scheduler.advanceUntilIdle()

        assert(viewModel.uiState.value is LoginUiState.Error)
    }
}
