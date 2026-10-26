<template>
  <header class="sticky top-0 z-40 w-full bg-white dark:bg-slate-900 border-b border-[#E4E7EC] dark:border-slate-800 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-colors">
    <div class="max-w-[1920px] mx-auto w-full px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 gap-3 lg:gap-8">
        
        <!-- Left: Logo & Search Bar -->
        <div class="flex items-center gap-3 lg:gap-4 shrink-0">
          <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-10 h-10 rounded-xl bg-[#1877F2] flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:bg-[#1666D8] transition-all">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
              </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-[#172033] dark:text-white hidden sm:inline">
              Social<span class="text-[#1877F2]">Hub</span>
            </span>
          </a>

          <!-- Universal Search Input with Live Dropdown & Enter Handler -->
          <div class="relative w-48 sm:w-64 lg:w-72" ref="searchContainerRef">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
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
              placeholder="Search for people, posts, or groups..."
              class="w-full pl-10 pr-4 py-2 bg-[#F5F7FB] dark:bg-slate-800 text-xs sm:text-sm rounded-full border-none focus:ring-2 focus:ring-[#1877F2] text-[#172033] dark:text-slate-100 placeholder-slate-400 transition-all outline-none"
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
                  class="w-full pt-1.5 pb-1 text-center font-bold text-[#1877F2] hover:underline border-t border-slate-100 dark:border-slate-800"
                >
                  See all results for "{{ searchQuery }}"
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Center: Navigation Pill Tabs (Desktop) -->
        <nav class="hidden md:flex items-center gap-1 lg:gap-2">
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
            :class="currentRoute === 'groups' ? 'text-[#1877F2] font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
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
            :class="currentRoute === 'pages' ? 'text-[#1877F2] font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
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

        <!-- Right: User Avatar & Dropdown -->
        <div class="flex items-center gap-2 relative">
          <div class="relative" ref="dropdownRef">
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

    <!-- Mobile Bottom Navigation Bar -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-[#E4E7EC] dark:border-slate-800 px-4 py-2">
      <div class="flex items-center justify-around">
        <a href="/" class="p-2 text-slate-600 dark:text-slate-400 hover:text-[#1877F2]">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
        </a>
        <a href="/reels" :class="['p-2 hover:text-purple-600', currentRoute === 'reels' ? 'text-purple-600 dark:text-purple-400' : 'text-slate-600 dark:text-slate-400']">
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
          </svg>
        </a>
        <a href="/friends" class="p-2 text-slate-600 dark:text-slate-400 hover:text-[#1877F2]">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </a>
        <a href="/notifications" class="p-2 text-slate-600 dark:text-slate-400 hover:text-[#1877F2] relative">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span v-if="unreadCount > 0" class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-[#F04438] ring-2 ring-white"></span>
        </a>
        <a href="/messages" :class="['p-2 hover:text-[#1877F2] relative', currentRoute === 'messages' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400']">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span v-if="unreadMessagesCount > 0" class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-[#F04438] ring-2 ring-white"></span>
        </a>
        <a :href="user ? '/profile/' + user.username : '/login'" class="p-2 text-slate-600 dark:text-slate-400 hover:text-[#1877F2]">
          <img
            :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
            class="w-6 h-6 rounded-full object-cover"
            alt="Profile"
          />
        </a>
      </div>
    </div>
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

const isDropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const searchContainerRef = ref<HTMLElement | null>(null);

const searchQuery = ref('');
const isSearchFocused = ref(false);
const isSearching = ref(false);
const searchResults = ref<any[]>([]);
let searchTimeout: any = null;

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

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
