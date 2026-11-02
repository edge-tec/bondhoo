package com.connectly.android.feature.profile.presentation.notifications

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.NotificationPreferencesRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import org.json.JSONObject
import javax.inject.Inject

@HiltViewModel
class NotificationSettingsViewModel @Inject constructor(
    private val api: ConnectlyApi,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val prefs = context.getSharedPreferences("connectly_notification_prefs", Context.MODE_PRIVATE)

    private val _uiState = MutableStateFlow(NotificationSettingsState())
    val uiState: StateFlow<NotificationSettingsState> = _uiState.asStateFlow()

    init {
        loadLocalPreferences()
        loadRemotePreferences()
    }

    private fun loadLocalPreferences() {
        val master = prefs.getBoolean("master_notifications", true)
        val dnd = prefs.getBoolean("do_not_disturb", false)
        val quietEnabled = prefs.getBoolean("quiet_hours_enabled", false)
        val quietStart = prefs.getString("quiet_hours_start", "22:00") ?: "22:00"
        val quietEnd = prefs.getString("quiet_hours_end", "07:00") ?: "07:00"
        val sound = prefs.getBoolean("sound_enabled", true)
        val vibration = prefs.getBoolean("vibration_enabled", true)
        val lockScreen = prefs.getString("lock_screen_privacy", "show_all") ?: "show_all"
        val inApp = prefs.getBoolean("in_app_notifications", true)
        val product = prefs.getBoolean("product_alerts", true)
        val marketing = prefs.getBoolean("marketing_alerts", false)
        val event = prefs.getBoolean("event_alerts", true)
        val live = prefs.getBoolean("live_alerts", true)
        val story = prefs.getBoolean("story_alerts", true)

        _uiState.update {
            it.copy(
                masterNotificationsEnabled = master,
                doNotDisturb = dnd,
                quietHoursEnabled = quietEnabled,
                quietHoursStart = quietStart,
                quietHoursEnd = quietEnd,
                soundEnabled = sound,
                vibrationEnabled = vibration,
                lockScreenPrivacy = lockScreen,
                inAppNotifications = inApp,
                productAlerts = product,
                marketingAlerts = marketing,
                eventAlerts = event,
                liveAlerts = live,
                storyAlerts = story
            )
        }
    }

    fun handleIntent(intent: NotificationSettingsIntent) {
        when (intent) {
            NotificationSettingsIntent.LoadSettings -> loadRemotePreferences()

            // Master & Controls
            is NotificationSettingsIntent.MasterToggled -> {
                _uiState.update { it.copy(masterNotificationsEnabled = intent.enabled) }
                prefs.edit().putBoolean("master_notifications", intent.enabled).apply()
                autoSyncBackend()
            }
            is NotificationSettingsIntent.DoNotDisturbToggled -> {
                _uiState.update { it.copy(doNotDisturb = intent.enabled) }
                prefs.edit().putBoolean("do_not_disturb", intent.enabled).apply()
            }
            is NotificationSettingsIntent.QuietHoursToggled -> {
                _uiState.update { it.copy(quietHoursEnabled = intent.enabled) }
                prefs.edit().putBoolean("quiet_hours_enabled", intent.enabled).apply()
            }
            is NotificationSettingsIntent.QuietHoursTimeChanged -> {
                _uiState.update { it.copy(quietHoursStart = intent.start, quietHoursEnd = intent.end) }
                prefs.edit()
                    .putString("quiet_hours_start", intent.start)
                    .putString("quiet_hours_end", intent.end)
                    .apply()
            }
            is NotificationSettingsIntent.SoundToggled -> {
                _uiState.update { it.copy(soundEnabled = intent.enabled) }
                prefs.edit().putBoolean("sound_enabled", intent.enabled).apply()
            }
            is NotificationSettingsIntent.VibrationToggled -> {
                _uiState.update { it.copy(vibrationEnabled = intent.enabled) }
                prefs.edit().putBoolean("vibration_enabled", intent.enabled).apply()
            }
            is NotificationSettingsIntent.LockScreenPrivacyChanged -> {
                _uiState.update { it.copy(lockScreenPrivacy = intent.privacy) }
                prefs.edit().putString("lock_screen_privacy", intent.privacy).apply()
            }

            // Channels
            is NotificationSettingsIntent.PushToggled -> {
                _uiState.update { it.copy(pushNotifications = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.InAppToggled -> {
                _uiState.update { it.copy(inAppNotifications = intent.enabled) }
                prefs.edit().putBoolean("in_app_notifications", intent.enabled).apply()
            }
            is NotificationSettingsIntent.EmailToggled -> {
                _uiState.update { it.copy(emailNotifications = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.SmsToggled -> {
                _uiState.update { it.copy(smsNotifications = intent.enabled) }
                autoSyncBackend()
            }

            // Categories
            is NotificationSettingsIntent.LikeAlertsToggled -> {
                _uiState.update { it.copy(likeAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.CommentAlertsToggled -> {
                _uiState.update { it.copy(commentAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.FriendRequestAlertsToggled -> {
                _uiState.update { it.copy(friendRequestAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.FriendAcceptedAlertsToggled -> {
                _uiState.update { it.copy(friendAcceptedAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.FollowerAlertsToggled -> {
                _uiState.update { it.copy(followerAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.MessageAlertsToggled -> {
                _uiState.update { it.copy(messageAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.MentionAlertsToggled -> {
                _uiState.update { it.copy(mentionAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.GroupAlertsToggled -> {
                _uiState.update { it.copy(groupAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.PageAlertsToggled -> {
                _uiState.update { it.copy(pageAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.EventAlertsToggled -> {
                _uiState.update { it.copy(eventAlerts = intent.enabled) }
                prefs.edit().putBoolean("event_alerts", intent.enabled).apply()
            }
            is NotificationSettingsIntent.LiveAlertsToggled -> {
                _uiState.update { it.copy(liveAlerts = intent.enabled) }
                prefs.edit().putBoolean("live_alerts", intent.enabled).apply()
            }
            is NotificationSettingsIntent.StoryAlertsToggled -> {
                _uiState.update { it.copy(storyAlerts = intent.enabled) }
                prefs.edit().putBoolean("story_alerts", intent.enabled).apply()
            }

            // System & Marketing
            is NotificationSettingsIntent.SecurityAlertsToggled -> {
                _uiState.update { it.copy(securityAlerts = intent.enabled) }
                autoSyncBackend()
            }
            is NotificationSettingsIntent.ProductAlertsToggled -> {
                _uiState.update { it.copy(productAlerts = intent.enabled) }
                prefs.edit().putBoolean("product_alerts", intent.enabled).apply()
            }
            is NotificationSettingsIntent.MarketingAlertsToggled -> {
                _uiState.update { it.copy(marketingAlerts = intent.enabled) }
                prefs.edit().putBoolean("marketing_alerts", intent.enabled).apply()
            }

            NotificationSettingsIntent.SaveAllSettings -> saveAllSettings()
            NotificationSettingsIntent.DismissMessages -> {
                _uiState.update { it.copy(successMessage = null, errorMessage = null) }
            }
        }
    }

    private fun loadRemotePreferences() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val resp = api.getNotificationPreferences()
                if (resp.isSuccessful && resp.body()?.data != null) {
                    val p = resp.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            pushNotifications = p.push_notifications,
                            emailNotifications = p.email_notifications,
                            smsNotifications = p.sms_notifications,
                            likeAlerts = p.like_alerts,
                            commentAlerts = p.comment_alerts,
                            friendRequestAlerts = p.friend_request_alerts,
                            friendAcceptedAlerts = p.friend_accepted_alerts,
                            followerAlerts = p.follower_alerts,
                            messageAlerts = p.message_alerts,
                            mentionAlerts = p.mention_alerts,
                            groupAlerts = p.group_alerts,
                            pageAlerts = p.page_alerts,
                            securityAlerts = p.security_alerts
                        )
                    }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Failed to load notification preferences.") }
            }
        }
    }

    private fun autoSyncBackend() {
        saveAllSettings(silent = true)
    }

    private fun saveAllSettings(silent: Boolean = false) {
        val s = _uiState.value
        viewModelScope.launch {
            if (!silent) _uiState.update { it.copy(isSaving = true) }
            try {
                val req = NotificationPreferencesRequest(
                    push_notifications = if (s.masterNotificationsEnabled) s.pushNotifications else false,
                    email_notifications = s.emailNotifications,
                    sms_notifications = s.smsNotifications,
                    like_alerts = s.likeAlerts,
                    comment_alerts = s.commentAlerts,
                    friend_request_alerts = s.friendRequestAlerts,
                    friend_accepted_alerts = s.friendAcceptedAlerts,
                    follower_alerts = s.followerAlerts,
                    message_alerts = s.messageAlerts,
                    mention_alerts = s.mentionAlerts,
                    group_alerts = s.groupAlerts,
                    page_alerts = s.pageAlerts,
                    security_alerts = s.securityAlerts // Never disabled by marketing preferences
                )
                val resp = api.updateNotificationPreferences(req)
                if (resp.isSuccessful) {
                    if (!silent) {
                        _uiState.update { it.copy(isSaving = false, successMessage = "Notification preferences updated.") }
                    }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to save notification preferences.")
                    _uiState.update { it.copy(isSaving = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSaving = false, errorMessage = e.localizedMessage ?: "Network error.") }
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

