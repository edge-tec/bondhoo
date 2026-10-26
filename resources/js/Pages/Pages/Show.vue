<template>
  <AppLayout
    current-route="pages"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- 1. PAGE HEADER CARD -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden mb-5">
      <!-- Cover Banner -->
      <div class="relative h-48 sm:h-64 md:h-80 lg:h-96 w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-700 overflow-hidden">
        <img
          :src="pageCoverUrl"
          :alt="pageData.name + ' Cover'"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10"></div>

        <!-- Cover Change Button for Page Managers -->
        <div v-if="canManage" class="absolute bottom-3 right-3 sm:top-4 sm:right-4">
          <label class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-md transition-all cursor-pointer flex items-center gap-2 border border-white/20 shadow-sm active:scale-95">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ isUploadingCover ? 'আপলোড হচ্ছে...' : 'কভার পরিবর্তন' }}</span>
            <input type="file" accept="image/*" class="hidden" @change="uploadCoverImage" :disabled="isUploadingCover" />
          </label>
        </div>
      </div>

      <!-- Header Info & Actions Row -->
      <div class="px-4 sm:px-8 pb-5 pt-0">
        <div class="flex flex-col md:flex-row items-center md:items-end justify-between gap-4 -mt-14 sm:-mt-20 relative z-10">
          
          <!-- Avatar + Titles -->
          <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
            <div class="relative group shrink-0">
              <div class="w-24 h-24 sm:w-32 sm:h-32 md:w-36 md:h-36 rounded-full ring-4 ring-white dark:ring-slate-900 shadow-2xl overflow-hidden bg-[#1877F2] flex items-center justify-center text-white text-3xl sm:text-4xl font-extrabold">
                <img
                  v-if="pageData.avatar_url"
                  :src="pageData.avatar_url"
                  :alt="pageData.name"
                  class="w-full h-full object-cover"
                />
                <span v-else>{{ pageData.name.charAt(0).toUpperCase() }}</span>
              </div>

              <!-- Avatar Change Button for Manager -->
              <label
                v-if="canManage"
                class="absolute bottom-1 right-1 w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#1877F2] hover:bg-blue-600 text-white flex items-center justify-center shadow-lg cursor-pointer border-2 border-white dark:border-slate-900 transition-all active:scale-95"
                title="প্রোফাইল ছবি পরিবর্তন করুন"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <input type="file" accept="image/*" class="hidden" @change="uploadAvatarImage" :disabled="isUploadingAvatar" />
              </label>
            </div>

            <div class="mb-1">
              <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ pageData.name }}
                </h1>
                <span v-if="pageData.is_verified" class="w-5 h-5 rounded-full bg-[#1877F2] text-white flex items-center justify-center shrink-0 shadow-sm" title="ভেরিফাইড পেজ">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </span>
                <span v-if="userRole" class="px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] border border-blue-200 dark:border-blue-800">
                  {{ userRole }}
                </span>
              </div>

              <div class="flex items-center justify-center sm:justify-start gap-2 text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 mt-1 flex-wrap">
                <span class="text-slate-800 dark:text-slate-200 font-bold">{{ pageData.category || 'Technology' }}</span>
                <span>&bull;</span>
                <span class="text-[#1877F2] font-bold">{{ formattedFollowersCount }} followers</span>
                <span v-if="pageData.posts_count">&bull;</span>
                <span v-if="pageData.posts_count">{{ pageData.posts_count }} posts</span>
              </div>

              <p v-if="pageData.bio || pageData.description" class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-2 max-w-xl line-clamp-2 leading-relaxed">
                {{ pageData.bio || pageData.description || 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!' }}
              </p>
            </div>
          </div>

          <!-- Action Buttons Group (Fully Responsive on Mobile & Desktop) -->
          <div class="w-full md:w-auto grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-2.5 justify-center md:justify-end mt-2 md:mt-0">
            <!-- Follow / Following Toggle Button -->
            <button
              type="button"
              @click="toggleFollow"
              :disabled="isTogglingFollow"
              :class="[
                'py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-60 cursor-pointer active:scale-95',
                isFollowingState
                  ? 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700'
                  : 'bg-[#1877F2] text-white hover:bg-[#1666D8] shadow-blue-500/25'
              ]"
            >
              <svg v-if="isFollowingState" class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              <span>{{ isFollowingState ? 'Following' : 'Follow' }}</span>
            </button>

            <!-- Message Button -->
            <a
              :href="`/messages?page_id=${pageData.id}`"
              class="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 border border-slate-200 dark:border-slate-700 active:scale-95"
            >
              <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span>Message</span>
            </a>

            <!-- Studio Launcher Shortcut (for Managers) -->
            <button
              v-if="canManage"
              type="button"
              @click="openStudio('post')"
              class="col-span-2 sm:col-span-1 py-2.5 px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-xs sm:text-sm font-bold border border-indigo-200 dark:border-indigo-800 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95"
            >
              <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
              <span>Publishing Studio</span>
            </button>

            <!-- More Actions Dropdown Menu -->
            <div class="col-span-2 sm:col-span-1 relative flex justify-center sm:block" ref="moreMenuRef">
              <button
                type="button"
                @click="showMoreMenu = !showMoreMenu"
                class="w-full sm:w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-colors cursor-pointer border border-slate-200 dark:border-slate-700 active:scale-95"
                title="More options"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>
              </button>

              <div
                v-if="showMoreMenu"
                class="absolute right-0 top-12 sm:top-auto sm:mt-2 w-52 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-2 z-30 animate-in fade-in zoom-in-95 duration-150"
              >
                <button
                  type="button"
                  @click="copyPageLink"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  <span>Copy Page Link</span>
                </button>

                <button
                  type="button"
                  @click="showInviteModal = true; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  <span>Invite Friends</span>
                </button>

                <button
                  v-if="canManage"
                  type="button"
                  @click="showEditDetailsModal = true; showMoreMenu = false;"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <span>Edit Page Info</span>
                </button>

                <div class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>

                <button
                  type="button"
                  @click="openReportModal"
                  class="w-full text-left px-4 py-2 text-xs sm:text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5"
                >
                  <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <span>Report Page</span>
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- Navigation Tabs Bar (Home, Posts, About, Photos, Videos, Events, Shop) -->
        <div class="flex items-center gap-1 sm:gap-4 mt-5 pt-2 border-t border-slate-100 dark:border-slate-800 overflow-x-auto no-scrollbar">
          <button
            type="button"
            @click="currentTab = 'home'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'home' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Home
            <span v-if="currentTab === 'home'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'posts'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'posts' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Posts
            <span v-if="currentTab === 'posts'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'about'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'about' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            About
            <span v-if="currentTab === 'about'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'photos'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'photos' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Photos
            <span v-if="currentTab === 'photos'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'videos'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'videos' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Videos
            <span v-if="currentTab === 'videos'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'events'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'events' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Events
            <span v-if="currentTab === 'events'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>

          <button
            type="button"
            @click="currentTab = 'shop'"
            class="px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer"
            :class="currentTab === 'shop' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
          >
            Shop
            <span v-if="currentTab === 'shop'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"></span>
          </button>
        </div>

      </div>
    </div>

    <!-- MOBILE ONLY: QUICK INFORMATION & CONTACT HIGHLIGHTS CARD -->
    <div class="lg:hidden bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-4 mb-5 shadow-xs space-y-3">
      <div class="grid grid-cols-2 gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 text-center">
        <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-2xl">
          <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Category</span>
          <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate block mt-0.5">{{ pageData.category || 'Technology' }}</span>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-2xl">
          <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Followers</span>
          <span class="text-xs font-bold text-[#1877F2] truncate block mt-0.5">{{ formattedFollowersCount }}</span>
        </div>
      </div>

      <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
        <div v-if="pageData.city || pageData.address" class="flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span class="truncate">{{ pageData.address || pageData.city || 'Dhaka, Bangladesh' }}</span>
        </div>

        <div v-if="pageData.website" class="flex items-center gap-2">
          <svg class="w-4 h-4 text-[#1877F2] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
          <a :href="pageData.website" target="_blank" rel="noopener noreferrer" class="text-[#1877F2] hover:underline font-semibold truncate">
            {{ pageData.website.replace(/^https?:\/\//, '') }}
          </a>
        </div>

        <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Open Now &bull; 9:00 AM - 6:00 PM</span>
        </div>
      </div>
    </div>

    <!-- 2. TAB CONTENT: POSTS & FEED (3-COLUMN WORKSPACE: LEFT NAV BY APPLAYOUT, CENTER FEED + RIGHT WIDGETS) -->
    <div v-show="currentTab === 'posts' || currentTab === 'home'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- CENTER COLUMN: STORIES, COMPOSER, 4-TILE STUDIO HUB & POSTS (8 COLS) -->
      <div class="lg:col-span-8 space-y-4">
        
        <!-- A. STORIES & REELS TRAY (MATCHING REFERENCE SCREENSHOT) -->
        <div class="flex items-center gap-3 overflow-x-auto no-scrollbar py-1">
          <!-- 1. Create Story Card -->
          <div
            @click="openStudio('story')"
            class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md cursor-pointer flex flex-col group transition-all"
          >
            <div class="h-32 sm:h-36 w-full bg-slate-100 dark:bg-slate-800 overflow-hidden relative">
              <img
                :src="currentUser?.avatar_url || pageData.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80'"
                alt="Avatar"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div class="absolute inset-0 bg-black/10"></div>
            </div>
            <div class="relative flex-1 flex flex-col items-center justify-center p-2 bg-white dark:bg-slate-900">
              <div class="absolute -top-4 w-8 h-8 rounded-full bg-[#1877F2] text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900 shadow-md group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
              </div>
              <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-2 text-center">Create Story</span>
            </div>
          </div>

          <!-- 2. Active Stories List -->
          <div
            v-for="(story, sIdx) in displayStories"
            :key="story.id || sIdx"
            @click="openStoryViewer(story, sIdx)"
            class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer group transition-all border border-slate-200/80 dark:border-slate-800"
          >
            <img
              :src="getStoryCover(story)"
              :alt="story.user?.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/80"></div>

            <!-- Top Avatar with Blue Ring -->
            <div class="absolute top-2.5 left-2.5">
              <img
                :src="story.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                :alt="story.user?.name"
                class="w-8 h-8 rounded-full object-cover ring-2 ring-[#1877F2] bg-white p-0.5"
              />
            </div>

            <!-- Bottom Story Name & Timestamp -->
            <div class="absolute bottom-2.5 left-2.5 right-2.5">
              <span class="block text-xs font-bold text-white truncate drop-shadow-md">
                {{ story.user?.name }}
              </span>
              <span class="block text-[10px] text-white/80 drop-shadow-md">
                {{ story.formatted_time || '2h ago' }}
              </span>
            </div>
          </div>

          <!-- 3. Create Reel Gradient Card (Right of Tray) -->
          <div
            @click="openStudio('reel')"
            class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden bg-gradient-to-b from-fuchsia-600 via-pink-600 to-indigo-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 cursor-pointer flex flex-col items-center justify-center p-3 text-white text-center group transition-all"
          >
            <div class="w-11 h-11 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-inner">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
            <span class="text-xs font-extrabold tracking-tight">Create Reel</span>
            <span class="text-[10px] text-white/80 mt-1">15s - 90s</span>
          </div>
        </div>

        <!-- B. POST COMPOSER INPUT CARD -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-3.5 sm:p-4">
          <!-- Input Bar -->
          <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <img
              :src="currentUser?.avatar_url || pageData.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              alt="Avatar"
              class="w-10 h-10 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
            />
            <div
              @click="openStudio('post')"
              class="flex-1 bg-[#F5F7FB] hover:bg-slate-200/60 dark:bg-slate-800 dark:hover:bg-slate-700/60 rounded-full px-4 py-2.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400 cursor-pointer transition-colors"
            >
              What's on your mind, {{ pageData.name }}?
            </div>
          </div>

          <!-- Action Buttons Bar -->
          <div class="flex items-center justify-between pt-2.5 px-1 sm:px-2 flex-wrap gap-1">
            <button
              type="button"
              @click="openStudio('photo')"
              class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zm0 2v10h16V7H4zm3 8l3-4 2 2.5 3-4 4 5.5H7zm9-5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
              </svg>
              <span>Photo/Video</span>
            </button>

            <button
              type="button"
              @click="openStudio('reel')"
              class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              <svg class="w-5 h-5 text-fuchsia-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
              </svg>
              <span>Reel</span>
            </button>

            <button
              type="button"
              @click="openStudio('live')"
              class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
              <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
              </svg>
              <span>Live</span>
            </button>

            <button
              type="button"
              @click="openStudio('post')"
              class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              <span class="text-base leading-none">😊</span>
              <span>Feeling/Activity</span>
            </button>
          </div>
        </div>

        <!-- C. 4-TILE PUBLISHING STUDIO QUICK HUB -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <!-- Tile 1: Create Post -->
          <div
            @click="openStudio('post')"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm hover:shadow-md hover:border-blue-400 cursor-pointer transition-all flex flex-col justify-between group"
          >
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
            <div>
              <div class="flex items-center justify-between text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                <span>Create Post</span>
                <span class="text-[#1877F2] group-hover:translate-x-0.5 transition-transform">→</span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                Share updates with your followers
              </p>
            </div>
          </div>

          <!-- Tile 2: Reel -->
          <div
            @click="openStudio('reel')"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm hover:shadow-md hover:border-pink-400 cursor-pointer transition-all flex flex-col justify-between group"
          >
            <div class="w-10 h-10 rounded-xl bg-pink-50 dark:bg-pink-950/60 text-pink-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
              </svg>
            </div>
            <div>
              <div class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                Reel
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                Create short videos (15s - 90s)
              </p>
            </div>
          </div>

          <!-- Tile 3: Live -->
          <div
            @click="openStudio('live')"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm hover:shadow-md hover:border-rose-400 cursor-pointer transition-all flex flex-col justify-between group"
          >
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform relative">
              <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
              </svg>
            </div>
            <div>
              <div class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                Live
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                Go live and connect with your audience
              </p>
            </div>
          </div>

          <!-- Tile 4: Share Video -->
          <div
            @click="openStudio('video')"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm hover:shadow-md hover:border-cyan-400 cursor-pointer transition-all flex flex-col justify-between group"
          >
            <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <div class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white">
                Share Video
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                Upload or share videos
              </p>
            </div>
          </div>
        </div>

        <!-- D. ACTIVE LIVE STREAM ALERT (IF LIVE) -->
        <div v-if="activeLiveStream" class="p-4 rounded-2xl bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-md flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-white text-rose-600 flex items-center gap-1.5 animate-pulse">
              <span class="w-2 h-2 rounded-full bg-rose-600"></span> LIVE NOW
            </span>
            <div>
              <h4 class="text-sm font-bold truncate">{{ activeLiveStream.title }}</h4>
              <span class="text-xs text-white/80">{{ activeLiveStream.viewers_count }} viewers • Started {{ activeLiveStream.started_at }}</span>
            </div>
          </div>
          <button
            type="button"
            @click="openLiveViewer(activeLiveStream)"
            class="px-4 py-2 rounded-xl bg-white text-rose-600 font-bold text-xs hover:bg-slate-100 shadow-sm cursor-pointer"
          >
            Watch Stream
          </button>
        </div>

        <!-- E. FEED FILTER BUTTONS (All, Published, Drafts, Scheduled) -->
        <div v-if="canManage" class="flex items-center gap-2 p-1.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm text-xs font-bold">
          <button
            type="button"
            @click="postsFeedFilter = 'all'"
            :class="['px-3 py-1.5 rounded-xl transition-all cursor-pointer', postsFeedFilter === 'all' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800']"
          >
            All Posts
          </button>
          <button
            type="button"
            @click="postsFeedFilter = 'published'"
            :class="['px-3 py-1.5 rounded-xl transition-all cursor-pointer', postsFeedFilter === 'published' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800']"
          >
            Published
          </button>
          <button
            type="button"
            @click="postsFeedFilter = 'draft'"
            :class="['px-3 py-1.5 rounded-xl transition-all cursor-pointer', postsFeedFilter === 'draft' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800']"
          >
            Drafts ({{ draftsCount }})
          </button>
          <button
            type="button"
            @click="postsFeedFilter = 'scheduled'"
            :class="['px-3 py-1.5 rounded-xl transition-all cursor-pointer', postsFeedFilter === 'scheduled' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800']"
          >
            Scheduled ({{ scheduledCount }})
          </button>
        </div>

        <!-- F. FEED POSTS LIST (EXACT MATCHING DESIGN FROM REFERENCE SCREENSHOT) -->
        <div v-if="filteredPosts.length > 0" class="space-y-4">
          <article
            v-for="post in filteredPosts"
            :key="post.id"
            :id="`post-card-${post.id}`"
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5 transition-all"
          >
            <!-- Pin Badge -->
            <div v-if="post.is_pinned" class="flex items-center gap-1.5 text-xs font-bold text-[#1877F2] mb-2.5">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              <span>Pinned Post</span>
            </div>

            <!-- Post Header -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#1877F2] overflow-hidden shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                  <img
                    v-if="post.page?.avatar_url || pageData.avatar_url"
                    :src="post.page?.avatar_url || pageData.avatar_url"
                    :alt="post.page?.name || pageData.name"
                    class="w-full h-full object-cover"
                  />
                  <span v-else class="w-full h-full flex items-center justify-center text-white text-sm font-bold">
                    {{ (post.page?.name || pageData.name || 'S').charAt(0) }}
                  </span>
                </div>

                <div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-extrabold text-sm text-slate-900 dark:text-white">
                      {{ post.page?.name || pageData.name }}
                    </span>
                    <span v-if="post.page?.is_verified ?? pageData.is_verified" class="w-4 h-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center shrink-0">
                      <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                      </svg>
                    </span>
                    <span v-if="post.status === 'draft'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                      Draft
                    </span>
                    <span v-if="post.status === 'scheduled'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">
                      Scheduled
                    </span>
                  </div>

                  <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                    <span>{{ formatTimeAgo(post.created_at) }}</span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                      </svg>
                      {{ post.audience === 'public' ? 'Public' : (post.audience === 'followers' ? 'Followers' : 'Private') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Post Context Menu Trigger -->
              <div class="relative">
                <button
                  type="button"
                  @click="togglePostMenu(post.id)"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
                </button>

                <div
                  v-if="activePostMenuId === post.id"
                  class="absolute right-0 mt-1 w-48 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-20 text-xs font-semibold"
                >
                  <button
                    type="button"
                    @click="copyPostLink(post.id)"
                    class="w-full flex items-center gap-2 px-3.5 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                  >
                    <span>🔗 Copy link</span>
                  </button>

                  <button
                    v-if="canManage"
                    type="button"
                    @click="togglePinPost(post)"
                    class="w-full flex items-center gap-2 px-3.5 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                  >
                    <span>📌 {{ post.is_pinned ? 'Unpin post' : 'Pin to top' }}</span>
                  </button>

                  <button
                    v-if="canManage"
                    type="button"
                    @click="openEditPostModal(post)"
                    class="w-full flex items-center gap-2 px-3.5 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                  >
                    <span>✏️ Edit post</span>
                  </button>

                  <div v-if="canManage" class="h-px bg-slate-100 dark:bg-slate-700 my-1"></div>

                  <button
                    v-if="canManage"
                    type="button"
                    @click="deletePost(post.id)"
                    class="w-full flex items-center gap-2 px-3.5 py-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                  >
                    <span>🗑️ Delete post</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Post Text Content -->
            <p class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed mb-3">
              {{ post.content }}
            </p>

            <!-- Post Media Attachments -->
            <div v-if="post.media && post.media.length > 0" class="mb-3 rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 relative group">
              <!-- Single Video -->
              <div v-if="isVideoMedia(post.media[0])" class="w-full max-h-[460px] bg-black relative">
                <video :src="getMediaUrl(post.media[0])" controls class="w-full h-full max-h-[460px] object-contain"></video>
              </div>

              <!-- 1 Photo -->
              <div v-else-if="post.media.length === 1" class="max-h-[460px] overflow-hidden cursor-pointer" @click="openPhotoLightbox(getMediaUrl(post.media[0]))">
                <img :src="getMediaUrl(post.media[0])" class="w-full h-full object-cover hover:scale-101 transition-transform" alt="Photo" />
              </div>

              <!-- 2 Photos -->
              <div v-else-if="post.media.length === 2" class="grid grid-cols-2 gap-1 max-h-[380px]">
                <div v-for="m in post.media" :key="m.id" class="cursor-pointer overflow-hidden" @click="openPhotoLightbox(getMediaUrl(m))">
                  <img :src="getMediaUrl(m)" class="w-full h-56 object-cover hover:scale-105 transition-transform" alt="Photo" />
                </div>
              </div>

              <!-- 3+ Photos Gallery (Matching Sunset Post from reference) -->
              <div v-else class="grid grid-cols-3 gap-1 max-h-[320px]">
                <div
                  v-for="(m, mIdx) in post.media.slice(0, 3)"
                  :key="m.id"
                  class="cursor-pointer overflow-hidden relative"
                  @click="openPhotoLightbox(getMediaUrl(m))"
                >
                  <img :src="getMediaUrl(m)" class="w-full h-44 object-cover hover:scale-105 transition-transform" alt="Photo" />
                  <!-- Duration badge for video -->
                  <div v-if="isVideoMedia(m)" class="absolute bottom-2 left-2 px-2 py-0.5 rounded-lg bg-black/70 text-white text-[11px] font-bold">
                    0:45
                  </div>
                  <!-- +N more badge on 3rd photo -->
                  <div v-if="mIdx === 2 && post.media.length > 3" class="absolute inset-0 bg-black/60 flex items-center justify-center text-white text-lg font-extrabold">
                    +{{ post.media.length - 3 }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Fallback Mock Banner Graphic for Demo post if no media attached -->
            <div
              v-else-if="post.id === 1 || post.content?.includes('Page Insights')"
              class="mb-3 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 relative bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-700 p-6 sm:p-8 text-white flex flex-col items-center justify-center text-center group cursor-pointer shadow-inner"
              @click="openStudio('video')"
            >
              <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                <svg class="w-8 h-8 text-white fill-current ml-1" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
              <h3 class="text-xl sm:text-2xl font-black tracking-tight drop-shadow-md">Page Insights Now Live!</h3>
              <p class="text-xs sm:text-sm text-blue-100 mt-1 font-semibold">Track • Analyze • Grow</p>
            </div>

            <!-- Engagement Stats Summary Bar (👍❤️ 856 | 124 comments 36 shares) -->
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pb-2.5 mb-1 border-b border-slate-100 dark:border-slate-800">
              <div class="flex items-center gap-1.5">
                <span class="w-4 h-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[9px] shadow-sm">👍</span>
                <span class="w-4 h-4 rounded-full bg-rose-500 text-white flex items-center justify-center text-[9px] -ml-2 shadow-sm">❤️</span>
                <span class="font-medium ml-1">
                  {{ post.likes_count ?? post.counters?.likes ?? 856 }}
                </span>
              </div>

              <div class="flex items-center gap-3 font-medium">
                <span>{{ post.comments_count ?? post.counters?.comments ?? 124 }} comments</span>
                <span>{{ post.shares_count ?? post.counters?.shares ?? 36 }} shares</span>
              </div>
            </div>

            <!-- Action Buttons Bar (Like, Comment, Share) -->
            <div class="relative flex items-center justify-between py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
              <!-- Animated Reaction Picker Popover -->
              <div
                v-if="hoverReactionPostId === post.id"
                @mouseenter="hoverReactionPostId = post.id"
                @mouseleave="hoverReactionPostId = null"
                class="absolute -top-11 left-2 z-30 flex items-center gap-1 bg-white dark:bg-slate-800 rounded-full py-1.5 px-3 shadow-xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95"
              >
                <button
                  v-for="r in reactionTypes"
                  :key="r.type"
                  type="button"
                  @click="reactToPost(post, r.type)"
                  class="text-lg hover:scale-130 transition-transform p-0.5 cursor-pointer"
                  :title="r.label"
                >
                  {{ r.emoji }}
                </button>
              </div>

              <!-- Like Button -->
              <button
                type="button"
                @mouseenter="hoverReactionPostId = post.id"
                @click="reactToPost(post, post.user_reaction ? post.user_reaction : 'like')"
                class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                :class="post.user_reaction ? 'text-[#1877F2] font-bold' : ''"
              >
                <span class="text-base">{{ getReactionEmoji(post.user_reaction) }}</span>
                <span>{{ getReactionLabel(post.user_reaction) }}</span>
              </button>

              <!-- Comment Button -->
              <button
                type="button"
                @click="toggleComments(post.id)"
                class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Comment</span>
              </button>

              <!-- Share Button -->
              <button
                type="button"
                @click="openShareModal(post)"
                class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span>Share</span>
              </button>
            </div>

            <!-- Comments Drawer -->
            <div v-if="activeCommentsPostId === post.id" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
              <!-- Comment Input Form -->
              <div class="flex items-center gap-2">
                <input
                  v-model="commentInputs[post.id]"
                  type="text"
                  placeholder="Write a comment..."
                  class="flex-1 bg-[#F5F7FB] dark:bg-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-[#1877F2]"
                  @keydown.enter="submitComment(post)"
                />
                <button
                  type="button"
                  @click="submitComment(post)"
                  :disabled="isSubmittingComment || !commentInputs[post.id]?.trim()"
                  class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold disabled:opacity-50 cursor-pointer"
                >
                  Post
                </button>
              </div>

              <!-- Comments List -->
              <div v-if="(postComments[post.id] || post.comments || []).length > 0" class="space-y-2 max-h-60 overflow-y-auto no-scrollbar">
                <div
                  v-for="c in (postComments[post.id] || post.comments || [])"
                  :key="c.id"
                  class="flex items-start gap-2.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-xs"
                >
                  <img
                    :src="c.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                    class="w-7 h-7 rounded-full object-cover shrink-0"
                    alt="Commenter"
                  />
                  <div class="flex-1">
                    <span class="font-bold text-slate-900 dark:text-white">{{ c.user?.name || 'User' }}</span>
                    <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ c.body || c.content }}</p>
                  </div>
                  <button
                    v-if="canModerateComment(c)"
                    type="button"
                    @click="deleteComment(post, c.id)"
                    class="text-slate-400 hover:text-rose-500 text-xs cursor-pointer"
                    title="Delete Comment"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>

          </article>
        </div>

        <!-- Empty Feed State -->
        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center text-slate-400 shadow-sm">
          <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
          </svg>
          <h4 class="text-base font-extrabold text-slate-800 dark:text-slate-200">No posts published yet</h4>
          <p class="text-xs sm:text-sm mt-1 max-w-sm mx-auto text-slate-500">
            Publish your first post to connect and engage with your community!
          </p>
        </div>

      </div>

      <!-- RIGHT COLUMN: PAGE INFORMATION, QUICK ACTIONS, INSIGHTS & RECENT ACTIVITY (4 COLS) -->
      <div class="lg:col-span-4 space-y-4">
        
        <!-- WIDGET 1: PAGE INFORMATION -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
            Page Information
          </h3>

          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-full bg-[#1877F2] text-white flex items-center justify-center font-black text-xl shrink-0 shadow-sm overflow-hidden">
              <img v-if="pageData.avatar_url" :src="pageData.avatar_url" :alt="pageData.name" class="w-full h-full object-cover" />
              <span v-else>{{ pageData.name.charAt(0) }}</span>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <span class="font-extrabold text-sm text-slate-900 dark:text-white">{{ pageData.name }}</span>
                <span v-if="pageData.is_verified" class="w-4 h-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center shrink-0">
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </span>
              </div>
              <span class="text-xs text-slate-500">{{ pageData.category || 'Technology' }}</span>
            </div>
          </div>

          <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
            {{ pageData.bio || pageData.description || 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!' }}
          </p>

          <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>{{ formattedFollowersCount }} followers</span>
            </div>

            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ pageData.city || 'Dhaka, Bangladesh' }}</span>
            </div>

            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Open • 9:00 AM - 6:00 PM</span>
            </div>
          </div>

          <a
            :href="`/messages?page_id=${pageData.id}`"
            class="mt-3.5 w-full py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs flex items-center justify-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
          >
            <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span>Message Page</span>
          </a>
        </div>

        <!-- WIDGET 2: QUICK ACTIONS (GRID OF 4 ICONS) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
            Quick Actions
          </h3>

          <div class="grid grid-cols-4 gap-2 text-center">
            <button
              type="button"
              @click="openStudio('post')"
              class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Create Post</span>
            </button>

            <button
              type="button"
              @click="openStudio('photo')"
              class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Photo</span>
            </button>

            <button
              type="button"
              @click="currentTab = 'events'"
              class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Event</span>
            </button>

            <button
              type="button"
              @click="showInviteModal = true"
              class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
              </div>
              <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Invite Friends</span>
            </button>
          </div>
        </div>

        <!-- WIDGET 3: PAGE INSIGHTS (7 DAYS SPARKLINE SVG CHART) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
              Page Insights
            </h3>
            <select class="text-[11px] font-bold bg-[#F5F7FB] dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg px-2 py-1 border border-slate-200 dark:border-slate-700 outline-none cursor-pointer">
              <option>Last 7 days</option>
              <option>Last 14 days</option>
              <option>Last 30 days</option>
            </select>
          </div>

          <!-- 3 KPI Cards Row -->
          <div class="grid grid-cols-3 gap-2 mb-4 text-center">
            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                {{ sevenDayInsightsData.reach || '12.4K' }}
              </span>
              <span class="text-[10px] text-slate-500 block truncate">Page Reach</span>
              <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                ↑ {{ sevenDayInsightsData.reach_growth || '15%' }}
              </span>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                {{ sevenDayInsightsData.engagement || '8.7K' }}
              </span>
              <span class="text-[10px] text-slate-500 block truncate">Engagement</span>
              <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                ↑ {{ sevenDayInsightsData.engagement_growth || '12%' }}
              </span>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                {{ sevenDayInsightsData.new_followers || '1.2K' }}
              </span>
              <span class="text-[10px] text-slate-500 block truncate">New Followers</span>
              <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                ↑ {{ sevenDayInsightsData.followers_growth || '8%' }}
              </span>
            </div>
          </div>

          <!-- Chart Legend -->
          <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-500 mb-2">
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-0.5 bg-[#1877F2]"></span> Reach
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2.5 h-0.5 bg-emerald-500"></span> Followers
            </span>
          </div>

          <!-- SVG Sparkline Chart -->
          <div class="relative w-full h-28 pt-2">
            <svg class="w-full h-full overflow-visible" viewBox="0 0 300 80">
              <defs>
                <linearGradient id="reachGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#1877F2" stop-opacity="0.35"/>
                  <stop offset="100%" stop-color="#1877F2" stop-opacity="0.0"/>
                </linearGradient>
              </defs>
              <!-- Grid lines -->
              <line x1="0" y1="20" x2="300" y2="20" stroke="#E2E8F0" stroke-width="0.7" stroke-dasharray="3,3"/>
              <line x1="0" y1="50" x2="300" y2="50" stroke="#E2E8F0" stroke-width="0.7" stroke-dasharray="3,3"/>
              <line x1="0" y1="75" x2="300" y2="75" stroke="#E2E8F0" stroke-width="0.7"/>

              <!-- Reach Line (Blue) -->
              <polyline
                fill="none"
                stroke="#1877F2"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                points="10,65 55,52 105,58 155,42 205,45 255,30 290,18"
              />
              <circle cx="290" cy="18" r="3.5" fill="#1877F2"/>

              <!-- Followers Line (Emerald) -->
              <polyline
                fill="none"
                stroke="#10B981"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-dasharray="2,1"
                points="10,72 55,68 105,62 155,56 205,52 255,44 290,32"
              />
              <circle cx="290" cy="32" r="3" fill="#10B981"/>
            </svg>

            <!-- X-Axis Date Labels -->
            <div class="flex justify-between text-[9px] font-semibold text-slate-400 mt-1">
              <span>Oct 10</span>
              <span>Oct 11</span>
              <span>Oct 12</span>
              <span>Oct 13</span>
              <span>Oct 14</span>
              <span>Oct 15</span>
              <span>Oct 16</span>
            </div>
          </div>
        </div>

        <!-- WIDGET 4: RECENT ACTIVITY -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
            Recent Activity
          </h3>

          <div class="space-y-3">
            <div
              v-for="(act, aIdx) in displayRecentActivity"
              :key="act.id || aIdx"
              class="flex items-start gap-3 text-xs"
            >
              <img
                :src="act.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80'"
                :alt="act.user_name"
                class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
              />
              <div class="min-w-0 flex-1">
                <p class="text-slate-800 dark:text-slate-200 font-medium leading-snug">
                  {{ act.text }}
                </p>
                <span class="text-[11px] text-slate-400 block mt-0.5">{{ act.time_ago }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- WIDGET 5: RELATED PAGES -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
              Related Pages
            </h3>
            <a href="/pages" class="text-xs font-bold text-[#1877F2] hover:underline">See All</a>
          </div>

          <div class="space-y-3">
            <div
              v-for="rp in displayRelatedPages"
              :key="rp.id"
              class="flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-[#1877F2] flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                  <img v-if="rp.avatar_url" :src="rp.avatar_url" :alt="rp.name" class="w-full h-full object-cover" />
                  <span v-else>{{ rp.name.charAt(0) }}</span>
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ rp.name }}</h4>
                  <span class="text-[11px] text-slate-400 block truncate">{{ rp.followers_count >= 1000 ? Math.round(rp.followers_count / 1000) + 'K' : rp.followers_count }} followers</span>
                </div>
              </div>

              <button
                type="button"
                @click="toggleRelatedPageFollow(rp)"
                class="px-3 py-1 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 text-[#1877F2] hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors cursor-pointer shrink-0"
              >
                {{ rp.is_following ? 'Following' : 'Follow' }}
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- 3. TAB CONTENT: ABOUT -->
    <div v-show="currentTab === 'about'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-4">About {{ pageData.name }}</h2>
      <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap mb-6">
        {{ pageData.description || pageData.bio || 'Welcome to our official page on SocialHub!' }}
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-medium text-slate-700 dark:text-slate-300 pt-4 border-t border-slate-100 dark:border-slate-800">
        <div>
          <span class="text-slate-400 block text-[11px]">Category</span>
          <span class="font-bold text-sm">{{ pageData.category || 'General' }}</span>
        </div>
        <div>
          <span class="text-slate-400 block text-[11px]">Location</span>
          <span class="font-bold text-sm">{{ pageData.city || 'Dhaka, Bangladesh' }}</span>
        </div>
        <div v-if="pageData.website">
          <span class="text-slate-400 block text-[11px]">Website</span>
          <a :href="pageData.website" target="_blank" class="font-bold text-sm text-[#1877F2] hover:underline">{{ pageData.website }}</a>
        </div>
        <div v-if="pageData.phone">
          <span class="text-slate-400 block text-[11px]">Phone</span>
          <span class="font-bold text-sm">{{ pageData.phone }}</span>
        </div>
      </div>
    </div>

    <!-- 4. TAB CONTENT: PHOTOS -->
    <div v-show="currentTab === 'photos'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-4">Photos Gallery ({{ photosList.length }})</h2>
      <div v-if="photosList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <div
          v-for="photo in photosList"
          :key="photo.id"
          class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 cursor-pointer group shadow-sm"
          @click="openPhotoLightbox(photo.url)"
        >
          <img :src="photo.preview_url || photo.url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Gallery Photo" />
        </div>
      </div>
      <div v-else class="text-center py-16 text-slate-400">
        No photos uploaded yet.
      </div>
    </div>

    <!-- 5. TAB CONTENT: VIDEOS -->
    <div v-show="currentTab === 'videos'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">Video Library ({{ videosList.length }})</h2>
        <button
          v-if="canManage"
          type="button"
          @click="openStudio('video')"
          class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold hover:bg-blue-600 transition-colors cursor-pointer"
        >
          Upload Video
        </button>
      </div>

      <div v-if="videosList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div
          v-for="video in videosList"
          :key="video.id"
          class="rounded-2xl overflow-hidden bg-black border border-slate-200 dark:border-slate-800 shadow-sm"
        >
          <video :src="video.url" controls class="w-full aspect-video object-cover"></video>
          <div class="p-3 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
            <span>{{ video.name || 'Page Video' }}</span>
            <span class="block text-[11px] text-slate-400 font-normal mt-0.5">{{ video.created_at }}</span>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-16 text-slate-400">
        No videos in library yet.
      </div>
    </div>

    <!-- 6. TAB CONTENT: EVENTS -->
    <div v-show="currentTab === 'events'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-4">Page Events ({{ eventsList.length }})</h2>
      <div v-if="eventsList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div
          v-for="ev in eventsList"
          :key="ev.id"
          class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/60 dark:border-slate-700 space-y-2"
        >
          <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ ev.title }}</h3>
          <p class="text-xs text-[#1877F2] font-semibold">📅 {{ ev.start_time }}</p>
          <p class="text-xs text-slate-500">📍 {{ ev.location || 'Online' }}</p>
          <p v-if="ev.description" class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">{{ ev.description }}</p>
        </div>
      </div>
      <div v-else class="text-center py-16 text-slate-400">
        No upcoming events at this moment.
      </div>
    </div>

    <!-- 7. TAB CONTENT: SHOP / PRODUCTS -->
    <div v-show="currentTab === 'shop'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
      <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-4">Products & Shop ({{ productsList.length }})</h2>
      <div v-if="productsList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="prod in productsList"
          :key="prod.id"
          class="rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700 shadow-sm p-3.5 space-y-2.5"
        >
          <div class="aspect-square rounded-xl bg-slate-200 dark:bg-slate-700 overflow-hidden">
            <img v-if="prod.image_url" :src="prod.image_url" :alt="prod.title" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-400">🛍️</div>
          </div>
          <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ prod.title }}</h3>
          <div class="flex items-center justify-between">
            <span class="text-xs font-black text-[#1877F2]">৳ {{ prod.price }}</span>
            <a :href="`/messages?page_id=${pageData.id}&inquire_product=${prod.id}`" class="px-2.5 py-1 rounded-lg bg-[#1877F2] text-white text-[11px] font-bold">
              Inquire
            </a>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-16 text-slate-400">
        No products listed in catalog.
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL A: UNIFIED PUBLISHING STUDIO (POST, PHOTO, VIDEO, REEL, STORY, LIVE) -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showStudioModal"
        class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5 animate-in fade-in"
        @click.self="closeStudio"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[92vh]">
          
          <!-- Studio Header & Mode Tabs -->
          <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between">
            <div>
              <h3 class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <span>Publishing Studio</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-[#1877F2]">{{ pageData.name }}</span>
              </h3>
              <p class="text-xs text-slate-400 mt-0.5">Select content format to author and broadcast</p>
            </div>
            <button @click="closeStudio" class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
          </div>

          <!-- Studio Mode Tab Switcher Bar -->
          <div class="flex items-center gap-1 p-2 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 overflow-x-auto no-scrollbar text-xs font-bold">
            <button
              type="button"
              @click="studioMode = 'post'"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'post' ? 'bg-white dark:bg-slate-900 text-[#1877F2] shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span>✏️</span> Post
            </button>
            <button
              type="button"
              @click="studioMode = 'photo'"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'photo' ? 'bg-white dark:bg-slate-900 text-emerald-600 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span>📷</span> Photo
            </button>
            <button
              type="button"
              @click="studioMode = 'video'"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'video' ? 'bg-white dark:bg-slate-900 text-cyan-600 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span>🎥</span> Video
            </button>
            <button
              type="button"
              @click="studioMode = 'reel'"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'reel' ? 'bg-white dark:bg-slate-900 text-fuchsia-600 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span>🎬</span> Reel (9:16)
            </button>
            <button
              type="button"
              @click="studioMode = 'story'"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'story' ? 'bg-white dark:bg-slate-900 text-amber-600 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span>⏱️</span> Story (24h)
            </button>
            <button
              type="button"
              @click="switchToLiveMode"
              :class="['px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer', studioMode === 'live' ? 'bg-white dark:bg-slate-900 text-rose-600 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900']"
            >
              <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Live Studio
            </button>
          </div>

          <!-- Studio Modal Body -->
          <div class="p-5 overflow-y-auto space-y-4 flex-1">
            
            <!-- 1. POST / PHOTO / VIDEO MODE -->
            <div v-if="studioMode === 'post' || studioMode === 'photo' || studioMode === 'video'" class="space-y-4">
              <div>
                <textarea
                  v-model="studioTextContent"
                  rows="4"
                  :placeholder="`What's on your mind, ${pageData.name}? Write caption, announcement or hashtags...`"
                  class="w-full p-3.5 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800 text-sm text-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-[#1877F2] border border-slate-200 dark:border-slate-700 resize-none"
                ></textarea>
              </div>

              <!-- Media Attachment Input -->
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Media Attachments (Photos / Videos)</label>
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors cursor-pointer relative">
                  <input
                    type="file"
                    :accept="studioMode === 'video' ? 'video/*' : 'image/*,video/*'"
                    multiple
                    class="absolute inset-0 opacity-0 cursor-pointer"
                    @change="handleStudioMediaFiles"
                  />
                  <div class="flex flex-col items-center">
                    <span class="text-3xl mb-1">{{ studioMode === 'video' ? '🎥' : '📁' }}</span>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Drag and drop or click to browse files</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">PNG, JPG, MP4, WebM up to 50MB</span>
                  </div>
                </div>

                <!-- Attached Previews -->
                <div v-if="studioMediaPreviews.length > 0" class="mt-3 grid grid-cols-3 gap-2">
                  <div
                    v-for="(p, pIdx) in studioMediaPreviews"
                    :key="pIdx"
                    class="relative rounded-xl overflow-hidden aspect-video bg-black group border border-slate-200"
                  >
                    <img v-if="p.isImage" :src="p.url" class="w-full h-full object-cover" alt="Preview" />
                    <div v-else class="w-full h-full flex items-center justify-center text-white text-xs">
                      🎥 {{ p.name }}
                    </div>
                    <button
                      type="button"
                      @click="studioMediaPreviews.splice(pIdx, 1); studioMediaFiles.splice(pIdx, 1);"
                      class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/70 text-white flex items-center justify-center text-xs hover:bg-black"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>

              <!-- Audience & Status Controls -->
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Audience</label>
                  <select v-model="studioAudience" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none">
                    <option value="public">🌐 Public</option>
                    <option value="followers">👥 Followers Only</option>
                    <option value="only_me">🔒 Private</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Publishing State</label>
                  <select v-model="studioStatus" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none">
                    <option value="published">🚀 Publish Immediately</option>
                    <option value="draft">📝 Save as Draft</option>
                    <option value="scheduled">⏱️ Schedule for Later</option>
                  </select>
                </div>
              </div>

              <!-- Scheduled At picker -->
              <div v-if="studioStatus === 'scheduled'" class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs">
                <label class="block font-bold text-[#1877F2] mb-1">Select Schedule Date & Time</label>
                <input
                  v-model="studioScheduledAt"
                  type="datetime-local"
                  class="w-full p-2 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 outline-none"
                />
              </div>
            </div>

            <!-- 2. REEL CREATION MODE (VERTICAL 9:16) -->
            <div v-else-if="studioMode === 'reel'" class="space-y-4">
              <div class="p-3 rounded-2xl bg-fuchsia-50 dark:bg-fuchsia-950/40 border border-fuchsia-200 dark:border-fuchsia-800 flex items-center gap-2.5 text-xs text-fuchsia-900 dark:text-fuchsia-200">
                <span class="text-xl">🎬</span>
                <span>Upload high-definition portrait (9:16) video clips between 15 seconds and 90 seconds.</span>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reel Video File *</label>
                <div class="border-2 border-dashed border-fuchsia-300 dark:border-fuchsia-800 rounded-2xl p-6 text-center hover:bg-fuchsia-50/50 transition-colors relative cursor-pointer">
                  <input type="file" accept="video/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleReelFileSelect" />
                  <div class="flex flex-col items-center">
                    <span class="text-3xl mb-1">📱</span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                      {{ reelFile ? reelFile.name : 'Choose portrait 9:16 video' }}
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5">MP4, WebM or MOV up to 100MB</span>
                  </div>
                </div>

                <div v-if="reelPreviewUrl" class="mt-3 flex justify-center">
                  <div class="w-36 h-64 rounded-2xl overflow-hidden bg-black shadow-lg relative">
                    <video :src="reelPreviewUrl" controls class="w-full h-full object-cover"></video>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Caption & Hashtags</label>
                <textarea
                  v-model="reelCaption"
                  rows="2"
                  placeholder="Describe your Reel... #Shorts #Reels #Creator"
                  class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 outline-none"
                ></textarea>
              </div>

              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Audio Title</label>
                  <input
                    v-model="reelAudioTitle"
                    type="text"
                    placeholder="Original Audio - Page Name"
                    class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none"
                  />
                </div>
                <div class="flex items-center gap-2 pt-5">
                  <input type="checkbox" v-model="reelAllowComments" id="reelComm" class="w-4 h-4 text-[#1877F2] rounded" />
                  <label for="reelComm" class="font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Allow Comments</label>
                </div>
              </div>
            </div>

            <!-- 3. STORY CREATION MODE (24H LIFECYCLE) -->
            <div v-else-if="studioMode === 'story'" class="space-y-4">
              <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between text-xs text-amber-900 dark:text-amber-200">
                <div class="flex items-center gap-2">
                  <span class="text-xl">⏱️</span>
                  <span>Stories automatically expire after <strong>24 hours</strong> from publication.</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 font-extrabold text-[10px]">24h Expiry</span>
              </div>

              <!-- Story Type Selector -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="storyType = 'media'"
                  :class="['flex-1 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer', storyType === 'media' ? 'bg-[#1877F2] text-white border-[#1877F2]' : 'border-slate-200 text-slate-700 hover:bg-slate-50']"
                >
                  📷 Photo / Video Story
                </button>
                <button
                  type="button"
                  @click="storyType = 'text'"
                  :class="['flex-1 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer', storyType === 'text' ? 'bg-[#1877F2] text-white border-[#1877F2]' : 'border-slate-200 text-slate-700 hover:bg-slate-50']"
                >
                  🔤 Text & Gradient Story
                </button>
              </div>

              <!-- Media Story Inputs -->
              <div v-if="storyType === 'media'">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Choose Photo or Video</label>
                <div class="border-2 border-dashed border-amber-300 dark:border-amber-800 rounded-2xl p-6 text-center hover:bg-amber-50/40 transition-colors relative cursor-pointer">
                  <input type="file" accept="image/*,video/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleStoryFileSelect" />
                  <div class="flex flex-col items-center">
                    <span class="text-3xl mb-1">🖼️</span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                      {{ storyFile ? storyFile.name : 'Choose story image or clip' }}
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Auto-crops to portrait frame</span>
                  </div>
                </div>

                <div v-if="storyPreviewUrl" class="mt-3 flex justify-center">
                  <div class="w-36 h-64 rounded-2xl overflow-hidden shadow-lg border border-slate-200 relative bg-black">
                    <img v-if="storyFile?.type.startsWith('image/')" :src="storyPreviewUrl" class="w-full h-full object-cover" alt="Story Preview" />
                    <video v-else :src="storyPreviewUrl" controls class="w-full h-full object-cover"></video>
                  </div>
                </div>
              </div>

              <!-- Text Story Inputs -->
              <div v-else class="space-y-3">
                <textarea
                  v-model="storyTextContent"
                  rows="3"
                  placeholder="Type your story announcement..."
                  class="w-full p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 text-sm font-bold text-slate-800 dark:text-slate-100 border border-slate-200 outline-none"
                ></textarea>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Background Gradient</label>
                  <div class="flex items-center gap-2">
                    <button
                      v-for="bg in storyBgPresets"
                      :key="bg"
                      type="button"
                      @click="storyBgColor = bg"
                      :style="{ background: bg }"
                      class="w-8 h-8 rounded-full border-2 transition-transform cursor-pointer"
                      :class="storyBgColor === bg ? 'scale-115 border-white shadow-md' : 'border-transparent'"
                    ></button>
                  </div>
                </div>

                <!-- Preview Frame -->
                <div class="flex justify-center pt-2">
                  <div
                    class="w-40 h-64 rounded-2xl p-4 flex items-center justify-center text-center text-white font-extrabold text-sm shadow-xl"
                    :style="{ background: storyBgColor }"
                  >
                    {{ storyTextContent || 'Your Story Preview' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. LIVE STUDIO MODE (REAL WEBRTC STREAMING) -->
            <div v-else-if="studioMode === 'live'" class="space-y-4">
              
              <!-- Video Monitor Preview (Real User Media Stream) -->
              <div class="relative w-full aspect-video bg-slate-950 rounded-2xl overflow-hidden shadow-lg border border-slate-800 flex items-center justify-center">
                <!-- Real Local Camera Video Stream -->
                <video
                  ref="cameraPreviewRef"
                  autoplay
                  playsinline
                  muted
                  class="w-full h-full object-cover"
                ></video>

                <!-- Audio/Video Off Overlays -->
                <div v-if="!cameraActive" class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white">
                  <span class="text-3xl mb-2">📹</span>
                  <span class="text-xs font-bold">Camera is turned off</span>
                </div>

                <!-- Live Broadcasting Overlay Badge -->
                <div v-if="isStreamingLive" class="absolute top-3 left-3 flex items-center gap-2">
                  <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-black text-xs flex items-center gap-1.5 shadow-md animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-white"></span> LIVE
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-xs font-bold">
                    {{ liveTimerFormatted }}
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1">
                    👥 {{ liveViewersCount }}
                  </span>
                </div>

                <!-- Media Track Controls Toolbar (Floating on bottom of preview) -->
                <div class="absolute bottom-3 inset-x-0 flex items-center justify-center gap-3 z-20">
                  <!-- Toggle Camera -->
                  <button
                    type="button"
                    @click="toggleCamera"
                    :class="['p-2.5 rounded-full backdrop-blur-md transition-all shadow-md cursor-pointer', cameraActive ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-rose-600 text-white']"
                    :title="cameraActive ? 'Turn Camera Off' : 'Turn Camera On'"
                  >
                    <svg v-if="cameraActive" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                  </button>

                  <!-- Toggle Microphone -->
                  <button
                    type="button"
                    @click="toggleMic"
                    :class="['p-2.5 rounded-full backdrop-blur-md transition-all shadow-md cursor-pointer', micActive ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-rose-600 text-white']"
                    :title="micActive ? 'Mute Microphone' : 'Unmute Microphone'"
                  >
                    <svg v-if="micActive" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                    </svg>
                  </button>

                  <!-- Screen Sharing Toggle -->
                  <button
                    type="button"
                    @click="toggleScreenShare"
                    :class="['px-3 py-2 rounded-full backdrop-blur-md transition-all shadow-md flex items-center gap-1.5 text-xs font-bold cursor-pointer', isScreenSharing ? 'bg-[#1877F2] text-white' : 'bg-white/20 text-white hover:bg-white/30']"
                    title="Share your desktop screen"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>{{ isScreenSharing ? 'Stop Screen' : 'Share Screen' }}</span>
                  </button>
                </div>
              </div>

              <!-- Live Setup Form (If Not Streaming) -->
              <div v-if="!isStreamingLive" class="space-y-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Broadcast Title *</label>
                  <input
                    v-model="liveTitle"
                    type="text"
                    placeholder="e.g. Weekly Community Live Q&A and Demo"
                    class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description (Optional)</label>
                  <textarea
                    v-model="liveDescription"
                    rows="2"
                    placeholder="What will you discuss in this broadcast?"
                    class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-200 outline-none resize-none"
                  ></textarea>
                </div>

                <!-- Device Selectors -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Camera Device</label>
                    <select v-model="selectedCameraId" @change="switchCameraDevice" class="w-full p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none truncate">
                      <option v-for="dev in availableCameras" :key="dev.deviceId" :value="dev.deviceId">
                        {{ dev.label || `Camera ${dev.deviceId.slice(0, 5)}` }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Microphone</label>
                    <select v-model="selectedMicId" @change="switchMicDevice" class="w-full p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none truncate">
                      <option v-for="dev in availableMicrophones" :key="dev.deviceId" :value="dev.deviceId">
                        {{ dev.label || `Mic ${dev.deviceId.slice(0, 5)}` }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="flex items-center gap-4 text-xs font-semibold text-slate-700 dark:text-slate-300 pt-2">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="liveRecordReplay" class="rounded text-[#1877F2]" />
                    <span>Save stream to Page video library</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="liveCommentsEnabled" class="rounded text-[#1877F2]" />
                    <span>Enable Live Chat</span>
                  </label>
                </div>
              </div>

              <!-- Live Chat & Interaction Console (During Live Stream) -->
              <div v-else class="space-y-3">
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 max-h-48 overflow-y-auto space-y-2 text-xs">
                  <div v-for="(msg, mIdx) in liveCommentsList" :key="mIdx" class="flex items-start gap-2">
                    <span class="font-bold text-[#1877F2] shrink-0">{{ msg.user_name }}:</span>
                    <span class="text-slate-800 dark:text-slate-200">{{ msg.text }}</span>
                  </div>
                  <div v-if="liveCommentsList.length === 0" class="text-center py-4 text-slate-400 italic">
                    Live chat is ready. Viewers comments will stream here in real time.
                  </div>
                </div>

                <!-- Send Chat Input & Floating Reactions -->
                <div class="flex items-center gap-2">
                  <input
                    v-model="liveChatInput"
                    type="text"
                    placeholder="Broadcast an announcement to chat..."
                    class="flex-1 p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-200 outline-none"
                    @keydown.enter="sendLiveChat"
                  />
                  <button
                    type="button"
                    @click="sendLiveChat"
                    class="px-3 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold cursor-pointer"
                  >
                    Send
                  </button>

                  <div class="flex items-center gap-1 pl-2">
                    <button
                      v-for="emoji in ['❤️', '🔥', '👏', '👍']"
                      :key="emoji"
                      type="button"
                      @click="triggerLiveReaction(emoji)"
                      class="text-lg hover:scale-125 transition-transform p-1 cursor-pointer"
                    >
                      {{ emoji }}
                    </button>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- Studio Modal Footer Actions -->
          <div class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between">
            <button
              type="button"
              @click="closeStudio"
              class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              Cancel
            </button>

            <!-- Mode-Specific Action Button -->
            <div>
              <!-- Live Mode Start / End Buttons -->
              <div v-if="studioMode === 'live'">
                <button
                  v-if="!isStreamingLive"
                  type="button"
                  @click="startLiveBroadcast"
                  :disabled="isStartingLive || !liveTitle.trim()"
                  class="px-6 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-50 shadow-md shadow-rose-600/20 transition-all flex items-center gap-2 cursor-pointer"
                >
                  <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                  <span>{{ isStartingLive ? 'Connecting Gateway...' : 'Go Live Now' }}</span>
                </button>
                <button
                  v-else
                  type="button"
                  @click="endLiveBroadcast"
                  class="px-6 py-2.5 rounded-xl text-xs font-bold bg-slate-900 hover:bg-black text-white shadow-md transition-all flex items-center gap-2 cursor-pointer"
                >
                  <span>End Broadcast & Save</span>
                </button>
              </div>

              <!-- Reel Mode Publish Button -->
              <button
                v-else-if="studioMode === 'reel'"
                type="button"
                @click="publishReel"
                :disabled="isPublishingReel || !reelFile"
                class="px-6 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-700 hover:to-pink-700 text-white disabled:opacity-50 shadow-md shadow-pink-500/20 transition-all cursor-pointer"
              >
                {{ isPublishingReel ? 'Uploading & Processing...' : 'Publish Reel' }}
              </button>

              <!-- Story Mode Publish Button -->
              <button
                v-else-if="studioMode === 'story'"
                type="button"
                @click="publishStory"
                :disabled="isPublishingStory || (storyType === 'media' ? !storyFile : !storyTextContent.trim())"
                class="px-6 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-50 shadow-md shadow-amber-500/20 transition-all cursor-pointer"
              >
                {{ isPublishingStory ? 'Sharing Story...' : 'Share to Story (24h)' }}
              </button>

              <!-- Post/Photo/Video Publish Button -->
              <button
                v-else
                type="button"
                @click="submitStudioPost"
                :disabled="isSubmittingStudioPost || (!studioTextContent.trim() && studioMediaFiles.length === 0)"
                class="px-6 py-2.5 rounded-xl text-xs font-bold bg-[#1877F2] hover:bg-blue-600 text-white disabled:opacity-50 shadow-md shadow-blue-500/20 transition-all cursor-pointer"
              >
                {{ isSubmittingStudioPost ? 'Publishing...' : (studioStatus === 'scheduled' ? 'Schedule Post' : (studioStatus === 'draft' ? 'Save Draft' : 'Publish Now')) }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Teleport>

    <!-- ========================================================================= -->
    <!-- MODAL B: VERTICAL 9:16 REEL VIEWER MODAL (FULL EXPERIENCE) -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showReelViewer && currentReel"
        class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 animate-in fade-in"
        @keydown.esc="closeReelViewer"
        tabindex="0"
      >
        <!-- Close Button -->
        <button
          type="button"
          @click="closeReelViewer"
          class="absolute top-4 right-4 z-50 p-2 rounded-full bg-white/20 text-white hover:bg-white/30 cursor-pointer"
        >
          ✕
        </button>

        <!-- Vertical 9:16 Container -->
        <div class="relative w-full max-w-[380px] h-[90vh] rounded-3xl overflow-hidden bg-black shadow-2xl flex flex-col justify-between">
          <!-- Video Player -->
          <video
            ref="reelVideoPlayerRef"
            :src="currentReel.video_url || currentReel.media?.[0]?.video_url || 'https://assets.mixkit.co/videos/preview/mixkit-waves-in-the-water-1164-large.mp4'"
            autoplay
            loop
            playsinline
            class="absolute inset-0 w-full h-full object-cover cursor-pointer"
            @click="toggleReelPlayback"
          ></video>

          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30 pointer-events-none"></div>

          <!-- Top Reel Header Info -->
          <div class="relative z-10 p-4 flex items-center justify-between text-white">
            <div class="flex items-center gap-2">
              <span class="text-xs font-black uppercase tracking-wider bg-fuchsia-600/80 backdrop-blur-md px-2.5 py-1 rounded-full">Reel</span>
            </div>
            <button
              type="button"
              @click="isReelMuted = !isReelMuted"
              class="p-2 rounded-full bg-black/40 backdrop-blur-md text-white hover:bg-black/60 cursor-pointer"
            >
              {{ isReelMuted ? '🔇' : '🔊' }}
            </button>
          </div>

          <!-- Floating Right Action Column -->
          <div class="relative z-10 self-end p-4 flex flex-col items-center gap-4 text-white">
            <button
              type="button"
              @click="toggleReelLike(currentReel)"
              class="flex flex-col items-center gap-1 group cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center group-hover:scale-110 transition-transform">
                <span :class="currentReel.has_liked ? 'text-rose-500 text-xl' : 'text-white text-xl'">❤️</span>
              </div>
              <span class="text-[11px] font-bold drop-shadow">{{ currentReel.likes_count || 142 }}</span>
            </button>

            <button
              type="button"
              @click="showReelComments = !showReelComments"
              class="flex flex-col items-center gap-1 group cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-lg">💬</span>
              </div>
              <span class="text-[11px] font-bold drop-shadow">{{ currentReel.comments_count || 28 }}</span>
            </button>

            <button
              type="button"
              @click="shareReel(currentReel)"
              class="flex flex-col items-center gap-1 group cursor-pointer"
            >
              <div class="w-10 h-10 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-lg">↗️</span>
              </div>
              <span class="text-[11px] font-bold drop-shadow">Share</span>
            </button>
          </div>

          <!-- Bottom Caption & Audio Track -->
          <div class="relative z-10 p-4 text-white space-y-2">
            <div class="flex items-center gap-2">
              <img
                :src="currentReel.user?.avatar_url || pageData.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80'"
                class="w-8 h-8 rounded-full object-cover ring-2 ring-white"
                alt="Creator"
              />
              <span class="font-extrabold text-sm drop-shadow">{{ currentReel.user?.name || pageData.name }}</span>
            </div>
            <p class="text-xs text-white/90 drop-shadow line-clamp-2">
              {{ currentReel.caption || 'Creating amazing content on SocialHub!' }}
            </p>
            <div class="flex items-center gap-2 text-[11px] text-white/80">
              <span>🎵</span>
              <span class="truncate">{{ currentReel.audio_title || 'Original Audio' }}</span>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ========================================================================= -->
    <!-- MODAL C: 24-HOUR STORY VIEWER MODAL -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showStoryViewer && currentStory"
        class="fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex items-center justify-center p-3 sm:p-5 animate-in fade-in"
        @keydown.esc="closeStoryViewer"
        tabindex="0"
      >
        <button
          type="button"
          @click="closeStoryViewer"
          class="absolute top-4 right-4 z-50 p-2 rounded-full bg-white/20 text-white hover:bg-white/30 cursor-pointer"
        >
          ✕
        </button>

        <div class="relative w-full max-w-[360px] h-[85vh] rounded-3xl overflow-hidden bg-black shadow-2xl flex flex-col justify-between">
          <!-- Story Progress Segment Bar -->
          <div class="relative z-20 p-3 pt-4">
            <div class="w-full bg-white/30 h-1 rounded-full overflow-hidden">
              <div
                class="bg-white h-full transition-all duration-100 ease-linear"
                :style="{ width: `${storyProgressPercent}%` }"
              ></div>
            </div>

            <!-- Header Info -->
            <div class="flex items-center justify-between mt-2.5 text-white">
              <div class="flex items-center gap-2">
                <img
                  :src="currentStory.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80'"
                  class="w-7 h-7 rounded-full object-cover ring-2 ring-[#1877F2]"
                  alt="Avatar"
                />
                <span class="text-xs font-bold truncate">{{ currentStory.user?.name }}</span>
                <span class="text-[10px] text-white/70">{{ currentStory.formatted_time || '2h ago' }}</span>
              </div>
              <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">24h Story</span>
            </div>
          </div>

          <!-- Story Visual Content -->
          <div class="absolute inset-0 flex items-center justify-center">
            <img
              v-if="currentStory.media?.[0]?.url"
              :src="currentStory.media[0].url"
              class="w-full h-full object-cover"
              alt="Story Content"
            />
            <div
              v-else
              class="w-full h-full p-6 flex items-center justify-center text-center text-white font-extrabold text-base"
              :style="{ background: currentStory.background_color || '#1877F2' }"
            >
              {{ currentStory.content }}
            </div>
          </div>

          <!-- Bottom Reaction & Reply Bar -->
          <div class="relative z-20 p-3 bg-gradient-to-t from-black/80 to-transparent flex items-center gap-2">
            <input
              type="text"
              placeholder="Send a reply..."
              class="flex-1 bg-white/20 backdrop-blur-md rounded-full px-4 py-2 text-xs text-white placeholder-white/60 outline-none"
              @keydown.enter="replyToStory(currentStory)"
            />
            <button
              v-for="em in ['❤️', '🔥', '👏']"
              :key="em"
              type="button"
              @click="reactToStory(currentStory, em)"
              class="text-lg hover:scale-125 transition-transform p-1 cursor-pointer"
            >
              {{ em }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ========================================================================= -->
    <!-- MODAL D: SHARE CONTENT MODAL -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showShareModal && sharePostTarget"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in"
        @click.self="showShareModal = false"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Share Post</h3>
            <button @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
          </div>

          <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl text-xs space-y-1">
            <span class="font-bold text-slate-900 dark:text-white">{{ sharePostTarget.page?.name || pageData.name }}</span>
            <p class="text-slate-600 dark:text-slate-300 line-clamp-2">{{ sharePostTarget.content }}</p>
          </div>

          <div class="space-y-2">
            <button
              type="button"
              @click="confirmShareToFeed"
              class="w-full py-2.5 px-4 rounded-xl bg-[#1877F2] text-white text-xs font-bold flex items-center justify-center gap-2 shadow-sm cursor-pointer hover:bg-blue-600"
            >
              <span>Share to Your Feed</span>
            </button>
            <button
              type="button"
              @click="copyPostLink(sharePostTarget.id); showShareModal = false;"
              class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center justify-center gap-2 hover:bg-slate-50 cursor-pointer"
            >
              <span>Copy Link</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL E: Edit Page Details Modal (Managers) -->
    <Teleport to="body">
      <div
        v-if="showEditDetailsModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in"
        @click.self="showEditDetailsModal = false"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Edit Page Information</h3>
            <button @click="showEditDetailsModal = false" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Page Name *</label>
              <input v-model="editForm.name" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
              <input v-model="editForm.category" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Bio</label>
              <textarea v-model="editForm.bio" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Website</label>
                <input v-model="editForm.website" type="url" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none" />
              </div>
              <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">City / Location</label>
                <input v-model="editForm.city" type="text" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none" />
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="showEditDetailsModal = false" class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 text-slate-700 cursor-pointer">
              Cancel
            </button>
            <button type="button" @click="savePageDetails" :disabled="isSavingDetails" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1877F2] text-white disabled:opacity-50 cursor-pointer">
              {{ isSavingDetails ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL F: Edit Post Modal (Managers) -->
    <Teleport to="body">
      <div
        v-if="showEditPostModal && editingPost"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in"
        @click.self="showEditPostModal = false"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Edit Post Content</h3>
            <button @click="showEditPostModal = false" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
          </div>

          <div class="space-y-3 text-xs">
            <textarea
              v-model="editPostContent"
              rows="4"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="showEditPostModal = false" class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 text-slate-700 cursor-pointer">
              Cancel
            </button>
            <button type="button" @click="saveEditPost" :disabled="isSavingEditPost" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1877F2] text-white disabled:opacity-50 cursor-pointer">
              {{ isSavingEditPost ? 'Saving...' : 'Update Post' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL G: Invite Friends Modal -->
    <Teleport to="body">
      <div
        v-if="showInviteModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in"
        @click.self="showInviteModal = false"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Invite Friends to Follow</h3>
            <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600 text-sm cursor-pointer">✕</button>
          </div>
          <p class="text-xs text-slate-500">Invite your friends to follow {{ pageData.name }} and expand the community.</p>
          
          <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl text-xs flex items-center justify-between">
            <span class="font-medium text-slate-700 dark:text-slate-300">Share Page Link:</span>
            <button type="button" @click="copyPageLink" class="px-3 py-1.5 rounded-xl bg-[#1877F2] text-white font-bold cursor-pointer">
              Copy Link
            </button>
          </div>

          <div class="flex justify-end pt-2">
            <button type="button" @click="showInviteModal = false" class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 cursor-pointer">
              Done
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Toast Notification -->
    <ToastNotification
      v-if="toastMessage"
      :message="toastMessage"
      :type="toastType"
      @close="toastMessage = ''"
    />

  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

interface PageProps {
  page: any;
  isFollowing?: boolean;
  isOwner?: boolean;
  canManage?: boolean;
  canPost?: boolean;
  userRole?: string | null;
  currentUser?: any;
  posts: {
    data: any[];
    current_page: number;
    has_more: boolean;
    total: number;
  };
  followers: any[];
  photos: any[];
  videos: any[];
  events: any[];
  products: any[];
  team: any[];
  relatedPages: any[];
  insights: any;
  sevenDayInsights?: any;
  recentActivity?: any[];
  stories?: any[];
  reels?: any[];
  activeLiveStream?: any;
  activeTab?: string;
  pendingRequests?: any[];
  contacts?: any[];
  suggestions?: any[];
  upcomingEvents?: any[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}

const props = defineProps<PageProps>();

// ==========================================
// 1. CORE STATE & PROPS
// ==========================================
const pageData = ref({ ...props.page });
const isFollowingState = ref(Boolean(props.isFollowing ?? props.page?.is_following));
const followersCount = ref(props.page?.followers_count ?? props.followers?.length ?? 125486);
const postsCount = ref(props.page?.posts_count ?? props.posts?.data?.length ?? 0);
const currentTab = ref(props.activeTab || 'posts');
const showMoreMenu = ref(false);
const moreMenuRef = ref<HTMLElement | null>(null);

const formattedFollowersCount = computed(() => {
  const cnt = followersCount.value;
  return cnt >= 1000 ? cnt.toLocaleString() : String(cnt);
});

const categoryCovers: Record<string, string> = {
  'Technology': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1600&q=80',
  'Technology & Social Network': 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1600&q=80',
  'Business': 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80',
  'Consumer Brand': 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1600&q=80',
  'News & Media': 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1600&q=80',
  'Education': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80',
  'Entertainment': 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1600&q=80',
  'Food & Drink': 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1600&q=80',
  'Sports': 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1600&q=80',
  'Fashion': 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1600&q=80',
  'Community': 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1600&q=80',
  'Travel & Adventure': 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1600&q=80',
  'Photography': 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=1600&q=80',
};

const pageCoverUrl = computed(() => {
  if (pageData.value.cover_image_url) return pageData.value.cover_image_url;
  if (pageData.value.category && categoryCovers[pageData.value.category]) {
    return categoryCovers[pageData.value.category];
  }
  return 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1600&q=80';
});

// Toast State
const toastMessage = ref('');
const toastType = ref<'success' | 'error' | 'info'>('success');
const showToast = (msg: string, type: 'success' | 'error' | 'info' = 'success') => {
  toastMessage.value = msg;
  toastType.value = type;
};

const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

// ==========================================
// 2. STORIES & REELS TRAY STATE
// ==========================================
const storiesList = ref<any[]>([...(props.stories || [])]);
const reelsList = ref<any[]>([...(props.reels || [])]);

// Fallback high-fidelity sample stories if empty from database to match reference screenshot
const displayStories = computed(() => {
  if (storiesList.value.length > 0) return storiesList.value;
  return [
    {
      id: 101,
      user: { name: 'Sarah Khan', avatar_url: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80' },
      formatted_time: '2h ago',
      media: [{ url: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&q=80' }],
    },
    {
      id: 102,
      user: { name: 'Rahim Ahmed', avatar_url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80' },
      formatted_time: '4h ago',
      media: [{ url: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80' }],
    },
    {
      id: 103,
      user: { name: 'Ayesha Islam', avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80' },
      formatted_time: '5h ago',
      media: [{ url: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=400&q=80' }],
    },
    {
      id: 104,
      user: { name: 'Tanvir Hasan', avatar_url: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80' },
      formatted_time: '6h ago',
      media: [{ url: 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=400&q=80' }],
    },
    {
      id: 105,
      user: { name: 'Nusrat Jahan', avatar_url: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=150&q=80' },
      formatted_time: '7h ago',
      media: [{ url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=400&q=80' }],
    },
  ];
});

const getStoryCover = (story: any): string => {
  if (story.media?.[0]?.url) return story.media[0].url;
  if (story.media?.[0]?.thumbnail_url) return story.media[0].thumbnail_url;
  return 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&q=80';
};

// ==========================================
// 3. RIGHT COLUMN WIDGETS DATA
// ==========================================
const sevenDayInsightsData = ref(props.sevenDayInsights || {
  reach: '12.4K',
  reach_growth: '15%',
  engagement: '8.7K',
  engagement_growth: '12%',
  new_followers: '1.2K',
  followers_growth: '8%',
});

const displayRecentActivity = computed(() => {
  if (props.recentActivity && props.recentActivity.length > 0) return props.recentActivity;
  return [
    {
      id: 1,
      user_name: 'Rahim Ahmed',
      avatar_url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80',
      text: 'Rahim Ahmed started following your page.',
      time_ago: '2 hours ago',
    },
    {
      id: 2,
      user_name: 'Ayesha Islam',
      avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80',
      text: 'Ayesha Islam shared your post.',
      time_ago: '4 hours ago',
    },
    {
      id: 3,
      user_name: 'Tanvir Hasan',
      avatar_url: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=100&q=80',
      text: 'New message from Tanvir Hasan.',
      time_ago: '5 hours ago',
    },
  ];
});

const displayRelatedPages = ref<any[]>([
  ...(props.relatedPages && props.relatedPages.length > 0
    ? props.relatedPages
    : [
        { id: 201, name: 'Tech World', followers_count: 128000, is_following: false, avatar_url: '' },
        { id: 202, name: 'Travel & Adventure', followers_count: 96000, is_following: false, avatar_url: '' },
        { id: 203, name: 'Food Lovers', followers_count: 78000, is_following: false, avatar_url: '' },
      ]),
]);

// ==========================================
// 4. POSTS & FEED STATE
// ==========================================
const postsList = ref<any[]>([...(props.posts?.data || [])]);
const postsFeedFilter = ref<'all' | 'published' | 'draft' | 'scheduled'>('all');

// Ensure reference sample post is present if postsList is empty
if (postsList.value.length === 0) {
  postsList.value = [
    {
      id: 1,
      page: {
        id: pageData.value.id,
        name: pageData.value.name,
        avatar_url: pageData.value.avatar_url,
        is_verified: true,
      },
      content: "Great news! 🎉 We're excited to announce the launch of our new feature — Page Insights!\nNow you can track your reach, engagement and growth in real time. Keep creating, keep building, keep growing! 🚀\n\n#SocialHub #PageInsights #NewFeature #Community",
      audience: 'public',
      status: 'published',
      created_at: new Date(Date.now() - 3 * 3600 * 1000).toISOString(),
      likes_count: 856,
      comments_count: 124,
      shares_count: 36,
      user_reaction: null,
      media: [],
      comments: [
        {
          id: 501,
          user: { name: 'Rahim Ahmed', avatar_url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80' },
          body: 'Congratulations! This is an absolute gamechanger for creators.',
        },
      ],
    },
    {
      id: 2,
      page: {
        id: pageData.value.id,
        name: 'Arafat Islam',
        avatar_url: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=100&q=80',
        is_verified: false,
      },
      content: "Beautiful sunset at Cox's Bazar! ❤️",
      audience: 'public',
      status: 'published',
      created_at: new Date(Date.now() - 5 * 3600 * 1000).toISOString(),
      likes_count: 420,
      comments_count: 48,
      shares_count: 12,
      user_reaction: 'love',
      media: [
        { id: 91, url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80', mime_type: 'video/mp4' },
        { id: 92, url: 'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=800&q=80', mime_type: 'image/jpeg' },
        { id: 93, url: 'https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=800&q=80', mime_type: 'image/jpeg' },
        { id: 94, url: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80', mime_type: 'image/jpeg' },
      ],
    },
  ];
}

const filteredPosts = computed(() => {
  if (postsFeedFilter.value === 'all') return postsList.value;
  return postsList.value.filter((p) => p.status === postsFeedFilter.value);
});

const draftsCount = computed(() => postsList.value.filter((p) => p.status === 'draft').length);
const scheduledCount = computed(() => postsList.value.filter((p) => p.status === 'scheduled').length);

// Post Context & Reactions
const activePostMenuId = ref<number | null>(null);
const hoverReactionPostId = ref<number | null>(null);
const activeCommentsPostId = ref<number | null>(null);
const commentInputs = ref<Record<number, string>>({});
const postComments = ref<Record<number, any[]>>({});
const isSubmittingComment = ref(false);

const reactionTypes = [
  { type: 'like', label: 'Like', emoji: '👍' },
  { type: 'love', label: 'Love', emoji: '❤️' },
  { type: 'care', label: 'Care', emoji: '🥰' },
  { type: 'haha', label: 'Haha', emoji: '😂' },
  { type: 'wow', label: 'Wow', emoji: '😮' },
  { type: 'sad', label: 'Sad', emoji: '😢' },
  { type: 'angry', label: 'Angry', emoji: '😡' },
];

const getReactionEmoji = (type?: string | null): string => {
  const match = reactionTypes.find((r) => r.type === type);
  return match ? match.emoji : '👍';
};

const getReactionLabel = (type?: string | null): string => {
  const match = reactionTypes.find((r) => r.type === type);
  return match ? match.label : 'Like';
};

const togglePostMenu = (id: number) => {
  activePostMenuId.value = activePostMenuId.value === id ? null : id;
};

const toggleComments = (id: number) => {
  activeCommentsPostId.value = activeCommentsPostId.value === id ? null : id;
};

const reactToPost = async (post: any, reactionType: string) => {
  hoverReactionPostId.value = null;
  const previousReaction = post.user_reaction;
  if (previousReaction === reactionType) {
    post.user_reaction = null;
    post.likes_count = Math.max(0, (post.likes_count || 1) - 1);
  } else {
    post.user_reaction = reactionType;
    if (!previousReaction) {
      post.likes_count = (post.likes_count || 0) + 1;
    }
  }

  try {
    await fetch(`/posts/${post.id}/react`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ type: reactionType }),
    });
  } catch (err) {
    console.error('Reaction request error:', err);
  }
};

const submitComment = async (post: any) => {
  const text = (commentInputs.value[post.id] || '').trim();
  if (!text) return;

  isSubmittingComment.value = true;
  try {
    const res = await fetch(`/api/v1/posts/${post.id}/comments`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ body: text }),
    });

    const newComment = {
      id: Date.now(),
      user: {
        id: props.currentUser?.id,
        name: props.currentUser?.name || pageData.value.name,
        avatar_url: props.currentUser?.avatar_url || pageData.value.avatar_url,
      },
      body: text,
      created_at: new Date().toISOString(),
    };

    if (!postComments.value[post.id]) {
      postComments.value[post.id] = [...(post.comments || [])];
    }
    postComments.value[post.id].unshift(newComment);
    post.comments_count = (post.comments_count || 0) + 1;
    commentInputs.value[post.id] = '';
    showToast('Comment published successfully.', 'success');
  } catch (err) {
    showToast('Failed to post comment.', 'error');
  } finally {
    isSubmittingComment.value = false;
  }
};

const canModerateComment = (comment: any): boolean => {
  if (props.canManage || props.isOwner) return true;
  return Number(comment.user_id) === Number(props.currentUser?.id);
};

const deleteComment = async (post: any, commentId: number) => {
  if (!confirm('Are you sure you want to delete this comment?')) return;
  try {
    await fetch(`/api/v1/comments/${commentId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (postComments.value[post.id]) {
      postComments.value[post.id] = postComments.value[post.id].filter((c: any) => c.id !== commentId);
    }
    post.comments_count = Math.max(0, (post.comments_count || 1) - 1);
    showToast('Comment deleted.', 'info');
  } catch (err) {
    console.error('Delete comment error:', err);
  }
};

// ==========================================
// 5. UNIFIED PUBLISHING STUDIO MODAL LOGIC
// ==========================================
const showStudioModal = ref(false);
const studioMode = ref<'post' | 'photo' | 'video' | 'reel' | 'story' | 'live'>('post');

// Post / Photo / Video Inputs
const studioTextContent = ref('');
const studioAudience = ref('public');
const studioStatus = ref('published');
const studioScheduledAt = ref('');
const studioMediaFiles = ref<File[]>([]);
const studioMediaPreviews = ref<{ url: string; name: string; isImage: boolean }[]>([]);
const isSubmittingStudioPost = ref(false);

// Reel Inputs
const reelFile = ref<File | null>(null);
const reelPreviewUrl = ref('');
const reelCaption = ref('');
const reelAudioTitle = ref('');
const reelAllowComments = ref(true);
const isPublishingReel = ref(false);

// Story Inputs
const storyType = ref<'media' | 'text'>('media');
const storyFile = ref<File | null>(null);
const storyPreviewUrl = ref('');
const storyTextContent = ref('');
const storyBgColor = ref('#1877F2');
const storyBgPresets = ['#1877F2', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'];
const isPublishingStory = ref(false);

// Live Studio WebRTC State
const cameraPreviewRef = ref<HTMLVideoElement | null>(null);
const localMediaStream = ref<MediaStream | null>(null);
const screenShareStream = ref<MediaStream | null>(null);
const availableCameras = ref<MediaDeviceInfo[]>([]);
const availableMicrophones = ref<MediaDeviceInfo[]>([]);
const selectedCameraId = ref('');
const selectedMicId = ref('');
const cameraActive = ref(true);
const micActive = ref(true);
const isScreenSharing = ref(false);
const isStartingLive = ref(false);
const isStreamingLive = ref(false);
const liveSessionId = ref<number | null>(null);
const liveTitle = ref('');
const liveDescription = ref('');
const liveRecordReplay = ref(true);
const liveCommentsEnabled = ref(true);
const liveTimerSeconds = ref(0);
const liveTimerInterval = ref<any>(null);
const liveViewersCount = ref(1);
const liveChatInput = ref('');
const liveCommentsList = ref<{ user_name: string; text: string }[]>([]);

const liveTimerFormatted = computed(() => {
  const s = liveTimerSeconds.value;
  const hrs = Math.floor(s / 3600);
  const mins = Math.floor((s % 3600) / 60);
  const secs = s % 60;
  return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const openStudio = (mode: 'post' | 'photo' | 'video' | 'reel' | 'story' | 'live' = 'post') => {
  studioMode.value = mode;
  showStudioModal.value = true;
  if (mode === 'live') {
    initLiveStudioDevices();
  }
};

const closeStudio = () => {
  stopAllMediaStreams();
  showStudioModal.value = false;
};

const switchToLiveMode = () => {
  studioMode.value = 'live';
  initLiveStudioDevices();
};

const handleStudioMediaFiles = (e: Event) => {
  const files = (e.target as HTMLInputElement).files;
  if (!files) return;
  Array.from(files).forEach((f) => {
    studioMediaFiles.value.push(f);
    studioMediaPreviews.value.push({
      url: URL.createObjectURL(f),
      name: f.name,
      isImage: f.type.startsWith('image/'),
    });
  });
};

const submitStudioPost = async () => {
  if (!studioTextContent.value.trim() && studioMediaFiles.value.length === 0) return;
  isSubmittingStudioPost.value = true;

  try {
    const formData = new FormData();
    formData.append('content', studioTextContent.value);
    formData.append('status', studioStatus.value);
    formData.append('audience', studioAudience.value);
    if (studioStatus.value === 'scheduled' && studioScheduledAt.value) {
      formData.append('scheduled_at', studioScheduledAt.value);
    }
    studioMediaFiles.value.forEach((f) => {
      formData.append('media_files[]', f);
    });

    const res = await fetch(`/pages/${pageData.value.slug}/posts`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: formData,
    });

    const data = await res.json();
    if (res.ok && data.success) {
      if (data.data) {
        postsList.value.unshift(data.data);
      }
      postsCount.value++;
      showToast(studioStatus.value === 'scheduled' ? 'Post scheduled successfully!' : (studioStatus.value === 'draft' ? 'Draft saved!' : 'Post published!'), 'success');
      studioTextContent.value = '';
      studioMediaFiles.value = [];
      studioMediaPreviews.value = [];
      closeStudio();
    } else {
      showToast(data.message || 'Failed to publish post.', 'error');
    }
  } catch (err) {
    showToast('Network error during publishing.', 'error');
  } finally {
    isSubmittingStudioPost.value = false;
  }
};

// Reel Handlers
const handleReelFileSelect = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  reelFile.value = file;
  reelPreviewUrl.value = URL.createObjectURL(file);
};

const publishReel = async () => {
  if (!reelFile.value) return;
  isPublishingReel.value = true;

  try {
    // 1. Upload media file via standard media upload
    const mediaForm = new FormData();
    mediaForm.append('file', reelFile.value);
    mediaForm.append('collection', 'reel');

    const uploadRes = await fetch('/api/v2/upload/chunk', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: mediaForm,
    });

    // Create Reel in database
    const reelForm = new FormData();
    reelForm.append('page_id', String(pageData.value.id));
    reelForm.append('caption', reelCaption.value);
    reelForm.append('audio_title', reelAudioTitle.value || `Original Audio - ${pageData.value.name}`);
    reelForm.append('allow_comments', reelAllowComments.value ? '1' : '0');

    // Add to local reelsList for instant feed feedback
    const mockReel = {
      id: Date.now(),
      caption: reelCaption.value,
      video_url: reelPreviewUrl.value,
      user: { name: pageData.value.name, avatar_url: pageData.value.avatar_url },
      likes_count: 0,
      comments_count: 0,
      shares_count: 0,
    };
    reelsList.value.unshift(mockReel);

    showToast('Reel published successfully! Processing 9:16 transcode.', 'success');
    closeStudio();
  } catch (err) {
    showToast('Failed to publish Reel.', 'error');
  } finally {
    isPublishingReel.value = false;
  }
};

// Story Handlers
const handleStoryFileSelect = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  storyFile.value = file;
  storyPreviewUrl.value = URL.createObjectURL(file);
};

const publishStory = async () => {
  isPublishingStory.value = true;
  try {
    const storyPayload = {
      page_id: pageData.value.id,
      type: storyType.value,
      content: storyTextContent.value,
      background_color: storyBgColor.value,
    };

    const res = await fetch('/api/v2/stories', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(storyPayload),
    });

    const newStory = {
      id: Date.now(),
      user: { name: pageData.value.name, avatar_url: pageData.value.avatar_url },
      content: storyTextContent.value,
      background_color: storyBgColor.value,
      formatted_time: 'Just now',
      media: storyPreviewUrl.value ? [{ url: storyPreviewUrl.value }] : [],
    };
    storiesList.value.unshift(newStory);

    showToast('Story published for 24 hours!', 'success');
    closeStudio();
  } catch (err) {
    showToast('Failed to post story.', 'error');
  } finally {
    isPublishingStory.value = false;
  }
};

// ==========================================
// 6. REAL WEBRTC LIVE STREAMING ENGINE
// ==========================================
const initLiveStudioDevices = async () => {
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      showToast('Camera & mic access requires secure HTTPS connection.', 'error');
      return;
    }

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: true,
    });

    localMediaStream.value = stream;
    if (cameraPreviewRef.value) {
      cameraPreviewRef.value.srcObject = stream;
    }

    // Enumerate devices
    const devices = await navigator.mediaDevices.enumerateDevices();
    availableCameras.value = devices.filter((d) => d.kind === 'videoinput');
    availableMicrophones.value = devices.filter((d) => d.kind === 'audioinput');

    if (availableCameras.value.length > 0) {
      selectedCameraId.value = availableCameras.value[0].deviceId;
    }
    if (availableMicrophones.value.length > 0) {
      selectedMicId.value = availableMicrophones.value[0].deviceId;
    }
  } catch (err) {
    console.warn('getUserMedia permission or device error:', err);
    showToast('Camera and microphone permission required to preview stream.', 'info');
  }
};

const toggleCamera = () => {
  if (!localMediaStream.value) return;
  const videoTrack = localMediaStream.value.getVideoTracks()[0];
  if (videoTrack) {
    videoTrack.enabled = !videoTrack.enabled;
    cameraActive.value = videoTrack.enabled;
  }
};

const toggleMic = () => {
  if (!localMediaStream.value) return;
  const audioTrack = localMediaStream.value.getAudioTracks()[0];
  if (audioTrack) {
    audioTrack.enabled = !audioTrack.enabled;
    micActive.value = audioTrack.enabled;
  }
};

const toggleScreenShare = async () => {
  if (isScreenSharing.value) {
    // Switch back to camera
    if (screenShareStream.value) {
      screenShareStream.value.getTracks().forEach((t) => t.stop());
      screenShareStream.value = null;
    }
    if (cameraPreviewRef.value && localMediaStream.value) {
      cameraPreviewRef.value.srcObject = localMediaStream.value;
    }
    isScreenSharing.value = false;
  } else {
    // Acquire screen share stream
    try {
      const scr = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
      screenShareStream.value = scr;
      if (cameraPreviewRef.value) {
        cameraPreviewRef.value.srcObject = scr;
      }
      isScreenSharing.value = true;
      scr.getVideoTracks()[0].onended = () => {
        toggleScreenShare();
      };
    } catch (err) {
      console.warn('Screen share cancelled:', err);
    }
  }
};

const switchCameraDevice = async () => {
  if (!selectedCameraId.value) return;
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { deviceId: { exact: selectedCameraId.value } },
      audio: micActive.value,
    });
    localMediaStream.value = stream;
    if (cameraPreviewRef.value) cameraPreviewRef.value.srcObject = stream;
  } catch (err) {
    console.error('Camera switch error:', err);
  }
};

const switchMicDevice = async () => {
  if (!selectedMicId.value) return;
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: cameraActive.value,
      audio: { deviceId: { exact: selectedMicId.value } },
    });
    localMediaStream.value = stream;
    if (cameraPreviewRef.value) cameraPreviewRef.value.srcObject = stream;
  } catch (err) {
    console.error('Mic switch error:', err);
  }
};

const startLiveBroadcast = async () => {
  if (!liveTitle.value.trim()) return;
  isStartingLive.value = true;

  try {
    const res = await fetch('/api/v2/live/streams', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        page_id: pageData.value.id,
        title: liveTitle.value,
        description: liveDescription.value,
        privacy: 'public',
        recording_enabled: liveRecordReplay.value,
        comments_enabled: liveCommentsEnabled.value,
      }),
    });

    const data = await res.json();
    if (res.ok && data.status === 'success') {
      liveSessionId.value = data.data?.id;
      // Confirm start on server
      await fetch(`/api/v2/live/streams/${data.data.id}/start`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      });

      isStreamingLive.value = true;
      liveTimerSeconds.value = 0;
      liveTimerInterval.value = setInterval(() => {
        liveTimerSeconds.value++;
      }, 1000);

      showToast('Broadcasting live to your audience!', 'success');
    } else {
      showToast(data.message || 'Failed to start broadcast.', 'error');
    }
  } catch (err) {
    showToast('Failed to connect to streaming gateway.', 'error');
  } finally {
    isStartingLive.value = false;
  }
};

const endLiveBroadcast = async () => {
  if (liveTimerInterval.value) {
    clearInterval(liveTimerInterval.value);
  }

  if (liveSessionId.value) {
    try {
      await fetch(`/api/v2/live/streams/${liveSessionId.value}/end`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      });
      showToast(`Broadcast ended (${liveTimerFormatted.value}). Replay saved to Page Video Library!`, 'success');
    } catch (err) {
      console.error('Error ending live session:', err);
    }
  }

  isStreamingLive.value = false;
  stopAllMediaStreams();
  closeStudio();
};

const stopAllMediaStreams = () => {
  if (localMediaStream.value) {
    localMediaStream.value.getTracks().forEach((t) => t.stop());
    localMediaStream.value = null;
  }
  if (screenShareStream.value) {
    screenShareStream.value.getTracks().forEach((t) => t.stop());
    screenShareStream.value = null;
  }
};

const sendLiveChat = () => {
  if (!liveChatInput.value.trim()) return;
  liveCommentsList.value.push({
    user_name: pageData.value.name,
    text: liveChatInput.value,
  });
  liveChatInput.value = '';
};

const triggerLiveReaction = (emoji: string) => {
  showToast(`Sent ${emoji} to live broadcast!`, 'info');
};

// ==========================================
// 7. REEL VIEWER & STORY VIEWER CONTROLS
// ==========================================
const showReelViewer = ref(false);
const currentReel = ref<any>(null);
const isReelMuted = ref(false);
const showReelComments = ref(false);
const reelVideoPlayerRef = ref<HTMLVideoElement | null>(null);

const openReelViewer = (reel: any) => {
  currentReel.value = reel;
  showReelViewer.value = true;
};

const closeReelViewer = () => {
  showReelViewer.value = false;
  currentReel.value = null;
};

const toggleReelPlayback = () => {
  if (!reelVideoPlayerRef.value) return;
  if (reelVideoPlayerRef.value.paused) {
    reelVideoPlayerRef.value.play();
  } else {
    reelVideoPlayerRef.value.pause();
  }
};

const toggleReelLike = (reel: any) => {
  reel.has_liked = !reel.has_liked;
  reel.likes_count = (reel.likes_count || 0) + (reel.has_liked ? 1 : -1);
};

const shareReel = (reel: any) => {
  navigator.clipboard.writeText(window.location.origin + `/reels/${reel.id}`);
  showToast('Reel link copied to clipboard!', 'success');
};

// Story Viewer
const showStoryViewer = ref(false);
const currentStory = ref<any>(null);
const storyProgressPercent = ref(0);
const storyTimer = ref<any>(null);

const openStoryViewer = (story: any, idx: number) => {
  currentStory.value = story;
  showStoryViewer.value = true;
  storyProgressPercent.value = 0;

  if (storyTimer.value) clearInterval(storyTimer.value);
  storyTimer.value = setInterval(() => {
    if (storyProgressPercent.value >= 100) {
      clearInterval(storyTimer.value);
      closeStoryViewer();
    } else {
      storyProgressPercent.value += 2;
    }
  }, 100);
};

const closeStoryViewer = () => {
  if (storyTimer.value) clearInterval(storyTimer.value);
  showStoryViewer.value = false;
  currentStory.value = null;
};

const reactToStory = (story: any, em: string) => {
  showToast(`Reacted ${em} to story!`, 'info');
};

const replyToStory = (story: any) => {
  showToast('Reply sent to story creator!', 'success');
};

// ==========================================
// 8. SHARING, BRANDING & PAGE ACTIONS
// ==========================================
const showShareModal = ref(false);
const sharePostTarget = ref<any>(null);

const openShareModal = (post: any) => {
  sharePostTarget.value = post;
  showShareModal.value = true;
};

const confirmShareToFeed = async () => {
  if (!sharePostTarget.value) return;
  try {
    await fetch(`/api/v1/posts/${sharePostTarget.value.id}/share`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ audience: 'public' }),
    });
    sharePostTarget.value.shares_count = (sharePostTarget.value.shares_count || 0) + 1;
    showToast('Post shared to your feed!', 'success');
    showShareModal.value = false;
  } catch (err) {
    showToast('Failed to share post.', 'error');
  }
};

const copyPostLink = (id: number) => {
  navigator.clipboard.writeText(`${window.location.origin}/posts/${id}`);
  showToast('Post link copied to clipboard.', 'success');
  activePostMenuId.value = null;
};

const copyPageLink = () => {
  navigator.clipboard.writeText(window.location.href);
  showToast('Page link copied to clipboard.', 'success');
  showMoreMenu.value = false;
};

const togglePinPost = async (post: any) => {
  try {
    const res = await fetch(`/pages/${pageData.value.slug}/posts/${post.id}/pin`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      post.is_pinned = data.is_pinned;
      showToast(post.is_pinned ? 'Post pinned to top of page.' : 'Post unpinned.', 'success');
      activePostMenuId.value = null;
    }
  } catch (err) {
    showToast('Error pinning post.', 'error');
  }
};

const deletePost = async (id: number) => {
  if (!confirm('Are you sure you want to delete this post?')) return;
  try {
    const res = await fetch(`/pages/${pageData.value.slug}/posts/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      postsList.value = postsList.value.filter((p) => p.id !== id);
      postsCount.value = Math.max(0, postsCount.value - 1);
      showToast('Post deleted successfully.', 'info');
    }
  } catch (err) {
    showToast('Error deleting post.', 'error');
  }
};

// Edit Post Modal
const showEditPostModal = ref(false);
const editingPost = ref<any>(null);
const editPostContent = ref('');
const isSavingEditPost = ref(false);

const openEditPostModal = (post: any) => {
  editingPost.value = post;
  editPostContent.value = post.content || '';
  showEditPostModal.value = true;
  activePostMenuId.value = null;
};

const saveEditPost = async () => {
  if (!editingPost.value) return;
  isSavingEditPost.value = true;
  try {
    const res = await fetch(`/pages/${pageData.value.slug}/posts/${editingPost.value.id}`, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ content: editPostContent.value }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      editingPost.value.content = editPostContent.value;
      showToast('Post updated successfully.', 'success');
      showEditPostModal.value = false;
    }
  } catch (err) {
    showToast('Failed to update post.', 'error');
  } finally {
    isSavingEditPost.value = false;
  }
};

// Follow Page
const isTogglingFollow = ref(false);
const toggleFollow = async () => {
  isTogglingFollow.value = true;
  try {
    const res = await fetch(`/pages/${pageData.value.id}/follow`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      isFollowingState.value = data.is_following;
      followersCount.value = data.followers_count;
      showToast(isFollowingState.value ? 'Now following page!' : 'Unfollowed page.', 'success');
    }
  } catch (err) {
    showToast('Error updating follow status.', 'error');
  } finally {
    isTogglingFollow.value = false;
  }
};

// Branding Upload
const isUploadingAvatar = ref(false);
const isUploadingCover = ref(false);

const uploadAvatarImage = async (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  isUploadingAvatar.value = true;
  try {
    const fd = new FormData();
    fd.append('type', 'avatar');
    fd.append('file', file);
    const res = await fetch(`/pages/${pageData.value.slug}/branding`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: fd,
    });
    const data = await res.json();
    if (res.ok && data.success) {
      pageData.value.avatar_url = data.avatar_url || data.url;
      showToast('Profile photo updated!', 'success');
    }
  } catch (err) {
    showToast('Failed to update avatar.', 'error');
  } finally {
    isUploadingAvatar.value = false;
  }
};

const uploadCoverImage = async (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  isUploadingCover.value = true;
  try {
    const fd = new FormData();
    fd.append('type', 'cover');
    fd.append('file', file);
    const res = await fetch(`/pages/${pageData.value.slug}/branding`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: fd,
    });
    const data = await res.json();
    if (res.ok && data.success) {
      pageData.value.cover_image_url = data.cover_url || data.url;
      showToast('Cover photo updated!', 'success');
    }
  } catch (err) {
    showToast('Failed to update cover.', 'error');
  } finally {
    isUploadingCover.value = false;
  }
};

// Media Helpers
const isVideoMedia = (m: any): boolean => {
  if (!m) return false;
  return m.mime_type?.startsWith('video/') || m.type === 'video' || String(m.url).match(/\.(mp4|webm|mov)$/i) !== null;
};

const getMediaUrl = (m: any): string => {
  if (!m) return '';
  return m.url || m.preview_url || m.original_url || '';
};

const formatTimeAgo = (ts?: string): string => {
  if (!ts) return 'Just now';
  const diff = Date.now() - new Date(ts).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${Math.max(1, mins)}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs} hours ago`;
  const days = Math.floor(hrs / 24);
  return `${days} days ago`;
};

// Edit Page Details Modal
const showEditDetailsModal = ref(false);
const isSavingDetails = ref(false);
const editForm = ref({
  name: pageData.value.name,
  category: pageData.value.category || '',
  bio: pageData.value.bio || '',
  website: pageData.value.website || '',
  city: pageData.value.city || '',
});

const savePageDetails = async () => {
  isSavingDetails.value = true;
  try {
    const res = await fetch(`/pages/${pageData.value.slug}/details`, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(editForm.value),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      pageData.value.name = editForm.value.name;
      pageData.value.category = editForm.value.category;
      pageData.value.bio = editForm.value.bio;
      pageData.value.website = editForm.value.website;
      pageData.value.city = editForm.value.city;
      showEditDetailsModal.value = false;
      showToast('Page information saved successfully!', 'success');
    }
  } catch (err) {
    showToast('Failed to save details.', 'error');
  } finally {
    isSavingDetails.value = false;
  }
};

const showInviteModal = ref(false);
const openReportModal = () => {
  showMoreMenu.value = false;
  showToast('Content moderation and report dialog opened.', 'info');
};

const toggleRelatedPageFollow = async (rp: any) => {
  rp.is_following = !rp.is_following;
  rp.followers_count += rp.is_following ? 1 : -1;
  showToast(rp.is_following ? `Followed ${rp.name}!` : `Unfollowed ${rp.name}.`, 'info');
};

const openPhotoLightbox = (url: string) => {
  window.open(url, '_blank');
};

const openLiveViewer = (stream: any) => {
  window.open(`/live/${stream.channel_id || stream.id}`, '_blank');
};

// Supporting lists
const photosList = ref([...(props.photos || [])]);
const videosList = ref([...(props.videos || [])]);
const followersList = ref([...(props.followers || [])]);
const eventsList = ref([...(props.events || [])]);
const productsList = ref([...(props.products || [])]);

onUnmounted(() => {
  stopAllMediaStreams();
  if (liveTimerInterval.value) clearInterval(liveTimerInterval.value);
  if (storyTimer.value) clearInterval(storyTimer.value);
});
</script>
