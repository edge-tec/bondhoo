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
    <!-- Group Header Container -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
      <!-- Cover Banner -->
      <div class="relative h-48 sm:h-72 md:h-80 w-full bg-gradient-to-r from-blue-600 to-indigo-700 overflow-hidden">
        <img
          :src="group.cover_image_url || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1600&q=80'"
          alt="Group Cover"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>

        <!-- Cover Change Button for Admins -->
        <div v-if="isAdmin" class="absolute top-4 right-4">
          <label class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-md transition-all cursor-pointer flex items-center gap-2">
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
              <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-full ring-4 ring-white dark:ring-slate-900 shadow-xl overflow-hidden bg-[#1877F2] flex items-center justify-center text-white">
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
              <label v-if="isAdmin" class="absolute bottom-1 right-1 w-9 h-9 rounded-full bg-[#1877F2] text-white flex items-center justify-center shadow-md cursor-pointer hover:bg-blue-600 transition-colors">
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
                class="absolute right-0 mt-2 w-48 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-30"
              >
                <button
                  type="button"
                  @click="openNotificationsModal"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                  Manage Notifications
                </button>
                <div class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>
                <button
                  type="button"
                  @click="leaveGroup"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2"
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

            <!-- More Actions Button -->
            <div class="relative">
              <button
                type="button"
                @click="showMoreMenu = !showMoreMenu"
                class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
              </button>

              <div
                v-if="showMoreMenu"
                class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-30"
              >
                <button
                  type="button"
                  @click="copyGroupLink"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Copy Link
                </button>
                <button
                  type="button"
                  @click="openReportModal"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2"
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
        <div class="flex items-center gap-2 sm:gap-4 mt-6 pt-2 border-t border-slate-100 dark:border-slate-800 overflow-x-auto no-scrollbar">
          <button
            type="button"
            @click="activeTab = 'about'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
            :class="activeTab === 'about' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            About
            <span v-if="activeTab === 'about'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="activeTab = 'posts'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
            :class="activeTab === 'posts' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            Posts
            <span v-if="activeTab === 'posts'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="activeTab = 'members'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
            :class="activeTab === 'members' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            Members
            <span v-if="activeTab === 'members'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="activeTab = 'files'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
            :class="activeTab === 'files' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'"
          >
            Files
            <span v-if="activeTab === 'files'" class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="openNotificationsModal"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-1.5"
          >
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Notifications
          </button>
        </div>

      </div>
    </div>

    <!-- TAB 1: POSTS & DISCUSSION (Matching Mockup with Left Feed + Right Meta) -->
    <div v-show="activeTab === 'posts'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Left/Main Column: Post Composer & Posts Feed -->
      <div class="lg:col-span-8 space-y-5">
        
        <!-- Post Composer Card -->
        <div v-if="canPost" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#1877F2] flex items-center justify-center text-white shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <input
              v-model="newPostContent"
              type="text"
              placeholder="Write something..."
              class="w-full px-4 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all"
              @keyup.enter="submitGroupPost"
            />
          </div>

          <!-- Attachment Buttons & Post Submit -->
          <div class="flex items-center justify-between mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-1 sm:gap-2">
              <button
                type="button"
                @click="triggerPostMediaUpload"
                class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1.5 transition-colors"
              >
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="hidden sm:inline">Photo/Video</span>
              </button>
              <button
                type="button"
                class="px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1.5 transition-colors"
              >
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="hidden sm:inline">Feeling/Activity</span>
              </button>
            </div>

            <button
              type="button"
              @click="submitGroupPost"
              :disabled="!newPostContent.trim() || isPosting"
              class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all disabled:opacity-40"
            >
              {{ isPosting ? 'Posting...' : 'Post' }}
            </button>
          </div>
        </div>

        <!-- Feed Posts List -->
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
                  :src="post.user?.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                  :alt="post.user?.name"
                  class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800"
                />
                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ post.user?.name || 'Group Member' }}
                  </h4>
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
            <p class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">
              {{ post.content }}
            </p>

            <!-- Post Media (if any) -->
            <div v-if="post.media && post.media.length > 0" class="rounded-2xl overflow-hidden max-h-96">
              <img :src="post.media[0].url" alt="Post attachment" class="w-full h-full object-cover" />
            </div>

            <!-- Action Reactions -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-500">
              <button
                type="button"
                class="flex items-center gap-1.5 hover:text-[#1877F2] transition-colors py-1 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                </svg>
                <span>Like</span>
              </button>

              <button
                type="button"
                class="flex items-center gap-1.5 hover:text-[#1877F2] transition-colors py-1 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Comment</span>
              </button>

              <button
                type="button"
                class="flex items-center gap-1.5 hover:text-[#1877F2] transition-colors py-1 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span>Share</span>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-10 text-center text-slate-400">
          <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
          <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No discussions yet</h4>
          <p class="text-xs sm:text-sm mt-1">Be the first to share an idea with this community!</p>
        </div>

      </div>

      <!-- Right Column: About Card + Members Widget -->
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
            <template v-for="(m, idx) in members.slice(0, 6)" :key="m.id">
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

          <!-- Recent Activity -->
          <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
              Recent Activity
            </h4>
            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>New member joined recently</span>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- TAB 2: MEMBERS DIRECTORY (Matching Mockup "Group Members" view) -->
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
            All Members ({{ members.length }})
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
            <!-- Message Button -->
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
                class="absolute right-0 mt-2 w-48 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-30"
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

    <!-- TAB 3: ABOUT DETAIL VIEW -->
    <div v-show="activeTab === 'about'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
      <div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
          About this group
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap mt-2 leading-relaxed">
          {{ group.description || 'No description provided.' }}
        </p>
      </div>

      <!-- Guidelines and Rules -->
      <div v-if="group.rules && group.rules.length > 0" class="pt-4 border-t border-slate-100 dark:border-slate-800">
        <h4 class="text-base font-bold text-slate-900 dark:text-white mb-3">
          Group Rules
        </h4>
        <div class="space-y-3">
          <div
            v-for="(rule, index) in group.rules"
            :key="index"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
          >
            <h5 class="text-sm font-bold text-slate-900 dark:text-white">
              {{ index + 1 }}. {{ rule.title }}
            </h5>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ rule.description }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: FILES -->
    <div v-show="activeTab === 'files'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
          Group Files & Media
        </h3>
      </div>
      <div class="py-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        <p class="text-sm">No files uploaded yet in this group.</p>
      </div>
    </div>

    <!-- MODAL: INVITE FRIENDS -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            Invite Friends to Group
          </h3>
          <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-4">
          <input
            v-model="inviteSearchQuery"
            type="text"
            placeholder="Search friends..."
            class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
          />

          <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            <div
              v-for="friend in filteredInviteFriends"
              :key="friend.id"
              @click="toggleInviteFriend(friend.id)"
              class="flex items-center justify-between p-2.5 rounded-2xl border transition-all cursor-pointer"
              :class="selectedInviteIds.includes(friend.id) ? 'border-[#1877F2] bg-blue-50/30 dark:bg-blue-950/20' : 'border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
            >
              <div class="flex items-center gap-3">
                <input
                  type="checkbox"
                  :checked="selectedInviteIds.includes(friend.id)"
                  class="w-4 h-4 text-[#1877F2] rounded border-slate-300"
                />
                <img
                  :src="friend.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                  :alt="friend.name"
                  class="w-8 h-8 rounded-full object-cover"
                />
                <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">{{ friend.name }}</span>
              </div>
            </div>

            <div v-if="filteredInviteFriends.length === 0" class="text-center py-6 text-xs text-slate-400">
              No eligible friends to invite.
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
            {{ isSendingInvites ? 'Sending...' : 'Send Invitations (' + selectedInviteIds.length + ')' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: GROUP NOTIFICATIONS (Matching Mockup "Group Notifications") -->
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
          <!-- Toggle: New posts -->
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">New posts</h4>
              <p class="text-xs text-slate-400">Get notified when new posts are shared in this group.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.new_posts" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <!-- Toggle: Comments -->
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Comments</h4>
              <p class="text-xs text-slate-400">Get notified about comments on your posts.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.comments" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <!-- Toggle: Member requests -->
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Member requests</h4>
              <p class="text-xs text-slate-400">Get notified about member join requests.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.member_requests" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <!-- Toggle: Mentions -->
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Mentions</h4>
              <p class="text-xs text-slate-400">Get notified when someone mentions or tags you.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="notificationSettings.mentions" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
            </label>
          </div>

          <!-- Toggle: Events -->
          <div class="flex items-center justify-between py-1">
            <div>
              <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Events</h4>
              <p class="text-xs text-slate-400">Get notified about upcoming community events.</p>
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
  membership?: {
    role: string;
    status: string;
  } | null;
  created_at?: string;
  rules?: Array<{ title: string; description: string }>;
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

// Active Tab ('about' | 'posts' | 'members' | 'files')
const activeTab = ref<string>('posts');

// Member status
const isMember = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  return m && m.status === 'active';
});

const isPendingApproval = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  return m && m.status === 'pending';
});

const isAdmin = computed(() => {
  const m = props.group.membership || props.group.viewer_membership;
  if (!m) {
    return currentUserId.value && props.group.creator?.id === currentUserId.value;
  }
  return ['owner', 'admin'].includes(m.role) || (currentUserId.value && props.group.creator?.id === currentUserId.value);
});

const canPost = computed(() => {
  if (isAdmin.value) return true;
  return isMember.value;
});

// Dropdown states
const showMemberMenu = ref<boolean>(false);
const showMoreMenu = ref<boolean>(false);
const activeMemberMenuId = ref<number | null>(null);

// Post creation
const newPostContent = ref<string>('');
const isPosting = ref<boolean>(false);
const postList = ref<any[]>([...props.posts.data]);

// Join action state
const isJoining = ref<boolean>(false);

// Invite modal state
const showInviteModal = ref<boolean>(false);
const inviteSearchQuery = ref<string>('');
const selectedInviteIds = ref<number[]>([]);
const isSendingInvites = ref<boolean>(false);

// Notifications modal state
const showNotificationsModal = ref<boolean>(false);
const isSavingPrefs = ref<boolean>(false);
const notificationSettings = reactive({
  new_posts: true,
  comments: true,
  member_requests: true,
  mentions: true,
  events: true,
});

// Members tab search and role filter
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
  if (!inviteSearchQuery.value.trim()) {
    return props.friends;
  }
  const q = inviteSearchQuery.value.toLowerCase();
  return props.friends.filter(f => f.name?.toLowerCase().includes(q));
});

const formatMemberCount = (num: number): string => {
  if (!num) return '0';
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  }
  return num.toLocaleString();
};

const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

// Actions
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
      } else {
        const pendingMem = { role: 'member', status: 'pending' };
        props.group.membership = pendingMem;
        props.group.viewer_membership = pendingMem;
      }
    } else {
      alert(resData.message || 'Failed to join group.');
    }
  } catch (err: any) {
    alert('Failed to join group.');
  } finally {
    isJoining.value = false;
  }
};

const cancelJoinRequest = async () => {
  if (!confirm('Cancel your pending join request?')) return;

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
    } else {
      alert(resData.message || 'Failed to cancel join request.');
    }
  } catch (err: any) {
    alert('Failed to cancel join request.');
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
    } else {
      alert(resData.message || 'Failed to leave group.');
    }
  } catch (err: any) {
    alert('Failed to leave group.');
  }
};

const submitGroupPost = async () => {
  if (!newPostContent.value.trim() || isPosting.value) return;

  isPosting.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/${props.group.id}/posts`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        content: newPostContent.value.trim(),
      }),
    });
    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      postList.value.unshift(resData.data);
      newPostContent.value = '';
    } else {
      alert(resData.message || 'Failed to create group post.');
    }
  } catch (err: any) {
    alert('Failed to create group post.');
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
    } else {
      alert('Failed to delete post.');
    }
  } catch (err: any) {
    alert('Failed to delete post.');
  }
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
      body: JSON.stringify({
        user_ids: selectedInviteIds.value,
      }),
    });
    const resData = await res.json();
    if (res.ok && resData.success) {
      alert(resData.message || 'Invitations sent successfully!');
      showInviteModal.value = false;
      selectedInviteIds.value = [];
    } else {
      alert(resData.message || 'Failed to send invitations.');
    }
  } catch (err: any) {
    alert('Failed to send invitations.');
  } finally {
    isSendingInvites.value = false;
  }
};

const openNotificationsModal = async () => {
  showMemberMenu.value = false;
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
      body: JSON.stringify({
        settings: notificationSettings,
      }),
    });
    if (res.ok) {
      showNotificationsModal.value = false;
    } else {
      alert('Failed to save notification preferences.');
    }
  } catch (err: any) {
    alert('Failed to save notification preferences.');
  } finally {
    isSavingPrefs.value = false;
  }
};

const toggleMemberActionMenu = (memberId: number) => {
  if (activeMemberMenuId.value === memberId) {
    activeMemberMenuId.value = null;
  } else {
    activeMemberMenuId.value = memberId;
  }
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
    } else {
      alert(resData.message || 'Failed to update member role.');
    }
  } catch (err: any) {
    alert('Failed to update member role.');
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
    } else {
      alert(resData.message || 'Failed to remove member.');
    }
  } catch (err: any) {
    alert('Failed to remove member.');
  }
};

const copyGroupLink = () => {
  showMoreMenu.value = false;
  navigator.clipboard.writeText(window.location.href);
  alert('Group link copied to clipboard!');
};

const openReportModal = () => {
  showMoreMenu.value = false;
  const reason = prompt('Please enter the reason for reporting this group:');
  if (reason) {
    const csrf = getCsrfToken();
    fetch(`/api/v1/groups/${props.group.id}/reports`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({
        reportable_type: 'group',
        reportable_id: props.group.id,
        reason_category: 'inappropriate_content',
        description: reason,
      }),
    }).then(() => {
      alert('Thank you. Your report has been submitted to moderators.');
    }).catch(() => {
      alert('Failed to submit report.');
    });
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
    }
  } catch (e) {
    alert('Failed to upload group photo.');
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
    }
  } catch (e) {
    alert('Failed to upload cover photo.');
  }
};

const triggerPostMediaUpload = () => {
  // Can trigger file picker
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
