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
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.outlined.AdminPanelSettings
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.network.api.PageRoleDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageTeamRolesScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    var showAssignDialog by remember { mutableStateOf(false) }
    var newMemberName by remember { mutableStateOf("") }
    var selectedRole by remember { mutableStateOf("editor") }

    val rolesList = remember {
        mutableStateListOf(
            PageRoleDto(id = 1, user_id = 101, user_name = "Page Owner", user_username = "owner_admin", role = "owner"),
            PageRoleDto(id = 2, user_id = 102, user_name = "Rahim Ahmed", user_username = "rahim_a", role = "editor"),
            PageRoleDto(id = 3, user_id = 103, user_name = "Nusrat Jahan", user_username = "nusrat_j", role = "moderator"),
            PageRoleDto(id = 4, user_id = 104, user_name = "Tanvir Hasan", user_username = "tanvir_h", role = "content_manager")
        )
    }

    if (showAssignDialog) {
        AlertDialog(
            onDismissRequest = { showAssignDialog = false },
            title = { Text("Assign Page Role", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = newMemberName,
                        onValueChange = { newMemberName = it },
                        label = { Text("User Name or @username") },
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
                            modifier = Modifier.fillMaxWidth().clickable { selectedRole = roleKey }
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
                        if (newMemberName.isNotBlank()) {
                            rolesList.add(
                                PageRoleDto(
                                    id = System.currentTimeMillis(),
                                    user_id = System.currentTimeMillis(),
                                    user_name = newMemberName,
                                    user_username = newMemberName.lowercase().replace(" ", "_"),
                                    role = selectedRole
                                )
                            )
                            newMemberName = ""
                            showAssignDialog = false
                        }
                    }
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
                containerColor = MaterialTheme.colorScheme.primary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Assign Role")
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            item {
                Text("Manage authorized page administrators, editors, and moderators.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }

            items(rolesList, key = { it.id }) { teamMember ->
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier.size(44.dp).clip(CircleShape).background(MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = teamMember.user_name.take(1).uppercase(),
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }

                        Spacer(modifier = Modifier.width(12.dp))

                        Column(modifier = Modifier.weight(1f)) {
                            Text(teamMember.user_name, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            Text("@${teamMember.user_username}", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }

                        Surface(
                            color = when (teamMember.role) {
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
                                color = when (teamMember.role) {
                                    "owner" -> Color(0xFFB45309)
                                    "admin" -> Color(0xFF1D4ED8)
                                    "editor" -> Color(0xFF15803D)
                                    else -> Color(0xFF6B21A8)
                                },
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                            )
                        }
                    }
                }
            }
        }
    }
}
