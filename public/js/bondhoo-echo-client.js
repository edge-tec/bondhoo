/**
 * Bondhoo Native Pusher/Reverb v7 WebSocket Client (window.Echo compatible)
 * Connects seamlessly to Laravel Reverb / Pusher servers without external bundle bloat.
 * Supports public, private, and presence channels, /broadcasting/auth CSRF+Sanctum auth,
 * automatic exponential backoff reconnect, and ping/pong keepalive.
 */
(function (global) {
    if (global.Echo) return;

    const getMeta = (name, fallback = '') =>
        document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || fallback;

    const broadcastDriver = getMeta('broadcast-connection', 'log');
    const reverbKey = getMeta('reverb-app-key', 'connectly_reverb_key');
    const reverbHost = getMeta('reverb-host', window.location.hostname);
    const reverbPort = getMeta('reverb-port', '');
    const reverbScheme = getMeta('reverb-scheme', window.location.protocol === 'https:' ? 'https' : 'http');

    class BondhooChannel {
        constructor(client, name) {
            this.client = client;
            this.name = name;
            this.listeners = new Map();
        }

        listen(eventName, callback) {
            const normalized = eventName.startsWith('.') ? eventName.slice(1) : eventName;
            if (!this.listeners.has(normalized)) {
                this.listeners.set(normalized, new Set());
            }
            this.listeners.get(normalized).add(callback);
            return this;
        }

        stopListening(eventName, callback) {
            const normalized = eventName.startsWith('.') ? eventName.slice(1) : eventName;
            if (!callback) {
                this.listeners.delete(normalized);
            } else {
                this.listeners.get(normalized)?.delete(callback);
            }
            return this;
        }

        notification(callback) {
            return this.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', callback);
        }

        here() { return this; }
        joining() { return this; }
        leaving() { return this; }

        emit(eventName, payload) {
            const normalized = eventName.startsWith('.') ? eventName.slice(1) : eventName;
            const cbs = this.listeners.get(normalized);
            if (cbs) {
                cbs.forEach((cb) => {
                    try { cb(payload); } catch (e) { console.error('[BondhooEcho] listener error:', e); }
                });
            }
        }
    }

    class BondhooEchoClient {
        constructor() {
            this.channels = new Map();
            this.ws = null;
            this.socketId = null;
            this.reconnectAttempt = 0;
            this.maxReconnectDelay = 30000;
            this.pingTimer = null;
            this.enabled = broadcastDriver === 'reverb' || broadcastDriver === 'pusher';
            if (this.enabled) {
                this.connect();
            }
        }

        getWebSocketUrl() {
            const proto = reverbScheme === 'https' ? 'wss' : 'ws';
            const portPart = reverbPort && reverbPort !== '80' && reverbPort !== '443' ? `:${reverbPort}` : '';
            return `${proto}://${reverbHost}${portPart}/app/${encodeURIComponent(reverbKey)}?protocol=7&client=web&version=1.0.0`;
        }

        connect() {
            if (!this.enabled || (this.ws && (this.ws.readyState === WebSocket.OPEN || this.ws.readyState === WebSocket.CONNECTING))) {
                return;
            }
            try {
                this.ws = new WebSocket(this.getWebSocketUrl());
                this.ws.onopen = () => {
                    this.reconnectAttempt = 0;
                    this.startPing();
                };
                this.ws.onmessage = (evt) => this.handleMessage(evt.data);
                this.ws.onclose = () => {
                    this.socketId = null;
                    this.stopPing();
                    this.scheduleReconnect();
                };
                this.ws.onerror = () => {
                    // Handled cleanly in onclose
                };
            } catch (_) {
                this.scheduleReconnect();
            }
        }

        startPing() {
            this.stopPing();
            this.pingTimer = setInterval(() => {
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.ws.send(JSON.stringify({ event: 'pusher:ping', data: {} }));
                }
            }, 25000);
        }

        stopPing() {
            if (this.pingTimer) {
                clearInterval(this.pingTimer);
                this.pingTimer = null;
            }
        }

        scheduleReconnect() {
            if (!this.enabled) return;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempt), this.maxReconnectDelay);
            this.reconnectAttempt += 1;
            setTimeout(() => this.connect(), delay);
        }

        handleMessage(raw) {
            let frame;
            try { frame = JSON.parse(raw); } catch (_) { return; }
            const eventName = frame.event || '';
            let data = frame.data;
            if (typeof data === 'string') {
                try { data = JSON.parse(data); } catch (_) {}
            }

            if (eventName === 'pusher:connection_established') {
                this.socketId = data?.socket_id || null;
                this.resubscribeAll();
                return;
            }

            if (eventName === 'pusher:ping') {
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.ws.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
                }
                return;
            }

            const channelName = frame.channel;
            if (channelName && this.channels.has(channelName)) {
                this.channels.get(channelName).emit(eventName, data);
            }
        }

        resubscribeAll() {
            this.channels.forEach((_, channelName) => this.subscribeChannel(channelName));
        }

        async subscribeChannel(channelName) {
            if (!this.ws || this.ws.readyState !== WebSocket.OPEN || !this.socketId) return;

            if (channelName.startsWith('private-') || channelName.startsWith('presence-')) {
                try {
                    const csrf = getMeta('csrf-token', '');
                    const token = localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '';
                    const authUrl = token ? '/api/v1/broadcasting/auth' : '/broadcasting/auth';
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    };
                    if (csrf) headers['X-CSRF-TOKEN'] = csrf;
                    if (token) headers['Authorization'] = `Bearer ${token}`;

                    const res = await fetch(authUrl, {
                        method: 'POST',
                        headers,
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            socket_id: this.socketId,
                            channel_name: channelName,
                        }),
                    });
                    if (!res.ok) return;
                    const authData = await res.json();
                    const payload = {
                        event: 'pusher:subscribe',
                        data: {
                            channel: channelName,
                            auth: authData.auth,
                        },
                    };
                    if (authData.channel_data) {
                        payload.data.channel_data = authData.channel_data;
                    }
                    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                        this.ws.send(JSON.stringify(payload));
                    }
                } catch (_) {}
            } else {
                this.ws.send(JSON.stringify({
                    event: 'pusher:subscribe',
                    data: { channel: channelName },
                }));
            }
        }

        channel(name) {
            if (!this.channels.has(name)) {
                this.channels.set(name, new BondhooChannel(this, name));
                this.subscribeChannel(name);
            }
            return this.channels.get(name);
        }

        private(name) {
            const fullName = name.startsWith('private-') ? name : `private-${name}`;
            return this.channel(fullName);
        }

        join(name) {
            const fullName = name.startsWith('presence-') ? name : `presence-${name}`;
            return this.channel(fullName);
        }

        leave(name) {
            [name, `private-${name}`, `presence-${name}`].forEach((ch) => this.leaveChannel(ch));
        }

        leaveChannel(channelName) {
            if (this.channels.has(channelName)) {
                this.channels.delete(channelName);
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    this.ws.send(JSON.stringify({
                        event: 'pusher:unsubscribe',
                        data: { channel: channelName },
                    }));
                }
            }
        }
    }

    global.Echo = new BondhooEchoClient();
})(window);
