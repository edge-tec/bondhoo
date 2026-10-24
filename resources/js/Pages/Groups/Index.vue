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
    <!-- Header with Create Group Button -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Groups
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Find and join communities that share your passions and interests.
        </p>
      </div>

      <a
        href="/groups/create"
        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all self-start sm:self-auto"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span>Create Group</span>
      </a>
    </div>

    <!-- Navigation Tabs: Discover Groups | My Groups | Invitations -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 mb-6 pb-2">
      <div class="flex items-center gap-2 sm:gap-4 overflow-x-auto no-scrollbar">
        <button
          type="button"
          @click="activeSubTab = 'discover'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
          :class="activeSubTab === 'discover' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
        >
          Discover Groups
          <span v-if="activeSubTab === 'discover'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>

        <button
          type="button"
          @click="activeSubTab = 'my_groups'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0"
          :class="activeSubTab === 'my_groups' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
        >
          My Groups ({{ myGroups.length }})
          <span v-if="activeSubTab === 'my_groups'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>

        <button
          type="button"
          @click="activeSubTab = 'invitations'"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative shrink-0 flex items-center gap-1.5"
          :class="activeSubTab === 'invitations' ? 'text-[#1877F2]' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
        >
          <span>Invitations</span>
          <span
            v-if="invitationList.length > 0"
            class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500 text-white"
          >
            {{ invitationList.length }}
          </span>
          <span v-if="activeSubTab === 'invitations'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="hidden md:block relative w-64">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search groups..."
          class="w-full pl-9 pr-4 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
        />
      </div>
    </div>

    <!-- Category Filter Pills -->
    <div v-if="activeSubTab === 'discover'" class="flex items-center gap-2 overflow-x-auto pb-4 mb-4 no-scrollbar">
      <button
        v-for="cat in categoryFilters"
        :key="cat"
        type="button"
        @click="selectedCategory = cat"
        class="px-4 py-1.5 rounded-full text-xs font-semibold shrink-0 transition-all"
        :class="selectedCategory === cat ? 'bg-[#1877F2] text-white shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
      >
        {{ cat }}
      </button>
    </div>

    <!-- INVITATIONS VIEW -->
    <div v-if="activeSubTab === 'invitations'" class="space-y-4">
      <div v-if="invitationList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="invite in invitationList"
          :key="invite.id"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden p-5 flex flex-col justify-between"
        >
          <div class="flex items-center gap-3 mb-3">
            <img
              :src="invite.group?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
              :alt="invite.group?.name"
              class="w-12 h-12 rounded-2xl object-cover ring-2 ring-blue-500/20"
            />
            <div class="min-w-0">
              <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                {{ invite.group?.name }}
              </h4>
              <p class="text-xs text-slate-400">
                Invited by <span class="font-semibold text-slate-600 dark:text-slate-300">{{ invite.inviter?.name }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="acceptInvite(invite.id)"
              class="flex-1 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold transition-all shadow-sm"
            >
              Accept
            </button>
            <button
              type="button"
              @click="declineInvite(invite.id)"
              class="flex-1 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all"
            >
              Decline
            </button>
          </div>
        </div>
      </div>

      <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">No Pending Invitations</h4>
        <p class="text-xs sm:text-sm mt-1">When friends invite you to join groups, they'll appear right here.</p>
      </div>
    </div>

    <!-- GROUPS GRID (DISCOVER & MY GROUPS) -->
    <div v-else>
      <div v-if="displayedGroups.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="grp in displayedGroups"
          :key="grp.id"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between group hover:shadow-md transition-all"
        >
          <div>
            <!-- Cover Photo Thumbnail -->
            <div class="h-28 w-full bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
              <img
                :src="grp.cover_image_url || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80'"
                :alt="grp.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <span
                class="absolute top-2.5 right-2.5 text-[10px] font-bold px-2 py-0.5 rounded-full capitalize backdrop-blur-md"
                :class="grp.privacy === 'public' ? 'bg-black/50 text-white' : 'bg-amber-500/80 text-white'"
              >
                {{ grp.privacy }}
              </span>
            </div>

            <!-- Avatar & Details -->
            <div class="p-5 pt-0 relative">
              <div class="w-14 h-14 rounded-2xl ring-4 ring-white dark:ring-slate-900 bg-[#1877F2] flex items-center justify-center text-white -mt-7 mb-3 shadow-md overflow-hidden">
                <img
                  v-if="grp.avatar_url"
                  :src="grp.avatar_url"
                  :alt="grp.name"
                  class="w-full h-full object-cover"
                />
                <svg v-else class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>

              <a :href="'/groups/' + (grp.slug || grp.id)" class="block">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white truncate group-hover:text-[#1877F2] transition-colors">
                  {{ grp.name }}
                </h3>
              </a>

              <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mt-1">
                <span>{{ grp.members_count || 1 }} members</span>
                <span v-if="grp.category">•</span>
                <span v-if="grp.category" class="text-[#1877F2]">{{ grp.category }}</span>
              </div>

              <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 mt-2 leading-relaxed">
                {{ grp.description || 'Join this group to connect and converse with community members.' }}
              </p>
            </div>
          </div>

          <!-- Bottom Action Button -->
          <div class="p-5 pt-0">
            <a
              :href="'/groups/' + (grp.slug || grp.id)"
              class="w-full py-2.5 rounded-2xl bg-blue-50 hover:bg-[#1877F2] text-[#1877F2] hover:text-white dark:bg-blue-950/40 dark:hover:bg-[#1877F2] text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm"
            >
              <span>View Group</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h4 class="text-base font-bold text-slate-700 dark:text-slate-300">
          {{ activeSubTab === 'my_groups' ? 'You haven’t joined any groups yet' : 'No groups found' }}
        </h4>
        <p class="text-xs sm:text-sm mt-1">
          {{ activeSubTab === 'my_groups' ? 'Explore popular groups or create one to connect with others.' : 'Try adjusting your search or category filters.' }}
        </p>
        <div class="mt-4">
          <a
            href="/groups/create"
            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs font-bold shadow-md shadow-blue-500/20"
          >
            Create a Group
          </a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../../types';

const props = withDefaults(
  defineProps<{
    groups: any[];
    myGroups: any[];
    invitations: any[];
    filters?: { q?: string; category?: string; sort?: string };
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
    unreadCount?: number;
    unreadMessagesCount?: number;
  }>(),
  {
    groups: () => [],
    myGroups: () => [],
    invitations: () => [],
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
    unreadCount: 0,
    unreadMessagesCount: 0,
  }
);

const activeSubTab = ref<'discover' | 'my_groups' | 'invitations'>('discover');
const searchQuery = ref<string>('');
const selectedCategory = ref<string>('All');
const invitationList = ref<any[]>([...props.invitations]);

const categoryFilters = [
  'All',
  'Technology',
  'Education',
  'Business',
  'Entertainment',
  'Sports',
  'Gaming',
  'Photography',
  'Travel',
  'Food',
  'Health and Fitness',
  'Local Community',
];

const displayedGroups = computed(() => {
  let list = activeSubTab.value === 'my_groups' ? props.myGroups : props.groups;

  if (selectedCategory.value !== 'All' && activeSubTab.value === 'discover') {
    list = list.filter(g => g.category?.toLowerCase() === selectedCategory.value.toLowerCase());
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(g =>
      g.name?.toLowerCase().includes(q) || g.description?.toLowerCase().includes(q)
    );
  }

  return list;
});

const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

const acceptInvite = async (invitationId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/invitations/${invitationId}/accept`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      invitationList.value = invitationList.value.filter(i => i.id !== invitationId);
      alert('Joined group successfully!');
    } else {
      alert(data.message || 'Failed to accept invitation.');
    }
  } catch (err: any) {
    alert('Failed to accept invitation.');
  }
};

const declineInvite = async (invitationId: number) => {
  try {
    const csrf = getCsrfToken();
    const res = await fetch(`/api/v1/groups/invitations/${invitationId}/decline`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
    });
    if (res.ok) {
      invitationList.value = invitationList.value.filter(i => i.id !== invitationId);
    }
  } catch (err: any) {
    alert('Failed to decline invitation.');
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
</style>
