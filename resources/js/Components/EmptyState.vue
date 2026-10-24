<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-8 text-center shadow-sm">
    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-tr from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center">
      <slot name="icon">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
      </slot>
    </div>
    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-1">
      {{ title }}
    </h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-5">
      {{ message }}
    </p>
    <div v-if="$slots.action" class="flex justify-center">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  message: string;
}>();
</script>
