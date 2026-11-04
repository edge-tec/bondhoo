package com.connectly.android.feature.groups.presentation.content

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.outlined.Campaign
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.PushPin
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
import com.connectly.android.core.network.api.GroupAnnouncementDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupAnnouncementsScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupContentViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var showCreateDialog by remember { mutableStateOf(false) }

    LaunchedEffect(groupId) {
        viewModel.handleIntent(GroupContentIntent.LoadAnnouncements(groupId))
    }

    if (showCreateDialog) {
        var title by remember { mutableStateOf("") }
        var content by remember { mutableStateOf("") }
        var isPinned by remember { mutableStateOf(true) }

        AlertDialog(
            onDismissRequest = { showCreateDialog = false },
            title = { Text("Publish Announcement") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = title,
                        onValueChange = { title = it },
                        label = { Text("Announcement Title") },
                        modifier = Modifier.fillMaxWidth()
                    )
                    OutlinedTextField(
                        value = content,
                        onValueChange = { content = it },
                        label = { Text("Announcement Content") },
                        modifier = Modifier.fillMaxWidth().heightIn(min = 100.dp)
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Pin to top of group")
                        Switch(checked = isPinned, onCheckedChange = { isPinned = it })
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(
                            GroupContentIntent.CreateAnnouncement(
                                title = title,
                                content = content,
                                isPinned = isPinned,
                                expiresAt = null
                            )
                        )
                        showCreateDialog = false
                    },
                    enabled = title.isNotBlank() && content.isNotBlank(),
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Publish")
                }
            },
            dismissButton = {
                TextButton(onClick = { showCreateDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Group Announcements", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { showCreateDialog = true }) {
                        Icon(Icons.Default.Add, contentDescription = "New Announcement", tint = ConnectlyBlue)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = { showCreateDialog = true },
                containerColor = ConnectlyBlue,
                contentColor = Color.White
            ) {
                Icon(Icons.Default.Add, contentDescription = "New Announcement")
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            state.actionMessage?.let { msg ->
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFDCFCE7))) {
                        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.CheckCircle, contentDescription = null, tint = Color(0xFF16A34A))
                            Spacer(Modifier.width(8.dp))
                            Text(msg, color = Color(0xFF166534), fontSize = 13.sp)
                        }
                    }
                }
            }

            if (state.announcements.isEmpty()) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(48.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(Icons.Outlined.Campaign, contentDescription = null, modifier = Modifier.size(56.dp), tint = ConnectlyBlue)
                        Spacer(Modifier.height(12.dp))
                        Text("No announcements yet", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text("Admins can post official updates for all members here.", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                }
            } else {
                items(state.announcements, key = { it.id }) { ann ->
                    AnnouncementCard(announcement = ann)
                }
            }
        }
    }
}

@Composable
private fun AnnouncementCard(announcement: GroupAnnouncementDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Outlined.PushPin, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Official Announcement", color = ConnectlyBlue, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
                if (announcement.created_at != null) {
                    Text(announcement.created_at.orEmpty(), fontSize = 11.sp, color = TextSecondaryLight)
                }
            }

            Text(announcement.title, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = TextPrimaryLight)
            Text(announcement.content, fontSize = 14.sp, color = TextSecondaryLight)

            if (!announcement.cta_text.isNullOrBlank()) {
                Spacer(Modifier.height(4.dp))
                Button(
                    onClick = { /* Handle CTA */ },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(announcement.cta_text.orEmpty())
                }
            }
        }
    }
}

