package com.connectly.android.feature.groups.presentation.pages.monetization

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.outlined.AccountBalance
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Info
import androidx.compose.material.icons.outlined.Payments
import androidx.compose.material.icons.outlined.Star
import androidx.compose.material.icons.outlined.Videocam
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
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageMonetizationScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PageMonetizationViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(pageId) {
        if (state.pageId != pageId) {
            viewModel.loadMonetization(pageId)
        }
    }

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let { msg ->
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

    // Active Dialogs & Bottom Sheets
    when (state.activeDialog) {
        MonetizationDialogType.ELIGIBILITY_DETAILS -> {
            EligibilityDetailsDialog(
                monetization = state.monetization,
                onDismiss = { viewModel.closeDialog() },
                onRequestReview = { viewModel.requestPolicyReview() }
            )
        }
        MonetizationDialogType.EARNINGS_BREAKDOWN -> {
            EarningsBreakdownDialog(
                monetization = state.monetization,
                onDismiss = { viewModel.closeDialog() }
            )
        }
        MonetizationDialogType.PAYOUT_SCHEDULE -> {
            PayoutScheduleDialog(
                monetization = state.monetization,
                onDismiss = { viewModel.closeDialog() },
                onConfigurePayout = { viewModel.openDialog(MonetizationDialogType.PAYOUT_SETUP) }
            )
        }
        MonetizationDialogType.IN_STREAM_ADS -> {
            InStreamAdsDialog(
                isEnabled = state.monetization.in_stream_ads_enabled,
                isSaving = state.isSaving,
                onDismiss = { viewModel.closeDialog() },
                onToggle = { enabled -> viewModel.toggleInStreamAds(enabled) }
            )
        }
        MonetizationDialogType.BRAND_COLLABS -> {
            BrandCollabsDialog(
                isEnabled = state.monetization.brand_collabs_enabled,
                isSaving = state.isSaving,
                onDismiss = { viewModel.closeDialog() },
                onToggle = { enabled -> viewModel.toggleBrandCollabs(enabled) }
            )
        }
        MonetizationDialogType.SUBSCRIPTIONS_STARS -> {
            SubscriptionsAndStarsDialog(
                subscriptionsEnabled = state.monetization.subscriptions_enabled,
                starsEnabled = state.monetization.stars_enabled,
                isSaving = state.isSaving,
                onDismiss = { viewModel.closeDialog() },
                onSave = { subs, stars -> viewModel.toggleSubscriptions(subs, stars) }
            )
        }
        MonetizationDialogType.PAYOUT_SETUP -> {
            PayoutAccountDialog(
                currentMethod = state.monetization.payout_method ?: "Bank Wire (SWIFT)",
                currentName = state.monetization.payout_account_name ?: "TechPulse Media Ltd",
                currentNumber = state.monetization.payout_account_number ?: "BD02SCBL00001234567890",
                currentBank = state.monetization.bank_name ?: "Standard Chartered Bank PLC",
                currentSwift = state.monetization.swift_code ?: "SCBLBDDX",
                isSaving = state.isSaving,
                onDismiss = { viewModel.closeDialog() },
                onSave = { method, name, num, bank, swift ->
                    viewModel.configurePayout(method, name, num, bank, swift)
                }
            )
        }
        MonetizationDialogType.PAYOUT_STATEMENTS -> {
            PayoutStatementsDialog(
                monetization = state.monetization,
                onDismiss = { viewModel.closeDialog() },
                onDownloadStatement = { statementId -> viewModel.downloadStatement(statementId) }
            )
        }
        null -> {}
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Monetization Hub", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(
                        onClick = { viewModel.loadMonetization(pageId) },
                        enabled = !state.isLoading
                    ) {
                        Icon(Icons.Filled.Refresh, contentDescription = "Refresh")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) },
        modifier = modifier
    ) { paddingValues ->
        if (state.isLoading && state.monetization.estimated_earnings == 0.0 && !state.monetization.is_eligible) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // 1. Status / Eligibility Card
                item {
                    val isEligible = state.monetization.is_eligible
                    val statusColor = if (isEligible) Color(0xFF10B981) else ConnectlyAmber
                    Card(
                        shape = RoundedCornerShape(14.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp)
                            .clickable { viewModel.openDialog(MonetizationDialogType.ELIGIBILITY_DETAILS) }
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(
                                    imageVector = if (isEligible) Icons.Outlined.CheckCircle else Icons.Filled.Info,
                                    contentDescription = null,
                                    tint = statusColor
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = if (isEligible) "Page is Eligible for Monetization" else "Monetization In Review",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = statusColor
                                )
                            }
                            Text(
                                text = if (isEligible)
                                    "Your Page meets all Partner Monetization Policies and content guidelines."
                                else
                                    "Your Page is under review for Partner Monetization Policies. Tap to view progress.",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                // 2. Earnings & Revenue Summary Section
                item {
                    Text("Earnings & Revenue Summary", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        val formattedEarnings = String.format(Locale.US, "$%,.2f", state.monetization.estimated_earnings)
                        val formattedPayout = String.format(Locale.US, "$%,.2f", state.monetization.net_payout)

                        // Estimated Earnings Card (Clickable)
                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            modifier = Modifier
                                .weight(1f)
                                .clickable { viewModel.openDialog(MonetizationDialogType.EARNINGS_BREAKDOWN) }
                        ) {
                            Column(modifier = Modifier.padding(14.dp)) {
                                Text("Estimated Earnings", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(formattedEarnings, fontWeight = FontWeight.Bold, fontSize = 20.sp, color = ConnectlyElectricBlue)
                                Text("This month", fontSize = 10.sp, color = Color(0xFF10B981))
                            }
                        }

                        // Next Net Payout Card (Clickable)
                        Card(
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            modifier = Modifier
                                .weight(1f)
                                .clickable { viewModel.openDialog(MonetizationDialogType.PAYOUT_SCHEDULE) }
                        ) {
                            Column(modifier = Modifier.padding(14.dp)) {
                                Text("Next Net Payout", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(formattedPayout, fontWeight = FontWeight.Bold, fontSize = 20.sp, color = MaterialTheme.colorScheme.onSurface)
                                Text("Payout date: ${state.monetization.payout_date}", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                    }
                }

                // 3. Monetization Products & Tools Section
                item {
                    Text("Monetization Products & Tools", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                item {
                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier.padding(14.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            // 1. In-Stream Ads
                            MonetizationToolRow(
                                title = "In-Stream Ads for Videos",
                                description = "Earn revenue from ads shown in your video content.",
                                active = state.monetization.in_stream_ads_enabled,
                                onClick = { viewModel.openDialog(MonetizationDialogType.IN_STREAM_ADS) }
                            )

                            HorizontalDivider()

                            // 2. Brand Content & Collaborations
                            MonetizationToolRow(
                                title = "Brand Content & Collaborations",
                                description = "Connect with brands for sponsored posts and video campaigns.",
                                active = state.monetization.brand_collabs_enabled,
                                onClick = { viewModel.openDialog(MonetizationDialogType.BRAND_COLLABS) }
                            )

                            HorizontalDivider()

                            // 3. Fan Subscriptions & Stars
                            val fanMonetized = state.monetization.subscriptions_enabled || state.monetization.stars_enabled
                            MonetizationToolRow(
                                title = "Fan Subscriptions & Stars",
                                description = "Monetize loyal audience support through direct monthly contributions.",
                                active = fanMonetized,
                                onClick = { viewModel.openDialog(MonetizationDialogType.SUBSCRIPTIONS_STARS) }
                            )
                        }
                    }
                }

                // 4. Payout Method & Settings Section
                item {
                    Text("Payout Method & Settings", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                item {
                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween,
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Outlined.Payments, contentDescription = null, tint = ConnectlyElectricBlue)
                                    Spacer(modifier = Modifier.width(10.dp))
                                    Column {
                                        Text(
                                            text = state.monetization.payout_method ?: "Bank Wire (SWIFT)",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 14.sp
                                        )
                                        Text(
                                            text = "Status: ${state.monetization.payout_status}",
                                            fontSize = 11.sp,
                                            color = if (state.monetization.payout_status == "Connected" || state.monetization.payout_status == "Active") Color(0xFF10B981) else Color(0xFF64748B)
                                        )
                                    }
                                }
                                Surface(
                                    color = Color(0xFFDCFCE7),
                                    shape = RoundedCornerShape(6.dp)
                                ) {
                                    Text(
                                        text = "VERIFIED",
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFF166534),
                                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                    )
                                }
                            }

                            if (!state.monetization.payout_account_name.isNullOrBlank() || !state.monetization.payout_account_number.isNullOrBlank()) {
                                HorizontalDivider()
                                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    state.monetization.payout_account_name?.let {
                                        Text("Beneficiary: $it", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                    state.monetization.payout_account_number?.let {
                                        Text("Account / Wallet: $it", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                    state.monetization.bank_name?.let {
                                        Text("Institution: $it", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                }
                            }

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                Button(
                                    onClick = { viewModel.openDialog(MonetizationDialogType.PAYOUT_SETUP) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(8.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
                                ) {
                                    Text("Configure Payout Account", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                                }

                                OutlinedButton(
                                    onClick = { viewModel.openDialog(MonetizationDialogType.PAYOUT_STATEMENTS) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(8.dp)
                                ) {
                                    Text("Payout Statements", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                                }
                            }
                        }
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(24.dp))
                }
            }
        }
    }
}

@Composable
private fun MonetizationToolRow(
    title: String,
    description: String,
    active: Boolean,
    onClick: () -> Unit
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 4.dp)
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Text(description, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        Spacer(modifier = Modifier.width(12.dp))
        Surface(
            color = if (active) Color(0xFFDCFCE7) else Color(0xFFF1F5F9),
            shape = RoundedCornerShape(6.dp),
            modifier = Modifier.clickable(onClick = onClick)
        ) {
            Text(
                text = if (active) "ACTIVE" else "SETUP",
                fontSize = 10.sp,
                fontWeight = FontWeight.Bold,
                color = if (active) Color(0xFF166534) else Color(0xFF475569),
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
            )
        }
    }
}

// ---------------------------------------------------------------------------
// DIALOGS & ACTION SHEETS
// ---------------------------------------------------------------------------

@Composable
private fun EligibilityDetailsDialog(
    monetization: com.connectly.android.core.network.api.PageMonetizationStatusDto,
    onDismiss: () -> Unit,
    onRequestReview: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Color(0xFF10B981))
                Spacer(modifier = Modifier.width(8.dp))
                Text("Monetization Policy Health", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Your Page is in good standing and meets all Partner Monetization Policies & Content Guidelines:",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                                Text("Followers Threshold:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                                Text("${monetization.current_followers} / ${monetization.required_followers}", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                            }
                            LinearProgressIndicator(
                                progress = { (monetization.current_followers.toFloat() / monetization.required_followers.coerceAtLeast(1).toFloat()).coerceIn(0f, 1f) },
                                modifier = Modifier.fillMaxWidth().height(6.dp),
                                color = Color(0xFF10B981),
                                trackColor = Color(0xFFE2E8F0)
                            )
                        }

                        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                                Text("Eligible Watch Minutes:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                                Text("${monetization.current_watch_minutes} / ${monetization.required_watch_minutes}", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                            }
                            LinearProgressIndicator(
                                progress = { (monetization.current_watch_minutes.toFloat() / monetization.required_watch_minutes.coerceAtLeast(1L).toFloat()).coerceIn(0f, 1f) },
                                modifier = Modifier.fillMaxWidth().height(6.dp),
                                color = Color(0xFF10B981),
                                trackColor = Color(0xFFE2E8F0)
                            )
                        }

                        HorizontalDivider()

                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Partner Policy Status:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Text(monetization.policy_status, fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                        }

                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Content Violations:", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                            Text("0 Strikes (Clean)", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                        }
                    }
                }

                Text(
                    "Publishing original high-retention video content ensures consistent advertising fill rate and unlocks premium brand sponsorships.",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        confirmButton = {
            Button(
                onClick = onDismiss,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                Text("Done", fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = {
            OutlinedButton(onClick = onRequestReview) {
                Text("Request Policy Review", fontSize = 12.sp)
            }
        }
    )
}

@Composable
private fun EarningsBreakdownDialog(
    monetization: com.connectly.android.core.network.api.PageMonetizationStatusDto,
    onDismiss: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.Payments, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Estimated Revenue Breakdown", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Surface(
                    shape = RoundedCornerShape(10.dp),
                    color = ConnectlyElectricBlue.copy(alpha = 0.08f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp)) {
                        Text("Total Estimated Earnings (This Month)", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            String.format(Locale.US, "$%,.2f USD", monetization.estimated_earnings),
                            fontSize = 24.sp,
                            fontWeight = FontWeight.Bold,
                            color = ConnectlyElectricBlue
                        )
                        Text("+18.4% compared to previous 30 days", fontSize = 11.sp, color = Color(0xFF10B981), fontWeight = FontWeight.SemiBold)
                    }
                }

                Text("Revenue Streams", fontWeight = FontWeight.Bold, fontSize = 13.sp)

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("🎬 In-Stream Video Ads:", fontSize = 12.sp)
                            Text(String.format(Locale.US, "$%,.2f", monetization.in_stream_revenue), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("🤝 Brand Collaborations:", fontSize = 12.sp)
                            Text(String.format(Locale.US, "$%,.2f", monetization.brand_collabs_revenue), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("⭐ Fan Subscriptions:", fontSize = 12.sp)
                            Text(String.format(Locale.US, "$%,.2f", monetization.subscriptions_revenue), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("✨ Stars & Virtual Gifts:", fontSize = 12.sp)
                            Text(String.format(Locale.US, "$%,.2f", monetization.stars_revenue), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }

                Text("Performance Metrics", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Text("Monetizable Views", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Text(String.format(Locale.US, "%,d", monetization.monetizable_views), fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                    Column {
                        Text("Average CPM", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Text(String.format(Locale.US, "$%.2f", monetization.average_cpm), fontSize = 13.sp, fontWeight = FontWeight.Bold, color = ConnectlyGreen)
                    }
                    Column {
                        Text("Effective RPM", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Text(String.format(Locale.US, "$%.2f", monetization.average_rpm), fontSize = 13.sp, fontWeight = FontWeight.Bold, color = ConnectlyElectricBlue)
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = onDismiss,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                Text("Close", fontWeight = FontWeight.Bold)
            }
        }
    )
}

@Composable
private fun PayoutScheduleDialog(
    monetization: com.connectly.android.core.network.api.PageMonetizationStatusDto,
    onDismiss: () -> Unit,
    onConfigurePayout: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.AccountBalance, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Next Scheduled Payout", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Surface(
                    shape = RoundedCornerShape(10.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Scheduled Amount:", fontSize = 12.sp)
                            Text(String.format(Locale.US, "$%,.2f USD", monetization.net_payout), fontSize = 15.sp, fontWeight = FontWeight.Bold, color = ConnectlyElectricBlue)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Scheduled Date:", fontSize = 12.sp)
                            Text(monetization.payout_date, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Payout Status:", fontSize = 12.sp)
                            Text("Scheduled & Verified ✓", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Transfer Method:", fontSize = 12.sp)
                            Text(monetization.payout_method ?: "Bank Wire (SWIFT)", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Minimum Threshold:", fontSize = 12.sp)
                            Text("$100.00 (Eligible ✓)", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                        }
                        Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                            Text("Tax Form (W-8BEN):", fontSize = 12.sp)
                            Text("Verified on File", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                        }
                    }
                }

                Text(
                    "Monthly payouts are automatically transferred to your configured account on the 15th of every month.",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        confirmButton = {
            Button(
                onClick = onDismiss,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                Text("Close", fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = {
            TextButton(onClick = onConfigurePayout) {
                Text("Edit Payout Account")
            }
        }
    )
}

@Composable
private fun InStreamAdsDialog(
    isEnabled: Boolean,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onToggle: (Boolean) -> Unit
) {
    var checked by remember { mutableStateOf(isEnabled) }
    var adPlacementMode by remember { mutableStateOf("Automatic Ad Breaks") }
    val placementModes = listOf("Automatic Ad Breaks", "Manual Cue Points")

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.Videocam, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("In-Stream Ads for Videos", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Deliver pre-roll, mid-roll, and overlay banner ads during your video playback to earn recurring advertising revenue.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text("Enable In-Stream Ads", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                            Text("Insert ad breaks into videos over 1 minute", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                        Switch(
                            checked = checked,
                            onCheckedChange = { checked = it },
                            enabled = !isSaving
                        )
                    }
                }

                if (checked) {
                    Text("Ad Placement Strategy", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        placementModes.forEach { mode ->
                            FilterChip(
                                selected = adPlacementMode == mode,
                                onClick = { adPlacementMode = mode },
                                label = { Text(mode, fontSize = 11.sp) }
                            )
                        }
                    }
                }

                Text(
                    "Estimated CPM: $2.50 - $4.80 USD per 1,000 monetizable plays.",
                    fontSize = 12.sp,
                    color = ConnectlyGreen,
                    fontWeight = FontWeight.SemiBold
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onToggle(checked) },
                enabled = !isSaving,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                if (isSaving) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                } else {
                    Text("Save Changes")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun BrandCollabsDialog(
    isEnabled: Boolean,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onToggle: (Boolean) -> Unit
) {
    var checked by remember { mutableStateOf(isEnabled) }
    var marketplaceDiscovery by remember { mutableStateOf(true) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.CheckCircle, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Brand Content & Collabs", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Tag brand partners in your business posts with the 'Paid Partnership' label, unlocking brand collaboration sponsorships.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Brand Collaboration Tagging", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text("Allow approved brand partners to sponsor posts", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(
                                checked = checked,
                                onCheckedChange = { checked = it },
                                enabled = !isSaving
                            )
                        }

                        HorizontalDivider()

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Creator Marketplace Discovery", fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                Text("Allow verified brands to find and contact your page", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(
                                checked = marketplaceDiscovery,
                                onCheckedChange = { marketplaceDiscovery = it },
                                enabled = !isSaving
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onToggle(checked) },
                enabled = !isSaving,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                if (isSaving) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                } else {
                    Text("Save Changes")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun SubscriptionsAndStarsDialog(
    subscriptionsEnabled: Boolean,
    starsEnabled: Boolean,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onSave: (subs: Boolean, stars: Boolean) -> Unit
) {
    var subs by remember { mutableStateOf(subscriptionsEnabled) }
    var stars by remember { mutableStateOf(starsEnabled) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.Star, contentDescription = null, tint = ConnectlyAmber)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Fan Subscriptions & Stars", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Monetize your most dedicated followers through recurring monthly fan club badges and Stars tipping during live broadcasts.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Monthly Subscriptions ($4.99/mo)", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                Text("Subscribers receive exclusive badges and supporter posts", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = subs, onCheckedChange = { subs = it }, enabled = !isSaving)
                        }

                        HorizontalDivider()

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Stars & Virtual Gifts", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                Text("Receive Stars tips (1 Star = $0.01 payout)", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Switch(checked = stars, onCheckedChange = { stars = it }, enabled = !isSaving)
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onSave(subs, stars) },
                enabled = !isSaving,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                if (isSaving) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                } else {
                    Text("Save Changes")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun PayoutAccountDialog(
    currentMethod: String,
    currentName: String,
    currentNumber: String,
    currentBank: String,
    currentSwift: String,
    isSaving: Boolean,
    onDismiss: () -> Unit,
    onSave: (method: String, name: String, number: String, bank: String?, swift: String?) -> Unit
) {
    val methods = listOf("Bank Wire (SWIFT)", "bKash", "Nagad")
    var selectedMethod by remember { mutableStateOf(if (currentMethod in methods) currentMethod else "Bank Wire (SWIFT)") }
    var accountName by remember { mutableStateOf(currentName) }
    var accountNumber by remember { mutableStateOf(currentNumber) }
    var bankName by remember { mutableStateOf(currentBank) }
    var swiftCode by remember { mutableStateOf(currentSwift) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.AccountBalance, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Configure Payout Account", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Monthly creator earnings are disbursed on the 15th to your selected payout recipient.",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Text("Payout Method", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    methods.forEach { method ->
                        FilterChip(
                            selected = selectedMethod == method,
                            onClick = { selectedMethod = method },
                            label = { Text(method, fontSize = 11.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = accountName,
                    onValueChange = { accountName = it },
                    label = { Text("Beneficiary / Account Holder Name") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = accountNumber,
                    onValueChange = { accountNumber = it },
                    label = { Text(if (selectedMethod == "Bank Wire (SWIFT)") "IBAN / Bank Account Number" else "$selectedMethod Mobile Number") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                if (selectedMethod == "Bank Wire (SWIFT)") {
                    OutlinedTextField(
                        value = bankName,
                        onValueChange = { bankName = it },
                        label = { Text("Bank / Financial Institution Name") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )

                    OutlinedTextField(
                        value = swiftCode,
                        onValueChange = { swiftCode = it },
                        label = { Text("SWIFT / BIC / Routing Code") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }
        },
        confirmButton = {
            Button(
                onClick = { onSave(selectedMethod, accountName, accountNumber, bankName, swiftCode) },
                enabled = !isSaving && accountName.isNotBlank() && accountNumber.isNotBlank(),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                if (isSaving) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), color = Color.White, strokeWidth = 2.dp)
                } else {
                    Text("Save Payout Account")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss, enabled = !isSaving) {
                Text("Cancel")
            }
        }
    )
}

@Composable
private fun PayoutStatementsDialog(
    monetization: com.connectly.android.core.network.api.PageMonetizationStatusDto,
    onDismiss: () -> Unit,
    onDownloadStatement: (String) -> Unit
) {
    val statements = listOf(
        Triple("Oct 15, 2026", "$1,120.00", "BND-STMT-2026-10"),
        Triple("Sep 15, 2026", "$895.50", "BND-STMT-2026-09"),
        Triple("Aug 15, 2026", "$740.00", "BND-STMT-2026-08")
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Outlined.Payments, contentDescription = null, tint = ConnectlyElectricBlue)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Monetization Statements", fontWeight = FontWeight.Bold, fontSize = 18.sp)
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    "Official disbursement statements and tax documentation for your page.",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Text("Disbursement History", fontWeight = FontWeight.Bold, fontSize = 13.sp)

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        statements.forEachIndexed { index, (date, amount, stmtId) ->
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column {
                                    Text(date, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                                    Text("Disbursed to ${monetization.payout_method ?: "Bank Wire"}", fontSize = 10.sp, color = Color(0xFF10B981))
                                }
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text(amount, fontSize = 13.sp, fontWeight = FontWeight.Bold)
                                    Spacer(modifier = Modifier.width(6.dp))
                                    TextButton(
                                        onClick = { onDownloadStatement(stmtId) },
                                        contentPadding = PaddingValues(horizontal = 6.dp, vertical = 2.dp)
                                    ) {
                                        Text("PDF", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                            if (index < statements.size - 1) {
                                HorizontalDivider()
                            }
                        }
                    }
                }

                Text("Tax Documentation", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = Color(0xFFDCFCE7).copy(alpha = 0.6f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("2025 Form W-8BEN / 1099-K", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFF166534))
                            Text("Verified & Submitted", fontSize = 10.sp, color = Color(0xFF166534))
                        }
                        TextButton(
                            onClick = { onDownloadStatement("TAX-W8BEN-2025") },
                            contentPadding = PaddingValues(horizontal = 6.dp, vertical = 2.dp)
                        ) {
                            Text("View Tax PDF", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF166534))
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = onDismiss,
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue)
            ) {
                Text("Close", fontWeight = FontWeight.Bold)
            }
        }
    )
}
