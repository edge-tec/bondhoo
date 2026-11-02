package com.connectly.android.feature.profile.presentation.messaging

data class MessagingCallSettingsState(
    // Messaging
    val whoCanMessage: String = "everyone", // "everyone", "friends"
    val messageRequestsEnabled: Boolean = true,
    val readReceiptsEnabled: Boolean = true,
    val typingIndicatorsEnabled: Boolean = true,
    val activeStatusEnabled: Boolean = true,
    val messengerSoundsEnabled: Boolean = true,
    val incomingMessageSound: String = "bubble_pop",
    val incomingMessageVolume: Int = 80,
    val outgoingMessageSound: String = "subtle_sent",
    val outgoingMessageVolume: Int = 80,

    // Voice & Video Calls
    val incomingVoiceCallNotification: Boolean = true,
    val incomingVideoCallNotification: Boolean = true,
    val cameraPermissionGranted: Boolean = false,
    val micPermissionGranted: Boolean = false,
    val callPrivacy: String = "everyone", // "everyone", "friends"
    val incomingCallSoundEnabled: Boolean = true,
    val incomingCallSound: String = "bondhoo_ring",
    val incomingCallVolume: Int = 85,
    val callVibrationEnabled: Boolean = true,

    // UI State
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface MessagingCallSettingsIntent {
    object LoadSettings : MessagingCallSettingsIntent
    object CheckRuntimePermissions : MessagingCallSettingsIntent

    // Messaging
    data class WhoCanMessageChanged(val scope: String) : MessagingCallSettingsIntent
    data class MessageRequestsToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class ReadReceiptsToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class TypingIndicatorsToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class ActiveStatusToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class MessengerSoundsToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class IncomingMessageSoundChanged(val sound: String) : MessagingCallSettingsIntent
    data class IncomingMessageVolumeChanged(val volume: Int) : MessagingCallSettingsIntent
    data class OutgoingMessageSoundChanged(val sound: String) : MessagingCallSettingsIntent

    // Calls
    data class IncomingVoiceCallToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class IncomingVideoCallToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class UpdateCameraPermission(val granted: Boolean) : MessagingCallSettingsIntent
    data class UpdateMicPermission(val granted: Boolean) : MessagingCallSettingsIntent
    data class CallPrivacyChanged(val scope: String) : MessagingCallSettingsIntent
    data class IncomingCallSoundToggled(val enabled: Boolean) : MessagingCallSettingsIntent
    data class IncomingCallSoundChanged(val sound: String) : MessagingCallSettingsIntent
    data class IncomingCallVolumeChanged(val volume: Int) : MessagingCallSettingsIntent
    data class CallVibrationToggled(val enabled: Boolean) : MessagingCallSettingsIntent

    object SaveAllSettings : MessagingCallSettingsIntent
    object DismissMessages : MessagingCallSettingsIntent
}
