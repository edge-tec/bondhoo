package com.connectly.android.feature.profile.presentation.marketplace

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Place
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.ShoppingBag
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.MarketplaceProductDto
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MarketplaceScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    api: ConnectlyApi? = null
) {
    var searchQuery by remember { mutableStateOf("") }
    var selectedCategory by remember { mutableStateOf("All") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var products by remember { mutableStateOf<List<MarketplaceProductDto>>(emptyList()) }
    val scope = rememberCoroutineScope()

    val categories = remember {
        listOf("All", "Electronics", "Vehicles", "Property", "Apparel", "Home", "Hobbies")
    }

    fun loadProducts() {
        if (api == null) return
        isLoading = true
        errorMessage = null
        scope.launch {
            try {
                val catQuery = if (selectedCategory == "All") null else selectedCategory
                val qQuery = searchQuery.takeIf { it.isNotBlank() }
                val response = api.getMarketplaceProducts(category = catQuery, query = qQuery)
                if (response.isSuccessful && response.body() != null) {
                    products = response.body()!!.data
                } else {
                    errorMessage = "Failed to load products"
                }
            } catch (e: Exception) {
                errorMessage = e.localizedMessage ?: "Network error"
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(selectedCategory) {
        loadProducts()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Marketplace",
                        fontWeight = FontWeight.Bold,
                        fontSize = 20.sp
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
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
            // Search Bar
            OutlinedTextField(
                value = searchQuery,
                onValueChange = {
                    searchQuery = it
                },
                placeholder = { Text("Search Marketplace...") },
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Default.Search,
                        contentDescription = "Search",
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                },
                trailingIcon = {
                    if (searchQuery.isNotBlank()) {
                        TextButton(onClick = { loadProducts() }) {
                            Text("Go", color = ConnectlyBlue, fontWeight = FontWeight.Bold)
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(24.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
            )

            // Category Chips Row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                categories.take(4).forEach { category ->
                    val isSelected = selectedCategory == category
                    FilterChip(
                        selected = isSelected,
                        onClick = { selectedCategory = category },
                        label = { Text(category, fontSize = 12.sp) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                            selectedLabelColor = ConnectlyBlue
                        )
                    )
                }
            }

            // Main Content Area
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                contentAlignment = Alignment.Center
            ) {
                when {
                    isLoading -> {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }
                    errorMessage != null && products.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = errorMessage ?: "Error loading marketplace",
                                color = MaterialTheme.colorScheme.error,
                                fontSize = 14.sp
                            )
                            Button(onClick = { loadProducts() }) {
                                Text("Retry")
                            }
                        }
                    }
                    products.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.ShoppingBag,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.size(56.dp)
                            )
                            Text(
                                text = "No products found",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Text(
                                text = "Be the first to list items in your community!",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    else -> {
                        LazyVerticalGrid(
                            columns = GridCells.Fixed(2),
                            horizontalArrangement = Arrangement.spacedBy(10.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                            modifier = Modifier.fillMaxSize()
                        ) {
                            items(products, key = { it.id }) { product ->
                                Card(
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(
                                        containerColor = MaterialTheme.colorScheme.surface
                                    ),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column {
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .height(130.dp)
                                                .background(MaterialTheme.colorScheme.surfaceVariant)
                                        ) {
                                            if (!product.primary_image_url.isNullOrBlank()) {
                                                AsyncImage(
                                                    model = product.primary_image_url,
                                                    contentDescription = product.title,
                                                    contentScale = ContentScale.Crop,
                                                    modifier = Modifier.fillMaxSize()
                                                )
                                            } else {
                                                Icon(
                                                    imageVector = Icons.Default.ShoppingBag,
                                                    contentDescription = null,
                                                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                                    modifier = Modifier
                                                        .size(36.dp)
                                                        .align(Alignment.Center)
                                                )
                                            }
                                        }

                                        Column(modifier = Modifier.padding(10.dp)) {
                                            Text(
                                                text = "${product.currency} ${product.price.toInt()}",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 15.sp,
                                                color = ConnectlyBlue
                                            )
                                            Text(
                                                text = product.title,
                                                fontWeight = FontWeight.Medium,
                                                fontSize = 13.sp,
                                                maxLines = 1,
                                                overflow = TextOverflow.Ellipsis,
                                                color = MaterialTheme.colorScheme.onSurface
                                            )
                                            if (!product.location.isNullOrBlank()) {
                                                Row(
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.spacedBy(2.dp),
                                                    modifier = Modifier.padding(top = 2.dp)
                                                ) {
                                                    Icon(
                                                        imageVector = Icons.Default.Place,
                                                        contentDescription = null,
                                                        tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                                        modifier = Modifier.size(12.dp)
                                                    )
                                                    Text(
                                                        text = product.location ?: "",
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
                            }
                        }
                    }
                }
            }
        }
    }
}
