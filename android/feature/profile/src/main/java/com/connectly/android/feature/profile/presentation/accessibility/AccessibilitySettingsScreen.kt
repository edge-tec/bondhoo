package com.connectly.android.feature.profile.presentation.accessibility

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ThemeManager

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AccessibilitySettingsScreen(
    themeManager: ThemeManager,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val fontScale by themeManager.fontScale.collectAsState()
    val highContrast by themeManager.highContrast.collectAsState()
    val reduceMotion by themeManager.reduceAnimations.collectAsState()
    val videoCaptions by themeManager.videoCaptions.collectAsState()
    val screenReaderOptimized by themeManager.screenReaderOptimized.collectAsState()

    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Accessibility",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        modifier = modifier
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(MaterialTheme.colorScheme.background)
                .verticalScroll(scrollState)
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Section 1: Vision & Text Scaling
            Card(
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text(
                        text = "Vision & Reading",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Text(
                        text = "Adjust text size and visual contrast for easier reading.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )

                    // Live preview box
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(12.dp))
                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                            .border(1.dp, MaterialTheme.colorScheme.outlineVariant, RoundedCornerShape(12.dp))
                            .padding(14.dp)
                    ) {
                        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Text(
                                text = "Preview Text",
                                style = MaterialTheme.typography.labelSmall,
                                color = ConnectlyBlue,
                                fontWeight = FontWeight.Bold
                            )
                            Text(
                                text = "Connectly helps you connect and share with the people in your life.",
                                fontSize = (14 * fontScale).sp,
                                lineHeight = (20 * fontScale).sp,
                                fontWeight = if (highContrast) FontWeight.Bold else FontWeight.Normal,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(4.dp))

                    Text(
                        text = "Font Size Scale: ${(fontScale * 100).toInt()}%",
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurface
                    )

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        FontScaleChip(
                            label = "Small (85%)",
                            scale = 0.85f,
                            selected = fontScale == 0.85f,
                            onSelect = { themeManager.setFontScale(0.85f) },
                            modifier = Modifier.weight(1f)
                        )
                        FontScaleChip(
                            label = "Default (100%)",
                            scale = 1.0f,
                            selected = fontScale == 1.0f,
                            onSelect = { themeManager.setFontScale(1.0f) },
                            modifier = Modifier.weight(1f)
                        )
                        FontScaleChip(
                            label = "Large (115%)",
                            scale = 1.15f,
                            selected = fontScale == 1.15f,
                            onSelect = { themeManager.setFontScale(1.15f) },
                            modifier = Modifier.weight(1f)
                        )
                        FontScaleChip(
                            label = "XL (130%)",
                            scale = 1.30f,
                            selected = fontScale == 1.30f,
                            onSelect = { themeManager.setFontScale(1.30f) },
                            modifier = Modifier.weight(1f)
                        )
                    }

                    HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                    AccessibilitySwitchRow(
                        title = "High Contrast Mode",
                        subtitle = "Increases color contrast on buttons, borders and text elements for improved legibility",
                        checked = highContrast,
                        onCheckedChange = { themeManager.setHighContrast(it) }
                    )
                }
            }

            // Section 2: Motion & Interaction
            Card(
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Text(
                        text = "Motion & Interaction",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )

                    AccessibilitySwitchRow(
                        title = "Reduce Motion",
                        subtitle = "Disables non-essential transition animations, screen transitions and carousel motions",
                        checked = reduceMotion,
                        onCheckedChange = { themeManager.setReduceAnimations(it) }
                    )

                    HorizontalDivider()

                    AccessibilitySwitchRow(
                        title = "Screen Reader Optimization",
                        subtitle = "Provides enhanced TalkBack semantics and detailed accessibility descriptions on buttons",
                        checked = screenReaderOptimized,
                        onCheckedChange = { themeManager.setScreenReaderOptimized(it) }
                    )
                }
            }

            // Section 3: Audio & Video Accessibility
            Card(
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Text(
                        text = "Media & Captions",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )

                    AccessibilitySwitchRow(
                        title = "Closed Captions",
                        subtitle = "Show subtitles automatically whenever captions are available on videos and reels",
                        checked = videoCaptions,
                        onCheckedChange = { themeManager.setVideoCaptions(it) }
                    )
                }
            }

            Spacer(modifier = Modifier.height(16.dp))
        }
    }
}

@Composable
private fun FontScaleChip(
    label: String,
    scale: Float,
    selected: Boolean,
    onSelect: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .clickable(onClick = onSelect)
            .background(
                if (selected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
            )
            .padding(vertical = 10.dp, horizontal = 2.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            fontWeight = if (selected) FontWeight.Bold else FontWeight.Medium,
            color = if (selected) androidx.compose.ui.graphics.Color.White else MaterialTheme.colorScheme.onSurface,
            fontSize = 11.sp
        )
    }
}

@Composable
private fun AccessibilitySwitchRow(
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.width(12.dp))
        Switch(
            checked = checked,
            onCheckedChange = onCheckedChange,
            colors = SwitchDefaults.colors(checkedThumbColor = ConnectlyBlue)
        )
    }
}
