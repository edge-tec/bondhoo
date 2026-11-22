package com.connectly.android.core.media.player

import android.content.Context
import android.net.Uri
import androidx.annotation.OptIn
import androidx.media3.common.C
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.database.StandaloneDatabaseProvider
import androidx.media3.datasource.DataSpec
import androidx.media3.datasource.DefaultHttpDataSource
import androidx.media3.datasource.cache.CacheDataSource
import androidx.media3.datasource.cache.CacheWriter
import androidx.media3.datasource.cache.LeastRecentlyUsedCacheEvictor
import androidx.media3.datasource.cache.SimpleCache
import androidx.media3.exoplayer.DefaultLoadControl
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.exoplayer.source.DefaultMediaSourceFactory
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import java.io.File
import java.util.concurrent.ConcurrentHashMap
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
@OptIn(UnstableApi::class)
class ConnectlyMedia3PlayerManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val playerPool = ConcurrentHashMap<String, ExoPlayer>()
    private val preloadScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    // 100 MB LRU disk cache for instant Reels & Feed video playback with zero re-buffering
    private val simpleCache: SimpleCache by lazy {
        val cacheDir = File(context.cacheDir, "bondhoo_media3_cache")
        val evictor = LeastRecentlyUsedCacheEvictor(100L * 1024L * 1024L)
        val dbProvider = StandaloneDatabaseProvider(context)
        SimpleCache(cacheDir, evictor, dbProvider)
    }

    private val cacheDataSourceFactory: CacheDataSource.Factory by lazy {
        val upstreamFactory = DefaultHttpDataSource.Factory()
            .setAllowCrossProtocolRedirects(true)
            .setConnectTimeoutMs(8000)
            .setReadTimeoutMs(8000)

        CacheDataSource.Factory()
            .setCache(simpleCache)
            .setUpstreamDataSourceFactory(upstreamFactory)
            .setFlags(CacheDataSource.FLAG_IGNORE_CACHE_ON_ERROR)
    }

    /**
     * Preloads the first ~1.5 MB of an upcoming Reel/video into disk cache for instant zero-lag swipe.
     */
    fun preloadVideo(videoUrl: String, bytesToPreload: Long = 1_500_000L) {
        if (videoUrl.isBlank()) return
        preloadScope.launch {
            try {
                val dataSpec = DataSpec.Builder()
                    .setUri(Uri.parse(videoUrl))
                    .setLength(bytesToPreload)
                    .build()
                val cacheDataSource = cacheDataSourceFactory.createDataSourceForDownloading()
                val writer = CacheWriter(cacheDataSource, dataSpec, null, null)
                writer.cache()
            } catch (_: Exception) {
                // Best-effort preload; player falls back to live stream transparently
            }
        }
    }

    /**
     * Obtains or creates a tuned, disk-cached ExoPlayer instance for the given video URL / tag.
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
                /* maxBufferMs = */ 8000,
                /* bufferForPlaybackMs = */ 400,
                /* bufferForPlaybackAfterRebufferMs = */ 800
            )
            .build()

        val mediaSourceFactory = DefaultMediaSourceFactory(context)
            .setDataSourceFactory(cacheDataSourceFactory)

        val player = ExoPlayer.Builder(context)
            .setMediaSourceFactory(mediaSourceFactory)
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
