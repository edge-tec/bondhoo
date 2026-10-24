<template>
  <Transition
    enter-active-class="ease-out duration-300 transition"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="ease-in duration-200 transition"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex flex-col justify-between p-4 sm:p-6 select-none"
      @keydown.esc="close"
      @keydown.left="prev"
      @keydown.right="next"
      tabindex="0"
      ref="modalRef"
    >
      <!-- Top Bar: Counter & Close Button -->
      <div class="flex items-center justify-between text-white shrink-0 z-10">
        <span class="text-xs sm:text-sm font-medium tracking-wide bg-black/40 px-3 py-1.5 rounded-full border border-white/10">
          {{ currentIndex + 1 }} / {{ photos.length }}
        </span>

        <button
          @click="close"
          class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
          title="Close (Esc)"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Main Viewer Area -->
      <div class="relative flex-1 flex items-center justify-center my-4 overflow-hidden">
        <!-- Prev Button -->
        <button
          v-if="photos.length > 1"
          @click="prev"
          class="absolute left-2 sm:left-4 z-10 w-11 h-11 rounded-full bg-black/50 hover:bg-black/75 border border-white/10 text-white flex items-center justify-center transition-all hover:scale-105"
          title="Previous (Left Arrow)"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Current Active Photo -->
        <div class="max-h-full max-w-full flex items-center justify-center p-2">
          <img
            :src="currentPhoto?.url"
            :alt="'Photo ' + (currentIndex + 1)"
            class="max-h-[80vh] max-w-full object-contain rounded-xl shadow-2xl transition-all duration-200"
          />
        </div>

        <!-- Next Button -->
        <button
          v-if="photos.length > 1"
          @click="next"
          class="absolute right-2 sm:right-4 z-10 w-11 h-11 rounded-full bg-black/50 hover:bg-black/75 border border-white/10 text-white flex items-center justify-center transition-all hover:scale-105"
          title="Next (Right Arrow)"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Bottom Thumbnail Strip (if multiple photos) -->
      <div v-if="photos.length > 1" class="shrink-0 flex items-center justify-center gap-2 overflow-x-auto py-2 z-10">
        <button
          v-for="(photo, idx) in photos"
          :key="idx"
          @click="currentIndex = idx"
          :class="[
            'w-14 h-14 rounded-lg overflow-hidden shrink-0 border-2 transition-all',
            currentIndex === idx ? 'border-[#1877F2] scale-105' : 'border-transparent opacity-60 hover:opacity-100'
          ]"
        >
          <img :src="photo.url" class="w-full h-full object-cover" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps<{
  isOpen: boolean;
  photos: Array<{ id?: number; url: string; type?: string }>;
  initialIndex?: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const currentIndex = ref(0);
const modalRef = ref<HTMLDivElement | null>(null);

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      currentIndex.value = props.initialIndex || 0;
      nextTick(() => {
        modalRef.value?.focus();
      });
    }
  }
);

const currentPhoto = computed(() => {
  if (!props.photos || props.photos.length === 0) return null;
  return props.photos[currentIndex.value] || props.photos[0];
});

const prev = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  } else {
    currentIndex.value = props.photos.length - 1;
  }
};

const next = () => {
  if (currentIndex.value < props.photos.length - 1) {
    currentIndex.value++;
  } else {
    currentIndex.value = 0;
  }
};

const close = () => {
  emit('close');
};
</script>
