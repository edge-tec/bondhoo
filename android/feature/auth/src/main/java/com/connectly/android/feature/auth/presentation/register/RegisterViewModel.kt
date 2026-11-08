package com.connectly.android.feature.auth.presentation.register

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.auth.domain.usecase.RegisterUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val registerUseCase: RegisterUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<RegisterUiState>(RegisterUiState.Idle)
    val uiState: StateFlow<RegisterUiState> = _uiState.asStateFlow()

    private val _effect = Channel<RegisterUiEffect>(Channel.BUFFERED)
    val effect: Flow<RegisterUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: RegisterUiIntent) {
        when (intent) {
            is RegisterUiIntent.Register -> executeRegister(
                intent.name,
                intent.email,
                intent.phone,
                intent.birthDate,
                intent.gender,
                intent.pass,
                intent.confirmPass,
                intent.termsAccepted
            )
            is RegisterUiIntent.ClearError -> _uiState.value = RegisterUiState.Idle
        }
    }

    private fun executeRegister(
        name: String,
        email: String,
        phone: String,
        birthDate: String,
        gender: String,
        pass: String,
        confirmPass: String,
        termsAccepted: Boolean
    ) {
        if (name.isBlank() || email.isBlank() || phone.isBlank() || birthDate.isBlank() || gender.isBlank() || pass.isBlank()) {
            _uiState.value = RegisterUiState.Error("Please fill out all required fields (Name, Email, Mobile, Birth Date, Gender, Password).")
            return
        }

        if (pass != confirmPass) {
            _uiState.value = RegisterUiState.Error("Passwords do not match.")
            return
        }

        if (!termsAccepted) {
            _uiState.value = RegisterUiState.Error("Please accept the Terms of Service to continue.")
            return
        }

        viewModelScope.launch {
            registerUseCase(name, email, phone, birthDate, gender, pass, confirmPass).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = RegisterUiState.Loading
                    is Result.Success -> {
                        val user = result.data
                        val requiresVerification = user.requiresEmailVerification
                        val userEmail = user.email ?: email
                        _uiState.value = RegisterUiState.Success(
                            user = user,
                            requiresEmailVerification = requiresVerification,
                            email = userEmail
                        )
                        if (requiresVerification && userEmail.isNotBlank()) {
                            _effect.send(RegisterUiEffect.NavigateToEmailVerification(userEmail, user.fallbackOtp))
                        } else {
                            _effect.send(RegisterUiEffect.NavigateToHome)
                        }
                    }
                    is Result.Error -> {
                        _uiState.value = RegisterUiState.Error(result.message ?: "Registration failed.")
                    }
                }
            }
        }
    }
}

