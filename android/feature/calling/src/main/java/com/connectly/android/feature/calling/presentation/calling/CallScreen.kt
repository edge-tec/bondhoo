package com.connectly.android.feature.calling.presentation.calling

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality

@Composable
fun CallScreen(
    callId: Long,
    isIncoming: Boolean,
    callType: CallType,
    onCallEnded: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CallViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(callId, isIncoming, callType) {
        if (isIncoming) {
            viewModel.handleIntent(CallIntent.IncomingCallReceived(callId, callType))
        } else {
            viewModel.handleIntent(CallIntent.StartOutgoingCall(receiverId = callId, callType = callType))
        }
    }

    LaunchedEffect(state.isCallEnded) {
        if (state.isCallEnded) {
            onCallEnded()
        }
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(
                        Color(0xFF0F172A),
                        Color(0xFF1E293B),
                        Color(0xFF020617)
                    )
                )
            )
    ) {
        when {
            // Incoming Call ringing screen
            state.callStatus == CallStatus.RINGING && state.isIncoming -> {
                IncomingCallContent(
                    state = state,
                    onAccept = { viewModel.handleIntent(CallIntent.AcceptCall) },
                    onReject = { viewModel.handleIntent(CallIntent.RejectCall) }
                )
            }

            // Active Video Call
            state.callType == CallType.VIDEO && state.callStatus == CallStatus.CONNECTED -> {
                ActiveVideoCallContent(
                    state = state,
                    onToggleMute = { viewModel.handleIntent(CallIntent.ToggleMute) },
                    onToggleCamera = { viewModel.handleIntent(CallIntent.ToggleCamera) },
                    onSwitchCamera = { viewModel.handleIntent(CallIntent.SwitchCamera) },
                    onEndCall = { viewModel.handleIntent(CallIntent.EndCall) }
                )
            }

            // Active Audio Call / Dialing / Outgoing
            else -> {
                ActiveAudioCallContent(
                    state = state,
                    onToggleMute = { viewModel.handleIntent(CallIntent.ToggleMute) },
                    onToggleSpeaker = { viewModel.handleIntent(CallIntent.ToggleSpeaker) },
                    onToggleCamera = { viewModel.handleIntent(CallIntent.ToggleCamera) },
                    onEndCall = { viewModel.handleIntent(CallIntent.EndCall) }
                )
            }
        }
    }
}

@Composable
private fun IncomingCallContent(
    state: CallState,
    onAccept: () -> Unit,
    onReject: () -> Unit,
    modifier: Modifier = Modifier
) {
    val infiniteTransition = rememberInfiniteTransition(label = "pulse")
    val pulseScale by infiniteTransition.animateFloat(
        initialValue = 1f,
        targetValue = 1.15f,
        animationSpec = infiniteRepeatable(
            animation = tween(1000, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        ),
        label = "pulseScale"
    )

    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(horizontal = 24.dp, vertical = 48.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(top = 32.dp)
        ) {
            Box(contentAlignment = Alignment.Center) {
                // Pulse halo
                Box(
                    modifier = Modifier
                        .size(160.dp)
                        .scale(pulseScale)
                        .clip(CircleShape)
                        .background(ConnectlyBlue.copy(alpha = 0.2f))
                )

                AsyncImage(
                    model = state.callSession?.caller?.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                    contentDescription = "Caller Avatar",
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .size(130.dp)
                        .clip(CircleShape)
                        .border(3.dp, Color.White, CircleShape)
                )
            }

            Spacer(modifier = Modifier.height(24.dp))

            Text(
                text = state.callSession?.caller?.name ?: "Sadia Islam",
                color = Color.White,
                fontSize = 26.sp,
                fontWeight = FontWeight.Bold
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = if (state.callType == CallType.VIDEO) "Incoming Connectly Video Call..." else "Incoming Connectly Audio Call...",
                color = Color.White.copy(alpha = 0.7f),
                fontSize = 16.sp
            )
        }

        // Action Buttons: Decline (Red) & Accept (Green)
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 32.dp),
            horizontalArrangement = Arrangement.SpaceEvenly,
            verticalAlignment = Alignment.CenterVertically
        ) {
            CallActionButton(
                icon = Icons.Default.CallEnd,
                backgroundColor = Color(0xFFEF4444),
                label = "Decline",
                onClick = onReject
            )

            CallActionButton(
                icon = if (state.callType == CallType.VIDEO) Icons.Default.Videocam else Icons.Default.Call,
                backgroundColor = ConnectlyGreen,
                label = "Accept",
                onClick = onAccept
            )
        }
    }
}

@Composable
private fun ActiveAudioCallContent(
    state: CallState,
    onToggleMute: () -> Unit,
    onToggleSpeaker: () -> Unit,
    onToggleCamera: () -> Unit,
    onEndCall: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(horizontal = 24.dp, vertical = 48.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        // Top info header
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(top = 24.dp)
        ) {
            // Network Quality Indicator Pill
            Surface(
                color = Color.White.copy(alpha = 0.12f),
                shape = RoundedCornerShape(16.dp),
                modifier = Modifier.padding(bottom = 20.dp)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .size(8.dp)
                            .clip(CircleShape)
                            .background(
                                when (state.networkQuality) {
                                    NetworkQuality.EXCELLENT -> ConnectlyGreen
                                    NetworkQuality.GOOD -> Color(0xFFEAB308)
                                    NetworkQuality.POOR -> Color(0xFFF97316)
                                    NetworkQuality.RECONNECTING -> Color(0xFFEF4444)
                                }
                            )
                    )
                    Text(
                        text = when (state.networkQuality) {
                            NetworkQuality.EXCELLENT -> "Excellent Connection"
                            NetworkQuality.GOOD -> "Good Network"
                            NetworkQuality.POOR -> "Weak Network"
                            NetworkQuality.RECONNECTING -> "Reconnecting..."
                        },
                        color = Color.White,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Medium
                    )
                }
            }

            AsyncImage(
                model = state.callSession?.receiver?.avatar ?: "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                contentDescription = "Receiver Avatar",
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .size(120.dp)
                    .clip(CircleShape)
                    .border(3.dp, ConnectlyBlue, CircleShape)
            )

            Spacer(modifier = Modifier.height(20.dp))

            Text(
                text = state.callSession?.receiver?.name ?: "Sadia Islam",
                color = Color.White,
                fontSize = 24.sp,
                fontWeight = FontWeight.Bold
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = if (state.callStatus == CallStatus.CONNECTED) state.formattedDuration else "Ringing...",
                color = Color.White.copy(alpha = 0.8f),
                fontSize = 18.sp,
                fontWeight = FontWeight.SemiBold
            )
        }

        // Waveform Visualizer Animation in Middle
        Row(
            horizontalArrangement = Arrangement.spacedBy(4.dp),
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.height(40.dp)
        ) {
            val infiniteTransition = rememberInfiniteTransition(label = "wave")
            repeat(7) { index ->
                val waveHeight by infiniteTransition.animateFloat(
                    initialValue = 10f,
                    targetValue = if (state.callStatus == CallStatus.CONNECTED && !state.isMuted) 36f else 12f,
                    animationSpec = infiniteRepeatable(
                        animation = tween(400 + index * 100, easing = LinearEasing),
                        repeatMode = RepeatMode.Reverse
                    ),
                    label = "wave_$index"
                )
                Box(
                    modifier = Modifier
                        .width(4.dp)
                        .height(waveHeight.dp)
                        .clip(RoundedCornerShape(2.dp))
                        .background(if (state.isMuted) Color.Gray else ConnectlyBlue)
                )
            }
        }

        // Bottom Controls Dock
        Surface(
            color = Color.White.copy(alpha = 0.1f),
            shape = RoundedCornerShape(32.dp),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 16.dp, horizontal = 12.dp),
                horizontalArrangement = Arrangement.SpaceEvenly,
                verticalAlignment = Alignment.CenterVertically
            ) {
                ControlIconButton(
                    icon = if (state.isMuted) Icons.Default.MicOff else Icons.Default.Mic,
                    isActive = state.isMuted,
                    onClick = onToggleMute
                )

                ControlIconButton(
                    icon = if (state.isSpeakerOn) Icons.Default.VolumeUp else Icons.Default.VolumeDown,
                    isActive = state.isSpeakerOn,
                    onClick = onToggleSpeaker
                )

                ControlIconButton(
                    icon = Icons.Default.Videocam,
                    isActive = false,
                    onClick = onToggleCamera
                )

                CallActionButton(
                    icon = Icons.Default.CallEnd,
                    backgroundColor = Color(0xFFEF4444),
                    onClick = onEndCall
                )
            }
        }
    }
}

@Composable
private fun ActiveVideoCallContent(
    state: CallState,
    onToggleMute: () -> Unit,
    onToggleCamera: () -> Unit,
    onSwitchCamera: () -> Unit,
    onEndCall: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(modifier = modifier.fillMaxSize()) {
        // Fullscreen Remote Video Stream Simulation Canvas
        AsyncImage(
            model = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1080&q=80",
            contentDescription = "Remote Video Stream",
            contentScale = ContentScale.Crop,
            modifier = Modifier.fillMaxSize()
        )

        // Gradient overlay for readability
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        colors = listOf(
                            Color.Black.copy(alpha = 0.5f),
                            Color.Transparent,
                            Color.Black.copy(alpha = 0.7f)
                        )
                    )
                )
        )

        // Top Header: Contact Name, Duration & Network Quality
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 20.dp, vertical = 16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(
                    text = state.callSession?.receiver?.name ?: "Sadia Islam",
                    color = Color.White,
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = state.formattedDuration,
                    color = Color.White.copy(alpha = 0.8f),
                    fontSize = 14.sp
                )
            }

            Surface(
                color = Color.Black.copy(alpha = 0.4f),
                shape = RoundedCornerShape(12.dp)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .size(8.dp)
                            .clip(CircleShape)
                            .background(ConnectlyGreen)
                    )
                    Text("HD", color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
            }
        }

        // PiP Local Video Preview Tile (Top Right)
        Surface(
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(top = 80.dp, end = 16.dp)
                .size(width = 110.dp, height = 150.dp)
                .clip(RoundedCornerShape(16.dp))
                .border(2.dp, Color.White.copy(alpha = 0.6f), RoundedCornerShape(16.dp)),
            color = Color.DarkGray
        ) {
            if (!state.isCameraOff) {
                AsyncImage(
                    model = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80",
                    contentDescription = "Local Camera Preview",
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize()
                )
            } else {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.VideocamOff,
                        contentDescription = "Camera Off",
                        tint = Color.White.copy(alpha = 0.6f)
                    )
                }
            }
        }

        // Bottom Video Call Floating Bar
        Surface(
            color = Color.Black.copy(alpha = 0.6f),
            shape = RoundedCornerShape(32.dp),
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .padding(bottom = 36.dp, start = 20.dp, end = 20.dp)
                .fillMaxWidth()
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 12.dp, horizontal = 8.dp),
                horizontalArrangement = Arrangement.SpaceEvenly,
                verticalAlignment = Alignment.CenterVertically
            ) {
                ControlIconButton(
                    icon = if (state.isMuted) Icons.Default.MicOff else Icons.Default.Mic,
                    isActive = state.isMuted,
                    onClick = onToggleMute
                )

                ControlIconButton(
                    icon = if (state.isCameraOff) Icons.Default.VideocamOff else Icons.Default.Videocam,
                    isActive = state.isCameraOff,
                    onClick = onToggleCamera
                )

                ControlIconButton(
                    icon = Icons.Default.FlipCameraAndroid,
                    isActive = false,
                    onClick = onSwitchCamera
                )

                CallActionButton(
                    icon = Icons.Default.CallEnd,
                    backgroundColor = Color(0xFFEF4444),
                    onClick = onEndCall
                )
            }
        }
    }
}

@Composable
private fun CallActionButton(
    icon: ImageVector,
    backgroundColor: Color,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    label: String? = null
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp),
        modifier = modifier
    ) {
        IconButton(
            onClick = onClick,
            modifier = Modifier
                .size(64.dp)
                .clip(CircleShape)
                .background(backgroundColor)
        ) {
            Icon(
                imageVector = icon,
                contentDescription = label ?: "Call Action",
                tint = Color.White,
                modifier = Modifier.size(32.dp)
            )
        }
        if (label != null) {
            Text(
                text = label,
                color = Color.White,
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium
            )
        }
    }
}

@Composable
private fun ControlIconButton(
    icon: ImageVector,
    isActive: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    IconButton(
        onClick = onClick,
        modifier = modifier
            .size(48.dp)
            .clip(CircleShape)
            .background(if (isActive) Color.White else Color.White.copy(alpha = 0.2f))
    ) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = if (isActive) Color.Black else Color.White,
            modifier = Modifier.size(24.dp)
        )
    }
}

