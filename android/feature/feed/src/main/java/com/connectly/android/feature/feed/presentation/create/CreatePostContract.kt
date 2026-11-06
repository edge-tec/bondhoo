package com.connectly.android.feature.feed.presentation.create

import android.net.Uri
import com.connectly.android.core.model.User
import java.io.File

data class CreatePostState(
    val content: String = "",
    val audience: String = "public", // "public", "friends", "only_me"
    val mediaFiles: List<File> = emptyList(),
    val mediaUris: List<Uri> = emptyList(),
    val feelingActivity: String? = null,
    val feelingEmoji: String? = null,
    val taggedUsers: List<User> = emptyList(),
    val location: String? = null,
    val isLoading: Boolean = false,
    val isPosted: Boolean = false,
    val errorMessage: String? = null
)

sealed interface CreatePostIntent {
    data class ContentChanged(val content: String) : CreatePostIntent
    data class AudienceChanged(val audience: String) : CreatePostIntent
    data class MediaAdded(val file: File, val uri: Uri? = null) : CreatePostIntent
    data class MediaRemoved(val file: File) : CreatePostIntent
    data class FeelingSelected(val feeling: String?, val emoji: String?) : CreatePostIntent
    data class TaggedUsersChanged(val users: List<User>) : CreatePostIntent
    data class LocationChanged(val location: String?) : CreatePostIntent
    object SubmitPost : CreatePostIntent
}

