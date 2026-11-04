package com.connectly.android.feature.groups.presentation.pages.settings

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.outlined.Block
import androidx.compose.material.icons.outlined.Lock
import androidx.compose.material.icons.outlined.Security
import androidx.compose.material.icons.outlined.VerifiedUser
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageSettingsScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    var isPublic by remember { mutableStateOf(true) }
    var allowReviews by remember { mutableStateOf(true) }
    var allowMessages by remember { mutableStateOf(true) }
    var commentModeration by remember { mutableStateOf(false) }
    var showDeleteConfirmDialog by remember { mutableStateOf(false) }

    if (showDeleteConfirmDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteConfirmDialog = false },
            title = { Text("Delete Business Page?", fontWeight = FontWeight.Bold) },
            text = { Text("Deleting this Page will permanently remove all posts, analytics, team roles, and settings. This action cannot be undone.") },
            confirmButton = {
                Button(
                    onClick = {
                        showDeleteConfirmDialog = false
                        onNavigateBack()
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Confirm Delete")
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteConfirmDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Settings & Controls", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            item {
                Text("Page Visibility & Privacy", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            item {
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column {
                                Text("Public Page Visibility", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Allow anyone to discover and follow this Page", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = isPublic, onCheckedChange = { isPublic = it })
                        }
                        HorizontalDivider()
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column {
                                Text("Allow Customer Reviews", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Display ratings and written reviews on profile", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = allowReviews, onCheckedChange = { allowReviews = it })
                        }
                        HorizontalDivider()
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column {
                                Text("Enable Direct Messaging", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Allow visitors to message page inbox", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = allowMessages, onCheckedChange = { allowMessages = it })
                        }
                        HorizontalDivider()
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column {
                                Text("Comment Moderation Filter", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Filter out spam and offensive keywords automatically", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = commentModeration, onCheckedChange = { commentModeration = it })
                        }
                    }
                }
            }

            item {
                Text("Verification & Danger Zone", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            }

            item {
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.VerifiedUser, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                            Spacer(modifier = Modifier.width(10.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Request Page Verification", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text("Apply for a verified checkmark badge for authentic business pages", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            OutlinedButton(onClick = { }) {
                                Text("Apply", fontSize = 12.sp)
                            }
                        }

                        HorizontalDivider()

                        Button(
                            onClick = { showDeleteConfirmDialog = true },
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Delete Business Page", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}
