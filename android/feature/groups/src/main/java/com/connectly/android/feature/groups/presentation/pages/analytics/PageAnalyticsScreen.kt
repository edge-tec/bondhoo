package com.connectly.android.feature.groups.presentation.pages.analytics

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardIntent
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageAnalyticsScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageDashboardViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.handleIntent(PageDashboardIntent.LoadDashboard(pageId))
        }
    }

    var selectedTab by remember { mutableIntStateOf(0) } // 0: Overview, 1: Audience, 2: Content, 3: Videos & Reels

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Insights & Analytics", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
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
        } else {
            val analytics = state.analytics
            val summary = analytics?.summary

            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Analytics Tabs
                item {
                    TabRow(selectedTabIndex = selectedTab) {
                        Tab(selected = selectedTab == 0, onClick = { selectedTab = 0 }, text = { Text("Overview", fontSize = 12.sp) })
                        Tab(selected = selectedTab == 1, onClick = { selectedTab = 1 }, text = { Text("Audience", fontSize = 12.sp) })
                        Tab(selected = selectedTab == 2, onClick = { selectedTab = 2 }, text = { Text("Content", fontSize = 12.sp) })
                        Tab(selected = selectedTab == 3, onClick = { selectedTab = 3 }, text = { Text("Video & Reels", fontSize = 12.sp) })
                    }
                }

                when (selectedTab) {
                    0 -> { // Overview
                        item {
                            AnalyticsSectionCard("Reach & Impressions") {
                                val reach = analytics?.resolvedReach ?: 0
                                val impressions = analytics?.resolvedImpressions ?: 0
                                val maxReach = maxOf(reach, impressions, 1)

                                ProgressMetricRow("Unique Reach", reach, maxReach, ConnectlyElectricBlue)
                                ProgressMetricRow("Total Impressions", impressions, maxReach, Color(0xFF10B981))
                                ProgressMetricRow("Engagement Rate", ((analytics?.resolvedEngagementRate ?: 0.0) * 10).toInt(), 1000, Color(0xFF8B5CF6))
                            }
                        }
                    }
                    1 -> { // Audience
                        item {
                            AnalyticsSectionCard("Audience & Follower Growth") {
                                Text("Total Followers: ${analytics?.resolvedFollowers ?: state.page?.followers_count ?: 0}", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                Text("New Followers in Period: +${analytics?.resolvedNewFollowers ?: 0}", fontSize = 13.sp, color = Color(0xFF10B981))
                                Text("Unfollowers: ${summary?.unfollowers ?: 0}", fontSize = 13.sp, color = MaterialTheme.colorScheme.error)
                                Text("Net Growth: +${summary?.net_follower_growth ?: analytics?.resolvedNewFollowers ?: 0}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                    2 -> { // Content
                        item {
                            AnalyticsSectionCard("Post Engagement Breakdown") {
                                StatRow("Total Posts in Period", "${summary?.posts_in_period ?: summary?.total_posts ?: 0}")
                                StatRow("Total Engagements", "${summary?.total_engagements ?: 0}")
                                StatRow("Reactions", "${analytics?.resolvedReactions ?: 0}")
                                StatRow("Comments", "${summary?.comments ?: summary?.total_comments ?: 0}")
                                StatRow("Shares", "${summary?.shares ?: summary?.total_shares ?: 0}")
                                StatRow("Link Clicks", "${summary?.clicks ?: summary?.link_clicks ?: 0}")
                            }
                        }
                    }
                    else -> { // Video & Reels
                        item {
                            AnalyticsSectionCard("Video & Reels Performance") {
                                StatRow("Video Views", "${summary?.video_views ?: analytics?.video_views ?: 0}")
                                StatRow("Reels Plays", "${summary?.reel_views ?: analytics?.reels_views ?: 0}")
                                StatRow("Story Views", "${summary?.story_views ?: analytics?.story_views ?: 0}")
                                StatRow("Scheduled Posts", "${summary?.scheduled_posts ?: 0}")
                                StatRow("Draft Posts", "${summary?.draft_posts ?: 0}")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AnalyticsSectionCard(title: String, content: @Composable ColumnScope.() -> Unit) {
    Card(
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 15.sp)
            content()
        }
    }
}

@Composable
private fun ProgressMetricRow(label: String, value: Int, total: Int, color: Color) {
    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(label, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text("$value", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        }
        val progress = if (total > 0) (value.toFloat() / total.toFloat()).coerceIn(0f, 1f) else 0f
        LinearProgressIndicator(
            progress = { progress },
            modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
            color = color,
            trackColor = color.copy(alpha = 0.2f)
        )
    }
}

@Composable
private fun StatRow(title: String, count: String) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(title, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(count, fontSize = 13.sp, fontWeight = FontWeight.Bold)
    }
}
