package com.connectly.android.core.websocket.unified

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.database.dao.ConversationDao
import com.connectly.android.core.database.dao.MessageDao
import com.connectly.android.core.database.entity.MessageEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.websocket.ReverbWebSocketManager
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class MessageSyncManager @Inject constructor(
    private val api: ConnectlyApi,
    private val messageDao: MessageDao,
    private val conversationDao: ConversationDao,
    private val tokenStorageManager: TokenStorageManager,
    private val webSocketManager: ReverbWebSocketManager,
    private val connectivityMonitor: NetworkConnectivityMonitor,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) {
    private val scope = CoroutineScope(ioDispatcher + SupervisorJob())
    private var lastKnownEventId: Long = 0L

    init {
        observeIncomingMessages()
        observeNetworkReconnect()
    }

    private fun observeIncomingMessages() {
        scope.launch {
            webSocketManager.messageNewFlow.collect { event ->
                val msg = event.message
                val currentUserId = tokenStorageManager.getUserId()

                // Persist to Room
                messageDao.insertMessage(
                    MessageEntity(
                        id = msg.id,
                        conversationId = msg.conversationId,
                        senderId = msg.senderId,
                        senderName = msg.senderName,
                        body = msg.body,
                        type = msg.type,
                        clientMessageId = msg.clientMessageId,
                        deliveryStatus = msg.deliveryStatus.rawValue,
                        isMine = msg.isMine,
                        createdAt = msg.createdAt
                    )
                )

                // If message is from someone else, automatically send delivery receipt to backend
                if (msg.senderId != currentUserId && msg.id > 0) {
                    acknowledgeDelivery(msg.id)
                }
            }
        }

        scope.launch {
            webSocketManager.messageReceiptFlow.collect { receipt ->
                val msgId = receipt.messageId
                if (msgId != null && msgId > 0) {
                    val status = when (receipt.status) {
                        "read" -> DeliveryStatus.READ.rawValue
                        else -> DeliveryStatus.DELIVERED.rawValue
                    }
                    messageDao.updateDeliveryStatus(msgId, status)
                }
                if (receipt.status == "read") {
                    conversationDao.markAsRead(receipt.conversationId)
                }
            }
        }

        scope.launch {
            webSocketManager.messageDeletedFlow.collect { deleted ->
                // Mark deleted or remove from local cache
                messageDao.updateDeliveryStatus(deleted.messageId, "deleted")
            }
        }
    }

    private fun observeNetworkReconnect() {
        scope.launch {
            connectivityMonitor.isConnected.collect { connected ->
                if (connected) {
                    syncMissedEvents()
                }
            }
        }
    }

    suspend fun syncMissedEvents() {
        try {
            val response = api.syncMessengerEvents(sinceId = lastKnownEventId)
            if (response.isSuccessful && response.body() != null) {
                val data = response.body()!!.data
                lastKnownEventId = data.latest_event_id
            }
        } catch (_: Exception) {
            // Handled on subsequent reconnect retry
        }
    }

    private fun acknowledgeDelivery(messageId: Long) {
        scope.launch {
            try {
                api.markDelivered(messageId)
            } catch (_: Exception) {
            }
        }
    }
}

