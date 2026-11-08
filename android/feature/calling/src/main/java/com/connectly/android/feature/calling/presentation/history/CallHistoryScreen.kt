package com.connectly.android.feature.calling.presentation.history

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.CallMade
import androidx.compose.material.icons.automirrored.filled.CallMissed
import androidx.compose.material.icons.automirrored.filled.CallReceived
import androidx.compose.material.icons.filled.Call
import androidx.compose.material.icons.filled.Videocam
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType
import com.connectly.android.feature.calling.presentation.calling.CallAvatar

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CallHistoryScreen(
    onNavigateBack: () -> Unit,
    onRedialCall: (receiverId: Long?, conversationId: Long?, callType: CallType) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CallHistoryViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Call History", fontWeight = FontWeight.Bold, fontSize = 20.sp) },
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
        when {
            state.isLoading -> {
                Box(modifier = Modifier.fillMaxSize().padding(paddingValues), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = ConnectlyBlue)
                }
            }
            state.callLogs.isEmpty() -> {
                Box(modifier = Modifier.fillMaxSize().padding(paddingValues), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.Call, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(48.dp))
                        Spacer(modifier = Modifier.height(10.dp))
                        Text("No calls yet", fontWeight = FontWeight.SemiBold, fontSize = 16.sp)
                        Text("Your audio, video & group calls will appear here.", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                }
            }
            else -> {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues)
                ) {
                    items(state.callLogs, key = { it.id }) { item ->
                        CallHistoryRow(
                            item = item,
                            onRedial = {
                                onRedialCall(
                                    item.otherUser.id.takeIf { it > 0L },
                                    item.conversationId,
                                    item.callType
                                )
                            }
                        )
                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f), thickness = 0.5.dp)
                    }
                }
            }
        }
    }
}

@Composable
private fun CallHistoryRow(
    item: CallHistoryItem,
    onRedial: () -> Unit,
    modifier: Modifier = Modifier
) {
    val displayTitle = item.title?.takeIf { it.isNotBlank() } ?: item.otherUser.name

    Row(
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onRedial)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        CallAvatar(
            name = displayTitle,
            avatarUrl = item.otherUser.avatar,
            size = 48.dp
        )

        Spacer(modifier = Modifier.width(14.dp))

        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = displayTitle,
                fontWeight = FontWeight.SemiBold,
                fontSize = 16.sp,
                color = if (item.isMissed) Color(0xFFEF4444) else MaterialTheme.colorScheme.onSurface
            )

            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                modifier = Modifier.padding(top = 2.dp)
            ) {
                Icon(
                    imageVector = when {
                        item.isMissed -> Icons.AutoMirrored.Filled.CallMissed
                        item.isIncoming -> Icons.AutoMirrored.Filled.CallReceived
                        else -> Icons.AutoMirrored.Filled.CallMade
                    },
                    contentDescription = null,
                    tint = when {
                        item.isMissed -> Color(0xFFEF4444)
                        item.isIncoming -> ConnectlyGreen
                        else -> ConnectlyBlue
                    },
                    modifier = Modifier.size(16.dp)
                )

                Text(
                    text = item.formattedTime,
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }

        IconButton(onClick = onRedial) {
            Icon(
                imageVector = if (item.callType.isVideo) Icons.Default.Videocam else Icons.Default.Call,
                contentDescription = "Call Back",
                tint = ConnectlyBlue
            )
        }
    }
}
