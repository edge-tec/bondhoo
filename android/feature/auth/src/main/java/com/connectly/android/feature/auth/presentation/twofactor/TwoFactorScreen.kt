package com.connectly.android.feature.auth.presentation.twofactor

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Security
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.connectly.android.core.designsystem.component.ConnectlyButton
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

@Composable
fun TwoFactorRoute(
    sessionToken: String,
    onNavigateToHome: () -> Unit,
    onBack: () -> Unit,
    viewModel: TwoFactorViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) {
        viewModel.effect.collect { effect ->
            when (effect) {
                is TwoFactorUiEffect.NavigateToHome -> onNavigateToHome()
                is TwoFactorUiEffect.ShowToast -> { /* Show Toast */ }
            }
        }
    }

    TwoFactorScreen(
        uiState = uiState,
        onVerifyClick = { code ->
            viewModel.handleIntent(TwoFactorUiIntent.Verify(code, sessionToken))
        },
        onResendClick = { viewModel.handleIntent(TwoFactorUiIntent.ResendCode) },
        onBack = onBack
    )
}

@Composable
fun TwoFactorScreen(
    uiState: TwoFactorUiState,
    onVerifyClick: (String) -> Unit,
    onResendClick: () -> Unit,
    onBack: () -> Unit
) {
    var codeInput by remember { mutableStateOf("") }
    val isLoading = uiState is TwoFactorUiState.Loading

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .statusBarsPadding()
            .navigationBarsPadding()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        IconButton(onClick = onBack, modifier = Modifier.align(Alignment.Start)) {
            Icon(Icons.Default.ArrowBack, contentDescription = "Back")
        }

        Spacer(modifier = Modifier.height(24.dp))

        Icon(
            imageVector = Icons.Default.Security,
            contentDescription = null,
            tint = ConnectlyBlue,
            modifier = Modifier.size(56.dp)
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "Two-Factor Verification",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold
        )

        Text(
            text = "Enter the 6-digit authentication code from your authenticator app or SMS.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
            textAlign = TextAlign.Center,
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
        )

        Spacer(modifier = Modifier.height(24.dp))

        OutlinedTextField(
            value = codeInput,
            onValueChange = { if (it.length <= 6) codeInput = it },
            label = { Text("6-Digit Code") },
            textStyle = MaterialTheme.typography.headlineMedium.copy(
                textAlign = TextAlign.Center,
                letterSpacing = 8.sp
            ),
            singleLine = true,
            shape = RoundedCornerShape(12.dp),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
            modifier = Modifier.fillMaxWidth()
        )

        Spacer(modifier = Modifier.height(24.dp))

        ConnectlyButton(
            text = "Verify Code",
            onClick = { onVerifyClick(codeInput) },
            isLoading = isLoading
        )

        Spacer(modifier = Modifier.height(16.dp))

        TextButton(onClick = onResendClick) {
            Text(
                text = "Resend Code",
                color = ConnectlyBlue,
                fontWeight = FontWeight.SemiBold
            )
        }
    }
}
