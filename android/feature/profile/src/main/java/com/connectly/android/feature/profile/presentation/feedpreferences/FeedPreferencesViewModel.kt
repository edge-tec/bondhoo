package com.connectly.android.feature.profile.presentation.feedpreferences

import android.content.Context
import android.content.SharedPreferences
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.UpdatePrivacyRequest
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class FeedPreferencesViewModel @Inject constructor(
    private val api: ConnectlyApi,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val prefs: SharedPreferences by lazy {
        context.getSharedPreferences("connectly_feed_preferences", Context.MODE_PRIVATE)
    }

    private val _uiState = MutableStateFlow(FeedPreferencesState())
    val uiState: StateFlow<FeedPreferencesState> = _uiState.asStateFlow()

    init {
        loadPreferences()
    }

    fun handleIntent(intent: FeedPreferencesIntent) {
        when (intent) {
            FeedPreferencesIntent.LoadPreferences -> loadPreferences()
            is FeedPreferencesIntent.DefaultPostAudienceChanged -> updateDefaultPostAudience(intent.audience)
            is FeedPreferencesIntent.SensitiveContentFilterChanged -> updateSensitiveFilter(intent.filter)
            is FeedPreferencesIntent.VideoAutoplayChanged -> updateVideoAutoplay(intent.mode)
            is FeedPreferencesIntent.VideoQualityChanged -> updateVideoQuality(intent.quality)
            is FeedPreferencesIntent.DataSaverToggled -> updateDataSaver(intent.enabled)
            is FeedPreferencesIntent.ReelsAutoplayNextToggled -> updateReelsAutoplay(intent.enabled)
            is FeedPreferencesIntent.StoryVisibilityChanged -> updateStoryVisibility(intent.visibility)
            is FeedPreferencesIntent.StoryReplyPermissionChanged -> updateStoryReplyPermission(intent.permission)
            is FeedPreferencesIntent.SaveStoriesToArchiveToggled -> updateSaveArchive(intent.enabled)
            FeedPreferencesIntent.ClearHiddenPosts -> clearHiddenPosts()
            FeedPreferencesIntent.ClearSnoozedAccounts -> clearSnoozedAccounts()
            FeedPreferencesIntent.SaveAllPreferences -> saveAll()
            FeedPreferencesIntent.DismissFeedback -> _uiState.update { it.copy(successMessage = null, errorMessage = null) }
        }
    }

    private fun loadPreferences() {
        _uiState.update { it.copy(isLoading = true) }

        // Load local preferences
        val sensitiveFilter = prefs.getString("sensitive_content_filter", "standard") ?: "standard"
        val autoplay = prefs.getString("video_autoplay", "wifi_only") ?: "wifi_only"
        val quality = prefs.getString("video_quality", "auto") ?: "auto"
        val dataSaver = prefs.getBoolean("data_saver_enabled", false)
        val reelsAutoplay = prefs.getBoolean("reels_autoplay_next", true)
        val replyPermission = prefs.getString("story_reply_permission", "everyone") ?: "everyone"
        val saveArchive = prefs.getBoolean("save_stories_to_archive", true)
        val snoozedCount = prefs.getInt("snoozed_accounts_count", 0)
        val hiddenCount = prefs.getInt("hidden_posts_count", 0)
        val favoritesCount = prefs.getInt("favorites_count", 3)

        _uiState.update {
            it.copy(
                sensitiveContentFilter = sensitiveFilter,
                videoAutoplay = autoplay,
                videoQuality = quality,
                dataSaverEnabled = dataSaver,
                reelsAutoplayNext = reelsAutoplay,
                storyReplyPermission = replyPermission,
                saveStoriesToArchive = saveArchive,
                snoozedAccountsCount = snoozedCount,
                hiddenPostsCount = hiddenCount,
                favoritesCount = favoritesCount
            )
        }

        // Load remote backend settings (who_can_see_posts & story_visibility)
        viewModelScope.launch {
            try {
                val response = api.getSettingsPrivacy()
                if (response.isSuccessful && response.body()?.data != null) {
                    val data = response.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            defaultPostAudience = data.who_can_see_posts ?: "public",
                            storyVisibility = data.story_visibility ?: "friends",
                            isLoading = false
                        )
                    }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (_: Exception) {
                _uiState.update { it.copy(isLoading = false) }
            }
        }
    }

    private fun updateDefaultPostAudience(audience: String) {
        _uiState.update { it.copy(defaultPostAudience = audience) }
        viewModelScope.launch {
            try {
                api.updateSettingsPrivacy(UpdatePrivacyRequest(who_can_see_posts = audience))
            } catch (_: Exception) {
            }
        }
    }

    private fun updateStoryVisibility(visibility: String) {
        _uiState.update { it.copy(storyVisibility = visibility) }
        viewModelScope.launch {
            try {
                api.updateSettingsPrivacy(UpdatePrivacyRequest(story_visibility = visibility))
            } catch (_: Exception) {
            }
        }
    }

    private fun updateSensitiveFilter(filter: String) {
        _uiState.update { it.copy(sensitiveContentFilter = filter) }
        prefs.edit().putString("sensitive_content_filter", filter).apply()
    }

    private fun updateVideoAutoplay(mode: String) {
        _uiState.update { it.copy(videoAutoplay = mode) }
        prefs.edit().putString("video_autoplay", mode).apply()
    }

    private fun updateVideoQuality(quality: String) {
        _uiState.update { it.copy(videoQuality = quality) }
        prefs.edit().putString("video_quality", quality).apply()
    }

    private fun updateDataSaver(enabled: Boolean) {
        _uiState.update { it.copy(dataSaverEnabled = enabled) }
        prefs.edit().putBoolean("data_saver_enabled", enabled).apply()
    }

    private fun updateReelsAutoplay(enabled: Boolean) {
        _uiState.update { it.copy(reelsAutoplayNext = enabled) }
        prefs.edit().putBoolean("reels_autoplay_next", enabled).apply()
    }

    private fun updateStoryReplyPermission(permission: String) {
        _uiState.update { it.copy(storyReplyPermission = permission) }
        prefs.edit().putString("story_reply_permission", permission).apply()
    }

    private fun updateSaveArchive(enabled: Boolean) {
        _uiState.update { it.copy(saveStoriesToArchive = enabled) }
        prefs.edit().putBoolean("save_stories_to_archive", enabled).apply()
    }

    private fun clearHiddenPosts() {
        prefs.edit().putInt("hidden_posts_count", 0).apply()
        _uiState.update {
            it.copy(
                hiddenPostsCount = 0,
                successMessage = "Hidden posts list cleared."
            )
        }
    }

    private fun clearSnoozedAccounts() {
        prefs.edit().putInt("snoozed_accounts_count", 0).apply()
        _uiState.update {
            it.copy(
                snoozedAccountsCount = 0,
                successMessage = "Snoozed accounts list reset."
            )
        }
    }

    private fun saveAll() {
        _uiState.update { it.copy(isSaving = true) }
        viewModelScope.launch {
            try {
                val state = _uiState.value
                val response = api.updateSettingsPrivacy(
                    UpdatePrivacyRequest(
                        who_can_see_posts = state.defaultPostAudience,
                        story_visibility = state.storyVisibility
                    )
                )

                // Save all to local preferences
                prefs.edit()
                    .putString("sensitive_content_filter", state.sensitiveContentFilter)
                    .putString("video_autoplay", state.videoAutoplay)
                    .putString("video_quality", state.videoQuality)
                    .putBoolean("data_saver_enabled", state.dataSaverEnabled)
                    .putBoolean("reels_autoplay_next", state.reelsAutoplayNext)
                    .putString("story_reply_permission", state.storyReplyPermission)
                    .putBoolean("save_stories_to_archive", state.saveStoriesToArchive)
                    .apply()

                if (response.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            isSaving = false,
                            successMessage = "Feed & content preferences saved successfully."
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSaving = false,
                            successMessage = "Preferences saved locally."
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        successMessage = "Preferences saved locally."
                    )
                }
            }
        }
    }
}
