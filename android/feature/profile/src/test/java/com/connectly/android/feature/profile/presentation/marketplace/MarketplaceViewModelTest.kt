package com.connectly.android.feature.profile.presentation.marketplace

import com.connectly.android.core.network.api.ApiResponse
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.ContactSellerResponseDto
import com.connectly.android.core.network.api.MarketplaceDeleteResponse
import com.connectly.android.core.network.api.MarketplaceOfferDto
import com.connectly.android.core.network.api.MarketplaceOrderDto
import com.connectly.android.core.network.api.MarketplaceProductDto
import com.connectly.android.core.network.api.ToggleFavoriteResponse
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.mockk
import kotlinx.coroutines.CompletableDeferred
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Response
import java.io.IOException

@OptIn(ExperimentalCoroutinesApi::class)
class MarketplaceViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private lateinit var viewModel: MarketplaceViewModel

    private val ownerListing = MarketplaceProductDto(
        id = 10L,
        title = "MacBook Pro M3",
        price = 185000.0,
        status = "active",
        is_owner = true,
        is_favorited = false,
        seller_name = "Seller User"
    )

    private val buyerListing = MarketplaceProductDto(
        id = 20L,
        title = "Sony A7 IV Camera",
        price = 210000.0,
        status = "active",
        is_owner = false,
        is_favorited = false,
        seller_name = "Camera World"
    )

    private val soldListing = buyerListing.copy(
        id = 30L,
        title = "iPad Pro M4",
        status = "sold"
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        coEvery { api.getMarketplaceCategories() } returns Response.success(
            ApiResponse(data = emptyList())
        )
        coEvery {
            api.getMarketplaceProducts(any(), any(), any(), any(), any(), any(), any())
        } returns Response.success(
            ApiResponse(data = listOf(ownerListing, buyerListing))
        )

        viewModel = MarketplaceViewModel(api)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `owner cannot open order offer or message dialogs on own listing`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        coEvery { api.getMarketplaceProductDetails(10L) } returns Response.success(
            ApiResponse(data = ownerListing)
        )

        viewModel.openProductDetails(ownerListing)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.openOrderDialog()
        assertFalse(viewModel.uiState.value.showOrderDialog)
        assertEquals("You cannot purchase your own listing.", viewModel.uiState.value.errorMessage)

        viewModel.openOfferDialog()
        assertFalse(viewModel.uiState.value.showOfferDialog)
        assertEquals("You cannot submit an offer on your own listing.", viewModel.uiState.value.errorMessage)

        viewModel.openMessageDialog()
        assertFalse(viewModel.uiState.value.showMessageDialog)
        assertEquals("You cannot message yourself on your own listing.", viewModel.uiState.value.errorMessage)

        viewModel.openEditDialog(ownerListing)
        assertTrue(viewModel.uiState.value.showEditDialog)
    }

    @Test
    fun `non-owner cannot edit listing and cannot buy or make offer on sold listing`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.openEditDialog(buyerListing)
        assertFalse(viewModel.uiState.value.showEditDialog)
        assertEquals("Only the listing owner can edit this item.", viewModel.uiState.value.errorMessage)

        coEvery { api.getMarketplaceProductDetails(30L) } returns Response.success(
            ApiResponse(data = soldListing)
        )
        viewModel.openProductDetails(soldListing)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.openOrderDialog()
        assertFalse(viewModel.uiState.value.showOrderDialog)
        assertTrue(viewModel.uiState.value.errorMessage?.contains("no longer available") == true)

        viewModel.openOfferDialog()
        assertFalse(viewModel.uiState.value.showOfferDialog)
        assertTrue(viewModel.uiState.value.errorMessage?.contains("active listings") == true)
    }

    @Test
    fun `deleteListing handles HTTP 200 with null data and removes listing from all lists`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        coEvery { api.getMyMarketplaceListings() } returns Response.success(
            ApiResponse(data = listOf(ownerListing))
        )
        viewModel.loadMyListings()
        testDispatcher.scheduler.advanceUntilIdle()

        coEvery { api.deleteMarketplaceProduct(10L) } returns Response.success(
            MarketplaceDeleteResponse.of(
                success = true,
                status = "success",
                message = "Listing deleted successfully."
            )
        )

        viewModel.deleteListing(10L)
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.products.any { it.id == 10L })
        assertFalse(state.myListings.any { it.id == 10L })
        assertEquals("Listing deleted successfully.", state.successMessage)
        assertNull(state.errorMessage)
    }

    @Test
    fun `deleteListing preserves item and parses server message on HTTP 403 and IOException`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        val forbiddenJson = """{"success":false,"message":"You do not own this listing."}"""
            .toResponseBody("application/json".toMediaType())
        coEvery { api.deleteMarketplaceProduct(20L) } returns Response.error(403, forbiddenJson)

        viewModel.deleteListing(20L)
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.products.any { it.id == 20L })
        assertEquals("You do not own this listing.", viewModel.uiState.value.errorMessage)

        coEvery { api.deleteMarketplaceProduct(10L) } throws IOException("Connection reset")
        viewModel.deleteListing(10L)
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.products.any { it.id == 10L })
        assertTrue(viewModel.uiState.value.errorMessage?.contains("Network connection unavailable") == true)
    }

    @Test
    fun `deleteListing duplicate tap guard fires single request while deletion is in flight`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val deferred = CompletableDeferred<Response<MarketplaceDeleteResponse>>()
        coEvery { api.deleteMarketplaceProduct(10L) } coAnswers { deferred.await() }

        viewModel.deleteListing(10L)
        testDispatcher.scheduler.runCurrent()
        assertTrue(10L in viewModel.uiState.value.deletingProductIds)

        // Rapid second tap
        viewModel.deleteListing(10L)
        testDispatcher.scheduler.runCurrent()

        deferred.complete(
            Response.success(
                MarketplaceDeleteResponse.of(success = true, message = "Deleted")
            )
        )
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify(exactly = 1) { api.deleteMarketplaceProduct(10L) }
        assertFalse(10L in viewModel.uiState.value.deletingProductIds)
    }

    @Test
    fun `orderProduct sends idempotency key and handles 409 conflict race cleanly`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        coEvery { api.getMarketplaceProductDetails(20L) } returns Response.success(
            ApiResponse(data = buyerListing)
        )
        viewModel.openProductDetails(buyerListing)
        testDispatcher.scheduler.advanceUntilIdle()
        viewModel.openOrderDialog()

        val conflictBody = """{"status":"error","message":"Item was just purchased by another buyer."}"""
            .toResponseBody("application/json".toMediaType())
        coEvery { api.orderMarketplaceProduct(20L, any()) } returns Response.error(409, conflictBody)

        viewModel.orderProduct(
            productId = 20L,
            shippingName = "Rahim Uddin",
            shippingPhone = "01711000000",
            shippingAddress = "Banani, Dhaka"
        )
        testDispatcher.scheduler.advanceUntilIdle()

        val stateAfterConflict = viewModel.uiState.value
        assertFalse(stateAfterConflict.showOrderDialog)
        assertFalse(stateAfterConflict.products.any { it.id == 20L })
        assertEquals("sold", stateAfterConflict.selectedProduct?.status)
        assertEquals("Item was just purchased by another buyer.", stateAfterConflict.errorMessage)

        // Verify a successful order appends to orders and sends non-null idempotency_key
        val otherItem = buyerListing.copy(id = 25L)
        val createdOrder = MarketplaceOrderDto(
            id = 500L,
            order_number = "ORD-500",
            product_id = 25L,
            amount = 210000.0,
            status = "confirmed"
        )
        coEvery { api.orderMarketplaceProduct(25L, match { !it.idempotency_key.isNullOrBlank() }) } returns Response.success(
            ApiResponse(data = createdOrder)
        )

        viewModel.orderProduct(
            productId = 25L,
            shippingName = "Rahim Uddin",
            shippingPhone = "01711000000",
            shippingAddress = "Banani, Dhaka"
        )
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.orders.any { it.id == 500L })
        assertNull(viewModel.uiState.value.errorMessage)
    }

    @Test
    fun `contactSeller stores pendingChatConversationId for direct Messenger navigation`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        coEvery { api.contactMarketplaceSeller(20L, any()) } returns Response.success(
            ApiResponse(
                data = ContactSellerResponseDto(conversation_id = 777L, seller_id = 9L, message_sent = true)
            )
        )

        viewModel.contactSeller(20L, "Is this camera still available?")
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(777L, viewModel.uiState.value.pendingChatConversationId)
        viewModel.consumePendingChatNavigation()
        assertNull(viewModel.uiState.value.pendingChatConversationId)
    }

    @Test
    fun `saved listings and favorite toggle sync and rollback on failure`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        // Owner cannot favorite own listing
        viewModel.toggleFavorite(ownerListing)
        coVerify(exactly = 0) { api.toggleFavoriteMarketplaceProduct(10L) }

        // Buyer favorites item successfully
        coEvery { api.toggleFavoriteMarketplaceProduct(20L) } returns Response.success(
            ToggleFavoriteResponse(success = true, is_favorited = true)
        )
        viewModel.toggleFavorite(buyerListing)
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.savedProducts.any { it.id == 20L })
        assertTrue(viewModel.uiState.value.products.first { it.id == 20L }.is_favorited)

        // Dedicated saved endpoint sync
        coEvery { api.getFavoriteMarketplaceProducts() } returns Response.success(
            ApiResponse(data = listOf(buyerListing.copy(is_favorited = true)))
        )
        viewModel.loadSavedListings()
        testDispatcher.scheduler.advanceUntilIdle()
        assertEquals(1, viewModel.uiState.value.savedProducts.size)

        // Offer lifecycle check
        val offerDto = MarketplaceOfferDto(id = 91L, product_id = 20L, offer_amount = 195000.0, status = "pending")
        coEvery { api.submitMarketplaceOffer(20L, any()) } returns Response.success(
            ApiResponse(data = offerDto)
        )
        viewModel.submitOffer(20L, 195000.0, "Cash ready")
        testDispatcher.scheduler.advanceUntilIdle()
        assertNotNull(viewModel.uiState.value.myOffers.firstOrNull { it.id == 91L })
    }
}
