package com.connectly.android.core.media.player

import android.content.Context
import androidx.annotation.OptIn
import androidx.media3.common.C
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.DefaultLoadControl
import androidx.media3.exoplayer.ExoPlayer
import dagger.hilt.android.qualifiers.ApplicationContext
import java.util.concurrent.ConcurrentHashMap
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
@OptIn(UnstableApi::class)
class ConnectlyMedia3PlayerManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val playerPool = ConcurrentHashMap<String, ExoPlayer>()

    /**
     * Obtains or creates a tuned ExoPlayer instance for the given video URL / tag.
     */
    fun getOrCreatePlayer(tag: String, videoUrl: String, isMuted: Boolean = false): ExoPlayer {
        val existing = playerPool[tag]
        if (existing != null) {
            existing.volume = if (isMuted) 0f else 1f
            return existing
        }

        val loadControl = DefaultLoadControl.Builder()
            .setBufferDurationsMs(
                /* minBufferMs = */ 1500,
                /* maxBufferMs = */ 5000,
                /* bufferForPlaybackMs = */ 500,
                /* bufferForPlaybackAfterRebufferMs = */ 1000
            )
            .build()

        val player = ExoPlayer.Builder(context)
            .setLoadControl(loadControl)
            .build()
            .apply {
                repeatMode = Player.REPEAT_MODE_ALL
                volume = if (isMuted) 0f else 1f
                videoScalingMode = C.VIDEO_SCALING_MODE_SCALE_TO_FIT_WITH_CROPPING
                setMediaItem(MediaItem.fromUri(videoUrl))
                prepare()
            }

        playerPool[tag] = player
        return player
    }

    /**
     * Pause playback for a specific player tag.
     */
    fun pausePlayer(tag: String) {
        playerPool[tag]?.pause()
    }

    /**
     * Release player instance and remove from pool to prevent memory leaks.
     */
    fun releasePlayer(tag: String) {
        playerPool.remove(tag)?.let { player ->
            player.stop()
            player.release()
        }
    }

    /**
     * Releases all active player instances in the pool.
     */
    fun releaseAll() {
        playerPool.forEach { (_, player) ->
            player.stop()
            player.release()
        }
        playerPool.clear()
    }
}

