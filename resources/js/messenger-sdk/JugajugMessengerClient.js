/**
 * Jugajug Enterprise Messenger Client SDK
 * Production-ready, client-independent communication SDK for Web, Desktop (Electron/Tauri), and Hybrid clients.
 * Serves as the reference client architecture for Android (Kotlin) and iOS (Swift) native implementations.
 */

export class JugajugMessengerClient {
    /**
     * @param {Object} config
     * @param {string} config.baseUrl API base URL (e.g. 'https://jugajug.com/api/v1')
     * @param {string} [config.wsUrl] WebSocket server URL or Pusher/Reverb host
     * @param {string} [config.token] Sanctum API Bearer Token
     * @param {string} [config.platform] 'web' | 'android' | 'ios' | 'macos' | 'windows' | 'linux'
     * @param {string} [config.deviceId] Unique device installation identifier
     */
    constructor(config) {
        this.baseUrl = config.baseUrl.replace(/\/+$/, '');
        this.token = config.token || null;
        this.platform = config.platform || 'web';
        this.deviceId = config.deviceId || this._generateUUID();
        this.lastSyncId = 0;
        this.outboxQueue = [];
        this.isSyncing = false;
        this.eventListeners = new Map();
        this.activeCall = null;
        this.peerConnection = null;
        this.localStream = null;
    }

    /**
     * Set or update authentication token
     * @param {string} token 
     */
    setToken(token) {
        this.token = token;
    }

    /**
     * Register active device session with the backend
     * @param {Object} [details]
     */
    async registerDevice(details = {}) {
        return this._request('POST', '/devices/register', {
            device_id: this.deviceId,
            platform: this.platform,
            device_name: details.deviceName || `${this.platform}-client`,
            app_version: details.appVersion || '1.0.0',
            push_token: details.pushToken || null,
        });
    }

    /**
     * Fetch list of active devices for current account
     */
    async listDevices() {
        return this._request('GET', '/devices');
    }

    /**
     * Revoke a device session
     * @param {number} deviceSessionId
     */
    async revokeDevice(deviceSessionId) {
        return this._request('DELETE', `/devices/${deviceSessionId}`);
    }

    /**
     * Check app version compatibility and mandatory updates
     */
    async checkVersion(currentVersion) {
        return this._request('GET', `/app/version?platform=${encodeURIComponent(this.platform)}&version=${encodeURIComponent(currentVersion)}`);
    }

    /**
     * Send a message with offline outbox queuing and duplicate prevention
     * @param {number} conversationId
     * @param {string} content
     * @param {Object} [options]
     */
    async sendMessage(conversationId, content, options = {}) {
        const clientUuid = options.client_uuid || this._generateUUID();
        const payload = {
            conversation_id: conversationId,
            content,
            type: options.type || 'text',
            client_uuid: clientUuid,
            idempotency_key: clientUuid,
            media_id: options.media_id || null,
            parent_id: options.parent_id || null,
        };

        try {
            const response = await this._request('POST', `/conversations/${conversationId}/messages`, payload);
            return response;
        } catch (error) {
            // Queue into offline outbox if network failure
            this.outboxQueue.push({
                payload,
                conversationId,
                timestamp: Date.now(),
                retries: 0,
            });
            this._emit('message_queued_offline', { clientUuid, conversationId, content });
            throw error;
        }
    }

    /**
     * Edit message with optimistic concurrency control
     * @param {number} messageId
     * @param {string} body
     * @param {number|null} [expectedVersion=null]
     */
    async editMessage(messageId, body, expectedVersion = null) {
        const payload = { body };
        if (expectedVersion !== null && expectedVersion !== undefined) {
            payload.expected_version = expectedVersion;
        }
        return this._request('PATCH', `/messages/${messageId}`, payload);
    }

    /**
     * Delete message (for me or for everyone)
     * @param {number} messageId
     * @param {boolean} [forEveryone=false]
     */
    async deleteMessage(messageId, forEveryone = false) {
        if (forEveryone) {
            return this.deleteMessageForEveryone(messageId);
        }
        return this.deleteMessageForMe(messageId);
    }

    /**
     * Delete message for current user only
     * @param {number} messageId
     */
    async deleteMessageForMe(messageId) {
        return this._request('POST', `/messages/${messageId}/delete-for-me`);
    }

    /**
     * Delete message for everyone (tombstone soft delete)
     * @param {number} messageId
     */
    async deleteMessageForEveryone(messageId) {
        return this._request('POST', `/messages/${messageId}/delete-for-everyone`);
    }

    /**
     * Clear conversation history for current user only
     * @param {number} conversationId
     */
    async clearConversation(conversationId) {
        return this._request('POST', `/conversations/${conversationId}/clear`);
    }

    /**
     * Send presence heartbeat
     * @param {string|null} [sessionId=null]
     */
    async sendHeartbeat(sessionId = null) {
        return this._request('POST', '/presence/heartbeat', {
            session_id: sessionId || this.deviceId,
        });
    }

    /**
     * Send presence offline beacon
     * @param {string|null} [sessionId=null]
     */
    async sendOffline(sessionId = null) {
        return this._request('POST', '/presence/offline', {
            session_id: sessionId || this.deviceId,
        });
    }

    /**
     * Fetch list of online friends
     */
    async getOnlineFriends() {
        return this._request('GET', '/friends/online');
    }

    /**
     * Get presence status of a user
     * @param {number} userId
     */
    async getPresence(userId) {
        return this._request('GET', `/presence/${userId}`);
    }

    /**
     * Replay and flush pending messages from offline outbox
     */
    async flushOutbox() {
        if (this.outboxQueue.length === 0) return;

        const queue = [...this.outboxQueue];
        this.outboxQueue = [];

        for (const item of queue) {
            try {
                await this._request('POST', `/conversations/${item.conversationId}/messages`, item.payload);
                this._emit('message_sent_from_outbox', item.payload);
            } catch (err) {
                if (item.retries < 5) {
                    item.retries++;
                    this.outboxQueue.push(item);
                } else {
                    this._emit('message_outbox_failed', { item, error: err });
                }
            }
        }
    }

    /**
     * Monotonic event replay synchronization (Catch-up / recovery)
     * @param {number} [limit=100]
     */
    async syncEvents(limit = 100) {
        if (this.isSyncing) return;
        this.isSyncing = true;

        try {
            const response = await this._request('GET', `/messenger/sync?since_id=${this.lastSyncId}&limit=${limit}`);
            if (response.success && response.data) {
                const events = response.data.events || [];
                for (const evt of events) {
                    this._emit('sync_event', evt);
                    this._emit(evt.event_type, evt.payload);
                }
                this.lastSyncId = response.data.last_sync_id || this.lastSyncId;
            }
            return response;
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Pin message in a conversation
     */
    async pinMessage(conversationId, messageId) {
        return this._request('POST', `/conversations/${conversationId}/messages/${messageId}/pin`);
    }

    /**
     * Unpin message
     */
    async unpinMessage(conversationId, messageId) {
        return this._request('DELETE', `/conversations/${conversationId}/messages/${messageId}/pin`);
    }

    /**
     * Save/bookmark message for current user
     */
    async saveMessage(messageId) {
        return this._request('POST', `/messages/${messageId}/save`);
    }

    /**
     * Unsave/remove message bookmark
     */
    async unsaveMessage(messageId) {
        return this._request('DELETE', `/messages/${messageId}/save`);
    }

    /**
     * Global and in-chat message search
     */
    async searchMessages(query, conversationId = null) {
        let url = `/messenger/search?query=${encodeURIComponent(query)}`;
        if (conversationId) url += `&conversation_id=${conversationId}`;
        return this._request('GET', url);
    }

    /**
     * Fetch WebRTC STUN/TURN ICE Servers
     */
    async getIceServers() {
        const response = await this._request('GET', '/calls/ice-servers');
        return response.data?.ice_servers || [{ urls: 'stun:stun.l.google.com:19302' }];
    }

    /**
     * Initiate audio or video call
     * @param {number} conversationId
     * @param {'audio'|'video'|'group_audio'|'group_video'} callType
     */
    async initiateCall(conversationId, callType = 'video') {
        const response = await this._request('POST', '/calls', {
            conversation_id: conversationId,
            call_type: callType,
        });
        this.activeCall = response.data;
        return response.data;
    }

    /**
     * Respond to incoming call
     * @param {number} callId
     * @param {'accept'|'reject'|'busy'} action
     */
    async respondToCall(callId, action) {
        const response = await this._request('POST', `/calls/${callId}/respond`, { action });
        if (action === 'accept') {
            this.activeCall = response.data;
        } else {
            this.activeCall = null;
        }
        return response.data;
    }

    /**
     * Transmit WebRTC signaling packet (offer, answer, candidate, screen_share, renegotiate)
     */
    async sendSignal(callId, signalType, payload, targetUserId = null) {
        return this._request('POST', `/calls/${callId}/signal`, {
            signal_type: signalType,
            payload,
            target_user_id: targetUserId,
        });
    }

    /**
     * Leave ongoing call
     */
    async leaveCall(callId) {
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        if (this.localStream) {
            this.localStream.getTracks().forEach(t => t.stop());
            this.localStream = null;
        }
        const response = await this._request('POST', `/calls/${callId}/leave`);
        this.activeCall = null;
        return response.data;
    }

    /**
     * Subscribe to event listener
     */
    on(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    _emit(event, data) {
        if (this.eventListeners.has(event)) {
            for (const cb of this.eventListeners.get(event)) {
                try {
                    cb(data);
                } catch (e) {
                    console.error('Error in event callback:', e);
                }
            }
        }
    }

    _generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async _request(method, endpoint, body = null) {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const options = { method, headers };
        if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(body);
        }

        const res = await fetch(`${this.baseUrl}${endpoint}`, options);
        const data = await res.json();

        if (!res.ok) {
            const error = new Error(data.message || `Request failed with status ${res.status}`);
            error.status = res.status;
            error.response = data;
            throw error;
        }

        return data;
    }
}
