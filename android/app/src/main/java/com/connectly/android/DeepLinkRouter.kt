package com.connectly.android

import android.content.Intent
import android.net.Uri
import com.connectly.android.core.model.CallType

/**
 * Parses incoming HTTPS App Links (https://bondhoo.com/...) and custom deep links
 * (bondhoo://, connectly://, jugajug://) as well as incoming call notification intents.
 */
sealed interface BondhooDeepLink {
    data class IncomingCall(val callId: Long, val callType: CallType = CallType.AUDIO) : BondhooDeepLink
    data class Chat(val conversationId: Long) : BondhooDeepLink
    data class Profile(val identifier: String) : BondhooDeepLink
    data class LiveStream(val streamId: Long) : BondhooDeepLink
    data object Marketplace : BondhooDeepLink
    data object Groups : BondhooDeepLink
    data object Pages : BondhooDeepLink
    data object Events : BondhooDeepLink
    data object Notifications : BondhooDeepLink
    data object Jobs : BondhooDeepLink
    data object Learning : BondhooDeepLink
    data object Search : BondhooDeepLink
    data object Settings : BondhooDeepLink
    data object CreatePost : BondhooDeepLink
}

object DeepLinkRouter {

    private const val EXTRA_CALL_ID = "extra_call_id"

    fun parseIntent(intent: Intent?): BondhooDeepLink? {
        if (intent == null) return null

        val callId = intent.getLongExtra(EXTRA_CALL_ID, 0L)
        if (callId > 0L) {
            return BondhooDeepLink.IncomingCall(callId = callId)
        }

        val uri = intent.data ?: return null
        return parseUri(uri)
    }

    fun parseUri(uri: Uri): BondhooDeepLink? {
        val scheme = uri.scheme?.lowercase().orEmpty()
        val host = uri.host?.lowercase().orEmpty()
        val segments = uri.pathSegments.filter { it.isNotBlank() }

        // Handle custom schemes: bondhoo://messenger/chat/123, connectly://notifications, etc.
        if (scheme in setOf("bondhoo", "connectly", "jugajug")) {
            return when (host) {
                "messenger", "messages", "chat" -> {
                    val id = segments.lastOrNull()?.toLongOrNull()
                    if (id != null && id > 0L) BondhooDeepLink.Chat(id) else null
                }
                "profile", "u", "user" -> {
                    segments.firstOrNull()?.let { BondhooDeepLink.Profile(it) }
                }
                "live" -> {
                    BondhooDeepLink.LiveStream(segments.firstOrNull()?.toLongOrNull() ?: 1L)
                }
                "marketplace" -> BondhooDeepLink.Marketplace
                "groups" -> BondhooDeepLink.Groups
                "pages" -> BondhooDeepLink.Pages
                "events" -> BondhooDeepLink.Events
                "notifications" -> BondhooDeepLink.Notifications
                "jobs" -> BondhooDeepLink.Jobs
                "learning" -> BondhooDeepLink.Learning
                "search" -> BondhooDeepLink.Search
                "settings" -> BondhooDeepLink.Settings
                "create-post", "post" -> BondhooDeepLink.CreatePost
                else -> null
            }
        }

        // Handle verified HTTPS App Links: https://bondhoo.com/...
        if ((scheme == "https" || scheme == "http") && (host == "bondhoo.com" || host == "www.bondhoo.com")) {
            val first = segments.firstOrNull()?.lowercase() ?: return null
            val second = segments.getOrNull(1)
            return when (first) {
                "messages", "messenger", "chat" -> {
                    val id = second?.toLongOrNull()
                    if (id != null && id > 0L) BondhooDeepLink.Chat(id) else null
                }
                "profile", "u", "user" -> {
                    second?.takeIf { it.isNotBlank() }?.let { BondhooDeepLink.Profile(it) }
                }
                "live" -> {
                    BondhooDeepLink.LiveStream(second?.toLongOrNull() ?: 1L)
                }
                "marketplace" -> BondhooDeepLink.Marketplace
                "groups" -> BondhooDeepLink.Groups
                "pages" -> BondhooDeepLink.Pages
                "events" -> BondhooDeepLink.Events
                "notifications" -> BondhooDeepLink.Notifications
                "jobs" -> BondhooDeepLink.Jobs
                "learning" -> BondhooDeepLink.Learning
                "search" -> BondhooDeepLink.Search
                "settings" -> BondhooDeepLink.Settings
                "create-post", "post" -> BondhooDeepLink.CreatePost
                else -> null
            }
        }

        return null
    }
}
