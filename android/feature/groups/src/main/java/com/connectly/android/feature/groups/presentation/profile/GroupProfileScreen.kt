package com.connectly.android.feature.groups.presentation.profile

import android.content.Intent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.network.api.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupProfileScreen(
    slugOrId: String,
    onNavigateBack: () -> Unit,
    onNavigateToDashboard: (groupId: Long) -> Unit = {},
    onNavigateToCreatePost: (groupId: Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: GroupProfileViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(slugOrId) {
        if (slugOrId.isNotBlank()) {
            viewModel.handleIntent(GroupProfileIntent.LoadProfile(slugOrId))
        }
    }

    val snackbarHostState = remember { SnackbarHostState() }
    LaunchedEffect(state.errorMessage, state.successMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
        }
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
        }
    }

    val tabs = listOf(
        "Overview",
        "Discussion",
        "Announcements",
        "Members",
        "Events",
        "Media",
        "Files",
        "About",
        "Dashboard"
    )

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = state.profileData?.group?.name ?: "Group Profile",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(
                        onClick = {
                            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                                type = "text/plain"
                                putExtra(Intent.EXTRA_SUBJECT, state.profileData?.group?.name ?: "Group")
                                putExtra(Intent.EXTRA_TEXT, "Check out ${state.profileData?.group?.name} on Connectly: https://connectly.test/groups/${state.profileData?.group?.username ?: slugOrId}")
                            }
                            context.startActivity(Intent.createChooser(shareIntent, "Share Group"))
                        }
                    ) {
                        Icon(Icons.Default.Share, contentDescription = "Share Group")
                    }
                    IconButton(onClick = { viewModel.handleIntent(GroupProfileIntent.ShowReportDialog(true)) }) {
                        Icon(Icons.Outlined.Flag, contentDescription = "Report Group")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
        }
    ) { innerPadding ->
        if (state.isLoading && state.profileData == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else if (state.profileData == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .background(BackgroundLight),
                contentAlignment = Alignment.Center
            ) {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center,
                    modifier = Modifier.padding(24.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.ErrorOutline,
                        contentDescription = null,
                        modifier = Modifier.size(64.dp),
                        tint = TextSecondaryLight
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = state.errorMessage ?: "Group profile unavailable.",
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 16.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                        textAlign = TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "The requested group could not be loaded or does not exist.",
                        fontSize = 13.sp,
                        color = TextSecondaryLight,
                        textAlign = TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(20.dp))
                    Button(
                        onClick = { viewModel.handleIntent(GroupProfileIntent.Refresh) },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Text("Retry")
                    }
                }
            }
        } else {
            val group = state.profileData?.group
            val tabsData = state.profileData?.tabs

            LazyColumn(
                modifier = modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .background(BackgroundLight)
            ) {
                // 1. Group Header (Cover, Profile Image, Titles, Meta, Actions)
                item {
                    GroupHeaderSection(
                        group = group,
                        onJoin = { viewModel.handleIntent(GroupProfileIntent.JoinGroup) },
                        onCancelRequest = { viewModel.handleIntent(GroupProfileIntent.CancelRequest) },
                        onLeave = { viewModel.handleIntent(GroupProfileIntent.LeaveGroup) },
                        onToggleFollow = { viewModel.handleIntent(GroupProfileIntent.ToggleFollow) },
                        onInvite = { viewModel.handleIntent(GroupProfileIntent.ShowInviteDialog(true)) },
                        onOpenDashboard = {
                            group?.id?.let { onNavigateToDashboard(it) }
                        }
                    )
                }

                // 2. Navigation Tabs (9 Tabs)
                item {
                    ScrollableTabRow(
                        selectedTabIndex = state.selectedTab,
                        edgePadding = 16.dp,
                        containerColor = MaterialTheme.colorScheme.surface,
                        contentColor = ConnectlyBlue,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        tabs.forEachIndexed { index, tabTitle ->
                            Tab(
                                selected = state.selectedTab == index,
                                onClick = { viewModel.handleIntent(GroupProfileIntent.SelectTab(index)) },
                                text = {
                                    Text(
                                        text = tabTitle,
                                        fontWeight = if (state.selectedTab == index) FontWeight.Bold else FontWeight.Normal,
                                        fontSize = 13.sp
                                    )
                                }
                            )
                        }
                    }
                }

                // 3. Tab Contents
                when (state.selectedTab) {
                    0 -> {
                        // Overview Tab
                        overviewTabContent(
                            overview = tabsData?.overview,
                            onNavigateToCreatePost = { group?.id?.let { onNavigateToCreatePost(it) } },
                            onSelectTab = { viewModel.handleIntent(GroupProfileIntent.SelectTab(it)) }
                        )
                    }
                    1 -> {
                        // Discussion Tab
                        discussionTabContent(
                            discussions = tabsData?.discussion?.discussions,
                            onNavigateToCreatePost = { group?.id?.let { onNavigateToCreatePost(it) } }
                        )
                    }
                    2 -> {
                        // Announcements Tab
                        announcementsTabContent(
                            announcements = tabsData?.announcements
                        )
                    }
                    3 -> {
                        // Members Tab
                        membersTabContent(
                            members = tabsData?.members
                        )
                    }
                    4 -> {
                        // Events Tab
                        eventsTabContent(
                            events = tabsData?.events
                        )
                    }
                    5 -> {
                        // Media Tab
                        mediaTabContent(
                            media = tabsData?.media
                        )
                    }
                    6 -> {
                        // Files Tab
                        filesTabContent(
                            files = tabsData?.files
                        )
                    }
                    7 -> {
                        // About Tab
                        aboutTabContent(
                            about = tabsData?.about,
                            group = group
                        )
                    }
                    8 -> {
                        // Dashboard Tab
                        dashboardTabContent(
                            group = group,
                            onOpenDashboard = { group?.id?.let { onNavigateToDashboard(it) } }
                        )
                    }
                }
            }
        }
    }

    if (state.showReportDialog) {
        ReportGroupDialog(
            onDismiss = { viewModel.handleIntent(GroupProfileIntent.ShowReportDialog(false)) },
            onSubmit = { reason, desc ->
                viewModel.handleIntent(GroupProfileIntent.ReportGroup(reason, desc))
            }
        )
    }
}

@Composable
private fun GroupHeaderSection(
    group: GroupDetailHeaderDto?,
    onJoin: () -> Unit,
    onCancelRequest: () -> Unit,
    onLeave: () -> Unit,
    onToggleFollow: () -> Unit,
    onInvite: () -> Unit,
    onOpenDashboard: () -> Unit
) {
    if (group == null) return

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.surface)
    ) {
        // Cover Image
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(160.dp)
                .background(
                    Brush.verticalGradient(
                        listOf(Color(0xFF1E3A8A), Color(0xFF0284C7))
                    )
                )
        ) {
            if (!group.cover_image.isNullOrBlank()) {
                AsyncImage(
                    model = group.cover_image,
                    contentDescription = "Cover Image",
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
            }
        }

        // Profile Avatar & Badges
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .offset(y = (-40).dp),
                verticalAlignment = Alignment.Bottom,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Box(
                    modifier = Modifier
                        .size(80.dp)
                        .clip(CircleShape)
                        .border(3.dp, MaterialTheme.colorScheme.surface, CircleShape)
                        .background(Color(0xFFE2E8F0)),
                    contentAlignment = Alignment.Center
                ) {
                    if (!group.avatar_url.isNullOrBlank()) {
                        AsyncImage(
                            model = group.avatar_url,
                            contentDescription = group.name,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        Icon(
                            Icons.Default.Groups,
                            contentDescription = null,
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(44.dp)
                        )
                    }
                }

                // Category & Privacy badges
                Row(
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    modifier = Modifier.padding(bottom = 8.dp)
                ) {
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = Color(0xFFEFF6FF)
                    ) {
                        Text(
                            text = group.category ?: "Community",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.SemiBold,
                            color = ConnectlyBlue,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = if (group.privacy == "public") Color(0xFFECFDF5) else Color(0xFFFFFBEB)
                    ) {
                        Text(
                            text = (group.privacy ?: "public").replaceFirstChar { it.uppercase() },
                            fontSize = 11.sp,
                            fontWeight = FontWeight.SemiBold,
                            color = if (group.privacy == "public") Color(0xFF059669) else Color(0xFFD97706),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                }
            }

            // Name & Username Handle
            Column(modifier = Modifier.offset(y = (-30).dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = group.name,
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimaryLight
                    )
                    if (group.is_verified) {
                        Spacer(modifier = Modifier.width(4.dp))
                        Icon(
                            Icons.Default.CheckCircle,
                            contentDescription = "Verified",
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                }
                if (!group.username.isNullOrBlank()) {
                    Text(
                        text = "@${group.username}",
                        fontSize = 13.sp,
                        color = TextSecondaryLight
                    )
                }

                val description = group.description
                if (!description.isNullOrBlank()) {
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        text = description,
                        fontSize = 13.sp,
                        color = TextPrimaryLight,
                        maxLines = 3,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                // Stats & Meta row
                Spacer(modifier = Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Outlined.People, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("${group.members_count} members", fontSize = 12.sp, color = TextSecondaryLight, fontWeight = FontWeight.Medium)
                    }
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(modifier = Modifier.size(6.dp).clip(CircleShape).background(Color(0xFF10B981)))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("${group.online_members_count} online", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Medium)
                    }
                    if (!group.activity_status.isNullOrBlank()) {
                        Text("•  ${group.activity_status}", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                }

                // Action Buttons Row
                Spacer(modifier = Modifier.height(14.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val actions = group.actions

                    // Join / Status Button
                    when {
                        actions?.can_join == true -> {
                            Button(
                                onClick = onJoin,
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Join Group", fontSize = 13.sp)
                            }
                        }
                        actions?.can_request == true -> {
                            Button(
                                onClick = onJoin,
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Request to Join", fontSize = 13.sp)
                            }
                        }
                        actions?.can_cancel_request == true -> {
                            OutlinedButton(
                                onClick = onCancelRequest,
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Cancel Request", fontSize = 12.sp, color = Color(0xFFEF4444))
                            }
                        }
                        actions?.can_leave == true -> {
                            var showLeaveMenu by remember { mutableStateOf(false) }
                            Box(modifier = Modifier.weight(1f)) {
                                Button(
                                    onClick = { showLeaveMenu = true },
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFF1F5F9)),
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(8.dp)
                                ) {
                                    Icon(Icons.Default.Check, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Joined", color = ConnectlyBlue, fontSize = 13.sp)
                                }
                                DropdownMenu(
                                    expanded = showLeaveMenu,
                                    onDismissRequest = { showLeaveMenu = false }
                                ) {
                                    DropdownMenuItem(
                                        text = { Text("Leave Group", color = Color(0xFFEF4444)) },
                                        onClick = {
                                            showLeaveMenu = false
                                            onLeave()
                                        },
                                        leadingIcon = {
                                            Icon(Icons.Outlined.ExitToApp, contentDescription = null, tint = Color(0xFFEF4444))
                                        }
                                    )
                                }
                            }
                        }
                    }

                    // Invite Button
                    if (actions?.can_invite == true) {
                        OutlinedButton(
                            onClick = onInvite,
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Icon(Icons.Outlined.PersonAdd, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Invite", fontSize = 13.sp)
                        }
                    }

                    // Follow Toggle
                    if (actions?.can_follow == true) {
                        IconButton(
                            onClick = onToggleFollow,
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .background(if (actions.is_following) Color(0xFFEFF6FF) else Color(0xFFF1F5F9))
                        ) {
                            Icon(
                                if (actions.is_following) Icons.Default.NotificationsActive else Icons.Outlined.NotificationsNone,
                                contentDescription = "Follow Updates",
                                tint = if (actions.is_following) ConnectlyBlue else TextSecondaryLight
                            )
                        }
                    }

                    // Admin Dashboard Shield Button
                    if (actions?.can_manage == true) {
                        IconButton(
                            onClick = onOpenDashboard,
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .background(Color(0xFFEFF6FF))
                        ) {
                            Icon(Icons.Outlined.AdminPanelSettings, contentDescription = "Dashboard", tint = ConnectlyBlue)
                        }
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 1: OVERVIEW
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.overviewTabContent(
    overview: GroupProfileOverviewDto?,
    onNavigateToCreatePost: () -> Unit,
    onSelectTab: (Int) -> Unit
) {
    if (overview == null) {
        item {
            EmptyTabNotice("No overview details available for this group.")
        }
        return
    }

    // Pinned Announcement
    overview.pinned_announcement?.let { pinned ->
        item {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFFEFF6FF)),
                shape = RoundedCornerShape(12.dp)
            ) {
                Column(modifier = Modifier.padding(14.dp)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.PushPin, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Pinned Announcement", fontWeight = FontWeight.Bold, color = ConnectlyBlue, fontSize = 13.sp)
                    }
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(pinned.body, fontSize = 13.sp, color = TextPrimaryLight)
                }
            }
        }
    }

    // Composer Bar
    item {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 6.dp)
                .clickable { onNavigateToCreatePost() },
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            shape = RoundedCornerShape(12.dp)
        ) {
            Row(
                modifier = Modifier.padding(12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(Icons.Outlined.Edit, contentDescription = null, tint = ConnectlyBlue)
                Spacer(modifier = Modifier.width(10.dp))
                Text("Start a professional discussion...", color = TextSecondaryLight, fontSize = 13.sp)
            }
        }
    }

    // Quick Metrics
    item {
        overview.metrics?.let { metrics ->
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                MetricMiniCard("Members", "${metrics.total_members}", Modifier.weight(1f))
                MetricMiniCard("Discussions", "${metrics.total_discussions}", Modifier.weight(1f))
                MetricMiniCard("Events", "${metrics.total_events}", Modifier.weight(1f))
            }
        }
    }

    // Recent Discussions Preview
    if (overview.recent_discussions.isNotEmpty()) {
        item {
            SectionHeader(title = "Recent Discussions", onSeeAll = { onSelectTab(1) })
        }
        items(overview.recent_discussions.take(3)) { discussion ->
            DiscussionCard(discussion = discussion)
        }
    }
}

// ----------------------------------------------------
// TAB 2: DISCUSSION
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.discussionTabContent(
    discussions: List<GroupDiscussionDto>?,
    onNavigateToCreatePost: () -> Unit
) {
    item {
        Button(
            onClick = onNavigateToCreatePost,
            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            shape = RoundedCornerShape(8.dp)
        ) {
            Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
            Spacer(modifier = Modifier.width(6.dp))
            Text("Create Discussion Post", fontSize = 13.sp)
        }
    }

    if (discussions.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No discussions yet. Start the conversation!")
        }
    } else {
        items(discussions) { disc ->
            DiscussionCard(discussion = disc)
        }
    }
}

// ----------------------------------------------------
// TAB 3: ANNOUNCEMENTS
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.announcementsTabContent(
    announcements: List<GroupAnnouncementDto>?
) {
    if (announcements.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No official announcements posted yet.")
        }
    } else {
        items(announcements) { ann ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Column(modifier = Modifier.padding(14.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = ann.author_name ?: "Group Admin",
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp,
                            color = ConnectlyBlue
                        )
                        if (ann.is_pinned) {
                            Surface(color = Color(0xFFEFF6FF), shape = RoundedCornerShape(6.dp)) {
                                Text("PINNED", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(ann.body, fontSize = 13.sp, color = TextPrimaryLight)
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(ann.created_at.orEmpty(), fontSize = 11.sp, color = TextSecondaryLight)
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 4: MEMBERS
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.membersTabContent(
    members: List<GroupMemberDto>?
) {
    if (members.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No members found.")
        }
    } else {
        items(members) { member ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                shape = RoundedCornerShape(10.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Row(
                    modifier = Modifier.padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Box(
                        modifier = Modifier
                            .size(40.dp)
                            .clip(CircleShape)
                            .background(Color(0xFFE2E8F0)),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(member.name.take(1).uppercase(), fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(member.name, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        Text(member.role.replaceFirstChar { it.uppercase() }, fontSize = 12.sp, color = ConnectlyBlue)
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 5: EVENTS
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.eventsTabContent(
    events: List<EventDto>?
) {
    if (events.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No upcoming events scheduled.")
        }
    } else {
        items(events) { ev ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                    Surface(
                        color = Color(0xFFEFF6FF),
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.size(50.dp)
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center
                        ) {
                            Icon(Icons.Outlined.Event, contentDescription = null, tint = ConnectlyBlue)
                        }
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(ev.title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                        Text(ev.starts_at ?: "Scheduled", fontSize = 12.sp, color = TextSecondaryLight)
                        Text("${ev.attendees_count} attending", fontSize = 11.sp, color = ConnectlyBlue)
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 6: MEDIA
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.mediaTabContent(
    media: List<GroupMediaItemDto>?
) {
    if (media.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No photos or media shared yet.")
        }
    } else {
        item {
            LazyVerticalGrid(
                columns = GridCells.Fixed(3),
                modifier = Modifier
                    .fillMaxWidth()
                    .height(300.dp)
                    .padding(16.dp),
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                items(media) { item ->
                    Box(
                        modifier = Modifier
                            .aspectRatio(1f)
                            .clip(RoundedCornerShape(8.dp))
                            .background(Color(0xFFE2E8F0))
                    ) {
                        AsyncImage(
                            model = item.url,
                            contentDescription = null,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 7: FILES
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.filesTabContent(
    files: List<GroupFileDto>?
) {
    if (files.isNullOrEmpty()) {
        item {
            EmptyTabNotice("No documents or files uploaded yet.")
        }
    } else {
        items(files) { file ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                shape = RoundedCornerShape(10.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Outlined.InsertDriveFile, contentDescription = null, tint = ConnectlyBlue)
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(file.name, fontWeight = FontWeight.Medium, fontSize = 13.sp)
                        Text(file.size, fontSize = 11.sp, color = TextSecondaryLight)
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 8: ABOUT
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.aboutTabContent(
    about: GroupAboutTabDto?,
    group: GroupDetailHeaderDto?
) {
    item {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // General Info Card
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("About this community", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                    Text(about?.description ?: group?.description ?: "No description provided.", fontSize = 13.sp, color = TextPrimaryLight)

                    Divider(modifier = Modifier.padding(vertical = 4.dp))

                    about?.industry?.let {
                        InfoRow(icon = Icons.Outlined.Work, label = "Industry", value = it)
                    }
                    about?.location?.let {
                        InfoRow(icon = Icons.Outlined.LocationOn, label = "Location", value = it)
                    }
                    about?.website?.let {
                        InfoRow(icon = Icons.Outlined.Language, label = "Website", value = it)
                    }
                    about?.creation_date?.let {
                        InfoRow(icon = Icons.Outlined.CalendarToday, label = "Created", value = it)
                    }
                }
            }

            // Group Rules Card
            if (!about?.rules.isNullOrEmpty()) {
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Text("Group Rules", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        about?.rules?.forEachIndexed { idx, rule ->
                            Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                Text("${idx + 1}. ${rule.title}", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                                rule.description?.let {
                                    Text(it, fontSize = 12.sp, color = TextSecondaryLight)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// TAB 9: DASHBOARD
// ----------------------------------------------------
private fun androidx.compose.foundation.lazy.LazyListScope.dashboardTabContent(
    group: GroupDetailHeaderDto?,
    onOpenDashboard: () -> Unit
) {
    item {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Icon(
                        Icons.Outlined.AdminPanelSettings,
                        contentDescription = null,
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(48.dp)
                    )
                    Text("Group Administration Dashboard", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Text(
                        "Access real-time member growth, engagement analytics, role management, post approvals, and membership screening.",
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                    Button(
                        onClick = onOpenDashboard,
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("Launch Full Management Hub", fontSize = 13.sp)
                    }
                }
            }
        }
    }
}

// Helper Components
@Composable
private fun MetricMiniCard(label: String, value: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(
            modifier = Modifier.padding(10.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(value, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = ConnectlyBlue)
            Text(label, fontSize = 11.sp, color = TextSecondaryLight)
        }
    }
}

@Composable
private fun SectionHeader(title: String, onSeeAll: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(title, fontWeight = FontWeight.Bold, fontSize = 15.sp)
        Text(
            "See All",
            fontSize = 12.sp,
            color = ConnectlyBlue,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.clickable { onSeeAll() }
        )
    }
}

@Composable
private fun DiscussionCard(discussion: GroupDiscussionDto) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 4.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(discussion.resolvedTitle, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text("by ${discussion.resolvedAuthorName}", fontSize = 11.sp, color = TextSecondaryLight)
                Text("${discussion.resolvedCommentsCount} comments", fontSize = 11.sp, color = ConnectlyBlue)
            }
        }
    }
}

@Composable
private fun EmptyTabNotice(message: String) {
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(message, color = TextSecondaryLight, fontSize = 13.sp)
    }
}

@Composable
private fun InfoRow(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(icon, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(16.dp))
        Spacer(modifier = Modifier.width(8.dp))
        Text("$label: ", fontSize = 12.sp, color = TextSecondaryLight)
        Text(value, fontSize = 12.sp, fontWeight = FontWeight.Medium, color = TextPrimaryLight)
    }
}

@Composable
private fun ReportGroupDialog(
    onDismiss: () -> Unit,
    onSubmit: (reason: String, description: String) -> Unit
) {
    var reason by remember { mutableStateOf("Inappropriate Content") }
    var description by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Report Group", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Select report category:", fontSize = 12.sp)
                OutlinedTextField(
                    value = reason,
                    onValueChange = { reason = it },
                    label = { Text("Reason") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Additional details (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    maxLines = 3
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(reason, description) },
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFEF4444))
            ) {
                Text("Submit Report")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

