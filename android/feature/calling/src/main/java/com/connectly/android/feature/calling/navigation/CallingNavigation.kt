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
    callId: Long = 0L,
    isIncoming: Boolean = false,
    callType: CallType = CallType.AUDIO,
    conversationId: Long? = null,
    receiverId: Long? = null,
    receiverIds: List<Long> = emptyList(),
    navOptions: NavOptions? = null
) {
    val convArg = conversationId ?: -1L
    val recArg = receiverId ?: (if (!isIncoming && callId > 0L && conversationId == null) callId else -1L)
    val recIdsCsv = receiverIds.filter { it > 0L }.joinToString(",")
    navigate(
        "$CALL_ROUTE/$callId/$isIncoming/${callType.rawValue}?conversationId=$convArg&receiverId=$recArg&receiverIds=$recIdsCsv",
        navOptions
    )
}

fun NavController.navigateToCallHistory(navOptions: NavOptions? = null) {
    navigate(CALL_HISTORY_ROUTE, navOptions)
}

fun NavGraphBuilder.callingGraph(
    navController: NavController
) {
    composable(
        route = "$CALL_ROUTE/{callId}/{isIncoming}/{callType}?conversationId={conversationId}&receiverId={receiverId}&receiverIds={receiverIds}",
        arguments = listOf(
            navArgument("callId") { type = NavType.LongType },
            navArgument("isIncoming") { type = NavType.BoolType; defaultValue = false },
            navArgument("callType") { type = NavType.StringType; defaultValue = "audio" },
            navArgument("conversationId") { type = NavType.LongType; defaultValue = -1L },
            navArgument("receiverId") { type = NavType.LongType; defaultValue = -1L },
            navArgument("receiverIds") { type = NavType.StringType; defaultValue = "" }
        )
    ) { backStackEntry ->
        val callId = backStackEntry.arguments?.getLong("callId") ?: 0L
        val isIncoming = backStackEntry.arguments?.getBoolean("isIncoming") ?: false
        val callTypeStr = backStackEntry.arguments?.getString("callType") ?: "audio"
        val callType = CallType.from(callTypeStr)
        val conversationId = backStackEntry.arguments?.getLong("conversationId")?.takeIf { it > 0L }
        val receiverId = backStackEntry.arguments?.getLong("receiverId")?.takeIf { it > 0L }
        val receiverIds = backStackEntry.arguments?.getString("receiverIds")
            .orEmpty()
            .split(",")
            .mapNotNull { it.trim().toLongOrNull()?.takeIf { id -> id > 0L } }

        CallScreen(
            callId = callId,
            isIncoming = isIncoming,
            callType = callType,
            conversationId = conversationId,
            receiverId = receiverId,
            receiverIds = receiverIds,
            onCallEnded = { navController.popBackStack() }
        )
    }

    composable(CALL_HISTORY_ROUTE) {
        CallHistoryScreen(
            onNavigateBack = { navController.popBackStack() },
            onRedialCall = { receiverId, conversationId, callType ->
                navController.navigateToCall(
                    callId = receiverId ?: conversationId ?: 0L,
                    isIncoming = false,
                    callType = callType,
                    conversationId = conversationId,
                    receiverId = receiverId
                )
            }
        )
    }
}
