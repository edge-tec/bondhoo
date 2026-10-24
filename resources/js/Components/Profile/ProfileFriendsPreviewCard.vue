<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Friends</h3>
        <p v-if="mutualCount > 0" class="text-xs text-[#667085] dark:text-slate-400 mt-0.5">
          {{ mutualCount }} mutual {{ mutualCount === 1 ? 'friend' : 'friends' }}
        </p>
        <p v-else-if="totalFriends > 0" class="text-xs text-[#667085] dark:text-slate-400 mt-0.5">
          {{ totalFriends }} {{ totalFriends === 1 ? 'friend' : 'friends' }}
        </p>
      </div>

      <a
        v-if="friends && friends.length > 0"
        :href="seeAllUrl || '#'"
        @click.prevent="$emit('see-all')"
        class="text-xs font-semibold text-[#1877F2] hover:underline cursor-pointer"
      >
        See All
      </a>
    </div>

    <!-- 2x3 Grid of 6 Friends -->
    <div v-if="previewFriends.length > 0" class="grid grid-cols-3 gap-3">
      <a
        v-for="friend in previewFriends"
        :key="friend.id"
        :href="'/profile/' + friend.username"
        class="group flex flex-col items-center text-center focus:outline-none"
      >
        <div class="w-full aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 group-hover:border-[#1877F2] transition-colors">
          <img
            :src="friend.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'"
            :alt="friend.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
            loading="lazy"
          />
        </div>
        <span class="mt-1.5 text-xs font-medium text-[#172033] dark:text-slate-200 truncate w-full group-hover:text-[#1877F2] transition-colors">
          {{ friend.name }}
        </span>
      </a>
    </div>

    <!-- Empty State -->
    <div v-else class="text-xs text-[#667085] py-4 text-center">
      No friends to show yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { User } from '../../types';

const props = withDefaults(
  defineProps<{
    friends: User[];
    totalFriends?: number;
    mutualCount?: number;
    seeAllUrl?: string;
  }>(),
  {
    totalFriends: 0,
    mutualCount: 0,
  }
);

defineEmits<{
  (e: 'see-all'): void;
}>();

const previewFriends = computed(() => {
  return props.friends.slice(0, 6);
});
</script>
