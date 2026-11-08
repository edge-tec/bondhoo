package com.connectly.android.feature.auth.presentation.emailverification

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.auth.domain.usecase.ResendEmailOtpUseCase
import com.connectly.android.feature.auth.domain.usecase.VerifyEmailOtpUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class EmailVerificationViewModel @Inject constructor(
    private val verifyEmailOtpUseCase: VerifyEmailOtpUseCase,
    private val resendEmailOtpUseCase: ResendEmailOtpUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<EmailVerificationUiState>(EmailVerificationUiState.Idle)
    val uiState: StateFlow<EmailVerificationUiState> = _uiState.asStateFlow()

    private val _effect = Channel<EmailVerificationUiEffect>(Channel.BUFFERED)
    val effect: Flow<EmailVerificationUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: EmailVerificationUiIntent) {
        when (intent) {
            is EmailVerificationUiIntent.VerifyOtp -> executeVerifyOtp(intent.email, intent.otp)
            is EmailVerificationUiIntent.ResendOtp -> executeResendOtp(intent.email)
            is EmailVerificationUiIntent.ClearError -> _uiState.value = EmailVerificationUiState.Idle
        }
    }

    private fun executeVerifyOtp(email: String, otp: String) {
        val cleanOtp = otp.trim()
        if (cleanOtp.length != 6) {
            _uiState.value = EmailVerificationUiState.Error("Please enter the 6-digit verification code sent to your email.")
            return
        }

        viewModelScope.launch {
            verifyEmailOtpUseCase(email, cleanOtp).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = EmailVerificationUiState.Loading
                    is Result.Success -> {
                        _uiState.value = EmailVerificationUiState.Success(result.data)
                        _effect.send(EmailVerificationUiEffect.NavigateToHome)
                    }
                    is Result.Error -> {
                        _uiState.value = EmailVerificationUiState.Error(result.message ?: "Invalid or expired OTP code.")
                    }
                }
            }
        }
    }

    private fun executeResendOtp(email: String) {
        viewModelScope.launch {
            resendEmailOtpUseCase(email).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = EmailVerificationUiState.Loading
                    is Result.Success -> {
                        _uiState.value = EmailVerificationUiState.CodeResent(result.data)
                        _effect.send(EmailVerificationUiEffect.ShowToast(result.data))
                    }
                    is Result.Error -> {
                        _uiState.value = EmailVerificationUiState.Error(result.message ?: "Failed to resend verification OTP.")
                    }
                }
            }
        }
    }
}

