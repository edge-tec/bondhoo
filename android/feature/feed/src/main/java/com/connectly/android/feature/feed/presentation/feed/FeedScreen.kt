package com.connectly.android.feature.feed.presentation.feed

import androidx.compose.animation.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Comment
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.component.ConnectlyEmojiPicker
import com.connectly.android.core.designsystem.component.ConnectlyQuickEmojiRow
import com.connectly.android.core.designsystem.component.resolveMediaUrl
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.ReactionType
import com.connectly.android.core.model.User

private enum class FeedShareMode { MAIN, GROUP_SELECT, PAGE_SELECT }

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeedScreen(
    onNavigateToCreatePost: (String?) -> Unit = {},
    onNavigateToStoryViewer: (Long) -> Unit,
    onNavigateToCreateStory: () -> Unit,
    onNavigateToProfile: () -> Unit,
    onNavigateToUserProfile: (String) -> Unit = {},
    onNavigateToSearch: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {},
    onNavigateToGroups: () -> Unit = {},
    onNavigateToPages: () -> Unit = {},
    onNavigateToLive: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: FeedViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val listState = rememberLazyListState()
    val snackbarHostState = remember { SnackbarHostState() }
    var showCreateLiveDialog by remember { mutableStateOf(false) }
    var isSearchMenuVisible by remember { mutableStateOf(false) }
    var searchQuery by remember { mutableStateOf("") }
    var sortOrder by remember { mutableStateOf("latest") } // "latest", "top"
    val keyboardController = LocalSoftwareKeyboardController.current

    val displayedPosts = remember(state.posts, searchQuery, sortOrder) {
        val filtered = if (searchQuery.isBlank()) {
            state.posts
        } else {
            val q = searchQuery.trim().lowercase()
            state.posts.filter { post ->
                post.content.lowercase().contains(q) ||
                post.author.name.lowercase().contains(q) ||
                post.author.username.lowercase().contains(q)
            }
        }
        if (sortOrder == "top") {
            filtered.sortedByDescending { it.reactionsCount + it.commentsCount + it.sharesCount }
        } else {
            filtered
        }
    }

    val matchingUsers = remember(state.suggestedFriends, searchQuery) {
        if (searchQuery.isBlank()) emptyList()
        else {
            val q = searchQuery.trim().lowercase()
            state.suggestedFriends.filter { user ->
                user.name.lowercase().contains(q) || user.username.lowercase().contains(q)
            }
        }
    }

    // Periodic silent sync for real-time newsfeed updates
    LaunchedEffect(Unit) {
        while (true) {
            kotlinx.coroutines.delay(20_000L)
            if (searchQuery.isBlank() && !state.isLoading && !state.isPaginating) {
                viewModel.handleIntent(FeedIntent.RefreshFeed)
            }
        }
    }

    // Pagination auto-load when reaching near end
    LaunchedEffect(listState) {
        snapshotFlow { listState.layoutInfo.visibleItemsInfo.lastOrNull()?.index }
            .collect { lastIndex ->
                if (lastIndex != null && lastIndex >= state.posts.size - 2 && state.hasMore && !state.isPaginating) {
                    viewModel.handleIntent(FeedIntent.LoadMorePosts)
                }
            }
    }

    // Snackbar notifications
    LaunchedEffect(state.reportSuccessMessage) {
        state.reportSuccessMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(FeedIntent.DismissMessages)
        }
    }
    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(FeedIntent.DismissMessages)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(MaterialTheme.colorScheme.surface)
            ) {
                if (isSearchMenuVisible) {
                    Surface(
                        color = MaterialTheme.colorScheme.surface,
                        shadowElevation = 3.dp,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .statusBarsPadding()
                                .padding(top = 8.dp, bottom = 8.dp)
                        ) {
                            // Top Search Bar Row
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                IconButton(
                                    onClick = {
                                        isSearchMenuVisible = false
                                        searchQuery = ""
                                        keyboardController?.hide()
                                    },
                                    modifier = Modifier.size(40.dp)
                                ) {
                                    Icon(
                                        imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                                        contentDescription = "Close search",
                                        tint = MaterialTheme.colorScheme.onSurface
                                    )
                                }

                                Spacer(modifier = Modifier.width(4.dp))

                                // Modern Pill Search Field (Zero clipping, vertically centered)
                                Box(
                                    modifier = Modifier
                                        .weight(1f)
                                        .height(44.dp)
                                        .clip(RoundedCornerShape(22.dp))
                                        .background(Color(0xFFF1F5F9))
                                        .border(
                                            BorderStroke(
                                                1.dp,
                                                if (searchQuery.isNotEmpty()) ConnectlyBlue else Color(0xFFE2E8F0)
                                            ),
                                            RoundedCornerShape(22.dp)
                                        )
                                        .padding(horizontal = 14.dp),
                                    contentAlignment = Alignment.CenterStart
                                ) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.Search,
                                            contentDescription = null,
                                            tint = if (searchQuery.isNotEmpty()) ConnectlyBlue else TextSecondaryLight,
                                            modifier = Modifier.size(20.dp)
                                        )
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Box(
                                            modifier = Modifier.weight(1f),
                                            contentAlignment = Alignment.CenterStart
                                        ) {
                                            if (searchQuery.isEmpty()) {
                                                Text(
                                                    text = "Search posts, topics, people...",
                                                    color = TextSecondaryLight,
                                                    fontSize = 14.sp
                                                )
                                            }
                                            BasicTextField(
                                                value = searchQuery,
                                                onValueChange = { searchQuery = it },
                                                singleLine = true,
                                                textStyle = TextStyle(
                                                    color = MaterialTheme.colorScheme.onSurface,
                                                    fontSize = 14.sp,
                                                    fontWeight = FontWeight.Normal
                                                ),
                                                cursorBrush = SolidColor(ConnectlyBlue),
                                                keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                                                keyboardActions = KeyboardActions(onSearch = { keyboardController?.hide() }),
                                                modifier = Modifier.fillMaxWidth()
                                            )
                                        }
                                        if (searchQuery.isNotEmpty()) {
                                            IconButton(
                                                onClick = { searchQuery = "" },
                                                modifier = Modifier.size(26.dp)
                                            ) {
                                                Icon(
                                                    imageVector = Icons.Default.Close,
                                                    contentDescription = "Clear search",
                                                    tint = TextSecondaryLight,
                                                    modifier = Modifier.size(16.dp)
                                                )
                                            }
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.width(4.dp))

                                IconButton(
                                    onClick = onNavigateToSearch,
                                    modifier = Modifier.size(40.dp)
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.Tune,
                                        contentDescription = "Advanced Search",
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(22.dp)
                                    )
                                }
                            }

                            Spacer(modifier = Modifier.height(4.dp))

                            // Category Menu Pills Row
                            val tabs = listOf(
                                "for_you" to "For You",
                                "following" to "Following",
                                "friends" to "Friends",
                                "groups" to "Groups",
                                "pages" to "Pages",
                                "saved" to "Saved Posts"
                            )
                            LazyRow(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 8.dp, vertical = 4.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(tabs) { (key, label) ->
                                    val isSelected = state.selectedTab == key
                                    Button(
                                        onClick = {
                                            when (key) {
                                                "groups" -> onNavigateToGroups()
                                                "pages" -> onNavigateToPages()
                                                else -> viewModel.handleIntent(FeedIntent.SelectTab(key))
                                            }
                                        },
                                        shape = RoundedCornerShape(20.dp),
                                        colors = ButtonDefaults.buttonColors(
                                            containerColor = if (isSelected) ConnectlyBlue else Color(0xFFF1F5F9),
                                            contentColor = if (isSelected) Color.White else TextPrimaryLight
                                        ),
                                        elevation = if (isSelected) ButtonDefaults.buttonElevation(defaultElevation = 2.dp) else null,
                                        contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
                                        modifier = Modifier.height(34.dp)
                                    ) {
                                        Text(
                                            text = label,
                                            fontSize = 13.sp,
                                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
                                        )
                                    }
                                }
                            }

                            // Quick Suggestions or Result Summary
                            if (searchQuery.isEmpty()) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(horizontal = 14.dp, vertical = 2.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = "Trending:",
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        color = TextSecondaryLight
                                    )
                                    Spacer(modifier = Modifier.width(8.dp))
                                    LazyRow(
                                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                                    ) {
                                        items(listOf("#Dhaka", "#Bondhoo", "#Connectly", "#Technology", "#Innovation")) { topic ->
                                            Surface(
                                                color = ConnectlyBlue.copy(alpha = 0.08f),
                                                shape = RoundedCornerShape(12.dp),
                                                modifier = Modifier.clickable {
                                                    searchQuery = topic.removePrefix("#")
                                                }
                                            ) {
                                                Text(
                                                    text = topic,
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.SemiBold,
                                                    color = ConnectlyBlue,
                                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                                                )
                                            }
                                        }
                                    }
                                }
                            } else {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(horizontal = 14.dp, vertical = 2.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = "${displayedPosts.size} posts found",
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Medium,
                                        color = TextSecondaryLight
                                    )
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                                    ) {
                                        Text(
                                            text = "Sort:",
                                            fontSize = 11.sp,
                                            color = TextSecondaryLight
                                        )
                                        Text(
                                            text = if (sortOrder == "latest") "Latest" else "Top Reacted",
                                            fontSize = 12.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = ConnectlyBlue,
                                            modifier = Modifier.clickable {
                                                sortOrder = if (sortOrder == "latest") "top" else "latest"
                                            }
                                        )
                                    }
                                }
                            }
                        }
                    }
                } else {
                    TopAppBar(
                        title = {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(34.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyBlue),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.People,
                                        contentDescription = "Connectly Logo",
                                        tint = Color.White,
                                        modifier = Modifier.size(20.dp)
                                    )
                                }
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = "Connectly",
                                    fontWeight = FontWeight.Bold,
                                    color = ConnectlyBlue,
                                    fontSize = 22.sp
                                )
                            }
                        },
                        actions = {
                            IconButton(onClick = { isSearchMenuVisible = true }) {
                                Icon(Icons.Default.Search, contentDescription = "Search & Menu Options")
                            }
                            BadgedBox(
                                badge = {
                                    Badge(containerColor = ConnectlyRose) { Text("3") }
                                }
                            ) {
                                IconButton(onClick = onNavigateToNotifications) {
                                    Icon(Icons.Default.Notifications, contentDescription = "Notifications")
                                }
                            }
                            IconButton(onClick = onNavigateToProfile) {
                                ConnectlyAvatar(
                                    imageUrl = state.currentUserAvatar,
                                    name = state.currentUserName ?: "My Profile",
                                    size = 34.dp
                                )
                            }
                        },
                        colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
                    )
                }
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            state = listState,
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(BackgroundLight)
        ) {
            // Stories Tray
            item {
                StoriesTraySection(
                    storiesTray = state.storiesTray,
                    onAddStoryClick = onNavigateToCreateStory,
                    onStoryClick = { onNavigateToStoryViewer(it) }
                )
            }

            // Composer Card ("What's on your mind?")
            item {
                CreatePostCard(
                    userAvatar = state.currentUserAvatar,
                    userName = state.currentUserName,
                    onCardClick = { onNavigateToCreatePost(null) },
                    onLiveClick = { onNavigateToLive(1L) },
                    onPhotoClick = { onNavigateToCreatePost("photo") },
                    onVideoClick = { onNavigateToCreatePost("video") },
                    onFeelingClick = { onNavigateToCreatePost("feeling") }
                )
            }

            // Suggested Friends Section
            if (state.suggestedFriends.isNotEmpty()) {
                item(key = "suggested_friends_${state.isSuggestedFriendsHidden}") {
                    SuggestedFriendsCarousel(
                        users = state.suggestedFriends,
                        requestedFriendIds = state.requestedFriendIds,
                        isSectionHidden = state.isSuggestedFriendsHidden,
                        onToggleHide = { viewModel.handleIntent(FeedIntent.ToggleHideSuggestedFriends) },
                        onAddFriend = { userId -> viewModel.handleIntent(FeedIntent.AddSuggestedFriend(userId)) },
                        onCancelFriend = { userId -> viewModel.handleIntent(FeedIntent.CancelSuggestedFriend(userId)) },
                        onProfileClick = onNavigateToUserProfile
                    )
                }
            }

            // Matching People Tray (When searching)
            if (searchQuery.isNotBlank() && matchingUsers.isNotEmpty()) {
                item(key = "matching_users_tray") {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color.White)
                            .padding(12.dp)
                    ) {
                        Text(
                            text = "Matching People",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        LazyRow(
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(matchingUsers, key = { it.id }) { user ->
                                Card(
                                    colors = CardDefaults.cardColors(containerColor = Color(0xFFF8FAFC)),
                                    shape = RoundedCornerShape(12.dp),
                                    border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                                    modifier = Modifier
                                        .width(130.dp)
                                        .clickable { onNavigateToUserProfile(user.username.ifBlank { user.id.toString() }) }
                                ) {
                                    Column(
                                        modifier = Modifier.padding(10.dp),
                                        horizontalAlignment = Alignment.CenterHorizontally
                                    ) {
                                        ConnectlyAvatar(
                                            imageUrl = user.avatar,
                                            name = user.name,
                                            size = 40.dp
                                        )
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text(
                                            text = user.name,
                                            style = MaterialTheme.typography.bodySmall,
                                            fontWeight = FontWeight.Bold,
                                            maxLines = 1,
                                            textAlign = TextAlign.Center
                                        )
                                        Text(
                                            text = "@${user.username}",
                                            style = MaterialTheme.typography.labelSmall,
                                            color = TextSecondaryLight,
                                            maxLines = 1,
                                            textAlign = TextAlign.Center
                                        )
                                    }
                                }
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                }
            }

            // Feed Posts List or Empty State
            if (searchQuery.isNotBlank() && displayedPosts.isEmpty() && matchingUsers.isEmpty()) {
                item(key = "empty_search_state") {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 40.dp, horizontal = 24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .clip(CircleShape)
                                .background(Color(0xFFEFF6FF)),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = Icons.Default.Search,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(32.dp)
                            )
                        }
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = "No results found for \"$searchQuery\"",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Text(
                            text = "Try searching across all of Connectly or select another tab above.",
                            style = MaterialTheme.typography.bodySmall,
                            color = TextSecondaryLight,
                            textAlign = TextAlign.Center
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = onNavigateToSearch,
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                        ) {
                            Text("Search All Connectly", fontWeight = FontWeight.SemiBold)
                        }
                    }
                }
            } else {
                items(displayedPosts, key = { it.id }) { post ->
                    PostItemCard(
                        post = post,
                        onReact = { reaction -> viewModel.handleIntent(FeedIntent.ReactToPost(post.id, reaction)) },
                        onCommentsClick = { viewModel.handleIntent(FeedIntent.OpenComments(post.id)) },
                        onShareClick = { viewModel.handleIntent(FeedIntent.SharePost(post.id)) },
                        onSaveClick = { viewModel.handleIntent(FeedIntent.SavePost(post.id)) },
                        onEditClick = { viewModel.handleIntent(FeedIntent.OpenEditPost(post)) },
                        onDeleteClick = { viewModel.handleIntent(FeedIntent.RequestDeletePost(post.id)) },
                        onHideClick = { viewModel.handleIntent(FeedIntent.HidePost(post.id)) },
                        onReportClick = { viewModel.handleIntent(FeedIntent.OpenReportDialog(post.id)) },
                        onProfileClick = { onNavigateToUserProfile(post.author.username.ifBlank { post.author.id.toString() }) }
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                }
            }

            // Pagination loading indicator
            if (state.isPaginating) {
                item {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(28.dp),
                            color = ConnectlyBlue,
                            strokeWidth = 2.5.dp
                        )
                    }
                }
            }
        }
    }

    // Comments Bottom Sheet
    if (state.activeCommentsPostId != null) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(FeedIntent.CloseComments) },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
        ) {
            CommentsDrawerContent(
                postId = state.activeCommentsPostId!!,
                comments = state.activeComments,
                activeParentId = state.activeParentCommentId,
                replyingToUser = state.replyingToUser,
                onProfileClick = onNavigateToUserProfile,
                onStartReply = { parentId, username ->
                    viewModel.handleIntent(FeedIntent.StartReply(parentId, username))
                },
                onCancelReply = {
                    viewModel.handleIntent(FeedIntent.CancelReply)
                },
                onAddComment = { body, parentId ->
                    viewModel.handleIntent(FeedIntent.AddComment(state.activeCommentsPostId!!, body, parentId))
                },
                onReactComment = { commentId, reaction ->
                    viewModel.handleIntent(FeedIntent.ReactToComment(commentId, reaction))
                },
                onDeleteComment = { commentId ->
                    viewModel.handleIntent(FeedIntent.DeleteComment(commentId))
                }
            )
        }
    }

    // Delete Post Confirmation Dialog
    if (state.deletingPostId != null) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(FeedIntent.DismissDeleteDialog) },
            title = { Text("Delete Post?", fontWeight = FontWeight.Bold) },
            text = { Text("Are you sure you want to delete this post? This action cannot be undone.") },
            confirmButton = {
                TextButton(
                    onClick = { viewModel.handleIntent(FeedIntent.ConfirmDeletePost(state.deletingPostId!!)) }
                ) {
                    Text("Delete", color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { viewModel.handleIntent(FeedIntent.DismissDeleteDialog) }
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    // Edit Post Dialog
    if (state.editingPost != null) {
        val editingPost = state.editingPost!!
        var editContent by remember(editingPost.id) { mutableStateOf(editingPost.content) }
        var editAudience by remember(editingPost.id) { mutableStateOf(editingPost.privacy) }

        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(FeedIntent.DismissEditPost) },
            title = { Text("Edit Post", fontWeight = FontWeight.Bold) },
            text = {
                Column {
                    OutlinedTextField(
                        value = editContent,
                        onValueChange = { editContent = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(140.dp),
                        label = { Text("Post Content") },
                        placeholder = { Text("What's on your mind?") }
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                    Text("Audience", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                    Spacer(modifier = Modifier.height(6.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf("public" to "Public", "friends" to "Friends", "only_me" to "Only Me").forEach { (aud, label) ->
                            FilterChip(
                                selected = editAudience == aud,
                                onClick = { editAudience = aud },
                                label = { Text(label, fontSize = 12.sp) }
                            )
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.handleIntent(FeedIntent.SubmitEditPost(editingPost.id, editContent, editAudience))
                    },
                    enabled = editContent.isNotBlank() && !state.isEditingPostSaving
                ) {
                    Text("Save", fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { viewModel.handleIntent(FeedIntent.DismissEditPost) }
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    // Report Post Dialog
    if (state.reportingPostId != null) {
        ReportPostDialog(
            postId = state.reportingPostId!!,
            onDismiss = { viewModel.handleIntent(FeedIntent.CloseReportDialog) },
            onSubmit = { reason, details ->
                viewModel.handleIntent(FeedIntent.SubmitReport(state.reportingPostId!!, reason, details))
            }
        )
    }

    val context = LocalContext.current

    // Smart Share Post Bottom Sheet
    if (state.sharingPostId != null) {
        val postId = state.sharingPostId!!

        LaunchedEffect(state.sharingPostId) {
            viewModel.handleIntent(FeedIntent.LoadShareTargets)
        }

        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(FeedIntent.DismissShareSheet) },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            when (state.sharingStep) {
                SharingStep.DESTINATIONS -> {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 20.dp)
                            .padding(bottom = 28.dp)
                    ) {
                        // Header Bar
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "Share Post",
                                fontWeight = FontWeight.Bold,
                                fontSize = 18.sp
                            )
                            IconButton(onClick = { viewModel.handleIntent(FeedIntent.DismissShareSheet) }) {
                                Icon(Icons.Default.Close, contentDescription = "Close Share")
                            }
                        }

                        Text(
                            text = "Share this post to your timeline, groups, or pages. The share count will only increase when shared.",
                            fontSize = 13.sp,
                            color = TextSecondaryLight
                        )

                        Spacer(modifier = Modifier.height(12.dp))

                        OutlinedTextField(
                            value = state.shareCaption,
                            onValueChange = { viewModel.handleIntent(FeedIntent.UpdateShareCaption(it)) },
                            placeholder = { Text("Say something about this post (optional)...", fontSize = 13.sp) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            minLines = 2,
                            maxLines = 3
                        )

                        Spacer(modifier = Modifier.height(14.dp))

                        // Option 1: Share to Timeline
                        OptionRow(
                            icon = Icons.Default.DynamicFeed,
                            title = "Share to Timeline",
                            subtitle = "Share post directly to your personal feed",
                            onClick = {
                                viewModel.handleIntent(
                                    FeedIntent.ShareToTimeline(
                                        postId = postId,
                                        caption = state.shareCaption.trim().ifEmpty { null }
                                    )
                                )
                            }
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        // Option 2: Share to Groups
                        OptionRow(
                            icon = Icons.Default.Groups,
                            title = "Share to Groups",
                            subtitle = "Select one or more joined groups",
                            onClick = {
                                viewModel.handleIntent(FeedIntent.SetSharingStep(SharingStep.GROUPS))
                            }
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        // Option 3: Share to Pages
                        OptionRow(
                            icon = Icons.Default.Flag,
                            title = "Share to Pages",
                            subtitle = "Select a page you manage",
                            onClick = {
                                viewModel.handleIntent(FeedIntent.SetSharingStep(SharingStep.PAGES))
                            }
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        // Option 4: More Options
                        OptionRow(
                            icon = Icons.Default.Share,
                            title = "More Options",
                            subtitle = "Share via Messenger, WhatsApp, or other apps",
                            onClick = {
                                val sendIntent = android.content.Intent().apply {
                                    action = android.content.Intent.ACTION_SEND
                                    putExtra(android.content.Intent.EXTRA_TEXT, "Check out this post on Bondhoo: https://bondhoo.com/posts/$postId\n${state.shareCaption.trim()}")
                                    type = "text/plain"
                                }
                                context.startActivity(android.content.Intent.createChooser(sendIntent, "Share post via"))
                                viewModel.handleIntent(FeedIntent.DismissShareSheet)
                            }
                        )
                    }
                }

                SharingStep.GROUPS -> {
                    val filteredGroups = if (state.groupSearchQuery.isBlank()) {
                        state.eligibleGroups
                    } else {
                        state.eligibleGroups.filter { it.name.contains(state.groupSearchQuery, ignoreCase = true) }
                    }

                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 20.dp)
                            .padding(bottom = 28.dp)
                    ) {
                        // Header Bar with Back Button
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                IconButton(onClick = { viewModel.handleIntent(FeedIntent.SetSharingStep(SharingStep.DESTINATIONS)) }) {
                                    Icon(Icons.Default.ArrowBack, contentDescription = "Back to destinations")
                                }
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(
                                    text = "Share to Groups",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 18.sp
                                )
                            }
                            IconButton(onClick = { viewModel.handleIntent(FeedIntent.DismissShareSheet) }) {
                                Icon(Icons.Default.Close, contentDescription = "Close Share")
                            }
                        }

                        // Search Input
                        OutlinedTextField(
                            value = state.groupSearchQuery,
                            onValueChange = { viewModel.handleIntent(FeedIntent.SearchShareGroups(it)) },
                            placeholder = { Text("Search joined groups...", fontSize = 13.sp) },
                            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(18.dp)) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        Spacer(modifier = Modifier.height(10.dp))

                        // Selection Action Row
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "${state.selectedGroupIds.size} selected of ${state.eligibleGroups.size}",
                                fontSize = 12.sp,
                                color = TextSecondaryLight,
                                fontWeight = FontWeight.Medium
                            )

                            Row {
                                TextButton(
                                    onClick = { viewModel.handleIntent(FeedIntent.SelectAllGroups) },
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                ) {
                                    Text("Select All", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                                }
                                TextButton(
                                    onClick = { viewModel.handleIntent(FeedIntent.ClearSelectedGroups) },
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                ) {
                                    Text("Clear", fontSize = 12.sp, color = TextSecondaryLight)
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(6.dp))

                        if (state.isFetchingShareTargets) {
                            Box(modifier = Modifier.fillMaxWidth().height(120.dp), contentAlignment = Alignment.Center) {
                                CircularProgressIndicator(color = ConnectlyBlue, modifier = Modifier.size(28.dp))
                            }
                        } else if (filteredGroups.isEmpty()) {
                            Box(modifier = Modifier.fillMaxWidth().height(120.dp), contentAlignment = Alignment.Center) {
                                Text(
                                    text = if (state.eligibleGroups.isEmpty()) "You haven't joined any groups yet." else "No matching groups found.",
                                    fontSize = 13.sp,
                                    color = TextSecondaryLight
                                )
                            }
                        } else {
                            LazyColumn(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .heightIn(max = 320.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(filteredGroups, key = { it.id }) { group ->
                                    val isSelected = state.selectedGroupIds.contains(group.id)
                                    Card(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clickable { viewModel.handleIntent(FeedIntent.ToggleSelectGroup(group.id)) },
                                        colors = CardDefaults.cardColors(
                                            containerColor = if (isSelected) ConnectlyBlue.copy(alpha = 0.08f) else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)
                                        ),
                                        shape = RoundedCornerShape(10.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(horizontal = 12.dp, vertical = 8.dp),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                                                Surface(shape = CircleShape, color = ConnectlyBlue.copy(alpha = 0.15f), modifier = Modifier.size(36.dp)) {
                                                    Box(contentAlignment = Alignment.Center) {
                                                        Icon(Icons.Default.Groups, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                                                    }
                                                }
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Column {
                                                    Text(group.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                                                    Text("${group.members_count} members • ${group.privacy.replaceFirstChar { it.uppercase() }}", fontSize = 11.sp, color = TextSecondaryLight)
                                                }
                                            }
                                            Checkbox(
                                                checked = isSelected,
                                                onCheckedChange = { viewModel.handleIntent(FeedIntent.ToggleSelectGroup(group.id)) },
                                                colors = CheckboxDefaults.colors(checkedColor = ConnectlyBlue)
                                            )
                                        }
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(14.dp))

                            Button(
                                onClick = {
                                    viewModel.handleIntent(
                                        FeedIntent.ShareToSelectedGroups(
                                            postId = postId,
                                            caption = state.shareCaption.trim().ifEmpty { null }
                                        )
                                    )
                                },
                                enabled = state.selectedGroupIds.isNotEmpty() && !state.isSharing,
                                modifier = Modifier.fillMaxWidth().height(48.dp),
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                if (state.isSharing) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                                } else {
                                    Text(
                                        text = if (state.selectedGroupIds.isEmpty()) "Select Groups to Share" else "Share to (${state.selectedGroupIds.size}) Groups",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 14.sp
                                    )
                                }
                            }
                        }
                    }
                }

                SharingStep.PAGES -> {
                    val filteredPages = if (state.pageSearchQuery.isBlank()) {
                        state.eligiblePages
                    } else {
                        state.eligiblePages.filter { it.name.contains(state.pageSearchQuery, ignoreCase = true) }
                    }

                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 20.dp)
                            .padding(bottom = 28.dp)
                    ) {
                        // Header Bar with Back Button
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                IconButton(onClick = { viewModel.handleIntent(FeedIntent.SetSharingStep(SharingStep.DESTINATIONS)) }) {
                                    Icon(Icons.Default.ArrowBack, contentDescription = "Back to destinations")
                                }
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(
                                    text = "Share to Pages",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 18.sp
                                )
                            }
                            IconButton(onClick = { viewModel.handleIntent(FeedIntent.DismissShareSheet) }) {
                                Icon(Icons.Default.Close, contentDescription = "Close Share")
                            }
                        }

                        // Search Input
                        OutlinedTextField(
                            value = state.pageSearchQuery,
                            onValueChange = { viewModel.handleIntent(FeedIntent.SearchSharePages(it)) },
                            placeholder = { Text("Search managed pages...", fontSize = 13.sp) },
                            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(18.dp)) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )

                        Spacer(modifier = Modifier.height(10.dp))

                        if (state.isFetchingShareTargets) {
                            Box(modifier = Modifier.fillMaxWidth().height(120.dp), contentAlignment = Alignment.Center) {
                                CircularProgressIndicator(color = ConnectlyBlue, modifier = Modifier.size(28.dp))
                            }
                        } else if (filteredPages.isEmpty()) {
                            Box(modifier = Modifier.fillMaxWidth().height(120.dp), contentAlignment = Alignment.Center) {
                                Text(
                                    text = if (state.eligiblePages.isEmpty()) "You do not manage any pages." else "No matching pages found.",
                                    fontSize = 13.sp,
                                    color = TextSecondaryLight
                                )
                            }
                        } else {
                            LazyColumn(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .heightIn(max = 320.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(filteredPages, key = { it.id }) { page ->
                                    val isSelected = state.selectedPageId == page.id
                                    Card(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clickable { viewModel.handleIntent(FeedIntent.SelectPage(page.id)) },
                                        colors = CardDefaults.cardColors(
                                            containerColor = if (isSelected) ConnectlyBlue.copy(alpha = 0.08f) else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)
                                        ),
                                        shape = RoundedCornerShape(10.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(horizontal = 12.dp, vertical = 8.dp),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                                                Surface(shape = CircleShape, color = ConnectlyBlue.copy(alpha = 0.15f), modifier = Modifier.size(36.dp)) {
                                                    Box(contentAlignment = Alignment.Center) {
                                                        Icon(Icons.Default.Flag, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                                                    }
                                                }
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Column {
                                                    Text(page.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                                                    Text("${page.category ?: "Page"} • Role: ${page.user_role.replaceFirstChar { it.uppercase() }}", fontSize = 11.sp, color = TextSecondaryLight)
                                                }
                                            }
                                            RadioButton(
                                                selected = isSelected,
                                                onClick = { viewModel.handleIntent(FeedIntent.SelectPage(page.id)) },
                                                colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
                                            )
                                        }
                                    }
                                }
                            }

                            Spacer(modifier = Modifier.height(14.dp))

                            Button(
                                onClick = {
                                    viewModel.handleIntent(
                                        FeedIntent.ShareToSelectedPage(
                                            postId = postId,
                                            caption = state.shareCaption.trim().ifEmpty { null }
                                        )
                                    )
                                },
                                enabled = state.selectedPageId != null && !state.isSharing,
                                modifier = Modifier.fillMaxWidth().height(48.dp),
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                if (state.isSharing) {
                                    CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                                } else {
                                    Text(
                                        text = if (state.selectedPageId == null) "Select a Page to Share" else "Share to Selected Page",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 14.sp
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

@Composable
private fun OptionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    title: String,
    subtitle: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Surface(
            shape = CircleShape,
            color = ConnectlyBlue.copy(alpha = 0.12f),
            modifier = Modifier.size(40.dp)
        ) {
            Box(contentAlignment = Alignment.Center) {
                Icon(icon, contentDescription = null, tint = ConnectlyBlue)
            }
        }
        Spacer(modifier = Modifier.width(14.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Text(subtitle, fontSize = 12.sp, color = TextSecondaryLight)
        }
        Icon(Icons.Default.ChevronRight, contentDescription = null, tint = TextSecondaryLight)
    }
}

@Composable
private fun StoriesTraySection(
    storiesTray: List<com.connectly.android.core.model.StoryUserTray>,
    onAddStoryClick: () -> Unit,
    onStoryClick: (Long) -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(0.dp)
    ) {
        Column(modifier = Modifier.padding(vertical = 8.dp)) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("Stories", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                if (storiesTray.isNotEmpty()) {
                    Text(
                        "See all >",
                        color = ConnectlyBlue,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.clickable {
                            val firstId = storiesTray.firstOrNull()?.stories?.firstOrNull()?.id
                                ?: storiesTray.firstOrNull()?.user?.id
                            if (firstId != null) {
                                onStoryClick(firstId)
                            }
                        }
                    )
                }
            }

            Spacer(modifier = Modifier.height(8.dp))

            LazyRow(
                contentPadding = PaddingValues(horizontal = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                // Add Story item
                item {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.clickable { onAddStoryClick() }
                    ) {
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .clip(CircleShape)
                                .border(2.dp, ConnectlyBlue, CircleShape)
                                .background(Color(0xFFE2E8F0)),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(Icons.Default.Add, contentDescription = "Add Story", tint = ConnectlyBlue, modifier = Modifier.size(30.dp))
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("Add Story", fontSize = 12.sp, fontWeight = FontWeight.Medium)
                    }
                }

                // Real stories from backend
                items(storiesTray) { tray ->
                    val storyId = tray.stories.firstOrNull()?.id ?: tray.user.id
                    val borderColor = if (tray.hasUnseenStories) ConnectlyBlue else Color.LightGray
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.clickable { onStoryClick(storyId) }
                    ) {
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .clip(CircleShape)
                                .border(2.5.dp, borderColor, CircleShape)
                                .padding(3.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            ConnectlyAvatar(
                                imageUrl = tray.user.avatar,
                                name = tray.user.name,
                                size = 56.dp
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = tray.user.name.split(" ").firstOrNull() ?: tray.user.name,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Medium,
                            maxLines = 1
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun CreatePostCard(
    userAvatar: String? = null,
    userName: String? = null,
    onCardClick: () -> Unit,
    onLiveClick: () -> Unit,
    onPhotoClick: () -> Unit,
    onVideoClick: () -> Unit,
    onFeelingClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(0.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                ConnectlyAvatar(
                    imageUrl = userAvatar,
                    name = userName ?: "You",
                    size = 40.dp
                )

                Spacer(modifier = Modifier.width(10.dp))

                Box(
                    modifier = Modifier
                        .weight(1f)
                        .height(40.dp)
                        .clip(RoundedCornerShape(20.dp))
                        .background(Color(0xFFF1F5F9))
                        .clickable { onCardClick() }
                        .padding(horizontal = 16.dp),
                    contentAlignment = Alignment.CenterStart
                ) {
                    Text("What's on your mind?", color = TextSecondaryLight, fontSize = 14.sp)
                }
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 10.dp), color = BorderLight)

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // Live 🔴
                Surface(
                    onClick = onLiveClick,
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFFFEF2F2),
                    border = BorderStroke(1.dp, Color(0xFFFEE2E2)),
                    modifier = Modifier.weight(1f)
                ) {
                    Row(
                        modifier = Modifier.padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.Videocam,
                            contentDescription = "Live",
                            tint = Color(0xFFEF4444),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Live", fontSize = 12.5.sp, fontWeight = FontWeight.Bold, color = Color(0xFFDC2626))
                    }
                }

                // Photo 🖼️
                Surface(
                    onClick = onPhotoClick,
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFFF0FDF4),
                    border = BorderStroke(1.dp, Color(0xFFDCFCE7)),
                    modifier = Modifier.weight(1f)
                ) {
                    Row(
                        modifier = Modifier.padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.PhotoLibrary,
                            contentDescription = "Photo",
                            tint = Color(0xFF10B981),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Photo", fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF059669))
                    }
                }

                // Video 📹
                Surface(
                    onClick = onVideoClick,
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFFF5F3FF),
                    border = BorderStroke(1.dp, Color(0xFFEDE9FE)),
                    modifier = Modifier.weight(1f)
                ) {
                    Row(
                        modifier = Modifier.padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.VideoCall,
                            contentDescription = "Video",
                            tint = Color(0xFF8B5CF6),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Video", fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF7C3AED))
                    }
                }

                // Feeling 😀
                Surface(
                    onClick = onFeelingClick,
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFFFFFBEB),
                    border = BorderStroke(1.dp, Color(0xFFFEF3C7)),
                    modifier = Modifier.weight(1f)
                ) {
                    Row(
                        modifier = Modifier.padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.EmojiEmotions,
                            contentDescription = "Feeling",
                            tint = Color(0xFFF59E0B),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Feeling", fontSize = 12.5.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFFD97706))
                    }
                }
            }
        }
    }
}

@Composable
private fun CreateLiveDialog(
    onDismiss: () -> Unit,
    onStartLive: (String, String?) -> Unit
) {
    var title by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var privacy by remember { mutableStateOf("public") }
    var isStarting by remember { mutableStateOf(false) }

    AlertDialog(
        onDismissRequest = { if (!isStarting) onDismiss() },
        title = {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(12.dp)
                        .clip(CircleShape)
                        .background(Color(0xFFEF4444))
                )
                Text(
                    text = "Go Live on BONDHOO",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp
                )
            }
        },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                Text(
                    text = "Broadcast video live to your friends, followers and community in real time.",
                    fontSize = 13.sp,
                    color = Color.Gray
                )

                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Live Stream Title *") },
                    placeholder = { Text("e.g. Chatting with friends, Q&A...") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    singleLine = true
                )

                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Description (Optional)") },
                    placeholder = { Text("Add more details about your broadcast...") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    maxLines = 3
                )

                // Privacy Selector
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterChip(
                        selected = privacy == "public",
                        onClick = { privacy = "public" },
                        label = { Text("Public 🌐") },
                        modifier = Modifier.weight(1f)
                    )
                    FilterChip(
                        selected = privacy == "friends",
                        onClick = { privacy = "friends" },
                        label = { Text("Friends 👥") },
                        modifier = Modifier.weight(1f)
                    )
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (title.isNotBlank()) {
                        isStarting = true
                        onStartLive(title.trim(), description.takeIf { it.isNotBlank() })
                    }
                },
                enabled = title.isNotBlank() && !isStarting,
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFEF4444)),
                shape = RoundedCornerShape(10.dp)
            ) {
                if (isStarting) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        color = Color.White,
                        strokeWidth = 2.dp
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                } else {
                    Icon(Icons.Default.Videocam, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                }
                Text(if (isStarting) "Starting Live..." else "Go Live Now", fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDismiss,
                enabled = !isStarting
            ) {
                Text("Cancel")
            }
        },
        shape = RoundedCornerShape(20.dp)
    )
}

@Composable
private fun SuggestedFriendsCarousel(
    users: List<User>,
    requestedFriendIds: Set<Long> = emptySet(),
    isSectionHidden: Boolean = false,
    onToggleHide: () -> Unit = {},
    onAddFriend: (Long) -> Unit,
    onCancelFriend: (Long) -> Unit = {},
    onProfileClick: (String) -> Unit = {}
) {
    if (isSectionHidden) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 6.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 14.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        Icons.Default.VisibilityOff,
                        contentDescription = null,
                        tint = TextSecondaryLight,
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = "People you may know is hidden",
                        fontSize = 13.sp,
                        color = TextSecondaryLight,
                        fontWeight = FontWeight.Medium
                    )
                }
                TextButton(
                    onClick = onToggleHide,
                    contentPadding = PaddingValues(horizontal = 10.dp, vertical = 2.dp)
                ) {
                    Text("Show", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                }
            }
        }
        return
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(0.dp)
    ) {
        Column(modifier = Modifier.padding(vertical = 12.dp)) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "People you may know",
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp
                )
                TextButton(
                    onClick = onToggleHide,
                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                ) {
                    Icon(
                        Icons.Default.VisibilityOff,
                        contentDescription = "Hide",
                        tint = TextSecondaryLight,
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = "Hide",
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        fontWeight = FontWeight.Medium
                    )
                }
            }

            LazyRow(
                contentPadding = PaddingValues(horizontal = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                items(users, key = { it.id }) { user ->
                    val isRequested = requestedFriendIds.contains(user.id)
                    Card(
                        modifier = Modifier
                            .width(140.dp)
                            .border(1.dp, BorderLight, RoundedCornerShape(12.dp)),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White)
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            modifier = Modifier.padding(8.dp)
                        ) {
                            Column(
                                horizontalAlignment = Alignment.CenterHorizontally,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { onProfileClick(user.username.ifBlank { user.id.toString() }) }
                            ) {
                                ConnectlyAvatar(
                                    imageUrl = user.avatar,
                                    name = user.name,
                                    size = 60.dp
                                )
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(user.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1)
                                Text("${user.friendsCount} mutual friends", fontSize = 11.sp, color = TextSecondaryLight, maxLines = 1)
                            }
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(
                                onClick = {
                                    if (isRequested) {
                                        onCancelFriend(user.id)
                                    } else {
                                        onAddFriend(user.id)
                                    }
                                },
                                shape = RoundedCornerShape(16.dp),
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = if (isRequested) Color(0xFFF1F5F9) else ConnectlyBlue,
                                    contentColor = if (isRequested) Color(0xFF475569) else Color.White
                                ),
                                border = if (isRequested) BorderStroke(1.dp, Color(0xFFCBD5E1)) else null,
                                contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp),
                                modifier = Modifier.fillMaxWidth().height(30.dp)
                            ) {
                                Text(
                                    text = if (isRequested) "Cancel" else "Add Friend",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold
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
private fun PostItemCard(
    post: Post,
    onReact: (String) -> Unit,
    onCommentsClick: () -> Unit,
    onShareClick: () -> Unit,
    onSaveClick: () -> Unit,
    onEditClick: () -> Unit = {},
    onDeleteClick: () -> Unit,
    onHideClick: () -> Unit = {},
    onReportClick: () -> Unit,
    onProfileClick: () -> Unit = {}
) {
    var showReactionMenu by remember { mutableStateOf(false) }
    var showOptionsMenu by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(0.dp)
    ) {
        Column(modifier = Modifier.padding(vertical = 12.dp)) {
            // Header Row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(
                    modifier = Modifier
                        .weight(1f)
                        .clickable(onClick = onProfileClick),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    ConnectlyAvatar(
                        imageUrl = post.author.avatar,
                        name = post.author.name,
                        size = 42.dp,
                        isVerified = post.author.isVerified
                    )

                    Spacer(modifier = Modifier.width(10.dp))

                    Column {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(post.author.name, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            if (post.author.isVerified) {
                                Spacer(modifier = Modifier.width(4.dp))
                                Icon(Icons.Default.CheckCircle, contentDescription = "Verified", tint = ConnectlyBlue, modifier = Modifier.size(15.dp))
                            }
                        }
                        Text("2h • ${post.privacy.replaceFirstChar { it.uppercase() }} 🌐", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                }

                Box {
                    IconButton(onClick = { showOptionsMenu = true }) {
                        Icon(Icons.Default.MoreVert, contentDescription = "Options", tint = TextSecondaryLight)
                    }

                    DropdownMenu(
                        expanded = showOptionsMenu,
                        onDismissRequest = { showOptionsMenu = false }
                    ) {
                        DropdownMenuItem(
                            text = { Text(if (post.isSaved) "Unsave Post" else "Save Post") },
                            leadingIcon = { Icon(if (post.isSaved) Icons.Default.Bookmark else Icons.Default.BookmarkBorder, contentDescription = null) },
                            onClick = {
                                showOptionsMenu = false
                                onSaveClick()
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Hide Post") },
                            leadingIcon = { Icon(Icons.Default.VisibilityOff, contentDescription = null) },
                            onClick = {
                                showOptionsMenu = false
                                onHideClick()
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Edit Post") },
                            leadingIcon = { Icon(Icons.Default.Edit, contentDescription = null) },
                            onClick = {
                                showOptionsMenu = false
                                onEditClick()
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Report Post", color = ConnectlyRose) },
                            leadingIcon = { Icon(Icons.Default.Flag, contentDescription = null, tint = ConnectlyRose) },
                            onClick = {
                                showOptionsMenu = false
                                onReportClick()
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Delete Post", color = Color(0xFFDC2626)) },
                            leadingIcon = { Icon(Icons.Default.Delete, contentDescription = null, tint = Color(0xFFDC2626)) },
                            onClick = {
                                showOptionsMenu = false
                                onDeleteClick()
                            }
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Safety Moderation Pending Banner
            if (post.status == "pending_review") {
                Surface(
                    color = Color(0xFFFFFBEB),
                    shape = RoundedCornerShape(8.dp),
                    border = BorderStroke(1.dp, Color(0xFFFDE68A)),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 4.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("🛡️", fontSize = 14.sp)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "Safety Review: This post is undergoing automated community review and is visible only to you until approved.",
                            color = Color(0xFF92400E),
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }
                Spacer(modifier = Modifier.height(4.dp))
            }

            // Post Content with highlighted hashtags & mentions
            if (post.content.isNotBlank()) {
                val annotatedContent = remember(post.content) {
                    buildAnnotatedString {
                        val words = post.content.split(" ")
                        words.forEachIndexed { index, word ->
                            when {
                                word.startsWith("#") -> {
                                    withStyle(SpanStyle(color = ConnectlyBlue, fontWeight = FontWeight.SemiBold)) {
                                        append(word)
                                    }
                                }
                                word.startsWith("@") -> {
                                    withStyle(SpanStyle(color = ConnectlyIndigo, fontWeight = FontWeight.SemiBold)) {
                                        append(word)
                                    }
                                }
                                else -> append(word)
                            }
                            if (index < words.size - 1) append(" ")
                        }
                    }
                }

                Text(
                    text = annotatedContent,
                    fontSize = 15.sp,
                    lineHeight = 21.sp,
                    modifier = Modifier.padding(horizontal = 16.dp)
                )
                Spacer(modifier = Modifier.height(10.dp))
            }

            // Attached Media Image(s)
            if (post.mediaUrls.isNotEmpty()) {
                AsyncImage(
                    model = resolveMediaUrl(post.mediaUrls.first()),
                    contentDescription = "Post Media",
                    contentScale = ContentScale.Crop,
                    alignment = Alignment.Center,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(260.dp)
                )
                Spacer(modifier = Modifier.height(8.dp))
            }

            // Reactions Count Row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("👍 ❤️ 🥰", fontSize = 14.sp)
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("${post.reactionsCount}", fontSize = 13.sp, color = TextSecondaryLight)
                }

                Text("${post.commentsCount} comments • ${post.sharesCount} shares", fontSize = 13.sp, color = TextSecondaryLight)
            }

            HorizontalDivider(modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp), color = BorderLight)

            // Reactions Bar Floating Menu
            AnimatedVisibility(visible = showReactionMenu) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 6.dp)
                        .clip(RoundedCornerShape(24.dp))
                        .background(Color(0xFFF1F5F9))
                        .padding(horizontal = 8.dp, vertical = 6.dp),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    ReactionType.values().forEach { reaction ->
                        Text(
                            text = reaction.emoji,
                            fontSize = 24.sp,
                            modifier = Modifier
                                .clickable {
                                    onReact(reaction.value)
                                    showReactionMenu = false
                                }
                                .padding(4.dp)
                        )
                    }
                }
            }

            // Action Buttons: Like, Comment, Share
            HorizontalDivider(color = Color(0xFFF1F5F9), thickness = 1.dp)
            Spacer(modifier = Modifier.height(6.dp))

            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // Like / React Button
                val isReacted = post.userReaction != null
                Surface(
                    onClick = { showReactionMenu = !showReactionMenu },
                    shape = RoundedCornerShape(10.dp),
                    color = if (isReacted) Color(0xFFEFF6FF) else Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, if (isReacted) ConnectlyBlue.copy(alpha = 0.35f) else Color(0xFFE2E8F0)),
                    modifier = Modifier
                        .weight(1f)
                        .height(38.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center,
                        modifier = Modifier.fillMaxSize()
                    ) {
                        val currentEmoji = ReactionType.values().find { it.value == post.userReaction }?.emoji ?: "👍"
                        Text(currentEmoji, fontSize = 16.sp)
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = post.userReaction?.replaceFirstChar { it.uppercase() } ?: "Like",
                            color = if (isReacted) ConnectlyBlue else Color(0xFF475569),
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 13.sp
                        )
                    }
                }

                // Comment Button
                Surface(
                    onClick = onCommentsClick,
                    shape = RoundedCornerShape(10.dp),
                    color = Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                    modifier = Modifier
                        .weight(1f)
                        .height(38.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center,
                        modifier = Modifier.fillMaxSize()
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.Comment,
                            contentDescription = "Comment",
                            tint = Color(0xFF475569),
                            modifier = Modifier.size(17.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Comment",
                            color = Color(0xFF475569),
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 13.sp
                        )
                    }
                }

                // Share Button
                Surface(
                    onClick = onShareClick,
                    shape = RoundedCornerShape(10.dp),
                    color = Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                    modifier = Modifier
                        .weight(1f)
                        .height(38.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center,
                        modifier = Modifier.fillMaxSize()
                    ) {
                        Icon(
                            imageVector = Icons.Default.Share,
                            contentDescription = "Share",
                            tint = Color(0xFF475569),
                            modifier = Modifier.size(17.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Share",
                            color = Color(0xFF475569),
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 13.sp
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun CommentsDrawerContent(
    postId: Long,
    comments: List<Comment>,
    activeParentId: Long?,
    replyingToUser: String?,
    onProfileClick: (String) -> Unit = {},
    onStartReply: (Long, String) -> Unit,
    onCancelReply: () -> Unit,
    onAddComment: (String, Long?) -> Unit,
    onReactComment: (Long, String) -> Unit,
    onDeleteComment: (Long) -> Unit = {}
) {
    var newCommentText by remember { mutableStateOf("") }
    var showEmojiPicker by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .fillMaxHeight(0.88f)
            .navigationBarsPadding()
            .imePadding()
            .padding(horizontal = 16.dp, vertical = 8.dp)
    ) {
        Text("Comments", fontWeight = FontWeight.Bold, fontSize = 18.sp)
        Spacer(modifier = Modifier.height(12.dp))

        if (comments.isEmpty()) {
            Box(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth(),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "No comments yet. Be the first to comment!",
                    fontSize = 13.sp,
                    color = TextSecondaryLight
                )
            }
        } else {
            LazyColumn(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                items(comments, key = { it.id }) { comment ->
                    val authorTarget = comment.author.username.ifBlank { comment.author.id.toString() }
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(start = if (comment.parentId != null) 36.dp else 0.dp)
                    ) {
                        Box(modifier = Modifier.clickable { onProfileClick(authorTarget) }) {
                            ConnectlyAvatar(
                                imageUrl = comment.author.avatar,
                                name = comment.author.name,
                                size = 36.dp
                            )
                        }

                        Spacer(modifier = Modifier.width(10.dp))

                        Column(modifier = Modifier.weight(1f)) {
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Color(0xFFF1F5F9))
                                    .padding(10.dp)
                            ) {
                                Column {
                                    Text(
                                        text = comment.author.name,
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 13.sp,
                                        modifier = Modifier.clickable { onProfileClick(authorTarget) }
                                    )
                                    Spacer(modifier = Modifier.height(2.dp))
                                    Text(comment.body, fontSize = 14.sp)
                                }
                            }

                            Row(
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                horizontalArrangement = Arrangement.spacedBy(16.dp)
                            ) {
                                Text(comment.createdAt, fontSize = 11.sp, color = TextSecondaryLight)
                                Text(
                                    "Like (${comment.reactionsCount})",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = if (comment.userReaction != null) ConnectlyBlue else TextSecondaryLight,
                                    modifier = Modifier.clickable { onReactComment(comment.id, "like") }
                                )
                                Text(
                                    "Reply",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = ConnectlyBlue,
                                    modifier = Modifier.clickable {
                                        onStartReply(comment.id, comment.author.username)
                                    }
                                )
                                Text(
                                    "Delete",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color(0xFFDC2626),
                                    modifier = Modifier.clickable {
                                        onDeleteComment(comment.id)
                                    }
                                )
                            }
                        }
                    }
                }
            }
        }

        // Active Reply Banner
        if (replyingToUser != null) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color(0xFFE0E7FF), RoundedCornerShape(8.dp))
                    .padding(horizontal = 12.dp, vertical = 6.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text = "Replying to @$replyingToUser",
                    color = ConnectlyBlue,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Medium
                )
                Icon(
                    imageVector = Icons.Default.Close,
                    contentDescription = "Cancel Reply",
                    modifier = Modifier
                        .size(16.dp)
                        .clickable { onCancelReply() },
                    tint = ConnectlyBlue
                )
            }
            Spacer(modifier = Modifier.height(6.dp))
        }

        // Quick Emoji Reaction Bar (1-tap to insert into comment)
        ConnectlyQuickEmojiRow(
            onEmojiSelected = { emoji ->
                newCommentText += emoji
            }
        )
        Spacer(modifier = Modifier.height(4.dp))

        // Comment Input Row
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(
                onClick = { showEmojiPicker = !showEmojiPicker },
                modifier = Modifier.size(40.dp)
            ) {
                Icon(
                    imageVector = Icons.Default.EmojiEmotions,
                    contentDescription = "Select Emoji",
                    tint = if (showEmojiPicker) ConnectlyBlue else Color(0xFF64748B),
                    modifier = Modifier.size(26.dp)
                )
            }

            Spacer(modifier = Modifier.width(4.dp))

            OutlinedTextField(
                value = newCommentText,
                onValueChange = { newCommentText = it },
                placeholder = {
                    Text(if (replyingToUser != null) "Write a reply..." else "Write a comment...")
                },
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(24.dp),
                maxLines = 3
            )

            Spacer(modifier = Modifier.width(6.dp))

            IconButton(
                onClick = {
                    if (newCommentText.isNotBlank()) {
                        onAddComment(newCommentText, activeParentId)
                        newCommentText = ""
                        showEmojiPicker = false
                    }
                },
                enabled = newCommentText.isNotBlank()
            ) {
                Icon(Icons.Default.Send, contentDescription = "Send", tint = ConnectlyBlue)
            }
        }

        // Full Categorized Emoji Picker
        if (showEmojiPicker) {
            Spacer(modifier = Modifier.height(8.dp))
            ConnectlyEmojiPicker(
                onEmojiSelected = { emoji ->
                    newCommentText += emoji
                },
                onBackspace = {
                    if (newCommentText.isNotEmpty()) {
                        newCommentText = if (newCommentText.length >= 2 && Character.isSurrogate(newCommentText.last())) {
                            newCommentText.dropLast(2)
                        } else {
                            newCommentText.dropLast(1)
                        }
                    }
                },
                onClose = { showEmojiPicker = false },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(240.dp)
            )
        }
    }
}

@Composable
private fun ReportPostDialog(
    postId: Long,
    onDismiss: () -> Unit,
    onSubmit: (String, String?) -> Unit
) {
    val reasons = listOf(
        "spam" to "Spam",
        "harassment" to "Harassment or bullying",
        "hate_speech" to "Hate speech",
        "false_information" to "False information",
        "violence" to "Violence or dangerous threat",
        "other" to "Something else"
    )
    var selectedReason by remember { mutableStateOf(reasons.first().first) }
    var detailsText by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Report Post", fontWeight = FontWeight.Bold) },
        text = {
            Column {
                Text("Select a reason for reporting this post:", fontSize = 13.sp, color = TextSecondaryLight)
                Spacer(modifier = Modifier.height(8.dp))

                reasons.forEach { (key, label) ->
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { selectedReason = key }
                            .padding(vertical = 4.dp)
                    ) {
                        RadioButton(
                            selected = selectedReason == key,
                            onClick = { selectedReason = key }
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(label, fontSize = 13.sp)
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(
                    value = detailsText,
                    onValueChange = { detailsText = it },
                    placeholder = { Text("Provide additional details (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    maxLines = 3
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(selectedReason, detailsText.ifBlank { null }) },
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyRose)
            ) {
                Text("Submit Report")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}
