package com.connectly.android.feature.auth.presentation.otp

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.PhoneAndroid
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
fun PhoneOtpRoute(
    onNavigateToHome: () -> Unit,
    onBack: () -> Unit,
    viewModel: PhoneOtpViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) {
        viewModel.effect.collect { effect ->
            when (effect) {
                is PhoneOtpUiEffect.NavigateToHome -> onNavigateToHome()
                is PhoneOtpUiEffect.ShowToast -> { /* Show Toast */ }
            }
        }
    }

    PhoneOtpScreen(
        uiState = uiState,
        onSendOtpClick = { phone -> viewModel.handleIntent(PhoneOtpUiIntent.SendOtp(phone)) },
        onVerifyOtpClick = { phone, otp -> viewModel.handleIntent(PhoneOtpUiIntent.VerifyOtp(phone, otp)) },
        onBack = onBack
    )
}

@Composable
fun PhoneOtpScreen(
    uiState: PhoneOtpUiState,
    onSendOtpClick: (String) -> Unit,
    onVerifyOtpClick: (String, String) -> Unit,
    onBack: () -> Unit
) {
    var phoneInput by remember { mutableStateOf("") }
    var otpInput by remember { mutableStateOf("") }
    val isOtpSent = uiState is PhoneOtpUiState.OtpSent
    val isLoading = uiState is PhoneOtpUiState.Loading

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
            imageVector = Icons.Default.PhoneAndroid,
            contentDescription = null,
            tint = ConnectlyBlue,
            modifier = Modifier.size(56.dp)
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = if (!isOtpSent) "Mobile Login" else "Verify Mobile OTP",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold
        )

        Text(
            text = if (!isOtpSent)
                "Enter your phone number to receive a verification code."
            else
                "Enter the 4-digit code sent to $phoneInput",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
            textAlign = TextAlign.Center,
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
        )

        Spacer(modifier = Modifier.height(24.dp))

        if (!isOtpSent) {
            OutlinedTextField(
                value = phoneInput,
                onValueChange = { phoneInput = it },
                label = { Text("Phone Number") },
                placeholder = { Text("+880 1700-000000") },
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            ConnectlyButton(
                text = "Send OTP",
                onClick = { onSendOtpClick(phoneInput) },
                isLoading = isLoading
            )
        } else {
            OutlinedTextField(
                value = otpInput,
                onValueChange = { if (it.length <= 4) otpInput = it },
                label = { Text("4-Digit OTP") },
                textStyle = MaterialTheme.typography.headlineMedium.copy(
                    textAlign = TextAlign.Center,
                    letterSpacing = 12.sp
                ),
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            ConnectlyButton(
                text = "Verify & Log In",
                onClick = { onVerifyOtpClick(phoneInput, otpInput) },
                isLoading = isLoading
            )
        }
    }
}
