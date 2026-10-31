package com.connectly.android.feature.auth.presentation.twofactor

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.auth.domain.usecase.Verify2FaUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class TwoFactorViewModel @Inject constructor(
    private val verify2FaUseCase: Verify2FaUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<TwoFactorUiState>(TwoFactorUiState.Idle)
    val uiState: StateFlow<TwoFactorUiState> = _uiState.asStateFlow()

    private val _effect = Channel<TwoFactorUiEffect>(Channel.BUFFERED)
    val effect: Flow<TwoFactorUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: TwoFactorUiIntent) {
        when (intent) {
            is TwoFactorUiIntent.Verify -> executeVerify(intent.code, intent.sessionToken)
            is TwoFactorUiIntent.ResendCode -> {
                viewModelScope.launch {
                    _effect.send(TwoFactorUiEffect.ShowToast("Security code resent to your device."))
                }
            }
        }
    }

    private fun executeVerify(code: String, sessionToken: String) {
        if (code.length != 6) {
            _uiState.value = TwoFactorUiState.Error("Please enter all 6 digits of the authentication code.")
            return
        }

        viewModelScope.launch {
            verify2FaUseCase(code, sessionToken).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.value = TwoFactorUiState.Loading
                    is Result.Success -> {
                        _uiState.value = TwoFactorUiState.Success(result.data)
                        _effect.send(TwoFactorUiEffect.NavigateToHome)
                    }
                    is Result.Error -> {
                        _uiState.value = TwoFactorUiState.Error(result.message ?: "Invalid 2FA code.")
                    }
                }
            }
        }
    }
}

