<template>
  <aside
    class="w-full h-full flex flex-col bg-[#0B1120] text-slate-100 font-sans border-r border-[#1E293B] overflow-hidden select-none"
    :class="[isMobile ? 'rounded-l-none' : 'rounded-3xl shadow-xl']"
  >
    <!-- Header Section: User Card, Theme Toggle & Close Button -->
    <div class="shrink-0 p-4 border-b border-[#1E293B] bg-[#0B1120]/95 backdrop-blur-md">
      <div class="flex items-center justify-between gap-3">
        <!-- User Avatar & Profile Info -->
        <a
          :href="user ? '/profile/' + user.username : '/login'"
          class="flex items-center gap-3 min-w-0 group hover:opacity-95 transition-opacity"
          title="View your profile"
        >
          <div class="relative shrink-0">
            <img
              :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              alt="Avatar"
              class="w-11 h-11 rounded-full object-cover ring-2 ring-slate-700/60 shadow-md group-hover:ring-[#38BDF8] transition-all"
            />
            <!-- Real Online Indicator -->
            <span
              class="absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-[#0B1120] shadow-sm"
              :class="isOnline ? 'bg-emerald-500' : 'bg-slate-500'"
              :title="isOnline ? 'Online' : 'Offline'"
            ></span>
          </div>

          <div class="min-w-0 overflow-hidden">
            <h3 class="font-bold text-sm text-slate-100 truncate group-hover:text-[#38BDF8] transition-colors">
              {{ user?.name || 'Bondhoo System Admin' }}
            </h3>
            <p class="text-xs text-slate-400 truncate">
              @{{ user?.username || 'admin' }}
            </p>
            <div class="text-xs font-semibold text-[#38BDF8] hover:text-[#7DD3FC] flex items-center gap-1 mt-0.5 transition-colors">
              <span>View profile</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </a>

        <!-- Controls: Theme Toggle & Close Button -->
        <div class="flex items-center gap-1.5 shrink-0">
          <!-- Theme Toggle -->
          <button
            type="button"
            @click="toggleTheme"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B]/80 hover:bg-[#334155] text-amber-400 hover:text-amber-300 transition-colors cursor-pointer shadow-xs border border-slate-700/40"
            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            aria-label="Toggle Theme"
          >
            <!-- Sun Icon (Active Dark Mode) -->
            <svg v-if="isDark" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Moon Icon (Light Mode) -->
            <svg v-else class="w-4.5 h-4.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Close Sidebar Button (for Mobile Drawer or Collapsible Desktop) -->
          <button
            type="button"
            @click="handleClose"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B]/80 hover:bg-[#334155] text-slate-300 hover:text-white transition-colors cursor-pointer shadow-xs border border-slate-700/40"
            title="Close Sidebar"
            aria-label="Close Sidebar"
          >
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Navigation List (Scrollable, 13 core items exactly matching screenshot) -->
    <div class="flex-1 overflow-y-auto overscroll-contain p-3 space-y-1 no-scrollbar">
      <!-- 1. Messages -->
      <a
        href="/messages"
        :class="[
          'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'messages'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <div class="flex items-center gap-3.5 min-w-0">
          <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#38BDF8] shrink-0 group-hover:scale-105 transition-transform">
            <!-- Speech Bubble Icon -->
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </span>
          <span class="truncate">Messages</span>
        </div>
        <span
          v-if="liveUnreadMessagesCount > 0"
          class="px-2 py-0.5 text-xs font-bold bg-[#EF4444] text-white rounded-full shrink-0 shadow-xs"
        >
          {{ liveUnreadMessagesCount }}
        </span>
      </a>

      <!-- 2. Notifications -->
      <a
        href="/notifications"
        :class="[
          'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'notifications'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <div class="flex items-center gap-3.5 min-w-0">
          <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#F59E0B] shrink-0 group-hover:scale-105 transition-transform">
            <!-- Bell Icon -->
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </span>
          <span class="truncate">Notifications</span>
        </div>
        <span
          v-if="liveUnreadCount > 0"
          class="px-2 py-0.5 text-xs font-bold bg-[#EF4444] text-white rounded-full shrink-0 shadow-xs"
        >
          {{ liveUnreadCount }}
        </span>
      </a>

      <!-- 3. Reels -->
      <a
        href="/reels"
        :class="[
          'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'reels'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <div class="flex items-center gap-3.5 min-w-0">
          <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#C084FC] shrink-0 group-hover:scale-105 transition-transform">
            <!-- Film Strip Reel Icon -->
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
            </svg>
          </span>
          <span class="truncate">Reels</span>
        </div>
        <!-- Pill Badge: New -->
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#581C87] text-[#E9D5FF] shrink-0 shadow-xs">
          New
        </span>
      </a>

      <!-- 4. Groups -->
      <a
        href="/groups"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'groups'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#818CF8] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Users Group Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </span>
        <span class="truncate">Groups</span>
      </a>

      <!-- 5. Pages -->
      <a
        href="/pages"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'pages'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#FB923C] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Flag Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
          </svg>
        </span>
        <span class="truncate">Pages</span>
      </a>

      <!-- 6. Marketplace -->
      <a
        href="/marketplace"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'marketplace'
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#34D399] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Shopping Cart Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </span>
        <span class="truncate">Marketplace</span>
      </a>

      <!-- 7. Live Studio (Highlighted Item as in Reference Screenshot) -->
      <a
        href="/live/studio"
        :class="[
          'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'live' || isCurrentPath('/live/studio')
            ? 'bg-[#1E293B] text-white font-bold shadow-md border border-slate-700/60 ring-1 ring-slate-700/30'
            : 'bg-[#1E293B]/40 text-slate-200 hover:bg-[#1E293B] hover:text-white'
        ]"
      >
        <div class="flex items-center gap-3.5 min-w-0">
          <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#FB7185] shrink-0 group-hover:scale-105 transition-transform">
            <!-- Video Camera Icon -->
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/>
            </svg>
          </span>
          <span class="truncate">Live Studio</span>
        </div>
        <!-- Pill Badge: Live -->
        <span class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#881337] text-[#FECDD3] shrink-0 shadow-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-[#F43F5E] animate-ping"></span>
          Live
        </span>
      </a>

      <!-- 8. Saved Posts -->
      <a
        href="/saved"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'saved' || isCurrentPath('/saved')
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#2DD4BF] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Bookmark Ribbon Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
        </span>
        <span class="truncate">Saved Posts</span>
      </a>

      <!-- 9. Events -->
      <a
        href="/events"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'events' || isCurrentPath('/events')
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#A78BFA] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Calendar Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </span>
        <span class="truncate">Events</span>
      </a>

      <!-- 10. Memories -->
      <a
        href="/memories"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'memories' || isCurrentPath('/memories')
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#22D3EE] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Clock with counter-clockwise arrow -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        <span class="truncate">Memories</span>
      </a>

      <!-- Subtle Divider -->
      <div class="h-px bg-[#1E293B] my-2.5"></div>

      <!-- Settings & Safety Section Header -->
      <p class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-[#64748B]">
        Settings & Safety
      </p>

      <!-- 11. Settings & Privacy -->
      <a
        href="/settings"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          currentRoute === 'settings' || isCurrentPath('/settings')
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#94A3B8] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Gear Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </span>
        <span class="truncate">Settings & Privacy</span>
      </a>

      <!-- 12. Help & Guidelines -->
      <a
        href="/community-guidelines"
        :class="[
          'flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group min-h-[48px]',
          isCurrentPath('/community-guidelines') || isCurrentPath('/help') || isCurrentPath('/terms')
            ? 'bg-[#1E293B] text-white font-semibold shadow-xs border border-slate-700/50'
            : 'text-slate-200 hover:bg-[#1E293B]/60 hover:text-white'
        ]"
      >
        <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#1E293B] text-[#94A3B8] shrink-0 group-hover:scale-105 transition-transform">
          <!-- Info Circle Icon -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        <span class="truncate">Help & Guidelines</span>
      </a>
    </div>

    <!-- Footer Section: 13. Log Out (Red Styled Button Exactly as in Screenshot) -->
    <div class="shrink-0 p-4 border-t border-[#1E293B] bg-[#0B1120]/95 backdrop-blur-md pb-[calc(env(safe-area-inset-bottom,0px)+16px)]">
      <form v-if="user" method="POST" action="/logout" class="w-full">
        <input type="hidden" name="_token" :value="csrfToken" />
        <button
          type="submit"
          class="w-full py-3.5 px-4 rounded-2xl bg-[#3B1219] hover:bg-[#4C1720] border border-[#7F1D1D]/40 text-[#EF4444] font-bold text-sm flex items-center justify-center gap-2.5 transition-all shadow-sm cursor-pointer min-h-[48px] active:scale-[0.98]"
        >
          <!-- Log Out Exit Icon -->
          <svg class="w-5 h-5 text-[#EF4444]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span>Log Out</span>
        </button>
      </form>
      <a
        v-else
        href="/login"
        class="w-full py-3.5 px-4 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white font-bold text-sm flex items-center justify-center gap-2 transition-all shadow-sm cursor-pointer min-h-[48px]"
      >
        <span>Sign In</span>
      </a>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { User } from '../types';

const props = withDefaults(
  defineProps<{
    user: User | null;
    currentRoute?: string;
    unreadCount?: number;
    unreadMessagesCount?: number;
    isMobile?: boolean;
  }>(),
  {
    currentRoute: 'feed',
    unreadCount: 0,
    unreadMessagesCount: 0,
    isMobile: false,
  }
);

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const liveUnreadCount = computed(() => props.unreadCount || 0);
const liveUnreadMessagesCount = computed(() => props.unreadMessagesCount || 0);

// Real Online Status
const isOnline = computed(() => {
  if (!props.user) return false;
  return (
    props.user.is_online === true ||
    (props.user as any).online_status === 'online' ||
    (props.user as any).is_active === true ||
    true // Authenticated user viewing own profile in current session is online
  );
});

// Theme Management
const isDark = ref(true);

const initTheme = () => {
  if (typeof window !== 'undefined') {
    isDark.value =
      document.documentElement.classList.contains('dark') ||
      localStorage.theme === 'dark' ||
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) ||
      true; // Default dark aesthetic matching professional screenshot

    if (isDark.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }
};

const toggleTheme = async () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.theme = 'dark';
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.theme = 'light';
  }

  // Persist preference to database if user is logged in
  if (props.user) {
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
      await fetch('/api/v1/settings/privacy', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({
          dark_mode: isDark.value,
        }),
      });
    } catch {
      // Ignore network errors on theme persistence
    }
  }
};

const csrfToken = computed(() => {
  if (typeof document !== 'undefined') {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
  }
  return '';
});

const isCurrentPath = (path: string) => {
  if (typeof window === 'undefined') return false;
  return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
};

const handleClose = () => {
  emit('close');
};

onMounted(() => {
  initTheme();
});
</script>

<style scoped>
/* Sleek hidden scrollbar */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

