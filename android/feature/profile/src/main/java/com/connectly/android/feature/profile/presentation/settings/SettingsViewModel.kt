package com.connectly.android.feature.profile.presentation.settings

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.io.File
import javax.inject.Inject

enum class SettingsDialogType {
    LOGOUT_CONFIRMATION,
    THEME_PICKER,
    LANGUAGE_PICKER,
    DATA_STORAGE,
    ACCESSIBILITY,
    FEED_PREFERENCES,
    MESSAGING_CALLS,
    NOTIFICATIONS_INFO,
    BLOCKED_USERS,
    HELP_SUPPORT,
    ABOUT_CONNECTLY
}

data class SettingsState(
    val userId: Long = 0L,
    val userName: String = "User",
    val userUsername: String = "user",
    val userEmail: String = "",
    val userAvatar: String? = null,
    val isVerified: Boolean = false,
    val searchQuery: String = "",
    val isSearchActive: Boolean = false,
    val blockedCount: Int = 0,
    val selectedTheme: String = "System Default",
    val selectedLanguage: String = "English",
    val cacheSizeFormatted: String = "0 MB",
    val dataSaverEnabled: Boolean = false,
    val autoplayOnWifiOnly: Boolean = true,
    val largeTextEnabled: Boolean = false,
    val highContrastEnabled: Boolean = false,
    val reduceMotionEnabled: Boolean = false,
    val showOnlineStatus: Boolean = true,
    val whoCanMessage: String = "Everyone",
    val isLoggingOut: Boolean = false,
    val isLoggedOut: Boolean = false,
    val isLoading: Boolean = false,
    val statusMessage: String? = null,
    val errorMessage: String? = null,
    val activeDialog: SettingsDialogType? = null
)

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val api: ConnectlyApi,
    private val userDao: UserDao,
    private val tokenStorageManager: TokenStorageManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(SettingsState())
    val uiState: StateFlow<SettingsState> = _uiState.asStateFlow()

    init {
        loadUserData()
        loadSettingsOverview()
    }

    private fun loadUserData() {
        val currentUserId = tokenStorageManager.getUserId()
        if (currentUserId > 0) {
            viewModelScope.launch {
                userDao.getUserById(currentUserId).collect { userEntity ->
                    if (userEntity != null) {
                        _uiState.update { state ->
                            state.copy(
                                userId = userEntity.id,
                                userName = userEntity.name,
                                userUsername = userEntity.username,
                                userEmail = userEntity.email,
                                userAvatar = userEntity.avatar,
                                isVerified = userEntity.isVerified
                            )
                        }
                    }
                }
            }
        }
    }

    fun loadSettingsOverview() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val response = api.getSettingsOverview()
                if (response.isSuccessful && response.body() != null) {
                    val data = response.body()!!.data
                    val account = data.account
                    val privacy = data.privacy
                    val blocking = data.blocking

                    _uiState.update { state ->
                        state.copy(
                            isLoading = false,
                            userName = account?.name?.takeIf { it.isNotBlank() } ?: state.userName,
                            userUsername = account?.username?.takeIf { it.isNotBlank() } ?: state.userUsername,
                            userEmail = account?.email ?: state.userEmail,
                            userAvatar = account?.avatar ?: state.userAvatar,
                            blockedCount = blocking?.blocked_count ?: state.blockedCount,
                            showOnlineStatus = privacy?.show_online_status ?: state.showOnlineStatus,
                            whoCanMessage = privacy?.who_can_message?.replaceFirstChar { it.uppercase() } ?: state.whoCanMessage,
                            selectedLanguage = if (privacy?.language == "bn") "বাংলা" else "English",
                            selectedTheme = if (privacy?.dark_mode == true) "Dark" else "Light"
                        )
                    }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false) }
            }
        }
    }

    fun onSearchQueryChanged(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun toggleSearchActive(active: Boolean) {
        _uiState.update { it.copy(isSearchActive = active, searchQuery = if (!active) "" else it.searchQuery) }
    }

    fun openDialog(type: SettingsDialogType) {
        _uiState.update { it.copy(activeDialog = type) }
    }

    fun dismissDialog() {
        _uiState.update { it.copy(activeDialog = null) }
    }

    fun setTheme(theme: String) {
        _uiState.update { it.copy(selectedTheme = theme, activeDialog = null, statusMessage = "Theme updated to $theme") }
    }

    fun setLanguage(language: String) {
        _uiState.update { it.copy(selectedLanguage = language, activeDialog = null, statusMessage = "Language set to $language") }
    }

    fun toggleDataSaver() {
        _uiState.update { it.copy(dataSaverEnabled = !it.dataSaverEnabled) }
    }

    fun toggleAutoplayOnWifi() {
        _uiState.update { it.copy(autoplayOnWifiOnly = !it.autoplayOnWifiOnly) }
    }

    fun toggleLargeText() {
        _uiState.update { it.copy(largeTextEnabled = !it.largeTextEnabled) }
    }

    fun toggleHighContrast() {
        _uiState.update { it.copy(highContrastEnabled = !it.highContrastEnabled) }
    }

    fun toggleReduceMotion() {
        _uiState.update { it.copy(reduceMotionEnabled = !it.reduceMotionEnabled) }
    }

    fun toggleOnlineStatus() {
        _uiState.update { it.copy(showOnlineStatus = !it.showOnlineStatus) }
    }

    fun calculateCacheSize(context: Context) {
        viewModelScope.launch {
            val cacheBytes = getDirSize(context.cacheDir) + getDirSize(context.externalCacheDir)
            val sizeMb = cacheBytes / (1024.0 * 1024.0)
            val formatted = String.format("%.1f MB", sizeMb)
            _uiState.update { it.copy(cacheSizeFormatted = formatted) }
        }
    }

    fun clearAppCache(context: Context) {
        viewModelScope.launch {
            try {
                deleteDir(context.cacheDir)
                context.externalCacheDir?.let { deleteDir(it) }
                _uiState.update { it.copy(cacheSizeFormatted = "0.0 MB", statusMessage = "App cache cleared successfully.") }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = "Failed to clear cache.") }
            }
        }
    }

    private fun getDirSize(dir: File?): Long {
        if (dir == null || !dir.exists()) return 0L
        var bytes = 0L
        dir.listFiles()?.forEach { file ->
            bytes += if (file.isDirectory) getDirSize(file) else file.length()
        }
        return bytes
    }

    private fun deleteDir(dir: File?): Boolean {
        if (dir == null || !dir.exists()) return true
        dir.listFiles()?.forEach { file ->
            if (file.isDirectory) deleteDir(file) else file.delete()
        }
        return true
    }

    fun dismissMessages() {
        _uiState.update { it.copy(statusMessage = null, errorMessage = null) }
    }

    fun confirmLogout() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoggingOut = true, activeDialog = null) }
            try {
                api.logout()
            } catch (_: Exception) {
                // Ignore network failure on local credential clearing
            } finally {
                tokenStorageManager.clearAll()
                _uiState.update { it.copy(isLoggingOut = false, isLoggedOut = true) }
            }
        }
    }
}
