package com.connectly.android.feature.groups.presentation.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
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
fun GroupDashboardScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    onQuickActionClick: (actionId: String, route: String) -> Unit = { _, _ -> },
    modifier: Modifier = Modifier,
    viewModel: GroupDashboardViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(groupId) {
        if (groupId > 0 && state.groupId != groupId) {
            viewModel.handleIntent(GroupDashboardIntent.LoadDashboard(groupId, state.selectedPeriod))
        }
    }

    val snackbarHostState = remember { SnackbarHostState() }
    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(
                            text = "Group Dashboard",
                            fontWeight = FontWeight.Bold,
                            fontSize = 18.sp
                        )
                        Text(
                            text = "Analytics & Community Management",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.handleIntent(GroupDashboardIntent.Refresh) }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh Dashboard")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
        }
    ) { innerPadding ->
        if (state.isLoading && state.dashboardData == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else {
            val overview = state.dashboardData?.overview
            val charts = state.dashboardData?.charts
            val quickActions = state.dashboardData?.quick_actions ?: emptyList()
            val filter = state.dashboardData?.filter

            LazyColumn(
                modifier = modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .background(BackgroundLight),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // 1. Date Filter Carousel
                item {
                    DateFilterSection(
                        selectedPeriod = state.selectedPeriod,
                        filterLabel = filter?.label,
                        onSelectPeriod = { period ->
                            if (period == "custom") {
                                viewModel.handleIntent(GroupDashboardIntent.ShowCustomDateDialog(true))
                            } else {
                                viewModel.handleIntent(GroupDashboardIntent.ChangePeriod(period))
                            }
                        }
                    )
                }

                // 2. Real-time Overview Statistics (Grid of 4x3 Metrics)
                if (overview != null) {
                    item {
                        OverviewMetricsSection(overview = overview)
                    }
                }

                // 3. Analytics Charts (Member Growth, Post & Comment Trends)
                if (charts != null) {
                    item {
                        ChartsVisualizerSection(charts = charts)
                    }

                    // 4. Top Performing Posts Leaderboard
                    if (charts.top_performing_posts.isNotEmpty()) {
                        item {
                            TopPerformingPostsSection(posts = charts.top_performing_posts)
                        }
                    }

                    // 5. Most Active Members
                    if (charts.most_active_members.isNotEmpty()) {
                        item {
                            MostActiveMembersSection(members = charts.most_active_members)
                        }
                    }

                    // 6. Member Retention & Engagement Health
                    val retention = charts.member_retention
                    if (retention != null) {
                        item {
                            MemberRetentionSection(
                                retention = retention,
                                engagementRate = overview?.engagement_rate ?: 0.0
                            )
                        }
                    }
                }

                // 7. Quick Management Actions (10 Core Controls)
                if (quickActions.isNotEmpty()) {
                    item {
                        QuickActionsSection(
                            actions = quickActions,
                            onActionClick = onQuickActionClick
                        )
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(24.dp))
                }
            }
        }
    }

    if (state.showCustomDateDialog) {
        CustomDateRangeDialog(
            onDismiss = { viewModel.handleIntent(GroupDashboardIntent.ShowCustomDateDialog(false)) },
            onSubmit = { start, end ->
                viewModel.handleIntent(GroupDashboardIntent.ApplyCustomDateRange(start, end))
            }
        )
    }
}

// ----------------------------------------------------
// 1. Date Filter Section
// ----------------------------------------------------
@Composable
private fun DateFilterSection(
    selectedPeriod: String,
    filterLabel: String?,
    onSelectPeriod: (String) -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.surface)
            .padding(vertical = 10.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .horizontalScroll(rememberScrollState())
                .padding(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            val filters = listOf(
                "7d" to "Last 7 Days",
                "30d" to "Last 30 Days",
                "90d" to "Last 90 Days",
                "12m" to "Last 12 Months",
                "custom" to "Custom Range"
            )

            filters.forEach { (key, label) ->
                val isSelected = selectedPeriod == key
                FilterChip(
                    selected = isSelected,
                    onClick = { onSelectPeriod(key) },
                    label = { Text(label, fontSize = 12.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = ConnectlyBlue,
                        selectedLabelColor = Color.White
                    ),
                    shape = RoundedCornerShape(16.dp)
                )
            }
        }

        if (!filterLabel.isNullOrBlank()) {
            Text(
                text = "Viewing: $filterLabel",
                fontSize = 11.sp,
                color = TextSecondaryLight,
                modifier = Modifier.padding(horizontal = 20.dp, vertical = 2.dp)
            )
        }
    }
}

// ----------------------------------------------------
// 2. Overview Metrics Section
// ----------------------------------------------------
@Composable
private fun OverviewMetricsSection(overview: DashboardOverviewDto) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text("Real-Time Overview", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)

        // Row 1: Members & Growth
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard(
                label = "Total Members",
                value = "${overview.total_members}",
                subtext = "+${overview.new_members} new this period",
                icon = Icons.Outlined.People,
                iconColor = ConnectlyBlue,
                modifier = Modifier.weight(1f)
            )
            MetricCard(
                label = "Member Growth",
                value = "${overview.member_growth}%",
                subtext = if (overview.member_growth >= 0) "▲ Positive trend" else "▼ Decline",
                icon = Icons.Outlined.TrendingUp,
                iconColor = if (overview.member_growth >= 0) Color(0xFF10B981) else Color(0xFFEF4444),
                modifier = Modifier.weight(1f)
            )
        }

        // Row 2: Active & Online
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard(
                label = "Active Members",
                value = "${overview.active_members}",
                subtext = "Interacted recently",
                icon = Icons.Outlined.PersonPinCircle,
                iconColor = Color(0xFF6366F1),
                modifier = Modifier.weight(1f)
            )
            MetricCard(
                label = "Online Now",
                value = "${overview.online_members}",
                subtext = "Live active sessions",
                icon = Icons.Outlined.Wifi,
                iconColor = Color(0xFF10B981),
                modifier = Modifier.weight(1f)
            )
        }

        // Row 3: Posts & Engagement
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard(
                label = "Total Posts",
                value = "${overview.total_posts}",
                subtext = "${overview.pending_posts} pending approval",
                icon = Icons.Outlined.Article,
                iconColor = Color(0xFF0284C7),
                modifier = Modifier.weight(1f)
            )
            MetricCard(
                label = "Engagement Rate",
                value = "${overview.engagement_rate}%",
                subtext = "${overview.total_reactions} reacts • ${overview.total_comments} comments",
                icon = Icons.Outlined.ThumbUp,
                iconColor = Color(0xFFF59E0B),
                modifier = Modifier.weight(1f)
            )
        }

        // Row 4: Join Requests & Reports Warning
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            MetricCard(
                label = "Join Requests",
                value = "${overview.pending_join_requests}",
                subtext = if (overview.pending_join_requests > 0) "Needs review" else "All caught up",
                icon = Icons.Outlined.HowToReg,
                iconColor = if (overview.pending_join_requests > 0) Color(0xFFD97706) else Color(0xFF64748B),
                modifier = Modifier.weight(1f)
            )
            MetricCard(
                label = "Reported Content",
                value = "${overview.reported_content}",
                subtext = if (overview.reported_content > 0) "Requires moderation" else "Clean community",
                icon = Icons.Outlined.Shield,
                iconColor = if (overview.reported_content > 0) Color(0xFFEF4444) else Color(0xFF10B981),
                modifier = Modifier.weight(1f)
            )
        }
    }
}

// ----------------------------------------------------
// 3. Charts Visualizer Section
// ----------------------------------------------------
@Composable
private fun ChartsVisualizerSection(charts: DashboardChartsDto) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Text("Activity & Growth Trends", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)

        // Member Growth Trend Chart Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
        ) {
            Column(modifier = Modifier.padding(14.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Daily Member Acquisition", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                    Surface(shape = RoundedCornerShape(8.dp), color = Color(0xFFEFF6FF)) {
                        Text(
                            "${charts.daily_member_growth.sumOf { it.count }} new",
                            fontSize = 11.sp,
                            color = ConnectlyBlue,
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                        )
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))

                // Simple Bar Chart representation
                if (charts.daily_member_growth.isNotEmpty()) {
                    val maxVal = maxOf(1L, charts.daily_member_growth.maxOf { it.count })
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(80.dp),
                        horizontalArrangement = Arrangement.spacedBy(4.dp),
                        verticalAlignment = Alignment.Bottom
                    ) {
                        charts.daily_member_growth.takeLast(14).forEach { point ->
                            val heightFraction = (point.count.toFloat() / maxVal.toFloat()).coerceIn(0.08f, 1f)
                            Box(
                                modifier = Modifier
                                    .weight(1f)
                                    .fillMaxHeight(heightFraction)
                                    .clip(RoundedCornerShape(topStart = 4.dp, topEnd = 4.dp))
                                    .background(if (point.count > 0) ConnectlyBlue else Color(0xFFE2E8F0))
                            )
                        }
                    }
                }
            }
        }

        // Post & Comment Activity Comparison
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
        ) {
            Column(modifier = Modifier.padding(14.dp)) {
                Text("Discussions & Comment Velocity", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                Spacer(modifier = Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceAround
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("${charts.post_activity.sumOf { it.count }}", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = ConnectlyBlue)
                        Text("Posts Published", fontSize = 11.sp, color = TextSecondaryLight)
                    }
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("${charts.comment_activity.sumOf { it.count }}", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = Color(0xFF10B981))
                        Text("Comments Added", fontSize = 11.sp, color = TextSecondaryLight)
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// 4. Top Performing Posts Section
// ----------------------------------------------------
@Composable
private fun TopPerformingPostsSection(posts: List<TopPerformingPostDto>) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text("Top-Performing Content", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)

        posts.forEachIndexed { index, post ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(10.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Row(
                    modifier = Modifier.padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Surface(
                        shape = CircleShape,
                        color = Color(0xFFEFF6FF),
                        modifier = Modifier.size(28.dp)
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Text("#${index + 1}", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = ConnectlyBlue)
                        }
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(post.content, maxLines = 2, overflow = TextOverflow.Ellipsis, fontSize = 13.sp, fontWeight = FontWeight.Medium)
                        Spacer(modifier = Modifier.height(4.dp))
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("by ${post.author?.name ?: "Member"}", fontSize = 11.sp, color = TextSecondaryLight)
                            Text("•", fontSize = 11.sp, color = TextSecondaryLight)
                            Text("${post.total_engagement} interactions", fontSize = 11.sp, color = ConnectlyBlue, fontWeight = FontWeight.SemiBold)
                        }
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// 5. Most Active Members Section
// ----------------------------------------------------
@Composable
private fun MostActiveMembersSection(members: List<ActiveMemberItemDto>) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text("Most Active Contributors", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)

        members.forEach { member ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(10.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
            ) {
                Row(
                    modifier = Modifier.padding(10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Box(
                        modifier = Modifier
                            .size(36.dp)
                            .clip(CircleShape)
                            .background(Color(0xFFE2E8F0)),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(member.name.take(1).uppercase(), fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(member.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                        Text("${member.role.replaceFirstChar { it.uppercase() }} • Joined ${member.joined_at ?: ""}", fontSize = 11.sp, color = TextSecondaryLight)
                    }
                    Surface(shape = RoundedCornerShape(8.dp), color = Color(0xFFF1F5F9)) {
                        Text("${member.posts_count} posts", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// 6. Member Retention & Engagement Health
// ----------------------------------------------------
@Composable
private fun MemberRetentionSection(retention: MemberRetentionDto, engagementRate: Double) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text("Community Health & Retention", fontWeight = FontWeight.Bold, fontSize = 15.sp)
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("Member Retention Rate", fontSize = 13.sp, color = TextSecondaryLight)
                Text("${retention.retention_rate}%", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF10B981))
            }
            LinearProgressIndicator(
                progress = { (retention.retention_rate / 100.0).toFloat().coerceIn(0f, 1f) },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(6.dp)
                    .clip(RoundedCornerShape(3.dp)),
                color = Color(0xFF10B981),
                trackColor = Color(0xFFF1F5F9)
            )

            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("Engagement Intensity", fontSize = 13.sp, color = TextSecondaryLight)
                Text("${engagementRate}%", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = ConnectlyBlue)
            }
            LinearProgressIndicator(
                progress = { (engagementRate / 100.0).toFloat().coerceIn(0f, 1f) },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(6.dp)
                    .clip(RoundedCornerShape(3.dp)),
                color = ConnectlyBlue,
                trackColor = Color(0xFFF1F5F9)
            )
        }
    }
}

// ----------------------------------------------------
// 7. Quick Management Actions
// ----------------------------------------------------
@Composable
private fun QuickActionsSection(
    actions: List<DashboardQuickActionDto>,
    onActionClick: (String, String) -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        Text("Management Quick Actions", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)

        actions.chunked(2).forEach { rowActions ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                rowActions.forEach { action ->
                    QuickActionCard(
                        action = action,
                        onClick = { onActionClick(action.id, action.route) },
                        modifier = Modifier.weight(1f)
                    )
                }
                if (rowActions.size == 1) {
                    Spacer(modifier = Modifier.weight(1f))
                }
            }
        }
    }
}

// Helper Components
@Composable
private fun MetricCard(
    label: String,
    value: String,
    subtext: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    iconColor: Color,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(label, fontSize = 12.sp, color = TextSecondaryLight)
                Icon(icon, contentDescription = null, tint = iconColor, modifier = Modifier.size(18.dp))
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(value, fontWeight = FontWeight.Bold, fontSize = 18.sp, color = TextPrimaryLight)
            Spacer(modifier = Modifier.height(2.dp))
            Text(subtext, fontSize = 10.sp, color = TextSecondaryLight, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
    }
}

@Composable
private fun QuickActionCard(
    action: DashboardQuickActionDto,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.clickable { onClick() },
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(action.title, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                val badge = action.badge
                if (!badge.isNullOrBlank()) {
                    Surface(shape = RoundedCornerShape(10.dp), color = Color(0xFFEF4444)) {
                        Text(badge, color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
                    }
                }
            }
            Text(action.description, fontSize = 11.sp, color = TextSecondaryLight, maxLines = 2, overflow = TextOverflow.Ellipsis)
        }
    }
}

@Composable
private fun CustomDateRangeDialog(
    onDismiss: () -> Unit,
    onSubmit: (startDate: String, endDate: String) -> Unit
) {
    var startDate by remember { mutableStateOf("2026-01-01") }
    var endDate by remember { mutableStateOf("2026-11-04") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Custom Date Filter", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = startDate,
                    onValueChange = { startDate = it },
                    label = { Text("Start Date (YYYY-MM-DD)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = endDate,
                    onValueChange = { endDate = it },
                    label = { Text("End Date (YYYY-MM-DD)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(startDate.trim(), endDate.trim()) },
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                Text("Apply Filter")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

