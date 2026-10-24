<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-5 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Photos</h3>
      <a
        v-if="photos && photos.length > 0"
        href="#"
        @click.prevent="$emit('see-all-photos')"
        class="text-xs font-semibold text-[#1877F2] hover:underline"
      >
        See All
      </a>
    </div>

    <!-- 2x3 Grid of 6 Photos -->
    <div v-if="displayPhotos.length > 0" class="grid grid-cols-3 gap-2">
      <div
        v-for="(photo, idx) in displayPhotos"
        :key="idx"
        class="aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 group relative cursor-pointer"
        @click="$emit('photo-click', { photo, index: idx })"
      >
        <img
          :src="photo.url"
          :alt="'Photo ' + (idx + 1)"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/15 transition-colors"></div>
      </div>
    </div>

    <div v-else class="text-xs text-[#667085] py-4 text-center">
      No photos uploaded yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    photos?: Array<{ id?: number; url: string; type?: string }>;
  }>(),
  {
    photos: () => [],
  }
);

defineEmits<{
  (e: 'see-all-photos'): void;
  (e: 'photo-click', photo: any): void;
}>();

const displayPhotos = computed(() => {
  return props.photos.slice(0, 6);
});
</script>
