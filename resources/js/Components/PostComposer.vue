<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 mb-4 shadow-sm transition-all">
    <!-- Top Row: Avatar & Rounded Input / Textarea -->
    <div class="flex items-start gap-3">
      <a :href="'/profile/' + (user?.username || 'user')" class="shrink-0">
        <img
          :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
          alt="Avatar"
          class="w-10 h-10 rounded-full object-cover ring-2 ring-blue-500/10 mt-0.5 hover:opacity-90 transition-opacity"
        />
      </a>
      <div class="flex-1 min-w-0">
        <!-- Collapsed Single-line Pill Input (matches screenshot) -->
        <div
          v-if="!isExpanded"
          @click="expandComposer"
          role="button"
          tabindex="0"
          @keydown.enter="expandComposer"
          @keydown.space.prevent="expandComposer"
          class="w-full bg-[#F0F2F5] dark:bg-slate-800/80 hover:bg-[#E4E6E9] dark:hover:bg-slate-800 text-sm text-[#65676B] dark:text-slate-400 rounded-full px-5 py-2.5 cursor-pointer transition-all flex items-center select-none"
        >
          <span>What's on your mind, {{ userName }}?</span>
        </div>

        <!-- Expanded Full Textarea Composer -->
        <div v-else class="space-y-2">
          <textarea
            ref="textareaRef"
            v-model="content"
            rows="3"
            :placeholder="'What\'s on your mind, ' + userName + '?'"
            class="w-full bg-[#F0F2F5] dark:bg-slate-800/80 text-sm text-[#172033] dark:text-slate-100 placeholder-slate-400 rounded-2xl p-3.5 border-none outline-none focus:ring-2 focus:ring-[#1877F2] resize-none transition-all"
          ></textarea>

          <!-- Selected Feelings & Tagged Friends Pills -->
          <div v-if="selectedFeeling || taggedUsers.length > 0" class="flex flex-wrap items-center gap-2 pt-1">
            <!-- Feeling badge -->
            <div
              v-if="selectedFeeling"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-full text-xs text-amber-800 dark:text-amber-300 font-medium animate-in fade-in"
            >
              <span>is {{ selectedFeeling }}</span>
              <button
                type="button"
                @click="selectedFeeling = ''"
                class="hover:text-amber-950 dark:hover:text-amber-100 ml-1 p-0.5 rounded-full hover:bg-amber-200/50"
                title="Remove feeling"
              >
                ✕
              </button>
            </div>

            <!-- Tagged friends badges -->
            <div
              v-for="friend in taggedUsers"
              :key="friend.id"
              class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-full text-xs text-[#1877F2] dark:text-blue-300 font-medium animate-in fade-in"
            >
              <span>with {{ friend.name }}</span>
              <button
                type="button"
                @click="removeTaggedUser(friend.id)"
                class="hover:text-blue-900 dark:hover:text-blue-100 ml-1 p-0.5 rounded-full hover:bg-blue-200/50"
                title="Remove friend tag"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Media Previews Grid (Images & Videos) -->
    <div v-if="mediaFiles.length > 0" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2.5">
      <div
        v-for="(item, idx) in mediaFiles"
        :key="idx"
        class="relative rounded-xl overflow-hidden group aspect-video bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
      >
        <!-- Video Preview -->
        <template v-if="item.isVideo">
          <video
            :src="item.url"
            muted
            playsinline
            class="w-full h-full object-cover"
          ></video>
          <div class="absolute bottom-1.5 left-1.5 bg-black/70 backdrop-blur-sm text-white text-[10px] px-2 py-0.5 rounded-full flex items-center gap-1 font-semibold pointer-events-none">
            <svg class="w-3 h-3 text-red-500 fill-current" viewBox="0 0 24 24">
              <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 2v12h16V6H4zm6 3l6 3-6 3V9z"/>
            </svg>
            <span>Video</span>
          </div>
        </template>

        <!-- Image Preview -->
        <template v-else>
          <img :src="item.url" class="w-full h-full object-cover" alt="Upload Preview" />
        </template>

        <!-- Remove Media Button -->
        <button
          type="button"
          @click="removeMedia(idx)"
          class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-red-600 transition-colors shadow-sm"
          title="Remove attachment"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Add more media card -->
      <label
        v-if="mediaFiles.length < 10"
        class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-[#1877F2] rounded-xl flex flex-col items-center justify-center aspect-video cursor-pointer text-slate-500 hover:text-[#1877F2] transition-colors bg-slate-50 dark:bg-slate-800/40"
      >
        <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="text-xs font-semibold">Add more</span>
        <input type="file" accept="image/*,video/*" multiple @change="handleFileChange" class="hidden" />
      </label>
    </div>

    <!-- Error Banner -->
    <div v-if="errorMessage" class="mt-3 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 p-2.5 rounded-xl flex items-center justify-between gap-2 border border-red-200 dark:border-red-900/50">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
    </div>

    <!-- Feeling / Activity Picker Drawer -->
    <div
      v-if="showFeelingPicker"
      class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/90 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between mb-3">
        <!-- Tabs: Feelings vs Activities -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="feelingTab = 'feelings'"
            :class="feelingTab === 'feelings' ? 'bg-[#1877F2] text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100'"
            class="px-3 py-1 rounded-xl text-xs font-bold transition-colors"
          >
            Feelings
          </button>
          <button
            type="button"
            @click="feelingTab = 'activities'"
            :class="feelingTab === 'activities' ? 'bg-[#1877F2] text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100'"
            class="px-3 py-1 rounded-xl text-xs font-bold transition-colors"
          >
            Activities
          </button>
        </div>
        <button
          type="button"
          @click="showFeelingPicker = false"
          class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
        >
          ✕
        </button>
      </div>

      <!-- Search Filter -->
      <input
        v-model="feelingSearchQuery"
        type="text"
        :placeholder="feelingTab === 'feelings' ? 'Search feelings (e.g. happy, excited)...' : 'Search activities (e.g. celebrating, traveling)...'"
        class="w-full bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2] mb-2.5"
      />

      <!-- Options Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-h-48 overflow-y-auto pr-1">
        <button
          v-for="item in filteredFeelings"
          :key="item.label"
          type="button"
          @click="chooseFeeling(item.label)"
          class="flex items-center gap-2 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-[#1877F2] hover:bg-blue-50/40 dark:hover:bg-blue-950/20 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-all text-left group"
        >
          <span class="text-base group-hover:scale-110 transition-transform">{{ item.emoji }}</span>
          <span class="truncate">{{ item.name }}</span>
        </button>
      </div>
    </div>

    <!-- Tag Friends Picker Drawer -->
    <div
      v-if="showTagPicker"
      class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/90 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Tag Friends</span>
          <span v-if="taggedUsers.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#1877F2] text-white">
            {{ taggedUsers.length }} selected
          </span>
        </div>
        <button
          type="button"
          @click="showTagPicker = false"
          class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
        >
          ✕
        </button>
      </div>

      <!-- Live Search Input -->
      <input
        v-model="tagSearchQuery"
        @input="debouncedSearchUsers"
        type="text"
        placeholder="Search friends by name or username..."
        class="w-full bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2] mb-2.5"
      />

      <!-- Friends List with Checkboxes -->
      <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
        <div
          v-for="u in searchResults"
          :key="u.id"
          @click="toggleTagUser(u)"
          class="flex items-center justify-between p-2 rounded-xl hover:bg-white dark:hover:bg-slate-900 cursor-pointer transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <img
              :src="u.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              class="w-7 h-7 rounded-full object-cover shrink-0"
              alt="Avatar"
            />
            <div class="truncate">
              <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ u.name }}</p>
              <p class="text-[10px] text-slate-400 truncate">@{{ u.username }}</p>
            </div>
          </div>
          <div
            :class="taggedUsers.some(t => t.id === u.id) ? 'bg-[#1877F2] border-[#1877F2] text-white' : 'border-slate-300 dark:border-slate-600 text-transparent'"
            class="w-5 h-5 rounded-md border flex items-center justify-center text-[10px] font-bold transition-colors shrink-0 ml-2"
          >
            ✓
          </div>
        </div>

        <div v-if="isSearchingTags" class="text-center py-3 text-xs text-slate-400 flex items-center justify-center gap-1.5">
          <svg class="animate-spin h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Searching friends...</span>
        </div>

        <p v-else-if="searchResults.length === 0" class="text-center py-3 text-xs text-slate-400">
          {{ tagSearchQuery ? 'No matching friends found.' : 'No friends found to tag.' }}
        </p>
      </div>

      <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-end">
        <button
          type="button"
          @click="showTagPicker = false"
          class="px-3 py-1 rounded-lg text-xs font-bold bg-[#1877F2] text-white hover:bg-[#1666D8] transition-colors"
        >
          Done
        </button>
      </div>
    </div>

    <!-- Divider Line (matches screenshot) -->
    <div class="h-px bg-[#E4E7EC] dark:bg-slate-800 my-3"></div>

    <!-- 5 Action Buttons Row (matches screenshot) -->
    <div class="flex items-center justify-between gap-1 sm:gap-2 flex-wrap pb-1">
      <!-- 1. Live Video -->
      <button
        type="button"
        @click="triggerLive"
        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-bold text-[#E41E3F] hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer group"
      >
        <svg class="w-4 h-4 text-[#E41E3F] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/>
        </svg>
        <span>Live Video</span>
      </button>

      <!-- 2. Reel -->
      <button
        type="button"
        @click="triggerReel"
        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-bold text-[#9333EA] hover:bg-purple-50 dark:hover:bg-purple-950/40 transition-colors cursor-pointer group"
      >
        <svg class="w-4 h-4 text-[#9333EA] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
        </svg>
        <span>Reel</span>
      </button>

      <!-- 3. Photo/Video -->
      <label class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group">
        <svg class="w-4 h-4 text-[#12B76A] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zm0 2v10h16V7H4zm3 8l3-4 2 2.5 3-4 4 5.5H7zm9-5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
        </svg>
        <span>Photo/Video</span>
        <input type="file" accept="image/*,video/*" multiple @change="handleFileChange" class="hidden" />
      </label>

      <!-- 4. Feeling/Activity -->
      <button
        type="button"
        @click="toggleFeelingPicker"
        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
      >
        <span class="text-sm group-hover:scale-110 transition-transform">😊</span>
        <span>Feeling/Activity</span>
      </button>

      <!-- 5. Tag Friends -->
      <button
        type="button"
        @click="toggleTagPicker"
        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
      >
        <svg class="w-4 h-4 text-[#1877F2] group-hover:scale-110 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span>Tag Friends</span>
      </button>
    </div>

    <!-- Bottom Row: Audience Selector & Post Button (matches screenshot) -->
    <div class="mt-2.5 pt-2 flex items-center gap-3">
      <!-- Audience Selector Dropdown Pill -->
      <div class="relative" ref="audienceDropdownRef">
        <button
          type="button"
          @click="showAudienceDropdown = !showAudienceDropdown"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#F5F7FB] dark:bg-slate-800 hover:bg-slate-200/70 dark:hover:bg-slate-700/70 text-slate-700 dark:text-slate-200 border border-[#E4E7EC] dark:border-slate-700 shadow-sm transition-all"
        >
          <span class="text-sm">{{ currentAudienceOption.icon }}</span>
          <span>{{ currentAudienceOption.label }}</span>
          <svg class="w-3.5 h-3.5 text-slate-500 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Custom Audience Popover Menu -->
        <div
          v-if="showAudienceDropdown"
          class="absolute left-0 mt-1.5 w-60 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-[#E4E7EC] dark:border-slate-800 p-1.5 z-30 animate-in fade-in zoom-in-95 duration-150"
        >
          <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800 mb-1">
            <p class="text-xs font-bold text-slate-800 dark:text-slate-100">Select Audience</p>
            <p class="text-[10px] text-slate-400">Who can see your post?</p>
          </div>
          <button
            v-for="opt in audienceOptions"
            :key="opt.value"
            type="button"
            @click="selectAudience(opt.value)"
            class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-left transition-colors"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-base">{{ opt.icon }}</span>
              <div>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ opt.label }}</p>
                <p class="text-[10px] text-slate-400 leading-tight">{{ opt.desc }}</p>
              </div>
            </div>
            <span v-if="audience === opt.value" class="text-[#1877F2] font-bold text-xs">✓</span>
          </button>
        </div>
      </div>

      <!-- Blue Post Button (matches screenshot) -->
      <button
        type="button"
        @click="submitPost"
        :disabled="isSubmitting || isPostEmpty"
        class="px-6 py-1.5 rounded-xl text-xs sm:text-sm font-bold bg-[#1877F2] hover:bg-[#1666D8] text-white disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all flex items-center gap-1.5"
      >
        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ isSubmitting ? 'Posting...' : 'Post' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import type { User } from '../types';

interface MediaItem {
  file: File;
  url: string;
  isVideo: boolean;
  name: string;
}

const props = defineProps<{
  user: User | null;
}>();

const emit = defineEmits<{
  (e: 'post-created', post: any): void;
  (e: 'open-live'): void;
  (e: 'open-reel-creator'): void;
}>();

const isExpanded = ref(false);
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const audienceDropdownRef = ref<HTMLDivElement | null>(null);
const content = ref('');
const audience = ref<'public' | 'friends' | 'only_me'>('public');
const showAudienceDropdown = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');
const mediaFiles = ref<MediaItem[]>([]);

// User first name display
const userName = computed(() => {
  if (props.user?.name) {
    return props.user.name.split(' ')[0];
  }
  return 'friend';
});

// Audience options configuration
const audienceOptions = [
  { value: 'public', label: 'Public', icon: '🌐', desc: 'Anyone on or off Bondhoo' },
  { value: 'friends', label: 'Friends', icon: '👥', desc: 'Your friends on Bondhoo' },
  { value: 'only_me', label: 'Only Me', icon: '🔒', desc: 'Only you can see this' },
] as const;

const currentAudienceOption = computed(() => {
  return audienceOptions.find((o) => o.value === audience.value) || audienceOptions[0];
});

const selectAudience = (val: 'public' | 'friends' | 'only_me') => {
  audience.value = val;
  showAudienceDropdown.value = false;
};

// Check if the post has any meaningful content to enable submit
const isPostEmpty = computed(() => {
  return (
    !content.value.trim() &&
    mediaFiles.value.length === 0 &&
    !selectedFeeling.value &&
    taggedUsers.value.length === 0
  );
});

// Expand composer and focus textarea
const expandComposer = () => {
  isExpanded.value = true;
  nextTick(() => {
    textareaRef.value?.focus();
  });
};

// Actions triggers
const triggerLive = () => {
  emit('open-live');
};

const triggerReel = () => {
  emit('open-reel-creator');
};

// Feelings & Activities state
const showFeelingPicker = ref(false);
const feelingTab = ref<'feelings' | 'activities'>('feelings');
const selectedFeeling = ref('');
const feelingSearchQuery = ref('');

const allFeelings = [
  { name: 'Happy', emoji: '😊', label: 'feeling happy 😊' },
  { name: 'Blessed', emoji: '🙏', label: 'feeling blessed 🙏' },
  { name: 'Loved', emoji: '🥰', label: 'feeling loved 🥰' },
  { name: 'Excited', emoji: '🤩', label: 'feeling excited 🤩' },
  { name: 'Proud', emoji: '🦁', label: 'feeling proud 🦁' },
  { name: 'Celebrating', emoji: '🎉', label: 'celebrating 🎉' },
  { name: 'Cool', emoji: '😎', label: 'feeling cool 😎' },
  { name: 'In Love', emoji: '❤️', label: 'feeling in love ❤️' },
  { name: 'Thankful', emoji: '💐', label: 'feeling thankful 💐' },
  { name: 'Crazy', emoji: '🤪', label: 'feeling crazy 🤪' },
  { name: 'Tired', emoji: '😴', label: 'feeling tired 😴' },
  { name: 'Sad', emoji: '😢', label: 'feeling sad 😢' },
  { name: 'Relaxed', emoji: '😌', label: 'feeling relaxed 😌' },
  { name: 'Motivated', emoji: '💪', label: 'feeling motivated 💪' },
  { name: 'Hopeful', emoji: '🌟', label: 'feeling hopeful 🌟' },
  { name: 'Grateful', emoji: '🙌', label: 'feeling grateful 🙌' },
  { name: 'Wonderful', emoji: '✨', label: 'feeling wonderful ✨' },
  { name: 'Peaceful', emoji: '🕊️', label: 'feeling peaceful 🕊️' },
];

const allActivities = [
  { name: 'Celebrating', emoji: '🥳', label: 'celebrating 🥳' },
  { name: 'Traveling to', emoji: '✈️', label: 'traveling to ✈️' },
  { name: 'Watching', emoji: '🎬', label: 'watching 🎬' },
  { name: 'Listening to', emoji: '🎧', label: 'listening to 🎧' },
  { name: 'Playing', emoji: '🎮', label: 'playing 🎮' },
  { name: 'Reading', emoji: '📚', label: 'reading 📚' },
  { name: 'Eating', emoji: '🍕', label: 'eating 🍕' },
  { name: 'Drinking', emoji: '☕', label: 'drinking ☕' },
  { name: 'Working out', emoji: '💪', label: 'working out 💪' },
  { name: 'Learning', emoji: '🧠', label: 'learning 🧠' },
  { name: 'Thinking about', emoji: '💭', label: 'thinking about 💭' },
  { name: 'Supporting', emoji: '🤝', label: 'supporting 🤝' },
];

const filteredFeelings = computed(() => {
  const list = feelingTab.value === 'feelings' ? allFeelings : allActivities;
  const q = feelingSearchQuery.value.trim().toLowerCase();
  if (!q) return list;
  return list.filter((i) => i.name.toLowerCase().includes(q) || i.label.toLowerCase().includes(q));
});

const toggleFeelingPicker = () => {
  expandComposer();
  showFeelingPicker.value = !showFeelingPicker.value;
  showTagPicker.value = false;
  showAudienceDropdown.value = false;
};

const chooseFeeling = (label: string) => {
  selectedFeeling.value = label;
  showFeelingPicker.value = false;
};

// Tag friends state
const showTagPicker = ref(false);
const tagSearchQuery = ref('');
const searchResults = ref<any[]>([]);
const taggedUsers = ref<Array<{ id: number; name: string; username: string; avatar_url?: string }>>([]);
const isSearchingTags = ref(false);
let searchDebounceTimeout: any = null;

const toggleTagPicker = () => {
  expandComposer();
  showTagPicker.value = !showTagPicker.value;
  showFeelingPicker.value = false;
  showAudienceDropdown.value = false;
  if (showTagPicker.value && searchResults.value.length === 0) {
    searchTaggableUsers();
  }
};

const debouncedSearchUsers = () => {
  if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
  searchDebounceTimeout = setTimeout(() => {
    searchTaggableUsers();
  }, 250);
};

const searchTaggableUsers = async () => {
  isSearchingTags.value = true;
  try {
    const q = encodeURIComponent(tagSearchQuery.value.trim());
    const res = await fetch(`/api/v1/posts/taggable-users?q=${q}&limit=20`, {
      headers: { 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (res.ok && data.data) {
      searchResults.value = data.data;
    }
  } catch (err) {
    console.error('Failed to search taggable users:', err);
  } finally {
    isSearchingTags.value = false;
  }
};

const toggleTagUser = (u: any) => {
  const index = taggedUsers.value.findIndex((t) => t.id === u.id);
  if (index > -1) {
    taggedUsers.value.splice(index, 1);
  } else {
    taggedUsers.value.push({
      id: u.id,
      name: u.name,
      username: u.username,
      avatar_url: u.avatar_url,
    });
  }
};

const removeTaggedUser = (id: number) => {
  taggedUsers.value = taggedUsers.value.filter((t) => t.id !== id);
};

// File handling for Photos & Videos
const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!target.files || target.files.length === 0) return;

  expandComposer();
  errorMessage.value = '';

  for (let i = 0; i < target.files.length; i++) {
    const file = target.files[i];

    // Check size limit: 50MB
    if (file.size > 50 * 1024 * 1024) {
      errorMessage.value = `File "${file.name}" exceeds the 50MB limit.`;
      continue;
    }

    const isVideo = file.type.startsWith('video/');
    mediaFiles.value.push({
      file,
      url: URL.createObjectURL(file),
      isVideo,
      name: file.name,
    });
  }

  // Clear target so same file can be re-selected if removed
  target.value = '';
};

const removeMedia = (index: number) => {
  const removed = mediaFiles.value.splice(index, 1);
  if (removed[0]?.url) {
    URL.revokeObjectURL(removed[0].url);
  }
};

// Close popovers on click outside
const handleClickOutside = (e: MouseEvent) => {
  if (audienceDropdownRef.value && !audienceDropdownRef.value.contains(e.target as Node)) {
    showAudienceDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  // Cleanup any lingering object URLs
  mediaFiles.value.forEach((m) => URL.revokeObjectURL(m.url));
});

// Submit Post
const submitPost = async () => {
  if (isPostEmpty.value) return;

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

    // Step 1: Upload attached media files if any
    const mediaIds: number[] = [];
    if (mediaFiles.value.length > 0) {
      for (const item of mediaFiles.value) {
        const formData = new FormData();
        formData.append('file', item.file);
        formData.append('collection', 'post');

        const uploadRes = await fetch('/api/v1/media/upload', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: formData,
        });

        const uploadData = await uploadRes.json();
        if (uploadRes.ok && (uploadData.data?.id || uploadData.data?.media_id)) {
          mediaIds.push(uploadData.data.id || uploadData.data.media_id);
        } else {
          errorMessage.value = uploadData.message || `Failed to upload "${item.name}".`;
          isSubmitting.value = false;
          return;
        }
      }
    }

    // Step 2: Create post
    const res = await fetch('/api/v1/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        content: content.value.trim() || undefined,
        audience: audience.value,
        feeling_activity: selectedFeeling.value || undefined,
        tagged_user_ids: taggedUsers.value.length > 0 ? taggedUsers.value.map((u) => u.id) : undefined,
        media_ids: mediaIds.length > 0 ? mediaIds : undefined,
      }),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      // Emit real-time post creation event to Feed/Profile
      emit('post-created', data.data);

      // Reset state cleanly
      content.value = '';
      mediaFiles.value.forEach((m) => URL.revokeObjectURL(m.url));
      mediaFiles.value = [];
      selectedFeeling.value = '';
      taggedUsers.value = [];
      showFeelingPicker.value = false;
      showTagPicker.value = false;
      showAudienceDropdown.value = false;
      isExpanded.value = false;
    } else {
      errorMessage.value = data.message || 'Failed to publish post. Please check required fields.';
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred while creating post.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>
