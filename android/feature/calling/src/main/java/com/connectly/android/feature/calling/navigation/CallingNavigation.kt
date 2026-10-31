package com.connectly.android.feature.calling.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.core.model.CallType
import com.connectly.android.feature.calling.presentation.calling.CallScreen
import com.connectly.android.feature.calling.presentation.history.CallHistoryScreen

const val CALL_ROUTE = "call_route"
const val CALL_HISTORY_ROUTE = "call_history_route"

fun NavController.navigateToCall(
    callId: Long,
    isIncoming: Boolean = false,
    callType: CallType = CallType.AUDIO,
    navOptions: NavOptions? = null
) {
    navigate("$CALL_ROUTE/$callId/$isIncoming/${callType.rawValue}", navOptions)
}

fun NavController.navigateToCallHistory(navOptions: NavOptions? = null) {
    navigate(CALL_HISTORY_ROUTE, navOptions)
}

fun NavGraphBuilder.callingGraph(
    navController: NavController
) {
    composable(
        route = "$CALL_ROUTE/{callId}/{isIncoming}/{callType}",
        arguments = listOf(
            navArgument("callId") { type = NavType.LongType },
            navArgument("isIncoming") { type = NavType.BoolType; defaultValue = false },
            navArgument("callType") { type = NavType.StringType; defaultValue = "audio" }
        )
    ) { backStackEntry ->
        val callId = backStackEntry.arguments?.getLong("callId") ?: 1L
        val isIncoming = backStackEntry.arguments?.getBoolean("isIncoming") ?: false
        val callTypeStr = backStackEntry.arguments?.getString("callType") ?: "audio"
        val callType = CallType.from(callTypeStr)

        CallScreen(
            callId = callId,
            isIncoming = isIncoming,
            callType = callType,
            onCallEnded = { navController.popBackStack() }
        )
    }

    composable(CALL_HISTORY_ROUTE) {
        CallHistoryScreen(
            onNavigateBack = { navController.popBackStack() },
            onRedialCall = { receiverId, callType ->
                navController.navigateToCall(receiverId, isIncoming = false, callType = callType)
            }
        )
    }
}
