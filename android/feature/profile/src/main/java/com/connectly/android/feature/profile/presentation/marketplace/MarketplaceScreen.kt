package com.connectly.android.feature.profile.presentation.marketplace

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.webkit.MimeTypeMap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import java.io.File
import java.io.FileOutputStream
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.network.api.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MarketplaceScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: MarketplaceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val context = LocalContext.current

    LaunchedEffect(uiState.errorMessage, uiState.successMessage) {
        uiState.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = "Marketplace",
                            fontWeight = FontWeight.Bold,
                            fontSize = 19.sp
                        )
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = ConnectlyBlue.copy(alpha = 0.12f)
                        ) {
                            Text(
                                text = "বন্ধু বাজার",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.SemiBold,
                                color = ConnectlyBlue,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                },
                actions = {
                    // + Sell Button
                    Button(
                        onClick = { viewModel.openCreateDialog() },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp),
                        shape = RoundedCornerShape(18.dp),
                        modifier = Modifier.padding(end = 8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Add,
                            contentDescription = "Sell Item",
                            tint = Color.White,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = "Sell",
                            color = Color.White,
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        modifier = modifier
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Horizontal Scrollable Tabs: Discover, Categories, My Items, Saved, Orders, Selling
            ScrollableTabRow(
                selectedTabIndex = uiState.activeTab.ordinal,
                containerColor = MaterialTheme.colorScheme.surface,
                contentColor = ConnectlyBlue,
                edgePadding = 8.dp
            ) {
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.DISCOVER,
                    onClick = { viewModel.setTab(MarketplaceTab.DISCOVER) },
                    text = { Text("Discover", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.CATEGORIES,
                    onClick = { viewModel.setTab(MarketplaceTab.CATEGORIES) },
                    text = { Text("Categories", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.MY_LISTINGS,
                    onClick = { viewModel.setTab(MarketplaceTab.MY_LISTINGS) },
                    text = { Text("My Items", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.SAVED,
                    onClick = { viewModel.setTab(MarketplaceTab.SAVED) },
                    text = { Text("Saved", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.MY_ORDERS,
                    onClick = { viewModel.setTab(MarketplaceTab.MY_ORDERS) },
                    text = { Text("Orders", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
                Tab(
                    selected = uiState.activeTab == MarketplaceTab.SELLING_DASHBOARD,
                    onClick = { viewModel.setTab(MarketplaceTab.SELLING_DASHBOARD) },
                    text = { Text("Selling", fontWeight = FontWeight.SemiBold, fontSize = 12.sp) }
                )
            }

            // Top Search & Category Filters (shown on Discover)
            if (uiState.activeTab == MarketplaceTab.DISCOVER) {
                // Search Bar
                OutlinedTextField(
                    value = uiState.searchQuery,
                    onValueChange = { viewModel.setSearchQuery(it) },
                    placeholder = { Text("Search products, electronics, cars...", fontSize = 13.sp) },
                    leadingIcon = {
                        Icon(
                            imageVector = Icons.Default.Search,
                            contentDescription = "Search",
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    },
                    trailingIcon = {
                        if (uiState.searchQuery.isNotBlank()) {
                            IconButton(onClick = {
                                viewModel.setSearchQuery("")
                                viewModel.executeSearch()
                            }) {
                                Icon(Icons.Default.Clear, contentDescription = "Clear")
                            }
                        } else {
                            IconButton(onClick = { viewModel.executeSearch() }) {
                                Icon(
                                    Icons.AutoMirrored.Filled.ArrowForward,
                                    contentDescription = "Go",
                                    tint = ConnectlyBlue
                                )
                            }
                        }
                    },
                    singleLine = true,
                    shape = RoundedCornerShape(24.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = ConnectlyBlue,
                        unfocusedBorderColor = MaterialTheme.colorScheme.outlineVariant
                    ),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 6.dp)
                )

                // Category Chips Scrollable Row
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState())
                        .padding(horizontal = 12.dp, vertical = 2.dp),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    uiState.categories.forEach { category ->
                        val isSelected = uiState.selectedCategory == category
                        FilterChip(
                            selected = isSelected,
                            onClick = { viewModel.setCategory(category) },
                            label = {
                                Text(
                                    text = category,
                                    fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                                    fontSize = 11.sp
                                )
                            },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                                selectedLabelColor = ConnectlyBlue
                            )
                        )
                    }
                }

                // Subcategory Chips Row (if selected category has subcategories)
                val currentCategoryDto = uiState.categoryList.firstOrNull { it.name.equals(uiState.selectedCategory, ignoreCase = true) }
                if (currentCategoryDto != null && currentCategoryDto.subcategories.isNotEmpty()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState())
                            .padding(horizontal = 12.dp, vertical = 2.dp),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        FilterChip(
                            selected = uiState.selectedSubcategory == null,
                            onClick = { viewModel.setSubcategory(null) },
                            label = { Text("All ${currentCategoryDto.name}", fontSize = 10.sp) }
                        )
                        currentCategoryDto.subcategories.forEach { sub ->
                            val isSubSelected = uiState.selectedSubcategory == sub.slug
                            FilterChip(
                                selected = isSubSelected,
                                onClick = { viewModel.setSubcategory(sub.slug) },
                                label = { Text(sub.name, fontSize = 10.sp) },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                                    selectedLabelColor = ConnectlyBlue
                                )
                            )
                        }
                    }
                }

                // Quick Filter Row: Condition & Sort
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState())
                        .padding(horizontal = 12.dp, vertical = 2.dp),
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    // Sort options
                    listOf(
                        "newest" to "Recent",
                        "price_low" to "Price: Low",
                        "price_high" to "Price: High",
                        "popular" to "Popular"
                    ).forEach { (sortKey, label) ->
                        val isSort = uiState.sortOption == sortKey
                        AssistChip(
                            onClick = { viewModel.setSort(sortKey) },
                            label = { Text(label, fontSize = 10.sp, color = if (isSort) ConnectlyBlue else MaterialTheme.colorScheme.onSurface) },
                            leadingIcon = if (isSort) {
                                { Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(12.dp), tint = ConnectlyBlue) }
                            } else null
                        )
                    }
                }
            }

            // Main Content Body based on Active Tab
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 10.dp, vertical = 4.dp),
                contentAlignment = Alignment.Center
            ) {
                when (uiState.activeTab) {
                    MarketplaceTab.DISCOVER -> {
                        if (uiState.isLoading) {
                            CircularProgressIndicator(color = ConnectlyBlue)
                        } else if (uiState.products.isEmpty()) {
                            EmptyStateView(
                                tab = MarketplaceTab.DISCOVER,
                                onActionClick = { viewModel.loadProducts() }
                            )
                        } else {
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                horizontalArrangement = Arrangement.spacedBy(10.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                items(uiState.products, key = { it.id }) { product ->
                                    ProductCard(
                                        product = product,
                                        onCardClick = { viewModel.openProductDetails(product) },
                                        onFavoriteClick = { viewModel.toggleFavorite(product) }
                                    )
                                }
                            }
                        }
                    }

                    MarketplaceTab.CATEGORIES -> {
                        CategoriesDirectoryView(
                            categoryList = uiState.categoryList,
                            onSelectCategory = { catName, subSlug ->
                                viewModel.setCategory(catName)
                                if (subSlug != null) viewModel.setSubcategory(subSlug)
                                viewModel.setTab(MarketplaceTab.DISCOVER)
                            }
                        )
                    }

                    MarketplaceTab.MY_LISTINGS -> {
                        if (uiState.isLoading) {
                            CircularProgressIndicator(color = ConnectlyBlue)
                        } else if (uiState.myListings.isEmpty()) {
                            EmptyStateView(
                                tab = MarketplaceTab.MY_LISTINGS,
                                onActionClick = { viewModel.openCreateDialog() }
                            )
                        } else {
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                horizontalArrangement = Arrangement.spacedBy(10.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                items(uiState.myListings, key = { it.id }) { product ->
                                    ProductCard(
                                        product = product,
                                        onCardClick = { viewModel.openProductDetails(product) },
                                        onFavoriteClick = { viewModel.toggleFavorite(product) }
                                    )
                                }
                            }
                        }
                    }

                    MarketplaceTab.SAVED -> {
                        if (uiState.isLoading) {
                            CircularProgressIndicator(color = ConnectlyBlue)
                        } else if (uiState.savedProducts.isEmpty()) {
                            EmptyStateView(
                                tab = MarketplaceTab.SAVED,
                                onActionClick = { viewModel.setTab(MarketplaceTab.DISCOVER) }
                            )
                        } else {
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                horizontalArrangement = Arrangement.spacedBy(10.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                items(uiState.savedProducts, key = { it.id }) { product ->
                                    ProductCard(
                                        product = product,
                                        onCardClick = { viewModel.openProductDetails(product) },
                                        onFavoriteClick = { viewModel.toggleFavorite(product) }
                                    )
                                }
                            }
                        }
                    }

                    MarketplaceTab.MY_ORDERS -> {
                        OrdersListView(
                            orders = uiState.orders,
                            isLoading = uiState.isLoading,
                            onRefresh = { viewModel.loadOrders() }
                        )
                    }

                    MarketplaceTab.SELLING_DASHBOARD -> {
                        SellingDashboardView(
                            summary = uiState.sellerSummary,
                            receivedOffers = uiState.receivedOffers,
                            orders = uiState.orders,
                            isLoading = uiState.isLoading,
                            isSubmitting = uiState.isSubmitting,
                            onAcceptOffer = { viewModel.acceptOffer(it) },
                            onRejectOffer = { viewModel.rejectOffer(it) },
                            onCounterOffer = { viewModel.openCounterDialog(it) },
                            onRefresh = { viewModel.loadSellerDashboard() }
                        )
                    }
                }
            }
        }
    }

    // Product Details Modal Dialog
    uiState.selectedProduct?.let { product ->
        ProductDetailDialog(
            product = product,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeProductDetails() },
            onMessageClick = { viewModel.openMessageDialog() },
            onOfferClick = { viewModel.openOfferDialog() },
            onOrderClick = { viewModel.openOrderDialog() },
            onFavoriteToggle = { viewModel.toggleFavorite(product) },
            onShareClick = {
                val shareIntent = Intent(Intent.ACTION_SEND).apply {
                    type = "text/plain"
                    putExtra(Intent.EXTRA_SUBJECT, product.title)
                    putExtra(
                        Intent.EXTRA_TEXT,
                        "Check out this product on BONDHOO Marketplace: ${product.title} for ${product.currency} ${product.price}\nhttps://bondhoo.com/marketplace/${product.id}"
                    )
                }
                context.startActivity(Intent.createChooser(shareIntent, "Share Listing"))
            },
            onReportClick = { viewModel.openReportDialog() },
            onDeleteClick = { viewModel.deleteListing(product.id) }
        )
    }

    // Make an Offer Dialog
    if (uiState.showOfferDialog && uiState.selectedProduct != null) {
        MakeOfferDialog(
            product = uiState.selectedProduct!!,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeOfferDialog() },
            onSubmit = { amount, message ->
                viewModel.submitOffer(uiState.selectedProduct!!.id, amount, message)
            }
        )
    }

    // Counter Offer Dialog
    if (uiState.showCounterDialog && uiState.selectedOfferForCounter != null) {
        CounterOfferDialog(
            offer = uiState.selectedOfferForCounter!!,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeCounterDialog() },
            onSubmit = { amount, message ->
                viewModel.counterOffer(uiState.selectedOfferForCounter!!.id, amount, message)
            }
        )
    }

    // Report Listing Dialog
    if (uiState.showReportDialog && uiState.selectedProduct != null) {
        ReportListingDialog(
            product = uiState.selectedProduct!!,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeReportDialog() },
            onSubmit = { reason, details ->
                viewModel.reportProduct(uiState.selectedProduct!!.id, reason, details)
            }
        )
    }

    // Create Listing Dialog
    if (uiState.showCreateDialog) {
        CreateListingDialog(
            categoryList = uiState.categoryList,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeCreateDialog() },
            onSubmit = { title, catId, subId, price, origPrice, condition, brand, location, delType, delCharge, isNeg, desc, img, files ->
                viewModel.createListing(
                    title = title,
                    categoryId = catId,
                    subcategoryId = subId,
                    price = price,
                    originalPrice = origPrice,
                    condition = condition,
                    brand = brand,
                    location = location,
                    deliveryType = delType,
                    deliveryCharge = delCharge,
                    isNegotiable = isNeg,
                    description = desc,
                    imageUrl = img,
                    imageFiles = files
                )
            }
        )
    }

    // Message Seller Dialog
    if (uiState.showMessageDialog && uiState.selectedProduct != null) {
        MessageSellerDialog(
            product = uiState.selectedProduct!!,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeMessageDialog() },
            onSend = { message ->
                viewModel.contactSeller(uiState.selectedProduct!!.id, message)
            }
        )
    }

    // Place Order Dialog
    if (uiState.showOrderDialog && uiState.selectedProduct != null) {
        PlaceOrderDialog(
            product = uiState.selectedProduct!!,
            isSubmitting = uiState.isSubmitting,
            onDismiss = { viewModel.closeOrderDialog() },
            onConfirmOrder = { name, phone, address, qty ->
                viewModel.orderProduct(uiState.selectedProduct!!.id, name, phone, address, qty)
            }
        )
    }
}

@Composable
private fun ProductCard(
    product: MarketplaceProductDto,
    onCardClick: () -> Unit,
    onFavoriteClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onCardClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(140.dp)
            ) {
                AsyncImage(
                    model = product.primary_image_url ?: product.primary_image ?: "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800",
                    contentDescription = product.title,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize()
                )

                // Condition Badge
                Surface(
                    shape = RoundedCornerShape(4.dp),
                    color = Color.Black.copy(alpha = 0.65f),
                    modifier = Modifier
                        .padding(8.dp)
                        .align(Alignment.TopStart)
                ) {
                    Text(
                        text = product.condition.uppercase(),
                        color = Color.White,
                        fontSize = 9.sp,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }

                // Bookmark/Favorite Button
                IconButton(
                    onClick = onFavoriteClick,
                    modifier = Modifier
                        .padding(4.dp)
                        .size(32.dp)
                        .align(Alignment.TopEnd)
                        .background(Color.White.copy(alpha = 0.85f), CircleShape)
                ) {
                    Icon(
                        imageVector = if (product.is_favorited) Icons.Default.Bookmark else Icons.Outlined.BookmarkBorder,
                        contentDescription = "Bookmark",
                        tint = if (product.is_favorited) ConnectlyBlue else Color.DarkGray,
                        modifier = Modifier.size(16.dp)
                    )
                }
            }

            Column(
                modifier = Modifier.padding(10.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                // Price with currency
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = "৳ ${product.price.toLong()}",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = ConnectlyBlue
                    )
                    val origPrice = product.original_price
                    if (origPrice != null && origPrice > product.price) {
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "৳ ${origPrice.toLong()}",
                            fontSize = 11.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            style = androidx.compose.ui.text.TextStyle(textDecoration = androidx.compose.ui.text.style.TextDecoration.LineThrough)
                        )
                    }
                }

                // Title
                Text(
                    text = product.title,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                // Location Pin
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(3.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.LocationOn,
                        contentDescription = null,
                        modifier = Modifier.size(12.dp),
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        text = product.location ?: "Dhaka, Bangladesh",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
        }
    }
}

@Composable
private fun ProductDetailDialog(
    product: MarketplaceProductDto,
    isSubmitting: Boolean = false,
    onDismiss: () -> Unit,
    onMessageClick: () -> Unit,
    onOfferClick: () -> Unit,
    onOrderClick: () -> Unit,
    onFavoriteToggle: () -> Unit,
    onShareClick: () -> Unit,
    onReportClick: () -> Unit,
    onDeleteClick: () -> Unit
) {
    var showConfirmDelete by remember { mutableStateOf(false) }

    if (showConfirmDelete) {
        AlertDialog(
            onDismissRequest = { if (!isSubmitting) showConfirmDelete = false },
            title = { Text("Delete Listing", fontWeight = FontWeight.Bold) },
            text = { Text("Are you sure you want to permanently delete \"${product.title}\"? This action cannot be undone.") },
            confirmButton = {
                Button(
                    onClick = {
                        onDeleteClick()
                        showConfirmDelete = false
                    },
                    enabled = !isSubmitting,
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    if (isSubmitting) {
                        CircularProgressIndicator(modifier = Modifier.size(16.dp), color = MaterialTheme.colorScheme.onError, strokeWidth = 2.dp)
                    } else {
                        Text("Delete")
                    }
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { showConfirmDelete = false },
                    enabled = !isSubmitting
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        confirmButton = {},
        dismissButton = {},
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                // Image Header with Condition Badge & Action Buttons
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(200.dp)
                        .clip(RoundedCornerShape(12.dp))
                ) {
                    AsyncImage(
                        model = product.primary_image_url ?: product.primary_image ?: "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800",
                        contentDescription = product.title,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize()
                    )

                    // Share button on top right
                    Row(
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .padding(8.dp),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        IconButton(
                            onClick = onShareClick,
                            modifier = Modifier
                                .size(32.dp)
                                .background(Color.White.copy(alpha = 0.85f), CircleShape)
                        ) {
                            Icon(Icons.Default.Share, contentDescription = "Share", tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
                        }
                        IconButton(
                            onClick = onFavoriteToggle,
                            modifier = Modifier
                                .size(32.dp)
                                .background(Color.White.copy(alpha = 0.85f), CircleShape)
                        ) {
                            Icon(
                                imageVector = if (product.is_favorited) Icons.Default.Bookmark else Icons.Outlined.BookmarkBorder,
                                contentDescription = "Save",
                                tint = if (product.is_favorited) ConnectlyBlue else Color.DarkGray,
                                modifier = Modifier.size(16.dp)
                            )
                        }
                    }
                }

                // Title & Price
                Text(
                    text = product.title,
                    fontSize = 17.sp,
                    fontWeight = FontWeight.Bold
                )

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Text(
                        text = "৳ ${product.price.toLong()}",
                        fontSize = 19.sp,
                        fontWeight = FontWeight.Bold,
                        color = ConnectlyBlue
                    )
                    val origPrice = product.original_price
                    if (origPrice != null && origPrice > product.price) {
                        Text(
                            text = "৳ ${origPrice.toLong()}",
                            fontSize = 13.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            style = androidx.compose.ui.text.TextStyle(textDecoration = androidx.compose.ui.text.style.TextDecoration.LineThrough)
                        )
                        if (product.discount_percent != null) {
                            Surface(color = Color(0xFFE8F5E9), shape = RoundedCornerShape(4.dp)) {
                                Text(
                                    text = "${product.discount_percent}% OFF",
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color(0xFF2E7D32),
                                    modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp)
                                )
                            }
                        }
                    }
                }

                // Attributes Row: Condition, Location, Delivery
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    AssistChip(
                        onClick = {},
                        label = { Text("Condition: ${product.condition.replaceFirstChar { it.uppercase() }}", fontSize = 11.sp) }
                    )
                    AssistChip(
                        onClick = {},
                        label = { Text(product.location ?: "Dhaka", fontSize = 11.sp) },
                        leadingIcon = { Icon(Icons.Default.LocationOn, contentDescription = null, modifier = Modifier.size(14.dp), tint = Color.Red) }
                    )
                }

                // Verified Seller Card
                Surface(
                    shape = RoundedCornerShape(10.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(10.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(36.dp)
                                .clip(CircleShape)
                                .background(ConnectlyBlue.copy(alpha = 0.2f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(Icons.Default.Person, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                        }
                        Column(modifier = Modifier.weight(1f)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(
                                    text = product.seller_name ?: "Bondhoo Seller",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 13.sp
                                )
                                Spacer(modifier = Modifier.width(4.dp))
                                Icon(Icons.Default.Verified, contentDescription = "Verified", tint = ConnectlyBlue, modifier = Modifier.size(14.dp))
                            }
                            Text(
                                text = "Verified Member • ★ 4.9 (42 reviews)",
                                fontSize = 11.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                // Description
                Text("Description", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                Text(
                    text = product.description ?: "No description provided.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                if (!product.is_owner) {
                    // Action Buttons: Message, Make Offer, Buy Now
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        OutlinedButton(
                            onClick = onMessageClick,
                            modifier = Modifier.weight(1f),
                            contentPadding = PaddingValues(horizontal = 6.dp)
                        ) {
                            Icon(Icons.Outlined.ChatBubbleOutline, contentDescription = null, modifier = Modifier.size(14.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Message", fontSize = 11.sp)
                        }

                        OutlinedButton(
                            onClick = onOfferClick,
                            modifier = Modifier.weight(1f),
                            contentPadding = PaddingValues(horizontal = 6.dp)
                        ) {
                            Icon(Icons.Default.LocalOffer, contentDescription = null, modifier = Modifier.size(14.dp), tint = ConnectlyBlue)
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Offer", fontSize = 11.sp)
                        }

                        Button(
                            onClick = onOrderClick,
                            modifier = Modifier.weight(1.2f),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            contentPadding = PaddingValues(horizontal = 6.dp)
                        ) {
                            Icon(Icons.Default.ShoppingCart, contentDescription = null, modifier = Modifier.size(14.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Buy Now", fontSize = 11.sp)
                        }
                    }

                    // Visitor Report option
                    TextButton(
                        onClick = onReportClick,
                        colors = ButtonDefaults.textButtonColors(contentColor = MaterialTheme.colorScheme.onSurfaceVariant),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Icon(Icons.Outlined.Report, contentDescription = null, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Report Listing", fontSize = 11.sp)
                    }
                } else {
                    // Owner Options: Delete My Listing
                    Button(
                        onClick = { showConfirmDelete = true },
                        enabled = !isSubmitting,
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(modifier = Modifier.size(16.dp), color = MaterialTheme.colorScheme.onError, strokeWidth = 2.dp)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Deleting...", fontSize = 13.sp)
                        } else {
                            Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Delete My Listing", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }
                }
            }
        }
    )
}

@Composable
private fun MakeOfferDialog(
    product: MarketplaceProductDto,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (amount: Double, message: String?) -> Unit
) {
    var offerText by remember { mutableStateOf("") }
    var note by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = {
            Text("Make an Offer (অফার পাঠান)", fontWeight = FontWeight.Bold, fontSize = 16.sp)
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(
                    text = "Listing: ${product.title}\nListed Price: ৳${product.price.toLong()}",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                OutlinedTextField(
                    value = offerText,
                    onValueChange = { offerText = it },
                    label = { Text("Your Offer in BDT (৳)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = note,
                    onValueChange = { note = it },
                    label = { Text("Note to seller (optional)") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val amt = offerText.toDoubleOrNull() ?: 0.0
                    onSubmit(amt, note.takeIf { it.isNotBlank() })
                },
                enabled = !isSubmitting && offerText.toDoubleOrNull() != null && (offerText.toDoubleOrNull() ?: 0.0) > 0,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Send Offer")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun CounterOfferDialog(
    offer: MarketplaceOfferDto,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (amount: Double, message: String?) -> Unit
) {
    var counterText by remember { mutableStateOf("") }
    var note by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = { Text("Send Counteroffer", fontWeight = FontWeight.Bold, fontSize = 16.sp) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(
                    text = "Buyer offered ৳${offer.offer_amount.toLong()} for ${offer.product_title ?: "this item"}.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                OutlinedTextField(
                    value = counterText,
                    onValueChange = { counterText = it },
                    label = { Text("Your Counter Price (৳)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = note,
                    onValueChange = { note = it },
                    label = { Text("Message to buyer") },
                    maxLines = 2,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val amt = counterText.toDoubleOrNull() ?: 0.0
                    onSubmit(amt, note.takeIf { it.isNotBlank() })
                },
                enabled = !isSubmitting && counterText.toDoubleOrNull() != null && (counterText.toDoubleOrNull() ?: 0.0) > 0,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Counter")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun ReportListingDialog(
    product: MarketplaceProductDto,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (reason: String, details: String?) -> Unit
) {
    val reasons = listOf("Scam or Fraud", "Counterfeit or Fake", "Prohibited Item", "Misleading Information", "Spam")
    var selectedReason by remember { mutableStateOf(reasons[0]) }
    var details by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = { Text("Report Listing", fontWeight = FontWeight.Bold, fontSize = 16.sp) },
        text = {
            Column(
                modifier = Modifier.verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Text("Why are you reporting \"${product.title}\"?", fontSize = 12.sp)
                reasons.forEach { r ->
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { selectedReason = r }
                    ) {
                        RadioButton(selected = selectedReason == r, onClick = { selectedReason = r })
                        Text(r, fontSize = 12.sp)
                    }
                }
                OutlinedTextField(
                    value = details,
                    onValueChange = { details = it },
                    label = { Text("Additional details (optional)") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(selectedReason.lowercase().replace(" ", "_"), details.takeIf { it.isNotBlank() }) },
                enabled = !isSubmitting,
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Submit Report")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun CategoriesDirectoryView(
    categoryList: List<MarketplaceCategoryDto>,
    onSelectCategory: (categoryName: String, subcategorySlug: String?) -> Unit
) {
    LazyVerticalGrid(
        columns = GridCells.Fixed(2),
        horizontalArrangement = Arrangement.spacedBy(10.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
        modifier = Modifier.fillMaxSize()
    ) {
        items(categoryList, key = { it.id }) { cat ->
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { onSelectCategory(cat.name, null) }
            ) {
                Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(text = cat.name, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = ConnectlyBlue)
                    Text(
                        text = "${cat.subcategories.size} subcategories",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    cat.subcategories.take(3).forEach { sub ->
                        Text(
                            text = "• ${sub.name}",
                            fontSize = 11.sp,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                            modifier = Modifier.clickable { onSelectCategory(cat.name, sub.slug) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun OrdersListView(
    orders: List<MarketplaceOrderDto>,
    isLoading: Boolean,
    onRefresh: () -> Unit
) {
    if (isLoading) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator(color = ConnectlyBlue)
        }
    } else if (orders.isEmpty()) {
        Column(
            modifier = Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(Icons.Outlined.ShoppingBag, contentDescription = null, modifier = Modifier.size(56.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(10.dp))
            Text("No Orders Yet", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            Text("When you buy or sell products, orders will appear here.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            items(orders, key = { it.id }) { order ->
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(order.order_number ?: "ORD-${order.id}", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                            Surface(
                                shape = RoundedCornerShape(4.dp),
                                color = when (order.status) {
                                    "completed", "delivered" -> Color(0xFFE8F5E9)
                                    "cancelled" -> Color(0xFFFFEBEE)
                                    else -> ConnectlyBlue.copy(alpha = 0.12f)
                                }
                            ) {
                                Text(
                                    text = order.status.uppercase(),
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = when (order.status) {
                                        "completed", "delivered" -> Color(0xFF2E7D32)
                                        "cancelled" -> Color(0xFFC62828)
                                        else -> ConnectlyBlue
                                    },
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                )
                            }
                        }
                        Text("Total: ৳ ${order.amount.toLong()}", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = ConnectlyBlue)
                        Text("Ship to: ${order.shipping_name ?: "Buyer"} • ${order.shipping_address ?: "Dhaka"}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Surface(shape = RoundedCornerShape(4.dp), color = Color(0xFFFFF8E1)) {
                            Text("🛡️ Escrow Protection Active", fontSize = 10.sp, color = Color(0xFFF57F17), modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SellingDashboardView(
    summary: MarketplaceSellerSummaryDto?,
    receivedOffers: List<MarketplaceOfferDto>,
    orders: List<MarketplaceOrderDto>,
    isLoading: Boolean,
    isSubmitting: Boolean,
    onAcceptOffer: (offerId: Long) -> Unit,
    onRejectOffer: (offerId: Long) -> Unit,
    onCounterOffer: (offer: MarketplaceOfferDto) -> Unit,
    onRefresh: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Metric Cards Grid
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard("Active Items", "${summary?.active_listings ?: 0}", Modifier.weight(1f))
            MetricCard("Sold Items", "${summary?.sold_listings ?: 0}", Modifier.weight(1f))
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard("Total Orders", "${summary?.total_orders ?: 0}", Modifier.weight(1f))
            MetricCard("Revenue", "৳ ${(summary?.gross_sales ?: 0.0).toLong()}", Modifier.weight(1f), isHighlight = true)
        }

        // Offers Management Section
        Text("Inquiries & Offers (${receivedOffers.size})", fontWeight = FontWeight.Bold, fontSize = 14.sp)
        if (receivedOffers.isEmpty()) {
            Surface(
                shape = RoundedCornerShape(10.dp),
                color = MaterialTheme.colorScheme.surface,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(
                    text = "No pending buyer offers at this time.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(14.dp)
                )
            }
        } else {
            receivedOffers.forEach { offer ->
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(offer.buyer_name ?: "Buyer", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                            Text(
                                text = "Offered: ৳ ${offer.offer_amount.toLong()}",
                                fontWeight = FontWeight.Bold,
                                color = ConnectlyBlue,
                                fontSize = 13.sp
                            )
                        }
                        if (offer.message != null) {
                            Text("\"${offer.message}\"", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                        if (offer.status == "pending") {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(6.dp)
                            ) {
                                Button(
                                    onClick = { onAcceptOffer(offer.id) },
                                    enabled = !isSubmitting,
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF2E7D32)),
                                    modifier = Modifier.weight(1f),
                                    contentPadding = PaddingValues(4.dp)
                                ) {
                                    Text("Accept", fontSize = 11.sp)
                                }
                                Button(
                                    onClick = { onCounterOffer(offer) },
                                    enabled = !isSubmitting,
                                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                    modifier = Modifier.weight(1f),
                                    contentPadding = PaddingValues(4.dp)
                                ) {
                                    Text("Counter", fontSize = 11.sp)
                                }
                                OutlinedButton(
                                    onClick = { onRejectOffer(offer.id) },
                                    enabled = !isSubmitting,
                                    modifier = Modifier.weight(1f),
                                    contentPadding = PaddingValues(4.dp)
                                ) {
                                    Text("Decline", fontSize = 11.sp, color = MaterialTheme.colorScheme.error)
                                }
                            }
                        } else {
                            Surface(shape = RoundedCornerShape(4.dp), color = ConnectlyBlue.copy(alpha = 0.1f)) {
                                Text(
                                    text = "Status: ${offer.status.uppercase()}",
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = ConnectlyBlue,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MetricCard(title: String, value: String, modifier: Modifier = Modifier, isHighlight: Boolean = false) {
    Card(
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = if (isHighlight) ConnectlyBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surface),
        modifier = modifier
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(text = title, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = value,
                fontSize = 16.sp,
                fontWeight = FontWeight.Bold,
                color = if (isHighlight) ConnectlyBlue else MaterialTheme.colorScheme.onSurface
            )
        }
    }
}

@Composable
private fun CreateListingDialog(
    categoryList: List<MarketplaceCategoryDto>,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (
        title: String,
        categoryId: Long,
        subcategoryId: Long?,
        price: Double,
        originalPrice: Double?,
        condition: String,
        brand: String?,
        location: String,
        deliveryType: String,
        deliveryCharge: Double,
        isNegotiable: Boolean,
        description: String,
        imageUrl: String?,
        imageFiles: List<File>
    ) -> Unit
) {
    val context = LocalContext.current
    var title by remember { mutableStateOf("") }
    var priceText by remember { mutableStateOf("") }
    var originalPriceText by remember { mutableStateOf("") }
    var location by remember { mutableStateOf("Dhaka, Bangladesh") }
    var brand by remember { mutableStateOf("") }
    var isNegotiable by remember { mutableStateOf(false) }
    var deliveryChargeText by remember { mutableStateOf("0") }
    var description by remember { mutableStateOf("") }
    var imageUrl by remember { mutableStateOf("") }
    var selectedUris by remember { mutableStateOf<List<Uri>>(emptyList()) }
    var showUrlField by remember { mutableStateOf(false) }
    var selectedCategoryId by remember { mutableLongStateOf(categoryList.firstOrNull()?.id ?: 1L) }
    var selectedSubcategoryId by remember { mutableStateOf<Long?>(null) }
    var selectedCondition by remember { mutableStateOf("used") }
    var selectedDeliveryType by remember { mutableStateOf("pickup") }

    val photoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickMultipleVisualMedia(maxItems = 5)
    ) { uris ->
        if (uris.isNotEmpty()) {
            selectedUris = (selectedUris + uris).distinct().take(5)
        }
    }

    val currentCat = categoryList.firstOrNull { it.id == selectedCategoryId }

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = {
            Text(
                text = "Create New Listing (পণ্য বিক্রি করুন)",
                fontWeight = FontWeight.Bold,
                fontSize = 16.sp
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Product Title (নাম)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = priceText,
                        onValueChange = { priceText = it },
                        label = { Text("Price (৳)") },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                    OutlinedTextField(
                        value = originalPriceText,
                        onValueChange = { originalPriceText = it },
                        label = { Text("Old Price (৳)") },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                }

                // Photo Upload Section
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(12.dp))
                        .background(Color(0xFFF1F5F9))
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "Product Photos (পণ্যের ছবি)",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF1E293B)
                        )
                        Text(
                            text = "${selectedUris.size}/5 Photos",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }

                    if (selectedUris.isNotEmpty()) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            selectedUris.forEachIndexed { index, uri ->
                                Box(
                                    modifier = Modifier
                                        .size(72.dp)
                                        .clip(RoundedCornerShape(8.dp))
                                ) {
                                    AsyncImage(
                                        model = uri,
                                        contentDescription = "Selected photo",
                                        contentScale = ContentScale.Crop,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                    if (index == 0) {
                                        Surface(
                                            color = ConnectlyBlue.copy(alpha = 0.9f),
                                            modifier = Modifier
                                                .align(Alignment.BottomCenter)
                                                .fillMaxWidth()
                                        ) {
                                            Text(
                                                text = "Cover",
                                                fontSize = 9.sp,
                                                color = Color.White,
                                                fontWeight = FontWeight.Bold,
                                                textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                            )
                                        }
                                    }
                                    IconButton(
                                        onClick = { selectedUris = selectedUris.filter { it != uri } },
                                        modifier = Modifier
                                            .align(Alignment.TopEnd)
                                            .size(20.dp)
                                            .background(Color.Black.copy(alpha = 0.6f), CircleShape)
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.Close,
                                            contentDescription = "Remove photo",
                                            tint = Color.White,
                                            modifier = Modifier.size(12.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }

                    OutlinedButton(
                        onClick = {
                            photoPickerLauncher.launch(
                                PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(8.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            contentColor = ConnectlyBlue
                        )
                    ) {
                        Icon(
                            imageVector = Icons.Default.AddPhotoAlternate,
                            contentDescription = null,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = if (selectedUris.isEmpty()) "+ Upload Photos from Device (ছবি যুক্ত করুন)" else "+ Add More Photos",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                    }

                    Row(
                        modifier = Modifier
                            .clickable { showUrlField = !showUrlField }
                            .padding(vertical = 4.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = if (showUrlField) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                            contentDescription = null,
                            tint = Color(0xFF64748B),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = if (showUrlField) "Hide Image URL option" else "Or add photo via URL (ছবির লিঙ্ক)",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }

                    if (showUrlField) {
                        OutlinedTextField(
                            value = imageUrl,
                            onValueChange = { imageUrl = it },
                            label = { Text("Photo Image URL (ছবির লিঙ্ক)", fontSize = 11.sp) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                }

                // Category selector row
                Text("Category (বিভাগ)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    categoryList.forEach { cat ->
                        FilterChip(
                            selected = selectedCategoryId == cat.id,
                            onClick = {
                                selectedCategoryId = cat.id
                                selectedSubcategoryId = null
                            },
                            label = { Text(cat.name, fontSize = 11.sp) }
                        )
                    }
                }

                // Subcategory selector
                if (currentCat != null && currentCat.subcategories.isNotEmpty()) {
                    Text("Subcategory", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        currentCat.subcategories.forEach { sub ->
                            FilterChip(
                                selected = selectedSubcategoryId == sub.id,
                                onClick = { selectedSubcategoryId = sub.id },
                                label = { Text(sub.name, fontSize = 10.sp) }
                            )
                        }
                    }
                }

                // Condition row
                Text("Condition (অবস্থা)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("new" to "New", "used" to "Used", "refurbished" to "Like New").forEach { (code, label) ->
                        FilterChip(
                            selected = selectedCondition == code,
                            onClick = { selectedCondition = code },
                            label = { Text(label, fontSize = 11.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = brand,
                    onValueChange = { brand = it },
                    label = { Text("Brand / Manufacturer") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = location,
                    onValueChange = { location = it },
                    label = { Text("Location (ঠিকানা)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Description (বিস্তারিত বিবরণ)") },
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val p = priceText.toDoubleOrNull() ?: 0.0
                    val orig = originalPriceText.toDoubleOrNull()
                    val delCh = deliveryChargeText.toDoubleOrNull() ?: 0.0
                    val files = selectedUris.mapNotNull { uriToFile(context, it) }
                    onSubmit(
                        title,
                        selectedCategoryId,
                        selectedSubcategoryId,
                        p,
                        orig,
                        selectedCondition,
                        brand.takeIf { it.isNotBlank() },
                        location,
                        selectedDeliveryType,
                        delCh,
                        isNegotiable,
                        description,
                        imageUrl.takeIf { it.isNotBlank() },
                        files
                    )
                },
                enabled = !isSubmitting && title.isNotBlank() && priceText.toDoubleOrNull() != null,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Publish Listing")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

/**
 * Safely copy content Uri into a cache file for multipart upload.
 */
private fun uriToFile(context: Context, uri: Uri): File? {
    return try {
        val contentResolver = context.contentResolver
        val mime = contentResolver.getType(uri) ?: "image/jpeg"
        val ext = MimeTypeMap.getSingleton().getExtensionFromMimeType(mime) ?: "jpg"
        val tempFile = File.createTempFile("market_prod_", ".$ext", context.cacheDir)
        contentResolver.openInputStream(uri)?.use { input ->
            FileOutputStream(tempFile).use { output ->
                input.copyTo(output)
            }
        }
        tempFile
    } catch (_: Exception) {
        null
    }
}

@Composable
private fun MessageSellerDialog(
    product: MarketplaceProductDto,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSend: (message: String) -> Unit
) {
    var messageText by remember { mutableStateOf("Hi! Is this item still available?") }
    val quickPhrases = listOf(
        "Is this item still available?",
        "What's your best price?",
        "Can I inspect the product?",
        "Is delivery available in Dhaka?"
    )

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = {
            Text(
                text = "Message Seller",
                fontWeight = FontWeight.Bold,
                fontSize = 16.sp
            )
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(
                    text = "Seller: ${product.seller_name ?: "Seller"}",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Quick chips
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    quickPhrases.forEach { phrase ->
                        SuggestionChip(
                            onClick = { messageText = phrase },
                            label = { Text(phrase, fontSize = 10.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = messageText,
                    onValueChange = { messageText = it },
                    label = { Text("Your Message") },
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSend(messageText) },
                enabled = !isSubmitting && messageText.isNotBlank(),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Send")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun PlaceOrderDialog(
    product: MarketplaceProductDto,
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onConfirmOrder: (shippingName: String, shippingPhone: String, shippingAddress: String, quantity: Int) -> Unit
) {
    var shippingName by remember { mutableStateOf("") }
    var shippingPhone by remember { mutableStateOf("") }
    var shippingAddress by remember { mutableStateOf("") }
    var quantity by remember { mutableIntStateOf(1) }

    val totalAmount = product.price * quantity

    AlertDialog(
        onDismissRequest = { if (!isSubmitting) onDismiss() },
        title = {
            Text(
                text = "Buy Now (অর্ডার কনফার্ম করুন)",
                fontWeight = FontWeight.Bold,
                fontSize = 16.sp
            )
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = ConnectlyBlue.copy(alpha = 0.08f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text(product.title, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                        Text(
                            text = "Total Payable: ৳ ${totalAmount.toLong()}",
                            fontSize = 14.sp,
                            fontWeight = FontWeight.Bold,
                            color = ConnectlyBlue
                        )
                        Text(
                            text = "🛡️ Escrow Protected: Payment released only upon verified delivery.",
                            fontSize = 10.sp,
                            color = Color(0xFF2E7D32)
                        )
                    }
                }

                OutlinedTextField(
                    value = shippingName,
                    onValueChange = { shippingName = it },
                    label = { Text("Your Full Name (নাম)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = shippingPhone,
                    onValueChange = { shippingPhone = it },
                    label = { Text("Phone Number (মোবাইল নম্বর)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = shippingAddress,
                    onValueChange = { shippingAddress = it },
                    label = { Text("Delivery Address (ঠিকানা)") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onConfirmOrder(shippingName, shippingPhone, shippingAddress, quantity) },
                enabled = !isSubmitting && shippingName.isNotBlank() && shippingPhone.isNotBlank() && shippingAddress.isNotBlank(),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                } else {
                    Text("Confirm Order (৳ ${totalAmount.toLong()})")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun EmptyStateView(
    tab: MarketplaceTab,
    onActionClick: () -> Unit
) {
    Column(
        modifier = Modifier.padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Icon(
            imageVector = when (tab) {
                MarketplaceTab.SAVED -> Icons.Outlined.BookmarkBorder
                MarketplaceTab.MY_LISTINGS -> Icons.Outlined.ShoppingBag
                else -> Icons.Default.SearchOff
            },
            contentDescription = null,
            modifier = Modifier.size(48.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = when (tab) {
                MarketplaceTab.SAVED -> "No saved items yet"
                MarketplaceTab.MY_LISTINGS -> "You haven't listed any items yet"
                else -> "No products found"
            },
            fontWeight = FontWeight.Bold,
            fontSize = 15.sp
        )
        Text(
            text = when (tab) {
                MarketplaceTab.SAVED -> "Bookmark products to view them easily anytime!"
                MarketplaceTab.MY_LISTINGS -> "Sell your unused electronics, fashion, or gadgets to your community!"
                else -> "Try adjusting your search query, price range, or category filter."
            },
            fontSize = 12.sp,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            textAlign = androidx.compose.ui.text.style.TextAlign.Center
        )
        Spacer(modifier = Modifier.height(4.dp))
        Button(
            onClick = onActionClick,
            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
        ) {
            Text(
                text = when (tab) {
                    MarketplaceTab.MY_LISTINGS -> "+ Create First Listing"
                    MarketplaceTab.SAVED -> "Browse Products"
                    else -> "Refresh Listings"
                },
                fontSize = 12.sp
            )
        }
    }
}
