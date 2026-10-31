package com.connectly.android.feature.calling.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.IceServer
import com.connectly.android.core.model.WebRtcSignal
import kotlinx.coroutines.flow.Flow

interface CallingRepository {
    fun initiateCall(receiverId: Long, callType: CallType, conversationId: Long? = null): Flow<Result<CallSession>>
    fun respondToCall(callId: Long, action: String): Flow<Result<CallSession>>
    fun leaveCall(callId: Long): Flow<Result<CallSession>>
    fun sendSignal(callId: Long, signalType: String, payload: Map<String, String>, targetUserId: Long? = null): Flow<Result<Unit>>
    fun observeSignals(callId: Long): Flow<WebRtcSignal>
    fun getIceServers(): Flow<Result<List<IceServer>>>
    fun getActiveCall(): Flow<CallSession?>
    fun getCallHistory(): Flow<Result<List<CallHistoryItem>>>
    fun updateCallState(callId: Long, isMuted: Boolean?, isCameraOff: Boolean?): Flow<Result<Unit>>
}

