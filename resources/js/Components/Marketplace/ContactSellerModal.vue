<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in">
    <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800 dark:text-white">
            Send Message to Seller
          </h3>
        </div>
        <button
          @click="close"
          class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Product Preview Snippet -->
      <div class="p-6 space-y-4">
        <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
          <img
            :src="product?.primary_image || defaultImage"
            :alt="product?.title"
            class="w-14 h-14 rounded-xl object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
          />
          <div class="min-w-0 flex-1">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
              {{ product?.title }}
            </h4>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-sm font-extrabold text-[#1877F2]">
                {{ product?.formatted_price || ('$' + product?.price) }}
              </span>
              <span class="text-xs text-slate-400 truncate">
                • {{ product?.location || 'Dhaka, Bangladesh' }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Seller: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ product?.seller?.name || 'Seller' }}</span>
            </p>
          </div>
        </div>

        <!-- Quick Message Pills -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">
            Suggested inquiries:
          </label>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="pill in promptPills"
              :key="pill"
              type="button"
              @click="messageText = pill"
              class="px-3 py-1.5 rounded-full text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-[#1877F2] hover:text-[#1877F2] dark:hover:text-[#1877F2] transition-colors bg-white dark:bg-slate-800"
            >
              {{ pill }}
            </button>
          </div>
        </div>

        <!-- Message Box -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">
            Your message:
          </label>
          <textarea
            v-model="messageText"
            rows="3"
            placeholder="Write a message to the seller..."
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] text-slate-800 dark:text-slate-100 resize-none transition-all"
          ></textarea>
        </div>

        <!-- Feedback Alert -->
        <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ successMessage }}</span>
          </div>
          <a
            :href="conversationUrl || '/messenger'"
            class="font-bold text-[#1877F2] hover:underline shrink-0"
          >
            Open Messenger →
          </a>
        </div>

        <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-200 text-xs flex items-center gap-2">
          <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
        <button
          type="button"
          @click="close"
          class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="sendMessage"
          :disabled="isSending || !messageText.trim()"
          class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-[#1877F2] hover:bg-[#1666D8] disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5"
        >
          <svg v-if="isSending" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isSending ? 'Sending...' : 'Send Message' }}</span>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { MarketplaceProduct } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  product: MarketplaceProduct | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const defaultImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';
const promptPills = [
  'Hi, is this still available?',
  'Is the price negotiable?',
  'Can you deliver to Dhaka?',
  'What is the physical condition?',
];

const messageText = ref('Hi, is this still available?');
const isSending = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const conversationUrl = ref('');

watch(
  () => props.product,
  (newVal) => {
    if (newVal) {
      messageText.value = `Hi ${newVal.seller?.name || ''}, is "${newVal.title}" still available?`;
      successMessage.value = '';
      errorMessage.value = '';
    }
  }
);

const close = () => {
  emit('close');
  successMessage.value = '';
  errorMessage.value = '';
};

const sendMessage = async () => {
  if (!props.product || !messageText.value.trim() || isSending.value) return;

  isSending.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${props.product.id}/contact`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        message: messageText.value.trim(),
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      successMessage.value = 'Message delivered! The seller has been notified.';
      if (data.data?.conversation_id) {
        conversationUrl.value = `/messenger?conversation_id=${data.data.conversation_id}`;
      } else {
        conversationUrl.value = '/messenger';
      }
    } else {
      errorMessage.value = data.message || 'Failed to send message. Please log in or try again.';
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred.';
  } finally {
    isSending.value = false;
  }
};
</script>
