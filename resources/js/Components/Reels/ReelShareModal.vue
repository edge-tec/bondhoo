<template>
  <Teleport to="body">
    <div
      v-if="isOpen && reel"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-md animate-fade-in"
      @click.self="closeModal"
    >
      <div
        class="w-full sm:max-w-lg bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] transition-all transform animate-slide-up"
      >
        <!-- 1. Header with Close Button -->
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Share Reel</h3>
              <p class="text-xs text-slate-500">Spread creativity with your network</p>
            </div>
          </div>
          <button
            type="button"
            @click="closeModal"
            class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            title="Close"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- 2. Navigation Tabs -->
        <div class="flex items-center px-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50 gap-1 overflow-x-auto no-scrollbar">
          <button
            type="button"
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'px-3.5 py-3 text-xs font-bold transition-all relative shrink-0 flex items-center gap-1.5 cursor-pointer',
              activeTab === tab.id
                ? 'text-purple-600 dark:text-purple-400'
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            <span>{{ tab.label }}</span>
            <div
              v-if="activeTab === tab.id"
              class="absolute bottom-0 inset-x-2 h-0.5 bg-gradient-to-r from-purple-600 to-pink-500 rounded-full"
            ></div>
          </button>
        </div>

        <!-- 3. Tab Body Container (Scrollable) -->
        <div class="p-5 overflow-y-auto flex-1 min-h-[260px]">
          <!-- TAB 1: Send in Direct Message -->
          <div v-if="activeTab === 'message'" class="space-y-4">
            <!-- Search Recipients Input -->
            <div class="relative">
              <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                v-model="searchQuery"
                @input="handleSearchInput"
                placeholder="Search friends by name or username..."
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <!-- Selected Recipients Pills -->
            <div v-if="selectedRecipients.length > 0" class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sending to:</span>
              <div
                v-for="rec in selectedRecipients"
                :key="rec.id"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-purple-50 dark:bg-purple-950/70 border border-purple-200 dark:border-purple-800 text-xs text-purple-700 dark:text-purple-300 font-semibold"
              >
                <span>{{ rec.name }}</span>
                <button
                  type="button"
                  @click="toggleRecipient(rec)"
                  class="w-3.5 h-3.5 rounded-full hover:bg-purple-200 dark:hover:bg-purple-800 flex items-center justify-center text-purple-600 dark:text-purple-400"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Recipients List -->
            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
              <div v-if="isLoadingRecipients" class="py-6 text-center text-xs text-slate-400">
                <div class="inline-block w-5 h-5 border-2 border-purple-600 border-t-transparent rounded-full animate-spin mb-1"></div>
                <div>Searching users...</div>
              </div>

              <div
                v-else-if="filteredRecipients.length === 0"
                class="py-6 text-center text-xs text-slate-400"
              >
                No matching friends or users found.
              </div>

              <div
                v-for="user in filteredRecipients"
                :key="user.id"
                @click="toggleRecipient(user)"
                :class="[
                  'p-2.5 rounded-xl border transition-all flex items-center justify-between cursor-pointer',
                  isRecipientSelected(user.id)
                    ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/30 ring-1 ring-purple-500'
                    : 'border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <img
                    :src="user.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                    class="w-9 h-9 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
                    alt="Avatar"
                  />
                  <div class="truncate">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ user.name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">@{{ user.username }}</p>
                  </div>
                </div>

                <div
                  :class="[
                    'w-5 h-5 rounded-full flex items-center justify-center border transition-all',
                    isRecipientSelected(user.id)
                      ? 'bg-purple-600 border-purple-600 text-white'
                      : 'border-slate-300 dark:border-slate-600'
                  ]"
                >
                  <svg v-if="isRecipientSelected(user.id)" class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Optional message to accompany reel -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Optional Message
              </label>
              <input
                type="text"
                v-model="directMessageNote"
                placeholder="Say something about this Reel (optional)..."
                maxlength="500"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>

            <!-- Submit Direct Message Button -->
            <button
              type="button"
              @click="submitDirectMessage"
              :disabled="selectedRecipients.length === 0 || isSubmitting"
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-500 hover:to-indigo-500 disabled:opacity-50 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
            >
              <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              <span>{{ isSubmitting ? 'Sending...' : `Send to ${selectedRecipients.length || 0} ${selectedRecipients.length === 1 ? 'friend' : 'friends'}` }}</span>
            </button>
          </div>

          <!-- TAB 2: Share to Profile / Wall -->
          <div v-else-if="activeTab === 'profile'" class="space-y-4">
            <div class="flex items-center gap-3">
              <img
                :src="currentUser?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                class="w-10 h-10 rounded-full object-cover ring-2 ring-purple-500/20"
                alt="My Avatar"
              />
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ currentUser?.name || 'You' }}</p>
                <div class="flex items-center gap-1 text-[11px] text-slate-400">
                  <span>🌐 Public Timeline</span>
                </div>
              </div>
            </div>

            <textarea
              v-model="profileCaption"
              rows="3"
              placeholder="What are your thoughts on this Reel? (optional)..."
              class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
            ></textarea>

            <!-- Embedded Reel Card Preview -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-2.5 bg-slate-50 dark:bg-slate-800/40 flex items-center gap-3">
              <div class="relative w-14 h-20 rounded-lg overflow-hidden bg-slate-950 shrink-0">
                <img
                  v-if="reel.cover_url || reel.thumbnail_url"
                  :src="reel.cover_url || reel.thumbnail_url"
                  class="w-full h-full object-cover"
                  alt="Thumb"
                />
                <div v-else class="w-full h-full flex items-center justify-center bg-slate-800 text-white/40">
                  <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
                <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                  <svg class="w-5 h-5 text-white fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
              </div>
              <div class="truncate flex-1">
                <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ reel.user?.name }}'s Reel</p>
                <p v-if="reel.caption" class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">
                  {{ reel.caption }}
                </p>
                <p class="text-[10px] text-purple-600 dark:text-purple-400 mt-1">🎵 {{ reel.audio_title || 'Original Audio' }}</p>
              </div>
            </div>

            <!-- Submit Profile Share -->
            <button
              type="button"
              @click="submitProfileShare"
              :disabled="isSubmitting"
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-[#1877F2] hover:bg-[#1666D8] text-white disabled:opacity-50 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
            >
              <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span>{{ isSubmitting ? 'Sharing...' : 'Share to My Profile' }}</span>
            </button>
          </div>

          <!-- TAB 3: Share to Group -->
          <div v-else-if="activeTab === 'group'" class="space-y-4">
            <div v-if="groups.length === 0" class="py-8 text-center text-xs text-slate-400">
              You haven't joined any groups yet. Join a community group to share Reels!
            </div>

            <template v-else>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Select Destination Group
                </label>
                <select
                  v-model="selectedGroupId"
                  class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option :value="null">-- Choose a Group --</option>
                  <option v-for="grp in groups" :key="grp.id" :value="grp.id">
                    👥 {{ grp.name }} ({{ grp.privacy }})
                  </option>
                </select>
              </div>

              <textarea
                v-model="groupCaption"
                rows="2"
                placeholder="Say something to the group (optional)..."
                class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
              ></textarea>

              <button
                type="button"
                @click="submitGroupShare"
                :disabled="!selectedGroupId || isSubmitting"
                class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-emerald-600 hover:bg-emerald-500 text-white disabled:opacity-50 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
              >
                <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <span>{{ isSubmitting ? 'Posting...' : 'Post to Group' }}</span>
              </button>
            </template>
          </div>

          <!-- TAB 4: Share to Page -->
          <div v-else-if="activeTab === 'page'" class="space-y-4">
            <div v-if="pages.length === 0" class="py-8 text-center text-xs text-slate-400">
              You do not manage any Pages yet. Create or manage a Page to share Reels there!
            </div>

            <template v-else>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Select Your Managed Page
                </label>
                <select
                  v-model="selectedPageId"
                  class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <option :value="null">-- Choose a Page --</option>
                  <option v-for="pg in pages" :key="pg.id" :value="pg.id">
                    🚩 {{ pg.name }}
                  </option>
                </select>
              </div>

              <textarea
                v-model="pageCaption"
                rows="2"
                placeholder="Write a status update for this Page (optional)..."
                class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
              ></textarea>

              <button
                type="button"
                @click="submitPageShare"
                :disabled="!selectedPageId || isSubmitting"
                class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-500 text-white disabled:opacity-50 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
              >
                <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <span>{{ isSubmitting ? 'Publishing...' : 'Publish to Page' }}</span>
              </button>
            </template>
          </div>
        </div>

        <!-- 4. Quick Actions Row (Copy Link, WhatsApp, Facebook, Native Share) -->
        <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 overflow-x-auto">
          <!-- Copy Link -->
          <button
            type="button"
            @click="copyReelLink"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all cursor-pointer shrink-0"
          >
            <svg v-if="!isCopied" class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
            </svg>
            <svg v-else class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span>{{ isCopied ? 'Link Copied!' : 'Copy Link' }}</span>
          </button>

          <!-- External Sharing Icons -->
          <div class="flex items-center gap-1.5 shrink-0">
            <!-- WhatsApp -->
            <a
              :href="getWhatsAppUrl()"
              target="_blank"
              rel="noopener"
              @click="recordSharePlatform('whatsapp')"
              class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all"
              title="Share on WhatsApp"
            >
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.301-.15-1.78-.879-2.056-.98-.276-.1-.477-.15-.678.15-.2.3-.778.98-.954 1.18-.176.2-.352.226-.653.075s-1.272-.469-2.424-1.496c-.896-.799-1.501-1.787-1.677-2.088-.176-.301-.019-.464.132-.614.136-.135.301-.351.452-.527.15-.176.2-.301.3-.502.101-.201.05-.377-.025-.527s-.678-1.633-.929-2.236c-.245-.588-.493-.508-.678-.518-.176-.01-.377-.01-.578-.01s-.527.075-.803.377c-.276.301-1.055 1.03-1.055 2.512s1.08 2.914 1.231 3.115c.15.201 2.125 3.245 5.148 4.552.719.311 1.281.497 1.719.636.723.23 1.381.198 1.901.12.579-.087 1.78-.728 2.032-1.431.251-.703.251-1.306.176-1.431-.076-.125-.277-.201-.578-.351z"/>
              </svg>
            </a>

            <!-- Facebook -->
            <a
              :href="getFacebookUrl()"
              target="_blank"
              rel="noopener"
              @click="recordSharePlatform('facebook')"
              class="w-8 h-8 rounded-full bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all"
              title="Share on Facebook"
            >
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>

            <!-- Native Share Button (if supported) -->
            <button
              v-if="hasNativeShare"
              type="button"
              @click="triggerNativeShare"
              class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 hover:bg-purple-600 hover:text-white flex items-center justify-center transition-all cursor-pointer"
              title="More Sharing Options"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, h } from 'vue';
import type { ReelItem, User } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  reel: ReelItem | null;
  currentUser: User | null;
  initialTab?: 'message' | 'profile' | 'group' | 'page';
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'shared', payload: { type: string; shares_count: number }): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

// Navigation Tabs
const activeTab = ref<'message' | 'profile' | 'group' | 'page'>('message');

// Icons as render functions
const MessageIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' }),
]);
const ProfileIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }),
]);
const GroupIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }),
]);
const PageIcon = () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
  h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '2', d: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9' }),
]);

const tabs = [
  { id: 'message' as const, label: 'Message', icon: MessageIcon },
  { id: 'profile' as const, label: 'Profile', icon: ProfileIcon },
  { id: 'group' as const, label: 'Groups', icon: GroupIcon },
  { id: 'page' as const, label: 'Pages', icon: PageIcon },
];

// Target data states
const groups = ref<Array<{ id: number; name: string; privacy: string }>>([]);
const pages = ref<Array<{ id: number; name: string; slug: string }>>([]);
const friends = ref<Array<{ id: number; name: string; username: string; avatar_url?: string }>>([]);

// Form states
const searchQuery = ref('');
const searchResults = ref<Array<{ id: number; name: string; username: string; avatar_url?: string }>>([]);
const isLoadingRecipients = ref(false);
const selectedRecipients = ref<Array<{ id: number; name: string; username: string; avatar_url?: string }>>([]);
const directMessageNote = ref('');

const profileCaption = ref('');
const selectedGroupId = ref<number | null>(null);
const groupCaption = ref('');
const selectedPageId = ref<number | null>(null);
const pageCaption = ref('');

const isSubmitting = ref(false);
const isCopied = ref(false);
const hasNativeShare = ref(typeof navigator !== 'undefined' && !!navigator.share);

// Filtered recipients
const filteredRecipients = computed(() => {
  if (searchQuery.value.trim().length > 0) {
    return searchResults.value;
  }
  return friends.value;
});

const isRecipientSelected = (userId: number) => {
  return selectedRecipients.value.some((r) => r.id === userId);
};

const toggleRecipient = (user: { id: number; name: string; username: string; avatar_url?: string }) => {
  const index = selectedRecipients.value.findIndex((r) => r.id === user.id);
  if (index >= 0) {
    selectedRecipients.value.splice(index, 1);
  } else {
    selectedRecipients.value.push(user);
  }
};

let searchDebounceTimeout: any = null;
const handleSearchInput = () => {
  clearTimeout(searchDebounceTimeout);
  const q = searchQuery.value.trim();
  if (!q) {
    searchResults.value = [];
    return;
  }
  isLoadingRecipients.value = true;
  searchDebounceTimeout = setTimeout(async () => {
    try {
      const res = await fetch(`/api/v2/reels/share-recipients?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      searchResults.value = data.data || [];
    } catch {
      searchResults.value = [];
    } finally {
      isLoadingRecipients.value = false;
    }
  }, 250);
};

// Fetch initial share targets (groups, pages, friends)
const fetchTargets = async () => {
  try {
    const res = await fetch('/api/v2/reels/share-targets', {
      headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.success && data.data) {
      groups.value = data.data.groups || [];
      pages.value = data.data.pages || [];
      friends.value = data.data.friends || [];
    }
  } catch (err) {
    console.warn('Failed to load share targets:', err);
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      if (props.initialTab) {
        activeTab.value = props.initialTab;
      }
      fetchTargets();
      selectedRecipients.value = [];
      directMessageNote.value = '';
      profileCaption.value = '';
      groupCaption.value = '';
      pageCaption.value = '';
      searchQuery.value = '';
      isCopied.value = false;
    }
  }
);

const closeModal = () => {
  emit('close');
};

// 1. Submit Direct Message
const submitDirectMessage = async () => {
  if (!props.reel || selectedRecipients.value.length === 0 || isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${props.reel.id}/message`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        recipient_ids: selectedRecipients.value.map((r) => r.id),
        message: directMessageNote.value.trim() || undefined,
        idempotency_key: `reel_msg_${props.reel.id}_${Date.now()}`,
      }),
    });

    const data = await res.json();
    if (data.success) {
      emit('toast', { message: `Reel sent to ${selectedRecipients.value.length} recipient(s)!`, type: 'success' });
      emit('shared', { type: 'message', shares_count: data.data?.shares_count ?? (props.reel.shares_count || 0) + 1 });
      closeModal();
    } else {
      emit('toast', { message: data.message || 'Failed to send message.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err?.message || 'Network error while sending message.', type: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

// 2. Submit Profile Share
const submitProfileShare = async () => {
  if (!props.reel || isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${props.reel.id}/share-post`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        caption: profileCaption.value.trim() || undefined,
      }),
    });

    const data = await res.json();
    if (data.success) {
      emit('toast', { message: 'Reel shared to your timeline successfully!', type: 'success' });
      emit('shared', { type: 'profile', shares_count: data.data?.shares_count ?? (props.reel.shares_count || 0) + 1 });
      closeModal();
    } else {
      emit('toast', { message: data.message || 'Failed to share to profile.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err?.message || 'Network error while sharing reel.', type: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

// 3. Submit Group Share
const submitGroupShare = async () => {
  if (!props.reel || !selectedGroupId.value || isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${props.reel.id}/share-group`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        group_id: selectedGroupId.value,
        caption: groupCaption.value.trim() || undefined,
      }),
    });

    const data = await res.json();
    if (data.success) {
      emit('toast', { message: 'Reel posted to group successfully!', type: 'success' });
      emit('shared', { type: 'group', shares_count: data.data?.shares_count ?? (props.reel.shares_count || 0) + 1 });
      closeModal();
    } else {
      emit('toast', { message: data.message || 'Failed to post to group.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err?.message || 'Network error.', type: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

// 4. Submit Page Share
const submitPageShare = async () => {
  if (!props.reel || !selectedPageId.value || isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    const res = await fetch(`/api/v2/reels/${props.reel.id}/share-page`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        page_id: selectedPageId.value,
        caption: pageCaption.value.trim() || undefined,
      }),
    });

    const data = await res.json();
    if (data.success) {
      emit('toast', { message: 'Reel published to your page successfully!', type: 'success' });
      emit('shared', { type: 'page', shares_count: data.data?.shares_count ?? (props.reel.shares_count || 0) + 1 });
      closeModal();
    } else {
      emit('toast', { message: data.message || 'Failed to publish to page.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err?.message || 'Network error.', type: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

// 5. External & Copy Link URLs
const getShareUrl = () => {
  if (typeof window === 'undefined' || !props.reel) return '';
  return `${window.location.origin}/reels/${props.reel.id}`;
};

const getWhatsAppUrl = () => {
  const url = getShareUrl();
  const text = `Check out this Reel on Bondhoo: ${url}`;
  return `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
};

const getFacebookUrl = () => {
  const url = getShareUrl();
  return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
};

const copyReelLink = async () => {
  const url = getShareUrl();
  if (!url) return;

  try {
    await navigator.clipboard.writeText(url);
    isCopied.value = true;
    emit('toast', { message: 'Reel link copied to clipboard!', type: 'success' });
    recordSharePlatform('copy_link');
    setTimeout(() => {
      isCopied.value = false;
    }, 2500);
  } catch {
    emit('toast', { message: 'Failed to copy link.', type: 'error' });
  }
};

const triggerNativeShare = async () => {
  if (!props.reel || typeof navigator === 'undefined' || !navigator.share) return;
  const url = getShareUrl();

  try {
    await navigator.share({
      title: `${props.reel.user?.name}'s Reel on Bondhoo`,
      text: props.reel.caption || 'Check out this Reel!',
      url,
    });
    recordSharePlatform('native');
  } catch {}
};

const recordSharePlatform = async (platform: string) => {
  if (!props.reel) return;
  try {
    const res = await fetch(`/api/v2/reels/${props.reel.id}/share`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({ platform }),
    });
    const data = await res.json();
    if (data.success && data.data?.shares_count) {
      emit('shared', { type: platform, shares_count: data.data.shares_count });
    }
  } catch {}
};
</script>
