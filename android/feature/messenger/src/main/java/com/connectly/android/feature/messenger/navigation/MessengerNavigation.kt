package com.connectly.android.feature.messenger.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.core.model.CallType
import com.connectly.android.feature.messenger.presentation.chat.ChatScreen
import com.connectly.android.feature.messenger.presentation.conversations.ConversationListScreen

const val CONVERSATIONS_ROUTE = "conversations_route"
const val CHAT_ROUTE = "chat_route"

fun NavController.navigateToConversations(navOptions: NavOptions? = null) {
    navigate(CONVERSATIONS_ROUTE, navOptions)
}

fun NavController.navigateToChat(conversationId: Long, navOptions: NavOptions? = null) {
    navigate("$CHAT_ROUTE/$conversationId", navOptions)
}

fun NavGraphBuilder.messengerGraph(
    navController: NavController,
    onStartAudioCall: (Long) -> Unit = {},
    onStartVideoCall: (Long) -> Unit = {},
    onStartCall: ((conversationId: Long?, receiverId: Long?, receiverIds: List<Long>, callType: CallType) -> Unit)? = null
) {
    val unifiedStartCall: (Long?, Long?, List<Long>, CallType) -> Unit = { convId, recId, recIds, callType ->
        if (onStartCall != null) {
            onStartCall(convId, recId, recIds, callType)
        } else {
            val fallbackId = convId ?: recId ?: recIds.firstOrNull() ?: 0L
            if (callType.isVideo) {
                onStartVideoCall(fallbackId)
            } else {
                onStartAudioCall(fallbackId)
            }
        }
    }

    composable(CONVERSATIONS_ROUTE) {
        ConversationListScreen(
            onNavigateToChat = { conversationId ->
                navController.navigateToChat(conversationId)
            },
            onNavigateBack = { navController.popBackStack() },
            onStartCall = unifiedStartCall
        )
    }

    composable(
        route = "$CHAT_ROUTE/{conversationId}",
        arguments = listOf(navArgument("conversationId") { type = NavType.LongType })
    ) { backStackEntry ->
        val conversationId = backStackEntry.arguments?.getLong("conversationId") ?: 0L
        ChatScreen(
            conversationId = conversationId,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToChat = { otherConversationId ->
                navController.navigateToChat(otherConversationId)
            },
            onStartCall = unifiedStartCall,
            onStartAudioCall = onStartAudioCall,
            onStartVideoCall = onStartVideoCall
        )
    }
}
