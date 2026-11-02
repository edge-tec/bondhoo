package com.connectly.android.feature.profile.presentation.feedpreferences

data class FeedPreferencesState(
    val defaultPostAudience: String = "public", // "public", "friends", "only_me"
    val sensitiveContentFilter: String = "standard", // "standard", "less", "more"
    val snoozedAccountsCount: Int = 0,
    val hiddenPostsCount: Int = 0,
    val favoritesCount: Int = 3,
    val videoAutoplay: String = "wifi_only", // "always", "wifi_only", "never"
    val videoQuality: String = "auto", // "auto", "high", "data_saver"
    val dataSaverEnabled: Boolean = false,
    val reelsAutoplayNext: Boolean = true,
    val storyVisibility: String = "friends", // "public", "friends"
    val storyReplyPermission: String = "everyone", // "everyone", "friends", "off"
    val saveStoriesToArchive: Boolean = true,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface FeedPreferencesIntent {
    object LoadPreferences : FeedPreferencesIntent
    data class DefaultPostAudienceChanged(val audience: String) : FeedPreferencesIntent
    data class SensitiveContentFilterChanged(val filter: String) : FeedPreferencesIntent
    data class VideoAutoplayChanged(val mode: String) : FeedPreferencesIntent
    data class VideoQualityChanged(val quality: String) : FeedPreferencesIntent
    data class DataSaverToggled(val enabled: Boolean) : FeedPreferencesIntent
    data class ReelsAutoplayNextToggled(val enabled: Boolean) : FeedPreferencesIntent
    data class StoryVisibilityChanged(val visibility: String) : FeedPreferencesIntent
    data class StoryReplyPermissionChanged(val permission: String) : FeedPreferencesIntent
    data class SaveStoriesToArchiveToggled(val enabled: Boolean) : FeedPreferencesIntent
    object ClearHiddenPosts : FeedPreferencesIntent
    object ClearSnoozedAccounts : FeedPreferencesIntent
    object SaveAllPreferences : FeedPreferencesIntent
    object DismissFeedback : FeedPreferencesIntent
}
