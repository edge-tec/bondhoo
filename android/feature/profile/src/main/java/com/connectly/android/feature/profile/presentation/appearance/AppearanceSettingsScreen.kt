package com.connectly.android.feature.profile.presentation.appearance

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AppearanceSettingsScreen(
    themeManager: ThemeManager,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val themeMode by themeManager.themeMode.collectAsState()
    val accentId by themeManager.accentId.collectAsState()
    val fontScale by themeManager.fontScale.collectAsState()
    val compactDisplay by themeManager.compactDisplay.collectAsState()
    val reduceAnimations by themeManager.reduceAnimations.collectAsState()

    val currentAccent = remember(accentId) { themeManager.getCurrentAccent() }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Appearance & Theme", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
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
                .background(BackgroundLight),
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 14.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            // ==========================================
            // SECTION 1: THEME MODE
            // ==========================================
            item {
                SectionHeader(title = "THEME MODE", icon = Icons.Default.BrightnessMedium, accent = currentAccent.color)
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            ThemeCard(
                                title = "Light",
                                subtitle = "Bright & clean",
                                isSelected = themeMode == "light",
                                previewColor = Color(0xFFF0F2F5),
                                accentColor = currentAccent.color,
                                modifier = Modifier.weight(1f),
                                onClick = { themeManager.setThemeMode("light") }
                            )

                            ThemeCard(
                                title = "Dark",
                                subtitle = "OLED Black",
                                isSelected = themeMode == "dark",
                                previewColor = Color(0xFF18191A),
                                accentColor = currentAccent.color,
                                modifier = Modifier.weight(1f),
                                onClick = { themeManager.setThemeMode("dark") }
                            )

                            ThemeCard(
                                title = "System",
                                subtitle = "Auto sync",
                                isSelected = themeMode == "system",
                                previewColor = Color(0xFF8B949E),
                                accentColor = currentAccent.color,
                                modifier = Modifier.weight(1f),
                                onClick = { themeManager.setThemeMode("system") }
                            )
                        }
                    }
                }
            }

            // ==========================================
            // SECTION 2: ACCENT COLOR
            // ==========================================
            item {
                SectionHeader(title = "ACCENT COLOR", icon = Icons.Default.Palette, accent = currentAccent.color)
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text(
                            text = "Active: ${currentAccent.name}",
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 14.sp,
                            color = TextPrimaryLight
                        )
                        Spacer(modifier = Modifier.height(12.dp))

                        LazyRow(
                            horizontalArrangement = Arrangement.spacedBy(14.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            items(AvailableAccents) { accent ->
                                val isSelected = accent.id == accentId
                                Box(
                                    modifier = Modifier
                                        .size(48.dp)
                                        .clip(CircleShape)
                                        .background(accent.color)
                                        .border(
                                            width = if (isSelected) 3.dp else 1.dp,
                                            color = if (isSelected) MaterialTheme.colorScheme.onSurface else Color.Transparent,
                                            shape = CircleShape
                                        )
                                        .clickable { themeManager.setAccentId(accent.id) },
                                    contentAlignment = Alignment.Center
                                ) {
                                    if (isSelected) {
                                        Icon(
                                            Icons.Default.Check,
                                            contentDescription = "Selected",
                                            tint = Color.White,
                                            modifier = Modifier.size(22.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // ==========================================
            // SECTION 3: FONT SIZE & TEXT SCALING
            // ==========================================
            item {
                SectionHeader(title = "FONT SIZE & TEXT SCALING", icon = Icons.Default.FormatSize, accent = currentAccent.color)
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Text Size", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                            val scaleLabel = when (fontScale) {
                                0.85f -> "Small (85%)"
                                1.15f -> "Large (115%)"
                                1.30f -> "Extra Large (130%)"
                                else -> "Default (100%)"
                            }
                            Text(scaleLabel, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = currentAccent.color)
                        }

                        Spacer(modifier = Modifier.height(10.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            listOf(
                                0.85f to "Small",
                                1.0f to "Default",
                                1.15f to "Large",
                                1.30f to "XL"
                            ).forEach { (scale, label) ->
                                val isSelected = fontScale == scale
                                Button(
                                    onClick = { themeManager.setFontScale(scale) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(8.dp),
                                    colors = ButtonDefaults.buttonColors(
                                        containerColor = if (isSelected) currentAccent.color else BackgroundLight,
                                        contentColor = if (isSelected) Color.White else TextPrimaryLight
                                    ),
                                    contentPadding = PaddingValues(vertical = 8.dp)
                                ) {
                                    Text(label, fontSize = 12.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal)
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Live Preview Box
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = BackgroundLight,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(12.dp)) {
                                Text(
                                    text = "Preview Text",
                                    fontSize = 11.sp,
                                    color = TextSecondaryLight,
                                    fontWeight = FontWeight.Bold
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = "Connectly helps you connect and share with the people in your life.",
                                    fontSize = (14 * fontScale).sp,
                                    color = TextPrimaryLight,
                                    lineHeight = (20 * fontScale).sp
                                )
                            }
                        }
                    }
                }
            }

            // ==========================================
            // SECTION 4: DISPLAY DENSITY & MOTION
            // ==========================================
            item {
                SectionHeader(title = "DISPLAY & ANIMATIONS", icon = Icons.Default.ViewCompact, accent = currentAccent.color)
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                        // Compact Mode
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { themeManager.setCompactDisplay(!compactDisplay) }
                                .padding(vertical = 10.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Compact Display", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                                Spacer(modifier = Modifier.height(2.dp))
                                Text("Reduces card padding and spacing to show more content on screen.", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Switch(
                                checked = compactDisplay,
                                onCheckedChange = { themeManager.setCompactDisplay(it) },
                                colors = SwitchDefaults.colors(
                                    checkedThumbColor = Color.White,
                                    checkedTrackColor = currentAccent.color
                                )
                            )
                        }

                        HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                        // Reduce Animations
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { themeManager.setReduceAnimations(!reduceAnimations) }
                                .padding(vertical = 10.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Reduce Animations", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                                Spacer(modifier = Modifier.height(2.dp))
                                Text("Minimizes motion and transitions for smoother rendering and battery saving.", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Switch(
                                checked = reduceAnimations,
                                onCheckedChange = { themeManager.setReduceAnimations(it) },
                                colors = SwitchDefaults.colors(
                                    checkedThumbColor = Color.White,
                                    checkedTrackColor = currentAccent.color
                                )
                            )
                        }
                    }
                }
            }

            item {
                Spacer(modifier = Modifier.height(30.dp))
            }
        }
    }
}

@Composable
private fun SectionHeader(title: String, icon: ImageVector, accent: Color) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp)) {
        Icon(icon, contentDescription = null, tint = accent, modifier = Modifier.size(16.dp))
        Spacer(modifier = Modifier.width(6.dp))
        Text(
            text = title,
            fontWeight = FontWeight.Bold,
            fontSize = 12.sp,
            color = TextSecondaryLight,
            letterSpacing = 0.5.sp
        )
    }
}

@Composable
private fun ThemeCard(
    title: String,
    subtitle: String,
    isSelected: Boolean,
    previewColor: Color,
    accentColor: Color,
    modifier: Modifier = Modifier,
    onClick: () -> Unit
) {
    Surface(
        modifier = modifier
            .clip(RoundedCornerShape(12.dp))
            .border(
                width = if (isSelected) 2.dp else 1.dp,
                color = if (isSelected) accentColor else BorderLight,
                shape = RoundedCornerShape(12.dp)
            )
            .clickable(onClick = onClick),
        color = BackgroundLight,
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(CircleShape)
                    .background(previewColor)
                    .border(1.dp, BorderLight, CircleShape),
                contentAlignment = Alignment.Center
            ) {
                if (isSelected) {
                    Icon(Icons.Default.Check, contentDescription = null, tint = accentColor, modifier = Modifier.size(18.dp))
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(title, fontWeight = FontWeight.Bold, fontSize = 13.sp, color = TextPrimaryLight)
            Text(subtitle, fontSize = 10.sp, color = TextSecondaryLight)
        }
    }
}

