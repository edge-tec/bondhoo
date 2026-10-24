<template>
  <AppLayout
    current-route="friends"
    :unread-count="unreadCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :hide-right-sidebar="true"
    full-width
  >
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm mb-6">
      <!-- Header & Tab Navigation -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E4E7EC] dark:border-slate-800 pb-4">
        <div>
          <h1 class="text-2xl font-bold text-[#172033] dark:text-slate-100">Friends</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage your connections, pending requests, and discover new people.</p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="activeTab = 'requests'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold relative transition-all',
              activeTab === 'requests'
                ? 'bg-[#1877F2] text-white shadow-sm'
                : 'bg-[#F5F7FB] dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            Requests
            <span
              v-if="internalRequests.length > 0"
              class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] bg-[#F04438] text-white"
            >
              {{ internalRequests.length }}
            </span>
          </button>

          <button
            @click="activeTab = 'friends'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all',
              activeTab === 'friends'
                ? 'bg-[#1877F2] text-white shadow-sm'
                : 'bg-[#F5F7FB] dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            All Friends ({{ internalFriends.length }})
          </button>

          <button
            @click="activeTab = 'suggestions'"
            :class="[
              'px-4 py-2 rounded-xl text-xs font-bold transition-all',
              activeTab === 'suggestions'
                ? 'bg-[#1877F2] text-white shadow-sm'
                : 'bg-[#F5F7FB] dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            Suggestions
          </button>
        </div>
      </div>

      <!-- Search Input Filter (for All Friends Tab) -->
      <div v-if="activeTab === 'friends'" class="mt-4 max-w-sm">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search your friends..."
            class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800/80 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 text-slate-800 dark:text-slate-100 placeholder-slate-400"
          />
        </div>
      </div>

      <!-- Tab Content 1: Friend Requests -->
      <div v-if="activeTab === 'requests'" class="mt-6">
        <div v-if="internalRequests.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="req in internalRequests"
            :key="req.id"
            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/70 dark:border-slate-700 flex flex-col justify-between"
          >
            <div class="flex items-center gap-3 mb-4">
              <a :href="'/profile/' + (req.sender?.username || 'user')">
                <img
                  :src="req.sender?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20"
                />
              </a>
              <div class="min-w-0 flex-1">
                <a
                  :href="'/profile/' + (req.sender?.username || 'user')"
                  class="font-semibold text-sm text-slate-800 dark:text-slate-100 hover:text-blue-600 truncate block"
                >
                  {{ req.sender?.name }}
                </a>
                <p class="text-xs text-slate-400 truncate">@{{ req.sender?.username }}</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="acceptRequest(req.user_id || req.sender?.id)"
                class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors"
              >
                Confirm
              </button>
              <button
                @click="declineRequest(req.user_id || req.sender?.id)"
                class="flex-1 py-2 px-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>

        <EmptyState
          v-else
          title="No Pending Friend Requests"
          description="You don't have any pending friend requests right now."
        />
      </div>

      <!-- Tab Content 2: All Friends Grid -->
      <div v-else-if="activeTab === 'friends'" class="mt-6">
        <div v-if="filteredFriends.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="friend in filteredFriends"
            :key="friend.id"
            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/70 dark:border-slate-700 flex items-center justify-between gap-3 hover:shadow-sm transition-all"
          >
            <div class="flex items-center gap-3 min-w-0">
              <a :href="'/profile/' + friend.username">
                <img
                  :src="friend.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20 shrink-0"
                />
              </a>
              <div class="min-w-0">
                <a
                  :href="'/profile/' + friend.username"
                  class="font-semibold text-sm text-slate-800 dark:text-slate-100 hover:text-blue-600 truncate block"
                >
                  {{ friend.name }}
                </a>
                <p class="text-xs text-slate-400 truncate">@{{ friend.username }}</p>
              </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
              <a
                :href="'/profile/' + friend.username"
                class="p-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-blue-600 text-xs"
                title="View Profile"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </a>
              <button
                @click="removeFriend(friend.id)"
                class="p-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-red-600 text-xs transition-colors"
                title="Unfriend"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12h-6" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <EmptyState
          v-else
          title="No Friends Found"
          :description="searchQuery ? 'No friends match your search query.' : 'You have not added any friends yet.'"
        />
      </div>

      <!-- Tab Content 3: Suggestions -->
      <div v-else-if="activeTab === 'suggestions'" class="mt-6">
        <div v-if="internalSuggestions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="sugg in internalSuggestions"
            :key="sugg.id"
            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/70 dark:border-slate-700 flex flex-col justify-between"
          >
            <div class="flex items-center gap-3 mb-4">
              <a :href="'/profile/' + sugg.username">
                <img
                  :src="sugg.avatar_url || 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20"
                />
              </a>
              <div class="min-w-0 flex-1">
                <a
                  :href="'/profile/' + sugg.username"
                  class="font-semibold text-sm text-slate-800 dark:text-slate-100 hover:text-blue-600 truncate block"
                >
                  {{ sugg.name }}
                </a>
                <p class="text-xs text-slate-400 truncate">@{{ sugg.username }}</p>
              </div>
            </div>

            <button
              @click="addSuggestedFriend(sugg.id)"
              :disabled="sugg.sent"
              :class="[
                'w-full py-2 px-3 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors',
                sugg.sent
                  ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 cursor-not-allowed'
                  : 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm'
              ]"
            >
              <svg v-if="!sugg.sent" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              {{ sugg.sent ? 'Request Sent' : 'Add Friend' }}
            </button>
          </div>
        </div>

        <EmptyState
          v-else
          title="No Suggestions Right Now"
          description="Check back later for new friend recommendations."
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import EmptyState from '../Components/EmptyState.vue';
import type { User } from '../types';

interface FriendsProps {
  requests?: any[];
  friends?: User[];
  suggestions?: (User & { sent?: boolean })[];
  unreadCount?: number;
  pendingRequests?: any[];
  contacts?: User[];
}

const props = withDefaults(defineProps<FriendsProps>(), {
  requests: () => [],
  friends: () => [],
  suggestions: () => [],
  unreadCount: 0,
  pendingRequests: () => [],
  contacts: () => [],
});

const activeTab = ref<'requests' | 'friends' | 'suggestions'>('requests');
const searchQuery = ref('');

const internalRequests = ref<any[]>([...props.requests]);
const internalFriends = ref<User[]>([...props.friends]);
const internalSuggestions = ref<(User & { sent?: boolean })[]>([...props.suggestions]);

const filteredFriends = computed(() => {
  if (!searchQuery.value.trim()) return internalFriends.value;
  const q = searchQuery.value.toLowerCase();
  return internalFriends.value.filter(
    (f) => f.name.toLowerCase().includes(q) || f.username.toLowerCase().includes(q)
  );
});

const acceptRequest = async (senderId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/accept/${senderId}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      const acceptedReq = internalRequests.value.find((r) => (r.user_id || r.sender?.id) === senderId);
      internalRequests.value = internalRequests.value.filter((r) => (r.user_id || r.sender?.id) !== senderId);
      if (acceptedReq && acceptedReq.sender) {
        internalFriends.value.unshift(acceptedReq.sender);
      }
    }
  } catch (err) {
    console.error(err);
  }
};

const declineRequest = async (senderId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/decline/${senderId}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      internalRequests.value = internalRequests.value.filter((r) => (r.user_id || r.sender?.id) !== senderId);
    }
  } catch (err) {
    console.error(err);
  }
};

const removeFriend = async (friendId: number) => {
  if (!confirm('Are you sure you want to remove this friend?')) return;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${friendId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      internalFriends.value = internalFriends.value.filter((f) => f.id !== friendId);
    }
  } catch (err) {
    console.error(err);
  }
};

const addSuggestedFriend = async (userId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/request/${userId}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      const sugg = internalSuggestions.value.find((s) => s.id === userId);
      if (sugg) {
        sugg.sent = true;
      }
    }
  } catch (err) {
    console.error(err);
  }
};
</script>
