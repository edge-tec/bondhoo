package com.connectly.android.feature.notifications.presentation.notifications

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Settings
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
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationType

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationsScreen(
    onNavigateToSettings: () -> Unit = {},
    onNotificationClick: (NotificationItem) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: NotificationsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Notifications",
                        fontWeight = FontWeight.Bold,
                        fontSize = 24.sp,
                        color = TextPrimaryLight
                    )
                },
                actions = {
                    IconButton(onClick = onNavigateToSettings) {
                        Icon(Icons.Default.Settings, contentDescription = "Notification Settings", tint = TextPrimaryLight)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Segmented Filter Chips: All, Likes, Comments, Follows (Matching Screen 9)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                CategoryChip(
                    label = "All",
                    isSelected = state.selectedCategory == "all",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectCategory("all")) }
                )
                CategoryChip(
                    label = "Likes",
                    isSelected = state.selectedCategory == "likes",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectCategory("likes")) }
                )
                CategoryChip(
                    label = "Comments",
                    isSelected = state.selectedCategory == "comments",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectCategory("comments")) }
                )
                CategoryChip(
                    label = "Follows",
                    isSelected = state.selectedCategory == "follows",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectCategory("follows")) }
                )
            }

            LazyColumn(
                modifier = Modifier.fillMaxSize()
            ) {
                items(state.notifications, key = { it.id }) { item ->
                    NotificationRow(
                        item = item,
                        onClick = {
                            viewModel.handleIntent(NotificationsIntent.MarkRead(item.id))
                            onNotificationClick(item)
                        },
                        onAcceptFriend = {
                            viewModel.handleIntent(NotificationsIntent.RespondFriendRequest(item.id, item.actor?.id ?: 1L, true))
                        },
                        onDeclineFriend = {
                            viewModel.handleIntent(NotificationsIntent.RespondFriendRequest(item.id, item.actor?.id ?: 1L, false))
                        }
                    )
                    HorizontalDivider(
                        color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
                        thickness = 0.5.dp
                    )
                }
            }
        }
    }
}

@Composable
private fun CategoryChip(
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
        contentPadding = PaddingValues(horizontal = 18.dp, vertical = 6.dp),
        modifier = modifier
    ) {
        Text(
            text = label,
            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
            fontSize = 13.sp
        )
    }
}

@Composable
private fun NotificationRow(
    item: NotificationItem,
    onClick: () -> Unit,
    onAcceptFriend: () -> Unit,
    onDeclineFriend: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .background(if (!item.isRead) ConnectlyBlue.copy(alpha = 0.05f) else Color.Transparent)
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Avatar with Badged Action Icon
        Box(modifier = Modifier.size(50.dp)) {
            AsyncImage(
                model = item.avatarUrl ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                contentDescription = null,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .fillMaxSize()
                    .clip(CircleShape)
            )

            item.badgeIcon?.let { badge ->
                Box(
                    modifier = Modifier
                        .size(18.dp)
                        .align(Alignment.BottomEnd)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.surface),
                    contentAlignment = Alignment.Center
                ) {
                    Text(text = badge, fontSize = 11.sp)
                }
            }
        }

        Spacer(modifier = Modifier.width(14.dp))

        // Notification Message & Time
        Column(
            modifier = Modifier.weight(1f)
        ) {
            Text(
                text = item.message,
                fontSize = 14.sp,
                fontWeight = if (!item.isRead) FontWeight.SemiBold else FontWeight.Normal,
                color = TextPrimaryLight
            )
        }

        // Action Buttons for Friend Request: ✔ (blue) and ✖ (red/gray)
        if (item.actionRequired) {
            Row(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                IconButton(
                    onClick = onAcceptFriend,
                    modifier = Modifier
                        .size(32.dp)
                        .clip(CircleShape)
                        .background(ConnectlyBlue)
                ) {
                    Icon(
                        imageVector = Icons.Default.Check,
                        contentDescription = "Accept",
                        tint = Color.White,
                        modifier = Modifier.size(16.dp)
                    )
                }

                IconButton(
                    onClick = onDeclineFriend,
                    modifier = Modifier
                        .size(32.dp)
                        .clip(CircleShape)
                        .background(Color(0xFFEF4444).copy(alpha = 0.85f))
                ) {
                    Icon(
                        imageVector = Icons.Default.Close,
                        contentDescription = "Decline",
                        tint = Color.White,
                        modifier = Modifier.size(16.dp)
                    )
                }
            }
        }
    }
}
