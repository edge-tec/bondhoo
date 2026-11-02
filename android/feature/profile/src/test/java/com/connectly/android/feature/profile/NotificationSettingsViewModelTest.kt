package com.connectly.android.feature.profile

import android.content.Context
import android.content.SharedPreferences
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.notifications.NotificationSettingsIntent
import com.connectly.android.feature.profile.presentation.notifications.NotificationSettingsViewModel
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
class NotificationSettingsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val context: Context = mockk(relaxed = true)
    private val sharedPrefs: SharedPreferences = mockk(relaxed = true)
    private val prefsEditor: SharedPreferences.Editor = mockk(relaxed = true)
    private lateinit var viewModel: NotificationSettingsViewModel

    private val dummyRemotePrefs = NotificationPreferencesDto(
        email_notifications = true,
        sms_notifications = false,
        push_notifications = true,
        friend_request_alerts = true,
        friend_accepted_alerts = true,
        comment_alerts = true,
        mention_alerts = true,
        message_alerts = true,
        like_alerts = true,
        share_alerts = true,
        follower_alerts = true,
        page_alerts = true,
        group_alerts = true,
        security_alerts = true
    )

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { context.getSharedPreferences(any(), any()) } returns sharedPrefs
        every { sharedPrefs.edit() } returns prefsEditor
        every { prefsEditor.putBoolean(any(), any()) } returns prefsEditor
        every { prefsEditor.putString(any(), any()) } returns prefsEditor

        coEvery { api.getNotificationPreferences() } returns Response.success(ApiResponse(success = true, data = dummyRemotePrefs))
        coEvery { api.updateNotificationPreferences(any()) } returns Response.success(ApiResponse(success = true, data = dummyRemotePrefs))

        viewModel = NotificationSettingsViewModel(api, context)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialPreferencesLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertTrue(state.masterNotificationsEnabled)
        assertTrue(state.pushNotifications)
        assertTrue(state.emailNotifications)
        assertTrue(state.likeAlerts)
        assertTrue(state.commentAlerts)
        assertTrue(state.friendRequestAlerts)
        assertTrue(state.securityAlerts)
    }

    @Test
    fun testMasterToggle() = runTest {
        viewModel.handleIntent(NotificationSettingsIntent.MasterToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()

        assertFalse(viewModel.uiState.value.masterNotificationsEnabled)
        verify { prefsEditor.putBoolean("master_notifications", false) }
    }

    @Test
    fun testDoNotDisturbAndQuietHours() = runTest {
        viewModel.handleIntent(NotificationSettingsIntent.DoNotDisturbToggled(true))
        assertTrue(viewModel.uiState.value.doNotDisturb)
        verify { prefsEditor.putBoolean("do_not_disturb", true) }

        viewModel.handleIntent(NotificationSettingsIntent.QuietHoursToggled(true))
        assertTrue(viewModel.uiState.value.quietHoursEnabled)
        verify { prefsEditor.putBoolean("quiet_hours_enabled", true) }

        viewModel.handleIntent(NotificationSettingsIntent.QuietHoursTimeChanged("23:00", "08:00"))
        assertEquals("23:00", viewModel.uiState.value.quietHoursStart)
        assertEquals("08:00", viewModel.uiState.value.quietHoursEnd)
        verify { prefsEditor.putString("quiet_hours_start", "23:00") }
        verify { prefsEditor.putString("quiet_hours_end", "08:00") }
    }

    @Test
    fun testCategoryTogglesAndBackendSync() = runTest {
        viewModel.handleIntent(NotificationSettingsIntent.LikeAlertsToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()
        assertFalse(viewModel.uiState.value.likeAlerts)

        viewModel.handleIntent(NotificationSettingsIntent.MessageAlertsToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()
        assertFalse(viewModel.uiState.value.messageAlerts)

        coVerify { api.updateNotificationPreferences(any()) }
    }

    @Test
    fun testSecurityAlertsIntegrity() = runTest {
        // Toggle marketing off
        viewModel.handleIntent(NotificationSettingsIntent.MarketingAlertsToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()

        // Security alerts must stay active
        assertTrue(viewModel.uiState.value.securityAlerts)
    }

    @Test
    fun testExplicitSaveAction() = runTest {
        viewModel.handleIntent(NotificationSettingsIntent.SaveAllSettings)
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals("Notification preferences updated.", viewModel.uiState.value.successMessage)
    }
}
