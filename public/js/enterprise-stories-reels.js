/**
 * JUGAJUG ENTERPRISE REELS, STORIES & RESUMABLE MEDIA SUITE
 * Production-Grade Short Video & Interactive Media Platform
 * Modern architecture: Chunked Resumable Upload, Video Trim/Crop/Rotate,
 * Cover Scrubber, Royalty-Free Audio Mixer, Comments, Saves, Shares, Viewers.
 */

// =========================================================================
// 1. PRODUCTION-READY VECTOR ICON SYSTEM (JUGJUG ICONS)
// =========================================================================
const JugajugIcons = {
    reel: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>`,
    upload: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>`,
    trim: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><line x1="20" y1="4" x2="8.12" y2="15.88"></line><line x1="14.47" y1="14.48" x2="20" y2="20"></line><line x1="8.12" y1="8.12" x2="12" y2="12"></line></svg>`,
    music: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>`,
    volume: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>`,
    mute: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>`,
    cover: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`,
    caption: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`,
    emoji: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>`,
    hashtag: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>`,
    mention: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path></svg>`,
    like: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>`,
    likeFilled: `<svg width="22" height="22" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>`,
    comment: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>`,
    share: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>`,
    save: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>`,
    saveFilled: `<svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>`,
    more: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>`,
    settings: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`,
    camera: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>`,
    rotate: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>`,
    crop: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.13 1L6 16a2 2 0 0 0 2 2h15"></path><path d="M1 6.13L16 6a2 2 0 0 1 2 2v15"></path></svg>`,
    play: `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>`,
    pause: `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>`,
    close: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
    send: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>`,
    trash: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`,
    flag: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>`,
    plus: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>`,
    verified: `<svg width="11" height="11" viewBox="0 0 24 24" fill="#3b82f6"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg>`,
    viewsEye: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`
};

// =========================================================================
// 2. ENTERPRISE TOAST NOTIFICATION SYSTEM
// =========================================================================
function showToast(message, icon = '✓') {
    let container = document.getElementById('jjToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'jjToastContainer';
        container.className = 'jj-toast-box';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'jj-toast';
    toast.innerHTML = `<span style="font-size:16px;">${icon}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

// =========================================================================
// 3. ENTERPRISE RESUMABLE CHUNKED UPLOADER
// =========================================================================
class ResumableChunkUploader {
    constructor(file, options = {}) {
        this.file = file;
        this.collection = options.collection || 'reel';
        this.chunkSize = options.chunkSize || (2 * 1024 * 1024); // 2 MB per chunk
        this.totalChunks = Math.ceil(file.size / this.chunkSize) || 1;
        this.sessionId = null;
        this.isPaused = false;
        this.isCancelled = false;
        this.currentChunk = 1;
        this.uploadedChunks = new Set();
        this.maxRetries = 5;

        // Callbacks
        this.onProgress = options.onProgress || (() => {});
        this.onComplete = options.onComplete || (() => {});
        this.onError = options.onError || (() => {});
        this.onStatusChange = options.onStatusChange || (() => {});

        // Network auto-recovery
        this.onlineHandler = () => {
            if (this.sessionId && this.isPaused && !this.isCancelled) {
                this.onStatusChange('ইন্টারনেট সংযোগ ফিরে এসেছে। আপলোড পুনরায় শুরু হচ্ছে...');
                this.resume();
            }
        };
        this.offlineHandler = () => {
            if (this.sessionId && !this.isPaused && !this.isCancelled) {
                this.pause();
                this.onStatusChange('ইন্টারনেট সংযোগ ব্যাহত হয়েছে। সংযোগের জন্য অপেক্ষা করা হচ্ছে...');
            }
        };
        window.addEventListener('online', this.onlineHandler);
        window.addEventListener('offline', this.offlineHandler);
    }

    getHeaders() {
        const getCookieToken = () => {
            const m = document.cookie.match(/(?:^|;\s*)jugajug_token=([^;]+)/);
            return m ? decodeURIComponent(m[1]) : '';
        };
        const token = window.currentToken || localStorage.getItem('jugajug_token') || localStorage.getItem('auth_token') || getCookieToken() || '';
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        return headers;
    }

    async start() {
        try {
            this.onStatusChange('আপলোড সেশন ভেরিফাই ও প্রস্তুত হচ্ছে...');
            
            const initRes = await fetch('/api/v2/uploads/init', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    ...this.getHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    filename: this.file.name,
                    file_size: this.file.size,
                    mime_type: this.file.type || 'application/octet-stream',
                    collection: this.collection,
                    chunk_size: this.chunkSize
                })
            });

            const initData = await initRes.json();
            if (!initRes.ok || !initData.success) {
                if (initRes.status === 401) {
                    throw new Error('অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে পেজটি রিফ্রেশ করুন বা পুনরায় লগইন করুন।');
                }
                throw new Error(initData.message || 'আপলোড সেশন তৈরি করা সম্ভব হয়নি।');
            }

            this.sessionId = initData.data.session_id;
            this.chunkSize = initData.data.chunk_size;
            this.totalChunks = initData.data.total_chunks;

            this.onStatusChange('চাঙ্ক আপলোড শুরু হচ্ছে...');
            await this.uploadNextChunk();
        } catch (err) {
            this.onError(err);
        }
    }

    async uploadNextChunk() {
        if (this.isPaused || this.isCancelled) return;

        if (this.currentChunk > this.totalChunks) {
            this.onStatusChange('ফাইল ইন্টিগ্রিটি ও সার্ভার অ্যাসেম্বলি সম্পন্ন হচ্ছে...');
            return;
        }

        const start = (this.currentChunk - 1) * this.chunkSize;
        const end = Math.min(start + this.chunkSize, this.file.size);
        const chunkBlob = this.file.slice(start, end);

        let attempt = 0;
        let success = false;

        while (attempt < this.maxRetries && !success && !this.isPaused && !this.isCancelled) {
            try {
                attempt++;
                const formData = new FormData();
                formData.append('chunk_number', this.currentChunk);
                formData.append('chunk', chunkBlob, `chunk_${this.currentChunk}.part`);

                const res = await fetch(`/api/v2/uploads/${this.sessionId}/chunk`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: this.getHeaders(),
                    body: formData
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    success = true;
                    this.uploadedChunks.add(this.currentChunk);

                    const uploadedBytes = Math.min(this.currentChunk * this.chunkSize, this.file.size);
                    const percent = Math.round((uploadedBytes / this.file.size) * 100);
                    
                    this.onProgress(percent, uploadedBytes, this.file.size, this.currentChunk, this.totalChunks);

                    if (data.data.is_complete) {
                        this.onStatusChange('মিডিয়া প্রসেসিং সফলভাবে সম্পন্ন!');
                        this.cleanupListeners();
                        this.onComplete(data.data.media);
                        return;
                    }

                    this.currentChunk++;
                    await this.uploadNextChunk();
                } else {
                    throw new Error(data.message || 'চাঙ্ক আপলোড ব্যর্থ হয়েছে');
                }
            } catch (err) {
                if (attempt >= this.maxRetries) {
                    this.onError(new Error(`চাঙ্ক ${this.currentChunk} আপলোড ব্যর্থ হয়েছে: ${err.message}`));
                    return;
                }
                this.onStatusChange(`কানেকশন ব্যাহত। পুনরায় চেষ্টা চলছে (${attempt}/${this.maxRetries})...`);
                await new Promise(r => setTimeout(r, 1200 * attempt));
            }
        }
    }

    pause() {
        this.isPaused = true;
        this.onStatusChange('আপলোড সাময়িকভাবে স্থগিত (Paused)');
    }

    async resume() {
        if (!this.isPaused) return;
        this.isPaused = false;
        this.onStatusChange('আপলোড পুনরায় শুরু হচ্ছে...');
        try {
            const res = await fetch(`/api/v2/uploads/${this.sessionId}/resume`, {
                headers: this.getHeaders()
            });
            const data = await res.json();
            if (data.success && data.data.next_chunk) {
                this.currentChunk = data.data.next_chunk;
            }
        } catch (e) {}
        await this.uploadNextChunk();
    }

    async cancel() {
        this.isCancelled = true;
        this.onStatusChange('আপলোড বাতিল করা হয়েছে।');
        this.cleanupListeners();
        if (this.sessionId) {
            try {
                await fetch(`/api/v2/uploads/${this.sessionId}/cancel`, {
                    method: 'POST',
                    headers: this.getHeaders()
                });
            } catch (e) {}
        }
    }

    cleanupListeners() {
        window.removeEventListener('online', this.onlineHandler);
        window.removeEventListener('offline', this.offlineHandler);
    }
}

// =========================================================================
// 4. MUSIC LIBRARY & AUDIO MIXER MANAGER
// =========================================================================
const JugajugMusicSuite = {
    audioElement: new Audio(),
    currentPlayingId: null,
    tracks: [],
    selectedTrack: null,
    targetContext: 'reel', // 'reel' or 'story'

    init() {
        this.audioElement.addEventListener('ended', () => {
            this.currentPlayingId = null;
            this.updatePlayStateUI();
        });
    },

    async openModal(context = 'reel') {
        this.targetContext = context;
        const modal = document.getElementById('musicLibraryModal');
        if (modal) modal.style.display = 'flex';
        await this.loadTracks();
    },

    closeModal() {
        this.audioElement.pause();
        this.currentPlayingId = null;
        this.updatePlayStateUI();
        const modal = document.getElementById('musicLibraryModal');
        if (modal) modal.style.display = 'none';
    },

    async loadTracks(genre = '', query = '') {
        const list = document.getElementById('musicTracksList');
        if (list) list.innerHTML = '<div style="text-align:center;padding:24px;color:#64748b;">মিউজিক ট্র্যাক লোড হচ্ছে...</div>';

        try {
            const token = window.currentToken || localStorage.getItem('jugajug_token') || localStorage.getItem('auth_token') || '';
            let url = `/api/v2/music?limit=25`;
            if (genre && genre !== 'all') url += `&genre=${encodeURIComponent(genre)}`;
            if (query) url += `&query=${encodeURIComponent(query)}`;

            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': token ? `Bearer ${token}` : ''
                }
            });
            const data = await res.json();
            this.tracks = data.data || [];
            this.renderTracks();
        } catch (e) {
            if (list) list.innerHTML = '<div style="text-align:center;padding:24px;color:#ef4444;">মিউজিক লোড করতে ব্যর্থ হয়েছে।</div>';
        }
    },

    renderTracks() {
        const list = document.getElementById('musicTracksList');
        if (!list) return;

        if (this.tracks.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:30px;color:#64748b;">কোনো ট্র্যাক পাওয়া যায়নি।</div>';
            return;
        }

        let html = '';
        this.tracks.forEach(track => {
            const isPlaying = this.currentPlayingId === track.id;
            const durationSec = track.duration_seconds || 180;
            const min = Math.floor(durationSec / 60);
            const sec = durationSec % 60;
            const durStr = `${min}:${sec < 10 ? '0' : ''}${sec}`;
            const cover = track.cover_image_url || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=120&q=80';

            html += `
                <div class="music-track-item">
                    <div class="music-track-left" onclick="JugajugMusicSuite.togglePreview(${track.id}, '${track.audio_url}')">
                        <button type="button" class="music-play-btn" id="musicPlayBtn_${track.id}">
                            ${isPlaying ? JugajugIcons.pause : JugajugIcons.play}
                        </button>
                        <img src="${cover}" class="music-track-cover" alt="${track.title}">
                        <div>
                            <div style="font-weight:700;font-size:14px;color:#0f172a;">${track.title}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">
                                ${track.artist || 'Jugajug Artist'} • ${track.genre || 'Royalty Free'} • ${durStr}
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="modal-btn-submit" style="padding:6px 14px;font-size:12px;margin:0;" onclick="JugajugMusicSuite.selectTrack(${track.id})">
                            বাছাই করুন
                        </button>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    },

    togglePreview(trackId, audioUrl) {
        if (this.currentPlayingId === trackId) {
            this.audioElement.pause();
            this.currentPlayingId = null;
        } else {
            this.audioElement.src = audioUrl;
            this.audioElement.play().catch(() => {});
            this.currentPlayingId = trackId;
        }
        this.updatePlayStateUI();
    },

    updatePlayStateUI() {
        this.tracks.forEach(track => {
            const btn = document.getElementById(`musicPlayBtn_${track.id}`);
            if (btn) {
                btn.innerHTML = (this.currentPlayingId === track.id) ? JugajugIcons.pause : JugajugIcons.play;
            }
        });
    },

    selectTrack(trackId) {
        const track = this.tracks.find(t => t.id === trackId);
        if (!track) return;

        this.selectedTrack = track;
        this.closeModal();

        if (this.targetContext === 'reel') {
            JugajugMediaSuite.applyReelMusic(track);
        } else {
            JugajugMediaSuite.applyStoryMusic(track);
        }
        showToast(`'${track.title}' ব্যাকগ্রাউন্ড মিউজিক হিসেবে যোগ করা হয়েছে! 🎵`, '🎵');
    }
};

// =========================================================================
// 5. MASTER JUGAJUG MEDIA SUITE (REELS & STORIES CONTROLLER)
// =========================================================================
const JugajugMediaSuite = {
    activeTab: 'stories',
    storiesFeed: [],
    reelsFeed: [],
    currentReelIndex: 0,
    currentStoryUserIndex: 0,
    currentStoryItemIndex: 0,
    currentStoryMediaIndex: 0,
    isMuted: false,
    storyTimer: null,
    storyProgressInterval: null,
    storyDurationMs: 5000,
    storyElapsedMs: 0,
    isStoryPaused: false,
    currentUploader: null,

    // Reel Studio Editor State
    reelStep: 'media', // 'media', 'editor', 'audio', 'details'
    selectedReelFile: null,
    reelVideoUrl: '',
    reelTrimStart: 0,
    reelTrimEnd: 0,
    reelVideoDuration: 0,
    reelRotation: 0,
    reelCropAspect: '9:16',
    reelCoverDataUrl: null,
    reelCustomCoverFile: null,
    reelSelectedMusic: null,
    reelOriginalVolume: 1.0,
    reelMusicVolume: 0.75,
    reelMusicStartOffset: 0,

    // Camera Recording State
    cameraStream: null,
    mediaRecorder: null,
    recordedBlobs: [],
    isRecordingCamera: false,
    cameraTimer: null,
    cameraSeconds: 0,

    // Story State
    selectedStoryFiles: [],
    storyMusicTrack: null,
    selectedEmoji: '',
    activeReelChannelId: null,

    init() {
        JugajugMusicSuite.init();
        this.bindGlobalKeyboard();
        this.loadStories();
        this.initRealtimeListeners();
    },

    initRealtimeListeners() {
        if (typeof window.Echo === 'undefined') {
            setTimeout(() => {
                if (typeof window.Echo !== 'undefined') {
                    this.setupEchoChannels();
                }
            }, 1000);
            return;
        }
        this.setupEchoChannels();
    },

    setupEchoChannels() {
        if (typeof window.Echo === 'undefined') return;

        // Global Reels Channel
        window.Echo.channel('reels')
            .listen('.reel.created', (reel) => {
                if (!reel || !reel.id) return;
                if (!this.reelsFeed.some(r => r.id === reel.id)) {
                    this.reelsFeed.unshift(reel);
                    if (this.activeTab === 'reels') {
                        this.renderReels();
                    }
                }
            })
            .listen('.reel.deleted', (data) => {
                if (!data || !data.reel_id) return;
                const id = data.reel_id;
                this.reelsFeed = this.reelsFeed.filter(r => r.id != id);
                if (this.activeTab === 'reels') {
                    this.renderReels();
                }
                const cur = this.reelsFeed[this.currentReelIndex];
                if (cur && cur.id == id) {
                    showToast('এই রিলটি মুছে ফেলা হয়েছে।', '🗑️');
                    this.closeReelViewer();
                }
            })
            .listen('.reel.expired', (data) => {
                if (!data || !data.reel_id) return;
                this.handleExpiredItem('reel', data.reel_id);
            });

        // Global Stories Channel
        window.Echo.channel('stories')
            .listen('.story.created', () => {
                this.loadStories();
            })
            .listen('.story.deleted', (data) => {
                if (data && data.story_id) {
                    this.loadStories();
                }
            })
            .listen('.story.expired', (data) => {
                if (!data || !data.story_id) return;
                this.handleExpiredItem('story', data.story_id);
            });
    },

    subscribeToReelChannel(reelId) {
        if (typeof window.Echo === 'undefined' || !reelId) return;
        this.unsubscribeFromReelChannel();
        this.activeReelChannelId = reelId;

        window.Echo.channel(`reel.${reelId}`)
            .listen('.reel.reaction.updated', (data) => {
                if (data && data.reel_id == reelId) {
                    const likesCount = document.getElementById('fbReelLikesCount');
                    if (likesCount && data.count !== undefined) {
                        likesCount.innerText = data.count;
                    }
                    const cur = this.reelsFeed.find(r => r.id == reelId);
                    if (cur) cur.likes_count = data.count;
                }
            })
            .listen('.reel.comment.created', (data) => {
                if (data && data.comment) {
                    const commentsCount = document.getElementById('fbReelCommentsCount');
                    const countTitle = document.getElementById('reelCommentsCountText');
                    const cur = this.reelsFeed.find(r => r.id == reelId);
                    if (cur) {
                        cur.comments_count = (cur.comments_count || 0) + 1;
                        if (commentsCount) commentsCount.innerText = cur.comments_count;
                        if (countTitle) countTitle.innerText = `${cur.comments_count}টি মন্তব্য`;
                    }
                    this.prependCommentToDrawer(data.comment);
                }
            })
            .listen('.reel.comment.deleted', (data) => {
                if (data && data.comment_id) {
                    document.getElementById(`reelCommentRow_${data.comment_id}`)?.remove();
                    const cur = this.reelsFeed.find(r => r.id == reelId);
                    if (cur && cur.comments_count > 0) {
                        cur.comments_count--;
                        const commentsCount = document.getElementById('fbReelCommentsCount');
                        const countTitle = document.getElementById('reelCommentsCountText');
                        if (commentsCount) commentsCount.innerText = cur.comments_count;
                        if (countTitle) countTitle.innerText = `${cur.comments_count}টি মন্তব্য`;
                    }
                }
            })
            .listen('.reel.expired', () => {
                this.handleExpiredItem('reel', reelId);
            });
    },

    unsubscribeFromReelChannel() {
        if (typeof window.Echo !== 'undefined' && this.activeReelChannelId) {
            window.Echo.leave(`reel.${this.activeReelChannelId}`);
        }
        this.activeReelChannelId = null;
    },

    handleExpiredItem(type, id) {
        showToast('এই ' + (type === 'reel' ? 'রিলটির' : 'স্টোরিটির') + ' ২৪ ঘণ্টার মেয়াদ শেষ হয়ে গেছে।', '⏳');
        if (type === 'reel') {
            this.reelsFeed = this.reelsFeed.filter(r => r.id != id);
            if (this.activeTab === 'reels') {
                this.renderReels();
            }
            const modal = document.getElementById('fbReelViewerModal');
            if (modal && modal.style.display === 'flex') {
                const cur = this.reelsFeed[this.currentReelIndex];
                if (!cur || cur.id == id) {
                    if (this.reelsFeed.length > 0) {
                        this.currentReelIndex = Math.min(this.currentReelIndex, this.reelsFeed.length - 1);
                        this.openReelViewerByIndex(this.currentReelIndex);
                    } else {
                        this.closeReelViewer();
                    }
                }
            }
        } else {
            this.loadStories();
            const storyModal = document.getElementById('fbStoryViewerModal');
            if (storyModal && storyModal.style.display === 'flex') {
                const userGroup = this.storiesFeed[this.currentStoryUserIndex];
                const curStory = userGroup?.stories[this.currentStoryItemIndex];
                if (!curStory || curStory.id == id) {
                    this.nextStoryItem();
                }
            }
        }
    },

    prependCommentToDrawer(c) {
        const list = document.getElementById('reelCommentsListContainer');
        if (!list) return;
        if (document.getElementById(`reelCommentRow_${c.id}`)) return;

        const author = c.user?.name || 'ব্যবহারকারী';
        const avatar = c.user?.profile?.avatar_url || c.user?.avatar_url;
        const isAuthor = (window.currentUser?.id && c.user?.id === window.currentUser.id);

        const row = document.createElement('div');
        row.className = 'reel-comment-item';
        row.id = `reelCommentRow_${c.id}`;
        row.innerHTML = `
            <div class="reel-comment-avatar">
                ${avatar ? `<img src="${avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : author.charAt(0)}
            </div>
            <div class="reel-comment-body">
                <div class="reel-comment-author">${author}</div>
                <div class="reel-comment-text">${c.comment || ''}</div>
                <div class="reel-comment-meta">
                    <span>এইমাত্র</span>
                    <button type="button" class="reel-comment-like-btn" onclick="JugajugMediaSuite.toggleReelCommentLike(${c.id})">
                        ❤️ <span id="commentLikesCount_${c.id}">0</span>
                    </button>
                    ${isAuthor ? `<button type="button" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:11px;" onclick="JugajugMediaSuite.deleteReelComment(${c.id})">মুছে ফেলুন</button>` : ''}
                </div>
            </div>
        `;
        if (list.children.length === 1 && !list.children[0].classList.contains('reel-comment-item')) {
            list.innerHTML = '';
        }
        list.prepend(row);
    },

    bindGlobalKeyboard() {
        window.addEventListener('keydown', (e) => {
            const reelModal = document.getElementById('fbReelViewerModal');
            if (reelModal && reelModal.style.display === 'flex') {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.nextReel();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.prevReel();
                } else if (e.key === 'Escape') {
                    this.closeReelViewer();
                } else if (e.key === ' ' || e.key === 'k') {
                    e.preventDefault();
                    this.toggleReelPlayPause();
                } else if (e.key === 'm') {
                    this.toggleReelMute();
                }
            }

            const storyModal = document.getElementById('fbStoryViewerModal');
            if (storyModal && storyModal.style.display === 'flex') {
                if (e.key === 'ArrowRight' || e.key === ' ') {
                    e.preventDefault();
                    this.nextStoryItem();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prevStoryItem();
                } else if (e.key === 'Escape') {
                    this.closeStoryViewer();
                }
            }
        });
    },

    getAuthHeaders() {
        const getCookieToken = () => {
            const m = document.cookie.match(/(?:^|;\s*)jugajug_token=([^;]+)/);
            return m ? decodeURIComponent(m[1]) : '';
        };
        const token = window.currentToken || localStorage.getItem('jugajug_token') || localStorage.getItem('auth_token') || getCookieToken() || '';
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        return headers;
    },

    switchTab(tab) {
        this.activeTab = tab;
        const storiesBtn = document.getElementById('srTabStories');
        const reelsBtn = document.getElementById('srTabReels');
        const storiesView = document.getElementById('srStoriesWrapper');
        const reelsView = document.getElementById('srReelsWrapper');

        if (tab === 'stories') {
            storiesBtn?.classList.add('active');
            reelsBtn?.classList.remove('active');
            if (storiesView) storiesView.style.display = 'block';
            if (reelsView) reelsView.style.display = 'none';
            this.loadStories();
        } else {
            reelsBtn?.classList.add('active');
            storiesBtn?.classList.remove('active');
            if (storiesView) storiesView.style.display = 'none';
            if (reelsView) reelsView.style.display = 'block';
            this.loadReels();
        }
    },

    /* =====================================================================
     * STORIES FEED & CAROUSEL
     * ===================================================================== */
    async loadStories() {
        const carousel = document.getElementById('storiesCarousel');
        if (!carousel) return;

        try {
            const res = await fetch('/api/v2/stories/feed', { headers: this.getAuthHeaders() });
            const json = await res.json();
            this.storiesFeed = json.data || [];

            const currentUser = window.currentUser || {};
            const userInitial = (currentUser.name || 'ইউ').charAt(0);
            const userAvatar = currentUser.avatar_url || currentUser.profile?.avatar_url || '';

            let html = `
                <div class="fb-story-card fb-create-story-card" onclick="JugajugMediaSuite.openCreateStoryModal()">
                    <div class="avatar-preview" style="background-image: url('${userAvatar}'); background-color: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                        ${!userAvatar ? `<span style="font-size: 38px; font-weight: 800; color: #64748b;">${userInitial}</span>` : ''}
                    </div>
                    <div class="bottom-action">
                        <div class="fb-create-story-plus">+</div>
                        <span class="fb-create-story-label">স্টোরি তৈরি করুন</span>
                    </div>
                </div>
            `;

            this.storiesFeed.forEach((userGroup, uIdx) => {
                const author = userGroup.user?.name || 'ব্যবহারকারী';
                const avatar = userGroup.user?.avatar_url || '';
                const initial = author.charAt(0);
                const allViewed = userGroup.all_viewed;
                const firstStory = userGroup.stories?.[0] || {};

                let bgStyle = '';
                let textPreview = '';

                if (firstStory.media && firstStory.media.length > 0) {
                    const m = firstStory.media[0];
                    const thumbUrl = m.urls?.thumbnail || m.urls?.original || '';
                    bgStyle = `background-image: url('${thumbUrl}'); background-size: cover;`;
                } else {
                    bgStyle = `background: ${firstStory.background_color || 'linear-gradient(135deg, #1877f2, #00c6ff)'};`;
                    textPreview = `<div class="fb-story-text-preview">${firstStory.content || ''}</div>`;
                }

                html += `
                    <div class="fb-story-card" onclick="JugajugMediaSuite.openStoryViewer(${uIdx}, 0)">
                        <div class="fb-story-bg" style="${bgStyle}">
                            <div class="fb-story-overlay"></div>
                            ${textPreview}
                            <div class="fb-story-ring ${allViewed ? 'viewed' : ''}">
                                ${avatar ? `<img src="${avatar}" alt="${author}">` : `<div class="initial-avatar">${initial}</div>`}
                            </div>
                            <div class="fb-story-footer-name">${author}</div>
                        </div>
                    </div>
                `;
            });

            carousel.innerHTML = html;
        } catch (err) {
            console.error('Stories feed loading failed:', err);
        }
    },

    openStoryViewer(userIndex, storyIndex = 0) {
        if (!this.storiesFeed[userIndex]) return;
        this.currentStoryUserIndex = userIndex;
        this.currentStoryItemIndex = storyIndex;
        this.currentStoryMediaIndex = 0;
        
        const modal = document.getElementById('fbStoryViewerModal');
        if (modal) modal.style.display = 'flex';
        this.renderCurrentStoryItem();
    },

    renderCurrentStoryItem() {
        this.clearStoryTimers();
        const userGroup = this.storiesFeed[this.currentStoryUserIndex];
        if (!userGroup) {
            this.closeStoryViewer();
            return;
        }

        const story = userGroup.stories[this.currentStoryItemIndex];
        if (!story) {
            if (this.currentStoryUserIndex + 1 < this.storiesFeed.length) {
                this.currentStoryUserIndex++;
                this.currentStoryItemIndex = 0;
                this.currentStoryMediaIndex = 0;
                this.renderCurrentStoryItem();
            } else {
                this.closeStoryViewer();
            }
            return;
        }

        // Author meta
        const authorName = document.getElementById('fbStoryAuthorName');
        const authorTime = document.getElementById('fbStoryTimeAgo');
        const authorAvatar = document.getElementById('fbStoryAuthorAvatar');
        const musicBadge = document.getElementById('fbStoryMusicBadge');
        const author = userGroup.user?.name || 'ব্যবহারকারী';

        if (authorName) {
            authorName.innerText = author;
            authorName.style.cursor = 'pointer';
            authorName.onclick = () => {
                if (userGroup.user) {
                    window.location.href = window.getUserProfileUrl ? window.getUserProfileUrl(userGroup.user) : `/u/${encodeURIComponent(userGroup.user.username || userGroup.user.id)}`;
                }
            };
        }
        if (authorTime) authorTime.innerText = this.formatTimeAgo(story.created_at);
        if (authorAvatar) {
            authorAvatar.style.cursor = 'pointer';
            authorAvatar.onclick = () => {
                if (userGroup.user) {
                    window.location.href = window.getUserProfileUrl ? window.getUserProfileUrl(userGroup.user) : `/u/${encodeURIComponent(userGroup.user.username || userGroup.user.id)}`;
                }
            };
            authorAvatar.innerHTML = userGroup.user?.avatar_url
                ? `<img src="${userGroup.user.avatar_url}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
                : author.charAt(0);
        }

        // Background Music Indicator
        if (musicBadge) {
            if (story.music_track) {
                musicBadge.style.display = 'inline-flex';
                musicBadge.innerHTML = `🎵 ${story.music_track.title} - ${story.music_track.artist}`;
            } else {
                musicBadge.style.display = 'none';
            }
        }

        // Author Viewers Pill & Delete Control
        const viewersPill = document.getElementById('fbStoryAuthorViewersPill');
        const deleteBtn = document.getElementById('fbStoryDeleteBtn');
        const currentUserId = window.currentUser?.id;
        const isOwner = (currentUserId && userGroup.user?.id === currentUserId);

        if (viewersPill) {
            if (isOwner) {
                viewersPill.style.display = 'inline-flex';
                viewersPill.innerText = `👁️ ${story.views_count || 0} জন দেখেছেন`;
                viewersPill.onclick = () => this.openStoryViewersModal(story.id);
            } else {
                viewersPill.style.display = 'none';
            }
        }

        if (deleteBtn) {
            deleteBtn.style.display = isOwner ? 'inline-flex' : 'none';
        }

        // Render Multi-segment Progress Bars
        this.renderStoryProgressBars(userGroup.stories.length, this.currentStoryItemIndex);

        // Viewport content
        const viewport = document.getElementById('fbStoryViewport');
        if (!viewport) return;
        viewport.innerHTML = '';

        if (story.type === 'media' && story.media && story.media.length > 0) {
            const currentMedia = story.media[this.currentStoryMediaIndex] || story.media[0];
            const mediaUrl = currentMedia.urls?.original || '';

            if (currentMedia.type === 'video') {
                const vid = document.createElement('video');
                vid.src = mediaUrl;
                vid.autoplay = true;
                vid.playsInline = true;
                vid.muted = this.isMuted;
                vid.style.cssText = 'width:100%;height:100%;object-fit:contain;background:#000;';

                vid.onloadedmetadata = () => {
                    this.storyDurationMs = Math.min(Math.max((vid.duration || 5) * 1000, 3000), 60000);
                    this.startStoryTimer();
                };
                vid.onerror = () => {
                    this.storyDurationMs = 5000;
                    this.startStoryTimer();
                };
                viewport.appendChild(vid);
            } else {
                const img = document.createElement('img');
                img.src = mediaUrl;
                img.style.cssText = 'width:100%;height:100%;object-fit:contain;background:#000;';
                viewport.appendChild(img);
                this.storyDurationMs = 5000;
                this.startStoryTimer();
            }
        } else {
            const textCard = document.createElement('div');
            textCard.style.cssText = `width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:30px;background:${story.background_color || '#1877f2'};color:#fff;font-size:24px;font-weight:700;text-align:center;font-family:${story.font_family || 'Inter'};`;
            textCard.innerText = story.content || '';
            viewport.appendChild(textCard);
            this.storyDurationMs = 5000;
            this.startStoryTimer();
        }

        // Interactive Poll Sticker
        if (story.interactive_sticker && story.interactive_sticker.type === 'poll') {
            const poll = story.interactive_sticker;
            const pollEl = document.createElement('div');
            pollEl.className = 'fb-story-poll-overlay';
            pollEl.innerHTML = `
                <div class="fb-poll-question">${poll.question}</div>
                <div class="fb-poll-options">
                    <button type="button" class="fb-poll-btn" onclick="JugajugMediaSuite.votePoll(${story.id}, 1)">${poll.option1}</button>
                    <button type="button" class="fb-poll-btn" onclick="JugajugMediaSuite.votePoll(${story.id}, 2)">${poll.option2}</button>
                </div>
            `;
            viewport.appendChild(pollEl);
        }

        this.recordStoryView(story.id);
    },

    renderStoryProgressBars(totalStories, currentIndex) {
        const barBox = document.getElementById('fbStoryProgressBarsBox');
        if (!barBox) return;
        barBox.innerHTML = '';

        for (let i = 0; i < totalStories; i++) {
            const segment = document.createElement('div');
            segment.className = 'fb-story-progress-segment';
            const fill = document.createElement('div');
            fill.className = 'fb-story-progress-fill';
            fill.id = `fbStoryProgressFill_${i}`;
            if (i < currentIndex) fill.style.width = '100%';
            else if (i === currentIndex) fill.style.width = '0%';
            segment.appendChild(fill);
            barBox.appendChild(segment);
        }
    },

    startStoryTimer() {
        this.clearStoryTimers();
        this.storyElapsedMs = 0;
        const currentBar = document.getElementById(`fbStoryProgressFill_${this.currentStoryItemIndex}`);
        const intervalStep = 50;

        this.storyProgressInterval = setInterval(() => {
            if (this.isStoryPaused) return;

            this.storyElapsedMs += intervalStep;
            const progress = Math.min((this.storyElapsedMs / this.storyDurationMs) * 100, 100);

            if (currentBar) {
                currentBar.style.width = `${progress}%`;
            }

            if (this.storyElapsedMs >= this.storyDurationMs) {
                this.nextStoryItem();
            }
        }, intervalStep);
    },

    pauseStory() {
        this.isStoryPaused = true;
        const vid = document.querySelector('#fbStoryViewport video');
        if (vid) vid.pause();
    },

    resumeStory() {
        this.isStoryPaused = false;
        const vid = document.querySelector('#fbStoryViewport video');
        if (vid) vid.play().catch(() => {});
    },

    nextStoryItem() {
        const userGroup = this.storiesFeed[this.currentStoryUserIndex];
        const story = userGroup?.stories[this.currentStoryItemIndex];
        if (story && story.media && story.media.length > 1 && (this.currentStoryMediaIndex + 1) < story.media.length) {
            this.currentStoryMediaIndex++;
            this.renderCurrentStoryItem();
            return;
        }

        this.currentStoryMediaIndex = 0;
        this.currentStoryItemIndex++;
        this.renderCurrentStoryItem();
    },

    prevStoryItem() {
        if (this.currentStoryMediaIndex > 0) {
            this.currentStoryMediaIndex--;
            this.renderCurrentStoryItem();
            return;
        }

        if (this.currentStoryItemIndex > 0) {
            this.currentStoryItemIndex--;
            this.renderCurrentStoryItem();
        } else if (this.currentStoryUserIndex > 0) {
            this.currentStoryUserIndex--;
            const prevStories = this.storiesFeed[this.currentStoryUserIndex]?.stories || [];
            this.currentStoryItemIndex = Math.max(prevStories.length - 1, 0);
            this.renderCurrentStoryItem();
        }
    },

    closeStoryViewer() {
        this.clearStoryTimers();
        const modal = document.getElementById('fbStoryViewerModal');
        if (modal) modal.style.display = 'none';
        const viewport = document.getElementById('fbStoryViewport');
        if (viewport) viewport.innerHTML = '';
        this.loadStories();
    },

    clearStoryTimers() {
        if (this.storyProgressInterval) clearInterval(this.storyProgressInterval);
        if (this.storyTimer) clearTimeout(this.storyTimer);
        this.storyProgressInterval = null;
        this.storyTimer = null;
    },

    async recordStoryView(storyId) {
        try {
            const res = await fetch(`/api/v2/stories/${storyId}/view`, {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            if (res.status === 410) {
                this.handleExpiredItem('story', storyId);
            }
        } catch (e) {}
    },

    async sendStoryReaction(type) {
        const userGroup = this.storiesFeed[this.currentStoryUserIndex];
        const story = userGroup?.stories[this.currentStoryItemIndex];
        if (!story) return;

        try {
            const res = await fetch(`/api/v2/stories/${story.id}/react`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type })
            });
            if (res.status === 410) {
                this.handleExpiredItem('story', story.id);
                return;
            }
            const data = await res.json();
            if (data.success) {
                this.showReactionAnimation(type);
            }
        } catch (e) {}
    },

    async sendStoryReply() {
        const input = document.getElementById('fbStoryReplyInput');
        const message = input?.value.trim();
        if (!message) return;

        const userGroup = this.storiesFeed[this.currentStoryUserIndex];
        const story = userGroup?.stories[this.currentStoryItemIndex];
        if (!story) return;

        try {
            const res = await fetch(`/api/v2/stories/${story.id}/reply`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });
            if (res.status === 410) {
                this.handleExpiredItem('story', story.id);
                return;
            }
            const data = await res.json();
            if (data.success) {
                input.value = '';
                showToast('রিপ্লাই পাঠানো হয়েছে! 💬', '💬');
            }
        } catch (e) {}
    },

    showReactionAnimation(type) {
        const emojiMap = {
            like: '👍', love: '❤️', care: '🥰', haha: '😂', wow: '😮', sad: '😢', angry: '😡'
        };
        const char = emojiMap[type] || '❤️';
        const flyEl = document.createElement('div');
        flyEl.innerText = char;
        flyEl.style.cssText = 'position:fixed;bottom:90px;right:40px;font-size:45px;z-index:999999;pointer-events:none;transition:all 1s cubic-bezier(0.2, 0.8, 0.2, 1);opacity:1;';
        document.body.appendChild(flyEl);

        setTimeout(() => {
            flyEl.style.transform = 'translateY(-200px) scale(1.6)';
            flyEl.style.opacity = '0';
        }, 50);
        setTimeout(() => flyEl.remove(), 1100);
    },

    async openStoryViewersModal(storyId) {
        this.pauseStory();
        const modal = document.getElementById('storyViewersListModal');
        const listContainer = document.getElementById('storyViewersContentList');
        if (modal) modal.style.display = 'flex';
        if (listContainer) listContainer.innerHTML = '<div style="text-align:center;padding:24px;color:#64748b;">ভিউয়ারদের তালিকা লোড হচ্ছে...</div>';

        try {
            const res = await fetch(`/api/v2/stories/${storyId}/viewers`, { headers: this.getAuthHeaders() });
            if (res.status === 410) {
                if (modal) modal.style.display = 'none';
                this.handleExpiredItem('story', storyId);
                return;
            }
            const data = await res.json();
            if (data.success && listContainer) {
                if (data.data.length === 0) {
                    listContainer.innerHTML = '<div style="text-align:center;color:#64748b;padding:30px;">এখনো কেউ এই স্টোরিটি দেখেননি।</div>';
                    return;
                }
                let html = '';
                data.data.forEach(v => {
                    const avatar = v.user?.avatar_url;
                    const name = v.user?.name || 'ব্যবহারকারী';
                    html += `
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:38px;height:38px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">
                                    ${avatar ? `<img src="${avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : name.charAt(0)}
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:14px;color:#0f172a;">${name}</div>
                                    <div style="font-size:11px;color:#64748b;">${this.formatTimeAgo(v.viewed_at)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            }
        } catch (e) {
            if (listContainer) listContainer.innerHTML = '<div style="color:red;text-align:center;padding:20px;">ভিউয়ারদের তালিকা লোড করতে ব্যর্থ হয়েছে।</div>';
        }
    },

    closeStoryViewersModal() {
        const modal = document.getElementById('storyViewersListModal');
        if (modal) modal.style.display = 'none';
        this.resumeStory();
    },

    votePoll(storyId, optionNum) {
        showToast(`পোল অপশন ${optionNum}-এ আপনার মতামত গৃহীত হয়েছে!`, '✓');
    },

    /* =====================================================================
     * CREATE STORY MODAL & MULTI-MEDIA RESUMABLE UPLOADER
     * ===================================================================== */
    openCreateStoryModal() {
        const modal = document.getElementById('createStoryModalV2');
        if (modal) modal.style.display = 'flex';
        this.resetCreateStoryForm();
    },

    closeCreateStoryModal() {
        const modal = document.getElementById('createStoryModalV2');
        if (modal) modal.style.display = 'none';
        if (this.currentUploader) {
            this.currentUploader.cancel();
            this.currentUploader = null;
        }
    },

    switchCreateStoryTab(tab) {
        const photoTab = document.getElementById('cstTabPhoto');
        const textTab = document.getElementById('cstTabText');
        const photoSection = document.getElementById('cstPhotoSection');
        const textSection = document.getElementById('cstTextSection');

        if (tab === 'photo') {
            photoTab?.classList.add('active');
            textTab?.classList.remove('active');
            if (photoSection) photoSection.style.display = 'block';
            if (textSection) textSection.style.display = 'none';
        } else {
            textTab?.classList.add('active');
            photoTab?.classList.remove('active');
            if (photoSection) photoSection.style.display = 'none';
            if (textSection) textSection.style.display = 'block';
            this.updateStoryTextPreview();
        }
    },

    setStoryBgPreset(gradient, dotEl) {
        const select = document.getElementById('storyTextBgPreset');
        if (select) select.value = gradient;
        document.querySelectorAll('.story-palette-dot').forEach(d => d.classList.remove('active'));
        if (dotEl) dotEl.classList.add('active');
        this.updateStoryTextPreview();
    },

    updateStoryTextPreview() {
        const card = document.getElementById('storyTextPreviewCard');
        const text = document.getElementById('storyTextContent')?.value || 'আপনার মনের কথা এখানে লিখুন...';
        const bg = document.getElementById('storyTextBgPreset')?.value || 'linear-gradient(135deg, #1877f2, #00c6ff)';
        const font = document.getElementById('storyFontFamilySelect')?.value || 'Hind Siliguri, sans-serif';
        if (card) {
            card.style.background = bg;
            card.style.fontFamily = font;
            card.innerText = text;
        }
    },

    resetCreateStoryForm() {
        this.selectedStoryFiles = [];
        this.storyMusicTrack = null;
        this.selectedEmoji = '';
        const fileInput = document.getElementById('storyMediaFileInput');
        if (fileInput) fileInput.value = '';
        const grid = document.getElementById('storyMultiMediaGrid');
        if (grid) { grid.innerHTML = ''; grid.style.display = 'none'; }
        const progressBox = document.getElementById('storyUploadProgressBox');
        if (progressBox) progressBox.style.display = 'none';
        const musicBadge = document.getElementById('storyAttachedMusicBadge');
        if (musicBadge) musicBadge.style.display = 'none';
        const pollContainer = document.getElementById('storyPollFieldsBox');
        if (pollContainer) pollContainer.style.display = 'none';
        const textContent = document.getElementById('storyTextContent');
        if (textContent) textContent.value = '';
        this.switchCreateStoryTab('photo');
    },

    handleStoryFileSelect(e) {
        const files = Array.from(e.target.files || []);
        if (!files.length) return;
        this.selectedStoryFiles = this.selectedStoryFiles.concat(files);
        this.renderStoryFilesPreview();
    },

    async startStoryCameraCapture() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('আপনার ব্রাউজার ক্যামেরা সাপোর্ট করে না।');
            return;
        }

        try {
            showToast('ক্যামেরা প্রস্তুত হচ্ছে... 📷', '📷');
            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'user' }, width: { ideal: 720 }, height: { ideal: 1280 } },
                    audio: true
                });
            } catch (strictErr) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                } catch (pairErr) {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                }
            }

            const video = document.createElement('video');
            video.srcObject = stream;
            video.muted = true;
            video.playsInline = true;
            await video.play();

            // Short countdown or instant snapshot
            setTimeout(() => {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 720;
                canvas.height = video.videoHeight || 1280;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                stream.getTracks().forEach(t => t.stop());

                canvas.toBlob(blob => {
                    if (blob) {
                        const file = new File([blob], `story_camera_${Date.now()}.jpg`, { type: 'image/jpeg' });
                        this.selectedStoryFiles.push(file);
                        this.renderStoryFilesPreview();
                        showToast('ক্যামেরা ছবি সফলভাবে যোগ করা হয়েছে! 📸', '✓');
                    }
                }, 'image/jpeg', 0.9);
            }, 600);
        } catch (err) {
            alert('ক্যামেরা চালু করতে সমস্যা হয়েছে: ' + err.message);
        }
    },

    renderStoryFilesPreview() {
        const grid = document.getElementById('storyMultiMediaGrid');
        if (!grid) return;

        if (this.selectedStoryFiles.length === 0) {
            grid.style.display = 'none';
            grid.innerHTML = '';
            return;
        }

        grid.style.display = 'grid';
        let html = '';
        this.selectedStoryFiles.forEach((file, idx) => {
            const isVid = file.type.startsWith('video/');
            const url = URL.createObjectURL(file);
            const badgeText = isVid ? '🎬 ভিডিও' : '📷 ছবি';
            const sizeMb = (file.size / 1024 / 1024).toFixed(1);

            html += `
                <div class="multi-media-item">
                    ${isVid ? `<video src="${url}" muted playsinline></video>` : `<img src="${url}">`}
                    <span class="multi-media-type-badge">${badgeText} (${sizeMb}M)</span>
                    <button type="button" class="multi-media-remove" onclick="JugajugMediaSuite.removeStoryFile(${idx})" title="মুছে ফেলুন">✕</button>
                </div>
            `;
        });

        if (this.selectedStoryFiles.length < 10) {
            html += `
                <div class="multi-media-item" onclick="document.getElementById('storyMediaFileInput')?.click()" style="display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed #94a3b8; border-radius:12px; cursor:pointer; background:rgba(241,245,249,0.7); min-height:100px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span style="font-size:11px; font-weight:700; color:#64748b; margin-top:4px;">আরও যোগ</span>
                </div>
            `;
        }

        grid.innerHTML = html;
    },

    removeStoryFile(index) {
        this.selectedStoryFiles.splice(index, 1);
        this.renderStoryFilesPreview();
    },

    applyStoryMusic(track) {
        this.storyMusicTrack = track;
        const badge = document.getElementById('storyAttachedMusicBadge');
        const titleEl = document.getElementById('storyAttachedMusicTitle');
        if (badge) badge.style.display = 'flex';
        if (titleEl) titleEl.innerText = `${track.title} - ${track.artist}`;
    },

    removeStoryMusic() {
        this.storyMusicTrack = null;
        const badge = document.getElementById('storyAttachedMusicBadge');
        if (badge) badge.style.display = 'none';
    },

    toggleStoryPoll() {
        const box = document.getElementById('storyPollFieldsBox');
        if (box) {
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
        }
    },

    async submitStory() {
        const activeTabIsPhoto = document.getElementById('cstTabPhoto')?.classList.contains('active');
        const privacy = document.getElementById('storyPrivacySelect')?.value || 'public';
        const submitBtn = document.getElementById('storySubmitBtn');

        // Poll Sticker
        let sticker = null;
        const pollQuestion = document.getElementById('storyPollQuestionInput')?.value.trim();
        const pollOpt1 = document.getElementById('storyPollOpt1Input')?.value.trim();
        const pollOpt2 = document.getElementById('storyPollOpt2Input')?.value.trim();
        if (pollQuestion && pollOpt1 && pollOpt2) {
            sticker = {
                type: 'poll',
                question: pollQuestion,
                option1: pollOpt1,
                option2: pollOpt2
            };
        }

        if (activeTabIsPhoto) {
            if (!this.selectedStoryFiles || this.selectedStoryFiles.length === 0) {
                alert('অনুগ্রহ করে অন্তত একটি ছবি বা ভিডিও নির্বাচন করুন।');
                return;
            }

            const progressBox = document.getElementById('storyUploadProgressBox');
            const progressBar = document.getElementById('storyProgressBarFill');
            const progressPercent = document.getElementById('storyProgressPercentText');
            const progressStatus = document.getElementById('storyProgressStatusText');

            if (progressBox) progressBox.style.display = 'block';
            if (submitBtn) submitBtn.disabled = true;

            const totalFiles = this.selectedStoryFiles.length;
            const uploadedMediaIds = [];

            try {
                for (let i = 0; i < totalFiles; i++) {
                    const file = this.selectedStoryFiles[i];
                    const collection = file.type.startsWith('video/') ? 'story_video' : 'story_photo';

                    if (progressStatus) {
                        progressStatus.innerText = `ফাইল ${i + 1}/${totalFiles} (${file.name}) আপলোড হচ্ছে...`;
                    }

                    const uploadedMedia = await new Promise((resolve, reject) => {
                        this.currentUploader = new ResumableChunkUploader(file, {
                            collection: collection,
                            onProgress: (percent, loadedBytes, totalBytes) => {
                                const overall = Math.round(((i + (percent / 100)) / totalFiles) * 100);
                                if (progressBar) progressBar.style.width = `${overall}%`;
                                if (progressPercent) progressPercent.innerText = `${overall}%`;
                            },
                            onStatusChange: (status) => {
                                if (progressStatus) progressStatus.innerText = status;
                            },
                            onComplete: (media) => resolve(media),
                            onError: (err) => reject(err)
                        });
                        this.currentUploader.start();
                    });

                    if (uploadedMedia && uploadedMedia.id) {
                        uploadedMediaIds.push(uploadedMedia.id);
                    }
                }

                if (progressStatus) progressStatus.innerText = 'স্টোরি সংরক্ষণ ও প্রকাশ হচ্ছে...';

                const caption = document.getElementById('storyPhotoCaption')?.value.trim() || '';
                const payload = {
                    type: 'media',
                    content: caption,
                    media_ids: uploadedMediaIds,
                    privacy: privacy,
                    music_track_id: this.storyMusicTrack ? this.storyMusicTrack.id : null,
                    interactive_sticker: sticker
                };

                const res = await fetch('/api/v2/stories', {
                    method: 'POST',
                    headers: {
                        ...this.getAuthHeaders(),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    showToast('স্টোরি সফলভাবে প্রকাশিত হয়েছে! 🌟', '🌟');
                    this.closeCreateStoryModal();
                    this.loadStories();
                } else {
                    alert(data.message || 'স্টোরি সেভ করতে সমস্যা হয়েছে।');
                }
            } catch (err) {
                alert('আপলোড ব্যর্থ হয়েছে: ' + err.message);
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        } else {
            // Text Story
            const content = document.getElementById('storyTextContent')?.value.trim();
            const bg = document.getElementById('storyTextBgPreset')?.value || 'linear-gradient(135deg, #1877f2, #00c6ff)';
            const font = document.getElementById('storyFontFamilySelect')?.value || 'Inter';

            if (!content) {
                alert('অনুগ্রহ করে স্টোরির জন্য কিছু লিখুন।');
                return;
            }

            try {
                const res = await fetch('/api/v2/stories', {
                    method: 'POST',
                    headers: {
                        ...this.getAuthHeaders(),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'text',
                        content: content,
                        background_color: bg,
                        font_family: font,
                        privacy: privacy,
                        music_track_id: this.storyMusicTrack ? this.storyMusicTrack.id : null,
                        interactive_sticker: sticker
                    })
                });

                const data = await res.json();
                if (data.success) {
                    showToast('টেক্সট স্টোরি প্রকাশিত হয়েছে! 🌟', '🌟');
                    this.closeCreateStoryModal();
                    this.loadStories();
                } else {
                    alert(data.message || 'স্টোরি পাবলিশ ব্যর্থ হয়েছে।');
                }
            } catch (err) {
                alert('ত্রুটি: ' + err.message);
            }
        }
    },

    /* =====================================================================
     * REELS SYSTEM: FEED, VIEWER, COMMENTS, SAVES, SHARES
     * ===================================================================== */
    async loadReels() {
        const container = document.getElementById('reelsCarousel');
        if (!container) return;

        try {
            const res = await fetch('/api/v2/reels/feed', { headers: this.getAuthHeaders() });
            const json = await res.json();
            this.reelsFeed = json.data || [];

            const currentUser = window.currentUser || {};
            const userInitial = (currentUser.name || 'ইউ').charAt(0);
            const userAvatar = currentUser.avatar_url || currentUser.profile?.avatar_url || '';

            let html = `
                <div class="fb-reel-card fb-create-reel-card" onclick="JugajugMediaSuite.openCreateReelModal()" title="নতুন রিল তৈরি করুন">
                    <div class="fb-create-reel-top">
                        ${userAvatar ? `<div class="fb-create-reel-bg-avatar" style="background-image: url('${userAvatar}');"></div>` : ''}
                        ${userAvatar ? `<img src="${userAvatar}" class="fb-create-reel-center-avatar" alt="Profile">` : `<div class="fb-create-reel-center-initial">${userInitial}</div>`}
                        <div class="fb-create-reel-badge-top">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            <span>ক্যামেরা</span>
                        </div>
                        <div class="fb-create-reel-plus-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                    </div>
                    <div class="fb-create-reel-bottom">
                        <div class="fb-create-reel-title">রিল তৈরি করুন</div>
                        <div class="fb-create-reel-sub">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="3"></rect><polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon></svg>
                            <span>শর্ট ভিডিও</span>
                        </div>
                    </div>
                </div>
            `;

            this.reelsFeed.forEach((reel, rIdx) => {
                const author = reel.user?.name || 'ক্রিয়েটর';
                const authorAvatar = reel.user?.avatar_url || '';
                const authorInitial = author.charAt(0);
                const isVerified = Boolean(reel.user?.is_verified);
                const thumb = reel.cover_image_url || reel.thumbnail_url || 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=400&q=80';
                const views = reel.views_count || 0;
                const likes = reel.likes_count || 0;
                const caption = reel.caption || '';
                const trackTitle = reel.music_track ? reel.music_track.title : (reel.audio_title || 'Original Audio');

                html += `
                    <div class="fb-reel-card" onclick="JugajugMediaSuite.openReelViewerByIndex(${rIdx})">
                        <img src="${thumb}" class="fb-reel-card-thumb" alt="${author}" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
                        <video class="fb-reel-card-thumb" src="${reel.video_url || ''}#t=0.5" preload="metadata" muted playsinline style="display: none;"></video>
                        <div class="fb-reel-top-badges">
                            <div class="fb-reel-badge-views">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                <span>${views}</span>
                            </div>
                            ${likes > 0 ? `
                                <div class="fb-reel-badge-likes">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    <span>${likes}</span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="fb-reel-hover-play">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><polygon points="7 4 19 12 7 20 7 4"></polygon></svg>
                        </div>
                        <div class="fb-reel-bottom-info">
                            <div class="fb-reel-author-row">
                                <div class="fb-reel-author-mini-avatar" style="${authorAvatar ? `background-image: url('${authorAvatar}');` : 'background: #3b82f6;'}">
                                    ${!authorAvatar ? `<span>${authorInitial}</span>` : ''}
                                </div>
                                <div class="fb-reel-author-name-text">
                                    <span>${author}</span>
                                    ${isVerified ? `<span class="fb-reel-verified-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="#3b82f6"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg></span>` : ''}
                                </div>
                            </div>
                            ${caption ? `<div class="fb-reel-bottom-caption">${caption}</div>` : ''}
                            <div class="fb-reel-music-tag">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                                <span class="fb-reel-music-tag-text">${trackTitle}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        } catch (e) {
            console.error('Reels feed loading failed:', e);
        }
    },

    openReelViewerByIndex(index) {
        if (!this.reelsFeed[index]) return;
        this.currentReelIndex = index;
        this.openReelViewer(this.reelsFeed[index].id);
    },

    openReelViewer(reelId) {
        const reel = this.reelsFeed.find(r => r.id === reelId) || this.reelsFeed[this.currentReelIndex];
        if (!reel) return;

        this.currentReelIndex = this.reelsFeed.findIndex(r => r.id === reel.id);

        const modal = document.getElementById('fbReelViewerModal');
        const video = document.getElementById('fbReelVideoEl');
        const authorName = document.getElementById('fbReelAuthorName');
        const caption = document.getElementById('fbReelCaptionText');
        const audio = document.getElementById('fbReelAudioText');
        const likesCount = document.getElementById('fbReelLikesCount');
        const commentsCount = document.getElementById('fbReelCommentsCount');
        const sharesCount = document.getElementById('fbReelSharesCount');
        const savesCount = document.getElementById('fbReelSavesCount');
        const likeBtn = document.getElementById('fbReelLikeBtn');
        const saveBtn = document.getElementById('fbReelSaveBtn');

        if (modal) modal.style.display = 'flex';
        if (video) {
            video.src = reel.video_url || '';
            video.muted = this.isMuted;
            video.currentTime = reel.trim_start || 0;
            video.play().catch(() => {});
        }
        if (authorName) {
            authorName.innerText = reel.user?.name || 'ক্রিয়েটর';
            authorName.style.cursor = 'pointer';
            authorName.onclick = () => {
                if (reel.user) {
                    window.location.href = window.getUserProfileUrl ? window.getUserProfileUrl(reel.user) : `/u/${encodeURIComponent(reel.user.username || reel.user.id)}`;
                }
            };
        }
        if (caption) caption.innerText = reel.caption || '';
        if (audio) {
            const trackTitle = reel.music_track ? `${reel.music_track.title} • ${reel.music_track.artist}` : (reel.audio_title || 'Original Audio');
            audio.innerText = trackTitle;
        }
        if (likesCount) likesCount.innerText = reel.likes_count || 0;
        if (commentsCount) commentsCount.innerText = reel.comments_count || 0;
        if (sharesCount) sharesCount.innerText = reel.shares_count || 0;
        if (savesCount) savesCount.innerText = reel.saves_count || 0;

        if (likeBtn) {
            likeBtn.innerHTML = reel.has_liked ? JugajugIcons.likeFilled : JugajugIcons.like;
            likeBtn.onclick = () => this.toggleReelLike(reel.id);
        }
        if (saveBtn) {
            saveBtn.innerHTML = reel.has_saved ? JugajugIcons.saveFilled : JugajugIcons.save;
            saveBtn.onclick = () => this.toggleReelSave(reel.id);
        }

        // More menu state reset & configuration
        const morePopup = document.getElementById('reelMoreMenuPopup');
        if (morePopup) morePopup.style.display = 'none';

        const currentUserId = window.currentUser?.id;
        const deleteBtn = document.getElementById('reelMoreDeleteBtn');
        if (deleteBtn) {
            deleteBtn.style.display = (currentUserId && reel.user_id === currentUserId) ? 'flex' : 'none';
        }

        const followText = document.getElementById('reelMoreFollowText');
        if (followText) {
            followText.innerText = reel.user?.is_following ? 'আনফলো করুন' : 'ফলো করুন';
        }

        this.recordReelView(reel.id);
        this.subscribeToReelChannel(reel.id);
    },

    nextReel() {
        if (this.currentReelIndex + 1 < this.reelsFeed.length) {
            this.currentReelIndex++;
            this.openReelViewerByIndex(this.currentReelIndex);
        } else {
            showToast('আর কোনো রিল নেই। নতুন কনটেন্ট তৈরি করুন!', 'ℹ');
        }
    },

    prevReel() {
        if (this.currentReelIndex > 0) {
            this.currentReelIndex--;
            this.openReelViewerByIndex(this.currentReelIndex);
        }
    },

    toggleReelPlayPause() {
        const video = document.getElementById('fbReelVideoEl');
        if (!video) return;
        if (video.paused) {
            video.play().catch(() => {});
        } else {
            video.pause();
        }
    },

    toggleReelMute() {
        this.isMuted = !this.isMuted;
        const video = document.getElementById('fbReelVideoEl');
        const muteBtn = document.getElementById('fbReelMuteBtn');
        if (video) video.muted = this.isMuted;
        if (muteBtn) muteBtn.innerHTML = this.isMuted ? JugajugIcons.mute : JugajugIcons.volume;
    },

    closeReelViewer() {
        this.unsubscribeFromReelChannel();
        const modal = document.getElementById('fbReelViewerModal');
        const video = document.getElementById('fbReelVideoEl');
        if (video) {
            video.pause();
            video.src = '';
        }
        this.closeReelCommentsDrawer();
        if (modal) modal.style.display = 'none';
    },

    async toggleReelLike(reelId) {
        if (!reelId) {
            const current = this.reelsFeed[this.currentReelIndex];
            if (current) reelId = current.id;
        }
        if (!reelId) return;

        try {
            const res = await fetch(`/api/v2/reels/${reelId}/react`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: 'like' })
            });
            if (res.status === 410) {
                this.handleExpiredItem('reel', reelId);
                return;
            }
            const data = await res.json();
            if (data.success) {
                const likeBtn = document.getElementById('fbReelLikeBtn');
                const likesCount = document.getElementById('fbReelLikesCount');
                if (likeBtn) likeBtn.innerHTML = data.data.reacted ? JugajugIcons.likeFilled : JugajugIcons.like;
                if (likesCount) likesCount.innerText = data.data.count;

                const cur = this.reelsFeed.find(r => r.id === reelId);
                if (cur) {
                    cur.has_liked = data.data.reacted;
                    cur.likes_count = data.data.count;
                }
            }
        } catch (e) {}
    },

    async toggleReelSave(reelId) {
        if (!reelId) {
            const current = this.reelsFeed[this.currentReelIndex];
            if (current) reelId = current.id;
        }
        if (!reelId) return;

        try {
            const res = await fetch(`/api/v2/reels/${reelId}/save`, {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            if (res.status === 410) {
                this.handleExpiredItem('reel', reelId);
                return;
            }
            const data = await res.json();
            if (data.success) {
                const saveBtn = document.getElementById('fbReelSaveBtn');
                const savesCount = document.getElementById('fbReelSavesCount');
                if (saveBtn) saveBtn.innerHTML = data.data.saved ? JugajugIcons.saveFilled : JugajugIcons.save;
                if (savesCount) savesCount.innerText = data.data.saves_count;
                showToast(data.message, data.data.saved ? '🔖' : '✓');

                const cur = this.reelsFeed.find(r => r.id === reelId);
                if (cur) {
                    cur.has_saved = data.data.saved;
                    cur.saves_count = data.data.saves_count;
                }
            }
        } catch (e) {}
    },

    toggleReelMoreMenu() {
        const popup = document.getElementById('reelMoreMenuPopup');
        if (!popup) return;
        const isShown = popup.style.display === 'flex' || popup.style.display === 'block';
        popup.style.display = isShown ? 'none' : 'flex';
    },

    copyReelLink() {
        const reel = this.reelsFeed[this.currentReelIndex];
        if (!reel) return;
        const url = `${window.location.origin}/reels/${reel.id}`;
        navigator.clipboard.writeText(url).then(() => {
            showToast('রিল লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে! 🔗', '🔗');
        });
        const popup = document.getElementById('reelMoreMenuPopup');
        if (popup) popup.style.display = 'none';
    },

    async toggleFollowCreator() {
        const reel = this.reelsFeed[this.currentReelIndex];
        if (!reel || !reel.user?.id) return;
        const creatorId = reel.user.id;
        const isFollowing = !!reel.user.is_following;

        try {
            const endpoint = `/api/v1/users/${creatorId}/follow`;
            const method = isFollowing ? 'DELETE' : 'POST';
            const res = await fetch(endpoint, {
                method,
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            if (data.success) {
                reel.user.is_following = !isFollowing;
                const followText = document.getElementById('reelMoreFollowText');
                if (followText) {
                    followText.innerText = reel.user.is_following ? 'আনফলো করুন' : 'ফলো করুন';
                }
                showToast(isFollowing ? 'আনফলো করা হয়েছে।' : 'ক্রিয়েটরকে ফলো করা হয়েছে! ✓', '✓');
            }
        } catch (e) {}

        const popup = document.getElementById('reelMoreMenuPopup');
        if (popup) popup.style.display = 'none';
    },

    hideCurrentReel() {
        showToast('এই রিলটি আপনার ফিড থেকে লুকানো হয়েছে।', '👁️');
        const popup = document.getElementById('reelMoreMenuPopup');
        if (popup) popup.style.display = 'none';
        this.nextReel();
    },

    async deleteCurrentReel() {
        const reel = this.reelsFeed[this.currentReelIndex];
        if (!reel) return;
        if (!confirm('আপনি কি নিশ্চিতভাবে এই রিলটি স্থায়ীভাবে ডিলিট করতে চান?')) return;

        try {
            const res = await fetch(`/api/v2/reels/${reel.id}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            if (data.success) {
                showToast('রিল সফলভাবে ডিলিট করা হয়েছে!', '🗑️');
                this.reelsFeed.splice(this.currentReelIndex, 1);
                if (this.reelsFeed.length === 0) {
                    this.closeReelViewer();
                    this.loadReels();
                } else {
                    this.currentReelIndex = Math.min(this.currentReelIndex, this.reelsFeed.length - 1);
                    this.openReelViewerByIndex(this.currentReelIndex);
                }
            } else {
                alert(data.message || 'ডিলিট করতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }

        const popup = document.getElementById('reelMoreMenuPopup');
        if (popup) popup.style.display = 'none';
    },

    async deleteCurrentStory() {
        const userGroup = this.storiesFeed[this.currentStoryUserIndex];
        const story = userGroup?.stories[this.currentStoryItemIndex];
        if (!story) return;
        if (!confirm('আপনি কি নিশ্চিতভাবে এই স্টোরিটি ডিলিট করতে চান?')) return;

        try {
            const res = await fetch(`/api/v2/stories/${story.id}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            if (data.success) {
                showToast('স্টোরি মুছে ফেলা হয়েছে!', '🗑️');
                userGroup.stories.splice(this.currentStoryItemIndex, 1);
                if (userGroup.stories.length === 0) {
                    this.storiesFeed.splice(this.currentStoryUserIndex, 1);
                    this.closeStoryViewer();
                } else {
                    this.currentStoryItemIndex = Math.min(this.currentStoryItemIndex, userGroup.stories.length - 1);
                    this.renderCurrentStoryItem();
                }
            } else {
                alert(data.message || 'স্টোরি ডিলিট করতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    },

    async messageReelCreator() {
        const reel = this.reelsFeed[this.currentReelIndex];
        if (!reel || !reel.user?.id) return;

        const currentUserId = window.currentUser?.id;
        if (currentUserId && reel.user.id === currentUserId) {
            showToast('এটি আপনার নিজের রিল!', 'ℹ');
            return;
        }

        try {
            showToast('মেসেঞ্জার কনভার্সন প্রস্তুত হচ্ছে...', '⏳');
            // 1. Get or create direct conversation
            const convRes = await fetch('/api/v1/conversations', {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'direct',
                    recipient_id: reel.user.id
                })
            });
            const convData = await convRes.json();
            if (!convData.success) {
                throw new Error(convData.message || 'কনভার্সন শুরু করা সম্ভব হয়নি।');
            }

            const conversation = convData.data;
            const convId = conversation.id;
            const convTitle = conversation.title || reel.user.name || 'চ্যাট';
            const shareUrl = `${window.location.origin}/reels/${reel.id}`;

            // 2. Send Reel attachment message
            await fetch(`/api/v1/conversations/${convId}/messages`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    body: `🎬 আমি আপনার এই রিলটি দেখেছি:\n${shareUrl}`
                })
            });

            // 3. Open Floating Realtime Chat
            if (typeof window.openRealChat === 'function') {
                window.openRealChat(convId, convTitle);
            }
            showToast('মেসেঞ্জারে চ্যাট খোলা হয়েছে! 💬', '💬');
        } catch (err) {
            alert('মেসেজ পাঠাতে সমস্যা হয়েছে: ' + err.message);
        }
    },

    /* =====================================================================
     * ENTERPRISE SHARE SYSTEM (FEED, STORY, MESSENGER, LINK, EXTERNAL)
     * ===================================================================== */
    shareTargetType: 'reel', // 'reel' or 'story'
    cachedShareConversations: [],

    openShareModal(context = 'reel') {
        this.shareTargetType = context;
        const modal = document.getElementById('reelShareModal');
        const titleEl = document.getElementById('reelShareModalTitle');
        if (titleEl) {
            titleEl.innerText = (context === 'story') ? 'স্টোরি শেয়ার করুন' : 'রিল শেয়ার করুন';
        }
        if (modal) modal.style.display = 'flex';
        this.loadShareConversations();
    },

    closeShareModal() {
        const modal = document.getElementById('reelShareModal');
        if (modal) modal.style.display = 'none';
    },

    async loadShareConversations() {
        const list = document.getElementById('shareMessengerList');
        if (!list) return;
        list.innerHTML = '<div style="text-align:center;color:#64748b;font-size:13px;padding:12px;">লোড হচ্ছে...</div>';

        try {
            const res = await fetch('/api/v1/conversations?per_page=20', {
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            const convs = Array.isArray(data.data) ? data.data : (data.data?.items || []);
            this.cachedShareConversations = convs;
            this.renderShareConversations(convs);
        } catch (e) {
            list.innerHTML = '<div style="text-align:center;color:#ef4444;font-size:13px;padding:12px;">কথোপকথন তালিকা পাওয়া যায়নি।</div>';
        }
    },

    renderShareConversations(conversations) {
        const list = document.getElementById('shareMessengerList');
        if (!list) return;

        if (!conversations || conversations.length === 0) {
            list.innerHTML = '<div style="text-align:center;color:#64748b;font-size:13px;padding:12px;">কোনো চ্যাট পাওয়া যায়নি।</div>';
            return;
        }

        const currentUserId = window.currentUser?.id;
        let html = '';
        conversations.forEach(c => {
            const other = c.participants?.find(p => p.id !== currentUserId);
            const title = c.title || other?.name || 'চ্যাট';
            const initial = title.charAt(0);
            const avatar = other?.profile?.avatar_url || other?.avatar_url;

            html += `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f8fafc;border-radius:10px;gap:8px;">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                        <div style="width:36px;height:36px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                            ${avatar ? `<img src="${avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : initial}
                        </div>
                        <div style="font-weight:700;font-size:13px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            ${title}
                        </div>
                    </div>
                    <button type="button" class="btn-fb-primary" style="padding:5px 12px;font-size:12px;border-radius:6px;flex-shrink:0;" onclick="JugajugMediaSuite.sendShareToConversation(${c.id})">
                        পাঠান
                    </button>
                </div>
            `;
        });
        list.innerHTML = html;
    },

    filterShareConversations(query) {
        query = (query || '').toLowerCase().trim();
        if (!query) {
            this.renderShareConversations(this.cachedShareConversations);
            return;
        }
        const filtered = this.cachedShareConversations.filter(c => {
            const title = (c.title || c.participants?.map(p => p.name).join(' ') || '').toLowerCase();
            return title.includes(query);
        });
        this.renderShareConversations(filtered);
    },

    getShareUrl() {
        if (this.shareTargetType === 'story') {
            const userGroup = this.storiesFeed[this.currentStoryUserIndex];
            const story = userGroup?.stories[this.currentStoryItemIndex];
            return story ? `${window.location.origin}/stories/${story.id}` : window.location.href;
        }
        const reel = this.reelsFeed[this.currentReelIndex];
        return reel ? `${window.location.origin}/reels/${reel.id}` : window.location.href;
    },

    async recordShareEvent() {
        if (this.shareTargetType === 'story') {
            const userGroup = this.storiesFeed[this.currentStoryUserIndex];
            const story = userGroup?.stories[this.currentStoryItemIndex];
            if (!story) return;
            try {
                await fetch(`/api/v2/stories/${story.id}/share`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
            } catch (e) {}
        } else {
            const reel = this.reelsFeed[this.currentReelIndex];
            if (!reel) return;
            try {
                const res = await fetch(`/api/v2/reels/${reel.id}/share`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success) {
                    const sharesCount = document.getElementById('fbReelSharesCount');
                    if (sharesCount) sharesCount.innerText = data.data.shares_count;
                    reel.shares_count = data.data.shares_count;
                }
            } catch (e) {}
        }
    },

    async shareToFeed() {
        const shareUrl = this.getShareUrl();
        const isStory = (this.shareTargetType === 'story');
        const captionPrefix = isStory ? '🌟 যুগাজুগ স্টোরি শেয়ার:' : '🎬 যুগাজুগ রিল শেয়ার:';

        try {
            await this.recordShareEvent();
            const res = await fetch('/api/v1/posts', {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content: `${captionPrefix}\n${shareUrl}`,
                    privacy: 'public'
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast('সফলভাবে আপনার টাইমলাইনে শেয়ার করা হয়েছে! 📢', '✓');
                this.closeShareModal();
            } else {
                alert(data.message || 'ফিডে শেয়ার করতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    },

    async shareToStory() {
        const shareUrl = this.getShareUrl();

        try {
            await this.recordShareEvent();
            const res = await fetch('/api/v2/stories', {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'text',
                    content: `🎬 নতুন রিল দেখুন:\n${shareUrl}`,
                    background_color: '#0f172a',
                    privacy: 'public'
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast('সফলভাবে আপনার স্টোরিতে শেয়ার করা হয়েছে! 🌟', '🌟');
                this.closeShareModal();
                this.loadStories();
            } else {
                alert(data.message || 'স্টোরিতে শেয়ার করতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    },

    async copyShareLink() {
        const shareUrl = this.getShareUrl();
        await this.recordShareEvent();
        navigator.clipboard.writeText(shareUrl).then(() => {
            showToast('লিঙ্ক সফলভাবে কপি করা হয়েছে! 🔗', '🔗');
            this.closeShareModal();
        });
    },

    async shareNativeExternal() {
        const shareUrl = this.getShareUrl();
        await this.recordShareEvent();
        if (navigator.share) {
            navigator.share({
                title: 'Jugajug',
                text: 'যুগাজুগ-এ এই কনটেন্টটি দেখুন!',
                url: shareUrl
            }).catch(() => {});
        } else {
            this.copyShareLink();
        }
        this.closeShareModal();
    },

    async sendShareToConversation(convId) {
        const shareUrl = this.getShareUrl();
        const prefix = (this.shareTargetType === 'story') ? '🌟 যুগাজুগ স্টোরি: ' : '🎬 যুগাজুগ রিল: ';

        try {
            await this.recordShareEvent();
            const res = await fetch(`/api/v1/conversations/${convId}/messages`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    body: `${prefix}${shareUrl}`
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast('মেসেঞ্জারে পাঠানো হয়েছে! ✉️', '✉️');
                this.closeShareModal();
            } else {
                alert(data.message || 'মেসেজ পাঠানো সম্ভব হয়নি।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    },

    /* =====================================================================
     * ENTERPRISE REPORT SYSTEM (REELS & STORIES WITH DUPLICATE PROTECTION)
     * ===================================================================== */
    reportTargetType: 'reel',

    openReportModal(context = 'reel') {
        this.reportTargetType = context;
        const modal = document.getElementById('reportContentModal');
        const titleEl = document.getElementById('reportModalTitle');
        if (titleEl) {
            titleEl.innerText = (context === 'story') ? 'স্টোরি রিপোর্ট করুন' : 'রিল রিপোর্ট করুন';
        }
        const defaultRadio = document.querySelector('input[name="reportReason"][value="spam"]');
        if (defaultRadio) defaultRadio.checked = true;
        const detailsInput = document.getElementById('reportDetailsInput');
        if (detailsInput) detailsInput.value = '';

        if (modal) modal.style.display = 'flex';

        // Close more popup if open
        const popup = document.getElementById('reelMoreMenuPopup');
        if (popup) popup.style.display = 'none';
    },

    closeReportModal() {
        const modal = document.getElementById('reportContentModal');
        if (modal) modal.style.display = 'none';
    },

    async submitReport() {
        const selectedRadio = document.querySelector('input[name="reportReason"]:checked');
        const reason = selectedRadio ? selectedRadio.value : 'other';
        const details = document.getElementById('reportDetailsInput')?.value.trim() || null;

        const isStory = (this.reportTargetType === 'story');
        let endpoint = '';
        if (isStory) {
            const userGroup = this.storiesFeed[this.currentStoryUserIndex];
            const story = userGroup?.stories[this.currentStoryItemIndex];
            if (!story) return;
            endpoint = `/api/v2/stories/${story.id}/report`;
        } else {
            const reel = this.reelsFeed[this.currentReelIndex];
            if (!reel) return;
            endpoint = `/api/v2/reels/${reel.id}/report`;
        }

        const submitBtn = document.getElementById('submitReportBtn');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason, details })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message || 'আপনার রিপোর্টটি সফলভাবে গ্রহণ করা হয়েছে। ধন্যবাদ।', '🚩');
                this.closeReportModal();
            } else {
                showToast(data.message || 'রিপোর্ট গ্রহণ করা যায়নি।', '⚠️');
                this.closeReportModal();
            }
        } catch (e) {
            alert('রিপোর্ট করতে সমস্যা হয়েছে: ' + e.message);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    },

    async recordReelView(reelId) {
        try {
            const res = await fetch(`/api/v2/reels/${reelId}/view`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ watch_time_seconds: 5.0 })
            });
            if (res.status === 410) {
                this.handleExpiredItem('reel', reelId);
            }
        } catch (e) {}
    },

    /* ---------------------------------------------------------------------
     * REEL COMMENTS DRAWER
     * --------------------------------------------------------------------- */
    openReelCommentsDrawer() {
        const reel = this.reelsFeed[this.currentReelIndex];
        if (!reel) return;

        const drawer = document.getElementById('reelCommentsDrawer');
        if (drawer) drawer.style.display = 'flex';
        this.loadReelComments(reel.id);
    },

    closeReelCommentsDrawer() {
        const drawer = document.getElementById('reelCommentsDrawer');
        if (drawer) drawer.style.display = 'none';
    },

    async loadReelComments(reelId) {
        const list = document.getElementById('reelCommentsListContainer');
        if (list) list.innerHTML = '<div style="text-align:center;padding:24px;color:#64748b;">মন্তব্য লোড হচ্ছে...</div>';

        try {
            const res = await fetch(`/api/v2/reels/${reelId}/comments`, { headers: this.getAuthHeaders() });
            if (res.status === 410) {
                this.closeReelCommentsDrawer();
                this.handleExpiredItem('reel', reelId);
                return;
            }
            const data = await res.json();
            const comments = data.data || [];

            const countTitle = document.getElementById('reelCommentsCountText');
            if (countTitle) countTitle.innerText = `${comments.length}টি মন্তব্য`;

            if (comments.length === 0) {
                if (list) list.innerHTML = '<div style="text-align:center;padding:30px;color:#64748b;">এখনো কোনো মন্তব্য নেই। প্রথম মন্তব্যটি আপনিই করুন! 💬</div>';
                return;
            }

            let html = '';
            comments.forEach(c => {
                const author = c.user?.name || 'ব্যবহারকারী';
                const avatar = c.user?.avatar_url;
                const isAuthor = (window.currentUser?.id && c.user?.id === window.currentUser.id);

                html += `
                    <div class="reel-comment-item" id="reelCommentRow_${c.id}">
                        <div class="reel-comment-avatar">
                            ${avatar ? `<img src="${avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : author.charAt(0)}
                        </div>
                        <div class="reel-comment-body">
                            <div class="reel-comment-author">${author}</div>
                            <div class="reel-comment-text">${c.comment}</div>
                            <div class="reel-comment-meta">
                                <span>${this.formatTimeAgo(c.created_at)}</span>
                                <button type="button" class="reel-comment-like-btn" onclick="JugajugMediaSuite.toggleReelCommentLike(${c.id})">
                                    ❤️ <span id="commentLikesCount_${c.id}">${c.likes_count || 0}</span>
                                </button>
                                ${isAuthor ? `<button type="button" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:11px;" onclick="JugajugMediaSuite.deleteReelComment(${c.id})">মুছে ফেলুন</button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            if (list) list.innerHTML = html;
        } catch (e) {
            if (list) list.innerHTML = '<div style="color:red;text-align:center;padding:20px;">মন্তব্য লোড করতে সমস্যা হয়েছে।</div>';
        }
    },

    async submitReelComment() {
        const reel = this.reelsFeed[this.currentReelIndex];
        const input = document.getElementById('reelCommentTextInput');
        const text = input?.value.trim();
        if (!reel || !text) return;

        try {
            const res = await fetch(`/api/v2/reels/${reel.id}/comments`, {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ comment: text })
            });
            if (res.status === 410) {
                this.closeReelCommentsDrawer();
                this.handleExpiredItem('reel', reel.id);
                return;
            }
            const data = await res.json();
            if (data.success) {
                input.value = '';
                this.loadReelComments(reel.id);
                const commentsCount = document.getElementById('fbReelCommentsCount');
                if (commentsCount) {
                    commentsCount.innerText = parseInt(commentsCount.innerText || 0) + 1;
                }
            } else {
                alert(data.message || 'মন্তব্য পাঠাতে সমস্যা হয়েছে।');
            }
        } catch (e) {
            alert('ত্রুটি: ' + e.message);
        }
    },

    async toggleReelCommentLike(commentId) {
        try {
            const res = await fetch(`/api/v2/reels/comments/${commentId}/like`, {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            if (data.success) {
                const countEl = document.getElementById(`commentLikesCount_${commentId}`);
                if (countEl) countEl.innerText = data.data.likes_count;
            }
        } catch (e) {}
    },

    async deleteReelComment(commentId) {
        if (!confirm('আপনি কি নিশ্চিতভাবে এই মন্তব্যটি মুছে ফেলতে চান?')) return;
        try {
            const res = await fetch(`/api/v2/reels/comments/${commentId}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById(`reelCommentRow_${commentId}`)?.remove();
            }
        } catch (e) {}
    },

    /* =====================================================================
     * PROFESSIONAL REEL CREATOR & STUDIO EDITOR
     * ===================================================================== */
    openCreateReelModal() {
        const modal = document.getElementById('createReelModalV2');
        if (modal) modal.style.display = 'flex';
        this.resetReelStudio();
    },

    closeCreateReelModal() {
        const modal = document.getElementById('createReelModalV2');
        if (modal) modal.style.display = 'none';
        this.stopCameraStream();
        if (this.currentUploader) {
            this.currentUploader.cancel();
            this.currentUploader = null;
        }
    },

    resetReelStudio() {
        this.reelStep = 'media';
        this.selectedReelFile = null;
        this.reelVideoUrl = '';
        this.reelTrimStart = 0;
        this.reelTrimEnd = 0;
        this.reelRotation = 0;
        this.reelCropAspect = '9:16';
        this.reelCoverDataUrl = null;
        this.reelCustomCoverFile = null;
        this.reelSelectedMusic = null;
        this.reelOriginalVolume = 1.0;
        this.reelMusicVolume = 0.75;
        this.reelMusicStartOffset = 0;

        const studioLayout = document.querySelector('.reel-studio-layout');
        if (studioLayout) studioLayout.classList.remove('has-media');

        const video = document.getElementById('reelStudioPreviewVideo');
        if (video) {
            video.src = '';
            video.style.transform = 'rotate(0deg)';
        }
        const fileInput = document.getElementById('reelVideoFileInput');
        if (fileInput) fileInput.value = '';
        const progressBox = document.getElementById('reelUploadProgressBox');
        if (progressBox) progressBox.style.display = 'none';

        this.setReelStep('media');
    },

    setReelStep(step) {
        this.reelStep = step;
        document.querySelectorAll('.reel-step-pill').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.step === step);
        });

        const activeBtn = document.querySelector(`.reel-step-pill[data-step="${step}"]`);
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        const panelMedia = document.getElementById('reelStudioStep_media');
        const panelEditor = document.getElementById('reelStudioStep_editor');
        const panelAudio = document.getElementById('reelStudioStep_audio');
        const panelDetails = document.getElementById('reelStudioStep_details');

        if (panelMedia) panelMedia.style.display = (step === 'media') ? 'block' : 'none';
        if (panelEditor) panelEditor.style.display = (step === 'editor') ? 'block' : 'none';
        if (panelAudio) panelAudio.style.display = (step === 'audio') ? 'block' : 'none';
        if (panelDetails) panelDetails.style.display = (step === 'details') ? 'block' : 'none';
    },

    handleReelFileSelect(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        this.loadReelVideoFile(file);
    },

    loadReelVideoFile(file) {
        if (!file.type.startsWith('video/')) {
            alert('অনুগ্রহ করে একটি সঠিক ভিডিও ফাইল নির্বাচন করুন (MP4, WebM, MOV)।');
            return;
        }

        this.selectedReelFile = file;
        this.reelVideoUrl = URL.createObjectURL(file);

        const studioLayout = document.querySelector('.reel-studio-layout');
        if (studioLayout) studioLayout.classList.add('has-media');

        const video = document.getElementById('reelStudioPreviewVideo');
        if (video) {
            video.src = this.reelVideoUrl;
            video.onloadedmetadata = () => {
                this.reelVideoDuration = video.duration || 15;
                this.reelTrimStart = 0;
                this.reelTrimEnd = this.reelVideoDuration;

                // Configure sliders
                const startSlider = document.getElementById('reelTrimStartSlider');
                const endSlider = document.getElementById('reelTrimEndSlider');
                const durationLabel = document.getElementById('reelTrimDurationLabel');

                if (startSlider) {
                    startSlider.max = this.reelVideoDuration;
                    startSlider.value = 0;
                }
                if (endSlider) {
                    endSlider.max = this.reelVideoDuration;
                    endSlider.value = this.reelVideoDuration;
                }
                if (durationLabel) {
                    durationLabel.innerText = `0:00 - ${this.formatSeconds(this.reelVideoDuration)} (মোট ${Math.round(this.reelVideoDuration)} সে.)`;
                }

                // Initial Cover Extraction
                this.captureCoverFrameFromVideo();
            };
        }

        this.setReelStep('editor');
    },

    /* Camera Recording in Browser */
    async startCameraCapture() {
        const cameraBox = document.getElementById('reelCameraViewBox');
        const cameraVideo = document.getElementById('reelCameraPreviewVideo');
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('আপনার ব্রাউজার ক্যামেরা রেকর্ডিং সমর্থন করে না। অনুগ্রহ করে ফাইল আপলোড করুন।');
            return;
        }

        try {
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'user' }, width: { ideal: 720 }, height: { ideal: 1280 } },
                    audio: true
                });
            } catch (strictErr) {
                try {
                    this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                } catch (pairErr) {
                    this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                }
            }

            if (cameraBox) cameraBox.style.display = 'block';
            if (cameraVideo) {
                cameraVideo.srcObject = this.cameraStream;
                cameraVideo.play();
            }

            const recordBtn = document.getElementById('reelCameraRecordBtn');
            if (recordBtn) recordBtn.style.display = 'inline-flex';
        } catch (e) {
            alert('ক্যামেরা বা মাইক্রোফোন ব্যবহারের অনুমতি পাওয়া যায়নি: ' + e.message);
        }
    },

    toggleCameraRecord() {
        if (!this.isRecordingCamera) {
            this.startRecordingClip();
        } else {
            this.stopRecordingClip();
        }
    },

    startRecordingClip() {
        this.recordedBlobs = [];
        const mimeTypes = ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4'];
        const mimeType = mimeTypes.find(type => MediaRecorder.isTypeSupported(type)) || 'video/webm';

        this.mediaRecorder = new MediaRecorder(this.cameraStream, { mimeType });
        this.mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                this.recordedBlobs.push(event.data);
            }
        };

        this.mediaRecorder.onstop = () => {
            const blob = new Blob(this.recordedBlobs, { type: mimeType });
            const file = new File([blob], `reel_camera_${Date.now()}.webm`, { type: mimeType });
            this.stopCameraStream();
            this.loadReelVideoFile(file);
        };

        this.mediaRecorder.start(1000);
        this.isRecordingCamera = true;
        this.cameraSeconds = 0;

        const recordBtn = document.getElementById('reelCameraRecordBtn');
        if (recordBtn) {
            recordBtn.style.background = '#dc2626';
            recordBtn.innerText = '⏹ রেকর্ডিং বন্ধ করুন (0s)';
        }

        const shutter = document.getElementById('reelCameraShutterInner');
        if (shutter) shutter.classList.add('recording');
        const timerText = document.getElementById('reelCameraSecondsText');
        if (timerText) timerText.innerText = 'রেকর্ডিং হচ্ছে (০:০০ / ১:৩০)';

        this.cameraTimer = setInterval(() => {
            this.cameraSeconds++;
            if (recordBtn) recordBtn.innerText = `⏹ রেকর্ডিং বন্ধ করুন (${this.cameraSeconds}s)`;
            if (timerText) timerText.innerText = `রেকর্ডিং হচ্ছে (${this.formatSeconds(this.cameraSeconds)} / ১:৩০)`;
            if (this.cameraSeconds >= 90) {
                this.stopRecordingClip();
            }
        }, 1000);
    },

    stopRecordingClip() {
        if (this.mediaRecorder && this.isRecordingCamera) {
            this.mediaRecorder.stop();
            this.isRecordingCamera = false;
            clearInterval(this.cameraTimer);
            const shutter = document.getElementById('reelCameraShutterInner');
            if (shutter) shutter.classList.remove('recording');
            const timerText = document.getElementById('reelCameraSecondsText');
            if (timerText) timerText.innerText = 'লাইভ ক্যামেরা (০:০০)';
        }
    },

    stopCameraStream() {
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach(track => track.stop());
            this.cameraStream = null;
        }
        const cameraBox = document.getElementById('reelCameraViewBox');
        if (cameraBox) cameraBox.style.display = 'none';
        clearInterval(this.cameraTimer);
        this.isRecordingCamera = false;
        const shutter = document.getElementById('reelCameraShutterInner');
        if (shutter) shutter.classList.remove('recording');
        const timerText = document.getElementById('reelCameraSecondsText');
        if (timerText) timerText.innerText = 'লাইভ ক্যামেরা (০:০০)';
    },

    /* Video Editor Controls */
    setReelCrop(aspect) {
        this.reelCropAspect = aspect;
        document.querySelectorAll('.reel-crop-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.aspect === aspect);
        });

        const video = document.getElementById('reelStudioPreviewVideo');
        if (video) {
            if (aspect === '9:16') {
                video.style.objectFit = 'cover';
                video.style.aspectRatio = '9 / 16';
            } else if (aspect === '1:1') {
                video.style.objectFit = 'cover';
                video.style.aspectRatio = '1 / 1';
            } else {
                video.style.objectFit = 'contain';
                video.style.aspectRatio = 'auto';
            }
        }
    },

    rotateReelVideo() {
        this.reelRotation = (this.reelRotation + 90) % 360;
        const video = document.getElementById('reelStudioPreviewVideo');
        if (video) {
            video.style.transform = `rotate(${this.reelRotation}deg)`;
        }
        const label = document.getElementById('reelRotationLabel');
        if (label) label.innerText = `${this.reelRotation}°`;
    },

    handleTrimSliderChange() {
        const startSlider = document.getElementById('reelTrimStartSlider');
        const endSlider = document.getElementById('reelTrimEndSlider');
        const video = document.getElementById('reelStudioPreviewVideo');
        const durationLabel = document.getElementById('reelTrimDurationLabel');

        let start = parseFloat(startSlider?.value || 0);
        let end = parseFloat(endSlider?.value || this.reelVideoDuration);

        if (start >= end) {
            start = Math.max(0, end - 1);
            if (startSlider) startSlider.value = start;
        }

        this.reelTrimStart = start;
        this.reelTrimEnd = end;

        if (video) {
            video.currentTime = start;
        }

        const totalSec = Math.round(end - start);
        if (durationLabel) {
            durationLabel.innerText = `${this.formatSeconds(start)} - ${this.formatSeconds(end)} (মোট ${totalSec} সে.)`;
        }
    },

    captureCoverFrameFromVideo() {
        const video = document.getElementById('reelStudioPreviewVideo');
        const canvas = document.getElementById('reelCoverFrameCanvas');
        if (!video || !canvas) return;

        canvas.width = 360;
        canvas.height = 640;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        this.reelCoverDataUrl = canvas.toDataURL('image/jpeg', 0.85);
        const coverThumb = document.getElementById('reelSelectedCoverThumb');
        if (coverThumb) {
            coverThumb.src = this.reelCoverDataUrl;
            coverThumb.style.display = 'block';
        }
    },

    handleCustomCoverUpload(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        this.reelCustomCoverFile = file;

        const coverThumb = document.getElementById('reelSelectedCoverThumb');
        if (coverThumb) {
            coverThumb.src = URL.createObjectURL(file);
            coverThumb.style.display = 'block';
        }
        showToast('কাস্টম কভার ইমেজ সফলভাবে নির্বাচন করা হয়েছে!', '🖼');
    },

    /* Audio Studio Controls */
    applyReelMusic(track) {
        this.reelSelectedMusic = track;
        const card = document.getElementById('reelStudioMusicCard');
        const titleEl = document.getElementById('reelStudioMusicTitle');
        const artistEl = document.getElementById('reelStudioMusicArtist');
        const thumbEl = document.getElementById('reelStudioMusicThumb');

        if (card) card.style.display = 'flex';
        if (titleEl) titleEl.innerText = track.title;
        if (artistEl) artistEl.innerText = `${track.artist} • ${track.genre || 'Music'}`;
        if (thumbEl && track.cover_image_url) thumbEl.src = track.cover_image_url;

        // Auto balance audio: lower video audio to 60%, music to 80%
        this.reelOriginalVolume = 0.6;
        this.reelMusicVolume = 0.8;
        const origSlider = document.getElementById('reelOriginalVolumeSlider');
        const musicSlider = document.getElementById('reelMusicVolumeSlider');
        if (origSlider) origSlider.value = 60;
        if (musicSlider) musicSlider.value = 80;
    },

    removeReelMusic() {
        this.reelSelectedMusic = null;
        const card = document.getElementById('reelStudioMusicCard');
        if (card) card.style.display = 'none';
    },

    appendHashtag(tag) {
        const input = document.getElementById('reelCaptionInput');
        if (!input) return;
        input.value = `${input.value} #${tag} `.trimStart();
        input.focus();
    },

    appendMention(name) {
        const input = document.getElementById('reelCaptionInput');
        if (!input) return;
        input.value = `${input.value} @${name} `.trimStart();
        input.focus();
    },

    /* =====================================================================
     * REEL PUBLISHING & RESUMABLE MULTI-PART UPLOAD
     * ===================================================================== */
    async submitReel(isDraft = false) {
        if (!this.selectedReelFile) {
            alert('অনুগ্রহ করে প্রথমে একটি রিল ভিডিও নির্বাচন বা রেকর্ড করুন।');
            this.setReelStep('media');
            return;
        }

        const progressBox = document.getElementById('reelUploadProgressBox');
        const progressBar = document.getElementById('reelProgressBarFill');
        const progressPercent = document.getElementById('reelProgressPercentText');
        const progressStatus = document.getElementById('reelProgressStatusText');
        const submitBtn = document.getElementById('reelSubmitBtn');
        const draftBtn = document.getElementById('reelDraftBtn');

        if (progressBox) progressBox.style.display = 'block';
        if (submitBtn) submitBtn.disabled = true;
        if (draftBtn) draftBtn.disabled = true;

        // 1. Resumable Chunked Upload of Video
        this.currentUploader = new ResumableChunkUploader(this.selectedReelFile, {
            collection: 'reel',
            onProgress: (percent, loadedBytes, totalBytes) => {
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressPercent) {
                    const loadedMb = (loadedBytes / 1024 / 1024).toFixed(1);
                    const totalMb = (totalBytes / 1024 / 1024).toFixed(1);
                    progressPercent.innerText = `${percent}% (${loadedMb} MB / ${totalMb} MB)`;
                }
            },
            onStatusChange: (status) => {
                if (progressStatus) progressStatus.innerText = status;
            },
            onComplete: async (media) => {
                try {
                    if (progressStatus) progressStatus.innerText = 'রিল প্রসেসিং ও পাবলিশিং কনফিগার হচ্ছে...';

                    // Optional Custom Cover Upload
                    let coverPath = null;
                    if (this.reelCustomCoverFile) {
                        try {
                            const coverForm = new FormData();
                            coverForm.append('file', this.reelCustomCoverFile);
                            coverForm.append('collection', 'reel_thumbnail');
                            const coverRes = await fetch('/api/v2/uploads/direct', {
                                method: 'POST',
                                headers: this.getAuthHeaders(),
                                body: coverForm
                            });
                            const coverData = await coverRes.json();
                            if (coverData.success) coverPath = coverData.data.file_path;
                        } catch (e) {}
                    }

                    const caption = document.getElementById('reelCaptionInput')?.value.trim() || '';
                    const privacy = document.getElementById('reelPrivacySelect')?.value || 'public';
                    const commentsEnabled = document.getElementById('reelCommentsEnabledCheck')?.checked ?? true;

                    const payload = {
                        media_id: media.id,
                        caption: caption,
                        privacy: privacy,
                        comments_enabled: commentsEnabled ? 1 : 0,
                        cover_image_path: coverPath,
                        trim_start: this.reelTrimStart,
                        trim_end: this.reelTrimEnd,
                        rotation_deg: this.reelRotation,
                        crop_aspect: this.reelCropAspect,
                        music_track_id: this.reelSelectedMusic ? this.reelSelectedMusic.id : null,
                        audio_volume: this.reelOriginalVolume,
                        music_volume: this.reelMusicVolume,
                        music_start_offset: this.reelMusicStartOffset,
                        is_draft: isDraft ? 1 : 0
                    };

                    const res = await fetch('/api/v2/reels', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            ...this.getAuthHeaders(),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();
                    if (data.success) {
                        showToast(isDraft ? 'রিলটি ড্রাফট হিসেবে সংরক্ষণ করা হয়েছে!' : 'রিল সফলভাবে প্রকাশিত হয়েছে!', '✓');
                        this.closeCreateReelModal();
                        this.loadReels();
                    } else {
                        alert(data.message || 'রিল সেভ করতে সমস্যা হয়েছে।');
                    }
                } catch (err) {
                    alert('ত্রুটি: ' + err.message);
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                    if (draftBtn) draftBtn.disabled = false;
                }
            },
            onError: (err) => {
                alert('রিল আপলোড ব্যর্থ হয়েছে: ' + err.message);
                if (submitBtn) submitBtn.disabled = false;
                if (draftBtn) draftBtn.disabled = false;
            }
        });

        this.currentUploader.start();
    },

    /* =====================================================================
     * UTILITY FUNCTIONS
     * ===================================================================== */
    formatTimeAgo(isoDate) {
        if (!isoDate) return '';
        const now = new Date();
        const past = new Date(isoDate);
        const diffSec = Math.floor((now - past) / 1000);

        if (diffSec < 60) return 'এইমাত্র';
        if (diffSec < 3600) return `${Math.floor(diffSec / 60)} মিনিট আগে`;
        if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} ঘণ্টা আগে`;
        return `${Math.floor(diffSec / 86400)} দিন আগে`;
    },

    formatSeconds(sec) {
        sec = Math.round(sec);
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}:${s < 10 ? '0' : ''}${s}`;
    }
};

// Global Initialization & Aliases
window.JugajugMediaSuite = JugajugMediaSuite;
window.BondhooMediaSuite = JugajugMediaSuite;
if (typeof JugajugMusicSuite !== 'undefined') {
    window.JugajugMusicSuite = JugajugMusicSuite;
    window.BondhooMusicSuite = JugajugMusicSuite;
}
document.addEventListener('DOMContentLoaded', () => {
    JugajugMediaSuite.init();
});
