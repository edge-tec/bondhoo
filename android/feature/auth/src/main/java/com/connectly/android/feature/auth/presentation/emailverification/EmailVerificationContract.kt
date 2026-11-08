package com.connectly.android.feature.auth.presentation.emailverification

import com.connectly.android.core.model.User

sealed interface EmailVerificationUiState {
    data object Idle : EmailVerificationUiState
    data object Loading : EmailVerificationUiState
    data class Success(val user: User) : EmailVerificationUiState
    data class Error(val message: String) : EmailVerificationUiState
    data class CodeResent(val message: String) : EmailVerificationUiState
}

sealed interface EmailVerificationUiIntent {
    data class VerifyOtp(val email: String, val otp: String) : EmailVerificationUiIntent
    data class ResendOtp(val email: String) : EmailVerificationUiIntent
    data object ClearError : EmailVerificationUiIntent
}

sealed interface EmailVerificationUiEffect {
    data object NavigateToHome : EmailVerificationUiEffect
    data class ShowToast(val message: String) : EmailVerificationUiEffect
}

