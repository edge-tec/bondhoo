package com.connectly.android.feature.feed.presentation.feed

import androidx.compose.animation.AnimatedVisibility
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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Comment
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.component.resolveMediaUrl
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.ReactionType
import com.connectly.android.core.model.User

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeedScreen(
    onNavigateToCreatePost: () -> Unit,
    onNavigateToStoryViewer: (Long) -> Unit,
    onNavigateToCreateStory: () -> Unit,
    onNavigateToProfile: () -> Unit,
    onNavigateToSearch: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: FeedViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val listState = rememberLazyListState()
    val snackbarHostState = remember { SnackbarHostState() }

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
                    IconButton(onClick = onNavigateToSearch) {
                        Icon(Icons.Default.Search, contentDescription = "Search")
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
                            imageUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                            name = "My Profile",
                            size = 34.dp
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
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
            // Tab row: For You, Following, Groups, Pages
            item {
                val tabs = listOf(
                    "for_you" to "For You",
                    "following" to "Following",
                    "groups" to "Groups",
                    "pages" to "Pages"
                )
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surface)
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    tabs.forEach { (key, label) ->
                        val isSelected = state.selectedTab == key
                        Button(
                            onClick = { viewModel.handleIntent(FeedIntent.SelectTab(key)) },
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = if (isSelected) ConnectlyBlue else Color(0xFFF1F5F9),
                                contentColor = if (isSelected) Color.White else TextPrimaryLight
                            ),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                            modifier = Modifier.height(36.dp)
                        ) {
                            Text(label, fontSize = 13.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium)
                        }
                    }
                }
            }

            // Stories Tray
            item {
                StoriesTraySection(
                    onAddStoryClick = onNavigateToCreateStory,
                    onStoryClick = { onNavigateToStoryViewer(it) }
                )
            }

            // Composer Card ("What's on your mind?")
            item {
                CreatePostCard(
                    onCardClick = onNavigateToCreatePost,
                    onPhotoClick = onNavigateToCreatePost,
                    onVideoClick = onNavigateToCreatePost,
                    onFeelingClick = onNavigateToCreatePost
                )
            }

            // Suggested Friends Section
            if (state.suggestedFriends.isNotEmpty()) {
                item {
                    SuggestedFriendsCarousel(
                        users = state.suggestedFriends,
                        onAddFriend = { /* Handled via FriendRepository */ }
                    )
                }
            }

            // Feed Posts List
            items(state.posts, key = { it.id }) { post ->
                PostItemCard(
                    post = post,
                    onReact = { reaction -> viewModel.handleIntent(FeedIntent.ReactToPost(post.id, reaction)) },
                    onCommentsClick = { viewModel.handleIntent(FeedIntent.OpenComments(post.id)) },
                    onShareClick = { viewModel.handleIntent(FeedIntent.SharePost(post.id)) },
                    onSaveClick = { viewModel.handleIntent(FeedIntent.SavePost(post.id)) },
                    onDeleteClick = { viewModel.handleIntent(FeedIntent.DeletePost(post.id)) },
                    onReportClick = { viewModel.handleIntent(FeedIntent.OpenReportDialog(post.id)) }
                )
                Spacer(modifier = Modifier.height(8.dp))
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
            onDismissRequest = { viewModel.handleIntent(FeedIntent.CloseComments) }
        ) {
            CommentsDrawerContent(
                postId = state.activeCommentsPostId!!,
                comments = state.activeComments,
                activeParentId = state.activeParentCommentId,
                replyingToUser = state.replyingToUser,
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
                }
            )
        }
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
}

@Composable
private fun StoriesTraySection(
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
                Text(
                    "See all >",
                    color = ConnectlyBlue,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.clickable { }
                )
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

                // Sample stories
                val mockStories = listOf(
                    Triple(301L, "Sadia", "https://images.unsplash.com/photo-1517841905240-472988babdf9"),
                    Triple(302L, "Rahat", "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d"),
                    Triple(303L, "Tania", "https://images.unsplash.com/photo-1494790108377-be9c29b29330")
                )

                items(mockStories) { (id, name, avatar) ->
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.clickable { onStoryClick(id) }
                    ) {
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .clip(CircleShape)
                                .border(2.5.dp, ConnectlyBlue, CircleShape)
                                .padding(3.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            ConnectlyAvatar(
                                imageUrl = avatar,
                                name = name,
                                size = 56.dp
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(name, fontSize = 12.sp, fontWeight = FontWeight.Medium)
                    }
                }
            }
        }
    }
}

@Composable
private fun CreatePostCard(
    onCardClick: () -> Unit,
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
                    imageUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                    name = "You",
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
                horizontalArrangement = Arrangement.SpaceAround
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable { onPhotoClick() }
                ) {
                    Icon(Icons.Default.PhotoLibrary, contentDescription = "Photo", tint = ConnectlyGreen, modifier = Modifier.size(20.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Photo", fontSize = 13.sp, fontWeight = FontWeight.Medium)
                }

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable { onVideoClick() }
                ) {
                    Icon(Icons.Default.VideoCall, contentDescription = "Video", tint = ConnectlyRose, modifier = Modifier.size(20.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Video", fontSize = 13.sp, fontWeight = FontWeight.Medium)
                }

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable { onFeelingClick() }
                ) {
                    Icon(Icons.Default.EmojiEmotions, contentDescription = "Feeling", tint = ConnectlyAmber, modifier = Modifier.size(20.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Feeling", fontSize = 13.sp, fontWeight = FontWeight.Medium)
                }
            }
        }
    }
}

@Composable
private fun SuggestedFriendsCarousel(
    users: List<User>,
    onAddFriend: (Long) -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(0.dp)
    ) {
        Column(modifier = Modifier.padding(vertical = 12.dp)) {
            Text(
                text = "People you may know",
                fontWeight = FontWeight.Bold,
                fontSize = 15.sp,
                modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp)
            )

            LazyRow(
                contentPadding = PaddingValues(horizontal = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                items(users, key = { it.id }) { user ->
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
                            ConnectlyAvatar(
                                imageUrl = user.avatar,
                                name = user.name,
                                size = 60.dp
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(user.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1)
                            Text("${user.friendsCount} mutual friends", fontSize = 11.sp, color = TextSecondaryLight, maxLines = 1)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(
                                onClick = { onAddFriend(user.id) },
                                shape = RoundedCornerShape(16.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
                                modifier = Modifier.fillMaxWidth().height(30.dp)
                            ) {
                                Text("Add Friend", fontSize = 11.sp, fontWeight = FontWeight.Bold)
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
    onDeleteClick: () -> Unit,
    onReportClick: () -> Unit
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
                ConnectlyAvatar(
                    imageUrl = post.author.avatar,
                    name = post.author.name,
                    size = 42.dp,
                    isVerified = post.author.isVerified
                )

                Spacer(modifier = Modifier.width(10.dp))

                Column(modifier = Modifier.weight(1f)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(post.author.name, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        if (post.author.isVerified) {
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(Icons.Default.CheckCircle, contentDescription = "Verified", tint = ConnectlyBlue, modifier = Modifier.size(15.dp))
                        }
                    }
                    Text("2h • ${post.privacy.replaceFirstChar { it.uppercase() }} 🌐", fontSize = 12.sp, color = TextSecondaryLight)
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
                            text = { Text("Report Post", color = ConnectlyRose) },
                            leadingIcon = { Icon(Icons.Default.Flag, contentDescription = null, tint = ConnectlyRose) },
                            onClick = {
                                showOptionsMenu = false
                                onReportClick()
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Delete Post") },
                            leadingIcon = { Icon(Icons.Default.Delete, contentDescription = null) },
                            onClick = {
                                showOptionsMenu = false
                                onDeleteClick()
                            }
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

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
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceAround
            ) {
                TextButton(
                    onClick = { showReactionMenu = !showReactionMenu }
                ) {
                    val currentEmoji = ReactionType.values().find { it.value == post.userReaction }?.emoji ?: "👍"
                    Text(currentEmoji, fontSize = 16.sp)
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = post.userReaction?.replaceFirstChar { it.uppercase() } ?: "Like",
                        color = if (post.userReaction != null) ConnectlyBlue else TextSecondaryLight,
                        fontWeight = FontWeight.SemiBold
                    )
                }

                TextButton(onClick = onCommentsClick) {
                    Icon(Icons.AutoMirrored.Filled.Comment, contentDescription = "Comment", tint = TextSecondaryLight, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Comment", color = TextSecondaryLight, fontWeight = FontWeight.SemiBold)
                }

                TextButton(onClick = onShareClick) {
                    Icon(Icons.Default.Share, contentDescription = "Share", tint = TextSecondaryLight, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Share", color = TextSecondaryLight, fontWeight = FontWeight.SemiBold)
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
    onStartReply: (Long, String) -> Unit,
    onCancelReply: () -> Unit,
    onAddComment: (String, Long?) -> Unit,
    onReactComment: (Long, String) -> Unit
) {
    var newCommentText by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .height(500.dp)
            .padding(16.dp)
    ) {
        Text("Comments", fontWeight = FontWeight.Bold, fontSize = 18.sp)
        Spacer(modifier = Modifier.height(12.dp))

        LazyColumn(
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(comments, key = { it.id }) { comment ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = if (comment.parentId != null) 36.dp else 0.dp)
                ) {
                    ConnectlyAvatar(
                        imageUrl = comment.author.avatar,
                        name = comment.author.name,
                        size = 36.dp
                    )

                    Spacer(modifier = Modifier.width(10.dp))

                    Column(modifier = Modifier.weight(1f)) {
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(12.dp))
                                .background(Color(0xFFF1F5F9))
                                .padding(10.dp)
                        ) {
                            Column {
                                Text(comment.author.name, fontWeight = FontWeight.Bold, fontSize = 13.sp)
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

        // Comment Input
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            OutlinedTextField(
                value = newCommentText,
                onValueChange = { newCommentText = it },
                placeholder = {
                    Text(if (replyingToUser != null) "Write a reply..." else "Write a comment...")
                },
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(20.dp),
                maxLines = 3
            )

            Spacer(modifier = Modifier.width(8.dp))

            IconButton(
                onClick = {
                    if (newCommentText.isNotBlank()) {
                        onAddComment(newCommentText, activeParentId)
                        newCommentText = ""
                    }
                },
                enabled = newCommentText.isNotBlank()
            ) {
                Icon(Icons.Default.Send, contentDescription = "Send", tint = ConnectlyBlue)
            }
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
        "spam" to "স্প্যাম (Spam)",
        "harassment" to "হয়রানি বা অপমানজনক আচরণ (Harassment)",
        "hate_speech" to "বিদ্বেষমূলক বক্তব্য (Hate speech)",
        "false_information" to "ভুয়া বা বিভ্রান্তিকর তথ্য (False information)",
        "violence" to "সহিংসতা বা হুমকি (Violence)",
        "other" to "অন্যান্য সমস্যা (Other)"
    )
    var selectedReason by remember { mutableStateOf(reasons.first().first) }
    var detailsText by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("পোস্ট রিপোর্ট করুন", fontWeight = FontWeight.Bold) },
        text = {
            Column {
                Text("এই পোস্টে কী সমস্যা হচ্ছে তা নির্বাচন করুন:", fontSize = 13.sp, color = TextSecondaryLight)
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
                    placeholder = { Text("অতিরিক্ত বিবরণ দিন (ঐচ্ছিক)") },
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
                Text("রিপোর্ট জমা দিন")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("বাতিল")
            }
        }
    )
}
