package com.connectly.android.feature.stories.presentation.create

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.filled.Public
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

private val STORY_COLORS = listOf(
    "#1877F2", // Connectly Royal Blue
    "#8B5CF6", // Purple
    "#EC4899", // Pink/Rose
    "#10B981", // Emerald
    "#F59E0B", // Amber
    "#EF4444", // Red
    "#0F172A"  // Dark Slate
)

private val FONT_OPTIONS = listOf("Modern", "Bold", "Serif", "Cursive")

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreateStoryScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: CreateStoryViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let { error ->
            snackbarHostState.showSnackbar(error)
            viewModel.clearError()
        }
    }

    val currentBgColor = try {
        Color(android.graphics.Color.parseColor(state.backgroundColor))
    } catch (_: Exception) {
        ConnectlyBlue
    }

    val textStyle = when (state.fontStyle) {
        "Bold" -> TextStyle(fontFamily = FontFamily.SansSerif, fontWeight = FontWeight.ExtraBold, fontSize = 24.sp)
        "Serif" -> TextStyle(fontFamily = FontFamily.Serif, fontWeight = FontWeight.Normal, fontStyle = FontStyle.Italic, fontSize = 22.sp)
        "Cursive" -> TextStyle(fontFamily = FontFamily.Cursive, fontWeight = FontWeight.Normal, fontSize = 26.sp)
        else -> TextStyle(fontFamily = FontFamily.SansSerif, fontWeight = FontWeight.SemiBold, fontSize = 22.sp)
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Create Story", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
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
                            onClick = { viewModel.publishStory(onSuccess = onNavigateBack) },
                            shape = RoundedCornerShape(20.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.padding(end = 8.dp)
                        ) {
                            Text("Share", fontWeight = FontWeight.Bold)
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
                .background(Color(0xFF0F172A))
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Privacy selector chip
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 12.dp),
                horizontalArrangement = Arrangement.Center
            ) {
                listOf(
                    Triple("public", "Public", Icons.Default.Public),
                    Triple("friends", "Friends", Icons.Default.People),
                    Triple("only_me", "Only Me", Icons.Default.Lock)
                ).forEach { (priv, label, icon) ->
                    val isSelected = state.privacy == priv
                    FilterChip(
                        selected = isSelected,
                        onClick = { viewModel.updatePrivacy(priv) },
                        label = { Text(label, fontSize = 12.sp) },
                        leadingIcon = {
                            Icon(icon, contentDescription = null, modifier = Modifier.size(14.dp))
                        },
                        modifier = Modifier.padding(horizontal = 4.dp),
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ConnectlyBlue,
                            selectedLabelColor = Color.White,
                            selectedLeadingIconColor = Color.White
                        )
                    )
                }
            }

            // Main Story Canvas
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .clip(RoundedCornerShape(16.dp))
                    .background(currentBgColor),
                contentAlignment = Alignment.Center
            ) {
                TextField(
                    value = state.storyText,
                    onValueChange = { viewModel.updateStoryText(it) },
                    placeholder = {
                        Text(
                            "Type your story here...",
                            color = Color.White.copy(alpha = 0.7f),
                            fontSize = 22.sp,
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
                        .padding(24.dp)
                )
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Background color palette
            Text(
                "Background Color",
                color = Color.White.copy(alpha = 0.8f),
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp, vertical = 4.dp)
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 4.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                STORY_COLORS.forEach { colorHex ->
                    val color = Color(android.graphics.Color.parseColor(colorHex))
                    val isSelected = state.backgroundColor.equals(colorHex, ignoreCase = true)
                    Box(
                        modifier = Modifier
                            .size(36.dp)
                            .clip(CircleShape)
                            .background(color)
                            .then(
                                if (isSelected) Modifier.border(3.dp, Color.White, CircleShape)
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
                                modifier = Modifier.size(18.dp)
                            )
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(8.dp))

            // Font options
            Text(
                "Font Style",
                color = Color.White.copy(alpha = 0.8f),
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp, vertical = 4.dp)
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FONT_OPTIONS.forEach { font ->
                    val isSelected = state.fontStyle == font
                    OutlinedButton(
                        onClick = { viewModel.updateFontStyle(font) },
                        colors = ButtonDefaults.outlinedButtonColors(
                            containerColor = if (isSelected) ConnectlyBlue else Color.White.copy(alpha = 0.1f),
                            contentColor = Color.White
                        ),
                        border = if (isSelected) null else ButtonDefaults.outlinedButtonBorder(),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp),
                        modifier = Modifier.weight(1f)
                    ) {
                        Text(font, fontSize = 12.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal)
                    }
                }
            }
        }
    }
}
