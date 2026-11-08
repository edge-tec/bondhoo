package com.connectly.android.feature.profile.presentation.profile

import android.content.Intent
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.foundation.lazy.items
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.resolveMediaUrl
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.FriendshipStatus

enum class ShareMode { MAIN, GROUP_SELECT, PAGE_SELECT }

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    onNavigateBack: () -> Unit,
    onNavigateToEditProfile: () -> Unit,
    onNavigateToSettings: () -> Unit,
    modifier: Modifier = Modifier,
    onNavigateToFriends: () -> Unit = {},
    onNavigateToChat: (Long) -> Unit = {},
    onNavigateToCreatePost: (String?) -> Unit = {},
    onNavigateToCreateStory: () -> Unit = {},
    onNavigateToLive: () -> Unit = {},
    onNavigateToUserProfile: (String) -> Unit = {},
    viewModel: ProfileViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val user = state.user
    val snackbarHostState = remember { SnackbarHostState() }
    val clipboardManager = LocalClipboardManager.current
    val context = LocalContext.current

    var showMoreBottomSheet by remember { mutableStateOf(false) }
    var reportReason by remember { mutableStateOf("Fake Account / Impersonation") }

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
                    Column {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                text = user?.name ?: "Mizanur Rahman",
                                fontWeight = FontWeight.Bold,
                                fontSize = 18.sp,
                                maxLines = 1
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
                        Text(
                            text = "@${user?.username ?: "mizanur_rahman"}",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.handleIntent(ProfileIntent.ShowManagePostsSheet) }) {
                        Icon(Icons.Default.Search, contentDescription = "Search Profile")
                    }
                    IconButton(onClick = { showMoreBottomSheet = true }) {
                        Icon(Icons.Default.MoreHoriz, contentDescription = "More Options")
                    }
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
            // ========================================================
            // 1. COVER PHOTO & OVERLAPPING AVATAR
            // ========================================================
            item {
                Box(modifier = Modifier.fillMaxWidth().height(220.dp)) {
                    AsyncImage(
                        model = resolveMediaUrl(user?.coverPhoto),
                        contentDescription = "Cover Photo",
                        contentScale = ContentScale.Crop,
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(165.dp)
                            .clickable(onClick = onNavigateToEditProfile)
                    )

                    // Edit Cover Photo camera icon badge
                    if (state.isOwner) {
                        Box(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .padding(top = 115.dp, end = 16.dp)
                                .size(34.dp)
                                .clip(CircleShape)
                                .background(Color.Black.copy(alpha = 0.6f))
                                .clickable(onClick = onNavigateToEditProfile),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Default.PhotoCamera,
                                contentDescription = "Edit Cover",
                                tint = Color.White,
                                modifier = Modifier.size(18.dp)
                            )
                        }
                    }

                    // Avatar overlapping cover
                    Box(
                        modifier = Modifier
                            .align(Alignment.BottomCenter)
                            .size(110.dp)
                            .clip(CircleShape)
                            .border(4.dp, MaterialTheme.colorScheme.surface, CircleShape)
                            .background(Color.LightGray)
                            .clickable(onClick = onNavigateToEditProfile)
                    ) {
                        AsyncImage(
                            model = resolveMediaUrl(user?.avatar),
                            contentDescription = "Avatar",
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )

                        // Camera icon badge on avatar for own profile
                        if (state.isOwner) {
                            Box(
                                modifier = Modifier
                                    .align(Alignment.BottomEnd)
                                    .padding(2.dp)
                                    .size(28.dp)
                                    .clip(CircleShape)
                                    .background(MaterialTheme.colorScheme.surfaceVariant)
                                    .border(2.dp, MaterialTheme.colorScheme.surface, CircleShape),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    Icons.Default.PhotoCamera,
                                    contentDescription = "Edit Avatar",
                                    tint = TextPrimaryLight,
                                    modifier = Modifier.size(16.dp)
                                )
                            }
                        }
                    }
                }
            }

            // ========================================================
            // 2. NAME, HANDLE, BIO
            // ========================================================
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 6.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.Center
                    ) {
                        Text(
                            text = user?.name ?: "Mizanur Rahman",
                            fontWeight = FontWeight.Bold,
                            fontSize = 22.sp,
                            color = TextPrimaryLight
                        )
                        if (user?.isVerified == true) {
                            Spacer(modifier = Modifier.width(6.dp))
                            Icon(
                                imageVector = Icons.Default.CheckCircle,
                                contentDescription = "Verified Badge",
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                    }

                    Text(
                        text = "@${user?.username ?: "mizanur_rahman"}",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Medium,
                        color = ConnectlyBlue,
                        modifier = Modifier.padding(top = 2.dp)
                    )

                    Spacer(modifier = Modifier.height(6.dp))

                    Text(
                        text = user?.bio ?: "Dream Big • Work Hard • Be Happy",
                        fontSize = 14.sp,
                        color = TextSecondaryLight,
                        lineHeight = 20.sp,
                        modifier = Modifier.padding(horizontal = 16.dp)
                    )

                    Spacer(modifier = Modifier.height(14.dp))

                    // ========================================================
                    // 3. ACTION BAR (OWN PROFILE VS OTHER PROFILE)
                    // ========================================================
                    if (state.isOwner) {
                        // FACEBOOK-GRADE OWN PROFILE ACTIONS
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            // + Add to Story (Primary Button)
                            Button(
                                onClick = onNavigateToCreateStory,
                                shape = RoundedCornerShape(8.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                modifier = Modifier.weight(1.3f).height(40.dp),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Add to story", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            }

                            // Edit Profile
                            FilledTonalButton(
                                onClick = onNavigateToEditProfile,
                                shape = RoundedCornerShape(8.dp),
                                modifier = Modifier.weight(1.2f).height(40.dp),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Edit profile", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            }

                            // Dashboard
                            FilledTonalButton(
                                onClick = { viewModel.handleIntent(ProfileIntent.ShowDashboardSheet) },
                                shape = RoundedCornerShape(8.dp),
                                modifier = Modifier.weight(1.2f).height(40.dp),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Icon(Icons.Default.SpaceDashboard, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Dashboard", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                            }

                            // 3-dot More Options
                            IconButton(
                                onClick = { showMoreBottomSheet = true },
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant)
                            ) {
                                Icon(
                                    Icons.Default.MoreHoriz,
                                    contentDescription = "More Options",
                                    tint = TextPrimaryLight,
                                    modifier = Modifier.size(20.dp)
                                )
                            }
                        }
                    } else {
                        // OTHER USER'S PROFILE ACTIONS (Add Friend, Follow, Message)
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            when (state.friendshipStatus) {
                                FriendshipStatus.NONE -> {
                                    Button(
                                        onClick = { viewModel.handleIntent(ProfileIntent.SendFriendRequest) },
                                        shape = RoundedCornerShape(8.dp),
                                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                        modifier = Modifier.weight(1f).height(40.dp),
                                        contentPadding = PaddingValues(horizontal = 8.dp)
                                    ) {
                                        Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Add Friend", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                    }
                                }
                                FriendshipStatus.PENDING_SENT -> {
                                    OutlinedButton(
                                        onClick = { viewModel.handleIntent(ProfileIntent.CancelFriendRequest) },
                                        shape = RoundedCornerShape(8.dp),
                                        modifier = Modifier.weight(1f).height(40.dp),
                                        contentPadding = PaddingValues(horizontal = 8.dp)
                                    ) {
                                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Cancel Request", fontSize = 12.sp, fontWeight = FontWeight.Medium)
                                    }
                                }
                                FriendshipStatus.PENDING_RECEIVED -> {
                                    Button(
                                        onClick = { viewModel.handleIntent(ProfileIntent.AcceptFriendRequest) },
                                        shape = RoundedCornerShape(8.dp),
                                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                                        modifier = Modifier.weight(1f).height(40.dp),
                                        contentPadding = PaddingValues(horizontal = 8.dp)
                                    ) {
                                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Accept", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                    }
                                }
                                FriendshipStatus.FRIENDS -> {
                                    FilledTonalButton(
                                        onClick = { viewModel.handleIntent(ProfileIntent.ShowUnfriendDialog) },
                                        shape = RoundedCornerShape(8.dp),
                                        modifier = Modifier.weight(1f).height(40.dp),
                                        contentPadding = PaddingValues(horizontal = 8.dp)
                                    ) {
                                        Icon(Icons.Default.Group, contentDescription = null, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Friends", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                    }
                                }
                                FriendshipStatus.BLOCKED -> {
                                    OutlinedButton(
                                        onClick = {},
                                        enabled = false,
                                        shape = RoundedCornerShape(8.dp),
                                        modifier = Modifier.weight(1f).height(40.dp)
                                    ) {
                                        Text("Blocked", fontSize = 13.sp)
                                    }
                                }
                            }

                            if (state.isFollowing) {
                                FilledTonalButton(
                                    onClick = { viewModel.handleIntent(ProfileIntent.ToggleFollow) },
                                    shape = RoundedCornerShape(8.dp),
                                    modifier = Modifier.weight(1f).height(40.dp),
                                    contentPadding = PaddingValues(horizontal = 8.dp)
                                ) {
                                    Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Following", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                }
                            } else {
                                OutlinedButton(
                                    onClick = { viewModel.handleIntent(ProfileIntent.ToggleFollow) },
                                    shape = RoundedCornerShape(8.dp),
                                    modifier = Modifier.weight(1f).height(40.dp),
                                    contentPadding = PaddingValues(horizontal = 8.dp)
                                ) {
                                    Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Follow", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                }
                            }

                            IconButton(
                                onClick = { user?.let { onNavigateToChat(it.id) } },
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant)
                            ) {
                                Icon(
                                    Icons.AutoMirrored.Filled.Chat,
                                    contentDescription = "Message",
                                    tint = ConnectlyBlue,
                                    modifier = Modifier.size(20.dp)
                                )
                            }

                            IconButton(
                                onClick = { viewModel.handleIntent(ProfileIntent.ShowDashboardSheet) },
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant)
                            ) {
                                Icon(
                                    Icons.Default.SpaceDashboard,
                                    contentDescription = "Dashboard",
                                    tint = ConnectlyBlue,
                                    modifier = Modifier.size(20.dp)
                                )
                            }

                            IconButton(
                                onClick = { showMoreBottomSheet = true },
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant)
                            ) {
                                Icon(
                                    Icons.Default.MoreHoriz,
                                    contentDescription = "More Options",
                                    tint = TextPrimaryLight,
                                    modifier = Modifier.size(20.dp)
                                )
                            }
                        }
                    }
                }
            }

            // ========================================================
            // 4. DETAILS SECTION & EDIT PUBLIC DETAILS BUTTON
            // ========================================================
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Text(
                        text = "Details",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimaryLight,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )

                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(12.dp))
                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
                            .padding(14.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Work, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(text = user?.occupation ?: "Software Engineer at Bondhoo", fontSize = 14.sp)
                        }
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.School, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(text = "Studied Computer Science & Engineering", fontSize = 14.sp)
                        }
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.LocationOn, contentDescription = null, tint = Color(0xFFE53935), modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(text = "Lives in ${user?.location ?: "Dhaka, Bangladesh"}", fontSize = 14.sp)
                        }
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.DateRange, contentDescription = null, tint = TextSecondaryLight, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(text = "Joined Bondhoo (বন্ধু) in October 2026", fontSize = 14.sp, color = TextSecondaryLight)
                        }

                        // Edit Public Details Button (Facebook-style)
                        if (state.isOwner) {
                            Spacer(modifier = Modifier.height(4.dp))
                            FilledTonalButton(
                                onClick = onNavigateToEditProfile,
                                shape = RoundedCornerShape(8.dp),
                                modifier = Modifier.fillMaxWidth().height(38.dp)
                            ) {
                                Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Edit public details", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                            }
                        }
                    }
                }
            }

            // ========================================================
            // 5. FRIENDS SECTION (FACEBOOK-STYLE WITH SEE ALL FRIENDS)
            // ========================================================
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 6.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text(
                                text = "Friends",
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = TextPrimaryLight
                            )
                            Text(
                                text = "${if (state.previewFriends.isNotEmpty()) state.previewFriends.size else (user?.friendsCount ?: 0)} friends",
                                fontSize = 13.sp,
                                color = TextSecondaryLight
                            )
                        }
                        TextButton(onClick = onNavigateToFriends) {
                            Text("Find friends", color = ConnectlyBlue, fontWeight = FontWeight.SemiBold)
                        }
                    }

                    // Real-time Friends preview row
                    if (state.previewFriends.isNotEmpty()) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(top = 8.dp),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            val previewToShow = state.previewFriends.take(3)
                            previewToShow.forEach { friend ->
                                Column(
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    modifier = Modifier
                                        .weight(1f)
                                        .clip(RoundedCornerShape(8.dp))
                                        .clickable {
                                            onNavigateToUserProfile(friend.username.ifBlank { friend.id.toString() })
                                        }
                                        .padding(vertical = 4.dp)
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(68.dp)
                                            .clip(RoundedCornerShape(8.dp))
                                            .background(Color.LightGray)
                                    ) {
                                        AsyncImage(
                                            model = resolveMediaUrl(friend.avatar),
                                            contentDescription = friend.name,
                                            contentScale = ContentScale.Crop,
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = friend.name.split(" ").firstOrNull() ?: friend.name,
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Medium,
                                        maxLines = 1,
                                        overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                                    )
                                }
                            }
                            repeat(3 - previewToShow.size) {
                                Spacer(modifier = Modifier.weight(1f))
                            }
                        }
                    }

                    // See all friends button
                    OutlinedButton(
                        onClick = onNavigateToFriends,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp)
                            .height(38.dp)
                    ) {
                        Text("See all friends", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                    }
                }
            }

            // ========================================================
            // 6. FACEBOOK-STYLE POST COMPOSER BOX (REELS, PHOTOS, LIVE, STORY, MANAGE POSTS)
            // ========================================================
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "Posts",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextPrimaryLight
                        )
                        TextButton(
                            onClick = { viewModel.handleIntent(ProfileIntent.ShowManagePostsSheet) }
                        ) {
                            Icon(Icons.Default.Tune, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Manage posts", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }

                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            // "What's on your mind?" Input row
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { onNavigateToCreatePost(null) }
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(40.dp)
                                        .clip(CircleShape)
                                        .background(Color.LightGray)
                                ) {
                                    AsyncImage(
                                        model = resolveMediaUrl(user?.avatar),
                                        contentDescription = null,
                                        contentScale = ContentScale.Crop,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Text(
                                    text = "What's on your mind?",
                                    color = TextSecondaryLight,
                                    fontSize = 14.sp
                                )
                            }

                            Spacer(modifier = Modifier.height(10.dp))
                            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                            Spacer(modifier = Modifier.height(6.dp))

                            // Quick action bar: Reel, Photo, Live, Story
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(6.dp)
                            ) {
                                // Reel
                                Surface(
                                    onClick = onNavigateToCreateStory,
                                    shape = RoundedCornerShape(20.dp),
                                    color = Color(0xFFFDF2F8),
                                    border = BorderStroke(1.dp, Color(0xFFFCE7F3)),
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.Center,
                                        modifier = Modifier.padding(vertical = 7.dp)
                                    ) {
                                        Icon(
                                            Icons.Default.VideoCameraBack,
                                            contentDescription = "Reel",
                                            tint = Color(0xFFE91E63),
                                            modifier = Modifier.size(17.dp)
                                        )
                                        Spacer(modifier = Modifier.width(3.dp))
                                        Text("Reel", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFFBE185D))
                                    }
                                }

                                // Photo
                                Surface(
                                    onClick = { onNavigateToCreatePost("photo") },
                                    shape = RoundedCornerShape(20.dp),
                                    color = Color(0xFFF0FDF4),
                                    border = BorderStroke(1.dp, Color(0xFFDCFCE7)),
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.Center,
                                        modifier = Modifier.padding(vertical = 7.dp)
                                    ) {
                                        Icon(
                                            Icons.Default.PhotoLibrary,
                                            contentDescription = "Photo",
                                            tint = Color(0xFF10B981),
                                            modifier = Modifier.size(17.dp)
                                        )
                                        Spacer(modifier = Modifier.width(3.dp))
                                        Text("Photo", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF059669))
                                    }
                                }

                                // Live
                                Surface(
                                    onClick = onNavigateToLive,
                                    shape = RoundedCornerShape(20.dp),
                                    color = Color(0xFFFEF2F2),
                                    border = BorderStroke(1.dp, Color(0xFFFEE2E2)),
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.Center,
                                        modifier = Modifier.padding(vertical = 7.dp)
                                    ) {
                                        Icon(
                                            Icons.Default.Videocam,
                                            contentDescription = "Live",
                                            tint = Color(0xFFEF4444),
                                            modifier = Modifier.size(17.dp)
                                        )
                                        Spacer(modifier = Modifier.width(3.dp))
                                        Text("Live", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFFDC2626))
                                    }
                                }

                                // Story
                                Surface(
                                    onClick = onNavigateToCreateStory,
                                    shape = RoundedCornerShape(20.dp),
                                    color = Color(0xFFEFF6FF),
                                    border = BorderStroke(1.dp, Color(0xFFDBEAFE)),
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.Center,
                                        modifier = Modifier.padding(vertical = 7.dp)
                                    ) {
                                        Icon(
                                            Icons.Default.AddCircleOutline,
                                            contentDescription = "Story",
                                            tint = ConnectlyBlue,
                                            modifier = Modifier.size(17.dp)
                                        )
                                        Spacer(modifier = Modifier.width(3.dp))
                                        Text("Story", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = ConnectlyBlue)
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ========================================================
            // 7. SEGMENTED TABS: POSTS, PHOTOS, VIDEOS, REELS
            // ========================================================
            item {
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
                    if (state.posts.isEmpty()) {
                        item {
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(24.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally
                                ) {
                                    Icon(
                                        Icons.Default.PostAdd,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(48.dp)
                                    )
                                    Spacer(modifier = Modifier.height(12.dp))
                                    Text(
                                        text = if (state.postFilter != "all") "No posts found for '${state.postFilter}'" else "No posts yet",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = "Share what's on your mind, upload photos, or publish a reel to share with friends and followers.",
                                        fontSize = 13.sp,
                                        color = TextSecondaryLight,
                                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                    )
                                    if (state.isOwner) {
                                        Spacer(modifier = Modifier.height(14.dp))
                                        Button(
                                            onClick = { onNavigateToCreatePost(null) },
                                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                                            shape = RoundedCornerShape(8.dp)
                                        ) {
                                            Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                            Spacer(modifier = Modifier.width(6.dp))
                                            Text("Create Post")
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        items(state.posts.size) { index ->
                            val post = state.posts[index]
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 16.dp, vertical = 6.dp),
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(modifier = Modifier.padding(14.dp)) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Box(
                                            modifier = Modifier
                                                .size(42.dp)
                                                .clip(CircleShape)
                                                .background(Color.LightGray)
                                        ) {
                                            AsyncImage(
                                                model = resolveMediaUrl(post.author.avatar ?: user?.avatar),
                                                contentDescription = null,
                                                contentScale = ContentScale.Crop,
                                                modifier = Modifier.fillMaxSize()
                                            )
                                        }
                                        Spacer(modifier = Modifier.width(10.dp))
                                        Column {
                                            Row(verticalAlignment = Alignment.CenterVertically) {
                                                Text(post.author.name.ifBlank { user?.name ?: "User" }, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                                if (user?.isVerified == true) {
                                                    Spacer(modifier = Modifier.width(4.dp))
                                                    Icon(
                                                        Icons.Default.CheckCircle,
                                                        contentDescription = null,
                                                        tint = ConnectlyBlue,
                                                        modifier = Modifier.size(14.dp)
                                                    )
                                                }
                                            }
                                            Text("@${post.author.username.ifBlank { user?.username ?: "user" }} • ${post.privacy.replaceFirstChar { it.uppercase() }}", fontSize = 12.sp, color = TextSecondaryLight)
                                        }
                                    }

                                    if (post.content.isNotBlank()) {
                                        Spacer(modifier = Modifier.height(10.dp))
                                        Text(post.content, fontSize = 14.sp)
                                    }

                                    val mediaUrl = post.mediaUrls.firstOrNull()?.trim()
                                    val resolvedUrl = resolveMediaUrl(mediaUrl)
                                    if (!resolvedUrl.isNullOrEmpty()) {
                                        Spacer(modifier = Modifier.height(10.dp))
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .heightIn(min = 160.dp, max = 340.dp)
                                                .clip(RoundedCornerShape(8.dp))
                                                .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
                                        ) {
                                            AsyncImage(
                                                model = resolvedUrl,
                                                contentDescription = "Post Image",
                                                contentScale = ContentScale.Crop,
                                                alignment = Alignment.Center,
                                                modifier = Modifier.fillMaxWidth()
                                            )
                                        }
                                    }

                                    Spacer(modifier = Modifier.height(12.dp))
                                    HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                    Spacer(modifier = Modifier.height(8.dp))

                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceAround
                                    ) {
                                        val isLiked = post.userReaction != null
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            modifier = Modifier
                                                .clip(RoundedCornerShape(8.dp))
                                                .clickable {
                                                    viewModel.handleIntent(ProfileIntent.ToggleLikePost(post.id))
                                                }
                                                .padding(horizontal = 10.dp, vertical = 6.dp)
                                        ) {
                                            Icon(
                                                Icons.Default.ThumbUp,
                                                contentDescription = "Like",
                                                tint = if (isLiked) ConnectlyBlue else TextSecondaryLight,
                                                modifier = Modifier.size(18.dp)
                                            )
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(
                                                "${post.reactionsCount} Likes",
                                                fontSize = 12.sp,
                                                color = if (isLiked) ConnectlyBlue else TextSecondaryLight,
                                                fontWeight = if (isLiked) FontWeight.Bold else FontWeight.Normal
                                            )
                                        }

                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            modifier = Modifier
                                                .clip(RoundedCornerShape(8.dp))
                                                .clickable {
                                                    viewModel.handleIntent(ProfileIntent.OpenComments(post.id))
                                                }
                                                .padding(horizontal = 10.dp, vertical = 6.dp)
                                        ) {
                                            Icon(
                                                Icons.Default.ChatBubbleOutline,
                                                contentDescription = "Comment",
                                                tint = TextSecondaryLight,
                                                modifier = Modifier.size(18.dp)
                                            )
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(
                                                "${post.commentsCount} Comments",
                                                fontSize = 12.sp,
                                                color = TextSecondaryLight
                                            )
                                        }

                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            modifier = Modifier
                                                .clip(RoundedCornerShape(8.dp))
                                                .clickable {
                                                    viewModel.handleIntent(ProfileIntent.OpenShareSheet(post.id))
                                                }
                                                .padding(horizontal = 10.dp, vertical = 6.dp)
                                        ) {
                                            Icon(
                                                Icons.Default.Share,
                                                contentDescription = "Share",
                                                tint = TextSecondaryLight,
                                                modifier = Modifier.size(18.dp)
                                            )
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(
                                                "${post.sharesCount} Shares",
                                                fontSize = 12.sp,
                                                color = TextSecondaryLight
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                1 -> {
                    // TAB 1: PHOTOS
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
                            if (photos.isEmpty()) {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(32.dp),
                                        horizontalAlignment = Alignment.CenterHorizontally
                                    ) {
                                        Icon(
                                            Icons.Default.PhotoLibrary,
                                            contentDescription = null,
                                            tint = ConnectlyBlue,
                                            modifier = Modifier.size(40.dp)
                                        )
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text(
                                            "No photos yet",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            "Photos attached to your posts will appear here.",
                                            fontSize = 13.sp,
                                            color = TextSecondaryLight,
                                            textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                        )
                                    }
                                }
                            } else {
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
                                                        .clip(RoundedCornerShape(8.dp))
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
                            if (videos.isEmpty()) {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(32.dp),
                                        horizontalAlignment = Alignment.CenterHorizontally
                                    ) {
                                        Icon(
                                            Icons.Default.VideoLibrary,
                                            contentDescription = null,
                                            tint = ConnectlyBlue,
                                            modifier = Modifier.size(40.dp)
                                        )
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text(
                                            "No videos yet",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            "Videos uploaded to your profile will appear here.",
                                            fontSize = 13.sp,
                                            color = TextSecondaryLight,
                                            textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                        )
                                    }
                                }
                            } else {
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
                                                        .height(130.dp)
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
                                                            .size(40.dp)
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
                            if (reels.isEmpty()) {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(32.dp),
                                        horizontalAlignment = Alignment.CenterHorizontally
                                    ) {
                                        Icon(
                                            Icons.Default.Movie,
                                            contentDescription = null,
                                            tint = ConnectlyBlue,
                                            modifier = Modifier.size(40.dp)
                                        )
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text(
                                            "No reels yet",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp
                                        )
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            "Short video reels created will appear here.",
                                            fontSize = 13.sp,
                                            color = TextSecondaryLight,
                                            textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                        )
                                    }
                                }
                            } else {
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
                                                        .background(Color.DarkGray),
                                                    contentAlignment = Alignment.Center
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

    // ========================================================
    // PROFESSIONAL DASHBOARD BOTTOM SHEET (FACEBOOK-STYLE)
    // ========================================================
    if (state.showDashboardSheet) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissDashboardSheet) },
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 8.dp)
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        Icons.Default.SpaceDashboard,
                        contentDescription = null,
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(24.dp)
                    )
                    Spacer(modifier = Modifier.width(10.dp))
                    Text(
                        text = "Professional Dashboard",
                        fontWeight = FontWeight.Bold,
                        fontSize = 20.sp
                    )
                }

                Text(
                    text = "Performance & Insights (Last 28 days)",
                    fontSize = 13.sp,
                    color = TextSecondaryLight,
                    modifier = Modifier.padding(top = 4.dp, bottom = 16.dp)
                )

                // Metrics Grid
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Card(
                        modifier = Modifier.weight(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text("Post Reach", fontSize = 12.sp, color = TextSecondaryLight)
                            Text("${state.analyticsTotalViews}", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                            Text("↑ 12.4%", fontSize = 11.sp, color = Color(0xFF10B981), fontWeight = FontWeight.SemiBold)
                        }
                    }
                    Card(
                        modifier = Modifier.weight(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text("This Week", fontSize = 12.sp, color = TextSecondaryLight)
                            Text("${state.analyticsViewsWeek}", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                            Text("↑ 8.1%", fontSize = 11.sp, color = Color(0xFF10B981), fontWeight = FontWeight.SemiBold)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Card(
                        modifier = Modifier.weight(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text("Views Today", fontSize = 12.sp, color = TextSecondaryLight)
                            Text("${state.analyticsViewsToday}", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                            Text("Active audience", fontSize = 11.sp, color = TextSecondaryLight)
                        }
                    }
                    Card(
                        modifier = Modifier.weight(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text("Followers", fontSize = 12.sp, color = TextSecondaryLight)
                            Text("${user?.followersCount ?: 865}", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                            Text("Growing community", fontSize = 11.sp, color = TextSecondaryLight)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))
                Text("Your Tools", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                Spacer(modifier = Modifier.height(8.dp))

                OptionRow(
                    icon = Icons.Default.MonetizationOn,
                    title = "Marketplace & Monetization",
                    subtitle = "Manage listings and seller balance",
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.DismissDashboardSheet)
                        onNavigateToSettings()
                    }
                )

                OptionRow(
                    icon = Icons.Default.Analytics,
                    title = "Detailed Analytics",
                    subtitle = "View full post reach breakdown",
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.DismissDashboardSheet)
                    }
                )

                Spacer(modifier = Modifier.height(24.dp))
            }
        }
    }

    // ========================================================
    // MANAGE POSTS BOTTOM SHEET
    // ========================================================
    if (state.showManagePostsSheet) {
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissManagePostsSheet) },
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 8.dp)
            ) {
                Text(
                    text = "Manage Posts",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                OptionRow(
                    icon = Icons.Default.FilterList,
                    title = "Filter by Privacy",
                    subtitle = if (state.postFilter == "public") "Currently showing: Public only (tap to reset)" else "Filter to Public posts",
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.FilterPosts(if (state.postFilter == "public") "all" else "public"))
                        viewModel.handleIntent(ProfileIntent.DismissManagePostsSheet)
                    }
                )

                OptionRow(
                    icon = Icons.Default.CalendarMonth,
                    title = "Filter by Date",
                    subtitle = "All recent published posts",
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.FilterPosts("all"))
                        viewModel.handleIntent(ProfileIntent.DismissManagePostsSheet)
                    }
                )

                OptionRow(
                    icon = Icons.Default.Archive,
                    title = "Archived Posts",
                    subtitle = "View your hidden posts and stories",
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.FilterPosts("archived"))
                        viewModel.handleIntent(ProfileIntent.DismissManagePostsSheet)
                    }
                )

                OptionRow(
                    icon = Icons.Default.Delete,
                    title = "Trash / Recycle Bin",
                    subtitle = "Restore or permanently delete items",
                    tint = MaterialTheme.colorScheme.error,
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.FilterPosts("trash"))
                        viewModel.handleIntent(ProfileIntent.DismissManagePostsSheet)
                    }
                )

                Spacer(modifier = Modifier.height(24.dp))
            }
        }
    }

    // ========================================================
    // 3-DOT MORE OPTIONS BOTTOM SHEET (FACEBOOK-STYLE)
    // ========================================================
    if (showMoreBottomSheet) {
        ModalBottomSheet(
            onDismissRequest = { showMoreBottomSheet = false },
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 8.dp)
            ) {
                Text(
                    text = if (state.isOwner) "Profile Settings" else "Profile Actions",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                // 1. Copy Link
                OptionRow(
                    icon = Icons.Default.ContentCopy,
                    title = "Copy Link to Profile",
                    subtitle = "https://bondhoo.com/@${user?.username ?: "user"}",
                    onClick = {
                        showMoreBottomSheet = false
                        clipboardManager.setText(AnnotatedString("https://bondhoo.com/@${user?.username ?: "user"}"))
                        viewModel.handleIntent(ProfileIntent.CopyProfileLink)
                    }
                )

                // 2. Share Profile
                OptionRow(
                    icon = Icons.Default.Share,
                    title = "Share this Profile",
                    subtitle = "Send profile via other apps",
                    onClick = {
                        showMoreBottomSheet = false
                        val sendIntent = Intent().apply {
                            action = Intent.ACTION_SEND
                            putExtra(
                                Intent.EXTRA_TEXT,
                                "Check out ${user?.name ?: "User"}'s profile on Bondhoo: https://bondhoo.com/@${user?.username ?: "user"}"
                            )
                            type = "text/plain"
                        }
                        context.startActivity(Intent.createChooser(sendIntent, "Share profile via"))
                        viewModel.handleIntent(ProfileIntent.ShareProfile)
                    }
                )

                if (state.isOwner) {
                    // Own profile options
                    OptionRow(
                        icon = Icons.Default.VerifiedUser,
                        title = "Profile Status",
                        subtitle = "Account in good standing • Verified",
                        onClick = { showMoreBottomSheet = false }
                    )

                    OptionRow(
                        icon = Icons.Default.History,
                        title = "Activity Log",
                        subtitle = "View and manage your interactions",
                        onClick = {
                            showMoreBottomSheet = false
                            onNavigateToSettings()
                        }
                    )

                    OptionRow(
                        icon = Icons.Default.Lock,
                        title = "Privacy Shortcuts & Settings",
                        subtitle = "Control who sees your posts and info",
                        onClick = {
                            showMoreBottomSheet = false
                            onNavigateToSettings()
                        }
                    )
                } else {
                    // Other user profile options
                    OptionRow(
                        icon = Icons.Default.SpaceDashboard,
                        title = "Profile Dashboard",
                        subtitle = "View ${user?.name ?: "user"}'s reach & insights",
                        onClick = {
                            showMoreBottomSheet = false
                            viewModel.handleIntent(ProfileIntent.ShowDashboardSheet)
                        }
                    )

                    OptionRow(
                        icon = Icons.AutoMirrored.Filled.Chat,
                        title = "Send Message",
                        subtitle = "Directly chat with ${user?.name ?: "user"}",
                        onClick = {
                            showMoreBottomSheet = false
                            user?.let { onNavigateToChat(it.id) }
                        }
                    )

                    HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                    OptionRow(
                        icon = Icons.Default.Block,
                        title = "Block User",
                        subtitle = "They won't be able to contact you",
                        tint = MaterialTheme.colorScheme.error,
                        onClick = {
                            showMoreBottomSheet = false
                            viewModel.handleIntent(ProfileIntent.ShowBlockDialog)
                        }
                    )

                    OptionRow(
                        icon = Icons.Default.Flag,
                        title = "Report Profile",
                        subtitle = "Report inappropriate behavior or spam",
                        tint = MaterialTheme.colorScheme.error,
                        onClick = {
                            showMoreBottomSheet = false
                            viewModel.handleIntent(ProfileIntent.ShowReportDialog)
                        }
                    )
                }

                Spacer(modifier = Modifier.height(24.dp))
            }
        }
    }

    // ========================================================
    // BLOCK USER CONFIRMATION DIALOG
    // ========================================================
    if (state.showBlockDialog) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissBlockDialog) },
            icon = { Icon(Icons.Default.Block, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("Block ${user?.name ?: "this user"}?") },
            text = {
                Text(
                    "${user?.name ?: "This user"} will no longer be able to view your profile, send you messages, or find your posts on Bondhoo (বন্ধু). They will not be notified that you blocked them."
                )
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(ProfileIntent.ConfirmBlockUser) },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Block")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissBlockDialog) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // ========================================================
    // REPORT PROFILE DIALOG
    // ========================================================
    if (state.showReportDialog) {
        val reasons = listOf(
            "Fake Account / Impersonation",
            "Harassment or Bullying",
            "Spam or Scam",
            "Hate Speech or Violence",
            "Inappropriate Content"
        )
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissReportDialog) },
            icon = { Icon(Icons.Default.Flag, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("Report Profile") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("Help us understand what's happening. Select a reason:", fontSize = 13.sp)
                    Spacer(modifier = Modifier.height(8.dp))
                    reasons.forEach { reason ->
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .clickable { reportReason = reason }
                                .padding(vertical = 4.dp)
                        ) {
                            RadioButton(
                                selected = reportReason == reason,
                                onClick = { reportReason = reason }
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(reason, fontSize = 14.sp)
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(ProfileIntent.ConfirmReportUser(reason = reportReason))
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Submit Report")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissReportDialog) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // ========================================================
    // UNFRIEND CONFIRMATION DIALOG
    // ========================================================
    if (state.showUnfriendDialog) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissUnfriendDialog) },
            icon = { Icon(Icons.Default.PersonRemove, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("Unfriend ${user?.name ?: "user"}?") },
            text = {
                Text("Are you sure you want to remove ${user?.name ?: "user"} as your friend? You can send them a friend request again anytime.")
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(ProfileIntent.UnfriendUser) },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Unfriend")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissUnfriendDialog) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // ========================================================
    // COMMENTS BOTTOM SHEET
    // ========================================================
    if (state.commentsPostId != null) {
        var newCommentText by remember { mutableStateOf("") }
        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissComments) },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp)
                    .padding(bottom = 24.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "Comments",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    IconButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissComments) }) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }
                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                if (state.isCommentsLoading) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(180.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }
                } else if (state.comments.isEmpty()) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(140.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            "No comments yet. Be the first to comment!",
                            color = TextSecondaryLight,
                            fontSize = 14.sp
                        )
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 340.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        items(state.comments.size) { idx ->
                            val comment = state.comments[idx]
                            Row(modifier = Modifier.fillMaxWidth()) {
                                val commentAuthor = comment.author ?: comment.user
                                Box(
                                    modifier = Modifier
                                        .size(36.dp)
                                        .clip(CircleShape)
                                        .background(Color.LightGray)
                                ) {
                                    AsyncImage(
                                        model = resolveMediaUrl(commentAuthor?.avatar),
                                        contentDescription = null,
                                        contentScale = ContentScale.Crop,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column(
                                    modifier = Modifier
                                        .weight(1f)
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                                        .padding(10.dp)
                                ) {
                                    Text(
                                        commentAuthor?.name ?: "User",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 13.sp
                                    )
                                    Text(
                                        comment.body ?: comment.content ?: "",
                                        fontSize = 13.sp
                                    )
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = newCommentText,
                        onValueChange = { newCommentText = it },
                        placeholder = { Text("Write a comment...", fontSize = 14.sp) },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(24.dp),
                        singleLine = true
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    IconButton(
                        onClick = {
                            if (newCommentText.isNotBlank()) {
                                viewModel.handleIntent(ProfileIntent.AddComment(state.commentsPostId!!, newCommentText.trim()))
                                newCommentText = ""
                            }
                        },
                        enabled = newCommentText.isNotBlank()
                    ) {
                        Icon(
                            Icons.AutoMirrored.Filled.Send,
                            contentDescription = "Send",
                            tint = if (newCommentText.isNotBlank()) ConnectlyBlue else Color.Gray
                        )
                    }
                }
            }
        }
    }

    // Smart Share Post Bottom Sheet
    if (state.sharingPostId != null) {
        val postId = state.sharingPostId!!

        LaunchedEffect(state.sharingPostId) {
            viewModel.handleIntent(ProfileIntent.LoadShareTargets)
        }

        ModalBottomSheet(
            onDismissRequest = { viewModel.handleIntent(ProfileIntent.DismissShareSheet) },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            when (state.sharingStep) {
                ProfileSharingStep.DESTINATIONS -> {
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
                            IconButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissShareSheet) }) {
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
                            onValueChange = { viewModel.handleIntent(ProfileIntent.UpdateShareCaption(it)) },
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
                                    ProfileIntent.ShareToTimeline(
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
                                viewModel.handleIntent(ProfileIntent.SetSharingStep(ProfileSharingStep.GROUPS))
                            }
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        // Option 3: Share to Pages
                        OptionRow(
                            icon = Icons.Default.Flag,
                            title = "Share to Pages",
                            subtitle = "Select a page you manage",
                            onClick = {
                                viewModel.handleIntent(ProfileIntent.SetSharingStep(ProfileSharingStep.PAGES))
                            }
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        // Option 4: More Options
                        OptionRow(
                            icon = Icons.Default.Share,
                            title = "More Options",
                            subtitle = "Share via Messenger, WhatsApp, or other apps",
                            onClick = {
                                val sendIntent = Intent().apply {
                                    action = Intent.ACTION_SEND
                                    putExtra(Intent.EXTRA_TEXT, "Check out this post on Bondhoo: https://bondhoo.com/posts/$postId\n${state.shareCaption.trim()}")
                                    type = "text/plain"
                                }
                                context.startActivity(Intent.createChooser(sendIntent, "Share post via"))
                                viewModel.handleIntent(ProfileIntent.DismissShareSheet)
                            }
                        )
                    }
                }

                ProfileSharingStep.GROUPS -> {
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
                                IconButton(onClick = { viewModel.handleIntent(ProfileIntent.SetSharingStep(ProfileSharingStep.DESTINATIONS)) }) {
                                    Icon(Icons.Default.ArrowBack, contentDescription = "Back to destinations")
                                }
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(
                                    text = "Share to Groups",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 18.sp
                                )
                            }
                            IconButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissShareSheet) }) {
                                Icon(Icons.Default.Close, contentDescription = "Close Share")
                            }
                        }

                        // Search Input
                        OutlinedTextField(
                            value = state.groupSearchQuery,
                            onValueChange = { viewModel.handleIntent(ProfileIntent.SearchShareGroups(it)) },
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
                                    onClick = { viewModel.handleIntent(ProfileIntent.SelectAllGroups) },
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                ) {
                                    Text("Select All", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                                }
                                TextButton(
                                    onClick = { viewModel.handleIntent(ProfileIntent.ClearSelectedGroups) },
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
                                            .clickable { viewModel.handleIntent(ProfileIntent.ToggleSelectGroup(group.id)) },
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
                                                    Text(group.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                    Text("${group.members_count} members • ${group.privacy.replaceFirstChar { it.uppercase() }}", fontSize = 11.sp, color = TextSecondaryLight)
                                                }
                                            }
                                            Checkbox(
                                                checked = isSelected,
                                                onCheckedChange = { viewModel.handleIntent(ProfileIntent.ToggleSelectGroup(group.id)) },
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
                                        ProfileIntent.ShareToSelectedGroups(
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

                ProfileSharingStep.PAGES -> {
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
                                IconButton(onClick = { viewModel.handleIntent(ProfileIntent.SetSharingStep(ProfileSharingStep.DESTINATIONS)) }) {
                                    Icon(Icons.Default.ArrowBack, contentDescription = "Back to destinations")
                                }
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(
                                    text = "Share to Pages",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 18.sp
                                )
                            }
                            IconButton(onClick = { viewModel.handleIntent(ProfileIntent.DismissShareSheet) }) {
                                Icon(Icons.Default.Close, contentDescription = "Close Share")
                            }
                        }

                        // Search Input
                        OutlinedTextField(
                            value = state.pageSearchQuery,
                            onValueChange = { viewModel.handleIntent(ProfileIntent.SearchSharePages(it)) },
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
                                            .clickable { viewModel.handleIntent(ProfileIntent.SelectPage(page.id)) },
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
                                                    Text(page.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                    Text("${page.category ?: "Page"} • Role: ${page.user_role.replaceFirstChar { it.uppercase() }}", fontSize = 11.sp, color = TextSecondaryLight)
                                                }
                                            }
                                            RadioButton(
                                                selected = isSelected,
                                                onClick = { viewModel.handleIntent(ProfileIntent.SelectPage(page.id)) },
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
                                        ProfileIntent.ShareToSelectedPage(
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
    onClick: () -> Unit,
    tint: Color = TextPrimaryLight
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(8.dp))
            .clickable(onClick = onClick)
            .padding(vertical = 10.dp, horizontal = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = null, tint = tint, modifier = Modifier.size(24.dp))
        Spacer(modifier = Modifier.width(14.dp))
        Column {
            Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 15.sp, color = tint)
            Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight)
        }
    }
}
