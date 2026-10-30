<template>
  <header class="sticky top-0 z-40 w-full bg-white dark:bg-slate-900 border-b border-[#E4E7EC] dark:border-slate-800 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-colors">
    <div class="max-w-[1920px] mx-auto w-full px-3 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 gap-2 sm:gap-4 lg:gap-8">
        
        <!-- Left: Logo & Search Bar -->
        <div class="flex items-center gap-2 sm:gap-3 lg:gap-4 min-w-0 flex-1 sm:flex-initial">
          <a href="/" class="flex items-center gap-2 shrink-0 group" aria-label="SocialHub Home">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#1877F2] flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:bg-[#1666D8] transition-all">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
              </svg>
            </div>
            <span class="text-lg sm:text-xl font-bold tracking-tight text-[#172033] dark:text-white hidden xs:inline">
              Social<span class="text-[#1877F2]">Hub</span>
            </span>
          </a>

          <!-- Universal Search Input with Live Dropdown & Mobile Responsiveness -->
          <div class="relative flex-1 min-w-0 max-w-[170px] xs:max-w-[220px] sm:max-w-xs lg:w-72" ref="searchContainerRef">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchQuery"
              @keyup.enter="handleSearch"
              @input="handleSearchInput"
              @focus="isSearchFocused = true"
              type="text"
              placeholder="Search..."
              class="w-full pl-9 pr-3 py-1.5 sm:py-2 bg-[#F5F7FB] dark:bg-slate-800 text-xs sm:text-sm rounded-full border-none focus:ring-2 focus:ring-[#1877F2] text-[#172033] dark:text-slate-100 placeholder-slate-400 transition-all outline-none"
            />

            <!-- Live Search Quick Results Dropdown -->
            <div
              v-if="isSearchFocused && (searchResults.length > 0 || isSearching)"
              class="absolute left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-[#E4E7EC] dark:border-slate-800 p-2 z-50 text-xs"
            >
              <div v-if="isSearching" class="p-3 text-center text-slate-400">
                Searching...
              </div>
              <div v-else class="space-y-1">
                <a
                  v-for="res in searchResults.slice(0, 5)"
                  :key="res.id"
                  :href="res.url"
                  class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-colors"
                >
                  <img
                    :src="res.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                    class="w-7 h-7 rounded-full object-cover shrink-0"
                    alt="Result"
                  />
                  <div class="min-w-0 flex-1">
                    <p class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ res.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate capitalize">{{ res.type }}</p>
                  </div>
                </a>
                <button
                  type="button"
                  @click="handleSearch"
                  class="w-full pt-1.5 pb-1 text-center font-bold text-[#1877F2] hover:underline border-t border-slate-100 dark:border-slate-800 cursor-pointer"
                >
                  See all results for "{{ searchQuery }}"
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Center: Navigation Pill Tabs (Desktop & Large Screens) -->
        <nav class="hidden md:flex items-center gap-1 lg:gap-2 shrink-0">
          <!-- Home Tab -->
          <a
            href="/"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'feed'
                ? 'text-[#1877F2] bg-blue-50/80 dark:bg-blue-900/30'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Home</span>
            <span v-if="currentRoute === 'feed'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Friends Tab -->
          <a
            href="/friends"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'friends'
                ? 'text-[#1877F2] bg-blue-50/80 dark:bg-blue-900/30'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Friends</span>
            <span v-if="currentRoute === 'friends'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Groups Tab -->
          <a
            href="/groups"
            class="px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 transition-all relative"
            :class="currentRoute === 'groups' ? 'text-[#1877F2] font-bold bg-blue-50/80 dark:bg-blue-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Groups</span>
            <span v-if="currentRoute === 'groups'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Pages Tab -->
          <a
            href="/pages"
            class="px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 transition-all relative"
            :class="currentRoute === 'pages' ? 'text-[#1877F2] font-bold bg-blue-50/80 dark:bg-blue-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>
            <span>Pages</span>
            <span v-if="currentRoute === 'pages'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Marketplace Tab -->
          <a
            href="/marketplace"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'marketplace'
                ? 'text-[#1877F2] bg-blue-50/80 dark:bg-blue-900/30 font-bold'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Marketplace</span>
            <span v-if="currentRoute === 'marketplace'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Reels Tab -->
          <a
            href="/reels"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'reels'
                ? 'text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/30 font-bold'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
            </svg>
            <span>Reels</span>
            <span v-if="currentRoute === 'reels'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-purple-600 rounded-full"></span>
          </a>

          <!-- Notifications Tab with Red Badge -->
          <a
            href="/notifications"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'notifications'
                ? 'text-[#1877F2] bg-blue-50/80 dark:bg-blue-900/30'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <div class="relative">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1.5 -right-2 px-1.5 py-0.2 min-w-[18px] h-[18px] bg-[#F04438] text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </div>
            <span>Notifications</span>
            <span v-if="currentRoute === 'notifications'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>

          <!-- Messages Tab with Real Dynamic Badge -->
          <a
            href="/messages"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs lg:text-sm font-semibold flex items-center gap-2 relative transition-all',
              currentRoute === 'messages'
                ? 'text-[#1877F2] bg-blue-50/80 dark:bg-blue-900/30'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <div class="relative">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span
                v-if="unreadMessagesCount > 0"
                class="absolute -top-1.5 -right-2 px-1.5 py-0.2 min-w-[18px] h-[18px] bg-[#F04438] text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900"
              >
                {{ unreadMessagesCount > 9 ? '9+' : unreadMessagesCount }}
              </span>
            </div>
            <span>Messages</span>
            <span v-if="currentRoute === 'messages'" class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1877F2] rounded-full"></span>
          </a>
        </nav>

        <!-- Right: Theme Toggle & User Avatar / Drawer Trigger -->
        <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
          <!-- Theme Switcher (Available on all screens) -->
          <button
            type="button"
            @click="toggleTheme"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            aria-label="Toggle Theme"
          >
            <!-- Sun icon when dark -->
            <svg v-if="isDark" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Moon icon when light -->
            <svg v-else class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Tablet & Mobile Menu Toggle Button (Opens Slide-Out Drawer) -->
          <button
            type="button"
            @click="openDrawer"
            class="lg:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer mobile-menu-toggle-btn"
            aria-label="Open Navigation Menu"
          >
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- User Dropdown (Desktop) -->
          <div class="relative hidden sm:block" ref="dropdownRef">
            <button
              @click="isDropdownOpen = !isDropdownOpen"
              class="flex items-center gap-2 p-1 pl-1 pr-2.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none cursor-pointer group"
            >
              <div class="relative shrink-0">
                <img
                  :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700 group-hover:ring-[#1877F2] transition-all shadow-xs"
                />
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-1.5 ring-white dark:ring-slate-900"></span>
              </div>
              <span class="text-sm font-semibold text-[#172033] dark:text-slate-200 hidden xl:inline">
                {{ user?.name || 'Guest' }}
              </span>
              <svg class="w-3.5 h-3.5 text-slate-500 hidden sm:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="isDropdownOpen"
              class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-[#E4E7EC] dark:border-slate-800 p-2 z-50 animate-in fade-in zoom-in-95"
            >
              <a
                :href="user ? '/profile/' + user.username : '/login'"
                class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              >
                <img
                  :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-9 h-9 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800 shadow-xs"
                />
                <div class="overflow-hidden">
                  <p class="text-xs font-bold text-[#172033] dark:text-white truncate">{{ user?.name || 'Guest' }}</p>
                  <p class="text-[11px] text-slate-400 truncate">View your profile</p>
                </div>
              </a>

              <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

              <a
                href="/saved"
                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
              >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                Saved Posts
              </a>

              <a
                href="/settings"
                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
              >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Settings & Privacy
              </a>

              <a
                href="/terms"
                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
              >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Help & Community Guidelines
              </a>

              <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

              <form v-if="user" method="POST" action="/logout">
                <input type="hidden" name="_token" :value="csrfToken" />
                <button
                  type="submit"
                  class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-colors text-left cursor-pointer"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Log Out
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Mobile Bottom Navigation Bar (< 768px) with Elevated Create Button -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 pb-[env(safe-area-inset-bottom,0px)] shadow-[0_-2px_12px_rgba(0,0,0,0.05)] transition-colors mobile-bottom-nav">
      <div class="grid grid-cols-5 items-center h-16 w-full max-w-lg mx-auto px-1">
        <!-- 1. Home -->
        <a
          href="/"
          class="flex flex-col items-center justify-center h-full min-h-[48px] py-1 text-center transition-colors mobile-nav-btn"
          :class="currentRoute === 'feed' ? 'text-[#1877F2] font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-[#1877F2]'"
          aria-label="Feed"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span class="text-[10px] font-semibold mt-0.5 truncate max-w-full text-center px-1 mobile-nav-label">Home</span>
        </a>

        <!-- 2. Reels -->
        <a
          href="/reels"
          class="flex flex-col items-center justify-center h-full min-h-[48px] py-1 text-center transition-colors mobile-nav-btn"
          :class="currentRoute === 'reels' ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-purple-600'"
          aria-label="Reels"
        >
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
          </svg>
          <span class="text-[10px] font-semibold mt-0.5 truncate max-w-full text-center px-1 mobile-nav-label">Reels</span>
        </a>

        <!-- 3. Elevated Create Button (Center) -->
        <div class="flex items-center justify-center h-full">
          <button
            type="button"
            id="mobileNavCreateBtn"
            @click="openCreateSheet"
            :aria-expanded="isCreateSheetOpen ? 'true' : 'false'"
            aria-controls="mobileCreateActionSheet"
            aria-haspopup="dialog"
            class="w-12 h-12 -mt-4 rounded-full bg-gradient-to-tr from-[#1877F2] to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/30 ring-4 ring-white dark:ring-slate-900 hover:shadow-blue-500/50 hover:scale-105 active:scale-95 transition-all cursor-pointer mobile-nav-btn mobile-nav-create-bubble"
            aria-label="Create Post, Story or Reel"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        <!-- 4. Messages / Chat -->
        <a
          href="/messages"
          class="flex flex-col items-center justify-center h-full min-h-[48px] py-1 text-center transition-colors relative mobile-nav-btn"
          :class="currentRoute === 'messages' ? 'text-[#1877F2] font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-[#1877F2]'"
          aria-label="Messages"
        >
          <div class="relative">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span
              v-if="unreadMessagesCount > 0"
              class="absolute -top-1 -right-2 px-1 min-w-[16px] h-4 bg-[#F04438] text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900"
            >
              {{ unreadMessagesCount > 9 ? '9+' : unreadMessagesCount }}
            </span>
          </div>
          <span class="text-[10px] font-semibold mt-0.5 truncate max-w-full text-center px-1 mobile-nav-label">Chat</span>
        </a>

        <!-- 5. Menu / Slide-out Drawer Trigger -->
        <button
          type="button"
          id="mobileMenuToggleBtn"
          @click="openDrawer"
          :aria-expanded="isDrawerOpen ? 'true' : 'false'"
          aria-controls="mobileMenuDrawer"
          aria-haspopup="dialog"
          class="flex flex-col items-center justify-center h-full min-h-[48px] py-1 text-center transition-colors text-slate-600 dark:text-slate-400 hover:text-[#1877F2] cursor-pointer mobile-nav-btn mobile-menu-toggle-btn"
          aria-label="Navigation Menu"
        >
          <div class="relative">
            <img
              :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              class="w-6 h-6 rounded-full object-cover ring-1 ring-slate-300 dark:ring-slate-700"
              alt="Menu"
            />
            <span
              v-if="unreadCount > 0"
              class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-[#F04438] ring-2 ring-white dark:ring-slate-900"
            ></span>
          </div>
          <span class="text-[10px] font-semibold mt-0.5 truncate max-w-full text-center px-1 mobile-nav-label">Menu</span>
        </button>
      </div>
    </nav>

    <!-- Slide-Out Navigation Drawer (Mobile & Tablet) -->
    <Teleport to="body">
      <div
        v-if="isDrawerOpen"
        id="mobileMenuDrawer"
        class="fixed inset-0 z-[70] overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mobileDrawerTitle"
      >
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300 z-[70]"
          @click="closeDrawer"
        ></div>

        <!-- Drawer Content Panel -->
        <div class="fixed inset-y-0 right-0 max-w-full flex z-[71]">
          <div class="w-screen max-w-[340px] xs:max-w-[360px] sm:max-w-md h-full bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800 animate-in slide-in-from-right duration-250 ease-out">
            <!-- 1. Header & User Profile Information -->
            <div class="shrink-0 p-4 border-b border-slate-100 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm flex items-center justify-between gap-3 pt-[calc(env(safe-area-inset-top,0px)+14px)]">
              <div class="flex items-center gap-3 min-w-0">
                <div class="relative shrink-0">
                  <img
                    :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                    class="w-11 h-11 rounded-full object-cover ring-2 ring-[#1877F2]/30 shadow-xs"
                    alt="Profile"
                  />
                  <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900"></span>
                </div>
                <div class="min-w-0">
                  <h4 id="mobileDrawerTitle" class="font-bold text-sm sm:text-base text-slate-900 dark:text-white truncate">
                    {{ user?.name || 'Guest User' }}
                  </h4>
                  <p v-if="user?.username" class="text-[11px] text-slate-400 dark:text-slate-500 truncate">
                    @{{ user.username }}
                  </p>
                  <a
                    :href="user ? '/profile/' + user.username : '/login'"
                    class="text-xs text-[#1877F2] font-semibold hover:underline flex items-center gap-1 mt-0.5 truncate"
                  >
                    <span>View profile</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </a>
                </div>
              </div>
              
              <div class="flex items-center gap-1.5 shrink-0">
                <!-- Theme toggle inside drawer -->
                <button
                  type="button"
                  @click="toggleTheme"
                  class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                  :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                  aria-label="Toggle Theme"
                >
                  <svg v-if="isDark" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  <svg v-else class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                  </svg>
                </button>

                <!-- Close Button -->
                <button
                  type="button"
                  @click="closeDrawer"
                  class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 hover:rotate-90 transition-all cursor-pointer"
                  aria-label="Close Navigation Drawer"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- 2. Scrollable Navigation Items (Independent Smooth Scroll) -->
            <div class="flex-1 overflow-y-auto overscroll-contain p-3 sm:p-4 space-y-1 scroll-smooth">
              <p class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Navigation Hub
              </p>
              
              <!-- Feed / Home -->
              <a
                href="/"
                :class="[
                  'flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'feed'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                </span>
                <span class="truncate">Feed / Home</span>
              </a>

              <!-- Friends -->
              <a
                href="/friends"
                :class="[
                  'flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'friends'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-sky-50 dark:bg-sky-950/60 text-sky-600 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                </span>
                <span class="truncate">Friends</span>
              </a>

              <!-- Messages -->
              <a
                href="/messages"
                :class="[
                  'flex items-center justify-between px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'messages'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <div class="flex items-center gap-3.5 min-w-0">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-blue-50 dark:bg-blue-950/60 text-blue-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                  </span>
                  <span class="truncate">Messages</span>
                </div>
                <span
                  v-if="unreadMessagesCount > 0"
                  class="px-2 py-0.5 text-xs font-bold bg-[#F04438] text-white rounded-full shrink-0"
                >
                  {{ unreadMessagesCount }}
                </span>
              </a>

              <!-- Notifications -->
              <a
                href="/notifications"
                :class="[
                  'flex items-center justify-between px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'notifications'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <div class="flex items-center gap-3.5 min-w-0">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/60 text-amber-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                  </span>
                  <span class="truncate">Notifications</span>
                </div>
                <span
                  v-if="unreadCount > 0"
                  class="px-2 py-0.5 text-xs font-bold bg-[#F04438] text-white rounded-full shrink-0"
                >
                  {{ unreadCount }}
                </span>
              </a>

              <!-- Reels -->
              <a
                href="/reels"
                :class="[
                  'flex items-center justify-between px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'reels'
                    ? 'bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 font-bold border-l-4 border-purple-600'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <div class="flex items-center gap-3.5 min-w-0">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                      <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                    </svg>
                  </span>
                  <span class="truncate">Reels</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 shrink-0">
                  New
                </span>
              </a>

              <!-- Groups -->
              <a
                href="/groups"
                :class="[
                  'flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'groups'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </span>
                <span class="truncate">Groups</span>
              </a>

              <!-- Pages -->
              <a
                href="/pages"
                :class="[
                  'flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'pages'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-orange-50 dark:bg-orange-950/60 text-orange-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                  </svg>
                </span>
                <span class="truncate">Pages</span>
              </a>

              <!-- Marketplace -->
              <a
                href="/marketplace"
                :class="[
                  'flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold transition-all group min-h-[48px]',
                  currentRoute === 'marketplace'
                    ? 'bg-blue-50/80 text-[#1877F2] dark:bg-blue-950/60 dark:text-blue-400 font-bold border-l-4 border-[#1877F2]'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80'
                ]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/60 text-emerald-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </span>
                <span class="truncate">Marketplace</span>
              </a>

              <!-- Live Studio -->
              <a
                href="/live/studio"
                class="flex items-center justify-between px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <div class="flex items-center gap-3.5 min-w-0">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                      <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/>
                    </svg>
                  </span>
                  <span class="truncate">Live Studio</span>
                </div>
                <span class="flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                  Live
                </span>
              </a>

              <!-- Saved Posts -->
              <a
                href="/saved"
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-teal-50 dark:bg-teal-950/60 text-teal-600 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                  </svg>
                </span>
                <span class="truncate">Saved Posts</span>
              </a>

              <!-- Events -->
              <a
                href="/events"
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-violet-50 dark:bg-violet-950/60 text-violet-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </span>
                <span class="truncate">Events</span>
              </a>

              <!-- Memories -->
              <a
                href="/memories"
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span class="truncate">Memories</span>
              </a>

              <div class="h-px bg-slate-100 dark:bg-slate-800 my-2"></div>

              <p class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Settings & Safety
              </p>

              <!-- Settings & Privacy -->
              <a
                href="/settings"
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </span>
                <span class="truncate">Settings & Privacy</span>
              </a>

              <!-- Help & Guidelines -->
              <a
                href="/terms"
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100/80 dark:hover:bg-slate-800/80 transition-all group min-h-[48px]"
              >
                <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span class="truncate">Help & Guidelines</span>
              </a>
            </div>

            <!-- 3. Drawer Footer: Logout / Sign In Action -->
            <div class="shrink-0 p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-sm pb-[calc(env(safe-area-inset-bottom,0px)+14px)]">
              <form v-if="user" method="POST" action="/logout">
                <input type="hidden" name="_token" :value="csrfToken" />
                <button
                  type="submit"
                  class="w-full flex items-center justify-center gap-2.5 py-3 px-4 text-sm font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/60 active:scale-[0.98] rounded-xl transition-all cursor-pointer min-h-[48px]"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  <span>Log Out</span>
                </button>
              </form>
              <a
                v-else
                href="/login"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 text-sm font-bold text-white bg-[#1877F2] hover:bg-[#1666D8] active:scale-[0.98] rounded-xl transition-all shadow-md shadow-blue-500/20 min-h-[48px]"
              >
                Sign In
              </a>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Create Action Sheet (Bottom Modal) -->
    <Teleport to="body">
      <div
        v-if="isCreateSheetOpen"
        id="mobileCreateActionSheet"
        class="fixed inset-0 z-50 flex items-end justify-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="createActionSheetTitle"
      >
        <!-- Backdrop -->
        <div
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
          @click="closeCreateSheet"
        ></div>

        <!-- Sheet Modal -->
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-t-3xl shadow-2xl p-5 border-t border-[#E4E7EC] dark:border-slate-800 animate-in slide-in-from-bottom duration-200 z-10 pb-[calc(env(safe-area-inset-bottom,0px)+16px)]">
          <!-- Grab Handle -->
          <div class="w-12 h-1.5 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-4"></div>

          <div class="flex items-center justify-between mb-4">
            <h3 id="createActionSheetTitle" class="font-bold text-base text-[#172033] dark:text-white">Create New</h3>
            <button
              type="button"
              @click="closeCreateSheet"
              class="p-1 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Quick Creation Grid -->
          <div class="grid grid-cols-4 gap-3 sm:gap-4 mb-4">
            <!-- 1. Post -->
            <a
              href="/"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Post</span>
            </a>

            <!-- 2. Story -->
            <a
              href="/stories/create"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Story</span>
            </a>

            <!-- 3. Reel -->
            <a
              href="/reels?create=1"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                  <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Reel</span>
            </a>

            <!-- 4. Live -->
            <a
              href="/live/studio"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                  <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/>
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Live</span>
            </a>

            <!-- 5. Page -->
            <a
              href="/pages/create"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-950/60 text-orange-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Page</span>
            </a>

            <!-- 6. Group -->
            <a
              href="/groups/create"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Group</span>
            </a>

            <!-- 7. Marketplace Listing -->
            <a
              href="/marketplace/create"
              class="flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-center group"
            >
              <div class="w-12 h-12 rounded-2xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Listing</span>
            </a>
          </div>

          <button
            type="button"
            @click="closeCreateSheet"
            class="w-full py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
          >
            Cancel
          </button>
        </div>
      </div>
    </Teleport>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { User } from '../types';

const props = withDefaults(
  defineProps<{
    user: User | null;
    currentRoute?: 'feed' | 'friends' | 'notifications' | 'profile' | 'pages' | 'messages' | 'groups' | 'marketplace' | 'reels';
    unreadCount?: number;
    unreadMessagesCount?: number;
  }>(),
  {
    unreadCount: 0,
    unreadMessagesCount: 0,
  }
);

// Navigation Drawer & Action Sheet State
const isDrawerOpen = ref(false);
const isCreateSheetOpen = ref(false);
const isDropdownOpen = ref(false);

const dropdownRef = ref<HTMLElement | null>(null);
const searchContainerRef = ref<HTMLElement | null>(null);

// Search State
const searchQuery = ref('');
const isSearchFocused = ref(false);
const isSearching = ref(false);
const searchResults = ref<any[]>([]);
let searchTimeout: any = null;

// Theme Switcher State
const isDark = ref(false);

const initTheme = () => {
  if (typeof window !== 'undefined') {
    isDark.value =
      document.documentElement.classList.contains('dark') ||
      localStorage.theme === 'dark' ||
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);

    if (isDark.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }
};

const toggleTheme = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.theme = 'dark';
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.theme = 'light';
  }
};

let lastFocusedElement: HTMLElement | null = null;

const openDrawer = () => {
  lastFocusedElement = document.activeElement as HTMLElement;
  isDrawerOpen.value = true;
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';
};

const closeDrawer = () => {
  isDrawerOpen.value = false;
  if (!isCreateSheetOpen.value) {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }
  if (lastFocusedElement) {
    lastFocusedElement.focus();
    lastFocusedElement = null;
  }
};

const openCreateSheet = () => {
  lastFocusedElement = document.activeElement as HTMLElement;
  isCreateSheetOpen.value = true;
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';
};

const closeCreateSheet = () => {
  isCreateSheetOpen.value = false;
  if (!isDrawerOpen.value) {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }
  if (lastFocusedElement) {
    lastFocusedElement.focus();
    lastFocusedElement = null;
  }
};

const csrfToken = computed(() => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
});

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    window.location.href = `/search?q=${encodeURIComponent(searchQuery.value.trim())}`;
  }
};

const handleSearchInput = () => {
  clearTimeout(searchTimeout);
  if (!searchQuery.value.trim() || searchQuery.value.trim().length < 2) {
    searchResults.value = [];
    return;
  }

  isSearching.value = true;
  searchTimeout = setTimeout(async () => {
    try {
      const res = await fetch(`/api/v1/search?q=${encodeURIComponent(searchQuery.value.trim())}&per_page=5`);
      const data = await res.json();
      if (res.ok && data.data) {
        const resultsObj = data.data.results || data.data;
        const users = (resultsObj.users || data.data.users || data.data.data || []).map((u: any) => ({
          id: 'user-' + u.id,
          title: u.name,
          avatar: u.avatar_url,
          url: `/profile/${u.username}`,
          type: 'Person',
        }));
        const groups = (resultsObj.groups || []).map((g: any) => ({
          id: 'group-' + g.id,
          title: g.name,
          avatar: g.avatar_url,
          url: `/groups/${g.id}`,
          type: 'Group',
        }));
        const pages = (resultsObj.pages || []).map((p: any) => ({
          id: 'page-' + p.id,
          title: p.name,
          avatar: p.avatar_url,
          url: `/pages/${p.id}`,
          type: 'Page',
        }));
        searchResults.value = [...users, ...groups, ...pages].slice(0, 6);
      }
    } catch (e) {
      console.error(e);
    } finally {
      isSearching.value = false;
    }
  }, 250);
};

const handleClickOutside = (e: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    isDropdownOpen.value = false;
  }
  if (searchContainerRef.value && !searchContainerRef.value.contains(e.target as Node)) {
    isSearchFocused.value = false;
  }
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    if (isDrawerOpen.value) closeDrawer();
    if (isCreateSheetOpen.value) closeCreateSheet();
    if (isDropdownOpen.value) isDropdownOpen.value = false;
  }
};

onMounted(() => {
  initTheme();
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeyDown);
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
});
</script>
