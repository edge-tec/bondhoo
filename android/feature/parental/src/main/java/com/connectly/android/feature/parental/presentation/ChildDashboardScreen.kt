package com.connectly.android.feature.parental.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.feature.parental.model.DevicePolicy
import com.connectly.android.feature.parental.model.ScreenTimeSummary

data class ChildDashboardUiState(
    val familyName: String = "My Family",
    val deviceName: String = "This Tablet",
    val isConnected: Boolean = true,
    val todayUsedMinutes: Long = 45,
    val dailyLimitMinutes: Long = 120,
    val upcomingBedtime: String = "20:30",
    val activeRestrictionsCount: Int = 2,
    val lastSyncTime: String = "Just now"
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChildDashboardScreen(
    state: ChildDashboardUiState,
    onRequestMoreTimeClick: () -> Unit = {},
    onPrivacyInfoClick: () -> Unit = {}
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Connectly Kids Safety",
                        fontWeight = FontWeight.Bold,
                        fontSize = 20.sp
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Section 1: Family Connection & Device Status
            item {
                Card(
                    shape = RoundedCornerShape(24.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(20.dp),
                        verticalAlignment = Alignment.CenterVertifally,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Column {
                            Text(
                                text = state.familyName,
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onPrimaryContainer
                            )
                            Text(
                                text = "${state.deviceName} • Active & Protected",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.8f)
                            )
                        }
                        Surface(
                            shape = RoundedCornerShape(12.dp),
                            color = Color(0xFF10B981)
                        ) {
                            Text(
                                text = "ONLINE",
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }
                }
            }

            // Section 2: Screen Time Usage & Daily Quota
            item {
                Card(
                    shape = RoundedCornerShape(24.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text(
                            text = "Today's Screen Time",
                            fontSize = 14.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(
                            verticalAlignment = Alignment.Bottom,
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Text(
                                text = "${state.todayUsedMinutes}",
                                fontSize = 36.sp,
                                fontWeight = FontWeight.ExtraBold,
                                color = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = "of ${state.dailyLimitMinutes} mins allowed",
                                fontSize = 14.sp,
                                modifier = Modifier.padding(bottom = 6.dp)
                            )
                        }

                        val progress = (state.todayUsedMinutes.toFloat() / state.dailyLimitMinutes.toFloat()).coerceIn(0f, 1f)
                        Spacer(modifier = Modifier.height(12.dp))
                        LinearProgressIndicator(
                            progress = { progress },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(8.dp),
                            color = if (progress > 0.9f) Color(0xFFEF4444) else MaterialTheme.colorScheme.primary,
                        )

                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = onRequestMoreTimeClick,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp)
                        ) {
                            Text(text = "Ask Parents for Extra Time")
                        }
                    }
                }
            }

            // Section 3: Bedtime Schedule & Restrictions
            item {
                Card(
                    shape = RoundedCornerShape(24.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text(
                            text = "Tonight's Bedtime",
                            fontSize = 14.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                        Text(
                            text = state.upcomingBedtime,
                            fontSize = 24.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.secondary
                        )
                        Text(
                            text = "Device locks automatically during curfew for healthy sleep.",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.8f),
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                }
            }

            // Section 4: Privacy & Zero Covert Monitoring Disclosure
            item {
                Card(
                    shape = RoundedCornerShape(24.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text(
                            text = "Your Privacy is Respected",
                            fontSize = 14.sp,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Connectly only counts app usage time. We never record your screen, camera, microphone, or private messages.",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(top = 4.dp, bottom = 8.dp)
                        )
                        OutlinedButton(
                            onClick = onPrivacyInfoClick,
                            shape = RoundedCornerShape(14.dp)
                        ) {
                            Text(text = "Read Full Child Safety Policy")
                        }
                    }
                }
            }

            // Section 5: Synchronization Status Footer
            item {
                Text(
                    text = "Last synced with family: ${state.lastSyncTime}",
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.outline,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 24.dp)
                        .wrapContentWidth(Alignment.CenterHorizontally)
                )
            }
        }
    }
}

