package com.connectly.android.feature.profile.presentation.messaging

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import androidx.core.content.ContextCompat
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.*
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
class MessagingCallSettingsViewModel @Inject constructor(
    private val api: ConnectlyApi,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val prefs = context.getSharedPreferences("connectly_messaging_call_prefs", Context.MODE_PRIVATE)

    private val _uiState = MutableStateFlow(MessagingCallSettingsState())
    val uiState: StateFlow<MessagingCallSettingsState> = _uiState.asStateFlow()

    init {
        checkRuntimePermissions()
        loadLocalPreferences()
        loadRemoteSettings()
    }

    fun checkRuntimePermissions() {
        val hasCamera = ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED
        val hasMic = ContextCompat.checkSelfPermission(context, Manifest.permission.RECORD_AUDIO) == PackageManager.PERMISSION_GRANTED

        _uiState.update {
            it.copy(
                cameraPermissionGranted = hasCamera,
                micPermissionGranted = hasMic
            )
        }
    }

    private fun loadLocalPreferences() {
        val messageRequests = prefs.getBoolean("message_requests", true)
        val typing = prefs.getBoolean("typing_indicators", true)
        val voiceCallAlert = prefs.getBoolean("voice_call_alert", true)
        val videoCallAlert = prefs.getBoolean("video_call_alert", true)
        val callVibration = prefs.getBoolean("call_vibration", true)
        val callPrivacy = prefs.getString("call_privacy", "everyone") ?: "everyone"

        _uiState.update {
            it.copy(
                messageRequestsEnabled = messageRequests,
                typingIndicatorsEnabled = typing,
                incomingVoiceCallNotification = voiceCallAlert,
                incomingVideoCallNotification = videoCallAlert,
                callVibrationEnabled = callVibration,
                callPrivacy = callPrivacy
            )
        }
    }

    fun handleIntent(intent: MessagingCallSettingsIntent) {
        when (intent) {
            MessagingCallSettingsIntent.LoadSettings -> loadRemoteSettings()
            MessagingCallSettingsIntent.CheckRuntimePermissions -> checkRuntimePermissions()

            // Messaging
            is MessagingCallSettingsIntent.WhoCanMessageChanged -> {
                _uiState.update { it.copy(whoCanMessage = intent.scope) }
                autoSave()
            }
            is MessagingCallSettingsIntent.MessageRequestsToggled -> {
                _uiState.update { it.copy(messageRequestsEnabled = intent.enabled) }
                prefs.edit().putBoolean("message_requests", intent.enabled).apply()
            }
            is MessagingCallSettingsIntent.ReadReceiptsToggled -> {
                _uiState.update { it.copy(readReceiptsEnabled = intent.enabled) }
                autoSave()
            }
            is MessagingCallSettingsIntent.TypingIndicatorsToggled -> {
                _uiState.update { it.copy(typingIndicatorsEnabled = intent.enabled) }
                prefs.edit().putBoolean("typing_indicators", intent.enabled).apply()
            }
            is MessagingCallSettingsIntent.ActiveStatusToggled -> {
                _uiState.update { it.copy(activeStatusEnabled = intent.enabled) }
                autoSave()
            }
            is MessagingCallSettingsIntent.MessengerSoundsToggled -> {
                _uiState.update { it.copy(messengerSoundsEnabled = intent.enabled) }
                autoSave()
            }
            is MessagingCallSettingsIntent.IncomingMessageSoundChanged -> {
                _uiState.update { it.copy(incomingMessageSound = intent.sound) }
                autoSave()
            }
            is MessagingCallSettingsIntent.IncomingMessageVolumeChanged -> {
                _uiState.update { it.copy(incomingMessageVolume = intent.volume) }
                autoSave()
            }
            is MessagingCallSettingsIntent.OutgoingMessageSoundChanged -> {
                _uiState.update { it.copy(outgoingMessageSound = intent.sound) }
                autoSave()
            }

            // Calls
            is MessagingCallSettingsIntent.IncomingVoiceCallToggled -> {
                _uiState.update { it.copy(incomingVoiceCallNotification = intent.enabled) }
                prefs.edit().putBoolean("voice_call_alert", intent.enabled).apply()
            }
            is MessagingCallSettingsIntent.IncomingVideoCallToggled -> {
                _uiState.update { it.copy(incomingVideoCallNotification = intent.enabled) }
                prefs.edit().putBoolean("video_call_alert", intent.enabled).apply()
            }
            is MessagingCallSettingsIntent.UpdateCameraPermission -> {
                _uiState.update { it.copy(cameraPermissionGranted = intent.granted) }
            }
            is MessagingCallSettingsIntent.UpdateMicPermission -> {
                _uiState.update { it.copy(micPermissionGranted = intent.granted) }
            }
            is MessagingCallSettingsIntent.CallPrivacyChanged -> {
                _uiState.update { it.copy(callPrivacy = intent.scope) }
                prefs.edit().putString("call_privacy", intent.scope).apply()
            }
            is MessagingCallSettingsIntent.IncomingCallSoundToggled -> {
                _uiState.update { it.copy(incomingCallSoundEnabled = intent.enabled) }
                autoSave()
            }
            is MessagingCallSettingsIntent.IncomingCallSoundChanged -> {
                _uiState.update { it.copy(incomingCallSound = intent.sound) }
                autoSave()
            }
            is MessagingCallSettingsIntent.IncomingCallVolumeChanged -> {
                _uiState.update { it.copy(incomingCallVolume = intent.volume) }
                autoSave()
            }
            is MessagingCallSettingsIntent.CallVibrationToggled -> {
                _uiState.update { it.copy(callVibrationEnabled = intent.enabled) }
                prefs.edit().putBoolean("call_vibration", intent.enabled).apply()
            }

            MessagingCallSettingsIntent.SaveAllSettings -> saveAllSettings()
            MessagingCallSettingsIntent.DismissMessages -> {
                _uiState.update { it.copy(successMessage = null, errorMessage = null) }
            }
        }
    }

    private fun loadRemoteSettings() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                // 1. Privacy settings (who_can_message, read_receipts, online_status)
                val privResp = api.getSettingsPrivacy()
                if (privResp.isSuccessful && privResp.body()?.data != null) {
                    val p = privResp.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            whoCanMessage = p.who_can_message,
                            readReceiptsEnabled = p.read_receipts_enabled,
                            activeStatusEnabled = p.show_online_status
                        )
                    }
                }

                // 2. Sound settings
                val soundResp = api.getMessengerSoundSettings()
                if (soundResp.isSuccessful && soundResp.body()?.data != null) {
                    val s = soundResp.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            messengerSoundsEnabled = s.messenger_sounds_enabled,
                            incomingCallSoundEnabled = s.incoming_call_sound_enabled,
                            incomingCallSound = s.incoming_call_sound,
                            incomingCallVolume = s.incoming_call_volume,
                            incomingMessageSound = s.incoming_message_sound,
                            incomingMessageVolume = s.incoming_message_volume,
                            outgoingMessageSound = s.outgoing_message_sound,
                            outgoingMessageVolume = s.outgoing_message_volume
                        )
                    }
                }

                _uiState.update { it.copy(isLoading = false) }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Failed to load settings.") }
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
                // 1. Sync Privacy settings
                api.updateSettingsPrivacy(
                    UpdateSettingsPrivacyRequest(
                        who_can_message = s.whoCanMessage,
                        read_receipts_enabled = s.readReceiptsEnabled,
                        show_online_status = s.activeStatusEnabled
                    )
                )

                // 2. Sync Sounds settings
                val soundResp = api.updateMessengerSoundSettings(
                    UpdateMessengerSoundsRequest(
                        messenger_sounds_enabled = s.messengerSoundsEnabled,
                        incoming_call_sound_enabled = s.incomingCallSoundEnabled,
                        incoming_call_sound = s.incomingCallSound,
                        incoming_call_volume = s.incomingCallVolume,
                        incoming_message_sound_enabled = s.messengerSoundsEnabled,
                        incoming_message_sound = s.incomingMessageSound,
                        incoming_message_volume = s.incomingMessageVolume,
                        outgoing_message_sound_enabled = s.messengerSoundsEnabled,
                        outgoing_message_sound = s.outgoingMessageSound,
                        outgoing_message_volume = s.outgoingMessageVolume
                    )
                )

                if (soundResp.isSuccessful) {
                    if (!silent) {
                        _uiState.update { it.copy(isSaving = false, successMessage = "Messaging & call settings saved.") }
                    }
                } else {
                    val rawErr = soundResp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to update settings.")
                    _uiState.update { it.copy(isSaving = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSaving = false, errorMessage = e.localizedMessage ?: "Network connection error.") }
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

