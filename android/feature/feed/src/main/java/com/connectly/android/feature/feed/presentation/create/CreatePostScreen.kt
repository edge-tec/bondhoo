package com.connectly.android.feature.feed.presentation.create

import android.content.Context
import android.net.Uri
import android.webkit.MimeTypeMap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.Close
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.VideoLibrary
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.model.User
import java.io.File
import java.io.FileOutputStream

data class FeelingOption(
    val key: String,
    val label: String,
    val emoji: String,
    val category: String = "Feelings"
)

val PRESET_FEELINGS = listOf(
    FeelingOption("happy", "Happy", "😀"),
    FeelingOption("blessed", "Blessed", "😇"),
    FeelingOption("loved", "Loved", "🥰"),
    FeelingOption("excited", "Excited", "🤩"),
    FeelingOption("motivated", "Motivated", "💪"),
    FeelingOption("thankful", "Thankful", "🙏"),
    FeelingOption("crazy", "Crazy", "🤪"),
    FeelingOption("cool", "Cool", "😎"),
    FeelingOption("sad", "Sad", "😢"),
    FeelingOption("tired", "Tired", "😴"),
    FeelingOption("relaxed", "Relaxed", "😌"),
    FeelingOption("working", "Working on", "💼", "Activities"),
    FeelingOption("celebrating", "Celebrating", "🥳", "Activities"),
    FeelingOption("traveling", "Traveling to", "✈️", "Activities"),
    FeelingOption("eating", "Eating", "🍕", "Activities"),
    FeelingOption("watching", "Watching", "🍿", "Activities"),
    FeelingOption("listening", "Listening to Music", "🎧", "Activities"),
    FeelingOption("thinking", "Thinking about", "🤔", "Activities")
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreatePostScreen(
    initialAction: String? = null,
    onNavigateBack: () -> Unit,
    onNavigateToLive: ((Long) -> Unit)? = null,
    modifier: Modifier = Modifier,
    viewModel: CreatePostViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val snackbarHostState = remember { SnackbarHostState() }

    var showFeelingsDialog by remember { mutableStateOf(false) }
    var showAudienceDialog by remember { mutableStateOf(false) }
    var showLocationDialog by remember { mutableStateOf(false) }
    var showTagFriendsDialog by remember { mutableStateOf(false) }

    // Media Pickers
    val photoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickMultipleVisualMedia(maxItems = 10)
    ) { uris ->
        uris.forEach { uri ->
            val file = uriToFile(context, uri)
            if (file != null) {
                viewModel.handleIntent(CreatePostIntent.MediaAdded(file, uri))
            }
        }
    }

    val videoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        if (uri != null) {
            val file = uriToFile(context, uri)
            if (file != null) {
                viewModel.handleIntent(CreatePostIntent.MediaAdded(file, uri))
            }
        }
    }

    // Auto-trigger initial action if navigated from specific button
    var initialActionHandled by remember { mutableStateOf(false) }
    LaunchedEffect(initialAction) {
        if (!initialActionHandled && !initialAction.isNullOrBlank()) {
            initialActionHandled = true
            when (initialAction.lowercase()) {
                "photo" -> photoPickerLauncher.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly))
                "video" -> videoPickerLauncher.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly))
                "feeling" -> showFeelingsDialog = true
                "live" -> onNavigateToLive?.invoke(System.currentTimeMillis())
            }
        }
    }

    LaunchedEffect(state.isPosted) {
        if (state.isPosted) {
            onNavigateBack()
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
        }
    }

    // Feelings Dialog
    if (showFeelingsDialog) {
        FeelingsSelectionDialog(
            selectedFeeling = state.feelingActivity,
            onDismiss = { showFeelingsDialog = false },
            onSelect = { option ->
                viewModel.handleIntent(CreatePostIntent.FeelingSelected(option.label, option.emoji))
                showFeelingsDialog = false
            }
        )
    }

    // Audience Dialog
    if (showAudienceDialog) {
        AudienceSelectionDialog(
            currentAudience = state.audience,
            onDismiss = { showAudienceDialog = false },
            onSelect = { audience ->
                viewModel.handleIntent(CreatePostIntent.AudienceChanged(audience))
                showAudienceDialog = false
            }
        )
    }

    // Location Dialog
    if (showLocationDialog) {
        LocationSelectionDialog(
            currentLocation = state.location,
            onDismiss = { showLocationDialog = false },
            onSelect = { loc ->
                viewModel.handleIntent(CreatePostIntent.LocationChanged(loc))
                showLocationDialog = false
            }
        )
    }

    // Tag Friends Dialog
    if (showTagFriendsDialog) {
        TagFriendsDialog(
            taggedUsers = state.taggedUsers,
            onDismiss = { showTagFriendsDialog = false },
            onDone = { users ->
                viewModel.handleIntent(CreatePostIntent.TaggedUsersChanged(users))
                showTagFriendsDialog = false
            }
        )
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Create Post", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    Button(
                        onClick = { viewModel.handleIntent(CreatePostIntent.SubmitPost) },
                        enabled = !state.isLoading && (state.content.isNotBlank() || state.mediaFiles.isNotEmpty() || !state.feelingActivity.isNullOrBlank()),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                        shape = RoundedCornerShape(20.dp),
                        modifier = Modifier.padding(end = 8.dp)
                    ) {
                        if (state.isLoading) {
                            CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                        } else {
                            Text("Post", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp)
        ) {
            // Author info and audience selector
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(46.dp)
                        .clip(CircleShape)
                        .background(Color(0xFFE2E8F0))
                ) {
                    Icon(
                        imageVector = Icons.Default.Person,
                        contentDescription = "User Avatar",
                        tint = Color.Gray,
                        modifier = Modifier
                            .size(30.dp)
                            .align(Alignment.Center)
                    )
                }

                Spacer(modifier = Modifier.width(12.dp))

                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = "You",
                            fontWeight = FontWeight.Bold,
                            fontSize = 15.sp
                        )
                        if (!state.feelingActivity.isNullOrBlank()) {
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = "is feeling ${state.feelingActivity} ${state.feelingEmoji.orEmpty()}",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(
                                imageVector = Icons.Outlined.Close,
                                contentDescription = "Remove feeling",
                                modifier = Modifier
                                    .size(14.dp)
                                    .clickable {
                                        viewModel.handleIntent(CreatePostIntent.FeelingSelected(null, null))
                                    },
                                tint = Color.Gray
                            )
                        }
                    }

                    if (state.taggedUsers.isNotEmpty()) {
                        Text(
                            text = "with ${state.taggedUsers.joinToString { it.name }}",
                            fontSize = 12.sp,
                            color = ConnectlyBlue
                        )
                    }

                    Spacer(modifier = Modifier.height(3.dp))

                    // Audience Chip
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = Color(0xFFF1F5F9),
                        modifier = Modifier.clickable { showAudienceDialog = true }
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                imageVector = when (state.audience) {
                                    "friends" -> Icons.Default.Group
                                    "only_me" -> Icons.Default.Lock
                                    else -> Icons.Default.Public
                                },
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(14.dp)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = when (state.audience) {
                                    "friends" -> "Friends"
                                    "only_me" -> "Only Me"
                                    else -> "Public"
                                },
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Medium,
                                color = ConnectlyBlue
                            )
                            Icon(
                                imageVector = Icons.Default.ArrowDropDown,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }
                }
            }

            // Location banner (if set)
            if (!state.location.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(8.dp))
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = Color(0xFFFEF2F2),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Outlined.LocationOn, contentDescription = null, tint = Color(0xFFEF4444), modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(state.location!!, fontSize = 12.sp, color = Color(0xFF991B1B), modifier = Modifier.weight(1f))
                        Icon(
                            imageVector = Icons.Default.Close,
                            contentDescription = "Remove location",
                            tint = Color.Gray,
                            modifier = Modifier
                                .size(14.dp)
                                .clickable { viewModel.handleIntent(CreatePostIntent.LocationChanged(null)) }
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // Post content input
            TextField(
                value = state.content,
                onValueChange = { viewModel.handleIntent(CreatePostIntent.ContentChanged(it)) },
                placeholder = { Text("What's on your mind?", fontSize = 18.sp, color = Color.Gray) },
                colors = TextFieldDefaults.colors(
                    focusedContainerColor = Color.Transparent,
                    unfocusedContainerColor = Color.Transparent,
                    focusedIndicatorColor = Color.Transparent,
                    unfocusedIndicatorColor = Color.Transparent
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
            )

            // Attached Media Previews
            if (state.mediaFiles.isNotEmpty() || state.mediaUris.isNotEmpty()) {
                Text(
                    text = "Attached Media (${state.mediaFiles.size})",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(bottom = 6.dp)
                )

                LazyRow(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp)
                ) {
                    items(state.mediaFiles) { file ->
                        val isVideo = file.extension.lowercase() in listOf("mp4", "mov", "webm", "m4v")
                        Box(
                            modifier = Modifier
                                .size(90.dp)
                                .clip(RoundedCornerShape(8.dp))
                                .background(Color(0xFFE2E8F0))
                        ) {
                            AsyncImage(
                                model = file,
                                contentDescription = "Attached media",
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.fillMaxSize()
                            )

                            if (isVideo) {
                                Box(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .background(Color.Black.copy(alpha = 0.3f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = Icons.Outlined.VideoLibrary,
                                        contentDescription = "Video",
                                        tint = Color.White,
                                        modifier = Modifier.size(24.dp)
                                    )
                                }
                            }

                            // Remove button
                            Box(
                                modifier = Modifier
                                    .padding(4.dp)
                                    .size(20.dp)
                                    .clip(CircleShape)
                                    .background(Color.Black.copy(alpha = 0.6f))
                                    .clickable { viewModel.handleIntent(CreatePostIntent.MediaRemoved(file)) }
                                    .align(Alignment.TopEnd),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Close,
                                    contentDescription = "Remove",
                                    tint = Color.White,
                                    modifier = Modifier.size(12.dp)
                                )
                            }
                        }
                    }

                    // Add more media card
                    item {
                        Box(
                            modifier = Modifier
                                .size(90.dp)
                                .clip(RoundedCornerShape(8.dp))
                                .border(1.dp, Color(0xFFCBD5E1), RoundedCornerShape(8.dp))
                                .background(Color(0xFFF8FAFC))
                                .clickable {
                                    photoPickerLauncher.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageAndVideo))
                                },
                            contentAlignment = Alignment.Center
                        ) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Icon(Icons.Default.AddPhotoAlternate, contentDescription = "Add More", tint = ConnectlyBlue)
                                Text("Add", fontSize = 11.sp, color = ConnectlyBlue, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }

            // Add to post tools (Photo, Video, Tag, Feeling, Location)
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 14.dp, vertical = 10.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Add to your post", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)

                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        IconButton(
                            onClick = {
                                onNavigateToLive?.invoke(System.currentTimeMillis())
                            }
                        ) {
                            Icon(Icons.Default.Videocam, contentDescription = "Go Live", tint = Color(0xFFEF4444))
                        }

                        IconButton(
                            onClick = {
                                photoPickerLauncher.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly))
                            }
                        ) {
                            Icon(Icons.Default.PhotoLibrary, contentDescription = "Add Photo", tint = ConnectlyGreen)
                        }

                        IconButton(
                            onClick = {
                                videoPickerLauncher.launch(PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly))
                            }
                        ) {
                            Icon(Icons.Default.VideoCall, contentDescription = "Add Video", tint = ConnectlyRose)
                        }

                        IconButton(onClick = { showTagFriendsDialog = true }) {
                            Icon(Icons.Default.PersonAdd, contentDescription = "Tag People", tint = ConnectlyBlue)
                        }

                        IconButton(onClick = { showFeelingsDialog = true }) {
                            Icon(Icons.Default.EmojiEmotions, contentDescription = "Feeling", tint = Color(0xFFF59E0B))
                        }

                        IconButton(onClick = { showLocationDialog = true }) {
                            Icon(Icons.Outlined.LocationOn, contentDescription = "Check in", tint = Color(0xFFEF4444))
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun FeelingsSelectionDialog(
    selectedFeeling: String?,
    onDismiss: () -> Unit,
    onSelect: (FeelingOption) -> Unit
) {
    var searchQuery by remember { mutableStateOf("") }
    val filtered = PRESET_FEELINGS.filter {
        it.label.contains(searchQuery, ignoreCase = true) || it.key.contains(searchQuery, ignoreCase = true)
    }

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = MaterialTheme.colorScheme.surface,
            modifier = Modifier
                .fillMaxWidth()
                .fillMaxHeight(0.75f)
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("How are you feeling?", fontWeight = FontWeight.Bold, fontSize = 17.sp)
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }

                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search feelings & activities...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    shape = RoundedCornerShape(12.dp)
                )

                LazyVerticalGrid(
                    columns = GridCells.Fixed(2),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.weight(1f)
                ) {
                    items(filtered) { option ->
                        val isSelected = option.label.equals(selectedFeeling, ignoreCase = true)
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = if (isSelected) ConnectlyBlue.copy(alpha = 0.15f) else Color(0xFFF8FAFC),
                            border = if (isSelected) androidx.compose.foundation.BorderStroke(1.dp, ConnectlyBlue) else null,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { onSelect(option) }
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(option.emoji, fontSize = 20.sp)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = option.label,
                                    fontSize = 13.sp,
                                    fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
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
private fun AudienceSelectionDialog(
    currentAudience: String,
    onDismiss: () -> Unit,
    onSelect: (String) -> Unit
) {
    val options = listOf(
        Triple("public", "Public", "Anyone on or off BONDHOO"),
        Triple("friends", "Friends", "Your friends on BONDHOO"),
        Triple("only_me", "Only Me", "Only you can see this post")
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Select Audience", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                options.forEach { (key, title, subtitle) ->
                    val isSelected = currentAudience == key
                    Surface(
                        shape = RoundedCornerShape(10.dp),
                        color = if (isSelected) ConnectlyBlue.copy(alpha = 0.1f) else Color(0xFFF8FAFC),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { onSelect(key) }
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                imageVector = when (key) {
                                    "friends" -> Icons.Default.Group
                                    "only_me" -> Icons.Default.Lock
                                    else -> Icons.Default.Public
                                },
                                contentDescription = null,
                                tint = if (isSelected) ConnectlyBlue else Color.Gray,
                                modifier = Modifier.size(24.dp)
                            )
                            Spacer(modifier = Modifier.width(12.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text(title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text(subtitle, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            if (isSelected) {
                                Icon(Icons.Default.Check, contentDescription = "Selected", tint = ConnectlyBlue)
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun LocationSelectionDialog(
    currentLocation: String?,
    onDismiss: () -> Unit,
    onSelect: (String?) -> Unit
) {
    var locationInput by remember { mutableStateOf(currentLocation ?: "") }
    val quickLocations = listOf(
        "Dhaka, Bangladesh",
        "Gulshan, Dhaka",
        "Banani, Dhaka",
        "Dhanmondi, Dhaka",
        "Chittagong, Bangladesh",
        "Sylhet, Bangladesh"
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Add Location / Check In", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                OutlinedTextField(
                    value = locationInput,
                    onValueChange = { locationInput = it },
                    placeholder = { Text("Where are you?") },
                    leadingIcon = { Icon(Icons.Outlined.LocationOn, contentDescription = null) },
                    shape = RoundedCornerShape(10.dp),
                    modifier = Modifier.fillMaxWidth()
                )

                Text("Quick suggestions:", fontSize = 12.sp, color = Color.Gray, fontWeight = FontWeight.SemiBold)

                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    quickLocations.forEach { loc ->
                        Surface(
                            shape = RoundedCornerShape(8.dp),
                            color = Color(0xFFF1F5F9),
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { locationInput = loc }
                        ) {
                            Text(
                                text = loc,
                                fontSize = 12.sp,
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp)
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onSelect(locationInput.ifBlank { null }) },
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                Text("Set Location")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun TagFriendsDialog(
    taggedUsers: List<User>,
    onDismiss: () -> Unit,
    onDone: (List<User>) -> Unit
) {
    val sampleFriends = remember {
        listOf(
            User(id = 2, name = "Sadia Rahman", username = "sadia", isVerified = true),
            User(id = 3, name = "Rahat Chowdhury", username = "rahat"),
            User(id = 4, name = "Tania Sultana", username = "tania"),
            User(id = 5, name = "Bondhoo System Admin", username = "admin", isVerified = true),
            User(id = 6, name = "Dewitt Marvin", username = "dewitt")
        )
    }

    var selected by remember { mutableStateOf(taggedUsers) }
    var searchQuery by remember { mutableStateOf("") }
    val filtered = sampleFriends.filter {
        it.name.contains(searchQuery, ignoreCase = true) || it.username.contains(searchQuery, ignoreCase = true)
    }

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = MaterialTheme.colorScheme.surface,
            modifier = Modifier
                .fillMaxWidth()
                .fillMaxHeight(0.75f)
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Tag People", fontWeight = FontWeight.Bold, fontSize = 17.sp)
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }

                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search friends...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    shape = RoundedCornerShape(12.dp)
                )

                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.weight(1f)
                ) {
                    items(filtered) { user ->
                        val isChecked = selected.any { it.id == user.id }
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = if (isChecked) ConnectlyBlue.copy(alpha = 0.1f) else Color(0xFFF8FAFC),
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    selected = if (isChecked) {
                                        selected.filter { it.id != user.id }
                                    } else {
                                        selected + user
                                    }
                                }
                        ) {
                            Row(
                                modifier = Modifier.padding(10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(36.dp)
                                        .clip(CircleShape)
                                        .background(Color(0xFFCBD5E1)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = user.name.take(1),
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 14.sp
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(user.name, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                                    Text("@${user.username}", fontSize = 11.sp, color = Color.Gray)
                                }
                                Checkbox(
                                    checked = isChecked,
                                    onCheckedChange = { checked ->
                                        selected = if (checked) {
                                            selected + user
                                        } else {
                                            selected.filter { it.id != user.id }
                                        }
                                    }
                                )
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                Button(
                    onClick = { onDone(selected) },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Done (${selected.size} tagged)", fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

/**
 * Safely copy content Uri into a cache file for multipart upload.
 */
private fun uriToFile(context: Context, uri: Uri): File? {
    return try {
        val contentResolver = context.contentResolver
        val mime = contentResolver.getType(uri) ?: "image/jpeg"
        val ext = MimeTypeMap.getSingleton().getExtensionFromMimeType(mime) ?: "jpg"
        val tempFile = File.createTempFile("post_upload_", ".$ext", context.cacheDir)
        contentResolver.openInputStream(uri)?.use { input ->
            FileOutputStream(tempFile).use { output ->
                input.copyTo(output)
            }
        }
        tempFile
    } catch (_: Exception) {
        null
    }
}
