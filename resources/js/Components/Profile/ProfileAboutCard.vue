<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">About</h3>
      <button
        v-if="isOwner"
        type="button"
        @click="$emit('open-edit')"
        class="text-xs font-semibold text-[#1877F2] hover:underline cursor-pointer"
      >
        Edit
      </button>
    </div>

    <div class="space-y-3.5 text-sm text-[#172033] dark:text-slate-200">
      <!-- Work / Occupation -->
      <div v-if="work" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        <span class="truncate">{{ work }}</span>
      </div>

      <!-- Education -->
      <div v-if="education" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M12 14l9-5-9-5-9 5 9 5z" />
            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
          </svg>
        </div>
        <span class="truncate">Studied at {{ education }}</span>
      </div>

      <!-- Current City -->
      <div v-if="city" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
        </div>
        <span class="truncate">Lives in <strong class="font-semibold">{{ city }}</strong></span>
      </div>

      <!-- Hometown -->
      <div v-if="hometown" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <span class="truncate">From <strong class="font-semibold">{{ hometown }}</strong></span>
      </div>

      <!-- Relationship Status -->
      <div v-if="relationshipStatus" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-rose-500 shrink-0">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
          </svg>
        </div>
        <span class="capitalize">{{ relationshipStatus }}</span>
      </div>

      <!-- Website -->
      <div v-if="website" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[#1877F2] shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
        </div>
        <a :href="website.startsWith('http') ? website : 'https://' + website" target="_blank" rel="noopener noreferrer" class="truncate text-[#1877F2] hover:underline font-medium">
          {{ website }}
        </a>
      </div>

      <!-- Empty fallback if no data provided yet -->
      <div v-if="!work && !education && !city && !hometown && !relationshipStatus && !website" class="text-xs text-[#667085] py-2 text-center">
        No bio details shared yet.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  work?: string | null;
  education?: string | null;
  city?: string | null;
  hometown?: string | null;
  relationshipStatus?: string | null;
  website?: string | null;
  isOwner?: boolean;
}>();

defineEmits<{
  (e: 'open-edit'): void;
}>();
</script>
