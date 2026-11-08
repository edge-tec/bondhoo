package com.connectly.android.feature.calling.presentation.calling

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
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
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallParticipant
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.NetworkQuality
import com.connectly.android.core.webrtc.ui.WebRtcVideoRenderer
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CallScreen(
    callId: Long,
    isIncoming: Boolean,
    callType: CallType,
    conversationId: Long? = null,
    receiverId: Long? = null,
    receiverIds: List<Long> = emptyList(),
    onCallEnded: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CallViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val snackbarHostState = remember { SnackbarHostState() }

    val requiredPermissions = remember(callType) {
        if (callType.isVideo) {
            arrayOf(Manifest.permission.RECORD_AUDIO, Manifest.permission.CAMERA)
        } else {
            arrayOf(Manifest.permission.RECORD_AUDIO)
        }
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions()
    ) { resultMap ->
        val audioGranted = resultMap[Manifest.permission.RECORD_AUDIO] == true ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.RECORD_AUDIO) == PackageManager.PERMISSION_GRANTED
        if (audioGranted) {
            viewModel.handleIntent(CallIntent.MediaPermissionsGranted)
        }
    }

    LaunchedEffect(requiredPermissions) {
        val allGranted = requiredPermissions.all { perm ->
            ContextCompat.checkSelfPermission(context, perm) == PackageManager.PERMISSION_GRANTED
        }
        if (allGranted) {
            viewModel.handleIntent(CallIntent.MediaPermissionsGranted)
        } else {
            permissionLauncher.launch(requiredPermissions)
        }
    }

    LaunchedEffect(callId, isIncoming, callType, conversationId, receiverId, receiverIds) {
        if (isIncoming) {
            viewModel.handleIntent(
                CallIntent.IncomingCallReceived(
                    callId = callId,
                    callType = callType,
                    conversationId = conversationId
                )
            )
        } else {
            val resolvedReceiver = receiverId?.takeIf { it > 0L } ?: callId.takeIf { it > 0L } ?: 0L
            viewModel.handleIntent(
                CallIntent.StartOutgoingCall(
                    receiverId = resolvedReceiver,
                    callType = callType,
                    conversationId = conversationId?.takeIf { it > 0L },
                    receiverIds = receiverIds
                )
            )
        }
    }

    // Active status & participant sync every 2s
    LaunchedEffect(state.callSession?.id, state.isCallEnded) {
        val activeId = state.callSession?.id ?: 0L
        if (activeId > 0L && !state.isCallEnded) {
            while (isActive) {
                delay(2000L)
                viewModel.handleIntent(CallIntent.SyncActiveCallState)
            }
        }
    }

    LaunchedEffect(state.isCallEnded) {
        if (state.isCallEnded) {
            onCallEnded()
        }
    }

    LaunchedEffect(state.statusMessage, state.errorMessage) {
        val msg = state.statusMessage ?: state.errorMessage
        if (!msg.isNullOrBlank() && state.callSession != null) {
            snackbarHostState.showSnackbar(msg)
            viewModel.handleIntent(CallIntent.ClearMessages)
        }
    }

    // Invite Friends to Call Sheet
    if (state.showInviteSheet) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(CallIntent.OpenInviteSheet(false)) }
        ) {
            InviteFriendsToCallSheet(
                searchQuery = state.inviteSearchQuery,
                friends = state.inviteFriends,
                onSearchChanged = { viewModel.handleIntent(CallIntent.InviteSearchChanged(it)) },
                onInvite = { selectedIds ->
                    viewModel.handleIntent(CallIntent.InviteFriends(selectedIds))
                }
            )
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(hostState = snackbarHostState) },
        containerColor = Color.Transparent,
        modifier = modifier
    ) { _ ->
        Box(
            modifier = Modifier
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
                // Fatal initiation error before session created
                state.callSession == null && !state.errorMessage.isNullOrBlank() -> {
                    CallFailedContent(
                        errorMessage = state.errorMessage.orEmpty(),
                        onClose = onCallEnded
                    )
                }

                // Incoming Call ringing screen
                state.callStatus == CallStatus.RINGING && state.isIncoming -> {
                    IncomingCallContent(
                        state = state,
                        onAccept = { viewModel.handleIntent(CallIntent.AcceptCall) },
                        onReject = { viewModel.handleIntent(CallIntent.RejectCall) }
                    )
                }

                // Group Audio / Group Video Call Grid
                state.isGroupCall -> {
                    GroupCallContent(
                        state = state,
                        onOpenInvite = { viewModel.handleIntent(CallIntent.OpenInviteSheet(true)) },
                        onToggleMute = { viewModel.handleIntent(CallIntent.ToggleMute) },
                        onToggleSpeaker = { viewModel.handleIntent(CallIntent.ToggleSpeaker) },
                        onToggleCamera = { viewModel.handleIntent(CallIntent.ToggleCamera) },
                        onSwitchCamera = { viewModel.handleIntent(CallIntent.SwitchCamera) },
                        onEndCall = { viewModel.handleIntent(CallIntent.EndCall) }
                    )
                }

                // Active 1-to-1 Video Call
                state.callType.isVideo && state.callStatus == CallStatus.CONNECTED -> {
                    ActiveVideoCallContent(
                        state = state,
                        onOpenInvite = { viewModel.handleIntent(CallIntent.OpenInviteSheet(true)) },
                        onToggleMute = { viewModel.handleIntent(CallIntent.ToggleMute) },
                        onToggleCamera = { viewModel.handleIntent(CallIntent.ToggleCamera) },
                        onSwitchCamera = { viewModel.handleIntent(CallIntent.SwitchCamera) },
                        onEndCall = { viewModel.handleIntent(CallIntent.EndCall) }
                    )
                }

                // Active 1-to-1 Audio Call / Dialing / Outgoing
                else -> {
                    ActiveAudioCallContent(
                        state = state,
                        onOpenInvite = { viewModel.handleIntent(CallIntent.OpenInviteSheet(true)) },
                        onToggleMute = { viewModel.handleIntent(CallIntent.ToggleMute) },
                        onToggleSpeaker = { viewModel.handleIntent(CallIntent.ToggleSpeaker) },
                        onToggleCamera = { viewModel.handleIntent(CallIntent.ToggleCamera) },
                        onEndCall = { viewModel.handleIntent(CallIntent.EndCall) }
                    )
                }
            }
        }
    }
}

@Composable
private fun CallFailedContent(
    errorMessage: String,
    onClose: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.CallEnd,
            contentDescription = null,
            tint = Color(0xFFEF4444),
            modifier = Modifier.size(64.dp)
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text("Call Unavailable", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 22.sp)
        Spacer(modifier = Modifier.height(8.dp))
        Text(
            text = errorMessage,
            color = Color.White.copy(alpha = 0.75f),
            fontSize = 14.sp,
            textAlign = TextAlign.Center
        )
        Spacer(modifier = Modifier.height(28.dp))
        Button(
            onClick = onClose,
            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
        ) {
            Text("Back to Messenger")
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

    val callLabel = when {
        state.isGroupCall && state.callType.isVideo -> "Incoming Bondoo Group Video Call..."
        state.isGroupCall -> "Incoming Bondoo Group Audio Call..."
        state.callType.isVideo -> "Incoming Bondoo Video Call..."
        else -> "Incoming Bondoo Audio Call..."
    }

    Column(
        modifier = modifier
            .fillMaxSize()
            .statusBarsPadding()
            .padding(horizontal = 24.dp, vertical = 48.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(top = 32.dp)
        ) {
            Box(contentAlignment = Alignment.Center) {
                Box(
                    modifier = Modifier
                        .size(160.dp)
                        .scale(pulseScale)
                        .clip(CircleShape)
                        .background(ConnectlyBlue.copy(alpha = 0.2f))
                )

                CallAvatar(
                    name = state.displayTitle,
                    avatarUrl = state.displayAvatar,
                    size = 130.dp,
                    borderColor = Color.White
                )
            }

            Spacer(modifier = Modifier.height(24.dp))

            Text(
                text = state.displayTitle,
                color = Color.White,
                fontSize = 26.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = callLabel,
                color = Color.White.copy(alpha = 0.75f),
                fontSize = 16.sp
            )
        }

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
                icon = if (state.callType.isVideo) Icons.Default.Videocam else Icons.Default.Call,
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
    onOpenInvite: () -> Unit,
    onToggleMute: () -> Unit,
    onToggleSpeaker: () -> Unit,
    onToggleCamera: () -> Unit,
    onEndCall: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .statusBarsPadding()
            .padding(horizontal = 24.dp, vertical = 32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        // Top row: Network Pill + Add Friend button
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Spacer(modifier = Modifier.size(44.dp))
            NetworkQualityPill(state.networkQuality)
            if (state.callSession != null) {
                IconButton(
                    onClick = onOpenInvite,
                    modifier = Modifier
                        .size(42.dp)
                        .clip(CircleShape)
                        .background(Color.White.copy(alpha = 0.14f))
                ) {
                    Icon(Icons.Default.PersonAdd, contentDescription = "Add Friend to Call", tint = Color.White)
                }
            } else {
                Spacer(modifier = Modifier.size(42.dp))
            }
        }

        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            CallAvatar(
                name = state.displayTitle,
                avatarUrl = state.displayAvatar,
                size = 124.dp,
                borderColor = ConnectlyBlue
            )

            Spacer(modifier = Modifier.height(20.dp))

            Text(
                text = state.displayTitle,
                color = Color.White,
                fontSize = 24.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = when (state.callStatus) {
                    CallStatus.CONNECTED -> state.formattedDuration
                    CallStatus.DIALING -> "Calling..."
                    else -> "Ringing..."
                },
                color = Color.White.copy(alpha = 0.8f),
                fontSize = 18.sp,
                fontWeight = FontWeight.SemiBold
            )
        }

        // Waveform Visualizer
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
                    .padding(vertical = 16.dp, horizontal = 10.dp),
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
                    icon = Icons.Default.PersonAdd,
                    isActive = false,
                    onClick = onOpenInvite
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
private fun GroupCallContent(
    state: CallState,
    onOpenInvite: () -> Unit,
    onToggleMute: () -> Unit,
    onToggleSpeaker: () -> Unit,
    onToggleCamera: () -> Unit,
    onSwitchCamera: () -> Unit,
    onEndCall: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .statusBarsPadding()
            .padding(horizontal = 16.dp, vertical = 16.dp)
    ) {
        // Group Call Header
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = state.displayTitle.ifBlank { "Bondoo Group Call" },
                    color = Color.White,
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    text = if (state.callStatus == CallStatus.CONNECTED) {
                        "${state.participants.size} participants • ${state.formattedDuration}"
                    } else {
                        "Ringing group members (${state.participants.size})..."
                    },
                    color = Color.White.copy(alpha = 0.75f),
                    fontSize = 13.sp
                )
            }

            FilledTonalButton(
                onClick = onOpenInvite,
                colors = ButtonDefaults.filledTonalButtonColors(
                    containerColor = ConnectlyBlue,
                    contentColor = Color.White
                )
            ) {
                Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text("Invite")
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Participant Tiles Grid
        LazyVerticalGrid(
            columns = GridCells.Fixed(2),
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            if (state.callType.isVideo && !state.isCameraOff && state.localVideoTrack != null && state.eglBaseContext != null) {
                item(key = "local_video_tile") {
                    Surface(
                        shape = RoundedCornerShape(18.dp),
                        color = Color(0xFF1E293B),
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(190.dp)
                            .clip(RoundedCornerShape(18.dp))
                            .border(1.5.dp, ConnectlyBlue, RoundedCornerShape(18.dp))
                    ) {
                        Box(modifier = Modifier.fillMaxSize()) {
                            WebRtcVideoRenderer(
                                videoTrack = state.localVideoTrack,
                                eglBaseContext = state.eglBaseContext,
                                modifier = Modifier.fillMaxSize(),
                                isMirror = state.isFrontCamera
                            )
                            Text(
                                text = "You",
                                color = Color.White,
                                fontSize = 12.sp,
                                fontWeight = FontWeight.SemiBold,
                                modifier = Modifier
                                    .align(Alignment.BottomStart)
                                    .padding(10.dp)
                                    .background(Color.Black.copy(alpha = 0.55f), RoundedCornerShape(8.dp))
                                    .padding(horizontal = 8.dp, vertical = 3.dp)
                            )
                        }
                    }
                }
            }

            items(state.participants, key = { p -> "part_${p.user.id}" }) { participant ->
                GroupParticipantTile(participant = participant)
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        // Group Call Controls Dock
        Surface(
            color = Color.White.copy(alpha = 0.12f),
            shape = RoundedCornerShape(32.dp),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 14.dp, horizontal = 8.dp),
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
                if (state.callType.isVideo) {
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
                }
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
private fun GroupParticipantTile(participant: CallParticipant) {
    val isConnected = participant.status.equals("connected", ignoreCase = true)
    Surface(
        shape = RoundedCornerShape(18.dp),
        color = Color(0xFF1E293B),
        modifier = Modifier
            .fillMaxWidth()
            .height(190.dp)
            .border(
                width = 1.5.dp,
                color = if (isConnected) ConnectlyGreen.copy(alpha = 0.7f) else Color.White.copy(alpha = 0.15f),
                shape = RoundedCornerShape(18.dp)
            )
    ) {
        Box(modifier = Modifier.fillMaxSize().padding(12.dp)) {
            Column(
                modifier = Modifier.align(Alignment.Center),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                CallAvatar(
                    name = participant.user.name,
                    avatarUrl = participant.user.avatar,
                    size = 68.dp,
                    borderColor = if (isConnected) ConnectlyGreen else ConnectlyBlue
                )
                Spacer(modifier = Modifier.height(10.dp))
                Text(
                    text = participant.user.name,
                    color = Color.White,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    text = participant.status.replaceFirstChar { it.uppercase() },
                    color = if (isConnected) ConnectlyGreen else Color.White.copy(alpha = 0.65f),
                    fontSize = 11.sp
                )
            }

            if (participant.isMuted) {
                Icon(
                    imageVector = Icons.Default.MicOff,
                    contentDescription = "Muted",
                    tint = Color(0xFFEF4444),
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .size(18.dp)
                )
            }
        }
    }
}

@Composable
private fun ActiveVideoCallContent(
    state: CallState,
    onOpenInvite: () -> Unit,
    onToggleMute: () -> Unit,
    onToggleCamera: () -> Unit,
    onSwitchCamera: () -> Unit,
    onEndCall: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(modifier = modifier.fillMaxSize()) {
        if (state.remoteVideoTrack != null && state.eglBaseContext != null) {
            WebRtcVideoRenderer(
                videoTrack = state.remoteVideoTrack,
                eglBaseContext = state.eglBaseContext,
                modifier = Modifier.fillMaxSize(),
                isMirror = false
            )
        } else {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Color(0xFF0F172A)),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    CallAvatar(
                        name = state.displayTitle,
                        avatarUrl = state.displayAvatar,
                        size = 120.dp,
                        borderColor = Color.White.copy(alpha = 0.5f)
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "Connecting video stream...",
                        color = Color.White.copy(alpha = 0.75f),
                        fontSize = 14.sp
                    )
                }
            }
        }

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
                    text = state.displayTitle,
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

            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                IconButton(
                    onClick = onOpenInvite,
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(Color.Black.copy(alpha = 0.45f))
                ) {
                    Icon(Icons.Default.PersonAdd, contentDescription = "Invite Friend", tint = Color.White)
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
        }

        Surface(
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(top = 86.dp, end = 16.dp)
                .size(width = 110.dp, height = 150.dp)
                .clip(RoundedCornerShape(16.dp))
                .border(2.dp, Color.White.copy(alpha = 0.6f), RoundedCornerShape(16.dp)),
            color = Color.DarkGray
        ) {
            if (!state.isCameraOff) {
                if (state.localVideoTrack != null && state.eglBaseContext != null) {
                    WebRtcVideoRenderer(
                        videoTrack = state.localVideoTrack,
                        eglBaseContext = state.eglBaseContext,
                        modifier = Modifier.fillMaxSize(),
                        isMirror = state.isFrontCamera
                    )
                } else {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(
                            color = ConnectlyBlue,
                            modifier = Modifier.size(24.dp),
                            strokeWidth = 2.dp
                        )
                    }
                }
            } else {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Icon(
                        Icons.Default.VideocamOff,
                        contentDescription = "Camera Off",
                        tint = Color.White.copy(alpha = 0.6f)
                    )
                }
            }
        }

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

                ControlIconButton(
                    icon = Icons.Default.PersonAdd,
                    isActive = false,
                    onClick = onOpenInvite
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
private fun InviteFriendsToCallSheet(
    searchQuery: String,
    friends: List<ActivePresenceFriend>,
    onSearchChanged: (String) -> Unit,
    onInvite: (List<Long>) -> Unit
) {
    var selectedIds by remember { mutableStateOf(setOf<Long>()) }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .fillMaxHeight(0.75f)
            .padding(horizontal = 16.dp)
    ) {
        Text("Invite Friends to Call", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = TextPrimaryLight)
        Text(
            "Select one or more friends to ring into this call",
            fontSize = 12.sp,
            color = TextSecondaryLight
        )
        Spacer(modifier = Modifier.height(10.dp))

        OutlinedTextField(
            value = searchQuery,
            onValueChange = onSearchChanged,
            placeholder = { Text("Search friends...") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            singleLine = true,
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(22.dp)
        )

        Spacer(modifier = Modifier.height(10.dp))

        LazyColumn(modifier = Modifier.weight(1f)) {
            if (friends.isEmpty()) {
                item {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 32.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text("No available friends found.", color = TextSecondaryLight, fontSize = 13.sp)
                    }
                }
            } else {
                items(friends, key = { "inv_friend_${it.id}" }) { friend ->
                    val checked = selectedIds.contains(friend.id)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                selectedIds = if (checked) selectedIds - friend.id else selectedIds + friend.id
                            }
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        CallAvatar(
                            name = friend.name,
                            avatarUrl = friend.avatarUrl,
                            size = 42.dp,
                            borderColor = if (friend.online) ConnectlyGreen else Color.Transparent
                        )
                        Spacer(modifier = Modifier.width(12.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(friend.name, fontWeight = FontWeight.SemiBold, color = TextPrimaryLight)
                            Text(
                                text = if (friend.online) "Active now" else "@${friend.username}",
                                fontSize = 12.sp,
                                color = if (friend.online) ConnectlyGreen else TextSecondaryLight
                            )
                        }
                        Checkbox(
                            checked = checked,
                            onCheckedChange = {
                                selectedIds = if (checked) selectedIds - friend.id else selectedIds + friend.id
                            },
                            colors = CheckboxDefaults.colors(checkedColor = ConnectlyBlue)
                        )
                    }
                }
            }
        }

        Button(
            onClick = { onInvite(selectedIds.toList()) },
            enabled = selectedIds.isNotEmpty(),
            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 12.dp),
            shape = RoundedCornerShape(14.dp)
        ) {
            Icon(Icons.Default.PersonAdd, contentDescription = null)
            Spacer(modifier = Modifier.width(8.dp))
            Text("Ring Selected Friends (${selectedIds.size})")
        }
    }
}

@Composable
private fun NetworkQualityPill(quality: NetworkQuality) {
    Surface(
        color = Color.White.copy(alpha = 0.12f),
        shape = RoundedCornerShape(16.dp)
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
                        when (quality) {
                            NetworkQuality.EXCELLENT -> ConnectlyGreen
                            NetworkQuality.GOOD -> Color(0xFFEAB308)
                            NetworkQuality.POOR -> Color(0xFFF97316)
                            NetworkQuality.RECONNECTING -> Color(0xFFEF4444)
                        }
                    )
            )
            Text(
                text = when (quality) {
                    NetworkQuality.EXCELLENT -> "Bondoo HD Voice"
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
}

@Composable
internal fun CallAvatar(
    name: String,
    avatarUrl: String?,
    size: Dp,
    borderColor: Color = Color.Transparent
) {
    val borderMod = if (borderColor != Color.Transparent) {
        Modifier.border(2.5.dp, borderColor, CircleShape)
    } else {
        Modifier
    }

    if (!avatarUrl.isNullOrBlank()) {
        AsyncImage(
            model = avatarUrl,
            contentDescription = name,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .then(borderMod)
        )
    } else {
        val initials = remember(name) {
            name.trim()
                .split("\\s+".toRegex())
                .filter { it.isNotEmpty() }
                .take(2)
                .joinToString("") { it.first().uppercaseChar().toString() }
                .ifBlank { "B" }
        }
        Box(
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .background(Brush.linearGradient(listOf(ConnectlyBlue, Color(0xFF4F46E5))))
                .then(borderMod),
            contentAlignment = Alignment.Center
        ) {
            Text(
                text = initials,
                color = Color.White,
                fontWeight = FontWeight.Bold,
                fontSize = (size.value * 0.34f).sp
            )
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
                .size(60.dp)
                .clip(CircleShape)
                .background(backgroundColor)
        ) {
            Icon(
                imageVector = icon,
                contentDescription = label ?: "Call Action",
                tint = Color.White,
                modifier = Modifier.size(30.dp)
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
            modifier = Modifier.size(22.dp)
        )
    }
}
