package com.connectly.android.feature.feed.presentation.create

import java.io.File

data class CreatePostState(
    val content: String = "",
    val audience: String = "public", // "public", "friends", "only_me"
    val mediaFiles: List<File> = emptyList(),
    val isLoading: Boolean = false,
    val isPosted: Boolean = false,
    val errorMessage: String? = null
)

sealed interface CreatePostIntent {
    data class ContentChanged(val content: String) : CreatePostIntent
    data class AudienceChanged(val audience: String) : CreatePostIntent
    data class MediaAdded(val file: File) : CreatePostIntent
    data class MediaRemoved(val file: File) : CreatePostIntent
    object SubmitPost : CreatePostIntent
}

