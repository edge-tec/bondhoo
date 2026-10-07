@extends('layouts.app')

@section('title', 'অ্যাডমিন লাইভ কন্ট্রোল সেন্টার — Bondhoo')

@section('styles')
<style>
    :root {
        --admin-card-bg: var(--fb-card);
        --admin-border: var(--fb-border);
        --live-red: #ef4444;
        --live-green: #10b981;
    }

    .admin-dashboard-container {
        max-width: 1400px;
        margin: 24px auto;
        padding: 0 16px 40px 16px;
    }

    /* Header Bar */
    .admin-header-card {
        background: var(--admin-card-bg);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        padding: 22px 28px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .admin-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .admin-shield-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(239, 68, 68, 0.12);
        color: var(--live-red);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .admin-heading {
        font-size: 22px;
        font-weight: 800;
        color: var(--fb-text-primary);
        margin: 0 0 4px 0;
    }

    .admin-subheading {
        font-size: 13px;
        color: var(--fb-text-secondary);
        margin: 0;
    }

    /* Metric Cards Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .metric-card {
        background: var(--admin-card-bg);
        border: 1px solid var(--admin-border);
        border-radius: 14px;
        padding: 18px 22px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .metric-value-num {
        font-size: 28px;
        font-weight: 800;
        color: var(--fb-text-primary);
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .metric-label-text {
        font-size: 12px;
        font-weight: 700;
        color: var(--fb-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-badge-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Filter Tabs Bar */
    .admin-tabs-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--admin-border);
        padding-bottom: 10px;
        overflow-x: auto;
    }

    .tab-nav-btn {
        background: transparent;
        border: none;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 700;
        color: var(--fb-text-secondary);
        border-radius: 10px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .tab-nav-btn:hover {
        background: var(--fb-hover);
        color: var(--fb-text-primary);
    }

    .tab-nav-btn.active {
        background: var(--fb-primary);
        color: white;
    }

    .tab-count-pill {
        background: rgba(0, 0, 0, 0.15);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 800;
    }

    .tab-nav-btn.active .tab-count-pill {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }

    /* Table & Container Card */
    .data-table-card {
        background: var(--admin-card-bg);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .enterprise-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }

    .enterprise-table th {
        background: var(--fb-bg);
        color: var(--fb-text-secondary);
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--admin-border);
        white-space: nowrap;
    }

    .enterprise-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--admin-border);
        color: var(--fb-text-primary);
        vertical-align: middle;
    }

    .enterprise-table tr:hover {
        background: var(--fb-hover);
    }

    .broadcaster-avatar-ring {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--live-red);
    }

    .badge-status {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        letter-spacing: 0.5px;
    }

    .badge-status.active-live {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge-status.archived {
        background: rgba(107, 114, 128, 0.15);
        color: #6b7280;
    }

    .badge-status.reported {
        background: rgba(245, 158, 11, 0.15);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .action-btn {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid var(--admin-border);
        background: var(--fb-bg);
        color: var(--fb-text-primary);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.15s;
    }

    .action-btn:hover {
        background: var(--fb-hover);
        color: var(--fb-primary);
    }

    .action-btn.danger {
        border-color: rgba(239, 68, 68, 0.4);
        color: #ef4444;
    }

    .action-btn.danger:hover {
        background: #ef4444;
        color: white;
    }

    /* Alert Banner */
    .admin-alert-banner {
        background: rgba(16, 185, 129, 0.15);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 700;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>
@endsection

@section('content')
<div class="admin-dashboard-container">
    @if(session('success'))
        <div class="admin-alert-banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Header Bar -->
    <div class="admin-header-card">
        <div class="admin-title-row">
            <div class="admin-shield-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div>
                <h1 class="admin-heading">অ্যাডমিন লাইভ কন্ট্রোল সেন্টার</h1>
                <p class="admin-subheading">প্ল্যাটফর্মের সকল চলমান লাইভ ব্রডকাস্ট পর্যবেক্ষণ, মডারেশন ও রিপোর্ট নিষ্পত্তি প্যানেল</p>
            </div>
        </div>
        <div>
            <a href="{{ route('watch.index') }}" class="action-btn" style="padding: 10px 18px; font-size: 13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <span>পাবলিক ওয়াচ ফিড</span>
            </a>
        </div>
    </div>

    <!-- Platform Live Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div>
                <div class="metric-value-num" style="color: #ef4444;">{{ $activeStreams->count() }}</div>
                <div class="metric-label-text">সক্রিয় লাইভ সম্প্রচার</div>
            </div>
            <div class="metric-badge-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            </div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-value-num" style="color: #3b82f6;">
                    {{ number_format($activeStreams->sum('viewers_count')) }}
                </div>
                <div class="metric-label-text">মোট সংযুক্ত দর্শক</div>
            </div>
            <div class="metric-badge-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-value-num" style="color: #10b981;">{{ $recentStreams->count() }}</div>
                <div class="metric-label-text">সংরক্ষিত রিপ্লে আর্কাইভ</div>
            </div>
            <div class="metric-badge-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
            </div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-value-num" style="color: #f59e0b;">{{ $reports->count() }}</div>
                <div class="metric-label-text">ইউজার রিপোর্টসমূহ</div>
            </div>
            <div class="metric-badge-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
        </div>
    </div>

    <!-- Filter Tabs Navigation -->
    <div class="admin-tabs-bar">
        <button class="tab-nav-btn active" onclick="switchAdminTab('activeTab', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>সক্রিয় লাইভ সেশনসমূহ</span>
            <span class="tab-count-pill">{{ $activeStreams->count() }}</span>
        </button>
        <button class="tab-nav-btn" onclick="switchAdminTab('endedTab', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
            <span>সমাপ্ত লাইভ আর্কাইভ</span>
            <span class="tab-count-pill">{{ $recentStreams->count() }}</span>
        </button>
        <button class="tab-nav-btn" onclick="switchAdminTab('reportsTab', this)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span>ইউজার রিপোর্টসমূহ</span>
            <span class="tab-count-pill">{{ $reports->count() }}</span>
        </button>
    </div>

    <!-- Tab 1: Active Live Streams -->
    <div id="activeTab" class="data-table-card">
        <div class="table-responsive-wrapper">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>আইডি</th>
                        <th>ব্রডকাস্টার</th>
                        <th>লাইভ শিরোনাম</th>
                        <th>গোপনীয়তা</th>
                        <th>বর্তমান দর্শক</th>
                        <th>পিক দর্শক</th>
                        <th>শুরুর সময়</th>
                        <th style="text-align: right;">মডারেশন অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeStreams as $stream)
                        <tr>
                            <td><strong>#{{ $stream->id }}</strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="{{ $stream->user->profile?->avatar_url ?: '/images/default-avatar.svg' }}"
                                         alt="{{ $stream->user->name }}"
                                         class="broadcaster-avatar-ring">
                                    <div>
                                        <div style="font-weight: 700;">{{ $stream->user->name }}</div>
                                        <div style="font-size: 11px; color: var(--fb-text-secondary);">@<span>{{ $stream->user->username }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; max-width: 280px; line-height: 1.3;">{{ $stream->title }}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px;">
                                    {{ $stream->description ?: 'বিবরণ নেই' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge-status active-live">{{ strtoupper($stream->privacy) }}</span>
                            </td>
                            <td>
                                <span style="font-weight: 800; color: #ef4444; font-size: 15px; display: inline-flex; align-items: center; gap: 5px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    {{ number_format($stream->viewers_count) }}
                                </span>
                            </td>
                            <td>{{ number_format($stream->peak_viewers) }}</td>
                            <td>{{ $stream->started_at ? $stream->started_at->diffForHumans() : '—' }}</td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <a href="{{ route('live.show', $stream->id) }}" target="_blank" class="action-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>দেখুন</span>
                                    </a>
                                    <form action="{{ route('admin.live.terminate', $stream->id) }}" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে অ্যাডমিন হিসেবে এই লাইভ সম্প্রচারটি এখনই তাৎক্ষণিকভাবে বন্ধ করবেন?')">
                                        @csrf
                                        <input type="hidden" name="reason" value="Violation of community safety standards">
                                        <button type="submit" class="action-btn danger">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                            <span>ফোর্স সমাপ্তি</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--fb-text-secondary); padding: 48px;">
                                <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--fb-bg); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                </div>
                                <div style="font-weight: 700; font-size: 15px; color: var(--fb-text-primary);">বর্তমানে কোনো লাইভ সম্প্রচার চলমান নেই</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 2: Ended Streams Archive -->
    <div id="endedTab" class="data-table-card" style="display: none;">
        <div class="table-responsive-wrapper">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>আইডি</th>
                        <th>ব্রডকাস্টার</th>
                        <th>শিরোনাম</th>
                        <th>পিক দর্শক</th>
                        <th>মোট রিঅ্যাকশন</th>
                        <th>মোট শেয়ার</th>
                        <th>রিপ্লে স্ট্যাটাস</th>
                        <th>সমাপ্তির সময়</th>
                        <th style="text-align: right;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentStreams as $stream)
                        <tr>
                            <td><strong>#{{ $stream->id }}</strong></td>
                            <td>
                                <div style="font-weight: 700;">{{ $stream->user->name }}</div>
                                <div style="font-size: 11px; color: var(--fb-text-secondary);">@<span>{{ $stream->user->username }}</span></div>
                            </td>
                            <td>
                                <div style="font-weight: 600; max-width: 260px;">{{ $stream->title }}</div>
                            </td>
                            <td>{{ number_format($stream->peak_viewers) }}</td>
                            <td>{{ number_format($stream->total_reactions) }}</td>
                            <td>{{ number_format($stream->total_shares) }}</td>
                            <td>
                                <span class="badge-status {{ $stream->recording_status === 'ready' ? 'active-live' : 'archived' }}">
                                    {{ strtoupper($stream->recording_status) }}
                                </span>
                            </td>
                            <td>{{ $stream->ended_at ? $stream->ended_at->diffForHumans() : '—' }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('live.show', $stream->id) }}" target="_blank" class="action-btn">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    <span>রেকর্ড দেখুন</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--fb-text-secondary); padding: 48px;">
                                কোনো সংরক্ষিত লাইভ আর্কাইভ পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab 3: Reports Moderation -->
    <div id="reportsTab" class="data-table-card" style="display: none;">
        <div class="table-responsive-wrapper">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>রিপোর্ট আইডি</th>
                        <th>লাইভ সেশন</th>
                        <th>রিপোর্টার</th>
                        <th>কারণ</th>
                        <th>বিস্তারিত তথ্য</th>
                        <th>স্ট্যাটাস</th>
                        <th>তারিখ</th>
                        <th style="text-align: right;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td><strong>#{{ $report->id }}</strong></td>
                            <td>
                                <a href="{{ route('live.show', $report->live_stream_id) }}" target="_blank" style="font-weight: 700; color: var(--fb-primary); text-decoration: none;">
                                    #{{ $report->live_stream_id }}: {{ $report->liveStream?->title }}
                                </a>
                            </td>
                            <td>{{ $report->reporter?->name }}</td>
                            <td>
                                <span class="badge-status reported">{{ strtoupper($report->reason) }}</span>
                            </td>
                            <td style="max-width: 220px; font-size: 12px;">{{ $report->details ?: 'কোনো বিবরণ নেই' }}</td>
                            <td>
                                <span class="badge-status active-live">{{ strtoupper($report->status) }}</span>
                            </td>
                            <td>{{ $report->created_at->diffForHumans() }}</td>
                            <td style="text-align: right;">
                                <form action="{{ route('admin.live.terminate', $report->live_stream_id) }}" method="POST" onsubmit="return confirm('এই রিপোর্টের ভিত্তিতে লাইভ সম্প্রচারটি এখনই বন্ধ করতে চান?')">
                                    @csrf
                                    <input type="hidden" name="reason" value="Reported: {{ $report->reason }}">
                                    <button type="submit" class="action-btn danger">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        <span>টার্মিনেট</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--fb-text-secondary); padding: 48px;">
                                <div style="font-size: 32px; margin-bottom: 8px;">✓</div>
                                <div style="font-weight: 700; color: var(--fb-text-primary);">কোনো অমীমাংসিত কমিউনিটি রিপোর্ট নেই!</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchAdminTab(tabId, btn) {
        document.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('activeTab').style.display = 'none';
        document.getElementById('endedTab').style.display = 'none';
        document.getElementById('reportsTab').style.display = 'none';

        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
    }
</script>
@endsection
