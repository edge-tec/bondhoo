package com.connectly.android.feature.groups.presentation.pages

import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PagesScreen(
    onNavigateBack: () -> Unit = {},
    onPageClick: (Long) -> Unit = {},
    onCreatePage: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    var selectedTab by remember { mutableStateOf("discover") }
    var followedPages by remember { mutableStateOf(setOf(1L)) }

    val samplePages = remember {
        listOf(
            PageItem(
                id = 1L,
                name = "Tech World BD",
                category = "Technology & Media",
                avatarUrl = "https://images.unsplash.com/photo-1518770660439-4636190af475",
                coverUrl = "https://images.unsplash.com/photo-1519389950473-47ba0277781c",
                followersCount = 186000,
                rating = 4.9,
                isVerified = true,
                about = "Your daily dose of tech trends, gadget reviews, and programming insights."
            ),
            PageItem(
                id = 2L,
                name = "Foodies BD Official",
                category = "Food & Restaurant",
                avatarUrl = "https://images.unsplash.com/photo-1555396273-367ea4eb4db5",
                coverUrl = "https://images.unsplash.com/photo-1504674900247-0877df9cc836",
                followersCount = 312000,
                rating = 4.8,
                isVerified = true,
                about = "Discover the best culinary spots, street food, and authentic recipes in Bangladesh."
            ),
            PageItem(
                id = 3L,
                name = "Dhaka Living & Lifestyle",
                category = "Community & Lifestyle",
                avatarUrl = "https://images.unsplash.com/photo-1513694203232-719a280e022f",
                coverUrl = "https://images.unsplash.com/photo-1477959858617-67f30bc75b82",
                followersCount = 94000,
                rating = 4.7,
                isVerified = false,
                about = "Urban lifestyle, cultural events, and living tips in the heart of Dhaka."
            ),
            PageItem(
                id = 4L,
                name = "Business Growth Accelerator",
                category = "Business & Entrepreneurship",
                avatarUrl = "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab",
                coverUrl = "https://images.unsplash.com/photo-1497366216548-37526070297c",
                followersCount = 175000,
                rating = 4.9,
                isVerified = true,
                about = "Insights, mentorship, and funding opportunities for ambitious startups."
            )
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Business Pages",
                        fontWeight = FontWeight.Bold,
                        fontSize = 22.sp,
                        color = TextPrimaryLight
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { /* Search */ }) {
                        Icon(Icons.Default.Search, contentDescription = "Search Pages")
                    }
                    IconButton(onClick = onCreatePage) {
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
                    PageFilterPill(
                        label = "Discover",
                        isSelected = selectedTab == "discover",
                        onClick = { selectedTab = "discover" }
                    )
                    PageFilterPill(
                        label = "Following",
                        badge = followedPages.size.toString(),
                        isSelected = selectedTab == "following",
                        onClick = { selectedTab = "following" }
                    )
                    PageFilterPill(
                        label = "Your Pages",
                        isSelected = selectedTab == "your_pages",
                        onClick = { selectedTab = "your_pages" }
                    )
                }
            }

            // Featured Business Page Banner (Discover mode)
            if (selectedTab == "discover") {
                item {
                    val featured = samplePages.first()
                    val isFollowed = followedPages.contains(featured.id)

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
                                        followedPages = if (isFollowed) followedPages - featured.id else followedPages + featured.id
                                    },
                                    colors = ButtonDefaults.buttonColors(
                                        containerColor = if (isFollowed) Color.White.copy(alpha = 0.25f) else ConnectlyElectricBlue,
                                        contentColor = Color.White
                                    ),
                                    shape = RoundedCornerShape(20.dp),
                                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                                    modifier = Modifier.height(34.dp)
                                ) {
                                    if (isFollowed) {
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

            // Pages List
            val displayedPages = when (selectedTab) {
                "following" -> samplePages.filter { followedPages.contains(it.id) }
                "your_pages" -> emptyList()
                else -> samplePages
            }

            items(displayedPages, key = { it.id }) { page ->
                val isFollowed = followedPages.contains(page.id)
                BusinessPageCard(
                    page = page,
                    isFollowed = isFollowed,
                    onFollowToggle = {
                        followedPages = if (isFollowed) followedPages - page.id else followedPages + page.id
                    },
                    onClick = { onPageClick(page.id) },
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 6.dp)
                )
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

