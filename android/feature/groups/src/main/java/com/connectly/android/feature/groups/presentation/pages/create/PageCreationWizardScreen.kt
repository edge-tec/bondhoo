package com.connectly.android.feature.groups.presentation.pages.create

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageCreationWizardScreen(
    onNavigateBack: () -> Unit = {},
    onPageCreated: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageCreationWizardViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    val popularCategories = listOf(
        "Business & Finance",
        "E-commerce & Retail",
        "Technology & Software",
        "Restaurant & Food",
        "Education & Training",
        "Creator & Influencer",
        "Health & Beauty",
        "Media & News Company",
        "Community & Non-profit"
    )

    val ctaOptions = listOf(
        "send_message" to "Send Message",
        "whatsapp" to "WhatsApp",
        "call_now" to "Call Now",
        "visit_website" to "Visit Website",
        "book_now" to "Book Now",
        "contact_us" to "Contact Us",
        "learn_more" to "Learn More"
    )

    val hourOptions = listOf(
        "always_open" to "Always Open (24/7)",
        "selected_hours" to "Open at Selected Hours",
        "no_hours" to "No Hours Available"
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Create Professional Page", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        Text(
                            text = "Step ${state.currentStep} of ${state.totalSteps}",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        bottomBar = {
            Surface(
                tonalElevation = 6.dp,
                shadowElevation = 8.dp,
                color = MaterialTheme.colorScheme.surface
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 12.dp)
                        .navigationBarsPadding(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    if (state.currentStep > 1) {
                        OutlinedButton(
                            onClick = { viewModel.previousStep() },
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.height(48.dp)
                        ) {
                            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Previous", fontWeight = FontWeight.SemiBold)
                        }
                    } else {
                        Spacer(modifier = Modifier.width(1.dp))
                    }

                    if (state.currentStep < state.totalSteps) {
                        Button(
                            onClick = {
                                if (state.currentStep == 1 && state.name.isBlank()) {
                                    // name is required
                                    return@Button
                                }
                                viewModel.nextStep()
                            },
                            enabled = state.currentStep != 1 || state.name.isNotBlank(),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue),
                            modifier = Modifier.height(48.dp)
                        ) {
                            Text("Next", fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.width(6.dp))
                            Icon(Icons.AutoMirrored.Filled.ArrowForward, contentDescription = null, modifier = Modifier.size(16.dp))
                        }
                    } else {
                        Button(
                            onClick = {
                                viewModel.submitCreatePage { newId ->
                                    onPageCreated(newId)
                                }
                            },
                            enabled = !state.isSubmitting && state.name.isNotBlank(),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                            modifier = Modifier.height(48.dp)
                        ) {
                            if (state.isSubmitting) {
                                CircularProgressIndicator(color = Color.White, modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Publishing Page...", fontWeight = FontWeight.Bold)
                            } else {
                                Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Create & Publish Page", fontWeight = FontWeight.Bold)
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
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Animated Step Progress Bar
            item {
                Column(modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) {
                    val progress by animateFloatAsState(targetValue = state.currentStep / state.totalSteps.toFloat(), label = "progress")
                    LinearProgressIndicator(
                        progress = { progress },
                        modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                        color = ConnectlyElectricBlue,
                        trackColor = MaterialTheme.colorScheme.surfaceVariant
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        listOf(
                            1 to "Identity",
                            2 to "Category",
                            3 to "Contact",
                            4 to "Location",
                            5 to "Action & Hours"
                        ).forEach { (stepNum, title) ->
                            val isActive = state.currentStep == stepNum
                            val isCompleted = state.currentStep > stepNum
                            Surface(
                                shape = RoundedCornerShape(16.dp),
                                color = when {
                                    isActive -> ConnectlyElectricBlue.copy(alpha = 0.15f)
                                    isCompleted -> Color(0xFF10B981).copy(alpha = 0.12f)
                                    else -> MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                },
                                modifier = Modifier.clickable { viewModel.goToStep(stepNum) }
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp)
                                ) {
                                    Text(
                                        text = "$stepNum. $title",
                                        fontSize = 11.sp,
                                        fontWeight = if (isActive) FontWeight.Bold else FontWeight.Medium,
                                        color = when {
                                            isActive -> ConnectlyElectricBlue
                                            isCompleted -> Color(0xFF10B981)
                                            else -> MaterialTheme.colorScheme.onSurfaceVariant
                                        }
                                    )
                                }
                            }
                        }
                    }
                }
            }

            // Error Banner
            if (state.errorMessage != null) {
                item {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Row(
                            modifier = Modifier.padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(Icons.Default.Error, contentDescription = null, tint = MaterialTheme.colorScheme.error, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(
                                text = state.errorMessage ?: "",
                                color = MaterialTheme.colorScheme.onErrorContainer,
                                fontSize = 13.sp,
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }
                }
            }

            // Real-Time Facebook-Style Live Page Preview Card
            item {
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column {
                        Box(modifier = Modifier.fillMaxWidth().height(120.dp)) {
                            if (state.coverUrl.isNotBlank()) {
                                AsyncImage(
                                    model = state.coverUrl,
                                    contentDescription = "Cover",
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier.fillMaxSize()
                                )
                            } else {
                                Box(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .background(
                                            Brush.horizontalGradient(
                                                listOf(Color(0xFF1E3A8A), Color(0xFF3B82F6), Color(0xFF06B6D4))
                                            )
                                        )
                                )
                            }

                            // Profile Avatar Overlay
                            Box(
                                modifier = Modifier
                                    .align(Alignment.BottomStart)
                                    .padding(start = 16.dp)
                                    .offset(y = 28.dp)
                                    .size(64.dp)
                                    .clip(CircleShape)
                                    .border(3.dp, MaterialTheme.colorScheme.surface, CircleShape)
                                    .background(Color(0xFFE2E8F0)),
                                contentAlignment = Alignment.Center
                            ) {
                                if (state.avatarUrl.isNotBlank()) {
                                    AsyncImage(
                                        model = state.avatarUrl,
                                        contentDescription = "Avatar",
                                        contentScale = ContentScale.Crop,
                                        modifier = Modifier.fillMaxSize()
                                    )
                                } else {
                                    Text(
                                        text = state.name.take(2).uppercase().ifBlank { "BP" },
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 20.sp,
                                        color = ConnectlyElectricBlue
                                    )
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(34.dp))

                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(
                                    text = state.name.ifBlank { "Your Page Name" },
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 17.sp,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                                Spacer(modifier = Modifier.width(4.dp))
                                Icon(
                                    Icons.Default.CheckCircle,
                                    contentDescription = "Verified Badge Preview",
                                    tint = ConnectlyElectricBlue,
                                    modifier = Modifier.size(16.dp)
                                )
                            }

                            Text(
                                text = "@${state.username.ifBlank { "page_handle" }} • ${state.category}",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )

                            if (state.bio.isNotBlank()) {
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(
                                    text = state.bio,
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurface,
                                    maxLines = 2
                                )
                            }

                            Spacer(modifier = Modifier.height(10.dp))

                            // Action CTA Button Preview
                            val actionLabel = ctaOptions.find { it.first == state.ctaType }?.second ?: "Send Message"
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = ConnectlyElectricBlue,
                                modifier = Modifier.fillMaxWidth().height(34.dp)
                            ) {
                                Row(
                                    modifier = Modifier.fillMaxSize(),
                                    horizontalArrangement = Arrangement.Center,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Outlined.TouchApp, contentDescription = null, tint = Color.White, modifier = Modifier.size(15.dp))
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text(actionLabel, color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }

            // STEP 1: IDENTITY & BASICS
            if (state.currentStep == 1) {
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            Text("Page Identity & Details", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("Enter the name and public handle of your business, organization or brand.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                            OutlinedTextField(
                                value = state.name,
                                onValueChange = { viewModel.onNameChanged(it) },
                                label = { Text("Page Name *") },
                                placeholder = { Text("e.g. Acme Tech Solutions") },
                                singleLine = true,
                                isError = state.name.isBlank(),
                                supportingText = {
                                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                        Text("Recognizable title for your brand", fontSize = 11.sp)
                                        Text("${state.name.length}/100", fontSize = 11.sp)
                                    }
                                },
                                modifier = Modifier.fillMaxWidth()
                            )

                            // Username with Live Availability Indicator
                            OutlinedTextField(
                                value = state.username,
                                onValueChange = { viewModel.onUsernameChanged(it) },
                                label = { Text("Page Username / Custom URL (@)") },
                                placeholder = { Text("e.g. acme_tech") },
                                singleLine = true,
                                trailingIcon = {
                                    if (state.isCheckingUsername) {
                                        CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                                    } else if (state.isUsernameAvailable == true) {
                                        Icon(Icons.Default.CheckCircle, contentDescription = "Available", tint = Color(0xFF10B981))
                                    } else if (state.isUsernameAvailable == false) {
                                        Icon(Icons.Default.Cancel, contentDescription = "Unavailable", tint = MaterialTheme.colorScheme.error)
                                    }
                                },
                                supportingText = {
                                    state.usernameMessage?.let { msg ->
                                        Text(
                                            text = msg,
                                            color = if (state.isUsernameAvailable == true) Color(0xFF10B981) else MaterialTheme.colorScheme.error,
                                            fontSize = 11.sp
                                        )
                                    }
                                },
                                modifier = Modifier.fillMaxWidth()
                            )

                            // Username suggestions if taken
                            if (state.usernameSuggestions.isNotEmpty()) {
                                Text("Suggested Available Handles:", fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
                                Row(
                                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                ) {
                                    state.usernameSuggestions.forEach { suggestion ->
                                        SuggestionChip(
                                            onClick = { viewModel.selectUsernameSuggestion(suggestion) },
                                            label = { Text("@$suggestion", fontSize = 11.sp) }
                                        )
                                    }
                                }
                            }

                            OutlinedTextField(
                                value = state.bio,
                                onValueChange = { viewModel.onBioChanged(it) },
                                label = { Text("Bio / Tagline (Short)") },
                                placeholder = { Text("Tell customers about your mission in 1-2 lines...") },
                                maxLines = 2,
                                supportingText = { Text("${state.bio.length}/255", fontSize = 11.sp) },
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = state.description,
                                onValueChange = { viewModel.onDescriptionChanged(it) },
                                label = { Text("Full Description (Optional)") },
                                placeholder = { Text("Detailed history, services, and company highlights...") },
                                maxLines = 4,
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                }
            }

            // STEP 2: CATEGORY & TAXONOMY
            if (state.currentStep == 2) {
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                            Text("Category & Industry", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("Select the category that best describes your Page. This helps customers discover your content.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                            Text("Popular Categories:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                popularCategories.chunked(2).forEach { rowCats ->
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        rowCats.forEach { cat ->
                                            val isSelected = state.category == cat
                                            FilterChip(
                                                selected = isSelected,
                                                onClick = { viewModel.onCategoryChanged(cat) },
                                                label = { Text(cat, fontSize = 11.sp) },
                                                leadingIcon = {
                                                    if (isSelected) {
                                                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(14.dp))
                                                    }
                                                },
                                                modifier = Modifier.weight(1f)
                                            )
                                        }
                                        if (rowCats.size == 1) {
                                            Spacer(modifier = Modifier.weight(1f))
                                        }
                                    }
                                }
                            }

                            OutlinedTextField(
                                value = state.subCategory,
                                onValueChange = { viewModel.onSubCategoryChanged(it) },
                                label = { Text("Secondary Subcategory / Specialization") },
                                placeholder = { Text("e.g. Cloud Architecture, Fine Dining, SaaS") },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                }
            }

            // STEP 3: CONTACT & DIGITAL PRESENCE
            if (state.currentStep == 3) {
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            Text("Contact & Presence", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("How customers and clients can connect with your business directly.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                            OutlinedTextField(
                                value = state.website,
                                onValueChange = { viewModel.onWebsiteChanged(it) },
                                label = { Text("Website") },
                                placeholder = { Text("https://example.com") },
                                leadingIcon = { Icon(Icons.Outlined.Language, contentDescription = null) },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = state.email,
                                onValueChange = { viewModel.onEmailChanged(it) },
                                label = { Text("Business Email") },
                                placeholder = { Text("contact@brand.com") },
                                leadingIcon = { Icon(Icons.Outlined.Email, contentDescription = null) },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = state.phone,
                                onValueChange = { viewModel.onPhoneChanged(it) },
                                label = { Text("Phone Number") },
                                placeholder = { Text("+880 1712-345678") },
                                leadingIcon = { Icon(Icons.Outlined.Phone, contentDescription = null) },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = state.whatsapp,
                                onValueChange = { viewModel.onWhatsappChanged(it) },
                                label = { Text("WhatsApp Business Number") },
                                placeholder = { Text("+880 1812-345678") },
                                leadingIcon = { Icon(Icons.Outlined.Chat, contentDescription = null) },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                }
            }

            // STEP 4: LOCATION & OPERATING ADDRESS
            if (state.currentStep == 4) {
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            Text("Location & Address", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("Help local customers find your storefront, office, or physical headquarters.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                            OutlinedTextField(
                                value = state.address,
                                onValueChange = { viewModel.onAddressChanged(it) },
                                label = { Text("Street Address") },
                                placeholder = { Text("House 12, Road 4, Gulshan-1") },
                                leadingIcon = { Icon(Icons.Outlined.Place, contentDescription = null) },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                OutlinedTextField(
                                    value = state.city,
                                    onValueChange = { viewModel.onCityChanged(it) },
                                    label = { Text("City / Town") },
                                    singleLine = true,
                                    modifier = Modifier.weight(1f)
                                )
                                OutlinedTextField(
                                    value = state.zipCode,
                                    onValueChange = { viewModel.onZipCodeChanged(it) },
                                    label = { Text("Postal / ZIP") },
                                    placeholder = { Text("1212") },
                                    singleLine = true,
                                    modifier = Modifier.weight(1f)
                                )
                            }

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                OutlinedTextField(
                                    value = state.stateOrDivision,
                                    onValueChange = { viewModel.onStateChanged(it) },
                                    label = { Text("State / Division") },
                                    singleLine = true,
                                    modifier = Modifier.weight(1f)
                                )
                                OutlinedTextField(
                                    value = state.country,
                                    onValueChange = { viewModel.onCountryChanged(it) },
                                    label = { Text("Country") },
                                    singleLine = true,
                                    modifier = Modifier.weight(1f)
                                )
                            }
                        }
                    }
                }
            }

            // STEP 5: ACTION CTA & BUSINESS HOURS
            if (state.currentStep == 5) {
                item {
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
                            Text("Action Button & Operating Hours", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Text("Choose the primary action button customers see on your page profile.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                            Text("Primary Call-To-Action (CTA):", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                ctaOptions.forEach { (key, label) ->
                                    val isSelected = state.ctaType == key
                                    Surface(
                                        shape = RoundedCornerShape(10.dp),
                                        color = if (isSelected) ConnectlyElectricBlue.copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                                        border = if (isSelected) androidx.compose.foundation.BorderStroke(1.dp, ConnectlyElectricBlue) else null,
                                        modifier = Modifier.fillMaxWidth().clickable { viewModel.onCtaTypeChanged(key) }
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            RadioButton(selected = isSelected, onClick = { viewModel.onCtaTypeChanged(key) })
                                            Spacer(modifier = Modifier.width(8.dp))
                                            Text(label, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal, fontSize = 13.sp)
                                        }
                                    }
                                }
                            }

                            if (state.ctaType in listOf("visit_website", "book_now", "learn_more")) {
                                OutlinedTextField(
                                    value = state.ctaUrl,
                                    onValueChange = { viewModel.onCtaUrlChanged(it) },
                                    label = { Text("Button Destination URL") },
                                    placeholder = { Text("https://example.com/order") },
                                    singleLine = true,
                                    modifier = Modifier.fillMaxWidth()
                                )
                            }

                            HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                            Text("Operating Hours:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                hourOptions.forEach { (key, label) ->
                                    val isSelected = state.businessHoursType == key
                                    Surface(
                                        shape = RoundedCornerShape(10.dp),
                                        color = if (isSelected) Color(0xFF10B981).copy(alpha = 0.12f) else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                                        border = if (isSelected) androidx.compose.foundation.BorderStroke(1.dp, Color(0xFF10B981)) else null,
                                        modifier = Modifier.fillMaxWidth().clickable { viewModel.onBusinessHoursTypeChanged(key) }
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            RadioButton(selected = isSelected, onClick = { viewModel.onBusinessHoursTypeChanged(key) })
                                            Spacer(modifier = Modifier.width(8.dp))
                                            Text(label, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal, fontSize = 13.sp)
                                        }
                                    }
                                }
                            }

                            HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                            Text("Branding & Images (Optional URLs):", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            OutlinedTextField(
                                value = state.avatarUrl,
                                onValueChange = { viewModel.onAvatarUrlChanged(it) },
                                label = { Text("Profile Logo / Avatar URL") },
                                placeholder = { Text("https://.../logo.png") },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = state.coverUrl,
                                onValueChange = { viewModel.onCoverUrlChanged(it) },
                                label = { Text("Cover Banner Image URL") },
                                placeholder = { Text("https://.../banner.jpg") },
                                singleLine = true,
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                    }
                }
            }

            item {
                Spacer(modifier = Modifier.height(30.dp))
            }
        }
    }
}

