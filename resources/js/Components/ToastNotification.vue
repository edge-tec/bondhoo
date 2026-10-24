<template>
  <Transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-[#E4E7EC] dark:border-slate-800 p-4 pointer-events-auto flex items-start gap-3"
    >
      <!-- Icon based on type -->
      <div
        :class="[
          'w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-white',
          type === 'success' ? 'bg-[#12B76A]' : type === 'error' ? 'bg-[#F04438]' : 'bg-[#1877F2]'
        ]"
      >
        <svg v-if="type === 'success'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else-if="type === 'error'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>

      <div class="flex-1 min-w-0 pt-0.5">
        <p class="text-xs font-bold text-[#172033] dark:text-slate-100">
          {{ title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notification') }}
        </p>
        <p class="text-xs text-[#667085] dark:text-slate-400 mt-0.5 leading-relaxed">
          {{ message }}
        </p>
      </div>

      <button
        @click="$emit('close')"
        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { watch } from 'vue';

const props = withDefaults(
  defineProps<{
    show: boolean;
    title?: string;
    message: string;
    type?: 'success' | 'error' | 'info';
    duration?: number;
  }>(),
  {
    type: 'success',
    duration: 4000,
  }
);

const emit = defineEmits<{
  (e: 'close'): void;
}>();

watch(
  () => props.show,
  (newVal) => {
    if (newVal && props.duration > 0) {
      setTimeout(() => {
        emit('close');
      }, props.duration);
    }
  }
);
</script>
