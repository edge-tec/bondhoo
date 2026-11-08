package com.connectly.android.feature.live.presentation.player

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.model.LiveComment
import com.connectly.android.core.webrtc.ui.WebRtcVideoRenderer

@Composable
fun LivePlayerScreen(
    streamId: Long = 1L,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: LivePlayerViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    val state by viewModel.uiState.collectAsState()
    var commentInput by remember { mutableStateOf("") }
    var showEndStreamConfirmDialog by remember { mutableStateOf(false) }

    var hasCameraPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED
        )
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        hasCameraPermission = permissions[Manifest.permission.CAMERA] ?: false
        viewModel.handleIntent(LivePlayerIntent.LoadStream(streamId))
    }

    LaunchedEffect(streamId) {
        if (!hasCameraPermission) {
            permissionLauncher.launch(
                arrayOf(Manifest.permission.CAMERA, Manifest.permission.RECORD_AUDIO)
            )
        } else {
            viewModel.handleIntent(LivePlayerIntent.LoadStream(streamId))
        }
    }

    LaunchedEffect(state.streamEnded) {
        if (state.streamEnded) {
            onNavigateBack()
        }
    }

    if (showEndStreamConfirmDialog) {
        AlertDialog(
            onDismissRequest = { showEndStreamConfirmDialog = false },
            title = { Text("End Live Broadcast?", fontWeight = FontWeight.Bold) },
            text = { Text("Are you sure you want to end your live video stream? All viewers will be disconnected.") },
            confirmButton = {
                Button(
                    onClick = {
                        showEndStreamConfirmDialog = false
                        viewModel.handleIntent(LivePlayerIntent.EndStream)
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFEF4444))
                ) {
                    Text("End Broadcast", color = Color.White)
                }
            },
            dismissButton = {
                TextButton(onClick = { showEndStreamConfirmDialog = false }) {
                    Text("Keep Streaming")
                }
            }
        )
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(Color.Black)
    ) {
        // Hardware-accelerated real Camera WebRTC Video Stream or fallback
        if (state.localVideoTrack != null && !state.isCameraOff) {
            WebRtcVideoRenderer(
                videoTrack = state.localVideoTrack,
                eglBaseContext = viewModel.eglBaseContext,
                isMirror = state.isFrontCamera,
                modifier = Modifier.fillMaxSize()
            )
        } else {
            AsyncImage(
                model = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1080&q=80",
                contentDescription = "Live Video Stream",
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }

        // Gradient shadow over video for UI clarity
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        colors = listOf(
                            Color.Black.copy(alpha = 0.45f),
                            Color.Transparent,
                            Color.Black.copy(alpha = 0.85f)
                        ),
                        startY = 0f,
                        endY = Float.POSITIVE_INFINITY
                    )
                )
        )

        // Top Bar: Red "LIVE" Badge + "👁 4.8K" Viewer Count + Broadcaster controls + Close (X)
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // Red LIVE Badge
                Surface(
                    color = Color(0xFFEF4444),
                    shape = RoundedCornerShape(6.dp)
                ) {
                    Text(
                        text = "LIVE",
                        color = Color.White,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.ExtraBold,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                    )
                }

                // Viewer Count Pill: 👁 4.8K
                Surface(
                    color = Color.Black.copy(alpha = 0.5f),
                    shape = RoundedCornerShape(14.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Visibility,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(14.dp)
                        )
                        Text(
                            text = state.liveStream?.formattedViewerCount ?: "1",
                            color = Color.White,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
            }

            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (state.isBroadcaster) {
                    // Switch Camera
                    IconButton(
                        onClick = { viewModel.handleIntent(LivePlayerIntent.SwitchCamera) },
                        modifier = Modifier
                            .size(36.dp)
                            .clip(CircleShape)
                            .background(Color.Black.copy(alpha = 0.5f))
                    ) {
                        Icon(Icons.Default.Cameraswitch, contentDescription = "Switch Camera", tint = Color.White, modifier = Modifier.size(20.dp))
                    }

                    // Mute Mic
                    IconButton(
                        onClick = { viewModel.handleIntent(LivePlayerIntent.ToggleMute) },
                        modifier = Modifier
                            .size(36.dp)
                            .clip(CircleShape)
                            .background(if (state.isMuted) Color(0xFFEF4444).copy(alpha = 0.8f) else Color.Black.copy(alpha = 0.5f))
                    ) {
                        Icon(
                            if (state.isMuted) Icons.Default.MicOff else Icons.Default.Mic,
                            contentDescription = "Mute Mic",
                            tint = Color.White,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }

                IconButton(
                    onClick = {
                        if (state.isBroadcaster) {
                            showEndStreamConfirmDialog = true
                        } else {
                            onNavigateBack()
                        }
                    },
                    modifier = Modifier
                        .size(36.dp)
                        .clip(CircleShape)
                        .background(Color.Black.copy(alpha = 0.4f))
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Close", tint = Color.White, modifier = Modifier.size(20.dp))
                }
            }
        }

        // Live Chat Comments Stream (Bottom Left - Matching Screen 8)
        Column(
            modifier = Modifier
                .align(Alignment.BottomStart)
                .fillMaxWidth(0.78f)
                .padding(start = 16.dp, bottom = 80.dp)
        ) {
            LazyColumn(
                modifier = Modifier.heightIn(max = 200.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
                reverseLayout = true
            ) {
                items(state.comments.reversed(), key = { it.id }) { comment ->
                    LiveCommentBubble(comment)
                }
            }
        }

        // Floating Hearts & Live Reactions (Right Side Floating Up)
        Column(
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .padding(end = 16.dp, bottom = 80.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            // Heart, thumbs-up, star reaction indicators
            FloatingReactionIcon("❤️")
            FloatingReactionIcon("👍")
            FloatingReactionIcon("⭐")
        }

        // Bottom Interactive Bar: Comment input + Gift icon 🎁 + Reaction ❤️ + Share (Matching Screen 8)
        Row(
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            // Comment text input pill
            Surface(
                color = Color.White.copy(alpha = 0.2f),
                shape = RoundedCornerShape(24.dp),
                modifier = Modifier.weight(1f)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    BasicTextFieldWithPlaceholder(
                        value = commentInput,
                        onValueChange = { commentInput = it },
                        placeholder = "Add a comment...",
                        onSend = {
                            if (commentInput.isNotBlank()) {
                                viewModel.handleIntent(LivePlayerIntent.SendComment(commentInput.trim()))
                                commentInput = ""
                            }
                        }
                    )
                }
            }

            // Gift Icon 🎁
            IconButton(
                onClick = { viewModel.handleIntent(LivePlayerIntent.ToggleGiftSheet) },
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Color.White.copy(alpha = 0.2f))
            ) {
                Text(text = "🎁", fontSize = 20.sp)
            }

            // Reaction Heart Button ❤️
            IconButton(
                onClick = { viewModel.handleIntent(LivePlayerIntent.SendReaction("heart")) },
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Color.White.copy(alpha = 0.2f))
            ) {
                Text(text = "❤️", fontSize = 20.sp)
            }

            // Share Button
            IconButton(
                onClick = { viewModel.handleIntent(LivePlayerIntent.ShareStream) },
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Color.White.copy(alpha = 0.2f))
            ) {
                Icon(Icons.Default.Share, contentDescription = "Share", tint = Color.White, modifier = Modifier.size(20.dp))
            }
        }
    }
}

@Composable
private fun LiveCommentBubble(
    comment: LiveComment,
    modifier: Modifier = Modifier
) {
    Surface(
        color = Color.Black.copy(alpha = 0.5f),
        shape = RoundedCornerShape(16.dp),
        modifier = modifier
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = comment.user.name,
                color = Color(0xFFFBBF24), // Gold yellow name
                fontWeight = FontWeight.Bold,
                fontSize = 13.sp
            )
            Spacer(modifier = Modifier.width(6.dp))
            Text(
                text = comment.comment,
                color = Color.White,
                fontSize = 13.sp
            )
        }
    }
}

@Composable
private fun FloatingReactionIcon(
    emoji: String,
    modifier: Modifier = Modifier
) {
    val infiniteTransition = rememberInfiniteTransition(label = "floating")
    val offsetY by infiniteTransition.animateFloat(
        initialValue = 0f,
        targetValue = -30f,
        animationSpec = infiniteRepeatable(
            animation = tween(1200, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "offset"
    )

    Text(
        text = emoji,
        fontSize = 24.sp,
        modifier = modifier.offset(y = offsetY.dp)
    )
}

@Composable
private fun BasicTextFieldWithPlaceholder(
    value: String,
    onValueChange: (String) -> Unit,
    placeholder: String,
    onSend: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(modifier = Modifier.weight(1f)) {
            if (value.isEmpty()) {
                Text(text = placeholder, color = Color.White.copy(alpha = 0.6f), fontSize = 14.sp)
            }
            androidx.compose.foundation.text.BasicTextField(
                value = value,
                onValueChange = onValueChange,
                textStyle = androidx.compose.ui.text.TextStyle(color = Color.White, fontSize = 14.sp),
                singleLine = true,
                modifier = Modifier.fillMaxWidth()
            )
        }
        if (value.isNotEmpty()) {
            IconButton(onClick = onSend, modifier = Modifier.size(24.dp)) {
                Icon(Icons.Default.Send, contentDescription = "Send", tint = ConnectlyBlue)
            }
        }
    }
}

