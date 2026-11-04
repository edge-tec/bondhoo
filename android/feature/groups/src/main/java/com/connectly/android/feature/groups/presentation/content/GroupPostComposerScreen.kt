package com.connectly.android.feature.groups.presentation.content

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupPostComposerScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupContentViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var showScheduleDialog by remember { mutableStateOf(false) }

    LaunchedEffect(state.actionMessage) {
        if (state.actionMessage != null) {
            onNavigateBack()
        }
    }

    if (showScheduleDialog) {
        var daysAhead by remember { mutableStateOf(1) }
        AlertDialog(
            onDismissRequest = { showScheduleDialog = false },
            title = { Text("Schedule Post") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Select when this post should automatically publish to the group:")
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        listOf(1, 2, 3, 7).forEach { days ->
                            FilterChip(
                                selected = daysAhead == days,
                                onClick = { daysAhead = days },
                                label = { Text("+$days d") }
                            )
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val scheduleStr = java.time.ZonedDateTime.now().plusDays(daysAhead.toLong()).toString()
                        viewModel.handleIntent(GroupContentIntent.UpdateScheduleTime(scheduleStr))
                        showScheduleDialog = false
                    }
                ) {
                    Text("Schedule")
                }
            },
            dismissButton = {
                TextButton(onClick = { showScheduleDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Create Group Post", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    TextButton(
                        onClick = { viewModel.handleIntent(GroupContentIntent.SubmitPost(isDraft = true)) },
                        enabled = !state.isSubmitting && state.composerContent.isNotBlank()
                    ) {
                        Text("Save Draft", color = TextSecondaryLight)
                    }
                    Button(
                        onClick = { viewModel.handleIntent(GroupContentIntent.SubmitPost(isDraft = false)) },
                        enabled = !state.isSubmitting && (state.composerContent.isNotBlank() || state.composerPollQuestion.isNotBlank()),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        if (state.isSubmitting) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(16.dp))
                        } else {
                            Text("Post")
                        }
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Error banner
            state.errorMessage?.let { err ->
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)) {
                        Text(err, color = MaterialTheme.colorScheme.onErrorContainer, modifier = Modifier.padding(12.dp))
                    }
                }
            }

            // Type selector pills
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = state.composerType == "text",
                        onClick = { viewModel.handleIntent(GroupContentIntent.UpdateComposerType("text")) },
                        label = { Text("Discussion") },
                        leadingIcon = { Icon(Icons.Outlined.Chat, contentDescription = null, modifier = Modifier.size(16.dp)) }
                    )
                    FilterChip(
                        selected = state.composerType == "media",
                        onClick = { viewModel.handleIntent(GroupContentIntent.UpdateComposerType("media")) },
                        label = { Text("Photo/Video") },
                        leadingIcon = { Icon(Icons.Outlined.Image, contentDescription = null, modifier = Modifier.size(16.dp)) }
                    )
                    FilterChip(
                        selected = state.composerType == "poll",
                        onClick = { viewModel.handleIntent(GroupContentIntent.UpdateComposerType("poll")) },
                        label = { Text("Poll") },
                        leadingIcon = { Icon(Icons.Outlined.Poll, contentDescription = null, modifier = Modifier.size(16.dp)) }
                    )
                }
            }

            // Post Content Text Area
            item {
                OutlinedTextField(
                    value = state.composerContent,
                    onValueChange = { viewModel.handleIntent(GroupContentIntent.UpdateComposerContent(it)) },
                    placeholder = { Text("What's on your mind? Share professional insights, questions, or updates...") },
                    modifier = Modifier.fillMaxWidth().heightIn(min = 140.dp),
                    shape = RoundedCornerShape(12.dp)
                )
            }

            // Poll Creator section
            if (state.composerType == "poll") {
                item {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                            Text("Create a Community Poll", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            OutlinedTextField(
                                value = state.composerPollQuestion,
                                onValueChange = { viewModel.handleIntent(GroupContentIntent.UpdatePollQuestion(it)) },
                                placeholder = { Text("Ask a question...") },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            )

                            state.composerPollOptions.forEachIndexed { idx, opt ->
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    OutlinedTextField(
                                        value = opt,
                                        onValueChange = { viewModel.handleIntent(GroupContentIntent.UpdatePollOption(idx, it)) },
                                        placeholder = { Text("Option ${idx + 1}") },
                                        modifier = Modifier.weight(1f),
                                        shape = RoundedCornerShape(8.dp)
                                    )
                                    if (state.composerPollOptions.size > 2) {
                                        IconButton(onClick = { viewModel.handleIntent(GroupContentIntent.RemovePollOption(idx)) }) {
                                            Icon(Icons.Default.Close, contentDescription = "Remove")
                                        }
                                    }
                                }
                            }

                            if (state.composerPollOptions.size < 6) {
                                TextButton(onClick = { viewModel.handleIntent(GroupContentIntent.AddPollOption) }) {
                                    Icon(Icons.Default.Add, contentDescription = null)
                                    Spacer(Modifier.width(4.dp))
                                    Text("Add Option")
                                }
                            }
                        }
                    }
                }
            }

            // Post Options / Tooling Bar
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Outlined.PushPin, contentDescription = null, tint = ConnectlyBlue)
                                Spacer(Modifier.width(8.dp))
                                Text("Pin to featured", fontSize = 14.sp)
                            }
                            Switch(
                                checked = state.composerIsPinned,
                                onCheckedChange = { viewModel.handleIntent(GroupContentIntent.ToggleComposerPin(it)) }
                            )
                        }

                        HorizontalDivider()

                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { showScheduleDialog = true }
                                .padding(vertical = 4.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Outlined.Schedule, contentDescription = null, tint = ConnectlyBlue)
                                Spacer(Modifier.width(8.dp))
                                Column {
                                    Text("Schedule publishing", fontSize = 14.sp)
                                    state.composerScheduledAt?.let {
                                        Text("Scheduled: $it", fontSize = 11.sp, color = ConnectlyBlue)
                                    }
                                }
                            }
                            Icon(Icons.Default.ChevronRight, contentDescription = null, tint = TextSecondaryLight)
                        }
                    }
                }
            }
        }
    }
}

