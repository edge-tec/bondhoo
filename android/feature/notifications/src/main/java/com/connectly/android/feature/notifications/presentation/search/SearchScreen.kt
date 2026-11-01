package com.connectly.android.feature.notifications.presentation.search

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.PersonAdd
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
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.User

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: SearchViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    OutlinedTextField(
                        value = state.query,
                        onValueChange = { viewModel.handleIntent(SearchIntent.QueryChanged(it)) },
                        placeholder = { Text("Search Connectly...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight) },
                        trailingIcon = {
                            if (state.query.isNotEmpty()) {
                                IconButton(onClick = { viewModel.handleIntent(SearchIntent.QueryChanged("")) }) {
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
                    val isSelected = filter == state.selectedFilter
                    Surface(
                        color = if (isSelected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier.clickable { viewModel.handleIntent(SearchIntent.FilterSelected(filter)) }
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
                if (state.query.isEmpty()) {
                    // Suggested Friends Carousel
                    if (state.discoveryData.suggestedFriends.isNotEmpty()) {
                        item {
                            Text(
                                "Suggested Friends",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                            LazyRow(
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(state.discoveryData.suggestedFriends, key = { it.id }) { user ->
                                    SuggestedFriendCard(user)
                                }
                            }
                        }
                    }

                    // Recommended Communities
                    if (state.discoveryData.recommendedGroups.isNotEmpty()) {
                        item {
                            Text(
                                "Recommended Communities",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                modifier = Modifier.padding(top = 8.dp, bottom = 8.dp)
                            )
                            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                state.discoveryData.recommendedGroups.forEach { group ->
                                    RecommendedGroupRow(group)
                                }
                            }
                        }
                    }

                    // Recent Searches
                    if (state.recentSearches.isNotEmpty()) {
                        item {
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Recent Searches", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                                Text(
                                    "Clear all",
                                    color = ConnectlyBlue,
                                    fontSize = 13.sp,
                                    modifier = Modifier.clickable { viewModel.handleIntent(SearchIntent.ClearRecentSearches) }
                                )
                            }
                        }

                        items(state.recentSearches) { searchItem ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { viewModel.handleIntent(SearchIntent.QueryChanged(searchItem)) }
                                    .padding(vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight)
                                Spacer(modifier = Modifier.width(12.dp))
                                Text(searchItem, fontSize = 14.sp, color = TextPrimaryLight, modifier = Modifier.weight(1f))
                                IconButton(onClick = { viewModel.handleIntent(SearchIntent.RemoveRecentItem(searchItem)) }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Remove", tint = TextSecondaryLight, modifier = Modifier.size(16.dp))
                                }
                            }
                        }
                    }

                    // Trending Topics & Hashtags
                    item {
                        Text(
                            "Trending Topics",
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp,
                            modifier = Modifier.padding(top = 8.dp, bottom = 8.dp)
                        )
                    }

                    items(state.discoveryData.trendingTopics) { hashtag ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { viewModel.handleIntent(SearchIntent.QueryChanged(hashtag)) }
                                .padding(vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(text = "🔥", fontSize = 16.sp)
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(hashtag, color = ConnectlyBlue, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        }
                    }
                } else {
                    // Search Query Results
                    item {
                        Text("Results for \"${state.query}\"", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    }

                    items(state.searchResults.users, key = { it.id }) { user ->
                        SearchResultRow(
                            title = user.name,
                            subtitle = "@${user.username}",
                            avatar = user.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330"
                        )
                    }

                    items(state.searchResults.groups, key = { it.id }) { group ->
                        SearchResultRow(
                            title = group.name,
                            subtitle = "${group.membersCount} members • Group",
                            avatar = group.coverImage ?: "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b"
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun SuggestedFriendCard(user: User) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(2.dp),
        modifier = Modifier.width(130.dp)
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(12.dp)
        ) {
            AsyncImage(
                model = user.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                contentDescription = user.name,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .size(54.dp)
                    .clip(CircleShape)
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(user.name, fontWeight = FontWeight.Bold, fontSize = 13.sp, maxLines = 1)
            Spacer(modifier = Modifier.height(8.dp))
            Button(
                onClick = { /* Add friend */ },
                shape = RoundedCornerShape(8.dp),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp),
                modifier = Modifier.height(28.dp)
            ) {
                Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(12.dp))
                Spacer(modifier = Modifier.width(4.dp))
                Text("Add", fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

@Composable
private fun RecommendedGroupRow(group: Group) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        AsyncImage(
            model = group.coverImage ?: "https://images.unsplash.com/photo-1511578314322-379afb476865",
            contentDescription = group.name,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .size(44.dp)
                .clip(RoundedCornerShape(8.dp))
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(group.name, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Text("${group.membersCount} members", fontSize = 12.sp, color = TextSecondaryLight)
        }
        OutlinedButton(
            onClick = { /* Join */ },
            shape = RoundedCornerShape(8.dp),
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
            modifier = Modifier.height(30.dp)
        ) {
            Text("Join", fontSize = 12.sp, color = ConnectlyBlue, fontWeight = FontWeight.SemiBold)
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
