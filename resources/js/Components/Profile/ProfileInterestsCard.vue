<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Interests</h3>
      <button
        v-if="isOwner"
        type="button"
        @click="$emit('open-edit')"
        class="text-xs font-semibold text-[#1877F2] hover:underline cursor-pointer"
      >
        Edit
      </button>
    </div>

    <!-- Tags Cloud -->
    <div v-if="displayInterests.length > 0" class="flex flex-wrap gap-2">
      <span
        v-for="(tag, idx) in displayInterests"
        :key="idx"
        class="px-3 py-1.5 rounded-full text-xs font-medium bg-[#F5F7FB] dark:bg-slate-800 text-[#172033] dark:text-slate-200 border border-[#E4E7EC] dark:border-slate-800 hover:border-[#1877F2]/40 transition-colors cursor-default"
      >
        {{ tag }}
      </span>
    </div>

    <div v-else class="text-xs text-[#667085] py-2 text-center">
      No interests added yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    interests?: string[];
    isOwner?: boolean;
  }>(),
  {
    interests: () => [],
  }
);

defineEmits<{
  (e: 'open-edit'): void;
}>();

const displayInterests = computed(() => props.interests || []);
</script>
