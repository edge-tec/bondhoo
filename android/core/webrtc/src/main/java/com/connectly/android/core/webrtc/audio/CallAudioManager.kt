package com.connectly.android.core.webrtc.audio

import android.content.Context
import android.media.AudioAttributes
import android.media.AudioFocusRequest
import android.media.AudioManager
import android.os.Build
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class CallAudioManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val audioManager = context.getSystemService(Context.AUDIO_SERVICE) as AudioManager

    private val _isSpeakerOn = MutableStateFlow(false)
    val isSpeakerOn: StateFlow<Boolean> = _isSpeakerOn.asStateFlow()

    private val _isMuted = MutableStateFlow(false)
    val isMuted: StateFlow<Boolean> = _isMuted.asStateFlow()

    private var audioFocusRequest: AudioFocusRequest? = null
    private var savedAudioMode: Int = AudioManager.MODE_NORMAL
    private var savedIsSpeakerphoneOn: Boolean = false
    private var savedIsMicrophoneMute: Boolean = false

    fun start(enableSpeaker: Boolean = false) {
        savedAudioMode = audioManager.mode
        savedIsSpeakerphoneOn = audioManager.isSpeakerphoneOn
        savedIsMicrophoneMute = audioManager.isMicrophoneMute

        requestAudioFocus()
        audioManager.mode = AudioManager.MODE_IN_COMMUNICATION
        setSpeakerphone(enableSpeaker)
        setMicrophoneMute(false)
    }

    fun stop() {
        abandonAudioFocus()
        audioManager.mode = savedAudioMode
        setSpeakerphone(savedIsSpeakerphoneOn)
        setMicrophoneMute(savedIsMicrophoneMute)
    }

    fun toggleSpeaker(): Boolean {
        val newState = !_isSpeakerOn.value
        setSpeakerphone(newState)
        return newState
    }

    fun setSpeakerphone(enable: Boolean) {
        _isSpeakerOn.value = enable
        audioManager.isSpeakerphoneOn = enable
    }

    fun toggleMute(): Boolean {
        val newState = !_isMuted.value
        setMicrophoneMute(newState)
        return newState
    }

    fun setMicrophoneMute(mute: Boolean) {
        _isMuted.value = mute
        audioManager.isMicrophoneMute = mute
    }

    private fun requestAudioFocus() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val playbackAttributes = AudioAttributes.Builder()
                .setUsage(AudioAttributes.USAGE_VOICE_COMMUNICATION)
                .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
                .build()

            val request = AudioFocusRequest.Builder(AudioManager.AUDIOFOCUS_GAIN_TRANSIENT)
                .setAudioAttributes(playbackAttributes)
                .setAcceptsDelayedFocusGain(true)
                .setOnAudioFocusChangeListener { focusChange ->
                    when (focusChange) {
                        AudioManager.AUDIOFOCUS_LOSS -> stop()
                        AudioManager.AUDIOFOCUS_LOSS_TRANSIENT_CAN_DUCK -> {}
                        AudioManager.AUDIOFOCUS_GAIN -> {}
                    }
                }
                .build()

            audioFocusRequest = request
            audioManager.requestAudioFocus(request)
        } else {
            @Suppress("DEPRECATION")
            audioManager.requestAudioFocus(
                null,
                AudioManager.STREAM_VOICE_CALL,
                AudioManager.AUDIOFOCUS_GAIN_TRANSIENT
            )
        }
    }

    private fun abandonAudioFocus() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            audioFocusRequest?.let { audioManager.abandonAudioFocusRequest(it) }
            audioFocusRequest = null
        } else {
            @Suppress("DEPRECATION")
            audioManager.abandonAudioFocus(null)
        }
    }
}
