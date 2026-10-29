<template>
  <Teleport to="body">
    <div
      v-if="isOpen && currentReel"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-md animate-fade-in select-none touch-none"
      @keydown.esc="closeViewer"
      @keydown.up.prevent="prevReel"
      @keydown.down.prevent="nextReel"
      tabindex="0"
    >
      <!-- Top Close Button -->
      <button
        type="button"
        @click="closeViewer"
        class="absolute top-4 right-4 z-50 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur-md transition-colors cursor-pointer"
        title="Close (Esc)"
      >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Up Arrow (Prev Reel) on Desktop -->
      <button
        v-if="currentIndex > 0"
        type="button"
        @click.stop="prevReel"
        class="hidden md:flex absolute top-10 left-1/2 -translate-x-1/2 z-40 p-2.5 rounded-full bg-white/15 hover:bg-white/30 text-white backdrop-blur-md transition-all hover:-translate-y-1 cursor-pointer"
        title="Previous Reel (Up Arrow)"
      >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
        </svg>
      </button>

      <!-- Main Player Shell (9:16 aspect ratio container) -->
      <div
        class="relative w-full md:max-w-[420px] h-full md:h-[92vh] md:max-h-[820px] bg-slate-950 rounded-none md:rounded-3xl overflow-hidden shadow-2xl flex flex-col justify-between border-0 md:border md:border-white/10 touch-none select-none will-change-transform"
        :style="cardTransformStyle"
        @touchstart.passive="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @touchcancel="handleTouchCancel"
        @wheel.passive="handleWheelScroll"
      >
        <!-- HTML5 Video Element -->
        <video
          ref="videoEl"
          :src="currentReel.video_url"
          class="absolute inset-0 w-full h-full object-cover cursor-pointer pointer-events-none"
          playsinline
          loop
          autoplay
          :muted="isMuted"
          @timeupdate="onTimeUpdate"
        ></video>

        <!-- 2X Speed Long-Press Floating Badge -->
        <div
          v-if="isLongPressing2x"
          class="absolute top-14 left-1/2 -translate-x-1/2 z-40 px-3.5 py-1.5 rounded-full bg-black/80 backdrop-blur-md text-amber-300 font-extrabold text-xs flex items-center gap-1.5 border border-amber-400/40 shadow-2xl pointer-events-none animate-pulse"
        >
          <span>⚡</span>
          <span>2X SPEED</span>
        </div>

        <!-- Coordinate-Aware Heart Burst Animation -->
        <div
          v-if="showHeartAnim"
          class="absolute pointer-events-none z-30 transition-transform -translate-x-1/2 -translate-y-1/2"
          :style="heartAnimStyle"
        >
          <div class="animate-ping duration-500 scale-125">
            <svg class="w-24 h-24 text-rose-500 fill-current drop-shadow-[0_10px_25px_rgba(244,63,94,0.7)]" viewBox="0 0 24 24">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
          </div>
        </div>

        <!-- Tap-to-play / pause indicator icon -->
        <div
          v-if="showPlayPauseIcon"
          class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
        >
          <div class="w-16 h-16 rounded-full bg-black/60 backdrop-blur-md text-white flex items-center justify-center animate-ping duration-300">
            <svg v-if="isPlaying" class="w-8 h-8 fill-current" viewBox="0 0 24 24">
              <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
            </svg>
            <svg v-else class="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
        </div>

        <!-- Floating Gradient Overlays -->
        <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/70 via-black/30 to-transparent pointer-events-none z-10"></div>
        <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-black/90 via-black/50 to-transparent pointer-events-none z-10"></div>

        <!-- Top Status Bar -->
        <div
          class="relative z-20 px-4 py-3 flex items-center justify-between text-white drop-shadow"
          @touchstart.stop
          @touchmove.stop
          @touchend.stop
        >
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
            <span class="text-xs font-extrabold tracking-wider uppercase text-purple-300">Bondhoo Reels</span>
          </div>

          <!-- Mute / Unmute Toggle Button -->
          <button
            type="button"
            @click.stop="toggleMute"
            class="p-2 rounded-full bg-black/40 hover:bg-black/60 text-white backdrop-blur-md transition-colors cursor-pointer"
            :title="isMuted ? 'Unmute' : 'Mute'"
          >
            <svg v-if="isMuted" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
          </button>
        </div>

        <!-- Right Floating Action Rail -->
        <div
          class="absolute right-3 bottom-20 z-20 flex flex-col items-center gap-4 text-white"
          @touchstart.stop
          @touchmove.stop
          @touchend.stop
        >
          <!-- Like / Reaction Button -->
          <div class="flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="toggleLike"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-125 cursor-pointer shadow-lg"
              :class="currentReel.has_liked ? 'text-red-500' : 'text-white'"
            >
              <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">{{ formatCount(currentReel.likes_count || 0) }}</span>
          </div>

          <!-- Comments Button -->
          <div class="flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="toggleCommentsDrawer"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-110 cursor-pointer shadow-lg"
            >
              <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">{{ formatCount(currentReel.comments_count || 0) }}</span>
          </div>

          <!-- Message / Send Button -->
          <div class="flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="openMessageModal"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-110 cursor-pointer shadow-lg hover:text-purple-300"
              title="Send in Direct Message"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">Send</span>
          </div>

          <!-- Share Button -->
          <div class="flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="openShareModal('profile')"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-110 cursor-pointer shadow-lg hover:text-pink-300"
              title="Share Reel"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">{{ formatCount(currentReel.shares_count || 0) }}</span>
          </div>

          <!-- Save / Bookmark Button -->
          <div class="flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="toggleSave"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-110 cursor-pointer shadow-lg"
              :class="currentReel.has_saved ? 'text-amber-400' : 'text-white'"
              title="Save Reel"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">Save</span>
          </div>

          <!-- More Options Menu Button -->
          <div class="relative flex flex-col items-center gap-1">
            <button
              type="button"
              @click.stop="toggleOptionsMenu"
              class="w-11 h-11 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md flex items-center justify-center transition-transform active:scale-110 cursor-pointer shadow-lg"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
              </svg>
            </button>
            <span class="text-[11px] font-bold drop-shadow">More</span>

            <!-- Dropdown Menu Popover -->
            <div
              v-if="isOptionsMenuOpen"
              class="absolute right-full bottom-0 mr-2 w-44 bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl border border-neutral-200 dark:border-neutral-800 py-1.5 z-40 text-neutral-800 dark:text-neutral-200"
            >
              <button
                v-if="canDeleteCurrentReel"
                type="button"
                @click.stop="openDeleteModal"
                class="w-full px-4 py-2 text-left text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 flex items-center gap-2 cursor-pointer"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Delete Reel</span>
              </button>
              <button
                type="button"
                @click.stop="openMessageModal"
                class="w-full px-4 py-2 text-left text-xs font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800 flex items-center gap-2.5 cursor-pointer"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Send in Message</span>
              </button>
              <button
                type="button"
                @click.stop="openShareModal('profile')"
                class="w-full px-4 py-2 text-left text-xs font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800 flex items-center gap-2.5 cursor-pointer"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span>Share to Wall</span>
              </button>
              <button
                type="button"
                @click.stop="copyReelLink"
                class="w-full px-4 py-2 text-left text-xs font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800 flex items-center gap-2.5 cursor-pointer"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Copy Link</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Bottom Metadata: Author, Caption & Audio -->
        <div
          class="relative z-20 p-4 pr-16 space-y-2 text-white"
          @touchstart.stop
          @touchmove.stop
          @touchend.stop
        >
          <!-- Author Info -->
          <div class="flex items-center gap-2.5">
            <img
              :src="currentReel.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              alt="Avatar"
              class="w-9 h-9 rounded-full object-cover ring-2 ring-purple-500 shadow-md"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold truncate drop-shadow">{{ currentReel.user?.name }}</span>
                <svg v-if="currentReel.user?.is_verified" class="w-3.5 h-3.5 text-blue-400 fill-current shrink-0" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
              </div>
              <span class="text-[10px] text-white/80 block drop-shadow">{{ currentReel.formatted_time || 'Just now' }}</span>
            </div>
          </div>

          <!-- Expandable Caption Text -->
          <div class="text-xs text-white/95 leading-relaxed drop-shadow">
            <p :class="[!isCaptionExpanded ? 'line-clamp-2' : 'max-h-36 overflow-y-auto pr-1']">
              {{ currentReel.caption }}
            </p>
            <button
              v-if="(currentReel.caption || '').length > 60"
              type="button"
              @click.stop="isCaptionExpanded = !isCaptionExpanded"
              class="text-[11px] font-bold text-white/70 hover:text-white mt-0.5 cursor-pointer inline-block"
            >
              {{ isCaptionExpanded ? 'less' : '...more' }}
            </button>
          </div>

          <!-- Audio Track Pill -->
          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-md text-[11px] font-medium text-white/90 drop-shadow">
            <svg class="w-3 h-3 text-purple-400 animate-spin" style="animation-duration: 4s;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
            <span class="truncate max-w-[200px]">{{ currentReel.audio_title || 'Original Audio' }}</span>
          </div>

          <!-- Progress Bar Scrubber -->
          <div class="w-full h-1 bg-white/20 rounded-full overflow-hidden mt-2">
            <div
              class="h-full bg-purple-500 rounded-full transition-all duration-150"
              :style="{ width: `${progressPercent}%` }"
            ></div>
          </div>
        </div>

        <!-- Slide-over Comments Drawer -->
        <div
          v-if="isCommentsOpen"
          class="absolute inset-x-0 bottom-0 top-1/3 z-30 bg-slate-900/95 backdrop-blur-xl border-t border-white/10 rounded-t-3xl flex flex-col p-4 animate-in slide-in-from-bottom duration-200"
          @touchstart.stop
          @touchmove.stop
          @touchend.stop
        >
          <!-- Drawer Header -->
          <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <span class="text-xs font-bold text-white">Comments ({{ commentsList.length }})</span>
            <button @click="isCommentsOpen = false" class="text-white/60 hover:text-white p-1 cursor-pointer">✕</button>
          </div>

          <!-- Comments List -->
          <div class="flex-1 overflow-y-auto py-3 space-y-3">
            <div v-if="isLoadingComments" class="text-center py-6 text-xs text-slate-400">Loading comments...</div>
            <div v-else-if="commentsList.length === 0" class="text-center py-6 text-xs text-slate-400">Be the first to comment on this reel!</div>
            <div
              v-for="c in commentsList"
              :key="c.id"
              class="flex items-start gap-2.5 text-xs"
            >
              <img
                :src="c.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                alt="Avatar"
                class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5"
              />
              <div class="flex-1 bg-white/5 rounded-2xl p-2.5">
                <span class="font-bold text-white block mb-0.5">{{ c.user?.name }}</span>
                <p class="text-slate-200 leading-snug">{{ c.content || c.body }}</p>
              </div>
            </div>
          </div>

          <!-- New Comment Input -->
          <div class="pt-2 border-t border-white/10 flex items-center gap-2">
            <input
              v-model="newCommentText"
              type="text"
              placeholder="Add a comment..."
              @keyup.enter="postComment"
              class="flex-1 bg-white/10 text-xs text-white placeholder-slate-400 rounded-full px-4 py-2 border-none outline-none focus:ring-1 focus:ring-purple-500"
            />
            <button
              type="button"
              @click="postComment"
              :disabled="!newCommentText.trim() || isPostingComment"
              class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-xs font-bold rounded-full transition-colors cursor-pointer"
            >
              Post
            </button>
          </div>
        </div>
      </div>

      <!-- Down Arrow (Next Reel) on Desktop -->
      <button
        v-if="currentIndex < reels.length - 1"
        type="button"
        @click.stop="nextReel"
        class="hidden md:flex absolute bottom-10 left-1/2 -translate-x-1/2 z-40 p-2.5 rounded-full bg-white/15 hover:bg-white/30 text-white backdrop-blur-md transition-all hover:translate-y-1 cursor-pointer"
        title="Next Reel (Down Arrow)"
      >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Advanced Share & Message Modal -->
      <ReelShareModal
        :is-open="isShareModalOpen"
        :reel="currentReel || null"
        :initial-tab="shareModalInitialTab"
        @close="isShareModalOpen = false"
        @shared="handleReelShared"
      />

      <!-- Delete Confirmation Modal -->
      <ReelDeleteConfirmModal
        :is-open="isDeleteModalOpen"
        :reel="currentReel || null"
        :is-deleting="isDeletingReel"
        @close="isDeleteModalOpen = false"
        @confirm="confirmDeleteReel"
      />
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed, onUnmounted } from 'vue';
import ReelShareModal from './ReelShareModal.vue';
import ReelDeleteConfirmModal from './ReelDeleteConfirmModal.vue';
import type { ReelItem, User } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  reels: ReelItem[];
  initialIndex?: number;
  currentUser: User | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'deleted', reelId: number): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

const currentIndex = ref(props.initialIndex || 0);
const videoEl = ref<HTMLVideoElement | null>(null);

const isPlaying = ref(true);
const isMuted = ref(false);
const showPlayPauseIcon = ref(false);
const showHeartAnim = ref(false);
const progressPercent = ref(0);
const isCaptionExpanded = ref(false);
const heartBurstPos = ref<{ x: number; y: number } | null>(null);

const isCommentsOpen = ref(false);
const isShareModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isDeletingReel = ref(false);
const isOptionsMenuOpen = ref(false);
const shareModalInitialTab = ref<'message' | 'profile' | 'group' | 'page'>('message');
const commentsList = ref<any[]>([]);
const isLoadingComments = ref(false);
const newCommentText = ref('');
const isPostingComment = ref(false);

// Advanced Gesture & Physics Engine
const isDragging = ref(false);
const dragOffset = ref(0);
const isLongPressing2x = ref(false);

let touchStartY = 0;
let touchStartX = 0;
let touchStartTime = 0;
let isVerticalGesture = false;
let longPressTimer: any = null;
let lastTapTime = 0;
let singleTapTimer: any = null;
let lastWheelTime = 0;

const currentReel = computed<ReelItem | undefined>(() => props.reels[currentIndex.value]);

const cardTransformStyle = computed(() => {
  if (isDragging.value) {
    return {
      transform: `translateY(${dragOffset.value}px)`,
      transition: 'none',
    };
  }
  return {
    transform: 'translateY(0px)',
    transition: 'transform 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
  };
});

const heartAnimStyle = computed(() => {
  if (heartBurstPos.value) {
    return {
      left: `${heartBurstPos.value.x}px`,
      top: `${heartBurstPos.value.y}px`,
    };
  }
  return {
    left: '50%',
    top: '50%',
  };
});

watch(
  () => props.initialIndex,
  (val) => {
    if (val !== undefined && val >= 0) {
      currentIndex.value = val;
    }
  }
);

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      recordView();
      isPlaying.value = true;
      if (videoEl.value) {
        videoEl.value.currentTime = 0;
        videoEl.value.play().catch(() => {});
      }
    } else {
      isCommentsOpen.value = false;
    }
  }
);

watch(
  currentIndex,
  () => {
    isCommentsOpen.value = false;
    isCaptionExpanded.value = false;
    progressPercent.value = 0;
    recordView();
    if (videoEl.value) {
      videoEl.value.currentTime = 0;
      videoEl.value.play().catch(() => {});
    }
  }
);

const closeViewer = () => {
  if (videoEl.value) {
    videoEl.value.pause();
  }
  emit('close');
};

const nextReel = () => {
  if (currentIndex.value < props.reels.length - 1) {
    currentIndex.value++;
  } else if (props.reels.length > 1) {
    currentIndex.value = 0;
  }
};

const prevReel = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  } else if (props.reels.length > 1) {
    currentIndex.value = props.reels.length - 1;
  }
};

const togglePlay = () => {
  if (!videoEl.value) return;
  if (videoEl.value.paused) {
    videoEl.value.play().catch(() => {});
    isPlaying.value = true;
  } else {
    videoEl.value.pause();
    isPlaying.value = false;
  }
  showPlayPauseIcon.value = true;
  setTimeout(() => {
    showPlayPauseIcon.value = false;
  }, 400);
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  if (videoEl.value) {
    videoEl.value.muted = isMuted.value;
  }
};

const onTimeUpdate = () => {
  if (!videoEl.value || !videoEl.value.duration) return;
  progressPercent.value = (videoEl.value.currentTime / videoEl.value.duration) * 100;
};

const formatCount = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
  return num.toString();
};

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

const recordView = async () => {
  if (!currentReel.value?.id) return;
  try {
    await fetch(`/api/v2/reels/${currentReel.value.id}/view`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (currentReel.value) {
      currentReel.value.views_count = (currentReel.value.views_count || 0) + 1;
    }
  } catch (e) {}
};

const toggleLike = async () => {
  if (!currentReel.value) return;
  const reel = currentReel.value;
  reel.has_liked = !reel.has_liked;
  reel.likes_count = (reel.likes_count || 0) + (reel.has_liked ? 1 : -1);

  try {
    await fetch(`/api/v2/reels/${reel.id}/react`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ type: 'like', reaction: 'like' }),
    });
  } catch (e) {
    reel.has_liked = !reel.has_liked;
    reel.likes_count = (reel.likes_count || 0) + (reel.has_liked ? 1 : -1);
  }
};

const toggleSave = async () => {
  if (!currentReel.value) return;
  const reel = currentReel.value;
  reel.has_saved = !reel.has_saved;
  emit('toast', {
    message: reel.has_saved ? 'Reel saved to your collections!' : 'Reel removed from saved.',
    type: 'success',
  });

  try {
    await fetch(`/api/v2/reels/${reel.id}/save`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
  } catch (e) {}
};

const canDeleteCurrentReel = computed(() => {
  if (!props.currentUser || !currentReel.value) return false;
  const isOwner = props.currentUser.id === currentReel.value.user?.id;
  const isAdmin = props.currentUser.is_admin || (props.currentUser as any).role === 'admin';
  return isOwner || isAdmin;
});

const openMessageModal = () => {
  isOptionsMenuOpen.value = false;
  shareModalInitialTab.value = 'message';
  isShareModalOpen.value = true;
};

const openShareModal = (tab: 'message' | 'profile' | 'group' | 'page' = 'profile') => {
  isOptionsMenuOpen.value = false;
  shareModalInitialTab.value = tab;
  isShareModalOpen.value = true;
};

const toggleOptionsMenu = () => {
  isOptionsMenuOpen.value = !isOptionsMenuOpen.value;
};

const openDeleteModal = () => {
  isOptionsMenuOpen.value = false;
  isDeleteModalOpen.value = true;
};

const handleReelShared = (payload: { count?: number; platform?: string }) => {
  if (currentReel.value) {
    if (typeof payload?.count === 'number') {
      currentReel.value.shares_count = payload.count;
    } else {
      currentReel.value.shares_count = (currentReel.value.shares_count || 0) + 1;
    }
  }
};

const copyReelLink = () => {
  if (!currentReel.value) return;
  const url = `${window.location.origin}/reels/${currentReel.value.id}`;
  navigator.clipboard.writeText(url);
  isOptionsMenuOpen.value = false;
  emit('toast', { message: 'Reel link copied to clipboard!', type: 'success' });

  fetch(`/api/v2/reels/${currentReel.value.id}/share`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
  if (currentReel.value) {
    currentReel.value.shares_count = (currentReel.value.shares_count || 0) + 1;
  }
};

const confirmDeleteReel = async () => {
  if (!currentReel.value) return;
  const reelId = currentReel.value.id;
  isDeletingReel.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${reelId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (res.ok) {
      isDeleteModalOpen.value = false;
      emit('toast', { message: 'Reel deleted successfully.', type: 'success' });
      emit('deleted', reelId);
      closeViewer();
    } else {
      const err = await res.json().catch(() => ({}));
      emit('toast', { message: err.message || 'Failed to delete Reel.', type: 'error' });
    }
  } catch (e) {
    emit('toast', { message: 'An error occurred while deleting the Reel.', type: 'error' });
  } finally {
    isDeletingReel.value = false;
  }
};

const toggleCommentsDrawer = async () => {
  isCommentsOpen.value = !isCommentsOpen.value;
  if (isCommentsOpen.value) {
    await loadComments();
  }
};

const loadComments = async () => {
  if (!currentReel.value) return;
  isLoadingComments.value = true;
  try {
    const res = await fetch(`/api/v2/reels/${currentReel.value.id}/comments`, {
      headers: { 'Accept': 'application/json' },
    });
    const data = await res.json();
    commentsList.value = data.data || [];
  } catch (e) {
    commentsList.value = [];
  } finally {
    isLoadingComments.value = false;
  }
};

const postComment = async () => {
  if (!newCommentText.value.trim() || !currentReel.value) return;
  isPostingComment.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${currentReel.value.id}/comments`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        comment: newCommentText.value.trim(),
        content: newCommentText.value.trim(),
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      commentsList.value.unshift(data.data);
      if (currentReel.value) {
        currentReel.value.comments_count = (currentReel.value.comments_count || 0) + 1;
      }
      newCommentText.value = '';
    }
  } catch (e) {
  } finally {
    isPostingComment.value = false;
  }
};

// ================= GESTURE ENGINE (TOUCH & MOUSEWHEEL) =================
const handleTouchStart = (e: TouchEvent) => {
  if (e.touches.length !== 1) return;
  const touch = e.touches[0];
  touchStartY = touch.clientY;
  touchStartX = touch.clientX;
  touchStartTime = Date.now();
  isVerticalGesture = false;
  isDragging.value = false;
  dragOffset.value = 0;

  if (longPressTimer) clearTimeout(longPressTimer);
  longPressTimer = setTimeout(() => {
    if (!isDragging.value && videoEl.value) {
      isLongPressing2x.value = true;
      videoEl.value.playbackRate = 2;
    }
  }, 320);
};

const handleTouchMove = (e: TouchEvent) => {
  if (e.touches.length !== 1) return;
  const touch = e.touches[0];
  const deltaY = touch.clientY - touchStartY;
  const deltaX = touch.clientX - touchStartX;

  if (Math.abs(deltaY) > 8 || Math.abs(deltaX) > 8) {
    if (longPressTimer) {
      clearTimeout(longPressTimer);
      longPressTimer = null;
    }
  }

  if (isLongPressing2x.value) {
    return;
  }

  if (!isVerticalGesture && (Math.abs(deltaY) > 6 || Math.abs(deltaX) > 6)) {
    isVerticalGesture = Math.abs(deltaY) >= Math.abs(deltaX);
  }

  if (isVerticalGesture) {
    if (e.cancelable) {
      e.preventDefault();
    }
    isDragging.value = true;

    let calculatedOffset = deltaY;
    if ((currentIndex.value === 0 && deltaY > 0) || (currentIndex.value === props.reels.length - 1 && deltaY < 0)) {
      calculatedOffset = deltaY * 0.32;
    }
    dragOffset.value = calculatedOffset;
  }
};

const handleTouchEnd = (e: TouchEvent) => {
  if (longPressTimer) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
  }

  if (isLongPressing2x.value) {
    isLongPressing2x.value = false;
    if (videoEl.value) {
      videoEl.value.playbackRate = 1;
    }
    isDragging.value = false;
    dragOffset.value = 0;
    return;
  }

  const touch = e.changedTouches[0];
  const deltaY = touch.clientY - touchStartY;
  const deltaX = touch.clientX - touchStartX;
  const duration = Math.max(1, Date.now() - touchStartTime);
  const velocity = Math.abs(deltaY) / duration;

  if (isDragging.value) {
    isDragging.value = false;
    const finalOffset = dragOffset.value;
    dragOffset.value = 0;

    if (Math.abs(finalOffset) > 45 || (velocity > 0.35 && Math.abs(finalOffset) > 20)) {
      if (finalOffset < 0) {
        nextReel();
      } else {
        prevReel();
      }
    }
    return;
  }

  // Tap handling (< 10px move)
  if (Math.abs(deltaY) < 10 && Math.abs(deltaX) < 10) {
    const now = Date.now();
    if (now - lastTapTime < 280) {
      // Double Tap Like
      if (singleTapTimer) {
        clearTimeout(singleTapTimer);
        singleTapTimer = null;
      }
      lastTapTime = 0;

      const rect = (e.currentTarget as HTMLElement)?.getBoundingClientRect();
      if (rect) {
        heartBurstPos.value = {
          x: touch.clientX - rect.left,
          y: touch.clientY - rect.top,
        };
      } else {
        heartBurstPos.value = null;
      }

      if (currentReel.value && !currentReel.value.has_liked) {
        toggleLike();
      }
      showHeartAnim.value = true;
      setTimeout(() => {
        showHeartAnim.value = false;
      }, 600);
    } else {
      // Single Tap Play/Pause
      lastTapTime = now;
      singleTapTimer = setTimeout(() => {
        togglePlay();
      }, 260);
    }
  }
};

const handleTouchCancel = () => {
  if (longPressTimer) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
  }
  if (singleTapTimer) {
    clearTimeout(singleTapTimer);
    singleTapTimer = null;
  }
  if (isLongPressing2x.value) {
    isLongPressing2x.value = false;
    if (videoEl.value) {
      videoEl.value.playbackRate = 1;
    }
  }
  isDragging.value = false;
  dragOffset.value = 0;
};

const handleWheelScroll = (e: WheelEvent) => {
  const now = Date.now();
  if (now - lastWheelTime < 450) return;
  if (Math.abs(e.deltaY) > 30) {
    lastWheelTime = now;
    if (e.deltaY > 0) {
      nextReel();
    } else {
      prevReel();
    }
  }
};

onUnmounted(() => {
  if (longPressTimer) clearTimeout(longPressTimer);
  if (singleTapTimer) clearTimeout(singleTapTimer);
});
</script>
