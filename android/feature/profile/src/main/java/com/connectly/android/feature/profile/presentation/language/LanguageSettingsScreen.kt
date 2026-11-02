package com.connectly.android.feature.profile.presentation.language

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.*
import kotlinx.coroutines.launch

data class LanguageItem(
    val code: String,
    val nativeName: String,
    val englishName: String,
    val region: String,
    val isTranslated: Boolean = true
)

val SupportedLanguages = listOf(
    LanguageItem("en", "English", "English", "United States / International"),
    LanguageItem("bn", "বাংলা", "Bengali", "বাংলাদেশ (Bangladesh)")
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LanguageSettingsScreen(
    themeManager: ThemeManager,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val activeLanguage by themeManager.language.collectAsState()
    val accentId by themeManager.accentId.collectAsState()
    val currentAccent = remember(accentId) { themeManager.getCurrentAccent() }
    val scope = rememberCoroutineScope()
    val snackbarHostState = remember { SnackbarHostState() }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Language & Region", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
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
            // Info Header Card
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = currentAccent.color.copy(alpha = 0.08f)),
                    elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.Translate, contentDescription = null, tint = currentAccent.color, modifier = Modifier.size(24.dp))
                        Spacer(modifier = Modifier.width(12.dp))
                        Column {
                            Text("Official Language Support", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = TextPrimaryLight)
                            Spacer(modifier = Modifier.height(2.dp))
                            Text(
                                "Connectly supports fully verified translations. Selecting a language applies immediately to navigation, buttons, and notifications.",
                                fontSize = 12.sp,
                                color = TextSecondaryLight,
                                lineHeight = 16.sp
                            )
                        }
                    }
                }
            }

            // Supported Languages List
            item {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                    Icon(Icons.Default.Language, contentDescription = null, tint = currentAccent.color, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "SELECT LANGUAGE",
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        letterSpacing = 0.5.sp
                    )
                }
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                ) {
                    Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                        SupportedLanguages.forEachIndexed { index, lang ->
                            val isSelected = activeLanguage == lang.code

                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable {
                                        themeManager.setLanguage(lang.code)
                                        scope.launch {
                                            snackbarHostState.showSnackbar("Language changed to ${lang.nativeName}")
                                        }
                                    }
                                    .padding(vertical = 12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(40.dp)
                                        .clip(CircleShape)
                                        .background(if (isSelected) currentAccent.color.copy(alpha = 0.12f) else BorderLight),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = lang.code.uppercase(),
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 13.sp,
                                        color = if (isSelected) currentAccent.color else TextSecondaryLight
                                    )
                                }

                                Spacer(modifier = Modifier.width(14.dp))

                                Column(modifier = Modifier.weight(1f)) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Text(
                                            text = lang.nativeName,
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp,
                                            color = TextPrimaryLight
                                        )
                                        if (lang.nativeName != lang.englishName) {
                                            Spacer(modifier = Modifier.width(6.dp))
                                            Text(
                                                text = "(${lang.englishName})",
                                                fontSize = 13.sp,
                                                color = TextSecondaryLight
                                            )
                                        }
                                    }
                                    Spacer(modifier = Modifier.height(2.dp))
                                    Text(text = lang.region, fontSize = 12.sp, color = TextMutedLight)
                                }

                                RadioButton(
                                    selected = isSelected,
                                    onClick = {
                                        themeManager.setLanguage(lang.code)
                                        scope.launch {
                                            snackbarHostState.showSnackbar("Language changed to ${lang.nativeName}")
                                        }
                                    },
                                    colors = RadioButtonDefaults.colors(selectedColor = currentAccent.color)
                                )
                            }

                            if (index < SupportedLanguages.size - 1) {
                                HorizontalDivider(color = BorderLight)
                            }
                        }
                    }
                }
            }

            // Region & Date Preview Card
            item {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                    Icon(Icons.Default.DateRange, contentDescription = null, tint = currentAccent.color, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "REGIONAL FORMATTING PREVIEW",
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        letterSpacing = 0.5.sp
                    )
                }
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
                            Text("Current Date Preview", fontSize = 13.sp, color = TextSecondaryLight)
                            Text(
                                text = if (activeLanguage == "bn") "মঙ্গলবার, ৩ নভেম্বর ২০২৬" else "Tuesday, November 3, 2026",
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp,
                                color = TextPrimaryLight
                            )
                        }

                        Spacer(modifier = Modifier.height(10.dp))
                        HorizontalDivider(color = BorderLight)
                        Spacer(modifier = Modifier.height(10.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Numerals & Numbers", fontSize = 13.sp, color = TextSecondaryLight)
                            Text(
                                text = if (activeLanguage == "bn") "১২,৪৫০ লাইক • ৩,১০০ কমেন্ট" else "12,450 likes • 3,100 comments",
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp,
                                color = currentAccent.color
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

