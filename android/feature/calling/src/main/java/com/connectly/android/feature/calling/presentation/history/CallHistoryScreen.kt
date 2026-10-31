package com.connectly.android.feature.calling.presentation.history

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CallHistoryScreen(
    onNavigateBack: () -> Unit,
    onRedialCall: (Long, CallType) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CallHistoryViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Call Logs", fontWeight = FontWeight.Bold, fontSize = 20.sp) },
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
        if (state.isLoading) {
            Box(modifier = Modifier.fillMaxSize().padding(paddingValues), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            ) {
                items(state.callLogs, key = { it.id }) { item ->
                    CallHistoryRow(
                        item = item,
                        onRedial = { onRedialCall(item.otherUser.id, item.callType) }
                    )
                    HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f), thickness = 0.5.dp)
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
    Row(
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onRedial)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        AsyncImage(
            model = item.otherUser.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
            contentDescription = item.otherUser.name,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .size(48.dp)
                .clip(CircleShape)
        )

        Spacer(modifier = Modifier.width(16.dp))

        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = item.otherUser.name,
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
                imageVector = if (item.callType == CallType.VIDEO) Icons.Default.Videocam else Icons.Default.Call,
                contentDescription = "Redial",
                tint = ConnectlyBlue
            )
        }
    }
}

