<!-- ==============================================
     BONDHOO MESSENGER SOUND & RINGTONE SETTINGS MODAL
     ============================================== -->
<div id="messengerSoundSettingsModal" class="mss-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); z-index: 100000; align-items: center; justify-content: center; backdrop-filter: blur(5px); padding: 16px;" onclick="if(event.target === this) closeMessengerSoundModal();">
    <div class="mss-modal-container" style="background: white; border-radius: 18px; max-width: 540px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3); border: 1px solid #e2e8f0; overflow: hidden; animation: mssFadeIn 0.2s ease-out;">
        
        <!-- Modal Header -->
        <div style="padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(24, 119, 242, 0.12); color: #1877f2; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">মেসেঞ্জার সাউন্ড ও রিংটোন সেটিংস</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">কল, ইনকামিং ও আউটগোয়িং বার্তার জন্য আলাদা সাউন্ড কনফিগার করুন</p>
                </div>
            </div>
            <button type="button" onclick="closeMessengerSoundModal()" style="background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 6px; border-radius: 50%; line-height: 1;" title="বন্ধ করুন">✕</button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div style="padding: 20px 22px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 18px;">

            <!-- Global Master Sound Toggle -->
            <div style="background: #f1f5f9; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border: 1px solid #e2e8f0;">
                <div>
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <span>🔔 মেসেঞ্জার সাউন্ড সক্রিয় রাখুন</span>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">বন্ধ করলে সমস্ত রিংটোন ও মেসেজ টোন নীরব থাকবে</div>
                </div>
                <label class="mss-switch">
                    <input type="checkbox" id="mssGlobalToggle" onchange="handleMssGlobalToggle(this.checked)">
                    <span class="mss-slider"></span>
                </label>
            </div>

            <!-- CARD 1: Incoming Call Ringtone -->
            <div class="mss-card" id="mssCallCard" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; color: #059669;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </span>
                        <div>
                            <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">ইনকামিং কল রিংটোন</div>
                            <div style="font-size: 11px; color: #64748b;">কল আসার সময় নিরবচ্ছিন্নভাবে বাজবে</div>
                        </div>
                    </div>
                    <label class="mss-switch">
                        <input type="checkbox" id="mssCallToggle">
                        <span class="mss-slider"></span>
                    </label>
                </div>

                <div class="mss-card-content" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Ringtone Selector & Preview -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="mssCallSelect" style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; background: white; color: #0f172a; outline: none;">
                            <option value="bondhoo_ring">Bondhoo Ring (ডিফল্ট)</option>
                            <option value="digital_bell">Digital Bell (ডিজিটাল বেল)</option>
                            <option value="celesta_chime">Celesta Chime (সেলেনিয়াম মেলোডি)</option>
                        </select>
                        <button type="button" id="mssCallPreviewBtn" onclick="toggleMssPreview('incoming_call')" class="mss-preview-btn">
                            <span class="mss-btn-icon">▶</span>
                            <span>প্রিভিউ</span>
                        </button>
                    </div>

                    <!-- Volume Slider -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px;">
                        <span style="font-size: 12px; color: #64748b; min-width: 48px;">ভলিউম:</span>
                        <input type="range" id="mssCallVolume" min="0" max="100" value="80" style="flex: 1; accent-color: #1877f2;" oninput="updateMssVolumeDisplay('call', this.value)">
                        <span id="mssCallVolDisplay" style="font-size: 12px; font-weight: 700; color: #0f172a; min-width: 36px; text-align: right;">80%</span>
                    </div>

                    <!-- Reset to default link -->
                    <div style="text-align: right; margin-top: 2px;">
                        <button type="button" onclick="resetMssCategory('incoming_call')" style="background: none; border: none; font-size: 11.5px; color: #1877f2; cursor: pointer; text-decoration: underline; padding: 0;">ডিফল্ট রিংটোনে ফিরুন</button>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Incoming Message Tone -->
            <div class="mss-card" id="mssMsgCard" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; background: #eff6ff; color: #2563eb;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </span>
                        <div>
                            <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">ইনকামিং মেসেজ টোন</div>
                            <div style="font-size: 11px; color: #64748b;">নতুন বার্তা আসলে একবার হালকা শব্দ করবে</div>
                        </div>
                    </div>
                    <label class="mss-switch">
                        <input type="checkbox" id="mssMsgToggle">
                        <span class="mss-slider"></span>
                    </label>
                </div>

                <div class="mss-card-content" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Sound Selector & Preview -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="mssMsgSelect" style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; background: white; color: #0f172a; outline: none;">
                            <option value="bubble_pop">Bubble Pop (ডিফল্ট বাবল)</option>
                            <option value="clear_ding">Clear Ding (ক্রিস্টাল ডিং)</option>
                            <option value="soft_chime">Soft Chime (নরম সুর)</option>
                        </select>
                        <button type="button" id="mssMsgPreviewBtn" onclick="toggleMssPreview('incoming_message')" class="mss-preview-btn">
                            <span class="mss-btn-icon">▶</span>
                            <span>প্রিভিউ</span>
                        </button>
                    </div>

                    <!-- Volume Slider -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px;">
                        <span style="font-size: 12px; color: #64748b; min-width: 48px;">ভলিউম:</span>
                        <input type="range" id="mssMsgVolume" min="0" max="100" value="75" style="flex: 1; accent-color: #1877f2;" oninput="updateMssVolumeDisplay('msg', this.value)">
                        <span id="mssMsgVolDisplay" style="font-size: 12px; font-weight: 700; color: #0f172a; min-width: 36px; text-align: right;">75%</span>
                    </div>

                    <!-- Reset to default link -->
                    <div style="text-align: right; margin-top: 2px;">
                        <button type="button" onclick="resetMssCategory('incoming_message')" style="background: none; border: none; font-size: 11.5px; color: #1877f2; cursor: pointer; text-decoration: underline; padding: 0;">ডিফল্ট টোনে ফিরুন</button>
                    </div>
                </div>
            </div>

            <!-- CARD 3: Outgoing Message Sent Tone -->
            <div class="mss-card" id="mssSentCard" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: white;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; background: #fdf2f8; color: #db2777;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </span>
                        <div>
                            <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">মেসেজ পাঠানো নিশ্চিতকরণ (Sent Tone)</div>
                            <div style="font-size: 11px; color: #64748b;">সার্ভারে মেসেজ সফলভাবে পৌঁছালে হালকা নিশ্চিতকরণ শব্দ হবে</div>
                        </div>
                    </div>
                    <label class="mss-switch">
                        <input type="checkbox" id="mssSentToggle">
                        <span class="mss-slider"></span>
                    </label>
                </div>

                <div class="mss-card-content" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Sound Selector & Preview -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="mssSentSelect" style="flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; background: white; color: #0f172a; outline: none;">
                            <option value="subtle_sent">Subtle Sent (ডিফল্ট কনফার্মেশন)</option>
                            <option value="soft_swoosh">Soft Swoosh (সফট সোয়াশ)</option>
                            <option value="quick_click">Quick Click (কুইক ক্লিক)</option>
                        </select>
                        <button type="button" id="mssSentPreviewBtn" onclick="toggleMssPreview('outgoing_message')" class="mss-preview-btn">
                            <span class="mss-btn-icon">▶</span>
                            <span>প্রিভিউ</span>
                        </button>
                    </div>

                    <!-- Volume Slider -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px;">
                        <span style="font-size: 12px; color: #64748b; min-width: 48px;">ভলিউম:</span>
                        <input type="range" id="mssSentVolume" min="0" max="100" value="70" style="flex: 1; accent-color: #1877f2;" oninput="updateMssVolumeDisplay('sent', this.value)">
                        <span id="mssSentVolDisplay" style="font-size: 12px; font-weight: 700; color: #0f172a; min-width: 36px; text-align: right;">70%</span>
                    </div>

                    <!-- Reset to default link -->
                    <div style="text-align: right; margin-top: 2px;">
                        <button type="button" onclick="resetMssCategory('outgoing_message')" style="background: none; border: none; font-size: 11.5px; color: #1877f2; cursor: pointer; text-decoration: underline; padding: 0;">ডিফল্ট সেন্ট টোনে ফিরুন</button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div style="padding: 14px 22px; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
            <button type="button" onclick="resetAllMssSettings()" style="background: none; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #64748b; cursor: pointer;">
                সব ডিফল্ট করুন
            </button>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="closeMessengerSoundModal()" style="background: #e2e8f0; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    বাতিল
                </button>
                <button type="button" id="mssSaveBtn" onclick="saveMessengerSoundSettings()" style="background: #1877f2; border: none; padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; color: white; cursor: pointer; box-shadow: 0 2px 6px rgba(24, 119, 242, 0.3);">
                    সংরক্ষণ করুন
                </button>
            </div>
        </div>

    </div>
</div>

<style>
    @keyframes mssFadeIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }
    .mss-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .mss-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .mss-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .25s ease;
        border-radius: 24px;
    }
    .mss-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .25s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .mss-switch input:checked + .mss-slider {
        background-color: #1877f2;
    }
    .mss-switch input:checked + .mss-slider:before {
        transform: translateX(20px);
    }
    .mss-preview-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #1877f2;
        background: white;
        color: #1877f2;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .mss-preview-btn:hover {
        background: rgba(24, 119, 242, 0.08);
    }
    .mss-preview-btn.playing {
        background: #1877f2;
        color: white;
    }
</style>

<script>
    let mssActivePreviewType = null;

    function openMessengerSoundModal() {
        const modal = document.getElementById('messengerSoundSettingsModal');
        if (!modal) return;
        modal.style.display = 'flex';

        // Stop any ringing or previews
        if (window.bondhooSoundManager) {
            window.bondhooSoundManager.stopPreview();
        }

        populateMessengerSoundModal();
    }

    function closeMessengerSoundModal() {
        const modal = document.getElementById('messengerSoundSettingsModal');
        if (modal) modal.style.display = 'none';

        if (window.bondhooSoundManager) {
            window.bondhooSoundManager.stopPreview();
        }
        resetMssPreviewButtons();
    }

    function resetMssPreviewButtons() {
        ['incoming_call', 'incoming_message', 'outgoing_message'].forEach(type => {
            const btn = getMssPreviewBtn(type);
            if (btn) {
                btn.classList.remove('playing');
                btn.innerHTML = '<span class="mss-btn-icon">▶</span><span>প্রিভিউ</span>';
            }
        });
        mssActivePreviewType = null;
    }

    function getMssPreviewBtn(type) {
        if (type === 'incoming_call') return document.getElementById('mssCallPreviewBtn');
        if (type === 'incoming_message') return document.getElementById('mssMsgPreviewBtn');
        if (type === 'outgoing_message') return document.getElementById('mssSentPreviewBtn');
        return null;
    }

    function populateMessengerSoundModal() {
        if (!window.bondhooSoundManager) return;
        const s = window.bondhooSoundManager.settings;

        const globalEl = document.getElementById('mssGlobalToggle');
        if (globalEl) globalEl.checked = s.messenger_sounds_enabled !== false;

        // 1. Call
        const callToggle = document.getElementById('mssCallToggle');
        if (callToggle) callToggle.checked = s.incoming_call_sound_enabled !== false;
        const callSelect = document.getElementById('mssCallSelect');
        if (callSelect) callSelect.value = s.incoming_call_sound || 'bondhoo_ring';
        const callVol = document.getElementById('mssCallVolume');
        if (callVol) {
            callVol.value = s.incoming_call_volume ?? 80;
            updateMssVolumeDisplay('call', callVol.value);
        }

        // 2. Incoming Msg
        const msgToggle = document.getElementById('mssMsgToggle');
        if (msgToggle) msgToggle.checked = s.incoming_message_sound_enabled !== false;
        const msgSelect = document.getElementById('mssMsgSelect');
        if (msgSelect) msgSelect.value = s.incoming_message_sound || 'bubble_pop';
        const msgVol = document.getElementById('mssMsgVolume');
        if (msgVol) {
            msgVol.value = s.incoming_message_volume ?? 75;
            updateMssVolumeDisplay('msg', msgVol.value);
        }

        // 3. Outgoing Msg
        const sentToggle = document.getElementById('mssSentToggle');
        if (sentToggle) sentToggle.checked = s.outgoing_message_sound_enabled !== false;
        const sentSelect = document.getElementById('mssSentSelect');
        if (sentSelect) sentSelect.value = s.outgoing_message_sound || 'subtle_sent';
        const sentVol = document.getElementById('mssSentVolume');
        if (sentVol) {
            sentVol.value = s.outgoing_message_volume ?? 70;
            updateMssVolumeDisplay('sent', sentVol.value);
        }

        handleMssGlobalToggle(globalEl ? globalEl.checked : true, false);
    }

    function handleMssGlobalToggle(enabled, save = true) {
        const cards = ['mssCallCard', 'mssMsgCard', 'mssSentCard'];
        cards.forEach(cId => {
            const c = document.getElementById(cId);
            if (c) {
                c.style.opacity = enabled ? '1' : '0.55';
            }
        });
    }

    function updateMssVolumeDisplay(prefix, val) {
        const disp = document.getElementById(`mss${prefix.charAt(0).toUpperCase() + prefix.slice(1)}VolDisplay`);
        if (disp) disp.innerText = `${val}%`;
    }

    async function toggleMssPreview(type) {
        if (!window.bondhooSoundManager) return;
        const btn = getMssPreviewBtn(type);

        if (mssActivePreviewType === type) {
            window.bondhooSoundManager.stopPreview();
            resetMssPreviewButtons();
            return;
        }

        resetMssPreviewButtons();
        mssActivePreviewType = type;

        let soundId = 'bondhoo_ring';
        let volume = 80;
        if (type === 'incoming_call') {
            soundId = document.getElementById('mssCallSelect')?.value || 'bondhoo_ring';
            volume = Number(document.getElementById('mssCallVolume')?.value || 80);
        } else if (type === 'incoming_message') {
            soundId = document.getElementById('mssMsgSelect')?.value || 'bubble_pop';
            volume = Number(document.getElementById('mssMsgVolume')?.value || 75);
        } else {
            soundId = document.getElementById('mssSentSelect')?.value || 'subtle_sent';
            volume = Number(document.getElementById('mssSentVolume')?.value || 70);
        }

        if (btn) {
            btn.classList.add('playing');
            btn.innerHTML = '<span class="mss-btn-icon">⏹</span><span>থামান</span>';
        }

        const played = await window.bondhooSoundManager.previewSound(type, soundId, volume);
        if (!played) {
            resetMssPreviewButtons();
        } else {
            // Auto reset button after expected duration
            setTimeout(() => {
                if (mssActivePreviewType === type) {
                    resetMssPreviewButtons();
                }
            }, type === 'incoming_call' ? 3400 : 800);
        }
    }

    async function resetMssCategory(type) {
        if (!window.bondhooSoundManager) return;
        window.bondhooSoundManager.stopPreview();
        resetMssPreviewButtons();

        await window.bondhooSoundManager.resetSettings(type);
        populateMessengerSoundModal();
        if (typeof showToast === 'function') {
            showToast('ডিফল্ট সাউন্ড সফলভাবে পুনরুদ্ধার করা হয়েছে 🎵');
        }
    }

    async function resetAllMssSettings() {
        if (!window.bondhooSoundManager) return;
        window.bondhooSoundManager.stopPreview();
        resetMssPreviewButtons();

        await window.bondhooSoundManager.resetSettings(null);
        populateMessengerSoundModal();
        if (typeof showToast === 'function') {
            showToast('সমস্ত সাউন্ড সেটিংস ফ্যাক্টরি ডিফল্টে রিসেট করা হয়েছে 🎵');
        }
    }

    async function saveMessengerSoundSettings() {
        if (!window.bondhooSoundManager) return;
        window.bondhooSoundManager.stopPreview();
        resetMssPreviewButtons();

        const btn = document.getElementById('mssSaveBtn');
        if (btn) btn.innerText = 'সংরক্ষণ হচ্ছে...';

        const payload = {
            messenger_sounds_enabled: document.getElementById('mssGlobalToggle')?.checked ?? true,
            incoming_call_sound_enabled: document.getElementById('mssCallToggle')?.checked ?? true,
            incoming_call_sound: document.getElementById('mssCallSelect')?.value || 'bondhoo_ring',
            incoming_call_volume: Number(document.getElementById('mssCallVolume')?.value ?? 80),
            incoming_message_sound_enabled: document.getElementById('mssMsgToggle')?.checked ?? true,
            incoming_message_sound: document.getElementById('mssMsgSelect')?.value || 'bubble_pop',
            incoming_message_volume: Number(document.getElementById('mssMsgVolume')?.value ?? 75),
            outgoing_message_sound_enabled: document.getElementById('mssSentToggle')?.checked ?? true,
            outgoing_message_sound: document.getElementById('mssSentSelect')?.value || 'subtle_sent',
            outgoing_message_volume: Number(document.getElementById('mssSentVolume')?.value ?? 70)
        };

        const res = await window.bondhooSoundManager.saveSettings(payload);
        if (btn) btn.innerText = 'সংরক্ষণ করুন';

        closeMessengerSoundModal();
        if (typeof showToast === 'function') {
            showToast('মেসেঞ্জার সাউন্ড সেটিংস সফলভাবে সংরক্ষিত হয়েছে! 🔔');
        } else {
            alert('মেসেঞ্জার সাউন্ড সেটিংস সফলভাবে সংরক্ষিত হয়েছে!');
        }
    }
</script>
