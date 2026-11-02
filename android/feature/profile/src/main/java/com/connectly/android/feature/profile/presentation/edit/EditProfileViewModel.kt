package com.connectly.android.feature.profile.presentation.edit

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.profile.domain.usecase.GetProfileUseCase
import com.connectly.android.feature.profile.domain.usecase.UpdateProfileUseCase
import com.connectly.android.feature.profile.domain.usecase.UploadAvatarUseCase
import com.connectly.android.feature.profile.domain.usecase.UploadCoverUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.io.File
import javax.inject.Inject

@HiltViewModel
class EditProfileViewModel @Inject constructor(
    private val api: ConnectlyApi,
    private val userDao: UserDao,
    private val tokenStorageManager: TokenStorageManager,
    private val getProfileUseCase: GetProfileUseCase,
    private val updateProfileUseCase: UpdateProfileUseCase,
    private val uploadAvatarUseCase: UploadAvatarUseCase,
    private val uploadCoverUseCase: UploadCoverUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(EditProfileState())
    val uiState: StateFlow<EditProfileState> = _uiState.asStateFlow()

    init {
        loadAccountData()
    }

    private fun loadAccountData() {
        val currentUserId = tokenStorageManager.getUserId()
        if (currentUserId > 0) {
            viewModelScope.launch {
                userDao.getUserById(currentUserId).collect { userEntity ->
                    if (userEntity != null) {
                        _uiState.update { state ->
                            state.copy(
                                userId = userEntity.id,
                                name = if (state.name.isBlank()) userEntity.name else state.name,
                                username = if (state.username.isBlank()) userEntity.username else state.username,
                                email = userEntity.email,
                                avatarUrl = userEntity.avatar,
                                bio = userEntity.bio ?: state.bio,
                                isIdentityVerified = userEntity.isVerified
                            )
                        }
                    }
                }
            }
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val response = api.getAccountSettings()
                if (response.isSuccessful && response.body() != null) {
                    val acc = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isLoading = false,
                            userId = acc.id,
                            name = acc.name.takeIf { it.isNotBlank() } ?: state.name,
                            username = acc.username.takeIf { it.isNotBlank() } ?: state.username,
                            email = acc.email ?: state.email,
                            phone = acc.phone ?: state.phone,
                            bio = acc.bio ?: state.bio,
                            avatarUrl = acc.avatar ?: state.avatarUrl,
                            createdAt = acc.created_at?.take(10) ?: state.createdAt
                        )
                    }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (_: Exception) {
                _uiState.update { it.copy(isLoading = false) }
            }
        }

        // Also fetch profile for occupation and location
        viewModelScope.launch {
            getProfileUseCase("me").collect { result ->
                if (result is Result.Success) {
                    val user = result.data
                    _uiState.update { state ->
                        state.copy(
                            occupation = user.occupation ?: state.occupation,
                            location = user.location ?: state.location,
                            coverUrl = user.coverPhoto ?: state.coverUrl,
                            isEmailVerified = user.email.isNotBlank(),
                            isIdentityVerified = user.isVerified
                        )
                    }
                }
            }
        }
    }

    fun handleIntent(intent: EditProfileIntent) {
        when (intent) {
            is EditProfileIntent.NameChanged -> _uiState.update { it.copy(name = intent.name) }
            is EditProfileIntent.UsernameChanged -> _uiState.update { it.copy(username = intent.username) }
            is EditProfileIntent.PhoneChanged -> _uiState.update { it.copy(phone = intent.phone) }
            is EditProfileIntent.BioChanged -> _uiState.update { it.copy(bio = intent.bio) }
            is EditProfileIntent.OccupationChanged -> _uiState.update { it.copy(occupation = intent.occupation) }
            is EditProfileIntent.LocationChanged -> _uiState.update { it.copy(location = intent.location) }
            is EditProfileIntent.AvatarSelected -> uploadAvatar(intent.file)
            is EditProfileIntent.CoverSelected -> uploadCover(intent.file)
            EditProfileIntent.SaveProfile -> saveProfile()
            is EditProfileIntent.OpenDialog -> _uiState.update { it.copy(activeDialog = intent.type) }
            EditProfileIntent.DismissDialog -> _uiState.update { it.copy(activeDialog = null) }
            is EditProfileIntent.ChangePassword -> changePassword(intent.currentPass, intent.newPass, intent.confirmPass)
            is EditProfileIntent.DeactivateAccount -> deactivateAccount(intent.password, intent.reason)
            is EditProfileIntent.DeleteAccount -> deleteAccount(intent.password, intent.confirmation, intent.reason)
            EditProfileIntent.DismissMessages -> _uiState.update { it.copy(successMessage = null, errorMessage = null) }
        }
    }

    private fun uploadAvatar(file: File) {
        viewModelScope.launch {
            uploadAvatarUseCase(file).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> {
                        _uiState.update { it.copy(isLoading = false, avatarUrl = result.data, successMessage = "Profile photo updated successfully!") }
                        syncLocalUser()
                    }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = "Avatar upload failed") }
                }
            }
        }
    }

    private fun uploadCover(file: File) {
        viewModelScope.launch {
            uploadCoverUseCase(file).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, coverUrl = result.data, successMessage = "Cover photo updated successfully!") }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = "Cover upload failed") }
                }
            }
        }
    }

    private fun saveProfile() {
        val s = _uiState.value
        if (s.name.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Name cannot be empty.") }
            return
        }

        if (s.username.length < 3) {
            _uiState.update { it.copy(errorMessage = "Username must be at least 3 characters.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                // 1. Update Core Account Info
                val accResp = api.updateAccountSettings(
                    UpdateAccountRequest(
                        name = s.name.trim(),
                        username = s.username.trim(),
                        phone = s.phone.trim().takeIf { it.isNotBlank() },
                        bio = s.bio.trim().takeIf { it.isNotBlank() }
                    )
                )

                // 2. Update Extended Profile Info (occupation, location)
                updateProfileUseCase(s.bio, s.occupation, s.location).collect { }

                if (accResp.isSuccessful) {
                    syncLocalUser()
                    _uiState.update { it.copy(isLoading = false, isSaved = true, successMessage = "Profile updated successfully!") }
                } else {
                    val rawErr = accResp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Profile update failed.")
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Network error.") }
            }
        }
    }

    private fun changePassword(currentPass: String, newPass: String, confirmPass: String) {
        if (currentPass.isBlank() || newPass.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please fill out all password fields.") }
            return
        }
        if (newPass.length < 8) {
            _uiState.update { it.copy(errorMessage = "New password must be at least 8 characters.") }
            return
        }
        if (newPass != confirmPass) {
            _uiState.update { it.copy(errorMessage = "New passwords do not match.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, activeDialog = null) }
            try {
                val resp = api.updatePassword(
                    UpdatePasswordRequest(
                        current_password = currentPass,
                        password = newPass,
                        password_confirmation = confirmPass
                    )
                )
                if (resp.isSuccessful) {
                    _uiState.update { it.copy(isLoading = false, successMessage = "Password changed successfully!") }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to change password. Current password may be incorrect.")
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Failed to change password.") }
            }
        }
    }

    private fun deactivateAccount(password: String, reason: String) {
        if (password.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Password is required to deactivate account.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, activeDialog = null) }
            try {
                val resp = api.deactivateAccount(
                    DeactivateAccountRequest(
                        password = password,
                        reason = reason.takeIf { it.isNotBlank() } ?: "User requested deactivation"
                    )
                )
                if (resp.isSuccessful) {
                    tokenStorageManager.clearAll()
                    _uiState.update { it.copy(isLoading = false, isTerminated = true) }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to deactivate account. Incorrect password.")
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Network error.") }
            }
        }
    }

    private fun deleteAccount(password: String, confirmation: String, reason: String) {
        if (confirmation != "DELETE") {
            _uiState.update { it.copy(errorMessage = "Please type DELETE to confirm permanent deletion.") }
            return
        }
        if (password.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Password is required to permanently delete account.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, activeDialog = null) }
            try {
                val resp = api.deleteAccount(
                    DeleteAccountRequest(
                        password = password,
                        confirmation = "DELETE",
                        reason = reason.takeIf { it.isNotBlank() } ?: "User requested account deletion"
                    )
                )
                if (resp.isSuccessful) {
                    tokenStorageManager.clearAll()
                    _uiState.update { it.copy(isLoading = false, isTerminated = true) }
                } else {
                    val rawErr = resp.errorBody()?.string()
                    val msg = parseErrorMessage(rawErr, "Failed to delete account. Incorrect password.")
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.localizedMessage ?: "Network error.") }
            }
        }
    }

    private fun syncLocalUser() {
        val s = _uiState.value
        if (s.userId > 0) {
            viewModelScope.launch {
                userDao.insertUser(
                    UserEntity(
                        id = s.userId,
                        name = s.name,
                        username = s.username,
                        email = s.email,
                        avatar = s.avatarUrl,
                        bio = s.bio,
                        isVerified = s.isIdentityVerified,
                        isOnline = true
                    )
                )
            }
        }
    }

    private fun parseErrorMessage(rawError: String?, fallback: String): String {
        if (rawError.isNullOrBlank()) return fallback
        return try {
            val json = JSONObject(rawError)
            when {
                json.has("message") -> json.getString("message")
                json.has("error") -> json.getString("error")
                else -> rawError
            }
        } catch (_: Exception) {
            rawError
        }
    }
}
