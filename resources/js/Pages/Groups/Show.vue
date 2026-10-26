<template>
  <AppLayout
    current-route="groups"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- Floating Toast Notification -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl bg-slate-900/95 text-white shadow-2xl border border-slate-700/80 backdrop-blur-md animate-fade-in"
    >
      <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <span class="text-xs sm:text-sm font-semibold tracking-wide">{{ toastMessage }}</span>
    </div>

    <!-- Group Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
      
      <!-- Cover Banner -->
      <div class="relative h-48 sm:h-72 md:h-80 w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 overflow-hidden">
        <img
          :src="group.cover_image_url || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1600&q=80'"
          alt="Group Cover"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        <!-- Cover Change Button for Admins -->
        <div v-if="isAdmin" class="absolute top-4 right-4 z-10">
          <label class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-md transition-all cursor-pointer flex items-center gap-2 shadow-lg hover:scale-105 active:scale-95">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Edit Cover
            <input type="file" accept="image/*" class="hidden" @change="uploadCoverPhoto" />
          </label>
        </div>
      </div>

      <!-- Header Info & Actions Row -->
      <div class="px-6 sm:px-8 pb-5 pt-0">
        <div class="flex flex-col md:flex-row items-center md:items-end justify-between gap-4 -mt-16 sm:-mt-20 relative z-10">
          
          <!-- Avatar + Titles -->
          <div class="flex flex-col md:flex-row items-center md:items-end gap-5 text-center md:text-left">
            <div class="relative group">
              <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-full ring-4 ring-white dark:ring-slate-900 shadow-xl overflow-hidden bg-[#1877F2] flex items-center justify-center text-white shrink-0">
                <img
                  v-if="group.avatar_url"
                  :src="group.avatar_url"
                  :alt="group.name"
                  class="w-full h-full object-cover"
                />
                <svg v-else class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>

              <!-- Avatar Change Button for Admin -->
              <label v-if="isAdmin" class="absolute bottom-1 right-1 w-9 h-9 rounded-full bg-[#1877F2] text-white flex items-center justify-center shadow-md cursor-pointer hover:bg-blue-600 transition-colors" title="Change Avatar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <input type="file" accept="image/*" class="hidden" @change="uploadAvatarPhoto" />
              </label>
            </div>

            <div class="mb-2">
              <div class="flex items-center justify-center md:justify-start gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {{ group.name }}
                </h1>
                <span v-if="group.is_verified" class="w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0" title="Verified Community">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </span>
                <span v-if="isShortcutPinned" class="w-5 h-5 rounded-full bg-amber-50 text-amber-500 dark:bg-amber-950/40 flex items-center justify-center shrink-0" title="Pinned to Shortcuts">
                  <svg class="w-3.5 h-3.5 fill-amber-400" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </span>
              </div>

              <div class="flex items-center justify-center md:justify-start gap-2 text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 mt-1">
                <span class="flex items-center gap-1.5">
                  <svg v-if="group.privacy === 'public'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  {{ group.privacy === 'public' ? 'Public Group' : 'Private Group' }}
                </span>
                <span>•</span>
                <span>{{ formatMemberCount(group.members_count) }} members</span>
                <span v-if="group.category">•</span>
                <span v-if="group.category" class="text-[#1877F2]">{{ group.category }}</span>
              </div>
            </div>
          </div>

          <!-- Action Buttons Group -->
          <div class="flex items-center gap-3">
            
            <!-- Joined / Member Status Dropdown -->
            <div v-if="isMember" class="relative">
              <button
                type="button"
                @click="showMemberMenu = !showMemberMenu"
                class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm font-bold transition-all flex items-center gap-2"
              >
                <span>Joined</span>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showMemberMenu }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-if="showMemberMenu"
                class="absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-40 animate-fade-in"
              >
                <button
                  type="button"
                  @click="openNotificationsModal"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                  Manage Notifications
                </button>
                <button
                  type="button"
                  @click="togglePinShortcut"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4" :class="isShortcutPinned ? 'text-amber-500 fill-amber-400' : 'text-slate-400'" viewBox="0 0 20 20">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                  </svg>
                  {{ isShortcutPinned ? 'Unpin from Shortcuts' : 'Pin to Shortcuts' }}
                </button>
                <div class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>
                <button
                  type="button"
                  @click="leaveGroup"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Leave Group
                </button>
              </div>
            </div>

            <!-- Pending Join Request (Cancel Option) -->
            <button
              v-else-if="isPendingApproval"
              type="button"
              @click="cancelJoinRequest"
              class="px-5 py-2.5 rounded-2xl bg-amber-50 hover:bg-amber-100 text-amber-700 text-sm font-bold border border-amber-300 shadow-sm transition-all flex items-center gap-2"
              title="Click to cancel pending request"
            >
              <span>Requested ⏳ (Cancel)</span>
            </button>

            <!-- Not a Member: Join / Request Join Button -->
            <button
              v-else
              type="button"
              @click="joinGroup"
              :disabled="isJoining"
              class="px-5 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2 disabled:opacity-50"
            >
              <span>{{ group.membership_approval_mode === 'approval_required' ? 'Request to Join' : 'Join Group' }}</span>
            </button>

            <!-- Invite Button -->
            <button
              type="button"
              @click="showInviteModal = true"
              class="px-4 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              Invite
            </button>

            <!-- More Actions 3-Dot Button -->
            <div class="relative">
              <button
                type="button"
                @click="showMoreMenu = !showMoreMenu"
                class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-colors"
                title="More Options"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
              </button>

              <div
                v-if="showMoreMenu"
                class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-2 z-40 animate-fade-in"
              >
                <!-- Pin to shortcuts -->
                <button
                  type="button"
                  @click="togglePinShortcut"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                  {{ isShortcutPinned ? 'Unpin from Shortcuts' : 'Pin to Shortcuts' }}
                </button>

                <!-- Notifications -->
                <button
                  type="button"
                  @click="openNotificationsModal"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                  Notification Settings
                </button>

                <!-- Community Rules -->
                <button
                  type="button"
                  @click="showRulesModal = true; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Group Rules
                </button>

                <!-- Admin: Settings -->
                <button
                  v-if="isAdmin"
                  type="button"
                  @click="showEditGroupModal = true; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Edit Group Settings
                </button>

                <!-- Admin: Manage Hub Shortcut -->
                <button
                  v-if="isAdmin"
                  type="button"
                  @click="activeTab = 'manage'; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/30 flex items-center gap-2.5 font-semibold"
                >
                  <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                  Admin Tools & Queue
                </button>

                <!-- Copy Link -->
                <button
                  type="button"
                  @click="copyGroupLink"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Copy Group Link
                </button>

                <div class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>

                <!-- Report Group -->
                <button
                  type="button"
                  @click="showReportModal = true; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2.5 text-xs sm:text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  Report Group
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- Navigation Tabs Bar -->
        <div class="flex items-center gap-1 sm:gap-2 mt-6 pt-2 border-t border-slate-100 dark:border-slate-800 overflow-x-auto no-scrollbar">
          
          <!-- Discussions / Posts -->
          <button
            type="button"
            @click="activeTab = 'posts'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'posts' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Discussions</span>
            <span v-if="activeTab === 'posts'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Featured / Announcements -->
          <button
            type="button"
            @click="activeTab = 'featured'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'featured' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Featured</span>
            <span v-if="announcementsList.length > 0" class="px-1.5 py-0.2 rounded-full bg-blue-100 text-[#1877F2] text-[10px] font-bold">
              {{ announcementsList.length }}
            </span>
            <span v-if="activeTab === 'featured'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- About -->
          <button
            type="button"
            @click="activeTab = 'about'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'about' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>About</span>
            <span v-if="activeTab === 'about'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Members -->
          <button
            type="button"
            @click="activeTab = 'members'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'members' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Members</span>
            <span class="text-xs text-slate-400 font-normal">({{ formatMemberCount(group.members_count) }})</span>
            <span v-if="activeTab === 'members'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Events -->
          <button
            type="button"
            @click="activeTab = 'events'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'events' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Events</span>
            <span v-if="eventsList.length > 0" class="px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">
              {{ eventsList.length }}
            </span>
            <span v-if="activeTab === 'events'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Media Gallery -->
          <button
            type="button"
            @click="activeTab = 'media'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'media' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Media</span>
            <span v-if="mediaGalleryList.length > 0" class="text-xs text-slate-400 font-normal">({{ mediaGalleryList.length }})</span>
            <span v-if="activeTab === 'media'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Files -->
          <button
            type="button"
            @click="activeTab = 'files'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'files' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Files</span>
            <span v-if="filesList.length > 0" class="text-xs text-slate-400 font-normal">({{ filesList.length }})</span>
            <span v-if="activeTab === 'files'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Polls -->
          <button
            type="button"
            @click="activeTab = 'polls'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'polls' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            <span>Polls</span>
            <span v-if="pollsList.length > 0" class="px-1.5 py-0.2 rounded-full bg-purple-100 text-purple-700 text-[10px] font-bold">
              {{ pollsList.length }}
            </span>
            <span v-if="activeTab === 'polls'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <!-- Manage / Admin Tools -->
          <button
            v-if="isAdmin"
            type="button"
            @click="activeTab = 'manage'"
            class="px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
            :class="activeTab === 'manage' ? 'text-amber-600 dark:text-amber-400 bg-amber-50/70 dark:bg-amber-950/30' : 'text-amber-600 dark:text-amber-400 hover:bg-amber-50/50'"
          >
            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Manage</span>
            <span v-if="pendingRequestsCount > 0" class="px-1.5 py-0.2 rounded-full bg-rose-500 text-white text-[10px] font-extrabold animate-pulse">
              {{ pendingRequestsCount }}
            </span>
            <span v-if="activeTab === 'manage'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-amber-500 rounded-full"></span>
          </button>

        </div>

      </div>
    </div>

    <!-- TAB 1: DISCUSSIONS & FEED -->
    <div v-show="activeTab === 'posts'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Left/Main Column: Post Composer & Feed -->
      <div class="lg:col-span-8 space-y-5">
        
        <!-- Professional Facebook-Style Post Composer Card -->
        <div v-if="canPost" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 transition-all">
          
          <!-- Avatar + Multiline Textarea Header -->
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-[#1877F2] flex items-center justify-center text-white shrink-0 overflow-hidden ring-2 ring-slate-100 dark:ring-slate-800">
              <img
                v-if="currentUserAvatar"
                :src="currentUserAvatar"
                alt="Your Avatar"
                class="w-full h-full object-cover"
              />
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>

            <div class="flex-1">
              <textarea
                v-model="newPostContent"
                rows="2"
                placeholder="Write something to the community..."
                class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/80 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all resize-none"
              ></textarea>

              <!-- Selected Feeling Tag -->
              <div v-if="selectedFeeling" class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 text-xs font-semibold border border-amber-200/80">
                  <span>{{ selectedFeeling.emoji }}</span>
                  <span>feeling {{ selectedFeeling.label.toLowerCase() }}</span>
                  <button type="button" @click="selectedFeeling = null" class="ml-1 hover:text-rose-600 font-bold">✕</button>
                </span>
              </div>
            </div>
          </div>

          <!-- Drag and Drop Media Upload Zone & Preview Gallery -->
          <div
            v-if="showMediaDropzone || composerMediaFiles.length > 0"
            class="mt-3.5 p-4 rounded-2xl border-2 border-dashed transition-all"
            :class="isDraggingMedia ? 'border-[#1877F2] bg-blue-50/60 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-800/30'"
            @dragover.prevent="isDraggingMedia = true"
            @dragleave.prevent="isDraggingMedia = false"
            @drop.prevent="handleMediaDrop"
          >
            <!-- Uploading Spinner Banner -->
            <div v-if="isUploadingMedia" class="py-3 flex items-center justify-center gap-2 text-xs font-bold text-[#1877F2]">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>Uploading media file(s)...</span>
            </div>

            <!-- Preview Gallery Cards -->
            <div v-if="composerMediaFiles.length > 0" class="space-y-3">
              <div
                class="grid gap-2 rounded-2xl overflow-hidden"
                :class="composerMediaFiles.length === 1 ? 'grid-cols-1' : (composerMediaFiles.length === 2 ? 'grid-cols-2' : 'grid-cols-3')"
              >
                <div
                  v-for="(media, mIdx) in composerMediaFiles"
                  :key="mIdx"
                  class="relative aspect-video rounded-xl overflow-hidden bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm group"
                >
                  <img :src="media.url" class="w-full h-full object-cover" />
                  
                  <!-- Remove Media Button -->
                  <button
                    type="button"
                    @click="removeComposerMedia(mIdx)"
                    class="absolute top-2 right-2 w-7 h-7 rounded-full bg-black/70 hover:bg-rose-600 text-white flex items-center justify-center text-xs shadow-md transition-all active:scale-95"
                    title="Remove media"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <!-- Add More Button inside Dropzone -->
              <div class="flex items-center justify-between pt-2">
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer transition-colors">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  <span>Add More Photos</span>
                  <input type="file" multiple accept="image/*,video/*" class="hidden" @change="handleComposerMediaUpload" />
                </label>

                <button
                  type="button"
                  @click="composerMediaFiles = []; showMediaDropzone = false;"
                  class="text-xs text-rose-500 hover:underline font-semibold"
                >
                  Clear all photos
                </button>
              </div>
            </div>

            <!-- Empty Dropzone Prompt -->
            <div v-else class="py-6 text-center">
              <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                Drag and drop photos or videos here
              </p>
              <p class="text-[11px] text-slate-400 mt-0.5">Supports high-res PNG, JPG, WebP, GIF</p>
              <label class="mt-2.5 inline-block px-4 py-1.5 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-sm hover:bg-blue-600 cursor-pointer">
                Browse Files
                <input type="file" multiple accept="image/*,video/*" class="hidden" @change="handleComposerMediaUpload" />
              </label>
            </div>
          </div>

          <!-- Feeling / Activity Picker Popover -->
          <div
            v-if="showFeelingPicker"
            class="mt-3 p-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl grid grid-cols-2 sm:grid-cols-4 gap-2 animate-fade-in"
          >
            <button
              v-for="feel in feelingsList"
              :key="feel.label"
              type="button"
              @click="selectFeeling(feel)"
              class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/60 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
            >
              <span class="text-base">{{ feel.emoji }}</span>
              <span>{{ feel.label }}</span>
            </button>
          </div>

          <!-- Attachment Actions Bar + Publish Button -->
          <div class="flex items-center justify-between mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-1 sm:gap-2">
              
              <!-- Photo/Video Action -->
              <button
                type="button"
                @click="triggerPhotoVideoPicker"
                class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1.5 transition-colors"
                :class="showMediaDropzone ? 'bg-blue-50 text-[#1877F2] dark:bg-blue-950/40' : ''"
              >
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="font-bold">Photo/Video</span>
                <input ref="mediaInputRef" type="file" multiple accept="image/*,video/*" class="hidden" @change="handleComposerMediaUpload" />
              </button>

              <!-- Poll Action -->
              <button
                type="button"
                @click="showCreatePollModal = true"
                class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1.5 transition-colors"
              >
                <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Poll</span>
              </button>

              <!-- Feeling/Activity Action -->
              <button
                type="button"
                @click="showFeelingPicker = !showFeelingPicker"
                class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1.5 transition-colors"
              >
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Feeling/Activity</span>
              </button>

            </div>

            <!-- Post Submit Button -->
            <button
              type="button"
              @click="submitGroupPost"
              :disabled="(!newPostContent.trim() && composerMediaFiles.length === 0) || isPosting"
              class="px-6 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all disabled:opacity-40 flex items-center gap-2"
            >
              <svg v-if="isPosting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ isPosting ? 'Publishing...' : 'Post' }}</span>
            </button>
          </div>
        </div>

        <!-- Feed Posts List with Rich Multiple Media Rendering -->
        <div v-if="postList.length > 0" class="space-y-4">
          <div
            v-for="post in postList"
            :key="post.id"
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 space-y-4"
          >
            <!-- Author Header -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img
                  :src="post.user?.avatar || post.user?.profile?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                  :alt="post.user?.name"
                  class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800"
                />
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                      {{ post.user?.name || 'Community Member' }}
                    </h4>
                    <span v-if="post.user_id === group.creator?.id" class="px-2 py-0.5 rounded-full bg-blue-50 text-[#1877F2] text-[10px] font-bold dark:bg-blue-950/40">
                      Admin
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    {{ post.created_at_human || 'Just now' }}
                  </p>
                </div>
              </div>

              <!-- Delete button if authorized -->
              <button
                v-if="canDeletePost(post)"
                type="button"
                @click="deletePost(post.id)"
                class="text-slate-400 hover:text-rose-500 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                title="Delete Post"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Post Content -->
            <p v-if="post.content" class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">
              {{ post.content }}
            </p>

            <!-- Post Multiple Media Gallery (Facebook Collage Layout) -->
            <div v-if="post.media && post.media.length > 0" class="rounded-2xl overflow-hidden">
              <!-- Single Image -->
              <div v-if="post.media.length === 1" class="max-h-96 w-full rounded-2xl overflow-hidden bg-slate-950">
                <img
                  :src="post.media[0].url"
                  alt="Attachment"
                  class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
                  @click="openLightbox(post.media[0].url)"
                />
              </div>

              <!-- Two Images Grid -->
              <div v-else-if="post.media.length === 2" class="grid grid-cols-2 gap-2 h-72">
                <div
                  v-for="(m, idx) in post.media"
                  :key="idx"
                  class="overflow-hidden rounded-xl bg-slate-950 cursor-pointer"
                  @click="openLightbox(m.url)"
                >
                  <img :src="m.url" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                </div>
              </div>

              <!-- Three or More Images Grid -->
              <div v-else class="grid grid-cols-3 gap-2 h-72">
                <div
                  v-for="(m, idx) in post.media.slice(0, 3)"
                  :key="idx"
                  class="relative overflow-hidden rounded-xl bg-slate-950 cursor-pointer"
                  @click="openLightbox(m.url)"
                >
                  <img :src="m.url" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                  <div
                    v-if="idx === 2 && post.media.length > 3"
                    class="absolute inset-0 bg-black/60 flex items-center justify-center text-white text-lg font-extrabold"
                  >
                    +{{ post.media.length - 3 }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Reactions (Like, Comment, Share) -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-500">
              
              <!-- Like Button -->
              <button
                type="button"
                @click="toggleLikePost(post)"
                class="flex items-center gap-1.5 transition-colors py-1.5 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800"
                :class="post.is_liked ? 'text-[#1877F2] font-bold' : 'hover:text-[#1877F2]'"
              >
                <svg class="w-4 h-4" :class="post.is_liked ? 'fill-[#1877F2]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                </svg>
                <span>{{ post.is_liked ? 'Liked' : 'Like' }}</span>
                <span v-if="post.likes_count" class="ml-1 text-slate-400">({{ post.likes_count }})</span>
              </button>

              <!-- Comment Button -->
              <button
                type="button"
                @click="togglePostComments(post)"
                class="flex items-center gap-1.5 hover:text-[#1877F2] transition-colors py-1.5 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Comment</span>
                <span v-if="post.comments_count" class="ml-1 text-slate-400">({{ post.comments_count }})</span>
              </button>

              <!-- Share Button -->
              <button
                type="button"
                @click="sharePost(post)"
                class="flex items-center gap-1.5 hover:text-[#1877F2] transition-colors py-1.5 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span>Share</span>
              </button>

            </div>

            <!-- Post Comments Section -->
            <div v-if="activeCommentPostId === post.id" class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
              <div class="flex items-center gap-2">
                <input
                  v-model="postCommentText"
                  type="text"
                  placeholder="Write a comment..."
                  class="w-full px-4 py-2 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2] focus:outline-none"
                  @keyup.enter="submitComment(post)"
                />
                <button
                  type="button"
                  @click="submitComment(post)"
                  class="px-4 py-2 rounded-full bg-[#1877F2] text-white text-xs font-bold shrink-0 hover:bg-blue-600 shadow-sm"
                >
                  Reply
                </button>
              </div>

              <!-- Comments List -->
              <div v-if="post.comments && post.comments.length > 0" class="space-y-2 mt-2">
                <div v-for="c in post.comments" :key="c.id" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-xs">
                  <div class="font-bold text-slate-800 dark:text-slate-200">{{ c.user?.name || 'Member' }}</div>
                  <div class="text-slate-600 dark:text-slate-400 mt-0.5">{{ c.content }}</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Empty Feed State -->
        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-10 text-center text-slate-400">
          <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
          <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No discussions yet</h4>
          <p class="text-xs sm:text-sm mt-1">Be the first to share an idea with this community!</p>
        </div>

      </div>

      <!-- Right Column: About Card + Members Widget + Community Rules -->
      <div class="lg:col-span-4 space-y-6">
        
        <!-- About Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-3">
            About
          </h3>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap mb-4">
            {{ group.description || 'Welcome to ' + group.name + '! Connect with other members, share insights and participate in discussions.' }}
          </p>

          <div class="space-y-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
              </svg>
              <span>{{ group.privacy === 'public' ? 'Public Group' : 'Private Group' }}</span>
            </div>

            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span>{{ group.category || 'General Community' }}</span>
            </div>

            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Created {{ group.created_at ? new Date(group.created_at).toLocaleDateString() : 'Jan 2024' }}</span>
            </div>
          </div>
        </div>

        <!-- Members Card Widget -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Members
              </h3>
              <p class="text-xs text-slate-400">
                {{ formatMemberCount(group.members_count) }} members
              </p>
            </div>
            <button
              type="button"
              @click="activeTab = 'members'"
              class="text-xs font-bold text-[#1877F2] hover:underline"
            >
              See all
            </button>
          </div>

          <!-- Members Avatar Pile -->
          <div class="flex items-center -space-x-2 overflow-hidden py-2">
            <template v-for="m in members.slice(0, 6)" :key="m.id">
              <img
                :src="m.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                :alt="m.user?.name"
                class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-slate-900 object-cover"
              />
            </template>
          </div>

          <!-- Invite Friends Button -->
          <button
            type="button"
            @click="showInviteModal = true"
            class="w-full mt-3 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Invite Friends
          </button>

          <!-- Group Admins List -->
          <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
              Group Admins
            </h4>
            <div class="space-y-3">
              <div
                v-for="adminMember in adminMembersList.slice(0, 3)"
                :key="adminMember.id"
                class="flex items-center gap-3"
              >
                <img
                  :src="adminMember.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                  :alt="adminMember.user?.name"
                  class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800"
                />
                <div>
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white">
                    {{ adminMember.user?.name }}
                  </h5>
                  <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-blue-50 text-[#1877F2] dark:bg-blue-950/40">
                    Admin
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rules Preview Card -->
        <div v-if="rulesList.length > 0" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              Group Rules
            </h3>
            <button
              type="button"
              @click="showRulesModal = true"
              class="text-xs font-bold text-[#1877F2] hover:underline"
            >
              See all
            </button>
          </div>
          <div class="space-y-2.5">
            <div
              v-for="(rule, rIdx) in rulesList.slice(0, 3)"
              :key="rule.id || rIdx"
              class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 text-xs"
            >
              <div class="font-bold text-slate-800 dark:text-slate-200">
                {{ rIdx + 1 }}. {{ rule.title }}
              </div>
              <p class="text-slate-500 line-clamp-2 mt-0.5">{{ rule.description }}</p>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- TAB 2: FEATURED / ANNOUNCEMENTS -->
    <div v-show="activeTab === 'featured'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">
            Featured Announcements
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Important notices and updates pinned by community administrators.
          </p>
        </div>
        <button
          v-if="isAdmin"
          type="button"
          @click="showAnnouncementModal = true"
          class="px-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Post Announcement
        </button>
      </div>

      <div v-if="announcementsList.length > 0" class="space-y-4">
        <div
          v-for="item in announcementsList"
          :key="item.id"
          class="p-6 rounded-3xl bg-gradient-to-r from-blue-50/50 via-indigo-50/30 to-purple-50/30 dark:from-slate-800/60 dark:to-slate-800/30 border border-blue-200/60 dark:border-slate-700 shadow-sm space-y-3"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold uppercase tracking-wider">
                📌 Pinned Announcement
              </span>
              <span class="text-xs text-slate-400">
                {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : 'Recent' }}
              </span>
            </div>
            <span v-if="item.author" class="text-xs text-slate-500 font-medium">
              By {{ item.author.name }}
            </span>
          </div>

          <h4 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ item.title }}
          </h4>

          <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
            {{ item.content }}
          </p>

          <div v-if="item.cta_url && item.cta_text" class="pt-2">
            <a
              :href="item.cta_url"
              target="_blank"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold hover:bg-blue-600 transition-colors shadow-sm"
            >
              <span>{{ item.cta_text }}</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div v-else class="py-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No announcements yet</h4>
        <p class="text-xs sm:text-sm mt-1">Community updates and guidelines will appear here.</p>
      </div>
    </div>

    <!-- TAB 3: ABOUT DETAIL VIEW -->
    <div v-show="activeTab === 'about'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-8">
      <div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
          About this group
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap mt-3 leading-relaxed">
          {{ group.description || 'Welcome to ' + group.name + '! Connect with other members, share insights and participate in discussions.' }}
        </p>
      </div>

      <!-- Quick Metadata Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
          <span class="text-xs text-slate-400 font-semibold uppercase">Privacy Status</span>
          <div class="text-sm font-bold text-slate-900 dark:text-white capitalize mt-1">
            {{ group.privacy === 'public' ? 'Public Community' : 'Private Community' }}
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ group.privacy === 'public' ? 'Anyone can see who is in the group and what they post.' : 'Only members can see who is in the group and what they post.' }}
          </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
          <span class="text-xs text-slate-400 font-semibold uppercase">Membership Mode</span>
          <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">
            {{ group.membership_approval_mode === 'approval_required' ? 'Admin Approval Required' : 'Open to All' }}
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ group.membership_approval_mode === 'approval_required' ? 'Requests must be reviewed by moderators.' : 'Anyone can join instantly.' }}
          </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
          <span class="text-xs text-slate-400 font-semibold uppercase">Post Approval</span>
          <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">
            {{ group.post_approval_mode === 'admin_approval' ? 'Posts Moderated' : 'Direct Publishing' }}
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ group.post_approval_mode === 'admin_approval' ? 'Admin must approve posts before they appear.' : 'Posts appear immediately.' }}
          </p>
        </div>
      </div>

      <!-- Guidelines and Rules -->
      <div v-if="rulesList.length > 0" class="pt-6 border-t border-slate-100 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-base font-bold text-slate-900 dark:text-white">
            Community Rules ({{ rulesList.length }})
          </h4>
          <button
            v-if="isAdmin"
            type="button"
            @click="activeTab = 'manage'; adminSubTab = 'rules';"
            class="text-xs text-[#1877F2] font-bold hover:underline"
          >
            Manage Rules
          </button>
        </div>
        <div class="space-y-3">
          <div
            v-for="(rule, index) in rulesList"
            :key="rule.id || index"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 flex items-start gap-4"
          >
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-950/60 text-[#1877F2] font-extrabold flex items-center justify-center shrink-0 text-sm">
              {{ index + 1 }}
            </div>
            <div>
              <h5 class="text-sm font-bold text-slate-900 dark:text-white">
                {{ rule.title }}
              </h5>
              <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                {{ rule.description }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Admins & Moderators Team -->
      <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
        <h4 class="text-base font-bold text-slate-900 dark:text-white mb-4">
          Leadership Team
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="admin in adminMembersList"
            :key="admin.id"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 flex items-center gap-3.5"
          >
            <img
              :src="admin.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
              :alt="admin.user?.name"
              class="w-11 h-11 rounded-full object-cover ring-2 ring-blue-500/30"
            />
            <div class="min-w-0">
              <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                {{ admin.user?.name }}
              </h5>
              <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-blue-100 text-[#1877F2] dark:bg-blue-950/60">
                {{ admin.role }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: MEMBERS DIRECTORY -->
    <div v-show="activeTab === 'members'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      
      <!-- Subtabs & Search Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="memberFilterRole = 'all'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
            :class="memberFilterRole === 'all' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            All ({{ members.length || 1 }})
          </button>
          <button
            type="button"
            @click="memberFilterRole = 'admin'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
            :class="memberFilterRole === 'admin' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            Admins ({{ adminMembersList.length }})
          </button>
          <button
            type="button"
            @click="memberFilterRole = 'moderator'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
            :class="memberFilterRole === 'moderator' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            Moderators ({{ moderatorMembersList.length }})
          </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-64">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="memberSearchQuery"
            type="text"
            placeholder="Search members..."
            class="w-full pl-10 pr-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/60 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] transition-all"
          />
        </div>
      </div>

      <!-- Members Grid List -->
      <div class="divide-y divide-slate-100 dark:divide-slate-800 mt-2">
        <div
          v-for="member in filteredMembers"
          :key="member.id"
          class="py-4 flex items-center justify-between gap-4"
        >
          <div class="flex items-center gap-3.5 min-w-0">
            <img
              :src="member.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
              :alt="member.user?.name"
              class="w-11 h-11 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800 shrink-0"
            />
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                  {{ member.user?.name }}
                </h4>
                <span
                  v-if="member.role !== 'member'"
                  class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full capitalize"
                  :class="member.role === 'owner' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/40' : 'bg-blue-100 text-[#1877F2] dark:bg-blue-950/40'"
                >
                  {{ member.role }}
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">
                Joined {{ member.joined_at || 'Recently' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a
              :href="'/messenger'"
              class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
            >
              Message
            </a>

            <!-- Admin Action Menu -->
            <div v-if="isAdmin && member.user_id !== currentUserId" class="relative">
              <button
                type="button"
                @click="toggleMemberActionMenu(member.id)"
                class="w-8 h-8 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 flex items-center justify-center transition-colors"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>

              <div
                v-if="activeMemberMenuId === member.id"
                class="absolute right-0 mt-2 w-48 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-40"
              >
                <button
                  v-if="member.role === 'member'"
                  type="button"
                  @click="updateMemberRole(member.user_id, 'moderator')"
                  class="w-full text-left px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                >
                  Make Moderator
                </button>
                <button
                  v-if="member.role === 'moderator'"
                  type="button"
                  @click="updateMemberRole(member.user_id, 'member')"
                  class="w-full text-left px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                >
                  Remove Moderator Role
                </button>
                <button
                  type="button"
                  @click="banMember(member.user_id)"
                  class="w-full text-left px-4 py-2 text-xs text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30"
                >
                  Ban from Group
                </button>
                <div class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>
                <button
                  type="button"
                  @click="removeMember(member.user_id)"
                  class="w-full text-left px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                >
                  Remove from Group
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredMembers.length === 0" class="py-12 text-center text-slate-400 text-sm">
          No members found matching your search.
        </div>
      </div>
    </div>

    <!-- TAB 5: EVENTS TAB -->
    <div v-show="activeTab === 'events'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">
            Upcoming Community Events
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Webinars, meetups, workshops, and study sessions organized by the group.
          </p>
        </div>
        <button
          v-if="canPost"
          type="button"
          @click="showCreateEventModal = true"
          class="px-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Event
        </button>
      </div>

      <!-- Events List -->
      <div v-if="eventsList.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div
          v-for="event in eventsList"
          :key="event.id"
          class="p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 hover:shadow-md transition-all flex flex-col justify-between"
        >
          <div>
            <div class="flex items-start gap-4 mb-3">
              <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col items-center justify-center shrink-0">
                <span class="text-[10px] font-extrabold uppercase text-[#1877F2]">
                  {{ formatEventMonth(event.start_time) }}
                </span>
                <span class="text-lg font-black text-slate-900 dark:text-white">
                  {{ formatEventDay(event.start_time) }}
                </span>
              </div>

              <div class="min-w-0">
                <h4 class="text-base font-bold text-slate-900 dark:text-white truncate">
                  {{ event.title }}
                </h4>
                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                  <span>{{ formatEventTime(event.start_time) }}</span>
                  <span v-if="event.is_online">•</span>
                  <span v-if="event.is_online" class="text-emerald-600 font-semibold">Online Meeting</span>
                </div>
              </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-3 mb-4 leading-relaxed">
              {{ event.description || 'Join our community session to learn, connect and collaborate.' }}
            </p>

            <div v-if="event.location" class="flex items-center gap-1.5 text-xs text-slate-500 mb-4">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ event.location }}</span>
            </div>
          </div>

          <!-- RSVP Action Bar -->
          <div class="pt-3 border-t border-slate-200/60 dark:border-slate-700 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500">
              👥 {{ event.going_count || event.attendees_count || 0 }} going
            </span>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="submitEventRsvp(event, 'going')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                :class="event.user_rsvp === 'going' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-emerald-100 hover:text-emerald-800'"
              >
                Going ✓
              </button>
              <button
                type="button"
                @click="submitEventRsvp(event, 'interested')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                :class="event.user_rsvp === 'interested' ? 'bg-[#1877F2] text-white shadow-sm' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-blue-100 hover:text-blue-800'"
              >
                Interested ★
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="py-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No events scheduled yet</h4>
        <p class="text-xs sm:text-sm mt-1">Host webinars, local hackathons, or workshops for members!</p>
      </div>
    </div>

    <!-- TAB 6: MEDIA GALLERY -->
    <div v-show="activeTab === 'media'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">
            Community Media Gallery
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Photos and video memories shared by members.
          </p>
        </div>

        <label class="px-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload Photo
          <input type="file" accept="image/*" class="hidden" @change="uploadMediaToGallery" />
        </label>
      </div>

      <!-- Media Grid -->
      <div v-if="mediaGalleryList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <div
          v-for="(item, idx) in mediaGalleryList"
          :key="idx"
          class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 relative group cursor-pointer shadow-sm hover:shadow-lg transition-all"
          @click="openLightbox(item.url)"
        >
          <img :src="item.url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
            </svg>
          </div>
        </div>
      </div>

      <div v-else class="py-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No media uploaded yet</h4>
        <p class="text-xs sm:text-sm mt-1">Upload event photos, screenshots, and artwork to share with everyone!</p>
      </div>
    </div>

    <!-- TAB 7: FILES & DOCUMENTS -->
    <div v-show="activeTab === 'files'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">
            Group Files & Documents
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Download guides, cheat-sheets, presentations, and resources.
          </p>
        </div>

        <button
          v-if="canPost"
          type="button"
          @click="showUploadFileModal = true"
          class="px-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload Document
        </button>
      </div>

      <!-- File List Table -->
      <div v-if="filesList.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
        <div
          v-for="file in filesList"
          :key="file.id"
          class="py-4 flex items-center justify-between gap-4"
        >
          <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="min-w-0">
              <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                {{ file.name }}
              </h4>
              <p class="text-xs text-slate-400 mt-0.5">
                {{ formatFileSize(file.file_size) }} • Uploaded {{ file.created_at ? new Date(file.created_at).toLocaleDateString() : 'Recently' }}
              </p>
            </div>
          </div>

          <a
            :href="file.file_url"
            target="_blank"
            download
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download
          </a>
        </div>
      </div>

      <div v-else class="py-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No documents uploaded</h4>
        <p class="text-xs sm:text-sm mt-1">Share PDFs, slide decks, and project templates with members.</p>
      </div>
    </div>

    <!-- TAB 8: POLLS & QUESTIONS -->
    <div v-show="activeTab === 'polls'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">
            Community Polls & Voting
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Cast your vote and see live opinion stats across the community.
          </p>
        </div>

        <button
          v-if="canPost"
          type="button"
          @click="showCreatePollModal = true"
          class="px-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Poll
        </button>
      </div>

      <!-- Polls Cards List -->
      <div v-if="pollsList.length > 0" class="space-y-6">
        <div
          v-for="poll in pollsList"
          :key="poll.id"
          class="p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/30 space-y-4"
        >
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/60 text-[10px] font-bold uppercase">
              📊 Live Poll
            </span>
            <span class="text-xs text-slate-400">
              {{ poll.total_votes || 0 }} total votes
            </span>
          </div>

          <h4 class="text-base font-bold text-slate-900 dark:text-white">
            {{ poll.question }}
          </h4>

          <!-- Poll Options -->
          <div class="space-y-2.5">
            <div
              v-for="opt in poll.options"
              :key="opt.id"
              class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3.5 cursor-pointer transition-all hover:border-[#1877F2]"
              @click="submitPollVote(poll, opt.id)"
            >
              <!-- Animated Progress Bar -->
              <div
                class="absolute inset-y-0 left-0 bg-blue-100/60 dark:bg-blue-900/30 transition-all duration-500"
                :style="{ width: calculatePollPercent(poll, opt.votes_count) + '%' }"
              ></div>

              <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div
                    class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                    :class="opt.is_voted ? 'border-[#1877F2] bg-[#1877F2]' : 'border-slate-300 dark:border-slate-600'"
                  >
                    <div v-if="opt.is_voted" class="w-2 h-2 rounded-full bg-white"></div>
                  </div>
                  <span class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ opt.option_text || opt.text }}
                  </span>
                </div>

                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                  {{ calculatePollPercent(poll, opt.votes_count) }}% ({{ opt.votes_count || 0 }})
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="py-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No active polls</h4>
        <p class="text-xs sm:text-sm mt-1">Start a poll to get feedback or community opinions.</p>
      </div>
    </div>

    <!-- TAB 9: MANAGE & ADMIN TOOLS (Admin Only) -->
    <div v-if="isAdmin" v-show="activeTab === 'manage'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <h3 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Community Management Hub
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Review membership requests, moderate posts, manage community rules, and edit settings.
          </p>
        </div>
      </div>

      <!-- Admin Sub-Navigation -->
      <div class="flex items-center gap-2 pb-2 overflow-x-auto no-scrollbar">
        <button
          type="button"
          @click="adminSubTab = 'requests'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2"
          :class="adminSubTab === 'requests' ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
        >
          <span>Pending Requests</span>
          <span v-if="pendingJoinRequests.length > 0" class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px]">
            {{ pendingJoinRequests.length }}
          </span>
        </button>

        <button
          type="button"
          @click="adminSubTab = 'moderation'; fetchModerationPosts();"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2"
          :class="adminSubTab === 'moderation' ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
        >
          <span>Moderation Queue</span>
          <span v-if="moderationPostsList.length > 0" class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px]">
            {{ moderationPostsList.length }}
          </span>
        </button>

        <button
          type="button"
          @click="adminSubTab = 'rules'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
          :class="adminSubTab === 'rules' ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
        >
          Rules Manager
        </button>

        <button
          type="button"
          @click="adminSubTab = 'settings'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
          :class="adminSubTab === 'settings' ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
        >
          Group Settings
        </button>
      </div>

      <!-- Sub-Tab 1: Member Requests -->
      <div v-show="adminSubTab === 'requests'" class="space-y-4">
        <div v-if="pendingJoinRequests.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="req in pendingJoinRequests"
            :key="req.id"
            class="py-4 flex items-center justify-between gap-4"
          >
            <div class="flex items-center gap-3.5">
              <img
                :src="req.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                :alt="req.user?.name"
                class="w-11 h-11 rounded-full object-cover ring-2 ring-slate-100"
              />
              <div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ req.user?.name }}
                </h4>
                <p class="text-xs text-slate-400">
                  Requested on {{ req.created_at ? new Date(req.created_at).toLocaleDateString() : 'Recently' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="approveMemberRequest(req.user_id)"
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-all"
              >
                Approve
              </button>
              <button
                type="button"
                @click="rejectMemberRequest(req.user_id)"
                class="px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200 transition-all"
              >
                Decline
              </button>
            </div>
          </div>
        </div>

        <div v-else class="py-12 text-center text-slate-400">
          <p class="text-sm">No pending membership requests right now.</p>
        </div>
      </div>

      <!-- Sub-Tab 2: Moderation Posts Queue -->
      <div v-show="adminSubTab === 'moderation'" class="space-y-4">
        <div v-if="moderationPostsList.length > 0" class="space-y-4">
          <div
            v-for="modPost in moderationPostsList"
            :key="modPost.id"
            class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <img
                  :src="modPost.user?.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                  class="w-8 h-8 rounded-full object-cover"
                />
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                  {{ modPost.user?.name || 'Member' }}
                </span>
              </div>
              <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">
                Awaiting Approval
              </span>
            </div>

            <p class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap">
              {{ modPost.content }}
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-700">
              <button
                type="button"
                @click="approvePostItem(modPost.id)"
                class="px-4 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"
              >
                Approve Post
              </button>
              <button
                type="button"
                @click="rejectPostItem(modPost.id)"
                class="px-4 py-1.5 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold border border-rose-200 hover:bg-rose-100"
              >
                Reject
              </button>
            </div>
          </div>
        </div>

        <div v-else class="py-12 text-center text-slate-400">
          <p class="text-sm">Moderation queue is clean! No posts pending review.</p>
        </div>
      </div>

      <!-- Sub-Tab 3: Rules Manager -->
      <div v-show="adminSubTab === 'rules'" class="space-y-6">
        <div class="p-5 rounded-2xl bg-blue-50/50 dark:bg-slate-800/50 border border-blue-100 dark:border-slate-700 space-y-4">
          <h4 class="text-sm font-bold text-slate-900 dark:text-white">
            Add New Community Rule
          </h4>
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rule Title</label>
              <input
                v-model="newRuleForm.title"
                type="text"
                placeholder="e.g. Be kind and respectful"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rule Description</label>
              <textarea
                v-model="newRuleForm.description"
                rows="2"
                placeholder="Explain the guideline and expectations clearly..."
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              ></textarea>
            </div>
            <button
              type="button"
              @click="submitCreateRule"
              :disabled="!newRuleForm.title.trim() || !newRuleForm.description.trim()"
              class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-sm hover:bg-blue-600 disabled:opacity-50"
            >
              Add Rule
            </button>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">
            Active Community Rules ({{ rulesList.length }})
          </h4>
          <div
            v-for="(rule, idx) in rulesList"
            :key="rule.id || idx"
            class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
          >
            <div class="font-bold text-xs text-slate-900 dark:text-white">
              {{ idx + 1 }}. {{ rule.title }}
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ rule.description }}</p>
          </div>
        </div>
      </div>

      <!-- Sub-Tab 4: Settings -->
      <div v-show="adminSubTab === 'settings'" class="space-y-5 max-w-xl">
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Group Name</label>
          <input
            v-model="editGroupForm.name"
            type="text"
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Group Description</label>
          <textarea
            v-model="editGroupForm.description"
            rows="3"
            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
          <input
            v-model="editGroupForm.category"
            type="text"
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Privacy Mode</label>
            <select
              v-model="editGroupForm.privacy"
              class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            >
              <option value="public">Public</option>
              <option value="private">Private</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Membership Mode</label>
            <select
              v-model="editGroupForm.membership_approval_mode"
              class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            >
              <option value="anyone">Open to Everyone</option>
              <option value="approval_required">Approval Required</option>
            </select>
          </div>
        </div>

        <button
          type="button"
          @click="saveGroupSettings"
          :disabled="isSavingSettings"
          class="px-6 py-2.5 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
        >
          {{ isSavingSettings ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>

    </div>

    <!-- MODAL 1: INVITE FRIENDS -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Invite Friends to Community
          </h3>
          <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
          <input
            v-model="inviteSearchQuery"
            type="text"
            placeholder="Search friends..."
            class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800"
          />
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 p-2">
          <div
            v-for="friend in filteredInviteFriends"
            :key="friend.id"
            class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
            @click="toggleInviteFriend(friend.id)"
          >
            <div class="flex items-center gap-3">
              <img
                :src="friend.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                :alt="friend.name"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div>
                <h5 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">{{ friend.name }}</h5>
                <p class="text-[10px] text-slate-400">{{ friend.mutual }}</p>
              </div>
            </div>

            <div
              class="w-5 h-5 rounded-md border flex items-center justify-center transition-colors"
              :class="selectedInviteIds.includes(friend.id) ? 'bg-[#1877F2] border-[#1877F2] text-white' : 'border-slate-300 dark:border-slate-600'"
            >
              <svg v-if="selectedInviteIds.includes(friend.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showInviteModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="sendGroupInvitations"
            :disabled="selectedInviteIds.length === 0 || isSendingInvites"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md shadow-blue-500/20 disabled:opacity-40"
          >
            {{ isSendingInvites ? 'Sending...' : 'Send (' + selectedInviteIds.length + ')' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: NOTIFICATIONS SETTINGS -->
    <div
      v-if="showNotificationsModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#1877F2] flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              Group Notifications
            </h3>
          </div>
          <button @click="showNotificationsModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-4">
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">New posts</h4>
              <p class="text-xs text-slate-400">Notified when new posts are shared in this group.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.new_posts" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Comments</h4>
              <p class="text-xs text-slate-400">Notified about comments on your posts.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.comments" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Events</h4>
              <p class="text-xs text-slate-400">Notified about upcoming group events.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.events" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showNotificationsModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveNotificationPreferences"
            :disabled="isSavingPrefs"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md shadow-blue-500/20 disabled:opacity-50"
          >
            {{ isSavingPrefs ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 3: RULES MODAL -->
    <div
      v-if="showRulesModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Community Rules & Standards
          </h3>
          <button @click="showRulesModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 max-h-96 overflow-y-auto space-y-3">
          <div
            v-for="(rule, idx) in rulesList"
            :key="rule.id || idx"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800"
          >
            <h5 class="text-sm font-bold text-slate-900 dark:text-white">
              {{ idx + 1 }}. {{ rule.title }}
            </h5>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
              {{ rule.description }}
            </p>
          </div>

          <div v-if="rulesList.length === 0" class="py-8 text-center text-slate-400 text-xs">
            No specific rules created for this group yet. Please follow standard platform civility rules.
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
          <button
            type="button"
            @click="showRulesModal = false"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold"
          >
            I Understand
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 4: REPORT GROUP -->
    <div
      v-if="showReportModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white text-rose-600 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Report Group to Moderators
          </h3>
          <button @click="showReportModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reason Category</label>
            <select
              v-model="reportForm.category"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            >
              <option value="spam">Spam or Scam</option>
              <option value="harassment">Hate Speech or Harassment</option>
              <option value="inappropriate_content">Inappropriate Content</option>
              <option value="false_information">Misinformation</option>
              <option value="other">Other Violation</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Details</label>
            <textarea
              v-model="reportForm.description"
              rows="3"
              placeholder="Please provide details about what violates community guidelines..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            ></textarea>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showReportModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitReport"
            :disabled="!reportForm.description.trim() || isSubmittingReport"
            class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold shadow-md hover:bg-rose-700 disabled:opacity-50"
          >
            {{ isSubmittingReport ? 'Submitting...' : 'Submit Report' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 5: CREATE EVENT -->
    <div
      v-if="showCreateEventModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Schedule a Group Event
          </h3>
          <button @click="showCreateEventModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4 max-h-96 overflow-y-auto">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Event Title *</label>
            <input
              v-model="createEventForm.title"
              type="text"
              placeholder="e.g. Weekly Tech Talk & Q&A"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
            <textarea
              v-model="createEventForm.description"
              rows="3"
              placeholder="What will members discuss and learn?"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time *</label>
              <input
                v-model="createEventForm.start_time"
                type="datetime-local"
                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Time</label>
              <input
                v-model="createEventForm.end_time"
                type="datetime-local"
                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" id="is_online" v-model="createEventForm.is_online" class="rounded" />
            <label for="is_online" class="text-xs font-semibold text-slate-700 dark:text-slate-300">This is an online video event</label>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Location or Meeting Link</label>
            <input
              v-model="createEventForm.location"
              type="text"
              placeholder="e.g. Google Meet link or auditorium address"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showCreateEventModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitCreateEvent"
            :disabled="!createEventForm.title.trim() || !createEventForm.start_time || isCreatingEvent"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ isCreatingEvent ? 'Scheduling...' : 'Create Event' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 6: CREATE POLL -->
    <div
      v-if="showCreatePollModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Create Community Poll
          </h3>
          <button @click="showCreatePollModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4 max-h-96 overflow-y-auto">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Question *</label>
            <input
              v-model="createPollForm.question"
              type="text"
              placeholder="Ask a question..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Poll Options</label>
            <div
              v-for="(opt, oIdx) in createPollForm.options"
              :key="oIdx"
              class="flex items-center gap-2"
            >
              <input
                v-model="createPollForm.options[oIdx]"
                type="text"
                :placeholder="'Option ' + (oIdx + 1)"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
              <button
                v-if="createPollForm.options.length > 2"
                type="button"
                @click="createPollForm.options.splice(oIdx, 1)"
                class="text-rose-500 hover:text-rose-700 p-1.5"
              >
                ✕
              </button>
            </div>
            <button
              v-if="createPollForm.options.length < 6"
              type="button"
              @click="createPollForm.options.push('')"
              class="text-xs text-[#1877F2] font-bold hover:underline pt-1 flex items-center gap-1"
            >
              + Add Option
            </button>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showCreatePollModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitCreatePoll"
            :disabled="!createPollForm.question.trim() || isCreatingPoll"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ isCreatingPoll ? 'Creating...' : 'Create Poll' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 7: POST ANNOUNCEMENT (Admin Only) -->
    <div
      v-if="showAnnouncementModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Publish Pinned Announcement
          </h3>
          <button @click="showAnnouncementModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Announcement Title *</label>
            <input
              v-model="createAnnouncementForm.title"
              type="text"
              placeholder="e.g. Welcome to Bangladesh Developers Community!"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Message Content *</label>
            <textarea
              v-model="createAnnouncementForm.content"
              rows="4"
              placeholder="Detailed guidelines, welcoming note, or important links..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Action Button Text</label>
              <input
                v-model="createAnnouncementForm.cta_text"
                type="text"
                placeholder="e.g. Read Guidelines"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Action Button URL</label>
              <input
                v-model="createAnnouncementForm.cta_url"
                type="text"
                placeholder="https://..."
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showAnnouncementModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitCreateAnnouncement"
            :disabled="!createAnnouncementForm.title.trim() || !createAnnouncementForm.content.trim() || isCreatingAnnouncement"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ isCreatingAnnouncement ? 'Publishing...' : 'Publish' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 8: UPLOAD FILE -->
    <div
      v-if="showUploadFileModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Upload Document to Group
          </h3>
          <button @click="showUploadFileModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Document Title *</label>
            <input
              v-model="uploadFileForm.name"
              type="text"
              placeholder="e.g. Developer Roadmap 2026.pdf"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">File or Download URL *</label>
            <input
              v-model="uploadFileForm.file_url"
              type="text"
              placeholder="https://... or /storage/files/..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showUploadFileModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitUploadFile"
            :disabled="!uploadFileForm.name.trim() || !uploadFileForm.file_url.trim() || isUploadingFile"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ isUploadingFile ? 'Uploading...' : 'Save File' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 9: EDIT GROUP SETTINGS (Admin Only) -->
    <div
      v-if="showEditGroupModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Edit Group Settings
          </h3>
          <button @click="showEditGroupModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-5 space-y-4 max-h-96 overflow-y-auto">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Group Name</label>
            <input
              v-model="editGroupForm.name"
              type="text"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
            <textarea
              v-model="editGroupForm.description"
              rows="3"
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
              <input
                v-model="editGroupForm.category"
                type="text"
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Privacy Mode</label>
              <select
                v-model="editGroupForm.privacy"
                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-900"
              >
                <option value="public">Public</option>
                <option value="private">Private</option>
              </select>
            </div>
          </div>
        </div>

        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
          <button
            type="button"
            @click="showEditGroupModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveGroupSettings"
            :disabled="isSavingSettings"
            class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md hover:bg-blue-600 disabled:opacity-50"
          >
            {{ isSavingSettings ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 10: FULLSCREEN MEDIA LIGHTBOX -->
    <div
      v-if="activeLightboxUrl"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
      @click.self="activeLightboxUrl = null"
    >
      <button
        type="button"
        @click="activeLightboxUrl = null"
        class="absolute top-5 right-5 text-white/80 hover:text-white p-2 rounded-full bg-white/10 hover:bg-white/20 transition-all text-xl"
      >
        ✕
      </button>
      <div class="max-w-4xl max-h-[85vh] rounded-2xl overflow-hidden shadow-2xl">
        <img :src="activeLightboxUrl" class="w-full h-full object-contain" />
      </div>
    </div>

  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../../types';

interface GroupData {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  category: string | null;
  privacy: 'public' | 'private' | 'hidden' | 'invite_only';
  avatar_url: string | null;
  cover_image_url: string | null;
  members_count: number;
  is_verified?: boolean;
  membership_approval_mode?: string;
  post_approval_mode?: string;
  membership?: {
    role: string;
    status: string;
  } | null;
  viewer_membership?: {
    role: string;
    status: string;
  } | null;
  created_at?: string;
  creator?: {
    id: number;
    name: string;
  };
  rules?: Array<{ id?: number; title: string; description: string; enforcement_level?: string }>;
  announcements?: Array<{ id: number; title: string; content: string; cta_text?: string; cta_url?: string; created_at?: string; author?: { name: string } }>;
  files?: Array<{ id: number; name: string; file_url: string; file_size?: number; created_at?: string }>;
  polls?: Array<any>;
  events?: Array<any>;
}

const props = withDefaults(
  defineProps<{
    group: GroupData;
    members: any[];
    posts: { data: any[]; next_cursor?: string | null };
    friends: any[];
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
    unreadCount?: number;
    unreadMessagesCount?: number;
  }>(),
  {
    members: () => [],
    posts: () => ({ data: [] }),
    friends: () => [],
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
    unreadCount: 0,
    unreadMessagesCount: 0,
  }
);

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const currentUserAvatar = computed(() => (page.props.auth as any)?.user?.avatar_url || (page.props.auth as any)?.user?.profile?.avatar_url);

// Active Tab
const activeTab = ref<string>('posts');
const adminSubTab = ref<'requests' | 'moderation' | 'rules' | 'settings'>('requests');

// Toast Feedback
const toastMessage = ref<string | null>(null);
const showToast = (msg: string) => {
  toastMessage.value = msg;
  setTimeout(() => {
    if (toastMessage.value === msg) {
      toastMessage.value = null;
    }
  }, 3500);
};

// Shortcut Pin
const isShortcutPinned = ref<boolean>(false);
const togglePinShortcut = () => {
  showMemberMenu.value = false;
  showMoreMenu.value = false;
  isShortcutPinned.value = !isShortcutPinned.value;
  showToast(isShortcutPinned.value ? 'Group pinned to your Shortcuts!' : 'Group removed from Shortcuts.');
};

// Member Status (Creator/Admin is automatically recognized as active member)
const isMember = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  if (m && m.status === 'active') return true;
  if (currentUserId.value && props.group.creator?.id === currentUserId.value) return true;
  return false;
});

const isPendingApproval = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  return m && m.status === 'pending';
});

const isAdmin = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  if (m && ['owner', 'admin'].includes(m.role)) return true;
  return Boolean(currentUserId.value && props.group.creator?.id === currentUserId.value);
});

const canPost = computed(() => {
  if (isAdmin.value) return true;
  return isMember.value;
});

// Dropdowns
const showMemberMenu = ref<boolean>(false);
const showMoreMenu = ref<boolean>(false);
const activeMemberMenuId = ref<number | null>(null);

// Data Lists
const postList = ref<any[]>([...props.posts.data]);
const announcementsList = ref<any[]>([...(props.group.announcements || [])]);
const eventsList = ref<any[]>([...(props.group.events || [])]);
const filesList = ref<any[]>([...(props.group.files || [])]);
const pollsList = ref<any[]>([...(props.group.polls || [])]);
const rulesList = ref<any[]>([...(props.group.rules || [])]);
const mediaGalleryList = ref<Array<{ url: string }>>([
  ...(props.group.cover_image_url ? [{ url: props.group.cover_image_url }] : []),
  ...((props.posts.data || []).flatMap(p => p.media || []).map((m: any) => ({ url: m.url })))
]);

// Admin Lists
const pendingJoinRequests = ref<any[]>([]);
const moderationPostsList = ref<any[]>([]);
const pendingRequestsCount = computed(() => pendingJoinRequests.value.length + moderationPostsList.value.length);

// Post Composer State
const newPostContent = ref<string>('');
const isPosting = ref<boolean>(false);
const showMediaDropzone = ref<boolean>(false);
const isDraggingMedia = ref<boolean>(false);
const isUploadingMedia = ref<boolean>(false);
const composerMediaFiles = ref<Array<{ id?: number; url: string; name?: string }>>([]);
const mediaInputRef = ref<HTMLInputElement | null>(null);

// Feelings / Activity Picker
const showFeelingPicker = ref<boolean>(false);
const selectedFeeling = ref<{ label: string; emoji: string } | null>(null);
const feelingsList = [
  { label: 'Happy', emoji: '😊' },
  { label: 'Excited', emoji: '🚀' },
  { label: 'Coding', emoji: '💻' },
  { label: 'Blessed', emoji: '🙏' },
  { label: 'Inspired', emoji: '💡' },
  { label: 'Focused', emoji: '🎯' },
  { label: 'Celebrating', emoji: '🎉' },
  { label: 'Drinking Coffee', emoji: '☕' },
  { label: 'Learning', emoji: '📚' },
  { label: 'Proud', emoji: '🌟' },
];

const selectFeeling = (feel: { label: string; emoji: string }) => {
  selectedFeeling.value = feel;
  showFeelingPicker.value = false;
  showToast(`Feeling ${feel.label} ${feel.emoji} added!`);
};

const triggerPhotoVideoPicker = () => {
  showMediaDropzone.value = true;
  mediaInputRef.value?.click();
};

// Post Comments
const activeCommentPostId = ref<number | null>(null);
const postCommentText = ref<string>('');

// Modals
const showInviteModal = ref<boolean>(false);
const showNotificationsModal = ref<boolean>(false);
const showRulesModal = ref<boolean>(false);
const showReportModal = ref<boolean>(false);
const showCreateEventModal = ref<boolean>(false);
const showCreatePollModal = ref<boolean>(false);
const showAnnouncementModal = ref<boolean>(false);
const showUploadFileModal = ref<boolean>(false);
const showEditGroupModal = ref<boolean>(false);
const activeLightboxUrl = ref<string | null>(null);

// Forms
const isJoining = ref<boolean>(false);
const isSendingInvites = ref<boolean>(false);
const inviteSearchQuery = ref<string>('');
const selectedInviteIds = ref<number[]>([]);
const isSavingPrefs = ref<boolean>(false);
const isCreatingEvent = ref<boolean>(false);
const isCreatingPoll = ref<boolean>(false);
const isCreatingAnnouncement = ref<boolean>(false);
const isUploadingFile = ref<boolean>(false);
const isSavingSettings = ref<boolean>(false);
const isSubmittingReport = ref<boolean>(false);

const notificationSettings = reactive({
  new_posts: true,
  comments: true,
  events: true,
});

const createEventForm = reactive({
  title: '',
  description: '',
  location: '',
  is_online: false,
  start_time: '',
  end_time: '',
});

const createPollForm = reactive({
  question: '',
  options: ['', ''],
});

const createAnnouncementForm = reactive({
  title: '',
  content: '',
  cta_text: '',
  cta_url: '',
});

const uploadFileForm = reactive({
  name: '',
  file_url: '',
});

const editGroupForm = reactive({
  name: props.group.name || '',
  description: props.group.description || '',
  category: props.group.category || '',
  privacy: props.group.privacy || 'public',
  membership_approval_mode: props.group.membership_approval_mode || 'anyone',
});

const newRuleForm = reactive({
  title: '',
  description: '',
});

const reportForm = reactive({
  category: 'spam',
  description: '',
});

// Members Tab Filters
const memberSearchQuery = ref<string>('');
const memberFilterRole = ref<'all' | 'admin' | 'moderator'>('all');

const adminMembersList = computed(() => {
  return props.members.filter(m => ['owner', 'admin'].includes(m.role));
});

const moderatorMembersList = computed(() => {
  return props.members.filter(m => m.role === 'moderator');
});

const filteredMembers = computed(() => {
  let list = props.members;
  if (memberFilterRole.value === 'admin') {
    list = adminMembersList.value;
  } else if (memberFilterRole.value === 'moderator') {
    list = moderatorMembersList.value;
  }

  if (memberSearchQuery.value.trim()) {
    const q = memberSearchQuery.value.toLowerCase();
    list = list.filter(m => m.user?.name?.toLowerCase().includes(q));
  }
  return list;
});

const filteredInviteFriends = computed(() => {
  if (!inviteSearchQuery.value.trim()) return props.friends;
  const q = inviteSearchQuery.value.toLowerCase();
  return props.friends.filter(f => f.name?.toLowerCase().includes(q));
});

const formatMemberCount = (num: number): string => {
  if (!num) return '1.5K';
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  }
  return num.toLocaleString();
};

const formatFileSize = (bytes?: number): string => {
  if (!bytes) return 'File Document';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const formatEventMonth = (dateStr: string): string => {
  if (!dateStr) return 'OCT';
  return new Date(dateStr).toLocaleString('en-US', { month: 'short' }).toUpperCase();
};

const formatEventDay = (dateStr: string): string => {
  if (!dateStr) return '1';
  return String(new Date(dateStr).getDate());
};

const formatEventTime = (dateStr: string): string => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

// ======================
// REAL-TIME ACTIONS
// ======================

const joinGroup = async () => {
  isJoining.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/join`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      if (resData.data?.status === 'active' || resData.data?.status === 'joined') {
        const activeMem = { role: 'member', status: 'active' };
        props.group.membership = activeMem;
        props.group.viewer_membership = activeMem;
        props.group.members_count = (props.group.members_count || 0) + 1;
        showToast('Welcome! You have joined the group.');
      } else {
        const pendingMem = { role: 'member', status: 'pending' };
        props.group.membership = pendingMem;
        props.group.viewer_membership = pendingMem;
        showToast('Join request submitted to group moderators.');
      }
    } else {
      showToast(resData.message || 'Failed to join group.');
    }
  } catch (err: any) {
    showToast('Failed to join group.');
  } finally {
    isJoining.value = false;
  }
};

const cancelJoinRequest = async () => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/requests/cancel`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      props.group.membership = null;
      props.group.viewer_membership = null;
      showToast('Join request cancelled.');
    } else {
      showToast(resData.message || 'Failed to cancel join request.');
    }
  } catch (err: any) {
    showToast('Failed to cancel join request.');
  }
};

const leaveGroup = async () => {
  showMemberMenu.value = false;
  if (!confirm('Are you sure you want to leave this group?')) return;

  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/leave`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      props.group.membership = null;
      props.group.viewer_membership = null;
      props.group.members_count = Math.max(0, (props.group.members_count || 1) - 1);
      showToast('You left the group.');
    } else {
      showToast(resData.message || 'Failed to leave group.');
    }
  } catch (err: any) {
    showToast('Failed to leave group.');
  }
};

// MULTI-PHOTO / VIDEO UPLOAD HANDLERS
const uploadFileItem = async (file: File) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', 'general');

  const csrf = getCsrfToken();
  const res = await fetch('/api/v1/groups/media/upload', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
    },
    body: formData,
  });
  const resData = await res.json();
  if (res.ok && resData.data?.url) {
    composerMediaFiles.value.push({
      id: resData.data.id,
      url: resData.data.url,
      name: file.name,
    });
  }
};

const handleComposerMediaUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const files = target.files;
  if (!files || files.length === 0) return;

  showMediaDropzone.value = true;
  isUploadingMedia.value = true;
  try {
    for (let i = 0; i < files.length; i++) {
      await uploadFileItem(files[i]);
    }
    showToast(`${files.length} photo(s) attached to post!`);
  } catch (err) {
    showToast('Error uploading some media files.');
  } finally {
    isUploadingMedia.value = false;
    target.value = '';
  }
};

const handleMediaDrop = async (e: DragEvent) => {
  isDraggingMedia.value = false;
  const files = e.dataTransfer?.files;
  if (!files || files.length === 0) return;

  isUploadingMedia.value = true;
  try {
    for (let i = 0; i < files.length; i++) {
      if (files[i].type.startsWith('image/') || files[i].type.startsWith('video/')) {
        await uploadFileItem(files[i]);
      }
    }
    showToast('Media files uploaded and attached!');
  } catch (err) {
    showToast('Error uploading media files.');
  } finally {
    isUploadingMedia.value = false;
  }
};

const removeComposerMedia = (idx: number) => {
  composerMediaFiles.value.splice(idx, 1);
};

// SUBMIT POST WITH FULL CONTENT & PHOTO/VIDEO
const submitGroupPost = async () => {
  let content = newPostContent.value.trim();
  if (selectedFeeling.value) {
    content = content ? `${content} — feeling ${selectedFeeling.value.label.toLowerCase()} ${selectedFeeling.value.emoji}` : `feeling ${selectedFeeling.value.label.toLowerCase()} ${selectedFeeling.value.emoji}`;
  }

  if (!content && composerMediaFiles.value.length === 0) {
    showToast('Please write something or attach a photo/video.');
    return;
  }

  isPosting.value = true;
  try {
    const csrf = getCsrfToken();
    const mediaIds = composerMediaFiles.value.map(m => m.id).filter(Boolean);

    const res = await fetch(`/api/v1/groups/${props.group.id}/posts`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        content,
        type: composerMediaFiles.value.length > 0 ? 'media' : 'text',
        media_ids: mediaIds,
      }),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      // Ensure media is present on the newly rendered post
      const newPost = resData.data;
      if ((!newPost.media || newPost.media.length === 0) && composerMediaFiles.value.length > 0) {
        newPost.media = composerMediaFiles.value.map(m => ({ url: m.url }));
      }
      postList.value.unshift(newPost);
      
      // Update media gallery tab
      composerMediaFiles.value.forEach(m => {
        mediaGalleryList.value.unshift({ url: m.url });
      });

      // Reset composer
      newPostContent.value = '';
      composerMediaFiles.value = [];
      selectedFeeling.value = null;
      showMediaDropzone.value = false;
      showToast('Your post with photos has been published!');
    } else {
      showToast(resData.message || 'Post submitted for admin approval.');
      newPostContent.value = '';
      composerMediaFiles.value = [];
      selectedFeeling.value = null;
      showMediaDropzone.value = false;
    }
  } catch (err: any) {
    showToast('Failed to create group post.');
  } finally {
    isPosting.value = false;
  }
};

const canDeletePost = (post: any): boolean => {
  if (isAdmin.value) return true;
  return post.user_id === currentUserId.value;
};

const deletePost = async (postId: number) => {
  if (!confirm('Are you sure you want to delete this post?')) return;

  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/posts/${postId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      postList.value = postList.value.filter(p => p.id !== postId);
      showToast('Post removed successfully.');
    } else {
      showToast('Failed to delete post.');
    }
  } catch (err: any) {
    showToast('Failed to delete post.');
  }
};

const toggleLikePost = (post: any) => {
  post.is_liked = !post.is_liked;
  post.likes_count = Math.max(0, (post.likes_count || 0) + (post.is_liked ? 1 : -1));
};

const togglePostComments = (post: any) => {
  activeCommentPostId.value = activeCommentPostId.value === post.id ? null : post.id;
};

const submitComment = (post: any) => {
  if (!postCommentText.value.trim()) return;
  if (!post.comments) post.comments = [];
  post.comments.push({
    id: Date.now(),
    content: postCommentText.value.trim(),
    user: { name: 'You' },
  });
  post.comments_count = (post.comments_count || 0) + 1;
  postCommentText.value = '';
  showToast('Comment added!');
};

const sharePost = (post: any) => {
  navigator.clipboard.writeText(window.location.origin + '/groups/' + props.group.slug);
  showToast('Post share link copied to clipboard!');
};

const toggleInviteFriend = (id: number) => {
  const index = selectedInviteIds.value.indexOf(id);
  if (index > -1) {
    selectedInviteIds.value.splice(index, 1);
  } else {
    selectedInviteIds.value.push(id);
  }
};

const sendGroupInvitations = async () => {
  if (selectedInviteIds.value.length === 0) return;

  isSendingInvites.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/invitations`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ user_ids: selectedInviteIds.value }),
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      showToast(resData.message || 'Invitations sent successfully!');
      showInviteModal.value = false;
      selectedInviteIds.value = [];
    } else {
      showToast(resData.message || 'Failed to send invitations.');
    }
  } catch (err: any) {
    showToast('Failed to send invitations.');
  } finally {
    isSendingInvites.value = false;
  }
};

// Event RSVP & Creation
const submitEventRsvp = async (event: any, status: 'going' | 'interested') => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/events/${event.id}/rsvp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ status }),
    });
    if (res.ok) {
      event.user_rsvp = status;
      if (status === 'going') {
        event.going_count = (event.going_count || 0) + 1;
      }
      showToast(`Marked as ${status === 'going' ? 'Going ✓' : 'Interested ★'}`);
    }
  } catch (err) {
    showToast('Failed to update RSVP.');
  }
};

const submitCreateEvent = async () => {
  if (!createEventForm.title.trim() || !createEventForm.start_time) return;

  isCreatingEvent.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/events`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(createEventForm),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      eventsList.value.unshift(resData.data);
      showCreateEventModal.value = false;
      createEventForm.title = '';
      createEventForm.description = '';
      createEventForm.location = '';
      showToast('Event created and posted to community!');
    } else {
      showToast(resData.message || 'Failed to create event.');
    }
  } catch (err) {
    showToast('Failed to create event.');
  } finally {
    isCreatingEvent.value = false;
  }
};

// Polls Voting & Creation
const calculatePollPercent = (poll: any, votesCount: number = 0): number => {
  const total = poll.total_votes || poll.options?.reduce((sum: number, o: any) => sum + (o.votes_count || 0), 0) || 0;
  if (!total) return 0;
  return Math.round((votesCount / total) * 100);
};

const submitPollVote = async (poll: any, optionId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/polls/${poll.id}/vote`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ option_ids: [optionId] }),
    });
    const resData = await res.json();
    if (res.ok && resData.data) {
      const idx = pollsList.value.findIndex(p => p.id === poll.id);
      if (idx > -1) {
        pollsList.value[idx] = resData.data;
      }
      showToast('Vote recorded!');
    } else {
      showToast(resData.message || 'Vote submitted.');
    }
  } catch (err) {
    showToast('Failed to record vote.');
  }
};

const submitCreatePoll = async () => {
  const validOptions = createPollForm.options.filter(o => o.trim());
  if (!createPollForm.question.trim() || validOptions.length < 2) {
    showToast('Please enter a question and at least 2 options.');
    return;
  }

  isCreatingPoll.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/polls`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        question: createPollForm.question.trim(),
        options: validOptions,
      }),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      pollsList.value.unshift(resData.data);
      showCreatePollModal.value = false;
      createPollForm.question = '';
      createPollForm.options = ['', ''];
      showToast('Community poll created successfully!');
    } else {
      showToast(resData.message || 'Failed to create poll.');
    }
  } catch (err) {
    showToast('Failed to create poll.');
  } finally {
    isCreatingPoll.value = false;
  }
};

// Announcement Creation
const submitCreateAnnouncement = async () => {
  if (!createAnnouncementForm.title.trim() || !createAnnouncementForm.content.trim()) return;

  isCreatingAnnouncement.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/announcements`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        ...createAnnouncementForm,
        is_pinned: true,
      }),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      announcementsList.value.unshift(resData.data);
      showAnnouncementModal.value = false;
      createAnnouncementForm.title = '';
      createAnnouncementForm.content = '';
      createAnnouncementForm.cta_text = '';
      createAnnouncementForm.cta_url = '';
      showToast('Announcement pinned to group!');
    } else {
      showToast(resData.message || 'Failed to post announcement.');
    }
  } catch (err) {
    showToast('Failed to post announcement.');
  } finally {
    isCreatingAnnouncement.value = false;
  }
};

// File Upload
const submitUploadFile = async () => {
  if (!uploadFileForm.name.trim() || !uploadFileForm.file_url.trim()) return;

  isUploadingFile.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/files`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(uploadFileForm),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      filesList.value.unshift(resData.data);
      showUploadFileModal.value = false;
      uploadFileForm.name = '';
      uploadFileForm.file_url = '';
      showToast('File document shared with community!');
    } else {
      showToast(resData.message || 'Failed to save file.');
    }
  } catch (err) {
    showToast('Failed to save file.');
  } finally {
    isUploadingFile.value = false;
  }
};

// Media Lightbox & Gallery Upload
const openLightbox = (url: string) => {
  activeLightboxUrl.value = url;
};

const uploadMediaToGallery = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('image', file);
  formData.append('type', 'general');

  try {
    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });
    const resData = await res.json();
    if (res.ok && resData.data?.url) {
      mediaGalleryList.value.unshift({ url: resData.data.url });
      showToast('Photo added to media gallery!');
    }
  } catch (err) {
    showToast('Failed to upload photo.');
  }
};

// Notifications Settings
const openNotificationsModal = async () => {
  showMemberMenu.value = false;
  showMoreMenu.value = false;
  showNotificationsModal.value = true;

  try {
    const res = await fetch(`/api/v1/groups/${props.group.id}/notification-preferences`, {
      headers: { 'Accept': 'application/json' },
    });
    const resData = await res.json();
    if (resData.data?.settings) {
      Object.assign(notificationSettings, resData.data.settings);
    }
  } catch (e) {
    // Keep defaults
  }
};

const saveNotificationPreferences = async () => {
  isSavingPrefs.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/notification-preferences`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ settings: notificationSettings }),
    });
    if (res.ok) {
      showNotificationsModal.value = false;
      showToast('Notification preferences saved.');
    }
  } catch (err: any) {
    showToast('Failed to save preferences.');
  } finally {
    isSavingPrefs.value = false;
  }
};

// Admin Moderation Queue & Member Requests
const fetchPendingRequests = async () => {
  try {
    const res = await fetch(`/api/v1/groups/${props.group.id}/requests`, {
      headers: { 'Accept': 'application/json' },
    });
    const resData = await res.json();
    if (res.ok && resData.data) {
      pendingJoinRequests.value = resData.data;
    }
  } catch (e) {
    //
  }
};

const fetchModerationPosts = async () => {
  try {
    const res = await fetch(`/api/v1/groups/${props.group.id}/moderation/posts`, {
      headers: { 'Accept': 'application/json' },
    });
    const resData = await res.json();
    if (res.ok && resData.data) {
      moderationPostsList.value = resData.data;
    }
  } catch (e) {
    //
  }
};

if (isAdmin.value) {
  fetchPendingRequests();
}

const approveMemberRequest = async (userId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/requests/${userId}/approve`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      pendingJoinRequests.value = pendingJoinRequests.value.filter(r => r.user_id !== userId);
      props.group.members_count = (props.group.members_count || 0) + 1;
      showToast('Member approved and joined the group!');
    }
  } catch (e) {
    showToast('Failed to approve member.');
  }
};

const rejectMemberRequest = async (userId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/requests/${userId}/reject`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      pendingJoinRequests.value = pendingJoinRequests.value.filter(r => r.user_id !== userId);
      showToast('Member request declined.');
    }
  } catch (e) {
    showToast('Failed to decline member.');
  }
};

const approvePostItem = async (postId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/moderation/posts/${postId}/approve`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const resData = await res.json();
    if (res.ok && resData.data) {
      moderationPostsList.value = moderationPostsList.value.filter(p => p.id !== postId);
      postList.value.unshift(resData.data);
      showToast('Post approved and published to feed!');
    }
  } catch (e) {
    showToast('Failed to approve post.');
  }
};

const rejectPostItem = async (postId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/moderation/posts/${postId}/reject`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      moderationPostsList.value = moderationPostsList.value.filter(p => p.id !== postId);
      showToast('Post rejected from moderation queue.');
    }
  } catch (e) {
    showToast('Failed to reject post.');
  }
};

// Rules Creation
const submitCreateRule = async () => {
  if (!newRuleForm.title.trim() || !newRuleForm.description.trim()) return;

  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/rules`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(newRuleForm),
    });
    const resData = await res.json();
    if (res.ok && resData.data) {
      rulesList.value.push(resData.data);
      newRuleForm.title = '';
      newRuleForm.description = '';
      showToast('New community rule added!');
    }
  } catch (e) {
    showToast('Failed to add rule.');
  }
};

// Group Settings
const saveGroupSettings = async () => {
  isSavingSettings.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(editGroupForm),
    });
    const resData = await res.json();
    if (res.ok) {
      props.group.name = editGroupForm.name;
      props.group.description = editGroupForm.description;
      props.group.category = editGroupForm.category;
      props.group.privacy = editGroupForm.privacy as any;
      props.group.membership_approval_mode = editGroupForm.membership_approval_mode;
      showEditGroupModal.value = false;
      showToast('Group settings updated successfully!');
    } else {
      showToast(resData.message || 'Failed to update settings.');
    }
  } catch (e) {
    showToast('Failed to update settings.');
  } finally {
    isSavingSettings.value = false;
  }
};

// Member Moderation
const toggleMemberActionMenu = (memberId: number) => {
  activeMemberMenuId.value = activeMemberMenuId.value === memberId ? null : memberId;
};

const updateMemberRole = async (userId: number, newRole: string) => {
  activeMemberMenuId.value = null;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/members/${userId}/role`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ role: newRole }),
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      const target = props.members.find(m => m.user_id === userId);
      if (target) {
        target.role = newRole;
      }
      showToast(`Member updated to ${newRole}!`);
    } else {
      showToast(resData.message || 'Failed to update member role.');
    }
  } catch (err: any) {
    showToast('Failed to update member role.');
  }
};

const banMember = async (userId: number) => {
  activeMemberMenuId.value = null;
  if (!confirm('Ban this user from the group?')) return;

  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/members/${userId}/ban`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      const idx = props.members.findIndex(m => m.user_id === userId);
      if (idx > -1) props.members.splice(idx, 1);
      props.group.members_count = Math.max(0, (props.group.members_count || 1) - 1);
      showToast('User has been banned.');
    }
  } catch (e) {
    showToast('Failed to ban user.');
  }
};

const removeMember = async (userId: number) => {
  activeMemberMenuId.value = null;
  if (!confirm('Remove this member from the group?')) return;

  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/members/${userId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      const idx = props.members.findIndex(m => m.user_id === userId);
      if (idx > -1) {
        props.members.splice(idx, 1);
      }
      props.group.members_count = Math.max(0, (props.group.members_count || 1) - 1);
      showToast('Member removed from group.');
    } else {
      showToast(resData.message || 'Failed to remove member.');
    }
  } catch (err: any) {
    showToast('Failed to remove member.');
  }
};

const copyGroupLink = () => {
  showMoreMenu.value = false;
  navigator.clipboard.writeText(window.location.href);
  showToast('Group link copied to clipboard!');
};

const submitReport = async () => {
  if (!reportForm.description.trim()) return;

  isSubmittingReport.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/reports`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        reportable_type: 'group',
        reportable_id: props.group.id,
        reason_category: reportForm.category,
        description: reportForm.description.trim(),
      }),
    });
    if (res.ok) {
      showReportModal.value = false;
      reportForm.description = '';
      showToast('Report submitted to moderation team. Thank you.');
    }
  } catch (e) {
    showToast('Failed to submit report.');
  } finally {
    isSubmittingReport.value = false;
  }
};

const uploadAvatarPhoto = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('avatar', file);
  formData.append('type', 'avatar');
  formData.append('group_id', String(props.group.id));

  try {
    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });
    const resData = await res.json();
    if (res.ok && resData.data?.url) {
      props.group.avatar_url = resData.data.url;
      showToast('Group photo updated!');
    }
  } catch (e) {
    showToast('Failed to upload group photo.');
  }
};

const uploadCoverPhoto = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('cover', file);
  formData.append('type', 'cover');
  formData.append('group_id', String(props.group.id));

  try {
    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });
    const resData = await res.json();
    if (res.ok && resData.data?.url) {
      props.group.cover_image_url = resData.data.url;
      showToast('Cover photo updated!');
    }
  } catch (e) {
    showToast('Failed to upload cover photo.');
  }
};
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
  animation: fadeIn 0.15s ease-out forwards;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
