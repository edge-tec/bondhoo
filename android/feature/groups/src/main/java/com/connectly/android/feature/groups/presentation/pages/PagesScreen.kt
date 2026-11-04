package com.connectly.android.feature.groups.presentation.pages

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PagesScreen(
    onNavigateBack: () -> Unit = {},
    onPageClick: (Long) -> Unit = {},
    onCreatePage: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PagesViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    var newPageName by remember { mutableStateOf("") }
    var newPageUsername by remember { mutableStateOf("") }
    var newPageCategory by remember { mutableStateOf("Business") }
    var newPageWebsite by remember { mutableStateOf("") }
    var newPageEmail by remember { mutableStateOf("") }
    var newPagePhone by remember { mutableStateOf("") }
    var newPageBio by remember { mutableStateOf("") }

    if (state.showCreateDialog) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(PagesIntent.ShowCreateDialog(false)) },
            title = { Text("Create Professional Page", fontWeight = FontWeight.Bold) },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    OutlinedTextField(
                        value = newPageName,
                        onValueChange = { newPageName = it },
                        label = { Text("Page Name *") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = newPageUsername,
                        onValueChange = { newPageUsername = it },
                        label = { Text("Username / Custom URL") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = newPageCategory,
                        onValueChange = { newPageCategory = it },
                        label = { Text("Category") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = newPageWebsite,
                        onValueChange = { newPageWebsite = it },
                        label = { Text("Website") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        OutlinedTextField(
                            value = newPageEmail,
                            onValueChange = { newPageEmail = it },
                            label = { Text("Business Email") },
                            singleLine = true,
                            modifier = Modifier.weight(1f)
                        )
                        OutlinedTextField(
                            value = newPagePhone,
                            onValueChange = { newPagePhone = it },
                            label = { Text("Phone") },
                            singleLine = true,
                            modifier = Modifier.weight(1f)
                        )
                    }
                    OutlinedTextField(
                        value = newPageBio,
                        onValueChange = { newPageBio = it },
                        label = { Text("Description & Bio") },
                        maxLines = 2,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (newPageName.isNotBlank()) {
                            viewModel.handleIntent(
                                PagesIntent.CreatePageDetailed(
                                    com.connectly.android.core.network.api.CreatePageRequest(
                                        name = newPageName,
                                        username = newPageUsername.ifBlank { null },
                                        category = newPageCategory,
                                        website = newPageWebsite.ifBlank { null },
                                        email = newPageEmail.ifBlank { null },
                                        phone = newPagePhone.ifBlank { null },
                                        bio = newPageBio,
                                        description = newPageBio
                                    )
                                )
                            )
                            newPageName = ""
                            newPageUsername = ""
                            newPageWebsite = ""
                            newPageEmail = ""
                            newPagePhone = ""
                            newPageBio = ""
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                ) {
                    Text("Create Page")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(PagesIntent.ShowCreateDialog(false)) }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    if (state.isSearchVisible) {
                        OutlinedTextField(
                            value = state.searchQuery,
                            onValueChange = { viewModel.handleIntent(PagesIntent.SearchQueryChanged(it)) },
                            placeholder = { Text("Search pages...", fontSize = 14.sp) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth().height(50.dp),
                            shape = RoundedCornerShape(25.dp),
                            trailingIcon = {
                                IconButton(onClick = { viewModel.handleIntent(PagesIntent.ToggleSearch) }) {
                                    Icon(Icons.Default.Close, contentDescription = "Close Search")
                                }
                            }
                        )
                    } else {
                        Text(
                            text = "Business Pages",
                            fontWeight = FontWeight.Bold,
                            fontSize = 22.sp,
                            color = TextPrimaryLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (!state.isSearchVisible) {
                        IconButton(onClick = { viewModel.handleIntent(PagesIntent.ToggleSearch) }) {
                            Icon(Icons.Default.Search, contentDescription = "Search Pages")
                        }
                    }
                    IconButton(onClick = { viewModel.handleIntent(PagesIntent.ShowCreateDialog(true)) }) {
                        Icon(Icons.Default.Add, contentDescription = "Create Page")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(BackgroundLight)
        ) {
            // Segmented Filter Tabs
            item {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val followedCount = state.pages.count { it.isFollowed }
                    PageFilterPill(
                        label = "Discover",
                        isSelected = state.selectedTab == "discover",
                        onClick = { viewModel.handleIntent(PagesIntent.SelectTab("discover")) }
                    )
                    PageFilterPill(
                        label = "Following",
                        badge = if (followedCount > 0) followedCount.toString() else null,
                        isSelected = state.selectedTab == "following",
                        onClick = { viewModel.handleIntent(PagesIntent.SelectTab("following")) }
                    )
                    PageFilterPill(
                        label = "Your Pages",
                        isSelected = state.selectedTab == "your_pages",
                        onClick = { viewModel.handleIntent(PagesIntent.SelectTab("your_pages")) }
                    )
                }
            }

            // Featured Business Page Banner (Discover mode)
            if (state.selectedTab == "discover" && state.searchQuery.isBlank()) {
                item {
                    state.featuredPage?.let { featured ->
                        Card(
                            shape = RoundedCornerShape(16.dp),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp, vertical = 6.dp)
                                .clickable { onPageClick(featured.id) },
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                        ) {
                            Box(modifier = Modifier.fillMaxWidth().height(160.dp)) {
                                AsyncImage(
                                    model = featured.coverUrl,
                                    contentDescription = featured.name,
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier.fillMaxSize()
                                )
                                Box(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .background(
                                            Brush.verticalGradient(
                                                listOf(Color.Transparent, Color.Black.copy(alpha = 0.85f))
                                            )
                                        )
                                )
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .align(Alignment.BottomStart)
                                        .padding(14.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            text = featured.name,
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 17.sp,
                                            color = Color.White
                                        )
                                        Text(
                                            text = "${featured.category} • ${featured.followersCount / 1000}K followers",
                                            fontSize = 12.sp,
                                            color = Color.White.copy(alpha = 0.85f)
                                        )
                                    }

                                    Button(
                                        onClick = {
                                            viewModel.handleIntent(PagesIntent.ToggleFollowPage(featured.id))
                                        },
                                        colors = ButtonDefaults.buttonColors(
                                            containerColor = if (featured.isFollowed) Color.White.copy(alpha = 0.25f) else ConnectlyElectricBlue,
                                            contentColor = Color.White
                                        ),
                                        shape = RoundedCornerShape(20.dp),
                                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                                        modifier = Modifier.height(34.dp)
                                    ) {
                                        if (featured.isFollowed) {
                                            Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(14.dp))
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text("Following", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                        } else {
                                            Text("Follow", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Error Banner
            if (state.errorMessage != null) {
                item {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 6.dp)
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = state.errorMessage ?: "Unable to connect to server",
                                color = MaterialTheme.colorScheme.onErrorContainer,
                                fontSize = 12.sp,
                                modifier = Modifier.weight(1f)
                            )
                            TextButton(onClick = { viewModel.handleIntent(PagesIntent.Refresh) }) {
                                Text("Retry", color = MaterialTheme.colorScheme.onErrorContainer, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }

            // Pages List
            val displayedPages = state.pages.filter { page ->
                val matchesTab = when (state.selectedTab) {
                    "following" -> page.isFollowed
                    "your_pages" -> page.isOwner
                    else -> true
                }
                val matchesQuery = if (state.searchQuery.isNotBlank()) {
                    page.name.contains(state.searchQuery, ignoreCase = true) ||
                        page.category.contains(state.searchQuery, ignoreCase = true)
                } else true
                matchesTab && matchesQuery
            }

            if (displayedPages.isEmpty() && !state.isLoading && state.errorMessage == null) {
                item {
                    when (state.selectedTab) {
                        "following" -> {
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp, vertical = 24.dp),
                                shape = RoundedCornerShape(16.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                            ) {
                                Column(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(24.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    Icon(
                                        Icons.Default.Star,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(48.dp)
                                    )
                                    Text(
                                        text = "You're not following any pages yet",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp,
                                        color = TextPrimaryLight
                                    )
                                    Text(
                                        text = "Follow businesses, creators, and official organisations to see their latest updates in your feed.",
                                        fontSize = 13.sp,
                                        color = TextSecondaryLight,
                                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Button(
                                        onClick = { viewModel.handleIntent(PagesIntent.SelectTab("discover")) },
                                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Text("Discover Pages")
                                    }
                                }
                            }
                        }
                        "your_pages" -> {
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp, vertical = 24.dp),
                                shape = RoundedCornerShape(16.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                            ) {
                                Column(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(24.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    Icon(
                                        Icons.Default.Add,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(48.dp)
                                    )
                                    Text(
                                        text = "You haven't created any pages yet",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp,
                                        color = TextPrimaryLight
                                    )
                                    Text(
                                        text = "Create a page for your business, brand, or project to connect with customers and share updates.",
                                        fontSize = 13.sp,
                                        color = TextSecondaryLight,
                                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Button(
                                        onClick = { viewModel.handleIntent(PagesIntent.ShowCreateDialog(true)) },
                                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Text("Create Page")
                                    }
                                }
                            }
                        }
                        else -> {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("No pages found.", color = TextSecondaryLight, fontSize = 14.sp)
                            }
                        }
                    }
                }
            } else {
                items(displayedPages, key = { it.id }) { page ->
                    BusinessPageCard(
                        page = page,
                        isFollowed = page.isFollowed,
                        onFollowToggle = {
                            viewModel.handleIntent(PagesIntent.ToggleFollowPage(page.id))
                        },
                        onClick = { onPageClick(page.id) },
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 6.dp)
                    )
                }
            }
        }
    }
}

@Composable
private fun PageFilterPill(
    label: String,
    badge: String? = null,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(20.dp),
        color = if (isSelected) ConnectlyElectricBlue else SurfaceLight,
        modifier = Modifier.height(36.dp)
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.padding(horizontal = 14.dp)
        ) {
            Text(
                text = label,
                color = if (isSelected) Color.White else TextPrimaryLight,
                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                fontSize = 13.sp
            )
            if (badge != null) {
                Spacer(modifier = Modifier.width(6.dp))
                Box(
                    modifier = Modifier
                        .size(18.dp)
                        .clip(CircleShape)
                        .background(if (isSelected) Color.White else ConnectlyElectricBlue),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = badge,
                        color = if (isSelected) ConnectlyElectricBlue else Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
        }
    }
}

@Composable
private fun BusinessPageCard(
    page: PageItem,
    isFollowed: Boolean,
    onFollowToggle: () -> Unit,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            ConnectlyAvatar(
                imageUrl = page.avatarUrl,
                name = page.name,
                size = 52.dp,
                isVerified = page.isVerified
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = page.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = TextPrimaryLight
                )
                Text(
                    text = page.category,
                    fontSize = 12.sp,
                    color = TextSecondaryLight
                )
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(4.dp),
                    modifier = Modifier.padding(top = 2.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Star,
                        contentDescription = "Rating",
                        tint = ConnectlyAmber,
                        modifier = Modifier.size(13.dp)
                    )
                    Text(
                        text = "${page.rating} • ${page.followersCount / 1000}K followers",
                        fontSize = 11.sp,
                        color = TextSecondaryLight
                    )
                }
            }

            Button(
                onClick = onFollowToggle,
                colors = ButtonDefaults.buttonColors(
                    containerColor = if (isFollowed) SurfaceSubtle else ConnectlyElectricBlue,
                    contentColor = if (isFollowed) TextPrimaryLight else Color.White
                ),
                shape = RoundedCornerShape(18.dp),
                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
                modifier = Modifier.height(34.dp)
            ) {
                Text(
                    text = if (isFollowed) "Following" else "Follow",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }
    }
}
