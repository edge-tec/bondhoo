package com.connectly.android.feature.profile.presentation.edit

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
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
import java.io.File
import javax.inject.Inject

@HiltViewModel
class EditProfileViewModel @Inject constructor(
    private val getProfileUseCase: GetProfileUseCase,
    private val updateProfileUseCase: UpdateProfileUseCase,
    private val uploadAvatarUseCase: UploadAvatarUseCase,
    private val uploadCoverUseCase: UploadCoverUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(EditProfileState())
    val uiState: StateFlow<EditProfileState> = _uiState.asStateFlow()

    init {
        loadCurrentProfile()
    }

    private fun loadCurrentProfile() {
        viewModelScope.launch {
            getProfileUseCase("me").collect { result ->
                if (result is Result.Success) {
                    val user = result.data
                    _uiState.update {
                        it.copy(
                            name = user.name,
                            bio = user.bio ?: "Dream Big • Work Hard • Be Happy",
                            occupation = user.occupation ?: "Software Engineer",
                            location = user.location ?: "Dhaka, Bangladesh",
                            avatarUrl = user.avatar,
                            coverUrl = user.coverPhoto
                        )
                    }
                }
            }
        }
    }

    fun handleIntent(intent: EditProfileIntent) {
        when (intent) {
            is EditProfileIntent.BioChanged -> _uiState.update { it.copy(bio = intent.bio) }
            is EditProfileIntent.OccupationChanged -> _uiState.update { it.copy(occupation = intent.occupation) }
            is EditProfileIntent.LocationChanged -> _uiState.update { it.copy(location = intent.location) }
            is EditProfileIntent.AvatarSelected -> uploadAvatar(intent.file)
            is EditProfileIntent.CoverSelected -> uploadCover(intent.file)
            EditProfileIntent.SaveProfile -> saveProfile()
        }
    }

    private fun uploadAvatar(file: File) {
        viewModelScope.launch {
            uploadAvatarUseCase(file).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, avatarUrl = result.data) }
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
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, coverUrl = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = "Cover upload failed") }
                }
            }
        }
    }

    private fun saveProfile() {
        val s = _uiState.value
        viewModelScope.launch {
            updateProfileUseCase(s.bio, s.occupation, s.location).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, isSaved = true) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = "Profile update failed") }
                }
            }
        }
    }
}

