package com.connectly.android.feature.groups.presentation.roles

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.network.api.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupRolesScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupRolesViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(groupId) {
        if (groupId > 0 && state.groupId != groupId) {
            viewModel.handleIntent(GroupRolesIntent.LoadRoles(groupId))
        }
    }

    val snackbarHostState = remember { SnackbarHostState() }
    LaunchedEffect(state.errorMessage, state.successMessage) {
        state.errorMessage?.let { snackbarHostState.showSnackbar(it) }
        state.successMessage?.let { snackbarHostState.showSnackbar(it) }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(
                            text = "Roles & Permissions",
                            fontWeight = FontWeight.Bold,
                            fontSize = 18.sp
                        )
                        Text(
                            text = "Role-Based Access Control (RBAC)",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (state.canManageRoles) {
                        IconButton(onClick = { viewModel.handleIntent(GroupRolesIntent.ShowCreateDialog(true)) }) {
                            Icon(Icons.Default.Add, contentDescription = "Add Custom Role")
                        }
                    }
                    IconButton(onClick = { viewModel.handleIntent(GroupRolesIntent.Refresh) }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
        }
    ) { innerPadding ->
        if (state.isLoading && state.defaultRoles.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else {
            Column(
                modifier = modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .background(BackgroundLight)
            ) {
                // Tab Row (0: Roles Catalog, 1: Audit History)
                TabRow(
                    selectedTabIndex = state.selectedTab,
                    containerColor = MaterialTheme.colorScheme.surface,
                    contentColor = ConnectlyBlue
                ) {
                    Tab(
                        selected = state.selectedTab == 0,
                        onClick = { viewModel.handleIntent(GroupRolesIntent.SelectTab(0)) },
                        text = { Text("Roles Catalog", fontWeight = FontWeight.SemiBold, fontSize = 13.sp) }
                    )
                    Tab(
                        selected = state.selectedTab == 1,
                        onClick = { viewModel.handleIntent(GroupRolesIntent.SelectTab(1)) },
                        text = { Text("Role History & Audit", fontWeight = FontWeight.SemiBold, fontSize = 13.sp) }
                    )
                }

                when (state.selectedTab) {
                    0 -> {
                        // Roles Catalog
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            // Section 1: Default System Roles
                            item {
                                Text("System Default Roles", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
                            }

                            items(state.defaultRoles) { role ->
                                RoleCard(
                                    role = role,
                                    canManage = state.canManageRoles,
                                    onAssignClick = { viewModel.handleIntent(GroupRolesIntent.ShowAssignDialog(true, role.name)) }
                                )
                            }

                            // Section 2: Custom Roles
                            item {
                                Spacer(modifier = Modifier.height(8.dp))
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text("Custom Roles", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
                                    if (state.canManageRoles) {
                                        TextButton(onClick = { viewModel.handleIntent(GroupRolesIntent.ShowCreateDialog(true)) }) {
                                            Text("+ Create Role", fontSize = 12.sp, color = ConnectlyBlue)
                                        }
                                    }
                                }
                            }

                            if (state.customRoles.isEmpty()) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        shape = RoundedCornerShape(10.dp),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                                    ) {
                                        Box(modifier = Modifier.padding(20.dp), contentAlignment = Alignment.Center) {
                                            Text("No custom roles created yet. Create custom roles with tailored permissions.", fontSize = 12.sp, color = TextSecondaryLight)
                                        }
                                    }
                                }
                            } else {
                                items(state.customRoles) { role ->
                                    RoleCard(
                                        role = role,
                                        canManage = state.canManageRoles,
                                        onEditClick = { viewModel.handleIntent(GroupRolesIntent.EditRole(role)) },
                                        onDeleteClick = { role.id?.let { viewModel.handleIntent(GroupRolesIntent.DeleteRole(it)) } },
                                        onAssignClick = { viewModel.handleIntent(GroupRolesIntent.ShowAssignDialog(true, role.name)) }
                                    )
                                }
                            }
                        }
                    }

                    1 -> {
                        // Role History & Audit
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            item {
                                Text("Role Assignment Audit Trail", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
                            }

                            if (state.roleHistory.isEmpty()) {
                                item {
                                    Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                        Text("No role changes recorded yet.", color = TextSecondaryLight, fontSize = 13.sp)
                                    }
                                }
                            } else {
                                items(state.roleHistory) { item ->
                                    RoleHistoryCard(item = item)
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if (state.showCreateRoleDialog) {
        CreateOrEditRoleDialog(
            existingRole = state.editingRole,
            availablePermissions = state.availablePermissions,
            onDismiss = { viewModel.handleIntent(GroupRolesIntent.ShowCreateDialog(false)) },
            onSubmit = { name, displayName, desc, color, perms ->
                if (state.editingRole != null && state.editingRole!!.id != null) {
                    viewModel.handleIntent(GroupRolesIntent.UpdateRole(state.editingRole!!.id!!, displayName, desc, color, perms))
                } else {
                    viewModel.handleIntent(GroupRolesIntent.CreateRole(name, displayName, desc, color, perms))
                }
            }
        )
    }

    if (state.showAssignRoleDialog) {
        AssignRoleDialog(
            targetRole = state.targetRoleForAssignment ?: "member",
            onDismiss = { viewModel.handleIntent(GroupRolesIntent.ShowAssignDialog(false)) },
            onSubmit = { role, userIds ->
                viewModel.handleIntent(GroupRolesIntent.AssignRole(role, userIds))
            }
        )
    }
}

@Composable
private fun RoleCard(
    role: GroupRoleDto,
    canManage: Boolean,
    onEditClick: (() -> Unit)? = null,
    onDeleteClick: (() -> Unit)? = null,
    onAssignClick: () -> Unit
) {
    val roleColor = try {
        Color(android.graphics.Color.parseColor(role.color))
    } catch (e: Exception) {
        ConnectlyBlue
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(modifier = Modifier.size(10.dp).clip(CircleShape).background(roleColor))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(role.display_name, fontWeight = FontWeight.Bold, fontSize = 15.sp, color = TextPrimaryLight)
                }

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = Color(0xFFF1F5F9)
                ) {
                    Text(
                        "${role.members_count} member(s)",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = ConnectlyBlue,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                    )
                }
            }

            val description = role.description
            if (!description.isNullOrBlank()) {
                Text(description, fontSize = 12.sp, color = TextSecondaryLight)
            }

            // Permissions Preview Chips
            if (role.permissions.isNotEmpty()) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 4.dp),
                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    role.permissions.take(3).forEach { perm ->
                        Surface(shape = RoundedCornerShape(6.dp), color = Color(0xFFEFF6FF)) {
                            Text(
                                text = perm.replace("_", " "),
                                fontSize = 10.sp,
                                color = ConnectlyBlue,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                    if (role.permissions.size > 3) {
                        Surface(shape = RoundedCornerShape(6.dp), color = Color(0xFFF1F5F9)) {
                            Text(
                                text = "+${role.permissions.size - 3} more",
                                fontSize = 10.sp,
                                color = TextSecondaryLight,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                }
            }

            // Actions row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.End,
                verticalAlignment = Alignment.CenterVertically
            ) {
                if (canManage && !role.is_system && onEditClick != null) {
                    TextButton(onClick = onEditClick) {
                        Text("Edit", fontSize = 12.sp, color = ConnectlyBlue)
                    }
                }
                if (canManage && !role.is_system && onDeleteClick != null) {
                    TextButton(onClick = onDeleteClick) {
                        Text("Delete", fontSize = 12.sp, color = Color(0xFFEF4444))
                    }
                }
                if (canManage) {
                    OutlinedButton(
                        onClick = onAssignClick,
                        shape = RoundedCornerShape(8.dp),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
                        modifier = Modifier.height(32.dp)
                    ) {
                        Icon(Icons.Outlined.PersonAdd, contentDescription = null, modifier = Modifier.size(14.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Assign Members", fontSize = 11.sp)
                    }
                }
            }
        }
    }
}

@Composable
private fun RoleHistoryCard(item: RoleHistoryItemDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = item.action.replace("_", " ").uppercase(),
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = ConnectlyBlue
                )
                Text(item.created_at?.take(10) ?: "", fontSize = 10.sp, color = TextSecondaryLight)
            }
            val reason = item.reason
            if (!reason.isNullOrBlank()) {
                Text(reason, fontSize = 12.sp, color = TextPrimaryLight)
            }
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                item.moderator?.name?.let {
                    Text("By: $it", fontSize = 11.sp, color = TextSecondaryLight)
                }
                item.target_user?.name?.let {
                    Text("• Target: $it", fontSize = 11.sp, color = ConnectlyBlue)
                }
            }
        }
    }
}

@Composable
private fun CreateOrEditRoleDialog(
    existingRole: GroupRoleDto?,
    availablePermissions: List<PermissionCategoryDto>,
    onDismiss: () -> Unit,
    onSubmit: (name: String, displayName: String, description: String, color: String, permissions: List<String>) -> Unit
) {
    var name by remember { mutableStateOf(existingRole?.name ?: "") }
    var displayName by remember { mutableStateOf(existingRole?.display_name ?: "") }
    var description by remember { mutableStateOf(existingRole?.description ?: "") }
    var selectedColor by remember { mutableStateOf(existingRole?.color ?: "#6366f1") }
    val selectedPermissions = remember { mutableStateListOf<String>().apply { addAll(existingRole?.permissions ?: emptyList()) } }

    val colors = listOf("#6366f1", "#8b5cf6", "#10b981", "#f59e0b", "#ef4444", "#0284c7", "#ec4899")

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(if (existingRole != null) "Edit Custom Role" else "Create Custom Role", fontWeight = FontWeight.Bold)
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                if (existingRole == null) {
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        label = { Text("Role Code (e.g. event_coordinator)") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                OutlinedTextField(
                    value = displayName,
                    onValueChange = { displayName = it },
                    label = { Text("Display Name") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Role Description") },
                    modifier = Modifier.fillMaxWidth(),
                    maxLines = 2
                )

                // Color Picker Chips
                Text("Role Badge Color:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    colors.forEach { c ->
                        val parsed = try { Color(android.graphics.Color.parseColor(c)) } catch (e: Exception) { ConnectlyBlue }
                        Box(
                            modifier = Modifier
                                .size(24.dp)
                                .clip(CircleShape)
                                .background(parsed)
                                .border(if (selectedColor == c) 2.dp else 0.dp, Color.Black, CircleShape)
                                .clickable { selectedColor = c }
                        )
                    }
                }

                // Multi-category permissions checklist
                Text("Permissions Granted:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                availablePermissions.forEach { cat ->
                    Text(cat.category, fontSize = 11.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    cat.permissions.forEach { perm ->
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    if (selectedPermissions.contains(perm.key)) {
                                        selectedPermissions.remove(perm.key)
                                    } else {
                                        selectedPermissions.add(perm.key)
                                    }
                                }
                        ) {
                            Checkbox(
                                checked = selectedPermissions.contains(perm.key),
                                onCheckedChange = { checked ->
                                    if (checked) selectedPermissions.add(perm.key) else selectedPermissions.remove(perm.key)
                                }
                            )
                            Column {
                                Text(perm.label, fontSize = 12.sp, fontWeight = FontWeight.Medium)
                                Text(perm.description, fontSize = 10.sp, color = TextSecondaryLight)
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(name, displayName, description, selectedColor, selectedPermissions.toList()) },
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                enabled = displayName.isNotBlank() && (existingRole != null || name.isNotBlank())
            ) {
                Text(if (existingRole != null) "Save Changes" else "Create Role")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancel") }
        }
    )
}

@Composable
private fun AssignRoleDialog(
    targetRole: String,
    onDismiss: () -> Unit,
    onSubmit: (role: String, userIds: List<Long>) -> Unit
) {
    var userIdInput by remember { mutableStateOf("") }
    var selectedRole by remember { mutableStateOf(targetRole) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Assign Role to Members", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Assign '$selectedRole' to member(s). Enter User ID(s) separated by commas:", fontSize = 12.sp)
                OutlinedTextField(
                    value = userIdInput,
                    onValueChange = { userIdInput = it },
                    placeholder = { Text("e.g. 101, 102, 105") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val ids = userIdInput.split(",")
                        .mapNotNull { it.trim().toLongOrNull() }
                    if (ids.isNotEmpty()) {
                        onSubmit(selectedRole, ids)
                    }
                },
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                enabled = userIdInput.isNotBlank()
            ) {
                Text("Confirm Assignment")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancel") }
        }
    )
}

