{{-- Global realtime listener: incoming audio/video calls + new message alerts (works on every page) --}}
@php
    $jjSyncCursor = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('messenger_sync_events')) {
            $jjSyncCursor = (int) \App\Models\MessengerSyncEvent::max('id');
        }
    } catch (\Throwable $e) {}
    $jjAuthUser = auth('web')->user();
    $jjAuthUserId = $jjAuthUser?->id;
    $jjServerToken = $jjAuthUser ? ($jjAuthUser->createToken('realtime_listener')->plainTextToken ?? '') : '';
@endphp
<div id="jjIncomingCall" class="jj-incoming-call" role="alertdialog" aria-live="assertive" aria-labelledby="jjIncomingCallName" hidden>
    <div class="jj-incoming-call__pulse">
        <img id="jjIncomingCallAvatar" src="/images/default-avatar.svg" alt="" onerror="this.onerror=null; this.src='/images/default-avatar.svg';">
    </div>
    <div class="jj-incoming-call__info">
        <div id="jjIncomingCallName" class="jj-incoming-call__name">ইনকামিং কল</div>
        <div id="jjIncomingCallType" class="jj-incoming-call__type">অডিও কল আসছে...</div>
    </div>
    <div class="jj-incoming-call__actions">
        <button type="button" id="jjIncomingCallDecline" class="jj-incoming-call__btn jj-incoming-call__btn--decline" title="প্রত্যাখ্যান করুন">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-3.33-2.67m-2.67-3.34a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
        </button>
        <button type="button" id="jjIncomingCallAccept" class="jj-incoming-call__btn jj-incoming-call__btn--accept" title="গ্রহণ করুন">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </button>
    </div>
</div>
<div id="jjMessageAlert" class="jj-message-alert" hidden></div>

<style>
    .jj-incoming-call {
        position: fixed; top: 20px; right: 20px; z-index: 100000;
        display: flex; align-items: center; gap: 14px;
        width: min(360px, calc(100vw - 32px)); padding: 16px 18px;
        background: rgba(15, 23, 42, 0.92); color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 20px;
        box-shadow: 0 20px 50px rgba(2, 6, 23, 0.45);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        font-family: inherit; animation: jjCallSlideIn 0.35s cubic-bezier(.2,.9,.3,1.2);
    }
    .jj-incoming-call[hidden], .jj-message-alert[hidden] { display: none !important; }
    .jj-incoming-call__pulse { position: relative; flex-shrink: 0; width: 52px; height: 52px; border-radius: 50%; }
    .jj-incoming-call__pulse::before, .jj-incoming-call__pulse::after {
        content: ''; position: absolute; inset: 0; border-radius: 50%;
        border: 2px solid rgba(34, 197, 94, 0.6); animation: jjCallRing 1.6s ease-out infinite;
    }
    .jj-incoming-call__pulse::after { animation-delay: 0.8s; }
    .jj-incoming-call__pulse img { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; position: relative; z-index: 1; background: #334155; }
    .jj-incoming-call__info { flex: 1; min-width: 0; }
    .jj-incoming-call__name { font-weight: 800; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .jj-incoming-call__type { font-size: 12.5px; color: #86efac; margin-top: 2px; }
    .jj-incoming-call__actions { display: flex; gap: 10px; }
    .jj-incoming-call__btn {
        width: 44px; height: 44px; border-radius: 50%; border: none; color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .jj-incoming-call__btn:hover { transform: scale(1.08); }
    .jj-incoming-call__btn:disabled { opacity: 0.6; cursor: wait; }
    .jj-incoming-call__btn--decline { background: #ef4444; box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45); }
    .jj-incoming-call__btn--accept { background: #22c55e; box-shadow: 0 6px 18px rgba(34, 197, 94, 0.45); animation: jjCallShake 1.2s ease-in-out infinite; }
    .jj-message-alert {
        position: fixed; bottom: 24px; left: 24px; z-index: 99999; max-width: min(340px, calc(100vw - 48px));
        display: flex; align-items: center; gap: 10px; padding: 12px 14px; cursor: pointer;
        background: #fff; color: #0f172a; border-radius: 16px; border: 1px solid #e2e8f0;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.18); animation: jjCallSlideIn 0.3s ease;
    }
    .jj-message-alert img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .jj-message-alert strong { display: block; font-size: 13.5px; }
    .jj-message-alert span { display: block; font-size: 12.5px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 240px; }
    @keyframes jjCallSlideIn { from { opacity: 0; transform: translateY(-14px) scale(0.97); } to { opacity: 1; transform: none; } }
    @keyframes jjCallRing { from { transform: scale(1); opacity: 1; } to { transform: scale(1.7); opacity: 0; } }
    @keyframes jjCallShake { 0%, 100% { transform: rotate(0); } 10%, 30% { transform: rotate(-12deg); } 20%, 40% { transform: rotate(12deg); } 50% { transform: rotate(0); } }
</style>

<script>
    (function () {
        if (window.__jjGlobalRealtimeListener) return;
        window.__jjGlobalRealtimeListener = true;

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const serverToken = @json($jjServerToken);
        let cursor = {{ $jjSyncCursor }};
        let myUserId = {{ $jjAuthUserId ? (int) $jjAuthUserId : 'null' }};
        let ringingCall = null;
        let ringCtx = null;
        let ringTimer = null;
        let inFlight = false;
        let failures = 0;
        let alertTimer = null;
        const isCallRoom = window.location.pathname.startsWith('/call/');
        const isMessengerPage = window.location.pathname.startsWith('/messages');

        if (!myUserId) {
            try { myUserId = JSON.parse(localStorage.getItem('bondhoo_user') || localStorage.getItem('jugajug_user') || 'null')?.id || null; } catch (e) {}
        }

        if (serverToken && !localStorage.getItem('bondhoo_token')) {
            try { localStorage.setItem('bondhoo_token', serverToken); } catch (e) {}
        }

        function apiFetch(url, options = {}) {
            const token = serverToken || localStorage.getItem('bondhoo_token') || localStorage.getItem('jugajug_token') || '';
            const headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            }, token ? { 'Authorization': `Bearer ${token}` } : {});
            return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers }));
        }

        function escapeText(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        // Synthesized ringtone (Web Audio API) — no external audio files required
        function startRingtone() {
            stopRingtone();
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                ringCtx = new AudioCtx();
                const burst = () => {
                    if (!ringCtx) return;
                    [0, 0.45].forEach(offset => {
                        const osc = ringCtx.createOscillator();
                        const gain = ringCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(offset ? 659 : 523, ringCtx.currentTime + offset);
                        gain.gain.setValueAtTime(0.0001, ringCtx.currentTime + offset);
                        gain.gain.exponentialRampToValueAtTime(0.15, ringCtx.currentTime + offset + 0.03);
                        gain.gain.exponentialRampToValueAtTime(0.0001, ringCtx.currentTime + offset + 0.4);
                        osc.connect(gain).connect(ringCtx.destination);
                        osc.start(ringCtx.currentTime + offset);
                        osc.stop(ringCtx.currentTime + offset + 0.42);
                    });
                    ringTimer = setTimeout(burst, 2000);
                };
                burst();
            } catch (e) {}
            if (navigator.vibrate) navigator.vibrate([400, 200, 400]);
        }

        function stopRingtone() {
            clearTimeout(ringTimer);
            if (ringCtx) {
                try { ringCtx.close(); } catch (e) {}
                ringCtx = null;
            }
        }

        function hideIncoming() {
            stopRingtone();
            ringingCall = null;
            const box = document.getElementById('jjIncomingCall');
            if (box) {
                box.hidden = true;
                box.style.display = 'none';
            }
        }

        function showIncoming(payload) {
            if (!payload || !payload.call_id) return;
            if (ringingCall && Number(ringingCall.call_id) === Number(payload.call_id)) return;
            ringingCall = payload;
            const isVideo = payload.call_type === 'video' || payload.call_type === 'group_video';
            const box = document.getElementById('jjIncomingCall');
            if (!box) return;
            document.getElementById('jjIncomingCallName').textContent = payload.caller?.name || 'Bondhoo ব্যবহারকারী';
            document.getElementById('jjIncomingCallType').textContent = isVideo ? '📹 ভিডিও কল আসছে...' : '📞 অডিও কল আসছে...';
            document.getElementById('jjIncomingCallAvatar').src = payload.caller?.avatar_url || '/images/default-avatar.svg';
            document.getElementById('jjIncomingCallAccept').disabled = false;
            document.getElementById('jjIncomingCallDecline').disabled = false;
            box.hidden = false;
            box.style.display = 'flex';
            startRingtone();

            if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
                try { new Notification(`${payload.caller?.name || 'কেউ'} আপনাকে কল করছেন`, { body: isVideo ? 'ভিডিও কল' : 'অডিও কল', tag: `call-${payload.call_id}` }); } catch (e) {}
            }
        }

        window.showIncomingCall = showIncoming;
        window.hideIncomingCall = hideIncoming;

        async function acceptCall() {
            if (!ringingCall) return;
            const call = ringingCall;
            const type = (call.call_type === 'video' || call.call_type === 'group_video') ? 'video' : 'audio';
            hideIncoming();
            window.location.href = `/call/${call.conversation_id}?type=${type}&answer=1&call_id=${call.call_id}`;
        }

        async function declineCall() {
            if (!ringingCall) return;
            const call = ringingCall;
            document.getElementById('jjIncomingCallDecline').disabled = true;
            hideIncoming();
            try {
                await apiFetch(`/api/v1/calls/${call.call_id}/respond`, { method: 'POST', body: JSON.stringify({ action: 'reject' }) });
            } catch (e) {}
        }

        function playMessagePing() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.25);
                osc.connect(gain).connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.26);
                setTimeout(() => ctx.close(), 400);
            } catch (e) {}
        }

        function showMessageAlert(message) {
            const box = document.getElementById('jjMessageAlert');
            if (!box) return;
            const sender = message.sender || {};
            const preview = message.body || (message.type === 'voice' ? '🎙️ ভয়েস বার্তা' : '📎 মিডিয়া পাঠিয়েছেন');
            box.innerHTML = `<img src="${escapeText(sender.avatar_url || '/images/default-avatar.svg')}" alt="" onerror="this.onerror=null; this.src='/images/default-avatar.svg';"><div><strong>${escapeText(sender.name || 'নতুন মেসেজ')}</strong><span>${escapeText(preview)}</span></div>`;
            box.onclick = () => { window.location.href = `/messages/${message.conversation_id}`; };
            box.hidden = false;
            clearTimeout(alertTimer);
            alertTimer = setTimeout(() => { box.hidden = true; }, 6000);
        }

        function handleNewMessage(message) {
            if (!message || !message.conversation_id) return;
            if (myUserId && Number(message.sender?.id) === Number(myUserId)) return;
            if (isMessengerPage || isCallRoom) return;

            // Pages with the floating chat popup (dashboard / profile): open or refresh it in place
            if (typeof window.openRealChat === 'function' && document.getElementById('messengerChatBox')) {
                let activeId = null;
                try { activeId = typeof activeChatConversationId !== 'undefined' ? activeChatConversationId : null; } catch (e) {}
                const box = document.getElementById('messengerChatBox');
                const isOpen = box && box.style.display !== 'none' && !box.classList.contains('hidden');

                playMessagePing();
                if (isOpen && Number(activeId) === Number(message.conversation_id)) {
                    if (typeof window.loadMessagesSilent === 'function') window.loadMessagesSilent(message.conversation_id);
                    return;
                }
                if (!isOpen) {
                    const s = message.sender || {};
                    window.openRealChat(message.conversation_id, s.name || 'চ্যাট', s.avatar_url || '', s.id || null, s.username || null);
                    return;
                }
                showMessageAlert(message);
                return;
            }

            playMessagePing();
            showMessageAlert(message);
        }

        function handleEvent(evt) {
            const p = evt.payload || {};

            if (evt.event_type === 'call.incoming') {
                if (isCallRoom || ringingCall) return;
                if (myUserId && Number(p.caller?.id) === Number(myUserId)) return;
                // Avoid stale invitations (more than 3 minutes old, accounting for clock skew)
                if (evt.timestamp) {
                    const age = Math.abs(Date.now() - new Date(evt.timestamp).getTime());
                    if (age > 180000) return;
                }
                showIncoming(p);
            } else if ((evt.event_type === 'call.ended' || evt.event_type === 'call.rejected') && ringingCall && Number(p.call_id) === Number(ringingCall.call_id)) {
                hideIncoming();
            } else if (evt.event_type === 'call.accepted' && ringingCall && Number(p.call_id) === Number(ringingCall.call_id) && Number(p.user_id) === Number(myUserId)) {
                hideIncoming(); // answered on another device/tab
            } else if (evt.event_type === 'message.created') {
                handleNewMessage(p);
                window.dispatchEvent(new CustomEvent('jugajug:message-created', { detail: { event: evt, payload: p } }));
            } else if (evt.event_type === 'message.updated') {
                handleMessageUpdated(p, evt);
                window.dispatchEvent(new CustomEvent('jugajug:message-updated', { detail: { event: evt, payload: p } }));
            } else if (evt.event_type === 'message.deleted' || evt.event_type === 'message.deleted_for_me') {
                handleMessageDeleted(p, evt);
                window.dispatchEvent(new CustomEvent('jugajug:message-deleted', { detail: { event: evt, payload: p } }));
            } else if (evt.event_type === 'conversation.cleared') {
                handleConversationCleared(p, evt);
                window.dispatchEvent(new CustomEvent('jugajug:conversation-cleared', { detail: { event: evt, payload: p } }));
            } else if (evt.event_type === 'presence.online' || evt.event_type === 'presence.offline') {
                handlePresenceChange(p, evt);
                window.dispatchEvent(new CustomEvent('jugajug:presence', { detail: { event: evt, payload: p } }));
            }
        }

        function handleMessageUpdated(p, evt) {
            const convId = evt.conversation_id || p.conversation_id;
            if (typeof activeChatConversationId !== 'undefined' && Number(activeChatConversationId) === Number(convId)) {
                if (typeof window.loadMessagesSilent === 'function') {
                    window.loadMessagesSilent(convId);
                }
            }
            if (typeof window.fetchConversations === 'function') {
                window.fetchConversations();
            }
        }

        function handleMessageDeleted(p, evt) {
            const convId = evt.conversation_id || p.conversation_id;
            if (typeof activeChatConversationId !== 'undefined' && Number(activeChatConversationId) === Number(convId)) {
                if (typeof window.loadMessagesSilent === 'function') {
                    window.loadMessagesSilent(convId);
                }
            }
            if (typeof window.fetchConversations === 'function') {
                window.fetchConversations();
            }
        }

        function handleConversationCleared(p, evt) {
            const convId = evt.conversation_id || p.conversation_id;
            if (typeof activeChatConversationId !== 'undefined' && Number(activeChatConversationId) === Number(convId)) {
                const container = document.getElementById('messengerChatMessages');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; color: var(--fb-text-secondary); font-size: 13px; padding: 24px;">
                            <div style="font-size: 28px; margin-bottom: 6px;">👋</div>
                            <div style="font-weight: 700;">কথোপকথন শুরু করুন!</div>
                            <div style="font-size: 11px; margin-top: 2px;">চ্যাট হিস্ট্রি মুছে ফেলা হয়েছে</div>
                        </div>`;
                }
            }
            if (typeof window.fetchConversations === 'function') {
                window.fetchConversations();
            }
        }

        function handlePresenceChange(p, evt) {
            const targetUserId = Number(p.user_id || evt.user_id);
            const isOnline = evt.event_type === 'presence.online';
            if (window.jugajugOnlineUsers) {
                if (isOnline) {
                    window.jugajugOnlineUsers.add(targetUserId);
                } else {
                    window.jugajugOnlineUsers.delete(targetUserId);
                }
            }
            if (typeof window.updateContactPresenceUI === 'function') {
                window.updateContactPresenceUI(targetUserId, isOnline);
            }
        }

        async function poll() {
            if (inFlight) return;
            inFlight = true;
            try {
                const res = await apiFetch(`/api/v1/messenger/sync?since_id=${cursor}&limit=200`);
                if (res.status === 401 || res.status === 419) {
                    failures++;
                    return;
                }
                const data = await res.json();
                if (data.success && data.data) {
                    failures = 0;
                    cursor = data.data.latest_event_id || cursor;
                    (data.data.events || []).forEach(handleEvent);
                }
            } catch (e) {
                failures++;
            } finally {
                inFlight = false;
                // Back off when unauthenticated/offline, otherwise poll quickly for realtime feel
                setTimeout(poll, failures > 0 ? Math.min(30000, 2000 * failures) : 2000);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('jjIncomingCallAccept')?.addEventListener('click', acceptCall);
            document.getElementById('jjIncomingCallDecline')?.addEventListener('click', declineCall);
            if ('Notification' in window && Notification.permission === 'default') {
                document.addEventListener('click', () => { try { Notification.requestPermission(); } catch (e) {} }, { once: true });
            }

            if (window.Echo && myUserId) {
                window.Echo.private(`user.${myUserId}`)
                    .listen('.call.incoming', (e) => {
                        const p = e.payload || e;
                        if (Number(p.caller?.id) !== Number(myUserId)) {
                            showIncoming(p);
                        }
                    })
                    .listen('.call.ended', () => hideIncoming())
                    .listen('.call.rejected', () => hideIncoming());
            }
        });

        if (!isCallRoom) {
            setTimeout(poll, 1200);
        }
    })();
</script>
