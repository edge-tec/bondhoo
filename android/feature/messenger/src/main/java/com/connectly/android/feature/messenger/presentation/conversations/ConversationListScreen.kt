package com.connectly.android.feature.messenger.presentation.conversations

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.combinedClickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.CallMade
import androidx.compose.material.icons.automirrored.filled.CallMissed
import androidx.compose.material.icons.automirrored.filled.CallReceived
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message

@OptIn(ExperimentalMaterial3Api::class, ExperimentalFoundationApi::class)
@Composable
fun ConversationListScreen(
    onNavigateToChat: (Long) -> Unit,
    onNavigateBack: () -> Unit,
    onStartCall: (conversationId: Long?, receiverId: Long?, receiverIds: List<Long>, callType: CallType) -> Unit = { _, _, _, _ -> },
    modifier: Modifier = Modifier,
    viewModel: ConversationsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    var showMuteDurationDialogFor by remember { mutableStateOf<Conversation?>(null) }
    var confirmClearConv by remember { mutableStateOf<Conversation?>(null) }
    var confirmDeleteConv by remember { mutableStateOf<Conversation?>(null) }

    LaunchedEffect(Unit) {
        viewModel.effects.collect { effect ->
            when (effect) {
                is ConversationsEffect.OpenChat -> onNavigateToChat(effect.conversationId)
                is ConversationsEffect.StartCall -> onStartCall(
                    effect.conversationId,
                    effect.receiverId,
                    effect.receiverIds,
                    effect.callType
                )
            }
        }
    }

    LaunchedEffect(Unit) {
        while (kotlinx.coroutines.currentCoroutineContext()[kotlinx.coroutines.Job]?.isActive != false) {
            kotlinx.coroutines.delay(8000L)
            viewModel.silentPeriodicSync()
        }
    }

    LaunchedEffect(state.errorMessage, state.statusMessage) {
        val msg = state.errorMessage ?: state.statusMessage
        if (!msg.isNullOrBlank()) {
            snackbarHostState.showSnackbar(msg)
            viewModel.handleIntent(ConversationsIntent.ClearMessages)
        }
    }

    // Mute Duration Picker Dialog
    showMuteDurationDialogFor?.let { conv ->
        AlertDialog(
            onDismissRequest = { showMuteDurationDialogFor = null },
            title = { Text("Mute Notifications", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    listOf(
                        "1h" to "For 1 Hour",
                        "8h" to "For 8 Hours",
                        "24h" to "For 24 Hours",
                        "forever" to "Until I turn it back on"
                    ).forEach { (code, label) ->
                        TextButton(
                            onClick = {
                                viewModel.handleIntent(ConversationsIntent.MuteConversation(conv.id, code))
                                showMuteDurationDialogFor = null
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
                TextButton(onClick = { showMuteDurationDialogFor = null }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Confirm Clear History Dialog
    confirmClearConv?.let { conv ->
        AlertDialog(
            onDismissRequest = { confirmClearConv = null },
            title = { Text("Clear Chat History?", fontWeight = FontWeight.Bold) },
            text = { Text("All messages in \"${conv.displayTitle}\" will be cleared for you. Other participants will still see their copies.") },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(ConversationsIntent.ClearConversation(conv.id))
                        confirmClearConv = null
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFEF4444))
                ) {
                    Text("Clear Chat")
                }
            },
            dismissButton = {
                TextButton(onClick = { confirmClearConv = null }) { Text("Cancel") }
            }
        )
    }

    // Confirm Delete / Leave Dialog
    confirmDeleteConv?.let { conv ->
        AlertDialog(
            onDismissRequest = { confirmDeleteConv = null },
            title = {
                Text(
                    if (conv.isGroup) "Leave Group?" else "Delete Conversation?",
                    fontWeight = FontWeight.Bold
                )
            },
            text = {
                Text(
                    if (conv.isGroup) {
                        "Are you sure you want to leave \"${conv.displayTitle}\"?"
                    } else {
                        "This will clear and remove \"${conv.displayTitle}\" from your inbox."
                    }
                )
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(ConversationsIntent.DeleteOrLeaveConversation(conv))
                        confirmDeleteConv = null
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFEF4444))
                ) {
                    Text(if (conv.isGroup) "Leave Group" else "Delete")
                }
            },
            dismissButton = {
                TextButton(onClick = { confirmDeleteConv = null }) { Text("Cancel") }
            }
        )
    }

    // Conversation Long-Press / Context Options Sheet
    state.selectedConversationForMenu?.let { conv ->
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null)) },
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 24.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 20.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    MessengerAvatar(
                        name = conv.displayTitle,
                        avatarUrl = conv.displayAvatar,
                        isGroup = conv.isGroup,
                        isSaved = conv.isSaved,
                        size = 44.dp
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(conv.displayTitle, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text(
                            if (conv.isGroup) "${conv.participantsCount.coerceAtLeast(1)} members"
                            else if (conv.isOnline) "Active now"
                            else "Conversation options",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                    }
                }
                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                if (!conv.isSaved) {
                    SheetActionRow(
                        icon = Icons.Default.Call,
                        label = if (conv.isGroup) "Start Group Audio Call" else "Audio Call",
                        onClick = {
                            viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null))
                            onStartCall(
                                conv.id,
                                conv.otherUser?.id,
                                emptyList(),
                                if (conv.isGroup) CallType.GROUP_AUDIO else CallType.AUDIO
                            )
                        }
                    )
                    SheetActionRow(
                        icon = Icons.Default.Videocam,
                        label = if (conv.isGroup) "Start Group Video Call" else "Video Call",
                        onClick = {
                            viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null))
                            onStartCall(
                                conv.id,
                                conv.otherUser?.id,
                                emptyList(),
                                if (conv.isGroup) CallType.GROUP_VIDEO else CallType.VIDEO
                            )
                        }
                    )
                }

                SheetActionRow(
                    icon = Icons.Default.PushPin,
                    label = if (conv.isPinned) "Unpin Conversation" else "Pin Conversation",
                    onClick = { viewModel.handleIntent(ConversationsIntent.TogglePin(conv.id)) }
                )

                SheetActionRow(
                    icon = if (conv.isMuted) Icons.Default.NotificationsActive else Icons.Default.NotificationsOff,
                    label = if (conv.isMuted) "Unmute Notifications" else "Mute Notifications",
                    onClick = {
                        if (conv.isMuted) {
                            viewModel.handleIntent(ConversationsIntent.MuteConversation(conv.id, "unmute"))
                        } else {
                            viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null))
                            showMuteDurationDialogFor = conv
                        }
                    }
                )

                SheetActionRow(
                    icon = if (conv.unreadCount > 0) Icons.Default.MarkChatRead else Icons.Default.MarkChatUnread,
                    label = if (conv.unreadCount > 0) "Mark as Read" else "Mark as Unread",
                    onClick = {
                        if (conv.unreadCount > 0) {
                            viewModel.handleIntent(ConversationsIntent.MarkRead(conv.id))
                        } else {
                            viewModel.handleIntent(ConversationsIntent.MarkUnread(conv.id))
                        }
                    }
                )

                SheetActionRow(
                    icon = if (conv.isArchived) Icons.Default.Unarchive else Icons.Default.Archive,
                    label = if (conv.isArchived) "Unarchive Chat" else "Archive Chat",
                    onClick = { viewModel.handleIntent(ConversationsIntent.ToggleArchive(conv.id)) }
                )

                SheetActionRow(
                    icon = Icons.Default.CleaningServices,
                    label = "Clear Chat History",
                    onClick = {
                        viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null))
                        confirmClearConv = conv
                    }
                )

                SheetActionRow(
                    icon = if (conv.isGroup) Icons.AutoMirrored.Filled.ExitToApp else Icons.Default.DeleteOutline,
                    label = if (conv.isGroup) "Leave Group" else "Delete Conversation",
                    tint = Color(0xFFEF4444),
                    onClick = {
                        viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(null))
                        confirmDeleteConv = conv
                    }
                )
            }
        }
    }

    // New Message / New Group / Group Audio & Video Call Modal Bottom Sheet
    if (state.showNewChatDialog) {
        NewChatAndGroupSheet(
            state = state,
            onDismiss = { viewModel.handleIntent(ConversationsIntent.ShowNewChatDialog(false)) },
            onSelectMode = { viewModel.handleIntent(ConversationsIntent.SetComposerMode(it)) },
            onSearchChange = { viewModel.handleIntent(ConversationsIntent.ComposerSearchChanged(it)) },
            onOpenDirectChat = { friend ->
                viewModel.handleIntent(ConversationsIntent.OpenOrCreateDirectChat(friend.id, friend.conversationId))
            },
            onCallFriend = { friend, isVideo ->
                viewModel.handleIntent(ConversationsIntent.ShowNewChatDialog(false))
                onStartCall(
                    friend.conversationId,
                    friend.id,
                    listOf(friend.id),
                    if (isVideo) CallType.VIDEO else CallType.AUDIO
                )
            },
            onToggleGroupMember = { viewModel.handleIntent(ConversationsIntent.ToggleGroupMember(it)) },
            onGroupTitleChange = { viewModel.handleIntent(ConversationsIntent.UpdateNewGroupTitle(it)) },
            onGroupDescChange = { viewModel.handleIntent(ConversationsIntent.UpdateNewGroupDescription(it)) },
            onCreateGroupChat = { viewModel.handleIntent(ConversationsIntent.CreateGroupConversation) },
            onStartGroupCall = { isVideo ->
                viewModel.handleIntent(ConversationsIntent.StartGroupCallFromSelection(isVideo))
            },
            onOpenSavedMessages = {
                viewModel.handleIntent(ConversationsIntent.ShowNewChatDialog(false))
                viewModel.handleIntent(ConversationsIntent.OpenSavedMessages)
            }
        )
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    if (state.isSearchVisible) {
                        OutlinedTextField(
                            value = state.searchQuery,
                            onValueChange = { viewModel.handleIntent(ConversationsIntent.SearchQueryChanged(it)) },
                            placeholder = { Text("Search chats, friends & messages...", fontSize = 14.sp) },
                            singleLine = true,
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(48.dp),
                            shape = RoundedCornerShape(24.dp),
                            trailingIcon = {
                                IconButton(onClick = { viewModel.handleIntent(ConversationsIntent.ToggleSearch) }) {
                                    Icon(Icons.Default.Close, contentDescription = "Close Search")
                                }
                            }
                        )
                    } else {
                        Text(
                            text = "Messenger",
                            fontWeight = FontWeight.Bold,
                            fontSize = 22.sp,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (!state.isSearchVisible) {
                        IconButton(onClick = { viewModel.handleIntent(ConversationsIntent.ToggleSearch) }) {
                            Icon(Icons.Default.Search, contentDescription = "Search chats")
                        }
                        IconButton(
                            onClick = {
                                viewModel.handleIntent(
                                    ConversationsIntent.ShowNewChatDialog(
                                        show = true,
                                        initialMode = ComposerSheetMode.GROUP
                                    )
                                )
                            }
                        ) {
                            Icon(Icons.Default.AddIcCall, contentDescription = "Group Call or New Group")
                        }
                        IconButton(
                            onClick = {
                                viewModel.handleIntent(
                                    ConversationsIntent.ShowNewChatDialog(
                                        show = true,
                                        initialMode = ComposerSheetMode.DIRECT
                                    )
                                )
                            }
                        ) {
                            Icon(Icons.Default.Edit, contentDescription = "New message")
                        }
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
            // Active Friends + Saved Messages Strip
            if (!state.isSearchVisible && state.selectedFilter != "calls") {
                LazyRow(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    contentPadding = PaddingValues(horizontal = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    item(key = "saved_messages_shortcut") {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            modifier = Modifier
                                .width(64.dp)
                                .clickable { viewModel.handleIntent(ConversationsIntent.OpenSavedMessages) }
                        ) {
                            MessengerAvatar(
                                name = "Saved",
                                avatarUrl = null,
                                isGroup = false,
                                isSaved = true,
                                size = 52.dp
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Saved",
                                fontSize = 11.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                fontWeight = FontWeight.Medium
                            )
                        }
                    }

                    item(key = "new_group_call_shortcut") {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            modifier = Modifier
                                .width(64.dp)
                                .clickable {
                                    viewModel.handleIntent(
                                        ConversationsIntent.ShowNewChatDialog(
                                            show = true,
                                            initialMode = ComposerSheetMode.GROUP
                                        )
                                    )
                                }
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(52.dp)
                                    .clip(CircleShape)
                                    .background(ConnectlyBlue.copy(alpha = 0.12f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.GroupAdd,
                                    contentDescription = "New Group",
                                    tint = ConnectlyBlue,
                                    modifier = Modifier.size(24.dp)
                                )
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "New Group",
                                fontSize = 11.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                fontWeight = FontWeight.Medium
                            )
                        }
                    }

                    items(state.activeFriends.take(25), key = { "friend_${it.id}" }) { friend ->
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            modifier = Modifier
                                .width(64.dp)
                                .clickable {
                                    viewModel.handleIntent(
                                        ConversationsIntent.OpenOrCreateDirectChat(friend.id, friend.conversationId)
                                    )
                                }
                        ) {
                            Box(modifier = Modifier.size(52.dp)) {
                                MessengerAvatar(
                                    name = friend.name,
                                    avatarUrl = friend.avatarUrl,
                                    isGroup = false,
                                    isSaved = false,
                                    size = 52.dp
                                )
                                if (friend.online) {
                                    Box(
                                        modifier = Modifier
                                            .size(14.dp)
                                            .align(Alignment.BottomEnd)
                                            .clip(CircleShape)
                                            .background(ConnectlyGreen)
                                            .border(2.dp, MaterialTheme.colorScheme.surface, CircleShape)
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = friend.name.substringBefore(" "),
                                fontSize = 11.sp,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                                fontWeight = FontWeight.Medium
                            )
                        }
                    }
                }
            }

            // Scrollable Filter Tabs
            val unreadTotal = state.conversations.count { it.unreadCount > 0 }
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf(
                    Triple("all", "All", null),
                    Triple("unread", "Unread", if (unreadTotal > 0) unreadTotal.toString() else null),
                    Triple("groups", "Groups", null),
                    Triple("pinned", "Pinned", null),
                    Triple("requests", "Requests", null),
                    Triple("archived", "Archived", null),
                    Triple("calls", "Calls", null)
                ).forEach { (key, label, badge) ->
                    FilterTabButton(
                        label = label,
                        badge = badge,
                        isSelected = state.selectedFilter == key,
                        onClick = { viewModel.handleIntent(ConversationsIntent.SelectFilter(key)) }
                    )
                }
            }

            if (state.isLoading && state.conversations.isEmpty() && state.callHistory.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = ConnectlyBlue)
                }
            } else if (state.selectedFilter == "calls" && state.searchQuery.isBlank()) {
                CallsTabContent(
                    calls = state.callHistory,
                    onStartCall = onStartCall,
                    onOpenGroupCallSheet = {
                        viewModel.handleIntent(
                            ConversationsIntent.ShowNewChatDialog(
                                show = true,
                                initialMode = ComposerSheetMode.GROUP
                            )
                        )
                    },
                    onRefresh = { viewModel.handleIntent(ConversationsIntent.Refresh) }
                )
            } else {
                LazyColumn(modifier = Modifier.fillMaxSize()) {
                    items(state.conversations, key = { "conv_${it.id}" }) { conversation ->
                        ConversationRowItem(
                            conversation = conversation,
                            onClick = { onNavigateToChat(conversation.id) },
                            onLongClick = {
                                viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(conversation))
                            },
                            onOpenMenu = {
                                viewModel.handleIntent(ConversationsIntent.ShowConversationMenu(conversation))
                            },
                            onAcceptRequest = {
                                viewModel.handleIntent(ConversationsIntent.HandleMessageRequest(conversation.id, "accept"))
                            },
                            onDeleteRequest = {
                                viewModel.handleIntent(ConversationsIntent.HandleMessageRequest(conversation.id, "delete"))
                            }
                        )
                        HorizontalDivider(
                            modifier = Modifier.padding(start = 78.dp, end = 16.dp),
                            color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f)
                        )
                    }

                    // Global search results: matching Friends & Messages
                    if (state.searchQuery.isNotBlank()) {
                        val matchedFriends = state.composerFriends
                        if (matchedFriends.isNotEmpty()) {
                            item(key = "search_friends_header") {
                                Text(
                                    text = "People & Friends",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 13.sp,
                                    color = ConnectlyBlue,
                                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp)
                                )
                            }
                            items(matchedFriends.take(10), key = { "search_friend_${it.id}" }) { friend ->
                                FriendDirectRow(
                                    friend = friend,
                                    onChat = {
                                        viewModel.handleIntent(
                                            ConversationsIntent.OpenOrCreateDirectChat(friend.id, friend.conversationId)
                                        )
                                    },
                                    onAudioCall = {
                                        onStartCall(friend.conversationId, friend.id, listOf(friend.id), CallType.AUDIO)
                                    },
                                    onVideoCall = {
                                        onStartCall(friend.conversationId, friend.id, listOf(friend.id), CallType.VIDEO)
                                    }
                                )
                            }
                        }

                        if (state.messageSearchResults.isNotEmpty()) {
                            item(key = "search_msgs_header") {
                                Text(
                                    text = "Matched Messages",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 13.sp,
                                    color = ConnectlyBlue,
                                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp)
                                )
                            }
                            items(state.messageSearchResults, key = { "search_msg_${it.id}" }) { msg ->
                                MatchedMessageRow(
                                    message = msg,
                                    onClick = {
                                        if (msg.conversationId > 0L) {
                                            onNavigateToChat(msg.conversationId)
                                        }
                                    }
                                )
                            }
                        }
                    }

                    if (state.conversations.isEmpty() && state.searchQuery.isBlank()) {
                        item(key = "empty_conversations") {
                            EmptyConversationsState(
                                filter = state.selectedFilter,
                                onNewMessage = {
                                    viewModel.handleIntent(
                                        ConversationsIntent.ShowNewChatDialog(
                                            show = true,
                                            initialMode = if (state.selectedFilter == "groups") ComposerSheetMode.GROUP else ComposerSheetMode.DIRECT
                                        )
                                    )
                                },
                                onRefresh = { viewModel.handleIntent(ConversationsIntent.Refresh) }
                            )
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun NewChatAndGroupSheet(
    state: ConversationsState,
    onDismiss: () -> Unit,
    onSelectMode: (ComposerSheetMode) -> Unit,
    onSearchChange: (String) -> Unit,
    onOpenDirectChat: (ActivePresenceFriend) -> Unit,
    onCallFriend: (ActivePresenceFriend, Boolean) -> Unit,
    onToggleGroupMember: (Long) -> Unit,
    onGroupTitleChange: (String) -> Unit,
    onGroupDescChange: (String) -> Unit,
    onCreateGroupChat: () -> Unit,
    onStartGroupCall: (Boolean) -> Unit,
    onOpenSavedMessages: () -> Unit
) {
    ModalBottomSheet(
        onDismissRequest = onDismiss,
        containerColor = MaterialTheme.colorScheme.surface,
        modifier = Modifier.fillMaxHeight(0.9f)
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = if (state.composerMode == ComposerSheetMode.DIRECT) "New Message or Call" else "New Group & Group Call",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp
                )
                IconButton(onClick = onDismiss) {
                    Icon(Icons.Default.Close, contentDescription = "Close")
                }
            }

            // Segmented Switcher: Direct vs Group / Group Call
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 8.dp)
                    .clip(RoundedCornerShape(12.dp))
                    .background(Color(0xFFF1F5F9))
                    .padding(4.dp),
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                Button(
                    onClick = { onSelectMode(ComposerSheetMode.DIRECT) },
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (state.composerMode == ComposerSheetMode.DIRECT) ConnectlyBlue else Color.Transparent,
                        contentColor = if (state.composerMode == ComposerSheetMode.DIRECT) Color.White else TextPrimaryLight
                    ),
                    elevation = null
                ) {
                    Text("Direct Chat / Call", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                }
                Button(
                    onClick = { onSelectMode(ComposerSheetMode.GROUP) },
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (state.composerMode == ComposerSheetMode.GROUP) ConnectlyBlue else Color.Transparent,
                        contentColor = if (state.composerMode == ComposerSheetMode.GROUP) Color.White else TextPrimaryLight
                    ),
                    elevation = null
                ) {
                    Text("Group / Group Call", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                }
            }

            if (state.composerMode == ComposerSheetMode.GROUP) {
                OutlinedTextField(
                    value = state.newGroupTitle,
                    onValueChange = onGroupTitleChange,
                    label = { Text("Group Name (required for Group Chat)") },
                    placeholder = { Text("e.g. Friends Hangout") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )
                Spacer(modifier = Modifier.height(6.dp))
                OutlinedTextField(
                    value = state.newGroupDescription,
                    onValueChange = onGroupDescChange,
                    placeholder = { Text("Group description (optional)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )
                if (state.selectedGroupMemberIds.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        text = "${state.selectedGroupMemberIds.size} friend(s) selected",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = ConnectlyBlue
                    )
                }
                Spacer(modifier = Modifier.height(8.dp))
            }

            OutlinedTextField(
                value = state.composerSearchQuery,
                onValueChange = onSearchChange,
                placeholder = { Text("Search friends by name or @username...") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(24.dp)
            )

            Spacer(modifier = Modifier.height(8.dp))

            LazyColumn(
                modifier = Modifier.weight(1f)
            ) {
                if (state.composerMode == ComposerSheetMode.DIRECT && state.composerSearchQuery.isBlank()) {
                    item(key = "saved_messages_row") {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { onOpenSavedMessages() }
                                .padding(vertical = 10.dp, horizontal = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            MessengerAvatar(
                                name = "Saved Messages",
                                avatarUrl = null,
                                isGroup = false,
                                isSaved = true,
                                size = 44.dp
                            )
                            Spacer(modifier = Modifier.width(12.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Saved Messages", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                Text("Save personal notes, media & links", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                        }
                        HorizontalDivider(color = Color(0xFFF1F5F9))
                    }
                }

                if (state.composerFriends.isEmpty()) {
                    item(key = "no_friends_found") {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 36.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "No matching friends found. Try searching by name or username.",
                                color = TextSecondaryLight,
                                fontSize = 14.sp,
                                textAlign = TextAlign.Center
                            )
                        }
                    }
                } else {
                    items(state.composerFriends, key = { "comp_friend_${it.id}" }) { friend ->
                        if (state.composerMode == ComposerSheetMode.DIRECT) {
                            FriendDirectRow(
                                friend = friend,
                                onChat = { onOpenDirectChat(friend) },
                                onAudioCall = { onCallFriend(friend, false) },
                                onVideoCall = { onCallFriend(friend, true) }
                            )
                        } else {
                            val isSelected = state.selectedGroupMemberIds.contains(friend.id)
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { onToggleGroupMember(friend.id) }
                                    .padding(vertical = 10.dp, horizontal = 4.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(modifier = Modifier.size(44.dp)) {
                                    MessengerAvatar(
                                        name = friend.name,
                                        avatarUrl = friend.avatarUrl,
                                        isGroup = false,
                                        isSaved = false,
                                        size = 44.dp
                                    )
                                    if (friend.online) {
                                        Box(
                                            modifier = Modifier
                                                .size(12.dp)
                                                .align(Alignment.BottomEnd)
                                                .clip(CircleShape)
                                                .background(ConnectlyGreen)
                                                .border(2.dp, Color.White, CircleShape)
                                        )
                                    }
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(friend.name, fontWeight = FontWeight.SemiBold, fontSize = 15.sp)
                                    Text(
                                        text = friend.lastSeenDisplay ?: "@${friend.username}",
                                        fontSize = 12.sp,
                                        color = if (friend.online) ConnectlyGreen else TextSecondaryLight
                                    )
                                }
                                Checkbox(
                                    checked = isSelected,
                                    onCheckedChange = { onToggleGroupMember(friend.id) },
                                    colors = CheckboxDefaults.colors(checkedColor = ConnectlyBlue)
                                )
                            }
                        }
                        HorizontalDivider(color = Color(0xFFF1F5F9))
                    }
                }
            }

            if (state.composerMode == ComposerSheetMode.GROUP) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 12.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Button(
                        onClick = onCreateGroupChat,
                        enabled = !state.isCreatingChat && state.selectedGroupMemberIds.isNotEmpty() && state.newGroupTitle.isNotBlank(),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        if (state.isCreatingChat) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(18.dp),
                                strokeWidth = 2.dp,
                                color = Color.White
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                        }
                        Icon(Icons.Default.Groups, contentDescription = null)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Create Group Chat (${state.selectedGroupMemberIds.size})")
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        OutlinedButton(
                            onClick = { onStartGroupCall(false) },
                            enabled = state.selectedGroupMemberIds.isNotEmpty(),
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Icon(Icons.Default.Call, contentDescription = null, tint = ConnectlyBlue)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Group Audio", fontSize = 13.sp)
                        }
                        Button(
                            onClick = { onStartGroupCall(true) },
                            enabled = state.selectedGroupMemberIds.isNotEmpty(),
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyGreen)
                        ) {
                            Icon(Icons.Default.Videocam, contentDescription = null)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Group Video", fontSize = 13.sp)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun FriendDirectRow(
    friend: ActivePresenceFriend,
    onChat: () -> Unit,
    onAudioCall: () -> Unit,
    onVideoCall: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onChat() }
            .padding(horizontal = 12.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(modifier = Modifier.size(46.dp)) {
            MessengerAvatar(
                name = friend.name,
                avatarUrl = friend.avatarUrl,
                isGroup = false,
                isSaved = false,
                size = 46.dp
            )
            if (friend.online) {
                Box(
                    modifier = Modifier
                        .size(12.dp)
                        .align(Alignment.BottomEnd)
                        .clip(CircleShape)
                        .background(ConnectlyGreen)
                        .border(2.dp, Color.White, CircleShape)
                )
            }
        }
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(friend.name, fontWeight = FontWeight.SemiBold, fontSize = 15.sp)
            Text(
                text = if (friend.online) "Active now" else (friend.lastSeenDisplay ?: "@${friend.username}"),
                fontSize = 12.sp,
                color = if (friend.online) ConnectlyGreen else TextSecondaryLight
            )
        }
        IconButton(onClick = onChat) {
            Icon(Icons.AutoMirrored.Filled.Chat, contentDescription = "Message", tint = ConnectlyBlue)
        }
        if (friend.canCall) {
            IconButton(onClick = onAudioCall) {
                Icon(Icons.Default.Call, contentDescription = "Audio Call", tint = ConnectlyBlue)
            }
            IconButton(onClick = onVideoCall) {
                Icon(Icons.Default.Videocam, contentDescription = "Video Call", tint = ConnectlyGreen)
            }
        }
    }
}

@Composable
private fun CallsTabContent(
    calls: List<CallHistoryItem>,
    onStartCall: (Long?, Long?, List<Long>, CallType) -> Unit,
    onOpenGroupCallSheet: () -> Unit,
    onRefresh: () -> Unit
) {
    LazyColumn(modifier = Modifier.fillMaxSize()) {
        item(key = "start_group_call_banner") {
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
                    .clickable { onOpenGroupCallSheet() },
                shape = RoundedCornerShape(14.dp),
                color = ConnectlyBlue.copy(alpha = 0.09f)
            ) {
                Row(
                    modifier = Modifier.padding(14.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Box(
                        modifier = Modifier
                            .size(42.dp)
                            .clip(CircleShape)
                            .background(ConnectlyBlue),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(Icons.Default.AddIcCall, contentDescription = null, tint = Color.White)
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text("Start Audio or Group Video Call", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        Text("Select one or multiple friends to call instantly", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                }
            }
        }

        if (calls.isEmpty()) {
            item(key = "empty_calls") {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(36.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Icon(
                        Icons.Default.Call,
                        contentDescription = null,
                        tint = TextSecondaryLight,
                        modifier = Modifier.size(48.dp)
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    Text("No recent calls yet", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        "Tap above to start an audio, video, or group call with your friends.",
                        fontSize = 13.sp,
                        color = TextSecondaryLight,
                        textAlign = TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                    TextButton(onClick = onRefresh) { Text("Refresh") }
                }
            }
        } else {
            items(calls, key = { "call_hist_${it.id}" }) { item ->
                val isGroup = item.callType.isGroup || item.participantCount > 2
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    MessengerAvatar(
                        name = item.title ?: item.otherUser.name,
                        avatarUrl = item.otherUser.avatar,
                        isGroup = isGroup,
                        isSaved = false,
                        size = 48.dp
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = item.title ?: item.otherUser.name,
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 15.sp,
                            color = if (item.isMissed) Color(0xFFEF4444) else MaterialTheme.colorScheme.onSurface
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            val dirIcon = when {
                                item.isMissed -> Icons.AutoMirrored.Filled.CallMissed
                                item.isIncoming -> Icons.AutoMirrored.Filled.CallReceived
                                else -> Icons.AutoMirrored.Filled.CallMade
                            }
                            Icon(
                                imageVector = dirIcon,
                                contentDescription = null,
                                tint = if (item.isMissed) Color(0xFFEF4444) else ConnectlyGreen,
                                modifier = Modifier.size(14.dp)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            val durLabel = if (item.durationSeconds > 0) {
                                val m = item.durationSeconds / 60
                                val s = item.durationSeconds % 60
                                if (m > 0) " (${m}m ${s}s)" else " (${s}s)"
                            } else ""
                            Text(
                                text = "${if (item.isMissed) "Missed" else if (item.isIncoming) "Incoming" else "Outgoing"}$durLabel • ${item.formattedTime}",
                                fontSize = 12.sp,
                                color = TextSecondaryLight
                            )
                        }
                    }
                    IconButton(
                        onClick = {
                            onStartCall(
                                item.conversationId,
                                item.otherUser.id.takeIf { it > 0L },
                                listOfNotNull(item.otherUser.id.takeIf { it > 0L }),
                                if (isGroup) CallType.GROUP_AUDIO else CallType.AUDIO
                            )
                        }
                    ) {
                        Icon(Icons.Default.Call, contentDescription = "Audio Call", tint = ConnectlyBlue)
                    }
                    IconButton(
                        onClick = {
                            onStartCall(
                                item.conversationId,
                                item.otherUser.id.takeIf { it > 0L },
                                listOfNotNull(item.otherUser.id.takeIf { it > 0L }),
                                if (isGroup) CallType.GROUP_VIDEO else CallType.VIDEO
                            )
                        }
                    ) {
                        Icon(Icons.Default.Videocam, contentDescription = "Video Call", tint = ConnectlyGreen)
                    }
                }
                HorizontalDivider(
                    modifier = Modifier.padding(start = 76.dp, end = 16.dp),
                    color = Color(0xFFF1F5F9)
                )
            }
        }
    }
}

@Composable
private fun MatchedMessageRow(
    message: Message,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        MessengerAvatar(
            name = message.senderName,
            avatarUrl = message.senderAvatar,
            isGroup = false,
            isSaved = false,
            size = 42.dp
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(message.senderName, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                Text(message.formattedTime, fontSize = 11.sp, color = TextSecondaryLight)
            }
            Text(
                text = message.body,
                fontSize = 13.sp,
                color = TextSecondaryLight,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
        }
    }
}

@Composable
private fun EmptyConversationsState(
    filter: String,
    onNewMessage: () -> Unit,
    onRefresh: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 32.dp, vertical = 56.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Box(
            modifier = Modifier
                .size(72.dp)
                .clip(CircleShape)
                .background(ConnectlyBlue.copy(alpha = 0.1f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = if (filter == "groups") Icons.Default.Groups else Icons.AutoMirrored.Filled.Chat,
                contentDescription = null,
                tint = ConnectlyBlue,
                modifier = Modifier.size(36.dp)
            )
        }
        Spacer(modifier = Modifier.height(16.dp))
        val heading = when (filter) {
            "unread" -> "You're all caught up!"
            "groups" -> "No group chats yet"
            "pinned" -> "No pinned conversations"
            "requests" -> "No pending message requests"
            "archived" -> "No archived chats"
            else -> "Start a conversation"
        }
        val subtitle = when (filter) {
            "unread" -> "You have no unread messages right now."
            "groups" -> "Create a group chat or start a group call with your friends."
            "pinned" -> "Long-press any chat to pin it to the top of your inbox."
            "requests" -> "New message requests from people outside your friends list appear here."
            "archived" -> "Archived chats stay hidden from your main inbox until you need them."
            else -> "Message or call any of your friends on Bondoo in real time."
        }
        Text(heading, fontWeight = FontWeight.Bold, fontSize = 18.sp)
        Spacer(modifier = Modifier.height(6.dp))
        Text(subtitle, fontSize = 13.sp, color = TextSecondaryLight, textAlign = TextAlign.Center)
        Spacer(modifier = Modifier.height(18.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            Button(
                onClick = onNewMessage,
                shape = RoundedCornerShape(22.dp),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(16.dp))
                Spacer(modifier = Modifier.width(6.dp))
                Text(if (filter == "groups") "Create Group" else "New Message")
            }
            OutlinedButton(
                onClick = onRefresh,
                shape = RoundedCornerShape(22.dp)
            ) {
                Text("Refresh")
            }
        }
    }
}

@Composable
private fun SheetActionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    tint: Color = MaterialTheme.colorScheme.onSurface,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() }
            .padding(horizontal = 20.dp, vertical = 14.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = label, tint = tint, modifier = Modifier.size(22.dp))
        Spacer(modifier = Modifier.width(16.dp))
        Text(label, fontSize = 15.sp, fontWeight = FontWeight.Medium, color = tint)
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
        modifier = Modifier.height(34.dp),
        elevation = null
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(label, fontSize = 13.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium)
            if (badge != null) {
                Spacer(modifier = Modifier.width(5.dp))
                Box(
                    modifier = Modifier
                        .defaultMinSize(minWidth = 18.dp, minHeight = 18.dp)
                        .clip(CircleShape)
                        .background(if (isSelected) Color.White.copy(alpha = 0.28f) else ConnectlyBlue)
                        .padding(horizontal = 4.dp),
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

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun ConversationRowItem(
    conversation: Conversation,
    onClick: () -> Unit,
    onLongClick: () -> Unit,
    onOpenMenu: () -> Unit,
    onAcceptRequest: () -> Unit,
    onDeleteRequest: () -> Unit
) {
    val hasUnread = conversation.unreadCount > 0

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .combinedClickable(
                onClick = onClick,
                onLongClick = onLongClick
            )
            .padding(horizontal = 16.dp, vertical = 11.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Box(modifier = Modifier.size(54.dp)) {
                MessengerAvatar(
                    name = conversation.displayTitle,
                    avatarUrl = conversation.displayAvatar,
                    isGroup = conversation.isGroup,
                    isSaved = conversation.isSaved,
                    size = 54.dp
                )
                if (conversation.isOnline && !conversation.isGroup && !conversation.isSaved) {
                    Box(
                        modifier = Modifier
                            .size(14.dp)
                            .align(Alignment.BottomEnd)
                            .clip(CircleShape)
                            .background(ConnectlyGreen)
                            .border(2.dp, MaterialTheme.colorScheme.surface, CircleShape)
                    )
                }
            }

            Spacer(modifier = Modifier.width(13.dp))

            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = conversation.displayTitle,
                        fontWeight = if (hasUnread) FontWeight.ExtraBold else FontWeight.SemiBold,
                        fontSize = 15.5.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        modifier = Modifier.weight(1f)
                    )
                    if (conversation.isPinned) {
                        Icon(
                            imageVector = Icons.Default.PushPin,
                            contentDescription = "Pinned",
                            tint = ConnectlyBlue,
                            modifier = Modifier
                                .padding(start = 4.dp)
                                .size(14.dp)
                        )
                    }
                    if (conversation.isMuted) {
                        Icon(
                            imageVector = Icons.Default.NotificationsOff,
                            contentDescription = "Muted",
                            tint = TextSecondaryLight,
                            modifier = Modifier
                                .padding(start = 4.dp)
                                .size(14.dp)
                        )
                    }
                    if (conversation.lastMessageTimeFormatted.isNotBlank()) {
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = conversation.lastMessageTimeFormatted,
                            fontSize = 11.5.sp,
                            color = if (hasUnread) ConnectlyBlue else TextSecondaryLight,
                            fontWeight = if (hasUnread) FontWeight.Bold else FontWeight.Normal
                        )
                    }
                }

                Spacer(modifier = Modifier.height(3.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    val previewText = when {
                        !conversation.draftMessage.isNullOrBlank() -> "Draft: ${conversation.draftMessage}"
                        conversation.lastMessage != null -> {
                            val last = conversation.lastMessage!!
                            val prefix = when {
                                last.isMine -> "You: "
                                conversation.isGroup && last.senderName.isNotBlank() -> "${last.senderName}: "
                                else -> ""
                            }
                            "$prefix${last.body}"
                        }
                        conversation.isGroup -> "${conversation.participantsCount.coerceAtLeast(1)} members • Tap to chat"
                        else -> "Tap to start chatting"
                    }

                    Text(
                        text = previewText,
                        fontSize = 13.5.sp,
                        color = when {
                            !conversation.draftMessage.isNullOrBlank() -> Color(0xFFEF4444)
                            hasUnread -> MaterialTheme.colorScheme.onSurface
                            else -> TextSecondaryLight
                        },
                        fontWeight = if (hasUnread) FontWeight.SemiBold else FontWeight.Normal,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        modifier = Modifier.weight(1f)
                    )

                    if (hasUnread) {
                        Spacer(modifier = Modifier.width(8.dp))
                        Box(
                            modifier = Modifier
                                .defaultMinSize(minWidth = 20.dp, minHeight = 20.dp)
                                .clip(CircleShape)
                                .background(ConnectlyBlue)
                                .padding(horizontal = 5.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = if (conversation.unreadCount > 99) "99+" else conversation.unreadCount.toString(),
                                color = Color.White,
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }

                    IconButton(
                        onClick = onOpenMenu,
                        modifier = Modifier.size(28.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.MoreVert,
                            contentDescription = "Conversation Menu",
                            tint = TextSecondaryLight,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                }
            }
        }

        if (conversation.isRequest && conversation.requestStatus == "pending") {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(start = 67.dp, top = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Button(
                    onClick = onAcceptRequest,
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp),
                    modifier = Modifier.height(32.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Accept", fontSize = 12.sp)
                }
                OutlinedButton(
                    onClick = onDeleteRequest,
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp),
                    modifier = Modifier.height(32.dp)
                ) {
                    Text("Delete", fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
internal fun MessengerAvatar(
    name: String,
    avatarUrl: String?,
    isGroup: Boolean,
    isSaved: Boolean,
    size: Dp
) {
    if (!avatarUrl.isNullOrBlank()) {
        AsyncImage(
            model = avatarUrl,
            contentDescription = name,
            contentScale = ContentScale.Crop,
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .background(Color(0xFFE2E8F0))
        )
    } else if (isSaved) {
        Box(
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .background(
                    Brush.linearGradient(
                        listOf(ConnectlyBlue, Color(0xFF7C3AED))
                    )
                ),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = Icons.Default.Bookmark,
                contentDescription = "Saved Messages",
                tint = Color.White,
                modifier = Modifier.size(size * 0.48f)
            )
        }
    } else if (isGroup) {
        Box(
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .background(ConnectlyBlue.copy(alpha = 0.14f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = Icons.Default.Groups,
                contentDescription = "Group",
                tint = ConnectlyBlue,
                modifier = Modifier.size(size * 0.54f)
            )
        }
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
                .background(
                    Brush.linearGradient(
                        listOf(Color(0xFF2563EB), Color(0xFF4F46E5))
                    )
                ),
            contentAlignment = Alignment.Center
        ) {
            Text(
                text = initials,
                color = Color.White,
                fontWeight = FontWeight.Bold,
                fontSize = (size.value * 0.36f).sp
            )
        }
    }
}
