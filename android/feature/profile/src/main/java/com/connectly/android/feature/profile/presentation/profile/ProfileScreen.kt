package com.connectly.android.feature.profile.presentation.profile

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
import androidx.compose.material.icons.automirrored.filled.Chat
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
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    onNavigateBack: () -> Unit,
    onNavigateToEditProfile: () -> Unit,
    onNavigateToSettings: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: ProfileViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val user = state.user
    val snackbarHostState = remember { SnackbarHostState() }
    var showMoreMenu by remember { mutableStateOf(false) }

    LaunchedEffect(state.userMessage) {
        state.userMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(ProfileIntent.DismissMessage)
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(ProfileIntent.DismissMessage)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = user?.name ?: "Mizanur Rahman",
                            fontWeight = FontWeight.Bold,
                            fontSize = 18.sp
                        )
                        if (user?.isVerified == true) {
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(
                                imageVector = Icons.Default.CheckCircle,
                                contentDescription = "Verified",
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(16.dp)
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
                    IconButton(onClick = onNavigateToSettings) {
                        Icon(Icons.Default.Settings, contentDescription = "Settings")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            item {
                // Cover photo and overlapping Avatar
                Box(modifier = Modifier.fillMaxWidth().height(210.dp)) {
                    AsyncImage(
                        model = user?.coverPhoto ?: "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                        contentDescription = "Cover Photo",
                        contentScale = ContentScale.Crop,
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(160.dp)
                    )

                    // Avatar overlapping cover
                    Box(
                        modifier = Modifier
                            .align(Alignment.BottomCenter)
                            .size(100.dp)
                            .clip(CircleShape)
                            .border(4.dp, Color.White, CircleShape)
                            .background(Color.LightGray)
                    ) {
                        AsyncImage(
                            model = user?.avatar ?: "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                            contentDescription = "Avatar",
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    }
                }
            }

            item {
                // Name, verified badge, and bio
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = user?.name ?: "Mizanur Rahman",
                            fontWeight = FontWeight.Bold,
                            fontSize = 20.sp,
                            color = TextPrimaryLight
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Icon(
                            imageVector = Icons.Default.CheckCircle,
                            contentDescription = "Verified Badge",
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(18.dp)
                        )
                    }

                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = user?.bio ?: "Dream Big • Work Hard • Be Happy",
                        fontSize = 14.sp,
                        color = TextSecondaryLight
                    )

                    Spacer(modifier = Modifier.height(16.dp))

                    // Metrics: Friends | Followers | Following
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceEvenly
                    ) {
                        MetricColumn(count = "${user?.friendsCount ?: 2500}", label = "Friends")
                        MetricColumn(count = "${user?.followersCount ?: 865}", label = "Followers")
                        MetricColumn(count = "${user?.followingCount ?: 128}", label = "Following")
                    }

                    Spacer(modifier = Modifier.height(16.dp))

                    // Action buttons: Edit Profile & More Options
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Button(
                            onClick = onNavigateToEditProfile,
                            shape = RoundedCornerShape(24.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.weight(1f).height(42.dp)
                        ) {
                            Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Edit Profile", fontWeight = FontWeight.SemiBold)
                        }

                        Box {
                            IconButton(
                                onClick = { showMoreMenu = true },
                                modifier = Modifier
                                    .size(42.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFFF1F5F9))
                            ) {
                                Icon(Icons.Default.MoreHoriz, contentDescription = "More")
                            }

                            DropdownMenu(
                                expanded = showMoreMenu,
                                onDismissRequest = { showMoreMenu = false }
                            ) {
                                DropdownMenuItem(
                                    text = { Text("Share Profile") },
                                    leadingIcon = { Icon(Icons.Default.Share, contentDescription = null) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.ShareProfile)
                                    }
                                )
                                DropdownMenuItem(
                                    text = { Text("Copy Profile Link") },
                                    leadingIcon = { Icon(Icons.Default.ContentCopy, contentDescription = null) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.CopyProfileLink)
                                    }
                                )
                                DropdownMenuItem(
                                    text = { Text(if (state.isFollowing) "Unfollow" else "Follow") },
                                    leadingIcon = { Icon(if (state.isFollowing) Icons.Default.PersonRemove else Icons.Default.PersonAdd, contentDescription = null) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.ToggleFollow)
                                    }
                                )
                                DropdownMenuItem(
                                    text = { Text("Add Friend") },
                                    leadingIcon = { Icon(Icons.Default.GroupAdd, contentDescription = null) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.SendFriendRequest)
                                    }
                                )
                                HorizontalDivider()
                                DropdownMenuItem(
                                    text = { Text("Block User", color = MaterialTheme.colorScheme.error) },
                                    leadingIcon = { Icon(Icons.Default.Block, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.BlockUser)
                                    }
                                )
                                DropdownMenuItem(
                                    text = { Text("Report Profile", color = MaterialTheme.colorScheme.error) },
                                    leadingIcon = { Icon(Icons.Default.Flag, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
                                    onClick = {
                                        showMoreMenu = false
                                        viewModel.handleIntent(ProfileIntent.ReportProfile)
                                    }
                                )
                            }
                        }
                    }
                }
            }

            item {
                // Segmented Tabs: Posts, Photos, Videos, Reels
                val tabs = listOf("Posts", "Photos", "Videos", "Reels")
                TabRow(
                    selectedTabIndex = state.selectedTab,
                    containerColor = MaterialTheme.colorScheme.surface,
                    contentColor = ConnectlyBlue,
                    modifier = Modifier.padding(top = 8.dp)
                ) {
                    tabs.forEachIndexed { index, title ->
                        Tab(
                            selected = state.selectedTab == index,
                            onClick = { viewModel.handleIntent(ProfileIntent.SelectTab(index)) },
                            text = {
                                Text(
                                    title,
                                    fontWeight = if (state.selectedTab == index) FontWeight.Bold else FontWeight.Normal
                                )
                            }
                        )
                    }
                }
            }

            // ========================================================
            // TAB CONTENT: POSTS, PHOTOS, VIDEOS, REELS
            // ========================================================
            when (state.selectedTab) {
                0 -> {
                    // TAB 0: POSTS
                    item {
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                        ) {
                            Column(modifier = Modifier.padding(12.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(
                                        modifier = Modifier
                                            .size(40.dp)
                                            .clip(CircleShape)
                                            .background(Color.LightGray)
                                    ) {
                                        AsyncImage(
                                            model = user?.avatar ?: "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                                            contentDescription = null,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                    Spacer(modifier = Modifier.width(10.dp))
                                    Column {
                                        Text(user?.name ?: "Mizanur Rahman", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                        Text("1h • Public", fontSize = 12.sp, color = TextSecondaryLight)
                                    }
                                }

                                Spacer(modifier = Modifier.height(10.dp))
                                Text("Life is a beautiful journey! ❤️", fontSize = 14.sp)
                                Spacer(modifier = Modifier.height(10.dp))

                                AsyncImage(
                                    model = "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                                    contentDescription = "Post Image",
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(180.dp)
                                        .clip(RoundedCornerShape(8.dp))
                                )
                            }
                        }
                    }
                }
                1 -> {
                    // TAB 1: PHOTOS (Grid gallery)
                    item {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            Text(
                                "Photos & Albums",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            val photos = state.photos
                            for (i in photos.indices step 3) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(bottom = 6.dp),
                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                ) {
                                    for (j in 0 until 3) {
                                        val idx = i + j
                                        if (idx < photos.size) {
                                            AsyncImage(
                                                model = photos[idx],
                                                contentDescription = "Photo $idx",
                                                contentScale = ContentScale.Crop,
                                                modifier = Modifier
                                                    .weight(1f)
                                                    .aspectRatio(1f)
                                                    .clip(RoundedCornerShape(6.dp))
                                            )
                                        } else {
                                            Spacer(modifier = Modifier.weight(1f))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                2 -> {
                    // TAB 2: VIDEOS
                    item {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            Text(
                                "Uploaded Videos",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            val videos = state.videos
                            for (i in videos.indices step 2) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(bottom = 8.dp),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    for (j in 0 until 2) {
                                        val idx = i + j
                                        if (idx < videos.size) {
                                            Box(
                                                modifier = Modifier
                                                    .weight(1f)
                                                    .height(120.dp)
                                                    .clip(RoundedCornerShape(8.dp))
                                                    .background(Color.Black),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                AsyncImage(
                                                    model = videos[idx],
                                                    contentDescription = "Video $idx",
                                                    contentScale = ContentScale.Crop,
                                                    modifier = Modifier.fillMaxSize()
                                                )
                                                Box(
                                                    modifier = Modifier
                                                        .size(36.dp)
                                                        .clip(CircleShape)
                                                        .background(Color.Black.copy(alpha = 0.6f)),
                                                    contentAlignment = Alignment.Center
                                                ) {
                                                    Icon(
                                                        Icons.Default.PlayArrow,
                                                        contentDescription = "Play",
                                                        tint = Color.White,
                                                        modifier = Modifier.size(24.dp)
                                                    )
                                                }
                                            }
                                        } else {
                                            Spacer(modifier = Modifier.weight(1f))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                3 -> {
                    // TAB 3: REELS
                    item {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            Text(
                                "Reels & Shorts",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            val reels = state.reels
                            for (i in reels.indices step 3) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(bottom = 6.dp),
                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                ) {
                                    for (j in 0 until 3) {
                                        val idx = i + j
                                        if (idx < reels.size) {
                                            Box(
                                                modifier = Modifier
                                                    .weight(1f)
                                                    .aspectRatio(9f / 16f)
                                                    .clip(RoundedCornerShape(8.dp))
                                                    .background(Color.DarkGray)
                                            ) {
                                                AsyncImage(
                                                    model = reels[idx],
                                                    contentDescription = "Reel $idx",
                                                    contentScale = ContentScale.Crop,
                                                    modifier = Modifier.fillMaxSize()
                                                )
                                                Row(
                                                    modifier = Modifier
                                                        .align(Alignment.BottomStart)
                                                        .padding(6.dp),
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Icon(
                                                        Icons.Default.PlayArrow,
                                                        contentDescription = null,
                                                        tint = Color.White,
                                                        modifier = Modifier.size(14.dp)
                                                    )
                                                    Spacer(modifier = Modifier.width(2.dp))
                                                    Text(
                                                        text = "1.2K",
                                                        color = Color.White,
                                                        fontSize = 11.sp,
                                                        fontWeight = FontWeight.Bold
                                                    )
                                                }
                                            }
                                        } else {
                                            Spacer(modifier = Modifier.weight(1f))
                                        }
                                    }
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
private fun MetricColumn(count: String, label: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(text = count, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)
        Text(text = label, fontSize = 12.sp, color = TextSecondaryLight)
    }
}

