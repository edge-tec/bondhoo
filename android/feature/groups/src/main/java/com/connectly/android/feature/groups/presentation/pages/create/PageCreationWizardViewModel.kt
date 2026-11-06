package com.connectly.android.feature.groups.presentation.pages.create

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.CreatePageRequest
import com.connectly.android.core.network.api.PageDto
import com.connectly.android.feature.groups.domain.usecase.CheckPageUsernameUseCase
import com.connectly.android.feature.groups.domain.usecase.CreatePageDetailedUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PageCreationWizardUiState(
    val currentStep: Int = 1,
    val totalSteps: Int = 5,
    // Step 1: Identity & Basics
    val name: String = "",
    val username: String = "",
    val isCheckingUsername: Boolean = false,
    val isUsernameAvailable: Boolean? = null,
    val usernameMessage: String? = null,
    val usernameSuggestions: List<String> = emptyList(),
    val bio: String = "",
    val description: String = "",
    // Step 2: Category
    val category: String = "Business & Finance",
    val subCategory: String = "",
    // Step 3: Contact & Presence
    val website: String = "",
    val email: String = "",
    val phone: String = "",
    val whatsapp: String = "",
    // Step 4: Location
    val address: String = "",
    val city: String = "Dhaka",
    val stateOrDivision: String = "Dhaka",
    val zipCode: String = "",
    val country: String = "Bangladesh",
    // Step 5: Action Button (CTA) & Operating Hours
    val ctaType: String = "send_message", // send_message, whatsapp, call_now, visit_website, book_now, learn_more
    val ctaUrl: String = "",
    val businessHoursType: String = "always_open", // always_open, selected_hours, no_hours
    // Visuals & Branding
    val avatarUrl: String = "",
    val coverUrl: String = "",
    // Submission status
    val isSubmitting: Boolean = false,
    val errorMessage: String? = null,
    val createdPage: PageDto? = null
)

@HiltViewModel
class PageCreationWizardViewModel @Inject constructor(
    private val checkPageUsernameUseCase: CheckPageUsernameUseCase,
    private val createPageDetailedUseCase: CreatePageDetailedUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageCreationWizardUiState())
    val uiState: StateFlow<PageCreationWizardUiState> = _uiState.asStateFlow()

    private var usernameCheckJob: Job? = null

    fun nextStep() {
        if (_uiState.value.currentStep < _uiState.value.totalSteps) {
            _uiState.update { it.copy(currentStep = it.currentStep + 1, errorMessage = null) }
        }
    }

    fun previousStep() {
        if (_uiState.value.currentStep > 1) {
            _uiState.update { it.copy(currentStep = it.currentStep - 1, errorMessage = null) }
        }
    }

    fun goToStep(step: Int) {
        if (step in 1.._uiState.value.totalSteps) {
            _uiState.update { it.copy(currentStep = step, errorMessage = null) }
        }
    }

    fun onNameChanged(name: String) {
        _uiState.update { it.copy(name = name) }
        if (_uiState.value.username.isBlank()) {
            val autoSlug = name.lowercase().replace("[^a-z0-9]".toRegex(), "_").take(30)
            if (autoSlug.length >= 3) {
                onUsernameChanged(autoSlug)
            }
        }
    }

    fun onUsernameChanged(username: String) {
        val clean = username.lowercase().replace("[^a-z0-9_.]".toRegex(), "")
        _uiState.update { it.copy(username = clean, isUsernameAvailable = null, usernameMessage = null) }

        usernameCheckJob?.cancel()
        if (clean.length >= 3) {
            usernameCheckJob = viewModelScope.launch {
                delay(400) // debounce
                _uiState.update { it.copy(isCheckingUsername = true) }
                checkPageUsernameUseCase(clean).collect { result ->
                    when (result) {
                        is Result.Success -> {
                            val data = result.data
                            _uiState.update {
                                it.copy(
                                    isCheckingUsername = false,
                                    isUsernameAvailable = data.available,
                                    usernameMessage = data.message ?: if (data.available) "Username available" else "Username is already taken",
                                    usernameSuggestions = data.suggestions
                                )
                            }
                        }
                        is Result.Error -> {
                            _uiState.update {
                                it.copy(isCheckingUsername = false, isUsernameAvailable = null, usernameMessage = null)
                            }
                        }
                        is Result.Loading -> _uiState.update { it.copy(isCheckingUsername = true) }
                    }
                }
            }
        }
    }

    fun selectUsernameSuggestion(suggestion: String) {
        onUsernameChanged(suggestion)
    }

    fun onBioChanged(bio: String) {
        _uiState.update { it.copy(bio = bio) }
    }

    fun onDescriptionChanged(desc: String) {
        _uiState.update { it.copy(description = desc) }
    }

    fun onCategoryChanged(category: String) {
        _uiState.update { it.copy(category = category) }
    }

    fun onSubCategoryChanged(subCategory: String) {
        _uiState.update { it.copy(subCategory = subCategory) }
    }

    fun onWebsiteChanged(website: String) {
        _uiState.update { it.copy(website = website) }
    }

    fun onEmailChanged(email: String) {
        _uiState.update { it.copy(email = email) }
    }

    fun onPhoneChanged(phone: String) {
        _uiState.update { it.copy(phone = phone) }
    }

    fun onWhatsappChanged(whatsapp: String) {
        _uiState.update { it.copy(whatsapp = whatsapp) }
    }

    fun onAddressChanged(address: String) {
        _uiState.update { it.copy(address = address) }
    }

    fun onCityChanged(city: String) {
        _uiState.update { it.copy(city = city) }
    }

    fun onStateChanged(state: String) {
        _uiState.update { it.copy(stateOrDivision = state) }
    }

    fun onZipCodeChanged(zip: String) {
        _uiState.update { it.copy(zipCode = zip) }
    }

    fun onCountryChanged(country: String) {
        _uiState.update { it.copy(country = country) }
    }

    fun onCtaTypeChanged(ctaType: String) {
        _uiState.update { it.copy(ctaType = ctaType) }
    }

    fun onCtaUrlChanged(ctaUrl: String) {
        _uiState.update { it.copy(ctaUrl = ctaUrl) }
    }

    fun onBusinessHoursTypeChanged(hoursType: String) {
        _uiState.update { it.copy(businessHoursType = hoursType) }
    }

    fun onAvatarUrlChanged(url: String) {
        _uiState.update { it.copy(avatarUrl = url) }
    }

    fun onCoverUrlChanged(url: String) {
        _uiState.update { it.copy(coverUrl = url) }
    }

    fun submitCreatePage(onSuccess: (Long) -> Unit) {
        val s = _uiState.value
        if (s.name.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a Page Name") }
            return
        }

        var websiteUrl = s.website.trim()
        if (websiteUrl.isNotBlank() && !websiteUrl.startsWith("http://") && !websiteUrl.startsWith("https://")) {
            websiteUrl = "https://$websiteUrl"
        }

        val request = CreatePageRequest(
            name = s.name.trim(),
            username = s.username.ifBlank { null },
            category = s.category,
            business_category = s.subCategory.ifBlank { s.category },
            bio = s.bio.ifBlank { null },
            short_description = s.bio.ifBlank { null },
            description = s.description.ifBlank { s.bio.ifBlank { null } },
            website = websiteUrl.ifBlank { null },
            email = s.email.ifBlank { null },
            phone = s.phone.ifBlank { null },
            whatsapp = s.whatsapp.ifBlank { null },
            address = s.address.ifBlank { null },
            country = s.country.ifBlank { "Bangladesh" },
            city = s.city.ifBlank { null },
            state = s.stateOrDivision.ifBlank { null },
            zip_code = s.zipCode.ifBlank { null },
            opening_hours = s.businessHoursType,
            cta_type = s.ctaType,
            cta_url = s.ctaUrl.ifBlank { null },
            avatar_url = s.avatarUrl.ifBlank { null },
            cover_image_url = s.coverUrl.ifBlank { null },
            visibility = "public",
            publishing_status = "published"
        )

        viewModelScope.launch {
            createPageDetailedUseCase(request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
                    is Result.Success -> {
                        _uiState.update { it.copy(isSubmitting = false, createdPage = result.data) }
                        onSuccess(result.data.id)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isSubmitting = false,
                                errorMessage = result.exception.message ?: "Failed to create business page"
                            )
                        }
                    }
                }
            }
        }
    }
}

