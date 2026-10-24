<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-fade-in"
    @click.self="close"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-3xl border border-[#E4E7EC] dark:border-slate-800 shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between shrink-0">
        <h3 class="text-lg font-bold text-[#172033] dark:text-slate-100">Edit Profile Information</h3>
        <button
          @click="close"
          class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation Tabs Inside Modal -->
      <div class="px-6 border-b border-[#E4E7EC] dark:border-slate-800 flex gap-4 overflow-x-auto shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
        <button
          v-for="tab in modalTabs"
          :key="tab.id"
          type="button"
          @click="activeModalTab = tab.id"
          :class="[
            'py-3 text-xs sm:text-sm font-semibold border-b-2 transition-colors whitespace-nowrap',
            activeModalTab === tab.id
              ? 'border-[#1877F2] text-[#1877F2]'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-6 overflow-y-auto flex-1 space-y-5">
        <!-- Error Banner -->
        <div v-if="errorMessage" class="p-3.5 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 text-xs flex items-center gap-2 border border-red-200 dark:border-red-800">
          <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- TAB 1: Basic Info -->
        <div v-show="activeModalTab === 'basic'" class="space-y-4">
          <!-- Full Name -->
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Full Name *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. Alex Carter"
            />
          </div>

          <!-- Bio -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold text-[#172033] dark:text-slate-200">Bio</label>
              <span class="text-[11px] text-slate-400">{{ (form.bio || '').length }} / 500</span>
            </div>
            <textarea
              v-model="form.bio"
              rows="3"
              maxlength="500"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] resize-none"
              placeholder="Tell friends about yourself..."
            ></textarea>
          </div>

          <!-- Birthday & Gender -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Birthday</label>
              <input
                v-model="form.birth_date"
                type="date"
                class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Gender</label>
              <select
                v-model="form.gender"
                class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              >
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                <option value="prefer_not_to_say">Prefer not to say</option>
              </select>
            </div>
          </div>

          <!-- Relationship Status -->
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Relationship Status</label>
            <select
              v-model="form.relationship_status"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
            >
              <option value="">Select Status</option>
              <option value="single">Single</option>
              <option value="in_a_relationship">In a relationship</option>
              <option value="engaged">Engaged</option>
              <option value="married">Married</option>
              <option value="its_complicated">It's complicated</option>
              <option value="separated">Separated</option>
              <option value="divorced">Divorced</option>
              <option value="widowed">Widowed</option>
            </select>
          </div>

          <!-- Website -->
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Website / Portfolio</label>
            <input
              v-model="form.website"
              type="url"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. https://alexcarter.dev"
            />
          </div>
        </div>

        <!-- TAB 2: Work & Education -->
        <div v-show="activeModalTab === 'work'" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Work / Occupation</label>
            <input
              v-model="form.work"
              type="text"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. Software Engineer at Tech Solutions"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Education / College / University</label>
            <input
              v-model="form.education"
              type="text"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. Computer Science at University"
            />
          </div>
        </div>

        <!-- TAB 3: Places Lived -->
        <div v-show="activeModalTab === 'places'" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Current City</label>
            <input
              v-model="form.city"
              type="text"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. Dhaka, Bangladesh"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Hometown</label>
            <input
              v-model="form.hometown"
              type="text"
              class="w-full px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
              placeholder="e.g. Chittagong, Bangladesh"
            />
          </div>
        </div>

        <!-- TAB 4: Interests -->
        <div v-show="activeModalTab === 'interests'" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-[#172033] dark:text-slate-200 mb-1.5">Your Interests & Hobbies</label>
            <div class="flex gap-2">
              <input
                v-model="newInterest"
                type="text"
                @keydown.enter.prevent="addInterest"
                class="flex-1 px-4 py-2 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-sm text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2]"
                placeholder="Type an interest and press Enter (e.g. Photography)"
              />
              <button
                type="button"
                @click="addInterest"
                class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-semibold hover:bg-[#1666D8] transition-colors"
              >
                Add
              </button>
            </div>
          </div>

          <!-- Existing tags -->
          <div class="flex flex-wrap gap-2 pt-2">
            <span
              v-for="(tag, index) in form.interests"
              :key="index"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-[#F5F7FB] dark:bg-slate-800 text-[#172033] dark:text-slate-200 border border-[#E4E7EC] dark:border-slate-700"
            >
              <span>{{ tag }}</span>
              <button
                type="button"
                @click="removeInterest(index)"
                class="text-slate-400 hover:text-rose-500 transition-colors"
              >
                &times;
              </button>
            </span>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-[#E4E7EC] dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
        <button
          type="button"
          @click="close"
          class="px-4 py-2.5 rounded-xl border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          Cancel
        </button>

        <button
          type="button"
          @click="saveProfile"
          :disabled="isSaving"
          class="px-6 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors disabled:opacity-50"
        >
          <svg v-if="isSaving" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isSaving ? 'Saving...' : 'Save Changes' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import type { User } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  user: User;
  profileDetails?: any;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'updated', payload: any): void;
}>();

const activeModalTab = ref<'basic' | 'work' | 'places' | 'interests'>('basic');
const isSaving = ref(false);
const errorMessage = ref('');
const newInterest = ref('');

const modalTabs = [
  { id: 'basic', label: 'Basic Info' },
  { id: 'work', label: 'Work & Education' },
  { id: 'places', label: 'Places Lived' },
  { id: 'interests', label: 'Interests' },
];

const form = reactive({
  name: '',
  bio: '',
  birth_date: '',
  gender: '',
  relationship_status: '',
  work: '',
  education: '',
  city: '',
  hometown: '',
  website: '',
  interests: [] as string[],
});

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      form.name = props.user.name || '';
      form.bio = props.user.bio || '';
      form.birth_date = props.profileDetails?.birth_date ? formatDateForInput(props.profileDetails.birth_date) : '';
      form.gender = props.profileDetails?.gender || '';
      form.relationship_status = props.profileDetails?.relationship_status || '';
      form.work = props.profileDetails?.work || '';
      form.education = props.profileDetails?.education || '';
      form.city = props.profileDetails?.city || '';
      form.hometown = props.profileDetails?.hometown || '';
      form.website = props.profileDetails?.website || '';
      form.interests = Array.isArray(props.profileDetails?.interests) ? [...props.profileDetails.interests] : [];
      errorMessage.value = '';
    }
  },
  { immediate: true }
);

const formatDateForInput = (dStr: string) => {
  try {
    const d = new Date(dStr);
    if (isNaN(d.getTime())) return '';
    return d.toISOString().split('T')[0];
  } catch {
    return '';
  }
};

const addInterest = () => {
  const val = newInterest.value.trim();
  if (val && !form.interests.includes(val)) {
    form.interests.push(val);
    newInterest.value = '';
  }
};

const removeInterest = (index: number) => {
  form.interests.splice(index, 1);
};

const close = () => {
  emit('close');
};

const saveProfile = async () => {
  isSaving.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile', {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        name: form.name,
        bio: form.bio,
        work: form.work,
        education: form.education,
        city: form.city,
        hometown: form.hometown,
        website: form.website || null,
        birth_date: form.birth_date || null,
        gender: form.gender || null,
        relationship_status: form.relationship_status || null,
        interests: form.interests,
      }),
    });

    const data = await res.json();
    if (!res.ok) {
      errorMessage.value = data.message || 'Failed to update profile. Please verify your inputs.';
      return;
    }

    emit('updated', {
      name: form.name,
      bio: form.bio,
      work: form.work,
      education: form.education,
      city: form.city,
      hometown: form.hometown,
      website: form.website,
      birth_date: form.birth_date ? new Date(form.birth_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : null,
      gender: form.gender,
      relationship_status: form.relationship_status,
      interests: form.interests,
    });
    close();
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred while saving profile.';
  } finally {
    isSaving.value = false;
  }
};
</script>
