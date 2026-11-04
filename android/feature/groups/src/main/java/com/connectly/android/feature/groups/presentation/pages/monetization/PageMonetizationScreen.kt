package com.connectly.android.feature.groups.presentation.pages.monetization

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.MonetizationOn
import androidx.compose.material.icons.outlined.Payments
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyElectricBlue
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardIntent
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageMonetizationScreen(
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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Monetization Hub", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Status Card
            item {
                Card(
                    shape = RoundedCornerShape(14.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.CheckCircle, contentDescription = null, tint = Color(0xFF10B981))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Page is Eligible for Monetization", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = Color(0xFF10B981))
                        }
                        Text("Your Page meets all Partner Monetization Policies and content guidelines.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
            }

            // Earnings Overview
            item {
                Text("Earnings & Revenue Summary", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        modifier = Modifier.weight(1f)
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Text("Estimated Earnings", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text("$1,240.50", fontWeight = FontWeight.Bold, fontSize = 20.sp, color = ConnectlyElectricBlue)
                            Text("This month", fontSize = 10.sp, color = Color(0xFF10B981))
                        }
                    }

                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        modifier = Modifier.weight(1f)
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Text("Next Net Payout", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text("$980.00", fontWeight = FontWeight.Bold, fontSize = 20.sp, color = MaterialTheme.colorScheme.onSurface)
                            Text("Payout date: Nov 15", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }
            }

            // Monetization Tools
            item {
                Text("Monetization Products & Tools", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            item {
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        MonetizationToolRow("In-Stream Ads for Videos", "Earn revenue from ads shown in your video content.", true)
                        HorizontalDivider()
                        MonetizationToolRow("Brand Content & Collaborations", "Connect with brands for sponsored posts and video campaigns.", true)
                        HorizontalDivider()
                        MonetizationToolRow("Fan Subscriptions & Stars", "Monetize loyal audience support through direct monthly contributions.", false)
                    }
                }
            }
        }
    }
}

@Composable
private fun MonetizationToolRow(title: String, description: String, active: Boolean) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Text(description, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        Surface(
            color = if (active) Color(0xFFDCFCE7) else Color(0xFFF1F5F9),
            shape = RoundedCornerShape(6.dp)
        ) {
            Text(
                text = if (active) "ACTIVE" else "SETUP",
                fontSize = 10.sp,
                fontWeight = FontWeight.Bold,
                color = if (active) Color(0xFF166534) else Color(0xFF475569),
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
            )
        }
    }
}
