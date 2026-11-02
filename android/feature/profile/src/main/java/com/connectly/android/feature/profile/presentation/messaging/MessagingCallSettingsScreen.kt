package com.connectly.android.feature.profile.presentation.messaging

import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MessagingCallSettingsScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: MessagingCallSettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { isGranted ->
        viewModel.handleIntent(MessagingCallSettingsIntent.UpdateCameraPermission(isGranted))
    }

    val micPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { isGranted ->
        viewModel.handleIntent(MessagingCallSettingsIntent.UpdateMicPermission(isGranted))
    }

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(MessagingCallSettingsIntent.DismissMessages)
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(MessagingCallSettingsIntent.DismissMessages)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Messaging & Calls", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (state.isSaving) {
                        CircularProgressIndicator(
                            modifier = Modifier
                                .size(24.dp)
                                .padding(end = 16.dp),
                            color = ConnectlyBlue,
                            strokeWidth = 2.dp
                        )
                    } else {
                        TextButton(onClick = { viewModel.handleIntent(MessagingCallSettingsIntent.SaveAllSettings) }) {
                            Text("Save", fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        if (state.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .background(BackgroundLight),
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 14.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                // ==========================================
                // SECTION 1: MESSAGING PREFERENCES
                // ==========================================
                item {
                    SectionHeader(title = "CHATS & MESSAGING", icon = Icons.Default.Chat)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Who can message
                            OptionSelector(
                                title = "Who Can Message You",
                                subtitle = "Control who can initiate new direct conversations with you.",
                                currentValue = state.whoCanMessage,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends Only"
                                ),
                                onSelect = { viewModel.handleIntent(MessagingCallSettingsIntent.WhoCanMessageChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Message Requests
                            SwitchRow(
                                title = "Message Requests",
                                subtitle = "Filter messages from people you aren't connected with into a separate requests folder.",
                                checked = state.messageRequestsEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.MessageRequestsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Read Receipts
                            SwitchRow(
                                title = "Read Receipts",
                                subtitle = "Allow people to see when you have read their messages.",
                                checked = state.readReceiptsEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.ReadReceiptsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Typing Indicators
                            SwitchRow(
                                title = "Typing Indicators",
                                subtitle = "Show typing status in chat when composing messages.",
                                checked = state.typingIndicatorsEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.TypingIndicatorsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Active Status
                            SwitchRow(
                                title = "Active Status",
                                subtitle = "Show when you are currently online and available to chat.",
                                checked = state.activeStatusEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.ActiveStatusToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 2: CHAT SOUNDS & ALERTS
                // ==========================================
                item {
                    SectionHeader(title = "CHAT SOUNDS & TONES", icon = Icons.Default.MusicNote)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            SwitchRow(
                                title = "In-App Messenger Sounds",
                                subtitle = "Play sounds for incoming and outgoing chat messages.",
                                checked = state.messengerSoundsEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.MessengerSoundsToggled(it)) }
                            )

                            if (state.messengerSoundsEnabled) {
                                HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                                OptionSelector(
                                    title = "Incoming Message Tone",
                                    subtitle = "Sound played when a friend sends you a message.",
                                    currentValue = state.incomingMessageSound,
                                    options = listOf(
                                        "bubble_pop" to "Bubble Pop (Default)",
                                        "clear_ding" to "Clear Ding",
                                        "soft_chime" to "Soft Chime"
                                    ),
                                    onSelect = { viewModel.handleIntent(MessagingCallSettingsIntent.IncomingMessageSoundChanged(it)) }
                                )

                                HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                                OptionSelector(
                                    title = "Outgoing Message Sound",
                                    subtitle = "Audible confirmation when your message is sent.",
                                    currentValue = state.outgoingMessageSound,
                                    options = listOf(
                                        "subtle_sent" to "Subtle Sent",
                                        "soft_swoosh" to "Soft Swoosh",
                                        "quick_click" to "Quick Click"
                                    ),
                                    onSelect = { viewModel.handleIntent(MessagingCallSettingsIntent.OutgoingMessageSoundChanged(it)) }
                                )
                            }
                        }
                    }
                }

                // ==========================================
                // SECTION 3: VOICE & VIDEO CALLS
                // ==========================================
                item {
                    SectionHeader(title = "VOICE & VIDEO CALLS", icon = Icons.Default.Call)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Incoming Voice Calls
                            SwitchRow(
                                title = "Incoming Voice Calls",
                                subtitle = "Allow incoming high-definition voice calls over WebRTC.",
                                checked = state.incomingVoiceCallNotification,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.IncomingVoiceCallToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Incoming Video Calls
                            SwitchRow(
                                title = "Incoming Video Calls",
                                subtitle = "Allow incoming high-definition video calls with camera.",
                                checked = state.incomingVideoCallNotification,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.IncomingVideoCallToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Call Privacy
                            OptionSelector(
                                title = "Who Can Call You",
                                subtitle = "Limit who is permitted to start voice and video calls with you.",
                                currentValue = state.callPrivacy,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends Only"
                                ),
                                onSelect = { viewModel.handleIntent(MessagingCallSettingsIntent.CallPrivacyChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Call Sound
                            OptionSelector(
                                title = "Call Ringtone",
                                subtitle = "Ringtone played during incoming voice & video calls.",
                                currentValue = state.incomingCallSound,
                                options = listOf(
                                    "bondhoo_ring" to "Bondhoo Classic Ring",
                                    "digital_bell" to "Digital Bell",
                                    "celesta_chime" to "Celesta Chime"
                                ),
                                onSelect = { viewModel.handleIntent(MessagingCallSettingsIntent.IncomingCallSoundChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Vibration
                            SwitchRow(
                                title = "Vibrate on Calls",
                                subtitle = "Vibrate phone when an incoming call arrives.",
                                checked = state.callVibrationEnabled,
                                onCheckedChange = { viewModel.handleIntent(MessagingCallSettingsIntent.CallVibrationToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 4: HARDWARE & RUNTIME PERMISSIONS
                // ==========================================
                item {
                    SectionHeader(title = "DEVICE & HARDWARE PERMISSIONS", icon = Icons.Default.Security)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Camera Permission
                            PermissionStatusRow(
                                title = "Camera Permission",
                                subtitle = "Required for two-way WebRTC video calls and video previews.",
                                isGranted = state.cameraPermissionGranted,
                                onRequestPermission = { cameraPermissionLauncher.launch(Manifest.permission.CAMERA) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Microphone Permission
                            PermissionStatusRow(
                                title = "Microphone Permission",
                                subtitle = "Required for high-fidelity voice transmission in calls and voice notes.",
                                isGranted = state.micPermissionGranted,
                                onRequestPermission = { micPermissionLauncher.launch(Manifest.permission.RECORD_AUDIO) }
                            )
                        }
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(30.dp))
                }
            }
        }
    }
}

@Composable
private fun SectionHeader(title: String, icon: ImageVector) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp)) {
        Icon(icon, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
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
private fun SwitchRow(
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onCheckedChange(!checked) }
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight, lineHeight = 16.sp)
        }
        Spacer(modifier = Modifier.width(12.dp))
        Switch(
            checked = checked,
            onCheckedChange = onCheckedChange,
            colors = SwitchDefaults.colors(
                checkedThumbColor = Color.White,
                checkedTrackColor = ConnectlyBlue
            )
        )
    }
}

@Composable
private fun PermissionStatusRow(
    title: String,
    subtitle: String,
    isGranted: Boolean,
    onRequestPermission: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                Spacer(modifier = Modifier.width(8.dp))
                Surface(
                    shape = RoundedCornerShape(4.dp),
                    color = if (isGranted) ConnectlyGreen.copy(alpha = 0.12f) else ConnectlyRed.copy(alpha = 0.12f)
                ) {
                    Text(
                        text = if (isGranted) "Granted" else "Denied",
                        color = if (isGranted) ConnectlyGreen else ConnectlyRed,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }
            }
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight, lineHeight = 16.sp)
        }

        if (!isGranted) {
            Spacer(modifier = Modifier.width(10.dp))
            Button(
                onClick = onRequestPermission,
                shape = RoundedCornerShape(8.dp),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
            ) {
                Text("Grant", fontSize = 12.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
private fun OptionSelector(
    title: String,
    subtitle: String,
    currentValue: String,
    options: List<Pair<String, String>>,
    onSelect: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }
    val displayLabel = options.firstOrNull { it.first == currentValue }?.second ?: currentValue

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { expanded = !expanded }
            .padding(vertical = 10.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                Spacer(modifier = Modifier.height(2.dp))
                Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight, lineHeight = 16.sp)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Surface(
                shape = RoundedCornerShape(8.dp),
                color = ConnectlyBlue.copy(alpha = 0.10f)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(text = displayLabel, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    Spacer(modifier = Modifier.width(4.dp))
                    Icon(
                        imageVector = if (expanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                        contentDescription = null,
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(16.dp)
                    )
                }
            }
        }

        if (expanded) {
            Spacer(modifier = Modifier.height(8.dp))
            Surface(
                shape = RoundedCornerShape(12.dp),
                color = BackgroundLight,
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    options.forEach { (key, label) ->
                        val isSelected = key == currentValue
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .background(if (isSelected) ConnectlyBlue.copy(alpha = 0.15f) else Color.Transparent)
                                .clickable {
                                    onSelect(key)
                                    expanded = false
                                }
                                .padding(horizontal = 12.dp, vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = isSelected,
                                onClick = {
                                    onSelect(key)
                                    expanded = false
                                },
                                colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                text = label,
                                fontSize = 13.sp,
                                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                color = if (isSelected) ConnectlyBlue else TextPrimaryLight
                            )
                        }
                    }
                }
            }
        }
    }
}

