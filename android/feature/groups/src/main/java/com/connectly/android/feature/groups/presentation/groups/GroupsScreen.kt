package com.connectly.android.feature.groups.presentation.groups

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Group
import androidx.compose.material.icons.outlined.Mail
import androidx.compose.material.icons.outlined.Security
import androidx.compose.material.icons.outlined.ThumbUp
import androidx.compose.material.icons.outlined.TrendingUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.GroupCategoryItem
import com.connectly.android.core.model.GroupFeedItem
import com.connectly.android.core.model.GroupInvitationItem

import com.connectly.android.core.network.api.CreateGroupRequest
import com.connectly.android.core.network.api.CreateGroupRuleItem

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupsScreen(
    onNavigateToGroupDetails: (Long) -> Unit = {},
    onCreateGroup: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: GroupsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    if (state.showCreateDialog) {
        CreateGroupWizardDialog(
            categories = state.categories,
            onDismiss = { viewModel.handleIntent(GroupsIntent.ShowCreateDialog(false)) },
            onSubmit = { request ->
                viewModel.handleIntent(GroupsIntent.SubmitCreateGroup(request))
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
                            onValueChange = { viewModel.handleIntent(GroupsIntent.SearchQueryChanged(it)) },
                            placeholder = { Text("Search by name, industry, category...", fontSize = 13.sp) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth().height(48.dp),
                            shape = RoundedCornerShape(24.dp),
                            trailingIcon = {
                                IconButton(onClick = { viewModel.handleIntent(GroupsIntent.ToggleSearch) }) {
                                    Icon(Icons.Default.Close, contentDescription = "Close Search")
                                }
                            }
                        )
                    } else {
                        Text(
                            text = "Groups",
                            fontWeight = FontWeight.Bold,
                            fontSize = 24.sp,
                            color = TextPrimaryLight
                        )
                    }
                },
                actions = {
                    if (!state.isSearchVisible) {
                        IconButton(onClick = { viewModel.handleIntent(GroupsIntent.ToggleSearch) }) {
                            Icon(Icons.Default.Search, contentDescription = "Search Groups", tint = TextPrimaryLight)
                        }
                    }
                    IconButton(onClick = { viewModel.handleIntent(GroupsIntent.ShowCreateDialog(true)) }) {
                        Icon(Icons.Default.Add, contentDescription = "Create Group", tint = TextPrimaryLight)
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
                .padding(paddingValues),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // 1. Navigation / Filter Tabs
            item {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 4.dp),
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

            // 2. Professional Categories Filter Chips Carousel (Visible on Discover tab or search)
            if (state.selectedTab == "discover" || state.searchQuery.isNotBlank()) {
                item {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState())
                            .padding(horizontal = 16.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        CategoryChip(
                            name = "All Categories",
                            isSelected = state.selectedCategory == null,
                            onClick = { viewModel.handleIntent(GroupsIntent.SelectCategory(null)) }
                        )
                        state.categories.forEach { category ->
                            CategoryChip(
                                name = category.name,
                                isSelected = state.selectedCategory == category.name || state.selectedCategory == category.slug,
                                onClick = {
                                    val newCat = if (state.selectedCategory == category.name) null else category.name
                                    viewModel.handleIntent(GroupsIntent.SelectCategory(newCat))
                                }
                            )
                        }
                    }
                }
            }

            // 3. Search Mode Active Display
            if (state.searchQuery.isNotBlank() || state.selectedCategory != null) {
                item {
                    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                        Text(
                            text = if (state.searchQuery.isNotBlank()) "Search results for \"${state.searchQuery}\"" else "Category: ${state.selectedCategory}",
                            fontWeight = FontWeight.Bold,
                            fontSize = 17.sp,
                            color = TextPrimaryLight
                        )
                        Text(
                            text = "${state.popularGroups.size} communities found",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                    }
                }

                items(state.popularGroups.chunked(2)) { pair ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        PopularGroupCard(
                            group = pair[0],
                            onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[0])) },
                            onClick = { onNavigateToGroupDetails(pair[0].id) },
                            modifier = Modifier.weight(1f)
                        )
                        if (pair.size > 1) {
                            PopularGroupCard(
                                group = pair[1],
                                onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[1])) },
                                onClick = { onNavigateToGroupDetails(pair[1].id) },
                                modifier = Modifier.weight(1f)
                            )
                        } else {
                            Spacer(modifier = Modifier.weight(1f))
                        }
                    }
                }
            } else when (state.selectedTab) {
                "your_groups" -> {
                    val yourGroups = state.myGroups

                    item {
                        Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                            SectionHeader(
                                title = "Your Communities (${yourGroups.size})",
                                icon = Icons.Outlined.Group
                            )
                            Text(
                                text = "Communities you have joined and participate in",
                                fontSize = 12.sp,
                                color = TextSecondaryLight
                            )
                        }
                    }

                    if (yourGroups.isEmpty() && !state.isLoading) {
                        item {
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
                                        Icons.Outlined.Group,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(48.dp)
                                    )
                                    Text(
                                        text = "You haven't joined any groups yet",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp,
                                        color = TextPrimaryLight
                                    )
                                    Text(
                                        text = "Discover active communities to connect with peers, ask questions, and share insights.",
                                        fontSize = 13.sp,
                                        color = TextSecondaryLight,
                                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Button(
                                        onClick = { viewModel.handleIntent(GroupsIntent.SelectTab("discover")) },
                                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Text("Explore Communities")
                                    }
                                }
                            }
                        }
                    } else {
                        items(yourGroups.chunked(2)) { pair ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                PopularGroupCard(
                                    group = pair[0],
                                    onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[0])) },
                                    onClick = { onNavigateToGroupDetails(pair[0].id) },
                                    modifier = Modifier.weight(1f)
                                )
                                if (pair.size > 1) {
                                    PopularGroupCard(
                                        group = pair[1],
                                        onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[1])) },
                                        onClick = { onNavigateToGroupDetails(pair[1].id) },
                                        modifier = Modifier.weight(1f)
                                    )
                                } else {
                                    Spacer(modifier = Modifier.weight(1f))
                                }
                            }
                        }
                    }
                }
                "manage" -> {
                    val managedGroups = state.groupsYouManage

                    item {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column {
                                SectionHeader(
                                    title = "Groups You Manage (${managedGroups.size})",
                                    icon = Icons.Outlined.Security
                                )
                                Text(
                                    text = "Communities where you are an owner, admin, or moderator",
                                    fontSize = 12.sp,
                                    color = TextSecondaryLight
                                )
                            }
                        }
                    }

                    if (managedGroups.isEmpty() && !state.isLoading) {
                        item {
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
                                        Icons.Outlined.Security,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(48.dp)
                                    )
                                    Text(
                                        text = "You don't manage any groups yet",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp,
                                        color = TextPrimaryLight
                                    )
                                    Text(
                                        text = "Create your own professional community or business group to lead discussions and grow an audience.",
                                        fontSize = 13.sp,
                                        color = TextSecondaryLight,
                                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Button(
                                        onClick = { viewModel.handleIntent(GroupsIntent.ShowCreateDialog(true)) },
                                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                        shape = RoundedCornerShape(20.dp)
                                    ) {
                                        Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Text("Create a Group")
                                    }
                                }
                            }
                        }
                    } else {
                        items(managedGroups.chunked(2)) { pair ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                PopularGroupCard(
                                    group = pair[0],
                                    onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[0])) },
                                    onClick = { onNavigateToGroupDetails(pair[0].id) },
                                    modifier = Modifier.weight(1f)
                                )
                                if (pair.size > 1) {
                                    PopularGroupCard(
                                        group = pair[1],
                                        onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[1])) },
                                        onClick = { onNavigateToGroupDetails(pair[1].id) },
                                        modifier = Modifier.weight(1f)
                                    )
                                } else {
                                    Spacer(modifier = Modifier.weight(1f))
                                }
                            }
                        }
                    }
                }
                else -> { // "discover"
                    // 4. Featured Hero Group
                    item {
                        state.featuredGroup?.let { featured ->
                            Box(modifier = Modifier.padding(horizontal = 16.dp)) {
                                FeaturedGroupHeroCard(
                                    group = featured,
                                    onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(featured)) },
                                    onClick = { onNavigateToGroupDetails(featured.id) }
                                )
                            }
                        }
                    }

                    // 5. Pending Group Invitations
                    if (state.pendingInvitations.isNotEmpty()) {
                        item {
                            Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                                SectionHeader(title = "Pending Group Invitations", icon = Icons.Outlined.Mail)
                                Spacer(modifier = Modifier.height(8.dp))
                                state.pendingInvitations.forEach { inv ->
                                    InvitationCard(
                                        invitation = inv,
                                        onAccept = { viewModel.handleIntent(GroupsIntent.AcceptInvitation(inv.id)) },
                                        onDecline = { viewModel.handleIntent(GroupsIntent.DeclineInvitation(inv.id)) },
                                        onClick = { onNavigateToGroupDetails(inv.groupId) }
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                }
                            }
                        }
                    }

                    // 6. Suggested Groups
                    if (state.suggestedGroups.isNotEmpty()) {
                        item {
                            Column {
                                SectionHeader(
                                    title = "Suggested For You",
                                    modifier = Modifier.padding(horizontal = 16.dp),
                                    icon = Icons.Outlined.CheckCircle
                                )
                                Spacer(modifier = Modifier.height(8.dp))
                                LazyRow(
                                    contentPadding = PaddingValues(horizontal = 16.dp),
                                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                                ) {
                                    items(state.suggestedGroups, key = { "sug_${it.id}" }) { group ->
                                        StandardGroupCard(
                                            group = group,
                                            onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(group)) },
                                            onClick = { onNavigateToGroupDetails(group.id) }
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // 7. Discover Communities Grid
                    item {
                        SectionHeader(
                            title = "Discover Communities",
                            modifier = Modifier.padding(horizontal = 16.dp),
                            icon = Icons.Outlined.Group
                        )
                    }

                    val displayGroups = if (state.discoverCommunities.isNotEmpty()) {
                        state.discoverCommunities
                    } else {
                        state.popularGroups
                    }

                    items(displayGroups.chunked(2)) { pair ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            PopularGroupCard(
                                group = pair[0],
                                onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[0])) },
                                onClick = { onNavigateToGroupDetails(pair[0].id) },
                                modifier = Modifier.weight(1f)
                            )
                            if (pair.size > 1) {
                                PopularGroupCard(
                                    group = pair[1],
                                    onToggleJoin = { viewModel.handleIntent(GroupsIntent.ToggleJoin(pair[1])) },
                                    onClick = { onNavigateToGroupDetails(pair[1].id) },
                                    modifier = Modifier.weight(1f)
                                )
                            } else {
                                Spacer(modifier = Modifier.weight(1f))
                            }
                        }
                    }

                    // 8. Group Activity Feed
                    if (state.activityFeed.isNotEmpty()) {
                        item {
                            Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                                SectionHeader(title = "Recent Group Discussions & Feed", icon = Icons.Outlined.ThumbUp)
                                Spacer(modifier = Modifier.height(10.dp))
                                state.activityFeed.forEach { feedItem ->
                                    FeedItemCard(
                                        item = feedItem,
                                        onGroupClick = { feedItem.groupId?.let { onNavigateToGroupDetails(it) } }
                                    )
                                    Spacer(modifier = Modifier.height(10.dp))
                                }
                            }
                        }
                    }
                }
            }

            item {
                Spacer(modifier = Modifier.height(32.dp))
            }
        }
    }
}

@Composable
private fun SectionHeader(
    title: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector? = null,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp)
    ) {
        if (icon != null) {
            Icon(icon, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
        }
        Text(
            text = title,
            fontWeight = FontWeight.Bold,
            fontSize = 17.sp,
            color = TextPrimaryLight
        )
    }
}

@Composable
private fun CategoryChip(
    name: String,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(16.dp),
        color = if (isSelected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
        contentColor = if (isSelected) Color.White else TextPrimaryLight,
        modifier = Modifier.height(32.dp)
    ) {
        Box(
            modifier = Modifier.padding(horizontal = 14.dp),
            contentAlignment = Alignment.Center
        ) {
            Text(
                text = name,
                fontSize = 12.sp,
                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
            )
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
            .background(
                Brush.linearGradient(
                    colors = listOf(
                        Color(0xFF1E3A8A),
                        Color(0xFF2563EB),
                        Color(0xFF3B82F6)
                    )
                )
            )
            .clickable(onClick = onClick)
    ) {
        if (!group.coverImage.isNullOrBlank()) {
            AsyncImage(
                model = group.coverImage,
                contentDescription = group.name,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }

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
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(95.dp)
                    .background(
                        Brush.linearGradient(
                            colors = listOf(
                                Color(0xFFE2E8F0),
                                Color(0xFFCBD5E1)
                            )
                        )
                    ),
                contentAlignment = Alignment.Center
            ) {
                if (!group.coverImage.isNullOrBlank()) {
                    AsyncImage(
                        model = group.coverImage,
                        contentDescription = group.name,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize()
                    )
                } else {
                    Icon(
                        Icons.Outlined.Group,
                        contentDescription = null,
                        tint = ConnectlyBlue.copy(alpha = 0.5f),
                        modifier = Modifier.size(36.dp)
                    )
                }
            }

            Column(modifier = Modifier.padding(10.dp)) {
                Text(
                    text = group.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 13.sp,
                    color = TextPrimaryLight,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
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

@Composable
private fun StandardGroupCard(
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
            .width(160.dp)
            .clickable(onClick = onClick)
    ) {
        Column {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(85.dp)
                    .background(
                        Brush.linearGradient(
                            colors = listOf(
                                Color(0xFFE2E8F0),
                                Color(0xFFCBD5E1)
                            )
                        )
                    ),
                contentAlignment = Alignment.Center
            ) {
                if (!group.coverImage.isNullOrBlank()) {
                    AsyncImage(
                        model = group.coverImage,
                        contentDescription = group.name,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize()
                    )
                } else {
                    Icon(
                        Icons.Outlined.Group,
                        contentDescription = null,
                        tint = ConnectlyBlue.copy(alpha = 0.5f),
                        modifier = Modifier.size(32.dp)
                    )
                }
            }
            Column(modifier = Modifier.padding(8.dp)) {
                Text(
                    text = group.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 13.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    text = group.formattedMembersCount,
                    fontSize = 11.sp,
                    color = TextSecondaryLight,
                    modifier = Modifier.padding(vertical = 4.dp)
                )
                Button(
                    onClick = onToggleJoin,
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (group.isMember) MaterialTheme.colorScheme.surfaceVariant else ConnectlyBlue,
                        contentColor = if (group.isMember) TextPrimaryLight else Color.White
                    ),
                    shape = RoundedCornerShape(8.dp),
                    contentPadding = PaddingValues(vertical = 2.dp),
                    modifier = Modifier.fillMaxWidth().height(30.dp)
                ) {
                    Text(text = if (group.isMember) "Joined" else "Join", fontSize = 11.sp)
                }
            }
        }
    }
}

@Composable
private fun MyGroupThumbnailCard(
    group: Group,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .width(84.dp)
            .clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier
                .size(60.dp)
                .clip(CircleShape)
                .border(2.dp, ConnectlyBlue.copy(alpha = 0.5f), CircleShape)
        ) {
            AsyncImage(
                model = group.avatarImage ?: group.coverImage,
                contentDescription = group.name,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = group.name,
            fontSize = 11.sp,
            fontWeight = FontWeight.Medium,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis,
            color = TextPrimaryLight
        )
    }
}

@Composable
private fun ManagedGroupCard(
    group: Group,
    onClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        modifier = Modifier
            .width(170.dp)
            .clickable(onClick = onClick)
    ) {
        Column(modifier = Modifier.padding(10.dp)) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier.fillMaxWidth()
            ) {
                Surface(
                    color = ConnectlyBlue.copy(alpha = 0.15f),
                    shape = RoundedCornerShape(6.dp)
                ) {
                    Text(
                        text = "ADMIN",
                        color = ConnectlyBlue,
                        fontWeight = FontWeight.Bold,
                        fontSize = 10.sp,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }
                Icon(Icons.Outlined.Security, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = group.name,
                fontWeight = FontWeight.Bold,
                fontSize = 13.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                text = "${group.membersCount} members",
                fontSize = 11.sp,
                color = TextSecondaryLight,
                modifier = Modifier.padding(top = 2.dp, bottom = 8.dp)
            )
            OutlinedButton(
                onClick = onClick,
                shape = RoundedCornerShape(8.dp),
                contentPadding = PaddingValues(vertical = 2.dp),
                modifier = Modifier.fillMaxWidth().height(30.dp)
            ) {
                Text("Manage", fontSize = 11.sp)
            }
        }
    }
}

@Composable
private fun InvitationCard(
    invitation: GroupInvitationItem,
    onAccept: () -> Unit,
    onDecline: () -> Unit,
    onClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(46.dp)
                    .clip(CircleShape)
                    .clickable(onClick = onClick)
            ) {
                AsyncImage(
                    model = invitation.groupAvatar,
                    contentDescription = invitation.groupName,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize()
                )
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = invitation.groupName,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp
                )
                Text(
                    text = "${invitation.inviterName} invited you to join",
                    fontSize = 12.sp,
                    color = TextSecondaryLight
                )
            }
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                Button(
                    onClick = onAccept,
                    shape = RoundedCornerShape(8.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                    contentPadding = PaddingValues(horizontal = 10.dp, vertical = 4.dp),
                    modifier = Modifier.height(32.dp)
                ) {
                    Text("Accept", fontSize = 11.sp)
                }
                OutlinedButton(
                    onClick = onDecline,
                    shape = RoundedCornerShape(8.dp),
                    contentPadding = PaddingValues(horizontal = 10.dp, vertical = 4.dp),
                    modifier = Modifier.height(32.dp)
                ) {
                    Text("Decline", fontSize = 11.sp)
                }
            }
        }
    }
}

@Composable
private fun FeedItemCard(
    item: GroupFeedItem,
    onGroupClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(36.dp)
                        .clip(CircleShape)
                ) {
                    AsyncImage(
                        model = item.authorAvatar,
                        contentDescription = item.authorName,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize()
                    )
                }
                Spacer(modifier = Modifier.width(8.dp))
                Column {
                    Text(
                        text = item.authorName,
                        fontWeight = FontWeight.Bold,
                        fontSize = 13.sp
                    )
                    Text(
                        text = "in ${item.groupName}",
                        fontSize = 11.sp,
                        color = ConnectlyBlue,
                        modifier = Modifier.clickable(onClick = onGroupClick)
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = item.content,
                fontSize = 13.sp,
                color = TextPrimaryLight
            )
            Spacer(modifier = Modifier.height(8.dp))
            Row(
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "${item.likesCount} likes",
                    fontSize = 11.sp,
                    color = TextSecondaryLight
                )
                Text(
                    text = "${item.commentsCount} comments",
                    fontSize = 11.sp,
                    color = TextSecondaryLight
                )
            }
        }
    }
}

@Composable
fun CreateGroupWizardDialog(
    categories: List<GroupCategoryItem>,
    onDismiss: () -> Unit,
    onSubmit: (CreateGroupRequest) -> Unit
) {
    var step by remember { mutableIntStateOf(1) }

    // Step 1: Basic Info
    var name by remember { mutableStateOf("") }
    var username by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var selectedCategory by remember { mutableStateOf(if (categories.isNotEmpty()) categories[0].name else "Technology") }
    var industry by remember { mutableStateOf("") }
    var location by remember { mutableStateOf("") }
    var website by remember { mutableStateOf("") }
    var contactEmail by remember { mutableStateOf("") }
    var contactPhone by remember { mutableStateOf("") }

    // Step 2: Group Type
    var privacy by remember { mutableStateOf("public") } // "public", "private", "hidden"

    // Step 3: Membership Settings
    var approvalMode by remember { mutableStateOf("anyone") }
    var allowInvites by remember { mutableStateOf(true) }
    var enableScreening by remember { mutableStateOf(false) }
    var maxMembers by remember { mutableStateOf("") }

    // Step 4: Content Settings
    var whoCanPost by remember { mutableStateOf("anyone_in_group") }
    var postApprovalMode by remember { mutableStateOf("auto") }
    var allowAnonymous by remember { mutableStateOf(false) }
    var allowScheduled by remember { mutableStateOf(true) }

    // Step 5: Group Rules
    var customRuleTitle by remember { mutableStateOf("") }
    var customRuleDesc by remember { mutableStateOf("") }
    val customRules = remember { mutableStateListOf<CreateGroupRuleItem>() }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Column {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Create Group (${step}/6)",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    Text(
                        text = when (step) {
                            1 -> "Basic Info"
                            2 -> "Group Type"
                            3 -> "Membership"
                            4 -> "Content"
                            5 -> "Rules"
                            else -> "Review"
                        },
                        fontSize = 12.sp,
                        color = ConnectlyBlue,
                        fontWeight = FontWeight.SemiBold
                    )
                }
                LinearProgressIndicator(
                    progress = { step / 6f },
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    color = ConnectlyBlue,
                )
            }
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(min = 280.dp, max = 380.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                when (step) {
                    1 -> {
                        // Step 1: Basic Information
                        OutlinedTextField(
                            value = name,
                            onValueChange = { name = it },
                            label = { Text("Group Name *") },
                            placeholder = { Text("e.g. Bangladesh Tech Entrepreneurs") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        OutlinedTextField(
                            value = username,
                            onValueChange = { username = it },
                            label = { Text("Unique Handle / Username") },
                            placeholder = { Text("e.g. bd-tech-entrepreneurs") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        OutlinedTextField(
                            value = description,
                            onValueChange = { description = it },
                            label = { Text("Description") },
                            placeholder = { Text("What is this professional community about?") },
                            maxLines = 2,
                            modifier = Modifier.fillMaxWidth()
                        )
                        OutlinedTextField(
                            value = industry,
                            onValueChange = { industry = it },
                            label = { Text("Industry") },
                            placeholder = { Text("e.g. Software, Fintech, Healthcare") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        OutlinedTextField(
                            value = location,
                            onValueChange = { location = it },
                            label = { Text("Location") },
                            placeholder = { Text("e.g. Dhaka, Bangladesh or Global") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }

                    2 -> {
                        // Step 2: Group Type / Privacy
                        Text("Select Visibility & Discovery Mode:", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)

                        Card(
                            onClick = { privacy = "public" },
                            colors = CardDefaults.cardColors(
                                containerColor = if (privacy == "public") ConnectlyBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant
                            ),
                            shape = RoundedCornerShape(10.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(10.dp)) {
                                Text("Public Group", fontWeight = FontWeight.Bold)
                                Text("Anyone can discover the group, view posts, and participate.", fontSize = 11.sp, color = TextSecondaryLight)
                            }
                        }

                        Card(
                            onClick = { privacy = "private" },
                            colors = CardDefaults.cardColors(
                                containerColor = if (privacy == "private") ConnectlyBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant
                            ),
                            shape = RoundedCornerShape(10.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(10.dp)) {
                                Text("Private Group", fontWeight = FontWeight.Bold)
                                Text("Discoverable in search, but only approved members can view posts.", fontSize = 11.sp, color = TextSecondaryLight)
                            }
                        }

                        Card(
                            onClick = { privacy = "hidden" },
                            colors = CardDefaults.cardColors(
                                containerColor = if (privacy == "hidden") ConnectlyBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant
                            ),
                            shape = RoundedCornerShape(10.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(10.dp)) {
                                Text("Hidden Group", fontWeight = FontWeight.Bold)
                                Text("Completely hidden from search. Only invited members can access.", fontSize = 11.sp, color = TextSecondaryLight)
                            }
                        }
                    }

                    3 -> {
                        // Step 3: Membership Settings
                        Text("Membership Policy:", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Admin Approval Required", fontSize = 13.sp)
                            Switch(
                                checked = approvalMode == "approval_required",
                                onCheckedChange = { approvalMode = if (it) "approval_required" else "anyone" }
                            )
                        }

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Allow Member Invitations", fontSize = 13.sp)
                            Switch(
                                checked = allowInvites,
                                onCheckedChange = { allowInvites = it }
                            )
                        }

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Membership Screening Questions", fontSize = 13.sp)
                            Switch(
                                checked = enableScreening,
                                onCheckedChange = { enableScreening = it }
                            )
                        }

                        OutlinedTextField(
                            value = maxMembers,
                            onValueChange = { maxMembers = it },
                            label = { Text("Maximum Members Limit (Optional)") },
                            placeholder = { Text("e.g. 5000") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }

                    4 -> {
                        // Step 4: Content Settings
                        Text("Publishing & Moderation:", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Admins Only Post Approval", fontSize = 13.sp)
                            Switch(
                                checked = postApprovalMode == "admin_approval",
                                onCheckedChange = { postApprovalMode = if (it) "admin_approval" else "auto" }
                            )
                        }

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Allow Anonymous Posts", fontSize = 13.sp)
                            Switch(
                                checked = allowAnonymous,
                                onCheckedChange = { allowAnonymous = it }
                            )
                        }

                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Allow Scheduled Posts", fontSize = 13.sp)
                            Switch(
                                checked = allowScheduled,
                                onCheckedChange = { allowScheduled = it }
                            )
                        }
                    }

                    5 -> {
                        // Step 5: Group Rules
                        Text("Community Rules:", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                        Text("5 professional standards are pre-configured by default.", fontSize = 11.sp, color = TextSecondaryLight)

                        OutlinedTextField(
                            value = customRuleTitle,
                            onValueChange = { customRuleTitle = it },
                            label = { Text("Add Custom Rule Title") },
                            placeholder = { Text("e.g. No unsolicited direct messages") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        OutlinedTextField(
                            value = customRuleDesc,
                            onValueChange = { customRuleDesc = it },
                            label = { Text("Rule Description") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        Button(
                            onClick = {
                                if (customRuleTitle.isNotBlank()) {
                                    customRules.add(
                                        CreateGroupRuleItem(
                                            title = customRuleTitle.trim(),
                                            description = customRuleDesc.trim()
                                        )
                                    )
                                    customRuleTitle = ""
                                    customRuleDesc = ""
                                }
                            },
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.align(Alignment.End)
                        ) {
                            Text("+ Add Rule", fontSize = 11.sp)
                        }

                        if (customRules.isNotEmpty()) {
                            Text("${customRules.size} custom rule(s) added", fontSize = 11.sp, color = ConnectlyBlue)
                        }
                    }

                    6 -> {
                        // Step 6: Review & Finalize
                        Card(
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                Text(name.ifBlank { "Untitled Community" }, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                                if (username.isNotBlank()) {
                                    Text("@${username}", color = ConnectlyBlue, fontSize = 12.sp)
                                }
                                Text("Type: ${privacy.replaceFirstChar { it.uppercase() }} Group", fontSize = 12.sp)
                                Text("Category: ${selectedCategory}", fontSize = 12.sp)
                                if (industry.isNotBlank()) {
                                    Text("Industry: ${industry}", fontSize = 12.sp)
                                }
                                if (location.isNotBlank()) {
                                    Text("Location: ${location}", fontSize = 12.sp)
                                }
                                Text("Creator: You (Designated Group Owner)", fontWeight = FontWeight.SemiBold, fontSize = 12.sp, color = ConnectlyBlue)
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {
            if (step < 6) {
                Button(
                    onClick = {
                        if (step == 1 && name.isBlank()) {
                            // Name is required
                        } else {
                            step++
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                    enabled = step != 1 || name.isNotBlank()
                ) {
                    Text("Next")
                }
            } else {
                Button(
                    onClick = {
                        onSubmit(
                            CreateGroupRequest(
                                name = name.trim(),
                                username = username.trim().ifBlank { null },
                                description = description.trim().ifBlank { null },
                                category = selectedCategory,
                                industry = industry.trim().ifBlank { null },
                                location = location.trim().ifBlank { null },
                                website = website.trim().ifBlank { null },
                                contact_email = contactEmail.trim().ifBlank { null },
                                contact_phone = contactPhone.trim().ifBlank { null },
                                privacy = privacy,
                                membership_approval_mode = approvalMode,
                                allow_member_invitations = allowInvites,
                                enable_membership_screening = enableScreening,
                                max_members_limit = maxMembers.toIntOrNull(),
                                who_can_post = whoCanPost,
                                post_approval_mode = postApprovalMode,
                                allow_anonymous_posts = allowAnonymous,
                                allow_scheduled_posts = allowScheduled,
                                rules = customRules.toList()
                            )
                        )
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Create Group")
                }
            }
        },
        dismissButton = {
            if (step > 1) {
                TextButton(onClick = { step-- }) {
                    Text("Back")
                }
            } else {
                TextButton(onClick = onDismiss) {
                    Text("Cancel")
                }
            }
        }
    )
}
