<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Basic Information</h3>
      <button
        v-if="isOwner"
        type="button"
        @click="$emit('open-edit')"
        class="text-xs font-semibold text-[#1877F2] hover:underline cursor-pointer"
      >
        Edit
      </button>
    </div>

    <div class="space-y-4 text-sm">
      <!-- Birthday -->
      <div v-if="birthday" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-[#667085] dark:text-slate-400 font-medium">Birthday</p>
          <p class="text-xs font-semibold text-[#172033] dark:text-slate-100">{{ birthday }}</p>
        </div>
      </div>

      <!-- Gender -->
      <div v-if="gender" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-[#667085] dark:text-slate-400 font-medium">Gender</p>
          <p class="text-xs font-semibold text-[#172033] dark:text-slate-100 capitalize">{{ gender }}</p>
        </div>
      </div>

      <!-- Relationship Status -->
      <div v-if="relationshipStatus" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-[#667085] dark:text-slate-400 font-medium">Relationship Status</p>
          <p class="text-xs font-semibold text-[#172033] dark:text-slate-100 capitalize">{{ relationshipStatus }}</p>
        </div>
      </div>

      <!-- Website -->
      <div v-if="website" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-xs text-[#667085] dark:text-slate-400 font-medium">Website</p>
          <a :href="website.startsWith('http') ? website : 'https://' + website" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-[#1877F2] hover:underline truncate block">
            {{ website }}
          </a>
        </div>
      </div>

      <div v-if="!birthday && !gender && !relationshipStatus && !website" class="text-xs text-[#667085] py-2 text-center">
        No basic information visible.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  birthday?: string | null;
  gender?: string | null;
  relationshipStatus?: string | null;
  website?: string | null;
  isOwner?: boolean;
}>();

defineEmits<{
  (e: 'open-edit'): void;
}>();
</script>
