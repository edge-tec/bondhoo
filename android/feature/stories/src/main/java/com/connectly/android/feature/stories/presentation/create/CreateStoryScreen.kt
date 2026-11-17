package com.connectly.android.feature.stories.presentation.create

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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
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
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import java.io.File
import java.io.FileOutputStream

private val STORY_COLORS = listOf(
    "#1877F2", // Connectly Royal Blue
    "#8B5CF6", // Purple
    "#EC4899", // Pink
    "#10B981", // Emerald Green
    "#F59E0B", // Amber Orange
    "#EF4444", // Crimson Red
    "#0F172A", // Dark Slate
    "#059669", // Teal
    "#7C3AED"  // Deep Violet
)

private val FONT_OPTIONS = listOf("Modern", "Bold", "Serif", "Cursive")

@OptIn(ExperimentalMaterial3Api::class, UnstableApi::class)
@Composable
fun CreateStoryScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CreateStoryViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    var showPrivacyMenu by remember { mutableStateOf(false) }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let { error ->
            snackbarHostState.showSnackbar(error)
            viewModel.clearError()
        }
    }

    // Photo picker launcher
    val photoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        if (uri != null) {
            val file = uriToTempFile(context, uri, isVideo = false)
            if (file != null) {
                viewModel.setMedia(file = file, uri = uri, isVideo = false)
            }
        }
    }

    // Video picker launcher
    val videoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        if (uri != null) {
            val file = uriToTempFile(context, uri, isVideo = true)
            if (file != null) {
                viewModel.setMedia(file = file, uri = uri, isVideo = true)
            }
        }
    }

    val currentBgColor = try {
        Color(android.graphics.Color.parseColor(state.backgroundColor))
    } catch (_: Exception) {
        ConnectlyBlue
    }

    val textStyle = when (state.fontStyle) {
        "Bold" -> TextStyle(fontFamily = FontFamily.SansSerif, fontWeight = FontWeight.ExtraBold, fontSize = 26.sp)
        "Serif" -> TextStyle(fontFamily = FontFamily.Serif, fontWeight = FontWeight.Normal, fontStyle = FontStyle.Italic, fontSize = 24.sp)
        "Cursive" -> TextStyle(fontFamily = FontFamily.Cursive, fontWeight = FontWeight.Normal, fontSize = 28.sp)
        else -> TextStyle(fontFamily = FontFamily.SansSerif, fontWeight = FontWeight.SemiBold, fontSize = 24.sp)
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = when (state.storyType) {
                            "photo" -> "ছবি স্টোরি"
                            "video" -> "ভিডিও স্টোরি"
                            else -> "টেক্সট স্টোরি"
                        },
                        fontWeight = FontWeight.Bold,
                        color = Color.White
                    )
                },
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
                    // Privacy Selector Button
                    Box {
                        TextButton(
                            onClick = { showPrivacyMenu = true },
                            colors = ButtonDefaults.textButtonColors(contentColor = Color.White)
                        ) {
                            Icon(
                                imageVector = when (state.privacy) {
                                    "friends" -> Icons.Default.People
                                    "only_me" -> Icons.Default.Lock
                                    else -> Icons.Default.Public
                                },
                                contentDescription = null,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                text = when (state.privacy) {
                                    "friends" -> "Friends"
                                    "only_me" -> "Only Me"
                                    else -> "Public"
                                },
                                fontSize = 13.sp
                            )
                        }

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

                    // Share Button
                    if (state.isLoading) {
                        CircularProgressIndicator(
                            modifier = Modifier
                                .size(24.dp)
                                .padding(end = 16.dp),
                            color = Color.White,
                            strokeWidth = 2.5.dp
                        )
                    } else {
                        Button(
                            onClick = { viewModel.publishStory(onSuccess = onNavigateBack) },
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.padding(end = 8.dp)
                        ) {
                            Text("শেয়ার", fontWeight = FontWeight.Bold)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.Black.copy(alpha = 0.85f),
                    titleContentColor = Color.White
                )
            )
        },
        bottomBar = {
            // Bottom control panel for switching modes and picking media
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color.Black.copy(alpha = 0.95f))
                    .navigationBarsPadding()
                    .padding(vertical = 8.dp)
            ) {
                // Mode Selector Tabs (Text, Photo, Video)
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp),
                    horizontalArrangement = Arrangement.SpaceEvenly,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    // Text Mode Tab
                    ModeTabItem(
                        title = "টেক্সট",
                        icon = Icons.Default.TextFields,
                        isSelected = state.storyType == "text",
                        onClick = {
                            viewModel.clearMedia()
                            viewModel.setStoryType("text")
                        }
                    )

                    // Photo Mode Tab
                    ModeTabItem(
                        title = "ছবি",
                        icon = Icons.Default.Photo,
                        isSelected = state.storyType == "photo",
                        onClick = {
                            if (state.selectedMediaFile == null || state.isVideo) {
                                photoPickerLauncher.launch(
                                    PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                                )
                            } else {
                                viewModel.setStoryType("photo")
                            }
                        }
                    )

                    // Video Mode Tab
                    ModeTabItem(
                        title = "ভিডিও",
                        icon = Icons.Default.Videocam,
                        isSelected = state.storyType == "video",
                        onClick = {
                            if (state.selectedMediaFile == null || !state.isVideo) {
                                videoPickerLauncher.launch(
                                    PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                                )
                            } else {
                                viewModel.setStoryType("video")
                            }
                        }
                    )
                }

                Spacer(modifier = Modifier.height(6.dp))

                // Color palette or action button depending on mode
                if (state.storyType == "text") {
                    // Color swatch
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 4.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        STORY_COLORS.forEach { colorHex ->
                            val color = Color(android.graphics.Color.parseColor(colorHex))
                            val isSelected = state.backgroundColor.equals(colorHex, ignoreCase = true)
                            Box(
                                modifier = Modifier
                                    .size(32.dp)
                                    .clip(CircleShape)
                                    .background(color)
                                    .then(
                                        if (isSelected) Modifier.border(2.5.dp, Color.White, CircleShape)
                                        else Modifier
                                    )
                                    .clickable { viewModel.updateBackgroundColor(colorHex) },
                                contentAlignment = Alignment.Center
                            ) {
                                if (isSelected) {
                                    Icon(
                                        Icons.Default.Check,
                                        contentDescription = null,
                                        tint = Color.White,
                                        modifier = Modifier.size(16.dp)
                                    )
                                }
                            }
                        }
                    }
                } else {
                    // Photo / Video action row: Replace media or clear
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 4.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        OutlinedButton(
                            onClick = {
                                if (state.storyType == "photo") {
                                    photoPickerLauncher.launch(
                                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                                    )
                                } else {
                                    videoPickerLauncher.launch(
                                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                                    )
                                }
                            },
                            colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White),
                            border = ButtonDefaults.outlinedButtonBorder()
                        ) {
                            Icon(Icons.Default.Refresh, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(if (state.storyType == "photo") "অন্য ছবি নির্বাচন করুন" else "অন্য ভিডিও নির্বাচন করুন")
                        }

                        IconButton(onClick = { viewModel.clearMedia() }) {
                            Icon(Icons.Default.Close, contentDescription = "Remove media", tint = Color.White)
                        }
                    }
                }
            }
        },
        modifier = modifier
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(Color.Black),
            contentAlignment = Alignment.Center
        ) {
            when (state.storyType) {
                "photo" -> {
                    // Photo Story Canvas
                    if (state.selectedMediaUri != null) {
                        Box(modifier = Modifier.fillMaxSize()) {
                            AsyncImage(
                                model = state.selectedMediaUri,
                                contentDescription = "Story Photo Preview",
                                contentScale = ContentScale.Crop,
                                modifier = Modifier.fillMaxSize()
                            )

                            // Scrim overlay for readability
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .align(Alignment.BottomCenter)
                                    .background(
                                        Brush.verticalGradient(
                                            colors = listOf(Color.Transparent, Color.Black.copy(alpha = 0.75f))
                                        )
                                    )
                                    .padding(16.dp)
                            ) {
                                OutlinedTextField(
                                    value = state.storyText,
                                    onValueChange = { viewModel.updateStoryText(it) },
                                    placeholder = {
                                        Text("ক্যাপশন লিখুন...", color = Color.White.copy(alpha = 0.7f), fontSize = 16.sp)
                                    },
                                    colors = OutlinedTextFieldDefaults.colors(
                                        focusedBorderColor = Color.White.copy(alpha = 0.7f),
                                        unfocusedBorderColor = Color.White.copy(alpha = 0.3f),
                                        focusedTextColor = Color.White,
                                        unfocusedTextColor = Color.White
                                    ),
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(24.dp)
                                )
                            }
                        }
                    } else {
                        // Empty photo state: prompt to select
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center,
                            modifier = Modifier.padding(24.dp)
                        ) {
                            Icon(
                                Icons.Default.AddPhotoAlternate,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(72.dp)
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                "গ্যালারি থেকে ছবি বেছে নিন",
                                color = Color.White,
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Button(
                                onClick = {
                                    photoPickerLauncher.launch(
                                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                                    )
                                },
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Text("ছবি নির্বাচন করুন")
                            }
                        }
                    }
                }

                "video" -> {
                    // Video Story Canvas
                    if (state.selectedMediaUri != null) {
                        Box(modifier = Modifier.fillMaxSize()) {
                            StoryVideoPreview(uri = state.selectedMediaUri!!)

                            // Scrim overlay with caption
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .align(Alignment.BottomCenter)
                                    .background(
                                        Brush.verticalGradient(
                                            colors = listOf(Color.Transparent, Color.Black.copy(alpha = 0.75f))
                                        )
                                    )
                                    .padding(16.dp)
                            ) {
                                OutlinedTextField(
                                    value = state.storyText,
                                    onValueChange = { viewModel.updateStoryText(it) },
                                    placeholder = {
                                        Text("ভিডিও ক্যাপশন লিখুন...", color = Color.White.copy(alpha = 0.7f), fontSize = 16.sp)
                                    },
                                    colors = OutlinedTextFieldDefaults.colors(
                                        focusedBorderColor = Color.White.copy(alpha = 0.7f),
                                        unfocusedBorderColor = Color.White.copy(alpha = 0.3f),
                                        focusedTextColor = Color.White,
                                        unfocusedTextColor = Color.White
                                    ),
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(24.dp)
                                )
                            }
                        }
                    } else {
                        // Empty video state: prompt to select
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center,
                            modifier = Modifier.padding(24.dp)
                        ) {
                            Icon(
                                Icons.Default.VideoLibrary,
                                contentDescription = null,
                                tint = ConnectlyBlue,
                                modifier = Modifier.size(72.dp)
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                "গ্যালারি থেকে ভিডিও বেছে নিন",
                                color = Color.White,
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Button(
                                onClick = {
                                    videoPickerLauncher.launch(
                                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.VideoOnly)
                                    )
                                },
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Text("ভিডিও নির্বাচন করুন")
                            }
                        }
                    }
                }

                else -> {
                    // Text Story Canvas
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .background(currentBgColor),
                        contentAlignment = Alignment.Center
                    ) {
                        TextField(
                            value = state.storyText,
                            onValueChange = { viewModel.updateStoryText(it) },
                            placeholder = {
                                Text(
                                    "আপনার স্টোরি লিখুন...",
                                    color = Color.White.copy(alpha = 0.75f),
                                    fontSize = 26.sp,
                                    textAlign = TextAlign.Center,
                                    modifier = Modifier.fillMaxWidth()
                                )
                            },
                            textStyle = textStyle.copy(
                                color = Color.White,
                                textAlign = TextAlign.Center
                            ),
                            colors = TextFieldDefaults.colors(
                                focusedContainerColor = Color.Transparent,
                                unfocusedContainerColor = Color.Transparent,
                                focusedIndicatorColor = Color.Transparent,
                                unfocusedIndicatorColor = Color.Transparent,
                                focusedTextColor = Color.White,
                                unfocusedTextColor = Color.White
                            ),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(32.dp)
                        )

                        // Font Style Switcher Pill at Top Center
                        Row(
                            modifier = Modifier
                                .align(Alignment.TopCenter)
                                .padding(top = 16.dp)
                                .clip(RoundedCornerShape(20.dp))
                                .background(Color.Black.copy(alpha = 0.4f))
                                .padding(horizontal = 8.dp, vertical = 4.dp),
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            FONT_OPTIONS.forEach { font ->
                                val isSelected = state.fontStyle == font
                                Text(
                                    text = font,
                                    color = if (isSelected) ConnectlyBlue else Color.White,
                                    fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                    fontSize = 12.sp,
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(if (isSelected) Color.White else Color.Transparent)
                                        .clickable { viewModel.updateFontStyle(font) }
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
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
private fun ModeTabItem(
    title: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .clip(RoundedCornerShape(16.dp))
            .clickable { onClick() }
            .padding(horizontal = 16.dp, vertical = 6.dp)
    ) {
        Icon(
            imageVector = icon,
            contentDescription = title,
            tint = if (isSelected) ConnectlyBlue else Color.White.copy(alpha = 0.6f),
            modifier = Modifier.size(24.dp)
        )
        Spacer(modifier = Modifier.height(2.dp))
        Text(
            text = title,
            fontSize = 12.sp,
            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
            color = if (isSelected) ConnectlyBlue else Color.White.copy(alpha = 0.6f)
        )
    }
}

@OptIn(UnstableApi::class)
@Composable
private fun StoryVideoPreview(uri: Uri) {
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

private fun uriToTempFile(context: Context, uri: Uri, isVideo: Boolean): File? {
    return try {
        val contentResolver = context.contentResolver
        val mime = contentResolver.getType(uri) ?: if (isVideo) "video/mp4" else "image/jpeg"
        val ext = MimeTypeMap.getSingleton().getExtensionFromMimeType(mime) ?: if (isVideo) "mp4" else "jpg"
        val prefix = if (isVideo) "story_vid_" else "story_img_"
        val tempFile = File.createTempFile(prefix, ".$ext", context.cacheDir)
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
