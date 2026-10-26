<template>
  <aside class="hidden lg:block w-64 xl:w-72 2xl:w-80 shrink-0 space-y-4 sticky top-20 self-start max-h-[calc(100vh-5.5rem)] overflow-y-auto no-scrollbar">
    <!-- User Profile Shortcut -->
    <a
      :href="user ? '/profile/' + user.username : '/login'"
      class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-white dark:hover:bg-slate-900 transition-all group"
    >
      <div class="relative shrink-0">
        <img
          :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
          alt="Avatar"
          class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-200/90 dark:ring-slate-700/80 shadow-xs group-hover:ring-[#1877F2]/50 group-hover:scale-105 transition-all"
        />
        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs"></span>
      </div>
      <div class="overflow-hidden">
        <h3 class="font-bold text-sm text-[#172033] dark:text-slate-100 truncate group-hover:text-[#1877F2] transition-colors">
          {{ user?.name || 'Guest User' }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
          View your profile
        </p>
      </div>
    </a>

    <!-- Main Navigation List -->
    <div class="space-y-1">
      <!-- Home Link -->
      <a
        href="/"
        :class="[
          'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
          currentRoute === 'feed'
            ? 'bg-[#E7F3FF] text-[#1877F2] dark:bg-blue-950/40 dark:text-blue-400'
            : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'
        ]"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-[#1877F2]">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
        </span>
        <span>Home</span>
      </a>

      <!-- Friends Link -->
      <a
        href="/friends"
        :class="[
          'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
          currentRoute === 'friends'
            ? 'bg-[#E7F3FF] text-[#1877F2] dark:bg-blue-950/40 dark:text-blue-400'
            : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'
        ]"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </span>
        <span>Friends</span>
      </a>

      <!-- Groups Link -->
      <a
        href="/groups"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all"
        :class="currentRoute === 'groups' ? 'bg-[#EBF5FF] text-[#1877F2] font-bold shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center" :class="currentRoute === 'groups' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </span>
        <span>Groups</span>
      </a>

      <!-- Marketplace Link -->
      <a
        href="/marketplace"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all"
        :class="currentRoute === 'marketplace' ? 'bg-[#E7F3FF] text-[#1877F2] dark:bg-blue-950/40 dark:text-blue-400 font-bold shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center" :class="currentRoute === 'marketplace' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400'">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </span>
        <span>Marketplace</span>
      </a>

      <!-- Reels Link -->
      <a
        href="/reels"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all group"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
          </svg>
        </span>
        <span class="flex-1">Reels</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">New</span>
      </a>

      <!-- Live Video Link -->
      <a
        href="/live/studio"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all group"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
            <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/>
          </svg>
        </span>
        <span class="flex-1">Live Video</span>
        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
      </a>

      <!-- Saved Link -->
      <a
        href="/saved"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
        </span>
        <span>Saved</span>
      </a>

      <!-- Memories Link -->
      <a
        href="/memories"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        <span>Memories</span>
      </a>

      <!-- Pages Link -->
      <a
        href="/pages"
        :class="[
          'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
          currentRoute === 'pages'
            ? 'bg-[#E7F3FF] text-[#1877F2] dark:bg-blue-950/40 dark:text-blue-400'
            : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'
        ]"
      >
        <span :class="['w-8 h-8 rounded-lg flex items-center justify-center', currentRoute === 'pages' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400']">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
          </svg>
        </span>
        <span>Pages</span>
      </a>

      <!-- Events Link -->
      <a
        href="/events"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
      >
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </span>
        <span>Events</span>
      </a>

      <!-- See more button -->
      <button
        @click="showMore = !showMore"
        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900 transition-all"
      >
        <span class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-500">
          <svg
            :class="['w-4 h-4 transition-transform', showMore ? 'rotate-180' : '']"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </span>
        <span>{{ showMore ? 'See less' : 'See more' }}</span>
      </button>
    </div>

    <div class="h-px bg-slate-200/80 dark:bg-slate-800 my-2"></div>

    <!-- Your Shortcuts Section -->
    <div class="space-y-2">
      <h4 class="px-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
        Your Shortcuts
      </h4>

      <div class="space-y-1">
        <a
          href="/groups?category=Technology"
          class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
        >
          <div class="w-7 h-7 rounded-lg bg-slate-800 text-white flex items-center justify-center text-[10px] font-mono shadow-sm">
            &lt;/&gt;
          </div>
          <span class="truncate">Web Developers</span>
        </a>

        <a
          href="/groups?category=Travel"
          class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
        >
          <div class="w-7 h-7 rounded-lg bg-[#0084FF] text-white flex items-center justify-center text-xs shadow-sm">
            ✈️
          </div>
          <span class="truncate">Travel &amp; Adventure</span>
        </a>

        <a
          href="/groups?category=Photography"
          class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
        >
          <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs shadow-sm">
            📷
          </div>
          <span class="truncate">Photography</span>
        </a>

        <a
          href="/groups?category=Food"
          class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
        >
          <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-sm">
            🍲
          </div>
          <span class="truncate">Food Lovers</span>
        </a>

        <a
          href="/groups?category=Technology"
          class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900 transition-all"
        >
          <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs shadow-sm">
            💻
          </div>
          <span class="truncate">Technology</span>
        </a>
      </div>
    </div>

    <!-- Marketplace "Sell Something" Promo Card or Social "Connect with friends" Promo Card -->
    <div v-if="currentRoute === 'marketplace'" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 shadow-sm space-y-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div>
          <h5 class="text-xs font-bold text-[#172033] dark:text-white">Sell Something</h5>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5">
            Reach more buyers and grow your business.
          </p>
        </div>
      </div>
      <a
        href="/marketplace/create"
        class="block w-full py-2.5 px-3 text-center text-xs font-bold rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white shadow-sm transition-all"
      >
        Create Listing
      </a>
    </div>

    <!-- Create Your Page Promo Card (Matching SocialHub Reference Design) -->
    <div v-else-if="currentRoute === 'pages'" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 shadow-sm space-y-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div>
          <h5 class="text-xs font-bold text-[#172033] dark:text-white">Create Your Page</h5>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5">
            Build your brand, grow your community.
          </p>
        </div>
      </div>
      <a
        href="/pages/create"
        class="block w-full py-2.5 px-3 text-center text-xs font-bold rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white shadow-sm transition-all"
      >
        Create Page
      </a>
    </div>

    <!-- Connect with Friends Promo Card (General) -->
    <div v-else class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 shadow-sm space-y-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div>
          <h5 class="text-xs font-bold text-[#172033] dark:text-white">Connect with friends</h5>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5">
            Share what's on your mind and see what friends are up to.
          </p>
        </div>
      </div>
      <a
        href="/app"
        class="block w-full py-2 px-3 text-center text-xs font-semibold rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white shadow-sm transition-colors"
      >
        Create Post
      </a>
    </div>

    <!-- Quick Footer Links -->
    <div class="px-3 text-[11px] text-slate-400 dark:text-slate-500 space-y-1">
      <div class="flex flex-wrap gap-x-2 gap-y-0.5">
        <a href="/settings/privacy" class="hover:underline">Privacy</a> ·
        <a href="/terms" class="hover:underline">Terms</a> ·
        <a href="/cookies" class="hover:underline">Cookies</a> ·
        <a href="/app" class="hover:underline">SocialHub © 2026</a>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { User } from '../types';

defineProps<{
  user: User | null;
  currentRoute?: 'feed' | 'friends' | 'notifications' | 'profile' | 'pages' | 'groups' | 'messages' | 'marketplace';
}>();

const showMore = ref(false);
</script>
