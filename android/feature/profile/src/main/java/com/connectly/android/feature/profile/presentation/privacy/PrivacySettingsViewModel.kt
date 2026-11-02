package com.connectly.android.feature.profile.presentation.privacy

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import org.json.JSONObject
import javax.inject.Inject

@HiltViewModel
class PrivacySettingsViewModel @Inject constructor(
    private val api: ConnectlyApi,
    private val tokenStorageManager: TokenStorageManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(PrivacySettingsState())
    val uiState: StateFlow<PrivacySettingsState> = _uiState.asStateFlow()

    init {
        loadSettings()
        loadSessions()
    }

    fun handleIntent(intent: PrivacySettingsIntent) {
        when (intent) {
            PrivacySettingsIntent.LoadSettings -> loadSettings()
            PrivacySettingsIntent.RefreshSessions -> loadSessions()

            // Profile Privacy
            is PrivacySettingsIntent.ProfileVisibilityChanged -> {
                _uiState.update { it.copy(profileVisibility = intent.visibility) }
                autoSave()
            }
            is PrivacySettingsIntent.ProfileLockedToggled -> {
                _uiState.update { it.copy(isProfileLocked = intent.locked) }
                autoSave()
            }
            is PrivacySettingsIntent.AvatarGuardToggled -> {
                _uiState.update { it.copy(hasAvatarGuard = intent.enabled) }
                autoSave()
            }
            is PrivacySettingsIntent.WhoCanSeePostsChanged -> {
                _uiState.update { it.copy(whoCanSeePosts = intent.audience) }
                autoSave()
            }
            is PrivacySettingsIntent.WhoCanSeeFriendsChanged -> {
                _uiState.update { it.copy(whoCanSeeFriends = intent.audience) }
                autoSave()
            }
            is PrivacySettingsIntent.WhoCanSendFriendRequestsChanged -> {
                _uiState.update { it.copy(whoCanSendFriendRequests = intent.scope) }
                autoSave()
            }
            is PrivacySettingsIntent.WhoCanFollowChanged -> {
                _uiState.update { it.copy(whoCanFollow = intent.scope) }
                autoSave()
            }
            is PrivacySettingsIntent.FindByEmailChanged -> {
                _uiState.update { it.copy(findByEmail = intent.scope) }
                autoSave()
            }
            is PrivacySettingsIntent.FindByPhoneChanged -> {
                _uiState.update { it.copy(findByPhone = intent.scope) }
                autoSave()
            }

            // Content Privacy
            is PrivacySettingsIntent.StoryVisibilityChanged -> {
                _uiState.update { it.copy(storyVisibility = intent.audience) }
                autoSave()
            }
            is PrivacySettingsIntent.ReelsAudienceChanged -> {
                _uiState.update { it.copy(reelsAudience = intent.audience) }
                autoSave()
            }
            is PrivacySettingsIntent.WhoCanMessageChanged -> {
                _uiState.update { it.copy(whoCanMessage = intent.scope) }
                autoSave()
            }
            is PrivacySettingsIntent.AllowTaggingToggled -> {
                _uiState.update { it.copy(allowTagging = intent.allowed) }
                autoSave()
            }
            is PrivacySettingsIntent.SearchEngineIndexingToggled -> {
                _uiState.update { it.copy(searchEngineIndexing = intent.enabled) }
                autoSave()
            }

            // Online Privacy
            is PrivacySettingsIntent.ShowOnlineStatusToggled -> {
                _uiState.update { it.copy(showOnlineStatus = intent.enabled) }
                autoSave()
            }
            is PrivacySettingsIntent.ShowLastSeenChanged -> {
                _uiState.update { it.copy(showLastSeen = intent.scope) }
                autoSave()
            }
            is PrivacySettingsIntent.ReadReceiptsToggled -> {
                _uiState.update { it.copy(readReceiptsEnabled = intent.enabled) }
                autoSave()
            }
            is PrivacySettingsIntent.TypingIndicatorsToggled -> {
                _uiState.update { it.copy(typingIndicatorsEnabled = intent.enabled) }
            }

            // Security
            is PrivacySettingsIntent.TwoFactorToggled -> toggleTwoFactor(intent.enabled)
            is PrivacySettingsIntent.LoginAlertsToggled -> {
                _uiState.update { it.copy(loginAlertsEnabled = intent.enabled) }
            }
            is PrivacySettingsIntent.ConfirmRevokeSession -> {
                _uiState.update { it.copy(sessionToRevoke = intent.session) }
            }
            PrivacySettingsIntent.DismissRevokeDialog -> {
                _uiState.update { it.copy(sessionToRevoke = null) }
            }
            PrivacySettingsIntent.ExecuteRevokeSession -> executeRevokeSession()
            PrivacySettingsIntent.ConfirmLogoutOthers -> {
                _uiState.update { it.copy(showLogoutOthersDialog = true) }
            }
            PrivacySettingsIntent.DismissLogoutOthersDialog -> {
                _uiState.update { it.copy(showLogoutOthersDialog = false) }
            }
            PrivacySettingsIntent.ExecuteLogoutOthers -> executeLogoutOthers()

            is PrivacySettingsIntent.OpenPasswordDialog -> {
                _uiState.update { it.copy(showPasswordChangeDialog = intent.open) }
            }
            is PrivacySettingsIntent.ChangePassword -> changePassword(intent.currentPass, intent.newPass, intent.confirmPass)

            PrivacySettingsIntent.SaveAllSettings -> saveAllSettings()
            PrivacySettingsIntent.DismissMessages -> {
                _uiState.update { it.copy(successMessage = null, errorMessage = null) }
            }
        }
    }

    private fun loadSettings() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val resp = api.getSettingsPrivacy()
                if (resp.isSuccessful && resp.body()?.data != null) {
                    val p = resp.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            whoCanSeePosts = p.who_can_see_posts,
                            whoCanSendFriendRequests = p.who_can_send_friend_requests,
                            whoCanFollow = p.who_can_follow,
                            whoCanMessage = p.who_can_message,
                            whoCanSeeFriends = p.who_can_see_friends,
                            storyVisibility = p.story_visibility,
                            isProfileLocked = p.is_profile_locked,
                            hasAvatarGuard = p.has_avatar_guard,
                            findByEmail = p.find_by_email,
                            findByPhone = p.find_by_phone,
                            profileVisibility = p.profile_visibility,
                            searchEngineIndexing = p.search_engine_indexing,
                            showOnlineStatus = p.show_online_status,
                            showLastSeen = p.show_last_seen,
                            readReceiptsEnabled = p.read_receipts_enabled,
                            twoFactorEnabled = p.two_factor_enabled
                        )
                    }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Failed to load privacy settings.") }
            }
        }
    }

    private fun loadSessions() {
        viewModelScope.launch {
            _uiState.update { it.copy(isSessionsLoading = true) }
            try {
                val resp = api.getActiveSessions()
                if (resp.isSuccessful && resp.body()?.data != null) {
                    _uiState.update { it.copy(isSessionsLoading = false, sessions = resp.body()!!.data!!) }
                } else {
                    _uiState.update { it.copy(isSessionsLoading = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSessionsLoading = false) }
            }
        }
    }

    private fun autoSave() {
        saveAllSettings(silent = true)
    }

    private fun saveAllSettings(silent: Boolean = false) {
        val s = _uiState.value
        viewModelScope.launch {
            if (!silent) _uiState.update { it.copy(isSaving = true) }
            try {
                val req = UpdateSettingsPrivacyRequest(
                    who_can_see_posts = s.whoCanSeePosts,
                    who_can_send_friend_requests = s.whoCanSendFriendRequests,
                    who_can_follow = s.whoCanFollow,
                    who_can_message = s.whoCanMessage,
                    who_can_see_friends = s.whoCanSeeFriends,
                    story_visibility = s.storyVisibility,
                    is_profile_locked = s.isProfileLocked,
                    has_avatar_guard = s.hasAvatarGuard,
                    find_by_email = s.findByEmail,
                    find_by_phone = s.findByPhone,
                    profile_visibility = s.profileVisibility,
                    search_engine_indexing = s.searchEngineIndexing,
                    show_online_status = s.showOnlineStatus,
                    show_last_seen = s.showLastSeen,
                    read_receipts_enabled = s.readReceiptsEnabled,
                    two_factor_enabled = s.twoFactorEnabled
                )
                val resp = api.updateSettingsPrivacy(req)
                if (resp.isSuccessful) {
                    if (!silent) {
                        _uiState.update { it.copy(isSaving = false, successMessage = "Privacy settings updated successfully.") }
                    }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to save privacy settings.")
                    _uiState.update { it.copy(isSaving = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSaving = false, errorMessage = e.localizedMessage ?: "Network connection error.") }
            }
        }
    }

    private fun toggleTwoFactor(enable: Boolean) {
        viewModelScope.launch {
            try {
                val resp = api.toggleTwoFactor(Toggle2faRequest(enable))
                if (resp.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            twoFactorEnabled = enable,
                            successMessage = if (enable) "Two-factor authentication enabled." else "Two-factor authentication disabled."
                        )
                    }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to toggle two-factor authentication.")
                    _uiState.update { it.copy(errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = e.localizedMessage ?: "Failed to update 2FA.") }
            }
        }
    }

    private fun executeRevokeSession() {
        val session = _uiState.value.sessionToRevoke ?: return
        viewModelScope.launch {
            _uiState.update { it.copy(sessionToRevoke = null) }
            try {
                val resp = api.revokeSession(session.id)
                if (resp.isSuccessful) {
                    _uiState.update { state ->
                        state.copy(
                            sessions = state.sessions.filter { it.id != session.id },
                            successMessage = "Device session revoked."
                        )
                    }
                } else {
                    _uiState.update { it.copy(errorMessage = "Could not revoke session.") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = e.localizedMessage ?: "Failed to revoke session.") }
            }
        }
    }

    private fun executeLogoutOthers() {
        viewModelScope.launch {
            _uiState.update { it.copy(showLogoutOthersDialog = false) }
            try {
                val resp = api.logoutAllSessions()
                if (resp.isSuccessful) {
                    _uiState.update { state ->
                        state.copy(
                            sessions = state.sessions.filter { it.is_current },
                            successMessage = "Logged out from all other devices."
                        )
                    }
                } else {
                    _uiState.update { it.copy(errorMessage = "Failed to logout from other devices.") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = e.localizedMessage ?: "Failed to terminate sessions.") }
            }
        }
    }

    private fun changePassword(currentPass: String, newPass: String, confirmPass: String) {
        if (currentPass.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Current password is required.") }
            return
        }
        if (newPass.length < 8) {
            _uiState.update { it.copy(errorMessage = "New password must be at least 8 characters.") }
            return
        }
        if (newPass != confirmPass) {
            _uiState.update { it.copy(errorMessage = "New passwords do not match.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(showPasswordChangeDialog = false) }
            try {
                val resp = api.updatePassword(
                    UpdatePasswordRequest(
                        current_password = currentPass,
                        password = newPass,
                        password_confirmation = confirmPass
                    )
                )
                if (resp.isSuccessful) {
                    _uiState.update { it.copy(successMessage = "Password updated successfully.") }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to update password. Current password may be incorrect.")
                    _uiState.update { it.copy(errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = e.localizedMessage ?: "Network error.") }
            }
        }
    }

    private fun parseErrorMessage(rawError: String?, fallback: String): String {
        if (rawError.isNullOrBlank()) return fallback
        return try {
            val json = JSONObject(rawError)
            when {
                json.has("message") -> json.getString("message")
                json.has("error") -> json.getString("error")
                else -> rawError
            }
        } catch (_: Exception) {
            rawError
        }
    }
}
