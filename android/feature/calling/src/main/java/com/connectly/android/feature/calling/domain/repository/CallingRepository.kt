package com.connectly.android.feature.calling.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.IceServer
import com.connectly.android.core.model.WebRtcSignal
import kotlinx.coroutines.flow.Flow

interface CallingRepository {
    fun initiateCall(
        receiverId: Long? = null,
        callType: CallType,
        conversationId: Long? = null,
        receiverIds: List<Long> = emptyList()
    ): Flow<Result<CallSession>>

    fun getCallDetails(callId: Long): Flow<Result<CallSession>>
    fun inviteFriendsToCall(callId: Long, userIds: List<Long>): Flow<Result<CallSession>>
    fun getFriendsToInvite(query: String? = null): Flow<Result<List<ActivePresenceFriend>>>
    fun respondToCall(callId: Long, action: String): Flow<Result<CallSession>>
    fun leaveCall(callId: Long): Flow<Result<CallSession>>
    fun sendSignal(callId: Long, signalType: String, payload: Map<String, String>, targetUserId: Long? = null): Flow<Result<Unit>>
    fun observeSignals(callId: Long): Flow<WebRtcSignal>
    fun getIceServers(): Flow<Result<List<IceServer>>>
    fun getActiveCall(): Flow<CallSession?>
    fun getCallHistory(): Flow<Result<List<CallHistoryItem>>>
    fun updateCallState(callId: Long, isMuted: Boolean?, isCameraOff: Boolean?): Flow<Result<Unit>>
}
