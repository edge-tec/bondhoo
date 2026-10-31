package com.connectly.android.feature.profile.presentation.privacy

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.profile.domain.usecase.UpdatePrivacySettingsUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PrivacySettingsViewModel @Inject constructor(
    private val updatePrivacySettingsUseCase: UpdatePrivacySettingsUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(PrivacySettingsState())
    val uiState: StateFlow<PrivacySettingsState> = _uiState.asStateFlow()

    fun handleIntent(intent: PrivacySettingsIntent) {
        when (intent) {
            is PrivacySettingsIntent.VisibilityChanged -> {
                _uiState.update { it.copy(profileVisibility = intent.visibility) }
            }
            is PrivacySettingsIntent.AllowTaggingChanged -> {
                _uiState.update { it.copy(allowTagging = intent.allowed) }
            }
            PrivacySettingsIntent.SaveSettings -> saveSettings()
        }
    }

    private fun saveSettings() {
        val visibility = _uiState.value.profileVisibility
        viewModelScope.launch {
            updatePrivacySettingsUseCase(visibility).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, isSaved = true) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = "Failed to update privacy settings") }
                }
            }
        }
    }
}

