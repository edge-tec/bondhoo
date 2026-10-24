<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')"
      @keydown.esc="$emit('close')"
    >
      <div
        class="w-full max-w-md bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl border border-neutral-200 dark:border-neutral-800 overflow-hidden transform transition-all animate-scale-up"
      >
        <!-- Modal Header with Warning Icon -->
        <div class="p-6 text-center">
          <div
            class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center shadow-inner"
          >
            <svg
              class="w-8 h-8"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </div>

          <h3 class="text-xl font-bold text-neutral-900 dark:text-white mb-2">
            Delete Reel?
          </h3>

          <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed mb-4">
            Are you sure you want to permanently delete this Reel? This will permanently remove the video, all likes, comments, and shares across the platform. This action cannot be undone.
          </p>

          <!-- Reel Preview mini pill -->
          <div
            v-if="reel"
            class="flex items-center gap-3 p-3 bg-neutral-100 dark:bg-neutral-800/60 rounded-xl mb-4 text-left border border-neutral-200/70 dark:border-neutral-700/60"
          >
            <div class="relative w-12 h-16 rounded-lg overflow-hidden bg-neutral-900 shrink-0">
              <img
                v-if="reel.thumbnail_url"
                :src="reel.thumbnail_url"
                class="w-full h-full object-cover"
                alt="Reel preview"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center text-neutral-500"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-neutral-900 dark:text-white truncate">
                {{ reel.caption || 'Untitled Reel' }}
              </p>
              <p class="text-[11px] text-neutral-500 dark:text-neutral-400 truncate mt-0.5">
                {{ reel.views_count || 0 }} views · {{ reel.likes_count || 0 }} likes
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-3 mt-6">
            <button
              type="button"
              :disabled="isDeleting"
              class="flex-1 px-4 py-2.5 rounded-xl font-medium text-sm text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors disabled:opacity-50"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isDeleting"
              class="flex-1 px-4 py-2.5 rounded-xl font-medium text-sm text-white bg-red-600 hover:bg-red-700 active:bg-red-800 transition-colors shadow-lg shadow-red-600/30 disabled:opacity-50 flex items-center justify-center gap-2"
              @click="$emit('confirm')"
            >
              <svg
                v-if="isDeleting"
                class="w-4 h-4 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              <span>{{ isDeleting ? 'Deleting...' : 'Delete Forever' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
interface ReelItem {
  id: number;
  caption?: string;
  thumbnail_url?: string;
  views_count?: number;
  likes_count?: number;
  [key: string]: any;
}

defineProps<{
  isOpen: boolean;
  reel: ReelItem | null;
  isDeleting: boolean;
}>();

defineEmits<{
  (e: 'close'): void;
  (e: 'confirm'): void;
}>();
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleUp {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fadeIn 0.15s ease-out forwards;
}

.animate-scale-up {
  animation: scaleUp 0.15s ease-out forwards;
}
</style>
