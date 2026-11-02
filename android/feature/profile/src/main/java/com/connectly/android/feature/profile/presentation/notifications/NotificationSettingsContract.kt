package com.connectly.android.feature.profile.presentation.notifications

data class NotificationSettingsState(
    // Master & Controls
    val masterNotificationsEnabled: Boolean = true,
    val doNotDisturb: Boolean = false,
    val quietHoursEnabled: Boolean = false,
    val quietHoursStart: String = "22:00",
    val quietHoursEnd: String = "07:00",
    val soundEnabled: Boolean = true,
    val vibrationEnabled: Boolean = true,
    val lockScreenPrivacy: String = "show_all", // "show_all", "hide_sensitive", "dont_show"

    // Channels
    val pushNotifications: Boolean = true,
    val inAppNotifications: Boolean = true,
    val emailNotifications: Boolean = true,
    val smsNotifications: Boolean = false,

    // Social Categories
    val likeAlerts: Boolean = true,
    val commentAlerts: Boolean = true,
    val friendRequestAlerts: Boolean = true,
    val friendAcceptedAlerts: Boolean = true,
    val followerAlerts: Boolean = true,
    val messageAlerts: Boolean = true,
    val mentionAlerts: Boolean = true,
    val groupAlerts: Boolean = true,
    val pageAlerts: Boolean = true,
    val eventAlerts: Boolean = true,
    val liveAlerts: Boolean = true,
    val storyAlerts: Boolean = true,

    // System & Marketing
    val securityAlerts: Boolean = true, // Critical security alerts cannot be silently disabled by general marketing toggles
    val productAlerts: Boolean = true,
    val marketingAlerts: Boolean = false,

    // UI state
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface NotificationSettingsIntent {
    object LoadSettings : NotificationSettingsIntent

    // Master & Controls
    data class MasterToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class DoNotDisturbToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class QuietHoursToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class QuietHoursTimeChanged(val start: String, val end: String) : NotificationSettingsIntent
    data class SoundToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class VibrationToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class LockScreenPrivacyChanged(val privacy: String) : NotificationSettingsIntent

    // Channels
    data class PushToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class InAppToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class EmailToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class SmsToggled(val enabled: Boolean) : NotificationSettingsIntent

    // Categories
    data class LikeAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class CommentAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class FriendRequestAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class FriendAcceptedAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class FollowerAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class MessageAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class MentionAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class GroupAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class PageAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class EventAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class LiveAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class StoryAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent

    // System & Marketing
    data class SecurityAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class ProductAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent
    data class MarketingAlertsToggled(val enabled: Boolean) : NotificationSettingsIntent

    object SaveAllSettings : NotificationSettingsIntent
    object DismissMessages : NotificationSettingsIntent
}

