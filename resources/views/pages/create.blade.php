@extends('layouts.app')

@section('title', 'নতুন এন্টারপ্রাইজ পেইজ তৈরি করুন — Bondhoo')

@section('styles')
<style>
    :root {
        --primary-brand: #059669;
        --primary-brand-hover: #047857;
        --primary-gradient: linear-gradient(135deg, #059669 0%, #0284c7 100%);
    }

    .stepper-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 28px;
        position: relative;
        overflow-x: auto;
        padding-bottom: 8px;
    }

    .stepper-container::-webkit-scrollbar {
        height: 4px;
    }
    .stepper-container::-webkit-scrollbar-thumb {
        background: var(--fb-border);
        border-radius: 4px;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 80px;
        cursor: pointer;
        position: relative;
        z-index: 2;
        user-select: none;
    }

    .step-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--fb-card);
        border: 2px solid var(--fb-border);
        color: var(--fb-text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 6px;
        transition: all 0.25s ease;
    }

    .step-item.active .step-circle {
        background: #059669;
        border-color: #059669;
        color: white;
        box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.2);
    }

    .step-item.completed .step-circle {
        background: #059669;
        border-color: #059669;
        color: white;
    }

    .step-label {
        font-size: 12px;
        color: var(--fb-text-secondary);
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
    }

    .step-item.active .step-label {
        color: #059669;
        font-weight: 700;
    }

    .type-card {
        border: 1px solid var(--fb-border);
        border-radius: 12px;
        padding: 18px 16px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--fb-card);
        display: flex;
        flex-direction: column;
        gap: 8px;
        position: relative;
    }

    .type-card:hover {
        border-color: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);
    }

    .type-card.selected {
        border-color: #059669;
        background: rgba(5, 150, 105, 0.05);
        box-shadow: 0 0 0 2px #059669;
    }

    .type-card-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #059669;
        color: white;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .type-card.selected .type-card-badge {
        display: flex;
    }

    .preview-card-sticky {
        position: sticky;
        top: 80px;
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 14px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    .preview-cover {
        height: 120px;
        background: linear-gradient(135deg, #059669, #0284c7);
        position: relative;
        background-size: cover;
        background-position: center;
    }

    .preview-avatar-box {
        position: absolute;
        bottom: -28px;
        left: 20px;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        border: 3px solid var(--fb-card);
        background: #e2e8f0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .preview-avatar-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .form-section-card {
        background: var(--fb-card);
        border: 1px solid var(--fb-border);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .btn-brand-primary {
        background: #059669;
        color: white;
        padding: 10px 22px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .btn-brand-primary:hover {
        background: #047857;
    }

    .btn-brand-secondary {
        background: var(--fb-bg);
        color: var(--fb-text);
        border: 1px solid var(--fb-border);
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-brand-secondary:hover {
        background: var(--fb-card-hover);
    }

    .availability-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        margin-top: 6px;
        font-weight: 500;
    }

    .availability-indicator.success {
        color: #059669;
    }

    .availability-indicator.error {
        color: #dc2626;
    }

    .availability-indicator.checking {
        color: #0284c7;
    }

    .media-dropzone {
        border: 2px dashed var(--fb-border);
        border-radius: 10px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;
    }

    .media-dropzone:hover {
        border-color: #059669;
        background: rgba(5, 150, 105, 0.02);
    }

    .hours-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--fb-border);
        gap: 12px;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')
<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 24px 16px;">
    
    <!-- Top Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 800; color: var(--fb-text); margin: 0 0 4px 0; display: flex; align-items: center; gap: 10px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                <span>এন্টারপ্রাইজ পেইজ ক্রিয়েশন অপারেটিং সিস্টেম</span>
            </h1>
            <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">
                আপনার ব্যবসা, ব্র্যান্ড, ক্রিয়েটর প্ল্যাটফর্ম বা সংগঠনের জন্য একটি প্রফেশনাল সোশ্যাল পেইজ প্রতিষ্ঠা করুন।
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <div id="autosaveStatusBadge" style="font-size: 13px; color: var(--fb-text-secondary); display: flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span id="autosaveText">অটোসেভ সক্রিয়</span>
            </div>
            <button type="button" class="btn-brand-secondary" onclick="manualSaveDraft()" id="btnSaveDraft">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline></svg>
                <span>ড্রাফট সেভ</span>
            </button>
            <a href="{{ route('pages.index') }}" class="btn-brand-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>বাতিল</span>
            </a>
        </div>
    </div>

    <!-- Quota & Draft Notification Alert -->
    @if($latestDraft)
    <div id="resumeDraftBanner" style="background: rgba(2, 132, 199, 0.08); border: 1px solid rgba(2, 132, 199, 0.3); border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span style="font-size: 14px; color: var(--fb-text);">আপনার একটি সংরক্ষিত ড্রাফট রয়েছে: <strong>{{ $latestDraft->title ?? 'Untitled Page' }}</strong> (সর্বশেষ সেভ: {{ $latestDraft->updated_at->diffForHumans() }})</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-brand-primary" style="padding: 6px 14px; font-size: 13px;" onclick="resumeDraft({{ $latestDraft->id }})">ড্রাফট চালু করুন</button>
            <button type="button" class="btn-brand-secondary" style="padding: 6px 12px; font-size: 13px;" onclick="discardDraft({{ $latestDraft->id }})">মুছে ফেলুন</button>
        </div>
    </div>
    @endif

    <!-- Stepper Navigation -->
    <div class="stepper-container" id="formStepper">
        <div class="step-item active" onclick="jumpToStep(1)" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">পেইজ টাইপ</div>
        </div>
        <div class="step-item" onclick="jumpToStep(2)" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">ক্যাটাগরি</div>
        </div>
        <div class="step-item" onclick="jumpToStep(3)" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">পরিচয় ও @নাম</div>
        </div>
        <div class="step-item" onclick="jumpToStep(4)" data-step="4">
            <div class="step-circle">4</div>
            <div class="step-label">ইন্ডাস্ট্রি তথ্য</div>
        </div>
        <div class="step-item" onclick="jumpToStep(5)" data-step="5">
            <div class="step-circle">5</div>
            <div class="step-label">যোগাযোগ ও সাইট</div>
        </div>
        <div class="step-item" onclick="jumpToStep(6)" data-step="6">
            <div class="step-circle">6</div>
            <div class="step-label">লোকেশন ও ব্রাঞ্চ</div>
        </div>
        <div class="step-item" onclick="jumpToStep(7)" data-step="7">
            <div class="step-circle">7</div>
            <div class="step-label">কর্মঘণ্টা</div>
        </div>
        <div class="step-item" onclick="jumpToStep(8)" data-step="8">
            <div class="step-circle">8</div>
            <div class="step-label">অ্যাকশন ও সোশ্যাল</div>
        </div>
        <div class="step-item" onclick="jumpToStep(9)" data-step="9">
            <div class="step-circle">9</div>
            <div class="step-label">ছবি ও ব্যানার</div>
        </div>
        <div class="step-item" onclick="jumpToStep(10)" data-step="10">
            <div class="step-circle">10</div>
            <div class="step-label">প্রাইভেসি ও রিভিউ</div>
        </div>
    </div>

    <!-- Main Grid: Form Formats & Live Dynamic Preview -->
    <div style="display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start;">
        
        <!-- Form Left Column -->
        <div id="formStepsContainer">
            
            <!-- STEP 1: Page Type Selection -->
            <div class="form-section-card step-panel" id="stepPanel1">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">১. আপনার পেইজের ধরন (Page Type) নির্বাচন করুন</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">আপনার প্রতিষ্ঠানের সাথে মানানসই পেইজ টাইপ সিলেক্ট করুন যাতে প্রাসঙ্গিক ফিচার সক্রিয় করা যায়।</p>
                </div>

                <!-- Page Type Search Input -->
                <div style="margin-bottom: 16px;">
                    <div style="position: relative;">
                        <input type="text" id="typeSearchInput" oninput="filterPageTypes()" placeholder="পেইজ টাইপ খুঁজুন (যেমন: Restaurant, Brand, NGO, Creator...)" style="width: 100%; padding: 10px 14px 10px 38px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 12px; color: var(--fb-text-secondary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>
                </div>

                <!-- Types Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;" id="pageTypesGrid">
                    @foreach($pageTypes as $pType)
                    <div class="type-card" onclick="selectPageType({{ $pType->id }}, '{{ addslashes($pType->name) }}', '{{ $pType->slug }}')" data-type-id="{{ $pType->id }}" data-type-name="{{ strtolower($pType->name) }}" data-type-desc="{{ strtolower($pType->description) }}">
                        <div class="type-card-badge">✓</div>
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(5, 150, 105, 0.1); color: #059669; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                        </div>
                        <strong style="font-size: 15px; color: var(--fb-text);">{{ $pType->name }}</strong>
                        <p style="font-size: 12px; color: var(--fb-text-secondary); margin: 0; line-height: 1.4;">{{ $pType->description }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- STEP 2: Category & Subcategory -->
            <div class="form-section-card step-panel" id="stepPanel2" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">২. ক্যাটাগরি ও সাব-ক্যাটাগরি নির্ধারণ</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">সঠিক ক্যাটাগরি আপনার পেইজটিকে দর্শকদের কাছে দ্রুত পৌঁছাতে সাহায্য করবে।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">প্রধান ক্যাটাগরি (Primary Category) <span style="color: #dc2626;">*</span></label>
                        <select id="primaryCategorySelect" onchange="onCategoryChanged()" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            <option value="">-- প্রথমে একটি ক্যাটাগরি নির্বাচন করুন --</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">উপ-ক্যাটাগরি (Subcategory)</label>
                        <select id="subcategorySelect" onchange="onSubcategoryChanged()" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            <option value="">-- প্রযোজ্য হলে নির্বাচন করুন --</option>
                        </select>
                    </div>

                    <!-- Popular Category Badges -->
                    <div id="popularCategoriesBox" style="margin-top: 10px;">
                        <span style="font-size: 12px; color: var(--fb-text-secondary); display: block; margin-bottom: 6px;">জনপ্রিয় ক্যাটাগরি:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;" id="popularBadgesContainer">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Identity & @Username -->
            <div class="form-section-card step-panel" id="stepPanel3" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৩. পেইজের নাম, ইউজারনেম ও পরিচয়</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">আপনার অফিশিয়াল পেইজ নাম এবং ইউনিক @ইউজারনেম নির্ধারণ করুন।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">পেইজের নাম (Page Name) <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="pageNameInput" oninput="onPageNameChanged()" maxlength="100" placeholder="যেমন: ঢাকা টেক সলিউশনস" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        <span style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px; display: block;">আপনার ব্যবসা বা ব্র্যান্ডের পরিচিত আসল নাম ব্যবহার করুন।</span>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">পেইজ ইউজারনেম (@Username) <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 12px; font-weight: 700; color: var(--fb-text-secondary); font-size: 15px;">@</span>
                            <input type="text" id="pageUsernameInput" oninput="onUsernameInputChanged()" maxlength="60" placeholder="yourbrand" style="width: 100%; padding: 12px 12px 12px 32px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div id="usernameIndicator" class="availability-indicator" style="display: none;"></div>
                        <div style="font-size: 12px; color: var(--fb-text-secondary); margin-top: 4px;">
                            লাইভ প্রিভিউ: <strong style="color: #059669;">bondhoo.com/pages/@<span id="previewUrlSlug">yourbrand</span></strong>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">সংক্ষিপ্ত পরিচিতি (Short Bio) <span style="color: #dc2626;">*</span></label>
                        <textarea id="pageShortDescInput" oninput="onShortDescChanged()" rows="2" maxlength="300" placeholder="১-২ লাইনে পেইজের মূল উদ্দেশ্য ও সেবা সম্পর্কে লিখুন..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">সম্পূর্ণ বিবরণ (Detailed About)</label>
                        <textarea id="pageFullAboutInput" oninput="onFullAboutChanged()" rows="4" maxlength="2000" placeholder="আপনার প্রতিষ্ঠানের ইতিহাস, অভিজ্ঞতা, অর্জন এবং কার্যক্রমের বিস্তারিত বিবরণ..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;"></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Industry-Specific Dynamic Fields -->
            <div class="form-section-card step-panel" id="stepPanel4" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৪. ইন্ডাস্ট্রি ও ব্যবসা সম্পর্কিত বিশেষ তথ্য</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">আপনার সিলেক্ট করা ক্যাটাগরির জন্য বিশেষভাবে নির্ধারিত ফিল্ডসমূহ পূরণ করুন।</p>
                </div>

                <div id="dynamicFieldsContainer" style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Dynamic fields rendered via JS based on selected category/type -->
                </div>

                <!-- Generic Business Registration Information -->
                <div id="generalBusinessSection" style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed var(--fb-border);">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--fb-text); margin: 0 0 14px 0;">অফিশিয়াল ব্যবসা নিবন্ধন (ঐচ্ছিক)</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: var(--fb-text);">আইনি নাম (Legal Business Name)</label>
                            <input type="text" id="legalNameInput" placeholder="নিবন্ধিত আইনি নাম" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: var(--fb-text);">প্রতিষ্ঠার সাল (Year Founded)</label>
                            <input type="number" id="yearFoundedInput" placeholder="যেমন: 2020" min="1800" max="2099" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: var(--fb-text);">রেজিস্ট্রেশন নম্বর / ট্রেড লাইসেন্স</label>
                            <input type="text" id="regNumberInput" placeholder="Trade License / Registration No" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: var(--fb-text);">ট্যাক্স / ভ্যাট আইডি (TIN / VAT ID)</label>
                            <input type="text" id="taxIdInput" placeholder="TIN / BIN / VAT" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: Contact & Official Website -->
            <div class="form-section-card step-panel" id="stepPanel5" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৫. যোগাযোগ ও অফিসিয়াল ওয়েবসাইট</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">গ্রাহক ও দর্শকদের সাথে সংযোগ স্থাপনের অফিশিয়াল তথ্য প্রদান করুন।</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">পাবলিক ইমেইল (Public Email)</label>
                        <input type="email" id="publicEmailInput" oninput="updatePreview()" placeholder="contact@yourdomain.com" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">ফোন নম্বর (Phone Number)</label>
                        <input type="text" id="phoneInput" oninput="updatePreview()" placeholder="+8801700000000" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">হোয়াটসঅ্যাপ নম্বর (WhatsApp)</label>
                        <input type="text" id="whatsappInput" placeholder="+8801800000000" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">অফিশিয়াল ওয়েবসাইট (Official Website)</label>
                        <input type="url" id="websiteInput" oninput="updatePreview()" placeholder="https://yourbrand.com" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <!-- STEP 6: Location & Branches -->
            <div class="form-section-card step-panel" id="stepPanel6" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৬. ঠিকানা ও শাখা কার্যালয় (Locations & Branches)</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">আপনার প্রধান কার্যালয় ও ব্রাঞ্চ অফিসের অবস্থান যুক্ত করুন।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">রাস্তা ও ভবনের ঠিকানা (Street Address)</label>
                        <input type="text" id="streetAddressInput" oninput="updatePreview()" placeholder="যেমন: হাউজ #১২, রোড #৫, গুলশান-২" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">শহর (City)</label>
                            <input type="text" id="cityInput" oninput="updatePreview()" placeholder="ঢাকা / চট্টগ্রাম / সিলেট" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">জেলা / বিভাগ (State / District)</label>
                            <input type="text" id="stateInput" placeholder="ঢাকা" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">পোস্টাল কোড (Zip Code)</label>
                            <input type="text" id="zipCodeInput" placeholder="1212" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Enterprise Multi-Branch Section -->
                    <div style="margin-top: 14px; padding-top: 16px; border-top: 1px dashed var(--fb-border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong style="font-size: 15px; color: var(--fb-text);">অতিরিক্ত শাখা কার্যালয় (Multiple Branches)</strong>
                            <button type="button" class="btn-brand-secondary" style="padding: 6px 12px; font-size: 13px;" onclick="addNewBranchRow()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>+ শাখা যোগ করুন</span>
                            </button>
                        </div>
                        <div id="branchesListContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Dynamically added branches -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 7: Business Hours -->
            <div class="form-section-card step-panel" id="stepPanel7" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৭. কর্মঘণ্টা ও সাপ্তাহিক সময়সূচি (Business Hours)</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">আপনার প্রতিষ্ঠান কখন খোলা থাকে তা নির্ধারণ করুন।</p>
                </div>

                <div id="businessHoursRowsContainer" style="display: flex; flex-direction: column;">
                    @php
                        $daysOfWeek = [
                            'saturday' => 'শনিবার (Saturday)',
                            'sunday' => 'রবিবার (Sunday)',
                            'monday' => 'সোমবার (Monday)',
                            'tuesday' => 'মঙ্গলবার (Tuesday)',
                            'wednesday' => 'বুধবার (Wednesday)',
                            'thursday' => 'বৃহস্পতিবার (Thursday)',
                            'friday' => 'শুক্রবার (Friday)',
                        ];
                    @endphp

                    @foreach($daysOfWeek as $dayKey => $dayLabel)
                    <div class="hours-row" data-day="{{ $dayKey }}">
                        <div style="width: 180px; font-weight: 600; font-size: 14px; color: var(--fb-text);">
                            {{ $dayLabel }}
                        </div>
                        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                <input type="radio" name="hours_mode_{{ $dayKey }}" value="open" checked onchange="toggleHoursRow('{{ $dayKey }}', 'open')"> খোলা
                            </label>
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                <input type="radio" name="hours_mode_{{ $dayKey }}" value="24h" onchange="toggleHoursRow('{{ $dayKey }}', '24h')"> ২৪ ঘণ্টা খোলা
                            </label>
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                <input type="radio" name="hours_mode_{{ $dayKey }}" value="closed" onchange="toggleHoursRow('{{ $dayKey }}', 'closed')"> বন্ধ
                            </label>

                            <div class="time-inputs-box" id="timeBox_{{ $dayKey }}" style="display: flex; align-items: center; gap: 6px;">
                                <input type="time" class="open-time-input" value="09:00" style="padding: 6px 8px; border-radius: 6px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text);">
                                <span>-</span>
                                <input type="time" class="close-time-input" value="18:00" style="padding: 6px 8px; border-radius: 6px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text);">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- STEP 8: CTA & Social Links -->
            <div class="form-section-card step-panel" id="stepPanel8" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৮. কল-টু-অ্যাকশন (CTA) বোতাম ও সোশ্যাল মিডিয়া</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">দর্শকদের সরাসরি মেসেজ, কল বা ওয়েবসাইটে নেওয়ার বোতাম কনফিগার করুন।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">মূল অ্যাকশন বাটন (Action Button)</label>
                        <select id="ctaTypeSelect" onchange="onCtaChanged()" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            <option value="contact_us">Contact Us (যোগাযোগ করুন)</option>
                            <option value="send_message" selected>Send Message (মেসেজ পাঠান)</option>
                            <option value="call_now">Call Now (কল করুন)</option>
                            <option value="whatsapp">WhatsApp (হোয়াটসঅ্যাপ চ্যাট)</option>
                            <option value="book_now">Book Now (বুক করুন)</option>
                            <option value="shop_now">Shop Now (কেনাকাটা করুন)</option>
                            <option value="order_now">Order Now (অর্ডার করুন)</option>
                            <option value="learn_more">Learn More (বিস্তারিত জানুন)</option>
                            <option value="visit_website">Visit Website (ওয়েবসাইট দেখুন)</option>
                        </select>
                    </div>

                    <div id="ctaUrlBox">
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">বাটন লিংক / গন্তব্য URL</label>
                        <input type="url" id="ctaUrlInput" placeholder="https://..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                    </div>

                    <!-- Social Media Links -->
                    <div style="margin-top: 14px; padding-top: 18px; border-top: 1px dashed var(--fb-border);">
                        <strong style="display: block; font-size: 15px; margin-bottom: 12px; color: var(--fb-text);">সোশ্যাল মিডিয়া প্রোফাইল লিংকসমূহ:</strong>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div>
                                <label style="display: block; font-size: 13px; margin-bottom: 4px; color: var(--fb-text);">Facebook Page / Profile</label>
                                <input type="url" id="socialFbInput" placeholder="https://facebook.com/..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; margin-bottom: 4px; color: var(--fb-text);">Instagram Handle / URL</label>
                                <input type="url" id="socialInstaInput" placeholder="https://instagram.com/..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; margin-bottom: 4px; color: var(--fb-text);">YouTube Channel</label>
                                <input type="url" id="socialYtInput" placeholder="https://youtube.com/@..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 13px; margin-bottom: 4px; color: var(--fb-text);">LinkedIn Company Page</label>
                                <input type="url" id="socialLiInput" placeholder="https://linkedin.com/company/..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 9: Media Assets -->
            <div class="form-section-card step-panel" id="stepPanel9" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">৯. প্রোফাইল ছবি ও কভার ব্যানার আপলোড</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">উচ্চমানের ছবি আপনার পেইজকে পেশাদার ও আকর্ষণীয় করে তোলে।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Avatar Upload -->
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 8px; color: var(--fb-text);">প্রোফাইল ছবি / লোগো (Profile Photo / Logo)</label>
                        <div class="media-dropzone" onclick="document.getElementById('avatarFileInput').click()">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            <p style="font-size: 14px; font-weight: 600; margin: 8px 0 4px 0; color: var(--fb-text);">প্রোফাইল ছবি আপলোড করতে ক্লিক করুন</p>
                            <span style="font-size: 12px; color: var(--fb-text-secondary);">JPG, PNG, WebP (সর্বোচ্চ 5MB)</span>
                            <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="handleAvatarFile(event)">
                        </div>
                    </div>

                    <!-- Cover Upload -->
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 8px; color: var(--fb-text);">কভার ব্যানার (Cover Banner Photo)</label>
                        <div class="media-dropzone" onclick="document.getElementById('coverFileInput').click()">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            <p style="font-size: 14px; font-weight: 600; margin: 8px 0 4px 0; color: var(--fb-text);">কভার ব্যানার আপলোড করতে ক্লিক করুন</p>
                            <span style="font-size: 12px; color: var(--fb-text-secondary);">প্রস্তাবিত সাইজ: 1200 x 450 px (সর্বোচ্চ 8MB)</span>
                            <input type="file" id="coverFileInput" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="handleCoverFile(event)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 10: Privacy, Verification & Final Review -->
            <div class="form-section-card step-panel" id="stepPanel10" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--fb-text); margin: 0 0 6px 0;">১০. গোপনীয়তা, অফিসিয়াল যাচাই ও চূড়ান্ত রিভিউ</h2>
                    <p style="font-size: 14px; color: var(--fb-text-secondary); margin: 0;">সবকিছু সঠিকভাবে পর্যবেক্ষণ করে পেইজ চালু করুন।</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 8px; color: var(--fb-text);">পেইজের দৃশ্যমানতা (Visibility)</label>
                        <select id="visibilitySelect" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text);">
                            <option value="public" selected>Public (সবাই দেখতে পাবে ও সার্চে আসবে)</option>
                            <option value="unlisted">Unlisted (শুধু লিংক থাকলে দেখতে পাবে)</option>
                            <option value="restricted">Restricted (সীমিত অ্যাক্সেস)</option>
                        </select>
                    </div>

                    <!-- Privacy Controls -->
                    <div style="padding: 16px; border: 1px solid var(--fb-border); border-radius: 10px; background: var(--fb-bg);">
                        <strong style="display: block; font-size: 14px; margin-bottom: 10px; color: var(--fb-text);">ফিল্ড-লেভেল প্রাইভেসি কনফিগারেশন:</strong>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="privEmailCheck" checked> সাধারণ দর্শকদের কাছে পাবলিক ইমেইল প্রদর্শন করুন
                            </label>
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="privPhoneCheck" checked> সাধারণ দর্শকদের কাছে ফোন নম্বর প্রদর্শন করুন
                            </label>
                            <label style="font-size: 13px; color: var(--fb-text); display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="privAddressCheck" checked> সাধারণ দর্শকদের কাছে পূর্ণাঙ্গ ঠিকানা প্রদর্শন করুন
                            </label>
                        </div>
                    </div>

                    <!-- Summary Review Box -->
                    <div style="border: 1px solid #059669; border-radius: 10px; padding: 18px; background: rgba(5, 150, 105, 0.04);">
                        <h3 style="font-size: 16px; font-weight: 700; color: #059669; margin: 0 0 10px 0;">রেজিস্ট্রেশন সারাংশ:</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
                            <div>পেইজ নাম: <strong id="reviewPageName">-</strong></div>
                            <div>ইউজারনেম: <strong id="reviewUsername">-</strong></div>
                            <div>পেইজ টাইপ: <strong id="reviewPageType">-</strong></div>
                            <div>ক্যাটাগরি: <strong id="reviewCategory">-</strong></div>
                            <div>লোকেশন: <strong id="reviewLocation">-</strong></div>
                            <div>অ্যাকশন বাটন: <strong id="reviewCta">-</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Bottom Action Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 14px; border-top: 1px solid var(--fb-border);">
                <button type="button" class="btn-brand-secondary" id="btnPrevStep" onclick="prevStep()" style="visibility: hidden;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    <span>পূর্ববর্তী ধাপ</span>
                </button>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-brand-primary" id="btnNextStep" onclick="nextStep()">
                        <span>পরবর্তী ধাপ</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <button type="button" class="btn-brand-primary" id="btnFinalSubmit" onclick="submitCreatePage()" style="display: none; background: #059669;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>পেইজ চূড়ান্ত তৈরি করুন</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Real-time Live Preview Sticky Card (Right Column) -->
        <div>
            <div class="preview-card-sticky">
                <div style="padding: 12px 16px; border-bottom: 1px solid var(--fb-border); font-size: 13px; font-weight: 700; color: var(--fb-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                    <span>রিয়েল-টাইম লাইভ প্রিভিউ</span>
                </div>

                <!-- Cover Preview -->
                <div class="preview-cover" id="livePreviewCover">
                    <div class="preview-avatar-box">
                        <img id="livePreviewAvatar" src="https://ui-avatars.com/api/?name=Page&background=059669&color=fff&size=128" alt="Page Avatar">
                    </div>
                </div>

                <!-- Profile Info Preview -->
                <div style="padding: 38px 18px 20px 18px;">
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                        <h3 id="livePreviewName" style="font-size: 18px; font-weight: 800; color: var(--fb-text); margin: 0;">আপনার পেইজের নাম</h3>
                        <span style="color: #0284c7; display: inline-flex;" title="Verified">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#0284c7" stroke="white" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                    </div>

                    <div style="font-size: 13px; color: var(--fb-text-secondary); margin-bottom: 10px;">
                        @<span id="livePreviewUsername">yourbrand</span> • <span id="livePreviewCategory" style="color: #059669; font-weight: 600;">ব্যবসা</span>
                    </div>

                    <p id="livePreviewBio" style="font-size: 13px; color: var(--fb-text); margin: 0 0 14px 0; line-height: 1.4;">
                        সংক্ষিপ্ত পরিচিতি এখানে প্রদর্শিত হবে।
                    </p>

                    <!-- CTA Preview Button -->
                    <button type="button" id="livePreviewCtaBtn" style="width: 100%; background: #059669; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-bottom: 16px; cursor: default;">
                        Send Message
                    </button>

                    <!-- Details pills -->
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--fb-text-secondary); border-top: 1px solid var(--fb-border); padding-top: 14px;">
                        <div id="livePreviewLocationRow" style="display: flex; align-items: center; gap: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span id="livePreviewLocationText">ঢাকা, বাংলাদেশ</span>
                        </div>
                        <div id="livePreviewWebsiteRow" style="display: flex; align-items: center; gap: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                            <span id="livePreviewWebsiteText">website.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    // State Management
    let currentStep = 1;
    const totalSteps = 10;
    let selectedPageTypeId = null;
    let selectedPageTypeName = '';
    let selectedPageTypeSlug = '';
    let selectedCategoryId = null;
    let selectedCategoryName = '';
    let selectedSubcategoryId = null;
    let usernameCheckTimeout = null;
    let isUsernameAvailable = false;
    let activeDraftId = {{ $latestDraft ? $latestDraft->id : 'null' }};
    let autosaveDebounceTimer = null;
    let uploadedAvatarBase64 = null;
    let uploadedCoverBase64 = null;

    // Preloaded Taxonomies from Backend
    const pageTypesData = @json($pageTypes);

    document.addEventListener('DOMContentLoaded', () => {
        // Pre-select first type if available
        if (pageTypesData && pageTypesData.length > 0) {
            selectPageType(pageTypesData[0].id, pageTypesData[0].name, pageTypesData[0].slug);
        }
    });

    // Step Navigation
    function jumpToStep(step) {
        if (step > currentStep && !validateCurrentStep()) {
            return;
        }
        setStep(step);
    }

    function nextStep() {
        if (!validateCurrentStep()) {
            return;
        }
        if (currentStep < totalSteps) {
            setStep(currentStep + 1);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            setStep(currentStep - 1);
        }
    }

    function setStep(step) {
        currentStep = step;
        
        // Hide all panels
        document.querySelectorAll('.step-panel').forEach(p => p.style.display = 'none');
        const activePanel = document.getElementById(`stepPanel${step}`);
        if (activePanel) {
            activePanel.style.display = 'block';
        }

        // Update Stepper Headers
        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.getAttribute('data-step'), 10);
            item.classList.remove('active', 'completed');
            if (s === step) {
                item.classList.add('active');
            } else if (s < step) {
                item.classList.add('completed');
            }
        });

        // Toggle Prev/Next buttons
        document.getElementById('btnPrevStep').style.visibility = step > 1 ? 'visible' : 'hidden';
        if (step === totalSteps) {
            document.getElementById('btnNextStep').style.display = 'none';
            document.getElementById('btnFinalSubmit').style.display = 'inline-flex';
            populateReviewSummary();
        } else {
            document.getElementById('btnNextStep').style.display = 'inline-flex';
            document.getElementById('btnFinalSubmit').style.display = 'none';
        }

        window.scrollTo({ top: 120, behavior: 'smooth' });
        triggerAutosave();
    }

    // Step Validation
    function validateCurrentStep() {
        if (currentStep === 1) {
            if (!selectedPageTypeId) {
                alert('অনুগ্রহ করে একটি পেইজ টাইপ নির্বাচন করুন।');
                return false;
            }
        } else if (currentStep === 2) {
            const cat = document.getElementById('primaryCategorySelect').value;
            if (!cat) {
                alert('অনুগ্রহ করে একটি প্রধান ক্যাটাগরি নির্বাচন করুন।');
                return false;
            }
        } else if (currentStep === 3) {
            const name = document.getElementById('pageNameInput').value.trim();
            const username = document.getElementById('pageUsernameInput').value.trim();
            const shortDesc = document.getElementById('pageShortDescInput').value.trim();

            if (!name || name.length < 2) {
                alert('পেইজের নাম সর্বনিম্ন ২ ক্যারেক্টার হতে হবে।');
                document.getElementById('pageNameInput').focus();
                return false;
            }
            if (!username) {
                alert('অনুগ্রহ করে একটি পেইজ ইউজারনেম দিন।');
                document.getElementById('pageUsernameInput').focus();
                return false;
            }
            if (!isUsernameAvailable) {
                alert('ইউজারনেমটি উপলব্ধ নয় বা এখনও যাচাই সম্পন্ন হয়নি।');
                return false;
            }
            if (!shortDesc) {
                alert('অনুগ্রহ করে একটি সংক্ষিপ্ত পরিচিতি লিখুন।');
                document.getElementById('pageShortDescInput').focus();
                return false;
            }
        }
        return true;
    }

    // Page Type Selection
    function selectPageType(id, name, slug) {
        selectedPageTypeId = id;
        selectedPageTypeName = name;
        selectedPageTypeSlug = slug;

        document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
        const selectedCard = document.querySelector(`.type-card[data-type-id="${id}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // Populate Categories Dropdown
        const catSelect = document.getElementById('primaryCategorySelect');
        catSelect.innerHTML = '<option value="">-- ক্যাটাগরি নির্বাচন করুন --</option>';

        const pType = pageTypesData.find(t => t.id === id);
        if (pType && pType.categories) {
            pType.categories.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                catSelect.appendChild(opt);
            });

            // Populate Popular Badges
            const popularContainer = document.getElementById('popularBadgesContainer');
            popularContainer.innerHTML = '';
            const popular = pType.categories.filter(c => c.is_popular);
            popular.forEach(pop => {
                const badge = document.createElement('span');
                badge.style.cssText = 'padding: 4px 10px; font-size: 12px; border-radius: 12px; background: rgba(5,150,105,0.1); color: #059669; cursor: pointer; font-weight: 500;';
                badge.textContent = pop.name;
                badge.onclick = () => {
                    catSelect.value = pop.id;
                    onCategoryChanged();
                };
                popularContainer.appendChild(badge);
            });
        }

        loadDynamicFields(id);
        updatePreview();
    }

    function filterPageTypes() {
        const query = document.getElementById('typeSearchInput').value.toLowerCase().trim();
        document.querySelectorAll('.type-card').forEach(card => {
            const name = card.getAttribute('data-type-name') || '';
            const desc = card.getAttribute('data-type-desc') || '';
            if (name.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Category Handling
    function onCategoryChanged() {
        const catId = parseInt(document.getElementById('primaryCategorySelect').value, 10);
        selectedCategoryId = catId || null;

        const subSelect = document.getElementById('subcategorySelect');
        subSelect.innerHTML = '<option value="">-- প্রযোজ্য হলে নির্বাচন করুন --</option>';

        if (catId) {
            const pType = pageTypesData.find(t => t.id === selectedPageTypeId);
            if (pType && pType.categories) {
                const cat = pType.categories.find(c => c.id === catId);
                if (cat) {
                    selectedCategoryName = cat.name;
                    if (cat.subcategories) {
                        cat.subcategories.forEach(sub => {
                            const opt = document.createElement('option');
                            opt.value = sub.id;
                            opt.textContent = sub.name;
                            subSelect.appendChild(opt);
                        });
                    }
                }
            }
        }
        updatePreview();
    }

    function onSubcategoryChanged() {
        selectedSubcategoryId = parseInt(document.getElementById('subcategorySelect').value, 10) || null;
    }

    // Dynamic Fields Loader
    async function loadDynamicFields(typeId, catId = null) {
        const container = document.getElementById('dynamicFieldsContainer');
        container.innerHTML = '<span style="color: var(--fb-text-secondary); font-size: 13px;">ফিল্ড লোড হচ্ছে...</span>';

        try {
            const res = await fetch(`/api/v2/pages/categories/${catId || 1}/fields?page_type_id=${typeId}`);
            const data = await res.json();

            if (data.success && data.data && data.data.length > 0) {
                container.innerHTML = '';
                data.data.forEach(f => {
                    const row = document.createElement('div');
                    row.innerHTML = `
                        <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: var(--fb-text);">
                            ${f.label} ${f.is_required ? '<span style="color: #dc2626;">*</span>' : ''}
                        </label>
                    `;

                    if (f.field_type === 'select') {
                        let optsHtml = '<option value="">-- নির্বাচন করুন --</option>';
                        if (f.options) {
                            Object.entries(f.options).forEach(([k, v]) => {
                                optsHtml += `<option value="${k}">${v}</option>`;
                            });
                        }
                        row.innerHTML += `
                            <select class="dynamic-field-input" data-key="${f.field_key}" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text);">
                                ${optsHtml}
                            </select>
                        `;
                    } else if (f.field_type === 'boolean') {
                        row.innerHTML += `
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--fb-text); cursor: pointer;">
                                <input type="checkbox" class="dynamic-field-input" data-key="${f.field_key}" ${f.default_value === '1' ? 'checked' : ''}>
                                <span>হ্যাঁ, সক্রিয়</span>
                            </label>
                        `;
                    } else if (f.field_type === 'textarea') {
                        row.innerHTML += `
                            <textarea class="dynamic-field-input" data-key="${f.field_key}" rows="2" placeholder="${f.placeholder || ''}" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;"></textarea>
                        `;
                    } else {
                        row.innerHTML += `
                            <input type="${f.field_type === 'url' ? 'url' : (f.field_type === 'number' ? 'number' : 'text')}" class="dynamic-field-input" data-key="${f.field_key}" placeholder="${f.placeholder || ''}" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--fb-border); background: var(--fb-bg); color: var(--fb-text); box-sizing: border-box;">
                        `;
                    }
                    container.appendChild(row);
                });
            } else {
                container.innerHTML = '<span style="color: var(--fb-text-secondary); font-size: 13px;">এই পেইজ টাইপের জন্য অতিরিক্ত বিশেষ ফিল্ড প্রযোজ্য নয়।</span>';
            }
        } catch (e) {
            container.innerHTML = '<span style="color: #dc2626; font-size: 13px;">ফিল্ড লোড করা সম্ভব হয়নি।</span>';
        }
    }

    // Username Real-time Check
    function onUsernameInputChanged() {
        const val = document.getElementById('pageUsernameInput').value.trim();
        document.getElementById('previewUrlSlug').textContent = val || 'yourbrand';

        clearTimeout(usernameCheckTimeout);
        const indicator = document.getElementById('usernameIndicator');

        if (!val) {
            indicator.style.display = 'none';
            isUsernameAvailable = false;
            return;
        }

        indicator.style.display = 'flex';
        indicator.className = 'availability-indicator checking';
        indicator.innerHTML = `<span>যাচাই করা হচ্ছে...</span>`;

        usernameCheckTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`/api/v2/pages/username/check?username=${encodeURIComponent(val)}`);
                const data = await res.json();

                if (data.available) {
                    indicator.className = 'availability-indicator success';
                    indicator.innerHTML = `<span>✓ @${data.normalized} ইউজারনেমটি খালি আছে!</span>`;
                    isUsernameAvailable = true;
                } else {
                    indicator.className = 'availability-indicator error';
                    let sugHtml = '';
                    if (data.suggestions && data.suggestions.length > 0) {
                        sugHtml = ` (পরামর্শ: ${data.suggestions.map(s => `<a href="javascript:void(0)" onclick="applyUsernameSuggestion('${s}')" style="color: #0284c7; text-decoration: underline;">@${s}</a>`).join(', ')})`;
                    }
                    indicator.innerHTML = `<span>✕ ${data.message}${sugHtml}</span>`;
                    isUsernameAvailable = false;
                }
            } catch (e) {
                indicator.className = 'availability-indicator error';
                indicator.innerHTML = `<span>নেটওয়ার্ক ত্রুটি।</span>`;
                isUsernameAvailable = false;
            }
            updatePreview();
        }, 350);
    }

    function applyUsernameSuggestion(sug) {
        document.getElementById('pageUsernameInput').value = sug;
        onUsernameInputChanged();
    }

    function onPageNameChanged() {
        const name = document.getElementById('pageNameInput').value.trim();
        const usernameInput = document.getElementById('pageUsernameInput');
        if (!usernameInput.value) {
            const slug = name.toLowerCase().replace(/[^a-z0-9_.]/g, '');
            if (slug) {
                usernameInput.value = slug;
                onUsernameInputChanged();
            }
        }
        updatePreview();
    }

    function onShortDescChanged() {
        updatePreview();
    }

    function onFullAboutChanged() {
        updatePreview();
    }

    function onCtaChanged() {
        const cta = document.getElementById('ctaTypeSelect');
        const ctaBtn = document.getElementById('livePreviewCtaBtn');
        ctaBtn.textContent = cta.options[cta.selectedIndex].text.split('(')[0].trim();
    }

    // Branch Adding
    function addNewBranchRow() {
        const container = document.getElementById('branchesListContainer');
        const row = document.createElement('div');
        row.className = 'branch-item-row';
        row.style.cssText = 'border: 1px solid var(--fb-border); border-radius: 8px; padding: 12px; background: var(--fb-bg); display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 8px; align-items: center;';
        row.innerHTML = `
            <input type="text" placeholder="শাখার নাম (যেমন: উত্তরা শাখা)" class="branch-name-input" style="padding: 8px; border-radius: 6px; border: 1px solid var(--fb-border); background: var(--fb-card); color: var(--fb-text);">
            <input type="text" placeholder="ঠিকানা" class="branch-address-input" style="padding: 8px; border-radius: 6px; border: 1px solid var(--fb-border); background: var(--fb-card); color: var(--fb-text);">
            <input type="text" placeholder="ফোন" class="branch-phone-input" style="padding: 8px; border-radius: 6px; border: 1px solid var(--fb-border); background: var(--fb-card); color: var(--fb-text);">
            <button type="button" onclick="this.closest('.branch-item-row').remove()" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 16px;">✕</button>
        `;
        container.appendChild(row);
    }

    // Business Hours Toggle
    function toggleHoursRow(day, mode) {
        const box = document.getElementById(`timeBox_${day}`);
        if (box) {
            box.style.display = mode === 'open' ? 'flex' : 'none';
        }
    }

    // Media Upload Handlers
    function handleAvatarFile(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                uploadedAvatarBase64 = e.target.result;
                document.getElementById('livePreviewAvatar').src = uploadedAvatarBase64;
            };
            reader.readAsDataURL(file);
        }
    }

    function handleCoverFile(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                uploadedCoverBase64 = e.target.result;
                document.getElementById('livePreviewCover').style.backgroundImage = `url(${uploadedCoverBase64})`;
            };
            reader.readAsDataURL(file);
        }
    }

    // Live Preview Synchronization
    function updatePreview() {
        const name = document.getElementById('pageNameInput')?.value.trim();
        const username = document.getElementById('pageUsernameInput')?.value.trim();
        const shortDesc = document.getElementById('pageShortDescInput')?.value.trim();
        const city = document.getElementById('cityInput')?.value.trim();
        const website = document.getElementById('websiteInput')?.value.trim();

        document.getElementById('livePreviewName').textContent = name || 'আপনার পেইজের নাম';
        document.getElementById('livePreviewUsername').textContent = username || 'yourbrand';
        document.getElementById('livePreviewBio').textContent = shortDesc || 'সংক্ষিপ্ত পরিচিতি এখানে প্রদর্শিত হবে।';
        document.getElementById('livePreviewCategory').textContent = selectedCategoryName || selectedPageTypeName || 'ব্যবসা';
        document.getElementById('livePreviewLocationText').textContent = city ? `${city}, বাংলাদেশ` : 'ঢাকা, বাংলাদেশ';
        document.getElementById('livePreviewWebsiteText').textContent = website || 'website.com';
    }

    function populateReviewSummary() {
        document.getElementById('reviewPageName').textContent = document.getElementById('pageNameInput').value.trim() || '-';
        document.getElementById('reviewUsername').textContent = '@' + (document.getElementById('pageUsernameInput').value.trim() || '-');
        document.getElementById('reviewPageType').textContent = selectedPageTypeName || '-';
        document.getElementById('reviewCategory').textContent = selectedCategoryName || '-';
        document.getElementById('reviewLocation').textContent = document.getElementById('cityInput').value.trim() || 'ঢাকা';
        const cta = document.getElementById('ctaTypeSelect');
        document.getElementById('reviewCta').textContent = cta.options[cta.selectedIndex].text;
    }

    // Collect Form Payload
    function collectFormPayload() {
        const customFields = {};
        document.querySelectorAll('.dynamic-field-input').forEach(inp => {
            const key = inp.getAttribute('data-key');
            if (key) {
                customFields[key] = inp.type === 'checkbox' ? inp.checked : inp.value;
            }
        });

        const branches = [];
        document.querySelectorAll('.branch-item-row').forEach(row => {
            const bName = row.querySelector('.branch-name-input')?.value.trim();
            const bAddr = row.querySelector('.branch-address-input')?.value.trim();
            const bPhone = row.querySelector('.branch-phone-input')?.value.trim();
            if (bName) {
                branches.push({ name: bName, street_address: bAddr, phone: bPhone });
            }
        });

        const businessHours = {};
        document.querySelectorAll('.hours-row').forEach(row => {
            const day = row.getAttribute('data-day');
            const mode = row.querySelector(`input[name="hours_mode_${day}"]:checked`)?.value || 'open';
            const openTime = row.querySelector('.open-time-input')?.value || '09:00';
            const closeTime = row.querySelector('.close-time-input')?.value || '18:00';
            businessHours[day] = { mode, open: openTime, close: closeTime };
        });

        return {
            page_type_id: selectedPageTypeId,
            page_category_id: selectedCategoryId,
            page_subcategory_id: selectedSubcategoryId,
            category: selectedCategoryName,
            name: document.getElementById('pageNameInput')?.value.trim(),
            username: document.getElementById('pageUsernameInput')?.value.trim(),
            short_description: document.getElementById('pageShortDescInput')?.value.trim(),
            description: document.getElementById('pageFullAboutInput')?.value.trim(),
            email: document.getElementById('publicEmailInput')?.value.trim(),
            phone: document.getElementById('phoneInput')?.value.trim(),
            website: document.getElementById('websiteInput')?.value.trim(),
            address: document.getElementById('streetAddressInput')?.value.trim(),
            city: document.getElementById('cityInput')?.value.trim(),
            state: document.getElementById('stateInput')?.value.trim(),
            zip_code: document.getElementById('zipCodeInput')?.value.trim(),
            cta_type: document.getElementById('ctaTypeSelect')?.value,
            cta_url: document.getElementById('ctaUrlInput')?.value.trim(),
            visibility: document.getElementById('visibilitySelect')?.value || 'public',
            avatar_url: uploadedAvatarBase64,
            cover_image_url: uploadedCoverBase64,
            business_details: {
                legal_business_name: document.getElementById('legalNameInput')?.value.trim(),
                registration_number: document.getElementById('regNumberInput')?.value.trim(),
                tax_id: document.getElementById('taxIdInput')?.value.trim(),
                year_founded: document.getElementById('yearFoundedInput')?.value.trim(),
            },
            custom_fields_data: customFields,
            branches: branches,
            business_hours: businessHours,
            social_links: {
                facebook: document.getElementById('socialFbInput')?.value.trim(),
                instagram: document.getElementById('socialInstaInput')?.value.trim(),
                youtube: document.getElementById('socialYtInput')?.value.trim(),
                linkedin: document.getElementById('socialLiInput')?.value.trim(),
            },
            privacy_settings: {
                public_email: document.getElementById('privEmailCheck')?.checked ?? true,
                public_phone: document.getElementById('privPhoneCheck')?.checked ?? true,
                public_address: document.getElementById('privAddressCheck')?.checked ?? true,
            },
            draft_id: activeDraftId,
        };
    }

    // Autosave Debounced Implementation
    function triggerAutosave() {
        clearTimeout(autosaveDebounceTimer);
        document.getElementById('autosaveText').textContent = 'সংরক্ষণ হচ্ছে...';

        autosaveDebounceTimer = setTimeout(async () => {
            const payload = collectFormPayload();
            try {
                const res = await fetch('/api/v2/pages/drafts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        draft_id: activeDraftId,
                        current_step: currentStep,
                        form_data: payload,
                    }),
                });
                const data = await res.json();
                if (data.success && data.data) {
                    activeDraftId = data.data.id;
                    document.getElementById('autosaveText').textContent = 'ড্রাফট সেভ হয়েছে';
                } else {
                    document.getElementById('autosaveText').textContent = 'সেভ ব্যর্থ';
                }
            } catch (e) {
                document.getElementById('autosaveText').textContent = 'অফলাইন';
            }
        }, 1200);
    }

    async function manualSaveDraft() {
        triggerAutosave();
        alert('আপনার ড্রাফট সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    async function discardDraft(id) {
        if (!confirm('আপনি কি এই ড্রাফটটি মুছে ফেলতে চান?')) return;
        try {
            await fetch(`/api/v2/pages/drafts/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            document.getElementById('resumeDraftBanner')?.remove();
            activeDraftId = null;
        } catch (e) {}
    }

    async function resumeDraft(id) {
        try {
            const res = await fetch(`/api/v2/pages/drafts/${id}`);
            const data = await res.json();
            if (data.success && data.data && data.data.form_data) {
                const fd = data.data.form_data;
                if (fd.name) document.getElementById('pageNameInput').value = fd.name;
                if (fd.username) {
                    document.getElementById('pageUsernameInput').value = fd.username;
                    onUsernameInputChanged();
                }
                if (fd.short_description) document.getElementById('pageShortDescInput').value = fd.short_description;
                if (fd.description) document.getElementById('pageFullAboutInput').value = fd.description;
                if (fd.email) document.getElementById('publicEmailInput').value = fd.email;
                if (fd.phone) document.getElementById('phoneInput').value = fd.phone;
                if (fd.website) document.getElementById('websiteInput').value = fd.website;
                if (fd.city) document.getElementById('cityInput').value = fd.city;
                if (fd.address) document.getElementById('streetAddressInput').value = fd.address;

                if (fd.page_type_id) {
                    const pType = pageTypesData.find(t => t.id === fd.page_type_id);
                    if (pType) selectPageType(pType.id, pType.name, pType.slug);
                }
                updatePreview();
                setStep(data.data.current_step || 1);
                document.getElementById('resumeDraftBanner')?.remove();
            }
        } catch (e) {
            alert('ড্রাফট লোড করা যায়নি।');
        }
    }

    // Final Atomic Page Creation Submit
    async function submitCreatePage() {
        const btn = document.getElementById('btnFinalSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span>পেইজ তৈরি হচ্ছে...</span>';

        const payload = collectFormPayload();

        try {
            const res = await fetch('/api/v2/pages/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success && data.data) {
                alert('অভিনন্দন! আপনার এন্টারপ্রাইজ পেইজ সফলভাবে প্রতিষ্ঠিত হয়েছে।');
                window.location.href = `/pages/${data.data.slug}/manage`;
            } else {
                alert(data.message || 'পেইজ তৈরি করতে সমস্যা হয়েছে। অনুগ্রহ করে তথ্যের সঠিকতা যাচাই করুন।');
                btn.disabled = false;
                btn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>পেইজ চূড়ান্ত তৈরি করুন</span>
                `;
            }
        } catch (e) {
            alert('সার্ভার যোগাযোগে ত্রুটি ঘটেছে। পুনরায় চেষ্টা করুন।');
            btn.disabled = false;
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>পেইজ চূড়ান্ত তৈরি করুন</span>
            `;
        }
    }
</script>
@endsection
