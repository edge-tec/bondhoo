package com.connectly.android.feature.groups.presentation.pages.roles

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
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
import com.connectly.android.core.designsystem.theme.ConnectlyElectricBlue

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageTeamRolesScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageTeamRolesViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var showAssignDialog by remember { mutableStateOf(false) }
    var newUserId by remember { mutableStateOf("") }
    var selectedRole by remember { mutableStateOf("editor") }

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.loadRoles(pageId)
        }
    }

    if (showAssignDialog) {
        AlertDialog(
            onDismissRequest = { showAssignDialog = false },
            title = { Text("Assign Page Role", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = newUserId,
                        onValueChange = { newUserId = it },
                        label = { Text("User ID (e.g. 2)") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )

                    Text("Select Role:", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                    val roles = listOf(
                        "admin" to "Admin (Full Page Management)",
                        "editor" to "Editor (Create & Edit Content)",
                        "moderator" to "Moderator (Manage Comments & Messages)",
                        "content_manager" to "Content Manager (Publish Posts & Reels)",
                        "analyst" to "Analyst (View Insights Only)"
                    )
                    roles.forEach { (roleKey, roleLabel) ->
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { selectedRole = roleKey }
                        ) {
                            RadioButton(
                                selected = selectedRole == roleKey,
                                onClick = { selectedRole = roleKey }
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(roleLabel, fontSize = 12.sp)
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val parsedId = newUserId.trim().toLongOrNull()
                        if (parsedId != null && parsedId > 0L) {
                            viewModel.assignRole(parsedId, selectedRole)
                            newUserId = ""
                            showAssignDialog = false
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                ) {
                    Text("Add Member")
                }
            },
            dismissButton = {
                TextButton(onClick = { showAssignDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Team & Roles", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = { showAssignDialog = true },
                containerColor = ConnectlyElectricBlue,
                contentColor = Color.White
            ) {
                Icon(Icons.Default.Add, contentDescription = "Assign Role")
            }
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
                CircularProgressIndicator(color = ConnectlyElectricBlue)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                item {
                    Text(
                        "Manage authorized page administrators, editors, and moderators with granular RBAC permissions.",
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                if (state.roles.isEmpty()) {
                    item {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 40.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "No team members assigned yet.",
                                fontSize = 14.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                } else {
                    items(state.roles, key = { it.id }) { teamMember ->
                        val displayName = if (teamMember.user_name.isNotBlank()) teamMember.user_name else "Team Member"
                        val displayUsername = if (teamMember.user_username.isNotBlank()) teamMember.user_username else "member_${teamMember.user_id}"

                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Row(
                                modifier = Modifier.padding(14.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(44.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyElectricBlue.copy(alpha = 0.12f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = displayName.take(1).uppercase(),
                                        fontWeight = FontWeight.Bold,
                                        color = ConnectlyElectricBlue
                                    )
                                }

                                Spacer(modifier = Modifier.width(12.dp))

                                Column(modifier = Modifier.weight(1f)) {
                                    Text(displayName, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                    Text("@$displayUsername", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }

                                Surface(
                                    color = when (teamMember.role.lowercase()) {
                                        "owner" -> Color(0xFFFEF3C7)
                                        "admin" -> Color(0xFFDBEAFE)
                                        "editor" -> Color(0xFFDCFCE7)
                                        else -> Color(0xFFF3E8FF)
                                    },
                                    shape = RoundedCornerShape(6.dp)
                                ) {
                                    Text(
                                        text = teamMember.role.uppercase().replace("_", " "),
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = when (teamMember.role.lowercase()) {
                                            "owner" -> Color(0xFFB45309)
                                            "admin" -> Color(0xFF1D4ED8)
                                            "editor" -> Color(0xFF15803D)
                                            else -> Color(0xFF6B21A8)
                                        },
                                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                    )
                                }

                                if (teamMember.role.lowercase() != "owner") {
                                    IconButton(onClick = { viewModel.removeRole(teamMember.id) }) {
                                        Icon(
                                            Icons.Default.Delete,
                                            contentDescription = "Remove",
                                            tint = MaterialTheme.colorScheme.error
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
