package com.connectly.android.feature.groups.presentation.pages.inbox

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.outlined.QuestionAnswer
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.network.api.PageInboxMessageDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageInboxScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    var selectedConversation by remember { mutableStateOf<PageInboxMessageDto?>(null) }
    var replyText by remember { mutableStateOf("") }

    val messagesList = remember {
        mutableStateListOf(
            PageInboxMessageDto(id = 1, conversation_id = 501, sender_id = 201, sender_name = "Fahim Shahriar", text = "Hello! Is the Smart Fitness Watch V2 currently in stock?", timestamp = "10:42 AM", is_read = false),
            PageInboxMessageDto(id = 2, conversation_id = 502, sender_id = 202, sender_name = "Nadia Islam", text = "Thank you for the quick support yesterday!", timestamp = "Yesterday", is_read = true, assigned_team_member = "Rahim Ahmed"),
            PageInboxMessageDto(id = 3, conversation_id = 503, sender_id = 203, sender_name = "Imran Khan", text = "Can I get a custom quote for app development?", timestamp = "Oct 24", is_read = true)
        )
    }

    if (selectedConversation != null) {
        AlertDialog(
            onDismissRequest = { selectedConversation = null },
            title = { Text("Reply to ${selectedConversation!!.sender_name}", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Surface(
                        color = MaterialTheme.colorScheme.surfaceVariant,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = "\"${selectedConversation!!.text}\"",
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
                            selectedConversation = null
                            replyText = ""
                        }
                    }
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
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            item {
                Text("Manage customer messages and support inquiries.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }

            items(messagesList.size) { idx ->
                val msg = messagesList[idx]
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
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
                                .size(44.dp)
                                .clip(CircleShape)
                                .background(if (!msg.is_read) MaterialTheme.colorScheme.primary else Color.Gray.copy(alpha = 0.3f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = msg.sender_name.take(1).uppercase(),
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }

                        Spacer(modifier = Modifier.width(12.dp))

                        Column(modifier = Modifier.weight(1f)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(msg.sender_name, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text(msg.timestamp, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Spacer(modifier = Modifier.height(2.dp))
                            Text(
                                text = msg.text,
                                fontSize = 12.sp,
                                maxLines = 1,
                                color = if (!msg.is_read) MaterialTheme.colorScheme.onSurface else MaterialTheme.colorScheme.onSurfaceVariant,
                                fontWeight = if (!msg.is_read) FontWeight.SemiBold else FontWeight.Normal
                            )
                            if (msg.assigned_team_member != null) {
                                Text("Assigned: ${msg.assigned_team_member}", fontSize = 10.sp, color = MaterialTheme.colorScheme.primary)
                            }
                        }
                    }
                }
            }
        }
    }
}
