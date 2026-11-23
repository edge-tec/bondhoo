package com.connectly.android.core.common

import android.net.Uri

data class PendingSharePayload(
    val text: String? = null,
    val mediaUris: List<Uri> = emptyList()
)

/**
 * In-memory handoff for Android System Share Sheet intents (ACTION_SEND / ACTION_SEND_MULTIPLE)
 * between MainActivity and CreatePostScreen.
 */
object PendingShareStore {
    @Volatile
    private var pending: PendingSharePayload? = null

    fun set(payload: PendingSharePayload) {
        pending = payload
    }

    fun consume(): PendingSharePayload? {
        val current = pending
        pending = null
        return current
    }
}
