package com.connectly.android.feature.auth.presentation.register

import com.connectly.android.core.model.User

sealed interface RegisterUiState {
    data object Idle : RegisterUiState
    data object Loading : RegisterUiState
    data class Success(val user: User) : RegisterUiState
    data class Error(val message: String) : RegisterUiState
}

sealed interface RegisterUiIntent {
    data class Register(
        val name: String,
        val email: String,
        val pass: String,
        val confirmPass: String,
        val termsAccepted: Boolean
    ) : RegisterUiIntent
    data object ClearError : RegisterUiIntent
}

sealed interface RegisterUiEffect {
    data object NavigateToHome : RegisterUiEffect
    data object NavigateToLogin : RegisterUiEffect
}
