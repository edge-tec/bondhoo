package com.connectly.android.feature.profile.presentation.privacy

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PrivacySettingsScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: PrivacySettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(state.isSaved) {
        if (state.isSaved) {
            onNavigateBack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Privacy Settings", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    TextButton(
                        onClick = { viewModel.handleIntent(PrivacySettingsIntent.SaveSettings) },
                        enabled = !state.isLoading
                    ) {
                        Text("Save", fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(16.dp)
        ) {
            Text(
                "Who can see your profile & posts?",
                fontWeight = FontWeight.Bold,
                fontSize = 16.sp,
                color = TextPrimaryLight
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                "Control who has access to view your posts, media and profile updates.",
                fontSize = 13.sp,
                color = TextSecondaryLight
            )

            Spacer(modifier = Modifier.height(16.dp))

            val options = listOf(
                "public" to "Public (Everyone on Connectly)",
                "followers" to "Followers Only",
                "friends" to "Friends Only",
                "only_me" to "Only Me (Private)"
            )

            options.forEach { (key, label) ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { viewModel.handleIntent(PrivacySettingsIntent.VisibilityChanged(key)) }
                        .padding(vertical = 12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    RadioButton(
                        selected = state.profileVisibility == key,
                        onClick = { viewModel.handleIntent(PrivacySettingsIntent.VisibilityChanged(key)) },
                        colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(text = label, fontSize = 15.sp)
                }
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))

            Text("Tagging & Mentions", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            Spacer(modifier = Modifier.height(8.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text("Allow friends to tag you", fontWeight = FontWeight.Medium, fontSize = 14.sp)
                    Text("When enabled, friends can tag you in posts and stories", fontSize = 12.sp, color = TextSecondaryLight)
                }
                Switch(
                    checked = state.allowTagging,
                    onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.AllowTaggingChanged(it)) }
                )
            }

            if (state.errorMessage != null) {
                Spacer(modifier = Modifier.height(16.dp))
                Text(state.errorMessage!!, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
            }

            if (state.isLoading) {
                Spacer(modifier = Modifier.height(16.dp))
                CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
            }
        }
    }
}
