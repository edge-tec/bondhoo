package com.connectly.android.feature.groups.presentation.pages.settings

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageSettingsDto
import com.connectly.android.core.network.api.RequestPageVerificationRequest
import com.connectly.android.feature.groups.domain.usecase.DeletePageUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageSettingsUseCase
import com.connectly.android.feature.groups.domain.usecase.RequestPageVerificationUseCase
import com.connectly.android.feature.groups.domain.usecase.UpdatePageSettingsUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PageSettingsUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = false,
    val settings: PageSettingsDto = PageSettingsDto(),
    val errorMessage: String? = null,
    val isSaving: Boolean = false,
    val saveSuccess: Boolean = false,
    val pageDeleted: Boolean = false,
    val verificationStatus: String = "unverified",
    val isRequestingVerification: Boolean = false,
    val verificationMessage: String? = null
)

@HiltViewModel
class PageSettingsViewModel @Inject constructor(
    private val getPageSettingsUseCase: GetPageSettingsUseCase,
    private val updatePageSettingsUseCase: UpdatePageSettingsUseCase,
    private val deletePageUseCase: DeletePageUseCase,
    private val requestPageVerificationUseCase: RequestPageVerificationUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageSettingsUiState())
    val uiState: StateFlow<PageSettingsUiState> = _uiState.asStateFlow()

    init {
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            _uiState.update { it.copy(pageId = pageId) }
            loadSettings(pageId)
        }
    }

    fun loadSettings(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getPageSettingsUseCase(pageId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update {
                        it.copy(
                            isLoading = false,
                            settings = result.data,
                            verificationStatus = result.data.verification_status
                        )
                    }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    fun updateSettings(newSettings: PageSettingsDto) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L) return

        _uiState.update { it.copy(isSaving = true, saveSuccess = false, errorMessage = null) }
        viewModelScope.launch {
            updatePageSettingsUseCase(pageId, newSettings).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isSaving = true) }
                    is Result.Success -> _uiState.update {
                        it.copy(
                            isSaving = false,
                            saveSuccess = true,
                            settings = result.data,
                            verificationStatus = result.data.verification_status
                        )
                    }
                    is Result.Error -> _uiState.update { it.copy(isSaving = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    fun requestVerification(
        verificationType: String = "business",
        legalName: String? = null,
        registrationNumber: String? = null,
        taxId: String? = null,
        notes: String? = null,
        onSuccess: () -> Unit = {}
    ) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L) return

        val request = RequestPageVerificationRequest(
            verification_type = verificationType,
            legal_name = legalName?.ifBlank { null },
            registration_number = registrationNumber?.ifBlank { null },
            tax_id = taxId?.ifBlank { null },
            additional_info = notes?.ifBlank { null }
        )

        _uiState.update { it.copy(isRequestingVerification = true, verificationMessage = null, errorMessage = null) }
        viewModelScope.launch {
            requestPageVerificationUseCase(pageId, request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isRequestingVerification = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isRequestingVerification = false,
                                verificationStatus = result.data.status,
                                verificationMessage = "Verification request submitted successfully! Under review."
                            )
                        }
                        onSuccess()
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isRequestingVerification = false,
                                errorMessage = result.exception.message ?: "Failed to submit verification request"
                            )
                        }
                    }
                }
            }
        }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, verificationMessage = null, saveSuccess = false) }
    }

    fun deletePage(onSuccess: () -> Unit) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L) return

        viewModelScope.launch {
            deletePageUseCase(pageId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(pageDeleted = true) }
                    onSuccess()
                }
            }
        }
    }
}

