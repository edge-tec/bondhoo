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
    <div class="space-y-8 max-w-7xl mx-auto pb-12">
      <!-- Flash Message / Alert Notification -->
      <div
        v-if="actionMessage"
        class="p-4 rounded-2xl flex items-center justify-between text-sm shadow-sm transition-all"
        :class="actionMessage.type === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900 dark:text-rose-300' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300'"
      >
        <div class="flex items-center gap-3">
          <svg v-if="actionMessage.type !== 'error'" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span class="font-semibold">{{ actionMessage.text }}</span>
        </div>
        <button @click="actionMessage = null" class="opacity-60 hover:opacity-100 transition-opacity">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TOP HUB HEADER (All Pages / Enterprise Social Hub)                      -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80 dark:border-slate-800/80">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">All Pages</h1>
            <span v-if="pagesMeta?.total" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-[#1877F2] dark:text-blue-400">
              {{ pagesMeta.total }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            Discover verified organizations, technology hubs, creators, and brands.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <a
            href="/pages/create"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 active:scale-95 transition-all shrink-0"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Create Page</span>
          </a>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- MAIN NAVIGATION TABS (Discover, Your Pages, Liked Pages, Studio)       -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto no-scrollbar py-1">
        <button
          @click="setMainTab('discover')"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer',
            mainTab === 'discover'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
          <span>All Pages (ওভারভিউ ও সব পেইজ)</span>
          <span v-if="pagesMeta?.total" class="px-1.5 py-0.5 text-[10px] rounded-full" :class="mainTab === 'discover' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
            {{ pagesMeta.total }}
          </span>
        </button>

        <button
          @click="setMainTab('my_pages')"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer',
            mainTab === 'my_pages'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
          </svg>
          <span>Your Pages (আমার পেইজসমূহ)</span>
          <span v-if="myPages.length" class="px-1.5 py-0.5 text-[10px] rounded-full" :class="mainTab === 'my_pages' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
            {{ myPages.length }}
          </span>
        </button>

        <button
          @click="setMainTab('liked')"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer',
            mainTab === 'liked'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
          </svg>
          <span>Liked Pages (লাইক করা পেইজ)</span>
          <span v-if="likedPages.length" class="px-1.5 py-0.5 text-[10px] rounded-full" :class="mainTab === 'liked' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
            {{ likedPages.length }}
          </span>
        </button>

        <button
          @click="setMainTab('invitations')"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer',
            mainTab === 'invitations'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Invitations (আমন্ত্রণসমূহ)</span>
          <span v-if="pendingInvitations && pendingInvitations.length" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white">
            {{ pendingInvitations.length }}
          </span>
        </button>

        <button
          v-if="activePage"
          @click="setMainTab('studio')"
          :class="[
            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap cursor-pointer sm:ml-auto',
            mainTab === 'studio'
              ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm'
              : 'bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50'
          ]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <span>Page Studio &bull; {{ activePage.name }}</span>
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TAB 1: DISCOVER / ALL PAGES SECTION (Directly matching screenshot)      -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-if="mainTab === 'discover'" class="space-y-6 animate-fade-in">
        <!-- Search, Quick Filter Pills, and Sort Row -->
        <div class="bg-[#0b1120] border border-slate-800 rounded-2xl p-3 sm:p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 shadow-sm">
          <!-- Search Input -->
          <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Search pages..."
              class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700/80 text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all"
            />
          </div>

          <!-- Quick Filters Pills & Sort Select -->
          <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap justify-between sm:justify-end">
            <div class="flex items-center gap-1.5 bg-slate-900/90 p-1 rounded-xl border border-slate-700/80">
              <button
                @click="switchDirectoryTab('all')"
                :class="[
                  'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                  activeDirectoryTab === 'all'
                    ? 'bg-[#1877F2] text-white shadow-xs'
                    : 'text-slate-400 hover:text-white'
                ]"
              >
                All
              </button>
              <button
                @click="switchDirectoryTab('my_pages')"
                :class="[
                  'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                  activeDirectoryTab === 'my_pages'
                    ? 'bg-[#1877F2] text-white shadow-xs'
                    : 'text-slate-400 hover:text-white'
                ]"
              >
                Your Pages
              </button>
              <button
                @click="switchDirectoryTab('followed')"
                :class="[
                  'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                  activeDirectoryTab === 'followed'
                    ? 'bg-[#1877F2] text-white shadow-xs'
                    : 'text-slate-400 hover:text-white'
                ]"
              >
                Liked Pages
              </button>
            </div>

            <!-- Sort Select -->
            <div class="relative">
              <select
                v-model="selectedSort"
                @change="handleFilterChange"
                class="px-3 py-2 pr-8 rounded-xl bg-slate-900/90 border border-slate-700/80 text-xs font-semibold text-slate-300 focus:outline-none focus:ring-2 focus:ring-[#1877F2] cursor-pointer appearance-none"
              >
                <option value="popular">Most Followed &#8693;</option>
                <option value="recent">Newest Pages</option>
                <option value="name">Alphabetical</option>
              </select>
              <span class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs">&#9662;</span>
            </div>
          </div>
        </div>

        <!-- Categories Carousel / Horizontal Scroll Bar -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
          <button
            @click="selectCategory('all')"
            :class="[
              'px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap cursor-pointer shrink-0',
              selectedCategoryFilter === 'all'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border border-slate-800'
            ]"
          >
            All Categories
          </button>
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectCategory(cat)"
            :class="[
              'px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap cursor-pointer shrink-0',
              selectedCategoryFilter === cat
                ? 'bg-white text-slate-900 shadow-sm'
                : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 border border-slate-800'
            ]"
          >
            {{ cat }}
          </button>
        </div>

        <!-- Directory Page Cards Grid (Pixel-matching screenshot) -->
        <div v-if="pagesList.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="page in pagesList"
            :key="page.id"
            class="bg-[#0b1120] border border-slate-800/90 rounded-2xl overflow-hidden hover:border-slate-700 transition-all flex flex-col group shadow-lg"
          >
            <!-- Scenic Cover Banner -->
            <div class="relative h-40 w-full overflow-hidden bg-slate-800">
              <img
                :src="getPageCover(page)"
                :alt="page.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-[#0b1120]/80 via-transparent to-black/30"></div>

              <!-- Top-right Heart Bookmark button -->
              <button
                @click.stop="toggleFavorite(page)"
                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-900/60 backdrop-blur-md hover:bg-slate-900/90 text-white/80 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer z-10"
                title="Save Page"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                </svg>
              </button>
            </div>

            <!-- Card Content (relative z-10) -->
            <div class="px-5 pb-5 pt-0 flex-1 flex flex-col justify-between relative z-10">
              <div>
                <!-- Overlapping Avatar - Floating above cover with relative z-20 -->
                <div
                  class="relative z-20 -mt-7 mb-3 w-14 h-14 rounded-2xl ring-4 ring-[#0b1120] bg-gradient-to-br text-white font-black flex items-center justify-center shadow-xl overflow-hidden text-xl shrink-0"
                  :class="getAvatarGradient(page)"
                >
                  <img v-if="page.avatar_url" :src="page.avatar_url" :alt="page.name" class="w-full h-full object-cover" />
                  <span v-else>{{ page.name.charAt(0) }}</span>
                </div>

                <!-- Page Name with Verified Checkmark -->
                <div class="flex items-center gap-1.5 flex-wrap">
                  <a
                    :href="`/pages/${page.slug}`"
                    class="font-extrabold text-base text-white hover:text-[#1877F2] transition-colors line-clamp-1"
                  >
                    {{ page.name }}
                  </a>
                  <span v-if="page.is_verified" class="text-[#1877F2] shrink-0" title="Verified Organization">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                  </span>
                </div>

                <!-- Category and Followers -->
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ page.category || 'General' }} &bull; {{ formatNumber(page.followers_count) }} followers
                </p>

                <!-- Description / Bio -->
                <p class="text-xs text-slate-300 line-clamp-2 mt-2 leading-relaxed min-h-[32px]">
                  {{ page.description || page.bio || 'Official social page on SocialHub network. Discover updates, news, and official announcements.' }}
                </p>
              </div>

              <!-- Actions Row (Matching screenshot thumbs up Like button + external link icon) -->
              <div class="flex items-center gap-2.5 mt-4 pt-3 border-t border-slate-800">
                <button
                  @click.stop="toggleFollowPage(page)"
                  :disabled="followLoadingId === page.id"
                  :class="[
                    'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-50 active:scale-95',
                    page.is_following
                      ? 'bg-slate-800 text-white hover:bg-slate-700 border border-slate-700'
                      : 'bg-[#1877F2] hover:bg-[#1666D8] text-white'
                  ]"
                >
                  <svg v-if="page.is_following" class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z" />
                  </svg>
                  <span>{{ page.is_following ? 'Liked Page' : 'Like Page' }}</span>
                </button>

                <!-- Visit Profile Icon Link -->
                <a
                  :href="`/pages/${page.slug}`"
                  class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition-all flex items-center justify-center border border-slate-700/60 shrink-0"
                  title="Visit Page Profile"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>

                <!-- Studio shortcut if user has manage rights -->
                <button
                  v-if="page.is_owner || page.user_role"
                  @click.stop="openStudio(page)"
                  class="w-9 h-9 rounded-xl bg-blue-900/40 hover:bg-blue-900/60 text-blue-400 hover:text-blue-300 transition-all flex items-center justify-center border border-blue-800/60 shrink-0 cursor-pointer"
                  title="Manage Page"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-[#0b1120] border border-slate-800 rounded-3xl p-12 text-center">
          <div class="w-16 h-16 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-white">No pages found</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            No pages match your current search or category filter. Try refining your keywords.
          </p>
          <button
            @click="clearFilters"
            class="px-4 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold transition-all"
          >
            Clear Filters
          </button>
        </div>

        <!-- Pagination Controls -->
        <div
          v-if="pagesMeta && pagesMeta.last_page > 1"
          class="flex items-center justify-between border-t border-slate-800 pt-4"
        >
          <p class="text-xs text-slate-400">
            Showing <span class="font-bold text-white">{{ (pagesMeta.current_page - 1) * pagesMeta.per_page + 1 }}</span> to
            <span class="font-bold text-white">{{ Math.min(pagesMeta.current_page * pagesMeta.per_page, pagesMeta.total) }}</span> of
            <span class="font-bold text-white">{{ pagesMeta.total }}</span> pages
          </p>
          <div class="flex items-center gap-1.5">
            <button
              :disabled="pagesMeta.current_page <= 1"
              @click="goToPage(pagesMeta.current_page - 1)"
              class="px-3 py-1.5 rounded-xl border border-slate-800 bg-[#0b1120] text-xs font-bold text-slate-300 hover:bg-slate-800 disabled:opacity-40 transition-all"
            >
              Previous
            </button>
            <button
              v-for="p in pagesMeta.last_page"
              :key="p"
              @click="goToPage(p)"
              :class="[
                'w-8 h-8 rounded-xl text-xs font-bold transition-all',
                p === pagesMeta.current_page
                  ? 'bg-[#1877F2] text-white'
                  : 'border border-slate-800 bg-[#0b1120] text-slate-400 hover:bg-slate-800'
              ]"
            >
              {{ p }}
            </button>
            <button
              :disabled="pagesMeta.current_page >= pagesMeta.last_page"
              @click="goToPage(pagesMeta.current_page + 1)"
              class="px-3 py-1.5 rounded-xl border border-slate-800 bg-[#0b1120] text-xs font-bold text-slate-300 hover:bg-slate-800 disabled:opacity-40 transition-all"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TAB 2: YOUR PAGES SECTION (আমার পেইজসমূহ)                             -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else-if="mainTab === 'my_pages'" class="space-y-6 animate-fade-in">
        <!-- Summary Stats Strip -->
        <div v-if="summaryStats && myPages.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400">Total Managed Pages</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ summaryStats.total_managed_pages || myPages.length }}</div>
          </div>
          <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400">Total Followers</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ formatNumber(summaryStats.total_followers) }}</div>
            <span class="text-[11px] font-bold text-emerald-500">{{ summaryStats.followers_growth }} this month</span>
          </div>
          <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400">Total Posts</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ summaryStats.total_posts }}</div>
            <span class="text-[11px] font-bold text-blue-500">{{ summaryStats.posts_growth }} this month</span>
          </div>
          <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400">Total Engagement</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ formatNumber(summaryStats.total_engagement) }}</div>
            <span class="text-[11px] font-bold text-emerald-500">{{ summaryStats.engagement_growth }} this month</span>
          </div>
        </div>

        <!-- Managed Pages Cards -->
        <div v-if="myPages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="p in myPages"
            :key="p.id"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
          >
            <div>
              <div class="h-28 w-full bg-slate-200 dark:bg-slate-800 relative">
                <img :src="getPageCover(p)" :alt="p.name" class="w-full h-full object-cover" />
                <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-900/70 backdrop-blur-xs text-white z-10">
                  {{ p.is_owner ? 'Owner' : (p.user_role || 'Admin') }}
                </span>
              </div>
              <div class="px-5 pt-0 pb-4 relative z-10">
                <div
                  class="relative z-20 -mt-8 mb-2 w-14 h-14 rounded-2xl ring-4 ring-white dark:ring-slate-900 bg-gradient-to-br text-white font-black flex items-center justify-center shadow-xl overflow-hidden text-xl shrink-0"
                  :class="getAvatarGradient(p)"
                >
                  <img v-if="p.avatar_url" :src="p.avatar_url" :alt="p.name" class="w-full h-full object-cover" />
                  <span v-else>{{ p.name.charAt(0) }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <h3 class="font-extrabold text-base text-slate-900 dark:text-white">{{ p.name }}</h3>
                  <span v-if="p.is_verified" class="text-[#1877F2]" title="Verified">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" /></svg>
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">{{ p.category || 'General' }} &bull; {{ formatNumber(p.followers_count) }} followers</p>
              </div>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
              <button
                @click="openStudio(p)"
                class="flex-1 py-2 px-3 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold transition-all text-center"
              >
                Open Studio
              </button>
              <a
                :href="`/pages/${p.slug}`"
                class="py-2 px-3 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-700 transition-all"
              >
                View Profile
              </a>
            </div>
          </div>
        </div>

        <!-- No Managed Pages CTA -->
        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-12 shadow-sm text-center">
          <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-[#1877F2] flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>
          </div>
          <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Build your Brand with SocialHub Pages</h2>
          <p class="text-sm text-slate-500 max-w-lg mx-auto mt-2 mb-6">
            Connect with millions of people, build an active community, and grow your enterprise with a dedicated official page.
          </p>
          <a
            href="/pages/create"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Create Your First Page</span>
          </a>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TAB 3: LIKED PAGES SECTION (লাইক করা পেইজ)                            -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else-if="mainTab === 'liked'" class="space-y-6 animate-fade-in">
        <div v-if="likedPages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="p in likedPages"
            :key="p.id"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-sm hover:shadow-md transition-all flex items-center justify-between gap-4"
          >
            <div class="flex items-center gap-3.5 min-w-0">
              <div class="w-12 h-12 rounded-2xl bg-[#1877F2] text-white font-extrabold flex items-center justify-center shadow-xs overflow-hidden shrink-0 text-lg">
                <img v-if="p.avatar_url" :src="p.avatar_url" :alt="p.name" class="w-full h-full object-cover" />
                <span v-else>{{ p.name.charAt(0) }}</span>
              </div>
              <div class="truncate">
                <div class="flex items-center gap-1.5">
                  <a :href="`/pages/${p.slug}`" class="font-bold text-sm text-slate-900 dark:text-white hover:text-[#1877F2] truncate">
                    {{ p.name }}
                  </a>
                  <span v-if="p.is_verified" class="text-[#1877F2]" title="Verified">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" /></svg>
                  </span>
                </div>
                <p class="text-xs text-slate-500">{{ p.category || 'General' }} &bull; {{ formatNumber(p.followers_count) }} followers</p>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <a
                :href="`/pages/${p.slug}`"
                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all"
              >
                Visit
              </a>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center shadow-sm">
          <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">You haven't liked any pages yet</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
            Discover verified companies, tech hubs, and creators to stay up to date with their latest announcements.
          </p>
          <button
            @click="setMainTab('discover')"
            class="px-5 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-sm transition-all cursor-pointer"
          >
            Explore All Pages
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TAB 4: INVITATIONS SECTION (আমন্ত্রণসমূহ)                               -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else-if="mainTab === 'invitations'" class="space-y-6 animate-fade-in">
        <div v-if="pendingInvitations && pendingInvitations.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="inv in pendingInvitations"
            :key="inv.id"
            class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-4 shadow-sm"
          >
            <div class="flex items-center gap-3.5 min-w-0">
              <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-[#1877F2] font-bold flex items-center justify-center shrink-0 overflow-hidden text-lg">
                <img v-if="inv.page?.avatar_url" :src="inv.page.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ inv.page?.name?.charAt(0) || 'P' }}</span>
              </div>
              <div class="truncate">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ inv.page?.name }}</h4>
                <p class="text-xs text-slate-500 capitalize">Role: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ inv.role }}</span> &bull; Invited by {{ inv.inviter?.name || 'Admin' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="acceptInvitation(inv)"
                :disabled="invActionLoading === inv.id"
                class="px-4 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold transition-all disabled:opacity-50"
              >
                Accept
              </button>
              <button
                @click="declineInvitation(inv)"
                :disabled="invActionLoading === inv.id"
                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all disabled:opacity-50"
              >
                Decline
              </button>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center shadow-sm">
          <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">No pending invitations</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
            When someone invites you to collaborate on or manage a page, their invitation will appear here.
          </p>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <!-- TAB 5: ENTERPRISE PAGE MANAGEMENT STUDIO (Hero for active page)         -->
      <!-- ═══════════════════════════════════════════════════════════════════════ -->
      <div v-else-if="mainTab === 'studio' && activePage" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden animate-fade-in">
        <!-- Studio Notice Banner & Quick Switcher -->
        <div class="px-6 py-3 bg-slate-100 dark:bg-slate-800/90 border-b border-slate-200 dark:border-slate-700/80 flex items-center justify-between flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-[#1877F2] dark:bg-blue-900/40 dark:text-blue-300">
              Studio Mode
            </span>
            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
              Managing <strong>{{ activePage.name }}</strong>
            </span>
          </div>
          <button
            @click="setMainTab('discover')"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1877F2] hover:text-[#1666D8] dark:text-blue-400 dark:hover:text-blue-300 transition-colors cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to All Pages</span>
          </button>
        </div>
        <!-- Cover Banner Photo with Scenic Mountain Gradient / Image -->
        <div class="relative h-48 sm:h-64 lg:h-72 w-full overflow-hidden bg-slate-200 dark:bg-slate-800">
          <img
            :src="activePage.cover_image_url || defaultCoverUrl"
            :alt="activePage.name"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
        </div>

        <!-- Identity and Header Information Row -->
        <div class="px-6 sm:px-8 pb-6 pt-4 relative">
          <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 -mt-16 sm:-mt-20 mb-6">
            <!-- Avatar & Titles -->
            <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 text-center sm:text-left">
              <div class="relative w-28 h-28 sm:w-36 sm:h-36 rounded-full border-4 border-white dark:border-slate-900 bg-white dark:bg-slate-800 shadow-md overflow-hidden shrink-0 flex items-center justify-center">
                <img
                  v-if="activePage.avatar_url"
                  :src="activePage.avatar_url"
                  :alt="activePage.name"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full bg-[#1877F2] text-white flex items-center justify-center">
                  <svg class="w-16 h-16 sm:w-20 sm:h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
              </div>

              <div class="space-y-1 sm:mb-2">
                <div class="flex items-center justify-center sm:justify-start gap-2">
                  <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ activePage.name }}
                  </h1>
                  <span v-if="activePage.is_verified" class="text-[#1877F2]" title="Verified Page">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                  </span>
                </div>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                  {{ activePage.category }} &bull; {{ formatNumber(activePage.followers_count) }} followers
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 max-w-2xl line-clamp-2">
                  {{ activePage.bio || activePage.description || 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!' }}
                </p>
              </div>
            </div>

            <!-- Page Actions -->
            <div class="flex items-center justify-center sm:justify-end gap-2.5 shrink-0 sm:mb-2">
              <button
                @click="toggleFollowPage"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-sm shadow-blue-500/20 transition-all cursor-pointer"
              >
                <svg v-if="isFollowingPage" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ isFollowingPage ? 'Following' : 'Follow' }}</span>
              </button>

              <button
                v-if="activePage.permissions?.can_edit"
                @click="openEditModal"
                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold transition-all cursor-pointer border border-slate-200 dark:border-slate-700"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit Page</span>
              </button>

              <div class="relative" ref="moreMenuRef">
                <button
                  @click="moreMenuOpen = !moreMenuOpen"
                  class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-200 shadow-xs transition-all cursor-pointer"
                  aria-label="More Options"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
                </button>

                <!-- Dropdown Menu -->
                <div
                  v-if="moreMenuOpen"
                  class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 shadow-xl py-2 z-30 animate-fade-in"
                >
                  <a :href="`/pages/${activePage.slug}`" class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>View Public Page</span>
                  </a>
                  <button @click="openSettingsModal(); moreMenuOpen = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Page Settings</span>
                  </button>
                  <button @click="openRolesModal(); moreMenuOpen = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Roles & Permissions</span>
                  </button>
                  <button @click="openFollowersModal(); moreMenuOpen = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Manage Followers</span>
                  </button>
                  <button @click="openPostModal(); moreMenuOpen = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Create Post</span>
                  </button>
                  <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                  <button
                    v-if="activePage.is_owner"
                    @click="openDeleteModal(); moreMenuOpen = false"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                  >
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete Page</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sub-Navigation Tabs Row -->
          <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 overflow-x-auto no-scrollbar -mx-6 px-6">
            <button
              v-for="tab in ['Home', 'Posts', 'About', 'Photos', 'Videos', 'Events', 'More']"
              :key="tab"
              @click="activeSubNav = tab.toLowerCase()"
              class="px-4 py-3 text-xs sm:text-sm font-bold transition-all relative shrink-0 cursor-pointer flex items-center gap-1"
              :class="activeSubNav === tab.toLowerCase() ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            >
              <span>{{ tab }}</span>
              <svg v-if="tab === 'More'" class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
              <span
                v-if="activeSubNav === tab.toLowerCase()"
                class="absolute bottom-0 left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"
              ></span>
            </button>
          </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- SUB-TAB CONTENT: HOME & POSTS (APPROVED 2-COLUMN DESIGN)             -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <div v-if="activeSubNav === 'home' || activeSubNav === 'posts' || activeSubNav === 'overview'" class="p-4 sm:p-6 lg:p-8 bg-[#F8FAFC] dark:bg-slate-950/50">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- CENTER COLUMN: STORIES, COMPOSER, 4-TILE STUDIO, POSTS FEED (8 COLS) -->
            <div class="lg:col-span-8 space-y-5">
              
              <!-- 1. STORIES CAROUSEL -->
              <div class="flex items-center gap-3 overflow-x-auto no-scrollbar py-1">
                <!-- Create Story Card -->
                <div
                  @click="openPostModal"
                  class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md cursor-pointer flex flex-col group transition-all"
                >
                  <div class="h-32 sm:h-36 w-full bg-blue-50 dark:bg-slate-800 overflow-hidden relative flex items-center justify-center">
                    <img
                      v-if="activePage.avatar_url"
                      :src="activePage.avatar_url"
                      alt="Avatar"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                    <div v-else class="w-14 h-14 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-xl font-bold">
                      {{ activePage.name?.charAt(0) || 'S' }}
                    </div>
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

                <!-- Active Stories List -->
                <div
                  v-for="(story, sIdx) in displayStories"
                  :key="story.id || sIdx"
                  class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden shadow-xs hover:shadow-md hover:-translate-y-0.5 cursor-pointer group transition-all border border-slate-200/80 dark:border-slate-800"
                >
                  <img
                    :src="story.cover_url"
                    :alt="story.user?.name"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/80"></div>

                  <!-- Top Avatar with Blue Ring -->
                  <div class="absolute top-2.5 left-2.5">
                    <img
                      :src="story.user?.avatar_url"
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

                <!-- Create Reel Card (Gradient) -->
                <div
                  @click="openPostModal"
                  class="relative shrink-0 w-28 sm:w-32 h-48 sm:h-52 rounded-2xl overflow-hidden bg-gradient-to-b from-fuchsia-600 via-pink-600 to-indigo-700 shadow-xs hover:shadow-md hover:-translate-y-0.5 cursor-pointer flex flex-col items-center justify-center p-3 text-white text-center group transition-all"
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

              <!-- 2. POST COMPOSER INPUT CARD -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4">
                <!-- Top User Input Row -->
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                  <div class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center font-bold overflow-hidden shrink-0">
                    <img v-if="activePage.avatar_url" :src="activePage.avatar_url" class="w-full h-full object-cover" />
                    <span v-else>{{ activePage.name?.charAt(0) || 'S' }}</span>
                  </div>
                  <div
                    @click="openPostModal"
                    class="flex-1 bg-[#F5F7FB] hover:bg-slate-200/60 dark:bg-slate-800 dark:hover:bg-slate-700/60 rounded-full px-4 py-2.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400 cursor-pointer transition-colors"
                  >
                    What's on your mind, {{ activePage.name }}?
                  </div>
                </div>

                <!-- Bottom Action Pills Row -->
                <div class="flex items-center justify-between pt-2.5 px-1 sm:px-2 flex-wrap gap-1">
                  <button
                    type="button"
                    @click="openPostModalWithPhoto"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                  >
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zm0 2v10h16V7H4zm3 8l3-4 2 2.5 3-4 4 5.5H7zm9-5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                    </svg>
                    <span>Photo/Video</span>
                  </button>

                  <button
                    type="button"
                    @click="openPostModal"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                  >
                    <svg class="w-5 h-5 text-fuchsia-600" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <span>Reel</span>
                  </button>

                  <button
                    type="button"
                    @click="openPostModal"
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
                    @click="openPostModal"
                    class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                  >
                    <span class="text-base leading-none">😊</span>
                    <span>Feeling/Activity</span>
                  </button>

                  <button
                    type="button"
                    class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- 3. 4-TILE PUBLISHING STUDIO QUICK HUB -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Tile 1: Create Post -->
                <div
                  @click="openPostModal"
                  class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs hover:shadow-md hover:border-blue-400 cursor-pointer transition-all flex flex-col justify-between group"
                >
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </div>
                  <div>
                    <div class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white flex items-center justify-between">
                      <span>Create Post</span>
                      <span class="text-slate-400 group-hover:translate-x-0.5 transition-transform">→</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
                      Share updates with your followers
                    </p>
                  </div>
                </div>

                <!-- Tile 2: Reel -->
                <div
                  @click="openPostModal"
                  class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs hover:shadow-md hover:border-fuchsia-400 cursor-pointer transition-all flex flex-col justify-between group"
                >
                  <div class="w-10 h-10 rounded-xl bg-fuchsia-50 dark:bg-fuchsia-950/60 text-fuchsia-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
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
                  @click="openPostModal"
                  class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs hover:shadow-md hover:border-rose-400 cursor-pointer transition-all flex flex-col justify-between group"
                >
                  <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
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
                  @click="openPostModal"
                  class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs hover:shadow-md hover:border-cyan-400 cursor-pointer transition-all flex flex-col justify-between group"
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

              <!-- 4. FEATURED POST 1 (SocialHub Official Announcement) -->
              <article class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5 space-y-3.5">
                <!-- Post Author Header -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center font-bold overflow-hidden shrink-0">
                      <img v-if="activePage.avatar_url" :src="activePage.avatar_url" class="w-full h-full object-cover" />
                      <span v-else>{{ activePage.name?.charAt(0) || 'S' }}</span>
                    </div>
                    <div>
                      <div class="flex items-center gap-1.5">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ activePage.name }}</h4>
                        <span class="w-4 h-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center shrink-0">
                          <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                          </svg>
                        </span>
                      </div>
                      <p class="text-xs text-slate-400 mt-0.5">3 hours ago &bull; Public</p>
                    </div>
                  </div>
                  <button class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                  </button>
                </div>

                <!-- Post Text -->
                <p class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line">
                  Great news! 🎉 We're excited to announce the launch of our new feature - Page Insights! Now you can track your reach, engagement and growth in real time. Keep creating, keep building, keep growing! 🚀

                  <span class="text-[#1877F2] font-semibold">#SocialHub #PageInsights #NewFeature #Community</span>
                </p>

                <!-- Media Banner Mockup (Page Insights Now Live!) -->
                <div class="rounded-2xl overflow-hidden bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-700 p-6 sm:p-10 text-white flex flex-col items-center justify-center text-center relative group shadow-inner">
                  <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg cursor-pointer">
                    <svg class="w-8 h-8 text-white fill-current ml-1" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  </div>
                  <h3 class="text-xl sm:text-2xl font-black tracking-tight drop-shadow-md">Page Insights Now Live!</h3>
                  <p class="text-xs sm:text-sm text-blue-100 mt-1 font-semibold">Track &bull; Analyze &bull; Grow</p>
                </div>

                <!-- Reactions Stats Bar -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
                  <div class="flex items-center gap-1.5">
                    <span class="flex -space-x-1">
                      <span class="w-5 h-5 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[10px]">👍</span>
                      <span class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px]">❤️</span>
                    </span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 ml-1">856</span>
                  </div>
                  <div class="flex items-center gap-3 font-medium">
                    <span>124 comments</span>
                    <span>&bull;</span>
                    <span>36 shares</span>
                  </div>
                </div>

                <!-- Interactive Buttons -->
                <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800 text-xs font-bold">
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-[#1877F2] bg-blue-50/60 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                    </svg>
                    <span>Like</span>
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>Comment</span>
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span>Share</span>
                  </button>
                </div>
              </article>

              <!-- 5. POST 2 (Arafat Islam / Sunset Community Post) -->
              <article class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <img
                      src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=100&q=80"
                      class="w-10 h-10 rounded-full object-cover shrink-0"
                    />
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Arafat Islam</h4>
                      <p class="text-xs text-slate-400 mt-0.5">5 hours ago &bull; Public</p>
                    </div>
                  </div>
                  <button class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                  </button>
                </div>

                <p class="text-sm text-slate-800 dark:text-slate-200">
                  Beautiful sunset at Cox's Bazar! ❤️
                </p>

                <!-- Media Split Grid (Video on left, Photo +3 on right) -->
                <div class="grid grid-cols-2 gap-1.5 rounded-2xl overflow-hidden max-h-72">
                  <div class="relative overflow-hidden bg-slate-900 h-64 cursor-pointer group">
                    <img
                      src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                    />
                    <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                      <div class="w-12 h-12 rounded-full bg-white/30 backdrop-blur-md flex items-center justify-center">
                        <svg class="w-6 h-6 text-white fill-current ml-0.5" viewBox="0 0 24 24">
                          <path d="M8 5v14l11-7z" />
                        </svg>
                      </div>
                    </div>
                    <span class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded-md bg-black/70 text-white text-[11px] font-bold">
                      0:45
                    </span>
                  </div>

                  <div class="relative overflow-hidden bg-slate-900 h-64 cursor-pointer group">
                    <img
                      src="https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=800&q=80"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                    />
                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center text-white text-2xl font-black">
                      +3
                    </div>
                  </div>
                </div>

                <!-- Reactions -->
                <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
                  <div class="flex items-center gap-1.5">
                    <span class="flex -space-x-1">
                      <span class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px]">❤️</span>
                      <span class="w-5 h-5 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[10px]">👍</span>
                    </span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 ml-1">420</span>
                  </div>
                  <div class="flex items-center gap-3 font-medium">
                    <span>48 comments</span>
                    <span>&bull;</span>
                    <span>12 shares</span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800 text-xs font-bold">
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <span>👍 Like</span>
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <span>💬 Comment</span>
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                    <span>↗️ Share</span>
                  </button>
                </div>
              </article>

              <!-- Additional Dynamic User Posts (if any) -->
              <div v-if="activePage.recent_posts && activePage.recent_posts.length > 0" class="space-y-4 pt-2">
                <article
                  v-for="post in activePage.recent_posts"
                  :key="post.id"
                  class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5 space-y-3.5"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center font-bold overflow-hidden">
                        <img v-if="activePage.avatar_url" :src="activePage.avatar_url" class="w-full h-full object-cover" />
                        <span v-else>{{ activePage.name?.charAt(0) }}</span>
                      </div>
                      <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ activePage.name }}</h4>
                        <p class="text-xs text-slate-400">{{ post.created_at || 'Recently' }} &bull; Public</p>
                      </div>
                    </div>
                  </div>
                  <p class="text-sm text-slate-800 dark:text-slate-200 whitespace-pre-line">{{ post.content }}</p>
                  <div v-if="post.media && post.media.length > 0" class="rounded-xl overflow-hidden max-h-96 bg-slate-100 dark:bg-slate-800">
                    <img :src="post.media[0].url || post.media[0].original_path" class="w-full h-full object-cover" />
                  </div>
                  <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ post.likes_count || 12 }} likes</span>
                    <span>{{ post.comments_count || 3 }} comments &bull; {{ post.shares_count || 1 }} shares</span>
                  </div>
                </article>
              </div>

            </div>

            <!-- RIGHT COLUMN: WIDGETS (4 COLS) -->
            <div class="lg:col-span-4 space-y-5">
              
              <!-- WIDGET 1: PAGE INFORMATION -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
                  Page Information
                </h3>

                <div class="flex items-center gap-3 mb-3">
                  <div class="w-12 h-12 rounded-xl bg-[#1877F2] text-white flex items-center justify-center font-black text-xl shrink-0 shadow-xs overflow-hidden">
                    <img v-if="activePage.avatar_url" :src="activePage.avatar_url" :alt="activePage.name" class="w-full h-full object-cover" />
                    <svg v-else class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                  </div>
                  <div>
                    <div class="flex items-center gap-1.5">
                      <span class="font-extrabold text-sm text-slate-900 dark:text-white">{{ activePage.name }}</span>
                      <span v-if="activePage.is_verified" class="text-[#1877F2]">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                      </span>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">{{ activePage.category || 'Technology' }}</span>
                  </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                  {{ activePage.bio || activePage.description || 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!' }}
                </p>

                <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 pb-4 border-b border-slate-100 dark:border-slate-800">
                  <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>{{ formatNumber(activePage.followers_count || 125486) }} followers</span>
                  </div>

                  <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ activePage.city || activePage.address || 'Dhaka, Bangladesh' }}</span>
                  </div>

                  <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Open &bull; 9:00 AM - 6:00 PM</span>
                  </div>
                </div>

                <a
                  :href="`/messages?page_id=${activePage.id}`"
                  class="mt-3.5 w-full py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs flex items-center justify-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-xs"
                >
                  <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                  <span>Message Page</span>
                </a>
              </div>

              <!-- WIDGET 2: QUICK ACTIONS -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
                  Quick Actions
                </h3>

                <div class="grid grid-cols-4 gap-2 text-center">
                  <button
                    type="button"
                    @click="openPostModal"
                    class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                  >
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Create Post</span>
                  </button>

                  <button
                    type="button"
                    @click="openPostModalWithPhoto"
                    class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                  >
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Photo</span>
                  </button>

                  <button
                    type="button"
                    @click="openEventModal"
                    class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                  >
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Event</span>
                  </button>

                  <button
                    type="button"
                    @click="openInviteModal"
                    class="flex flex-col items-center gap-1.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                  >
                    <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 flex items-center justify-center">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                      </svg>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Invite Friends</span>
                  </button>
                </div>
              </div>

              <!-- WIDGET 3: PAGE INSIGHTS (7 DAYS SPARKLINE SVG CHART) -->
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                    Page Insights
                  </h3>
                  <select
                    v-model="insightsRange"
                    class="text-[11px] font-bold bg-[#F5F7FB] dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg px-2 py-1 border border-slate-200 dark:border-slate-700 outline-none cursor-pointer"
                  >
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30">Last 30 days</option>
                  </select>
                </div>

                <!-- 3 KPI Cards Row -->
                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                  <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                      12.4K
                    </span>
                    <span class="text-[10px] text-slate-500 block truncate">Page Reach</span>
                    <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                      ↑ 15%
                    </span>
                  </div>

                  <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                      8.7K
                    </span>
                    <span class="text-[10px] text-slate-500 block truncate">Engagement</span>
                    <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                      ↑ 12%
                    </span>
                  </div>

                  <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white block">
                      1.2K
                    </span>
                    <span class="text-[10px] text-slate-500 block truncate">New Followers</span>
                    <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                      ↑ 8%
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
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5">
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
                      :src="act.avatar_url"
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
              <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                    Related Pages
                  </h3>
                  <a href="#discovery" @click="activeSubNav = 'about'" class="text-xs font-bold text-[#1877F2] hover:underline">See All</a>
                </div>

                <div class="space-y-3">
                  <div
                    v-for="rp in displayRelatedPages"
                    :key="rp.id"
                    class="flex items-center justify-between gap-3"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden text-white" :style="{ backgroundColor: rp.icon_bg }">
                        <span>{{ rp.icon_emoji }}</span>
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ rp.name }}</h4>
                        <span class="text-[11px] text-slate-400 block truncate">{{ formatNumber(rp.followers_count) }} followers</span>
                      </div>
                    </div>

                    <button
                      type="button"
                      @click="rp.is_following = !rp.is_following"
                      class="px-3 py-1 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 text-[#1877F2] hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors cursor-pointer shrink-0"
                    >
                      {{ rp.is_following ? 'Following' : 'Follow' }}
                    </button>
                  </div>
                </div>
              </div>

            </div>

          </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- SUB-TAB CONTENT: OTHER SUB-TABS (ABOUT / PHOTOS / VIDEOS / EVENTS)   -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <div v-else class="p-6 sm:p-8 bg-[#F8FAFC] dark:bg-slate-950/50">
          <!-- About Sub-tab -->
          <div v-if="activeSubNav === 'about'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">About {{ activePage.name }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div><strong class="text-slate-500 block mb-1">Category:</strong> {{ activePage.category }}</div>
              <div><strong class="text-slate-500 block mb-1">Username:</strong> @{{ activePage.username || activePage.slug }}</div>
              <div><strong class="text-slate-500 block mb-1">Website:</strong> {{ activePage.website || 'Not specified' }}</div>
              <div><strong class="text-slate-500 block mb-1">Email:</strong> {{ activePage.email || 'Not specified' }}</div>
              <div><strong class="text-slate-500 block mb-1">Phone:</strong> {{ activePage.phone || 'Not specified' }}</div>
              <div><strong class="text-slate-500 block mb-1">Location:</strong> {{ activePage.city || activePage.address || 'Dhaka, Bangladesh' }}</div>
            </div>
          </div>

          <!-- Photos Sub-tab -->
          <div v-else-if="activeSubNav === 'photos'" class="space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Page Media & Photos</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700">
                <img :src="activePage.cover_image_url || defaultCoverUrl" class="w-full h-full object-cover" />
              </div>
              <div v-if="activePage.avatar_url" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700">
                <img :src="activePage.avatar_url" class="w-full h-full object-cover" />
              </div>
            </div>
          </div>

          <!-- Events Sub-tab -->
          <div v-else-if="activeSubNav === 'events'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Page Events</h3>
              <button @click="openEventModal" class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-sm">
                + Create Event
              </button>
            </div>
            <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center">
              <p class="text-xs text-slate-500">No upcoming events scheduled. Host webinars, live streams, or gatherings for your audience.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Welcome Banner if no managed page exists in studio -->
      <div v-else-if="mainTab === 'studio' && !activePage" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-12 shadow-sm text-center">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-[#1877F2] flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
          </svg>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Build your Brand with SocialHub Pages</h2>
        <p class="text-sm text-slate-500 max-w-lg mx-auto mt-2 mb-6">
          Connect with millions of people, build an active community, and grow your enterprise with a dedicated official page.
        </p>
        <a
          href="/pages/create"
          class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          <span>Create Your First Page</span>
        </a>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: CREATE POST ON PAGE                                             -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showPostModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Create Post &bull; {{ activePage?.name }}</h3>
          <button @click="showPostModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="submitPost" class="space-y-4">
          <div>
            <textarea
              v-model="postForm.content"
              rows="4"
              required
              placeholder="What's happening in your organization or community?"
              class="w-full p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Image or Media URL (Optional)</label>
            <input
              v-model="postForm.media_url"
              type="url"
              placeholder="https://images.unsplash.com/..."
              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Audience</label>
              <select v-model="postForm.audience" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold focus:outline-none">
                <option value="public">Public</option>
                <option value="followers">Followers Only</option>
                <option value="only_me">Only Me / Admin</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Publication Status</label>
              <select v-model="postForm.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold focus:outline-none">
                <option value="published">Publish Immediately</option>
                <option value="draft">Save as Draft</option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="showPostModal = false"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="postSubmitting"
              class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50"
            >
              {{ postSubmitting ? 'Publishing...' : 'Publish Post' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 2: EDIT PAGE DETAILS                                               -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Page Details</h3>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="submitEditPage" class="space-y-3.5">
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Page Name</label>
            <input v-model="editForm.name" type="text" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Category</label>
            <input v-model="editForm.category" type="text" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Short Bio</label>
            <textarea v-model="editForm.bio" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Website</label>
              <input v-model="editForm.website" type="url" placeholder="https://..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Email</label>
              <input v-model="editForm.email" type="email" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Phone</label>
              <input v-model="editForm.phone" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">City / Location</label>
              <input v-model="editForm.city" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
            <button type="submit" :disabled="editSubmitting" class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50">
              {{ editSubmitting ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: PAGE SETTINGS                                                   -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Page Settings</h3>
          <button @click="showSettingsModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="submitSettings" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Page Visibility</label>
            <select v-model="settingsForm.visibility" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold focus:outline-none">
              <option value="public">Public (Visible to everyone)</option>
              <option value="unlisted">Unlisted (Restricted)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Call To Action (CTA) Button</label>
            <select v-model="settingsForm.cta_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold focus:outline-none">
              <option value="Learn More">Learn More</option>
              <option value="Contact Us">Contact Us</option>
              <option value="Visit Website">Visit Website</option>
              <option value="Shop Now">Shop Now</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">CTA Destination URL</label>
            <input v-model="settingsForm.cta_url" type="url" placeholder="https://..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]" />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="showSettingsModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
            <button type="submit" :disabled="settingsSubmitting" class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50">
              {{ settingsSubmitting ? 'Saving...' : 'Update Settings' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 4: ROLES & TEAM PERMISSIONS                                        -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showRolesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Page Roles & Permissions</h3>
            <p class="text-xs text-slate-500">Manage administrators, editors, and moderators</p>
          </div>
          <button @click="showRolesModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Invite New Member Form -->
        <form @submit.prevent="submitInviteMember" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 space-y-3">
          <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Invite Team Member</h4>
          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="inviteForm.identifier"
              type="text"
              required
              placeholder="Username or email address..."
              class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]"
            />
            <select
              v-model="inviteForm.role"
              class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold focus:outline-none"
            >
              <option value="admin">Admin</option>
              <option value="editor">Editor</option>
              <option value="moderator">Moderator</option>
              <option value="analyst">Analyst</option>
            </select>
          </div>
          <button
            type="submit"
            :disabled="inviteSubmitting"
            class="w-full py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50"
          >
            {{ inviteSubmitting ? 'Sending Invite...' : 'Send Role Invitation' }}
          </button>
        </form>

        <!-- Current Team Members List -->
        <div>
          <h4 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Current Page Team</h4>
          <div v-if="activePage.team_members && activePage.team_members.length > 0" class="space-y-2">
            <div
              v-for="m in activePage.team_members"
              :key="m.id"
              class="p-3 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/40 text-[#1877F2] font-bold text-xs flex items-center justify-center shrink-0 overflow-hidden">
                  <img v-if="m.user?.avatar_url" :src="m.user.avatar_url" class="w-full h-full object-cover" />
                  <span v-else>{{ m.user?.name?.charAt(0) || 'U' }}</span>
                </div>
                <div class="truncate">
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ m.user?.name }}</h5>
                  <p class="text-[11px] text-slate-500 capitalize">{{ m.role }} &bull; {{ m.status }}</p>
                </div>
              </div>
              <button
                v-if="activePage.is_owner"
                @click="removeTeamMember(m.id)"
                class="text-xs font-bold text-rose-500 hover:text-rose-700 px-2 py-1 rounded-lg hover:bg-rose-50"
              >
                Remove
              </button>
            </div>
          </div>
          <div v-else class="text-xs text-slate-400 py-3 text-center">
            No additional team members added. You are managing this page as owner.
          </div>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 5: MANAGE FOLLOWERS                                                -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showFollowersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Page Followers ({{ formatNumber(activePage.followers_count) }})</h3>
            <p class="text-xs text-slate-500">People and profiles following {{ activePage.name }}</p>
          </div>
          <button @click="showFollowersModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="relative">
          <input
            v-model="followersSearch"
            @input="fetchFollowers"
            type="text"
            placeholder="Search followers..."
            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]"
          />
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 min-h-[200px]">
          <div v-if="followersListLoading" class="text-xs text-slate-400 py-6 text-center">Loading followers...</div>
          <div v-else-if="followersList.length > 0" class="space-y-2">
            <div
              v-for="f in followersList"
              :key="f.id"
              class="p-3 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-[#1877F2] font-bold text-xs flex items-center justify-center shrink-0 overflow-hidden">
                  <img v-if="f.user?.avatar_url" :src="f.user.avatar_url" class="w-full h-full object-cover" />
                  <span v-else>{{ f.user?.name?.charAt(0) || 'U' }}</span>
                </div>
                <div class="truncate">
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ f.user?.name }}</h5>
                  <p class="text-[11px] text-slate-400">Followed {{ f.created_at || 'Recently' }}</p>
                </div>
              </div>
              <button
                v-if="activePage.permissions?.can_moderate"
                @click="removeFollower(f.user_id)"
                class="text-xs font-bold text-rose-500 hover:text-rose-700 px-2 py-1 rounded-lg hover:bg-rose-50"
              >
                Remove
              </button>
            </div>
          </div>
          <div v-else class="text-xs text-slate-400 py-6 text-center">
            No followers found matching the query.
          </div>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 6: INVITE FRIENDS                                                  -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Invite Friends to Page</h3>
            <p class="text-xs text-slate-500">Send invites to your contacts</p>
          </div>
          <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2">
          <div
            v-for="friend in (contacts || [])"
            :key="friend.id"
            class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-blue-100 text-[#1877F2] font-bold text-xs flex items-center justify-center overflow-hidden">
                <img v-if="friend.avatar_url" :src="friend.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ friend.name?.charAt(0) }}</span>
              </div>
              <span class="text-xs font-bold text-slate-900 dark:text-white">{{ friend.name }}</span>
            </div>
            <button
              @click="sendFriendInvite(friend)"
              :disabled="invitedFriends.includes(friend.id)"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
              :class="invitedFriends.includes(friend.id) ? 'bg-slate-100 text-slate-400 dark:bg-slate-800' : 'bg-[#1877F2] text-white hover:bg-[#1666D8]'"
            >
              {{ invitedFriends.includes(friend.id) ? 'Invited' : 'Invite' }}
            </button>
          </div>
          <div v-if="!contacts || contacts.length === 0" class="text-xs text-slate-400 py-6 text-center">
            No contacts available to invite right now.
          </div>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 7: DELETE PAGE CONFIRMATION                                        -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-rose-200 dark:border-rose-900/50 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center mx-auto">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>

        <div class="text-center space-y-1">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Page Permanently?</h3>
          <p class="text-xs text-slate-500 leading-relaxed">
            Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200">{{ activePage.name }}</strong>? This action will remove posts, follower associations, and media.
          </p>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">
            Type <span class="font-bold text-rose-600">{{ activePage.name }}</span> to confirm:
          </label>
          <input
            v-model="deleteConfirmName"
            type="text"
            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-rose-500"
          />
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
          <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
          <button
            type="button"
            @click="executeDeletePage"
            :disabled="deleteConfirmName !== activePage.name || deleteSubmitting"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-all disabled:opacity-40"
          >
            {{ deleteSubmitting ? 'Deleting...' : 'Delete Page' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 8: MONETIZATION / PAGE QUALITY DIALOG                              -->
    <!-- ═════════════════════════════════════════════════════════════════════════ -->
    <div v-if="showQualityModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-sm w-full p-6 text-center space-y-4">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center mx-auto">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Page Quality: Good Standing</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
          Your page follows SocialHub Community Standards with zero policy violations or restrictions.
        </p>
        <button @click="showQualityModal = false" class="w-full py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">
          Close
        </button>
      </div>
    </div>

    <div v-if="showMonetizationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-sm w-full p-6 text-center space-y-4">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mx-auto">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Monetization Hub</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
          Eligible for in-stream brand sponsorships, creator subscriptions, and ticketed live events. Maintain 10K+ followers and authentic engagement to unlock payout tools.
        </p>
        <button @click="showMonetizationModal = false" class="w-full py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">
          Close
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../../types';

interface PageData {
  id: number;
  name: string;
  slug: string;
  username?: string;
  category: string;
  bio?: string;
  description?: string;
  avatar_url?: string;
  cover_image_url?: string;
  followers_count: number;
  posts_count: number;
  is_verified?: boolean;
  status?: string;
  visibility?: string;
  website?: string;
  email?: string;
  phone?: string;
  city?: string;
  address?: string;
  cta_type?: string;
  cta_url?: string;
  is_owner?: boolean;
  user_role?: string;
  permissions?: Record<string, boolean>;
  stats?: {
    followers: number;
    followers_growth: string;
    reach: number;
    reach_growth: string;
    engagement: number;
    engagement_growth: string;
    views: number;
    views_growth: string;
  };
  recent_posts?: any[];
  top_content?: any[];
  trend_data?: { date: string; followers: number; reach: number; engagement: number }[];
  team_members?: any[];
}

const props = withDefaults(
  defineProps<{
    managedPages?: PageData[];
    followedPages?: any[];
    selectedPage?: PageData | null;
    summaryStats?: Record<string, any>;
    pages?: {
      data: any[];
      current_page: number;
      last_page: number;
      total: number;
      per_page: number;
    };
    categories?: string[];
    pendingInvitations?: any[];
    filters?: {
      tab?: string;
      q?: string;
      category?: string;
      sort?: string;
    };
    unreadCount?: number;
    unreadMessagesCount?: number;
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
  }>(),
  {
    managedPages: () => [],
    followedPages: () => [],
    selectedPage: null,
    categories: () => [],
    pendingInvitations: () => [],
    filters: () => ({ tab: 'all', q: '', category: 'all', sort: 'popular' }),
    unreadCount: 0,
    unreadMessagesCount: 0,
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
  }
);

// Dynamic and Category Tailored High-Resolution Cover Pools
const categoryCovers: Record<string, string> = {
  'Technology': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80',
  'Technology & Social Network': 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80',
  'Business': 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
  'Consumer Brand': 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80',
  'News & Media': 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1200&q=80',
  'Education': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=80',
  'Entertainment': 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80',
  'Food & Drink': 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80',
  'Sports': 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=80',
  'Fashion': 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1200&q=80',
  'Community': 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=80',
  'Travel & Adventure': 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1200&q=80',
  'Photography': 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=1200&q=80',
};

const defaultCoverList = [
  'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80',
];

const defaultCoverUrl = 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80';

const getPageCover = (page: any): string => {
  if (page?.cover_image_url) return page.cover_image_url;
  if (page?.category && categoryCovers[page.category]) return categoryCovers[page.category];
  const idx = Math.abs((page?.id || 0)) % defaultCoverList.length;
  return defaultCoverList[idx];
};

const avatarGradients = [
  'from-blue-600 to-indigo-700',
  'from-emerald-500 to-teal-700',
  'from-violet-600 to-purple-800',
  'from-rose-500 to-pink-700',
  'from-amber-500 to-orange-600',
  'from-cyan-600 to-blue-700',
];

const getAvatarGradient = (page: any): string => {
  const code = (page?.name || 'P').charCodeAt(0) + (page?.id || 0);
  return avatarGradients[code % avatarGradients.length];
};

// Active Selected Page State
const activePage = ref<PageData | null>(props.selectedPage || props.managedPages[0] || null);

// Navigation & Filters State
const mainTab = ref<string>(
  props.filters?.tab === 'my_pages' ? 'my_pages' : (props.filters?.tab === 'followed' ? 'liked' : (props.filters?.tab === 'studio' ? 'studio' : 'discover'))
);
const activeSubNav = ref<'overview' | 'posts' | 'about' | 'photos' | 'events'>('overview');
const activeDirectoryTab = ref<string>(props.filters?.tab || 'all');
const searchQuery = ref<string>(props.filters?.q || '');
const selectedCategoryFilter = ref<string>(props.filters?.category || 'all');
const selectedSort = ref<string>(props.filters?.sort || 'popular');
const insightsRange = ref<string>('7');

// Directory Pages List with Optimistic Reaction/Follow State
const pagesList = ref<any[]>([...(props.pages?.data || [])]);
const pagesMeta = computed(() => props.pages);

const featuredPages = computed(() => {
  return pagesList.value.filter((p: any) => p.is_verified || p.followers_count > 0).slice(0, 3);
});

const setMainTab = (tab: string) => {
  mainTab.value = tab;
  if (tab === 'discover') {
    activeDirectoryTab.value = 'all';
    applyFilters();
  } else if (tab === 'my_pages') {
    activeDirectoryTab.value = 'my_pages';
    applyFilters();
  } else if (tab === 'liked') {
    activeDirectoryTab.value = 'followed';
    applyFilters();
  }
};

// Modals State
const moreMenuOpen = ref(false);
const showPostModal = ref(false);
const showEditModal = ref(false);
const showSettingsModal = ref(false);
const showRolesModal = ref(false);
const showFollowersModal = ref(false);
const showInviteModal = ref(false);
const showDeleteModal = ref(false);
const showQualityModal = ref(false);
const showMonetizationModal = ref(false);

// Action Feedback Alerts
const actionMessage = ref<{ text: string; type: 'success' | 'error' } | null>(null);
const followLoadingId = ref<number | null>(null);
const invActionLoading = ref<number | null>(null);

// Forms State
const postForm = ref({ content: '', media_url: '', audience: 'public', status: 'published' });
const postSubmitting = ref(false);

const editForm = ref({
  name: activePage.value?.name || '',
  category: activePage.value?.category || '',
  bio: activePage.value?.bio || '',
  website: activePage.value?.website || '',
  email: activePage.value?.email || '',
  phone: activePage.value?.phone || '',
  city: activePage.value?.city || '',
});
const editSubmitting = ref(false);

const settingsForm = ref({
  visibility: activePage.value?.visibility || 'public',
  cta_type: activePage.value?.cta_type || 'Learn More',
  cta_url: activePage.value?.cta_url || '',
});
const settingsSubmitting = ref(false);

const inviteForm = ref({ identifier: '', role: 'editor' });
const inviteSubmitting = ref(false);

const followersSearch = ref('');
const followersList = ref<any[]>([]);
const followersListLoading = ref(false);

const invitedFriends = ref<number[]>([]);
const deleteConfirmName = ref('');
const deleteSubmitting = ref(false);

// Format Numbers to 'K' or 'M'
const formatNumber = (num: number | undefined): string => {
  if (num === undefined || num === null) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  return num.toString();
};

// SVG Chart Calculations for 7-day trend
const chartPoints = computed(() => {
  const data = activePage.value?.trend_data || [];
  if (data.length === 0) return '0,100 350,50';
  const maxVal = Math.max(...data.map(d => d.reach), 50);
  const stepX = 350 / Math.max(1, data.length - 1);
  return data
    .map((d, idx) => {
      const x = idx * stepX;
      const y = 100 - (d.reach / maxVal) * 75;
      return `${x},${Math.round(y)}`;
    })
    .join(' ');
});

const chartCoordinates = computed(() => {
  const data = activePage.value?.trend_data || [];
  if (data.length === 0) return [];
  const maxVal = Math.max(...data.map(d => d.reach), 50);
  const stepX = 350 / Math.max(1, data.length - 1);
  return data.map((d, idx) => ({
    x: idx * stepX,
    y: Math.round(100 - (d.reach / maxVal) * 75),
    reach: d.reach,
  }));
});

// Helper for CSRF Token
const getCsrfToken = (): string => {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') || '' : '';
};

// Select Active Managed Page
const selectPage = (p: PageData) => {
  router.get('/pages', { page: p.slug }, { preserveState: false, preserveScroll: true });
};

// Directory Search & Filter Handlers
let searchDebounceTimer: any = null;
const handleSearch = () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    applyFilters();
  }, 350);
};

const switchDirectoryTab = (tabId: string) => {
  activeDirectoryTab.value = tabId;
  applyFilters();
};

const selectCategory = (cat: string) => {
  selectedCategoryFilter.value = cat;
  applyFilters();
};

const handleFilterChange = () => {
  applyFilters();
};

const clearFilters = () => {
  searchQuery.value = '';
  selectedCategoryFilter.value = 'all';
  activeDirectoryTab.value = 'all';
  selectedSort.value = 'popular';
  applyFilters();
};

const applyFilters = () => {
  router.get(
    '/pages',
    {
      tab: activeDirectoryTab.value,
      q: searchQuery.value || undefined,
      category: selectedCategoryFilter.value !== 'all' ? selectedCategoryFilter.value : undefined,
      sort: selectedSort.value,
      page: activePage.value?.slug ? activePage.value.slug : undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

const goToPage = (pageNumber: number) => {
  router.get(
    '/pages',
    {
      tab: activeDirectoryTab.value,
      q: searchQuery.value || undefined,
      category: selectedCategoryFilter.value !== 'all' ? selectedCategoryFilter.value : undefined,
      sort: selectedSort.value,
      page: pageNumber,
    },
    { preserveState: true, preserveScroll: true }
  );
};

const isFollowingPage = computed(() => (activePage.value ? Boolean(activePage.value.is_following) : false));

const openStudio = (p: PageData) => {
  activePage.value = p;
  mainTab.value = 'studio';
};

const myPages = computed(() => props.managedPages || []);
const likedPages = computed(() => props.followedPages || []);

// Follow / Unfollow Page with Optimistic Update
const toggleFollowPage = async (targetPage?: any) => {
  const page = targetPage || activePage.value;
  if (!page) return;

  followLoadingId.value = page.id;
  const prevFollowing = Boolean(page.is_following);
  const prevCount = page.followers_count || 0;

  // Optimistic update
  page.is_following = !prevFollowing;
  page.followers_count = prevFollowing ? Math.max(0, prevCount - 1) : prevCount + 1;

  try {
    const response = await fetch(`/api/v1/pages/${page.id}/follow`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const result = await response.json();
    if (result.success && result.data) {
      page.is_following = result.data.is_following;
      page.followers_count = result.data.followers_count;
    }
  } catch (e) {
    // Rollback
    page.is_following = prevFollowing;
    page.followers_count = prevCount;
    actionMessage.value = { text: 'Could not update follow state. Please try again.', type: 'error' };
  } finally {
    followLoadingId.value = null;
  }
};

// Toggle Bookmark / Favorite
const toggleFavorite = (page: any) => {
  actionMessage.value = { text: `Saved "${page.name}" to your favorites.`, type: 'success' };
};

// Share Post
const sharePost = (post: any) => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(`${window.location.origin}/pages/${activePage.value?.slug}`);
    actionMessage.value = { text: 'Page link copied to clipboard!', type: 'success' };
  }
};

// Like Post
const toggleLikePost = async (post: any) => {
  const prevReact = post.user_reaction;
  post.user_reaction = prevReact ? null : 'like';
  post.likes_count = (post.likes_count || 0) + (prevReact ? -1 : 1);

  try {
    await fetch(`/posts/${post.id}/react`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ type: 'like' }),
    });
  } catch (e) {
    post.user_reaction = prevReact;
  }
};

// Open Modals
const openPostModal = () => {
  postForm.value = { content: '', media_url: '', audience: 'public', status: 'published' };
  showPostModal.value = true;
};

const openPostModalWithPhoto = () => {
  openPostModal();
};

const openEventModal = () => {
  activeSubNav.value = 'events';
};

const openInviteModal = () => {
  showInviteModal.value = true;
};

const openEditModal = () => {
  editForm.value = {
    name: activePage.value?.name || '',
    category: activePage.value?.category || '',
    bio: activePage.value?.bio || '',
    website: activePage.value?.website || '',
    email: activePage.value?.email || '',
    phone: activePage.value?.phone || '',
    city: activePage.value?.city || '',
  };
  showEditModal.value = true;
};

const openSettingsModal = () => {
  settingsForm.value = {
    visibility: activePage.value?.visibility || 'public',
    cta_type: activePage.value?.cta_type || 'Learn More',
    cta_url: activePage.value?.cta_url || '',
  };
  showSettingsModal.value = true;
};

const openRolesModal = () => {
  inviteForm.value = { identifier: '', role: 'editor' };
  showRolesModal.value = true;
};

const openFollowersModal = () => {
  showFollowersModal.value = true;
  fetchFollowers();
};

const openDetailedInsightsModal = () => {
  actionMessage.value = { text: 'Viewing latest 30-day analytics report for ' + activePage.value?.name, type: 'success' };
};

const openDeleteModal = () => {
  deleteConfirmName.value = '';
  showDeleteModal.value = true;
};

// Form Submissions
const submitPost = async () => {
  if (!activePage.value) return;
  postSubmitting.value = true;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/posts`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        content: postForm.value.content,
        audience: postForm.value.audience,
        status: postForm.value.status,
        link_url: postForm.value.media_url || undefined,
      }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showPostModal.value = false;
      actionMessage.value = { text: 'Post published successfully!', type: 'success' };
      // Refresh page data
      router.reload({ only: ['selectedPage'] });
    } else {
      actionMessage.value = { text: data.message || 'Failed to publish post.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error publishing post.', type: 'error' };
  } finally {
    postSubmitting.value = false;
  }
};

const submitEditPage = async () => {
  if (!activePage.value) return;
  editSubmitting.value = true;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/details`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(editForm.value),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showEditModal.value = false;
      if (activePage.value) {
        activePage.value.name = editForm.value.name;
        activePage.value.category = editForm.value.category;
        activePage.value.bio = editForm.value.bio;
        activePage.value.website = editForm.value.website;
        activePage.value.email = editForm.value.email;
        activePage.value.phone = editForm.value.phone;
        activePage.value.city = editForm.value.city;
      }
      actionMessage.value = { text: 'Page details updated successfully!', type: 'success' };
    } else {
      actionMessage.value = { text: data.message || 'Failed to update details.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error updating page details.', type: 'error' };
  } finally {
    editSubmitting.value = false;
  }
};

const submitSettings = async () => {
  if (!activePage.value) return;
  settingsSubmitting.value = true;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/settings`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(settingsForm.value),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showSettingsModal.value = false;
      actionMessage.value = { text: 'Page settings saved successfully!', type: 'success' };
    } else {
      actionMessage.value = { text: data.message || 'Failed to update settings.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error updating settings.', type: 'error' };
  } finally {
    settingsSubmitting.value = false;
  }
};

const submitInviteMember = async () => {
  if (!activePage.value) return;
  inviteSubmitting.value = true;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/team/invite`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(inviteForm.value),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      inviteForm.value.identifier = '';
      actionMessage.value = { text: 'Role invitation sent successfully!', type: 'success' };
      router.reload({ only: ['selectedPage'] });
    } else {
      actionMessage.value = { text: data.message || 'Failed to invite team member.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error sending invitation.', type: 'error' };
  } finally {
    inviteSubmitting.value = false;
  }
};

const removeTeamMember = async (memberId: number) => {
  if (!activePage.value) return;
  if (!confirm('Are you sure you want to remove this team member?')) return;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/team/${memberId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      actionMessage.value = { text: 'Team member removed.', type: 'success' };
      router.reload({ only: ['selectedPage'] });
    }
  } catch (e) {
    actionMessage.value = { text: 'Failed to remove team member.', type: 'error' };
  }
};

const fetchFollowers = async () => {
  if (!activePage.value) return;
  followersListLoading.value = true;

  try {
    const query = followersSearch.value ? `?q=${encodeURIComponent(followersSearch.value)}` : '';
    const res = await fetch(`/pages/${activePage.value.slug}/followers${query}`, {
      headers: { 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (data.success && data.data) {
      followersList.value = data.data;
    }
  } catch (e) {
    followersList.value = [];
  } finally {
    followersListLoading.value = false;
  }
};

const removeFollower = async (userId: number) => {
  if (!activePage.value) return;
  if (!confirm('Remove this user from page followers?')) return;

  try {
    const res = await fetch(`/pages/${activePage.value.slug}/followers/${userId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      actionMessage.value = { text: 'Follower removed from page.', type: 'success' };
      fetchFollowers();
      if (activePage.value) {
        activePage.value.followers_count = Math.max(0, activePage.value.followers_count - 1);
      }
    }
  } catch (e) {
    actionMessage.value = { text: 'Failed to remove follower.', type: 'error' };
  }
};

const sendFriendInvite = (friend: User) => {
  invitedFriends.value.push(friend.id);
  actionMessage.value = { text: `Invitation sent to ${friend.name}!`, type: 'success' };
};

const executeDeletePage = async () => {
  if (!activePage.value) return;
  deleteSubmitting.value = true;

  try {
    const res = await fetch(`/api/v2/pages/${activePage.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showDeleteModal.value = false;
      actionMessage.value = { text: 'Page deleted successfully.', type: 'success' };
      window.location.href = '/pages';
    } else {
      actionMessage.value = { text: data.message || 'Failed to delete page.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Error deleting page.', type: 'error' };
  } finally {
    deleteSubmitting.value = false;
  }
};

const acceptInvitation = async (inv: any) => {
  invActionLoading.value = inv.id;
  try {
    const res = await fetch('/api/v2/pages/team/accept', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ token: inv.invitation_token }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      actionMessage.value = { text: 'You are now an active manager of this page!', type: 'success' };
      router.reload();
    } else {
      actionMessage.value = { text: data.message || 'Failed to accept invitation.', type: 'error' };
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error accepting invitation.', type: 'error' };
  } finally {
    invActionLoading.value = null;
  }
};

const declineInvitation = async (inv: any) => {
  invActionLoading.value = inv.id;
  try {
    const res = await fetch('/api/v2/pages/team/decline', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ token: inv.invitation_token }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      actionMessage.value = { text: 'Invitation declined.', type: 'success' };
      router.reload();
    }
  } catch (e) {
    actionMessage.value = { text: 'Network error declining invitation.', type: 'error' };
  } finally {
    invActionLoading.value = null;
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
