<template>
  <AppLayout
    current-route="learning"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="max-w-2xl mx-auto py-10 px-4">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 shadow-xl space-y-6 text-center">
        <div v-if="certificate && certificate.is_valid" class="space-y-4">
          <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl mx-auto">
            ✓
          </div>
          <span class="inline-block px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-extrabold uppercase tracking-wider">
            Authentic Verified Credential
          </span>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white">
            {{ certificate.course_title }}
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            This certifies that <strong class="text-slate-900 dark:text-white">{{ certificate.student_name }}</strong> successfully completed all curriculum requirements.
          </p>

          <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 text-xs space-y-2 text-left border border-slate-100 dark:border-slate-800">
            <div class="flex justify-between">
              <span class="text-slate-400">Certificate ID:</span>
              <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ certificate.certificate_code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Instructor:</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ certificate.instructor_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Issued Date:</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ formattedDate }}</span>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            <a
              :href="'/api/v1/learning/certificates/' + certificate.certificate_code + '/download'"
              target="_blank"
              class="px-5 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-md shadow-blue-500/20"
            >
              Download Printable Certificate
            </a>
            <a
              href="/learning"
              class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-50"
            >
              Explore Courses
            </a>
          </div>
        </div>

        <div v-else-if="certificate && certificate.is_revoked" class="space-y-3">
          <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-3xl mx-auto">!</div>
          <h1 class="text-xl font-black text-rose-600">Certificate Revoked</h1>
          <p class="text-xs text-slate-500">
            Credential <span class="font-mono font-bold">{{ code }}</span> was revoked. Reason: {{ certificate.revoked_reason || 'Policy violation' }}.
          </p>
          <a href="/learning" class="inline-block px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">Back to Learning</a>
        </div>

        <div v-else class="space-y-3">
          <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-3xl mx-auto">?</div>
          <h1 class="text-xl font-black text-slate-900 dark:text-white">Certificate Not Found</h1>
          <p class="text-xs text-slate-500">
            No valid Bondoo Learning credential matches code <span class="font-mono font-bold">{{ code }}</span>.
          </p>
          <a href="/learning" class="inline-block px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">Explore Courses</a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps<{
  code: string;
  certificate: {
    certificate_code: string;
    is_valid: boolean;
    is_revoked: boolean;
    revoked_reason?: string | null;
    student_name: string;
    course_title: string;
    instructor_name: string;
    issued_at: string;
    verification_url: string;
  } | null;
}>();

const formattedDate = computed(() => {
  if (!props.certificate?.issued_at) return '';
  try {
    return new Date(props.certificate.issued_at).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  } catch {
    return props.certificate.issued_at;
  }
});
</script>

