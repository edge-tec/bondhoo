package com.connectly.android.feature.profile.presentation.privacy

import com.connectly.android.core.network.api.UserSessionDto

data class PrivacySettingsState(
    // 1. Profile Privacy
    val profileVisibility: String = "public", // "public", "friends", "private"
    val isProfileLocked: Boolean = false,
    val hasAvatarGuard: Boolean = false,
    val whoCanSeePosts: String = "public", // "public", "friends", "only_me"
    val whoCanSeeFriends: String = "public", // "public", "friends", "only_me"
    val whoCanSendFriendRequests: String = "everyone", // "everyone", "friends_of_friends"
    val whoCanFollow: String = "everyone", // "everyone", "friends"
    val findByEmail: String = "everyone", // "everyone", "friends", "only_me"
    val findByPhone: String = "everyone", // "everyone", "friends", "only_me"

    // 2. Content Privacy
    val storyVisibility: String = "public", // "public", "friends"
    val reelsAudience: String = "public", // "public", "friends"
    val whoCanMessage: String = "everyone", // "everyone", "friends"
    val allowTagging: Boolean = true,
    val searchEngineIndexing: Boolean = true,

    // 3. Online Privacy
    val showOnlineStatus: Boolean = true,
    val showLastSeen: String = "everyone", // "everyone", "friends", "nobody"
    val readReceiptsEnabled: Boolean = true,
    val typingIndicatorsEnabled: Boolean = true,

    // 4. Security
    val twoFactorEnabled: Boolean = false,
    val loginAlertsEnabled: Boolean = true,
    val sessions: List<UserSessionDto> = emptyList(),
    val isSessionsLoading: Boolean = false,

    // Dialog & UI State
    val sessionToRevoke: UserSessionDto? = null,
    val showLogoutOthersDialog: Boolean = false,
    val showPasswordChangeDialog: Boolean = false,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface PrivacySettingsIntent {
    object LoadSettings : PrivacySettingsIntent
    object RefreshSessions : PrivacySettingsIntent

    // Profile Privacy
    data class ProfileVisibilityChanged(val visibility: String) : PrivacySettingsIntent
    data class ProfileLockedToggled(val locked: Boolean) : PrivacySettingsIntent
    data class AvatarGuardToggled(val enabled: Boolean) : PrivacySettingsIntent
    data class WhoCanSeePostsChanged(val audience: String) : PrivacySettingsIntent
    data class WhoCanSeeFriendsChanged(val audience: String) : PrivacySettingsIntent
    data class WhoCanSendFriendRequestsChanged(val scope: String) : PrivacySettingsIntent
    data class WhoCanFollowChanged(val scope: String) : PrivacySettingsIntent
    data class FindByEmailChanged(val scope: String) : PrivacySettingsIntent
    data class FindByPhoneChanged(val scope: String) : PrivacySettingsIntent

    // Content Privacy
    data class StoryVisibilityChanged(val audience: String) : PrivacySettingsIntent
    data class ReelsAudienceChanged(val audience: String) : PrivacySettingsIntent
    data class WhoCanMessageChanged(val scope: String) : PrivacySettingsIntent
    data class AllowTaggingToggled(val allowed: Boolean) : PrivacySettingsIntent
    data class SearchEngineIndexingToggled(val enabled: Boolean) : PrivacySettingsIntent

    // Online Privacy
    data class ShowOnlineStatusToggled(val enabled: Boolean) : PrivacySettingsIntent
    data class ShowLastSeenChanged(val scope: String) : PrivacySettingsIntent
    data class ReadReceiptsToggled(val enabled: Boolean) : PrivacySettingsIntent
    data class TypingIndicatorsToggled(val enabled: Boolean) : PrivacySettingsIntent

    // Security
    data class TwoFactorToggled(val enabled: Boolean) : PrivacySettingsIntent
    data class LoginAlertsToggled(val enabled: Boolean) : PrivacySettingsIntent
    data class ConfirmRevokeSession(val session: UserSessionDto) : PrivacySettingsIntent
    object DismissRevokeDialog : PrivacySettingsIntent
    object ExecuteRevokeSession : PrivacySettingsIntent
    object ConfirmLogoutOthers : PrivacySettingsIntent
    object DismissLogoutOthersDialog : PrivacySettingsIntent
    object ExecuteLogoutOthers : PrivacySettingsIntent

    data class OpenPasswordDialog(val open: Boolean) : PrivacySettingsIntent
    data class ChangePassword(val currentPass: String, val newPass: String, val confirmPass: String) : PrivacySettingsIntent

    object SaveAllSettings : PrivacySettingsIntent
    object DismissMessages : PrivacySettingsIntent
}
