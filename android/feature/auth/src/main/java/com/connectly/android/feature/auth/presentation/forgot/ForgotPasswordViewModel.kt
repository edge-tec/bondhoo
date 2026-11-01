package com.connectly.android.feature.auth.presentation.forgot

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed interface ForgotPasswordUiState {
    data object Idle : ForgotPasswordUiState
    data object Loading : ForgotPasswordUiState
    data class Success(val message: String) : ForgotPasswordUiState
    data class Error(val message: String) : ForgotPasswordUiState
}

sealed interface ForgotPasswordUiIntent {
    data class SubmitEmail(val email: String) : ForgotPasswordUiIntent
    data object ClearError : ForgotPasswordUiIntent
}

sealed interface ForgotPasswordUiEffect {
    data class ShowToast(val message: String) : ForgotPasswordUiEffect
    data object NavigateToLogin : ForgotPasswordUiEffect
}

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow<ForgotPasswordUiState>(ForgotPasswordUiState.Idle)
    val uiState: StateFlow<ForgotPasswordUiState> = _uiState.asStateFlow()

    private val _effect = Channel<ForgotPasswordUiEffect>(Channel.BUFFERED)
    val effect: Flow<ForgotPasswordUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: ForgotPasswordUiIntent) {
        when (intent) {
            is ForgotPasswordUiIntent.SubmitEmail -> sendResetLink(intent.email)
            is ForgotPasswordUiIntent.ClearError -> _uiState.value = ForgotPasswordUiState.Idle
        }
    }

    private fun sendResetLink(email: String) {
        if (email.isBlank() || !email.contains("@")) {
            _uiState.value = ForgotPasswordUiState.Error("Please enter a valid email address.")
            return
        }

        viewModelScope.launch {
            _uiState.value = ForgotPasswordUiState.Loading
            try {
                val response = api.forgotPassword(com.connectly.android.core.network.api.ForgotPasswordRequest(email = email))
                if (response.isSuccessful) {
                    _uiState.value = ForgotPasswordUiState.Success("Password reset instructions have been sent to $email")
                    _effect.send(ForgotPasswordUiEffect.ShowToast("Reset link sent to your email."))
                } else {
                    _uiState.value = ForgotPasswordUiState.Error("Failed to send reset link. Please check the email.")
                }
            } catch (e: Exception) {
                _uiState.value = ForgotPasswordUiState.Error(e.localizedMessage ?: "Failed to send reset link.")
            }
        }
    }
}

