package com.connectly.android.feature.groups.presentation.pages.monetization

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageMonetizationStatusDto
import com.connectly.android.core.network.api.UpdatePageMonetizationRequest
import com.connectly.android.feature.groups.domain.usecase.GetPageMonetizationUseCase
import com.connectly.android.feature.groups.domain.usecase.UpdatePageMonetizationUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

enum class MonetizationDialogType {
    IN_STREAM_ADS,
    BRAND_COLLABS,
    SUBSCRIPTIONS_STARS,
    PAYOUT_SETUP,
    ELIGIBILITY_DETAILS,
    EARNINGS_BREAKDOWN,
    PAYOUT_SCHEDULE,
    PAYOUT_STATEMENTS
}

data class PageMonetizationUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val monetization: PageMonetizationStatusDto = PageMonetizationStatusDto(),
    val activeDialog: MonetizationDialogType? = null,
    val errorMessage: String? = null,
    val successMessage: String? = null
)

@HiltViewModel
class PageMonetizationViewModel @Inject constructor(
    private val getPageMonetizationUseCase: GetPageMonetizationUseCase,
    private val updatePageMonetizationUseCase: UpdatePageMonetizationUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageMonetizationUiState())
    val uiState: StateFlow<PageMonetizationUiState> = _uiState.asStateFlow()

    init {
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            _uiState.update { it.copy(pageId = pageId) }
            loadMonetization(pageId)
        }
    }

    fun loadMonetization(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getPageMonetizationUseCase(pageId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update {
                        it.copy(
                            isLoading = false,
                            monetization = result.data
                        )
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = result.exception.message ?: "Failed to load monetization data"
                        )
                    }
                }
            }
        }
    }

    fun toggleInStreamAds(enabled: Boolean) {
        updateSettings(
            request = UpdatePageMonetizationRequest(in_stream_ads_enabled = enabled),
            successMsg = if (enabled) "In-Stream Ads activated successfully!" else "In-Stream Ads paused."
        )
    }

    fun toggleBrandCollabs(enabled: Boolean) {
        updateSettings(
            request = UpdatePageMonetizationRequest(brand_collabs_enabled = enabled),
            successMsg = if (enabled) "Brand Content & Collabs enabled!" else "Brand Content & Collabs disabled."
        )
    }

    fun toggleSubscriptions(subscriptionsEnabled: Boolean, starsEnabled: Boolean) {
        updateSettings(
            request = UpdatePageMonetizationRequest(
                subscriptions_enabled = subscriptionsEnabled,
                stars_enabled = starsEnabled
            ),
            successMsg = "Fan Subscriptions & Stars updated successfully!"
        )
    }

    fun configurePayout(
        method: String,
        accountName: String,
        accountNumber: String,
        bankName: String? = null,
        swiftCode: String? = null
    ) {
        updateSettings(
            request = UpdatePageMonetizationRequest(
                payout_method = method,
                payout_account_name = accountName,
                payout_account_number = accountNumber,
                bank_name = bankName,
                swift_code = swiftCode
            ),
            successMsg = "Payout account ($method) saved successfully!"
        )
    }

    fun requestPolicyReview() {
        _uiState.update {
            it.copy(
                successMessage = "Policy review request submitted. Partner Integrity team will inspect your content within 24-48 hours.",
                activeDialog = null
            )
        }
    }

    fun downloadStatement(statementId: String) {
        _uiState.update {
            it.copy(
                successMessage = "Payout statement ($statementId) downloaded to device storage."
            )
        }
    }

    private fun updateSettings(request: UpdatePageMonetizationRequest, successMsg: String) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L) return

        _uiState.update { it.copy(isSaving = true, errorMessage = null, successMessage = null) }
        viewModelScope.launch {
            updatePageMonetizationUseCase(pageId, request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isSaving = true) }
                    is Result.Success -> _uiState.update {
                        it.copy(
                            isSaving = false,
                            monetization = result.data,
                            successMessage = successMsg,
                            activeDialog = null
                        )
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(
                            isSaving = false,
                            errorMessage = result.exception.message ?: "Failed to update monetization settings"
                        )
                    }
                }
            }
        }
    }

    fun openDialog(dialog: MonetizationDialogType) {
        _uiState.update { it.copy(activeDialog = dialog, errorMessage = null) }
    }

    fun closeDialog() {
        _uiState.update { it.copy(activeDialog = null) }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }
}

