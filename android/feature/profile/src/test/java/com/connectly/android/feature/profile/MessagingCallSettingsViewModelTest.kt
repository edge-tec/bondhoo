package com.connectly.android.feature.profile

import android.content.Context
import android.content.SharedPreferences
import android.content.pm.PackageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.presentation.messaging.MessagingCallSettingsIntent
import com.connectly.android.feature.profile.presentation.messaging.MessagingCallSettingsViewModel
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

import androidx.core.content.ContextCompat

@OptIn(ExperimentalCoroutinesApi::class)
class MessagingCallSettingsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val context: Context = mockk(relaxed = true)
    private val sharedPrefs: SharedPreferences = mockk(relaxed = true)
    private val prefsEditor: SharedPreferences.Editor = mockk(relaxed = true)
    private lateinit var viewModel: MessagingCallSettingsViewModel

    private val dummyPrivacy = SettingsPrivacyDto(
        who_can_message = "everyone",
        show_online_status = true,
        read_receipts_enabled = true
    )

    private val dummySounds = MessengerSoundsDto(
        messenger_sounds_enabled = true,
        incoming_call_sound_enabled = true,
        incoming_call_sound = "bondhoo_ring",
        incoming_call_volume = 85,
        incoming_message_sound_enabled = true,
        incoming_message_sound = "bubble_pop",
        incoming_message_volume = 80,
        outgoing_message_sound_enabled = true,
        outgoing_message_sound = "subtle_sent",
        outgoing_message_volume = 80
    )

    @Before
    fun setup() {
        mockkStatic(ContextCompat::class)
        every { ContextCompat.checkSelfPermission(any(), any()) } returns PackageManager.PERMISSION_GRANTED

        Dispatchers.setMain(testDispatcher)
        every { context.getSharedPreferences(any(), any()) } returns sharedPrefs
        every { sharedPrefs.edit() } returns prefsEditor
        every { prefsEditor.putBoolean(any(), any()) } returns prefsEditor
        every { prefsEditor.putString(any(), any()) } returns prefsEditor

        every { context.checkPermission(any(), any(), any()) } returns PackageManager.PERMISSION_DENIED

        coEvery { api.getSettingsPrivacy() } returns Response.success(ApiResponse(data = dummyPrivacy))
        coEvery { api.getMessengerSoundSettings() } returns Response.success(ApiResponse(data = dummySounds))
        coEvery { api.updateSettingsPrivacy(any()) } returns Response.success(ApiResponse(data = dummyPrivacy))
        coEvery { api.updateMessengerSoundSettings(any()) } returns Response.success(ApiResponse(data = dummySounds))

        viewModel = MessagingCallSettingsViewModel(api, context)
    }

    @After
    fun tearDown() {
        unmockkStatic(ContextCompat::class)
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialSettingsLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertEquals("everyone", state.whoCanMessage)
        assertTrue(state.readReceiptsEnabled)
        assertTrue(state.activeStatusEnabled)
        assertTrue(state.messengerSoundsEnabled)
        assertEquals("bubble_pop", state.incomingMessageSound)
        assertEquals("bondhoo_ring", state.incomingCallSound)
    }

    @Test
    fun testRuntimePermissionsUpdate() = runTest {
        viewModel.handleIntent(MessagingCallSettingsIntent.UpdateCameraPermission(true))
        assertTrue(viewModel.uiState.value.cameraPermissionGranted)

        viewModel.handleIntent(MessagingCallSettingsIntent.UpdateMicPermission(true))
        assertTrue(viewModel.uiState.value.micPermissionGranted)
    }

    @Test
    fun testWhoCanMessageAndActiveStatusToggles() = runTest {
        viewModel.handleIntent(MessagingCallSettingsIntent.WhoCanMessageChanged("friends"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("friends", viewModel.uiState.value.whoCanMessage)

        viewModel.handleIntent(MessagingCallSettingsIntent.ActiveStatusToggled(false))
        testDispatcher.scheduler.advanceUntilIdle()
        assertFalse(viewModel.uiState.value.activeStatusEnabled)

        coVerify { api.updateSettingsPrivacy(any()) }
    }

    @Test
    fun testSoundAndRingtonePreferences() = runTest {
        viewModel.handleIntent(MessagingCallSettingsIntent.IncomingMessageSoundChanged("clear_ding"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("clear_ding", viewModel.uiState.value.incomingMessageSound)

        viewModel.handleIntent(MessagingCallSettingsIntent.IncomingCallSoundChanged("celesta_chime"))
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals("celesta_chime", viewModel.uiState.value.incomingCallSound)

        coVerify { api.updateMessengerSoundSettings(any()) }
    }

    @Test
    fun testCallPrivacyAndVibration() = runTest {
        viewModel.handleIntent(MessagingCallSettingsIntent.CallPrivacyChanged("friends"))
        assertEquals("friends", viewModel.uiState.value.callPrivacy)
        verify { prefsEditor.putString("call_privacy", "friends") }

        viewModel.handleIntent(MessagingCallSettingsIntent.CallVibrationToggled(false))
        assertFalse(viewModel.uiState.value.callVibrationEnabled)
        verify { prefsEditor.putBoolean("call_vibration", false) }
    }

    @Test
    fun testExplicitSaveAction() = runTest {
        viewModel.handleIntent(MessagingCallSettingsIntent.SaveAllSettings)
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals("Messaging & call settings saved.", viewModel.uiState.value.successMessage)
    }
}

