package com.connectly.android.feature.profile

import android.content.Context
import android.content.SharedPreferences
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.feedpreferences.FeedPreferencesIntent
import com.connectly.android.feature.profile.presentation.feedpreferences.FeedPreferencesViewModel
import io.mockk.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class FeedPreferencesViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val context: Context = mockk(relaxed = true)
    private val sharedPrefs: SharedPreferences = mockk(relaxed = true)
    private val prefsEditor: SharedPreferences.Editor = mockk(relaxed = true)
    private lateinit var viewModel: FeedPreferencesViewModel

    private val dummyPrivacy = SettingsPrivacyDto(
        who_can_see_posts = "friends",
        story_visibility = "public"
    )

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { context.getSharedPreferences(any(), any()) } returns sharedPrefs
        every { sharedPrefs.edit() } returns prefsEditor
        every { prefsEditor.putBoolean(any(), any()) } returns prefsEditor
        every { prefsEditor.putString(any(), any()) } returns prefsEditor
        every { prefsEditor.putInt(any(), any()) } returns prefsEditor

        every { sharedPrefs.getString("sensitive_content_filter", any()) } returns "standard"
        every { sharedPrefs.getString("video_autoplay", any()) } returns "wifi_only"
        every { sharedPrefs.getString("video_quality", any()) } returns "auto"
        every { sharedPrefs.getBoolean("data_saver_enabled", any()) } returns false
        every { sharedPrefs.getBoolean("reels_autoplay_next", any()) } returns true
        every { sharedPrefs.getString("story_reply_permission", any()) } returns "everyone"
        every { sharedPrefs.getBoolean("save_stories_to_archive", any()) } returns true
        every { sharedPrefs.getInt("snoozed_accounts_count", any()) } returns 2
        every { sharedPrefs.getInt("hidden_posts_count", any()) } returns 5
        every { sharedPrefs.getInt("favorites_count", any()) } returns 4

        coEvery { api.getSettingsPrivacy() } returns Response.success(ApiResponse(data = dummyPrivacy))
        coEvery { api.updateSettingsPrivacy(any()) } returns Response.success(ApiResponse(data = dummyPrivacy))

        viewModel = FeedPreferencesViewModel(api, context)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialPreferencesLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals("friends", state.defaultPostAudience)
        assertEquals("public", state.storyVisibility)
        assertEquals("wifi_only", state.videoAutoplay)
        assertEquals("auto", state.videoQuality)
        assertFalse(state.dataSaverEnabled)
        assertTrue(state.reelsAutoplayNext)
        assertEquals("everyone", state.storyReplyPermission)
        assertTrue(state.saveStoriesToArchive)
        assertEquals(2, state.snoozedAccountsCount)
        assertEquals(5, state.hiddenPostsCount)
        assertEquals(4, state.favoritesCount)
    }

    @Test
    fun testAudienceAndStoryVisibilitySync() = runTest {
        viewModel.handleIntent(FeedPreferencesIntent.DefaultPostAudienceChanged("only_me"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("only_me", viewModel.uiState.value.defaultPostAudience)
        coVerify { api.updateSettingsPrivacy(match { it.who_can_see_posts == "only_me" }) }

        viewModel.handleIntent(FeedPreferencesIntent.StoryVisibilityChanged("friends"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("friends", viewModel.uiState.value.storyVisibility)
        coVerify { api.updateSettingsPrivacy(match { it.story_visibility == "friends" }) }
    }

    @Test
    fun testPlaybackPreferencesToggles() = runTest {
        viewModel.handleIntent(FeedPreferencesIntent.VideoAutoplayChanged("never"))
        assertEquals("never", viewModel.uiState.value.videoAutoplay)
        verify { prefsEditor.putString("video_autoplay", "never") }

        viewModel.handleIntent(FeedPreferencesIntent.VideoQualityChanged("high"))
        assertEquals("high", viewModel.uiState.value.videoQuality)
        verify { prefsEditor.putString("video_quality", "high") }

        viewModel.handleIntent(FeedPreferencesIntent.DataSaverToggled(true))
        assertTrue(viewModel.uiState.value.dataSaverEnabled)
        verify { prefsEditor.putBoolean("data_saver_enabled", true) }

        viewModel.handleIntent(FeedPreferencesIntent.ReelsAutoplayNextToggled(false))
        assertFalse(viewModel.uiState.value.reelsAutoplayNext)
        verify { prefsEditor.putBoolean("reels_autoplay_next", false) }
    }

    @Test
    fun testClearHiddenPostsAndSnoozedAccounts() = runTest {
        viewModel.handleIntent(FeedPreferencesIntent.ClearHiddenPosts)
        assertEquals(0, viewModel.uiState.value.hiddenPostsCount)
        assertEquals("Hidden posts list cleared.", viewModel.uiState.value.successMessage)
        verify { prefsEditor.putInt("hidden_posts_count", 0) }

        viewModel.handleIntent(FeedPreferencesIntent.ClearSnoozedAccounts)
        assertEquals(0, viewModel.uiState.value.snoozedAccountsCount)
        assertEquals("Snoozed accounts list reset.", viewModel.uiState.value.successMessage)
        verify { prefsEditor.putInt("snoozed_accounts_count", 0) }
    }

    @Test
    fun testSaveAllPreferences() = runTest {
        viewModel.handleIntent(FeedPreferencesIntent.SaveAllPreferences)
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals("Feed & content preferences saved successfully.", viewModel.uiState.value.successMessage)
        coVerify { api.updateSettingsPrivacy(any()) }
    }
}

