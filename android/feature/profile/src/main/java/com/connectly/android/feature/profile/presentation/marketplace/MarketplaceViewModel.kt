package com.connectly.android.feature.profile.presentation.marketplace

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File
import javax.inject.Inject

enum class MarketplaceTab {
    DISCOVER,
    CATEGORIES,
    MY_LISTINGS,
    SAVED,
    MY_ORDERS,
    SELLING_DASHBOARD
}

data class MarketplaceUiState(
    val products: List<MarketplaceProductDto> = emptyList(),
    val myListings: List<MarketplaceProductDto> = emptyList(),
    val savedProducts: List<MarketplaceProductDto> = emptyList(),
    val categoryList: List<MarketplaceCategoryDto> = emptyList(),
    val categories: List<String> = listOf("All", "Electronics", "Fashion", "Vehicles", "Property", "Home & Living", "Sports", "Books"),
    val selectedCategory: String = "All",
    val selectedSubcategory: String? = null,
    val searchQuery: String = "",
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val selectedCondition: String? = null,
    val sortOption: String = "newest", // newest, price_low, price_high, popular
    val activeTab: MarketplaceTab = MarketplaceTab.DISCOVER,
    val myOffers: List<MarketplaceOfferDto> = emptyList(),
    val receivedOffers: List<MarketplaceOfferDto> = emptyList(),
    val orders: List<MarketplaceOrderDto> = emptyList(),
    val sellerSummary: MarketplaceSellerSummaryDto? = null,
    val buyerSummary: MarketplaceBuyerSummaryDto? = null,
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val selectedProduct: MarketplaceProductDto? = null,
    val showCreateDialog: Boolean = false,
    val showMessageDialog: Boolean = false,
    val showOrderDialog: Boolean = false,
    val showOfferDialog: Boolean = false,
    val showReportDialog: Boolean = false,
    val showCounterDialog: Boolean = false,
    val selectedOfferForCounter: MarketplaceOfferDto? = null
)

@HiltViewModel
class MarketplaceViewModel @Inject constructor(
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(MarketplaceUiState())
    val uiState: StateFlow<MarketplaceUiState> = _uiState.asStateFlow()

    init {
        loadCategories()
        loadProducts()
    }

    fun loadCategories() {
        viewModelScope.launch {
            try {
                val response = api.getMarketplaceCategories()
                if (response.isSuccessful && response.body() != null) {
                    val catList = response.body()!!.data
                    val names = listOf("All") + catList.map { it.name }
                    _uiState.update { it.copy(categoryList = catList, categories = names) }
                }
            } catch (e: Exception) {
                // Keep default categories if network fetch fails
            }
        }
    }

    fun setCategory(category: String) {
        _uiState.update { it.copy(selectedCategory = category, selectedSubcategory = null) }
        loadProducts()
    }

    fun setSubcategory(subcategorySlug: String?) {
        _uiState.update { it.copy(selectedSubcategory = subcategorySlug) }
        loadProducts()
    }

    fun setPriceRange(min: Double?, max: Double?) {
        _uiState.update { it.copy(minPrice = min, maxPrice = max) }
        loadProducts()
    }

    fun setCondition(condition: String?) {
        _uiState.update { it.copy(selectedCondition = condition) }
        loadProducts()
    }

    fun setSort(sort: String) {
        _uiState.update { it.copy(sortOption = sort) }
        loadProducts()
    }

    fun setSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun executeSearch() {
        loadProducts()
    }

    fun setTab(tab: MarketplaceTab) {
        _uiState.update { it.copy(activeTab = tab) }
        when (tab) {
            MarketplaceTab.DISCOVER -> loadProducts()
            MarketplaceTab.CATEGORIES -> loadCategories()
            MarketplaceTab.MY_LISTINGS -> loadMyListings()
            MarketplaceTab.SAVED -> loadSavedListings()
            MarketplaceTab.MY_ORDERS -> loadOrders()
            MarketplaceTab.SELLING_DASHBOARD -> loadSellerDashboard()
        }
    }

    fun openProductDetails(product: MarketplaceProductDto) {
        _uiState.update { it.copy(selectedProduct = product) }
    }

    fun closeProductDetails() {
        _uiState.update { it.copy(selectedProduct = null) }
    }

    fun openCreateDialog() {
        _uiState.update { it.copy(showCreateDialog = true) }
    }

    fun closeCreateDialog() {
        _uiState.update { it.copy(showCreateDialog = false) }
    }

    fun openMessageDialog() {
        _uiState.update { it.copy(showMessageDialog = true) }
    }

    fun closeMessageDialog() {
        _uiState.update { it.copy(showMessageDialog = false) }
    }

    fun openOrderDialog() {
        _uiState.update { it.copy(showOrderDialog = true) }
    }

    fun closeOrderDialog() {
        _uiState.update { it.copy(showOrderDialog = false) }
    }

    fun openOfferDialog() {
        _uiState.update { it.copy(showOfferDialog = true) }
    }

    fun closeOfferDialog() {
        _uiState.update { it.copy(showOfferDialog = false) }
    }

    fun openReportDialog() {
        _uiState.update { it.copy(showReportDialog = true) }
    }

    fun closeReportDialog() {
        _uiState.update { it.copy(showReportDialog = false) }
    }

    fun openCounterDialog(offer: MarketplaceOfferDto) {
        _uiState.update { it.copy(showCounterDialog = true, selectedOfferForCounter = offer) }
    }

    fun closeCounterDialog() {
        _uiState.update { it.copy(showCounterDialog = false, selectedOfferForCounter = null) }
    }

    fun clearMessages() {
        _uiState.update { it.copy(errorMessage = null, successMessage = null) }
    }

    fun loadProducts() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val state = _uiState.value
                val catQuery = if (state.selectedCategory == "All") null else state.selectedCategory
                val qQuery = state.searchQuery.takeIf { it.isNotBlank() }
                val response = api.getMarketplaceProducts(
                    category = catQuery,
                    subcategory = state.selectedSubcategory,
                    query = qQuery,
                    minPrice = state.minPrice,
                    maxPrice = state.maxPrice,
                    condition = state.selectedCondition,
                    sort = state.sortOption
                )
                if (response.isSuccessful && response.body() != null) {
                    val list = response.body()!!.data
                    _uiState.update { it.copy(products = list, isLoading = false) }
                } else {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Failed to load products: ${response.code()}"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Network connection error"
                    )
                }
            }
        }
    }

    fun loadMyListings() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val response = api.getMyMarketplaceListings()
                if (response.isSuccessful && response.body() != null) {
                    val list = response.body()!!.data
                    _uiState.update { it.copy(myListings = list, isLoading = false) }
                } else {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Could not retrieve your listings: ${response.code()}"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Error loading your items"
                    )
                }
            }
        }
    }

    fun loadSavedListings() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val response = api.getSavedItems()
                if (response.isSuccessful && response.body() != null) {
                    val saved = response.body()!!.data
                    val savedProductIds = saved.map { it.item_id }.toSet()
                    val filtered = _uiState.value.products.filter { it.id in savedProductIds }
                    _uiState.update { it.copy(savedProducts = filtered, isLoading = false) }
                } else {
                    val localSaved = _uiState.value.products.filter { it.is_favorited }
                    _uiState.update { it.copy(savedProducts = localSaved, isLoading = false) }
                }
            } catch (e: Exception) {
                val localSaved = _uiState.value.products.filter { it.is_favorited }
                _uiState.update { it.copy(savedProducts = localSaved, isLoading = false) }
            }
        }
    }

    fun loadOrders() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val response = api.getMarketplaceOrders()
                if (response.isSuccessful && response.body() != null) {
                    _uiState.update { it.copy(orders = response.body()!!.data, isLoading = false) }
                } else {
                    _uiState.update { it.copy(isLoading = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false) }
            }
        }
    }

    fun loadSellerDashboard() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val summaryRes = api.getMarketplaceSellerDashboard()
                val offersRes = api.getReceivedMarketplaceOffers()
                val ordersRes = api.getMarketplaceOrders()
                val listingsRes = api.getMyMarketplaceListings()

                _uiState.update { state ->
                    state.copy(
                        isLoading = false,
                        sellerSummary = summaryRes.body()?.data,
                        receivedOffers = offersRes.body()?.data ?: emptyList(),
                        orders = ordersRes.body()?.data ?: emptyList(),
                        myListings = listingsRes.body()?.data ?: state.myListings
                    )
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false) }
            }
        }
    }

    fun loadMyOffers() {
        viewModelScope.launch {
            try {
                val response = api.getMyMarketplaceOffers()
                if (response.isSuccessful && response.body() != null) {
                    _uiState.update { it.copy(myOffers = response.body()!!.data) }
                }
            } catch (_: Exception) {}
        }
    }

    fun submitOffer(productId: Long, amount: Double, message: String?) {
        if (amount <= 0) {
            _uiState.update { it.copy(errorMessage = "Please enter a valid offer amount") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val request = CreateOfferRequest(offer_amount = amount, message = message)
                val response = api.submitMarketplaceOffer(productId, request)
                if (response.isSuccessful && response.body() != null) {
                    val offer = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            showOfferDialog = false,
                            myOffers = listOf(offer) + state.myOffers,
                            successMessage = "Offer of ৳${amount.toLong()} submitted to seller!"
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = "Could not submit offer: ${response.code()}"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Failed to submit offer"
                    )
                }
            }
        }
    }

    fun acceptOffer(offerId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            try {
                val response = api.acceptMarketplaceOffer(offerId)
                if (response.isSuccessful) {
                    _uiState.update { state ->
                        val updated = state.receivedOffers.map {
                            if (it.id == offerId) it.copy(status = "accepted") else it
                        }
                        state.copy(
                            isSubmitting = false,
                            receivedOffers = updated,
                            successMessage = "Offer accepted! Buyer has been notified."
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = "Failed to accept offer") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error accepting offer") }
            }
        }
    }

    fun rejectOffer(offerId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            try {
                val response = api.rejectMarketplaceOffer(offerId)
                if (response.isSuccessful) {
                    _uiState.update { state ->
                        val updated = state.receivedOffers.map {
                            if (it.id == offerId) it.copy(status = "rejected") else it
                        }
                        state.copy(
                            isSubmitting = false,
                            receivedOffers = updated,
                            successMessage = "Offer declined."
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = "Failed to decline offer") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error declining offer") }
            }
        }
    }

    fun counterOffer(offerId: Long, counterAmount: Double, counterMessage: String?) {
        if (counterAmount <= 0) {
            _uiState.update { it.copy(errorMessage = "Please enter a valid counter amount") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            try {
                val request = CounterOfferRequest(counter_amount = counterAmount, counter_message = counterMessage)
                val response = api.counterMarketplaceOffer(offerId, request)
                if (response.isSuccessful) {
                    _uiState.update { state ->
                        val updated = state.receivedOffers.map {
                            if (it.id == offerId) it.copy(status = "countered", counter_amount = counterAmount) else it
                        }
                        state.copy(
                            isSubmitting = false,
                            showCounterDialog = false,
                            selectedOfferForCounter = null,
                            receivedOffers = updated,
                            successMessage = "Counteroffer sent to buyer!"
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = "Failed to counter offer") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error countering offer") }
            }
        }
    }

    fun cancelOffer(offerId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            try {
                val response = api.cancelMarketplaceOffer(offerId)
                if (response.isSuccessful) {
                    _uiState.update { state ->
                        val updated = state.myOffers.map {
                            if (it.id == offerId) it.copy(status = "cancelled") else it
                        }
                        state.copy(
                            isSubmitting = false,
                            myOffers = updated,
                            successMessage = "Offer cancelled."
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = "Failed to cancel offer") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error cancelling offer") }
            }
        }
    }

    fun reportProduct(productId: Long, reason: String, details: String?) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true) }
            try {
                val request = ReportMarketplaceRequest(reason = reason, details = details)
                val response = api.reportMarketplaceProduct(productId, request)
                if (response.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showReportDialog = false,
                            successMessage = "Report submitted. Our moderation team will inspect this listing."
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = "Failed to submit report") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error reporting product") }
            }
        }
    }

    fun toggleFavorite(product: MarketplaceProductDto) {
        val newFav = !product.is_favorited
        _uiState.update { state ->
            val updated = state.products.map {
                if (it.id == product.id) it.copy(is_favorited = newFav) else it
            }
            val updatedSaved = if (newFav) {
                listOf(product.copy(is_favorited = true)) + state.savedProducts.filter { it.id != product.id }
            } else {
                state.savedProducts.filter { it.id != product.id }
            }
            state.copy(products = updated, savedProducts = updatedSaved)
        }

        viewModelScope.launch {
            try {
                val response = api.toggleFavoriteMarketplaceProduct(product.id)
                val isFav = response.body()?.is_favorited ?: newFav
                _uiState.update { state ->
                    val updatedProducts = state.products.map {
                        if (it.id == product.id) it.copy(is_favorited = isFav) else it
                    }
                    val updatedSelected = if (state.selectedProduct?.id == product.id) {
                        state.selectedProduct.copy(is_favorited = isFav)
                    } else state.selectedProduct

                    state.copy(
                        products = updatedProducts,
                        selectedProduct = updatedSelected,
                        successMessage = if (isFav) "Added to saved items" else "Removed from saved items"
                    )
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(errorMessage = "Failed to update favorite status") }
            }
        }
    }

    fun createListing(
        title: String,
        categoryId: Long,
        subcategoryId: Long? = null,
        price: Double,
        originalPrice: Double? = null,
        condition: String,
        brand: String? = null,
        location: String,
        deliveryType: String = "pickup",
        deliveryCharge: Double = 0.0,
        isNegotiable: Boolean = false,
        description: String,
        imageUrl: String? = null,
        imageFiles: List<File> = emptyList(),
        additionalUrls: List<String> = emptyList()
    ) {
        if (title.isBlank() || price <= 0.0) {
            _uiState.update { it.copy(errorMessage = "Please provide a title and valid price") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val uploadedUrls = mutableListOf<String>()

                // 1. Upload local files to server if any
                for (file in imageFiles) {
                    try {
                        val reqFile = file.asRequestBody("image/*".toMediaTypeOrNull())
                        val part = MultipartBody.Part.createFormData("image", file.name, reqFile)
                        val uploadRes = api.uploadMarketplaceMedia(part)
                        if (uploadRes.isSuccessful && uploadRes.body() != null) {
                            val mediaData = uploadRes.body()!!.data
                            val url = mediaData?.url ?: mediaData?.resolvedUrl
                            if (!url.isNullOrBlank()) {
                                uploadedUrls.add(url)
                            }
                        }
                    } catch (_: Exception) {
                        // Keep going for remaining files
                    }
                }

                // 2. Aggregate final images
                val finalImages = mutableListOf<String>()
                finalImages.addAll(uploadedUrls)
                if (!imageUrl.isNullOrBlank()) {
                    finalImages.add(imageUrl.trim())
                }
                finalImages.addAll(additionalUrls.filter { it.isNotBlank() })

                val request = CreateMarketplaceProductRequest(
                    category_id = categoryId,
                    subcategory_id = subcategoryId,
                    title = title.trim(),
                    description = description.trim(),
                    price = price,
                    original_price = originalPrice,
                    currency = "BDT",
                    condition = condition,
                    brand = brand?.trim()?.takeIf { it.isNotBlank() },
                    location = location.trim(),
                    delivery_type = deliveryType,
                    delivery_charge = deliveryCharge,
                    is_negotiable = isNegotiable,
                    images = finalImages.distinct()
                )
                val response = api.createMarketplaceProduct(request)
                if (response.isSuccessful && response.body() != null) {
                    val created = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            showCreateDialog = false,
                            products = listOf(created) + state.products,
                            myListings = listOf(created) + state.myListings,
                            successMessage = "Listing created successfully!"
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = "Failed to publish listing (${response.code()})"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Error creating listing"
                    )
                }
            }
        }
    }

    fun contactSeller(productId: Long, messageText: String) {
        if (messageText.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Message cannot be empty") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.contactMarketplaceSeller(productId, ContactSellerRequest(message = messageText.trim()))
                if (response.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showMessageDialog = false,
                            successMessage = "Message sent to seller! They will receive a notification."
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = "Failed to send message: ${response.code()}"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Failed to connect to seller"
                    )
                }
            }
        }
    }

    fun orderProduct(
        productId: Long,
        shippingName: String,
        shippingPhone: String,
        shippingAddress: String,
        quantity: Int = 1
    ) {
        if (shippingName.isBlank() || shippingPhone.isBlank() || shippingAddress.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please fill in all shipping details") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val request = OrderMarketplaceProductRequest(
                    shipping_name = shippingName.trim(),
                    shipping_phone = shippingPhone.trim(),
                    shipping_address = shippingAddress.trim(),
                    quantity = quantity,
                    payment_method = "sandbox"
                )
                val response = api.orderMarketplaceProduct(productId, request)
                if (response.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showOrderDialog = false,
                            successMessage = "Order placed successfully! Escrow protection is active."
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = "Could not place order: ${response.code()}"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Network error placing order"
                    )
                }
            }
        }
    }

    fun deleteListing(productId: Long) {
        if (_uiState.value.isSubmitting) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.deleteMarketplaceProduct(productId)
                val code = response.code()
                val body = response.body()

                if (response.isSuccessful) {
                    val isExplicitFailure = body != null && !body.success && body.status == "error"
                    if (isExplicitFailure) {
                        _uiState.update { state ->
                            state.copy(
                                isSubmitting = false,
                                errorMessage = body.message ?: "Failed to delete listing"
                            )
                        }
                    } else {
                        val msg = body?.message ?: "Listing removed successfully"
                        _uiState.update { state ->
                            state.copy(
                                isSubmitting = false,
                                products = state.products.filter { it.id != productId },
                                myListings = state.myListings.filter { it.id != productId },
                                savedProducts = state.savedProducts.filter { it.id != productId },
                                selectedProduct = null,
                                successMessage = msg
                            )
                        }
                    }
                } else if (code == 404) {
                    // Listing is absent from server; sync UI state accordingly
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            products = state.products.filter { it.id != productId },
                            myListings = state.myListings.filter { it.id != productId },
                            savedProducts = state.savedProducts.filter { it.id != productId },
                            selectedProduct = null,
                            successMessage = "Listing was already removed from marketplace"
                        )
                    }
                } else {
                    val serverMsg = try {
                        response.errorBody()?.string()?.let { errJson ->
                            "\"message\"\\s*:\\s*\"([^\"]+)\"".toRegex().find(errJson)?.groupValues?.get(1)
                        }
                    } catch (_: Exception) { null }

                    val fallbackMsg = when (code) {
                        401 -> "Session expired. Please log in again to manage listings."
                        403 -> "Unauthorized to delete this listing."
                        422 -> "Cannot delete listing at this time."
                        in 500..599 -> "Server error occurred. Please try again later ($code)."
                        else -> "Failed to delete listing ($code)"
                    }

                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            errorMessage = serverMsg ?: fallbackMsg
                        )
                    }
                }
            } catch (e: Exception) {
                val isNetworkError = e is java.io.IOException || e is java.net.SocketTimeoutException
                val msg = if (isNetworkError) {
                    "Network connection unavailable. Please check your connection and retry."
                } else {
                    e.localizedMessage ?: "Failed to delete listing due to an unexpected error."
                }
                _uiState.update { state ->
                    state.copy(
                        isSubmitting = false,
                        errorMessage = msg
                    )
                }
            }
        }
    }
}
