package com.connectly.android.core.webrtc.ui

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.viewinterop.AndroidView
import org.webrtc.EglBase
import org.webrtc.RendererCommon
import org.webrtc.SurfaceViewRenderer
import org.webrtc.VideoTrack

/**
 * Production Jetpack Compose wrapper for WebRTC native SurfaceViewRenderer.
 * Efficiently binds hardware-accelerated video frames to the Android view hierarchy.
 */
@Composable
fun WebRtcVideoRenderer(
    videoTrack: VideoTrack?,
    eglBaseContext: EglBase.Context,
    modifier: Modifier = Modifier,
    isMirror: Boolean = false,
    scalingType: RendererCommon.ScalingType = RendererCommon.ScalingType.SCALE_ASPECT_FILL,
    mediaOverlay: Boolean = false
) {
    AndroidView(
        modifier = modifier,
        factory = { context ->
            SurfaceViewRenderer(context).apply {
                init(eglBaseContext, null)
                setScalingType(scalingType)
                setMirror(isMirror)
                setEnableHardwareScaler(true)
                if (mediaOverlay) {
                    setZOrderMediaOverlay(true)
                }
                videoTrack?.let { track ->
                    track.addSink(this)
                    this.tag = track
                }
            }
        },
        update = { renderer ->
            renderer.setMirror(isMirror)
            renderer.setScalingType(scalingType)
            val attachedTrack = renderer.tag as? VideoTrack
            if (attachedTrack != videoTrack) {
                attachedTrack?.removeSink(renderer)
                videoTrack?.addSink(renderer)
                renderer.tag = videoTrack
            }
        },
        onRelease = { renderer ->
            val attachedTrack = renderer.tag as? VideoTrack ?: videoTrack
            attachedTrack?.removeSink(renderer)
            renderer.tag = null
            renderer.release()
        }
    )
}
