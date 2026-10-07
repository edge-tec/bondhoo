<!-- Jugajug Enterprise Post Composer Component -->
<div class="jj-composer-overlay" id="jjComposerOverlay" role="dialog" aria-modal="true" aria-labelledby="jjComposerTitle">
    <div class="jj-composer-card" id="jjComposerCard">
        
        <!-- Header -->
        <div class="jj-composer-header">
            <div class="jj-composer-header-title">
                <span class="jj-composer-title-text" id="jjComposerTitle">নতুন পোস্ট তৈরি করুন</span>
                <span class="jj-composer-draft-indicator" id="jjDraftStatusBadge" style="display: none;">
                    <span id="jjDraftStatusText">✓ সংরক্ষিত</span>
                </span>
            </div>
            <div class="jj-composer-header-actions">
                <button type="button" class="jj-composer-icon-btn" id="jjDraftsMenuBtn" title="সংরক্ষিত খসড়াগুলো দেখুন" onclick="window.EnterprisePostComposer.toggleDraftsList()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </button>
                <button type="button" class="jj-composer-icon-btn" id="jjComposerCloseBtn" title="বন্ধ করুন" onclick="window.EnterprisePostComposer.close()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        <!-- Scrollable Composer Body -->
        <div class="jj-composer-body" id="jjComposerBody">
            
            <!-- Author & Audience Row -->
            <div class="jj-composer-author-row">
                <img src="/images/default-avatar.svg" alt="ব্যবহারকারী" class="jj-composer-avatar" id="jjAuthorAvatar">
                <div class="jj-composer-author-meta">
                    <div class="jj-composer-author-name" id="jjAuthorNameWrap">
                        <span id="jjAuthorName">আপনি</span>
                        <span id="jjGroupTargetLabel" style="display: none; color: var(--jj-primary); font-size: 13px;">▶ <b id="jjGroupNameText">গ্রুপ</b></span>
                    </div>
                    <div class="jj-composer-pill-row">
                        <!-- Privacy / Audience Selector -->
                        <div class="jj-composer-pill" id="jjAudiencePill" onclick="window.EnterprisePostComposer.toggleAudienceMenu()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="jjAudienceIcon"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            <span id="jjAudienceText">পাবলিক</span>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>

                        <!-- Group Selector Pill (if any) -->
                        <div class="jj-composer-pill" id="jjGroupPill" onclick="window.EnterprisePostComposer.openDrawer('group')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span id="jjGroupPillText">পোস্ট গন্তব্য: প্রোফাইল</span>
                        </div>

                        <!-- Schedule Badge (if scheduled) -->
                        <div class="jj-composer-pill" id="jjScheduleBadge" style="display: none; background: #fef3c7; color: #b45309;" onclick="window.EnterprisePostComposer.openDrawer('schedule')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span id="jjScheduleBadgeText">সিডিউল করা</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audience Dropdown Menu -->
            <div class="jj-suggest-popup" id="jjAudienceMenu">
                <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.setAudience('public')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span>🌍 পাবলিক (সবার জন্য দৃশ্যমান)</span>
                </div>
                <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.setAudience('friends')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                    <span>👥 শুধু বন্ধুরা</span>
                </div>
                <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.setAudience('followers')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>🌟 ফলোয়াররা</span>
                </div>
                <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.setAudience('only_me')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span>🔒 শুধুমাত্র আমি (গোপন)</span>
                </div>
            </div>

            <!-- Content Warning Box (if toggled) -->
            <div id="jjContentWarningWrap" style="display: none; background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--jj-radius-sm); padding: 8px 12px;">
                <div style="font-size: 11px; font-weight: 700; color: #e11d48; margin-bottom: 4px;">⚠️ কনটেন্ট সতর্কতা / সেনসিটিভ টপিক লেবেল</div>
                <input type="text" id="jjContentWarningInput" placeholder="সতর্কবার্তা লিখুন (যেমন: স্পয়লার, সংবেদনশীল দৃশ্য...)" style="width: 100%; border: none; background: transparent; font-size: 13px; outline: none; color: #9f1239;">
            </div>

            <!-- Canvas & Textarea Container -->
            <div class="jj-composer-canvas-wrap" id="jjCanvasWrap">
                <textarea class="jj-composer-textarea" id="jjComposerText" placeholder="আপনার মনে কি ভাবনা চলছে? শেয়ার করুন..." rows="3"></textarea>
            </div>

            <!-- Mention & Hashtag Suggestions Popup -->
            <div class="jj-suggest-popup" id="jjMentionPopup"></div>

            <!-- Canvas Gradient Presets Palette -->
            <div class="jj-composer-palette" id="jjCanvasPalette">
                <span style="font-size: 11px; font-weight: 600; color: var(--jj-text-muted); margin-right: 4px;">ক্যানভাস কালার:</span>
                <div class="jj-palette-circle none active" title="স্বাভাবিক" onclick="window.EnterprisePostComposer.setCanvasStyle(null)"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #f97316 0%, #ec4899 100%);" title="Sunset Glow" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-sunset')"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 50%, #8b5cf6 100%);" title="Aurora Sky" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-aurora')"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);" title="Midnight" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-midnight')"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);" title="Emerald Forest" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-forest')"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #7c3aed 0%, #d946ef 50%, #f43f5e 100%);" title="Cyberpunk" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-cyberpunk')"></div>
                <div class="jj-palette-circle" style="background: linear-gradient(135deg, #e11d48 0%, #be185d 100%);" title="Berry Velvet" onclick="window.EnterprisePostComposer.setCanvasStyle('gradient-berry')"></div>
            </div>

            <!-- Active Tags / Metadata Badges -->
            <div class="jj-badges-container" id="jjBadgesContainer"></div>

            <!-- Adaptive Media Grid -->
            <div class="jj-media-grid" id="jjMediaGrid" style="display: none;"></div>

            <!-- Link Preview Box -->
            <div class="jj-link-preview-box" id="jjLinkPreviewBox" style="display: none;">
                <button type="button" class="jj-link-close-btn" onclick="window.EnterprisePostComposer.removeLinkPreview()" title="লিঙ্ক প্রিভিউ সরান">✕</button>
                <img src="" alt="" class="jj-link-preview-img" id="jjLinkPreviewImg" style="display: none;">
                <div class="jj-link-preview-meta">
                    <span class="jj-link-preview-domain" id="jjLinkPreviewDomain">EXAMPLE.COM</span>
                    <span class="jj-link-preview-title" id="jjLinkPreviewTitle">লিঙ্ক শিরোনাম</span>
                    <span class="jj-link-preview-desc" id="jjLinkPreviewDesc">লিঙ্কের বিবরণ</span>
                </div>
            </div>

            <!-- Poll Creator Box -->
            <div class="jj-poll-builder" id="jjPollBuilder" style="display: none;">
                <div class="jj-poll-title-bar">
                    <span>📊 পোল বা ভোটিং প্রশ্ন তৈরি করুন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.removePoll()" style="width:24px;height:24px;" title="পোল বাতিল করুন">✕</button>
                </div>
                <input type="text" class="jj-poll-opt-input" id="jjPollQuestion" placeholder="আপনার ভোটিং প্রশ্নটি লিখুন...">
                <div id="jjPollOptionsWrap" style="display: flex; flex-direction: column; gap: 8px;">
                    <input type="text" class="jj-poll-opt-input" placeholder="অপশন ১">
                    <input type="text" class="jj-poll-opt-input" placeholder="অপশন ২">
                </div>
                <div class="jj-poll-actions-row">
                    <button type="button" class="jj-poll-add-btn" onclick="window.EnterprisePostComposer.addPollOption()">+ আরও অপশন যোগ করুন</button>
                    <select id="jjPollDuration" style="font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--jj-card-border); background: var(--jj-card-bg); color: var(--jj-text-main);">
                        <option value="24">২৪ ঘণ্টা পর্যন্ত ভোট চলবে</option>
                        <option value="72">৩ দিন পর্যন্ত চলবে</option>
                        <option value="168">৭ দিন পর্যন্ত চলবে</option>
                    </select>
                </div>
            </div>

            <!-- GIF Drawer -->
            <div class="jj-drawer-panel" id="jjGifDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>অনুসন্ধান করুন অ্যানিমেটেড GIF</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('gif')" style="width:24px;height:24px;">✕</button>
                </div>
                <input type="text" class="jj-search-input" id="jjGifSearchInput" placeholder="GIF খুঁজুন (যেমন: happy, thumbs up, tech...)" oninput="window.EnterprisePostComposer.searchGifs(this.value)">
                <div class="jj-category-chips">
                    <span class="jj-chip active" onclick="window.EnterprisePostComposer.loadGifCategory('trending', this)">🔥 ট্রেন্ডিং</span>
                    <span class="jj-chip" onclick="window.EnterprisePostComposer.loadGifCategory('happy', this)">😄 আনন্দ</span>
                    <span class="jj-chip" onclick="window.EnterprisePostComposer.loadGifCategory('celebration', this)">🎉 উদযাপন</span>
                    <span class="jj-chip" onclick="window.EnterprisePostComposer.loadGifCategory('tech', this)">💻 টেক</span>
                    <span class="jj-chip" onclick="window.EnterprisePostComposer.loadGifCategory('bangladesh', this)">🇧🇩 বাংলাদেশ</span>
                </div>
                <div class="jj-gif-grid" id="jjGifGrid"></div>
            </div>

            <!-- Feeling & Activity Drawer -->
            <div class="jj-drawer-panel" id="jjFeelingDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>অনুভূতি বা কার্যকলাপ যুক্ত করুন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('feeling')" style="width:24px;height:24px;">✕</button>
                </div>
                <input type="text" class="jj-search-input" id="jjFeelingSearchInput" placeholder="খুঁজুন..." oninput="window.EnterprisePostComposer.filterFeelings(this.value)">
                <div class="jj-category-chips">
                    <span class="jj-chip active" id="jjFeelingTabFeelings" onclick="window.EnterprisePostComposer.switchFeelingTab('feelings')">😊 অনুভূতি</span>
                    <span class="jj-chip" id="jjFeelingTabActivities" onclick="window.EnterprisePostComposer.switchFeelingTab('activities')">🎯 কার্যকলাপ</span>
                </div>
                <div class="jj-search-results-list" id="jjFeelingsList"></div>
            </div>

            <!-- Location Drawer -->
            <div class="jj-drawer-panel" id="jjLocationDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>লোকেশন বা অবস্থান নির্বাচন করুন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('location')" style="width:24px;height:24px;">✕</button>
                </div>
                <input type="text" class="jj-search-input" id="jjLocationSearchInput" placeholder="শহর বা স্থান লিখুন (যেমন: ঢাকা, চট্টগ্রাম, সিলেট, লন্ডন...)" oninput="window.EnterprisePostComposer.searchLocations(this.value)">
                <div class="jj-search-results-list" id="jjLocationsList"></div>
            </div>

            <!-- Tag Friends Drawer -->
            <div class="jj-drawer-panel" id="jjTagFriendsDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>বন্ধুদের ট্যাগ করুন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('tagFriends')" style="width:24px;height:24px;">✕</button>
                </div>
                <input type="text" class="jj-search-input" id="jjTagSearchInput" placeholder="বন্ধুর নাম খুঁজুন..." oninput="window.EnterprisePostComposer.searchTaggableUsers(this.value, 'tag')">
                <div class="jj-search-results-list" id="jjTagUsersList"></div>
            </div>

            <!-- Collaborator Drawer -->
            <div class="jj-drawer-panel" id="jjCollaboratorDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>যৌথ লেখক বা কোলাবোরেটর আমন্ত্রণ জানান</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('collaborator')" style="width:24px;height:24px;">✕</button>
                </div>
                <p style="font-size: 12px; color: var(--jj-text-muted); margin: 0;">যৌথ সহযোগী গ্রহণ করলে পোস্টটি উভয়ের প্রোফাইলে প্রদর্শিত হবে।</p>
                <input type="text" class="jj-search-input" id="jjCollabSearchInput" placeholder="সহযোগীর নাম খুঁজুন..." oninput="window.EnterprisePostComposer.searchTaggableUsers(this.value, 'collab')">
                <div class="jj-search-results-list" id="jjCollabUsersList"></div>
            </div>

            <!-- Group Selector Drawer -->
            <div class="jj-drawer-panel" id="jjGroupDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>যে গ্রুপে পোস্ট করতে চান তা বেছে নিন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('group')" style="width:24px;height:24px;">✕</button>
                </div>
                <div class="jj-search-results-list" id="jjUserGroupsList"></div>
            </div>

            <!-- Schedule Drawer -->
            <div class="jj-drawer-panel" id="jjScheduleDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>পোস্ট প্রকাশের সময় নির্ধারণ করুন</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('schedule')" style="width:24px;height:24px;">✕</button>
                </div>
                <p style="font-size: 12px; color: var(--jj-text-muted); margin: 0;">নির্ধারিত সময়ে পোস্টটি ব্যাকএন্ড জব কিউ থেকে স্বয়ংক্রিয়ভাবে প্রকাশিত হবে।</p>
                <input type="datetime-local" class="jj-search-input" id="jjScheduleDateTime">
                <div style="display:flex; justify-content:flex-end; gap: 8px;">
                    <button type="button" class="jj-btn-secondary" onclick="window.EnterprisePostComposer.clearSchedule()">সিডিউল বাতিল</button>
                    <button type="button" class="jj-btn-primary" onclick="window.EnterprisePostComposer.saveSchedule()">নির্ধারণ করুন</button>
                </div>
            </div>

            <!-- Drafts List Drawer -->
            <div class="jj-drawer-panel" id="jjDraftsDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>সংরক্ষিত খসড়াগুলো</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('drafts')" style="width:24px;height:24px;">✕</button>
                </div>
                <div class="jj-search-results-list" id="jjDraftsItemsList"></div>
            </div>

            <!-- Advanced Settings Drawer -->
            <div class="jj-drawer-panel" id="jjAdvancedDrawer" style="display: none;">
                <div class="jj-drawer-header">
                    <span>অ্যাডভান্সড পোস্ট সেটিংস</span>
                    <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeDrawer('advanced')" style="width:24px;height:24px;">✕</button>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; cursor: pointer;">
                        <span>📖 একই সাথে স্টোরিতেও প্রকাশ করুন (Share to Story)</span>
                        <input type="checkbox" id="jjToggleStory" style="width: 18px; height: 18px; accent-color: var(--jj-primary);">
                    </label>
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; cursor: pointer;">
                        <span>🤖 এআই-জেনারেটেড কন্টেন্ট লেবেল যুক্ত করুন</span>
                        <input type="checkbox" id="jjToggleAi" style="width: 18px; height: 18px; accent-color: var(--jj-primary);">
                    </label>
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; cursor: pointer;">
                        <span>💬 এই পোস্টে কমেন্ট বন্ধ রাখুন (Disable Comments)</span>
                        <input type="checkbox" id="jjToggleComments" style="width: 18px; height: 18px; accent-color: var(--jj-primary);">
                    </label>
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; cursor: pointer;">
                        <span>⚠️ সেনসিটিভ কনটেন্ট সতর্কতা যোগ করুন</span>
                        <input type="checkbox" id="jjToggleWarning" onchange="window.EnterprisePostComposer.toggleWarningBox(this.checked)" style="width: 18px; height: 18px; accent-color: var(--jj-primary);">
                    </label>
                </div>
            </div>

            <!-- Hidden File Input for Image/Video Selection -->
            <input type="file" id="jjFileInput" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" style="display: none;" onchange="window.EnterprisePostComposer.handleFiles(this.files)">

        </div>

        <!-- Action Toolbar -->
        <div class="jj-composer-toolbar">
            <span style="font-size: 13px; font-weight: 700; color: var(--jj-text-muted);">পোস্টে যুক্ত করুন:</span>
            <div class="jj-toolbar-icons">
                <!-- Media Upload (Photo/Video) -->
                <button type="button" class="jj-tool-btn" title="ছবি বা ভিডিও যুক্ত করুন" onclick="document.getElementById('jjFileInput').click()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </button>
                <!-- Feeling / Activity -->
                <button type="button" class="jj-tool-btn" title="অনুভূতি বা কার্যকলাপ" onclick="window.EnterprisePostComposer.openDrawer('feeling')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                </button>
                <!-- GIF Search -->
                <button type="button" class="jj-tool-btn" title="অ্যানিমেটেড GIF" onclick="window.EnterprisePostComposer.openDrawer('gif')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="M10 8H7v8h3"></path><path d="M14 8v8"></path><path d="M17 8h3v8h-3"></path><line x1="7" y1="12" x2="9" y2="12"></line><line x1="17" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <!-- Poll -->
                <button type="button" class="jj-tool-btn" title="ভোটিং পোল" onclick="window.EnterprisePostComposer.openDrawer('poll')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </button>
                <!-- Tag Friends -->
                <button type="button" class="jj-tool-btn" title="বন্ধুদের ট্যাগ করুন" onclick="window.EnterprisePostComposer.openDrawer('tagFriends')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </button>
                <!-- Location -->
                <button type="button" class="jj-tool-btn" title="লোকেশন যুক্ত করুন" onclick="window.EnterprisePostComposer.openDrawer('location')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </button>
                <!-- Collaborator -->
                <button type="button" class="jj-tool-btn" title="যৌথ লেখক (Collaborator)" onclick="window.EnterprisePostComposer.openDrawer('collaborator')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </button>
                <!-- Canvas Presets Palette Toggle -->
                <button type="button" class="jj-tool-btn" title="ব্যাকগ্রাউন্ড ক্যানভাস কালার" onclick="window.EnterprisePostComposer.toggleCanvasPalette()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a10 10 0 0 1 10 10c0 2.5-1.5 4.5-4 4.5h-2a2 2 0 0 0-2 2c0 1.5-1 2.5-2 2.5a10 10 0 0 1-2-19z"></path></svg>
                </button>
                <!-- More Settings -->
                <button type="button" class="jj-tool-btn" title="অ্যাডভান্সড সেটিংস ও সিডিউল" onclick="window.EnterprisePostComposer.openDrawer('advanced')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </button>
            </div>
        </div>

        <!-- Footer Submit Row -->
        <div class="jj-composer-footer">
            <button type="button" class="jj-btn-secondary" onclick="window.EnterprisePostComposer.saveDraftManually()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>খসড়া রাখুন</span>
            </button>
            <button type="button" class="jj-btn-primary" id="jjPublishBtn" onclick="window.EnterprisePostComposer.submitPost()">
                <span id="jjPublishBtnText">পোস্ট প্রকাশ করুন 🚀</span>
            </button>
        </div>

    </div>
</div>

<!-- Non-destructive In-Composer Image Editor Submodal -->
<div class="jj-editor-overlay" id="jjImageEditorModal">
    <div class="jj-editor-card">
        <div class="jj-composer-header">
            <span class="jj-composer-title-text">ছবি এডিট করুন (Non-destructive)</span>
            <button type="button" class="jj-composer-icon-btn" onclick="window.EnterprisePostComposer.closeImageEditor()">✕</button>
        </div>
        <div class="jj-editor-preview-container">
            <img id="jjEditorPreviewImg" src="" alt="এডিটর প্রিভিউ">
        </div>
        <div class="jj-editor-controls">
            <!-- Crop aspect ratios & Rotate -->
            <div class="jj-control-row">
                <span style="font-size: 13px; font-weight: 700;">সাইজ ও ঘূর্ণন:</span>
                <span class="jj-filter-badge active" onclick="window.EnterprisePostComposer.setCropRatio('original', this)">মূল সাইজ</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setCropRatio('1:1', this)">১:১ (বর্গাকার)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setCropRatio('4:5', this)">৪:৫ (পোর্ট্রেট)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setCropRatio('16:9', this)">১৬:৯ (ওয়াইড)</span>
                <button type="button" class="jj-btn-secondary" style="padding: 4px 10px; font-size: 12px;" onclick="window.EnterprisePostComposer.rotateEditorImage()">
                    🔄 ৯০° ঘোরান
                </button>
            </div>
            <!-- Filters -->
            <div class="jj-control-row">
                <span style="font-size: 13px; font-weight: 700;">ফিল্টার:</span>
                <span class="jj-filter-badge active" onclick="window.EnterprisePostComposer.setFilter('none', this)">নরমাল</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setFilter('vibrant', this)">উজ্জ্বল (Vibrant)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setFilter('noir', this)">সাদা-কালো (Noir)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setFilter('warm', this)">উষ্ণ (Warm)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setFilter('cool', this)">শীতল (Cool)</span>
                <span class="jj-filter-badge" onclick="window.EnterprisePostComposer.setFilter('vintage', this)">ভিন্টেজ (Vintage)</span>
            </div>
            <!-- Alt text & Caption -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <input type="text" class="jj-search-input" id="jjMediaCaptionInput" placeholder="এই ছবির ক্যাপশন লিখুন...">
                <input type="text" class="jj-search-input" id="jjMediaAltInput" placeholder="অ্যাক্সেসিবিলিটি Alt টেক্সট (স্ক্রিন রিডারের জন্য)...">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                <button type="button" class="jj-btn-secondary" onclick="window.EnterprisePostComposer.closeImageEditor()">বাতিল</button>
                <button type="button" class="jj-btn-primary" onclick="window.EnterprisePostComposer.saveImageEdits()">পরিবর্তন সংরক্ষণ করুন</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Jugajug Enterprise Post Composer JavaScript Engine
 * Complete, production-grade social post composer architecture.
 */
(function() {
    window.EnterprisePostComposer = {
        state: {
            content: '',
            media: [], // [{ id, url, thumbnail, mime_type, size, isUploading, progress, xhr, meta: { alt, caption, filter, rotate, crop } }]
            audience: 'public',
            type: 'text',
            background_style: null,
            location: null,
            feeling_activity: null,
            tagged_users: [], // [{ id, name, username, avatar_url }]
            collaborator: null, // { id, name, username, avatar_url }
            group: null, // { id, name }
            poll_data: null,
            link_preview: null,
            is_ai_generated: false,
            comments_disabled: false,
            content_warning: null,
            share_to_story: false,
            scheduled_at: null,
            draft_id: null,
        },

        editingMediaIndex: null,
        autoSaveTimeout: null,
        linkDetectTimeout: null,
        mentionDebounceTimeout: null,
        isSubmitting: false,

        feelingsList: [
            { text: '— 😊 আনন্দিত বোধ করছি', label: '😊 আনন্দিত' },
            { text: '— 🤩 রোমাঞ্চিত বোধ করছি', label: '🤩 রোমাঞ্চিত' },
            { text: '— 😇 কৃতজ্ঞ বোধ করছি', label: '😇 কৃতজ্ঞ' },
            { text: '— 😎 আত্মবিশ্বাসী বোধ করছি', label: '😎 আত্মবিশ্বাসী' },
            { text: '— 😴 ক্লান্ত বোধ করছি', label: '😴 ক্লান্ত' },
            { text: '— 🥳 উদযাপিত বোধ করছি', label: '🥳 উদযাপিত' },
            { text: '— 💡 অনুপ্রাণিত বোধ করছি', label: '💡 অনুপ্রাণিত' },
            { text: '— 💖 ভালোবাসাময় বোধ করছি', label: '💖 ভালোবাসাময়' },
        ],

        activitiesList: [
            { text: '— ☕ চা খাচ্ছি', label: '☕ চা খাচ্ছি' },
            { text: '— 💻 কোডিং করছি', label: '💻 কোডিং করছি' },
            { text: '— ✈️ ভ্রমণ করছি', label: '✈️ ভ্রমণ করছি' },
            { text: '— 📚 পড়াশোনা করছি', label: '📚 পড়াশোনা করছি' },
            { text: '— 🎧 গান শুনছি', label: '🎧 গান শুনছি' },
            { text: '— 🍕 খাচ্ছি', label: '🍕 খাচ্ছি' },
            { text: '— 🎬 সিনেমা দেখছি', label: '🎬 সিনেমা দেখছি' },
            { text: '— 🏃‍♂️ ব্যায়াম করছি', label: '🏃‍♂️ ব্যায়াম করছি' },
        ],

        init: function() {
            const self = this;
            const textarea = document.getElementById('jjComposerText');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    self.state.content = textarea.value;
                    self.autoResizeTextarea(textarea);
                    self.scheduleAutoSave();
                    self.handleTextChange(textarea.value);
                });

                // Clipboard paste support for direct images!
                textarea.addEventListener('paste', function(e) {
                    if (e.clipboardData && e.clipboardData.files && e.clipboardData.files.length > 0) {
                        e.preventDefault();
                        self.handleFiles(e.clipboardData.files);
                    }
                });
            }

            // Drag and Drop support on the composer card
            const card = document.getElementById('jjComposerCard');
            if (card) {
                ['dragenter', 'dragover'].forEach(evt => {
                    card.addEventListener(evt, (e) => {
                        e.preventDefault();
                        card.style.borderColor = 'var(--jj-primary)';
                    });
                });
                ['dragleave', 'drop'].forEach(evt => {
                    card.addEventListener(evt, (e) => {
                        e.preventDefault();
                        card.style.borderColor = 'var(--jj-card-border)';
                    });
                });
                card.addEventListener('drop', (e) => {
                    e.preventDefault();
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        self.handleFiles(e.dataTransfer.files);
                    }
                });
            }

            // Close modals when pressing Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (document.getElementById('jjImageEditorModal')?.classList.contains('active')) {
                        self.closeImageEditor();
                    } else if (document.getElementById('jjComposerOverlay')?.classList.contains('jj-open')) {
                        self.close();
                    }
                }
            });

            // Initialize user info from window if available
            self.loadCurrentUserInfo();
        },

        loadCurrentUserInfo: function() {
            try {
                const user = window.currentUser || (typeof currentUser !== 'undefined' ? currentUser : null);
                if (user) {
                    const avatarEl = document.getElementById('jjAuthorAvatar');
                    const nameEl = document.getElementById('jjAuthorName');
                    if (avatarEl && user.avatar_url) avatarEl.src = user.avatar_url;
                    if (nameEl && user.name) nameEl.textContent = user.name;
                }
            } catch (e) {
                console.warn('Error loading user info in composer:', e);
            }
        },

        open: function(mode = null) {
            const overlay = document.getElementById('jjComposerOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                setTimeout(() => overlay.classList.add('jj-open'), 10);
            }
            this.loadCurrentUserInfo();

            // Auto-check for existing drafts
            this.fetchLatestDraft();

            // If a specific mode was requested
            if (mode === 'photo' || mode === 'video') {
                document.getElementById('jjFileInput')?.click();
            } else if (mode === 'feeling') {
                this.openDrawer('feeling');
            } else if (mode === 'poll') {
                this.openDrawer('poll');
            } else {
                document.getElementById('jjComposerText')?.focus();
            }
        },

        close: function() {
            const self = this;
            // If has unposted changes, save draft immediately before closing
            if (self.state.content.trim() || self.state.media.length > 0) {
                self.saveDraft();
            }
            const overlay = document.getElementById('jjComposerOverlay');
            if (overlay) {
                overlay.classList.remove('jj-open');
                setTimeout(() => { overlay.style.display = 'none'; }, 250);
            }
        },

        autoResizeTextarea: function(el) {
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 80) + 'px';
        },

        getAuthToken: function() {
            return window.currentToken || localStorage.getItem('auth_token') || '';
        },

        getAuthHeaders: function(custom = {}) {
            const token = this.getAuthToken();
            const headers = {
                'Accept': 'application/json',
                ...custom
            };
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
            }
            return headers;
        },

        /* ------------------------------------------------------------- */
        /* MEDIA HANDLING (REAL MULTIPART & CHUNKING WITH PROGRESS)     */
        /* ------------------------------------------------------------- */
        handleFiles: function(files) {
            if (!files || files.length === 0) return;
            const self = this;

            // Reset background canvas if media is added
            if (self.state.background_style) {
                self.setCanvasStyle(null);
            }

            Array.from(files).forEach(file => {
                const isVideo = file.type.startsWith('video/');
                const isImage = file.type.startsWith('image/');
                if (!isVideo && !isImage) {
                    alert('শুধুমাত্র ছবি বা ভিডিও আপলোড করা যাবে।');
                    return;
                }

                // Check file size limit (50MB)
                if (file.size > 52428800) {
                    alert(`ফাইলের সাইজ ৫০ মেগাবাইটের বেশি হতে পারবে না: ${file.name}`);
                    return;
                }

                const localUrl = URL.createObjectURL(file);
                const mediaItem = {
                    id: null,
                    file: file,
                    url: localUrl,
                    thumbnail: localUrl,
                    mime_type: file.type,
                    size: file.size,
                    isUploading: true,
                    progress: 0,
                    xhr: null,
                    meta: {
                        caption: '',
                        alt: '',
                        filter: 'none',
                        rotate: 0,
                        crop: 'original'
                    }
                };

                const mediaIndex = self.state.media.push(mediaItem) - 1;
                self.renderMediaGrid();

                // Trigger real upload
                self.uploadMediaFile(mediaIndex);
            });
        },

        uploadMediaFile: function(index) {
            const self = this;
            const mediaItem = self.state.media[index];
            if (!mediaItem || !mediaItem.file) return;

            const formData = new FormData();
            formData.append('file', mediaItem.file);
            formData.append('collection', 'post');

            const xhr = new XMLHttpRequest();
            mediaItem.xhr = xhr;

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    mediaItem.progress = percent;
                    self.updateUploadProgressUI(index, percent);
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        const mediaData = res.data || res;
                        mediaItem.id = mediaData.id;
                        mediaItem.isUploading = false;
                        mediaItem.progress = 100;
                        if (mediaData.urls && mediaData.urls.original) {
                            mediaItem.url = mediaData.urls.original;
                            mediaItem.thumbnail = mediaData.urls.thumbnail || mediaData.urls.original;
                        }
                        self.renderMediaGrid();
                        self.scheduleAutoSave();
                    } catch (e) {
                        console.error('Failed to parse upload response:', e);
                        self.markUploadError(index, 'প্রসেসিং ত্রুটি');
                    }
                } else {
                    let errMsg = 'আপলোড ব্যর্থ হয়েছে';
                    try {
                        const err = JSON.parse(xhr.responseText);
                        if (err.message) errMsg = err.message;
                    } catch (_) {}
                    self.markUploadError(index, errMsg);
                }
            });

            xhr.addEventListener('error', function() {
                self.markUploadError(index, 'নেটওয়ার্ক সংযোগ ত্রুটি');
            });

            xhr.addEventListener('abort', function() {
                self.removeMedia(index);
            });

            xhr.open('POST', '/api/v1/media/upload', true);
            const token = self.getAuthToken();
            if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            xhr.setRequestHeader('Accept', 'application/json');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) xhr.setRequestHeader('X-CSRF-TOKEN', csrfMeta.getAttribute('content'));

            xhr.send(formData);
        },

        updateUploadProgressUI: function(index, percent) {
            const fillEl = document.getElementById(`jjProgressFill-${index}`);
            const textEl = document.getElementById(`jjProgressText-${index}`);
            if (fillEl) fillEl.style.width = percent + '%';
            if (textEl) textEl.textContent = `${percent}% আপলোড হচ্ছে...`;
        },

        markUploadError: function(index, message) {
            const mediaItem = this.state.media[index];
            if (!mediaItem) return;
            mediaItem.isUploading = false;
            mediaItem.hasError = true;
            mediaItem.errorMessage = message;
            this.renderMediaGrid();
        },

        removeMedia: function(index) {
            const mediaItem = this.state.media[index];
            if (mediaItem && mediaItem.xhr && mediaItem.isUploading) {
                mediaItem.xhr.abort();
            }
            this.state.media.splice(index, 1);
            this.renderMediaGrid();
            this.scheduleAutoSave();
        },

        renderMediaGrid: function() {
            const grid = document.getElementById('jjMediaGrid');
            if (!grid) return;
            const items = this.state.media;

            if (items.length === 0) {
                grid.style.display = 'none';
                grid.innerHTML = '';
                return;
            }

            grid.style.display = 'grid';
            const countClass = items.length >= 5 ? 'count-5' : `count-${items.length}`;
            grid.className = `jj-media-grid ${countClass}`;

            let html = '';
            const displayCount = Math.min(items.length, 4);

            for (let i = 0; i < displayCount; i++) {
                const item = items[i];
                const isVideo = item.mime_type && item.mime_type.startsWith('video/');
                const isFourthAndMore = (i === 3 && items.length > 4);
                const filterStyle = this.getFilterCss(item.meta?.filter);
                const transformStyle = item.meta?.rotate ? `transform: rotate(${item.meta.rotate}deg);` : '';

                html += `<div class="jj-media-cell" id="jjMediaCell-${i}">`;

                if (isVideo) {
                    html += `<video src="${item.url}" muted playsinline preload="metadata" style="${filterStyle} ${transformStyle}"></video>`;
                } else {
                    html += `<img src="${item.thumbnail || item.url}" alt="${item.meta?.alt || 'ছবি'}" style="${filterStyle} ${transformStyle}">`;
                }

                // Upload progress overlay
                if (item.isUploading) {
                    html += `
                        <div class="jj-upload-progress-cell">
                            <div class="jj-progress-bar-wrap">
                                <div class="jj-progress-bar-fill" id="jjProgressFill-${i}" style="width: ${item.progress || 0}%;"></div>
                            </div>
                            <div class="jj-progress-status-text">
                                <span id="jjProgressText-${i}">${item.progress || 0}% আপলোড হচ্ছে...</span>
                                <button type="button" class="jj-cell-btn danger" onclick="window.EnterprisePostComposer.removeMedia(${i})" title="বাতিল করুন">✕</button>
                            </div>
                        </div>
                    `;
                } else if (item.hasError) {
                    html += `
                        <div class="jj-upload-progress-cell" style="background: rgba(239,68,68,0.85);">
                            <div style="font-size: 13px; font-weight: 700;">⚠️ ${item.errorMessage || 'ত্রুটি'}</div>
                            <div style="display:flex;gap:6px;margin-top:6px;">
                                <button type="button" class="jj-btn-secondary" style="padding:4px 8px;font-size:11px;" onclick="window.EnterprisePostComposer.uploadMediaFile(${i})">পুনরায় চেষ্টা</button>
                                <button type="button" class="jj-cell-btn danger" onclick="window.EnterprisePostComposer.removeMedia(${i})">✕</button>
                            </div>
                        </div>
                    `;
                } else {
                    // Cell hover action buttons
                    html += `
                        <div class="jj-cell-actions">
                            ${!isVideo ? `<button type="button" class="jj-cell-btn" onclick="window.EnterprisePostComposer.openImageEditor(${i})" title="এডিট করুন">🎨</button>` : ''}
                            <button type="button" class="jj-cell-btn danger" onclick="window.EnterprisePostComposer.removeMedia(${i})" title="মুছে ফেলুন">✕</button>
                        </div>
                    `;
                }

                if (isFourthAndMore) {
                    html += `<div class="jj-more-overlay">+${items.length - 3}</div>`;
                }

                html += `</div>`;
            }

            grid.innerHTML = html;
        },

        getFilterCss: function(filterName) {
            switch (filterName) {
                case 'vibrant': return 'filter: saturate(1.4) contrast(1.1);';
                case 'noir': return 'filter: grayscale(100%) contrast(1.2);';
                case 'warm': return 'filter: sepia(0.3) saturate(1.2);';
                case 'cool': return 'filter: hue-rotate(180deg) saturate(0.9);';
                case 'vintage': return 'filter: sepia(0.5) contrast(0.9);';
                default: return '';
            }
        },

        /* ------------------------------------------------------------- */
        /* IN-COMPOSER NON-DESTRUCTIVE IMAGE EDITOR                     */
        /* ------------------------------------------------------------- */
        openImageEditor: function(index) {
            const item = this.state.media[index];
            if (!item) return;
            this.editingMediaIndex = index;

            const modal = document.getElementById('jjImageEditorModal');
            const previewImg = document.getElementById('jjEditorPreviewImg');
            const captionInput = document.getElementById('jjMediaCaptionInput');
            const altInput = document.getElementById('jjMediaAltInput');

            if (previewImg) previewImg.src = item.url;
            if (captionInput) captionInput.value = item.meta?.caption || '';
            if (altInput) altInput.value = item.meta?.alt || '';

            this.applyEditorStyles(item.meta?.filter, item.meta?.rotate);
            modal.classList.add('active');
        },

        closeImageEditor: function() {
            document.getElementById('jjImageEditorModal')?.classList.remove('active');
            this.editingMediaIndex = null;
        },

        setCropRatio: function(ratio, badge) {
            document.querySelectorAll('#jjImageEditorModal .jj-control-row:first-child .jj-filter-badge').forEach(b => b.classList.remove('active'));
            if (badge) badge.classList.add('active');
            const img = document.getElementById('jjEditorPreviewImg');
            if (!img) return;

            if (ratio === '1:1') {
                img.style.aspectRatio = '1 / 1';
                img.style.objectFit = 'cover';
            } else if (ratio === '4:5') {
                img.style.aspectRatio = '4 / 5';
                img.style.objectFit = 'cover';
            } else if (ratio === '16:9') {
                img.style.aspectRatio = '16 / 9';
                img.style.objectFit = 'cover';
            } else {
                img.style.aspectRatio = 'auto';
                img.style.objectFit = 'contain';
            }
            if (this.editingMediaIndex !== null && this.state.media[this.editingMediaIndex]) {
                this.state.media[this.editingMediaIndex].meta.crop = ratio;
            }
        },

        rotateEditorImage: function() {
            if (this.editingMediaIndex === null) return;
            const item = this.state.media[this.editingMediaIndex];
            item.meta.rotate = ((item.meta.rotate || 0) + 90) % 360;
            this.applyEditorStyles(item.meta.filter, item.meta.rotate);
        },

        setFilter: function(filterName, badge) {
            document.querySelectorAll('#jjImageEditorModal .jj-control-row:nth-child(2) .jj-filter-badge').forEach(b => b.classList.remove('active'));
            if (badge) badge.classList.add('active');
            if (this.editingMediaIndex === null) return;
            this.state.media[this.editingMediaIndex].meta.filter = filterName;
            this.applyEditorStyles(filterName, this.state.media[this.editingMediaIndex].meta.rotate);
        },

        applyEditorStyles: function(filterName, rotateDeg = 0) {
            const img = document.getElementById('jjEditorPreviewImg');
            if (!img) return;
            img.style.filter = this.getFilterCss(filterName).replace('filter:', '').replace(';', '');
            img.style.transform = `rotate(${rotateDeg}deg)`;
        },

        saveImageEdits: function() {
            if (this.editingMediaIndex === null) return;
            const item = this.state.media[this.editingMediaIndex];
            const captionInput = document.getElementById('jjMediaCaptionInput');
            const altInput = document.getElementById('jjMediaAltInput');

            item.meta.caption = captionInput?.value.trim() || '';
            item.meta.alt = altInput?.value.trim() || '';

            this.closeImageEditor();
            this.renderMediaGrid();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* CANVAS BACKGROUND PRESETS                                    */
        /* ------------------------------------------------------------- */
        toggleCanvasPalette: function() {
            const palette = document.getElementById('jjCanvasPalette');
            if (palette) palette.classList.toggle('visible');
        },

        setCanvasStyle: function(styleName) {
            this.state.background_style = styleName;
            const wrap = document.getElementById('jjCanvasWrap');
            if (!wrap) return;

            wrap.className = 'jj-composer-canvas-wrap';
            if (styleName) {
                wrap.classList.add('has-canvas', styleName);
            }

            // Update palette active states
            document.querySelectorAll('.jj-palette-circle').forEach(el => el.classList.remove('active'));
            if (!styleName) {
                document.querySelector('.jj-palette-circle.none')?.classList.add('active');
            }
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* AUDIENCE & DRAWERS                                            */
        /* ------------------------------------------------------------- */
        toggleAudienceMenu: function() {
            const menu = document.getElementById('jjAudienceMenu');
            if (!menu) return;
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        },

        setAudience: function(aud) {
            this.state.audience = aud;
            const textEl = document.getElementById('jjAudienceText');
            if (textEl) {
                const labels = {
                    public: '🌍 পাবলিক',
                    friends: '👥 বন্ধুরা',
                    followers: '🌟 ফলোয়াররা',
                    only_me: '🔒 শুধুমাত্র আমি'
                };
                textEl.textContent = labels[aud] || 'পাবলিক';
            }
            document.getElementById('jjAudienceMenu').style.display = 'none';
            this.scheduleAutoSave();
        },

        openDrawer: function(drawerName) {
            this.closeAllDrawers();
            const drawerId = `jj${drawerName.charAt(0).toUpperCase() + drawerName.slice(1)}Drawer`;
            const drawer = document.getElementById(drawerId);
            if (drawer) {
                drawer.style.display = 'flex';
                if (drawerName === 'gif') this.loadGifCategory('trending');
                if (drawerName === 'feeling') this.switchFeelingTab('feelings');
                if (drawerName === 'group') this.fetchUserGroups();
                if (drawerName === 'poll') this.setupPollBox();
            }
        },

        closeDrawer: function(drawerName) {
            const drawerId = `jj${drawerName.charAt(0).toUpperCase() + drawerName.slice(1)}Drawer`;
            const drawer = document.getElementById(drawerId);
            if (drawer) drawer.style.display = 'none';
        },

        closeAllDrawers: function() {
            const drawerIds = [
                'jjGifDrawer', 'jjFeelingDrawer', 'jjLocationDrawer',
                'jjTagFriendsDrawer', 'jjCollaboratorDrawer', 'jjGroupDrawer',
                'jjScheduleDrawer', 'jjDraftsDrawer', 'jjAdvancedDrawer'
            ];
            drawerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            const audMenu = document.getElementById('jjAudienceMenu');
            if (audMenu) audMenu.style.display = 'none';
        },

        /* ------------------------------------------------------------- */
        /* GIF SEARCH                                                   */
        /* ------------------------------------------------------------- */
        loadGifCategory: function(category, chip) {
            if (chip) {
                document.querySelectorAll('#jjGifDrawer .jj-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            }
            this.fetchGifs('', category);
        },

        searchGifs: function(query) {
            clearTimeout(this.gifSearchTimeout);
            this.gifSearchTimeout = setTimeout(() => {
                this.fetchGifs(query);
            }, 300);
        },

        fetchGifs: async function(q = '', category = '') {
            const grid = document.getElementById('jjGifGrid');
            if (!grid) return;
            grid.innerHTML = '<div style="grid-column: span 3; text-align:center; padding: 20px; font-size: 13px; color: var(--jj-text-muted);">GIF লোড হচ্ছে...</div>';

            try {
                const params = new URLSearchParams();
                if (q) params.set('q', q);
                if (category) params.set('category', category);

                const res = await fetch(`/api/v1/posts/gifs?${params.toString()}`, {
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(item => {
                        html += `
                            <div class="jj-gif-item" onclick="window.EnterprisePostComposer.selectGif('${item.url}', '${item.title || 'GIF'}')">
                                <img src="${item.preview || item.url}" alt="${item.title || 'GIF'}" loading="lazy">
                            </div>
                        `;
                    });
                    grid.innerHTML = html;
                } else {
                    grid.innerHTML = '<div style="grid-column: span 3; text-align:center; padding: 20px; font-size: 13px; color: var(--jj-text-muted);">কোনো GIF পাওয়া যায়নি</div>';
                }
            } catch (e) {
                grid.innerHTML = '<div style="grid-column: span 3; text-align:center; padding: 20px; font-size: 13px; color: var(--jj-danger);">GIF লোড করতে সমস্যা হয়েছে</div>';
            }
        },

        selectGif: function(url, title) {
            this.state.media.push({
                id: null,
                file: null,
                url: url,
                thumbnail: url,
                mime_type: 'image/gif',
                size: 0,
                isUploading: false,
                progress: 100,
                meta: { caption: title, alt: title, filter: 'none', rotate: 0, crop: 'original' }
            });
            this.closeDrawer('gif');
            this.renderMediaGrid();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* FEELING & ACTIVITY                                           */
        /* ------------------------------------------------------------- */
        switchFeelingTab: function(tab) {
            const tabFeelings = document.getElementById('jjFeelingTabFeelings');
            const tabActivities = document.getElementById('jjFeelingTabActivities');
            if (tab === 'feelings') {
                tabFeelings?.classList.add('active');
                tabActivities?.classList.remove('active');
                this.renderFeelingsList(this.feelingsList);
            } else {
                tabActivities?.classList.add('active');
                tabFeelings?.classList.remove('active');
                this.renderFeelingsList(this.activitiesList);
            }
        },

        filterFeelings: function(query) {
            const q = query.toLowerCase();
            const isFeelings = document.getElementById('jjFeelingTabFeelings')?.classList.contains('active');
            const list = isFeelings ? this.feelingsList : this.activitiesList;
            const filtered = list.filter(item => item.label.toLowerCase().includes(q));
            this.renderFeelingsList(filtered);
        },

        renderFeelingsList: function(items) {
            const list = document.getElementById('jjFeelingsList');
            if (!list) return;
            if (items.length === 0) {
                list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">কোনো ফলাফল পাওয়া যায়নি</div>';
                return;
            }
            let html = '';
            items.forEach(item => {
                html += `
                    <div class="jj-search-user-item" onclick="window.EnterprisePostComposer.selectFeeling('${item.text}')">
                        <span style="font-size: 14px; font-weight: 600;">${item.label}</span>
                    </div>
                `;
            });
            list.innerHTML = html;
        },

        selectFeeling: function(feelingText) {
            this.state.feeling_activity = feelingText;
            this.closeDrawer('feeling');
            this.renderBadges();
            this.scheduleAutoSave();
        },

        removeFeeling: function() {
            this.state.feeling_activity = null;
            this.renderBadges();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* LOCATION                                                      */
        /* ------------------------------------------------------------- */
        searchLocations: function(query) {
            const locations = [
                'ঢাকা, বাংলাদেশ', 'চট্টগ্রাম, বাংলাদেশ', 'সিলেট, বাংলাদেশ',
                'রাজশাহী, বাংলাদেশ', 'খুলনা, বাংলাদেশ', 'কক্সবাজার, বাংলাদেশ',
                'লন্ডন, যুক্তরাজ্য', 'নিউ ইয়র্ক, যুক্তরাষ্ট্র', 'টরন্টো, কানাডা',
                'দুবাই, সংযুক্ত আরব আমিরাত', 'টোকিও, জাপান', 'রিমোট / বাসা থেকে'
            ];
            const q = query.trim().toLowerCase();
            const results = q ? locations.filter(loc => loc.toLowerCase().includes(q)) : locations;
            const list = document.getElementById('jjLocationsList');
            if (!list) return;

            let html = '';
            results.forEach(loc => {
                html += `
                    <div class="jj-search-user-item" onclick="window.EnterprisePostComposer.selectLocation('${loc}')">
                        <span>📍 ${loc}</span>
                    </div>
                `;
            });
            list.innerHTML = html;
        },

        selectLocation: function(loc) {
            this.state.location = loc;
            this.closeDrawer('location');
            this.renderBadges();
            this.scheduleAutoSave();
        },

        removeLocation: function() {
            this.state.location = null;
            this.renderBadges();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* TAG USERS & COLLABORATOR (LIVE BACKEND SUGGESTIONS)           */
        /* ------------------------------------------------------------- */
        searchTaggableUsers: function(query, targetType = 'tag') {
            const self = this;
            clearTimeout(self.searchUserTimeout);
            self.searchUserTimeout = setTimeout(async () => {
                const listId = targetType === 'tag' ? 'jjTagUsersList' : 'jjCollabUsersList';
                const list = document.getElementById(listId);
                if (!list) return;

                list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">অনুসন্ধান হচ্ছে...</div>';
                try {
                    const res = await fetch(`/api/v1/posts/taggable-users?q=${encodeURIComponent(query)}`, {
                        headers: self.getAuthHeaders()
                    });
                    const data = await res.json();
                    if (data.success && data.data && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(user => {
                            html += `
                                <div class="jj-search-user-item" onclick="window.EnterprisePostComposer.selectUser(${JSON.stringify(user).replace(/"/g, '&quot;')}, '${targetType}')">
                                    <img src="${user.avatar_url || '/images/default-avatar.svg'}" class="jj-search-user-avatar">
                                    <div style="display:flex;flex-direction:column;">
                                        <span style="font-weight:600;font-size:13px;">${user.name}</span>
                                        <span style="font-size:11px;color:var(--jj-text-muted);">@${user.username || user.id}</span>
                                    </div>
                                </div>
                            `;
                        });
                        list.innerHTML = html;
                    } else {
                        list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">কোনো ব্যবহারকারী পাওয়া যায়নি</div>';
                    }
                } catch (e) {
                    list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-danger);">অনুসন্ধান ব্যর্থ হয়েছে</div>';
                }
            }, 250);
        },

        selectUser: function(user, targetType) {
            if (targetType === 'tag') {
                if (!this.state.tagged_users.some(u => u.id === user.id)) {
                    this.state.tagged_users.push(user);
                }
                this.closeDrawer('tagFriends');
            } else if (targetType === 'collab') {
                this.state.collaborator = user;
                this.closeDrawer('collaborator');
            }
            this.renderBadges();
            this.scheduleAutoSave();
        },

        removeTaggedUser: function(userId) {
            this.state.tagged_users = this.state.tagged_users.filter(u => u.id !== userId);
            this.renderBadges();
            this.scheduleAutoSave();
        },

        removeCollaborator: function() {
            this.state.collaborator = null;
            this.renderBadges();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* GROUPS SHARING                                               */
        /* ------------------------------------------------------------- */
        fetchUserGroups: async function() {
            const list = document.getElementById('jjUserGroupsList');
            if (!list) return;
            list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">গ্রুপ লোড হচ্ছে...</div>';

            try {
                const res = await fetch('/api/v1/posts/user-groups', {
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    let html = `
                        <div class="jj-search-user-item" onclick="window.EnterprisePostComposer.selectGroup(null)">
                            <span style="font-weight:600;font-size:13px;">👤 ব্যক্তিগত প্রোফাইল টাইমলাইন</span>
                        </div>
                    `;
                    data.data.forEach(group => {
                        html += `
                            <div class="jj-search-user-item" onclick="window.EnterprisePostComposer.selectGroup(${JSON.stringify(group).replace(/"/g, '&quot;')})">
                                <span style="font-weight:600;font-size:13px;">👥 ${group.name}</span>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                } else {
                    list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">আপনি এখনও কোনো গ্রুপে যুক্ত নন</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-danger);">গ্রুপ তালিকা আনতে সমস্যা হয়েছে</div>';
            }
        },

        selectGroup: function(group) {
            this.state.group = group;
            const targetLabel = document.getElementById('jjGroupTargetLabel');
            const groupNameText = document.getElementById('jjGroupNameText');
            const groupPillText = document.getElementById('jjGroupPillText');

            if (group) {
                if (targetLabel) targetLabel.style.display = 'inline';
                if (groupNameText) groupNameText.textContent = group.name;
                if (groupPillText) groupPillText.textContent = `গ্রুপ: ${group.name}`;
            } else {
                if (targetLabel) targetLabel.style.display = 'none';
                if (groupPillText) groupPillText.textContent = 'পোস্ট গন্তব্য: প্রোফাইল';
            }
            this.closeDrawer('group');
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* POLL BUILDER                                                 */
        /* ------------------------------------------------------------- */
        setupPollBox: function() {
            document.getElementById('jjPollBuilder').style.display = 'flex';
        },

        addPollOption: function() {
            const wrap = document.getElementById('jjPollOptionsWrap');
            if (!wrap) return;
            const currentInputs = wrap.querySelectorAll('input');
            if (currentInputs.length >= 5) {
                alert('সর্বোচ্চ ৫টি অপশন যোগ করা যাবে।');
                return;
            }
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'jj-poll-opt-input';
            input.placeholder = `অপশন ${currentInputs.length + 1}`;
            wrap.appendChild(input);
        },

        removePoll: function() {
            this.state.poll_data = null;
            const builder = document.getElementById('jjPollBuilder');
            if (builder) builder.style.display = 'none';
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* LINK PREVIEW (SSRF PROTECTED VIA BACKEND)                     */
        /* ------------------------------------------------------------- */
        handleTextChange: function(text) {
            const self = this;
            clearTimeout(self.linkDetectTimeout);

            // Auto-detect URL in typed text if no media or preview yet
            if (self.state.media.length === 0 && !self.state.link_preview) {
                const urlMatch = text.match(/(https?:\/\/[^\s]+)/i);
                if (urlMatch && urlMatch[0]) {
                    self.linkDetectTimeout = setTimeout(() => {
                        self.fetchLinkPreview(urlMatch[0]);
                    }, 800);
                }
            }

            // Handle @mention and #hashtag detection
            self.handleMentionsAndHashtags(text);
        },

        fetchLinkPreview: async function(url) {
            try {
                const res = await fetch('/api/v1/posts/link-preview', {
                    method: 'POST',
                    headers: this.getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ url: url })
                });
                const data = await res.json();
                if (data.success && data.data && (data.data.title || data.data.domain)) {
                    this.state.link_preview = data.data;
                    this.renderLinkPreview();
                    this.scheduleAutoSave();
                }
            } catch (e) {
                console.warn('Link preview fetch skipped/failed:', e);
            }
        },

        renderLinkPreview: function() {
            const box = document.getElementById('jjLinkPreviewBox');
            if (!box) return;
            const prev = this.state.link_preview;

            if (!prev) {
                box.style.display = 'none';
                return;
            }

            box.style.display = 'flex';
            const img = document.getElementById('jjLinkPreviewImg');
            const domain = document.getElementById('jjLinkPreviewDomain');
            const title = document.getElementById('jjLinkPreviewTitle');
            const desc = document.getElementById('jjLinkPreviewDesc');

            if (img) {
                if (prev.image) {
                    img.src = prev.image;
                    img.style.display = 'block';
                } else {
                    img.style.display = 'none';
                }
            }
            if (domain) domain.textContent = prev.domain || '';
            if (title) title.textContent = prev.title || prev.url || '';
            if (desc) desc.textContent = prev.description || '';
        },

        removeLinkPreview: function() {
            this.state.link_preview = null;
            this.renderLinkPreview();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* MENTIONS & HASHTAGS AUTOCOMPLETE                             */
        /* ------------------------------------------------------------- */
        handleMentionsAndHashtags: function(text) {
            const textarea = document.getElementById('jjComposerText');
            const popup = document.getElementById('jjMentionPopup');
            if (!textarea || !popup) return;

            const cursorPos = textarea.selectionStart;
            const textBefore = text.slice(0, cursorPos);
            const mentionMatch = textBefore.match(/@([a-zA-Z0-9_]{1,20})$/);
            const hashtagMatch = textBefore.match(/#([a-zA-Z0-9_\u0980-\u09FF]{1,20})$/);

            if (mentionMatch) {
                const query = mentionMatch[1];
                clearTimeout(this.mentionDebounceTimeout);
                this.mentionDebounceTimeout = setTimeout(() => {
                    this.showMentionSuggestions(query);
                }, 200);
            } else if (hashtagMatch) {
                const tag = hashtagMatch[1];
                this.showHashtagSuggestions(tag);
            } else {
                popup.style.display = 'none';
            }
        },

        showMentionSuggestions: async function(query) {
            const popup = document.getElementById('jjMentionPopup');
            if (!popup) return;

            try {
                const res = await fetch(`/api/v1/posts/taggable-users?q=${encodeURIComponent(query)}`, {
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(user => {
                        html += `
                            <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.insertMention('${user.username || user.id}')">
                                <img src="${user.avatar_url || '/images/default-avatar.svg'}" style="width:20px;height:20px;border-radius:50%;">
                                <span>${user.name} (@${user.username || user.id})</span>
                            </div>
                        `;
                    });
                    popup.innerHTML = html;
                    popup.style.display = 'block';
                } else {
                    popup.style.display = 'none';
                }
            } catch (e) {
                popup.style.display = 'none';
            }
        },

        showHashtagSuggestions: function(query) {
            const popup = document.getElementById('jjMentionPopup');
            if (!popup) return;
            const popularTags = ['bondhoo', 'bangladesh', 'tech', 'coding', 'lifestyle', 'community', 'news', 'jugajug'];
            const matched = popularTags.filter(t => t.toLowerCase().includes(query.toLowerCase()));

            if (matched.length > 0) {
                let html = '';
                matched.forEach(t => {
                    html += `
                        <div class="jj-suggest-item" onclick="window.EnterprisePostComposer.insertHashtag('${t}')">
                            <span>#${t}</span>
                        </div>
                    `;
                });
                popup.innerHTML = html;
                popup.style.display = 'block';
            } else {
                popup.style.display = 'none';
            }
        },

        insertMention: function(username) {
            const textarea = document.getElementById('jjComposerText');
            if (!textarea) return;
            const val = textarea.value;
            const cursorPos = textarea.selectionStart;
            const before = val.slice(0, cursorPos).replace(/@[a-zA-Z0-9_]*$/, `@${username} `);
            const after = val.slice(cursorPos);
            textarea.value = before + after;
            textarea.selectionStart = textarea.selectionEnd = before.length;
            this.state.content = textarea.value;
            document.getElementById('jjMentionPopup').style.display = 'none';
            textarea.focus();
            this.scheduleAutoSave();
        },

        insertHashtag: function(tag) {
            const textarea = document.getElementById('jjComposerText');
            if (!textarea) return;
            const val = textarea.value;
            const cursorPos = textarea.selectionStart;
            const before = val.slice(0, cursorPos).replace(/#[a-zA-Z0-9_\u0980-\u09FF]*$/, `#${tag} `);
            const after = val.slice(cursorPos);
            textarea.value = before + after;
            textarea.selectionStart = textarea.selectionEnd = before.length;
            this.state.content = textarea.value;
            document.getElementById('jjMentionPopup').style.display = 'none';
            textarea.focus();
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* BADGES (TAGGED, COLLABORATOR, FEELING, LOCATION)              */
        /* ------------------------------------------------------------- */
        renderBadges: function() {
            const container = document.getElementById('jjBadgesContainer');
            if (!container) return;
            let html = '';

            if (this.state.feeling_activity) {
                html += `
                    <div class="jj-active-badge">
                        <span>${this.state.feeling_activity}</span>
                        <button type="button" onclick="window.EnterprisePostComposer.removeFeeling()" title="মুছুন">✕</button>
                    </div>
                `;
            }

            if (this.state.location) {
                html += `
                    <div class="jj-active-badge">
                        <span>📍 ${this.state.location}</span>
                        <button type="button" onclick="window.EnterprisePostComposer.removeLocation()" title="মুছুন">✕</button>
                    </div>
                `;
            }

            if (this.state.collaborator) {
                html += `
                    <div class="jj-active-badge">
                        <span>🤝 সহযোগী: ${this.state.collaborator.name}</span>
                        <button type="button" onclick="window.EnterprisePostComposer.removeCollaborator()" title="মুছুন">✕</button>
                    </div>
                `;
            }

            if (this.state.tagged_users && this.state.tagged_users.length > 0) {
                this.state.tagged_users.forEach(u => {
                    html += `
                        <div class="jj-active-badge">
                            <span>👤 @${u.name}</span>
                            <button type="button" onclick="window.EnterprisePostComposer.removeTaggedUser(${u.id})" title="মুছুন">✕</button>
                        </div>
                    `;
                });
            }

            container.innerHTML = html;
        },

        /* ------------------------------------------------------------- */
        /* ADVANCED SETTINGS (SCHEDULE, STORY, WARNING)                  */
        /* ------------------------------------------------------------- */
        toggleWarningBox: function(checked) {
            const wrap = document.getElementById('jjContentWarningWrap');
            if (wrap) wrap.style.display = checked ? 'block' : 'none';
            if (!checked) {
                document.getElementById('jjContentWarningInput').value = '';
                this.state.content_warning = null;
            }
        },

        saveSchedule: function() {
            const dtInput = document.getElementById('jjScheduleDateTime');
            if (!dtInput || !dtInput.value) {
                alert('অনুগ্রহ করে সঠিক তারিখ ও সময় নির্বাচন করুন।');
                return;
            }
            const date = new Date(dtInput.value);
            if (date <= new Date()) {
                alert('সিডিউলের তারিখ ও সময় ভবিষ্যৎকালীন হতে হবে।');
                return;
            }
            this.state.scheduled_at = date.toISOString();
            const badge = document.getElementById('jjScheduleBadge');
            const badgeText = document.getElementById('jjScheduleBadgeText');
            if (badge) badge.style.display = 'inline-flex';
            if (badgeText) badgeText.textContent = `সিডিউল: ${date.toLocaleDateString('bn-BD', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;

            // Update publish button text
            const pubText = document.getElementById('jjPublishBtnText');
            if (pubText) pubText.textContent = 'পোস্ট সিডিউল করুন ⏰';

            this.closeDrawer('schedule');
            this.scheduleAutoSave();
        },

        clearSchedule: function() {
            this.state.scheduled_at = null;
            document.getElementById('jjScheduleBadge').style.display = 'none';
            document.getElementById('jjPublishBtnText').textContent = 'পোস্ট প্রকাশ করুন 🚀';
            this.closeDrawer('schedule');
            this.scheduleAutoSave();
        },

        /* ------------------------------------------------------------- */
        /* REAL DEBOUNCED AUTO-SAVE & DRAFT SYSTEM                       */
        /* ------------------------------------------------------------- */
        scheduleAutoSave: function() {
            const self = this;
            clearTimeout(self.autoSaveTimeout);
            const statusBadge = document.getElementById('jjDraftStatusBadge');
            const statusText = document.getElementById('jjDraftStatusText');
            if (statusBadge) statusBadge.style.display = 'inline-flex';
            if (statusText) statusText.textContent = 'সংরক্ষণ হচ্ছে...';

            self.autoSaveTimeout = setTimeout(() => {
                self.saveDraft();
            }, 2500);
        },

        saveDraftManually: function() {
            this.saveDraft(true);
        },

        saveDraft: async function(isManual = false) {
            const self = this;
            const content = self.state.content.trim();
            const mediaIds = self.state.media.filter(m => m.id).map(m => m.id);

            // Don't auto-save if completely empty
            if (!content && mediaIds.length === 0 && !self.state.poll_data) {
                const statusBadge = document.getElementById('jjDraftStatusBadge');
                if (statusBadge) statusBadge.style.display = 'none';
                return;
            }

            const payload = {
                draft_id: self.state.draft_id,
                content: content,
                audience: self.state.audience,
                type: self.state.type,
                location: self.state.location,
                feeling_activity: self.state.feeling_activity,
                media_ids: mediaIds,
                background_style: self.state.background_style,
                collaborator_id: self.state.collaborator?.id || null,
                tagged_user_ids: self.state.tagged_users.map(u => u.id),
                group_id: self.state.group?.id || null,
                poll_data: self.getPollDataPayload(),
                link_preview: self.state.link_preview,
                is_ai_generated: document.getElementById('jjToggleAi')?.checked || false,
                comments_disabled: document.getElementById('jjToggleComments')?.checked || false,
                content_warning: document.getElementById('jjContentWarningInput')?.value.trim() || null,
                shared_to_story: document.getElementById('jjToggleStory')?.checked || false,
                scheduled_at: self.state.scheduled_at,
            };

            try {
                const res = await fetch('/api/v1/posts/drafts', {
                    method: 'POST',
                    headers: self.getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success && data.data) {
                    self.state.draft_id = data.data.id;
                    const statusText = document.getElementById('jjDraftStatusText');
                    if (statusText) statusText.textContent = '✓ সংরক্ষিত';
                    if (isManual) alert('খসড়া সফলভাবে সংরক্ষিত হয়েছে!');
                }
            } catch (e) {
                const statusText = document.getElementById('jjDraftStatusText');
                if (statusText) statusText.textContent = 'সংরক্ষণ ব্যর্থ';
            }
        },

        fetchLatestDraft: async function() {
            try {
                const res = await fetch('/api/v1/posts/drafts', {
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    const latest = data.data[0];
                    // If composer currently empty, prompt or auto-restore
                    if (!this.state.content && this.state.media.length === 0) {
                        this.restoreDraftData(latest);
                    }
                }
            } catch (e) {
                console.warn('Drafts check skipped:', e);
            }
        },

        toggleDraftsList: async function() {
            const drawer = document.getElementById('jjDraftsDrawer');
            if (drawer.style.display === 'flex') {
                this.closeDrawer('drafts');
                return;
            }
            this.openDrawer('drafts');
            const list = document.getElementById('jjDraftsItemsList');
            if (!list) return;

            list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">খসড়া লোড হচ্ছে...</div>';

            try {
                const res = await fetch('/api/v1/posts/drafts', {
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                if (data.success && data.data && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(d => {
                        const snippet = d.content ? d.content.substring(0, 45) + '...' : '(ছবি/মিডিয়া খসড়া)';
                        const date = new Date(d.updated_at).toLocaleDateString('bn-BD', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        html += `
                            <div class="jj-search-user-item" style="justify-content: space-between;">
                                <div onclick="window.EnterprisePostComposer.restoreDraftData(${JSON.stringify(d).replace(/"/g, '&quot;')})" style="flex:1;">
                                    <div style="font-weight:600;font-size:13px;">${snippet}</div>
                                    <div style="font-size:11px;color:var(--jj-text-muted);">${date}</div>
                                </div>
                                <button type="button" class="jj-cell-btn danger" style="width:24px;height:24px;" onclick="window.EnterprisePostComposer.deleteDraft(${d.id})">✕</button>
                            </div>
                        `;
                    });
                    list.innerHTML = html;
                } else {
                    list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-text-muted);">কোনো সংরক্ষিত খসড়া নেই</div>';
                }
            } catch (e) {
                list.innerHTML = '<div style="padding:10px;text-align:center;font-size:12px;color:var(--jj-danger);">খসড়া লোড করতে ব্যর্থ</div>';
            }
        },

        restoreDraftData: function(d) {
            this.state.draft_id = d.id;
            this.state.content = d.content || '';
            const textarea = document.getElementById('jjComposerText');
            if (textarea) {
                textarea.value = this.state.content;
                this.autoResizeTextarea(textarea);
            }
            if (d.audience) this.setAudience(d.audience);
            if (d.background_style) this.setCanvasStyle(d.background_style);
            if (d.feeling_activity) this.state.feeling_activity = d.feeling_activity;
            if (d.location) this.state.location = d.location;
            if (d.link_preview) {
                this.state.link_preview = d.link_preview;
                this.renderLinkPreview();
            }

            this.renderBadges();
            this.closeDrawer('drafts');
            const statusText = document.getElementById('jjDraftStatusText');
            if (statusText) statusText.textContent = '✓ খসড়া রিস্টোর করা হয়েছে';
        },

        deleteDraft: async function(draftId) {
            try {
                await fetch(`/api/v1/posts/drafts/${draftId}`, {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                });
                this.toggleDraftsList();
            } catch (e) {
                console.warn('Failed to delete draft:', e);
            }
        },

        getPollDataPayload: function() {
            const builder = document.getElementById('jjPollBuilder');
            if (!builder || builder.style.display === 'none') return null;

            const question = document.getElementById('jjPollQuestion')?.value.trim();
            const optionInputs = document.querySelectorAll('#jjPollOptionsWrap input');
            const options = [];

            optionInputs.forEach((inp, idx) => {
                if (inp.value.trim()) {
                    options.push({ id: idx + 1, text: inp.value.trim() });
                }
            });

            if (!question || options.length < 2) return null;

            const duration = document.getElementById('jjPollDuration')?.value || 24;
            return {
                question: question,
                options: options,
                duration_hours: parseInt(duration, 10)
            };
        },

        /* ------------------------------------------------------------- */
        /* POST PUBLISHING (AUTHENTICATED, VALIDATED, REALTIME)          */
        /* ------------------------------------------------------------- */
        submitPost: async function() {
            const self = this;
            if (self.isSubmitting) return;

            // Check if any media still uploading
            const stillUploading = self.state.media.some(m => m.isUploading);
            if (stillUploading) {
                alert('মিডিয়া আপলোড সম্পন্ন হওয়া পর্যন্ত অনুগ্রহ করে অপেক্ষা করুন।');
                return;
            }

            const content = self.state.content.trim();
            const mediaIds = self.state.media.filter(m => m.id).map(m => m.id);
            const pollData = self.getPollDataPayload();

            // Validate that post is not completely empty
            if (!content && mediaIds.length === 0 && !pollData) {
                alert('পোস্ট করার জন্য কিছু লিখুন অথবা ছবি/ভিডিও বা পোল যোগ করুন।');
                document.getElementById('jjComposerText')?.focus();
                return;
            }

            self.isSubmitting = true;
            const publishBtn = document.getElementById('jjPublishBtn');
            const publishBtnText = document.getElementById('jjPublishBtnText');
            if (publishBtn) publishBtn.disabled = true;
            if (publishBtnText) publishBtnText.textContent = self.state.scheduled_at ? 'সিডিউল হচ্ছে...' : 'প্রকাশ হচ্ছে...';

            // Prepare media metadata (captions, alt text, filters)
            const mediaMeta = {};
            self.state.media.forEach(m => {
                if (m.id && m.meta) {
                    mediaMeta[m.id] = m.meta;
                }
            });

            // Determine post type
            let postType = 'text';
            if (mediaIds.length > 0) postType = 'media';
            else if (pollData) postType = 'poll';
            else if (self.state.link_preview) postType = 'link';

            const payload = {
                content: content,
                audience: self.state.audience,
                type: postType,
                location: self.state.location,
                feeling_activity: self.state.feeling_activity,
                media_ids: mediaIds,
                media_meta: Object.keys(mediaMeta).length > 0 ? mediaMeta : null,
                poll_data: pollData,
                link_preview: self.state.link_preview,
                collaborator_id: self.state.collaborator?.id || null,
                tagged_user_ids: self.state.tagged_users.map(u => u.id),
                group_id: self.state.group?.id || null,
                background_style: self.state.background_style,
                is_ai_generated: document.getElementById('jjToggleAi')?.checked || false,
                comments_disabled: document.getElementById('jjToggleComments')?.checked || false,
                content_warning: document.getElementById('jjContentWarningInput')?.value.trim() || null,
                share_to_story: document.getElementById('jjToggleStory')?.checked || false,
                scheduled_at: self.state.scheduled_at,
                status: self.state.scheduled_at ? 'scheduled' : 'published',
            };

            try {
                const res = await fetch('/api/v1/posts', {
                    method: 'POST',
                    headers: self.getAuthHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.status === 201 || (data.success && data.data)) {
                    // Success! Reset state and close modal
                    self.resetState();
                    self.close();

                    const successMsg = self.state.scheduled_at
                        ? 'পোস্টটি সফলভাবে সিডিউল করা হয়েছে! ⏰'
                        : 'পোস্টটি সফলভাবে প্রকাশিত হয়েছে! 🚀';

                    if (typeof showToast === 'function') {
                        showToast(successMsg);
                    } else {
                        alert(successMsg);
                    }

                    // Refresh feed if feed fetcher exists
                    if (typeof fetchFeedPosts === 'function') {
                        fetchFeedPosts();
                    } else if (typeof loadTimelinePosts === 'function') {
                        loadTimelinePosts();
                    }
                } else {
                    const errMsg = data.message || 'পোস্ট প্রকাশ করতে সমস্যা হয়েছে।';
                    alert(errMsg);
                }
            } catch (err) {
                console.error('Post creation error:', err);
                alert('নেটওয়ার্ক সংযোগ ত্রুটি। খসড়া সংরক্ষণ করা হয়েছে, দয়া করে পুনরায় চেষ্টা করুন।');
            } finally {
                self.isSubmitting = false;
                if (publishBtn) publishBtn.disabled = false;
                if (publishBtnText) {
                    publishBtnText.textContent = self.state.scheduled_at ? 'পোস্ট সিডিউল করুন ⏰' : 'পোস্ট প্রকাশ করুন 🚀';
                }
            }
        },

        resetState: function() {
            this.state = {
                content: '',
                media: [],
                audience: 'public',
                type: 'text',
                background_style: null,
                location: null,
                feeling_activity: null,
                tagged_users: [],
                collaborator: null,
                group: null,
                poll_data: null,
                link_preview: null,
                is_ai_generated: false,
                comments_disabled: false,
                content_warning: null,
                share_to_story: false,
                scheduled_at: null,
                draft_id: null,
            };

            const textarea = document.getElementById('jjComposerText');
            if (textarea) {
                textarea.value = '';
                this.autoResizeTextarea(textarea);
            }
            this.setCanvasStyle(null);
            this.setAudience('public');
            this.renderMediaGrid();
            this.renderBadges();
            this.renderLinkPreview();
            this.removePoll();
            this.clearSchedule();
            this.closeAllDrawers();
            const draftBadge = document.getElementById('jjDraftStatusBadge');
            if (draftBadge) draftBadge.style.display = 'none';
        }
    };

    // Auto-initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.EnterprisePostComposer.init());
    } else {
        window.EnterprisePostComposer.init();
    }

    // Bridge with existing legacy functions for backwards compatibility:
    window.openCreatePostModal = function(mode = null) {
        window.EnterprisePostComposer.open(mode);
    };
    window.closeCreatePostModal = function() {
        window.EnterprisePostComposer.close();
    };
    window.openPostModal = function(mode = null) {
        window.EnterprisePostComposer.open(mode);
    };
})();
</script>
