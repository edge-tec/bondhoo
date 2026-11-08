package com.connectly.android.feature.calling.presentation.incoming

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.calling.domain.usecase.GetActiveCallUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import java.util.Collections
import javax.inject.Inject

data class IncomingCallTrigger(
    val callId: Long,
    val callType: CallType,
    val conversationId: Long?
)

@HiltViewModel
class IncomingCallObserverViewModel @Inject constructor(
    private val getActiveCallUseCase: GetActiveCallUseCase,
    private val webSocketManager: ReverbWebSocketManager,
    private val tokenStorageManager: TokenStorageManager
) : ViewModel() {

    private val _incomingTrigger = MutableSharedFlow<IncomingCallTrigger>(extraBufferCapacity = 8)
    val incomingTrigger: SharedFlow<IncomingCallTrigger> = _incomingTrigger.asSharedFlow()

    private val notifiedCallIds: MutableSet<Long> = Collections.synchronizedSet(LinkedHashSet())

    init {
        observeWebSocketIncomingCalls()
    }

    private fun observeWebSocketIncomingCalls() {
        viewModelScope.launch {
            webSocketManager.callIncomingFlow.collect { ev ->
                val myId = tokenStorageManager.getUserId()
                if (ev.callId > 0L && ev.callerId != myId && notifiedCallIds.add(ev.callId)) {
                    _incomingTrigger.emit(
                        IncomingCallTrigger(
                            callId = ev.callId,
                            callType = CallType.from(ev.callType),
                            conversationId = ev.conversationId.takeIf { it > 0L }
                        )
                    )
                }
            }
        }
    }

    suspend fun pollActiveIncomingCall() {
        val token = tokenStorageManager.getAccessToken()
        val myId = tokenStorageManager.getUserId()
        if (token.isNullOrBlank() || myId <= 0L) return

        webSocketManager.connect()
        webSocketManager.subscribeUserChannel(myId)

        getActiveCallUseCase().collect { activeSession ->
            if (activeSession != null &&
                activeSession.id > 0L &&
                activeSession.isIncoming &&
                activeSession.status == CallStatus.RINGING &&
                notifiedCallIds.add(activeSession.id)
            ) {
                _incomingTrigger.emit(
                    IncomingCallTrigger(
                        callId = activeSession.id,
                        callType = activeSession.callType,
                        conversationId = activeSession.conversationId
                    )
                )
            }
        }
    }
}

@Composable
fun IncomingCallObserver(
    onIncomingCall: (callId: Long, callType: CallType, conversationId: Long?) -> Unit,
    viewModel: IncomingCallObserverViewModel = hiltViewModel()
) {
    LaunchedEffect(Unit) {
        viewModel.incomingTrigger.collect { trigger ->
            onIncomingCall(trigger.callId, trigger.callType, trigger.conversationId)
        }
    }

    LaunchedEffect(Unit) {
        while (isActive) {
            delay(4000L)
            viewModel.pollActiveIncomingCall()
        }
    }
}

