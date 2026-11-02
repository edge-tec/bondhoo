package com.connectly.android.core.webrtc.notification

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.util.Log
import com.connectly.android.core.webrtc.session.CallSessionManager
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

/**
 * BroadcastReceiver for handling notification action intents (Accept, Reject, End Call).
 */
@AndroidEntryPoint
class CallActionBroadcastReceiver : BroadcastReceiver() {

    companion object {
        private const val TAG = "CallActionReceiver"
    }

    @Inject
    lateinit var callSessionManager: CallSessionManager

    @Inject
    lateinit var callNotificationManager: CallNotificationManager

    override fun onReceive(context: Context, intent: Intent) {
        val action = intent.action ?: return
        val callId = intent.getLongExtra(CallNotificationManager.EXTRA_CALL_ID, 0L)

        Log.d(TAG, "onReceive action: $action for callId: $callId")

        when (action) {
            CallNotificationManager.ACTION_ACCEPT_CALL -> {
                callNotificationManager.dismissIncomingCallNotification()
                callSessionManager.acceptCall(callId, null)

                // Launch main activity with call screen intent
                val launchIntent = context.packageManager.getLaunchIntentForPackage(context.packageName)?.apply {
                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_SINGLE_TOP
                    data = Uri.parse("connectly://call/$callId/false/audio")
                }
                if (launchIntent != null) {
                    context.startActivity(launchIntent)
                }
            }

            CallNotificationManager.ACTION_REJECT_CALL -> {
                callNotificationManager.dismissIncomingCallNotification()
                callSessionManager.rejectCall()
            }

            CallNotificationManager.ACTION_END_CALL -> {
                callNotificationManager.dismissOngoingCallNotification()
                callSessionManager.endCall()
            }
        }
    }
}

