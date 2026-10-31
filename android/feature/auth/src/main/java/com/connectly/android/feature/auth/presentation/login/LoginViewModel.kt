package com.connectly.android.feature.auth.presentation.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.auth.domain.usecase.CheckAuthStatusUseCase
import com.connectly.android.feature.auth.domain.usecase.LoginUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val loginUseCase: LoginUseCase,
    private val checkAuthStatusUseCase: CheckAuthStatusUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    private val _effect = Channel<LoginUiEffect>(Channel.BUFFERED)
    val effect: Flow<LoginUiEffect> = _effect.receiveAsFlow()

    init {
        checkSession()
    }

    private fun checkSession() {
        if (checkAuthStatusUseCase()) {
            viewModelScope.launch {
                _effect.send(LoginUiEffect.NavigateToHome)
            }
        }
    }

    fun handleIntent(intent: LoginUiIntent) {
        when (intent) {
            is LoginUiIntent.Login -> executeLogin(intent.login, intent.pass)
            is LoginUiIntent.ClearError -> _uiState.value = LoginUiState.Idle
            is LoginUiIntent.AuthenticateBiometrics -> {
                // Triggered after biometrics success
                viewModelScope.launch {
                    _effect.send(LoginUiEffect.NavigateToHome)
                }
            }
        }
    }

    private fun executeLogin(login: String, pass: String) {
        if (login.isBlank() || pass.isBlank()) {
            _uiState.value = LoginUiState.Error("Please enter your email/username and password.")
            return
        }

        viewModelScope.launch {
            loginUseCase(login, pass).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = LoginUiState.Loading
                    is Result.Success -> {
                        _uiState.value = LoginUiState.Success(result.data)
                        _effect.send(LoginUiEffect.NavigateToHome)
                    }
                    is Result.Error -> {
                        _uiState.value = LoginUiState.Error(result.message ?: "Authentication failed.")
                    }
                }
            }
        }
    }
}
