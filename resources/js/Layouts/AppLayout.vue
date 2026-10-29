<template>
  <div class="min-h-screen w-full overflow-x-hidden bg-[#F5F7FB] dark:bg-slate-950 text-[#172033] dark:text-slate-100 flex flex-col font-sans transition-colors selection:bg-[#1877F2] selection:text-white antialiased">
    <!-- Top Navigation Bar & Mobile Bottom Bar -->
    <Navbar
      :user="currentUser"
      :current-route="currentRoute"
      :unread-count="unreadNotificationsCount"
      :unread-messages-count="unreadMessagesCount"
    />

    <!-- Flash Notifications Banner -->
    <div v-if="flashSuccess" class="max-w-[1920px] mx-auto w-full px-3 sm:px-6 lg:px-8 mt-4">
      <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 rounded-2xl flex items-center justify-between text-sm shadow-sm animate-fade-in">
        <div class="flex items-center gap-2.5">
          <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span class="font-medium">{{ flashSuccess }}</span>
        </div>
        <button @click="dismissFlash" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-300">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Main Content Area: Unified Responsive Layout Shell -->
    <div :class="['flex-1 mx-auto w-full max-w-[1920px]', currentRoute === 'reels' ? 'p-0 pb-16 md:px-6 lg:px-8 md:py-5 md:pb-8' : 'px-3 sm:px-6 lg:px-8 py-3.5 sm:py-5 pb-24 md:pb-8']">
      <div :class="['flex items-start justify-between', currentRoute === 'reels' ? 'gap-0 md:gap-6' : 'gap-4 lg:gap-6']">
        <!-- Column 1: Left Navigation Sidebar (Desktop) - Anchored at the exact same position across ALL pages -->
        <SidebarLeft :user="currentUser" :current-route="currentRoute" />

        <!-- Column 2: Center Feed / Main Body -->
        <main class="flex-1 min-w-0 flex justify-center w-full">
          <div :class="['w-full', !fullWidth ? 'max-w-[680px]' : 'max-w-full']">
            <slot />
          </div>
        </main>

        <!-- Column 3: Right Sidebar (People You May Know, Contacts & Events) -->
        <SidebarRight
          v-if="!hideRightSidebar"
          :pending-requests="internalPendingRequests"
          :contacts="contacts"
          :suggestions="suggestions"
          :upcoming-events="upcomingEvents"
          @accept-request="handleAcceptRequest"
          @reject-request="handleRejectRequest"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Navbar from '../Components/Navbar.vue';
import SidebarLeft from '../Components/SidebarLeft.vue';
import SidebarRight from '../Components/SidebarRight.vue';
import type { User, SharedProps, SuggestedFriend, UpcomingEvent } from '../types';

const props = withDefaults(
  defineProps<{
    currentRoute?: 'feed' | 'friends' | 'notifications' | 'profile' | 'pages' | 'messages' | 'groups' | 'marketplace' | 'reels';
    unreadCount?: number;
    unreadMessagesCount?: number;
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
    hideRightSidebar?: boolean;
    fullWidth?: boolean;
  }>(),
  {
    hideRightSidebar: false,
    fullWidth: false,
    unreadCount: 0,
    unreadMessagesCount: 0,
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
  }
);

const page = usePage<SharedProps>();
const currentUser = computed<User | null>(() => page.props.auth?.user || null);

const unreadNotificationsCount = ref(props.unreadCount);
const internalPendingRequests = ref<any[]>([...props.pendingRequests]);

const flashDismissed = ref(false);
const flashSuccess = computed(() => {
  if (flashDismissed.value) return null;
  return page.props.flash?.success || null;
});

const dismissFlash = () => {
  flashDismissed.value = true;
};

const handleAcceptRequest = async (senderId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${senderId}/accept`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      internalPendingRequests.value = internalPendingRequests.value.filter(
        (r) => (r.user_id || r.sender?.id) !== senderId
      );
    }
  } catch (err) {
    console.error('Failed to accept friend request:', err);
  }
};

const handleRejectRequest = async (senderId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${senderId}/decline`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      internalPendingRequests.value = internalPendingRequests.value.filter(
        (r) => (r.user_id || r.sender?.id) !== senderId
      );
    }
  } catch (err) {
    console.error('Failed to decline friend request:', err);
  }
};
</script>
