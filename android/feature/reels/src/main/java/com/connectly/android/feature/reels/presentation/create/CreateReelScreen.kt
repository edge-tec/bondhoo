package com.connectly.android.feature.reels.presentation.create

import android.content.Context
import android.net.Uri
import android.webkit.MimeTypeMap
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.common.util.UnstableApi
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.AspectRatioFrameLayout
import androidx.media3.ui.PlayerView
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import java.io.File
import java.io.FileOutputStream

private val POPULAR_HASHTAGS = listOf("#bondhoo", "#reels", "#bangladesh", "#trending", "#viral", "#foryou")

@OptIn(ExperimentalMaterial3Api::class, UnstableApi::class)
@Composable
fun CreateReelScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CreateReelViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val scrollState = rememberScrollState()
    var showPrivacyMenu by remember { mutableStateOf(false) }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let { error ->
            snackbarHostState.showSnackbar(error)
            viewModel.clearError()
        }
    }

    // Video Picker Launcher
    val videoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        if (uri != null) {
            val file = uriToTempVideoFile(context, uri)
            if (file != null) {
                viewModel.setVideo(file = file, uri = uri)
            }
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("নতুন রিলস তৈরি করুন", fontWeight = FontWeight.Bold, color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back",
                            tint = Color.White
                        )
                    }
                },
                actions = {
                    if (state.isLoading) {
                        CircularProgressIndicator(
                            modifier = Modifier
                                .size(24.dp)
                                .padding(end = 16.dp),
                            color = ConnectlyBlue,
                            strokeWidth = 2.5.dp
                        )
                    } else {
                        Button(
                            onClick = { viewModel.publishReel(onSuccess = onNavigateBack) },
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.padding(end = 8.dp)
                        ) {
                            Text("শেয়ার করুন", fontWeight = FontWeight.Bold)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color(0xFF0F172A),
                    titleContentColor = Color.White
                )
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(Color(0xFF0F172A))
                .verticalScroll(scrollState)
                .padding(16.dp)
        ) {
            // Video Preview or Selection Box (9:16 Aspect ratio banner)
            if (state.selectedVideoUri != null) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(380.dp),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.Black)
                ) {
                    Box(modifier = Modifier.fillMaxSize()) {
                        ReelLocalVideoPreview(uri = state.selectedVideoUri!!)

                        // Top right change video button
                        IconButton(
                            onClick = {
                                videoPickerLauncher.launch(
                                    PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                                )
                            },
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .padding(12.dp)
                                .clip(CircleShape)
                                .background(Color.Black.copy(alpha = 0.6f))
                        ) {
                            Icon(Icons.Default.Refresh, contentDescription = "Change Video", tint = Color.White)
                        }

                        // Bottom badge
                        Row(
                            modifier = Modifier
                                .align(Alignment.BottomStart)
                                .padding(12.dp)
                                .clip(RoundedCornerShape(8.dp))
                                .background(Color.Black.copy(alpha = 0.6f))
                                .padding(horizontal = 8.dp, vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.Videocam,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(16.dp)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("ভিডিও নির্বাচিত হয়েছে", color = Color.White, fontSize = 12.sp)
                        }
                    }
                }
            } else {
                // Empty video prompt card
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(240.dp)
                        .clickable {
                            videoPickerLauncher.launch(
                                PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                            )
                        },
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFF1E293B)),
                    border = CardDefaults.outlinedCardBorder()
                ) {
                    Column(
                        modifier = Modifier.fillMaxSize(),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.Center
                    ) {
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .clip(CircleShape)
                                .background(ConnectlyBlue.copy(alpha = 0.2f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Default.VideoCall,
                                contentDescription = "Select Reel Video",
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(36.dp)
                            )
                        }
                        Spacer(modifier = Modifier.height(14.dp))
                        Text(
                            "রিলসের জন্য ভিডিও নির্বাচন করুন",
                            color = Color.White,
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Text(
                            "গ্যালারি থেকে আপনার ভিডিওটি বেছে নিন",
                            color = Color.White.copy(alpha = 0.6f),
                            fontSize = 13.sp
                        )
                        Spacer(modifier = Modifier.height(14.dp))
                        Button(
                            onClick = {
                                videoPickerLauncher.launch(
                                    PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                                )
                            },
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                        ) {
                            Icon(Icons.Default.Upload, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("ভিডিও আপলোড করুন")
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            // Caption Text Field
            Text(
                "ক্যাপশন ও বর্ণনা",
                color = Color.White,
                fontSize = 14.sp,
                fontWeight = FontWeight.SemiBold
            )
            Spacer(modifier = Modifier.height(8.dp))
            OutlinedTextField(
                value = state.caption,
                onValueChange = { viewModel.updateCaption(it) },
                placeholder = {
                    Text(
                        "আপনার রিলস সম্পর্কে কিছু লিখুন... #bondhoo #reels",
                        color = Color.White.copy(alpha = 0.5f),
                        fontSize = 14.sp
                    )
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(110.dp),
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = ConnectlyBlue,
                    unfocusedBorderColor = Color(0xFF334155),
                    focusedContainerColor = Color(0xFF1E293B),
                    unfocusedContainerColor = Color(0xFF1E293B),
                    focusedTextColor = Color.White,
                    unfocusedTextColor = Color.White
                )
            )

            Spacer(modifier = Modifier.height(10.dp))

            // Quick Hashtag Chips
            Text(
                "জনপ্রিয় হ্যাশট্যাগ:",
                color = Color.White.copy(alpha = 0.7f),
                fontSize = 12.sp,
                fontWeight = FontWeight.Medium
            )
            Spacer(modifier = Modifier.height(6.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                POPULAR_HASHTAGS.take(4).forEach { tag ->
                    SuggestionChip(
                        onClick = { viewModel.appendHashtag(tag) },
                        label = { Text(tag, fontSize = 11.sp, color = Color.White) },
                        colors = SuggestionChipDefaults.suggestionChipColors(
                            containerColor = Color(0xFF334155)
                        ),
                        border = null
                    )
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Audio Title
            Text(
                "অডিও বা মিউজিক টাইটেল",
                color = Color.White,
                fontSize = 14.sp,
                fontWeight = FontWeight.SemiBold
            )
            Spacer(modifier = Modifier.height(8.dp))
            OutlinedTextField(
                value = state.audioTitle,
                onValueChange = { viewModel.updateAudioTitle(it) },
                placeholder = { Text("Original Audio", color = Color.White.copy(alpha = 0.5f)) },
                leadingIcon = {
                    Icon(Icons.Default.MusicNote, contentDescription = null, tint = ConnectlyBlue)
                },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = ConnectlyBlue,
                    unfocusedBorderColor = Color(0xFF334155),
                    focusedContainerColor = Color(0xFF1E293B),
                    unfocusedContainerColor = Color(0xFF1E293B),
                    focusedTextColor = Color.White,
                    unfocusedTextColor = Color.White
                )
            )

            Spacer(modifier = Modifier.height(16.dp))

            // Privacy and Comment Options Card
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFF1E293B))
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    // Privacy Option Row
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { showPrivacyMenu = true },
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                imageVector = when (state.privacy) {
                                    "friends" -> Icons.Default.People
                                    "only_me" -> Icons.Default.Lock
                                    else -> Icons.Default.Public
                                },
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(20.dp)
                            )
                            Spacer(modifier = Modifier.width(10.dp))
                            Column {
                                Text("প্রাইভেসি", color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Medium)
                                Text(
                                    text = when (state.privacy) {
                                        "friends" -> "বন্ধুরা দেখতে পারবে (Friends)"
                                        "only_me" -> "শুধু আমি (Only Me)"
                                        else -> "সবার জন্য উন্মুক্ত (Public)"
                                    },
                                    color = Color.White.copy(alpha = 0.6f),
                                    fontSize = 12.sp
                                )
                            }
                        }

                        Icon(Icons.Default.KeyboardArrowDown, contentDescription = null, tint = Color.White)

                        DropdownMenu(
                            expanded = showPrivacyMenu,
                            onDismissRequest = { showPrivacyMenu = false }
                        ) {
                            DropdownMenuItem(
                                text = { Text("Public (সবার জন্য)") },
                                leadingIcon = { Icon(Icons.Default.Public, contentDescription = null) },
                                onClick = {
                                    viewModel.updatePrivacy("public")
                                    showPrivacyMenu = false
                                }
                            )
                            DropdownMenuItem(
                                text = { Text("Friends (বন্ধুরা)") },
                                leadingIcon = { Icon(Icons.Default.People, contentDescription = null) },
                                onClick = {
                                    viewModel.updatePrivacy("friends")
                                    showPrivacyMenu = false
                                }
                            )
                            DropdownMenuItem(
                                text = { Text("Only Me (ব্যক্তিগত)") },
                                leadingIcon = { Icon(Icons.Default.Lock, contentDescription = null) },
                                onClick = {
                                    viewModel.updatePrivacy("only_me")
                                    showPrivacyMenu = false
                                }
                            )
                        }
                    }

                    HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp), color = Color(0xFF334155))

                    // Allow Comments Switch
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                Icons.Default.ChatBubbleOutline,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(20.dp)
                            )
                            Spacer(modifier = Modifier.width(10.dp))
                            Column {
                                Text("মন্তব্য করার অনুমতি দিন", color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Medium)
                                Text("দর্শকরা আপনার রিলসে মন্তব্য করতে পারবে", color = Color.White.copy(alpha = 0.6f), fontSize = 12.sp)
                            }
                        }

                        Switch(
                            checked = state.allowComments,
                            onCheckedChange = { viewModel.toggleAllowComments(it) },
                            colors = SwitchDefaults.colors(
                                checkedThumbColor = Color.White,
                                checkedTrackColor = ConnectlyBlue
                            )
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(24.dp))

            // Large Share Button at bottom
            Button(
                onClick = { viewModel.publishReel(onSuccess = onNavigateBack) },
                enabled = !state.isLoading && state.selectedVideoFile != null,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(50.dp),
                shape = RoundedCornerShape(25.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = ConnectlyBlue,
                    disabledContainerColor = Color(0xFF334155)
                )
            ) {
                if (state.isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(22.dp),
                        color = Color.White,
                        strokeWidth = 2.5.dp
                    )
                    Spacer(modifier = Modifier.width(10.dp))
                    Text("রিলস আপলোড হচ্ছে...", fontWeight = FontWeight.Bold, color = Color.White)
                } else {
                    Icon(Icons.Default.Send, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("রিলস শেয়ার করুন", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                }
            }

            Spacer(modifier = Modifier.height(30.dp))
        }
    }
}

@OptIn(UnstableApi::class)
@Composable
private fun ReelLocalVideoPreview(uri: Uri) {
    val context = LocalContext.current
    val exoPlayer = remember(uri) {
        ExoPlayer.Builder(context).build().apply {
            setMediaItem(MediaItem.fromUri(uri))
            repeatMode = Player.REPEAT_MODE_ALL
            prepare()
            playWhenReady = true
        }
    }

    DisposableEffect(exoPlayer) {
        onDispose {
            exoPlayer.release()
        }
    }

    AndroidView(
        factory = { ctx ->
            PlayerView(ctx).apply {
                player = exoPlayer
                useController = false
                resizeMode = AspectRatioFrameLayout.RESIZE_MODE_ZOOM
            }
        },
        modifier = Modifier.fillMaxSize()
    )
}

private fun uriToTempVideoFile(context: Context, uri: Uri): File? {
    return try {
        val contentResolver = context.contentResolver
        val mime = contentResolver.getType(uri) ?: "video/mp4"
        val ext = MimeTypeMap.getSingleton().getExtensionFromMimeType(mime) ?: "mp4"
        val tempFile = File.createTempFile("reel_vid_", ".$ext", context.cacheDir)
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
