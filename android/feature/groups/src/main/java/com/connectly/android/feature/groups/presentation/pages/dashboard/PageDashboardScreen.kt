package com.connectly.android.feature.groups.presentation.pages.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*

data class QuickActionItem(
    val title: String,
    val icon: androidx.compose.ui.graphics.vector.ImageVector,
    val color: Color,
    val routeKey: String
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageDashboardScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    onNavigateToAnalytics: (Long) -> Unit = {},
    onNavigateToContentManager: (Long) -> Unit = {},
    onNavigateToRoles: (Long) -> Unit = {},
    onNavigateToInbox: (Long) -> Unit = {},
    onNavigateToSettings: (Long) -> Unit = {},
    onNavigateToMonetization: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageDashboardViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.handleIntent(PageDashboardIntent.LoadDashboard(pageId))
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Professional Dashboard", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.handleIntent(PageDashboardIntent.Refresh) }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
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
        } else if (state.page == null && state.errorMessage != null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(24.dp),
                contentAlignment = Alignment.Center
            ) {
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(
                        modifier = Modifier.padding(24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Icon(
                            Icons.Outlined.ErrorOutline,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.error,
                            modifier = Modifier.size(48.dp)
                        )
                        Text(
                            text = "Dashboard Unavailable",
                            fontWeight = FontWeight.Bold,
                            fontSize = 18.sp
                        )
                        Text(
                            text = state.errorMessage ?: "Could not load the Professional Dashboard for this page.",
                            fontSize = 13.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = androidx.compose.ui.text.style.TextAlign.Center
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            OutlinedButton(onClick = onNavigateBack) {
                                Text("Go Back")
                            }
                            Button(
                                onClick = { viewModel.handleIntent(PageDashboardIntent.Refresh) },
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                            ) {
                                Text("Retry")
                            }
                        }
                    }
                }
            }
        } else {
            val page = state.page
            val analytics = state.analytics
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                if (state.errorMessage != null) {
                    item {
                        Card(
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                            modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = state.errorMessage ?: "Failed to refresh analytics",
                                    color = MaterialTheme.colorScheme.onErrorContainer,
                                    fontSize = 12.sp,
                                    modifier = Modifier.weight(1f)
                                )
                                TextButton(onClick = { viewModel.handleIntent(PageDashboardIntent.Refresh) }) {
                                    Text("Retry", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onErrorContainer)
                                }
                            }
                        }
                    }
                }
                // Page Overview Header
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    ) {
                        Row(
                            modifier = Modifier.padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            AsyncImage(
                                model = page?.resolvedAvatar,
                                contentDescription = page?.name,
                                modifier = Modifier.size(56.dp).clip(CircleShape),
                                contentScale = ContentScale.Crop
                            )
                            Spacer(modifier = Modifier.width(12.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text(
                                        text = page?.name ?: "Business Page",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 17.sp
                                    )
                                    if (page?.is_verified == true) {
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Icon(
                                            Icons.Default.CheckCircle,
                                            contentDescription = "Verified",
                                            tint = ConnectlyElectricBlue,
                                            modifier = Modifier.size(16.dp)
                                        )
                                    }
                                }
                                Text(
                                    text = "@${page?.resolvedUsername ?: "page"} • ${page?.category ?: "Business"}",
                                    fontSize = 12.sp,
                                    color = TextSecondaryLight
                                )
                            }
                        }
                    }
                }

                // Date Range Filters
                item {
                    Column {
                        Text("Performance Overview", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            listOf(
                                "1d" to "Today",
                                "7d" to "Last 7 Days",
                                "28d" to "Last 28 Days",
                                "30d" to "Last 30 Days",
                                "90d" to "Last 90 Days"
                            ).forEach { (rangeKey, label) ->
                                FilterChip(
                                    selected = state.selectedRange == rangeKey,
                                    onClick = { viewModel.handleIntent(PageDashboardIntent.SelectRange(rangeKey)) },
                                    label = { Text(label, fontSize = 12.sp) }
                                )
                            }
                        }
                    }
                }

                // Key Performance Metrics Grid
                item {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            val reach = analytics?.resolvedReach ?: 0
                            val reachStr = if (reach >= 1000) "${reach / 1000}K" else "$reach"
                            MetricCard(
                                title = "Total Reach",
                                value = reachStr,
                                subtitle = "Impressions: ${analytics?.resolvedImpressions ?: 0}",
                                icon = Icons.Outlined.Visibility,
                                color = ConnectlyElectricBlue,
                                modifier = Modifier.weight(1f)
                            )
                            val views = analytics?.resolvedPageViews ?: 0
                            val viewsStr = if (views >= 1000) "${views / 1000}K" else "$views"
                            MetricCard(
                                title = "Page Views",
                                value = viewsStr,
                                subtitle = "Reactions: ${analytics?.resolvedReactions ?: 0}",
                                icon = Icons.Outlined.TrendingUp,
                                color = Color(0xFF10B981),
                                modifier = Modifier.weight(1f)
                            )
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            MetricCard(
                                title = "New Followers",
                                value = "+${analytics?.resolvedNewFollowers ?: 0}",
                                subtitle = "Total: ${page?.followers_count ?: analytics?.resolvedFollowers ?: 0}",
                                icon = Icons.Outlined.People,
                                color = Color(0xFF8B5CF6),
                                modifier = Modifier.weight(1f)
                            )
                            MetricCard(
                                title = "Engagement Rate",
                                value = "${analytics?.resolvedEngagementRate ?: 0.0}%",
                                subtitle = "Period: ${state.selectedRange}",
                                icon = Icons.Outlined.Favorite,
                                color = Color(0xFFEC4899),
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }
                }

                // Quick Management Tools Section
                item {
                    Text("Management Tools & Shortcuts", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                item {
                    val actions = listOf(
                        QuickActionItem("Content Hub", Icons.Outlined.Article, ConnectlyElectricBlue, "content"),
                        QuickActionItem("Page Insights", Icons.Outlined.BarChart, Color(0xFF10B981), "analytics"),
                        QuickActionItem("Team & Roles", Icons.Outlined.AdminPanelSettings, Color(0xFF8B5CF6), "roles"),
                        QuickActionItem("Page Inbox", Icons.Outlined.Inbox, Color(0xFFF59E0B), "inbox"),
                        QuickActionItem("Monetization", Icons.Outlined.MonetizationOn, Color(0xFFEC4899), "monetization"),
                        QuickActionItem("Page Settings", Icons.Outlined.Settings, Color(0xFF64748B), "settings")
                    )

                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        actions.chunked(2).forEach { rowActions ->
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                rowActions.forEach { action ->
                                    Card(
                                        shape = RoundedCornerShape(14.dp),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                                        modifier = Modifier
                                            .weight(1f)
                                            .clickable {
                                                when (action.routeKey) {
                                                    "content" -> onNavigateToContentManager(pageId)
                                                    "analytics" -> onNavigateToAnalytics(pageId)
                                                    "roles" -> onNavigateToRoles(pageId)
                                                    "inbox" -> onNavigateToInbox(pageId)
                                                    "monetization" -> onNavigateToMonetization(pageId)
                                                    "settings" -> onNavigateToSettings(pageId)
                                                }
                                            }
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(14.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Box(
                                                modifier = Modifier
                                                    .size(42.dp)
                                                    .clip(RoundedCornerShape(10.dp))
                                                    .background(action.color.copy(alpha = 0.12f)),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Icon(action.icon, contentDescription = action.title, tint = action.color)
                                            }
                                            Spacer(modifier = Modifier.width(10.dp))
                                            Text(action.title, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MetricCard(
    title: String,
    value: String,
    subtitle: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    color: Color,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = modifier
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(title, fontSize = 12.sp, color = TextSecondaryLight, fontWeight = FontWeight.Medium)
                Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(18.dp))
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text(value, fontWeight = FontWeight.Bold, fontSize = 20.sp, color = TextPrimaryLight)
            Spacer(modifier = Modifier.height(2.dp))
            Text(subtitle, fontSize = 11.sp, color = color, fontWeight = FontWeight.Medium)
        }
    }
}
