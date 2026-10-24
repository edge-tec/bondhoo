<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-fade-in"
    @click.self="close"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-3xl border border-[#E4E7EC] dark:border-slate-800 shadow-2xl w-full max-w-lg overflow-hidden flex flex-col"
    >
      <!-- Header -->
      <div class="px-6 py-4 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-bold text-[#172033] dark:text-slate-100">Share Post</h3>
        <button
          @click="close"
          class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4">
        <!-- Caption input -->
        <textarea
          v-model="caption"
          rows="3"
          class="w-full px-4 py-3 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 placeholder-slate-400 outline-none focus:border-[#1877F2] resize-none"
          placeholder="Say something about this post..."
        ></textarea>

        <!-- Original Post Snippet Preview -->
        <div v-if="post" class="p-3.5 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2">
          <div class="flex items-center gap-2">
            <img
              :src="post.author?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80'"
              class="w-7 h-7 rounded-full object-cover"
            />
            <span class="text-xs font-bold text-[#172033] dark:text-slate-200">{{ post.author?.name }}</span>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">{{ post.content }}</p>
        </div>

        <div v-if="errorMessage" class="text-xs text-red-600 dark:text-red-400 p-2.5 rounded-xl bg-red-50 dark:bg-red-950/30">
          {{ errorMessage }}
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-[#E4E7EC] dark:border-slate-800 flex items-center justify-end gap-3 bg-slate-50/50 dark:bg-slate-900/50">
        <button
          type="button"
          @click="close"
          class="px-4 py-2 rounded-xl border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          Cancel
        </button>

        <button
          type="button"
          @click="submitShare"
          :disabled="isSharing"
          class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors disabled:opacity-50"
        >
          <svg v-if="isSharing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isSharing ? 'Sharing...' : 'Share Now' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
  isOpen: boolean;
  post: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'shared', postId: number): void;
}>();

const caption = ref('');
const isSharing = ref(false);
const errorMessage = ref('');

const close = () => {
  caption.value = '';
  errorMessage.value = '';
  emit('close');
};

const submitShare = async () => {
  if (!props.post) return;
  isSharing.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/posts/${props.post.id}/share`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        caption: caption.value || null,
      }),
    });

    const data = await res.json();
    if (!res.ok) {
      errorMessage.value = data.message || 'Failed to share post.';
      return;
    }

    emit('shared', props.post.id);
    close();
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred while sharing.';
  } finally {
    isSharing.value = false;
  }
};
</script>
