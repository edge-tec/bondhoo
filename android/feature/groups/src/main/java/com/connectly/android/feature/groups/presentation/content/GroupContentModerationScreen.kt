package com.connectly.android.feature.groups.presentation.content

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Shield
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
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.network.api.GroupReportDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupContentModerationScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupContentViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(groupId) {
        viewModel.handleIntent(GroupContentIntent.LoadReports(groupId))
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Content Moderation", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        Text("${state.reports.size} reports recorded", fontSize = 12.sp, color = TextSecondaryLight)
                    }
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
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            state.actionMessage?.let { msg ->
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFDCFCE7))) {
                        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.CheckCircle, contentDescription = null, tint = Color(0xFF16A34A))
                            Spacer(Modifier.width(8.dp))
                            Text(msg, color = Color(0xFF166534), fontSize = 13.sp)
                        }
                    }
                }
            }

            if (state.reports.isEmpty()) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(48.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(Icons.Outlined.Shield, contentDescription = null, modifier = Modifier.size(56.dp), tint = Color(0xFF16A34A))
                        Spacer(Modifier.height(12.dp))
                        Text("No reported content", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text("The community is healthy and clean.", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                }
            } else {
                items(state.reports, key = { it.id }) { report ->
                    ReportCard(
                        report = report,
                        onDismiss = {
                            viewModel.handleIntent(
                                GroupContentIntent.ActionReport(
                                    reportId = report.id,
                                    status = "dismissed",
                                    decisionNote = "Dismissed by moderator as non-violating",
                                    removeContent = false
                                )
                            )
                        },
                        onRemoveContent = {
                            viewModel.handleIntent(
                                GroupContentIntent.ActionReport(
                                    reportId = report.id,
                                    status = "action_taken",
                                    decisionNote = "Violating content removed by moderator",
                                    removeContent = true
                                )
                            )
                        }
                    )
                }
            }
        }
    }
}

@Composable
private fun ReportCard(
    report: GroupReportDto,
    onDismiss: () -> Unit,
    onRemoveContent: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(4.dp))
                            .background(Color(0xFFFEE2E2))
                            .padding(horizontal = 6.dp, vertical = 2.dp)
                    ) {
                        Text(report.reason_category.uppercase(), color = Color(0xFFDC2626), fontSize = 10.sp, fontWeight = FontWeight.Bold)
                    }
                    Spacer(Modifier.width(8.dp))
                    Text("Report #${report.id}", fontSize = 12.sp, color = TextSecondaryLight)
                }
                Text(report.status.uppercase(), fontSize = 10.sp, fontWeight = FontWeight.Bold, color = if (report.status == "pending") Color(0xFFD97706) else Color(0xFF16A34A))
            }

            if (!report.description.isNullOrBlank()) {
                Text("Details: ${report.description}", fontSize = 13.sp, color = TextPrimaryLight)
            }

            report.reporter?.let { rep ->
                Text("Reported by @${rep.username}", fontSize = 11.sp, color = TextSecondaryLight)
            }

            if (report.status == "pending") {
                HorizontalDivider()
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedButton(onClick = onDismiss) {
                        Text("Dismiss")
                    }
                    Spacer(Modifier.width(8.dp))
                    Button(
                        onClick = onRemoveContent,
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                    ) {
                        Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Remove Post")
                    }
                }
            }
        }
    }
}

