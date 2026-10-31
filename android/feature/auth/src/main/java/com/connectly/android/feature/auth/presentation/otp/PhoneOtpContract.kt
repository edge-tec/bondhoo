package com.connectly.android.feature.auth.presentation.otp

import com.connectly.android.core.model.User

sealed interface PhoneOtpUiState {
    data object Idle : PhoneOtpUiState
    data object OtpSent : PhoneOtpUiState
    data object Loading : PhoneOtpUiState
    data class Success(val user: User) : PhoneOtpUiState
    data class Error(val message: String) : PhoneOtpUiState
}

sealed interface PhoneOtpUiIntent {
    data class SendOtp(val phone: String) : PhoneOtpUiIntent
    data class VerifyOtp(val phone: String, val otp: String) : PhoneOtpUiIntent
    data object Reset : PhoneOtpUiIntent
}

sealed interface PhoneOtpUiEffect {
    data object NavigateToHome : PhoneOtpUiEffect
    data class ShowToast(val message: String) : PhoneOtpUiEffect
}
