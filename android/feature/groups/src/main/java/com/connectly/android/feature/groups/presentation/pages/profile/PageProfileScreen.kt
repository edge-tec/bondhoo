package com.connectly.android.feature.groups.presentation.pages.profile

import android.content.Intent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageProfileScreen(
    pageIdOrSlug: String,
    onNavigateBack: () -> Unit = {},
    onNavigateToDashboard: (Long) -> Unit = {},
    onNavigateToInbox: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageProfileViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(pageIdOrSlug) {
        if (state.pageIdOrSlug != pageIdOrSlug) {
            viewModel.handleIntent(PageProfileIntent.LoadProfile(pageIdOrSlug))
        }
    }

    var showWriteReviewDialog by remember { mutableStateOf(false) }
    var reviewRating by remember { mutableIntStateOf(5) }
    var reviewText by remember { mutableStateOf("") }

    if (showWriteReviewDialog) {
        AlertDialog(
            onDismissRequest = { showWriteReviewDialog = false },
            title = { Text("Write a Page Review", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Rating:", fontWeight = FontWeight.SemiBold)
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        (1..5).forEach { star ->
                            IconButton(onClick = { reviewRating = star }) {
                                Icon(
                                    imageVector = if (star <= reviewRating) Icons.Default.Star else Icons.Outlined.Star,
                                    contentDescription = "Star $star",
                                    tint = ConnectlyAmber
                                )
                            }
                        }
                    }
                    OutlinedTextField(
                        value = reviewText,
                        onValueChange = { reviewText = it },
                        label = { Text("Your Review & Experience") },
                        maxLines = 4,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (reviewText.isNotBlank()) {
                            viewModel.handleIntent(PageProfileIntent.SubmitReview(reviewRating, reviewText))
                            reviewText = ""
                            showWriteReviewDialog = false
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                ) {
                    Text("Submit Review")
                }
            },
            dismissButton = {
                TextButton(onClick = { showWriteReviewDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(state.page?.name ?: "Page Profile", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = {
                        val shareIntent = Intent(Intent.ACTION_SEND).apply {
                            type = "text/plain"
                            putExtra(Intent.EXTRA_SUBJECT, state.page?.name ?: "Page")
                            putExtra(Intent.EXTRA_TEXT, "Check out ${state.page?.name} on Connectly!")
                        }
                        context.startActivity(Intent.createChooser(shareIntent, "Share Page"))
                    }) {
                        Icon(Icons.Default.Share, contentDescription = "Share")
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        if (state.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyElectricBlue)
            }
        } else if (state.page == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                Text("Page not found or unavailable.", color = TextSecondaryLight)
            }
        } else {
            val page = state.page!!
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            ) {
                // Cover & Header Section
                item {
                    Box(modifier = Modifier.fillMaxWidth().height(180.dp)) {
                        if (!page.resolvedCover.isNullOrBlank()) {
                            AsyncImage(
                                model = page.resolvedCover,
                                contentDescription = "Cover Image",
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.fillMaxSize()
                            )
                        } else {
                            Box(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .background(
                                        Brush.verticalGradient(listOf(Color(0xFF1E3A8A), Color(0xFF0284C7)))
                                    )
                            )
                        }
                    }
                }

                // Profile Avatar & Key Metadata
                item {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.Bottom,
                            modifier = Modifier.offset(y = (-40).dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(80.dp)
                                    .clip(CircleShape)
                                    .background(Color.White)
                                    .padding(3.dp)
                            ) {
                                AsyncImage(
                                    model = page.resolvedAvatar,
                                    contentDescription = page.name,
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier.fillMaxSize().clip(CircleShape)
                                )
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column(modifier = Modifier.padding(bottom = 8.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text(
                                        text = page.name,
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 19.sp,
                                        color = TextPrimaryLight
                                    )
                                    if (page.is_verified) {
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Icon(
                                            Icons.Default.CheckCircle,
                                            contentDescription = "Verified",
                                            tint = ConnectlyElectricBlue,
                                            modifier = Modifier.size(18.dp)
                                        )
                                    }
                                }
                                Text(
                                    text = "@${page.resolvedUsername} • ${page.category}",
                                    fontSize = 12.sp,
                                    color = TextSecondaryLight
                                )
                            }
                        }

                        // Short Bio
                        if (!page.bio.isNullOrBlank()) {
                            Text(
                                text = page.bio!!,
                                fontSize = 13.sp,
                                color = TextPrimaryLight,
                                modifier = Modifier.offset(y = (-30).dp)
                            )
                        }

                        // Stats Row
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .offset(y = (-20).dp),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            StatCard("Followers", "${page.followers_count}")
                            StatCard("Rating", "★ ${page.rating}")
                            StatCard("Posts", "${page.total_posts}")
                            StatCard("Videos", "${page.total_videos}")
                        }

                        // Action Buttons Row
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .offset(y = (-10).dp),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Button(
                                onClick = { viewModel.handleIntent(PageProfileIntent.ToggleFollow) },
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = if (state.isFollowing) SurfaceSubtle else ConnectlyElectricBlue,
                                    contentColor = if (state.isFollowing) TextPrimaryLight else Color.White
                                ),
                                shape = RoundedCornerShape(20.dp),
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(if (state.isFollowing) "Following" else "Follow", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }

                            OutlinedButton(
                                onClick = { onNavigateToInbox(page.id) },
                                shape = RoundedCornerShape(20.dp),
                                modifier = Modifier.weight(1f)
                            ) {
                                Icon(Icons.Outlined.Message, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Message", fontSize = 13.sp)
                            }
                        }

                        // Professional Dashboard / Manager Shortcut Button if owner/admin
                        if (page.is_owner || page.is_admin) {
                            Button(
                                onClick = { onNavigateToDashboard(page.id) },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF0F172A)),
                                shape = RoundedCornerShape(12.dp),
                                modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp)
                            ) {
                                Icon(Icons.Default.Dashboard, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Professional Dashboard", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                            }
                        }
                    }
                }

                // Page Navigation Tabs Bar
                item {
                    val tabs = listOf("Home", "About", "Reviews", "Products", "Services", "Media")
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState())
                            .padding(horizontal = 16.dp, vertical = 6.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        tabs.forEachIndexed { index, tabName ->
                            FilterChip(
                                selected = state.selectedTab == index,
                                onClick = { viewModel.handleIntent(PageProfileIntent.SelectTab(index)) },
                                label = { Text(tabName, fontSize = 12.sp, fontWeight = FontWeight.SemiBold) }
                            )
                        }
                    }
                    HorizontalDivider(modifier = Modifier.padding(bottom = 8.dp))
                }

                // Tab Specific Contents
                when (state.selectedTab) {
                    0 -> { // Home / Posts Tab
                        item {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text("Page About & Details", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    page.description ?: page.bio ?: "Welcome to ${page.name}! Follow for updates, announcements, and business insights.",
                                    fontSize = 13.sp,
                                    color = TextSecondaryLight
                                )
                                if (!page.website.isNullOrBlank()) {
                                    Spacer(modifier = Modifier.height(6.dp))
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Icon(Icons.Outlined.Language, contentDescription = null, tint = ConnectlyElectricBlue, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Text(page.website!!, fontSize = 12.sp, color = ConnectlyElectricBlue)
                                    }
                                }
                            }
                        }
                    }
                    1 -> { // About Tab
                        item {
                            AboutTabSection(page = page)
                        }
                    }
                    2 -> { // Reviews Tab
                        item {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp, vertical = 8.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Page Reviews (${state.reviews.size})", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                                Button(
                                    onClick = { showWriteReviewDialog = true },
                                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                                ) {
                                    Text("Write Review", fontSize = 12.sp)
                                }
                            }
                        }
                        if (state.reviews.isEmpty()) {
                            item {
                                Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                    Text("No reviews yet. Be the first to leave a review!", color = TextSecondaryLight)
                                }
                            }
                        } else {
                            items(state.reviews, key = { it.id }) { review ->
                                ReviewCard(review = review)
                            }
                        }
                    }
                    3 -> { // Products Tab
                        if (state.products.isEmpty()) {
                            item {
                                Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                    Text("No products added to catalog.", color = TextSecondaryLight)
                                }
                            }
                        } else {
                            items(state.products, key = { it.id }) { product ->
                                Card(
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                    modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 6.dp)
                                ) {
                                    Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                                        AsyncImage(
                                            model = product.image_url,
                                            contentDescription = product.name,
                                            modifier = Modifier.size(60.dp).clip(RoundedCornerShape(8.dp)),
                                            contentScale = ContentScale.Crop
                                        )
                                        Spacer(modifier = Modifier.width(12.dp))
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(product.name, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                            Text(product.description, fontSize = 11.sp, color = TextSecondaryLight, maxLines = 1)
                                            Text("$${product.price}", fontWeight = FontWeight.Bold, color = ConnectlyElectricBlue, fontSize = 13.sp)
                                        }
                                    }
                                }
                            }
                        }
                    }
                    4 -> { // Services Tab
                        if (state.services.isEmpty()) {
                            item {
                                Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                    Text("No services listed yet.", color = TextSecondaryLight)
                                }
                            }
                        } else {
                            items(state.services, key = { it.id }) { service ->
                                Card(
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                    modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 6.dp)
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Text(service.name, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                        Text(service.description, fontSize = 12.sp, color = TextSecondaryLight, modifier = Modifier.padding(vertical = 4.dp))
                                        Text(service.price_info, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = ConnectlyElectricBlue)
                                    }
                                }
                            }
                        }
                    }
                    5 -> { // Media / Photos Tab
                        item {
                            Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                Text("No media uploaded yet.", color = TextSecondaryLight)
                            }
                        }
                    }
                    else -> {
                        item {
                            Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                Text("No content in this section yet.", color = TextSecondaryLight)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StatCard(label: String, value: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
        Text(label, fontSize = 11.sp, color = TextSecondaryLight)
    }
}

@Composable
private fun AboutTabSection(page: com.connectly.android.core.network.api.PageDto) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Text("Company & Contact Info", fontWeight = FontWeight.Bold, fontSize = 16.sp)

        InfoItem(Icons.Outlined.Business, "Category", page.category)
        if (!page.email.isNullOrBlank()) InfoItem(Icons.Outlined.Email, "Business Email", page.email!!)
        if (!page.phone.isNullOrBlank()) InfoItem(Icons.Outlined.Phone, "Phone Number", page.phone!!)
        if (!page.website.isNullOrBlank()) InfoItem(Icons.Outlined.Language, "Website", page.website!!)
        if (!page.address.isNullOrBlank()) InfoItem(Icons.Outlined.LocationOn, "Address", page.address!!)
        if (!page.opening_hours.isNullOrBlank()) InfoItem(Icons.Outlined.Schedule, "Hours", page.opening_hours!!)
    }
}

@Composable
private fun InfoItem(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(icon, contentDescription = null, tint = ConnectlyElectricBlue, modifier = Modifier.size(20.dp))
        Spacer(modifier = Modifier.width(10.dp))
        Column {
            Text(label, fontSize = 11.sp, color = TextSecondaryLight)
            Text(value, fontSize = 13.sp, fontWeight = FontWeight.Medium, color = TextPrimaryLight)
        }
    }
}

@Composable
private fun ReviewCard(review: com.connectly.android.core.network.api.PageReviewDto) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 6.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(review.author_name, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                Row {
                    (1..review.rating).forEach { _ ->
                        Icon(Icons.Default.Star, contentDescription = null, tint = ConnectlyAmber, modifier = Modifier.size(14.dp))
                    }
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(review.review_text, fontSize = 12.sp, color = TextPrimaryLight)
            Spacer(modifier = Modifier.height(4.dp))
            Text(review.created_at ?: "Recently", fontSize = 10.sp, color = TextSecondaryLight)
        }
    }
}
