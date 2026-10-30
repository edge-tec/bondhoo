<template>
  <AppLayout
    current-route="feed"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
  >
    <!-- Contextual Tab / Search Filter Header Banner (Saved Posts, Memories, Events, Search) -->
    <div
      v-if="(activeTab && activeTab !== 'feed') || searchQuery"
      class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 mb-4 shadow-xs flex flex-wrap items-center justify-between gap-3"
    >
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center shrink-0">
          <svg v-if="activeTab === 'saved'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
          <svg v-else-if="activeTab === 'memories'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else-if="activeTab === 'events'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <div class="min-w-0">
          <h2 class="text-base font-bold text-[#172033] dark:text-white truncate">
            <template v-if="searchQuery">Search Results for "{{ searchQuery }}"</template>
            <template v-else-if="activeTab === 'saved'">Saved Posts</template>
            <template v-else-if="activeTab === 'memories'">Your Memories</template>
            <template v-else-if="activeTab === 'events'">Upcoming Community Events</template>
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
            <template v-if="searchQuery">Showing matching posts and updates across Jugajug</template>
            <template v-else-if="activeTab === 'saved'">Bookmarked posts and discussions for quick access</template>
            <template v-else-if="activeTab === 'memories'">Look back on moments and posts you have shared</template>
            <template v-else-if="activeTab === 'events'">Discover and join upcoming events across the platform</template>
          </p>
        </div>
      </div>
      <a
        href="/dashboard"
        class="px-3.5 py-2 rounded-xl text-xs font-bold text-[#1877F2] bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-colors shrink-0"
      >
        Back to All Feed
      </a>
    </div>

    <!-- Dedicated Mobile/Desktop Events Grid when activeTab === 'events' -->
    <div
      v-if="activeTab === 'events' && upcomingEvents && upcomingEvents.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4"
    >
      <a
        v-for="ev in upcomingEvents"
        :key="ev.id"
        :href="'/events/' + ev.id"
        class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-3.5 flex items-center gap-3.5 hover:border-[#1877F2] transition-all shadow-2xs"
      >
        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] flex flex-col items-center justify-center shrink-0 font-bold">
          <span class="text-[10px] uppercase tracking-wider">{{ ev.month }}</span>
          <span class="text-sm leading-none mt-0.5">{{ ev.day }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-bold text-[#172033] dark:text-white truncate">{{ ev.title }}</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ ev.time_formatted }}</p>
          <p class="text-[11px] text-slate-400 truncate">{{ ev.address }}</p>
        </div>
      </a>
    </div>

    <!-- 1. Stories & Reels Carousel Tray -->
    <StoriesTray
      :current-user="currentUser"
      :stories="stories"
      :reels="reelsList"
      @open-live="isLiveModalOpen = true"
      @open-reel-creator="isReelCreatorOpen = true"
      @toast="showToast"
    />

    <!-- 2. Post Composer (Live Video + Reel + Media + Feelings + Tagging) -->
    <PostComposer
      :user="currentUser"
      @post-created="handlePostCreated"
      @open-live="isLiveModalOpen = true"
      @open-reel-creator="isReelCreatorOpen = true"
    />

    <!-- 3. Reels Upload Modal Studio -->
    <ReelCreatorModal
      :is-open="isReelCreatorOpen"
      :current-user="currentUser"
      @close="isReelCreatorOpen = false"
      @reel-created="handleReelCreated"
    />

    <!-- 4. Realtime Camera Live Broadcasting Studio -->
    <LiveStudioModal
      :is-open="isLiveModalOpen"
      :current-user="currentUser"
      @close="isLiveModalOpen = false"
      @toast="showToast"
    />

    <!-- Error Banner -->
    <div v-if="fetchError" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-4 rounded-2xl mb-4 text-sm text-red-700 dark:text-red-300 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ fetchError }}</span>
      </div>
      <button @click="loadMorePosts" class="text-xs font-semibold underline hover:no-underline">Retry</button>
    </div>

    <!-- 3. Post Feed Stream -->
    <div v-if="postList.length > 0" class="space-y-4">
      <PostCard
        v-for="post in postList"
        :key="post.id"
        :post="post"
        :current-user-id="currentUser?.id"
        @post-deleted="handlePostDeleted"
        @toast="showToast"
      />
    </div>

    <!-- 4. Loading Skeletons -->
    <div v-if="isLoading" class="space-y-4">
      <PostSkeleton />
      <PostSkeleton />
    </div>

    <!-- 5. Empty Feed State -->
    <EmptyState
      v-if="!isLoading && postList.length === 0"
      title="No Posts in Your Feed Yet"
      description="Connect with friends or share your first post to bring your feed to life!"
      action-label="Find Friends"
      action-link="/friends"
    />

    <!-- 6. Load More Older Posts Trigger -->
    <div v-if="hasMore && !isLoading" class="text-center py-6">
      <button
        @click="loadMorePosts"
        class="px-6 py-2.5 bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-800 hover:border-[#1877F2] rounded-xl text-xs font-bold text-[#172033] dark:text-slate-200 shadow-sm hover:shadow transition-all"
      >
        Load Older Posts
      </button>
    </div>

    <!-- Floating Toast Notification -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold text-white animate-in slide-in-from-bottom-5 duration-200"
      :class="toastType === 'error' ? 'bg-red-600' : (toastType === 'info' ? 'bg-slate-800' : 'bg-[#1877F2]')"
    >
      <svg v-if="toastType === 'success'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      <svg v-else-if="toastType === 'error'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      <span>{{ toastMessage }}</span>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import StoriesTray from '../Components/StoriesTray.vue';
import PostComposer from '../Components/PostComposer.vue';
import PostCard from '../Components/PostCard.vue';
import PostSkeleton from '../Components/PostSkeleton.vue';
import EmptyState from '../Components/EmptyState.vue';
import ReelCreatorModal from '../Components/Reels/ReelCreatorModal.vue';
import LiveStudioModal from '../Components/Live/LiveStudioModal.vue';
import type { PostItem, User, SharedProps, UserStoryGroup, SuggestedFriend, UpcomingEvent, ReelItem } from '../types';

interface FeedProps {
  posts: {
    data: PostItem[];
    next_cursor?: string | null;
  } | PostItem[];
  stories?: UserStoryGroup[];
  reels?: ReelItem[];
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
  activeTab?: string;
  searchQuery?: string;
}

const props = withDefaults(defineProps<FeedProps>(), {
  stories: () => [],
  reels: () => [],
  pendingRequests: () => [],
  contacts: () => [],
  suggestions: () => [],
  upcomingEvents: () => [],
  unreadCount: 0,
  unreadMessagesCount: 0,
  activeTab: 'feed',
  searchQuery: '',
});

const page = usePage<SharedProps>();
const currentUser = computed<User | null>(() => page.props.auth?.user || null);

const initialPosts = Array.isArray(props.posts) ? props.posts : (props.posts?.data || []);
const initialNextCursor = !Array.isArray(props.posts) ? (props.posts?.next_cursor || null) : null;

const postList = ref<PostItem[]>([...initialPosts]);
const reelsList = ref<ReelItem[]>([...(props.reels || [])]);
const nextCursor = ref<string | null>(initialNextCursor);
const isLoading = ref(false);
const hasMore = ref(Boolean(initialNextCursor));
const fetchError = ref<string | null>(null);

// Modal states for Reels and Live Studio
const isReelCreatorOpen = ref(false);
const isLiveModalOpen = ref(false);

// Toast notification state
const toastMessage = ref('');
const toastType = ref<'success' | 'error' | 'info'>('success');
let toastTimer: any = null;

const showToast = (payload: { message: string; type?: 'success' | 'error' | 'info' }) => {
  toastMessage.value = payload.message;
  toastType.value = payload.type || 'success';
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastMessage.value = '';
  }, 3500);
};

const handleReelCreated = (newReel: ReelItem) => {
  isReelCreatorOpen.value = false;
  reelsList.value.unshift(newReel);
  showToast({ message: 'Your Reel was successfully published!', type: 'success' });
};

const handlePostCreated = (newPost: any) => {
  postList.value.unshift(newPost);
  showToast({ message: 'Your post was successfully published!', type: 'success' });
};

const handlePostDeleted = (deletedId: number) => {
  postList.value = postList.value.filter((p) => p.id !== deletedId);
  showToast({ message: 'Post was removed from feed.', type: 'info' });
};

const loadMorePosts = async () => {
  if (!nextCursor.value || isLoading.value) return;

  isLoading.value = true;
  fetchError.value = null;

  try {
    const res = await fetch(`/api/v1/feed?cursor=${encodeURIComponent(nextCursor.value)}`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await res.json();
    if (res.ok && data.data) {
      postList.value.push(...data.data);
      nextCursor.value = data.next_cursor || null;
      hasMore.value = Boolean(data.next_cursor);
    } else {
      fetchError.value = data.message || 'Failed to load more posts.';
    }
  } catch (err: any) {
    fetchError.value = err.message || 'Network error occurred while fetching posts.';
  } finally {
    isLoading.value = false;
  }
};
</script>
