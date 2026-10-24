<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-fade-in"
    @click.self="close"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-3xl border border-[#E4E7EC] dark:border-slate-800 shadow-2xl w-full max-w-md overflow-hidden flex flex-col"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-base font-bold text-[#172033] dark:text-slate-100">
          Report {{ targetType === 'user' ? 'Profile' : 'Post' }}
        </h3>
        <button
          @click="close"
          class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-4">
        <p class="text-xs text-slate-600 dark:text-slate-400">
          Please select a reason for reporting this {{ targetType }}. Our moderation team reviews reports 24/7.
        </p>

        <!-- Reasons list -->
        <div class="space-y-2">
          <label
            v-for="r in reasons"
            :key="r.id"
            class="flex items-center gap-3 p-3 rounded-xl border border-[#E4E7EC] dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-colors"
          >
            <input
              type="radio"
              v-model="selectedReason"
              :value="r.id"
              class="w-4 h-4 text-[#1877F2] focus:ring-[#1877F2]"
            />
            <span class="text-xs font-medium text-[#172033] dark:text-slate-200">{{ r.label }}</span>
          </label>
        </div>

        <!-- Additional details -->
        <div>
          <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Additional Details (Optional)</label>
          <textarea
            v-model="details"
            rows="3"
            maxlength="1000"
            class="w-full px-3.5 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-xs text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] resize-none"
            placeholder="Help us understand the issue..."
          ></textarea>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="text-xs text-red-600 dark:text-red-400 p-2.5 rounded-xl bg-red-50 dark:bg-red-950/30">
          {{ errorMessage }}
        </div>
      </div>

      <!-- Modal Footer -->
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
          @click="submitReport"
          :disabled="isSubmitting || !selectedReason"
          class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold flex items-center gap-2 transition-colors disabled:opacity-50"
        >
          <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isSubmitting ? 'Submitting...' : 'Submit Report' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
  isOpen: boolean;
  targetType: 'user' | 'post' | 'comment';
  targetId: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'submitted'): void;
}>();

const selectedReason = ref('spam');
const details = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');

const reasons = [
  { id: 'spam', label: 'Spam or Fake Content' },
  { id: 'harassment', label: 'Harassment or Bullying' },
  { id: 'hate_speech', label: 'Hate Speech or Discrimination' },
  { id: 'false_information', label: 'False Information' },
  { id: 'violence', label: 'Violence or Dangerous Organizations' },
  { id: 'other', label: 'Other violation' },
];

const close = () => {
  errorMessage.value = '';
  details.value = '';
  emit('close');
};

const submitReport = async () => {
  if (!selectedReason.value) return;
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/reports', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        reportable_type: props.targetType,
        reportable_id: props.targetId,
        reason: selectedReason.value,
        details: details.value || null,
      }),
    });

    const data = await res.json();
    if (!res.ok) {
      errorMessage.value = data.message || 'Failed to submit report.';
      return;
    }

    emit('submitted');
    close();
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>
