package com.connectly.android.feature.notifications.presentation.notifications

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.NotificationItem
import com.connectly.android.core.model.NotificationType

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationsScreen(
    onNavigateBack: () -> Unit = {},
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
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = "Notifications",
                            fontWeight = FontWeight.Bold,
                            fontSize = 22.sp,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        if (state.unreadCount > 0) {
                            Spacer(modifier = Modifier.width(8.dp))
                            Box(
                                modifier = Modifier
                                    .size(22.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFFEF4444)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = if (state.unreadCount > 99) "99+" else "${state.unreadCount}",
                                    color = Color.White,
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back",
                            tint = MaterialTheme.colorScheme.onSurface
                        )
                    }
                },
                actions = {
                    if (state.unreadCount > 0) {
                        IconButton(onClick = { viewModel.handleIntent(NotificationsIntent.MarkAllRead) }) {
                            Icon(
                                imageVector = Icons.Default.DoneAll,
                                contentDescription = "Mark all as read",
                                tint = ConnectlyBlue
                            )
                        }
                    }
                    IconButton(onClick = onNavigateToSettings) {
                        Icon(
                            imageVector = Icons.Default.Settings,
                            contentDescription = "Notification Settings",
                            tint = MaterialTheme.colorScheme.onSurface
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Horizontal Filter & Category Row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // All / Unread Filter Pills
                FilterChip(
                    selected = state.selectedFilter == "all",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectFilter("all")) },
                    label = { Text("All", fontSize = 13.sp) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = ConnectlyBlue,
                        selectedLabelColor = Color.White
                    )
                )

                FilterChip(
                    selected = state.selectedFilter == "unread",
                    onClick = { viewModel.handleIntent(NotificationsIntent.SelectFilter("unread")) },
                    label = { Text("Unread", fontSize = 13.sp) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = ConnectlyBlue,
                        selectedLabelColor = Color.White
                    )
                )

                VerticalDivider(
                    modifier = Modifier
                        .height(28.dp)
                        .padding(horizontal = 2.dp),
                    color = MaterialTheme.colorScheme.outlineVariant
                )

                // Category Pills
                listOf(
                    "all" to "All Categories",
                    "likes" to "Likes",
                    "comments" to "Comments",
                    "follows" to "Follows",
                    "groups" to "Groups",
                    "live" to "Live"
                ).forEach { (catKey, label) ->
                    val isSelected = state.selectedCategory == catKey
                    FilterChip(
                        selected = isSelected,
                        onClick = { viewModel.handleIntent(NotificationsIntent.SelectCategory(catKey)) },
                        label = { Text(label, fontSize = 13.sp) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                            selectedLabelColor = ConnectlyBlue
                        )
                    )
                }
            }

            // Main List & States
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .weight(1f),
                contentAlignment = Alignment.Center
            ) {
                when {
                    state.isLoading && state.notifications.isEmpty() -> {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }

                    state.errorMessage != null && state.notifications.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp),
                            modifier = Modifier.padding(24.dp)
                        ) {
                            Text(
                                text = state.errorMessage ?: "Failed to load notifications",
                                color = MaterialTheme.colorScheme.error,
                                textAlign = TextAlign.Center
                            )
                            Button(onClick = { viewModel.handleIntent(NotificationsIntent.Refresh) }) {
                                Text("Retry")
                            }
                        }
                    }

                    state.notifications.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp),
                            modifier = Modifier.padding(32.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(72.dp)
                                    .clip(CircleShape)
                                    .background(ConnectlyBlue.copy(alpha = 0.1f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.NotificationsNone,
                                    contentDescription = null,
                                    tint = ConnectlyBlue,
                                    modifier = Modifier.size(36.dp)
                                )
                            }
                            Text(
                                text = if (state.selectedFilter == "unread") "No unread notifications" else "No notifications yet",
                                fontWeight = FontWeight.Bold,
                                fontSize = 18.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Text(
                                text = "When people interact with your posts, comments, or groups, you'll see them right here in real time.",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                fontSize = 13.sp,
                                textAlign = TextAlign.Center
                            )
                        }
                    }

                    else -> {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize()
                        ) {
                            items(state.notifications, key = { it.id }) { item ->
                                NotificationRow(
                                    item = item,
                                    onClick = {
                                        if (!item.isRead) {
                                            viewModel.handleIntent(NotificationsIntent.MarkRead(item.id))
                                        }
                                        onNotificationClick(item)
                                    },
                                    onDelete = {
                                        viewModel.handleIntent(NotificationsIntent.DeleteNotification(item.id))
                                    },
                                    onAcceptFriend = {
                                        viewModel.handleIntent(
                                            NotificationsIntent.RespondFriendRequest(item.id, item.actor?.id ?: 1L, true)
                                        )
                                    },
                                    onDeclineFriend = {
                                        viewModel.handleIntent(
                                            NotificationsIntent.RespondFriendRequest(item.id, item.actor?.id ?: 1L, false)
                                        )
                                    }
                                )
                                HorizontalDivider(
                                    color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f),
                                    thickness = 0.5.dp
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun NotificationRow(
    item: NotificationItem,
    onClick: () -> Unit,
    onDelete: () -> Unit,
    onAcceptFriend: () -> Unit,
    onDeclineFriend: () -> Unit,
    modifier: Modifier = Modifier
) {
    val (badgeIcon, badgeBg, badgeTint) = resolveBadgeDetails(item.type)

    Row(
        modifier = modifier
            .fillMaxWidth()
            .background(if (!item.isRead) ConnectlyBlue.copy(alpha = 0.06f) else Color.Transparent)
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Avatar with overlaid activity badge icon
        Box(modifier = Modifier.size(52.dp)) {
            ConnectlyAvatar(
                imageUrl = item.avatarUrl ?: item.actor?.avatar,
                name = item.actor?.name ?: "Connectly User",
                size = 48.dp
            )

            Box(
                modifier = Modifier
                    .size(20.dp)
                    .align(Alignment.BottomEnd)
                    .clip(CircleShape)
                    .background(badgeBg),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = badgeIcon,
                    contentDescription = null,
                    tint = badgeTint,
                    modifier = Modifier.size(12.dp)
                )
            }
        }

        Spacer(modifier = Modifier.width(14.dp))

        // Message and time
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = item.message,
                fontSize = 14.sp,
                fontWeight = if (!item.isRead) FontWeight.SemiBold else FontWeight.Normal,
                color = MaterialTheme.colorScheme.onSurface,
                maxLines = 3,
                overflow = TextOverflow.Ellipsis
            )
            Spacer(modifier = Modifier.height(3.dp))
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    text = item.timeAgo,
                    fontSize = 12.sp,
                    color = if (!item.isRead) ConnectlyBlue else MaterialTheme.colorScheme.onSurfaceVariant
                )
                if (!item.isRead) {
                    Box(
                        modifier = Modifier
                            .size(6.dp)
                            .clip(CircleShape)
                            .background(ConnectlyBlue)
                    )
                }
            }

            // Friend Request inline actions
            if (item.actionRequired) {
                Spacer(modifier = Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(
                        onClick = onAcceptFriend,
                        shape = RoundedCornerShape(16.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                        modifier = Modifier.height(32.dp)
                    ) {
                        Text("Confirm", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                    OutlinedButton(
                        onClick = onDeclineFriend,
                        shape = RoundedCornerShape(16.dp),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                        modifier = Modifier.height(32.dp)
                    ) {
                        Text("Delete", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
            }
        }

        // Delete individual notification button
        IconButton(
            onClick = onDelete,
            modifier = Modifier.size(32.dp)
        ) {
            Icon(
                imageVector = Icons.Default.Close,
                contentDescription = "Delete notification",
                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f),
                modifier = Modifier.size(16.dp)
            )
        }
    }
}

private fun resolveBadgeDetails(type: NotificationType): Triple<ImageVector, Color, Color> {
    return when (type) {
        NotificationType.LIKE, NotificationType.REACTION -> Triple(
            Icons.Default.Favorite,
            Color(0xFFF43F5E),
            Color.White
        )
        NotificationType.COMMENT -> Triple(
            Icons.Default.ChatBubble,
            Color(0xFF0EA5E9),
            Color.White
        )
        NotificationType.FRIEND_REQUEST, NotificationType.FOLLOWER -> Triple(
            Icons.Default.PersonAdd,
            Color(0xFF6366F1),
            Color.White
        )
        NotificationType.GROUP_INVITE -> Triple(
            Icons.Default.Groups,
            Color(0xFF8B5CF6),
            Color.White
        )
        NotificationType.LIVE_ALERT -> Triple(
            Icons.Default.Videocam,
            Color(0xFFEF4444),
            Color.White
        )
        NotificationType.EVENT_REMINDER -> Triple(
            Icons.Default.Event,
            Color(0xFFF59E0B),
            Color.White
        )
        NotificationType.MESSENGER, NotificationType.CALL -> Triple(
            Icons.Default.Chat,
            Color(0xFF10B981),
            Color.White
        )
        NotificationType.SECURITY -> Triple(
            Icons.Default.Shield,
            Color(0xFFDC2626),
            Color.White
        )
        else -> Triple(
            Icons.Default.Notifications,
            Color(0xFF64748B),
            Color.White
        )
    }
}
