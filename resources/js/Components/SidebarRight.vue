<template>
  <aside class="hidden xl:block w-80 2xl:w-84 shrink-0 space-y-4 sticky top-20 self-start max-h-[calc(100vh-5.5rem)] overflow-y-auto no-scrollbar">
    <!-- 1. People You May Know / Friend Requests Widget -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 shadow-sm">
      <div class="flex items-center justify-between mb-3.5">
        <h3 class="text-sm font-bold text-[#172033] dark:text-slate-100">
          {{ pendingRequests && pendingRequests.length > 0 ? 'Friend Requests' : 'People You May Know' }}
        </h3>
        <a href="/friends" class="text-xs font-semibold text-[#1877F2] hover:underline">See All</a>
      </div>

      <!-- Pending Friend Requests if any exist -->
      <div v-if="pendingRequests && pendingRequests.length > 0" class="space-y-3 mb-2">
        <div
          v-for="req in pendingRequests.slice(0, 3)"
          :key="req.id"
          class="flex flex-col gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800"
        >
          <div class="flex items-center gap-2.5">
            <img
              :src="req.sender?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              class="w-10 h-10 rounded-full object-cover"
              alt="Avatar"
            />
            <div class="min-w-0 flex-1">
              <h4 class="text-xs font-bold text-[#172033] dark:text-slate-100 truncate">
                {{ req.sender?.name }}
              </h4>
              <p class="text-[11px] text-slate-400 truncate">Sent you a request</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="$emit('accept-request', req.user_id || req.sender?.id)"
              class="flex-1 py-1.5 px-3 bg-[#1877F2] hover:bg-[#1666D8] text-white rounded-lg text-xs font-semibold shadow-sm transition-colors"
            >
              Confirm
            </button>
            <button
              @click="$emit('reject-request', req.user_id || req.sender?.id)"
              class="flex-1 py-1.5 px-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Suggested Friends (People You May Know) -->
      <div v-else class="space-y-3">
        <div
          v-for="person in activeSuggestions"
          :key="person.id || person.name"
          class="flex items-center justify-between gap-2"
        >
          <a :href="person.username ? '/profile/' + person.username : '#'" class="flex items-center gap-2.5 min-w-0 group">
            <img
              :src="person.avatar_url || person.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              class="w-10 h-10 rounded-full object-cover shrink-0 ring-1 ring-blue-500/20 group-hover:scale-105 transition-transform"
              :alt="person.name"
            />
            <div class="min-w-0">
              <h4 class="text-xs font-bold text-[#172033] dark:text-slate-100 truncate group-hover:text-[#1877F2] transition-colors">
                {{ person.name }}
              </h4>
              <p class="text-[11px] text-slate-400 truncate">{{ person.mutual || 'Suggested for you' }}</p>
            </div>
          </a>
          
          <div class="flex items-center gap-1 shrink-0">
            <button
              type="button"
              @click="handleAddFriend(person)"
              :disabled="person.added || person.isSending"
              :class="[
                'px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition-all',
                person.added
                  ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-default'
                  : 'bg-[#E7F3FF] text-[#1877F2] hover:bg-[#1877F2] hover:text-white'
              ]"
            >
              <svg v-if="!person.added" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              <span>{{ person.added ? 'Sent' : (person.isSending ? 'Sending...' : 'Add Friend') }}</span>
            </button>
            <a :href="person.username ? '/profile/' + person.username : '#'" class="text-slate-400 hover:text-slate-600 p-1" title="View profile">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Online Contacts Widget -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-[#172033] dark:text-slate-100">Online Contacts</h3>
        <a href="/messenger" class="text-slate-400 hover:text-slate-600 p-1" title="Open Messenger">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </a>
      </div>

      <!-- Filter Contacts Search -->
      <div class="relative mb-3">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input
          v-model="contactQuery"
          type="text"
          placeholder="Search contacts..."
          class="w-full pl-8 pr-3 py-1.5 bg-[#F5F7FB] dark:bg-slate-800 text-xs rounded-lg border-none focus:ring-1 focus:ring-[#1877F2] text-[#172033] dark:text-slate-100 placeholder-slate-400 transition-all outline-none"
        />
      </div>

      <!-- Contacts List -->
      <div class="space-y-1 max-h-64 overflow-y-auto no-scrollbar">
        <div
          v-for="friend in filteredContacts"
          :key="friend.id || friend.name"
          @click="openChat(friend)"
          class="flex items-center justify-between p-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors group cursor-pointer"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="relative shrink-0">
              <img
                :src="friend.avatar_url || friend.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                alt="Avatar"
                class="w-8 h-8 rounded-full object-cover"
              />
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-[#12B76A] ring-2 ring-white dark:ring-slate-900"></span>
            </div>
            <div class="min-w-0">
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-[#1877F2] transition-colors truncate block">
                {{ friend.name }}
              </span>
              <span class="text-[10px] text-slate-400 block">Online</span>
            </div>
          </div>
          <button type="button" class="text-slate-400 hover:text-[#1877F2] opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Message">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- 3. Upcoming Events Widget -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 shadow-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-[#172033] dark:text-slate-100">Upcoming Events</h3>
        <a href="/events" class="text-xs font-semibold text-[#1877F2] hover:underline">See All</a>
      </div>

      <div class="space-y-2">
        <a
          v-for="ev in activeEvents"
          :key="ev.id"
          :href="'/events/' + ev.id"
          class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group block"
        >
          <!-- Date Badge -->
          <div class="w-11 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex flex-col items-center justify-center shrink-0">
            <span class="text-[9px] font-bold text-[#F04438] uppercase tracking-wider">{{ ev.month }}</span>
            <span class="text-sm font-black text-[#172033] dark:text-white leading-none">{{ ev.day }}</span>
          </div>

          <!-- Event Details -->
          <div class="min-w-0 flex-1">
            <h4 class="text-xs font-bold text-[#172033] dark:text-slate-100 group-hover:text-[#1877F2] transition-colors truncate">
              {{ ev.title }}
            </h4>
            <p class="text-[11px] text-slate-400 truncate">{{ ev.time_formatted }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ ev.address }}</p>
          </div>

          <!-- Event Thumbnail -->
          <img
            :src="ev.cover_image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=150&q=80'"
            alt="Event"
            class="w-11 h-11 rounded-xl object-cover shrink-0"
          />
        </a>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../types';

const props = defineProps<{
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
}>();

defineEmits<{
  (e: 'accept-request', id: number): void;
  (e: 'reject-request', id: number): void;
}>();

const contactQuery = ref('');

const activeSuggestions = ref<any[]>([]);

watch(
  () => props.suggestions,
  (newVal) => {
    if (newVal && newVal.length > 0) {
      activeSuggestions.value = newVal.map((s) => ({ ...s, isSending: false }));
    } else {
      activeSuggestions.value = [
        {
          id: 101,
          name: 'Emily Johnson',
          username: 'emilyjohnson',
          mutual: '12 mutual friends',
          avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80',
          added: false,
          isSending: false,
        },
        {
          id: 102,
          name: 'Michael Brown',
          username: 'michaelbrown',
          mutual: '8 mutual friends',
          avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
          added: false,
          isSending: false,
        },
        {
          id: 103,
          name: 'Olivia Davis',
          username: 'oliviadavis',
          mutual: '15 mutual friends',
          avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
          added: false,
          isSending: false,
        },
        {
          id: 104,
          name: 'William Taylor',
          username: 'williamtaylor',
          mutual: '5 mutual friends',
          avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
          added: false,
          isSending: false,
        },
      ];
    }
  },
  { immediate: true }
);

const handleAddFriend = async (person: any) => {
  person.isSending = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${person.id}/request`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      person.added = true;
    } else {
      const data = await res.json().catch(() => ({}));
      // If already pending or sent
      if (res.status === 409 || data.message?.includes('already')) {
        person.added = true;
      }
    }
  } catch (err) {
    console.error('Failed to send friend request:', err);
  } finally {
    person.isSending = false;
  }
};

const defaultContacts = [
  { id: 201, name: 'Sarah Wilson', avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=150&q=80' },
  { id: 202, name: 'James Miller', avatar: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80' },
  { id: 203, name: 'Emma Davis', avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80' },
  { id: 204, name: 'Daniel Smith', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80' },
  { id: 205, name: 'Sophia Wilson', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80' },
];

const allContactsList = computed(() => {
  if (props.contacts && props.contacts.length > 0) {
    return props.contacts.map((c) => ({
      id: c.id,
      name: c.name,
      avatar: c.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
    }));
  }
  return defaultContacts;
});

const filteredContacts = computed(() => {
  if (!contactQuery.value.trim()) return allContactsList.value;
  return allContactsList.value.filter((c) =>
    c.name.toLowerCase().includes(contactQuery.value.toLowerCase())
  );
});

const defaultEvents: UpcomingEvent[] = [
  {
    id: 1,
    title: 'Tech Conference 2026',
    month: 'JUN',
    day: '14',
    time_formatted: 'Sat, 10:00 AM - 4:00 PM',
    address: 'City Convention Center',
    cover_image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=150&q=80',
  },
];

const activeEvents = computed(() => {
  if (props.upcomingEvents && props.upcomingEvents.length > 0) {
    return props.upcomingEvents;
  }
  return defaultEvents;
});

const openChat = (friend: any) => {
  if (friend.id) {
    window.location.href = `/messenger?user=${friend.id}`;
  } else {
    window.location.href = '/messenger';
  }
};
</script>
