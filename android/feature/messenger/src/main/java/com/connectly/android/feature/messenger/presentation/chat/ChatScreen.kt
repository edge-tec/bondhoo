package com.connectly.android.feature.messenger.presentation.chat

import android.Manifest
import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.media.AudioAttributes
import android.media.MediaPlayer
import android.media.MediaRecorder
import android.net.Uri
import android.os.Build
import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.combinedClickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.automirrored.filled.Forward
import androidx.compose.material.icons.automirrored.filled.InsertDriveFile
import androidx.compose.material.icons.automirrored.filled.Reply
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.MessageAttachment
import com.connectly.android.feature.messenger.presentation.conversations.MessengerAvatar
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import java.io.File
import java.io.FileOutputStream
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class, ExperimentalFoundationApi::class)
@Composable
fun ChatScreen(
    conversationId: Long,
    onNavigateBack: () -> Unit,
    onNavigateToChat: (Long) -> Unit = {},
    onStartCall: ((conversationId: Long?, receiverId: Long?, receiverIds: List<Long>, callType: CallType) -> Unit)? = null,
    onStartAudioCall: (Long) -> Unit = {},
    onStartVideoCall: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: ChatViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()
    val snackbarHostState = remember { SnackbarHostState() }
    val listState = rememberLazyListState()

    var inputText by remember { mutableStateOf("") }
    var showAttachmentMenu by remember { mutableStateOf(false) }
    var selectedMessageForActions by remember { mutableStateOf<Message?>(null) }
    var reportingMessage by remember { mutableStateOf<Message?>(null) }
    var showPinnedListDialog by remember { mutableStateOf(false) }
    var fullScreenImageUrl by remember { mutableStateOf<String?>(null) }

    // Voice Recording state
    var isRecording by remember { mutableStateOf(false) }
    var recordingSeconds by remember { mutableIntStateOf(0) }
    var activeRecorder by remember { mutableStateOf<MediaRecorder?>(null) }
    var activeAudioFile by remember { mutableStateOf<File?>(null) }

    // Populate composer when editing an existing message
    LaunchedEffect(state.editingMessage) {
        val editing = state.editingMessage
        if (editing != null) {
            inputText = editing.body
        }
    }

    // Initial load
    LaunchedEffect(conversationId) {
        viewModel.handleIntent(ChatIntent.LoadChat(conversationId))
    }

    // Active silent sync every 3.5s while ChatScreen is visible
    LaunchedEffect(conversationId) {
        while (isActive) {
            delay(3500L)
            viewModel.silentSync()
        }
    }

    // Auto-scroll to bottom when new messages arrive
    LaunchedEffect(state.displayedMessages.size) {
        val count = state.displayedMessages.size
        if (count > 0 && !state.isSearchMode) {
            listState.animateScrollToItem(count - 1)
        }
    }

    // One-off navigation & call effects
    LaunchedEffect(Unit) {
        viewModel.effects.collect { effect ->
            when (effect) {
                ChatEffect.NavigateBack -> onNavigateBack()
                is ChatEffect.OpenOtherChat -> onNavigateToChat(effect.conversationId)
                is ChatEffect.StartCall -> {
                    if (onStartCall != null) {
                        onStartCall(
                            effect.conversationId,
                            effect.receiverId,
                            effect.receiverIds,
                            effect.callType
                        )
                    } else if (effect.callType.isVideo) {
                        onStartVideoCall(conversationId)
                    } else {
                        onStartAudioCall(conversationId)
                    }
                }
            }
        }
    }

    // Snackbar feedback
    LaunchedEffect(state.errorMessage, state.statusMessage) {
        val msg = state.errorMessage ?: state.statusMessage
        if (!msg.isNullOrBlank()) {
            snackbarHostState.showSnackbar(msg)
            viewModel.handleIntent(ChatIntent.ClearStatusMessages)
        }
    }

    // Recording elapsed timer
    LaunchedEffect(isRecording) {
        if (isRecording) {
            recordingSeconds = 0
            while (isActive && isRecording) {
                delay(1000L)
                recordingSeconds += 1
            }
        }
    }

    // Cleanup recorder if user leaves screen while recording
    DisposableEffect(Unit) {
        onDispose {
            runCatching {
                activeRecorder?.stop()
                activeRecorder?.release()
            }
            activeRecorder = null
        }
    }

    // Voice Note Streaming MediaPlayer
    val playingMessage = remember(state.playingMessageId, state.messages) {
        state.messages.firstOrNull { it.id == state.playingMessageId }
    }
    DisposableEffect(playingMessage?.audioUrl, state.isAudioPlaying) {
        var player: MediaPlayer? = null
        val url = playingMessage?.audioUrl
        if (state.isAudioPlaying && !url.isNullOrBlank()) {
            runCatching {
                player = MediaPlayer().apply {
                    setAudioAttributes(
                        AudioAttributes.Builder()
                            .setContentType(AudioAttributes.CONTENT_TYPE_SPEECH)
                            .setUsage(AudioAttributes.USAGE_MEDIA)
                            .build()
                    )
                    setDataSource(url)
                    setOnPreparedListener { mp -> mp.start() }
                    setOnCompletionListener { viewModel.onVoicePlaybackCompleted() }
                    setOnErrorListener { _, _, _ ->
                        viewModel.onVoicePlaybackCompleted()
                        true
                    }
                    prepareAsync()
                }
            }.onFailure {
                viewModel.onVoicePlaybackCompleted()
            }
        }
        onDispose {
            runCatching {
                player?.stop()
                player?.release()
            }
        }
    }

    fun startVoiceRecorder() {
        val outFile = File(context.cacheDir, "voice_${System.currentTimeMillis()}.m4a")
        val recorder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            MediaRecorder(context)
        } else {
            @Suppress("DEPRECATION")
            MediaRecorder()
        }
        runCatching {
            recorder.setAudioSource(MediaRecorder.AudioSource.MIC)
            recorder.setOutputFormat(MediaRecorder.OutputFormat.MPEG_4)
            recorder.setAudioEncoder(MediaRecorder.AudioEncoder.AAC)
            recorder.setAudioSamplingRate(44100)
            recorder.setAudioEncodingBitRate(96000)
            recorder.setOutputFile(outFile.absolutePath)
            recorder.prepare()
            recorder.start()
            activeRecorder = recorder
            activeAudioFile = outFile
            isRecording = true
        }.onFailure { err ->
            runCatching { recorder.release() }
            runCatching { outFile.delete() }
            coroutineScope.launch {
                snackbarHostState.showSnackbar("Microphone unavailable: ${err.localizedMessage ?: "check permissions"}")
            }
        }
    }

    fun finishVoiceRecorder(send: Boolean) {
        val recorder = activeRecorder
        val file = activeAudioFile
        val elapsed = recordingSeconds.coerceAtLeast(1)
        isRecording = false
        activeRecorder = null
        activeAudioFile = null

        val stoppedCleanly = runCatching {
            recorder?.stop()
        }.isSuccess
        runCatching { recorder?.release() }

        if (send && file != null && stoppedCleanly && file.exists() && file.length() > 0L) {
            viewModel.handleIntent(ChatIntent.SendVoiceFile(file, elapsed))
        } else {
            if (file != null) runCatching { file.delete() }
            if (send) {
                coroutineScope.launch {
                    snackbarHostState.showSnackbar("Voice recording was too short")
                }
            }
        }
    }

    // Activity Result Launchers for real Photo/Video, Camera, Document, and Audio Permission
    val mediaPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri: Uri? ->
        if (uri != null) {
            val copied = copyUriToTempFile(context, uri, "jpg")
            if (copied != null) {
                val caption = inputText
                inputText = ""
                viewModel.handleIntent(ChatIntent.SendAttachmentFile(copied.first, copied.second, caption))
            }
        }
    }

    val documentPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenDocument()
    ) { uri: Uri? ->
        if (uri != null) {
            val copied = copyUriToTempFile(context, uri, "bin")
            if (copied != null) {
                val caption = inputText
                inputText = ""
                viewModel.handleIntent(ChatIntent.SendAttachmentFile(copied.first, copied.second, caption))
            }
        }
    }

    val cameraPreviewLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.TakePicturePreview()
    ) { bitmap: Bitmap? ->
        if (bitmap != null) {
            val file = File(context.cacheDir, "camera_${System.currentTimeMillis()}.jpg")
            val saved = runCatching {
                FileOutputStream(file).use { out ->
                    bitmap.compress(Bitmap.CompressFormat.JPEG, 92, out)
                }
                true
            }.getOrDefault(false)
            if (saved) {
                val caption = inputText
                inputText = ""
                viewModel.handleIntent(ChatIntent.SendAttachmentFile(file, "image/jpeg", caption))
            }
        }
    }

    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            cameraPreviewLauncher.launch(null)
        } else {
            coroutineScope.launch {
                snackbarHostState.showSnackbar("Camera permission is required to take a photo")
            }
        }
    }

    val audioPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { granted ->
        if (granted) {
            startVoiceRecorder()
        } else {
            coroutineScope.launch {
                snackbarHostState.showSnackbar("Microphone permission is required for voice messages")
            }
        }
    }

    // Fullscreen image viewer
    fullScreenImageUrl?.let { url ->
        Dialog(onDismissRequest = { fullScreenImageUrl = null }) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(16.dp))
                    .background(Color.Black)
            ) {
                AsyncImage(
                    model = url,
                    contentDescription = "Attachment preview",
                    contentScale = ContentScale.Fit,
                    modifier = Modifier
                        .fillMaxWidth()
                        .heightIn(min = 240.dp, max = 560.dp)
                )
                IconButton(
                    onClick = { fullScreenImageUrl = null },
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(8.dp)
                        .background(Color.Black.copy(alpha = 0.55f), CircleShape)
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Close", tint = Color.White)
                }
            }
        }
    }

    // Pinned messages list dialog
    if (showPinnedListDialog && state.pinnedMessages.isNotEmpty()) {
        AlertDialog(
            onDismissRequest = { showPinnedListDialog = false },
            title = { Text("Pinned Messages (${state.pinnedMessages.size})", fontWeight = FontWeight.Bold) },
            text = {
                LazyColumn(
                    modifier = Modifier.heightIn(max = 320.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(state.pinnedMessages, key = { "pin_${it.message.id}" }) { pin ->
                        Surface(
                            shape = RoundedCornerShape(12.dp),
                            color = Color(0xFFF1F5F9),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Row(
                                modifier = Modifier.padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(pin.message.senderName, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                    Text(
                                        text = pin.message.body.ifBlank { "[Attachment]" },
                                        fontSize = 13.sp,
                                        maxLines = 2,
                                        overflow = TextOverflow.Ellipsis
                                    )
                                }
                                IconButton(
                                    onClick = {
                                        viewModel.handleIntent(ChatIntent.TogglePinMessage(pin.message.copy(isPinned = true)))
                                    }
                                ) {
                                    Icon(Icons.Default.PushPin, contentDescription = "Unpin", tint = ConnectlyBlue)
                                }
                            }
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = { showPinnedListDialog = false }) { Text("Close") }
            }
        )
    }

    // Report Message Dialog
    reportingMessage?.let { targetMsg ->
        val reasons = listOf("spam" to "Spam or unwanted message", "harassment" to "Harassment or bullying", "inappropriate" to "Inappropriate content", "scam" to "Scam or fraud")
        AlertDialog(
            onDismissRequest = { reportingMessage = null },
            title = { Text("Report Message", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("Why are you reporting this message?", fontSize = 13.sp, color = TextSecondaryLight)
                    Spacer(modifier = Modifier.height(6.dp))
                    reasons.forEach { (code, label) ->
                        TextButton(
                            onClick = {
                                viewModel.handleIntent(ChatIntent.ReportMessage(targetMsg.id, code))
                                reportingMessage = null
                            },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(label, modifier = Modifier.fillMaxWidth(), textAlign = TextAlign.Start)
                        }
                    }
                }
            },
            confirmButton = {},
            dismissButton = {
                TextButton(onClick = { reportingMessage = null }) { Text("Cancel") }
            }
        )
    }

    // Share Attachment Menu Sheet
    if (showAttachmentMenu) {
        ModalBottomSheet(onDismissRequest = { showAttachmentMenu = false }) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 12.dp),
                verticalArrangement = Arrangement.spacedBy(18.dp)
            ) {
                Text(
                    text = "Share in Chat",
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
                        label = "Photo/Video",
                        color = ConnectlyBlue,
                        onClick = {
                            showAttachmentMenu = false
                            mediaPickerLauncher.launch(
                                PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageAndVideo)
                            )
                        }
                    )

                    AttachmentOptionItem(
                        icon = Icons.Default.PhotoCamera,
                        label = "Camera",
                        color = Color(0xFFEC4899),
                        onClick = {
                            showAttachmentMenu = false
                            val hasCam = ContextCompat.checkSelfPermission(
                                context,
                                Manifest.permission.CAMERA
                            ) == PackageManager.PERMISSION_GRANTED
                            if (hasCam) {
                                cameraPreviewLauncher.launch(null)
                            } else {
                                cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                            }
                        }
                    )

                    AttachmentOptionItem(
                        icon = Icons.AutoMirrored.Filled.InsertDriveFile,
                        label = "Document",
                        color = Color(0xFF8B5CF6),
                        onClick = {
                            showAttachmentMenu = false
                            documentPickerLauncher.launch(arrayOf("*/*"))
                        }
                    )

                    if (!state.isSaved) {
                        AttachmentOptionItem(
                            icon = Icons.Default.Videocam,
                            label = if (state.isGroup) "Group Call" else "Video Call",
                            color = Color(0xFF10B981),
                            onClick = {
                                showAttachmentMenu = false
                                viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = true))
                            }
                        )
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    // Long-Press Message Actions Sheet
    selectedMessageForActions?.let { msg ->
        ModalBottomSheet(onDismissRequest = { selectedMessageForActions = null }) {
            MessageActionsSheetContent(
                message = msg,
                onReact = { emoji ->
                    viewModel.handleIntent(ChatIntent.ReactMessage(msg.id, emoji))
                    selectedMessageForActions = null
                },
                onReply = {
                    viewModel.handleIntent(ChatIntent.SetReplyTo(msg))
                    selectedMessageForActions = null
                },
                onEdit = {
                    viewModel.handleIntent(ChatIntent.StartEditMessage(msg))
                    selectedMessageForActions = null
                },
                onForward = {
                    viewModel.handleIntent(ChatIntent.OpenForwardSheet(msg))
                    selectedMessageForActions = null
                },
                onTogglePin = {
                    viewModel.handleIntent(ChatIntent.TogglePinMessage(msg))
                    selectedMessageForActions = null
                },
                onToggleSave = {
                    viewModel.handleIntent(ChatIntent.ToggleSaveMessage(msg))
                    selectedMessageForActions = null
                },
                onCopy = {
                    val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as? ClipboardManager
                    clipboard?.setPrimaryClip(ClipData.newPlainText("Bondoo Message", msg.body))
                    selectedMessageForActions = null
                    coroutineScope.launch { snackbarHostState.showSnackbar("Copied to clipboard") }
                },
                onDeleteForMe = {
                    viewModel.handleIntent(ChatIntent.DeleteMessage(msg.id, forEveryone = false))
                    selectedMessageForActions = null
                },
                onDeleteForEveryone = {
                    viewModel.handleIntent(ChatIntent.DeleteMessage(msg.id, forEveryone = true))
                    selectedMessageForActions = null
                },
                onReport = {
                    selectedMessageForActions = null
                    reportingMessage = msg
                }
            )
        }
    }

    // Forward Message Picker Sheet
    state.forwardingMessage?.let { msgToForward ->
        ModalBottomSheet(onDismissRequest = { viewModel.handleIntent(ChatIntent.OpenForwardSheet(null)) }) {
            ForwardMessageSheetContent(
                message = msgToForward,
                conversations = state.forwardTargets,
                friends = state.forwardFriends,
                onForwardToConversation = { targetId ->
                    viewModel.handleIntent(ChatIntent.ForwardToConversation(msgToForward.id, targetId))
                },
                onForwardToFriend = { friendId ->
                    viewModel.handleIntent(ChatIntent.ForwardToFriend(msgToForward.id, friendId))
                }
            )
        }
    }

    // Chat Info & Group Management Bottom Sheet
    if (state.showChatInfoSheet) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(false)) }
        ) {
            ChatInfoAndGroupSheet(
                state = state,
                onStartAudioCall = {
                    viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(false))
                    viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = false))
                },
                onStartVideoCall = {
                    viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(false))
                    viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = true))
                },
                onToggleMute = { viewModel.handleIntent(ChatIntent.ToggleMuteConversation) },
                onTogglePin = { viewModel.handleIntent(ChatIntent.TogglePinConversation) },
                onToggleArchive = { viewModel.handleIntent(ChatIntent.ToggleArchiveConversation) },
                onSearchInChat = {
                    viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(false))
                    viewModel.handleIntent(ChatIntent.ToggleSearchMode(true))
                },
                onClearHistory = { viewModel.handleIntent(ChatIntent.ClearConversationHistory) },
                onDeleteConversation = { viewModel.handleIntent(ChatIntent.DeleteConversation) },
                onLeaveGroup = { viewModel.handleIntent(ChatIntent.LeaveGroup) },
                onUpdateGroupInfo = { title, desc ->
                    viewModel.handleIntent(ChatIntent.UpdateGroupInfo(title, desc))
                },
                onLoadFriendsForGroup = { viewModel.handleIntent(ChatIntent.LoadFriendsForGroup) },
                onAddMembers = { ids -> viewModel.handleIntent(ChatIntent.AddGroupMembers(ids)) },
                onRemoveMember = { uid -> viewModel.handleIntent(ChatIntent.RemoveGroupMember(uid)) },
                onUpdateMemberRole = { uid, role -> viewModel.handleIntent(ChatIntent.UpdateMemberRole(uid, role)) },
                onMessageMember = { uid -> viewModel.handleIntent(ChatIntent.MessageMemberDirectly(uid)) },
                onOpenMediaUrl = { url -> fullScreenImageUrl = url },
                onOpenFileUrl = { url -> openExternalUrl(context, url) }
            )
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(hostState = snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .clickable { viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(true)) }
                            .padding(vertical = 2.dp)
                    ) {
                        Box(modifier = Modifier.size(38.dp)) {
                            MessengerAvatar(
                                name = state.participantName,
                                avatarUrl = state.participantAvatar,
                                isGroup = state.isGroup,
                                isSaved = state.isSaved,
                                size = 38.dp
                            )
                            if (state.isOnline && !state.isGroup && !state.isSaved) {
                                Box(
                                    modifier = Modifier
                                        .size(11.dp)
                                        .align(Alignment.BottomEnd)
                                        .clip(CircleShape)
                                        .background(ConnectlyGreen)
                                        .border(1.5.dp, Color.White, CircleShape)
                                )
                            }
                        }

                        Spacer(modifier = Modifier.width(10.dp))

                        Column(modifier = Modifier.weight(1f, fill = false)) {
                            Text(
                                text = state.participantName,
                                fontWeight = FontWeight.Bold,
                                fontSize = 15.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                color = TextPrimaryLight
                            )
                            val subtitle = when {
                                state.isTyping -> "${state.typingUserName ?: "Typing"} is typing..."
                                state.isSaved -> "Personal space"
                                state.isGroup -> {
                                    val count = state.conversation?.participantsCount?.takeIf { it > 0 }
                                        ?: state.conversation?.members?.size ?: 0
                                    if (count > 0) "$count members • Tap for group info" else "Group chat"
                                }
                                state.isOnline -> "Active now"
                                else -> "Tap for chat info"
                            }
                            Text(
                                text = subtitle,
                                fontSize = 11.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                color = if (state.isTyping) ConnectlyBlue else if (state.isOnline && !state.isGroup) ConnectlyGreen else TextSecondaryLight
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
                    if (!state.isSaved) {
                        IconButton(onClick = { viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = false)) }) {
                            Icon(Icons.Default.Call, contentDescription = "Audio Call", tint = ConnectlyBlue)
                        }
                        IconButton(onClick = { viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = true)) }) {
                            Icon(Icons.Default.Videocam, contentDescription = "Video Call", tint = ConnectlyBlue)
                        }
                    }
                    IconButton(onClick = { viewModel.handleIntent(ChatIntent.ToggleSearchMode(!state.isSearchMode)) }) {
                        Icon(Icons.Default.Search, contentDescription = "Search in Chat", tint = TextPrimaryLight)
                    }
                    IconButton(onClick = { viewModel.handleIntent(ChatIntent.ToggleChatInfoSheet(true)) }) {
                        Icon(Icons.Default.Info, contentDescription = "Chat Info", tint = ConnectlyBlue)
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
            // In-Chat Search Bar
            if (state.isSearchMode) {
                Surface(color = Color.White, shadowElevation = 2.dp) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 12.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        OutlinedTextField(
                            value = state.searchQuery,
                            onValueChange = { viewModel.handleIntent(ChatIntent.SearchQueryChanged(it)) },
                            placeholder = { Text("Search messages in this chat...", fontSize = 13.sp) },
                            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                            singleLine = true,
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(22.dp)
                        )
                        IconButton(onClick = { viewModel.handleIntent(ChatIntent.ToggleSearchMode(false)) }) {
                            Icon(Icons.Default.Close, contentDescription = "Close Search")
                        }
                    }
                }
            }

            // Pinned Message Banner
            if (state.pinnedMessages.isNotEmpty() && !state.isSearchMode) {
                val topPin = state.pinnedMessages.first()
                Surface(
                    color = Color(0xFFEFF6FF),
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { showPinnedListDialog = true }
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            Icons.Default.PushPin,
                            contentDescription = "Pinned",
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "Pinned Message (${state.pinnedMessages.size})",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = ConnectlyBlue
                            )
                            Text(
                                text = topPin.message.body.ifBlank { "[Attachment]" },
                                fontSize = 12.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                color = TextPrimaryLight
                            )
                        }
                        Text("View", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = ConnectlyBlue)
                    }
                }
            }

            // Message Request Banner
            if (state.isPendingRequest) {
                Surface(color = Color(0xFFFFFBEB), shadowElevation = 1.dp) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 10.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = "${state.participantName} sent you a message request",
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp,
                            color = TextPrimaryLight
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Button(
                                onClick = { viewModel.handleIntent(ChatIntent.HandleMessageRequest("accept")) },
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp)
                            ) {
                                Text("Accept", fontSize = 12.sp)
                            }
                            OutlinedButton(
                                onClick = { viewModel.handleIntent(ChatIntent.HandleMessageRequest("delete")) },
                                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp)
                            ) {
                                Text("Delete", fontSize = 12.sp)
                            }
                            TextButton(
                                onClick = { viewModel.handleIntent(ChatIntent.HandleMessageRequest("block")) }
                            ) {
                                Text("Block", fontSize = 12.sp, color = Color(0xFFDC2626))
                            }
                        }
                    }
                }
            }

            // Upload progress strip
            if (state.isUploadingAttachment) {
                LinearProgressIndicator(
                    modifier = Modifier.fillMaxWidth(),
                    color = ConnectlyBlue
                )
            }

            // Messages List
            Box(modifier = Modifier.weight(1f)) {
                when {
                    state.isLoading && state.displayedMessages.isEmpty() -> {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = ConnectlyBlue)
                        }
                    }
                    state.displayedMessages.isEmpty() -> {
                        EmptyChatPlaceholder(
                            participantName = state.participantName,
                            participantAvatar = state.participantAvatar,
                            isGroup = state.isGroup,
                            isSaved = state.isSaved,
                            isSearch = state.isSearchMode && state.searchQuery.isNotBlank()
                        )
                    }
                    else -> {
                        LazyColumn(
                            state = listState,
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 12.dp),
                            contentPadding = PaddingValues(vertical = 10.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(
                                items = state.displayedMessages,
                                key = { msg -> if (msg.id > 0) "msg_${msg.id}" else msg.clientMessageId }
                            ) { message ->
                                ChatMessageBubble(
                                    message = message,
                                    isGroupChat = state.isGroup,
                                    isPlayingVoice = state.playingMessageId == message.id && state.isAudioPlaying,
                                    onLongPress = { selectedMessageForActions = message },
                                    onToggleVoice = { viewModel.handleIntent(ChatIntent.TogglePlayVoice(message)) },
                                    onToggleReaction = { emoji ->
                                        viewModel.handleIntent(ChatIntent.ReactMessage(message.id, emoji))
                                    },
                                    onRetry = { viewModel.handleIntent(ChatIntent.RetryMessage(message)) },
                                    onOpenImage = { url -> fullScreenImageUrl = url },
                                    onOpenFile = { url -> openExternalUrl(context, url) },
                                    onCallBack = { isVideo ->
                                        viewModel.handleIntent(ChatIntent.StartCallFromChat(isVideo = isVideo))
                                    }
                                )
                            }
                        }
                    }
                }
            }

            // Reply or Edit Banner above Composer
            val activeReply = state.replyingToMessage
            val activeEdit = state.editingMessage
            if (activeReply != null || activeEdit != null) {
                Surface(color = Color(0xFFF1F5F9)) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 14.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = if (activeEdit != null) Icons.Default.Edit else Icons.AutoMirrored.Filled.Reply,
                            contentDescription = null,
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = if (activeEdit != null) "Editing message" else "Replying to ${activeReply?.senderName}",
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Bold,
                                color = ConnectlyBlue
                            )
                            Text(
                                text = (activeEdit?.body ?: activeReply?.body).orEmpty().ifBlank { "[Attachment]" },
                                fontSize = 12.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                color = TextSecondaryLight
                            )
                        }
                        IconButton(
                            onClick = {
                                if (activeEdit != null) {
                                    inputText = ""
                                    viewModel.handleIntent(ChatIntent.StartEditMessage(null))
                                } else {
                                    viewModel.handleIntent(ChatIntent.SetReplyTo(null))
                                }
                            },
                            modifier = Modifier.size(28.dp)
                        ) {
                            Icon(Icons.Default.Close, contentDescription = "Cancel", modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }

            // Composer Bar (Normal Composer OR Live Voice Recording Bar)
            Surface(
                color = MaterialTheme.colorScheme.surface,
                tonalElevation = 4.dp,
                shadowElevation = 4.dp,
                modifier = Modifier.fillMaxWidth()
            ) {
                if (isRecording) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 14.dp, vertical = 10.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        IconButton(onClick = { finishVoiceRecorder(send = false) }) {
                            Icon(Icons.Default.Delete, contentDescription = "Cancel Recording", tint = Color(0xFFDC2626))
                        }
                        Box(
                            modifier = Modifier
                                .size(10.dp)
                                .clip(CircleShape)
                                .background(Color(0xFFDC2626))
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "Recording voice • ${formatDurationSeconds(recordingSeconds)}",
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 14.sp,
                            color = TextPrimaryLight,
                            modifier = Modifier.weight(1f)
                        )
                        Button(
                            onClick = { finishVoiceRecorder(send = true) },
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            shape = RoundedCornerShape(20.dp)
                        ) {
                            Icon(Icons.AutoMirrored.Filled.Send, contentDescription = "Send Voice", modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Send")
                        }
                    }
                } else {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 8.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        IconButton(onClick = { showAttachmentMenu = true }) {
                            Icon(Icons.Default.AddCircle, contentDescription = "More Attachments", tint = ConnectlyBlue)
                        }

                        if (inputText.isBlank() && state.editingMessage == null) {
                            IconButton(
                                onClick = {
                                    mediaPickerLauncher.launch(
                                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageAndVideo)
                                    )
                                }
                            ) {
                                Icon(Icons.Default.Image, contentDescription = "Gallery", tint = ConnectlyBlue)
                            }
                        }

                        OutlinedTextField(
                            value = inputText,
                            onValueChange = { newText ->
                                inputText = newText
                                viewModel.handleIntent(ChatIntent.TypingChanged(newText.isNotBlank()))
                            },
                            placeholder = {
                                Text(
                                    if (state.editingMessage != null) "Edit message..." else "Message...",
                                    fontSize = 14.sp
                                )
                            },
                            modifier = Modifier
                                .weight(1f)
                                .heightIn(min = 44.dp, max = 120.dp),
                            shape = RoundedCornerShape(22.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedContainerColor = Color(0xFFF1F5F9),
                                unfocusedContainerColor = Color(0xFFF1F5F9),
                                focusedBorderColor = Color.Transparent,
                                unfocusedBorderColor = Color.Transparent
                            )
                        )

                        Spacer(modifier = Modifier.width(4.dp))

                        if (inputText.isNotBlank()) {
                            IconButton(
                                onClick = {
                                    val textToSend = inputText
                                    inputText = ""
                                    viewModel.handleIntent(ChatIntent.TypingChanged(false))
                                    viewModel.handleIntent(ChatIntent.SendTextMessage(textToSend))
                                }
                            ) {
                                Icon(
                                    imageVector = if (state.editingMessage != null) Icons.Default.CheckCircle else Icons.AutoMirrored.Filled.Send,
                                    contentDescription = "Send",
                                    tint = ConnectlyBlue
                                )
                            }
                        } else {
                            IconButton(
                                onClick = {
                                    val hasAudio = ContextCompat.checkSelfPermission(
                                        context,
                                        Manifest.permission.RECORD_AUDIO
                                    ) == PackageManager.PERMISSION_GRANTED
                                    if (hasAudio) {
                                        startVoiceRecorder()
                                    } else {
                                        audioPermissionLauncher.launch(Manifest.permission.RECORD_AUDIO)
                                    }
                                }
                            ) {
                                Icon(Icons.Default.Mic, contentDescription = "Record Voice Message", tint = ConnectlyBlue)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun EmptyChatPlaceholder(
    participantName: String,
    participantAvatar: String?,
    isGroup: Boolean,
    isSaved: Boolean,
    isSearch: Boolean
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        MessengerAvatar(
            name = participantName,
            avatarUrl = participantAvatar,
            isGroup = isGroup,
            isSaved = isSaved,
            size = 72.dp
        )
        Spacer(modifier = Modifier.height(12.dp))
        Text(
            text = if (isSearch) "No matching messages" else participantName,
            fontWeight = FontWeight.Bold,
            fontSize = 18.sp,
            color = TextPrimaryLight
        )
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = when {
                isSearch -> "Try another search keyword."
                isSaved -> "Save notes, links, photos, and documents for yourself here."
                isGroup -> "Say hello to everyone in $participantName!"
                else -> "You're connected on Bondoo. Send a message or start a call!"
            },
            fontSize = 13.sp,
            color = TextSecondaryLight,
            textAlign = TextAlign.Center
        )
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun ChatMessageBubble(
    message: Message,
    isGroupChat: Boolean,
    isPlayingVoice: Boolean,
    onLongPress: () -> Unit,
    onToggleVoice: () -> Unit,
    onToggleReaction: (String) -> Unit,
    onRetry: () -> Unit,
    onOpenImage: (String) -> Unit,
    onOpenFile: (String) -> Unit,
    onCallBack: (Boolean) -> Unit
) {
    // Centered System Message Pill
    if (message.type.equals("system", ignoreCase = true)) {
        Box(modifier = Modifier.fillMaxWidth(), contentAlignment = Alignment.Center) {
            Surface(
                shape = RoundedCornerShape(12.dp),
                color = Color(0xFFE2E8F0).copy(alpha = 0.7f)
            ) {
                Text(
                    text = message.body,
                    fontSize = 11.sp,
                    color = TextSecondaryLight,
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
                    textAlign = TextAlign.Center
                )
            }
        }
        return
    }

    val isMine = message.isMine
    val bubbleColor = when {
        message.isDeleted -> Color(0xFFF1F5F9)
        isMine -> ConnectlyBlue
        else -> Color.White
    }
    val textColor = when {
        message.isDeleted -> TextSecondaryLight
        isMine -> Color.White
        else -> TextPrimaryLight
    }

    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = if (isMine) Arrangement.End else Arrangement.Start,
        verticalAlignment = Alignment.Bottom
    ) {
        if (!isMine && isGroupChat) {
            MessengerAvatar(
                name = message.senderName,
                avatarUrl = message.senderAvatar,
                isGroup = false,
                isSaved = false,
                size = 28.dp
            )
            Spacer(modifier = Modifier.width(6.dp))
        }

        Column(
            horizontalAlignment = if (isMine) Alignment.End else Alignment.Start,
            modifier = Modifier.widthIn(max = 290.dp)
        ) {
            if (!isMine && isGroupChat) {
                Text(
                    text = message.senderName,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = TextSecondaryLight,
                    modifier = Modifier.padding(start = 4.dp, bottom = 2.dp)
                )
            }

            Card(
                shape = RoundedCornerShape(
                    topStart = 18.dp,
                    topEnd = 18.dp,
                    bottomStart = if (isMine) 18.dp else 4.dp,
                    bottomEnd = if (isMine) 4.dp else 18.dp
                ),
                colors = CardDefaults.cardColors(containerColor = bubbleColor),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.combinedClickable(
                    onClick = {},
                    onLongClick = onLongPress
                )
            ) {
                Column(modifier = Modifier.padding(vertical = 6.dp)) {
                    // Forwarded indicator
                    if (message.isForwarded && !message.isDeleted) {
                        Text(
                            text = "↪ Forwarded",
                            fontSize = 11.sp,
                            fontStyle = FontStyle.Italic,
                            color = if (isMine) Color.White.copy(alpha = 0.8f) else TextSecondaryLight,
                            modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp)
                        )
                    }

                    // Quoted Reply Block
                    val reply = message.replyPreview
                    if (reply != null && !message.isDeleted) {
                        Surface(
                            shape = RoundedCornerShape(8.dp),
                            color = if (isMine) Color.Black.copy(alpha = 0.16f) else Color(0xFFF1F5F9),
                            modifier = Modifier
                                .padding(horizontal = 8.dp, vertical = 4.dp)
                                .fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp)) {
                                Text(
                                    text = reply.senderName,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 11.sp,
                                    color = if (isMine) Color.White else ConnectlyBlue
                                )
                                Text(
                                    text = reply.body.ifBlank { "[Attachment]" },
                                    fontSize = 12.sp,
                                    maxLines = 2,
                                    overflow = TextOverflow.Ellipsis,
                                    color = if (isMine) Color.White.copy(alpha = 0.9f) else TextSecondaryLight
                                )
                            }
                        }
                    }

                    when {
                        message.isDeleted -> {
                            Text(
                                text = "🚫 This message was deleted",
                                fontStyle = FontStyle.Italic,
                                fontSize = 13.sp,
                                color = textColor,
                                modifier = Modifier.padding(horizontal = 14.dp, vertical = 6.dp)
                            )
                        }

                        message.type.equals("call", ignoreCase = true) -> {
                            val isVideoCall = message.callType?.contains("video", ignoreCase = true) == true
                            Row(
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(36.dp)
                                        .clip(CircleShape)
                                        .background(if (isMine) Color.White.copy(alpha = 0.2f) else ConnectlyBlue.copy(alpha = 0.12f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = if (isVideoCall) Icons.Default.Videocam else Icons.Default.Call,
                                        contentDescription = "Call",
                                        tint = if (isMine) Color.White else ConnectlyBlue
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column(modifier = Modifier.weight(1f, fill = false)) {
                                    Text(
                                        text = message.body.ifBlank { if (isVideoCall) "Video Call" else "Audio Call" },
                                        fontWeight = FontWeight.SemiBold,
                                        fontSize = 13.sp,
                                        color = textColor
                                    )
                                    val dur = message.callDurationSeconds ?: 0
                                    val sub = if (dur > 0) formatDurationSeconds(dur) else (message.callOutcome ?: "Tap to call back")
                                    Text(
                                        text = sub,
                                        fontSize = 11.sp,
                                        color = if (isMine) Color.White.copy(alpha = 0.85f) else TextSecondaryLight
                                    )
                                }
                                Spacer(modifier = Modifier.width(8.dp))
                                TextButton(onClick = { onCallBack(isVideoCall) }) {
                                    Text(
                                        "Call",
                                        fontWeight = FontWeight.Bold,
                                        color = if (isMine) Color.White else ConnectlyBlue
                                    )
                                }
                            }
                        }

                        message.type.equals("voice", ignoreCase = true) -> {
                            Row(
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                IconButton(
                                    onClick = onToggleVoice,
                                    modifier = Modifier
                                        .size(36.dp)
                                        .clip(CircleShape)
                                        .background(if (isMine) Color.White.copy(alpha = 0.22f) else ConnectlyBlue)
                                ) {
                                    Icon(
                                        imageVector = if (isPlayingVoice) Icons.Default.Pause else Icons.Default.PlayArrow,
                                        contentDescription = if (isPlayingVoice) "Pause" else "Play",
                                        tint = Color.White
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column {
                                    Text(
                                        text = if (isPlayingVoice) "Playing voice note..." else "▂▄▆█▄▂▅▇▃▅▆▂▄",
                                        color = textColor,
                                        fontSize = 13.sp,
                                        fontWeight = FontWeight.SemiBold
                                    )
                                    Text(
                                        text = formatDurationSeconds(message.audioDurationSeconds ?: 0),
                                        color = if (isMine) Color.White.copy(alpha = 0.85f) else TextSecondaryLight,
                                        fontSize = 11.sp
                                    )
                                }
                            }
                        }

                        else -> {
                            // Render Image / Video / Document Attachments
                            val imageAttachments = message.attachments.filter { it.isImage }
                            val fallbackImageUrls = if (imageAttachments.isEmpty() && message.type == "media") {
                                message.mediaUrls
                            } else {
                                emptyList()
                            }

                            imageAttachments.forEach { img ->
                                AsyncImage(
                                    model = img.url.ifBlank { img.previewUrl },
                                    contentDescription = img.fileName.ifBlank { "Photo" },
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier
                                        .padding(horizontal = 6.dp, vertical = 3.dp)
                                        .fillMaxWidth()
                                        .heightIn(min = 140.dp, max = 240.dp)
                                        .clip(RoundedCornerShape(12.dp))
                                        .clickable { onOpenImage(img.url) }
                                )
                            }

                            fallbackImageUrls.forEach { url ->
                                AsyncImage(
                                    model = url,
                                    contentDescription = "Photo",
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier
                                        .padding(horizontal = 6.dp, vertical = 3.dp)
                                        .fillMaxWidth()
                                        .heightIn(min = 140.dp, max = 240.dp)
                                        .clip(RoundedCornerShape(12.dp))
                                        .clickable { onOpenImage(url) }
                                )
                            }

                            val otherAttachments = message.attachments.filterNot { it.isImage }
                            otherAttachments.forEach { att ->
                                Surface(
                                    shape = RoundedCornerShape(10.dp),
                                    color = if (isMine) Color.White.copy(alpha = 0.16f) else Color(0xFFF1F5F9),
                                    modifier = Modifier
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                        .fillMaxWidth()
                                        .clickable { onOpenFile(att.downloadUrl ?: att.url) }
                                ) {
                                    Row(
                                        modifier = Modifier.padding(10.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(
                                            imageVector = if (att.isVideo) Icons.Default.PlayCircle else Icons.AutoMirrored.Filled.InsertDriveFile,
                                            contentDescription = null,
                                            tint = if (isMine) Color.White else ConnectlyBlue,
                                            modifier = Modifier.size(28.dp)
                                        )
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                text = att.fileName.ifBlank { if (att.isVideo) "Video Attachment" else "Document" },
                                                fontWeight = FontWeight.SemiBold,
                                                fontSize = 12.sp,
                                                maxLines = 1,
                                                overflow = TextOverflow.Ellipsis,
                                                color = textColor
                                            )
                                            if (att.fileSize > 0) {
                                                Text(
                                                    text = formatFileSize(att.fileSize),
                                                    fontSize = 10.sp,
                                                    color = if (isMine) Color.White.copy(alpha = 0.8f) else TextSecondaryLight
                                                )
                                            }
                                        }
                                        Icon(
                                            Icons.Default.Download,
                                            contentDescription = "Open",
                                            tint = if (isMine) Color.White else ConnectlyBlue,
                                            modifier = Modifier.size(18.dp)
                                        )
                                    }
                                }
                            }

                            if (message.body.isNotBlank()) {
                                Text(
                                    text = message.body,
                                    color = textColor,
                                    fontSize = 14.sp,
                                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 6.dp)
                                )
                            }
                        }
                    }
                }
            }

            // Reactions Row
            if (message.reactions.isNotEmpty() && !message.isDeleted) {
                Row(
                    horizontalArrangement = Arrangement.spacedBy(4.dp),
                    modifier = Modifier.padding(top = 3.dp)
                ) {
                    message.reactions.forEach { (emoji, count) ->
                        if (count > 0) {
                            val isSelected = message.myReaction == emoji
                            Surface(
                                shape = RoundedCornerShape(12.dp),
                                color = if (isSelected) ConnectlyBlue.copy(alpha = 0.16f) else Color.White,
                                border = androidx.compose.foundation.BorderStroke(
                                    1.dp,
                                    if (isSelected) ConnectlyBlue else Color(0xFFE2E8F0)
                                ),
                                modifier = Modifier.clickable { onToggleReaction(emoji) }
                            ) {
                                Text(
                                    text = if (count > 1) "$emoji $count" else emoji,
                                    fontSize = 11.sp,
                                    modifier = Modifier.padding(horizontal = 7.dp, vertical = 2.dp)
                                )
                            }
                        }
                    }
                }
            }

            // Timestamp, Flags & Delivery Receipt
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                modifier = Modifier.padding(top = 2.dp, start = 4.dp, end = 4.dp)
            ) {
                if (message.isPinned) {
                    Icon(Icons.Default.PushPin, contentDescription = "Pinned", tint = ConnectlyBlue, modifier = Modifier.size(11.dp))
                }
                if (message.isSaved) {
                    Icon(Icons.Default.Bookmark, contentDescription = "Saved", tint = ConnectlyBlue, modifier = Modifier.size(11.dp))
                }
                if (message.formattedTime.isNotBlank()) {
                    Text(message.formattedTime, fontSize = 10.sp, color = TextSecondaryLight)
                }
                if (message.isEdited && !message.isDeleted) {
                    Text("• Edited", fontSize = 10.sp, color = TextSecondaryLight)
                }
                if (isMine) {
                    if (message.deliveryStatus == DeliveryStatus.FAILED) {
                        Text(
                            text = "⚠️ Tap to retry",
                            fontSize = 10.sp,
                            color = Color(0xFFDC2626),
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.clickable { onRetry() }
                        )
                    } else {
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
}

@Composable
private fun MessageActionsSheetContent(
    message: Message,
    onReact: (String) -> Unit,
    onReply: () -> Unit,
    onEdit: () -> Unit,
    onForward: () -> Unit,
    onTogglePin: () -> Unit,
    onToggleSave: () -> Unit,
    onCopy: () -> Unit,
    onDeleteForMe: () -> Unit,
    onDeleteForEveryone: () -> Unit,
    onReport: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 20.dp)
    ) {
        if (!message.isDeleted) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 10.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                listOf("❤️", "😂", "😮", "😢", "😡", "👍").forEach { emoji ->
                    val selected = message.myReaction == emoji
                    Box(
                        modifier = Modifier
                            .size(44.dp)
                            .clip(CircleShape)
                            .background(if (selected) ConnectlyBlue.copy(alpha = 0.18f) else Color(0xFFF1F5F9))
                            .clickable { onReact(emoji) },
                        contentAlignment = Alignment.Center
                    ) {
                        Text(emoji, fontSize = 22.sp)
                    }
                }
            }
            HorizontalDivider(color = Color(0xFFF1F5F9))
        }

        if (!message.isDeleted) {
            SheetActionRow(Icons.AutoMirrored.Filled.Reply, "Reply", onClick = onReply)
            if (message.isMine && message.type == "text") {
                SheetActionRow(Icons.Default.Edit, "Edit Message", onClick = onEdit)
            }
            SheetActionRow(Icons.AutoMirrored.Filled.Forward, "Forward Message", onClick = onForward)
            SheetActionRow(
                Icons.Default.PushPin,
                if (message.isPinned) "Unpin Message" else "Pin Message",
                onClick = onTogglePin
            )
            SheetActionRow(
                Icons.Default.Bookmark,
                if (message.isSaved) "Remove from Saved Messages" else "Save Message",
                onClick = onToggleSave
            )
            if (message.body.isNotBlank()) {
                SheetActionRow(Icons.Default.ContentCopy, "Copy Text", onClick = onCopy)
            }
        }

        SheetActionRow(
            Icons.Default.DeleteOutline,
            "Delete for Me",
            tint = Color(0xFFDC2626),
            onClick = onDeleteForMe
        )
        if (message.isMine && !message.isDeleted) {
            SheetActionRow(
                Icons.Default.DeleteForever,
                "Unsend / Delete for Everyone",
                tint = Color(0xFFDC2626),
                onClick = onDeleteForEveryone
            )
        }
        if (!message.isMine) {
            SheetActionRow(
                Icons.Default.Flag,
                "Report Message",
                tint = Color(0xFFDC2626),
                onClick = onReport
            )
        }
    }
}

@Composable
private fun ForwardMessageSheetContent(
    message: Message,
    conversations: List<Conversation>,
    friends: List<com.connectly.android.core.model.ActivePresenceFriend>,
    onForwardToConversation: (Long) -> Unit,
    onForwardToFriend: (Long) -> Unit
) {
    var filterQuery by remember { mutableStateOf("") }
    val filteredConvs = remember(conversations, filterQuery) {
        if (filterQuery.isBlank()) conversations
        else conversations.filter { it.displayTitle.contains(filterQuery, ignoreCase = true) }
    }
    val filteredFriends = remember(friends, filterQuery) {
        if (filterQuery.isBlank()) friends
        else friends.filter {
            it.name.contains(filterQuery, ignoreCase = true) ||
                it.username.contains(filterQuery, ignoreCase = true)
        }
    }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .fillMaxHeight(0.75f)
            .padding(horizontal = 16.dp)
    ) {
        Text("Forward Message", fontWeight = FontWeight.Bold, fontSize = 18.sp)
        Text(
            text = message.body.ifBlank { "[Attachment]" },
            fontSize = 12.sp,
            color = TextSecondaryLight,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
        Spacer(modifier = Modifier.height(10.dp))
        OutlinedTextField(
            value = filterQuery,
            onValueChange = { filterQuery = it },
            placeholder = { Text("Search chats or friends...") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            singleLine = true,
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(22.dp)
        )
        Spacer(modifier = Modifier.height(10.dp))

        LazyColumn(modifier = Modifier.weight(1f)) {
            if (filteredConvs.isNotEmpty()) {
                item {
                    Text(
                        "Recent Chats",
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(vertical = 6.dp)
                    )
                }
                items(filteredConvs, key = { "fwd_conv_${it.id}" }) { conv ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        MessengerAvatar(
                            name = conv.displayTitle,
                            avatarUrl = conv.displayAvatar,
                            isGroup = conv.isGroup,
                            isSaved = conv.isSaved,
                            size = 40.dp
                        )
                        Spacer(modifier = Modifier.width(10.dp))
                        Text(conv.displayTitle, fontWeight = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                        Button(
                            onClick = { onForwardToConversation(conv.id) },
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp)
                        ) {
                            Text("Send", fontSize = 12.sp)
                        }
                    }
                }
            }

            if (filteredFriends.isNotEmpty()) {
                item {
                    Text(
                        "Friends",
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(vertical = 6.dp)
                    )
                }
                items(filteredFriends, key = { "fwd_friend_${it.id}" }) { friend ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        MessengerAvatar(
                            name = friend.name,
                            avatarUrl = friend.avatarUrl,
                            isGroup = false,
                            isSaved = false,
                            size = 40.dp
                        )
                        Spacer(modifier = Modifier.width(10.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(friend.name, fontWeight = FontWeight.SemiBold)
                            Text("@${friend.username}", fontSize = 11.sp, color = TextSecondaryLight)
                        }
                        OutlinedButton(
                            onClick = { onForwardToFriend(friend.id) },
                            contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp)
                        ) {
                            Text("Send", fontSize = 12.sp)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ChatInfoAndGroupSheet(
    state: ChatState,
    onStartAudioCall: () -> Unit,
    onStartVideoCall: () -> Unit,
    onToggleMute: () -> Unit,
    onTogglePin: () -> Unit,
    onToggleArchive: () -> Unit,
    onSearchInChat: () -> Unit,
    onClearHistory: () -> Unit,
    onDeleteConversation: () -> Unit,
    onLeaveGroup: () -> Unit,
    onUpdateGroupInfo: (String, String?) -> Unit,
    onLoadFriendsForGroup: () -> Unit,
    onAddMembers: (List<Long>) -> Unit,
    onRemoveMember: (Long) -> Unit,
    onUpdateMemberRole: (Long, String) -> Unit,
    onMessageMember: (Long) -> Unit,
    onOpenMediaUrl: (String) -> Unit,
    onOpenFileUrl: (String) -> Unit
) {
    val conv = state.conversation
    var showEditGroupDialog by remember { mutableStateOf(false) }
    var showAddMembersDialog by remember { mutableStateOf(false) }
    var confirmClear by remember { mutableStateOf(false) }
    var confirmDelete by remember { mutableStateOf(false) }
    var confirmLeave by remember { mutableStateOf(false) }
    var selectedMediaTab by remember { mutableIntStateOf(0) } // 0 = Photos/Videos, 1 = Files

    if (showEditGroupDialog && conv != null) {
        var editTitle by remember { mutableStateOf(conv.title.orEmpty()) }
        var editDesc by remember { mutableStateOf(conv.description.orEmpty()) }
        AlertDialog(
            onDismissRequest = { showEditGroupDialog = false },
            title = { Text("Edit Group Info", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = editTitle,
                        onValueChange = { editTitle = it },
                        label = { Text("Group Name") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = editDesc,
                        onValueChange = { editDesc = it },
                        label = { Text("Description") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        onUpdateGroupInfo(editTitle, editDesc)
                        showEditGroupDialog = false
                    },
                    enabled = editTitle.isNotBlank()
                ) {
                    Text("Save")
                }
            },
            dismissButton = {
                TextButton(onClick = { showEditGroupDialog = false }) { Text("Cancel") }
            }
        )
    }

    if (showAddMembersDialog) {
        var selectedIds by remember { mutableStateOf(setOf<Long>()) }
        AlertDialog(
            onDismissRequest = { showAddMembersDialog = false },
            title = { Text("Add Friends to Group", fontWeight = FontWeight.Bold) },
            text = {
                if (state.availableFriendsForGroup.isEmpty()) {
                    Text("All your friends are already in this group.", fontSize = 13.sp, color = TextSecondaryLight)
                } else {
                    LazyColumn(modifier = Modifier.heightIn(max = 300.dp)) {
                        items(state.availableFriendsForGroup, key = { "add_mem_${it.id}" }) { friend ->
                            val checked = selectedIds.contains(friend.id)
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable {
                                        selectedIds = if (checked) selectedIds - friend.id else selectedIds + friend.id
                                    }
                                    .padding(vertical = 6.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                MessengerAvatar(
                                    name = friend.name,
                                    avatarUrl = friend.avatarUrl,
                                    isGroup = false,
                                    isSaved = false,
                                    size = 36.dp
                                )
                                Spacer(modifier = Modifier.width(10.dp))
                                Text(friend.name, modifier = Modifier.weight(1f), fontWeight = FontWeight.Medium)
                                Checkbox(
                                    checked = checked,
                                    onCheckedChange = {
                                        selectedIds = if (checked) selectedIds - friend.id else selectedIds + friend.id
                                    }
                                )
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        onAddMembers(selectedIds.toList())
                        showAddMembersDialog = false
                    },
                    enabled = selectedIds.isNotEmpty()
                ) {
                    Text("Add (${selectedIds.size})")
                }
            },
            dismissButton = {
                TextButton(onClick = { showAddMembersDialog = false }) { Text("Cancel") }
            }
        )
    }

    if (confirmClear) {
        AlertDialog(
            onDismissRequest = { confirmClear = false },
            title = { Text("Clear Chat History?") },
            text = { Text("This clears all messages in this chat from your view.") },
            confirmButton = {
                TextButton(onClick = { confirmClear = false; onClearHistory() }) {
                    Text("Clear", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = { TextButton(onClick = { confirmClear = false }) { Text("Cancel") } }
        )
    }

    if (confirmDelete) {
        AlertDialog(
            onDismissRequest = { confirmDelete = false },
            title = { Text("Delete Conversation?") },
            text = { Text("This conversation will be removed from your Messenger list.") },
            confirmButton = {
                TextButton(onClick = { confirmDelete = false; onDeleteConversation() }) {
                    Text("Delete", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = { TextButton(onClick = { confirmDelete = false }) { Text("Cancel") } }
        )
    }

    if (confirmLeave) {
        AlertDialog(
            onDismissRequest = { confirmLeave = false },
            title = { Text("Leave Group?") },
            text = { Text("You won't receive new messages from ${state.participantName} unless someone adds you back.") },
            confirmButton = {
                TextButton(onClick = { confirmLeave = false; onLeaveGroup() }) {
                    Text("Leave Group", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = { TextButton(onClick = { confirmLeave = false }) { Text("Cancel") } }
        )
    }

    LazyColumn(
        modifier = Modifier
            .fillMaxWidth()
            .fillMaxHeight(0.86f),
        contentPadding = PaddingValues(bottom = 28.dp)
    ) {
        // Profile / Group Header
        item {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 8.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                MessengerAvatar(
                    name = state.participantName,
                    avatarUrl = state.participantAvatar,
                    isGroup = state.isGroup,
                    isSaved = state.isSaved,
                    size = 76.dp
                )
                Spacer(modifier = Modifier.height(10.dp))
                Text(state.participantName, fontWeight = FontWeight.Bold, fontSize = 20.sp)
                val sub = when {
                    state.isGroup && !conv?.description.isNullOrBlank() -> conv?.description.orEmpty()
                    state.isGroup -> "${conv?.members?.size ?: conv?.participantsCount ?: 0} group members"
                    !conv?.otherUser?.username.isNullOrBlank() -> "@${conv?.otherUser?.username}"
                    else -> if (state.isOnline) "Active now" else "Bondoo Messenger"
                }
                Text(sub, fontSize = 13.sp, color = TextSecondaryLight, textAlign = TextAlign.Center)

                Spacer(modifier = Modifier.height(16.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    if (!state.isSaved) {
                        AttachmentOptionItem(Icons.Default.Call, "Audio", ConnectlyBlue, onStartAudioCall)
                        AttachmentOptionItem(Icons.Default.Videocam, "Video", ConnectlyBlue, onStartVideoCall)
                    }
                    AttachmentOptionItem(
                        icon = if (conv?.isMuted == true) Icons.Default.NotificationsOff else Icons.Default.Notifications,
                        label = if (conv?.isMuted == true) "Unmute" else "Mute",
                        color = Color(0xFF64748B),
                        onClick = onToggleMute
                    )
                    AttachmentOptionItem(
                        icon = Icons.Default.PushPin,
                        label = if (conv?.isPinned == true) "Unpin" else "Pin",
                        color = Color(0xFF0EA5E9),
                        onClick = onTogglePin
                    )
                    AttachmentOptionItem(Icons.Default.Search, "Search", Color(0xFF8B5CF6), onSearchInChat)
                }
            }
            HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp), color = Color(0xFFF1F5F9))
        }

        // Group Management Section
        if (state.isGroup && conv != null) {
            val amIAdmin = conv.myRole == "admin" || conv.creatorId != null
            item {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 20.dp, vertical = 4.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "Group Members (${conv.members.size})",
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        modifier = Modifier.weight(1f)
                    )
                    TextButton(onClick = { showEditGroupDialog = true }) {
                        Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Edit")
                    }
                    TextButton(
                        onClick = {
                            onLoadFriendsForGroup()
                            showAddMembersDialog = true
                        }
                    ) {
                        Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Add")
                    }
                }
            }

            items(conv.members, key = { "grp_member_${it.id}" }) { member ->
                val memberRole = conv.memberRoles[member.id] ?: "member"
                var memberMenuExpanded by remember { mutableStateOf(false) }

                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { memberMenuExpanded = true }
                        .padding(horizontal = 20.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    MessengerAvatar(
                        name = member.name,
                        avatarUrl = member.avatar,
                        isGroup = false,
                        isSaved = false,
                        size = 40.dp
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(member.name, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        Text("@${member.username}", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                    if (memberRole == "admin" || conv.creatorId == member.id) {
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = ConnectlyBlue.copy(alpha = 0.12f)
                        ) {
                            Text(
                                text = if (conv.creatorId == member.id) "Creator" else "Admin",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = ConnectlyBlue,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                            )
                        }
                    }

                    Box {
                        IconButton(onClick = { memberMenuExpanded = true }) {
                            Icon(Icons.Default.MoreVert, contentDescription = "Member Options", tint = TextSecondaryLight)
                        }
                        DropdownMenu(
                            expanded = memberMenuExpanded,
                            onDismissRequest = { memberMenuExpanded = false }
                        ) {
                            DropdownMenuItem(
                                text = { Text("Message ${member.name}") },
                                onClick = {
                                    memberMenuExpanded = false
                                    onMessageMember(member.id)
                                }
                            )
                            if (amIAdmin && conv.creatorId != member.id) {
                                val nextRole = if (memberRole == "admin") "member" else "admin"
                                DropdownMenuItem(
                                    text = { Text(if (memberRole == "admin") "Dismiss as Admin" else "Make Group Admin") },
                                    onClick = {
                                        memberMenuExpanded = false
                                        onUpdateMemberRole(member.id, nextRole)
                                    }
                                )
                                DropdownMenuItem(
                                    text = { Text("Remove from Group", color = Color(0xFFDC2626)) },
                                    onClick = {
                                        memberMenuExpanded = false
                                        onRemoveMember(member.id)
                                    }
                                )
                            }
                        }
                    }
                }
            }

            item {
                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp), color = Color(0xFFF1F5F9))
            }
        }

        // Shared Media & Files Section
        item {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FilterChip(
                    selected = selectedMediaTab == 0,
                    onClick = { selectedMediaTab = 0 },
                    label = { Text("Shared Photos & Videos (${state.sharedMedia.size})") }
                )
                FilterChip(
                    selected = selectedMediaTab == 1,
                    onClick = { selectedMediaTab = 1 },
                    label = { Text("Files (${state.sharedFiles.size})") }
                )
            }

            if (selectedMediaTab == 0) {
                if (state.sharedMedia.isEmpty()) {
                    Text(
                        "No shared photos or videos yet.",
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(horizontal = 20.dp, vertical = 8.dp)
                    )
                } else {
                    LazyRow(
                        contentPadding = PaddingValues(horizontal = 20.dp, vertical = 8.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(state.sharedMedia, key = { "shared_m_${it.id}" }) { media ->
                            AsyncImage(
                                model = media.previewUrl ?: media.url,
                                contentDescription = media.fileName,
                                contentScale = ContentScale.Crop,
                                modifier = Modifier
                                    .size(84.dp)
                                    .clip(RoundedCornerShape(10.dp))
                                    .background(Color(0xFFE2E8F0))
                                    .clickable { onOpenMediaUrl(media.url) }
                            )
                        }
                    }
                }
            } else {
                if (state.sharedFiles.isEmpty()) {
                    Text(
                        "No shared documents yet.",
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(horizontal = 20.dp, vertical = 8.dp)
                    )
                } else {
                    Column(modifier = Modifier.padding(horizontal = 20.dp)) {
                        state.sharedFiles.forEach { fileAtt ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { onOpenFileUrl(fileAtt.downloadUrl ?: fileAtt.url) }
                                    .padding(vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(Icons.AutoMirrored.Filled.InsertDriveFile, contentDescription = null, tint = ConnectlyBlue)
                                Spacer(modifier = Modifier.width(10.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(fileAtt.fileName.ifBlank { "Document" }, fontWeight = FontWeight.Medium, fontSize = 13.sp)
                                    if (fileAtt.fileSize > 0) {
                                        Text(formatFileSize(fileAtt.fileSize), fontSize = 11.sp, color = TextSecondaryLight)
                                    }
                                }
                                Icon(Icons.Default.Download, contentDescription = "Download", tint = ConnectlyBlue)
                            }
                        }
                    }
                }
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 10.dp), color = Color(0xFFF1F5F9))
        }

        // Privacy & Conversation Management Actions
        item {
            SheetActionRow(
                icon = Icons.Default.Inventory2,
                label = if (conv?.isArchived == true) "Unarchive Conversation" else "Archive Conversation",
                onClick = onToggleArchive
            )
            SheetActionRow(
                icon = Icons.Default.CleaningServices,
                label = "Clear Chat History",
                tint = Color(0xFFDC2626),
                onClick = { confirmClear = true }
            )
            if (state.isGroup) {
                SheetActionRow(
                    icon = Icons.AutoMirrored.Filled.ExitToApp,
                    label = "Leave Group",
                    tint = Color(0xFFDC2626),
                    onClick = { confirmLeave = true }
                )
            } else if (!state.isSaved) {
                SheetActionRow(
                    icon = Icons.Default.DeleteForever,
                    label = "Delete Conversation",
                    tint = Color(0xFFDC2626),
                    onClick = { confirmDelete = true }
                )
            }
        }
    }
}

@Composable
private fun SheetActionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    tint: Color = TextPrimaryLight,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 20.dp, vertical = 13.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = label, tint = tint, modifier = Modifier.size(22.dp))
        Spacer(modifier = Modifier.width(14.dp))
        Text(label, fontSize = 15.sp, fontWeight = FontWeight.Medium, color = tint)
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
                .size(50.dp)
                .clip(CircleShape)
                .background(color.copy(alpha = 0.14f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(imageVector = icon, contentDescription = label, tint = color, modifier = Modifier.size(24.dp))
        }
        Text(text = label, fontSize = 12.sp, fontWeight = FontWeight.Medium, color = TextPrimaryLight)
    }
}

private fun formatDurationSeconds(seconds: Int): String {
    val safe = seconds.coerceAtLeast(0)
    val mins = safe / 60
    val secs = safe % 60
    return String.format(Locale.US, "%d:%02d", mins, secs)
}

private fun formatFileSize(bytes: Long): String {
    if (bytes < 1024L) return "$bytes B"
    val kb = bytes / 1024.0
    if (kb < 1024.0) return String.format(Locale.US, "%.1f KB", kb)
    val mb = kb / 1024.0
    return String.format(Locale.US, "%.1f MB", mb)
}

private fun openExternalUrl(context: Context, url: String) {
    if (url.isBlank()) return
    runCatching {
        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url)).apply {
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(intent)
    }
}

private fun copyUriToTempFile(context: Context, uri: Uri, fallbackExt: String): Pair<File, String>? {
    return runCatching {
        val resolver = context.contentResolver
        val mimeType = resolver.getType(uri) ?: when (fallbackExt) {
            "jpg" -> "image/jpeg"
            else -> "application/octet-stream"
        }
        var displayName = "attachment_${System.currentTimeMillis()}.$fallbackExt"
        resolver.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME), null, null, null)?.use { cursor ->
            val idx = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (idx >= 0 && cursor.moveToFirst()) {
                val rawName = cursor.getString(idx)
                if (!rawName.isNullOrBlank()) {
                    displayName = rawName.replace(Regex("[^a-zA-Z0-9._-]"), "_")
                }
            }
        }
        val target = File(context.cacheDir, "upload_${System.currentTimeMillis()}_$displayName")
        resolver.openInputStream(uri)?.use { input ->
            FileOutputStream(target).use { output ->
                input.copyTo(output)
            }
        } ?: return null
        target to mimeType
    }.getOrNull()
}
