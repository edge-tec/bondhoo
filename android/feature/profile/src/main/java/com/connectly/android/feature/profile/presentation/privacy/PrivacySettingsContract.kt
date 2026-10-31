package com.connectly.android.feature.profile.presentation.privacy

data class PrivacySettingsState(
    val profileVisibility: String = "public", // "public", "followers", "friends", "only_me"
    val avatarPrivacy: String = "public",
    val bioPrivacy: String = "public",
    val allowTagging: Boolean = true,
    val isLoading: Boolean = false,
    val isSaved: Boolean = false,
    val errorMessage: String? = null
)

sealed interface PrivacySettingsIntent {
    data class VisibilityChanged(val visibility: String) : PrivacySettingsIntent
    data class AllowTaggingChanged(val allowed: Boolean) : PrivacySettingsIntent
    object SaveSettings : PrivacySettingsIntent
}
