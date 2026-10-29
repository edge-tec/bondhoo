<template>
  <AppLayout title="Reels — Bondhoo" current-route="reels" :hide-right-sidebar="true" :full-width="true">
    <div class="w-full max-w-7xl mx-auto p-0 md:px-4 md:py-3 flex flex-col items-center h-[calc(100dvh-120px)] sm:h-[calc(100dvh-112px)] md:h-auto md:min-h-[calc(100vh-80px)] select-none overflow-hidden touch-none">
      <!-- 1. Top Reels Subheader & Tab Switcher (Desktop) -->
      <div class="hidden md:flex w-full max-w-4xl flex-wrap items-center justify-between gap-3 mb-3 px-2">
        <div class="flex items-center gap-1 sm:gap-2 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-1.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <button
            type="button"
            @click="switchTab('for_you')"
            :class="[
              'px-3 sm:px-4 py-1.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all cursor-pointer flex items-center gap-1.5',
              currentTab === 'for_you'
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md shadow-purple-500/25'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-slate-800/60'
            ]"
          >
            <span>🔥</span>
            <span>For You</span>
          </button>

          <button
            type="button"
            @click="switchTab('following')"
            :class="[
              'px-3 sm:px-4 py-1.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all cursor-pointer flex items-center gap-1.5',
              currentTab === 'following'
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md shadow-purple-500/25'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-slate-800/60'
            ]"
          >
            <span>👥</span>
            <span>Following</span>
          </button>

          <button
            type="button"
            @click="switchTab('my_reels')"
            :class="[
              'px-3 sm:px-4 py-1.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all cursor-pointer flex items-center gap-1.5',
              currentTab === 'my_reels'
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md shadow-purple-500/25'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-slate-800/60'
            ]"
          >
            <span>🎬</span>
            <span>My Reels</span>
          </button>

          <button
            type="button"
            @click="switchTab('saved')"
            :class="[
              'px-3 sm:px-4 py-1.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all cursor-pointer flex items-center gap-1.5',
              currentTab === 'saved'
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md shadow-purple-500/25'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-slate-800/60'
            ]"
          >
            <span>🔖</span>
            <span>Saved</span>
          </button>
        </div>

        <!-- Create Reel Trigger Button (Desktop) -->
        <button
          type="button"
          @click="isCreateModalOpen = true"
          class="px-4 py-2 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 hover:from-purple-700 hover:to-pink-700 text-white text-xs sm:text-sm font-extrabold shadow-lg shadow-purple-500/25 flex items-center gap-2 transition-all cursor-pointer hover:scale-105 active:scale-95"
        >
          <div class="w-5 h-5 rounded-lg bg-white/20 flex items-center justify-center">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <span>Create Reel</span>
        </button>
      </div>

      <!-- 2. Main Reels Feed Container -->
      <div
        v-if="reelsList.length > 0 && currentReel"
        class="relative w-full h-full md:h-auto flex items-center justify-center gap-4 my-auto touch-none select-none"
        @touchstart.passive="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @touchcancel="handleTouchCancel"
        @wheel.passive="handleWheelScroll"
      >
        <!-- Desktop Previous Reel Navigation Chevron -->
        <button
          v-if="currentIndex > 0"
          type="button"
          @click.stop="prevReel"
          class="hidden lg:flex w-12 h-12 rounded-full bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-200 shadow-xl items-center justify-center hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 hover:scale-110 active:scale-95 transition-all cursor-pointer z-20 group"
          title="Previous Reel (Up Arrow)"
        >
          <svg class="w-6 h-6 group-hover:-translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
          </svg>
        </button>

        <!-- Vertical 9:16 Video Player Card -->
        <div
          :class="[
            'relative w-full h-full md:h-[84vh] md:max-w-[420px] md:aspect-[9/16] bg-slate-950 rounded-2xl md:rounded-3xl overflow-hidden shadow-2xl border-0 md:border md:border-slate-800/80 flex flex-col justify-between group select-none will-change-transform',
            slideDirection === 'up' && !isDragging ? 'animate-slide-up' : slideDirection === 'down' && !isDragging ? 'animate-slide-down' : ''
          ]"
          :style="cardTransformStyle"
        >
          <!-- HTML5 Video Element -->
          <video
            ref="videoEl"
            :src="currentReel.video_url"
            class="absolute inset-0 w-full h-full object-cover cursor-pointer pointer-events-none"
            playsinline
            autoplay
            :muted="isMuted"
            @timeupdate="onTimeUpdate"
            @ended="onVideoEnded"
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

          <!-- Play / Pause Center Icon Indicator -->
          <div
            v-if="showPlayPauseIcon"
            class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
          >
            <div class="w-16 h-16 rounded-full bg-black/60 backdrop-blur-md text-white flex items-center justify-center animate-ping duration-200">
              <svg v-if="isPlaying" class="w-8 h-8 fill-current" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
              </svg>
              <svg v-else class="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>

          <!-- Animated Swipe Hint on Mobile (First Load) -->
          <div
            v-if="showSwipeHint && isMobileViewport"
            class="absolute bottom-24 left-1/2 -translate-x-1/2 z-30 flex flex-col items-center pointer-events-none transition-opacity duration-500"
          >
            <div class="px-3 py-1 rounded-full bg-black/65 backdrop-blur-md border border-white/20 text-white text-[11px] font-bold flex items-center gap-1.5 shadow-xl animate-bounce">
              <svg class="w-3.5 h-3.5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
              </svg>
              <span>Swipe up for next</span>
            </div>
          </div>

          <!-- Top Gradient & Header Info / Floating Controls -->
          <div
            class="relative z-20 px-3 sm:px-4 pt-3 pb-4 flex items-center justify-between text-white bg-gradient-to-b from-black/85 via-black/40 to-transparent"
            @touchstart.stop
            @touchmove.stop
            @touchend.stop
          >
            <!-- Left Side: Brand Logo (Desktop) + Auto-Advance Toggle Indicator -->
            <div class="flex items-center gap-2">
              <div class="hidden md:flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 animate-pulse"></span>
                <span class="text-xs font-black tracking-wider uppercase bg-clip-text text-transparent bg-gradient-to-r from-purple-300 via-pink-300 to-rose-300">
                  Bondhoo Reels
                </span>
              </div>

              <!-- Auto-Advance Toggle Pill (Desktop & Mobile) -->
              <button
                type="button"
                @click.stop="toggleAutoAdvance"
                :class="[
                  'px-2.5 py-1 rounded-full text-[10px] font-black tracking-wide backdrop-blur-md flex items-center gap-1.5 transition-all cursor-pointer border shadow-sm',
                  isAutoAdvanceEnabled
                    ? 'bg-emerald-500/25 text-emerald-300 border-emerald-500/40 shadow-emerald-500/20'
                    : 'bg-black/50 text-slate-300 border-white/10 hover:bg-black/70'
                ]"
                :title="isAutoAdvanceEnabled ? 'Auto-play active (click to toggle)' : 'Loop active (click to toggle)'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="isAutoAdvanceEnabled ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400'"></span>
                <span>{{ isAutoAdvanceEnabled ? '⚡ AUTO' : '⏸️ LOOP' }}</span>
              </button>
            </div>

            <!-- Center (Mobile Only): TikTok / Shorts Style Floating Tabs -->
            <div class="flex md:hidden items-center gap-1 bg-black/40 backdrop-blur-md px-2 py-0.5 rounded-full border border-white/15">
              <button
                type="button"
                @click.stop="switchTab('for_you')"
                :class="[
                  'px-2.5 py-0.5 rounded-full text-xs font-bold transition-all cursor-pointer',
                  currentTab === 'for_you' ? 'text-white font-extrabold drop-shadow' : 'text-white/60 hover:text-white'
                ]"
              >
                For You
              </button>
              <span class="text-white/30 text-xs">|</span>
              <button
                type="button"
                @click.stop="switchTab('following')"
                :class="[
                  'px-2.5 py-0.5 rounded-full text-xs font-bold transition-all cursor-pointer',
                  currentTab === 'following' ? 'text-white font-extrabold drop-shadow' : 'text-white/60 hover:text-white'
                ]"
              >
                Following
              </button>
            </div>

            <!-- Right Controls: Speed, Sound & Mobile Create Button -->
            <div class="flex items-center gap-1.5 sm:gap-2">
              <!-- Playback Speed Switcher -->
              <button
                type="button"
                @click.stop="togglePlaybackRate"
                class="px-2 py-1 rounded-full bg-black/45 hover:bg-black/70 text-white backdrop-blur-md text-[10px] font-bold border border-white/15 transition-colors cursor-pointer"
                title="Playback Speed"
              >
                {{ playbackRate }}x
              </button>

              <!-- Sound Mute & Volume Popover -->
              <div class="relative group/vol flex items-center">
                <button
                  type="button"
                  @click.stop="toggleMute"
                  class="p-2 rounded-full bg-black/45 hover:bg-black/70 text-white backdrop-blur-md transition-colors cursor-pointer border border-white/15"
                  :title="isMuted ? 'Unmute (M)' : 'Mute (M)'"
                >
                  <svg v-if="isMuted" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                  </svg>
                  <svg v-else class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                  </svg>
                </button>

                <!-- Hidden/Hover Volume Slider (Desktop) -->
                <div class="hidden md:group-hover/vol:flex items-center absolute right-full mr-1.5 px-2.5 py-1 bg-black/70 backdrop-blur-md rounded-full border border-white/10 z-30 animate-in fade-in duration-150">
                  <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    v-model.number="volumeLevel"
                    @input="updateVolume"
                    class="w-16 h-1 bg-slate-600 rounded-lg appearance-none cursor-pointer accent-purple-500"
                  />
                </div>
              </div>

              <!-- Mobile Create Reel Button (+) -->
              <button
                type="button"
                @click.stop="isCreateModalOpen = true"
                class="md:hidden p-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md transition-transform active:scale-90 cursor-pointer"
                title="Create Reel"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Right Floating Action Rail -->
          <div
            class="absolute right-3 bottom-14 z-20 flex flex-col items-center gap-3.5 text-white"
            @touchstart.stop
            @touchmove.stop
            @touchend.stop
          >
            <!-- Like Button -->
            <div class="flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="toggleLike"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-125 cursor-pointer shadow-xl border border-white/10"
                :class="currentReel.has_liked ? 'text-rose-500' : 'text-white'"
                title="Like this Reel (L)"
              >
                <svg class="w-6 h-6 fill-current transition-transform" :class="currentReel.has_liked ? 'scale-110' : ''" viewBox="0 0 24 24">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">{{ formatCount(currentReel.likes_count || 0) }}</span>
            </div>

            <!-- Comments Button -->
            <div class="flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="toggleCommentsDrawer"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-110 cursor-pointer shadow-xl border border-white/10"
                :class="isCommentsOpen ? 'text-purple-400 bg-purple-900/60' : 'text-white'"
                title="View Comments"
              >
                <svg class="w-5.5 h-5.5 fill-current" viewBox="0 0 24 24">
                  <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">{{ formatCount(currentReel.comments_count || 0) }}</span>
            </div>

            <!-- Message / Send Button -->
            <div class="flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="openMessageModal"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-110 cursor-pointer shadow-xl border border-white/10 hover:text-purple-300"
                title="Send in Direct Message"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">Send</span>
            </div>

            <!-- Share Button -->
            <div class="flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="openShareModal('profile')"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-110 cursor-pointer shadow-xl border border-white/10 hover:text-pink-300"
                title="Share Reel"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">{{ formatCount(currentReel.shares_count || 0) }}</span>
            </div>

            <!-- Save Bookmark Button -->
            <div class="flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="toggleSave"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-110 cursor-pointer shadow-xl border border-white/10"
                :class="currentReel.has_saved ? 'text-amber-400' : 'text-white'"
                title="Save to Collection"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">Save</span>
            </div>

            <!-- 3-Dots Options Menu -->
            <div class="relative flex flex-col items-center gap-1">
              <button
                type="button"
                @click.stop="toggleOptionsMenu"
                class="w-11 h-11 rounded-full bg-black/45 hover:bg-black/70 backdrop-blur-md flex items-center justify-center transition-all active:scale-110 cursor-pointer shadow-xl border border-white/10"
                title="More Options"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
              </button>
              <span class="text-[11px] font-extrabold drop-shadow">More</span>

              <!-- Dropdown Menu Popover -->
              <div
                v-if="isOptionsMenuOpen"
                class="absolute right-full bottom-0 mr-2 w-48 bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl border border-neutral-200 dark:border-neutral-800 py-1.5 z-40 text-neutral-800 dark:text-neutral-200 animate-in fade-in duration-150"
              >
                <button
                  v-if="canDeleteCurrentReel"
                  type="button"
                  @click.stop="openDeleteModal"
                  class="w-full px-4 py-2.5 text-left text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 flex items-center gap-2.5 cursor-pointer"
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

            <!-- Rotating Vinyl Sound Disc -->
            <div class="pt-1 flex flex-col items-center">
              <div
                class="relative w-10 h-10 rounded-full bg-slate-900 border-2 border-slate-700/80 p-0.5 shadow-xl overflow-hidden cursor-pointer flex items-center justify-center"
                :class="isPlaying ? 'animate-spin [animation-duration:4s]' : ''"
                @click.stop="toggleMute"
                title="Audio Disc"
              >
                <img
                  :src="currentReel.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Sound"
                  class="w-6 h-6 rounded-full object-cover"
                />
                <div class="absolute inset-0 rounded-full border border-white/20"></div>
              </div>
            </div>
          </div>

          <!-- Bottom Metadata Overlay: Author, Follow, Caption, Music -->
          <div
            class="relative z-20 p-4 pr-16 space-y-2 text-white bg-gradient-to-t from-black/95 via-black/60 to-transparent"
            @touchstart.stop
            @touchmove.stop
            @touchend.stop
          >
            <!-- Author Row -->
            <div class="flex items-center gap-2.5">
              <a :href="'/profile/' + (currentReel.user?.username || '')" class="shrink-0 group">
                <img
                  :src="currentReel.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-9 h-9 rounded-full object-cover ring-2 ring-purple-500 shadow-md group-hover:scale-105 transition-transform"
                />
              </a>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                  <a :href="'/profile/' + (currentReel.user?.username || '')" class="text-xs font-bold truncate drop-shadow hover:underline">
                    {{ currentReel.user?.name }}
                  </a>
                  <svg v-if="currentReel.user?.is_verified" class="w-3.5 h-3.5 text-blue-400 fill-current shrink-0" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                </div>
                <span class="text-[10px] text-white/80 block drop-shadow">{{ currentReel.formatted_time || 'Just now' }}</span>
              </div>

              <!-- Follow/Unfollow Button -->
              <button
                v-if="currentUser && currentReel.user && currentUser.id !== currentReel.user.id"
                type="button"
                @click.stop="toggleFollow"
                :class="[
                  'px-3 py-1 rounded-full text-xs font-extrabold transition-all cursor-pointer shadow-md',
                  currentReel.is_following
                    ? 'bg-white/20 hover:bg-white/30 text-white border border-white/20'
                    : 'bg-white text-slate-900 hover:bg-slate-200'
                ]"
              >
                {{ currentReel.is_following ? 'Following' : 'Follow' }}
              </button>
            </div>

            <!-- Expandable Caption Text with Hashtag Highlighting -->
            <div class="text-xs text-white/95 leading-relaxed drop-shadow">
              <p :class="[!isCaptionExpanded ? 'line-clamp-2' : 'max-h-36 overflow-y-auto pr-1']">
                <span v-for="(part, pIdx) in parseCaptionParts(currentReel.caption || '')" :key="pIdx">
                  <span v-if="part.isTag" class="text-purple-300 font-extrabold hover:underline cursor-pointer">{{ part.text }} </span>
                  <span v-else>{{ part.text }} </span>
                </span>
              </p>
              <button
                v-if="(currentReel.caption || '').length > 70"
                type="button"
                @click.stop="isCaptionExpanded = !isCaptionExpanded"
                class="text-[11px] font-bold text-white/70 hover:text-white mt-0.5 cursor-pointer inline-block"
              >
                {{ isCaptionExpanded ? 'less' : '...more' }}
              </button>
            </div>

            <!-- Audio Track Pill -->
            <div class="flex items-center gap-2 pt-0.5">
              <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/45 backdrop-blur-md text-[11px] font-semibold text-purple-200 border border-white/10 max-w-[240px] truncate shadow">
                <svg class="w-3.5 h-3.5 text-purple-400 shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <span class="truncate">{{ currentReel.audio_title || 'Original Audio' }}</span>
              </div>
            </div>
          </div>

          <!-- Bottom Interactive Video Progress Scrubber Bar -->
          <div
            ref="scrubberBar"
            class="absolute bottom-0 inset-x-0 h-1.5 hover:h-2.5 bg-white/20 z-30 cursor-pointer transition-all group/scrubber"
            @click.stop="seekVideo"
            @touchstart.stop
            @touchmove.stop
            @touchend.stop
          >
            <div
              class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 relative transition-all duration-75"
              :style="{ width: `${videoProgress}%` }"
            >
              <span class="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-white shadow-md opacity-0 group-hover/scrubber:opacity-100 transition-opacity"></span>
            </div>
          </div>
        </div>

        <!-- Desktop Next Reel Navigation Chevron -->
        <button
          v-if="currentIndex < reelsList.length - 1"
          type="button"
          @click.stop="nextReel"
          class="hidden lg:flex w-12 h-12 rounded-full bg-white/90 dark:bg-slate-800/90 backdrop-blur-md border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-200 shadow-xl items-center justify-center hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 hover:scale-110 active:scale-95 transition-all cursor-pointer z-20 group"
          title="Next Reel (Down Arrow)"
        >
          <svg class="w-6 h-6 group-hover:translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Slide-out Comments Drawer on Desktop -->
        <div
          v-if="isCommentsOpen"
          class="hidden md:flex flex-col w-[360px] h-[82vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-30 animate-in slide-in-from-right duration-200"
        >
          <!-- Drawer Header -->
          <div class="px-4 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/70">
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Comments</h3>
              <span class="px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 text-xs font-bold">
                {{ currentReel.comments_count || 0 }}
              </span>
            </div>
            <button
              type="button"
              @click="isCommentsOpen = false"
              class="p-1 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Comments List -->
          <div class="flex-1 overflow-y-auto p-4 space-y-3.5">
            <div v-if="isLoadingComments" class="py-12 text-center text-xs text-slate-400 flex flex-col items-center gap-2">
              <svg class="animate-spin w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Loading comments...</span>
            </div>
            <div v-else-if="commentsList.length === 0" class="py-16 text-center text-xs text-slate-400 space-y-1">
              <div class="text-2xl mb-1">💬</div>
              <p class="font-bold text-slate-600 dark:text-slate-300">No comments yet</p>
              <p>Be the first to share your thoughts!</p>
            </div>
            <div
              v-else
              v-for="c in commentsList"
              :key="c.id"
              class="flex gap-2.5 group"
            >
              <img
                :src="c.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                alt="Avatar"
                class="w-8 h-8 rounded-full object-cover shrink-0 mt-0.5"
              />
              <div class="flex-1 min-w-0">
                <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ c.user?.name }}</span>
                    <button
                      v-if="currentUser && c.user_id === currentUser.id"
                      type="button"
                      @click="deleteComment(c.id)"
                      class="text-[10px] text-red-500 opacity-0 group-hover:opacity-100 transition-opacity hover:underline cursor-pointer"
                    >
                      Delete
                    </button>
                  </div>
                  <p class="text-xs text-slate-700 dark:text-slate-300 break-words leading-relaxed">{{ c.comment || c.content }}</p>
                </div>
                <div class="flex items-center gap-3 mt-1 px-1 text-[10px] text-slate-400 font-medium">
                  <span>{{ c.created_at ? formatTimeAgo(c.created_at) : 'Just now' }}</span>
                  <button
                    type="button"
                    @click="likeComment(c)"
                    class="font-bold hover:text-purple-600 cursor-pointer flex items-center gap-1"
                    :class="c.has_liked ? 'text-purple-600' : ''"
                  >
                    <span>❤️</span>
                    <span>{{ c.likes_count || 0 }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Comment Input Footer -->
          <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
            <!-- Quick Emoji Buttons -->
            <div class="flex items-center gap-1.5 pb-2">
              <button
                v-for="emoji in ['🔥', '❤️', '👏', '😂', '😍', '🙌', '✨']"
                :key="emoji"
                type="button"
                @click="newCommentText += emoji"
                class="text-sm p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer hover:scale-110 active:scale-95"
              >
                {{ emoji }}
              </button>
            </div>

            <form @submit.prevent="postComment" class="flex items-center gap-2">
              <input
                v-model="newCommentText"
                type="text"
                placeholder="Add a comment..."
                class="flex-1 bg-[#F5F7FB] dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
              <button
                type="submit"
                :disabled="!newCommentText.trim() || isPostingComment"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-xs font-bold disabled:opacity-50 transition-all cursor-pointer shadow-sm"
              >
                Post
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- 3. Empty State when no Reels in selected tab -->
      <div
        v-else
        class="w-full max-w-md my-auto flex flex-col items-center justify-center p-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 text-center shadow-lg animate-in fade-in"
      >
        <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-purple-500/20 to-pink-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-4 shadow-inner">
          <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
          </svg>
        </div>
        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 mb-1">
          {{ currentTab === 'saved' ? 'No Saved Reels' : currentTab === 'my_reels' ? 'No Reels Uploaded Yet' : 'No Reels Found' }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mb-5 leading-relaxed">
          {{ currentTab === 'saved' ? 'Save reels to view them anytime in your private collection.' : 'Share your creativity with the world through 9:16 vertical videos and viral sounds.' }}
        </p>
        <button
          type="button"
          @click="isCreateModalOpen = true"
          class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 hover:from-purple-700 hover:to-pink-700 text-white text-xs font-bold shadow-md shadow-purple-500/25 cursor-pointer transition-all hover:scale-105 active:scale-95"
        >
          + Upload Your First Reel
        </button>
      </div>

      <!-- 4. Mobile Bottom Sheet Comments Drawer -->
      <Teleport to="body">
        <div
          v-if="isCommentsOpen && isMobileViewport"
          class="fixed inset-0 z-50 flex flex-col justify-end bg-black/70 backdrop-blur-sm md:hidden animate-fade-in"
          @click="isCommentsOpen = false"
        >
          <div
            class="w-full bg-white dark:bg-slate-900 rounded-t-3xl max-h-[75vh] flex flex-col shadow-2xl border-t border-slate-200 dark:border-slate-800 animate-in slide-in-from-bottom duration-200"
            @click.stop
            @touchstart.stop
            @touchmove.stop
            @touchend.stop
          >
            <div class="w-10 h-1.5 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto my-3"></div>
            <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
              <span class="text-xs font-bold text-slate-900 dark:text-slate-100">
                Comments ({{ currentReel?.comments_count || 0 }})
              </span>
              <button type="button" @click="isCommentsOpen = false" class="text-slate-400 p-1 cursor-pointer">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
              <div v-if="commentsList.length === 0" class="py-8 text-center text-xs text-slate-400">
                No comments yet.
              </div>
              <div v-for="c in commentsList" :key="c.id" class="flex gap-2.5">
                <img
                  :src="c.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  alt="Avatar"
                  class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5"
                />
                <div class="flex-1 text-xs">
                  <div class="bg-slate-100 dark:bg-slate-800 p-2.5 rounded-2xl">
                    <span class="font-bold block text-slate-900 dark:text-slate-100">{{ c.user?.name }}</span>
                    <span class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ c.comment || c.content }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-3 border-t border-slate-100 dark:border-slate-800">
              <form @submit.prevent="postComment" class="flex items-center gap-2">
                <input
                  v-model="newCommentText"
                  type="text"
                  placeholder="Add a comment..."
                  class="flex-1 bg-[#F5F7FB] dark:bg-slate-800 text-xs rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700"
                />
                <button
                  type="submit"
                  :disabled="!newCommentText.trim() || isPostingComment"
                  class="px-3.5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold disabled:opacity-50"
                >
                  Post
                </button>
              </form>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- 5. Advanced Share & Message Modal -->
      <ReelShareModal
        :is-open="isShareModalOpen"
        :reel="currentReel || null"
        :initial-tab="shareModalInitialTab"
        @close="isShareModalOpen = false"
        @shared="handleReelShared"
      />

      <!-- 6. Delete Confirmation Modal -->
      <ReelDeleteConfirmModal
        :is-open="isDeleteModalOpen"
        :reel="currentReel || null"
        :is-deleting="isDeletingReel"
        @close="isDeleteModalOpen = false"
        @confirm="confirmDeleteReel"
      />

      <!-- 7. Create Reel Studio Modal -->
      <ReelCreatorModal
        :is-open="isCreateModalOpen"
        :current-user="currentUser"
        @close="isCreateModalOpen = false"
        @reel-created="handleReelCreated"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import ReelCreatorModal from '../Components/Reels/ReelCreatorModal.vue';
import ReelShareModal from '../Components/Reels/ReelShareModal.vue';
import ReelDeleteConfirmModal from '../Components/Reels/ReelDeleteConfirmModal.vue';
import type { User, SharedProps, ReelItem } from '../types';

const props = withDefaults(
  defineProps<{
    reels: {
      data: ReelItem[];
      current_page: number;
      last_page: number;
      total: number;
    };
    initialReelId?: number | null;
    activeTab?: string;
  }>(),
  {
    initialReelId: null,
    activeTab: 'for_you',
  }
);

const page = usePage<SharedProps>();
const currentUser = computed<User | null>(() => page.props.auth?.user || null);

const currentTab = ref(props.activeTab || 'for_you');
const reelsList = ref<ReelItem[]>([...(props.reels?.data || [])]);
const currentIndex = ref(0);

const isCreateModalOpen = ref(false);
const isCommentsOpen = ref(false);
const isShareModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isDeletingReel = ref(false);
const isOptionsMenuOpen = ref(false);
const shareModalInitialTab = ref<'message' | 'profile' | 'group' | 'page'>('message');

const videoEl = ref<HTMLVideoElement | null>(null);
const scrubberBar = ref<HTMLDivElement | null>(null);
const isPlaying = ref(true);
const isMuted = ref(true);
const volumeLevel = ref(0.8);
const playbackRate = ref(1);
const videoProgress = ref(0);
const showPlayPauseIcon = ref(false);
const showHeartAnim = ref(false);
let playPauseTimer: any = null;
let viewTrackingTimer: any = null;

// Comments state
const commentsList = ref<any[]>([]);
const isLoadingComments = ref(false);
const isPostingComment = ref(false);
const newCommentText = ref('');

// Auto-advance & transitions
const isAutoAdvanceEnabled = ref(true);
const slideDirection = ref<'up' | 'down' | 'none'>('none');
const isMobileViewport = ref(false);

// Advanced Gesture & Physics Engine
const isDragging = ref(false);
const dragOffset = ref(0);
const isLongPressing2x = ref(false);
const showSwipeHint = ref(true);
const isCaptionExpanded = ref(false);
const heartBurstPos = ref<{ x: number; y: number } | null>(null);

let touchStartY = 0;
let touchStartX = 0;
let touchStartTime = 0;
let isVerticalGesture = false;
let longPressTimer: any = null;
let lastTapTime = 0;
let singleTapTimer: any = null;
let lastWheelTime = 0;
let swipeHintDismissTimer: any = null;

const currentReel = computed<ReelItem | undefined>(() => reelsList.value[currentIndex.value]);

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

const toggleAutoAdvance = () => {
  isAutoAdvanceEnabled.value = !isAutoAdvanceEnabled.value;
};

const checkMobile = () => {
  if (typeof window !== 'undefined') {
    isMobileViewport.value = window.innerWidth < 768;
  }
};

const formatCount = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
  return num.toString();
};

const formatTimeAgo = (dateStr: string) => {
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'Just now';
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  return `${Math.floor(hours / 24)}d ago`;
};

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

const parseCaptionParts = (caption: string) => {
  if (!caption) return [];
  const words = caption.split(' ');
  return words.map((w) => ({
    text: w,
    isTag: w.startsWith('#') || w.startsWith('@'),
  }));
};

const switchTab = (tab: string) => {
  currentTab.value = tab;
  router.get('/reels', { tab }, { preserveState: false, preserveScroll: true });
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
  if (playPauseTimer) clearTimeout(playPauseTimer);
  playPauseTimer = setTimeout(() => {
    showPlayPauseIcon.value = false;
  }, 400);
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  if (videoEl.value) {
    videoEl.value.muted = isMuted.value;
    if (!isMuted.value && videoEl.value.volume === 0) {
      videoEl.value.volume = volumeLevel.value;
    }
  }
};

const updateVolume = () => {
  if (videoEl.value) {
    videoEl.value.volume = volumeLevel.value;
    if (volumeLevel.value > 0 && isMuted.value) {
      isMuted.value = false;
      videoEl.value.muted = false;
    }
  }
};

const togglePlaybackRate = () => {
  const rates = [1, 1.5, 2];
  const nextIdx = (rates.indexOf(playbackRate.value) + 1) % rates.length;
  playbackRate.value = rates[nextIdx];
  if (videoEl.value) {
    videoEl.value.playbackRate = playbackRate.value;
  }
};

const seekVideo = (e: MouseEvent) => {
  if (!scrubberBar.value || !videoEl.value || !videoEl.value.duration) return;
  const rect = scrubberBar.value.getBoundingClientRect();
  const clickX = e.clientX - rect.left;
  const percentage = Math.max(0, Math.min(1, clickX / rect.width));
  videoEl.value.currentTime = percentage * videoEl.value.duration;
  videoProgress.value = percentage * 100;
};

const onTimeUpdate = () => {
  if (!videoEl.value || !videoEl.value.duration) return;
  videoProgress.value = (videoEl.value.currentTime / videoEl.value.duration) * 100;
};

const onVideoEnded = () => {
  if (isAutoAdvanceEnabled.value) {
    nextReel();
  } else {
    if (videoEl.value) {
      videoEl.value.currentTime = 0;
      videoEl.value.play().catch(() => {});
    }
  }
};

const startViewTracking = () => {
  if (viewTrackingTimer) clearTimeout(viewTrackingTimer);
  viewTrackingTimer = setTimeout(async () => {
    if (!currentReel.value?.id) return;
    try {
      await fetch(`/api/v2/reels/${currentReel.value.id}/view`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ watch_time_seconds: 3.0 }),
      });
      if (currentReel.value) {
        currentReel.value.views_count = (currentReel.value.views_count || 0) + 1;
      }
    } catch (e) {}
  }, 3000);
};

const nextReel = () => {
  if (reelsList.value.length === 0) return;
  slideDirection.value = 'up';
  setTimeout(() => {
    slideDirection.value = 'none';
  }, 320);

  if (currentIndex.value < reelsList.value.length - 1) {
    currentIndex.value++;
    onReelSwitched();
  } else if (reelsList.value.length > 1) {
    currentIndex.value = 0;
    onReelSwitched();
  }
};

const prevReel = () => {
  if (reelsList.value.length === 0) return;
  slideDirection.value = 'down';
  setTimeout(() => {
    slideDirection.value = 'none';
  }, 320);

  if (currentIndex.value > 0) {
    currentIndex.value--;
    onReelSwitched();
  } else if (reelsList.value.length > 1) {
    currentIndex.value = reelsList.value.length - 1;
    onReelSwitched();
  }
};

const onReelSwitched = () => {
  videoProgress.value = 0;
  isPlaying.value = true;
  playbackRate.value = 1;
  isCaptionExpanded.value = false;
  commentsList.value = [];
  isCommentsOpen.value = false;
  isOptionsMenuOpen.value = false;
  startViewTracking();

  if (videoEl.value) {
    videoEl.value.currentTime = 0;
    videoEl.value.playbackRate = 1;
    videoEl.value.play().catch(() => {
      isMuted.value = true;
      if (videoEl.value) {
        videoEl.value.muted = true;
        videoEl.value.play().catch(() => {});
      }
    });
  }
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
  reel.saves_count = (reel.saves_count || 0) + (reel.has_saved ? 1 : -1);

  try {
    await fetch(`/api/v2/reels/${reel.id}/save`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
  } catch (e) {
    reel.has_saved = !reel.has_saved;
  }
};

const toggleFollow = async () => {
  if (!currentReel.value?.user) return;
  const targetUser = currentReel.value.user;
  const isFollowing = currentReel.value.is_following;
  currentReel.value.is_following = !isFollowing;

  try {
    const url = `/api/v1/users/${targetUser.id}/follow`;
    await fetch(url, {
      method: isFollowing ? 'DELETE' : 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
  } catch (e) {
    currentReel.value.is_following = isFollowing;
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

const deleteComment = async (commentId: number) => {
  if (!currentReel.value || !confirm('Are you sure you want to delete this comment?')) return;
  try {
    const res = await fetch(`/api/v2/reels/comments/${commentId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      commentsList.value = commentsList.value.filter((c) => c.id !== commentId);
      if (currentReel.value && (currentReel.value.comments_count || 0) > 0) {
        currentReel.value.comments_count--;
      }
    }
  } catch (e) {}
};

const likeComment = async (comment: any) => {
  comment.has_liked = !comment.has_liked;
  comment.likes_count = (comment.likes_count || 0) + (comment.has_liked ? 1 : -1);

  try {
    await fetch(`/api/v2/reels/comments/${comment.id}/like`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
  } catch (e) {
    comment.has_liked = !comment.has_liked;
  }
};

const canDeleteCurrentReel = computed(() => {
  if (!currentUser.value || !currentReel.value) return false;
  const isOwner = currentUser.value.id === currentReel.value.user?.id;
  const isAdmin = currentUser.value.is_admin || (currentUser.value as any).role === 'admin';
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
      const deletedIndex = currentIndex.value;
      reelsList.value.splice(deletedIndex, 1);
      isDeleteModalOpen.value = false;

      if (reelsList.value.length === 0) {
        currentIndex.value = 0;
      } else if (currentIndex.value >= reelsList.value.length) {
        currentIndex.value = reelsList.value.length - 1;
        onReelSwitched();
      } else {
        onReelSwitched();
      }
    } else {
      const err = await res.json().catch(() => ({}));
      alert(err.message || 'Failed to delete Reel. Please check permissions.');
    }
  } catch (e) {
    alert('An unexpected error occurred while deleting the Reel.');
  } finally {
    isDeletingReel.value = false;
  }
};

const getReelShareUrl = () => {
  if (typeof window === 'undefined' || !currentReel.value) return '';
  return `${window.location.origin}/reels/${currentReel.value.id}`;
};

const copyReelLink = () => {
  if (!currentReel.value) return;
  const url = getReelShareUrl();
  navigator.clipboard.writeText(url);
  isOptionsMenuOpen.value = false;

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

const handleReelCreated = (newReel: ReelItem) => {
  isCreateModalOpen.value = false;
  reelsList.value.unshift(newReel);
  currentIndex.value = 0;
  onReelSwitched();
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (isCommentsOpen.value || isCreateModalOpen.value || isShareModalOpen.value) {
    return;
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    nextReel();
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    prevReel();
  } else if (e.key === ' ' || e.code === 'Space') {
    e.preventDefault();
    togglePlay();
  } else if (e.key === 'm' || e.key === 'M') {
    e.preventDefault();
    toggleMute();
  } else if (e.key === 'l' || e.key === 'L') {
    e.preventDefault();
    toggleLike();
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

    // Rubber-band resistance at limits
    let calculatedOffset = deltaY;
    if ((currentIndex.value === 0 && deltaY > 0) || (currentIndex.value === reelsList.value.length - 1 && deltaY < 0)) {
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
      videoEl.value.playbackRate = playbackRate.value;
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

    // Threshold: moved > 45px OR flicked fast (velocity > 0.35 with > 20px move)
    if (Math.abs(finalOffset) > 45 || (velocity > 0.35 && Math.abs(finalOffset) > 20)) {
      showSwipeHint.value = false;
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
      videoEl.value.playbackRate = playbackRate.value;
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
    showSwipeHint.value = false;
    if (e.deltaY > 0) {
      nextReel();
    } else {
      prevReel();
    }
  }
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
  window.addEventListener('keydown', handleKeyDown);
  startViewTracking();

  swipeHintDismissTimer = setTimeout(() => {
    showSwipeHint.value = false;
  }, 4500);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  window.removeEventListener('keydown', handleKeyDown);
  if (viewTrackingTimer) clearTimeout(viewTrackingTimer);
  if (playPauseTimer) clearTimeout(playPauseTimer);
  if (longPressTimer) clearTimeout(longPressTimer);
  if (singleTapTimer) clearTimeout(singleTapTimer);
  if (swipeHintDismissTimer) clearTimeout(swipeHintDismissTimer);
});
</script>

<style scoped>
@keyframes slideUpIn {
  0% {
    opacity: 0.65;
    transform: translateY(35px) scale(0.985);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes slideDownIn {
  0% {
    opacity: 0.65;
    transform: translateY(-35px) scale(0.985);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-slide-up {
  animation: slideUpIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-slide-down {
  animation: slideDownIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
