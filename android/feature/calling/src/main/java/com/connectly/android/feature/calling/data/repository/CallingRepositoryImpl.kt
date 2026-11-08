package com.connectly.android.feature.calling.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallParticipant
import com.connectly.android.core.model.CallSession
import com.connectly.android.core.model.CallStatus
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.IceServer
import com.connectly.android.core.model.User
import com.connectly.android.core.model.WebRtcSignal
import com.connectly.android.core.network.api.CallParticipantDto
import com.connectly.android.core.network.api.CallSessionDto
import com.connectly.android.core.network.api.CallSignalRequest
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.InitiateCallRequest
import com.connectly.android.core.network.api.InviteToCallRequest
import com.connectly.android.core.network.api.RespondCallRequest
import com.connectly.android.core.network.api.UpdateCallStateRequest
import com.connectly.android.core.network.api.UserDto
import com.connectly.android.core.network.di.NetworkModule
import com.connectly.android.feature.calling.data.webrtc.WebRtcCallManager
import com.connectly.android.feature.calling.domain.repository.CallingRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.flow
import org.json.JSONObject
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class CallingRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val webRtcManager: WebRtcCallManager,
    private val tokenStorageManager: TokenStorageManager
) : CallingRepository {

    private val signalFlow = MutableSharedFlow<WebRtcSignal>(replay = 1)

    override fun initiateCall(
        receiverId: Long?,
        callType: CallType,
        conversationId: Long?,
        receiverIds: List<Long>
    ): Flow<Result<CallSession>> = flow {
        emit(Result.Loading)
        try {
            val cleanConvId = conversationId?.takeIf { it > 0L }
            val cleanRecId = receiverId?.takeIf { it > 0L }
            val cleanRecIds = receiverIds.filter { it > 0L }.distinct()

            val response = api.initiateCall(
                InitiateCallRequest(
                    conversation_id = cleanConvId,
                    receiver_id = if (cleanRecIds.isEmpty()) cleanRecId else null,
                    receiver_ids = cleanRecIds.takeIf { it.isNotEmpty() },
                    call_type = callType.rawValue
                )
            )

            val dto = response.body()?.data
            if (response.isSuccessful && dto != null) {
                val session = mapDtoToCallSession(dto, isIncomingOverride = false, fallbackCallType = callType)
                webRtcManager.startAudioSession(enableSpeaker = session.callType.isVideo)
                emit(Result.Success(session))
            } else {
                val errMsg = extractApiErrorMessage(response.errorBody()?.string(), "Unable to start call")
                emit(Result.Error(IllegalStateException(errMsg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getCallDetails(callId: Long): Flow<Result<CallSession>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getCallDetails(callId)
            val dto = response.body()?.data
            if (response.isSuccessful && dto != null) {
                emit(Result.Success(mapDtoToCallSession(dto)))
            } else {
                val errMsg = extractApiErrorMessage(response.errorBody()?.string(), "Call not found")
                emit(Result.Error(IllegalStateException(errMsg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun inviteFriendsToCall(callId: Long, userIds: List<Long>): Flow<Result<CallSession>> = flow {
        emit(Result.Loading)
        try {
            val cleanIds = userIds.filter { it > 0L }.distinct()
            val response = api.inviteToCall(
                callId,
                InviteToCallRequest(
                    friend_id = cleanIds.singleOrNull(),
                    friend_ids = cleanIds
                )
            )
            if (response.isSuccessful) {
                val detailRes = api.getCallDetails(callId)
                val updatedDto = detailRes.body()?.data
                if (detailRes.isSuccessful && updatedDto != null) {
                    emit(Result.Success(mapDtoToCallSession(updatedDto)))
                } else {
                    emit(Result.Error(IllegalStateException("Invited friends, refreshing call...")))
                }
            } else {
                val errMsg = extractApiErrorMessage(response.errorBody()?.string(), "Could not invite friends to call")
                emit(Result.Error(IllegalStateException(errMsg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getFriendsToInvite(query: String?): Flow<Result<List<ActivePresenceFriend>>> = flow {
        emit(Result.Loading)
        try {
            val combined = LinkedHashMap<Long, ActivePresenceFriend>()
            val currentUserId = tokenStorageManager.getUserId()

            runCatching {
                val activeRes = api.getActiveFriends(query?.takeIf { it.isNotBlank() })
                if (activeRes.isSuccessful) {
                    activeRes.body()?.data?.forEach { dto ->
                        if (dto.id != currentUserId) {
                            combined[dto.id] = ActivePresenceFriend(
                                id = dto.id,
                                name = dto.name,
                                username = dto.username,
                                avatarUrl = normalizeUrl(dto.avatar_url),
                                online = dto.online,
                                lastSeenDisplay = dto.last_seen_display,
                                conversationId = dto.conversation_id,
                                canCall = dto.can_call
                            )
                        }
                    }
                }
            }

            runCatching {
                val friendsRes = api.getFriends(search = query?.takeIf { it.isNotBlank() })
                if (friendsRes.isSuccessful) {
                    friendsRes.body()?.data?.forEach { userDto ->
                        if (userDto.id != currentUserId && !combined.containsKey(userDto.id)) {
                            combined[userDto.id] = ActivePresenceFriend(
                                id = userDto.id,
                                name = userDto.name,
                                username = userDto.username.orEmpty(),
                                avatarUrl = normalizeUrl(userDto.avatar ?: userDto.profile?.avatar_url),
                                online = userDto.is_online ?: false,
                                lastSeenDisplay = if (userDto.is_online == true) "Active now" else null,
                                conversationId = null,
                                canCall = true
                            )
                        }
                    }
                }
            }

            emit(
                Result.Success(
                    combined.values.sortedWith(
                        compareByDescending<ActivePresenceFriend> { it.online }
                            .thenBy { it.name.lowercase(Locale.getDefault()) }
                    )
                )
            )
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun respondToCall(callId: Long, action: String): Flow<Result<CallSession>> = flow {
        emit(Result.Loading)
        try {
            val response = api.respondToCall(callId, RespondCallRequest(action))
            val dto = response.body()?.data
            if (response.isSuccessful && dto != null) {
                val session = mapDtoToCallSession(dto, isIncomingOverride = true)
                if (action == "accept") {
                    webRtcManager.startAudioSession(enableSpeaker = session.callType.isVideo)
                } else {
                    webRtcManager.endAudioSession()
                }
                emit(Result.Success(session))
            } else {
                if (action != "accept") {
                    webRtcManager.endAudioSession()
                }
                val errMsg = extractApiErrorMessage(response.errorBody()?.string(), "Failed to respond to call")
                emit(Result.Error(IllegalStateException(errMsg)))
            }
        } catch (e: Exception) {
            if (action != "accept") {
                webRtcManager.endAudioSession()
            }
            emit(Result.Error(e))
        }
    }

    override fun leaveCall(callId: Long): Flow<Result<CallSession>> = flow {
        webRtcManager.endAudioSession()
        emit(Result.Loading)
        try {
            val response = api.leaveCall(callId)
            val dto = response.body()?.data
            if (response.isSuccessful && dto != null) {
                emit(Result.Success(mapDtoToCallSession(dto)))
            } else {
                emit(Result.Success(createEndedSession(callId)))
            }
        } catch (_: Exception) {
            emit(Result.Success(createEndedSession(callId)))
        }
    }

    override fun sendSignal(
        callId: Long,
        signalType: String,
        payload: Map<String, String>,
        targetUserId: Long?
    ): Flow<Result<Unit>> = flow {
        try {
            api.sendSignal(
                callId,
                CallSignalRequest(
                    signal_type = signalType,
                    payload = payload,
                    target_user_id = targetUserId
                )
            )
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun observeSignals(callId: Long): Flow<WebRtcSignal> = signalFlow.asSharedFlow()

    override fun getIceServers(): Flow<Result<List<IceServer>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getIceServers()
            val bodyData = res.body()?.data
            if (res.isSuccessful && bodyData != null) {
                val servers = bodyData.ice_servers.map {
                    IceServer(urls = it.urls, username = it.username, credential = it.credential)
                }
                emit(Result.Success(servers))
            } else {
                emit(Result.Success(listOf(IceServer(urls = listOf("stun:stun.l.google.com:19302")))))
            }
        } catch (_: Exception) {
            emit(Result.Success(listOf(IceServer(urls = listOf("stun:stun.l.google.com:19302")))))
        }
    }

    override fun getActiveCall(): Flow<CallSession?> = flow {
        try {
            val res = api.getActiveCall()
            val dto = res.body()?.data
            if (res.isSuccessful && dto != null) {
                emit(mapDtoToCallSession(dto))
            } else {
                emit(null)
            }
        } catch (_: Exception) {
            emit(null)
        }
    }

    override fun getCallHistory(): Flow<Result<List<CallHistoryItem>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getCallHistory()
            val dtoList = res.body()?.data
            if (res.isSuccessful && dtoList != null) {
                val currentUserId = tokenStorageManager.getUserId() ?: 0L
                val items = dtoList.map { dto ->
                    val session = mapDtoToCallSession(dto)
                    val callerObj = dto.caller
                    val isIncoming = if (currentUserId > 0L && callerObj != null && callerObj.id > 0L) {
                        callerObj.id != currentUserId
                    } else {
                        !dto.is_mine
                    }
                    val peer = if (isIncoming) session.caller else session.receiver
                    val outcome = dto.outcome ?: dto.status
                    val isMissed = outcome.equals("missed", ignoreCase = true) ||
                        outcome.equals("no_answer", ignoreCase = true) ||
                        (isIncoming && dto.status.equals("rejected", ignoreCase = true))

                    CallHistoryItem(
                        id = dto.id,
                        conversationId = dto.conversation_id,
                        title = session.title,
                        otherUser = peer,
                        participantCount = dto.participants.size.coerceAtLeast(2),
                        callType = session.callType,
                        isIncoming = isIncoming,
                        isMissed = isMissed,
                        outcome = outcome,
                        durationSeconds = dto.duration_seconds,
                        formattedTime = formatCallTimestamp(dto.started_at ?: dto.created_at)
                    )
                }
                emit(Result.Success(items))
            } else {
                val errMsg = extractApiErrorMessage(res.errorBody()?.string(), "Failed to load call history")
                emit(Result.Error(IllegalStateException(errMsg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun updateCallState(
        callId: Long,
        isMuted: Boolean?,
        isCameraOff: Boolean?
    ): Flow<Result<Unit>> = flow {
        try {
            api.updateCallState(
                callId,
                UpdateCallStateRequest(
                    is_muted = isMuted,
                    is_camera_off = isCameraOff
                )
            )
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    private fun mapDtoToCallSession(
        dto: CallSessionDto,
        isIncomingOverride: Boolean? = null,
        fallbackCallType: CallType = CallType.AUDIO
    ): CallSession {
        val currentUserId = tokenStorageManager.getUserId() ?: 0L
        val mappedParticipants = dto.participants.map { pDto -> mapParticipant(pDto) }
        val resolvedCallType = if (dto.call_type.isNotBlank()) CallType.from(dto.call_type) else fallbackCallType

        val callerDto = dto.caller
        val receiverDto = dto.receiver

        val callerUser = callerDto?.let { mapUser(it) }
            ?: mappedParticipants.firstOrNull { it.role == "caller" }?.user
            ?: User(id = 0L, name = "Bondoo Member", username = "")

        val otherParticipantUser = receiverDto?.let { mapUser(it) }
            ?: mappedParticipants.firstOrNull { it.user.id != currentUserId && it.user.id != callerUser.id }?.user
            ?: mappedParticipants.firstOrNull { it.user.id != currentUserId }?.user
            ?: User(id = currentUserId, name = "Bondoo Member", username = "")

        val isIncoming = isIncomingOverride
            ?: if (currentUserId > 0L && callerUser.id > 0L) {
                callerUser.id != currentUserId
            } else {
                !dto.is_mine
            }

        val isGroup = resolvedCallType.isGroup || mappedParticipants.size > 2
        val groupTitle = if (isGroup) {
            val otherNames = mappedParticipants
                .map { it.user }
                .filter { it.id != currentUserId && it.name.isNotBlank() }
                .map { it.name }
            if (otherNames.isNotEmpty()) otherNames.take(3).joinToString(", ") else "Group Call"
        } else {
            null
        }

        return CallSession(
            id = dto.id,
            channelName = dto.room_id ?: dto.channel_name ?: "call_${dto.id}",
            conversationId = dto.conversation_id,
            title = groupTitle,
            callType = resolvedCallType,
            status = CallStatus.from(dto.status),
            caller = callerUser,
            receiver = if (isIncoming) {
                mappedParticipants.firstOrNull { it.user.id == currentUserId }?.user
                    ?: User(id = currentUserId, name = "You", username = "you")
            } else {
                otherParticipantUser
            },
            participants = mappedParticipants,
            isIncoming = isIncoming,
            durationSeconds = dto.duration_seconds
        )
    }

    private fun mapParticipant(dto: CallParticipantDto): CallParticipant {
        return CallParticipant(
            id = dto.id,
            user = mapUser(dto.user),
            role = dto.role,
            status = dto.status,
            isMuted = dto.is_muted,
            isCameraOff = dto.is_camera_off,
            isScreenSharing = dto.is_screen_sharing
        )
    }

    private fun mapUser(dto: UserDto): User {
        return User(
            id = dto.id,
            name = dto.name,
            username = dto.username.orEmpty(),
            avatar = normalizeUrl(dto.avatar ?: dto.profile?.avatar_url)
        )
    }

    private fun createEndedSession(callId: Long): CallSession {
        val currentUserId = tokenStorageManager.getUserId() ?: 0L
        return CallSession(
            id = callId,
            channelName = "call_$callId",
            callType = CallType.AUDIO,
            status = CallStatus.ENDED,
            caller = User(id = currentUserId, name = "You", username = ""),
            receiver = User(id = 0L, name = "Call Ended", username = ""),
            isIncoming = false
        )
    }

    private fun normalizeUrl(rawUrl: String?): String? {
        return NetworkModule.resolveMediaUrl(rawUrl)
    }

    private fun formatCallTimestamp(isoString: String?): String {
        if (isoString.isNullOrBlank()) return "Recent"
        return runCatching {
            val instant = Instant.parse(isoString)
            val zoned = instant.atZone(ZoneId.systemDefault())
            DateTimeFormatter.ofPattern("MMM d, h:mm a", Locale.getDefault()).format(zoned)
        }.getOrDefault(isoString.take(16))
    }

    private fun extractApiErrorMessage(rawErrorBody: String?, fallback: String): String {
        if (rawErrorBody.isNullOrBlank()) return fallback
        return runCatching {
            JSONObject(rawErrorBody).optString("message").takeIf { it.isNotBlank() } ?: fallback
        }.getOrDefault(fallback)
    }
}
