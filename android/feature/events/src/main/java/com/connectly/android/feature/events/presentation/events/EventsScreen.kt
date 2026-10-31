package com.connectly.android.feature.events.presentation.events

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.StarBorder
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventsScreen(
    onNavigateToEventDetails: (Long) -> Unit = {},
    onCreateEvent: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: EventsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Events",
                        fontWeight = FontWeight.Bold,
                        fontSize = 24.sp,
                        color = TextPrimaryLight
                    )
                },
                actions = {
                    IconButton(onClick = { /* Search */ }) {
                        Icon(Icons.Default.Search, contentDescription = "Search Events", tint = TextPrimaryLight)
                    }
                    IconButton(onClick = onCreateEvent) {
                        Icon(Icons.Default.CalendarMonth, contentDescription = "Create Event", tint = TextPrimaryLight)
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
        ) {
            // Segmented Filter Tabs: Discover, My Events, Past (Matching Screen 7)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FilterTabButton(
                    label = "Discover",
                    isSelected = state.selectedTab == "discover",
                    onClick = { viewModel.handleIntent(EventsIntent.SelectTab("discover")) }
                )
                FilterTabButton(
                    label = "My Events",
                    isSelected = state.selectedTab == "my_events",
                    onClick = { viewModel.handleIntent(EventsIntent.SelectTab("my_events")) }
                )
                FilterTabButton(
                    label = "Past",
                    isSelected = state.selectedTab == "past",
                    onClick = { viewModel.handleIntent(EventsIntent.SelectTab("past")) }
                )
            }

            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
                contentPadding = PaddingValues(vertical = 8.dp)
            ) {
                items(state.events, key = { it.id }) { event ->
                    EventCardItem(
                        event = event,
                        onInterestedClick = { viewModel.handleIntent(EventsIntent.ToggleRsvp(event.id, RsvpStatus.INTERESTED)) },
                        onGoingClick = { viewModel.handleIntent(EventsIntent.ToggleRsvp(event.id, RsvpStatus.GOING)) },
                        onClick = { onNavigateToEventDetails(event.id) }
                    )
                }
            }
        }
    }
}

@Composable
private fun FilterTabButton(
    label: String,
    isSelected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Button(
        onClick = onClick,
        colors = ButtonDefaults.buttonColors(
            containerColor = if (isSelected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
            contentColor = if (isSelected) Color.White else TextPrimaryLight
        ),
        shape = RoundedCornerShape(20.dp),
        contentPadding = PaddingValues(horizontal = 18.dp, vertical = 8.dp),
        modifier = modifier
    ) {
        Text(text = label, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium, fontSize = 13.sp)
    }
}

@Composable
private fun EventCardItem(
    event: Event,
    onInterestedClick: () -> Unit,
    onGoingClick: () -> Unit,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            // Header with Date Badge & Title
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.Top
            ) {
                // Calendar Date Badge (e.g. NOV 15)
                Column(
                    modifier = Modifier
                        .width(48.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .background(MaterialTheme.colorScheme.surfaceVariant),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(
                        text = event.dateMonth,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFFEF4444),
                        modifier = Modifier.padding(top = 4.dp)
                    )
                    Text(
                        text = event.dateDay,
                        fontSize = 18.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = TextPrimaryLight,
                        modifier = Modifier.padding(bottom = 4.dp)
                    )
                }

                Spacer(modifier = Modifier.width(14.dp))

                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = event.title,
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp,
                        color = TextPrimaryLight
                    )
                    Text(
                        text = event.timeFormatted,
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(top = 2.dp)
                    )
                    Text(
                        text = "📍 ${event.location}",
                        fontSize = 12.sp,
                        color = TextSecondaryLight,
                        modifier = Modifier.padding(top = 2.dp)
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // Event Hero Image Banner
            AsyncImage(
                model = event.bannerUrl,
                contentDescription = event.title,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(150.dp)
                    .clip(RoundedCornerShape(12.dp))
            )

            Spacer(modifier = Modifier.height(14.dp))

            // RSVP Action Buttons: [★ Interested] & [✔ Going]
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                val isInterested = event.myRsvp == RsvpStatus.INTERESTED
                OutlinedButton(
                    onClick = onInterestedClick,
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.outlinedButtonColors(
                        containerColor = if (isInterested) ConnectlyBlue.copy(alpha = 0.1f) else Color.Transparent,
                        contentColor = if (isInterested) ConnectlyBlue else TextPrimaryLight
                    ),
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(
                        imageVector = if (isInterested) Icons.Filled.Star else Icons.Outlined.StarBorder,
                        contentDescription = null,
                        modifier = Modifier.size(16.dp),
                        tint = if (isInterested) ConnectlyBlue else TextSecondaryLight
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(text = "Interested", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                }

                val isGoing = event.myRsvp == RsvpStatus.GOING
                Button(
                    onClick = onGoingClick,
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (isGoing) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant,
                        contentColor = if (isGoing) Color.White else TextPrimaryLight
                    ),
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(
                        imageVector = Icons.Default.Check,
                        contentDescription = null,
                        modifier = Modifier.size(16.dp),
                        tint = if (isGoing) Color.White else TextSecondaryLight
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(text = "Going", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                }
            }
        }
    }
}

