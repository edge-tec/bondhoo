package com.connectly.android.core.media.model

import com.connectly.android.core.network.api.MediaUploadResponse

sealed interface UploadStatus {
    object Idle : UploadStatus
    data class Progress(
        val sessionId: String,
        val currentChunk: Int,
        val totalChunks: Int,
        val progressPercent: Float,
        val bytesUploaded: Long,
        val totalBytes: Long
    ) : UploadStatus
    data class Success(val sessionId: String, val media: MediaUploadResponse) : UploadStatus
    data class Error(val message: String, val canRetry: Boolean = true) : UploadStatus
    object Cancelled : UploadStatus
}

