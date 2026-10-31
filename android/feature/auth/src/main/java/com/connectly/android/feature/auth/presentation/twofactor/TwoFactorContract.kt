package com.connectly.android.feature.auth.presentation.twofactor

import com.connectly.android.core.model.User

sealed interface TwoFactorUiState {
    data object Idle : TwoFactorUiState
    data object Loading : TwoFactorUiState
    data class Success(val user: User) : TwoFactorUiState
    data class Error(val message: String) : TwoFactorUiState
}

sealed interface TwoFactorUiIntent {
    data class Verify(val code: String, val sessionToken: String) : TwoFactorUiIntent
    data object ResendCode : TwoFactorUiIntent
}

sealed interface TwoFactorUiEffect {
    data object NavigateToHome : TwoFactorUiEffect
    data class ShowToast(val message: String) : TwoFactorUiEffect
}

