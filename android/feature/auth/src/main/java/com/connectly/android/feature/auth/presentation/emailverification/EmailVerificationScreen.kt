package com.connectly.android.feature.auth.presentation.emailverification

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.MarkEmailRead
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.connectly.android.core.designsystem.component.ConnectlyButton
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import kotlinx.coroutines.delay

@Composable
fun EmailVerificationRoute(
    email: String,
    onNavigateToHome: () -> Unit,
    onBackToLogin: () -> Unit,
    viewModel: EmailVerificationViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) {
        viewModel.effect.collect { effect ->
            when (effect) {
                is EmailVerificationUiEffect.NavigateToHome -> onNavigateToHome()
                is EmailVerificationUiEffect.ShowToast -> {
                    Toast.makeText(context, effect.message, Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    EmailVerificationScreen(
        email = email,
        uiState = uiState,
        onVerifyOtp = { otp ->
            viewModel.handleIntent(EmailVerificationUiIntent.VerifyOtp(email, otp))
        },
        onResendOtp = {
            viewModel.handleIntent(EmailVerificationUiIntent.ResendOtp(email))
        },
        onBackToLogin = onBackToLogin,
        onClearError = {
            viewModel.handleIntent(EmailVerificationUiIntent.ClearError)
        }
    )
}

@Composable
fun EmailVerificationScreen(
    email: String,
    uiState: EmailVerificationUiState,
    onVerifyOtp: (String) -> Unit,
    onResendOtp: () -> Unit,
    onBackToLogin: () -> Unit,
    onClearError: () -> Unit
) {
    var otpInput by remember { mutableStateOf("") }
    var resendCooldown by remember { mutableIntStateOf(60) }
    var canResend by remember { mutableStateOf(false) }
    val keyboardController = LocalSoftwareKeyboardController.current

    val isLoading = uiState is EmailVerificationUiState.Loading

    // Cooldown timer for resending OTP
    LaunchedEffect(resendCooldown, canResend) {
        if (!canResend && resendCooldown > 0) {
            delay(1000L)
            resendCooldown -= 1
            if (resendCooldown == 0) {
                canResend = true
            }
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .statusBarsPadding()
            .navigationBarsPadding()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        IconButton(
            onClick = onBackToLogin,
            modifier = Modifier.align(Alignment.Start)
        ) {
            Icon(Icons.Default.ArrowBack, contentDescription = "Back")
        }

        Spacer(modifier = Modifier.height(24.dp))

        Surface(
            shape = RoundedCornerShape(24.dp),
            color = ConnectlyBlue.copy(alpha = 0.12f),
            modifier = Modifier.size(96.dp)
        ) {
            Box(contentAlignment = Alignment.Center) {
                Icon(
                    imageVector = Icons.Default.MarkEmailRead,
                    contentDescription = null,
                    tint = ConnectlyBlue,
                    modifier = Modifier.size(52.dp)
                )
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            text = "Verify Your Email",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onBackground
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            text = "We have sent a 6-digit verification code to your email account:",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
            textAlign = TextAlign.Center
        )

        Spacer(modifier = Modifier.height(4.dp))

        Text(
            text = email,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Bold,
            color = ConnectlyBlue,
            textAlign = TextAlign.Center
        )

        Spacer(modifier = Modifier.height(24.dp))

        if (uiState is EmailVerificationUiState.Error) {
            Card(
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.errorContainer
                ),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp)
            ) {
                Text(
                    text = uiState.message,
                    color = MaterialTheme.colorScheme.onErrorContainer,
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(12.dp)
                )
            }
        }

        if (uiState is EmailVerificationUiState.CodeResent) {
            Card(
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer
                ),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp)
            ) {
                Text(
                    text = uiState.message,
                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(12.dp)
                )
            }
        }

        OutlinedTextField(
            value = otpInput,
            onValueChange = {
                if (it.length <= 6 && it.all { char -> char.isDigit() }) {
                    otpInput = it
                    onClearError()
                }
            },
            label = { Text("6-Digit Verification Code") },
            placeholder = { Text("123456") },
            singleLine = true,
            textStyle = LocalTextStyle.current.copy(
                fontSize = 24.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                letterSpacing = 6.sp
            ),
            shape = RoundedCornerShape(14.dp),
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.Number,
                imeAction = ImeAction.Done
            ),
            keyboardActions = KeyboardActions(
                onDone = {
                    keyboardController?.hide()
                    if (otpInput.length == 6) {
                        onVerifyOtp(otpInput)
                    }
                }
            ),
            modifier = Modifier.fillMaxWidth()
        )

        Spacer(modifier = Modifier.height(24.dp))

        ConnectlyButton(
            text = "Verify Email",
            onClick = {
                keyboardController?.hide()
                onVerifyOtp(otpInput)
            },
            isLoading = isLoading
        )

        Spacer(modifier = Modifier.height(20.dp))

        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.Center,
            modifier = Modifier.fillMaxWidth()
        ) {
            if (canResend) {
                TextButton(
                    onClick = {
                        canResend = false
                        resendCooldown = 60
                        onResendOtp()
                    }
                ) {
                    Icon(
                        imageVector = Icons.Default.Refresh,
                        contentDescription = null,
                        modifier = Modifier.size(16.dp),
                        tint = ConnectlyBlue
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "Resend Code",
                        fontWeight = FontWeight.Bold,
                        color = ConnectlyBlue
                    )
                }
            } else {
                Text(
                    text = "Resend code in ${resendCooldown}s",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.5f)
                )
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "Already verified? Log In",
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
            color = ConnectlyBlue,
            modifier = Modifier.clickable { onBackToLogin() }
        )
    }
}

