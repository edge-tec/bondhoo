package com.connectly.android.feature.calling.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.IceServer
import com.connectly.android.core.model.WebRtcSignal
import com.connectly.android.feature.calling.domain.repository.CallingRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class InitiateCallUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(
        receiverId: Long? = null,
        callType: CallType,
        conversationId: Long? = null,
        receiverIds: List<Long> = emptyList()
    ): Flow<Result<CallSession>> {
        return repository.initiateCall(receiverId, callType, conversationId, receiverIds)
    }
}

class GetCallDetailsUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long): Flow<Result<CallSession>> {
        return repository.getCallDetails(callId)
    }
}

class InviteFriendsToCallUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long, userIds: List<Long>): Flow<Result<CallSession>> {
        return repository.inviteFriendsToCall(callId, userIds)
    }
}

class GetFriendsToInviteUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(query: String? = null): Flow<Result<List<ActivePresenceFriend>>> {
        return repository.getFriendsToInvite(query)
    }
}

class RespondCallUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long, action: String): Flow<Result<CallSession>> {
        return repository.respondToCall(callId, action)
    }
}

class LeaveCallUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long): Flow<Result<CallSession>> {
        return repository.leaveCall(callId)
    }
}

class SendCallSignalUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long, signalType: String, payload: Map<String, String>, targetUserId: Long? = null): Flow<Result<Unit>> {
        return repository.sendSignal(callId, signalType, payload, targetUserId)
    }
}

class ObserveCallSignalsUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long): Flow<WebRtcSignal> {
        return repository.observeSignals(callId)
    }
}

class GetIceServersUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(): Flow<Result<List<IceServer>>> {
        return repository.getIceServers()
    }
}

class GetActiveCallUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(): Flow<CallSession?> {
        return repository.getActiveCall()
    }
}

class GetCallHistoryUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(): Flow<Result<List<CallHistoryItem>>> {
        return repository.getCallHistory()
    }
}

class UpdateCallStateUseCase @Inject constructor(
    private val repository: CallingRepository
) {
    operator fun invoke(callId: Long, isMuted: Boolean?, isCameraOff: Boolean?): Flow<Result<Unit>> {
        return repository.updateCallState(callId, isMuted, isCameraOff)
    }
}
