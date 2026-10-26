<template>
  <div
    class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-4 mb-4 shadow-sm transition-all relative"
    :class="{ 'ring-2 ring-[#1877F2]/40 border-[#1877F2]': isDraggingOver }"
    @dragover.prevent="handleDragOver"
    @dragenter.prevent="handleDragEnter"
    @dragleave.prevent="handleDragLeave"
    @drop.prevent="handleDrop"
  >
    <!-- Full-card Drag Over Highlight Overlay -->
    <div
      v-if="isDraggingOver"
      class="absolute inset-0 bg-[#1877F2]/10 backdrop-blur-[2px] rounded-2xl z-40 flex flex-col items-center justify-center border-2 border-dashed border-[#1877F2] pointer-events-none animate-in fade-in duration-150"
    >
      <div class="bg-white dark:bg-slate-900 px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3 border border-blue-200 dark:border-blue-800">
        <svg class="w-7 h-7 text-[#1877F2] animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Drop your photos & videos to upload</span>
      </div>
    </div>

    <!-- Top Row: Avatar & Rounded Input / Textarea -->
    <div class="flex items-start gap-3">
      <a :href="'/profile/' + (user?.username || 'user')" class="shrink-0 relative group/avatar" title="Your Profile">
        <img
          :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
          alt="Avatar"
          class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-200/80 dark:ring-slate-700 shadow-sm mt-0.5 group-hover/avatar:scale-105 transition-transform"
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
        <div v-else class="space-y-2.5">
          <!-- Header Row: Author Name, Audience Pill & Close Button -->
          <div class="flex items-center justify-between pb-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ user?.name || 'You' }}</span>
              <!-- Audience Selector Dropdown Pill -->
              <div class="relative" ref="audienceDropdownRef">
                <button
                  type="button"
                  @click="showAudienceDropdown = !showAudienceDropdown"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-[#F0F2F5] dark:bg-slate-800 hover:bg-[#E4E6E9] dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-[#E4E7EC] dark:border-slate-700 transition-colors shadow-2xs"
                  aria-haspopup="true"
                  :aria-expanded="showAudienceDropdown"
                >
                  <span>{{ currentAudienceOption.icon }}</span>
                  <span>{{ currentAudienceOption.label }}</span>
                  <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Custom Audience Popover Menu -->
                <div
                  v-if="showAudienceDropdown"
                  class="absolute left-0 mt-1 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-[#E4E7EC] dark:border-slate-800 p-1.5 z-30 animate-in fade-in zoom-in-95 duration-150"
                >
                  <div class="px-2.5 py-1 border-b border-slate-100 dark:border-slate-800 mb-1">
                    <p class="text-[11px] font-bold text-slate-800 dark:text-slate-100">Select Audience</p>
                    <p class="text-[9px] text-slate-400">Who can view this post?</p>
                  </div>
                  <button
                    v-for="opt in audienceOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectAudience(opt.value)"
                    class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-left transition-colors"
                  >
                    <div class="flex items-center gap-2">
                      <span class="text-sm">{{ opt.icon }}</span>
                      <div>
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ opt.label }}</p>
                        <p class="text-[9px] text-slate-400 leading-tight">{{ opt.desc }}</p>
                      </div>
                    </div>
                    <span v-if="audience === opt.value" class="text-[#1877F2] font-bold text-xs">✓</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Collapse Button -->
            <button
              type="button"
              @click="collapseComposer"
              class="w-7 h-7 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors text-xs"
              title="Close composer"
            >
              ✕
            </button>
          </div>

          <!-- Multiline Textarea with Auto-Grow and Character Counter -->
          <div class="relative">
            <textarea
              ref="textareaRef"
              v-model="content"
              @input="adjustTextareaHeight"
              rows="3"
              maxlength="5000"
              :placeholder="'What\'s on your mind, ' + userName + '?'"
              class="w-full bg-[#F0F2F5] dark:bg-slate-800/80 text-sm text-[#172033] dark:text-slate-100 placeholder-slate-400 rounded-2xl p-3.5 pr-12 border-none outline-none focus:ring-2 focus:ring-[#1877F2] resize-none transition-all min-h-[96px] max-h-[320px]"
            ></textarea>

            <!-- Inline Emoji Trigger Button -->
            <button
              type="button"
              @click="toggleEmojiPicker"
              class="absolute bottom-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-full hover:bg-white/80 dark:hover:bg-slate-700 transition-colors"
              title="Insert Emoji"
            >
              <span class="text-base">😀</span>
            </button>
          </div>

          <!-- Character Counter & Language Indicator -->
          <div class="flex items-center justify-between text-[11px] px-1 text-slate-400">
            <span class="flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span>English / বাংলা / Unicode</span>
            </span>
            <span
              :class="{
                'text-amber-600 font-semibold': content.length >= 4500 && content.length < 5000,
                'text-red-600 font-bold': content.length >= 5000,
              }"
            >
              {{ content.length }} / 5,000
            </span>
          </div>

          <!-- Selected Metadata Chips (Feelings, Tags, Location) -->
          <div v-if="selectedFeeling || taggedUsers.length > 0 || selectedLocation" class="flex flex-wrap items-center gap-1.5 pt-0.5">
            <!-- Feeling badge -->
            <div
              v-if="selectedFeeling"
              class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-full text-xs text-amber-800 dark:text-amber-300 font-medium animate-in fade-in"
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
              class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-full text-xs text-[#1877F2] dark:text-blue-300 font-medium animate-in fade-in"
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

            <!-- Location badge -->
            <div
              v-if="selectedLocation"
              class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-full text-xs text-emerald-800 dark:text-emerald-300 font-medium animate-in fade-in"
            >
              <span>📍 in {{ selectedLocation }}</span>
              <button
                type="button"
                @click="selectedLocation = ''"
                class="hover:text-emerald-950 dark:hover:text-emerald-100 ml-1 p-0.5 rounded-full hover:bg-emerald-200/50"
                title="Remove location"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Drag-and-Drop Media Zone & Upload Gallery (Photos & Videos) -->
    <div v-if="mediaFiles.length > 0 || isDropzoneActive" class="mt-3.5">
      <!-- Empty Dropzone State (When user clicked Photo/Video with 0 files yet) -->
      <div
        v-if="mediaFiles.length === 0"
        @click="triggerFileInput"
        class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-[#1877F2] rounded-2xl p-6 flex flex-col items-center justify-center text-center cursor-pointer bg-slate-50/70 dark:bg-slate-800/30 hover:bg-blue-50/30 transition-all group"
      >
        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zm0 2v10h16V7H4zm3 8l3-4 2 2.5 3-4 4 5.5H7zm9-5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Add Photos / Videos</p>
        <p class="text-xs text-slate-400 mt-0.5">or drag and drop files directly here</p>
        <span class="inline-block mt-2 text-[10px] text-slate-400 bg-white dark:bg-slate-800 px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">
          JPG, PNG, WEBP, GIF, MP4, WEBM (up to 100MB)
        </span>
      </div>

      <!-- Uploaded Media Gallery (Facebook-Style Responsive Layout) -->
      <div v-else class="space-y-2">
        <!-- Gallery Top Bar: Media Count & Aspect Ratio Toggle -->
        <div class="flex items-center justify-between text-xs px-1 text-slate-500">
          <span class="font-semibold text-slate-700 dark:text-slate-300">
            {{ mediaFiles.length }} {{ mediaFiles.length === 1 ? 'file' : 'files' }} attached
            <span v-if="isAnyUploading" class="text-[#1877F2] font-normal ml-1">
              (Uploading {{ activeUploadCount }}...)
            </span>
          </span>

          <div class="flex items-center gap-1.5">
            <button
              type="button"
              @click="triggerFileInput"
              v-if="mediaFiles.length < 10"
              class="text-[#1877F2] hover:underline font-semibold flex items-center gap-1"
            >
              <span>+ Add more</span>
            </button>
          </div>
        </div>

        <!-- Dynamic Grid: Single (1), Double (2), Triple (3), or 4+ Grid -->
        <div
          class="gap-2 rounded-2xl overflow-hidden"
          :class="{
            'grid grid-cols-1': mediaFiles.length === 1,
            'grid grid-cols-2': mediaFiles.length === 2,
            'grid grid-cols-2 sm:grid-cols-3': mediaFiles.length >= 3,
          }"
        >
          <div
            v-for="(item, idx) in mediaFiles"
            :key="item.clientId"
            class="relative rounded-xl overflow-hidden group bg-slate-900 border border-slate-200 dark:border-slate-700 transition-all select-none"
            :class="getMediaContainerClass(idx, mediaFiles.length, item.aspectRatio)"
          >
            <!-- 1. Video Player Preview with Controls -->
            <template v-if="item.isVideo">
              <video
                :src="item.url"
                controls
                playsinline
                preload="metadata"
                class="w-full h-full object-cover"
              ></video>
              <div class="absolute top-2 left-2 bg-black/70 backdrop-blur-sm text-white text-[10px] px-2 py-0.5 rounded-full flex items-center gap-1 font-semibold pointer-events-none z-10">
                <svg class="w-3 h-3 text-red-500 fill-current" viewBox="0 0 24 24">
                  <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 2v12h16V6H4zm6 3l6 3-6 3V9z" />
                </svg>
                <span>Video</span>
                <span v-if="item.duration">({{ formatDuration(item.duration) }})</span>
              </div>
            </template>

            <!-- 2. Image Preview -->
            <template v-else>
              <img
                :src="item.url"
                :alt="item.altText || item.name"
                class="w-full h-full transition-transform duration-200 group-hover:scale-[1.02]"
                :class="item.aspectRatio === 'original' ? 'object-contain bg-slate-950' : 'object-cover'"
              />
            </template>

            <!-- Real-Time Upload Progress Overlay -->
            <div
              v-if="item.status === 'uploading'"
              class="absolute inset-0 bg-black/65 backdrop-blur-xs flex flex-col items-center justify-center p-3 text-white z-20"
            >
              <div class="w-10 h-10 rounded-full border-3 border-white/20 border-t-white animate-spin mb-2"></div>
              <p class="text-xs font-bold">{{ item.progress }}%</p>
              <div class="w-3/4 bg-white/20 rounded-full h-1.5 mt-1.5 overflow-hidden">
                <div
                  class="bg-[#1877F2] h-full rounded-full transition-all duration-150"
                  :style="{ width: item.progress + '%' }"
                ></div>
              </div>
              <p class="text-[10px] text-slate-300 mt-1">Uploading...</p>
              <button
                type="button"
                @click="cancelItemUpload(item.clientId)"
                class="mt-2 text-[10px] underline text-slate-300 hover:text-white"
              >
                Cancel
              </button>
            </div>

            <!-- Upload Error Overlay with Retry -->
            <div
              v-else-if="item.status === 'error'"
              class="absolute inset-0 bg-red-950/85 backdrop-blur-xs flex flex-col items-center justify-center p-2 text-white z-20 text-center"
            >
              <svg class="w-6 h-6 text-red-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <p class="text-[11px] font-bold text-red-200 leading-tight">{{ item.errorMessage || 'Upload failed' }}</p>
              <div class="flex items-center gap-2 mt-2">
                <button
                  type="button"
                  @click="retryUpload(item.clientId)"
                  class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-white text-red-900 hover:bg-slate-100 transition-colors"
                >
                  Retry
                </button>
                <button
                  type="button"
                  @click="removeMedia(idx)"
                  class="px-2 py-0.5 rounded-md text-[10px] bg-red-800/80 hover:bg-red-800 text-white transition-colors"
                >
                  Remove
                </button>
              </div>
            </div>

            <!-- Ready Status Badge -->
            <div
              v-else-if="item.status === 'ready'"
              class="absolute top-2 left-2 bg-emerald-600/90 backdrop-blur-sm text-white text-[10px] px-1.5 py-0.5 rounded-full flex items-center gap-1 font-semibold z-10 pointer-events-none"
            >
              <span>✓ Ready</span>
            </div>

            <!-- Hover Controls Overlay (Top Right: Action Buttons) -->
            <div class="absolute top-2 right-2 flex items-center gap-1 z-20">
              <!-- Fullscreen Lightbox Button -->
              <button
                type="button"
                @click="openLightbox(idx)"
                class="w-7 h-7 rounded-full bg-black/60 hover:bg-black/90 text-white flex items-center justify-center transition-all shadow-sm"
                title="Preview full size"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h6m0 0v6m0-6L14 10M9 21H3m0 0v-6m0 6l7-7" />
                </svg>
              </button>

              <!-- Alt Text Accessibility Button (Images only) -->
              <button
                v-if="!item.isVideo"
                type="button"
                @click="openAltTextEditor(item)"
                class="px-1.5 h-7 rounded-full bg-black/60 hover:bg-black/90 text-white text-[10px] font-extrabold flex items-center justify-center transition-all shadow-sm"
                :class="{ 'bg-blue-600 hover:bg-blue-700': Boolean(item.altText) }"
                title="Add Alt Text description"
              >
                ALT
              </button>

              <!-- Remove Media Button -->
              <button
                type="button"
                @click="removeMedia(idx)"
                class="w-7 h-7 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-red-600 transition-colors shadow-sm"
                title="Remove attachment"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Bottom Rearrange & Aspect Ratio Controls (on hover) -->
            <div
              v-if="mediaFiles.length > 1 || !item.isVideo"
              class="absolute bottom-2 inset-x-2 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity z-20 pointer-events-auto"
            >
              <!-- Rearrange Reorder Buttons -->
              <div v-if="mediaFiles.length > 1" class="flex items-center gap-1 bg-black/70 backdrop-blur-sm p-1 rounded-lg">
                <button
                  type="button"
                  @click="moveMedia(idx, -1)"
                  :disabled="idx === 0"
                  class="w-5 h-5 rounded text-white flex items-center justify-center hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent transition-colors text-xs"
                  title="Move left"
                >
                  ←
                </button>
                <span class="text-[10px] text-slate-300 font-bold px-0.5">{{ idx + 1 }}/{{ mediaFiles.length }}</span>
                <button
                  type="button"
                  @click="moveMedia(idx, 1)"
                  :disabled="idx === mediaFiles.length - 1"
                  class="w-5 h-5 rounded text-white flex items-center justify-center hover:bg-white/20 disabled:opacity-30 disabled:hover:bg-transparent transition-colors text-xs"
                  title="Move right"
                >
                  →
                </button>
              </div>

              <!-- Aspect Ratio Toggle for single/grid images -->
              <div v-if="!item.isVideo" class="bg-black/70 backdrop-blur-sm p-1 rounded-lg flex items-center gap-1 text-[10px] text-white">
                <button
                  type="button"
                  @click="cycleAspectRatio(item)"
                  class="px-1.5 py-0.5 rounded hover:bg-white/20 transition-colors capitalize font-semibold"
                  title="Change aspect ratio"
                >
                  {{ item.aspectRatio }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Banner -->
    <div
      v-if="errorMessage"
      class="mt-3 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 p-2.5 rounded-xl flex items-center justify-between gap-2 border border-red-200 dark:border-red-900/50 animate-in fade-in"
    >
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

    <!-- Location Search & Selection Drawer -->
    <div
      v-if="showLocationPicker"
      class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/90 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Add Location</span>
        <button
          type="button"
          @click="showLocationPicker = false"
          class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
        >
          ✕
        </button>
      </div>

      <!-- Search & Custom Input -->
      <div class="flex gap-2 mb-2.5">
        <input
          v-model="locationSearchQuery"
          @keydown.enter.prevent="useCustomLocation"
          type="text"
          placeholder="Enter city or place (e.g. Dhaka, Chittagong, Sylhet, London)..."
          class="w-full bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2]"
        />
        <button
          v-if="locationSearchQuery.trim()"
          type="button"
          @click="useCustomLocation"
          class="px-3 py-2 rounded-xl text-xs font-bold bg-[#1877F2] text-white hover:bg-[#1666D8] transition-colors shrink-0"
        >
          Use
        </button>
      </div>

      <!-- Detect GPS Location Button -->
      <button
        type="button"
        @click="detectCurrentLocation"
        :disabled="isDetectingLocation"
        class="w-full mb-2.5 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-[#1877F2] flex items-center justify-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-colors"
      >
        <svg v-if="!isDetectingLocation" class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg v-else class="animate-spin h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ isDetectingLocation ? 'Detecting current location...' : 'Use Current Location' }}</span>
      </button>

      <!-- Common Locations List -->
      <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
        <button
          v-for="loc in filteredLocations"
          :key="loc"
          type="button"
          @click="selectLocation(loc)"
          class="w-full flex items-center gap-2 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-[#1877F2] hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-all text-left"
        >
          <span>📍</span>
          <span>{{ loc }}</span>
        </button>
      </div>
    </div>

    <!-- Emoji Picker Drawer -->
    <div
      v-if="showEmojiPicker"
      class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/90 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-1 overflow-x-auto py-1">
          <button
            v-for="cat in emojiCategories"
            :key="cat.id"
            type="button"
            @click="activeEmojiCategory = cat.id"
            :class="activeEmojiCategory === cat.id ? 'bg-[#1877F2] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100'"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors shrink-0"
          >
            <span>{{ cat.icon }}</span>
            <span class="ml-1 hidden sm:inline">{{ cat.name }}</span>
          </button>
        </div>
        <button
          type="button"
          @click="showEmojiPicker = false"
          class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 shrink-0 ml-2"
        >
          ✕
        </button>
      </div>

      <!-- Emoji Grid -->
      <div class="grid grid-cols-8 sm:grid-cols-10 gap-1.5 max-h-44 overflow-y-auto p-1 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
        <button
          v-for="emoji in currentCategoryEmojis"
          :key="emoji"
          type="button"
          @click="insertEmoji(emoji)"
          class="w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-lg hover:scale-125 transition-transform"
        >
          {{ emoji }}
        </button>
      </div>
    </div>

    <!-- Alt Text Accessibility Modal / Drawer -->
    <div
      v-if="editingAltItem"
      class="mt-3 p-3.5 bg-slate-50 dark:bg-slate-800/90 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Accessibility Alt Text</span>
        <button
          type="button"
          @click="editingAltItem = null"
          class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
        >
          ✕
        </button>
      </div>
      <p class="text-[11px] text-slate-500 mb-2">
        Provide a concise description of this image for people with visual impairments who use screen readers.
      </p>
      <div class="flex gap-3">
        <img :src="editingAltItem.url" class="w-16 h-16 rounded-xl object-cover shrink-0 border border-slate-200" alt="Preview" />
        <div class="flex-1 space-y-2">
          <textarea
            v-model="editingAltItem.altText"
            rows="2"
            maxlength="200"
            placeholder="e.g. Friends enjoying coffee together at a sunny cafe patio in Dhaka..."
            class="w-full bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 rounded-xl p-2.5 border border-slate-200 dark:border-slate-700 outline-none focus:ring-2 focus:ring-[#1877F2] resize-none"
          ></textarea>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">{{ (editingAltItem.altText || '').length }}/200</span>
            <button
              type="button"
              @click="editingAltItem = null"
              class="px-3 py-1 rounded-lg text-xs font-bold bg-[#1877F2] text-white hover:bg-[#1666D8] transition-colors"
            >
              Save Alt Text
            </button>
          </div>
        </div>
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
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-bold text-[#E41E3F] hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer group"
      >
        <svg class="w-4 h-4 text-[#E41E3F] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z" />
        </svg>
        <span>Live Video</span>
      </button>

      <!-- 2. Reel -->
      <button
        type="button"
        @click="triggerReel"
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-bold text-[#9333EA] hover:bg-purple-50 dark:hover:bg-purple-950/40 transition-colors cursor-pointer group"
      >
        <svg class="w-4 h-4 text-[#9333EA] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z" />
        </svg>
        <span>Reel</span>
      </button>

      <!-- 3. Photo/Video (Clickable & expands dropzone/file picker) -->
      <button
        type="button"
        @click="clickPhotoVideoAction"
        class="cursor-pointer inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
      >
        <svg class="w-4 h-4 text-[#12B76A] fill-current group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24">
          <path d="M4 5h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zm0 2v10h16V7H4zm3 8l3-4 2 2.5 3-4 4 5.5H7zm9-5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
        </svg>
        <span>Photo/Video</span>
      </button>

      <!-- 4. Feeling/Activity -->
      <button
        type="button"
        @click="toggleFeelingPicker"
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
      >
        <span class="text-sm group-hover:scale-110 transition-transform">😊</span>
        <span class="hidden sm:inline">Feeling/Activity</span>
      </button>

      <!-- 5. Tag Friends -->
      <button
        type="button"
        @click="toggleTagPicker"
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
      >
        <svg class="w-4 h-4 text-[#1877F2] group-hover:scale-110 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span class="hidden sm:inline">Tag Friends</span>
      </button>

      <!-- 6. Location -->
      <button
        type="button"
        @click="toggleLocationPicker"
        class="inline-flex items-center gap-1.5 px-2 sm:px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold text-[#344054] dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group"
        title="Check in / Add location"
      >
        <svg class="w-4 h-4 text-emerald-600 group-hover:scale-110 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="hidden md:inline">Location</span>
      </button>
    </div>

    <!-- Hidden Native File Input (Accepts Images and Videos) -->
    <input
      ref="fileInputRef"
      type="file"
      accept="image/jpeg,image/png,image/webp,image/gif,image/avif,video/mp4,video/webm,video/quicktime,video/mov"
      multiple
      @change="handleFileChange"
      class="hidden"
    />

    <!-- Bottom Row: Audience Selector (when collapsed) & Blue Post Button -->
    <div class="mt-2.5 pt-2 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <!-- Status indicator when uploading in background -->
        <div v-if="isAnyUploading" class="flex items-center gap-1.5 text-xs text-[#1877F2] font-semibold animate-pulse">
          <svg class="animate-spin h-3.5 w-3.5 text-[#1877F2]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Uploading media ({{ totalUploadProgress }}%)...</span>
        </div>
      </div>

      <!-- Blue Post Button (matches screenshot) -->
      <button
        type="button"
        @click="submitPost"
        :disabled="isSubmitting || isPostEmpty || hasUploadErrors"
        class="px-6 py-2 rounded-xl text-xs sm:text-sm font-bold bg-[#1877F2] hover:bg-[#1666D8] text-white disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all flex items-center gap-1.5 ml-auto"
      >
        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ isSubmitting ? 'Publishing...' : 'Post' }}</span>
      </button>
    </div>

    <!-- Fullscreen Media Lightbox Modal -->
    <div
      v-if="activeLightboxIndex !== null"
      class="fixed inset-0 bg-black/90 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-in fade-in duration-150"
      @click.self="closeLightbox"
    >
      <button
        type="button"
        @click="closeLightbox"
        class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition-colors text-lg z-50"
        title="Close preview"
      >
        ✕
      </button>

      <!-- Previous Button -->
      <button
        v-if="mediaFiles.length > 1"
        type="button"
        @click="navigateLightbox(-1)"
        class="absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition-colors text-xl z-50"
        title="Previous"
      >
        ‹
      </button>

      <!-- Lightbox Main Content -->
      <div class="max-w-4xl max-h-[85vh] flex flex-col items-center justify-center relative select-none">
        <template v-if="currentLightboxItem?.isVideo">
          <video
            :src="currentLightboxItem.url"
            controls
            autoplay
            class="max-w-full max-h-[75vh] rounded-2xl shadow-2xl"
          ></video>
        </template>
        <template v-else-if="currentLightboxItem">
          <img
            :src="currentLightboxItem.url"
            :alt="currentLightboxItem.altText || currentLightboxItem.name"
            class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-2xl"
          />
        </template>

        <!-- Lightbox Caption & Meta Info -->
        <div class="mt-3 text-center text-white">
          <p class="text-sm font-semibold">{{ currentLightboxItem?.name }}</p>
          <p v-if="currentLightboxItem?.altText" class="text-xs text-slate-300 italic mt-0.5">
            "{{ currentLightboxItem.altText }}"
          </p>
          <p class="text-xs text-slate-400 mt-1">
            {{ (activeLightboxIndex ?? 0) + 1 }} of {{ mediaFiles.length }}
          </p>
        </div>
      </div>

      <!-- Next Button -->
      <button
        v-if="mediaFiles.length > 1"
        type="button"
        @click="navigateLightbox(1)"
        class="absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition-colors text-xl z-50"
        title="Next"
      >
        ›
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import type { User } from '../types';

export interface MediaUploadItem {
  clientId: string;
  mediaId?: number;
  file: File;
  name: string;
  size: number;
  url: string;
  isVideo: boolean;
  duration?: number;
  aspectRatio: 'original' | 'square' | 'wide' | 'portrait';
  altText: string;
  status: 'pending' | 'uploading' | 'ready' | 'error';
  progress: number;
  errorMessage?: string;
  xhr?: XMLHttpRequest;
}

const props = withDefaults(
  defineProps<{
    user: User | null;
    groupId?: number | null;
    pageId?: number | null;
  }>(),
  {
    groupId: null,
    pageId: null,
  }
);

const emit = defineEmits<{
  (e: 'post-created', post: any): void;
  (e: 'open-live'): void;
  (e: 'open-reel-creator'): void;
}>();

// Refs & Reactive State
const isExpanded = ref(false);
const isDropzoneActive = ref(false);
const isDraggingOver = ref(false);
let dragCounter = 0;

const textareaRef = ref<HTMLTextAreaElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const audienceDropdownRef = ref<HTMLDivElement | null>(null);

const content = ref('');
const audience = ref<'public' | 'friends' | 'only_me'>('public');
const showAudienceDropdown = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');

// Media list
const mediaFiles = ref<MediaUploadItem[]>([]);

// Lightbox modal index
const activeLightboxIndex = ref<number | null>(null);
const currentLightboxItem = computed(() => {
  if (activeLightboxIndex.value === null) return null;
  return mediaFiles.value[activeLightboxIndex.value] || null;
});

// Alt text editor item
const editingAltItem = ref<MediaUploadItem | null>(null);

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

// Check if any file is actively uploading
const isAnyUploading = computed(() => {
  return mediaFiles.value.some((m) => m.status === 'uploading' || m.status === 'pending');
});

const activeUploadCount = computed(() => {
  return mediaFiles.value.filter((m) => m.status === 'uploading' || m.status === 'pending').length;
});

const totalUploadProgress = computed(() => {
  if (mediaFiles.value.length === 0) return 100;
  const sum = mediaFiles.value.reduce((acc, m) => acc + (m.status === 'ready' ? 100 : m.progress), 0);
  return Math.round(sum / mediaFiles.value.length);
});

const hasUploadErrors = computed(() => {
  return mediaFiles.value.some((m) => m.status === 'error');
});

// Check if the post has any meaningful content to enable submit
const isPostEmpty = computed(() => {
  return (
    !content.value.trim() &&
    mediaFiles.value.length === 0 &&
    !selectedFeeling.value &&
    taggedUsers.value.length === 0 &&
    !selectedLocation.value
  );
});

// Auto-grow textarea
const adjustTextareaHeight = () => {
  if (!textareaRef.value) return;
  textareaRef.value.style.height = 'auto';
  textareaRef.value.style.height = Math.min(textareaRef.value.scrollHeight, 320) + 'px';
};

// Expand / Collapse composer
const expandComposer = () => {
  isExpanded.value = true;
  nextTick(() => {
    textareaRef.value?.focus();
    adjustTextareaHeight();
  });
};

const collapseComposer = () => {
  if (isPostEmpty.value) {
    isExpanded.value = false;
    isDropzoneActive.value = false;
    showFeelingPicker.value = false;
    showTagPicker.value = false;
    showLocationPicker.value = false;
    showEmojiPicker.value = false;
  } else {
    // If user has unsaved draft content, confirm before clearing or keep expanded
    isExpanded.value = false;
  }
};

// Actions triggers
const triggerLive = () => {
  emit('open-live');
};

const triggerReel = () => {
  emit('open-reel-creator');
};

const clickPhotoVideoAction = () => {
  expandComposer();
  isDropzoneActive.value = true;
  triggerFileInput();
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
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
  showLocationPicker.value = false;
  showEmojiPicker.value = false;
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
  showLocationPicker.value = false;
  showEmojiPicker.value = false;
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
      headers: { Accept: 'application/json' },
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

// Location State
const showLocationPicker = ref(false);
const selectedLocation = ref('');
const locationSearchQuery = ref('');
const isDetectingLocation = ref(false);

const popularLocations = [
  'Dhaka, Bangladesh',
  'Chittagong, Bangladesh',
  'Sylhet, Bangladesh',
  'Rajshahi, Bangladesh',
  'Khulna, Bangladesh',
  'Cox\'s Bazar, Bangladesh',
  'London, United Kingdom',
  'New York, USA',
  'Toronto, Canada',
  'Dubai, United Arab Emirates',
  'Tokyo, Japan',
  'Remote / Working From Home',
];

const filteredLocations = computed(() => {
  const q = locationSearchQuery.value.trim().toLowerCase();
  if (!q) return popularLocations;
  return popularLocations.filter((loc) => loc.toLowerCase().includes(q));
});

const toggleLocationPicker = () => {
  expandComposer();
  showLocationPicker.value = !showLocationPicker.value;
  showFeelingPicker.value = false;
  showTagPicker.value = false;
  showEmojiPicker.value = false;
  showAudienceDropdown.value = false;
};

const selectLocation = (loc: string) => {
  selectedLocation.value = loc;
  showLocationPicker.value = false;
  locationSearchQuery.value = '';
};

const useCustomLocation = () => {
  if (locationSearchQuery.value.trim()) {
    selectLocation(locationSearchQuery.value.trim());
  }
};

const detectCurrentLocation = () => {
  if (!navigator.geolocation) {
    errorMessage.value = 'Geolocation is not supported by your browser.';
    return;
  }
  isDetectingLocation.value = true;
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      isDetectingLocation.value = false;
      const lat = pos.coords.latitude.toFixed(2);
      const lng = pos.coords.longitude.toFixed(2);
      selectLocation(`Location (${lat}, ${lng})`);
    },
    (err) => {
      isDetectingLocation.value = false;
      errorMessage.value = `Location access denied or unavailable (${err.message}).`;
    },
    { timeout: 10000 }
  );
};

// Emoji Picker State
const showEmojiPicker = ref(false);
const activeEmojiCategory = ref('smileys');

const emojiCategories = [
  { id: 'smileys', name: 'Smileys', icon: '😊' },
  { id: 'gestures', name: 'Gestures', icon: '👍' },
  { id: 'hearts', name: 'Hearts', icon: '❤️' },
  { id: 'food', name: 'Food', icon: '🍕' },
  { id: 'travel', name: 'Travel', icon: '✈️' },
  { id: 'activities', name: 'Fun', icon: '🎉' },
];

const emojiDictionary: Record<string, string[]> = {
  smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😋', '😜', '🤪', '😎', '🤩', '🥳', '😏', '🧐', '🤓'],
  gestures: ['👍', '👎', '👏', '🙌', '🤝', '👊', '✌️', '🤟', '🤘', '👌', '🤌', '👈', '👉', '👆', '👇', '✋', '👋', '🙏', '💪', '🫡'],
  hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟'],
  food: ['🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥗', '🍝', '🍜', '🍣', '🍱', '🍦', '🍩', '🍪', '🎂', '☕', '🍵', '🧃', '🥤'],
  travel: ['✈️', '🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚲', '🛵', '🏍️', '🚂', '🚁', '🏖️', '🏝️'],
  activities: ['🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉', '🎱', '🎮', '🎯', '🎸', '🎨'],
};

const currentCategoryEmojis = computed(() => {
  return emojiDictionary[activeEmojiCategory.value] || emojiDictionary.smileys;
});

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value;
  showFeelingPicker.value = false;
  showTagPicker.value = false;
  showLocationPicker.value = false;
  showAudienceDropdown.value = false;
};

const insertEmoji = (emoji: string) => {
  if (!textareaRef.value) {
    content.value += emoji;
    return;
  }
  const start = textareaRef.value.selectionStart;
  const end = textareaRef.value.selectionEnd;
  const text = content.value;
  content.value = text.substring(0, start) + emoji + text.substring(end);
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.selectionStart = textareaRef.value.selectionEnd = start + emoji.length;
      textareaRef.value.focus();
      adjustTextareaHeight();
    }
  });
};

// Drag and Drop Event Listeners
const handleDragEnter = () => {
  dragCounter++;
  isDraggingOver.value = true;
};

const handleDragOver = (e: DragEvent) => {
  isDraggingOver.value = true;
};

const handleDragLeave = () => {
  dragCounter--;
  if (dragCounter <= 0) {
    dragCounter = 0;
    isDraggingOver.value = false;
  }
};

const handleDrop = (e: DragEvent) => {
  dragCounter = 0;
  isDraggingOver.value = false;
  if (!e.dataTransfer || !e.dataTransfer.files || e.dataTransfer.files.length === 0) return;
  processSelectedFiles(e.dataTransfer.files);
};

// File Selection Handler
const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!target.files || target.files.length === 0) return;
  processSelectedFiles(target.files);
  // Clear input value so same file can be re-selected if removed
  target.value = '';
};

// Validate & Process Files (Single & Multiple)
const processSelectedFiles = (fileList: FileList) => {
  expandComposer();
  isDropzoneActive.value = true;
  errorMessage.value = '';

  const maxAllowedTotal = 10;
  const availableSlots = maxAllowedTotal - mediaFiles.value.length;

  if (availableSlots <= 0) {
    errorMessage.value = `You can attach up to ${maxAllowedTotal} photos and videos per post.`;
    return;
  }

  const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
  const allowedVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'];

  const countToTake = Math.min(fileList.length, availableSlots);

  for (let i = 0; i < countToTake; i++) {
    const file = fileList[i];
    const isImage = allowedImageTypes.includes(file.type);
    const isVideo = allowedVideoTypes.includes(file.type) || file.type.startsWith('video/');

    if (!isImage && !isVideo) {
      errorMessage.value = `File "${file.name}" has an unsupported format. Please select valid photos (JPG, PNG, WEBP, GIF) or videos (MP4, WEBM).`;
      continue;
    }

    // Limit check: 100MB max
    const maxBytes = 100 * 1024 * 1024;
    if (file.size > maxBytes) {
      errorMessage.value = `File "${file.name}" exceeds the 100MB limit.`;
      continue;
    }

    const clientId = 'media_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const url = URL.createObjectURL(file);

    const newItem: MediaUploadItem = {
      clientId,
      file,
      name: file.name,
      size: file.size,
      url,
      isVideo,
      aspectRatio: 'original',
      altText: '',
      status: 'uploading',
      progress: 0,
    };

    // If video, inspect duration via temporary video element
    if (isVideo) {
      const tempVideo = document.createElement('video');
      tempVideo.src = url;
      tempVideo.preload = 'metadata';
      tempVideo.onloadedmetadata = () => {
        newItem.duration = tempVideo.duration;
      };
    }

    mediaFiles.value.push(newItem);

    // Immediately kick off real-time asynchronous background upload!
    uploadMediaItem(newItem);
  }

  if (fileList.length > availableSlots) {
    errorMessage.value = `Only ${countToTake} files were added. Maximum limit is 10 media attachments.`;
  }
};

// Real-Time Asynchronous Media Upload with genuine XMLHttpRequest progress
const uploadMediaItem = (item: MediaUploadItem) => {
  item.status = 'uploading';
  item.progress = 0;
  item.errorMessage = undefined;

  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
  const formData = new FormData();
  formData.append('file', item.file);
  formData.append('collection', item.isVideo ? 'video' : 'post');

  const xhr = new XMLHttpRequest();
  item.xhr = xhr;

  xhr.upload.onprogress = (event) => {
    if (event.lengthComputable) {
      item.progress = Math.round((event.loaded / event.total) * 100);
    }
  };

  xhr.onload = () => {
    item.xhr = undefined;
    if (xhr.status >= 200 && xhr.status < 300) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.data?.id || response.data?.media_id) {
          item.mediaId = response.data.id || response.data.media_id;
          item.status = 'ready';
          item.progress = 100;
        } else {
          item.status = 'error';
          item.errorMessage = response.message || 'Upload server error';
        }
      } catch (err) {
        item.status = 'error';
        item.errorMessage = 'Failed to parse upload response.';
      }
    } else {
      try {
        const response = JSON.parse(xhr.responseText);
        item.errorMessage = response.message || `Upload failed with code ${xhr.status}.`;
      } catch {
        item.errorMessage = `Upload failed with HTTP ${xhr.status}.`;
      }
      item.status = 'error';
    }
  };

  xhr.onerror = () => {
    item.xhr = undefined;
    item.status = 'error';
    item.errorMessage = 'Network connection lost during upload. Click Retry to re-upload.';
  };

  xhr.onabort = () => {
    item.xhr = undefined;
  };

  xhr.open('POST', '/api/v1/media/upload');
  xhr.setRequestHeader('Accept', 'application/json');
  if (csrfToken) {
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
  }
  xhr.send(formData);
};

// Retry failed upload
const retryUpload = (clientId: string) => {
  const item = mediaFiles.value.find((m) => m.clientId === clientId);
  if (item) {
    uploadMediaItem(item);
  }
};

// Cancel single item upload
const cancelItemUpload = (clientId: string) => {
  const idx = mediaFiles.value.findIndex((m) => m.clientId === clientId);
  if (idx > -1) {
    removeMedia(idx);
  }
};

// Remove Media Item (with server cleanup if already uploaded)
const removeMedia = async (index: number) => {
  const item = mediaFiles.value[index];
  if (!item) return;

  // Abort active upload if in-flight
  if (item.xhr) {
    item.xhr.abort();
  }

  // Revoke object URL
  if (item.url) {
    URL.revokeObjectURL(item.url);
  }

  // If already uploaded to server, clean up in background via DELETE /api/v1/media/{id}
  if (item.mediaId) {
    try {
      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
      fetch(`/api/v1/media/${item.mediaId}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      }).catch((e) => console.warn('Could not delete unattached media on server:', e));
    } catch (e) {
      // Ignore cleanup error
    }
  }

  mediaFiles.value.splice(index, 1);
  if (mediaFiles.value.length === 0) {
    isDropzoneActive.value = false;
  }
};

// Move Media Left/Right (Rearrange Order)
const moveMedia = (index: number, direction: -1 | 1) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= mediaFiles.value.length) return;
  const temp = mediaFiles.value[index];
  mediaFiles.value[index] = mediaFiles.value[newIndex];
  mediaFiles.value[newIndex] = temp;
};

// Cycle Aspect Ratio for Images
const cycleAspectRatio = (item: MediaUploadItem) => {
  const ratios: Array<'original' | 'square' | 'wide' | 'portrait'> = ['original', 'square', 'wide', 'portrait'];
  const curIdx = ratios.indexOf(item.aspectRatio);
  item.aspectRatio = ratios[(curIdx + 1) % ratios.length];
};

// Alt Text Accessibility Editor
const openAltTextEditor = (item: MediaUploadItem) => {
  editingAltItem.value = item;
};

// Lightbox Navigation
const openLightbox = (index: number) => {
  activeLightboxIndex.value = index;
};

const closeLightbox = () => {
  activeLightboxIndex.value = null;
};

const navigateLightbox = (direction: -1 | 1) => {
  if (activeLightboxIndex.value === null) return;
  const newIdx = activeLightboxIndex.value + direction;
  if (newIdx >= 0 && newIdx < mediaFiles.value.length) {
    activeLightboxIndex.value = newIdx;
  } else if (newIdx < 0) {
    activeLightboxIndex.value = mediaFiles.value.length - 1;
  } else {
    activeLightboxIndex.value = 0;
  }
};

// Keydown listener for Lightbox (ESC, Left, Right)
const handleGlobalKeydown = (e: KeyboardEvent) => {
  if (activeLightboxIndex.value !== null) {
    if (e.key === 'Escape') closeLightbox();
    else if (e.key === 'ArrowLeft') navigateLightbox(-1);
    else if (e.key === 'ArrowRight') navigateLightbox(1);
  }
};

// Helper: Format video duration (seconds -> mm:ss)
const formatDuration = (sec: number): string => {
  const m = Math.floor(sec / 60);
  const s = Math.floor(sec % 60);
  return `${m}:${s < 10 ? '0' : ''}${s}`;
};

// Helper: Grid aspect classes based on item count and aspect ratio setting
const getMediaContainerClass = (idx: number, total: number, ratio: string): string => {
  if (total === 1) {
    if (ratio === 'square') return 'aspect-square max-h-[500px]';
    if (ratio === 'wide') return 'aspect-video max-h-[500px]';
    if (ratio === 'portrait') return 'aspect-[4/5] max-h-[550px]';
    return 'max-h-[550px] min-h-[260px]';
  }
  if (total === 2) {
    return 'aspect-[4/3]';
  }
  if (total === 3) {
    return idx === 0 ? 'col-span-2 aspect-video sm:col-span-1 sm:aspect-square' : 'aspect-square';
  }
  return 'aspect-square';
};

// Close popovers on click outside
const handleClickOutside = (e: MouseEvent) => {
  if (audienceDropdownRef.value && !audienceDropdownRef.value.contains(e.target as Node)) {
    showAudienceDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('keydown', handleGlobalKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('keydown', handleGlobalKeydown);

  // Abort any in-flight uploads and revoke object URLs
  mediaFiles.value.forEach((m) => {
    if (m.xhr) m.xhr.abort();
    if (m.url) URL.revokeObjectURL(m.url);
  });
});

// Submit Post
const submitPost = async () => {
  if (isPostEmpty.value || isSubmitting.value) return;

  // 1. Check if any uploads failed
  if (hasUploadErrors.value) {
    errorMessage.value = 'Please retry or remove failed media uploads before publishing.';
    return;
  }

  // 2. Wait for any active uploads to finish
  if (isAnyUploading.value) {
    errorMessage.value = 'Please wait for your media files to finish uploading...';
    // Poll or wait until all uploads are resolved
    let waitCount = 0;
    while (isAnyUploading.value && waitCount < 60) {
      await new Promise((r) => setTimeout(r, 500));
      waitCount++;
    }
    if (isAnyUploading.value) {
      errorMessage.value = 'Media upload is taking too long. Please check your network connection.';
      return;
    }
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';

    // Collect ready media IDs in user-arranged order
    const orderedMediaIds = mediaFiles.value
      .filter((m) => m.status === 'ready' && m.mediaId)
      .map((m) => m.mediaId as number);

    // Build alt texts and aspect ratios metadata
    const altTextsMap: Record<number, string> = {};
    const aspectRatiosMap: Record<number, string> = {};

    mediaFiles.value.forEach((m) => {
      if (m.mediaId) {
        if (m.altText) altTextsMap[m.mediaId] = m.altText;
        if (m.aspectRatio) aspectRatiosMap[m.mediaId] = m.aspectRatio;
      }
    });

    const mediaMeta = {
      order: orderedMediaIds,
      alt_texts: altTextsMap,
      aspect_ratios: aspectRatiosMap,
    };

    const postPayload: Record<string, any> = {
      content: content.value.trim() || undefined,
      audience: audience.value,
      type: orderedMediaIds.length > 0 ? 'media' : 'text',
      media_ids: orderedMediaIds.length > 0 ? orderedMediaIds : undefined,
      media_meta: orderedMediaIds.length > 0 ? mediaMeta : undefined,
      location: selectedLocation.value || undefined,
      feeling_activity: selectedFeeling.value || undefined,
      tagged_user_ids: taggedUsers.value.length > 0 ? taggedUsers.value.map((u) => u.id) : undefined,
      group_id: props.groupId || undefined,
      page_id: props.pageId || undefined,
    };

    const res = await fetch('/api/v1/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(postPayload),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      // Emit real-time post creation event to Feed/Profile
      emit('post-created', data.data);

      // Clean up local object URLs
      mediaFiles.value.forEach((m) => {
        if (m.url) URL.revokeObjectURL(m.url);
      });

      // Reset state cleanly
      content.value = '';
      mediaFiles.value = [];
      selectedFeeling.value = '';
      selectedLocation.value = '';
      taggedUsers.value = [];
      showFeelingPicker.value = false;
      showTagPicker.value = false;
      showLocationPicker.value = false;
      showEmojiPicker.value = false;
      showAudienceDropdown.value = false;
      isDropzoneActive.value = false;
      isExpanded.value = false;

      if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
      }
    } else {
      errorMessage.value = data.message || 'Failed to publish post. Please check your connection and inputs.';
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'Network error occurred while creating post.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>
