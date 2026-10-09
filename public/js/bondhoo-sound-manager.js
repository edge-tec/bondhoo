/**
 * Bondhoo Messenger — Professional Three Independent Notification Sounds & Ringtone System
 * Manages:
 * 1. Incoming Call Ringtone (looping, interruptible, call lifecycle bound)
 * 2. Incoming Message Tone (one-shot, deduplicated by message ID, mute-aware)
 * 3. Outgoing Message Sent Tone (one-shot confirmation after backend acceptance)
 */

(function (window) {
    'use strict';

    const SOUND_CATALOGUE = {
        incoming_call: {
            'bondhoo_ring': { name: 'Bondhoo Ring (ডিফল্ট)', url: '/sounds/messenger/bondhoo_ring.wav', isDefault: true },
            'digital_bell': { name: 'Digital Bell (ডিজিটাল বেল)', url: '/sounds/messenger/digital_bell.wav', isDefault: false },
            'celesta_chime': { name: 'Celesta Chime (সেলেনিয়াম মেলোডি)', url: '/sounds/messenger/celesta_chime.wav', isDefault: false }
        },
        incoming_message: {
            'bubble_pop': { name: 'Bubble Pop (ডিফল্ট বাবল)', url: '/sounds/messenger/bubble_pop.wav', isDefault: true },
            'clear_ding': { name: 'Clear Ding (ক্রিস্টাল ডিং)', url: '/sounds/messenger/clear_ding.wav', isDefault: false },
            'soft_chime': { name: 'Soft Chime (নরম সুর)', url: '/sounds/messenger/soft_chime.wav', isDefault: false }
        },
        outgoing_message: {
            'subtle_sent': { name: 'Subtle Sent (ডিফল্ট কনফার্মেশন)', url: '/sounds/messenger/subtle_sent.wav', isDefault: true },
            'soft_swoosh': { name: 'Soft Swoosh (সফট সোয়াশ)', url: '/sounds/messenger/soft_swoosh.wav', isDefault: false },
            'quick_click': { name: 'Quick Click (কুইক ক্লিক)', url: '/sounds/messenger/quick_click.wav', isDefault: false }
        }
    };

    const DEFAULT_SETTINGS = {
        messenger_sounds_enabled: true,
        incoming_call_sound_enabled: true,
        incoming_call_sound: 'bondhoo_ring',
        incoming_call_volume: 80,
        incoming_message_sound_enabled: true,
        incoming_message_sound: 'bubble_pop',
        incoming_message_volume: 75,
        outgoing_message_sound_enabled: true,
        outgoing_message_sound: 'subtle_sent',
        outgoing_message_volume: 70,
        custom_call_sound_path: null,
        custom_incoming_msg_sound_path: null,
        custom_outgoing_msg_sound_path: null
    };

    class BondhooSoundManager {
        constructor() {
            this.settings = Object.assign({}, DEFAULT_SETTINGS);
            this.audioCache = new Map();
            this.activePreviewAudio = null;
            this.activeCallAudio = null;
            this.activeCallTimer = null;
            this.currentRingingCallId = null;
            this.processedIncomingMsgIds = new Set();
            this.processedOutgoingMsgIds = new Set();
            this.audioUnlocked = false;
            this.isSyncing = false;

            this._loadSettingsFromLocal();
            this._setupUserInteractionUnlock();
            this._setupMultiTabSync();
        }

        /**
         * Initialize settings from localStorage and sync with server
         */
        _loadSettingsFromLocal() {
            try {
                const stored = localStorage.getItem('bondhoo_sound_settings');
                if (stored) {
                    const parsed = JSON.parse(stored);
                    this.settings = Object.assign({}, DEFAULT_SETTINGS, parsed);
                }
            } catch (e) {}
        }

        _saveSettingsToLocal() {
            try {
                localStorage.setItem('bondhoo_sound_settings', JSON.stringify(this.settings));
            } catch (e) {}
        }

        _setupMultiTabSync() {
            window.addEventListener('storage', (e) => {
                if (e.key === 'bondhoo_sound_settings' && e.newValue) {
                    try {
                        this.settings = Object.assign({}, DEFAULT_SETTINGS, JSON.parse(e.newValue));
                        this._dispatchSettingsChanged();
                    } catch (err) {}
                }
            });
        }

        _dispatchSettingsChanged() {
            window.dispatchEvent(new CustomEvent('bondhoo:sounds-changed', {
                detail: { settings: this.settings }
            }));
        }

        /**
         * Unlocks browser audio autoplay on first user interaction
         */
        _setupUserInteractionUnlock() {
            const unlock = () => {
                if (this.audioUnlocked) return;
                this.audioUnlocked = true;

                // Silent Web Audio context unlock
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx) {
                        const ctx = new AudioCtx();
                        if (ctx.state === 'suspended') {
                            ctx.resume();
                        }
                    }
                } catch (e) {}

                document.removeEventListener('click', unlock, true);
                document.removeEventListener('keydown', unlock, true);
                document.removeEventListener('touchstart', unlock, true);
            };

            document.addEventListener('click', unlock, { once: true, capture: true });
            document.addEventListener('keydown', unlock, { once: true, capture: true });
            document.addEventListener('touchstart', unlock, { once: true, capture: true });
        }

        /**
         * Fetch current settings from backend API
         */
        async syncSettingsFromServer() {
            if (this.isSyncing) return this.settings;
            this.isSyncing = true;
            try {
                const token = (typeof currentToken !== 'undefined' && currentToken)
                    ? currentToken
                    : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');

                const res = await fetch('/api/v1/settings/messenger-sounds', {
                    headers: Object.assign({
                        'Accept': 'application/json'
                    }, token ? { 'Authorization': `Bearer ${token}` } : {})
                });

                if (res.ok) {
                    const json = await res.json();
                    if (json.success && json.data) {
                        this.settings = Object.assign({}, this.settings, json.data);
                        this._saveSettingsToLocal();
                        this._dispatchSettingsChanged();
                    }
                }
            } catch (e) {
                // Network failure: fallback to localStorage gracefully
            } finally {
                this.isSyncing = false;
            }
            return this.settings;
        }

        /**
         * Update user preferences via API and locally
         */
        async saveSettings(updated) {
            this.settings = Object.assign({}, this.settings, updated);
            this._saveSettingsToLocal();
            this._dispatchSettingsChanged();

            try {
                const token = (typeof currentToken !== 'undefined' && currentToken)
                    ? currentToken
                    : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const res = await fetch('/api/v1/settings/messenger-sounds', {
                    method: 'PUT',
                    headers: Object.assign({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }, token ? { 'Authorization': `Bearer ${token}` } : {}),
                    body: JSON.stringify(updated)
                });
                const json = await res.json();
                if (json.success && json.data) {
                    this.settings = Object.assign({}, this.settings, json.data);
                    this._saveSettingsToLocal();
                }
                return json;
            } catch (e) {
                return { success: true, data: this.settings };
            }
        }

        /**
         * Reset sound settings to defaults
         */
        async resetSettings(type = null) {
            try {
                const token = (typeof currentToken !== 'undefined' && currentToken)
                    ? currentToken
                    : (localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const res = await fetch('/api/v1/settings/messenger-sounds/reset', {
                    method: 'POST',
                    headers: Object.assign({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }, token ? { 'Authorization': `Bearer ${token}` } : {}),
                    body: JSON.stringify({ type: type })
                });
                const json = await res.json();
                if (json.success && json.data) {
                    this.settings = Object.assign({}, this.settings, json.data);
                    this._saveSettingsToLocal();
                    this._dispatchSettingsChanged();
                    return json;
                }
            } catch (e) {}

            // Fallback local reset
            if (type === 'incoming_call') {
                this.settings.incoming_call_sound_enabled = true;
                this.settings.incoming_call_sound = 'bondhoo_ring';
                this.settings.incoming_call_volume = 80;
            } else if (type === 'incoming_message') {
                this.settings.incoming_message_sound_enabled = true;
                this.settings.incoming_message_sound = 'bubble_pop';
                this.settings.incoming_message_volume = 75;
            } else if (type === 'outgoing_message') {
                this.settings.outgoing_message_sound_enabled = true;
                this.settings.outgoing_message_sound = 'subtle_sent';
                this.settings.outgoing_message_volume = 70;
            } else {
                this.settings = Object.assign({}, DEFAULT_SETTINGS);
            }
            this._saveSettingsToLocal();
            this._dispatchSettingsChanged();
            return { success: true, data: this.settings };
        }

        /**
         * Resolves sound URL based on sound type and ID
         */
        getSoundUrl(type, soundId = null) {
            soundId = soundId || this.settings[`${type}_sound`];

            // Custom sound file override
            if (soundId === 'custom') {
                if (type === 'incoming_call' && this.settings.custom_call_sound_path) return this.settings.custom_call_sound_path;
                if (type === 'incoming_message' && this.settings.custom_incoming_msg_sound_path) return this.settings.custom_incoming_msg_sound_path;
                if (type === 'outgoing_message' && this.settings.custom_outgoing_msg_sound_path) return this.settings.custom_outgoing_msg_sound_path;
            }

            const catalogue = SOUND_CATALOGUE[type] || {};
            if (catalogue[soundId]) {
                return catalogue[soundId].url;
            }

            // Fallback to default in catalogue
            const keys = Object.keys(catalogue);
            return keys.length > 0 ? catalogue[keys[0]].url : '';
        }

        /**
         * Get or create Audio element instance
         */
        _getAudioElement(url) {
            if (!this.audioCache.has(url)) {
                const audio = new Audio(url);
                audio.preload = 'auto';
                this.audioCache.set(url, audio);
            }
            return this.audioCache.get(url);
        }

        /**
         * Stop any currently playing audio preview
         */
        stopPreview() {
            if (this.activePreviewAudio) {
                try {
                    this.activePreviewAudio.pause();
                    this.activePreviewAudio.currentTime = 0;
                } catch (e) {}
                this.activePreviewAudio = null;
            }
        }

        /**
         * Preview a sound (called from settings UI)
         */
        async previewSound(type, soundId, volumePercent = null) {
            this.stopPreview();

            if (volumePercent === null) {
                volumePercent = this.settings[`${type}_volume`] ?? 80;
            }

            const url = this.getSoundUrl(type, soundId);
            if (!url) return false;

            try {
                const audio = new Audio(url);
                audio.volume = Math.max(0, Math.min(1, volumePercent / 100));
                this.activePreviewAudio = audio;

                audio.onended = () => {
                    if (this.activePreviewAudio === audio) {
                        this.activePreviewAudio = null;
                    }
                };

                await audio.play();
                return true;
            } catch (err) {
                this.stopPreview();
                return false;
            }
        }

        /* =============================================================
         * 1. INCOMING CALL RINGTONE
         * ============================================================= */
        /**
         * Play incoming call ringtone continuously until stopped.
         * Validates callId and deduplicates.
         */
        playIncomingCallRingtone(callId = null) {
            if (!this.settings.messenger_sounds_enabled) return false;
            if (!this.settings.incoming_call_sound_enabled) return false;

            // Already ringing for this call
            if (this.currentRingingCallId && Number(this.currentRingingCallId) === Number(callId)) {
                return true;
            }

            this.stopIncomingCallRingtone();
            this.currentRingingCallId = callId;

            const url = this.getSoundUrl('incoming_call');
            const volume = Math.max(0, Math.min(1, (this.settings.incoming_call_volume ?? 80) / 100));

            const playLoop = () => {
                if (!this.currentRingingCallId) return;

                try {
                    const audio = new Audio(url);
                    audio.volume = volume;
                    this.activeCallAudio = audio;

                    audio.onended = () => {
                        if (this.currentRingingCallId) {
                            this.activeCallTimer = setTimeout(playLoop, 1200);
                        }
                    };

                    const promise = audio.play();
                    if (promise !== undefined) {
                        promise.catch(() => {
                            // Audio autoplay blocked by browser policy
                        });
                    }
                } catch (e) {}
            };

            playLoop();

            if (navigator.vibrate) {
                try { navigator.vibrate([400, 250, 400]); } catch (e) {}
            }

            return true;
        }

        /**
         * Stop incoming call ringtone immediately
         */
        stopIncomingCallRingtone(callId = null) {
            if (callId !== null && this.currentRingingCallId !== null && Number(this.currentRingingCallId) !== Number(callId)) {
                return;
            }

            this.currentRingingCallId = null;
            clearTimeout(this.activeCallTimer);
            this.activeCallTimer = null;

            if (this.activeCallAudio) {
                try {
                    this.activeCallAudio.pause();
                    this.activeCallAudio.currentTime = 0;
                } catch (e) {}
                this.activeCallAudio = null;
            }
        }

        /* =============================================================
         * 2. INCOMING MESSAGE TONE
         * ============================================================= */
        /**
         * Play short incoming message tone once.
         * Deduplicates by messageId and checks conversation mute status.
         */
        playIncomingMessageTone(messageId = null, conversationId = null, isMuted = false) {
            if (!this.settings.messenger_sounds_enabled) return false;
            if (!this.settings.incoming_message_sound_enabled) return false;
            if (isMuted) return false;

            // Deduplicate by messageId
            if (messageId) {
                const idStr = String(messageId);
                if (this.processedIncomingMsgIds.has(idStr)) {
                    return false;
                }
                this.processedIncomingMsgIds.add(idStr);
                // Keep set within 300 entries to prevent memory leak
                if (this.processedIncomingMsgIds.size > 300) {
                    const first = this.processedIncomingMsgIds.values().next().value;
                    this.processedIncomingMsgIds.delete(first);
                }
            }

            const url = this.getSoundUrl('incoming_message');
            const volume = Math.max(0, Math.min(1, (this.settings.incoming_message_volume ?? 75) / 100));

            try {
                const audio = new Audio(url);
                audio.volume = volume;
                const promise = audio.play();
                if (promise !== undefined) {
                    promise.catch(() => {});
                }
                return true;
            } catch (e) {
                return false;
            }
        }

        /* =============================================================
         * 3. OUTGOING MESSAGE SENT TONE
         * ============================================================= */
        /**
         * Play subtle confirmation tone once after backend acceptance.
         */
        playOutgoingMessageSentTone(messageId = null) {
            if (!this.settings.messenger_sounds_enabled) return false;
            if (!this.settings.outgoing_message_sound_enabled) return false;

            if (messageId) {
                const idStr = String(messageId);
                if (this.processedOutgoingMsgIds.has(idStr)) {
                    return false;
                }
                this.processedOutgoingMsgIds.add(idStr);
                if (this.processedOutgoingMsgIds.size > 300) {
                    const first = this.processedOutgoingMsgIds.values().next().value;
                    this.processedOutgoingMsgIds.delete(first);
                }
            }

            const url = this.getSoundUrl('outgoing_message');
            const volume = Math.max(0, Math.min(1, (this.settings.outgoing_message_volume ?? 70) / 100));

            try {
                const audio = new Audio(url);
                audio.volume = volume;
                const promise = audio.play();
                if (promise !== undefined) {
                    promise.catch(() => {});
                }
                return true;
            } catch (e) {
                return false;
            }
        }
    }

    // Export singleton on window
    window.BondhooSoundCatalogue = SOUND_CATALOGUE;
    window.bondhooSoundManager = new BondhooSoundManager();

    // Auto-sync settings if user is logged in
    document.addEventListener('DOMContentLoaded', () => {
        window.bondhooSoundManager.syncSettingsFromServer();
    });

})(window);
