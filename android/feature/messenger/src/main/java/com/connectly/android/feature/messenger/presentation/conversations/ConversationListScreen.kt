package com.connectly.android.feature.messenger.presentation.conversations

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Search
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
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.Conversation

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ConversationListScreen(
    onNavigateToChat: (Long) -> Unit,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: ConversationsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Messenger",
                        fontWeight = FontWeight.Bold,
                        fontSize = 22.sp,
                        color = TextPrimaryLight
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { /* Search chats */ }) {
                        Icon(Icons.Default.Search, contentDescription = "Search chats")
                    }
                    IconButton(onClick = { /* Compose new chat */ }) {
                        Icon(Icons.Default.Edit, contentDescription = "New message")
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
            // Segmented Filter Tabs: "All", "Unread 2", "Groups" (Matching Screen 5)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FilterTabButton(
                    label = "All",
                    isSelected = state.selectedFilter == "all",
                    onClick = { viewModel.handleIntent(ConversationsIntent.SelectFilter("all")) }
                )

                FilterTabButton(
                    label = "Unread",
                    badge = "2",
                    isSelected = state.selectedFilter == "unread",
                    onClick = { viewModel.handleIntent(ConversationsIntent.SelectFilter("unread")) }
                )

                FilterTabButton(
                    label = "Groups",
                    isSelected = state.selectedFilter == "groups",
                    onClick = { viewModel.handleIntent(ConversationsIntent.SelectFilter("groups")) }
                )
            }

            // Conversation items list
            LazyColumn(
                modifier = Modifier.fillMaxSize()
            ) {
                items(state.conversations, key = { it.id }) { conversation ->
                    ConversationRowItem(
                        conversation = conversation,
                        onClick = { onNavigateToChat(conversation.id) }
                    )
                    HorizontalDivider(
                        modifier = Modifier.padding(start = 76.dp, end = 16.dp),
                        color = Color(0xFFF1F5F9)
                    )
                }
            }
        }
    }
}

@Composable
private fun FilterTabButton(
    label: String,
    badge: String? = null,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    Button(
        onClick = onClick,
        shape = RoundedCornerShape(20.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = if (isSelected) ConnectlyBlue else Color(0xFFF1F5F9),
            contentColor = if (isSelected) Color.White else TextPrimaryLight
        ),
        contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
        modifier = Modifier.height(34.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(label, fontSize = 13.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium)
            if (badge != null) {
                Spacer(modifier = Modifier.width(4.dp))
                Box(
                    modifier = Modifier
                        .size(16.dp)
                        .clip(CircleShape)
                        .background(if (isSelected) Color.White.copy(alpha = 0.3f) else ConnectlyBlue),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = badge,
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
        }
    }
}

@Composable
private fun ConversationRowItem(
    conversation: Conversation,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Avatar with Online Green Dot or Group Icon
        Box(modifier = Modifier.size(52.dp)) {
            if (conversation.type == "group") {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .clip(CircleShape)
                        .background(Color(0xFFE2E8F0)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.Group,
                        contentDescription = "Group",
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(28.dp)
                    )
                }
            } else {
                AsyncImage(
                    model = conversation.avatar ?: "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                    contentDescription = conversation.title,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .fillMaxSize()
                        .clip(CircleShape)
                )
            }

            // Online indicator dot
            if (conversation.isOnline) {
                Box(
                    modifier = Modifier
                        .size(14.dp)
                        .align(Alignment.BottomEnd)
                        .clip(CircleShape)
                        .background(ConnectlyGreen)
                        .border(2.dp, Color.White, CircleShape)
                )
            }
        }

        Spacer(modifier = Modifier.width(14.dp))

        // Name and Last Message
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = conversation.title ?: "Chat",
                fontWeight = FontWeight.Bold,
                fontSize = 15.sp,
                color = TextPrimaryLight
            )

            Spacer(modifier = Modifier.height(3.dp))

            val lastMsgText = conversation.lastMessage?.body ?: "No messages yet"
            val isTyping = lastMsgText.contains("Typing")

            Text(
                text = "$lastMsgText • 5m",
                fontSize = 13.sp,
                color = if (isTyping) ConnectlyBlue else TextSecondaryLight,
                fontWeight = if (isTyping) FontWeight.SemiBold else FontWeight.Normal,
                maxLines = 1
            )
        }

        // Unread Badge
        if (conversation.unreadCount > 0) {
            Box(
                modifier = Modifier
                    .size(20.dp)
                    .clip(CircleShape)
                    .background(ConnectlyBlue),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "${conversation.unreadCount}",
                    color = Color.White,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}
