package com.connectly.android.feature.profile.presentation.edit

import java.io.File

data class EditProfileState(
    val userId: Long = 0L,
    val name: String = "",
    val username: String = "",
    val email: String = "",
    val phone: String = "",
    val bio: String = "",
    val occupation: String = "",
    val location: String = "",
    val avatarUrl: String? = null,
    val coverUrl: String? = null,
    val isEmailVerified: Boolean = false,
    val isPhoneVerified: Boolean = false,
    val isIdentityVerified: Boolean = false,
    val createdAt: String = "",
    val isLoading: Boolean = false,
    val isSaved: Boolean = false,
    val isTerminated: Boolean = false,
    val activeDialog: AccountDialogType? = null,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

enum class AccountDialogType {
    CHANGE_PASSWORD,
    DEACTIVATE_ACCOUNT,
    DELETE_ACCOUNT
}

sealed interface EditProfileIntent {
    data class NameChanged(val name: String) : EditProfileIntent
    data class UsernameChanged(val username: String) : EditProfileIntent
    data class PhoneChanged(val phone: String) : EditProfileIntent
    data class BioChanged(val bio: String) : EditProfileIntent
    data class OccupationChanged(val occupation: String) : EditProfileIntent
    data class LocationChanged(val location: String) : EditProfileIntent
    data class AvatarSelected(val file: File) : EditProfileIntent
    data class CoverSelected(val file: File) : EditProfileIntent
    object SaveProfile : EditProfileIntent
    data class OpenDialog(val type: AccountDialogType) : EditProfileIntent
    object DismissDialog : EditProfileIntent
    data class ChangePassword(val currentPass: String, val newPass: String, val confirmPass: String) : EditProfileIntent
    data class DeactivateAccount(val password: String, val reason: String) : EditProfileIntent
    data class DeleteAccount(val password: String, val confirmation: String, val reason: String) : EditProfileIntent
    object DismissMessages : EditProfileIntent
}
