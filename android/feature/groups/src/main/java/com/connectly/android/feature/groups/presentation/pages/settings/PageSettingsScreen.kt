package com.connectly.android.feature.groups.presentation.pages.settings

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.outlined.VerifiedUser
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyAmber
import com.connectly.android.core.designsystem.theme.ConnectlyElectricBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageSettingsScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageSettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    var isPublic by remember { mutableStateOf(true) }
    var allowReviews by remember { mutableStateOf(true) }
    var allowMessages by remember { mutableStateOf(true) }
    var commentModeration by remember { mutableStateOf(false) }

    var showDeleteConfirmDialog by remember { mutableStateOf(false) }
    var showVerificationDialog by remember { mutableStateOf(false) }

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.loadSettings(pageId)
        }
    }

    LaunchedEffect(state.settings) {
        isPublic = state.settings.is_public
        allowReviews = state.settings.allow_reviews
        allowMessages = state.settings.allow_messages
        commentModeration = state.settings.comment_moderation
    }

    LaunchedEffect(state.saveSuccess) {
        if (state.saveSuccess) {
            snackbarHostState.showSnackbar("Page settings saved successfully ✅")
            viewModel.clearMessages()
        }
    }

    LaunchedEffect(state.verificationMessage) {
        state.verificationMessage?.let { msg ->
            snackbarHostState.showSnackbar(msg)
            viewModel.clearMessages()
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let { err ->
            snackbarHostState.showSnackbar("Error: $err")
            viewModel.clearMessages()
        }
    }

    if (showDeleteConfirmDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteConfirmDialog = false },
            title = { Text("Delete Business Page?", fontWeight = FontWeight.Bold) },
            text = { Text("Deleting this Page will permanently remove all posts, analytics, team roles, and settings. This action cannot be undone.") },
            confirmButton = {
                Button(
                    onClick = {
                        showDeleteConfirmDialog = false
                        viewModel.deletePage(onSuccess = onNavigateBack)
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

    if (showVerificationDialog) {
        RequestVerificationDialog(
            isSubmitting = state.isRequestingVerification,
            onDismiss = { showVerificationDialog = false },
            onSubmit = { type, legalName, regNumber, taxId, notes ->
                viewModel.requestVerification(
                    verificationType = type,
                    legalName = legalName,
                    registrationNumber = regNumber,
                    taxId = taxId,
                    notes = notes,
                    onSuccess = { showVerificationDialog = false }
                )
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
        snackbarHost = { SnackbarHost(snackbarHostState) },
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
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                item {
                    Text("Page Visibility & Privacy", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                item {
                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                            // 1. Public Page Visibility
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                    Text("Public Page Visibility", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                    Text("Allow anyone to discover and follow this Page", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Switch(
                                    checked = isPublic,
                                    onCheckedChange = {
                                        isPublic = it
                                        viewModel.updateSettings(
                                            state.settings.copy(
                                                is_public = it,
                                                page_visibility = if (it) "public" else "private"
                                            )
                                        )
                                    }
                                )
                            }
                            HorizontalDivider()

                            // 2. Allow Customer Reviews
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                    Text("Allow Customer Reviews", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                    Text("Display ratings and written reviews on profile", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Switch(
                                    checked = allowReviews,
                                    onCheckedChange = {
                                        allowReviews = it
                                        viewModel.updateSettings(state.settings.copy(allow_reviews = it))
                                    }
                                )
                            }
                            HorizontalDivider()

                            // 3. Enable Direct Messaging
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                    Text("Enable Direct Messaging", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                    Text("Allow visitors to message page inbox", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Switch(
                                    checked = allowMessages,
                                    onCheckedChange = {
                                        allowMessages = it
                                        viewModel.updateSettings(state.settings.copy(allow_messages = it))
                                    }
                                )
                            }
                            HorizontalDivider()

                            // 4. Comment Moderation Filter
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                    Text("Comment Moderation Filter", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                    Text("Filter out spam and offensive keywords automatically", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Switch(
                                    checked = commentModeration,
                                    onCheckedChange = {
                                        commentModeration = it
                                        viewModel.updateSettings(state.settings.copy(comment_moderation = it))
                                    }
                                )
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
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(Icons.Outlined.VerifiedUser, contentDescription = null, tint = ConnectlyElectricBlue)
                                Spacer(modifier = Modifier.width(10.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Request Page Verification", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                    Text("Apply for a verified checkmark badge for authentic business pages", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                Spacer(modifier = Modifier.width(6.dp))

                                when (state.verificationStatus.lowercase()) {
                                    "pending", "under_review" -> {
                                        Surface(
                                            color = ConnectlyAmber.copy(alpha = 0.15f),
                                            shape = RoundedCornerShape(12.dp)
                                        ) {
                                            Text(
                                                text = "Pending Review",
                                                color = ConnectlyAmber,
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 11.sp,
                                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp)
                                            )
                                        }
                                    }
                                    "approved", "verified" -> {
                                        Surface(
                                            color = ConnectlyGreen.copy(alpha = 0.15f),
                                            shape = RoundedCornerShape(12.dp)
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 5.dp)
                                            ) {
                                                Icon(
                                                    Icons.Default.CheckCircle,
                                                    contentDescription = null,
                                                    tint = ConnectlyGreen,
                                                    modifier = Modifier.size(14.dp)
                                                )
                                                Spacer(modifier = Modifier.width(4.dp))
                                                Text(
                                                    text = "Verified",
                                                    color = ConnectlyGreen,
                                                    fontWeight = FontWeight.Bold,
                                                    fontSize = 11.sp
                                                )
                                            }
                                        }
                                    }
                                    else -> {
                                        OutlinedButton(
                                            onClick = { showVerificationDialog = true },
                                            shape = RoundedCornerShape(20.dp),
                                            contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp)
                                        ) {
                                            Text("Apply", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                                        }
                                    }
                                }
                            }

                            HorizontalDivider()

                            Button(
                                onClick = { showDeleteConfirmDialog = true },
                                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                                shape = RoundedCornerShape(8.dp),
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
}

@Composable
fun RequestVerificationDialog(
    isSubmitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (type: String, legalName: String, regNumber: String, taxId: String, notes: String) -> Unit
) {
    var selectedType by remember { mutableStateOf("business") }
    var legalName by remember { mutableStateOf("") }
    var regNumber by remember { mutableStateOf("") }
    var taxId by remember { mutableStateOf("") }
    var notes by remember { mutableStateOf("") }

    val verificationTypes = listOf(
        "business" to "Business / Enterprise",
        "brand" to "Brand / Company",
        "organization" to "Organization / NGO",
        "creator" to "Creator / Public Figure",
        "media" to "Media / Publishing"
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Request Page Verification", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Provide your business credentials to request official verification badge review.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Text("Verification Category *", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    verificationTypes.forEach { (typeKey, typeLabel) ->
                        Surface(
                            onClick = { selectedType = typeKey },
                            shape = RoundedCornerShape(8.dp),
                            color = if (selectedType == typeKey) ConnectlyElectricBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Row(
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                RadioButton(
                                    selected = selectedType == typeKey,
                                    onClick = { selectedType = typeKey }
                                )
                                Spacer(modifier = Modifier.width(6.dp))
                                Text(typeLabel, fontSize = 13.sp, fontWeight = if (selectedType == typeKey) FontWeight.Bold else FontWeight.Normal)
                            }
                        }
                    }
                }

                OutlinedTextField(
                    value = legalName,
                    onValueChange = { legalName = it },
                    label = { Text("Legal Entity / Business Name") },
                    placeholder = { Text("e.g. Acme Corporation Ltd.") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = regNumber,
                    onValueChange = { regNumber = it },
                    label = { Text("Trade License / Registration No.") },
                    placeholder = { Text("e.g. TRAD/DNCC/12345/2026") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = taxId,
                    onValueChange = { taxId = it },
                    label = { Text("Tax ID / TIN / BIN") },
                    placeholder = { Text("e.g. 1234567890123") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = notes,
                    onValueChange = { notes = it },
                    label = { Text("Additional Notes & Links") },
                    placeholder = { Text("Official website, press releases, or portfolio links") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmit(selectedType, legalName, regNumber, taxId, notes) },
                enabled = !isSubmitting,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(
                        color = Color.White,
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                }
                Text("Submit Request")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSubmitting) {
                Text("Cancel")
            }
        }
    )
}
