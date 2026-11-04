package com.connectly.android.feature.notifications.presentation.search

import androidx.compose.foundation.background
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
import androidx.compose.material.icons.filled.SearchOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(
    onNavigateBack: () -> Unit,
    onUserClick: (Long) -> Unit = {},
    onGroupClick: (Long) -> Unit = {},
    onPageClick: (Long) -> Unit = {},
    onEventClick: (Long) -> Unit = {},
    onPostClick: (Long) -> Unit = {},
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
            // Filter Pills (All, People, Posts, Groups, Pages, Events)
            LazyRow(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(listOf("All", "People", "Posts", "Groups", "Pages", "Events")) { filter ->
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

            if (state.isLoading) {
                LinearProgressIndicator(
                    modifier = Modifier.fillMaxWidth(),
                    color = ConnectlyBlue
                )
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
                                    SuggestedFriendCard(
                                        user = user,
                                        onClick = { onUserClick(user.id) }
                                    )
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
                                    RecommendedGroupRow(
                                        group = group,
                                        onClick = { onGroupClick(group.id) }
                                    )
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
                    val hasResults = state.searchResults.users.isNotEmpty() ||
                        state.searchResults.groups.isNotEmpty() ||
                        state.searchResults.pages.isNotEmpty() ||
                        state.searchResults.posts.isNotEmpty() ||
                        state.searchResults.events.isNotEmpty()

                    if (hasResults) {
                        item {
                            Text("Results for \"${state.query}\"", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        }

                        if (state.selectedFilter in listOf("All", "People") && state.searchResults.users.isNotEmpty()) {
                            item {
                                Text("People", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = ConnectlyBlue, modifier = Modifier.padding(top = 8.dp))
                            }
                            items(state.searchResults.users, key = { "user_${it.id}" }) { user ->
                                SearchResultRow(
                                    title = user.name,
                                    subtitle = "@${user.username}${if (!user.bio.isNullOrBlank()) " • ${user.bio}" else ""}",
                                    avatar = user.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                                    onClick = { onUserClick(user.id) }
                                )
                            }
                        }

                        if (state.selectedFilter in listOf("All", "Groups") && state.searchResults.groups.isNotEmpty()) {
                            item {
                                Text("Groups", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = ConnectlyBlue, modifier = Modifier.padding(top = 8.dp))
                            }
                            items(state.searchResults.groups, key = { "group_${it.id}" }) { group ->
                                SearchResultRow(
                                    title = group.name,
                                    subtitle = "${group.membersCount} members • Group",
                                    avatar = group.coverImage ?: "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b",
                                    onClick = { onGroupClick(group.id) }
                                )
                            }
                        }

                        if (state.selectedFilter in listOf("All", "Pages") && state.searchResults.pages.isNotEmpty()) {
                            item {
                                Text("Pages", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = ConnectlyBlue, modifier = Modifier.padding(top = 8.dp))
                            }
                            items(state.searchResults.pages, key = { "page_${it.id}" }) { page ->
                                SearchResultRow(
                                    title = page.name,
                                    subtitle = "${page.category} • Page",
                                    avatar = page.avatarUrl ?: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3",
                                    onClick = { onPageClick(page.id) }
                                )
                            }
                        }

                        if (state.selectedFilter in listOf("All", "Posts") && state.searchResults.posts.isNotEmpty()) {
                            item {
                                Text("Posts", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = ConnectlyBlue, modifier = Modifier.padding(top = 8.dp))
                            }
                            items(state.searchResults.posts, key = { "post_${it.id}" }) { post ->
                                SearchResultRow(
                                    title = post.author.name,
                                    subtitle = post.content.take(60) + if (post.content.length > 60) "..." else "",
                                    avatar = post.author.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                                    onClick = { onPostClick(post.id) }
                                )
                            }
                        }

                        if (state.selectedFilter in listOf("All", "Events") && state.searchResults.events.isNotEmpty()) {
                            item {
                                Text("Events", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = ConnectlyBlue, modifier = Modifier.padding(top = 8.dp))
                            }
                            items(state.searchResults.events, key = { "event_${it.id}" }) { event ->
                                SearchResultRow(
                                    title = event.title,
                                    subtitle = "${event.location} • Event",
                                    avatar = event.bannerUrl ?: "https://images.unsplash.com/photo-1511578314322-379afb476865",
                                    onClick = { onEventClick(event.id) }
                                )
                            }
                        }
                    } else if (!state.isLoading) {
                        // Empty Search State
                        item {
                            Column(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 48.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(64.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyBlue.copy(alpha = 0.1f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.SearchOff,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(32.dp)
                                    )
                                }
                                Text(
                                    text = "No results found for \"${state.query}\"",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 17.sp,
                                    color = TextPrimaryLight
                                )
                                Text(
                                    text = "Check the spelling or try searching for people, posts, or groups with other keywords.",
                                    fontSize = 13.sp,
                                    color = TextSecondaryLight,
                                    textAlign = TextAlign.Center
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
private fun SuggestedFriendCard(
    user: User,
    onClick: () -> Unit = {}
) {
    var isRequested by remember { mutableStateOf(false) }

    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(2.dp),
        modifier = Modifier
            .width(130.dp)
            .clickable(onClick = onClick)
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
                onClick = { isRequested = !isRequested },
                shape = RoundedCornerShape(8.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = if (isRequested) Color(0xFFF1F5F9) else ConnectlyBlue,
                    contentColor = if (isRequested) TextPrimaryLight else Color.White
                ),
                contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp),
                modifier = Modifier.height(28.dp)
            ) {
                if (isRequested) {
                    Text("Requested", fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
                } else {
                    Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(12.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Add", fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
                }
            }
        }
    }
}

@Composable
private fun RecommendedGroupRow(
    group: Group,
    onClick: () -> Unit = {}
) {
    var isJoined by remember { mutableStateOf(false) }

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
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
            onClick = { isJoined = !isJoined },
            shape = RoundedCornerShape(8.dp),
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
            modifier = Modifier.height(30.dp)
        ) {
            Text(
                text = if (isJoined) "Joined" else "Join",
                fontSize = 12.sp,
                color = if (isJoined) TextSecondaryLight else ConnectlyBlue,
                fontWeight = FontWeight.SemiBold
            )
        }
    }
}

@Composable
private fun SearchResultRow(
    title: String,
    subtitle: String,
    avatar: String,
    onClick: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
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
