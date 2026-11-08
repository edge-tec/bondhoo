@file:OptIn(
    androidx.compose.material3.ExperimentalMaterial3Api::class,
    androidx.media3.common.util.UnstableApi::class
)

package com.connectly.android.feature.reels.presentation.player

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.VerticalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Comment
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyEmojiPicker
import com.connectly.android.core.designsystem.component.ConnectlyQuickEmojiRow
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.PlayerView
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.component.resolveMediaUrl
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyRose
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Reel

@OptIn(ExperimentalMaterial3Api::class, UnstableApi::class)
@Composable
fun ReelsScreen(
    onNavigateToCreateReel: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: ReelsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var feedMenuExpanded by remember { mutableStateOf(false) }
    var selectedFeedType by remember { mutableStateOf("For You") }
    var showSearchDialog by remember { mutableStateOf(false) }
    var searchQuery by remember { mutableStateOf("") }

    if (showSearchDialog) {
        AlertDialog(
            onDismissRequest = { showSearchDialog = false },
            title = { Text("Search Reels", fontWeight = FontWeight.Bold) },
            text = {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search hashtags, creators, sounds...") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
            },
            confirmButton = {
                Button(
                    onClick = { showSearchDialog = false },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Search")
                }
            },
            dismissButton = {
                TextButton(onClick = { showSearchDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    if (state.reels.isEmpty()) {
        Box(
            modifier = modifier
                .fillMaxSize()
                .background(Color.Black),
            contentAlignment = Alignment.Center
        ) {
            if (state.isLoading) {
                CircularProgressIndicator(color = Color.White)
            } else {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center,
                    modifier = Modifier.padding(24.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.VideoLibrary,
                        contentDescription = null,
                        tint = Color.White.copy(alpha = 0.6f),
                        modifier = Modifier.size(64.dp)
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "No Videos Yet",
                        color = Color.White,
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Be the first to share an exciting video!",
                        color = Color.White.copy(alpha = 0.7f),
                        fontSize = 14.sp
                    )
                    Spacer(modifier = Modifier.height(20.dp))
                    Button(
                        onClick = onNavigateToCreateReel,
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Create Video", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
        return
    }

    val pagerState = rememberPagerState(pageCount = { state.reels.size })

    LaunchedEffect(pagerState.currentPage) {
        viewModel.handleIntent(ReelsIntent.PageChanged(pagerState.currentPage))
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(Color.Black)
    ) {
        VerticalPager(
            state = pagerState,
            modifier = Modifier.fillMaxSize()
        ) { pageIndex ->
            val reel = state.reels[pageIndex]
            val isCurrentPage = pagerState.currentPage == pageIndex

            ReelPlayerItem(
                reel = reel,
                isCurrentPage = isCurrentPage,
                onLikeClick = { viewModel.handleIntent(ReelsIntent.ToggleLike(reel.id)) },
                onCommentClick = { viewModel.handleIntent(ReelsIntent.OpenComments(reel.id)) },
                onShareClick = { viewModel.handleIntent(ReelsIntent.ShareReel(reel.id)) },
                onSaveClick = { viewModel.handleIntent(ReelsIntent.ToggleSave(reel.id)) },
                onFollowClick = { viewModel.handleIntent(ReelsIntent.FollowCreator(reel.creator.id)) }
            )
        }

        // Top Navigation Header: "Reels v", Search, Camera
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 16.dp, vertical = 8.dp)
                .align(Alignment.TopCenter),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable { feedMenuExpanded = true }
                ) {
                    Text(
                        text = selectedFeedType,
                        fontWeight = FontWeight.Bold,
                        fontSize = 22.sp,
                        color = Color.White
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Icon(
                        imageVector = Icons.Default.KeyboardArrowDown,
                        contentDescription = "Dropdown",
                        tint = Color.White
                    )
                }

                DropdownMenu(
                    expanded = feedMenuExpanded,
                    onDismissRequest = { feedMenuExpanded = false }
                ) {
                    val feedCategories = listOf(
                        "For You" to "for_you",
                        "Following" to "following",
                        "Friends" to "friends",
                        "Popular" to "popular",
                        "Latest" to "latest"
                    )
                    feedCategories.forEach { (label, tabKey) ->
                        DropdownMenuItem(
                            text = { Text(label) },
                            onClick = {
                                selectedFeedType = label
                                feedMenuExpanded = false
                                viewModel.handleIntent(ReelsIntent.LoadFeed(tabKey))
                            }
                        )
                    }
                }
            }

            Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                IconButton(onClick = { showSearchDialog = true }) {
                    Icon(Icons.Default.Search, contentDescription = "Search", tint = Color.White)
                }
                IconButton(onClick = onNavigateToCreateReel) {
                    Icon(Icons.Default.CameraAlt, contentDescription = "Camera", tint = Color.White)
                }
            }
        }
    }

    // Reels Comments Modal Bottom Sheet
    if (state.activeCommentsReelId != null) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ReelsIntent.CloseComments) }
        ) {
            ReelsCommentsDrawerContent(
                reelId = state.activeCommentsReelId!!,
                comments = state.activeComments,
                onAddComment = { body ->
                    viewModel.handleIntent(ReelsIntent.AddComment(state.activeCommentsReelId!!, body))
                }
            )
        }
    }
}

@OptIn(UnstableApi::class)
@Composable
private fun ReelPlayerItem(
    reel: Reel,
    isCurrentPage: Boolean,
    onLikeClick: () -> Unit,
    onCommentClick: () -> Unit,
    onShareClick: () -> Unit,
    onSaveClick: () -> Unit,
    onFollowClick: () -> Unit
) {
    val context = LocalContext.current
    var isPlaying by remember { mutableStateOf(true) }

    val resolvedVideoUrl = remember(reel.videoUrl) {
        resolveMediaUrl(reel.videoUrl) ?: reel.videoUrl
    }

    // Media3 ExoPlayer instance lifecycle managed per reel
    val exoPlayer = remember(context, resolvedVideoUrl) {
        ExoPlayer.Builder(context).build().apply {
            val mediaItem = MediaItem.fromUri(resolvedVideoUrl)
            setMediaItem(mediaItem)
            repeatMode = Player.REPEAT_MODE_ONE
            prepare()
        }
    }

    DisposableEffect(isCurrentPage) {
        if (isCurrentPage) {
            exoPlayer.playWhenReady = true
            isPlaying = true
        } else {
            exoPlayer.playWhenReady = false
            isPlaying = false
        }
        onDispose {
            exoPlayer.pause()
        }
    }

    DisposableEffect(Unit) {
        onDispose {
            exoPlayer.release()
        }
    }

    // Rotation animation for music disc
    val infiniteTransition = rememberInfiniteTransition(label = "disc_rotate")
    val rotation by infiniteTransition.animateFloat(
        initialValue = 0f,
        targetValue = 360f,
        animationSpec = infiniteRepeatable(
            animation = tween(durationMillis = 4000, easing = LinearEasing),
            repeatMode = RepeatMode.Restart
        ),
        label = "rotation"
    )

    Box(
        modifier = Modifier
            .fillMaxSize()
            .pointerInput(Unit) {
                detectTapGestures(
                    onDoubleTap = {
                        onLikeClick()
                    },
                    onTap = {
                        if (exoPlayer.isPlaying) {
                            exoPlayer.pause()
                            isPlaying = false
                        } else {
                            exoPlayer.play()
                            isPlaying = true
                        }
                    }
                )
            }
    ) {
        // Media3 Video Surface
        AndroidView(
            factory = { ctx ->
                PlayerView(ctx).apply {
                    player = exoPlayer
                    useController = false
                }
            },
            modifier = Modifier.fillMaxSize()
        )

        // Fallback Poster thumbnail
        if (!isPlaying && !reel.thumbnailUrl.isNullOrBlank()) {
            AsyncImage(
                model = reel.thumbnailUrl,
                contentDescription = null,
                contentScale = ContentScale.Crop,
                modifier = Modifier.fillMaxSize()
            )
        }

        // Gradient Vignette Scrim at bottom
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(260.dp)
                .align(Alignment.BottomCenter)
                .background(
                    Brush.verticalGradient(
                        colors = listOf(Color.Transparent, Color.Black.copy(alpha = 0.85f))
                    )
                )
        )

        // Right Sidebar Actions (Matching Screen 3: Like, Comment, Share, Spinning Disc)
        Column(
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .padding(end = 12.dp, bottom = 48.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(18.dp)
        ) {
            // Like
            ActionColumn(
                icon = if (reel.isLiked) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                count = if (reel.likesCount >= 1000) String.format("%.1fK", reel.likesCount / 1000.0) else "${reel.likesCount}",
                tint = if (reel.isLiked) ConnectlyRose else Color.White,
                onClick = onLikeClick
            )

            // Comment
            ActionColumn(
                icon = Icons.AutoMirrored.Filled.Comment,
                count = "${reel.commentsCount}",
                onClick = onCommentClick
            )

            // Share
            val sharesText = if (reel.sharesCount >= 1000) String.format("%.1fK", reel.sharesCount / 1000.0) else "${reel.sharesCount}"
            ActionColumn(
                icon = Icons.AutoMirrored.Filled.Send,
                count = sharesText,
                onClick = onShareClick
            )

            // Rotating Music Disc
            Box(
                modifier = Modifier
                    .size(46.dp)
                    .clip(CircleShape)
                    .border(2.dp, Color.White, CircleShape)
                    .rotate(rotation)
                    .background(Color.DarkGray),
                contentAlignment = Alignment.Center
            ) {
                AsyncImage(
                    model = reel.creator.avatar,
                    contentDescription = "Music Disc",
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.size(28.dp).clip(CircleShape)
                )
            }
        }

        // Bottom Left Overlay: Creator Info, Caption, Audio, Progress Bar
        Column(
            modifier = Modifier
                .align(Alignment.BottomStart)
                .padding(start = 16.dp, end = 72.dp, bottom = 24.dp)
        ) {
            // Creator row with Follow button
            Row(
                verticalAlignment = Alignment.CenterVertically
            ) {
                ConnectlyAvatar(
                    imageUrl = reel.creator.avatar,
                    name = reel.creator.name,
                    size = 38.dp,
                    isVerified = reel.creator.isVerified
                )

                Spacer(modifier = Modifier.width(10.dp))

                Text(
                    text = reel.creator.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = Color.White
                )

                Spacer(modifier = Modifier.width(12.dp))

                Button(
                    onClick = onFollowClick,
                    shape = RoundedCornerShape(16.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (reel.isFollowingCreator) Color.White.copy(alpha = 0.3f) else ConnectlyBlue
                    ),
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp),
                    modifier = Modifier.height(30.dp)
                ) {
                    Text(
                        text = if (reel.isFollowingCreator) "Following" else "Follow",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = Color.White
                    )
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Caption
            Text(
                text = reel.caption,
                color = Color.White,
                fontSize = 14.sp,
                fontWeight = FontWeight.Normal
            )

            Spacer(modifier = Modifier.height(8.dp))

            // Audio Information
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    imageVector = Icons.Default.MusicNote,
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(16.dp)
                )
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    text = reel.music?.title ?: "Original Audio",
                    color = Color.White,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Medium
                )
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Progress bar
            LinearProgressIndicator(
                progress = { 0.45f },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(2.5.dp),
                color = Color.White,
                trackColor = Color.White.copy(alpha = 0.3f)
            )
        }
    }
}

@Composable
private fun ActionColumn(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    count: String,
    tint: Color = Color.White,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier.clickable { onClick() }
    ) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = tint,
            modifier = Modifier.size(30.dp)
        )
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = count,
            color = Color.White,
            fontSize = 12.sp,
            fontWeight = FontWeight.SemiBold
        )
    }
}

@Composable
private fun ReelsCommentsDrawerContent(
    reelId: Long,
    comments: List<Comment>,
    onAddComment: (String) -> Unit
) {
    var newCommentText by remember { mutableStateOf("") }
    var showEmojiPicker by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .height(580.dp)
            .padding(16.dp)
    ) {
        Text("Reel Comments", fontWeight = FontWeight.Bold, fontSize = 18.sp)
        Spacer(modifier = Modifier.height(12.dp))

        androidx.compose.foundation.lazy.LazyColumn(
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            items(comments.size) { index ->
                val comment = comments[index]
                Row(modifier = Modifier.fillMaxWidth()) {
                    Box(
                        modifier = Modifier
                            .size(34.dp)
                            .clip(CircleShape)
                            .background(Color.LightGray)
                    ) {
                        AsyncImage(
                            model = comment.author.avatar,
                            contentDescription = null,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(comment.author.name, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                        Text(comment.body, fontSize = 13.sp)
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(8.dp))

        // Quick Emoji Reaction Bar
        ConnectlyQuickEmojiRow(
            onEmojiSelected = { emoji ->
                newCommentText += emoji
            }
        )
        Spacer(modifier = Modifier.height(4.dp))

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
                placeholder = { Text("Add a comment...") },
                modifier = Modifier.weight(1f),
                shape = RoundedCornerShape(24.dp)
            )
            Spacer(modifier = Modifier.width(6.dp))
            IconButton(
                onClick = {
                    if (newCommentText.isNotBlank()) {
                        onAddComment(newCommentText)
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
                    .height(220.dp)
            )
        }
    }
}
