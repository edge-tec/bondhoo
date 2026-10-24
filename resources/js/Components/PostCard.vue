<template>
  <article class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 sm:p-5 mb-4 shadow-sm transition-all hover:shadow-md">
    <!-- 1. Post Header: Author, Timestamp, Privacy & Options Menu -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-3">
        <a :href="'/profile/' + (post.author?.username || 'user')" class="group shrink-0">
          <img
            :src="post.author?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
            alt="Avatar"
            class="w-10 h-10 rounded-full object-cover ring-2 ring-blue-500/10 group-hover:scale-105 transition-transform"
          />
        </a>
        <div>
          <div class="flex items-center gap-1.5 flex-wrap">
            <a :href="'/profile/' + (post.author?.username || 'user')" class="font-bold text-sm text-[#172033] dark:text-slate-100 hover:text-[#1877F2] transition-colors">
              {{ post.author?.name || 'Community Member' }}
            </a>
            <!-- Optional Verified Check Badge -->
            <svg v-if="post.author?.is_verified" class="w-4 h-4 text-[#1877F2] fill-current" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span v-if="post.feeling_activity" class="text-xs text-slate-500 font-normal">
              is {{ post.feeling_activity }}
            </span>
            <span v-if="post.tagged_users && post.tagged_users.length > 0" class="text-xs text-slate-500 font-normal">
              <span v-if="!post.feeling_activity">is</span> with
              <template v-for="(tu, tIdx) in post.tagged_users" :key="tu.id">
                <a :href="'/profile/' + (tu.username || tu.id)" class="font-semibold text-slate-800 dark:text-slate-200 hover:text-[#1877F2] hover:underline">
                  {{ tu.name }}
                </a>
                <span v-if="tIdx < post.tagged_users.length - 2">, </span>
                <span v-else-if="tIdx === post.tagged_users.length - 2"> and </span>
              </template>
            </span>
            <span v-if="post.location" class="text-xs text-slate-500 font-normal">
              in <span class="font-medium text-slate-700 dark:text-slate-300">{{ post.location }}</span>
            </span>
          </div>
          <div class="flex items-center gap-1.5 text-xs text-slate-400">
            <span>{{ formatRelativeTime(post.created_at) }}</span>
            <span>•</span>
            <span class="inline-flex items-center gap-1 font-medium">
              <span v-if="post.audience === 'public'">🌐</span>
              <span v-else-if="post.audience === 'friends'">👥</span>
              <span v-else>🔒</span>
              <span class="capitalize">{{ post.audience || 'public' }}</span>
            </span>
            <span v-if="post.is_pinned" class="ml-1 text-[#1877F2] font-semibold flex items-center gap-0.5">
              📌 Pinned
            </span>
          </div>
        </div>
      </div>

      <!-- 3-Dots Context Menu -->
      <div class="relative" ref="menuRef">
        <button
          @click="isMenuOpen = !isMenuOpen"
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          title="More Options"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
          </svg>
        </button>

        <div
          v-if="isMenuOpen"
          class="absolute right-0 mt-1 w-52 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-[#E4E7EC] dark:border-slate-800 p-1.5 z-20 text-xs font-semibold"
        >
          <button
            @click="copyPostLink"
            class="w-full flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-left"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Copy Link
          </button>

          <button
            @click="toggleSave"
            class="w-full flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-left"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            {{ isSaved ? 'Unsave Post' : 'Save Post' }}
          </button>

          <button
            v-if="isAuthor"
            @click="startEditing"
            class="w-full flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors text-left"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Post
          </button>

          <button
            v-if="!isAuthor"
            @click="openReportModal"
            class="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-lg transition-colors text-left"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Report Post
          </button>
          
          <button
            v-if="isAuthor"
            @click="deletePost"
            class="w-full flex items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors text-left"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Post
          </button>
        </div>
      </div>
    </div>

    <!-- 2. Post Text Content & Inline Edit -->
    <div v-if="isEditing" class="mb-3 space-y-2">
      <textarea
        v-model="editedContent"
        rows="3"
        class="w-full bg-[#F5F7FB] dark:bg-slate-800 text-sm text-[#172033] dark:text-slate-100 rounded-xl p-3 border border-[#E4E7EC] dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2]"
      ></textarea>
      <div class="flex items-center justify-end gap-2 text-xs font-semibold">
        <button
          type="button"
          @click="isEditing = false"
          class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="saveEdit"
          :disabled="isSavingEdit"
          class="px-4 py-1.5 rounded-lg bg-[#1877F2] text-white hover:bg-[#1666D8] disabled:opacity-50 transition-colors"
        >
          {{ isSavingEdit ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>
    <p v-else-if="currentContent" class="text-sm text-[#172033] dark:text-slate-200 whitespace-pre-line leading-relaxed mb-3">
      {{ currentContent }}
    </p>

    <!-- 3A. Shared Reel Attachment Card -->
    <div
      v-if="post.type === 'reel' || post.media_meta?.shared_reel_id"
      class="mb-3 rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md group/sharedreel"
    >
      <!-- Original Creator Attribution Header -->
      <div
        v-if="post.media_meta?.reel?.creator"
        class="px-4 py-2.5 bg-slate-950/40 backdrop-blur-md flex items-center justify-between border-b border-white/10 text-white"
      >
        <div class="flex items-center gap-2 min-w-0">
          <img
            :src="post.media_meta.reel.creator.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
            alt="Creator"
            class="w-6 h-6 rounded-full object-cover ring-1 ring-purple-500"
          />
          <div class="truncate">
            <span class="text-xs font-bold text-white hover:underline cursor-pointer">
              {{ post.media_meta.reel.creator.name }}
            </span>
            <span class="text-[10px] text-slate-300 ml-1.5 font-medium">Original Creator</span>
          </div>
        </div>

        <a
          :href="'/reels?reel=' + (post.media_meta.reel.id || post.media_meta.shared_reel_id)"
          class="text-[11px] font-bold text-purple-400 hover:text-purple-300 flex items-center gap-1 transition-colors"
        >
          <span>Watch Reel</span>
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </a>
      </div>

      <!-- Reel Video or Thumbnail Player Preview -->
      <div class="relative w-full aspect-[9/14] max-h-[500px] bg-black flex items-center justify-center overflow-hidden">
        <video
          v-if="post.media_meta?.reel?.video_url"
          :src="post.media_meta.reel.video_url"
          :poster="post.media_meta.reel.thumbnail_url"
          controls
          playsinline
          preload="metadata"
          class="w-full h-full object-contain"
        ></video>
        <div
          v-else-if="post.media_meta?.reel?.thumbnail_url"
          class="relative w-full h-full cursor-pointer"
          @click="openReelFromPost(post.media_meta?.shared_reel_id || post.media_meta?.reel?.id)"
        >
          <img
            :src="post.media_meta.reel.thumbnail_url"
            class="w-full h-full object-cover"
            alt="Reel preview"
          />
          <div class="absolute inset-0 flex items-center justify-center bg-black/30">
            <div class="w-14 h-14 rounded-full bg-purple-600/90 text-white flex items-center justify-center shadow-2xl group-hover/sharedreel:scale-110 transition-transform">
              <svg class="w-7 h-7 fill-current ml-0.5" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </div>
        </div>
        <div
          v-else
          class="py-12 px-4 text-center text-slate-400 flex flex-col items-center gap-2"
        >
          <svg class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <p class="text-xs font-semibold text-slate-300">This Reel is no longer available.</p>
          <p class="text-[11px] text-slate-500">It may have been removed or deleted by its creator.</p>
        </div>

        <!-- Reel Original Caption Overlay if any -->
        <div
          v-if="post.media_meta?.reel?.caption"
          class="absolute bottom-0 inset-x-0 p-3 bg-gradient-to-t from-black/90 via-black/50 to-transparent pointer-events-none"
        >
          <p class="text-xs text-white/95 line-clamp-2 drop-shadow">
            {{ post.media_meta.reel.caption }}
          </p>
        </div>
      </div>
    </div>

    <!-- 3B. Post Media Attachments (Images & Videos) -->
    <div v-if="post.media && post.media.length > 0" class="mb-3 rounded-xl overflow-hidden border border-slate-100 dark:border-slate-800">
      <div
        :class="post.media.length === 1 ? 'max-h-[500px] bg-black/5' : (post.media.length === 2 ? 'grid grid-cols-2 gap-1' : 'grid grid-cols-3 gap-1')"
      >
        <div
          v-for="(m, mIdx) in post.media.slice(0, 3)"
          :key="m.id"
          class="relative overflow-hidden bg-black"
        >
          <video
            v-if="isVideoMedia(m)"
            :src="getVideoUrl(m)"
            controls
            playsinline
            preload="metadata"
            :class="post.media.length === 1 ? 'w-full max-h-[500px]' : (post.media.length === 2 ? 'w-full h-64 object-cover' : 'w-full h-44 object-cover')"
          ></video>
          <img
            v-else
            :src="getMediaUrl(m)"
            loading="lazy"
            :class="post.media.length === 1 ? 'w-full h-full object-cover' : (post.media.length === 2 ? 'w-full h-64 object-cover' : 'w-full h-44 object-cover')"
            alt="Post Photo"
          />
          <div
            v-if="mIdx === 2 && post.media.length > 3"
            class="absolute inset-0 bg-black/55 flex items-center justify-center text-white text-2xl font-bold pointer-events-none"
          >
            +{{ post.media.length - 3 }}
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Engagement Stats Counter Bar -->
    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pb-2.5 mb-1.5 border-b border-[#E4E7EC] dark:border-slate-800">
      <!-- Left: Overlapping Reaction Badges -->
      <div v-if="likesCount > 0" class="flex items-center gap-2">
        <div class="flex items-center -space-x-1">
          <span class="w-4 h-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[9px] ring-2 ring-white dark:ring-slate-900 shadow-sm">
            👍
          </span>
          <span v-if="likesCount > 1" class="w-4 h-4 rounded-full bg-[#F02849] text-white flex items-center justify-center text-[9px] ring-2 ring-white dark:ring-slate-900 shadow-sm">
            ❤️
          </span>
          <span v-if="likesCount > 2" class="w-4 h-4 rounded-full bg-[#F7B125] text-white flex items-center justify-center text-[9px] ring-2 ring-white dark:ring-slate-900 shadow-sm">
            🥰
          </span>
        </div>
        <span class="font-medium">
          {{ formatReactionSummary(likesCount) }}
        </span>
      </div>
      <div v-else class="flex items-center gap-2 text-slate-400">
        <span class="font-normal italic">Be the first to react</span>
      </div>

      <!-- Right: Comments and Shares Counters -->
      <div class="flex items-center gap-3 font-medium">
        <span>{{ commentsCount }} comments</span>
        <span>{{ sharesCount }} shares</span>
      </div>
    </div>

    <!-- 5. Action Buttons with Hover Reactions Picker -->
    <div class="relative flex items-center justify-between py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
      <!-- Facebook-style Animated Hover Reactions Bar -->
      <div
        v-if="showReactionsPicker"
        @mouseenter="showReactionsPicker = true"
        @mouseleave="showReactionsPicker = false"
        class="absolute -top-12 left-2 z-30 flex items-center gap-1.5 bg-white dark:bg-slate-800 rounded-full py-1.5 px-3 shadow-xl border border-[#E4E7EC] dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
      >
        <button
          v-for="r in reactionTypes"
          :key="r.type"
          @click="selectReaction(r.type)"
          class="text-xl hover:scale-130 transition-transform p-1 cursor-pointer"
          :title="r.label"
        >
          {{ r.emoji }}
        </button>
      </div>

      <!-- Like / React Button -->
      <button
        type="button"
        @mouseenter="showReactionsPicker = true"
        @click="toggleLike"
        :class="[
          'flex-1 py-2 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors',
          currentReaction ? 'text-[#1877F2] font-bold' : ''
        ]"
      >
        <svg v-if="!currentReaction" class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
        </svg>
        <span v-else class="text-sm">{{ currentReactionEmoji }}</span>
        <span class="capitalize">{{ currentReaction || 'Like' }}</span>
      </button>

      <!-- Comment Toggle Button -->
      <button
        type="button"
        @click="showComments = !showComments"
        class="flex-1 py-2 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <span>Comment</span>
      </button>

      <!-- Share Button -->
      <button
        type="button"
        @click="openShareModal"
        class="flex-1 py-2 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
        </svg>
        <span>Share</span>
      </button>
    </div>

    <!-- 6. Comments Section -->
    <div v-if="showComments" class="mt-3 pt-3 border-t border-[#E4E7EC] dark:border-slate-800 space-y-3">
      <!-- Comment Input Form -->
      <div class="flex items-center gap-2.5">
        <input
          v-model="newComment"
          type="text"
          placeholder="Write a comment..."
          @keyup.enter="submitComment"
          class="flex-1 bg-[#F5F7FB] dark:bg-slate-800/80 border-none rounded-full px-4 py-2 text-xs text-[#172033] dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-[#1877F2] outline-none"
        />
        <button
          type="button"
          @click="submitComment"
          :disabled="isSubmittingComment || !newComment.trim()"
          class="p-2 rounded-full bg-[#1877F2] text-white disabled:opacity-40 hover:bg-[#1666D8] transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </div>

      <!-- Comments List -->
      <div v-if="comments.length > 0" class="space-y-2.5 pt-1">
        <div
          v-for="c in comments"
          :key="c.id"
          class="flex items-start gap-2.5 group"
        >
          <img
            :src="c.author?.avatar_url || c.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
            class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5"
            alt="Avatar"
          />
          <div class="flex-1 bg-[#F5F7FB] dark:bg-slate-800/60 rounded-2xl p-2.5 px-3 text-xs">
            <div class="flex items-center justify-between mb-0.5">
              <span class="font-bold text-[#172033] dark:text-slate-100">
                {{ c.author?.name || c.user?.name || 'Commenter' }}
              </span>
              <button
                v-if="currentUserId === (c.author?.id || c.user?.id) || currentUserId === post.author?.id"
                @click="deleteComment(c.id)"
                class="text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                title="Delete comment"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <p class="text-slate-700 dark:text-slate-300 leading-normal">{{ c.body }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Share Modal -->
    <div
      v-if="showShareModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150"
      @click.self="showShareModal = false"
    >
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
          <h3 class="font-bold text-base text-[#172033] dark:text-slate-100">Share Post to Your Feed</h3>
          <button @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            ✕
          </button>
        </div>
        <textarea
          v-model="shareCaption"
          rows="3"
          placeholder="Say something about this post... (optional)"
          class="w-full bg-[#F5F7FB] dark:bg-slate-800 text-sm text-[#172033] dark:text-slate-100 rounded-xl p-3 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2] resize-none mb-3"
        ></textarea>
        <!-- Preview of shared content -->
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 text-xs mb-4">
          <p class="font-bold text-slate-700 dark:text-slate-200 mb-1">{{ post.author?.name }}</p>
          <p class="text-slate-600 dark:text-slate-300 line-clamp-2">{{ currentContent }}</p>
        </div>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showShareModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitShare"
            :disabled="isSharing"
            class="px-5 py-2 rounded-xl text-xs font-bold bg-[#1877F2] text-white hover:bg-[#1666D8] disabled:opacity-50 transition-colors"
          >
            {{ isSharing ? 'Sharing...' : 'Share Now' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div
      v-if="showReportModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150"
      @click.self="showReportModal = false"
    >
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
          <h3 class="font-bold text-base text-rose-600 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Report Post
          </h3>
          <button @click="showReportModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            ✕
          </button>
        </div>
        <p class="text-xs text-slate-500 mb-3">Please select a reason why this post violates community standards:</p>
        <div class="space-y-2 mb-4">
          <label
            v-for="opt in reportOptions"
            :key="opt.value"
            class="flex items-center gap-2.5 p-2 rounded-xl border border-slate-200 dark:border-slate-800 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 text-xs"
          >
            <input type="radio" v-model="reportReason" :value="opt.value" class="text-[#1877F2]" />
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ opt.label }}</span>
          </label>
        </div>
        <textarea
          v-model="reportDetails"
          rows="2"
          placeholder="Additional details (optional)..."
          class="w-full bg-[#F5F7FB] dark:bg-slate-800 text-xs text-[#172033] dark:text-slate-100 rounded-xl p-3 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2] resize-none mb-4"
        ></textarea>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showReportModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitReport"
            :disabled="isSubmittingReport"
            class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-50 transition-colors"
          >
            {{ isSubmittingReport ? 'Submitting...' : 'Submit Report' }}
          </button>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { PostItem, CommentItem } from '../types';

const props = defineProps<{
  post: PostItem;
  currentUserId?: number;
}>();

const emit = defineEmits<{
  (e: 'post-deleted', id: number): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

const isAuthor = computed(() => props.currentUserId === props.post.author?.id);
const isMenuOpen = ref(false);
const isSaved = ref(Boolean(props.post.is_saved));
const isSavingItem = ref(false);
const showComments = ref(false);
const showReactionsPicker = ref(false);
const currentReaction = ref<string | null>(props.post.user_reaction || props.post.viewer_reaction || null);
const likesCount = ref(props.post.likes_count ?? props.post.counters?.likes ?? 0);
const commentsCount = ref(props.post.comments_count ?? props.post.counters?.comments ?? 0);
const sharesCount = ref(props.post.shares_count ?? props.post.counters?.shares ?? 0);
const comments = ref<CommentItem[]>(props.post.comments || []);
const newComment = ref('');
const isSubmittingComment = ref(false);

// Inline edit state
const isEditing = ref(false);
const editedContent = ref(props.post.content || '');
const currentContent = ref(props.post.content || '');
const isSavingEdit = ref(false);

// Share modal state
const showShareModal = ref(false);
const shareCaption = ref('');
const isSharing = ref(false);

// Report modal state
const showReportModal = ref(false);
const reportReason = ref('spam');
const reportDetails = ref('');
const isSubmittingReport = ref(false);
const reportOptions = [
  { value: 'spam', label: 'Spam or misleading content' },
  { value: 'harassment', label: 'Harassment or hate speech' },
  { value: 'false_information', label: 'False or fake information' },
  { value: 'violence', label: 'Violence or dangerous acts' },
  { value: 'other', label: 'Something else' },
];

const reactionTypes = [
  { type: 'like', emoji: '👍', label: 'Like' },
  { type: 'love', emoji: '❤️', label: 'Love' },
  { type: 'care', emoji: '🥰', label: 'Care' },
  { type: 'haha', emoji: '😆', label: 'Haha' },
  { type: 'wow', emoji: '😮', label: 'Wow' },
  { type: 'sad', emoji: '😢', label: 'Sad' },
  { type: 'angry', emoji: '😡', label: 'Angry' },
];

const currentReactionEmoji = computed(() => {
  if (!currentReaction.value) return '👍';
  const found = reactionTypes.find((r) => r.type === currentReaction.value);
  return found ? found.emoji : '👍';
});

const formatRelativeTime = (dateStr: string) => {
  if (!dateStr) return 'Recently';
  const diff = Date.now() - new Date(dateStr).getTime();
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h`;
  const days = Math.floor(hours / 24);
  return `${days}d`;
};

const formatReactionSummary = (count: number) => {
  if (count <= 0) return 'Be the first to react';
  if (currentReaction.value) {
    if (count === 1) return 'You reacted';
    return `You and ${count - 1} other${count - 1 === 1 ? '' : 's'}`;
  }
  return `${count} reaction${count === 1 ? '' : 's'}`;
};

const getMediaUrl = (media: any) => {
  if (media.url) return media.url;
  if (media.original_path) return '/storage/' + media.original_path.replace(/^\//, '');
  return 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1000&q=80';
};

const isVideoMedia = (media: any): boolean => {
  return typeof media?.mime_type === 'string' && media.mime_type.startsWith('video/');
};

const getVideoUrl = (media: any): string => {
  return media.original_url || media.urls?.original || getMediaUrl(media);
};

const openReelFromPost = (reelId: number | string | undefined): void => {
  if (!reelId) return;
  router.visit(`/reels?reel=${reelId}`);
};

const selectReaction = async (type: string) => {
  showReactionsPicker.value = false;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/posts/${props.post.id}/react`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ type }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      if (data.data.reacted) {
        if (!currentReaction.value) likesCount.value++;
        currentReaction.value = type;
      } else {
        likesCount.value = Math.max(0, likesCount.value - 1);
        currentReaction.value = null;
      }
    }
  } catch (err) {
    console.error('Reaction failed:', err);
  }
};

const toggleLike = () => {
  selectReaction(currentReaction.value ? currentReaction.value : 'like');
};

const submitComment = async () => {
  if (!newComment.value.trim() || isSubmittingComment.value) return;
  isSubmittingComment.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/posts/${props.post.id}/comments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ body: newComment.value }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      comments.value.push(data.data);
      commentsCount.value++;
      newComment.value = '';
    }
  } catch (err) {
    console.error('Comment submission failed:', err);
  } finally {
    isSubmittingComment.value = false;
  }
};

const deleteComment = async (commentId: number) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/comments/${commentId}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    });
    if (res.ok) {
      comments.value = comments.value.filter((c) => c.id !== commentId);
      commentsCount.value = Math.max(0, commentsCount.value - 1);
    }
  } catch (err) {
    console.error('Failed to delete comment:', err);
  }
};

const startEditing = () => {
  isMenuOpen.value = false;
  editedContent.value = currentContent.value;
  isEditing.value = true;
};

const saveEdit = async () => {
  if (isSavingEdit.value) return;
  isSavingEdit.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/posts/${props.post.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ content: editedContent.value }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      currentContent.value = editedContent.value;
      isEditing.value = false;
      emit('toast', { message: 'Post updated successfully!', type: 'success' });
    } else {
      emit('toast', { message: data.message || 'Failed to update post.', type: 'error' });
    }
  } catch (err) {
    emit('toast', { message: 'Network error while updating post.', type: 'error' });
  } finally {
    isSavingEdit.value = false;
  }
};

const deletePost = async () => {
  isMenuOpen.value = false;
  if (!confirm('Are you sure you want to delete this post?')) return;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/posts/${props.post.id}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    });
    if (res.ok) {
      emit('post-deleted', props.post.id);
      emit('toast', { message: 'Post deleted successfully.', type: 'info' });
    }
  } catch (err) {
    console.error('Delete post error:', err);
  }
};

const copyPostLink = () => {
  isMenuOpen.value = false;
  navigator.clipboard.writeText(window.location.origin + '/posts/' + props.post.id);
  emit('toast', { message: 'Post link copied to clipboard!', type: 'success' });
};

const toggleSave = async () => {
  isMenuOpen.value = false;
  isSavingItem.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile/saved-items/toggle', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        item_type: 'post',
        item_id: props.post.id,
      }),
    });
    const data = await res.json();
    if (res.ok) {
      isSaved.value = !isSaved.value;
      emit('toast', {
        message: isSaved.value ? 'Post saved to your collection!' : 'Post removed from saved items.',
        type: 'success',
      });
    }
  } catch (err: any) {
    emit('toast', { message: 'Could not update saved status.', type: 'error' });
  } finally {
    isSavingItem.value = false;
  }
};

const openShareModal = () => {
  shareCaption.value = '';
  showShareModal.value = true;
};

const submitShare = async () => {
  if (isSharing.value) return;
  isSharing.value = true;
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
        caption: shareCaption.value.trim() || undefined,
      }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      sharesCount.value = data.data.shares_count || (sharesCount.value + 1);
      showShareModal.value = false;
      emit('toast', { message: 'Post shared to your timeline!', type: 'success' });
    } else {
      emit('toast', { message: data.message || 'Could not share post.', type: 'error' });
    }
  } catch (err) {
    emit('toast', { message: 'Network error occurred while sharing.', type: 'error' });
  } finally {
    isSharing.value = false;
  }
};

const openReportModal = () => {
  isMenuOpen.value = false;
  reportReason.value = 'spam';
  reportDetails.value = '';
  showReportModal.value = true;
};

const submitReport = async () => {
  if (isSubmittingReport.value) return;
  isSubmittingReport.value = true;
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
        reportable_type: 'post',
        reportable_id: props.post.id,
        reason: reportReason.value,
        details: reportDetails.value.trim() || undefined,
      }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showReportModal.value = false;
      emit('toast', { message: 'Thank you. Your report has been submitted for review.', type: 'success' });
    } else {
      emit('toast', { message: data.message || 'Failed to submit report.', type: 'error' });
    }
  } catch (err) {
    emit('toast', { message: 'Network error while submitting report.', type: 'error' });
  } finally {
    isSubmittingReport.value = false;
  }
};
</script>
