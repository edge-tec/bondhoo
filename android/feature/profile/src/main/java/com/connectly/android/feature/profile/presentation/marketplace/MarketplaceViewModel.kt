package com.connectly.android.feature.profile.presentation.marketplace

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File
import java.util.UUID
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
    val sortOption: String = "newest",
    val activeTab: MarketplaceTab = MarketplaceTab.DISCOVER,
    val myOffers: List<MarketplaceOfferDto> = emptyList(),
    val receivedOffers: List<MarketplaceOfferDto> = emptyList(),
    val orders: List<MarketplaceOrderDto> = emptyList(),
    val sellerOrders: List<MarketplaceOrderDto> = emptyList(),
    val sellerSummary: MarketplaceSellerSummaryDto? = null,
    val buyerSummary: MarketplaceBuyerSummaryDto? = null,
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val deletingProductIds: Set<Long> = emptySet(),
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val selectedProduct: MarketplaceProductDto? = null,
    val editingProduct: MarketplaceProductDto? = null,
    val showCreateDialog: Boolean = false,
    val showEditDialog: Boolean = false,
    val showMessageDialog: Boolean = false,
    val showOrderDialog: Boolean = false,
    val showOfferDialog: Boolean = false,
    val showReportDialog: Boolean = false,
    val showCounterDialog: Boolean = false,
    val selectedOfferForCounter: MarketplaceOfferDto? = null,
    val pendingChatConversationId: Long? = null
)

@HiltViewModel
class MarketplaceViewModel @Inject constructor(
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(MarketplaceUiState())
    val uiState: StateFlow<MarketplaceUiState> = _uiState.asStateFlow()

    private var loadProductsJob: Job? = null

    init {
        loadCategories()
        loadProducts()
    }

    private fun extractServerError(rawJson: String?, fallback: String): String =
        ApiErrorParser.extractMessage(rawJson, fallback)

    private fun syncUpdatedProductInLists(updated: MarketplaceProductDto) {
        _uiState.update { state ->
            val nextProducts = if (updated.status == "active") {
                val exists = state.products.any { it.id == updated.id }
                if (exists) state.products.map { if (it.id == updated.id) updated else it } else state.products
            } else {
                state.products.filter { it.id != updated.id }
            }
            val nextMyListings = state.myListings.map { if (it.id == updated.id) updated else it }
            val nextSaved = state.savedProducts.mapNotNull { item ->
                if (item.id != updated.id) item
                else if (updated.status == "active" || updated.status == "sold") updated.copy(is_favorited = true)
                else null
            }
            val nextSelected = if (state.selectedProduct?.id == updated.id) updated else state.selectedProduct
            state.copy(
                products = nextProducts,
                myListings = nextMyListings,
                savedProducts = nextSaved,
                selectedProduct = nextSelected
            )
        }
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
            } catch (_: Exception) {
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
        viewModelScope.launch {
            try {
                val res = api.getMarketplaceProductDetails(product.id)
                if (res.isSuccessful && res.body()?.data != null) {
                    val fresh = res.body()!!.data
                    syncUpdatedProductInLists(fresh)
                }
            } catch (_: Exception) {
                // Keep cached product details if refresh fails
            }
        }
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

    fun openEditDialog(product: MarketplaceProductDto) {
        if (!product.is_owner) {
            _uiState.update { it.copy(errorMessage = "Only the listing owner can edit this item.") }
            return
        }
        _uiState.update { it.copy(showEditDialog = true, editingProduct = product) }
    }

    fun closeEditDialog() {
        _uiState.update { it.copy(showEditDialog = false, editingProduct = null) }
    }

    fun openMessageDialog() {
        val current = _uiState.value.selectedProduct
        if (current?.is_owner == true) {
            _uiState.update { it.copy(errorMessage = "You cannot message yourself on your own listing.") }
            return
        }
        _uiState.update { it.copy(showMessageDialog = true) }
    }

    fun closeMessageDialog() {
        _uiState.update { it.copy(showMessageDialog = false) }
    }

    fun openOrderDialog() {
        val current = _uiState.value.selectedProduct
        if (current == null) return
        if (current.is_owner) {
            _uiState.update { it.copy(errorMessage = "You cannot purchase your own listing.") }
            return
        }
        if (current.status != "active") {
            _uiState.update { it.copy(errorMessage = "This listing is no longer available for purchase.") }
            return
        }
        _uiState.update { it.copy(showOrderDialog = true) }
    }

    fun closeOrderDialog() {
        _uiState.update { it.copy(showOrderDialog = false) }
    }

    fun openOfferDialog() {
        val current = _uiState.value.selectedProduct
        if (current == null) return
        if (current.is_owner) {
            _uiState.update { it.copy(errorMessage = "You cannot submit an offer on your own listing.") }
            return
        }
        if (current.status != "active") {
            _uiState.update { it.copy(errorMessage = "Offers can only be submitted on active listings.") }
            return
        }
        _uiState.update { it.copy(showOfferDialog = true) }
    }

    fun closeOfferDialog() {
        _uiState.update { it.copy(showOfferDialog = false) }
    }

    fun openReportDialog() {
        val current = _uiState.value.selectedProduct
        if (current?.is_owner == true) return
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

    fun consumePendingChatNavigation() {
        _uiState.update { it.copy(pendingChatConversationId = null) }
    }

    fun loadProducts() {
        loadProductsJob?.cancel()
        loadProductsJob = viewModelScope.launch {
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
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to load products (${response.code()})")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = errMsg
                        )
                    }
                }
            } catch (e: Exception) {
                if (e is kotlinx.coroutines.CancellationException) throw e
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
                    val errMsg = extractServerError(response.errorBody()?.string(), "Could not retrieve your listings (${response.code()})")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = errMsg
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
                val response = api.getFavoriteMarketplaceProducts()
                if (response.isSuccessful && response.body() != null) {
                    val saved = response.body()!!.data
                    _uiState.update { it.copy(savedProducts = saved, isLoading = false) }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Could not load saved items (${response.code()})")
                    _uiState.update { it.copy(isLoading = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Failed to load saved items"
                    )
                }
            }
        }
    }

    fun loadOrders() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val ordersRes = api.getMarketplaceOrders(role = "buyer")
                val myOffersRes = try { api.getMyMarketplaceOffers() } catch (_: Exception) { null }
                val buyerOrders = if (ordersRes.isSuccessful) {
                    ordersRes.body()?.resolvedOrders() ?: emptyList()
                } else {
                    emptyList()
                }
                val sentOffers = if (myOffersRes?.isSuccessful == true) {
                    myOffersRes.body()?.data ?: _uiState.value.myOffers
                } else {
                    _uiState.value.myOffers
                }
                _uiState.update {
                    it.copy(
                        orders = buyerOrders,
                        myOffers = sentOffers,
                        isLoading = false,
                        errorMessage = if (!ordersRes.isSuccessful) extractServerError(ordersRes.errorBody()?.string(), "Failed to load orders (${ordersRes.code()})") else null
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Network error loading orders"
                    )
                }
            }
        }
    }

    fun loadSellerDashboard() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val summaryRes = api.getMarketplaceSellerDashboard()
                val offersRes = api.getReceivedMarketplaceOffers()
                val sellerOrdersRes = api.getMarketplaceOrders(role = "seller")
                val listingsRes = api.getMyMarketplaceListings()

                _uiState.update { state ->
                    state.copy(
                        isLoading = false,
                        sellerSummary = summaryRes.body()?.data ?: sellerOrdersRes.body()?.summary,
                        receivedOffers = offersRes.body()?.data ?: emptyList(),
                        sellerOrders = sellerOrdersRes.body()?.resolvedOrders() ?: emptyList(),
                        myListings = listingsRes.body()?.data ?: state.myListings
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Failed to load seller dashboard"
                    )
                }
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
        if (_uiState.value.isSubmitting) return
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
                            myOffers = listOf(offer) + state.myOffers.filter { it.id != offer.id },
                            successMessage = "Offer of ৳${amount.toLong()} submitted to seller!"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Could not submit offer (${response.code()})")
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = errMsg
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
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.acceptMarketplaceOffer(offerId)
                if (response.isSuccessful) {
                    val dto = response.body()?.data
                    _uiState.update { state ->
                        val updatedReceived = state.receivedOffers.map {
                            if (it.id == offerId) (dto ?: it.copy(status = "accepted")) else it
                        }
                        val updatedMine = state.myOffers.map {
                            if (it.id == offerId) (dto ?: it.copy(status = "accepted")) else it
                        }
                        state.copy(
                            isSubmitting = false,
                            receivedOffers = updatedReceived,
                            myOffers = updatedMine,
                            successMessage = response.body()?.message ?: "Offer accepted!"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to accept offer (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error accepting offer") }
            }
        }
    }

    fun rejectOffer(offerId: Long) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.rejectMarketplaceOffer(offerId)
                if (response.isSuccessful) {
                    val dto = response.body()?.data
                    _uiState.update { state ->
                        val updatedReceived = state.receivedOffers.map {
                            if (it.id == offerId) (dto ?: it.copy(status = "rejected")) else it
                        }
                        val updatedMine = state.myOffers.map {
                            if (it.id == offerId) (dto ?: it.copy(status = "rejected")) else it
                        }
                        state.copy(
                            isSubmitting = false,
                            receivedOffers = updatedReceived,
                            myOffers = updatedMine,
                            successMessage = "Offer declined."
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to decline offer (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error declining offer") }
            }
        }
    }

    fun counterOffer(offerId: Long, counterAmount: Double, counterMessage: String?) {
        if (_uiState.value.isSubmitting) return
        if (counterAmount <= 0) {
            _uiState.update { it.copy(errorMessage = "Please enter a valid counter amount") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val request = CounterOfferRequest(counter_amount = counterAmount, counter_message = counterMessage)
                val response = api.counterMarketplaceOffer(offerId, request)
                if (response.isSuccessful) {
                    val dto = response.body()?.data
                    _uiState.update { state ->
                        val updated = state.receivedOffers.map {
                            if (it.id == offerId) (dto ?: it.copy(status = "countered", counter_amount = counterAmount, counter_message = counterMessage)) else it
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
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to counter offer (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error countering offer") }
            }
        }
    }

    fun cancelOffer(offerId: Long) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
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
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to cancel offer (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error cancelling offer") }
            }
        }
    }

    fun shipOrder(orderId: Long, carrier: String?, trackingNumber: String?) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.shipMarketplaceOrder(
                    orderId,
                    ShipMarketplaceOrderRequest(
                        carrier = carrier?.trim()?.takeIf { it.isNotBlank() },
                        tracking_number = trackingNumber?.trim()?.takeIf { it.isNotBlank() }
                    )
                )
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            sellerOrders = state.sellerOrders.map { if (it.id == orderId) updated else it },
                            orders = state.orders.map { if (it.id == orderId) updated else it },
                            successMessage = "Order marked as shipped."
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to ship order (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error updating shipment") }
            }
        }
    }

    fun confirmOrderDelivery(orderId: Long) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.confirmMarketplaceOrderDelivery(orderId)
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            orders = state.orders.map { if (it.id == orderId) updated else it },
                            successMessage = "Delivery confirmed! Escrow funds released to seller."
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Could not confirm delivery (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error confirming delivery") }
            }
        }
    }

    fun cancelOrder(orderId: Long, reason: String = "Cancelled by user") {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.cancelMarketplaceOrder(orderId, CancelMarketplaceOrderRequest(reason = reason))
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = response.body()!!.data
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            orders = state.orders.map { if (it.id == orderId) updated else it },
                            sellerOrders = state.sellerOrders.map { if (it.id == orderId) updated else it },
                            successMessage = "Order cancelled."
                        )
                    }
                    loadProducts()
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Could not cancel order (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error cancelling order") }
            }
        }
    }

    fun reportProduct(productId: Long, reason: String, details: String?) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
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
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to submit report (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = e.localizedMessage ?: "Error reporting product") }
            }
        }
    }

    fun toggleFavorite(product: MarketplaceProductDto) {
        if (product.is_owner) return
        val previousFav = product.is_favorited
        val newFav = !previousFav
        _uiState.update { state ->
            val updated = state.products.map {
                if (it.id == product.id) it.copy(is_favorited = newFav) else it
            }
            val updatedSaved = if (newFav) {
                listOf(product.copy(is_favorited = true)) + state.savedProducts.filter { it.id != product.id }
            } else {
                state.savedProducts.filter { it.id != product.id }
            }
            val updatedSelected = if (state.selectedProduct?.id == product.id) {
                state.selectedProduct.copy(is_favorited = newFav)
            } else state.selectedProduct
            state.copy(products = updated, savedProducts = updatedSaved, selectedProduct = updatedSelected)
        }

        viewModelScope.launch {
            try {
                val response = api.toggleFavoriteMarketplaceProduct(product.id)
                if (response.isSuccessful && response.body() != null) {
                    val isFav = response.body()!!.is_favorited
                    _uiState.update { state ->
                        val updatedProducts = state.products.map {
                            if (it.id == product.id) it.copy(is_favorited = isFav) else it
                        }
                        val updatedSaved = if (isFav) {
                            listOf(product.copy(is_favorited = true)) + state.savedProducts.filter { it.id != product.id }
                        } else {
                            state.savedProducts.filter { it.id != product.id }
                        }
                        val updatedSelected = if (state.selectedProduct?.id == product.id) {
                            state.selectedProduct.copy(is_favorited = isFav)
                        } else state.selectedProduct

                        state.copy(
                            products = updatedProducts,
                            savedProducts = updatedSaved,
                            selectedProduct = updatedSelected,
                            successMessage = if (isFav) "Added to saved items" else "Removed from saved items"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to update favorite status (${response.code()})")
                    rollbackFavorite(product, previousFav, errMsg)
                }
            } catch (_: Exception) {
                rollbackFavorite(product, previousFav, "Failed to update favorite status")
            }
        }
    }

    private fun rollbackFavorite(product: MarketplaceProductDto, previousFav: Boolean, message: String) {
        _uiState.update { state ->
            val updatedProducts = state.products.map {
                if (it.id == product.id) it.copy(is_favorited = previousFav) else it
            }
            val updatedSaved = if (previousFav) {
                listOf(product.copy(is_favorited = true)) + state.savedProducts.filter { it.id != product.id }
            } else {
                state.savedProducts.filter { it.id != product.id }
            }
            val updatedSelected = if (state.selectedProduct?.id == product.id) {
                state.selectedProduct.copy(is_favorited = previousFav)
            } else state.selectedProduct
            state.copy(
                products = updatedProducts,
                savedProducts = updatedSaved,
                selectedProduct = updatedSelected,
                errorMessage = message
            )
        }
    }

    private suspend fun uploadImageFiles(imageFiles: List<File>): List<String> {
        val uploadedUrls = mutableListOf<String>()
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
        return uploadedUrls
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
        if (_uiState.value.isSubmitting) return
        if (title.isBlank() || price <= 0.0) {
            _uiState.update { it.copy(errorMessage = "Please provide a title and valid price") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val uploadedUrls = uploadImageFiles(imageFiles)
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
                            products = listOf(created) + state.products.filter { it.id != created.id },
                            myListings = listOf(created) + state.myListings.filter { it.id != created.id },
                            successMessage = "Listing created successfully!"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to publish listing (${response.code()})")
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = errMsg
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

    fun updateListing(
        productId: Long,
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
        existingImages: List<String> = emptyList(),
        imageUrl: String? = null,
        imageFiles: List<File> = emptyList()
    ) {
        if (_uiState.value.isSubmitting) return
        if (title.isBlank() || price <= 0.0) {
            _uiState.update { it.copy(errorMessage = "Please provide a valid title and price") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val uploadedUrls = uploadImageFiles(imageFiles)
                val mergedImages = mutableListOf<String>()
                mergedImages.addAll(existingImages)
                mergedImages.addAll(uploadedUrls)
                if (!imageUrl.isNullOrBlank()) {
                    mergedImages.add(imageUrl.trim())
                }

                val request = UpdateMarketplaceProductRequest(
                    category_id = categoryId,
                    subcategory_id = subcategoryId,
                    title = title.trim(),
                    description = description.trim(),
                    price = price,
                    original_price = originalPrice,
                    condition = condition,
                    brand = brand?.trim()?.takeIf { it.isNotBlank() },
                    location = location.trim(),
                    delivery_type = deliveryType,
                    delivery_charge = deliveryCharge,
                    is_negotiable = isNegotiable,
                    images = mergedImages.distinct().takeIf { it.isNotEmpty() }
                )
                val response = api.updateMarketplaceProduct(productId, request)
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = response.body()!!.data
                    syncUpdatedProductInLists(updated)
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showEditDialog = false,
                            editingProduct = null,
                            successMessage = "Listing updated successfully!"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to update listing (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Error updating listing"
                    )
                }
            }
        }
    }

    fun updateListingStatus(productId: Long, newStatus: String) {
        if (_uiState.value.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.updateMarketplaceProductStatus(
                    productId,
                    UpdateMarketplaceStatusRequest(status = newStatus)
                )
                if (response.isSuccessful && response.body()?.data != null) {
                    val updated = response.body()!!.data
                    syncUpdatedProductInLists(updated)
                    val label = when (newStatus) {
                        "sold" -> "Listing marked as sold."
                        "inactive" -> "Listing paused and hidden from public Discover."
                        "active" -> "Listing reactivated!"
                        else -> "Listing status updated."
                    }
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            successMessage = label
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to update listing status (${response.code()})")
                    _uiState.update { it.copy(isSubmitting = false, errorMessage = errMsg) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSubmitting = false,
                        errorMessage = e.localizedMessage ?: "Error updating listing status"
                    )
                }
            }
        }
    }

    fun contactSeller(productId: Long, messageText: String) {
        if (_uiState.value.isSubmitting) return
        if (messageText.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Message cannot be empty") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.contactMarketplaceSeller(productId, ContactSellerRequest(message = messageText.trim()))
                if (response.isSuccessful) {
                    val convId = response.body()?.data?.conversation_id
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            showMessageDialog = false,
                            selectedProduct = if (convId != null) null else it.selectedProduct,
                            pendingChatConversationId = convId,
                            successMessage = "Message sent to seller!"
                        )
                    }
                } else {
                    val errMsg = extractServerError(response.errorBody()?.string(), "Failed to send message (${response.code()})")
                    _uiState.update {
                        it.copy(
                            isSubmitting = false,
                            errorMessage = errMsg
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
        quantity: Int = 1,
        deliveryType: String = "standard"
    ) {
        if (_uiState.value.isSubmitting) return
        if (shippingName.isBlank() || shippingPhone.isBlank() || shippingAddress.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please fill in all shipping details") }
            return
        }

        val current = _uiState.value.selectedProduct
        if (current != null && current.id == productId) {
            if (current.is_owner) {
                _uiState.update { it.copy(errorMessage = "You cannot purchase your own listing.") }
                return
            }
            if (current.status != "active") {
                _uiState.update { it.copy(errorMessage = "This listing is no longer available.") }
                return
            }
        }

        val idempotencyKey = "and-ord-$productId-${UUID.randomUUID()}"

        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val request = OrderMarketplaceProductRequest(
                    shipping_name = shippingName.trim(),
                    shipping_phone = shippingPhone.trim(),
                    shipping_address = shippingAddress.trim(),
                    delivery_type = deliveryType,
                    quantity = quantity,
                    payment_method = "sandbox",
                    idempotency_key = idempotencyKey
                )
                val response = api.orderMarketplaceProduct(productId, request)
                if (response.isSuccessful) {
                    val createdOrder = response.body()?.data
                    _uiState.update { state ->
                        val nextOrders = if (createdOrder != null) {
                            listOf(createdOrder) + state.orders.filter { it.id != createdOrder.id }
                        } else state.orders
                        state.copy(
                            isSubmitting = false,
                            showOrderDialog = false,
                            selectedProduct = null,
                            orders = nextOrders,
                            successMessage = "Order placed successfully! Escrow protection is active."
                        )
                    }
                    loadProducts()
                } else {
                    val code = response.code()
                    val errMsg = extractServerError(
                        response.errorBody()?.string(),
                        if (code == 409) "Sorry, this item was just purchased by another buyer."
                        else "Could not place order ($code)"
                    )
                    _uiState.update { state ->
                        val nextProducts = if (code == 409) state.products.filter { it.id != productId } else state.products
                        val nextSelected = if (code == 409 && state.selectedProduct?.id == productId) {
                            state.selectedProduct.copy(status = "sold")
                        } else state.selectedProduct
                        state.copy(
                            isSubmitting = false,
                            showOrderDialog = if (code == 409) false else state.showOrderDialog,
                            products = nextProducts,
                            selectedProduct = nextSelected,
                            errorMessage = errMsg
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
        val snapshot = _uiState.value
        if (snapshot.isSubmitting || productId in snapshot.deletingProductIds) return

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isSubmitting = true,
                    deletingProductIds = it.deletingProductIds + productId,
                    errorMessage = null
                )
            }
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
                                deletingProductIds = state.deletingProductIds - productId,
                                errorMessage = body.message ?: "Failed to delete listing"
                            )
                        }
                    } else {
                        val msg = body?.message ?: "Listing removed successfully"
                        _uiState.update { state ->
                            state.copy(
                                isSubmitting = false,
                                deletingProductIds = state.deletingProductIds - productId,
                                products = state.products.filter { it.id != productId },
                                myListings = state.myListings.filter { it.id != productId },
                                savedProducts = state.savedProducts.filter { it.id != productId },
                                selectedProduct = if (state.selectedProduct?.id == productId) null else state.selectedProduct,
                                successMessage = msg
                            )
                        }
                    }
                } else if (code == 404) {
                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            deletingProductIds = state.deletingProductIds - productId,
                            products = state.products.filter { it.id != productId },
                            myListings = state.myListings.filter { it.id != productId },
                            savedProducts = state.savedProducts.filter { it.id != productId },
                            selectedProduct = if (state.selectedProduct?.id == productId) null else state.selectedProduct,
                            successMessage = "Listing was already removed from marketplace"
                        )
                    }
                } else {
                    val fallbackMsg = when (code) {
                        401 -> "Session expired. Please log in again to manage listings."
                        403 -> "Unauthorized to delete this listing."
                        422 -> "Cannot delete listing at this time."
                        in 500..599 -> "Server error occurred. Please try again later ($code)."
                        else -> "Failed to delete listing ($code)"
                    }
                    val serverMsg = extractServerError(response.errorBody()?.string(), fallbackMsg)

                    _uiState.update { state ->
                        state.copy(
                            isSubmitting = false,
                            deletingProductIds = state.deletingProductIds - productId,
                            errorMessage = serverMsg
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
                        deletingProductIds = state.deletingProductIds - productId,
                        errorMessage = msg
                    )
                }
            }
        }
    }
}
