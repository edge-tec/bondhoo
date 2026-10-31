package com.connectly.android.feature.auth.presentation.login

import com.connectly.android.core.model.User

sealed interface LoginUiState {
    data object Idle : LoginUiState
    data object Loading : LoginUiState
    data class Success(val user: User) : LoginUiState
    data class Error(val message: String) : LoginUiState
}

sealed interface LoginUiIntent {
    data class Login(val login: String, val pass: String) : LoginUiIntent
    data object ClearError : LoginUiIntent
    data object AuthenticateBiometrics : LoginUiIntent
}

sealed interface LoginUiEffect {
    data class ShowToast(val message: String) : LoginUiEffect
    data object NavigateToHome : LoginUiEffect
    data object NavigateToRegister : LoginUiEffect
    data object NavigateToForgotPassword : LoginUiEffect
    data object NavigateToPhoneOtp : LoginUiEffect
    data class NavigateTo2Fa(val sessionToken: String) : LoginUiEffect
}

