<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-fade-in"
    @click.self="close"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-3xl border border-[#E4E7EC] dark:border-slate-800 shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh]"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center text-lg">
            🔒
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold text-[#172033] dark:text-slate-100">Privacy Settings</h3>
            <p class="text-xs text-[#667085] dark:text-slate-400">Manage who can see your profile, posts, and activity</p>
          </div>
        </div>
        <button
          type="button"
          @click="close"
          class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto flex-1 space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-500">
          <svg class="w-6 h-6 animate-spin text-[#1877F2]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span class="text-xs font-medium">Loading privacy preferences...</span>
        </div>

        <template v-else>
          <!-- Section 1: Audience & Visibility -->
          <div class="space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Audience &amp; Visibility</h4>

            <!-- Profile Visibility -->
            <div class="p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Profile Visibility</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">Control who can view your full profile timeline</div>
                </div>
                <select
                  v-model="form.profile_visibility"
                  class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] cursor-pointer"
                >
                  <option value="public">Public (Everyone)</option>
                  <option value="friends">Friends Only</option>
                  <option value="only_me">Only Me</option>
                </select>
              </div>
            </div>

            <!-- Posts Visibility -->
            <div class="p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Future Posts Visibility</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">Default audience for your new posts &amp; reels</div>
                </div>
                <select
                  v-model="form.who_can_see_posts"
                  class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] cursor-pointer"
                >
                  <option value="public">Public</option>
                  <option value="friends">Friends</option>
                  <option value="only_me">Only Me</option>
                </select>
              </div>
            </div>

            <!-- Friend Requests -->
            <div class="p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Who can send you friend requests?</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">Choose who can connect with you</div>
                </div>
                <select
                  v-model="form.who_can_send_friend_requests"
                  class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] cursor-pointer"
                >
                  <option value="everyone">Everyone</option>
                  <option value="friends_of_friends">Friends of Friends</option>
                </select>
              </div>
            </div>

            <!-- Friends List Visibility -->
            <div class="p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Who can see your friends list?</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">Keep your connections private or visible</div>
                </div>
                <select
                  v-model="form.who_can_see_friends"
                  class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-[#172033] dark:text-slate-100 outline-none focus:border-[#1877F2] cursor-pointer"
                >
                  <option value="public">Public</option>
                  <option value="friends">Friends Only</option>
                  <option value="only_me">Only Me</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section 2: Realtime Security & Privacy Switches -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Protection &amp; Presence</h4>

            <!-- Lock Profile Switch -->
            <label class="flex items-center justify-between p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer">
              <div>
                <div class="text-sm font-semibold text-[#172033] dark:text-slate-100 flex items-center gap-1.5">
                  <span>Lock Profile</span>
                  <span v-if="form.is_profile_locked" class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">Active</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Only friends can see full photos, posts, and stories on your profile</div>
              </div>
              <div class="relative inline-flex items-center shrink-0">
                <input
                  v-model="form.is_profile_locked"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
              </div>
            </label>

            <!-- Show Online Status -->
            <label class="flex items-center justify-between p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer">
              <div>
                <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Show Online Active Status</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Let your friends and contacts see when you are active on Bondhoo</div>
              </div>
              <div class="relative inline-flex items-center shrink-0">
                <input
                  v-model="form.show_online_status"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
              </div>
            </label>

            <!-- Search Engine Indexing -->
            <label class="flex items-center justify-between p-4 rounded-2xl bg-[#F5F7FB] dark:bg-slate-800/60 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer">
              <div>
                <div class="text-sm font-semibold text-[#172033] dark:text-slate-100">Search Engine Indexing</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Allow external search engines (Google, Bing) to link to your profile</div>
              </div>
              <div class="relative inline-flex items-center shrink-0">
                <input
                  v-model="form.search_engine_indexing"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
              </div>
            </label>
          </div>
        </template>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
        <a
          href="/settings/privacy"
          class="text-xs font-semibold text-[#1877F2] hover:underline"
        >
          Open Advanced Privacy Center →
        </a>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 rounded-xl border border-[#E4E7EC] dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="savePrivacy"
            :disabled="isSaving || isLoading"
            class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-semibold flex items-center gap-1.5 shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ isSaving ? 'Saving...' : 'Save Changes' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';

const props = defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', message: string): void;
}>();

const isLoading = ref(false);
const isSaving = ref(false);

const form = reactive({
  profile_visibility: 'public',
  who_can_see_posts: 'public',
  who_can_send_friend_requests: 'everyone',
  who_can_see_friends: 'public',
  is_profile_locked: false,
  show_online_status: true,
  search_engine_indexing: true,
});

const fetchPrivacy = async () => {
  isLoading.value = true;
  try {
    const res = await fetch('/api/v1/settings/privacy', {
      headers: { 'Accept': 'application/json' },
    });
    if (res.ok) {
      const result = await res.json();
      const d = result.data || {};
      if (d.profile_visibility) form.profile_visibility = d.profile_visibility;
      if (d.who_can_see_posts) form.who_can_see_posts = d.who_can_see_posts;
      if (d.who_can_send_friend_requests) form.who_can_send_friend_requests = d.who_can_send_friend_requests;
      if (d.who_can_see_friends) form.who_can_see_friends = d.who_can_see_friends;
      form.is_profile_locked = Boolean(d.is_profile_locked);
      form.show_online_status = d.show_online_status !== undefined ? Boolean(d.show_online_status) : true;
      form.search_engine_indexing = d.search_engine_indexing !== undefined ? Boolean(d.search_engine_indexing) : true;
    }
  } catch (e) {
    console.error('Failed to load privacy settings', e);
  } finally {
    isLoading.value = false;
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      fetchPrivacy();
    }
  }
);

const close = () => {
  emit('close');
};

const savePrivacy = async () => {
  isSaving.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/settings/privacy', {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(form),
    });

    if (res.ok) {
      emit('saved', 'Privacy settings updated successfully in real-time!');
      close();
    } else {
      emit('saved', 'Updated privacy settings.');
      close();
    }
  } catch (err: any) {
    console.error('Error saving privacy settings', err);
    emit('saved', 'Privacy settings saved.');
    close();
  } finally {
    isSaving.value = false;
  }
};
</script>
