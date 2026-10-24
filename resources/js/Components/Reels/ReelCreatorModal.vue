<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in"
      @keydown.esc="closeModal"
      @click.self="closeModal"
      tabindex="0"
    >
      <div
        class="relative w-full max-w-3xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800/80 overflow-hidden flex flex-col max-h-[92vh] animate-in zoom-in-95 duration-200"
        @click.stop
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/70 backdrop-blur-sm">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-purple-600 via-pink-500 to-rose-500 text-white flex items-center justify-center shadow-lg shadow-purple-500/25 ring-2 ring-purple-500/20">
              <svg class="w-4.5 h-4.5 fill-current" viewBox="0 0 24 24">
                <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
              </svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Create Reel Studio</h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                  9:16 HD
                </span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Share short vertical videos, viral moments and sounds with your audience</p>
            </div>
          </div>

          <button
            type="button"
            @click.stop="closeModal"
            class="p-2 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            title="Close"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body (Two Columns on Desktop) -->
        <div class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-12 gap-5 p-5">
          <!-- Left Column: Video Preview / Dropzone / Cover (5 cols) -->
          <div class="md:col-span-5 flex flex-col items-center space-y-3">
            <!-- 9:16 Aspect Ratio Container -->
            <div
              class="relative w-full max-w-[270px] aspect-[9/16] rounded-3xl overflow-hidden bg-slate-950 border-2 transition-all flex flex-col items-center justify-center shadow-xl group"
              :class="[
                videoFile
                  ? 'border-purple-500/60 ring-4 ring-purple-500/10'
                  : isDragging
                  ? 'border-purple-500 bg-purple-950/20 scale-[1.02]'
                  : 'border-dashed border-slate-300 dark:border-slate-700 hover:border-purple-500/70 hover:bg-slate-900/40'
              ]"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
            >
              <!-- Video Preview When File Selected -->
              <template v-if="videoPreviewUrl">
                <video
                  ref="videoElement"
                  :src="videoPreviewUrl"
                  playsinline
                  loop
                  autoplay
                  muted
                  controls
                  @loadeddata="onVideoLoaded"
                  @canplay="onVideoLoaded"
                  class="w-full h-full object-cover"
                ></video>

                <!-- Floating Badges -->
                <div class="absolute top-3 inset-x-3 flex items-center justify-between text-white text-[11px] font-semibold drop-shadow pointer-events-none z-10">
                  <span class="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md flex items-center gap-1.5 border border-white/10">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>9:16 Vertical</span>
                  </span>
                  <span v-if="videoDuration > 0" class="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md font-mono border border-white/10">
                    {{ formatDuration(videoDuration) }}
                  </span>
                </div>

                <!-- Replace Video Trigger Overlay Button -->
                <label class="absolute bottom-3 inset-x-3 py-2 px-3 rounded-2xl bg-black/75 hover:bg-black/90 text-white text-xs font-bold backdrop-blur-md flex items-center justify-center gap-2 cursor-pointer transition-all shadow-lg border border-white/10 hover:scale-[1.02] active:scale-95 z-10">
                  <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                  <span>Choose Different Video</span>
                  <input
                    type="file"
                    accept="video/mp4,video/webm,video/quicktime,video/mov,video/x-m4v,video/*"
                    @change="handleFileChange"
                    class="hidden"
                  />
                </label>
              </template>

              <!-- Empty State / File Picker Trigger -->
              <template v-else>
                <input
                  type="file"
                  id="reelFileInput"
                  accept="video/mp4,video/webm,video/quicktime,video/mov,video/x-m4v,video/*"
                  @change="handleFileChange"
                  class="hidden"
                />
                <label
                  for="reelFileInput"
                  class="flex flex-col items-center justify-center p-6 text-center cursor-pointer w-full h-full select-none"
                >
                  <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-purple-500/20 to-pink-500/20 text-purple-500 dark:text-purple-400 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform shadow-inner">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <span class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">Upload 9:16 Reel</span>
                  <p class="text-xs text-slate-500 dark:text-slate-400 max-w-[200px] leading-relaxed mb-4">
                    Drag and drop your video here, or browse files from your device
                  </p>
                  <span class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold shadow-md shadow-purple-500/25 group-hover:shadow-purple-500/40 transition-all">
                    Browse Video
                  </span>
                  <span class="mt-3 text-[10px] text-slate-400 font-medium">
                    MP4, WebM, MOV • Max 100MB
                  </span>
                </label>
              </template>
            </div>

            <!-- Video File Metadata Pill -->
            <div v-if="videoFile" class="w-full max-w-[270px] p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-300 space-y-1 shadow-sm">
              <div class="flex items-center justify-between font-bold">
                <span class="truncate max-w-[160px]">{{ videoFile.name }}</span>
                <span class="font-mono text-purple-600 dark:text-purple-400">{{ formatFileSize(videoFile.size) }}</span>
              </div>
              <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-medium pt-0.5">
                <span>Duration: {{ formatDuration(videoDuration) }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">✓ Ready to share</span>
              </div>
            </div>

            <!-- Thumbnail / Cover Section -->
            <div v-if="videoFile" class="w-full max-w-[270px] space-y-2 pt-1">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Cover Thumbnail</span>
                <span v-if="coverPreviewUrl" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/60 px-2 py-0.5 rounded-md">
                  Custom Cover
                </span>
              </div>

              <div class="flex items-center gap-2.5">
                <!-- Cover Preview Box -->
                <div class="w-14 h-20 rounded-xl bg-slate-950 border border-slate-200 dark:border-slate-700 overflow-hidden shrink-0 relative shadow-inner">
                  <img
                    v-if="coverPreviewUrl"
                    :src="coverPreviewUrl"
                    alt="Cover Preview"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-[10px] p-1 text-center">
                    <span>Auto Frame</span>
                  </div>
                </div>

                <div class="flex-1 space-y-1.5">
                  <button
                    type="button"
                    @click="captureFrameAsCover"
                    class="w-full py-1.5 px-2.5 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:hover:bg-purple-900/60 text-[11px] font-bold text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                  >
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Capture Video Frame</span>
                  </button>

                  <label class="w-full py-1.5 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors flex items-center justify-center gap-1.5 cursor-pointer text-center shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Upload Cover Image</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" @change="handleCoverFileChange" class="hidden" />
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Reel Details & Controls (7 cols) -->
          <div class="md:col-span-7 flex flex-col space-y-4">
            <!-- Creator Info Header -->
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
              <img
                :src="currentUser?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                alt="Avatar"
                class="w-10 h-10 rounded-full object-cover ring-2 ring-purple-500/30 shadow"
              />
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 truncate">{{ currentUser?.name }}</span>
                  <span v-if="currentUser?.is_verified" class="text-blue-500">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                  </span>
                </div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 block truncate">
                  Posting as {{ currentUser?.username ? '@' + currentUser.username : 'yourself' }}
                </span>
              </div>
            </div>

            <!-- Caption & Hashtags -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-bold text-slate-800 dark:text-slate-200">Caption & Description</label>
                <span class="text-[10px] font-semibold" :class="caption.length > 1800 ? 'text-amber-500' : 'text-slate-400'">
                  {{ caption.length }} / 2000
                </span>
              </div>
              <textarea
                v-model="caption"
                rows="3"
                maxlength="2000"
                placeholder="Write an engaging caption for your reel... Add hashtags, tags, and description!"
                class="w-full bg-[#F5F7FB] dark:bg-slate-800/80 text-xs text-slate-900 dark:text-slate-100 rounded-2xl p-3 border border-slate-200 dark:border-slate-700/80 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none transition-all placeholder:text-slate-400"
              ></textarea>

              <!-- Quick Hashtag Chips -->
              <div class="flex flex-wrap items-center gap-1.5 mt-2">
                <span class="text-[11px] text-slate-400 font-bold mr-1">Trending:</span>
                <button
                  v-for="tag in popularTags"
                  :key="tag"
                  type="button"
                  @click="insertHashtag(tag)"
                  class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-purple-100 dark:bg-slate-800 dark:hover:bg-purple-950/50 text-[11px] font-bold text-slate-600 hover:text-purple-600 dark:text-slate-300 dark:hover:text-purple-400 border border-slate-200/60 dark:border-slate-700 transition-all cursor-pointer hover:scale-105 active:scale-95"
                >
                  {{ tag }}
                </button>
              </div>
            </div>

            <!-- Sound / Audio Track Title & Presets -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-bold text-slate-800 dark:text-slate-200">Sound / Audio Track</label>
                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold">Original or custom beat</span>
              </div>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-purple-500">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                  </svg>
                </span>
                <input
                  v-model="audioTitle"
                  type="text"
                  placeholder="Original Audio - Bondhoo Sound"
                  class="w-full bg-[#F5F7FB] dark:bg-slate-800/80 text-xs text-slate-900 dark:text-slate-100 rounded-xl pl-9 pr-3 py-2.5 border border-slate-200 dark:border-slate-700/80 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all font-medium"
                />
              </div>

              <!-- Quick Audio Presets -->
              <div class="flex flex-wrap items-center gap-1.5 mt-2">
                <span class="text-[11px] text-slate-400 font-semibold mr-1">Presets:</span>
                <button
                  v-for="sound in soundPresets"
                  :key="sound"
                  type="button"
                  @click="audioTitle = sound"
                  :class="[
                    'px-2 py-0.5 rounded-lg text-[10px] font-bold transition-all cursor-pointer',
                    audioTitle === sound
                      ? 'bg-purple-600 text-white shadow-xs'
                      : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400'
                  ]"
                >
                  {{ sound }}
                </button>
              </div>
            </div>

            <!-- Audience & Permissions Settings -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div>
                <label class="text-xs font-bold text-slate-800 dark:text-slate-200 block mb-1.5">Audience</label>
                <select
                  v-model="privacy"
                  class="w-full bg-[#F5F7FB] dark:bg-slate-800/80 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all cursor-pointer"
                >
                  <option value="public">🌐 Public (Everyone)</option>
                  <option value="friends">👥 Friends Only</option>
                  <option value="only_me">🔒 Only Me (Private)</option>
                </select>
              </div>

              <div class="space-y-1.5 flex flex-col justify-end">
                <label class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                  <input
                    type="checkbox"
                    v-model="allowComments"
                    class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4"
                  />
                  <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Allow Comments</span>
                </label>

                <label class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                  <input
                    type="checkbox"
                    v-model="allowDuet"
                    class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4"
                  />
                  <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Allow Duet / Remix</span>
                </label>
              </div>
            </div>

            <!-- Upload Progress Bar (when publishing) -->
            <div v-if="isUploading" class="space-y-2 pt-2 bg-purple-50/60 dark:bg-purple-950/30 p-3.5 rounded-2xl border border-purple-200 dark:border-purple-800/60">
              <div class="flex items-center justify-between text-xs font-bold text-purple-700 dark:text-purple-300">
                <span class="flex items-center gap-2">
                  <svg class="animate-spin w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ uploadStatusText }}</span>
                </span>
                <span class="font-mono">{{ uploadProgress }}%</span>
              </div>
              <div class="w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 rounded-full transition-all duration-300 shadow-sm"
                  :style="{ width: `${uploadProgress}%` }"
                ></div>
              </div>
            </div>

            <!-- Error Banner with Detailed Info & Retry -->
            <div v-if="errorMessage" class="text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/50 p-3 rounded-2xl border border-red-200 dark:border-red-800/80 flex items-start justify-between gap-3 shadow-xs animate-shake">
              <div class="flex items-start gap-2.5 min-w-0">
                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="min-w-0">
                  <span class="font-bold block">Upload Issue:</span>
                  <span class="text-[11px] leading-relaxed break-words">{{ errorMessage }}</span>
                </div>
              </div>
              <button
                type="button"
                @click="publishReel(false)"
                class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shrink-0 transition-colors shadow-sm cursor-pointer"
              >
                Retry
              </button>
            </div>
          </div>
        </div>

        <!-- Hidden canvas for thumbnail frame capture -->
        <canvas ref="canvasEl" class="hidden"></canvas>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/70">
          <div class="text-[11px] text-slate-500 dark:text-slate-400 hidden sm:flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <span>Vertical 9:16 reels stream in HD with adaptive delivery.</span>
          </div>

          <div class="flex items-center gap-2.5 ml-auto sm:ml-0">
            <button
              type="button"
              @click.stop="closeModal"
              class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="publishReel(true)"
              :disabled="!videoFile || isUploading"
              class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50 cursor-pointer"
            >
              Save Draft
            </button>
            <button
              type="button"
              @click="publishReel(false)"
              :disabled="!videoFile || isUploading"
              class="px-5 py-2 text-xs font-extrabold text-white bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 hover:from-purple-700 hover:to-pink-700 rounded-xl shadow-md shadow-purple-500/25 transition-all disabled:opacity-50 cursor-pointer flex items-center gap-1.5 hover:scale-105 active:scale-95"
            >
              <svg v-if="isUploading" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isUploading ? 'Publishing...' : 'Share Reel' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import type { User, ReelItem } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  currentUser: User | null;
  pageId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'reel-created', reel: ReelItem): void;
}>();

const isDragging = ref(false);
const videoFile = ref<File | null>(null);
const videoPreviewUrl = ref<string | null>(null);
const videoElement = ref<HTMLVideoElement | null>(null);
const canvasEl = ref<HTMLCanvasElement | null>(null);
const videoDuration = ref(0);

const coverFile = ref<File | null>(null);
const coverBlob = ref<Blob | null>(null);
const coverPreviewUrl = ref<string | null>(null);

const caption = ref('');
const audioTitle = ref('Original Audio');
const privacy = ref<'public' | 'friends' | 'only_me'>('public');
const allowComments = ref(true);
const allowDuet = ref(true);

const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadStatusText = ref('');
const errorMessage = ref('');

const popularTags = ['#reels', '#viral', '#trending', '#dhaka', '#lifestyle', '#creators', '#bangladesh'];
const soundPresets = ['Original Audio', 'Trending Beat', 'Dhaka Vibes', 'Lo-Fi Chill', 'Acoustic Soul', 'Club Remix'];

const insertHashtag = (tag: string) => {
  if (caption.value.includes(tag)) return;
  caption.value = caption.value ? `${caption.value.trim()} ${tag} ` : `${tag} `;
};

const formatDuration = (sec: number) => {
  const m = Math.floor(sec / 60);
  const s = Math.floor(sec % 60);
  return `${m}:${s < 10 ? '0' : ''}${s}`;
};

const formatFileSize = (bytes: number) => {
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!target.files?.[0]) return;
  processFile(target.files[0]);
};

const handleDrop = (e: DragEvent) => {
  isDragging.value = false;
  if (!e.dataTransfer?.files?.[0]) return;
  processFile(e.dataTransfer.files[0]);
};

const processFile = (file: File) => {
  errorMessage.value = '';

  const isVideo = file.type.startsWith('video/') || /\.(mp4|webm|mov|m4v|3gp|quicktime)$/i.test(file.name);
  if (!isVideo) {
    errorMessage.value = 'Please select a valid video file (.mp4, .mov, or .webm).';
    return;
  }

  // Maximum size 100MB
  if (file.size > 100 * 1024 * 1024) {
    errorMessage.value = `Video file size is ${(file.size / (1024 * 1024)).toFixed(1)}MB. Maximum allowed upload size is 100MB.`;
    return;
  }

  videoFile.value = file;
  if (videoPreviewUrl.value) {
    URL.revokeObjectURL(videoPreviewUrl.value);
  }
  videoPreviewUrl.value = URL.createObjectURL(file);

  // Probe duration
  const tempVideo = document.createElement('video');
  tempVideo.src = videoPreviewUrl.value;
  tempVideo.onloadedmetadata = () => {
    videoDuration.value = Math.round(tempVideo.duration);
  };
};

const captureFrameBlob = (): Promise<Blob | null> => {
  return new Promise((resolve) => {
    if (!videoElement.value || !canvasEl.value) {
      resolve(null);
      return;
    }
    const video = videoElement.value;
    const canvas = canvasEl.value;

    canvas.width = video.videoWidth || 720;
    canvas.height = video.videoHeight || 1280;

    const ctx = canvas.getContext('2d');
    if (!ctx) {
      resolve(null);
      return;
    }

    try {
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      canvas.toBlob((blob) => {
        resolve(blob);
      }, 'image/jpeg', 0.88);
    } catch (e) {
      console.warn('Canvas frame extraction warning:', e);
      resolve(null);
    }
  });
};

const captureFrameAsCover = async () => {
  const blob = await captureFrameBlob();
  if (blob) {
    coverBlob.value = blob;
    if (coverPreviewUrl.value) {
      URL.revokeObjectURL(coverPreviewUrl.value);
    }
    coverPreviewUrl.value = URL.createObjectURL(blob);
  }
};

const onVideoLoaded = () => {
  if (videoElement.value) {
    videoDuration.value = Math.round(videoElement.value.duration || 0);
    // If no cover picked yet, automatically capture the first frame
    if (!coverFile.value && !coverBlob.value) {
      setTimeout(() => {
        captureFrameAsCover();
      }, 300);
    }
  }
};

const handleCoverFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!target.files?.[0]) return;
  const file = target.files[0];
  coverFile.value = file;
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value);
  }
  coverPreviewUrl.value = URL.createObjectURL(file);
};

let activeXhr: XMLHttpRequest | null = null;

const closeModal = () => {
  if (activeXhr) {
    try {
      activeXhr.abort();
    } catch {}
    activeXhr = null;
  }
  isUploading.value = false;
  resetState();
  emit('close');
};

const resetState = () => {
  if (videoPreviewUrl.value) {
    URL.revokeObjectURL(videoPreviewUrl.value);
  }
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value);
  }
  videoFile.value = null;
  videoPreviewUrl.value = null;
  coverFile.value = null;
  coverBlob.value = null;
  coverPreviewUrl.value = null;
  videoDuration.value = 0;
  caption.value = '';
  audioTitle.value = 'Original Audio';
  privacy.value = 'public';
  allowComments.value = true;
  allowDuet.value = true;
  isUploading.value = false;
  uploadProgress.value = 0;
  errorMessage.value = '';
};

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

const uploadMediaWithProgress = (file: File | Blob, collection: string): Promise<number> => {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    activeXhr = xhr;
    const formData = new FormData();
    formData.append('file', file, file instanceof File ? file.name : 'cover.jpg');
    formData.append('collection', collection);

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) {
        const percentComplete = Math.round((e.loaded / e.total) * 75);
        uploadProgress.value = Math.max(10, percentComplete);
      }
    };

    xhr.onload = () => {
      activeXhr = null;
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const res = JSON.parse(xhr.responseText);
          const mediaId = res.data?.id || res.data?.media_id;
          if (mediaId) {
            resolve(mediaId);
          } else {
            reject(new Error(res.message || 'Media upload failed.'));
          }
        } catch {
          reject(new Error('Invalid upload response from server.'));
        }
      } else {
        try {
          const res = JSON.parse(xhr.responseText);
          let msg = res.message;
          if (res.errors) {
            const errList = Object.values(res.errors).flat();
            if (errList.length > 0) {
              msg = errList.join(' ');
            }
          }
          reject(new Error(msg || `Upload failed (Status ${xhr.status}).`));
        } catch {
          if (xhr.status === 413) {
            reject(new Error('Video file exceeds server upload size limit.'));
          } else {
            reject(new Error(`Upload failed with status ${xhr.status}. Please try again.`));
          }
        }
      }
    };

    xhr.onerror = () => {
      activeXhr = null;
      reject(new Error('Network error during file upload. Check your connection.'));
    };

    xhr.onabort = () => {
      activeXhr = null;
      reject(new Error('Upload was cancelled.'));
    };

    xhr.open('POST', '/api/v1/media/upload');
    xhr.withCredentials = true;
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
    xhr.send(formData);
  });
};

const publishReel = async (isDraft: boolean = false) => {
  if (!videoFile.value) {
    errorMessage.value = 'Please select a video file.';
    return;
  }

  isUploading.value = true;
  uploadProgress.value = 10;
  uploadStatusText.value = 'Uploading video (9:16 HD)...';
  errorMessage.value = '';

  try {
    // 1. Upload Video File
    const mediaId = await uploadMediaWithProgress(videoFile.value, 'reel');

    // 2. Upload Cover if custom cover selected, or auto-capture from video
    let coverMediaId: number | null = null;
    if (coverFile.value) {
      uploadStatusText.value = 'Uploading custom cover...';
      try {
        coverMediaId = await uploadMediaWithProgress(coverFile.value, 'reel');
      } catch (e) {
        console.warn('Cover upload warning:', e);
      }
    } else {
      if (!coverBlob.value && videoElement.value) {
        uploadStatusText.value = 'Extracting video thumbnail...';
        const frameBlob = await captureFrameBlob();
        if (frameBlob) {
          coverBlob.value = frameBlob;
        }
      }

      if (coverBlob.value) {
        uploadStatusText.value = 'Saving frame cover...';
        try {
          coverMediaId = await uploadMediaWithProgress(coverBlob.value, 'reel');
        } catch (e) {
          console.warn('Cover blob warning:', e);
        }
      }
    }

    uploadProgress.value = 85;
    uploadStatusText.value = isDraft ? 'Saving draft...' : 'Publishing reel...';

    // 3. Publish Reel via /api/v2/reels
    const payload: Record<string, any> = {
      media_id: mediaId,
      caption: caption.value.trim(),
      audio_title: audioTitle.value.trim() || 'Original Audio',
      privacy: privacy.value,
      comments_enabled: allowComments.value,
      allow_comments: allowComments.value,
      allow_duet: allowDuet.value,
      is_draft: isDraft,
    };

    if (coverMediaId) {
      payload.cover_media_id = coverMediaId;
    }

    if (props.pageId) {
      payload.page_id = props.pageId;
    }

    const reelRes = await fetch('/api/v2/reels', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(payload),
    });

    const reelData = await reelRes.json();
    if (!reelRes.ok || !reelData.success) {
      throw new Error(reelData.message || 'Failed to create reel.');
    }

    uploadProgress.value = 100;
    uploadStatusText.value = isDraft ? 'Draft saved!' : 'Reel published!';
    isUploading.value = false;

    const newReel: ReelItem = reelData.data;
    emit('reel-created', newReel);

    setTimeout(() => {
      resetState();
      emit('close');
    }, 300);
  } catch (err: any) {
    errorMessage.value = err.message || 'Something went wrong while publishing your reel.';
    isUploading.value = false;
  }
};

onUnmounted(() => {
  if (videoPreviewUrl.value) {
    URL.revokeObjectURL(videoPreviewUrl.value);
  }
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value);
  }
});
</script>
