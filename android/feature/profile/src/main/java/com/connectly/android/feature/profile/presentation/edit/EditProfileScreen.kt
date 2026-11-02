package com.connectly.android.feature.profile.presentation.edit

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForwardIos
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.component.resolveMediaUrl
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditProfileScreen(
    onNavigateBack: () -> Unit,
    onAccountTerminated: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: EditProfileViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    // Dialog form states
    var currentPass by remember { mutableStateOf("") }
    var newPass by remember { mutableStateOf("") }
    var confirmPass by remember { mutableStateOf("") }

    var deactPassword by remember { mutableStateOf("") }
    var deactReason by remember { mutableStateOf("") }

    var delPassword by remember { mutableStateOf("") }
    var delConfirmation by remember { mutableStateOf("") }
    var delReason by remember { mutableStateOf("") }

    LaunchedEffect(state.isSaved) {
        if (state.isSaved) {
            onNavigateBack()
        }
    }

    LaunchedEffect(state.isTerminated) {
        if (state.isTerminated) {
            onAccountTerminated()
        }
    }

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(EditProfileIntent.DismissMessages)
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(EditProfileIntent.DismissMessages)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Account & Profile", fontWeight = FontWeight.Bold, fontSize = 20.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    TextButton(
                        onClick = { viewModel.handleIntent(EditProfileIntent.SaveProfile) },
                        enabled = !state.isLoading
                    ) {
                        Text("Save", fontWeight = FontWeight.Bold, color = ConnectlyBlue, fontSize = 16.sp)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(BackgroundLight)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // 1. Photos Section (Cover & Avatar)
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(200.dp)
            ) {
                // Cover photo
                val resolvedCover = resolveMediaUrl(state.coverUrl) ?: state.coverUrl
                AsyncImage(
                    model = resolvedCover ?: "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                    contentDescription = "Cover Image",
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(140.dp)
                        .clip(RoundedCornerShape(12.dp))
                )

                Surface(
                    shape = CircleShape,
                    color = Color.Black.copy(alpha = 0.5f),
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(10.dp)
                        .size(36.dp)
                ) {
                    IconButton(onClick = { /* Pick cover image */ }) {
                        Icon(Icons.Default.CameraAlt, contentDescription = "Change Cover", tint = Color.White, modifier = Modifier.size(18.dp))
                    }
                }

                // Avatar Photo
                Box(
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .size(100.dp)
                ) {
                    ConnectlyAvatar(
                        imageUrl = state.avatarUrl,
                        name = state.name,
                        size = 100.dp,
                        isVerified = state.isIdentityVerified,
                        modifier = Modifier.border(3.dp, Color.White, CircleShape)
                    )

                    Surface(
                        shape = CircleShape,
                        color = ConnectlyBlue,
                        modifier = Modifier
                            .align(Alignment.BottomEnd)
                            .size(32.dp)
                    ) {
                        IconButton(onClick = { /* Pick avatar image */ }) {
                            Icon(Icons.Default.CameraAlt, contentDescription = "Change Avatar", tint = Color.White, modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }

            // 2. Personal Information Fields Card
            Card(
                shape = RoundedCornerShape(14.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    Text("PERSONAL INFORMATION", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = TextSecondaryLight)

                    // Full Name
                    OutlinedTextField(
                        value = state.name,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.NameChanged(it)) },
                        label = { Text("Full Name") },
                        leadingIcon = { Icon(Icons.Default.Person, contentDescription = null, tint = ConnectlyBlue) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )

                    // Username
                    OutlinedTextField(
                        value = state.username,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.UsernameChanged(it)) },
                        label = { Text("Username") },
                        leadingIcon = { Icon(Icons.Default.AlternateEmail, contentDescription = null, tint = ConnectlyBlue) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )

                    // Email Address
                    OutlinedTextField(
                        value = state.email,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Email Address") },
                        leadingIcon = { Icon(Icons.Default.Email, contentDescription = null, tint = ConnectlyBlue) },
                        trailingIcon = {
                            if (state.isEmailVerified) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.padding(end = 8.dp)
                                ) {
                                    Icon(Icons.Default.CheckCircle, contentDescription = "Verified", tint = ConnectlyGreen, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Verified", fontSize = 11.sp, color = ConnectlyGreen, fontWeight = FontWeight.SemiBold)
                                }
                            }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )

                    // Phone Number
                    OutlinedTextField(
                        value = state.phone,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.PhoneChanged(it)) },
                        label = { Text("Phone Number") },
                        placeholder = { Text("+880 1XXXXXXXXX") },
                        leadingIcon = { Icon(Icons.Default.Phone, contentDescription = null, tint = ConnectlyBlue) },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )

                    // Bio
                    OutlinedTextField(
                        value = state.bio,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.BioChanged(it)) },
                        label = { Text("Bio") },
                        placeholder = { Text("Write something about yourself...") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3,
                        maxLines = 5,
                        shape = RoundedCornerShape(10.dp)
                    )

                    // Occupation & Location
                    OutlinedTextField(
                        value = state.occupation,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.OccupationChanged(it)) },
                        label = { Text("Occupation") },
                        leadingIcon = { Icon(Icons.Default.Work, contentDescription = null, tint = ConnectlyBlue) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )

                    OutlinedTextField(
                        value = state.location,
                        onValueChange = { viewModel.handleIntent(EditProfileIntent.LocationChanged(it)) },
                        label = { Text("Location") },
                        leadingIcon = { Icon(Icons.Default.LocationOn, contentDescription = null, tint = ConnectlyBlue) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp)
                    )
                }
            }

            // 3. Verification & Account Information Card
            Card(
                shape = RoundedCornerShape(14.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    Text("ACCOUNT INFORMATION & STATUS", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = TextSecondaryLight)

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("User ID", fontSize = 14.sp)
                        Text("#${state.userId}", fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Member Since", fontSize = 14.sp)
                        Text(state.createdAt.ifBlank { "November 2026" }, color = TextSecondaryLight, fontSize = 13.sp)
                    }

                    HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Identity Verification", fontSize = 14.sp)
                        Surface(
                            shape = RoundedCornerShape(12.dp),
                            color = if (state.isIdentityVerified) ConnectlyGreen.copy(alpha = 0.12f) else ConnectlyBlue.copy(alpha = 0.1f)
                        ) {
                            Text(
                                text = if (state.isIdentityVerified) "Verified Active" else "Standard Account",
                                color = if (state.isIdentityVerified) ConnectlyGreen else ConnectlyBlue,
                                fontWeight = FontWeight.Bold,
                                fontSize = 11.sp,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                            )
                        }
                    }
                }
            }

            // 4. Security & Sensitive Account Actions
            Card(
                shape = RoundedCornerShape(14.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Text("SECURITY & ACCOUNT CONTROL", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = TextSecondaryLight)

                    // Change Password
                    ActionItemRow(
                        icon = Icons.Default.Key,
                        title = "Change Password",
                        subtitle = "Update login password with current confirmation",
                        onClick = { viewModel.handleIntent(EditProfileIntent.OpenDialog(AccountDialogType.CHANGE_PASSWORD)) }
                    )

                    HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                    // Deactivate Account
                    ActionItemRow(
                        icon = Icons.Default.PauseCircle,
                        title = "Deactivate Account",
                        subtitle = "Temporarily hide your profile and posts",
                        tint = ConnectlyAmber,
                        onClick = { viewModel.handleIntent(EditProfileIntent.OpenDialog(AccountDialogType.DEACTIVATE_ACCOUNT)) }
                    )

                    HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                    // Permanently Delete Account
                    ActionItemRow(
                        icon = Icons.Default.DeleteForever,
                        title = "Permanently Delete Account",
                        subtitle = "Irreversible data erasure under retention policy",
                        tint = ConnectlyRose,
                        onClick = { viewModel.handleIntent(EditProfileIntent.OpenDialog(AccountDialogType.DELETE_ACCOUNT)) }
                    )
                }
            }

            if (state.isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.align(Alignment.CenterHorizontally).size(28.dp),
                    color = ConnectlyBlue
                )
            }

            Spacer(modifier = Modifier.height(20.dp))
        }
    }

    // Interactive Dialogs for Sensitive Operations
    when (state.activeDialog) {
        AccountDialogType.CHANGE_PASSWORD -> {
            AlertDialog(
                onDismissRequest = { viewModel.handleIntent(EditProfileIntent.DismissDialog) },
                title = { Text("Change Password", fontWeight = FontWeight.Bold) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text("Ensure your new password has at least 8 characters.", fontSize = 13.sp, color = TextSecondaryLight)

                        OutlinedTextField(
                            value = currentPass,
                            onValueChange = { currentPass = it },
                            label = { Text("Current Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = newPass,
                            onValueChange = { newPass = it },
                            label = { Text("New Password (min 8 chars)") },
                            visualTransformation = PasswordVisualTransformation(),
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = confirmPass,
                            onValueChange = { confirmPass = it },
                            label = { Text("Confirm New Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            viewModel.handleIntent(EditProfileIntent.ChangePassword(currentPass, newPass, confirmPass))
                            currentPass = ""
                            newPass = ""
                            confirmPass = ""
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Text("Update Password")
                    }
                },
                dismissButton = {
                    TextButton(onClick = { viewModel.handleIntent(EditProfileIntent.DismissDialog) }) {
                        Text("Cancel")
                    }
                }
            )
        }

        AccountDialogType.DEACTIVATE_ACCOUNT -> {
            AlertDialog(
                onDismissRequest = { viewModel.handleIntent(EditProfileIntent.DismissDialog) },
                title = { Text("Deactivate Account?", fontWeight = FontWeight.Bold, color = ConnectlyAmber) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text(
                            text = "Deactivating will temporarily hide your profile, posts, and comments. You can reactivate anytime simply by logging back in.",
                            fontSize = 13.sp,
                            color = TextSecondaryLight
                        )

                        OutlinedTextField(
                            value = deactPassword,
                            onValueChange = { deactPassword = it },
                            label = { Text("Enter Your Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = deactReason,
                            onValueChange = { deactReason = it },
                            label = { Text("Reason (Optional)") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            viewModel.handleIntent(EditProfileIntent.DeactivateAccount(deactPassword, deactReason))
                            deactPassword = ""
                            deactReason = ""
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyAmber)
                    ) {
                        Text("Deactivate Now", color = Color.White)
                    }
                },
                dismissButton = {
                    TextButton(onClick = { viewModel.handleIntent(EditProfileIntent.DismissDialog) }) {
                        Text("Cancel")
                    }
                }
            )
        }

        AccountDialogType.DELETE_ACCOUNT -> {
            AlertDialog(
                onDismissRequest = { viewModel.handleIntent(EditProfileIntent.DismissDialog) },
                title = { Text("Permanently Delete Account?", fontWeight = FontWeight.Bold, color = ConnectlyRose) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text(
                            text = "Warning: This action is permanent and cannot be undone. All your posts, photos, messages, and followers will be permanently scheduled for deletion.",
                            fontSize = 13.sp,
                            color = ConnectlyRose,
                            fontWeight = FontWeight.SemiBold
                        )

                        Text("To confirm, type DELETE below and provide your password:", fontSize = 12.sp)

                        OutlinedTextField(
                            value = delConfirmation,
                            onValueChange = { delConfirmation = it },
                            label = { Text("Type DELETE") },
                            placeholder = { Text("DELETE") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )

                        OutlinedTextField(
                            value = delPassword,
                            onValueChange = { delPassword = it },
                            label = { Text("Your Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            viewModel.handleIntent(EditProfileIntent.DeleteAccount(delPassword, delConfirmation, delReason))
                            delPassword = ""
                            delConfirmation = ""
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyRose),
                        enabled = delConfirmation == "DELETE"
                    ) {
                        Text("Delete Forever", color = Color.White)
                    }
                },
                dismissButton = {
                    TextButton(onClick = { viewModel.handleIntent(EditProfileIntent.DismissDialog) }) {
                        Text("Cancel")
                    }
                }
            )
        }

        null -> {}
    }
}

@Composable
private fun ActionItemRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    title: String,
    subtitle: String,
    tint: Color = ConnectlyBlue,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(
            modifier = Modifier
                .size(36.dp)
                .clip(CircleShape)
                .background(tint.copy(alpha = 0.12f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(imageVector = icon, contentDescription = title, tint = tint, modifier = Modifier.size(18.dp))
        }

        Spacer(modifier = Modifier.width(12.dp))

        Column(modifier = Modifier.weight(1f)) {
            Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
            Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight)
        }

        Icon(
            imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
            contentDescription = null,
            tint = Color.LightGray,
            modifier = Modifier.size(12.dp)
        )
    }
}
