package com.connectly.android.feature.messenger.presentation.chat

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
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.*
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
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChatScreen(
    conversationId: Long,
    onNavigateBack: () -> Unit,
    onStartAudioCall: (Long) -> Unit = {},
    onStartVideoCall: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: ChatViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var inputText by remember { mutableStateOf("") }
    var showAttachmentMenu by remember { mutableStateOf(false) }

    LaunchedEffect(conversationId) {
        viewModel.handleIntent(ChatIntent.LoadChat(conversationId))
    }

    if (showAttachmentMenu) {
        ModalBottomSheet(
            onDismissRequest = { showAttachmentMenu = false }
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text(
                    text = "Share Content",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp,
                    color = TextPrimaryLight
                )

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceAround
                ) {
                    AttachmentOptionItem(
                        icon = Icons.Default.PhotoLibrary,
                        label = "Gallery",
                        color = ConnectlyBlue,
                        onClick = {
                            viewModel.handleIntent(ChatIntent.SendTextMessage("📷 [Shared a photo]"))
                            showAttachmentMenu = false
                        }
                    )

                    AttachmentOptionItem(
                        icon = Icons.Default.InsertDriveFile,
                        label = "Document",
                        color = Color(0xFF8B5CF6),
                        onClick = {
                            viewModel.handleIntent(ChatIntent.SendTextMessage("📄 [Shared document.pdf]"))
                            showAttachmentMenu = false
                        }
                    )

                    AttachmentOptionItem(
                        icon = Icons.Default.LocationOn,
                        label = "Location",
                        color = Color(0xFF10B981),
                        onClick = {
                            viewModel.handleIntent(ChatIntent.SendTextMessage("📍 [Live Location: Dhaka, Bangladesh]"))
                            showAttachmentMenu = false
                        }
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(modifier = Modifier.size(38.dp)) {
                            AsyncImage(
                                model = state.participantAvatar,
                                contentDescription = state.participantName,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier
                                    .fillMaxSize()
                                    .clip(CircleShape)
                            )
                            if (state.isOnline) {
                                Box(
                                    modifier = Modifier
                                        .size(10.dp)
                                        .align(Alignment.BottomEnd)
                                        .clip(CircleShape)
                                        .background(ConnectlyGreen)
                                        .border(1.5.dp, Color.White, CircleShape)
                                )
                            }
                        }

                        Spacer(modifier = Modifier.width(10.dp))

                        Column {
                            Text(
                                text = state.participantName,
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                color = TextPrimaryLight
                            )
                            Text(
                                text = if (state.isTyping) "Typing..." else "Active now",
                                fontSize = 11.sp,
                                color = if (state.isTyping) ConnectlyBlue else TextSecondaryLight
                            )
                        }
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { onStartAudioCall(conversationId) }) {
                        Icon(Icons.Default.Call, contentDescription = "Call", tint = ConnectlyBlue)
                    }
                    IconButton(onClick = { onStartVideoCall(conversationId) }) {
                        Icon(Icons.Default.Videocam, contentDescription = "Video Call", tint = ConnectlyBlue)
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
                .background(Color(0xFFF8FAFC))
        ) {
            // Chat message bubbles
            LazyColumn(
                modifier = Modifier
                    .weight(1f)
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(state.messages, key = { it.clientMessageId }) { message ->
                    ChatMessageBubble(
                        message = message,
                        onReact = { reaction ->
                            viewModel.handleIntent(ChatIntent.ReactMessage(message.id, reaction))
                        }
                    )
                }
            }

            // Message Composer Bar
            Surface(
                color = MaterialTheme.colorScheme.surface,
                tonalElevation = 4.dp,
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    IconButton(onClick = { showAttachmentMenu = true }) {
                        Icon(Icons.Default.AttachFile, contentDescription = "Attach", tint = ConnectlyBlue)
                    }

                    OutlinedTextField(
                        value = inputText,
                        onValueChange = { inputText = it },
                        placeholder = { Text("Message...", fontSize = 14.sp) },
                        modifier = Modifier
                            .weight(1f)
                            .heightIn(min = 40.dp, max = 100.dp),
                        shape = RoundedCornerShape(20.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedContainerColor = Color(0xFFF1F5F9),
                            unfocusedContainerColor = Color(0xFFF1F5F9),
                            focusedBorderColor = Color.Transparent,
                            unfocusedBorderColor = Color.Transparent
                        )
                    )

                    Spacer(modifier = Modifier.width(6.dp))

                    if (inputText.isNotBlank()) {
                        IconButton(
                            onClick = {
                                viewModel.handleIntent(ChatIntent.SendTextMessage(inputText))
                                inputText = ""
                            }
                        ) {
                            Icon(Icons.AutoMirrored.Filled.Send, contentDescription = "Send", tint = ConnectlyBlue)
                        }
                    } else {
                        // Voice message button
                        IconButton(
                            onClick = {
                                viewModel.handleIntent(ChatIntent.SendVoiceMessage(durationSeconds = 14))
                            }
                        ) {
                            Icon(Icons.Default.Mic, contentDescription = "Voice Message", tint = ConnectlyBlue)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AttachmentOptionItem(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    color: Color,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(6.dp),
        modifier = Modifier.clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier
                .size(52.dp)
                .clip(CircleShape)
                .background(color.copy(alpha = 0.15f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(imageVector = icon, contentDescription = label, tint = color, modifier = Modifier.size(26.dp))
        }
        Text(text = label, fontSize = 12.sp, fontWeight = FontWeight.Medium, color = TextPrimaryLight)
    }
}

@Composable
private fun ChatMessageBubble(
    message: Message,
    onReact: (String) -> Unit
) {
    val isMine = message.isMine
    val bubbleColor = if (isMine) ConnectlyBlue else Color.White
    val textColor = if (isMine) Color.White else TextPrimaryLight

    Box(
        modifier = Modifier.fillMaxWidth(),
        contentAlignment = if (isMine) Alignment.CenterEnd else Alignment.CenterStart
    ) {
        Column(
            horizontalAlignment = if (isMine) Alignment.End else Alignment.Start,
            modifier = Modifier.widthIn(max = 280.dp)
        ) {
            Card(
                shape = RoundedCornerShape(
                    topStart = 16.dp,
                    topEnd = 16.dp,
                    bottomStart = if (isMine) 16.dp else 2.dp,
                    bottomEnd = if (isMine) 2.dp else 16.dp
                ),
                colors = CardDefaults.cardColors(containerColor = bubbleColor),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
            ) {
                if (message.type == "voice") {
                    // Voice note waveform player
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier
                                .size(32.dp)
                                .clip(CircleShape)
                                .background(if (isMine) Color.White.copy(alpha = 0.2f) else ConnectlyBlue),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(Icons.Default.PlayArrow, contentDescription = "Play", tint = if (isMine) Color.White else Color.White)
                        }
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("|||||l||l|||l||l||", color = textColor, fontWeight = FontWeight.Bold, letterSpacing = 2.sp)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("0:14", color = textColor, fontSize = 12.sp)
                    }
                } else {
                    Text(
                        text = message.body,
                        color = textColor,
                        fontSize = 14.sp,
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp)
                    )
                }
            }

            Spacer(modifier = Modifier.height(2.dp))

            // Delivery Status & Time
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text("12:45 PM", fontSize = 10.sp, color = TextSecondaryLight)

                if (isMine) {
                    val statusIcon = when (message.deliveryStatus) {
                        DeliveryStatus.SENDING -> "⌛"
                        DeliveryStatus.SENT -> "✓"
                        DeliveryStatus.DELIVERED -> "✓✓"
                        DeliveryStatus.READ -> "✓✓"
                        DeliveryStatus.FAILED -> "⚠️"
                    }
                    val statusColor = if (message.deliveryStatus == DeliveryStatus.READ) ConnectlyBlue else TextSecondaryLight
                    Text(text = statusIcon, fontSize = 11.sp, color = statusColor, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}
