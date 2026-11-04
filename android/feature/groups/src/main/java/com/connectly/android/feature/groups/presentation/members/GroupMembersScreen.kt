package com.connectly.android.feature.groups.presentation.members

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.network.api.GroupMemberDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupMembersScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    onNavigateToUserProfile: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: GroupMembersViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(groupId) {
        if (state.groupId != groupId) {
            viewModel.handleIntent(GroupMembersIntent.LoadMembers(groupId))
        }
    }

    // Role selection dialog
    state.showRoleDialogForUser?.let { member ->
        var selectedRole by remember { mutableStateOf(member.role.lowercase()) }
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) },
            title = { Text("Change Role for ${member.name}") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    val roles = listOf(
                        "admin" to "Administrator (Full access except owner actions)",
                        "moderator" to "Moderator (Content moderation & member discipline)",
                        "content_manager" to "Content Manager (Manage posts & announcements)",
                        "member" to "Member (Standard community member)",
                        "restricted" to "Restricted (Limited posting privileges)"
                    )
                    roles.forEach { (roleKey, description) ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { selectedRole = roleKey }
                                .padding(vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = selectedRole == roleKey,
                                onClick = { selectedRole = roleKey }
                            )
                            Spacer(Modifier.width(8.dp))
                            Column {
                                Text(roleKey.replace("_", " ").replaceFirstChar { it.uppercase() }, fontWeight = FontWeight.SemiBold)
                                Text(description, style = MaterialTheme.typography.bodySmall, color = TextSecondaryLight)
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(GroupMembersIntent.ChangeMemberRole(member.id, selectedRole))
                    }
                ) {
                    Text("Save Role")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Restrict Dialog
    state.showRestrictDialogForUser?.let { member ->
        var durationDays by remember { mutableStateOf(7) }
        var reason by remember { mutableStateOf("Violation of group rules") }

        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) },
            title = { Text("Restrict ${member.name}") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text(
                        "Restricted members cannot create posts or write comments for the chosen duration.",
                        fontSize = 13.sp,
                        color = TextSecondaryLight
                    )
                    Text("Duration:", fontWeight = FontWeight.SemiBold)
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf(1, 3, 7, 30).forEach { days ->
                            FilterChip(
                                selected = durationDays == days,
                                onClick = { durationDays = days },
                                label = { Text("$days d") }
                            )
                        }
                    }
                    OutlinedTextField(
                        value = reason,
                        onValueChange = { reason = it },
                        label = { Text("Reason for restriction") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(GroupMembersIntent.RestrictMember(member.id, durationDays, reason))
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFF97316))
                ) {
                    Text("Restrict")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Ban Dialog
    state.showBanConfirmDialogForUser?.let { member ->
        var banReason by remember { mutableStateOf("Spam or harmful behavior") }
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) },
            title = { Text("Ban ${member.name} from Group?") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(
                        "This will remove the user from the group and prevent them from requesting to re-join.",
                        color = MaterialTheme.colorScheme.error,
                        fontSize = 13.sp
                    )
                    OutlinedTextField(
                        value = banReason,
                        onValueChange = { banReason = it },
                        label = { Text("Reason for ban") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(GroupMembersIntent.BanMember(member.id, banReason))
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Confirm Ban")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Remove Dialog
    state.showRemoveConfirmDialogForUser?.let { member ->
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) },
            title = { Text("Remove ${member.name}?") },
            text = {
                Text("Are you sure you want to remove this member from the group? They can request to join again later.")
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(GroupMembersIntent.RemoveMember(member.id))
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Remove")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissDialogs) }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Member Activity Bottom Sheet / Dialog
    state.inspectingMemberActivity?.let { activity ->
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupMembersIntent.DismissMemberActivity) },
            title = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.AccountCircle, contentDescription = null, tint = ConnectlyBlue)
                    Spacer(Modifier.width(8.dp))
                    Text("Member Activity Details", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                }
            },
            text = {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    val mem = activity.member
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(
                            modifier = Modifier
                                .size(48.dp)
                                .clip(CircleShape)
                                .background(ConnectlyBlue.copy(alpha = 0.1f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(mem.name.take(1), fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                        }
                        Spacer(Modifier.width(12.dp))
                        Column {
                            Text(mem.name, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("@${mem.username} • Role: ${mem.role.uppercase()}", fontSize = 12.sp, color = TextSecondaryLight)
                        }
                    }

                    HorizontalDivider()

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceAround
                    ) {
                        StatCounter("Posts", activity.activity_summary.posts_count.toString())
                        StatCounter("Comments", activity.activity_summary.comments_count.toString())
                        StatCounter("Warnings", activity.activity_summary.warnings_count.toString())
                    }

                    HorizontalDivider()

                    Text("Recent Moderation Actions", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                    if (activity.recent_moderation_actions.isEmpty()) {
                        Text("No moderation actions recorded for this member.", fontSize = 12.sp, color = TextSecondaryLight)
                    } else {
                        activity.recent_moderation_actions.take(3).forEach { action ->
                            Card(
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Column(modifier = Modifier.padding(8.dp)) {
                                    Text(
                                        "${action.action_type.uppercase()} by ${action.moderator_name}",
                                        fontWeight = FontWeight.SemiBold,
                                        fontSize = 12.sp
                                    )
                                    Text("Reason: ${action.reason ?: "N/A"}", fontSize = 11.sp, color = TextSecondaryLight)
                                    Text(action.created_at.orEmpty(), fontSize = 10.sp, color = TextSecondaryLight)
                                }
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissMemberActivity) }) {
                    Text("Close")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Manage Members", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        Text(
                            "${state.members.size} members found",
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
                    if (state.selectedMemberIds.isNotEmpty()) {
                        TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.ClearSelection) }) {
                            Text("Clear")
                        }
                    } else {
                        TextButton(onClick = { viewModel.handleIntent(GroupMembersIntent.SelectAllMembers) }) {
                            Text("Select All")
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        bottomBar = {
            // Bulk action bar
            AnimatedVisibility(visible = state.selectedMemberIds.isNotEmpty()) {
                Surface(
                    tonalElevation = 8.dp,
                    color = MaterialTheme.colorScheme.surface,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                "${state.selectedMemberIds.size} selected",
                                fontWeight = FontWeight.Bold,
                                color = ConnectlyBlue
                            )
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                OutlinedButton(
                                    onClick = {
                                        viewModel.handleIntent(
                                            GroupMembersIntent.ExecuteBulkAction(
                                                action = "assign_role",
                                                role = "moderator"
                                            )
                                        )
                                    },
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text("Make Mod", fontSize = 11.sp)
                                }
                                Button(
                                    onClick = {
                                        viewModel.handleIntent(
                                            GroupMembersIntent.ExecuteBulkAction(
                                                action = "restrict",
                                                durationDays = 7,
                                                reason = "Bulk restriction by admin"
                                            )
                                        )
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFF97316)),
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text("Restrict", fontSize = 11.sp)
                                }
                                Button(
                                    onClick = {
                                        viewModel.handleIntent(
                                            GroupMembersIntent.ExecuteBulkAction(
                                                action = "remove",
                                                reason = "Bulk removal by admin"
                                            )
                                        )
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text("Remove", fontSize = 11.sp)
                                }
                            }
                        }
                    }
                }
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            // Search and Filter Header
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = state.searchQuery,
                        onValueChange = { viewModel.handleIntent(GroupMembersIntent.SearchQueryChanged(it)) },
                        placeholder = { Text("Search by name or username...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    )

                    // Role Filter Chips
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf(
                            null to "All Roles",
                            "owner" to "Owner",
                            "admin" to "Admins",
                            "moderator" to "Moderators",
                            "content_manager" to "Content Mgrs",
                            "member" to "Members",
                            "restricted" to "Restricted"
                        ).forEach { (roleKey, label) ->
                            FilterChip(
                                selected = state.selectedRole == roleKey,
                                onClick = { viewModel.handleIntent(GroupMembersIntent.RoleFilterChanged(roleKey)) },
                                label = { Text(label, fontSize = 12.sp) }
                            )
                        }
                    }

                    // Sort Chips
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf(
                            "join_date_desc" to "Newest First",
                            "join_date_asc" to "Oldest First",
                            "most_active" to "Most Active",
                            "alphabetical" to "Alphabetical (A-Z)"
                        ).forEach { (sortKey, label) ->
                            FilterChip(
                                selected = state.selectedSort == sortKey,
                                onClick = { viewModel.handleIntent(GroupMembersIntent.SortChanged(sortKey)) },
                                label = { Text(label, fontSize = 11.sp) },
                                leadingIcon = if (state.selectedSort == sortKey) {
                                    { Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(14.dp)) }
                                } else null
                            )
                        }
                    }
                }
            }

            // Status message / Error banner
            state.actionMessage?.let { msg ->
                item {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFDCFCE7)),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp)
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF16A34A))
                            Spacer(Modifier.width(8.dp))
                            Text(msg, color = Color(0xFF166534), fontSize = 13.sp, modifier = Modifier.weight(1f))
                            IconButton(onClick = { viewModel.handleIntent(GroupMembersIntent.DismissMessage) }) {
                                Icon(Icons.Default.Close, contentDescription = null, tint = Color(0xFF166534))
                            }
                        }
                    }
                }
            }

            // Member list items
            if (state.isLoading) {
                item {
                    Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }
                }
            } else if (state.members.isEmpty()) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(32.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(Icons.Outlined.Group, contentDescription = null, modifier = Modifier.size(48.dp), tint = TextSecondaryLight)
                        Spacer(Modifier.height(8.dp))
                        Text("No members found", fontWeight = FontWeight.Bold, color = TextPrimaryLight)
                        Text("Try adjusting your search or filters.", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                }
            } else {
                items(state.members, key = { it.id }) { member ->
                    MemberRowCard(
                        member = member,
                        isSelected = state.selectedMemberIds.contains(member.id),
                        onToggleSelect = { viewModel.handleIntent(GroupMembersIntent.ToggleSelectMember(member.id)) },
                        onViewActivity = { viewModel.handleIntent(GroupMembersIntent.InspectMemberActivity(member.id)) },
                        onChangeRole = { viewModel.handleIntent(GroupMembersIntent.OpenRoleDialog(member)) },
                        onRestrict = {
                            if (member.account_status == "restricted") {
                                viewModel.handleIntent(GroupMembersIntent.UnrestrictMember(member.id))
                            } else {
                                viewModel.handleIntent(GroupMembersIntent.OpenRestrictDialog(member))
                            }
                        },
                        onBan = { viewModel.handleIntent(GroupMembersIntent.OpenBanDialog(member)) },
                        onRemove = { viewModel.handleIntent(GroupMembersIntent.OpenRemoveDialog(member)) },
                        onNavigateToProfile = { onNavigateToUserProfile(member.id) }
                    )
                }
            }
        }
    }
}

@Composable
private fun StatCounter(label: String, value: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, fontWeight = FontWeight.Bold, fontSize = 18.sp, color = ConnectlyBlue)
        Text(label, fontSize = 11.sp, color = TextSecondaryLight)
    }
}

@Composable
private fun MemberRowCard(
    member: GroupMemberDto,
    isSelected: Boolean,
    onToggleSelect: () -> Unit,
    onViewActivity: () -> Unit,
    onChangeRole: () -> Unit,
    onRestrict: () -> Unit,
    onBan: () -> Unit,
    onRemove: () -> Unit,
    onNavigateToProfile: () -> Unit
) {
    var menuExpanded by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (isSelected) ConnectlyBlue.copy(alpha = 0.08f) else MaterialTheme.colorScheme.surface
        ),
        shape = RoundedCornerShape(12.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Checkbox(
                checked = isSelected,
                onCheckedChange = { onToggleSelect() }
            )

            Spacer(Modifier.width(8.dp))

            // Avatar
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .clickable { onNavigateToProfile() }
            ) {
                if (!member.avatar_url.isNullOrBlank()) {
                    AsyncImage(
                        model = member.avatar_url,
                        contentDescription = member.name,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier.fillMaxSize()
                    )
                } else {
                    Box(
                        modifier = Modifier.fillMaxSize().background(ConnectlyBlue),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = member.name.take(1).uppercase(),
                            color = Color.White,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
            }

            Spacer(Modifier.width(12.dp))

            // Details
            Column(modifier = Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = member.name,
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = TextPrimaryLight,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Spacer(Modifier.width(6.dp))
                    RoleBadge(member.role)
                }

                Text(
                    text = "@${member.username}",
                    fontSize = 12.sp,
                    color = TextSecondaryLight
                )

                Spacer(Modifier.height(4.dp))

                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "${member.posts_published} posts • ${member.comments_count} cmts",
                        fontSize = 11.sp,
                        color = TextSecondaryLight
                    )
                    if (member.account_status != "active") {
                        StatusBadge(member.account_status)
                    }
                }
            }

            // 3-dots Menu
            Box {
                IconButton(onClick = { menuExpanded = true }) {
                    Icon(Icons.Default.MoreVert, contentDescription = "Options")
                }

                DropdownMenu(
                    expanded = menuExpanded,
                    onDismissRequest = { menuExpanded = false }
                ) {
                    DropdownMenuItem(
                        text = { Text("View Profile") },
                        leadingIcon = { Icon(Icons.Outlined.Person, contentDescription = null) },
                        onClick = {
                            menuExpanded = false
                            onNavigateToProfile()
                        }
                    )
                    DropdownMenuItem(
                        text = { Text("Activity Details") },
                        leadingIcon = { Icon(Icons.Outlined.Analytics, contentDescription = null) },
                        onClick = {
                            menuExpanded = false
                            onViewActivity()
                        }
                    )
                    DropdownMenuItem(
                        text = { Text("Change Role") },
                        leadingIcon = { Icon(Icons.Outlined.ManageAccounts, contentDescription = null) },
                        onClick = {
                            menuExpanded = false
                            onChangeRole()
                        }
                    )
                    DropdownMenuItem(
                        text = { Text(if (member.account_status == "restricted") "Unrestrict Member" else "Restrict Member") },
                        leadingIcon = { Icon(Icons.Outlined.Block, contentDescription = null) },
                        onClick = {
                            menuExpanded = false
                            onRestrict()
                        }
                    )
                    HorizontalDivider()
                    DropdownMenuItem(
                        text = { Text("Remove from Group", color = MaterialTheme.colorScheme.error) },
                        leadingIcon = { Icon(Icons.Outlined.PersonRemove, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
                        onClick = {
                            menuExpanded = false
                            onRemove()
                        }
                    )
                    DropdownMenuItem(
                        text = { Text("Ban Member", color = MaterialTheme.colorScheme.error) },
                        leadingIcon = { Icon(Icons.Outlined.Gavel, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
                        onClick = {
                            menuExpanded = false
                            onBan()
                        }
                    )
                }
            }
        }
    }
}

@Composable
private fun RoleBadge(role: String) {
    val (bgColor, textColor, label) = when (role.lowercase()) {
        "owner" -> Triple(Color(0xFFFEF3C7), Color(0xFFD97706), "Owner")
        "admin" -> Triple(Color(0xFFDBEAFE), Color(0xFF2563EB), "Admin")
        "moderator" -> Triple(Color(0xFFF3E8FF), Color(0xFF9333EA), "Mod")
        "content_manager" -> Triple(Color(0xFFDCFCE7), Color(0xFF16A34A), "Content")
        "restricted" -> Triple(Color(0xFFFEE2E2), Color(0xFFDC2626), "Restricted")
        else -> Triple(Color(0xFFF1F5F9), Color(0xFF64748B), "Member")
    }

    Box(
        modifier = Modifier
            .clip(RoundedCornerShape(6.dp))
            .background(bgColor)
            .padding(horizontal = 6.dp, vertical = 2.dp)
    ) {
        Text(text = label, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = textColor)
    }
}

@Composable
private fun StatusBadge(status: String) {
    val (bgColor, textColor) = when (status.lowercase()) {
        "restricted" -> Pair(Color(0xFFFEF2F2), Color(0xFFDC2626))
        "muted" -> Pair(Color(0xFFFFFBEB), Color(0xFFD97706))
        "banned" -> Pair(Color(0xFF450A0A), Color(0xFFFCA5A5))
        else -> Pair(Color(0xFFF1F5F9), Color(0xFF64748B))
    }

    Box(
        modifier = Modifier
            .clip(RoundedCornerShape(4.dp))
            .background(bgColor)
            .padding(horizontal = 5.dp, vertical = 1.dp)
    ) {
        Text(text = status.uppercase(), fontSize = 9.sp, fontWeight = FontWeight.Bold, color = textColor)
    }
}

