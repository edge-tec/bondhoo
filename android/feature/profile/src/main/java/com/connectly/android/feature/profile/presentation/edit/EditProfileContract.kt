package com.connectly.android.feature.profile.presentation.edit

import java.io.File

data class EditProfileState(
    val name: String = "",
    val bio: String = "",
    val occupation: String = "",
    val location: String = "",
    val avatarUrl: String? = null,
    val coverUrl: String? = null,
    val isLoading: Boolean = false,
    val isSaved: Boolean = false,
    val errorMessage: String? = null
)

sealed interface EditProfileIntent {
    data class BioChanged(val bio: String) : EditProfileIntent
    data class OccupationChanged(val occupation: String) : EditProfileIntent
    data class LocationChanged(val location: String) : EditProfileIntent
    data class AvatarSelected(val file: File) : EditProfileIntent
    data class CoverSelected(val file: File) : EditProfileIntent
    object SaveProfile : EditProfileIntent
}
