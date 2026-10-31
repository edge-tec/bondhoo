package com.connectly.android.feature.auth.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.auth.presentation.forgot.ForgotPasswordRoute
import com.connectly.android.feature.auth.presentation.login.LoginRoute
import com.connectly.android.feature.auth.presentation.otp.PhoneOtpRoute
import com.connectly.android.feature.auth.presentation.register.RegisterRoute
import com.connectly.android.feature.auth.presentation.twofactor.TwoFactorRoute

const val LOGIN_ROUTE = "auth_login"
const val REGISTER_ROUTE = "auth_register"
const val FORGOT_PASSWORD_ROUTE = "auth_forgot_password"
const val TWO_FACTOR_ROUTE = "auth_two_factor/{sessionToken}"
const val PHONE_OTP_ROUTE = "auth_phone_otp"

fun NavController.navigateToLogin(navOptions: NavOptions? = null) {
    navigate(LOGIN_ROUTE, navOptions)
}

fun NavController.navigateToRegister(navOptions: NavOptions? = null) {
    navigate(REGISTER_ROUTE, navOptions)
}

fun NavController.navigateToPhoneOtp(navOptions: NavOptions? = null) {
    navigate(PHONE_OTP_ROUTE, navOptions)
}

fun NavController.navigateToForgotPassword(navOptions: NavOptions? = null) {
    navigate(FORGOT_PASSWORD_ROUTE, navOptions)
}

fun NavGraphBuilder.authGraph(
    onNavigateToHome: () -> Unit,
    navController: NavController
) {
    composable(LOGIN_ROUTE) {
        LoginRoute(
            onNavigateToHome = onNavigateToHome,
            onNavigateToRegister = { navController.navigateToRegister() },
            onNavigateToPhoneOtp = { navController.navigateToPhoneOtp() },
            onNavigateToForgotPassword = { navController.navigateToForgotPassword() }
        )
    }

    composable(FORGOT_PASSWORD_ROUTE) {
        ForgotPasswordRoute(
            onNavigateToLogin = { navController.popBackStack() }
        )
    }

    composable(REGISTER_ROUTE) {
        RegisterRoute(
            onNavigateToHome = onNavigateToHome,
            onNavigateToLogin = { navController.popBackStack() }
        )
    }

    composable(PHONE_OTP_ROUTE) {
        PhoneOtpRoute(
            onNavigateToHome = onNavigateToHome,
            onBack = { navController.popBackStack() }
        )
    }

    composable(TWO_FACTOR_ROUTE) { backStackEntry ->
        val sessionToken = backStackEntry.arguments?.getString("sessionToken") ?: ""
        TwoFactorRoute(
            sessionToken = sessionToken,
            onNavigateToHome = onNavigateToHome,
            onBack = { navController.popBackStack() }
        )
    }
}

