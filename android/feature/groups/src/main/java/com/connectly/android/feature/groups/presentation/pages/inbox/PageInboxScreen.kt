package com.connectly.android.feature.groups.presentation.pages.inbox

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Send
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
import com.connectly.android.core.designsystem.theme.ConnectlyElectricBlue
import com.connectly.android.core.network.api.PageInboxMessageDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageInboxScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageInboxViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var selectedConversation by remember { mutableStateOf<PageInboxMessageDto?>(null) }
    var replyText by remember { mutableStateOf("") }

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.loadInbox(pageId)
        }
    }

    if (selectedConversation != null) {
        val conv = selectedConversation!!
        val senderLabel = if (conv.sender_name.isNotBlank()) conv.sender_name else "Visitor #${conv.sender_id}"
        val messageBody = if (conv.text.isNotBlank()) conv.text else conv.last_message_preview ?: "Conversation"

        AlertDialog(
            onDismissRequest = { selectedConversation = null },
            title = { Text("Reply to $senderLabel", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Surface(
                        color = MaterialTheme.colorScheme.surfaceVariant,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = "\"$messageBody\"",
                            fontSize = 12.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                    OutlinedTextField(
                        value = replyText,
                        onValueChange = { replyText = it },
                        label = { Text("Write response...") },
                        maxLines = 3,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (replyText.isNotBlank()) {
                            viewModel.sendMessage(conv.conversation_id.takeIf { it > 0 } ?: conv.id, replyText)
                            selectedConversation = null
                            replyText = ""
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                ) {
                    Icon(Icons.AutoMirrored.Filled.Send, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Send Message")
                }
            },
            dismissButton = {
                TextButton(onClick = { selectedConversation = null }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Messaging Inbox", fontWeight = FontWeight.Bold) },
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
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                item {
                    Text(
                        "Manage customer inquiries, direct chats, and automated quick responses.",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                if (state.messages.isEmpty()) {
                    item {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 40.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "No messages or conversations in inbox yet.",
                                fontSize = 14.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                } else {
                    items(state.messages, key = { it.id }) { msg ->
                        val senderTitle = if (msg.sender_name.isNotBlank()) msg.sender_name else "Visitor #${msg.sender_id}"
                        val messagePreview = if (msg.text.isNotBlank()) msg.text else msg.last_message_preview ?: "New message"

                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { selectedConversation = msg }
                        ) {
                            Row(
                                modifier = Modifier.padding(14.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(46.dp)
                                        .clip(CircleShape)
                                        .background(
                                            if (!msg.is_read) ConnectlyElectricBlue.copy(alpha = 0.15f)
                                            else MaterialTheme.colorScheme.surfaceVariant
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = senderTitle.take(1).uppercase(),
                                        fontWeight = FontWeight.Bold,
                                        color = if (!msg.is_read) ConnectlyElectricBlue else MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }

                                Spacer(modifier = Modifier.width(12.dp))

                                Column(modifier = Modifier.weight(1f)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Text(
                                            text = senderTitle,
                                            fontWeight = if (!msg.is_read) FontWeight.Bold else FontWeight.SemiBold,
                                            fontSize = 14.sp
                                        )
                                        Text(
                                            text = if (msg.timestamp.isNotBlank()) msg.timestamp else msg.last_message_at ?: "",
                                            fontSize = 11.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }

                                    Spacer(modifier = Modifier.height(3.dp))
                                    Text(
                                        text = messagePreview,
                                        fontSize = 12.sp,
                                        color = if (!msg.is_read) MaterialTheme.colorScheme.onSurface else MaterialTheme.colorScheme.onSurfaceVariant,
                                        fontWeight = if (!msg.is_read) FontWeight.SemiBold else FontWeight.Normal,
                                        maxLines = 1
                                    )
                                }

                                if (!msg.is_read) {
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Box(
                                        modifier = Modifier
                                            .size(8.dp)
                                            .clip(CircleShape)
                                            .background(ConnectlyElectricBlue)
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
