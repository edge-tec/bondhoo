package com.connectly.android.feature.auth.presentation.otp

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.auth.domain.usecase.SendPhoneOtpUseCase
import com.connectly.android.feature.auth.domain.usecase.VerifyPhoneOtpUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PhoneOtpViewModel @Inject constructor(
    private val sendPhoneOtpUseCase: SendPhoneOtpUseCase,
    private val verifyPhoneOtpUseCase: VerifyPhoneOtpUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<PhoneOtpUiState>(PhoneOtpUiState.Idle)
    val uiState: StateFlow<PhoneOtpUiState> = _uiState.asStateFlow()

    private val _effect = Channel<PhoneOtpUiEffect>(Channel.BUFFERED)
    val effect: Flow<PhoneOtpUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: PhoneOtpUiIntent) {
        when (intent) {
            is PhoneOtpUiIntent.SendOtp -> executeSendOtp(intent.phone)
            is PhoneOtpUiIntent.VerifyOtp -> executeVerifyOtp(intent.phone, intent.otp)
            is PhoneOtpUiIntent.Reset -> _uiState.value = PhoneOtpUiState.Idle
        }
    }

    private fun executeSendOtp(phone: String) {
        if (phone.length < 10) {
            _uiState.value = PhoneOtpUiState.Error("Please enter a valid mobile number.")
            return
        }

        viewModelScope.launch {
            sendPhoneOtpUseCase(phone).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = PhoneOtpUiState.Loading
                    is Result.Success -> {
                        _uiState.value = PhoneOtpUiState.OtpSent
                        _effect.send(PhoneOtpUiEffect.ShowToast(result.data))
                    }
                    is Result.Error -> {
                        _uiState.value = PhoneOtpUiState.Error(result.message ?: "Failed to send OTP.")
                    }
                }
            }
        }
    }

    private fun executeVerifyOtp(phone: String, otp: String) {
        if (otp.length != 4) {
            _uiState.value = PhoneOtpUiState.Error("Please enter the 4-digit OTP code.")
            return
        }

        viewModelScope.launch {
            verifyPhoneOtpUseCase(phone, otp).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = PhoneOtpUiState.Loading
                    is Result.Success -> {
                        _uiState.value = PhoneOtpUiState.Success(result.data)
                        _effect.send(PhoneOtpUiEffect.NavigateToHome)
                    }
                    is Result.Error -> {
                        _uiState.value = PhoneOtpUiState.Error(result.message ?: "Invalid OTP code.")
                    }
                }
            }
        }
    }
}
