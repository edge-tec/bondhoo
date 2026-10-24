<template>
  <AppLayout
    current-route="profile"
    :unread-count="unreadCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- Master Profile Container: Central Profile Section + Right Profile Sidebar -->
    <div class="flex flex-col xl:flex-row items-start gap-6">
      <!-- Central Profile Section (Profile Header Card + Sub-Columns) -->
      <div class="flex-1 min-w-0 w-full space-y-6">
        <!-- 1. Profile Header Card -->
        <ProfileHeader
          :user="profileUser"
          :profile-details="profileDetails"
          :relationship-state="relationshipState"
          :action-loading="actionLoading"
          :is-owner="isOwnProfile"
          :mutual-count="mutualFriendsCount"
          :active-tab="activeTab"
          :friends-count="friendsCount"
          :posts-count="postsCount"
          :is-following="isFollowingUser"
          @update:active-tab="activeTab = $event"
          @send-friend-request="sendFriendRequest"
          @cancel-friend-request="cancelFriendRequest"
          @accept-friend-request="acceptFriendRequest"
          @decline-friend-request="declineFriendRequest"
          @unfriend="unfriend"
          @open-edit="isEditModalOpen = true"
          @avatar-uploaded="handleAvatarUploaded"
          @cover-uploaded="handleCoverUploaded"
          @toggle-follow="toggleFollowUser"
          @block-user="confirmBlockUser"
          @report-profile="openReportUser"
          @toast="showToast($event.message, $event.type)"
        />

        <!-- 2. Content Switching by Active Tab -->

        <!-- TAB 1: Posts (Main 2-Subcolumn Layout matching Mockup) -->
        <div v-if="activeTab === 'posts'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Left Sub-Column: About Card + Friends Preview Card -->
          <div class="lg:col-span-5 space-y-6">
            <!-- About Card -->
            <ProfileAboutCard
              :work="profileDetails?.work"
              :education="profileDetails?.education"
              :city="profileDetails?.city"
              :hometown="profileDetails?.hometown"
              :relationship-status="profileDetails?.relationship_status"
              :website="profileDetails?.website"
              :is-owner="isOwnProfile"
              @open-edit="isEditModalOpen = true"
            />

            <!-- Friends Preview Card (2x3 Grid) -->
            <ProfileFriendsPreviewCard
              :friends="friendsList"
              :total-friends="friendsCount"
              :mutual-count="mutualFriendsCount"
              @see-all="activeTab = 'friends'"
            />
          </div>

          <!-- Center Sub-Column: Post Composer + Profile News Feed -->
          <div class="lg:col-span-7 space-y-6">
            <!-- Post Composer: shown if viewer is owner -->
            <PostComposer
              v-if="isOwnProfile"
              :user="currentUser"
              @post-created="handlePostCreated"
              @open-live="isLiveModalOpen = true"
              @open-reel-creator="isReelCreatorOpen = true"
            />

            <!-- Vertically Stacked Profile News Feed -->
            <div v-if="userPosts.length > 0" class="space-y-4">
              <PostCard
                v-for="post in userPosts"
                :key="post.id"
                :post="post"
                :current-user-id="currentUser?.id"
                @post-deleted="handlePostDeleted"
                @share-post="openShareModal"
                @report-post="openReportPost"
                @toast="showToast($event.message, $event.type)"
              />
            </div>

            <!-- Empty Posts State -->
            <EmptyState
              v-else
              title="No Posts to Show"
              description="This user hasn't published any posts yet."
              :action-label="isOwnProfile ? 'Create Post' : undefined"
            />
          </div>
        </div>

        <!-- TAB 2: About (Expanded View) -->
        <div v-else-if="activeTab === 'about'" class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm space-y-6">
          <div class="border-b border-[#E4E7EC] dark:border-slate-800 pb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-[#172033] dark:text-slate-100">About {{ profileUser.name }}</h3>
            <button
              v-if="isOwnProfile"
              type="button"
              @click="isEditModalOpen = true"
              class="text-xs font-semibold text-[#1877F2] hover:underline cursor-pointer"
            >
              Edit Details
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div class="space-y-4">
              <h4 class="font-bold text-xs uppercase tracking-wider text-[#667085] dark:text-slate-400">Work & Education</h4>
              <div v-if="profileDetails?.work" class="flex items-center gap-3">
                <span class="text-base">💼</span>
                <span>Works at <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.work }}</strong></span>
              </div>
              <div v-if="profileDetails?.education" class="flex items-center gap-3">
                <span class="text-base">🎓</span>
                <span>Studied at <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.education }}</strong></span>
              </div>
            </div>

            <div class="space-y-4">
              <h4 class="font-bold text-xs uppercase tracking-wider text-[#667085] dark:text-slate-400">Places Lived</h4>
              <div v-if="profileDetails?.city" class="flex items-center gap-3">
                <span class="text-base">🏡</span>
                <span>Lives in <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.city }}</strong></span>
              </div>
              <div v-if="profileDetails?.hometown" class="flex items-center gap-3">
                <span class="text-base">📍</span>
                <span>From <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.hometown }}</strong></span>
              </div>
            </div>

            <div class="space-y-4">
              <h4 class="font-bold text-xs uppercase tracking-wider text-[#667085] dark:text-slate-400">Basic Info</h4>
              <div v-if="profileDetails?.birth_date" class="flex items-center gap-3">
                <span class="text-base">🎂</span>
                <span>Born on <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.birth_date }}</strong></span>
              </div>
              <div v-if="profileDetails?.gender" class="flex items-center gap-3">
                <span class="text-base">👤</span>
                <span class="capitalize">Gender: <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.gender }}</strong></span>
              </div>
              <div v-if="profileDetails?.relationship_status" class="flex items-center gap-3">
                <span class="text-base">❤️</span>
                <span class="capitalize">Status: <strong class="text-[#172033] dark:text-slate-100">{{ profileDetails.relationship_status }}</strong></span>
              </div>
            </div>

            <div class="space-y-4">
              <h4 class="font-bold text-xs uppercase tracking-wider text-[#667085] dark:text-slate-400">Contact & Membership</h4>
              <div v-if="profileDetails?.website" class="flex items-center gap-3">
                <span class="text-base">🌐</span>
                <span>Website: <a :href="profileDetails.website.startsWith('http') ? profileDetails.website : 'https://' + profileDetails.website" target="_blank" rel="noopener noreferrer" class="text-[#1877F2] hover:underline font-semibold">{{ profileDetails.website }}</a></span>
              </div>
              <div v-if="profileUser.email && isOwnProfile" class="flex items-center gap-3">
                <span class="text-base">✉️</span>
                <span>{{ profileUser.email }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-base">📅</span>
                <span>Joined {{ profileDetails?.joined_date || formatYear(profileUser.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: Friends (Full Grid) -->
        <div v-else-if="activeTab === 'friends'" class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-[#E4E7EC] dark:border-slate-800 pb-4">
            <h3 class="text-lg font-bold text-[#172033] dark:text-slate-100">
              All Friends ({{ friendsCount }})
            </h3>
          </div>

          <div v-if="friendsList && friendsList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <a
              v-for="friend in friendsList"
              :key="friend.id"
              :href="'/profile/' + friend.username"
              class="flex flex-col items-center p-3 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 hover:border-[#1877F2]/50 hover:shadow-sm transition-all group text-center"
            >
              <img
                :src="friend.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'"
                class="w-16 h-16 rounded-full object-cover group-hover:scale-105 transition-transform ring-2 ring-slate-100 dark:ring-slate-800"
                :alt="friend.name"
              />
              <h4 class="mt-2 text-xs font-semibold text-[#172033] dark:text-slate-100 truncate w-full group-hover:text-[#1877F2] transition-colors">
                {{ friend.name }}
              </h4>
              <p class="text-[11px] text-[#667085] truncate w-full">@{{ friend.username }}</p>
            </a>
          </div>

          <EmptyState
            v-else
            title="No Friends Yet"
            description="No friends connected to display for this profile."
          />
        </div>

        <!-- TAB 4: Photos (Full Gallery Grid with Lightbox Trigger) -->
        <div v-else-if="activeTab === 'photos'" class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-[#E4E7EC] dark:border-slate-800 pb-4">
            <h3 class="text-lg font-bold text-[#172033] dark:text-slate-100">Photos</h3>
          </div>

          <div v-if="photosList && photosList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div
              v-for="(photo, idx) in photosList"
              :key="idx"
              @click="openLightbox(idx)"
              class="aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-800 group relative cursor-pointer"
            >
              <img
                :src="photo.url"
                :alt="'Photo ' + (idx + 1)"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                loading="lazy"
              />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                </svg>
              </div>
            </div>
          </div>

          <EmptyState
            v-else
            title="No Photos"
            description="No photos uploaded to this profile yet."
          />
        </div>

        <!-- TAB 5: Videos (Gallery Grid) -->
        <div v-else-if="activeTab === 'videos'" class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-[#E4E7EC] dark:border-slate-800 pb-4">
            <h3 class="text-lg font-bold text-[#172033] dark:text-slate-100">
              Videos ({{ videosList.length }})
            </h3>
          </div>

          <div v-if="videosList && videosList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="video in videosList"
              :key="video.id"
              class="rounded-xl overflow-hidden bg-black/90 border border-[#E4E7EC] dark:border-slate-800 group relative shadow-xs"
            >
              <video
                :src="video.url"
                :poster="video.thumbnail_url || video.thumbnail"
                controls
                preload="metadata"
                class="w-full aspect-video object-cover"
              ></video>
            </div>
          </div>

          <EmptyState
            v-else
            title="No Videos Found"
            description="No published videos available on this profile."
          />
        </div>
      </div>

      <!-- 3. Right Profile Sidebar (Photos, Basic Info, Friends, Interests) -->
      <aside class="w-full xl:w-[320px] 2xl:w-[340px] shrink-0 space-y-6">
        <!-- Card 1: Photos Card (2x3 Grid with Lightbox Click) -->
        <ProfilePhotosCard
          :photos="photosList"
          @see-all-photos="activeTab = 'photos'"
          @photo-click="openLightbox($event.index)"
        />

        <!-- Card 2: Basic Information Card (Birthday, Gender, Relationship) -->
        <ProfileBasicInfoCard
          :birthday="profileDetails?.birth_date"
          :gender="profileDetails?.gender"
          :relationship-status="profileDetails?.relationship_status"
          :website="profileDetails?.website"
          :is-owner="isOwnProfile"
          @open-edit="isEditModalOpen = true"
        />

        <!-- Card 3: Friends Card (List with Mutual Counts & Add Friend) -->
        <ProfileFriendsCard
          :friends="friendsList"
          :current-user-id="currentUser?.id"
          @see-all-friends="activeTab = 'friends'"
        />

        <!-- Card 4: Interests Card (Pill Tags) -->
        <ProfileInterestsCard
          :interests="profileDetails?.interests"
          :is-owner="isOwnProfile"
          @open-edit="isEditModalOpen = true"
        />
      </aside>
    </div>

    <!-- Modals & Global Overlays -->

    <!-- Edit Profile Modal -->
    <EditProfileModal
      :is-open="isEditModalOpen"
      :user="profileUser"
      :profile-details="profileDetails"
      @close="isEditModalOpen = false"
      @updated="handleProfileUpdated"
    />

    <!-- Lightbox Photo Viewer Modal -->
    <PhotoLightboxModal
      :is-open="isLightboxOpen"
      :photos="photosList"
      :initial-index="lightboxIndex"
      @close="isLightboxOpen = false"
    />

    <!-- Report Modal -->
    <ReportModal
      :is-open="isReportModalOpen"
      :target-type="reportTargetType"
      :target-id="reportTargetId"
      @close="isReportModalOpen = false"
      @submitted="showToast('Report submitted successfully. Thank you!', 'success')"
    />

    <!-- Share Post Modal -->
    <SharePostModal
      :is-open="isShareModalOpen"
      :post="activeSharePost"
      @close="isShareModalOpen = false"
      @shared="handlePostShared"
    />

    <!-- Reel Creator Modal Studio -->
    <ReelCreatorModal
      :is-open="isReelCreatorOpen"
      :current-user="currentUser"
      @close="isReelCreatorOpen = false"
      @reel-created="handleReelCreated"
    />

    <!-- Realtime Camera Live Broadcasting Studio -->
    <LiveStudioModal
      :is-open="isLiveModalOpen"
      :current-user="currentUser"
      @close="isLiveModalOpen = false"
      @toast="showToast($event.message, $event.type)"
    />

    <!-- Global Toast Feedback -->
    <ToastNotification
      :show="toast.show"
      :message="toast.message"
      :type="toast.type"
      @close="toast.show = false"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import ProfileHeader from '../Components/Profile/ProfileHeader.vue';
import ProfileAboutCard from '../Components/Profile/ProfileAboutCard.vue';
import ProfileFriendsPreviewCard from '../Components/Profile/ProfileFriendsPreviewCard.vue';
import ProfilePhotosCard from '../Components/Profile/ProfilePhotosCard.vue';
import ProfileBasicInfoCard from '../Components/Profile/ProfileBasicInfoCard.vue';
import ProfileFriendsCard from '../Components/Profile/ProfileFriendsCard.vue';
import ProfileInterestsCard from '../Components/Profile/ProfileInterestsCard.vue';
import EditProfileModal from '../Components/Profile/EditProfileModal.vue';
import PhotoLightboxModal from '../Components/Profile/PhotoLightboxModal.vue';
import ReportModal from '../Components/Profile/ReportModal.vue';
import ToastNotification from '../Components/ToastNotification.vue';
import PostComposer from '../Components/PostComposer.vue';
import PostCard from '../Components/PostCard.vue';
import EmptyState from '../Components/EmptyState.vue';
import SharePostModal from '../Components/Profile/SharePostModal.vue';
import ReelCreatorModal from '../Components/Reels/ReelCreatorModal.vue';
import LiveStudioModal from '../Components/Live/LiveStudioModal.vue';
import type { User, PostItem, SharedProps } from '../types';

interface ProfileProps {
  user: User & { created_at?: string };
  profile_details?: {
    work?: string | null;
    education?: string | null;
    city?: string | null;
    hometown?: string | null;
    relationship_status?: string | null;
    birth_date?: string | null;
    gender?: string | null;
    website?: string | null;
    interests?: string[];
    joined_date?: string | null;
  };
  mutual_friends_count?: number;
  is_following?: boolean;
  photos?: Array<{ id?: number; url: string; type?: string }>;
  videos?: Array<{ id?: number; url: string; thumbnail_url?: string; thumbnail?: string; type?: string }>;
  posts?: {
    data: PostItem[];
    next_cursor?: string | null;
  } | PostItem[];
  friends?: User[];
  relationship?: 'self' | 'friends' | 'sent' | 'received' | 'none' | 'blocked';
  stats?: {
    posts_count?: number;
    friends_count?: number;
    followers_count?: number;
  };
  unreadCount?: number;
  pendingRequests?: any[];
  contacts?: User[];
}

const props = withDefaults(defineProps<ProfileProps>(), {
  friends: () => [],
  photos: () => [],
  videos: () => [],
  relationship: 'none',
  is_following: false,
  mutual_friends_count: 0,
  pendingRequests: () => [],
  contacts: () => [],
  unreadCount: 0,
});

const page = usePage<SharedProps>();
const currentUser = computed<User | null>(() => page.props.auth?.user || null);

const profileUser = ref(props.user);
const profileDetails = ref(props.profile_details || {});
const activeTab = ref('posts');
const relationshipState = ref(props.relationship);
const isFollowingUser = ref(props.is_following);
const actionLoading = ref(false);
const mutualFriendsCount = ref(props.mutual_friends_count);

const initialPosts = Array.isArray(props.posts) ? props.posts : (props.posts?.data || []);
const userPosts = ref<PostItem[]>([...initialPosts]);
const friendsList = ref<User[]>([...props.friends]);
const photosList = ref([...props.photos]);
const videosList = ref([...props.videos]);

// Modals State
const isEditModalOpen = ref(false);
const isLightboxOpen = ref(false);
const lightboxIndex = ref(0);
const isReportModalOpen = ref(false);
const reportTargetType = ref<'user' | 'post' | 'comment'>('user');
const reportTargetId = ref(props.user.id);
const isShareModalOpen = ref(false);
const activeSharePost = ref<any>(null);
const isReelCreatorOpen = ref(false);
const isLiveModalOpen = ref(false);

const handleReelCreated = (newReel: any) => {
  isReelCreatorOpen.value = false;
  showToast('Your Reel was successfully published!', 'success');
};

const openShareModal = (post: any) => {
  activeSharePost.value = post;
  isShareModalOpen.value = true;
};

const handlePostShared = (postId: number) => {
  showToast('Post shared to your feed successfully!', 'success');
};

const openReportPost = (post: any) => {
  reportTargetType.value = 'post';
  reportTargetId.value = post.id;
  isReportModalOpen.value = true;
};

// Toast Notification State
const toast = ref<{ show: boolean; message: string; type: 'success' | 'error' | 'info' }>({
  show: false,
  message: '',
  type: 'success',
});

const showToast = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
  toast.value = { show: true, message, type };
};

const isOwnProfile = computed(() => {
  return currentUser.value && currentUser.value.id === profileUser.value.id;
});

const postsCount = computed(() => props.stats?.posts_count ?? userPosts.value.length);
const friendsCount = computed(() => props.stats?.friends_count ?? profileUser.value.friends_count ?? friendsList.value.length);
const followersCount = computed(() => props.stats?.followers_count ?? profileUser.value.followers_count ?? 0);

const formatYear = (dateStr?: string) => {
  if (!dateStr) return '2026';
  return new Date(dateStr).getFullYear();
};

const handlePostCreated = (newPost: any) => {
  userPosts.value.unshift(newPost);
  showToast('Post created successfully!', 'success');
};

const handlePostDeleted = (deletedId: number) => {
  userPosts.value = userPosts.value.filter((p) => p.id !== deletedId);
  showToast('Post deleted successfully.', 'info');
};

const handleProfileUpdated = (updatedPayload: any) => {
  profileUser.value.name = updatedPayload.name;
  profileUser.value.bio = updatedPayload.bio;
  profileDetails.value = {
    ...profileDetails.value,
    ...updatedPayload,
  };
  showToast('Profile information updated successfully!', 'success');
};

const handleAvatarUploaded = (newUrl: string) => {
  profileUser.value.avatar_url = newUrl;
  // Also add to photos if not present
  photosList.value.unshift({ url: newUrl, type: 'avatar' });
};

const handleCoverUploaded = (newUrl: string) => {
  profileUser.value.cover_image_url = newUrl;
  photosList.value.unshift({ url: newUrl, type: 'cover' });
};

const openLightbox = (index: number) => {
  lightboxIndex.value = index;
  isLightboxOpen.value = true;
};

const openReportUser = () => {
  reportTargetType.value = 'user';
  reportTargetId.value = profileUser.value.id;
  isReportModalOpen.value = true;
};

const confirmBlockUser = async () => {
  if (!confirm(`Are you sure you want to block ${profileUser.value.name}? They will no longer be able to message you or see your profile.`)) {
    return;
  }

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/profile/${profileUser.value.username}/block`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      showToast(`${profileUser.value.name} has been blocked.`, 'info');
      relationshipState.value = 'blocked';
    } else {
      showToast('Failed to block user.', 'error');
    }
  } catch (err: any) {
    showToast(err.message || 'Error blocking user.', 'error');
  }
};

const toggleFollowUser = async () => {
  const method = isFollowingUser.value ? 'DELETE' : 'POST';
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}/follow`, {
      method,
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      isFollowingUser.value = !isFollowingUser.value;
      showToast(isFollowingUser.value ? `Following ${profileUser.value.name}` : `Unfollowed ${profileUser.value.name}`, 'success');
    } else {
      showToast('Could not update follow status.', 'error');
    }
  } catch (err: any) {
    showToast(err.message || 'Network error.', 'error');
  }
};

const sendFriendRequest = async () => {
  actionLoading.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}/request`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      relationshipState.value = 'sent';
      showToast('Friend request sent!', 'success');
    } else {
      const data = await res.json();
      showToast(data.message || 'Could not send friend request.', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Failed to send request.', 'error');
  } finally {
    actionLoading.value = false;
  }
};

const cancelFriendRequest = async () => {
  actionLoading.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}/cancel`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      relationshipState.value = 'none';
      showToast('Friend request cancelled.', 'info');
    } else {
      showToast('Could not cancel friend request.', 'error');
    }
  } catch (err) {
    console.error(err);
  } finally {
    actionLoading.value = false;
  }
};

const acceptFriendRequest = async () => {
  actionLoading.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}/accept`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      relationshipState.value = 'friends';
      showToast(`You and ${profileUser.value.name} are now friends!`, 'success');
    } else {
      showToast('Could not accept friend request.', 'error');
    }
  } catch (err) {
    console.error(err);
  } finally {
    actionLoading.value = false;
  }
};

const declineFriendRequest = async () => {
  actionLoading.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}/decline`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      relationshipState.value = 'none';
      showToast('Friend request declined.', 'info');
    } else {
      showToast('Could not decline friend request.', 'error');
    }
  } catch (err) {
    console.error(err);
  } finally {
    actionLoading.value = false;
  }
};

const unfriend = async () => {
  if (!confirm(`Are you sure you want to remove ${profileUser.value.name} from your friends?`)) return;
  actionLoading.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/friends/${profileUser.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      relationshipState.value = 'none';
      if (friendsCount.value > 0) {
        profileUser.value.friends_count = Math.max(0, (profileUser.value.friends_count || 1) - 1);
      }
      showToast('Removed from friends.', 'info');
    } else {
      showToast('Could not remove friend.', 'error');
    }
  } catch (err) {
    console.error(err);
  } finally {
    actionLoading.value = false;
  }
};
</script>
