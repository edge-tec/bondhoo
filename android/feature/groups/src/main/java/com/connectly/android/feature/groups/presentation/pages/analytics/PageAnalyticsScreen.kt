package com.connectly.android.feature.groups.presentation.pages.analytics

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.BarChart
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.TrendingUp
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
                                ProgressMetricRow("Organic Reach", 380000, 450000, ConnectlyElectricBlue)
                                ProgressMetricRow("Total Impressions", 890000, 1000000, Color(0xFF10B981))
                                ProgressMetricRow("Page Views", 32000, 50000, Color(0xFF8B5CF6))
                            }
                        }
                    }
                    1 -> { // Audience
                        item {
                            AnalyticsSectionCard("Audience Demographics & Growth") {
                                Text("Net Follower Growth: +${analytics?.new_followers ?: 2450}", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text("Top Locations: Dhaka (45%), Chittagong (22%), Sylhet (12%)", fontSize = 12.sp, color = TextSecondaryLight)
                                ProgressMetricRow("Active Audience Ratio", 72, 100, ConnectlyElectricBlue)
                            }
                        }
                    }
                    2 -> { // Content
                        item {
                            AnalyticsSectionCard("Post Engagement Breakdown") {
                                StatRow("Post Engagement", "${analytics?.post_engagement ?: 68000}")
                                StatRow("Reactions", "${analytics?.total_reactions ?: 42000}")
                                StatRow("Comments", "${analytics?.total_comments ?: 18000}")
                                StatRow("Shares", "${analytics?.total_shares ?: 8000}")
                                StatRow("Link Clicks", "${analytics?.link_clicks ?: 9400}")
                            }
                        }
                    }
                    else -> { // Video & Reels
                        item {
                            AnalyticsSectionCard("Video & Reels Performance") {
                                StatRow("Video Views", "${analytics?.video_views ?: 125000}")
                                StatRow("Reels Plays", "${analytics?.reels_views ?: 310000}")
                                StatRow("Story Views", "${analytics?.story_views ?: 45000}")
                                StatRow("Live Stream Views", "${analytics?.live_views ?: 12000}")
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
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
            content()
        }
    }
}

@Composable
private fun ProgressMetricRow(label: String, current: Int, max: Int, color: Color) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(label, fontSize = 12.sp, color = TextSecondaryLight)
            Text("$current", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        }
        Spacer(modifier = Modifier.height(4.dp))
        LinearProgressIndicator(
            progress = { (current.toFloat() / max.toFloat()).coerceIn(0f, 1f) },
            modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
            color = color,
            trackColor = color.copy(alpha = 0.15f)
        )
    }
}

@Composable
private fun StatRow(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, fontSize = 13.sp, color = TextSecondaryLight)
        Text(value, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = TextPrimaryLight)
    }
}
