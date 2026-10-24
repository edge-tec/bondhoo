<template>
  <div class="relative mb-4 select-none">
    <!-- Top Tray Navigation Tabs: Stories vs Reels vs Go Live -->
    <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-2.5 mb-3 px-1">
      <div class="flex items-center gap-1.5 sm:gap-2">
        <button
          type="button"
          @click="activeTab = 'stories'"
          :class="[
            'flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
            activeTab === 'stories'
              ? 'bg-blue-50 dark:bg-blue-950/60 text-[#1877F2] dark:text-blue-400 shadow-sm border border-blue-200/60 dark:border-blue-900/40'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
          </svg>
          <span>Stories</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'reels'"
          :class="[
            'flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
            activeTab === 'reels'
              ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 shadow-sm border border-purple-200/60 dark:border-purple-900/40'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
          </svg>
          <span>Reels</span>
          <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-extrabold">{{ activeReelsList.length }}</span>
        </button>
      </div>

      <button
        type="button"
        @click="$emit('open-live')"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/40 transition-all cursor-pointer shadow-sm"
      >
        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
        <span>Go Live</span>
      </button>
    </div>

    <!-- Carousel Container -->
    <div
      ref="scrollContainer"
      class="flex items-center gap-2.5 overflow-x-auto no-scrollbar py-1 scroll-smooth"
    >
      <!-- A. STORIES MODE -->
      <template v-if="activeTab === 'stories'">
        <!-- 1. Create Story Card -->
        <div
          @click="openCreatorModal"
          class="relative flex-shrink-0 w-[120px] sm:w-[128px] h-[184px] rounded-2xl overflow-hidden border border-[#E4E7EC] dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col group cursor-pointer shadow-sm hover:shadow-md transition-all"
          title="Create a Story"
        >
          <div class="h-[128px] w-full overflow-hidden bg-slate-100 dark:bg-slate-800 relative">
            <img
              :src="currentUser?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'"
              alt="My Avatar"
              class="!w-full !h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          </div>
          <div class="relative flex-1 flex flex-col items-center justify-center p-1 bg-white dark:bg-slate-900">
            <div class="absolute -top-4 w-8 h-8 rounded-full bg-[#1877F2] text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900 shadow-md group-hover:scale-110 transition-transform">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
            </div>
            <span class="text-xs font-bold text-[#172033] dark:text-slate-100 mt-2 text-center">
              Create Story
            </span>
          </div>
        </div>

        <!-- 2. Active Stories Cards -->
        <div
          v-for="(group, idx) in activeStoryGroups"
          :key="group.user?.id || idx"
          @click="openViewerModal(group, idx)"
          class="relative flex-shrink-0 w-[120px] sm:w-[128px] h-[184px] rounded-2xl overflow-hidden cursor-pointer group shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all bg-slate-900"
        >
          <img
            :src="getGroupThumbnail(group)"
            class="absolute inset-0 !w-full !h-full object-cover group-hover:scale-105 transition-transform duration-300"
            :alt="group.user?.name"
          />
          <!-- Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-transparent to-black/85"></div>
          
          <!-- User Avatar with Blue Border Ring (gray ring if all viewed) -->
          <div class="absolute top-2.5 left-2.5 z-10">
            <div
              :class="[
                'w-9 h-9 rounded-full p-0.5 bg-white/20 transition-all flex items-center justify-center shrink-0 shadow-md',
                group.all_viewed ? 'ring-2 ring-slate-400' : 'ring-2 ring-[#1877F2]'
              ]"
            >
              <img
                :src="group.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                class="!w-full !h-full rounded-full object-cover block shrink-0"
                :alt="group.user?.name"
              />
            </div>
          </div>

          <!-- Story User Name -->
          <span class="absolute bottom-2.5 left-2.5 right-2.5 text-xs font-bold text-white truncate drop-shadow-md z-10">
            {{ group.user?.name }}
          </span>
        </div>
      </template>

      <!-- B. REELS MODE -->
      <template v-else>
        <!-- 1. Create Reel Card -->
        <div
          @click="$emit('open-reel-creator')"
          class="relative flex-shrink-0 w-[120px] sm:w-[128px] h-[184px] rounded-2xl overflow-hidden border border-[#E4E7EC] dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col group cursor-pointer shadow-sm hover:shadow-md transition-all"
          title="Create a Reel"
        >
          <div class="h-[128px] w-full overflow-hidden bg-slate-950 relative">
            <img
              :src="currentUser?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'"
              alt="My Avatar"
              class="!w-full !h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-90"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-purple-950/80 via-transparent to-transparent"></div>
          </div>
          <div class="relative flex-1 flex flex-col items-center justify-center p-1 bg-white dark:bg-slate-900">
            <div class="absolute -top-4 w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-pink-500 text-white flex items-center justify-center ring-4 ring-white dark:ring-slate-900 shadow-md group-hover:scale-110 transition-transform">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
            </div>
            <span class="text-xs font-bold text-[#172033] dark:text-slate-100 mt-2 text-center">
              Create Reel
            </span>
          </div>
        </div>

        <!-- 2. Active Reel Cards -->
        <template v-if="activeReelsList.length > 0">
          <div
            v-for="(reel, rIdx) in activeReelsList"
            :key="reel.id || rIdx"
            @click="openReelViewer(rIdx)"
            class="relative flex-shrink-0 w-[120px] sm:w-[128px] h-[184px] rounded-2xl overflow-hidden cursor-pointer group shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all bg-slate-950"
          >
            <!-- Video Cover or Real First Frame -->
            <img
              v-if="(reel.cover_url || reel.thumbnail_url) && !failedReelImages[reel.id || rIdx]"
              :src="reel.cover_url || reel.thumbnail_url"
              @error="failedReelImages[reel.id || rIdx] = true"
              class="absolute inset-0 !w-full !h-full object-cover group-hover:scale-105 transition-transform duration-300"
              :alt="reel.caption || 'Reel'"
            />
            <video
              v-else-if="reel.video_url"
              :src="reel.video_url + '#t=0.001'"
              preload="metadata"
              muted
              playsinline
              class="absolute inset-0 !w-full !h-full object-cover group-hover:scale-105 transition-transform duration-300 pointer-events-none"
            ></video>
            <div
              v-else
              class="absolute inset-0 !w-full !h-full bg-gradient-to-tr from-purple-900 to-slate-900 flex items-center justify-center text-white/50"
            >
              <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/85"></div>

            <!-- Creator Avatar with Gradient Ring -->
            <div class="absolute top-2.5 left-2.5 z-10">
              <div class="w-9 h-9 rounded-full p-0.5 bg-gradient-to-tr from-purple-500 to-pink-500 shadow-sm flex items-center justify-center shrink-0">
                <img
                  :src="reel.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  class="!w-full !h-full rounded-full object-cover block shrink-0"
                  :alt="reel.user?.name"
                />
              </div>
            </div>

            <!-- Play Count Badge -->
            <div class="absolute top-2.5 right-2.5 px-1.5 py-0.5 rounded-full bg-black/50 backdrop-blur-sm text-[10px] text-white font-bold flex items-center gap-1 drop-shadow">
              <svg class="w-3 h-3 fill-current text-purple-400" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
              <span>{{ formatReelCount(reel.views_count || 0) }}</span>
            </div>

            <!-- Reel Creator & Audio Title -->
            <div class="absolute bottom-2.5 inset-x-2.5 space-y-0.5">
              <span class="text-xs font-bold text-white block truncate drop-shadow-md">
                {{ reel.user?.name }}
              </span>
              <div class="flex items-center gap-1 text-[10px] text-purple-200 drop-shadow truncate">
                <svg class="w-2.5 h-2.5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <span class="truncate">{{ reel.audio_title || 'Original Audio' }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- 3. Empty State Card when no reels -->
        <div
          v-else
          @click="$emit('open-reel-creator')"
          class="flex-shrink-0 w-[200px] sm:w-[220px] h-[184px] rounded-2xl border-2 border-dashed border-purple-300 dark:border-purple-800/60 bg-purple-50/50 dark:bg-purple-950/20 p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-purple-100/50 dark:hover:bg-purple-900/30 transition-all group"
        >
          <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-600 dark:text-purple-300 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-sm">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
            </svg>
          </div>
          <p class="text-xs font-bold text-slate-800 dark:text-slate-200">No Reels Yet</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Be the first to upload and share a 9:16 Reel!</p>
          <span class="mt-2 px-2.5 py-1 rounded-lg bg-purple-600 text-white text-[11px] font-bold shadow-sm group-hover:bg-purple-700 transition-colors">
            + Create Reel
          </span>
        </div>
      </template>
    </div>

    <!-- Right Scroll Navigation Arrow Button -->
    <button
      type="button"
      @click="scrollRight"
      class="hidden sm:flex absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-[#E4E7EC] dark:border-slate-700 shadow-md items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-700 transition-all z-10 cursor-pointer"
      title="Scroll Right"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <!-- 3. Fullscreen / Centered Story Viewer Modal -->
    <Teleport to="body">
      <div
        v-if="isViewerOpen && currentViewingGroup"
        class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center animate-fade-in"
        @keydown.esc="closeViewer"
        tabindex="0"
      >
        <!-- Close Button -->
        <button
          type="button"
          @click="closeViewer"
          class="absolute top-4 right-4 z-50 p-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
          title="Close (Esc)"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Previous Story Arrow Button (Desktop) -->
        <button
          v-if="currentGroupIndex > 0 || currentStoryIndex > 0"
          type="button"
          @click="prevStory"
          class="hidden md:flex absolute left-8 top-1/2 -translate-y-1/2 z-40 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Story Container Box (Mobile 9:16 aspect ratio) -->
        <div
          class="relative w-full max-w-sm sm:max-w-md h-[90vh] max-h-[780px] bg-slate-950 rounded-3xl overflow-hidden shadow-2xl flex flex-col justify-between"
          @mousedown="pauseTimer"
          @mouseup="resumeTimer"
          @touchstart="pauseTimer"
          @touchend="resumeTimer"
        >
          <!-- Top Controls & Progress Bars -->
          <div class="absolute top-0 inset-x-0 z-30 p-3 bg-gradient-to-b from-black/80 via-black/40 to-transparent">
            <!-- Progress Bar Segments -->
            <div class="flex items-center gap-1.5 mb-3">
              <div
                v-for="(s, sIdx) in currentStoriesList"
                :key="s.id || sIdx"
                class="flex-1 h-1 rounded-full bg-white/30 overflow-hidden"
              >
                <div
                  class="h-full bg-white rounded-full transition-all duration-100"
                  :style="{
                    width: sIdx < currentStoryIndex ? '100%' : (sIdx === currentStoryIndex ? progressPercent + '%' : '0%')
                  }"
                ></div>
              </div>
            </div>

            <!-- Author Header -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <img
                  :src="currentViewingGroup.user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                  class="w-9 h-9 rounded-full object-cover ring-2 ring-white/50"
                  alt="Avatar"
                />
                <div>
                  <h4 class="text-sm font-bold text-white drop-shadow">
                    {{ currentViewingGroup.user?.name }}
                  </h4>
                  <p class="text-[11px] text-white/70">
                    {{ formatStoryTime(currentStory?.created_at) }}
                  </p>
                </div>
              </div>

              <!-- Play/Pause Toggle -->
              <button
                type="button"
                @click.stop="togglePlayPause"
                class="p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/10"
              >
                <svg v-if="isPaused" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Story Center Display: Image, Video, or Text -->
          <div class="relative flex-1 flex items-center justify-center overflow-hidden">
            <!-- Left & Right Touch Click Zones -->
            <div @click.stop="prevStory" class="absolute left-0 inset-y-0 w-1/3 z-20 cursor-pointer"></div>
            <div @click.stop="nextStory" class="absolute right-0 inset-y-0 w-1/3 z-20 cursor-pointer"></div>

            <!-- Media Image / Video -->
            <img
              v-if="currentStoryMediaUrl"
              :src="currentStoryMediaUrl"
              class="w-full h-full object-cover"
              alt="Story Content"
            />
            <!-- Text Story on Stylized Gradient -->
            <div
              v-else
              :class="[
                'w-full h-full p-8 flex flex-col items-center justify-center text-center text-white font-bold text-xl leading-relaxed',
                currentStory?.background_color || 'bg-gradient-to-tr from-blue-600 to-indigo-800'
              ]"
            >
              <p>{{ currentStory?.content || 'Moment shared on SocialHub' }}</p>
            </div>
          </div>

          <!-- Bottom Actions: Reactions & Quick Reply -->
          <div class="relative z-30 p-3 bg-gradient-to-t from-black/90 via-black/50 to-transparent flex items-center gap-2">
            <!-- Quick Emoji Reactions Bar -->
            <div class="flex items-center gap-1">
              <button
                v-for="emoji in ['👍', '❤️', '🔥', '😂', '😮', '😢']"
                :key="emoji"
                type="button"
                @click.stop="reactToStory(emoji)"
                class="text-xl p-1 hover:scale-125 transition-transform cursor-pointer"
              >
                {{ emoji }}
              </button>
            </div>

            <!-- Reply Input -->
            <div class="flex-1 flex items-center gap-2 bg-white/15 rounded-full px-3 py-1.5 border border-white/20">
              <input
                v-model="replyText"
                @keyup.enter="sendReply"
                type="text"
                placeholder="Reply to story..."
                class="w-full bg-transparent text-xs text-white placeholder-white/60 outline-none"
              />
              <button
                type="button"
                @click="sendReply"
                :disabled="!replyText.trim()"
                class="text-white disabled:opacity-40 hover:text-blue-400"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Next Story Arrow Button (Desktop) -->
        <button
          v-if="currentGroupIndex < activeStoryGroups.length - 1 || currentStoryIndex < currentStoriesList.length - 1"
          type="button"
          @click="nextStory"
          class="hidden md:flex absolute right-8 top-1/2 -translate-y-1/2 z-40 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </Teleport>

    <!-- 4. Story Creator Modal -->
    <Teleport to="body">
      <div
        v-if="isCreatorOpen"
        class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      >
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-[#E4E7EC] dark:border-slate-800 w-full max-w-lg shadow-2xl overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="p-4 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-base text-[#172033] dark:text-white">Create a Story</h3>
            <button
              type="button"
              @click="closeCreatorModal"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-full"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Creator Mode Switcher Tabs -->
          <div class="p-3 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2 border-b border-[#E4E7EC] dark:border-slate-800">
            <button
              type="button"
              @click="creatorType = 'media'"
              :class="[
                'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                creatorType === 'media'
                  ? 'bg-white dark:bg-slate-900 text-[#1877F2] shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60'
              ]"
            >
              <span>📷</span>
              <span>Photo / Video Story</span>
            </button>
            <button
              type="button"
              @click="creatorType = 'text'"
              :class="[
                'flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                creatorType === 'text'
                  ? 'bg-white dark:bg-slate-900 text-[#1877F2] shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60'
              ]"
            >
              <span>✍️</span>
              <span>Text Story</span>
            </button>
          </div>

          <!-- Body -->
          <div class="p-5 space-y-4">
            <!-- Media Upload Mode -->
            <div v-if="creatorType === 'media'" class="space-y-3">
              <div
                v-if="!creatorMediaPreview"
                class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-8 flex flex-col items-center justify-center text-center hover:border-[#1877F2] transition-colors cursor-pointer relative"
              >
                <input
                  type="file"
                  accept="image/*,video/*"
                  @change="handleCreatorFile"
                  class="absolute inset-0 opacity-0 cursor-pointer"
                />
                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mb-2">
                  <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Click to upload photo or vertical video</p>
                <p class="text-[11px] text-slate-400 mt-0.5">PNG, JPG, WebP, MP4 up to 50MB</p>
              </div>

              <!-- Media Preview -->
              <div v-else class="relative rounded-2xl overflow-hidden aspect-[9/14] max-h-72 bg-black mx-auto">
                <img :src="creatorMediaPreview" class="w-full h-full object-cover" alt="Preview" />
                <button
                  type="button"
                  @click="removeCreatorMedia"
                  class="absolute top-2 right-2 p-1.5 rounded-full bg-black/60 text-white hover:bg-black/80"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Text Story Mode -->
            <div v-else class="space-y-3">
              <!-- Live Preview Card -->
              <div
                :class="[
                  'rounded-2xl p-6 text-center text-white font-bold text-lg min-h-48 flex items-center justify-center shadow-inner transition-all',
                  creatorSelectedGradient
                ]"
              >
                <p class="whitespace-pre-line">{{ creatorText || 'Start typing your story...' }}</p>
              </div>

              <textarea
                v-model="creatorText"
                rows="3"
                placeholder="What's on your mind for this 24-hour story?"
                class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 text-xs text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-[#1877F2]"
              ></textarea>

              <!-- Color Palette Picker -->
              <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                  Background Style
                </label>
                <div class="flex items-center gap-2">
                  <button
                    v-for="grad in gradients"
                    :key="grad.name"
                    type="button"
                    @click="creatorSelectedGradient = grad.class"
                    :class="[
                      'w-7 h-7 rounded-full shadow-sm transition-transform',
                      grad.class,
                      creatorSelectedGradient === grad.class ? 'ring-2 ring-offset-2 ring-[#1877F2] scale-110' : ''
                    ]"
                    :title="grad.name"
                  ></button>
                </div>
              </div>
            </div>

            <!-- Privacy Selector -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
              <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Audience:</span>
              <select
                v-model="creatorPrivacy"
                class="text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700 outline-none"
              >
                <option value="public">🌐 Public</option>
                <option value="friends">👥 Friends Only</option>
                <option value="only_me">🔒 Only Me</option>
              </select>
            </div>

            <!-- Error Banner -->
            <div v-if="creatorError" class="p-2.5 bg-red-50 dark:bg-red-950/40 border border-red-200 rounded-xl text-xs text-red-600">
              {{ creatorError }}
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-[#E4E7EC] dark:border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="closeCreatorModal"
              class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="publishStory"
              :disabled="isPublishing || (creatorType === 'media' && !creatorMediaFile) || (creatorType === 'text' && !creatorText.trim())"
              class="px-5 py-2 text-xs font-bold bg-[#1877F2] hover:bg-[#1666D8] text-white rounded-xl shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
            >
              <svg v-if="isPublishing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isPublishing ? 'Publishing...' : 'Share to Story' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Reel Viewer Modal -->
    <ReelViewerModal
      :is-open="isReelViewerOpen"
      :reels="activeReelsList"
      :initial-index="selectedReelIndex"
      :current-user="currentUser"
      @close="isReelViewerOpen = false"
      @toast="$emit('toast', $event)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import type { User, UserStoryGroup, Story, ReelItem } from '../types';
import ReelViewerModal from './Reels/ReelViewerModal.vue';

const props = defineProps<{
  currentUser: User | null;
  stories?: UserStoryGroup[];
  reels?: ReelItem[];
}>();

const emit = defineEmits<{
  (e: 'open-live'): void;
  (e: 'open-reel-creator'): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

const activeTab = ref<'stories' | 'reels'>('stories');
const scrollContainer = ref<HTMLElement | null>(null);

const isReelViewerOpen = ref(false);
const selectedReelIndex = ref(0);

const openReelViewer = (index: number) => {
  selectedReelIndex.value = index;
  isReelViewerOpen.value = true;
};

const formatReelCount = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
  return num.toString();
};

const internalReels = ref<ReelItem[]>([]);
const failedReelImages = ref<Record<string | number, boolean>>({});

watch(
  () => props.reels,
  (newReels) => {
    internalReels.value = newReels && newReels.length > 0 ? [...newReels] : [];
  },
  { immediate: true }
);

const activeReelsList = computed(() => internalReels.value);

const scrollRight = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollBy({ left: 240, behavior: 'smooth' });
  }
};

// Fallback high-fidelity sample stories if backend has none yet
const fallbackStoryGroups: UserStoryGroup[] = [
  {
    user: {
      id: 881,
      name: 'Sarah Wilson',
      username: 'sarahwilson',
      avatar_url: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=150&q=80',
    },
    all_viewed: false,
    stories_count: 1,
    stories: [
      {
        id: 901,
        user_id: 881,
        type: 'media',
        media: [
          {
            id: 1,
            url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&h=650&q=80',
          },
        ],
        created_at: new Date(Date.now() - 3600000).toISOString(),
      },
    ],
  },
  {
    user: {
      id: 882,
      name: 'James Miller',
      username: 'jamesmiller',
      avatar_url: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80',
    },
    all_viewed: false,
    stories_count: 1,
    stories: [
      {
        id: 902,
        user_id: 882,
        type: 'media',
        media: [
          {
            id: 2,
            url: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=400&h=650&q=80',
          },
        ],
        created_at: new Date(Date.now() - 7200000).toISOString(),
      },
    ],
  },
  {
    user: {
      id: 883,
      name: 'Emma Davis',
      username: 'emmadavis',
      avatar_url: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80',
    },
    all_viewed: false,
    stories_count: 1,
    stories: [
      {
        id: 903,
        user_id: 883,
        type: 'media',
        media: [
          {
            id: 3,
            url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=400&h=650&q=80',
          },
        ],
        created_at: new Date(Date.now() - 14400000).toISOString(),
      },
    ],
  },
  {
    user: {
      id: 884,
      name: 'Daniel Smith',
      username: 'danielsmith',
      avatar_url: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
    },
    all_viewed: false,
    stories_count: 1,
    stories: [
      {
        id: 904,
        user_id: 884,
        type: 'media',
        media: [
          {
            id: 4,
            url: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=400&h=650&q=80',
          },
        ],
        created_at: new Date(Date.now() - 28800000).toISOString(),
      },
    ],
  },
];

const internalStoryGroups = ref<UserStoryGroup[]>([]);

watch(
  () => props.stories,
  (newStories) => {
    if (newStories && newStories.length > 0) {
      internalStoryGroups.value = [...newStories];
    } else {
      internalStoryGroups.value = [...fallbackStoryGroups];
    }
  },
  { immediate: true }
);

const activeStoryGroups = computed(() => internalStoryGroups.value);

const getGroupThumbnail = (group: UserStoryGroup): string => {
  const firstStory = group.stories?.[0];
  if (firstStory?.media?.[0]?.url) return firstStory.media[0].url;
  if (firstStory?.story_media?.[0]?.media?.url) return firstStory.story_media[0].media.url;
  return 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=300&q=80';
};

/* ------------------------------------------------------------- */
/* STORY VIEWER LOGIC */
/* ------------------------------------------------------------- */
const isViewerOpen = ref(false);
const currentGroupIndex = ref(0);
const currentStoryIndex = ref(0);
const progressPercent = ref(0);
const isPaused = ref(false);
const replyText = ref('');
let storyInterval: any = null;

const currentViewingGroup = computed<UserStoryGroup | null>(() => {
  return activeStoryGroups.value[currentGroupIndex.value] || null;
});

const currentStoriesList = computed<Story[]>(() => {
  return currentViewingGroup.value?.stories || [];
});

const currentStory = computed<Story | null>(() => {
  return currentStoriesList.value[currentStoryIndex.value] || null;
});

const currentStoryMediaUrl = computed(() => {
  const s = currentStory.value;
  if (!s) return null;
  if (s.media?.[0]?.url) return s.media[0].url;
  if (s.story_media?.[0]?.media?.url) return s.story_media[0].media.url;
  return null;
});

const openViewerModal = (group: UserStoryGroup, groupIdx: number) => {
  currentGroupIndex.value = groupIdx;
  currentStoryIndex.value = 0;
  progressPercent.value = 0;
  isPaused.value = false;
  isViewerOpen.value = true;
  startStoryTimer();
  markStoryViewed();
};

const closeViewer = () => {
  clearInterval(storyInterval);
  isViewerOpen.value = false;
};

const startStoryTimer = () => {
  clearInterval(storyInterval);
  progressPercent.value = 0;
  const stepMs = 50;
  const totalMs = 5000;
  const increment = (stepMs / totalMs) * 100;

  storyInterval = setInterval(() => {
    if (!isPaused.value) {
      progressPercent.value += increment;
      if (progressPercent.value >= 100) {
        nextStory();
      }
    }
  }, stepMs);
};

const pauseTimer = () => {
  isPaused.value = true;
};

const resumeTimer = () => {
  isPaused.value = false;
};

const togglePlayPause = () => {
  isPaused.value = !isPaused.value;
};

const nextStory = () => {
  if (currentStoryIndex.value < currentStoriesList.value.length - 1) {
    currentStoryIndex.value++;
    startStoryTimer();
    markStoryViewed();
  } else if (currentGroupIndex.value < activeStoryGroups.value.length - 1) {
    currentGroupIndex.value++;
    currentStoryIndex.value = 0;
    startStoryTimer();
    markStoryViewed();
  } else {
    closeViewer();
  }
};

const prevStory = () => {
  if (currentStoryIndex.value > 0) {
    currentStoryIndex.value--;
    startStoryTimer();
  } else if (currentGroupIndex.value > 0) {
    currentGroupIndex.value--;
    currentStoryIndex.value = (activeStoryGroups.value[currentGroupIndex.value]?.stories.length || 1) - 1;
    startStoryTimer();
  }
};

const markStoryViewed = async () => {
  const story = currentStory.value;
  if (!story || !story.id || story.id >= 900) return; // skip dummy
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v2/stories/${story.id}/view`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
  } catch (e) {
    // silently ignore view error
  }
};

const reactToStory = async (emoji: string) => {
  const story = currentStory.value;
  if (!story || !story.id) return;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v2/stories/${story.id}/react`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ type: 'like' }),
    });
  } catch (e) {
    console.error(e);
  }
};

const sendReply = async () => {
  if (!replyText.value.trim() || !currentStory.value) return;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v2/stories/${currentStory.value.id}/reply`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ message: replyText.value }),
    });
    replyText.value = '';
  } catch (e) {
    console.error(e);
  }
};

const formatStoryTime = (iso?: string) => {
  if (!iso) return '2h ago';
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  return `${hrs}h ago`;
};

/* ------------------------------------------------------------- */
/* STORY CREATOR LOGIC */
/* ------------------------------------------------------------- */
const isCreatorOpen = ref(false);
const creatorType = ref<'media' | 'text'>('media');
const creatorMediaFile = ref<File | null>(null);
const creatorMediaPreview = ref<string | null>(null);
const creatorText = ref('');
const creatorSelectedGradient = ref('bg-gradient-to-tr from-blue-600 to-indigo-800');
const creatorPrivacy = ref<'public' | 'friends' | 'only_me'>('public');
const isPublishing = ref(false);
const creatorError = ref('');

const gradients = [
  { name: 'Classic Blue', class: 'bg-gradient-to-tr from-blue-600 to-indigo-800' },
  { name: 'Sunset Orange', class: 'bg-gradient-to-tr from-rose-500 to-amber-500' },
  { name: 'Emerald Forest', class: 'bg-gradient-to-tr from-emerald-600 to-teal-800' },
  { name: 'Purple Dream', class: 'bg-gradient-to-tr from-purple-600 to-pink-600' },
  { name: 'Dark Velvet', class: 'bg-gradient-to-tr from-slate-900 to-slate-800' },
];

const openCreatorModal = () => {
  isCreatorOpen.value = true;
  creatorError.value = '';
};

const closeCreatorModal = () => {
  isCreatorOpen.value = false;
  removeCreatorMedia();
  creatorText.value = '';
};

const handleCreatorFile = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!target.files?.[0]) return;
  const file = target.files[0];
  creatorMediaFile.value = file;
  creatorMediaPreview.value = URL.createObjectURL(file);
};

const removeCreatorMedia = () => {
  creatorMediaFile.value = null;
  creatorMediaPreview.value = null;
};

const publishStory = async () => {
  isPublishing.value = true;
  creatorError.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

    let mediaIds: number[] = [];
    if (creatorType.value === 'media' && creatorMediaFile.value) {
      const formData = new FormData();
      formData.append('file', creatorMediaFile.value);
      formData.append('collection', 'story');

      const uploadRes = await fetch('/api/v1/media/upload', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: formData,
      });

      const uploadData = await uploadRes.json();
      if (!uploadRes.ok || (!uploadData.data?.id && !uploadData.data?.media_id)) {
        throw new Error(uploadData.message || 'Media upload failed.');
      }
      mediaIds.push(uploadData.data.id || uploadData.data.media_id);
    }

    const payload: any = {
      type: creatorType.value,
      privacy: creatorPrivacy.value,
    };

    if (creatorType.value === 'media') {
      payload.media_ids = mediaIds;
    } else {
      payload.content = creatorText.value;
      payload.background_color = creatorSelectedGradient.value;
    }

    const res = await fetch('/api/v2/stories', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      // Prepend to stories list
      const newGroup: UserStoryGroup = {
        user: props.currentUser || { id: 1, name: 'You', username: 'me' },
        all_viewed: false,
        stories_count: 1,
        stories: [data.data],
      };
      internalStoryGroups.value.unshift(newGroup);
      closeCreatorModal();
    } else {
      creatorError.value = data.message || 'Failed to publish story.';
    }
  } catch (err: any) {
    creatorError.value = err.message || 'An error occurred while publishing story.';
  } finally {
    isPublishing.value = false;
  }
};

onUnmounted(() => {
  clearInterval(storyInterval);
});
</script>
