<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Friends</h3>
      <a
        v-if="friends && friends.length > 0"
        href="#"
        @click.prevent="$emit('see-all-friends')"
        class="text-xs font-semibold text-[#1877F2] hover:underline"
      >
        See All
      </a>
    </div>

    <!-- Friends Vertical List -->
    <div v-if="displayFriends.length > 0" class="space-y-3">
      <div
        v-for="friend in displayFriends"
        :key="friend.id"
        class="flex items-center justify-between gap-3 group"
      >
        <!-- Avatar & Name Info -->
        <a
          :href="'/profile/' + friend.username"
          class="flex items-center gap-3 min-w-0 flex-1 hover:opacity-90 transition-opacity"
        >
          <img
            :src="friend.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
            :alt="friend.name"
            class="w-10 h-10 rounded-full object-cover shrink-0 ring-1 ring-[#E4E7EC] dark:ring-slate-800"
          />
          <div class="min-w-0 flex-1">
            <h4 class="text-xs font-semibold text-[#172033] dark:text-slate-100 truncate group-hover:text-[#1877F2] transition-colors">
              {{ friend.name }}
            </h4>
            <p class="text-[11px] text-[#667085] dark:text-slate-400 truncate">
              {{ (friend as any).mutual_count ? (friend as any).mutual_count + ' mutual friends' : '@' + friend.username }}
            </p>
          </div>
        </a>

        <!-- Action Button & More -->
        <div class="flex items-center gap-1.5 shrink-0">
          <button
            @click.stop="handleAddFriend(friend)"
            :disabled="actionPendingId === friend.id || sentIds.includes(friend.id)"
            :class="[
              'px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors',
              sentIds.includes(friend.id)
                ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-default'
                : 'bg-[#1877F2] hover:bg-[#1666D8] text-white shadow-xs'
            ]"
          >
            <svg v-if="!sentIds.includes(friend.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>{{ sentIds.includes(friend.id) ? 'Sent' : 'Add Friend' }}</span>
          </button>

          <!-- Dropdown / More -->
          <button
            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            title="More Options"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <circle cx="5" cy="10" r="1.5" />
              <circle cx="10" cy="10" r="1.5" />
              <circle cx="15" cy="10" r="1.5" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="text-xs text-[#667085] py-4 text-center">
      No friends to show.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { User } from '../../types';

const props = withDefaults(
  defineProps<{
    friends: User[];
    currentUserId?: number;
  }>(),
  {
    friends: () => [],
  }
);

defineEmits<{
  (e: 'see-all-friends'): void;
}>();

const actionPendingId = ref<number | null>(null);
const sentIds = ref<number[]>([]);

const displayFriends = computed(() => {
  return props.friends.slice(0, 4);
});

const handleAddFriend = async (friend: User) => {
  if (sentIds.value.includes(friend.id)) return;
  actionPendingId.value = friend.id;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/request/${friend.id}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      sentIds.value.push(friend.id);
    }
  } catch (err) {
    console.error(err);
  } finally {
    actionPendingId.value = null;
  }
};
</script>
