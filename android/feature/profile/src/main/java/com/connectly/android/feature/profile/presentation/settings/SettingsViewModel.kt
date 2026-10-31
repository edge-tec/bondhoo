package com.connectly.android.feature.profile.presentation.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SettingsState(
    val userName: String = "Mizanur Rahman",
    val userAvatar: String = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
    val isLoggingOut: Boolean = false,
    val isLoggedOut: Boolean = false
)

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val api: ConnectlyApi,
    private val tokenStorageManager: TokenStorageManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(SettingsState())
    val uiState: StateFlow<SettingsState> = _uiState.asStateFlow()

    fun logout() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoggingOut = true) }
            try {
                api.logout()
            } catch (_: Exception) {
                // Ignore network error on logout
            } finally {
                tokenStorageManager.clearAll()
                _uiState.update { it.copy(isLoggingOut = false, isLoggedOut = true) }
            }
        }
    }
}

