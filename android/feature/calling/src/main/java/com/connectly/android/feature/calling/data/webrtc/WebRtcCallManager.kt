package com.connectly.android.feature.calling.data.webrtc

import android.content.Context
import android.media.AudioAttributes
import android.media.AudioFocusRequest
import android.media.AudioManager
import android.os.Build
import com.connectly.android.core.model.NetworkQuality
import com.connectly.android.core.webrtc.audio.CallAudioManager
import com.connectly.android.core.webrtc.engine.WebRtcEngine
import com.connectly.android.core.webrtc.signaling.WebRtcSignalingCoordinator
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class WebRtcCallManager @Inject constructor(
    @ApplicationContext private val context: Context,
    val callAudioManager: CallAudioManager? = null,
    val webRtcEngine: WebRtcEngine? = null,
    val signalingCoordinator: WebRtcSignalingCoordinator? = null
) {
    private val audioManager = context.getSystemService(Context.AUDIO_SERVICE) as AudioManager

    private val _isMuted = MutableStateFlow(false)
    val isMuted: StateFlow<Boolean> = _isMuted.asStateFlow()

    private val _isCameraOff = MutableStateFlow(false)
    val isCameraOff: StateFlow<Boolean> = _isCameraOff.asStateFlow()

    private val _isFrontCamera = MutableStateFlow(true)
    val isFrontCamera: StateFlow<Boolean> = _isFrontCamera.asStateFlow()

    private val _isSpeakerOn = MutableStateFlow(false)
    val isSpeakerOn: StateFlow<Boolean> = _isSpeakerOn.asStateFlow()

    private val _networkQuality = MutableStateFlow(NetworkQuality.EXCELLENT)
    val networkQuality: StateFlow<NetworkQuality> = _networkQuality.asStateFlow()

    private val _isConnected = MutableStateFlow(false)
    val isConnected: StateFlow<Boolean> = _isConnected.asStateFlow()

    val localVideoTrack = webRtcEngine?.localVideoTrack
    val remoteVideoTrack = webRtcEngine?.remoteVideoTrack
    val eglBaseContext get() = webRtcEngine?.eglBaseContext

    private var audioFocusRequest: AudioFocusRequest? = null

    /**
     * Acquire audio focus and configure audio routing for communication.
     */
    fun startAudioSession(enableSpeaker: Boolean = false) {
        if (callAudioManager != null) {
            callAudioManager.start(enableSpeaker)
            _isSpeakerOn.value = callAudioManager.isSpeakerOn.value
            _isMuted.value = callAudioManager.isMuted.value
            _isConnected.value = true
            return
        }

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
                        AudioManager.AUDIOFOCUS_LOSS -> endAudioSession()
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

        audioManager.mode = AudioManager.MODE_IN_COMMUNICATION
        setSpeakerphoneOn(enableSpeaker)
        audioManager.isMicrophoneMute = false
        _isMuted.value = false
        _isConnected.value = true
    }

    /**
     * Release audio focus and reset hardware state.
     */
    fun endAudioSession() {
        if (callAudioManager != null) {
            callAudioManager.stop()
            _isConnected.value = false
            _isMuted.value = false
            _isSpeakerOn.value = false
            webRtcEngine?.endCall(notifySignaling = false)
            return
        }

        _isConnected.value = false
        audioManager.isMicrophoneMute = false
        setSpeakerphoneOn(false)
        audioManager.mode = AudioManager.MODE_NORMAL

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            audioFocusRequest?.let { audioManager.abandonAudioFocusRequest(it) }
            audioFocusRequest = null
        } else {
            @Suppress("DEPRECATION")
            audioManager.abandonAudioFocus(null)
        }
    }

    fun toggleMute(): Boolean {
        if (callAudioManager != null) {
            val newMute = callAudioManager.toggleMute()
            _isMuted.value = newMute
            webRtcEngine?.toggleMute()
            return newMute
        }
        val newMute = !_isMuted.value
        _isMuted.value = newMute
        audioManager.isMicrophoneMute = newMute
        return newMute
    }

    fun toggleCamera(): Boolean {
        if (webRtcEngine != null) {
            val newCamState = !webRtcEngine.toggleCamera()
            _isCameraOff.value = newCamState
            return newCamState
        }
        val newCamState = !_isCameraOff.value
        _isCameraOff.value = newCamState
        return newCamState
    }

    fun switchCamera(): Boolean {
        if (webRtcEngine != null) {
            val isFront = webRtcEngine.switchCamera()
            _isFrontCamera.value = isFront
            return isFront
        }
        val newFrontState = !_isFrontCamera.value
        _isFrontCamera.value = newFrontState
        return newFrontState
    }

    fun toggleSpeaker(): Boolean {
        if (callAudioManager != null) {
            val newSpeaker = callAudioManager.toggleSpeaker()
            _isSpeakerOn.value = newSpeaker
            return newSpeaker
        }
        val newSpeaker = !_isSpeakerOn.value
        setSpeakerphoneOn(newSpeaker)
        return newSpeaker
    }

    private fun setSpeakerphoneOn(on: Boolean) {
        _isSpeakerOn.value = on
        audioManager.isSpeakerphoneOn = on
    }

    fun updateNetworkQuality(quality: NetworkQuality) {
        _networkQuality.value = quality
    }
}
