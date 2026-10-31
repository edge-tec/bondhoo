package com.connectly.android.feature.notifications.presentation.search

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    var searchQuery by remember { mutableStateOf("") }
    var selectedFilter by remember { mutableStateOf("All") }

    val recentSearches = remember {
        mutableStateListOf("Sadia Islam", "#MusicFestival", "Travel Lovers BD", "Tech World")
    }

    val trendingHashtags = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD", "#StartupDhaka")

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        placeholder = { Text("Search Connectly...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight) },
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { searchQuery = "" }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear")
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(24.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            unfocusedBorderColor = Color.Transparent,
                            focusedBorderColor = ConnectlyBlue
                        ),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(48.dp)
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Filter Pills (All, People, Posts, Groups, Events)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf("All", "People", "Posts", "Groups", "Events").forEach { filter ->
                    val isSelected = filter == selectedFilter
                    Surface(
                        color = if (isSelected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier.clickable { selectedFilter = filter }
                    ) {
                        Text(
                            text = filter,
                            color = if (isSelected) Color.White else TextPrimaryLight,
                            fontSize = 12.sp,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                            modifier = Modifier.padding(horizontal = 14.dp, vertical = 6.dp)
                        )
                    }
                }
            }

            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Recent Searches
                if (searchQuery.isEmpty() && recentSearches.isNotEmpty()) {
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Recent Searches", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text(
                                "Clear all",
                                color = ConnectlyBlue,
                                fontSize = 13.sp,
                                modifier = Modifier.clickable { recentSearches.clear() }
                            )
                        }
                    }

                    items(recentSearches) { searchItem ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { searchQuery = searchItem }
                                .padding(vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight)
                            Spacer(modifier = Modifier.width(12.dp))
                            Text(searchItem, fontSize = 14.sp, color = TextPrimaryLight, modifier = Modifier.weight(1f))
                            IconButton(onClick = { recentSearches.remove(searchItem) }) {
                                Icon(Icons.Default.Clear, contentDescription = "Remove", tint = TextSecondaryLight, modifier = Modifier.size(16.dp))
                            }
                        }
                    }

                    // Trending Hashtags
                    item {
                        Text(
                            "Trending Topics",
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp,
                            modifier = Modifier.padding(top = 16.dp, bottom = 8.dp)
                        )
                    }

                    items(trendingHashtags) { hashtag ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { searchQuery = hashtag }
                                .padding(vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(text = "🔥", fontSize = 16.sp)
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(hashtag, color = ConnectlyBlue, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        }
                    }
                }

                // Instant Search Results
                if (searchQuery.isNotEmpty()) {
                    item {
                        Text("Search Results for \"$searchQuery\"", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    }

                    item {
                        SearchResultRow(
                            title = "Sadia Islam",
                            subtitle = "@sadia_islam • Active now",
                            avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330"
                        )
                    }
                    item {
                        SearchResultRow(
                            title = "Travel Lovers BD",
                            subtitle = "248K members • Group",
                            avatar = "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b"
                        )
                    }
                    item {
                        SearchResultRow(
                            title = "Tech World",
                            subtitle = "156K members • Group",
                            avatar = "https://images.unsplash.com/photo-1511578314322-379afb476865"
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun SearchResultRow(
    title: String,
    subtitle: String,
    avatar: String,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        AsyncImage(
            model = avatar,
            contentDescription = title,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .size(44.dp)
                .clip(CircleShape)
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = TextPrimaryLight)
            Text(subtitle, fontSize = 12.sp, color = TextSecondaryLight)
        }
    }
}
