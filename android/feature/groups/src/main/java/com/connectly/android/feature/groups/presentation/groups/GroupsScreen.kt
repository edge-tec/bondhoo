package com.connectly.android.feature.groups.presentation.groups

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
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
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.Group

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupsScreen(
    onNavigateToGroupDetails: (Long) -> Unit = {},
    onCreateGroup: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: GroupsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Groups",
                        fontWeight = FontWeight.Bold,
                        fontSize = 24.sp,
                        color = TextPrimaryLight
                    )
                },
                actions = {
                    IconButton(onClick = { /* Search */ }) {
                        Icon(Icons.Default.Search, contentDescription = "Search Groups", tint = TextPrimaryLight)
                    }
                    IconButton(onClick = onCreateGroup) {
                        Icon(Icons.Default.Add, contentDescription = "Create Group", tint = TextPrimaryLight)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Segmented Filter Tabs: Discover, Your Groups, Manage (Matching Screen 4)
            item(span = { GridItemSpan(2) }) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    SegmentedTabButton(
                        label = "Discover",
                        isSelected = state.selectedTab == "discover",
                        onClick = { viewModel.handleIntent(GroupsIntent.SelectTab("discover")) }
                    )
                    SegmentedTabButton(
                        label = "Your Groups",
                        isSelected = state.selectedTab == "your_groups",
                        onClick = { viewModel.handleIntent(GroupsIntent.SelectTab("your_groups")) }
                    )
                    SegmentedTabButton(
                        label = "Manage",
                        isSelected = state.selectedTab == "manage",
                        onClick = { viewModel.handleIntent(GroupsIntent.SelectTab("manage")) }
                    )
                }
            }

            // Featured Hero Group Card: Travel Lovers BD (Matching Screen 4)
            item(span = { GridItemSpan(2) }) {
                state.featuredGroup?.let { featured ->
                    FeaturedGroupHeroCard(
                        group = featured,
                        onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(featured)) },
                        onClick = { onNavigateToGroupDetails(featured.id) }
                    )
                }
            }

            // Section Header: "Popular Groups" & "See all >"
            item(span = { GridItemSpan(2) }) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 16.dp, bottom = 4.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Popular Groups",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp,
                        color = TextPrimaryLight
                    )
                    Text(
                        text = "See all >",
                        color = ConnectlyBlue,
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 14.sp,
                        modifier = Modifier.clickable { /* See all */ }
                    )
                }
            }

            // 2-Column Popular Groups Grid (Tech World, Foodies BD, Dhaka Living, Business Growth)
            items(state.popularGroups, key = { it.id }) { group ->
                PopularGroupCard(
                    group = group,
                    onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(group)) },
                    onClick = { onNavigateToGroupDetails(group.id) }
                )
            }

            item(span = { GridItemSpan(2) }) {
                Spacer(modifier = Modifier.height(24.dp))
            }
        }
    }
}

@Composable
private fun SegmentedTabButton(
    label: String,
    isSelected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Button(
        onClick = onClick,
        colors = ButtonDefaults.buttonColors(
            containerColor = if (isSelected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
            contentColor = if (isSelected) Color.White else TextPrimaryLight
        ),
        shape = RoundedCornerShape(20.dp),
        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
        modifier = modifier
    ) {
        Text(text = label, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium, fontSize = 13.sp)
    }
}

@Composable
private fun FeaturedGroupHeroCard(
    group: Group,
    onToggleJoin: () -> Unit,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .height(210.dp)
            .clip(RoundedCornerShape(16.dp))
            .clickable(onClick = onClick)
    ) {
        AsyncImage(
            model = group.coverImage,
            contentDescription = group.name,
            contentScale = ContentScale.Crop,
            modifier = Modifier.fillMaxSize()
        )

        // Gradient overlay
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        colors = listOf(
                            Color.Transparent,
                            Color.Black.copy(alpha = 0.85f)
                        )
                    )
                )
        )

        Column(
            modifier = Modifier
                .align(Alignment.BottomStart)
                .padding(16.dp)
        ) {
            Text(
                text = group.name,
                fontWeight = FontWeight.Bold,
                fontSize = 20.sp,
                color = Color.White
            )
            Text(
                text = group.formattedMembersCount,
                color = Color.White.copy(alpha = 0.8f),
                fontSize = 13.sp,
                modifier = Modifier.padding(top = 2.dp, bottom = 12.dp)
            )

            Button(
                onClick = onToggleJoin,
                colors = ButtonDefaults.buttonColors(
                    containerColor = if (group.isMember) Color.White.copy(alpha = 0.3f) else ConnectlyBlue,
                    contentColor = Color.White
                ),
                shape = RoundedCornerShape(10.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                if (group.isMember) {
                    Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Joined", fontWeight = FontWeight.Bold)
                } else {
                    Text("Join Group", fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
private fun PopularGroupCard(
    group: Group,
    onToggleJoin: () -> Unit,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Column {
            AsyncImage(
                model = group.coverImage,
                contentDescription = group.name,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(90.dp)
            )

            Column(
                modifier = Modifier.padding(10.dp)
            ) {
                Text(
                    text = group.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp,
                    color = TextPrimaryLight,
                    maxLines = 1
                )
                Text(
                    text = group.formattedMembersCount,
                    fontSize = 11.sp,
                    color = TextSecondaryLight,
                    modifier = Modifier.padding(top = 2.dp, bottom = 8.dp)
                )

                Button(
                    onClick = onToggleJoin,
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (group.isMember) MaterialTheme.colorScheme.surfaceVariant else ConnectlyBlue,
                        contentColor = if (group.isMember) TextPrimaryLight else Color.White
                    ),
                    shape = RoundedCornerShape(8.dp),
                    contentPadding = PaddingValues(vertical = 4.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(32.dp)
                ) {
                    Text(
                        text = if (group.isMember) "Joined" else "Join",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }
        }
    }
}
