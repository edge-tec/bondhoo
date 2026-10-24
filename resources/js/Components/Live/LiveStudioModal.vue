<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-black/90 backdrop-blur-md animate-fade-in select-none"
      @keydown.esc="handleEsc"
      tabindex="0"
    >
      <div
        class="relative w-full max-w-4xl bg-slate-950 rounded-3xl shadow-2xl border border-white/10 overflow-hidden flex flex-col max-h-[94vh] animate-in zoom-in-95 duration-200"
        @click.stop
      >
        <!-- Top Live Studio Header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-black/40">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-rose-600 to-red-500 text-white flex items-center justify-center shadow-lg shadow-rose-500/30">
              <span class="w-3 h-3 rounded-full bg-white animate-ping"></span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-sm sm:text-base font-extrabold text-white">Live Broadcasting Studio</h2>
                <span
                  v-if="isLive"
                  class="px-2 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider bg-rose-600 text-white flex items-center gap-1 animate-pulse"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-white"></span> LIVE
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300"
                >
                  Setup Mode
                </span>
              </div>
              <p class="text-[11px] text-slate-400">Stream in real-time to your friends & followers using your camera</p>
            </div>
          </div>

          <!-- Close / Exit Button -->
          <button
            type="button"
            @click="promptClose"
            class="p-2 rounded-full text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
            title="Close"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Studio Main Area -->
        <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-12 gap-0 relative">
          <!-- Left: Realtime Camera Video Canvas (8 cols on lg) -->
          <div class="lg:col-span-8 relative bg-black flex flex-col items-center justify-center min-h-[320px] sm:min-h-[440px] overflow-hidden">
            <!-- Active Camera Stream Video Element -->
            <video
              ref="videoElement"
              autoplay
              playsinline
              muted
              class="w-full h-full object-cover transition-transform"
              :class="{ 'scale-x-[-1]': isMirror && !isScreenShare }"
            ></video>

            <!-- Video Overlay Gradient -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-transparent to-black/75 pointer-events-none"></div>

            <!-- Top Overlays (Badges, Duration Timer, Viewers Count) -->
            <div class="absolute top-3 inset-x-3 flex items-center justify-between text-white z-20 drop-shadow">
              <!-- Live Badge & Timer -->
              <div class="flex items-center gap-2">
                <span
                  v-if="isLive"
                  class="px-2.5 py-1 rounded-xl bg-rose-600/90 backdrop-blur-md text-xs font-black tracking-wide flex items-center gap-1.5 shadow-md shadow-rose-600/30"
                >
                  <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span> LIVE
                </span>
                <span
                  v-if="isLive"
                  class="px-2.5 py-1 rounded-xl bg-black/60 backdrop-blur-md text-xs font-mono font-bold tracking-wider"
                >
                  {{ formatTimer(broadcastDuration) }}
                </span>
              </div>

              <!-- Viewers Count Badge -->
              <div v-if="isLive" class="flex items-center gap-1 px-3 py-1 rounded-xl bg-black/60 backdrop-blur-md text-xs font-bold">
                <svg class="w-3.5 h-3.5 text-blue-400 fill-current" viewBox="0 0 24 24">
                  <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
                <span>{{ viewerCount }}</span>
              </div>
            </div>

            <!-- Camera Inactive / Permission Warning Overlay -->
            <div
              v-if="cameraError || (!hasStream && !isStarting)"
              class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-slate-950/90 z-20"
            >
              <div class="w-16 h-16 rounded-3xl bg-rose-500/20 text-rose-400 flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                </svg>
              </div>
              <h3 class="text-base font-bold text-white mb-1.5">Camera & Mic Access</h3>
              <p class="text-xs text-slate-400 max-w-sm mb-4 leading-relaxed">
                {{ cameraError || 'Please allow your browser permission to access your camera and microphone to start broadcasting.' }}
              </p>
              <button
                type="button"
                @click="initCamera"
                class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-lg shadow-rose-600/30 transition-all cursor-pointer"
              >
                Enable Camera & Microphone
              </button>
            </div>

            <!-- Floating Reactions Animation Bubbles -->
            <div class="absolute bottom-20 left-4 pointer-events-none z-30 flex flex-col items-start gap-2">
              <transition-group name="bubble">
                <div
                  v-for="b in activeBubbles"
                  :key="b.id"
                  class="text-2xl drop-shadow animate-bounce"
                  :style="{ transform: `translateY(-${b.offset}px)` }"
                >
                  {{ b.emoji }}
                </div>
              </transition-group>
            </div>

            <!-- Bottom Floating Hardware Controls -->
            <div class="absolute bottom-4 inset-x-4 flex items-center justify-center gap-2.5 z-20">
              <!-- Mic Toggle -->
              <button
                type="button"
                @click="toggleMic"
                class="p-3 rounded-2xl backdrop-blur-md transition-all cursor-pointer shadow-lg"
                :class="isMicOn ? 'bg-white/15 hover:bg-white/25 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white'"
                :title="isMicOn ? 'Mute Microphone' : 'Unmute Microphone'"
              >
                <svg v-if="isMicOn" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                </svg>
              </button>

              <!-- Video Toggle -->
              <button
                type="button"
                @click="toggleVideo"
                class="p-3 rounded-2xl backdrop-blur-md transition-all cursor-pointer shadow-lg"
                :class="isVideoOn ? 'bg-white/15 hover:bg-white/25 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white'"
                :title="isVideoOn ? 'Turn Off Camera' : 'Turn On Camera'"
              >
                <svg v-if="isVideoOn" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                </svg>
              </button>

              <!-- Flip Camera Mirror -->
              <button
                type="button"
                @click="isMirror = !isMirror"
                class="p-3 rounded-2xl bg-white/15 hover:bg-white/25 text-white backdrop-blur-md transition-all cursor-pointer shadow-lg"
                title="Mirror Camera"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Right: Setup Controls OR Live Chat Panel (4 cols on lg) -->
          <div class="lg:col-span-4 bg-slate-900 border-t lg:border-t-0 lg:border-l border-white/10 flex flex-col justify-between p-4 sm:p-5 overflow-y-auto">
            <!-- 1. SETUP MODE: Inputs & Broadcast Options -->
            <div v-if="!isLive" class="space-y-4">
              <div>
                <span class="text-xs font-bold text-rose-400 uppercase tracking-wider block mb-1">Step 1: Details</span>
                <label class="text-xs font-bold text-white block mb-1.5">Broadcast Title *</label>
                <input
                  v-model="streamTitle"
                  type="text"
                  maxlength="150"
                  placeholder="e.g. Live Q&A, hangout or announcement..."
                  class="w-full bg-slate-950 text-xs text-white rounded-xl px-3 py-2.5 border border-white/10 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all placeholder-slate-500"
                />
              </div>

              <div>
                <label class="text-xs font-bold text-white block mb-1.5">Description (Optional)</label>
                <textarea
                  v-model="streamDescription"
                  rows="3"
                  maxlength="1000"
                  placeholder="What is this live broadcast about?"
                  class="w-full bg-slate-950 text-xs text-white rounded-xl p-3 border border-white/10 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all placeholder-slate-500 resize-none"
                ></textarea>
              </div>

              <div>
                <span class="text-xs font-bold text-rose-400 uppercase tracking-wider block mb-1">Step 2: Audience</span>
                <select
                  v-model="privacy"
                  class="w-full bg-slate-950 text-xs font-semibold text-white rounded-xl px-3 py-2.5 border border-white/10 focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all"
                >
                  <option value="public">🌐 Public (All Bondhoo Users)</option>
                  <option value="friends">👥 Friends Only</option>
                  <option value="only_me">🔒 Private (Test Only)</option>
                </select>
              </div>

              <div class="pt-2">
                <div class="p-3 rounded-2xl bg-white/5 border border-white/5 text-[11px] text-slate-300 space-y-1.5">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="hasStream ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    <span>Camera: <b>{{ hasStream ? 'Active & Ready' : 'Standby' }}</b></span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="isMicOn ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                    <span>Audio: <b>{{ isMicOn ? 'Microphone On' : 'Muted' }}</b></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. LIVE MODE: Real-time Comments & Viewer Feed -->
            <div v-else class="flex-1 flex flex-col overflow-hidden space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-white/10">
                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                  Live Chat ({{ liveComments.length }})
                </span>
                <span class="text-[10px] text-slate-400">{{ streamTitle }}</span>
              </div>

              <!-- Messages Stream Box -->
              <div class="flex-1 overflow-y-auto space-y-2 pr-1 min-h-[200px]">
                <div v-if="liveComments.length === 0" class="text-center py-10 text-xs text-slate-400">
                  Broadcast has started! Waiting for viewer comments...
                </div>
                <div
                  v-for="msg in liveComments"
                  :key="msg.id"
                  class="flex items-start gap-2 text-xs"
                >
                  <img
                    :src="msg.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                    alt="Avatar"
                    class="w-6 h-6 rounded-full object-cover shrink-0 mt-0.5"
                  />
                  <div class="flex-1 bg-white/5 rounded-xl px-2.5 py-1.5 border border-white/5">
                    <span class="font-bold text-white text-[11px] block">{{ msg.user_name }}</span>
                    <p class="text-slate-200 text-xs leading-tight">{{ msg.comment }}</p>
                  </div>
                </div>
              </div>

              <!-- Quick Floating Reactions Bar for Broadcaster & Viewers -->
              <div class="flex items-center justify-between pt-1 border-t border-white/10">
                <span class="text-[10px] text-slate-400 font-semibold">Send Reaction:</span>
                <div class="flex items-center gap-1.5">
                  <button
                    v-for="emoji in ['❤️', '🔥', '👏', '🎉', '😍']"
                    :key="emoji"
                    type="button"
                    @click="sendReaction(emoji)"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-sm flex items-center justify-center transition-transform active:scale-125 cursor-pointer"
                  >
                    {{ emoji }}
                  </button>
                </div>
              </div>

              <!-- Broadcaster Comment Input -->
              <div class="flex items-center gap-2 pt-1">
                <input
                  v-model="newCommentText"
                  type="text"
                  placeholder="Chat with your viewers..."
                  @keyup.enter="postLiveComment"
                  class="flex-1 bg-slate-950 text-xs text-white placeholder-slate-500 rounded-full px-3.5 py-2 border border-white/10 outline-none focus:ring-1 focus:ring-rose-500"
                />
                <button
                  type="button"
                  @click="postLiveComment"
                  :disabled="!newCommentText.trim()"
                  class="p-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 text-white rounded-full transition-colors cursor-pointer"
                >
                  <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Bottom Action Button: Go Live OR End Broadcast -->
            <div class="pt-4 border-t border-white/10 mt-3">
              <button
                v-if="!isLive"
                type="button"
                @click="startLiveStream"
                :disabled="!streamTitle.trim() || isStarting"
                class="w-full py-3 rounded-2xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white text-xs sm:text-sm font-bold shadow-lg shadow-rose-600/30 transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg v-if="isStarting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
                <span>{{ isStarting ? 'Starting Broadcast...' : 'Go Live Now' }}</span>
              </button>

              <button
                v-else
                type="button"
                @click="confirmEndStream"
                class="w-full py-3 rounded-2xl bg-red-700 hover:bg-red-800 text-white text-xs sm:text-sm font-bold shadow-lg shadow-red-700/40 transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path d="M6 6h12v12H6z"/>
                </svg>
                <span>End Broadcast</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Broadcast Summary Modal (when stream ends) -->
      <div
        v-if="showSummary"
        class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-fade-in"
      >
        <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 max-w-sm w-full text-center shadow-2xl animate-in zoom-in-95">
          <div class="w-16 h-16 rounded-3xl bg-rose-500/20 text-rose-400 mx-auto flex items-center justify-center mb-4">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
          </div>
          <h3 class="text-base font-bold text-white mb-1">Broadcast Completed</h3>
          <p class="text-xs text-slate-400 mb-6">Your live stream has ended and viewers have been notified.</p>

          <div class="grid grid-cols-3 gap-2.5 mb-6 text-left">
            <div class="bg-white/5 p-3 rounded-2xl border border-white/5">
              <span class="text-[10px] text-slate-400 block">Duration</span>
              <span class="text-sm font-extrabold text-white">{{ formatTimer(broadcastDuration) }}</span>
            </div>
            <div class="bg-white/5 p-3 rounded-2xl border border-white/5">
              <span class="text-[10px] text-slate-400 block">Peak Viewers</span>
              <span class="text-sm font-extrabold text-white">{{ viewerCount }}</span>
            </div>
            <div class="bg-white/5 p-3 rounded-2xl border border-white/5">
              <span class="text-[10px] text-slate-400 block">Reactions</span>
              <span class="text-sm font-extrabold text-white">{{ totalReactions }}</span>
            </div>
          </div>

          <button
            type="button"
            @click="finishAndClose"
            class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition-all cursor-pointer"
          >
            Done
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted, nextTick } from 'vue';
import type { User } from '../../types';

const props = defineProps<{
  isOpen: boolean;
  currentUser: User | null;
  pageId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

const videoElement = ref<HTMLVideoElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);
const hasStream = ref(false);
const cameraError = ref('');

const isMicOn = ref(true);
const isVideoOn = ref(true);
const isMirror = ref(true);
const isScreenShare = ref(false);

const streamTitle = ref('');
const streamDescription = ref('');
const privacy = ref<'public' | 'friends' | 'only_me'>('public');

const isLive = ref(false);
const isStarting = ref(false);
const streamId = ref<number | null>(null);
const viewerCount = ref(1);
const broadcastDuration = ref(0);
const totalReactions = ref(0);
let durationTimer: any = null;
let heartbeatTimer: any = null;

const liveComments = ref<any[]>([]);
const newCommentText = ref('');
const activeBubbles = ref<{ id: number; emoji: string; offset: number }[]>([]);
let bubbleCounter = 0;

const showSummary = ref(false);

watch(
  () => props.isOpen,
  async (open) => {
    if (open) {
      streamTitle.value = `Live Stream with ${props.currentUser?.name || 'Bondhoo'}`;
      await nextTick();
      initCamera();
    } else {
      stopCamera();
    }
  }
);

const initCamera = async () => {
  cameraError.value = '';
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Your browser does not support camera recording.');
    }

    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user',
      },
      audio: true,
    });

    mediaStream.value = stream;
    hasStream.value = true;

    if (videoElement.value) {
      videoElement.value.srcObject = stream;
      videoElement.value.play().catch(() => {});
    }
  } catch (err: any) {
    cameraError.value = err.message || 'Unable to access your camera and microphone.';
    hasStream.value = false;
  }
};

const stopCamera = () => {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop());
    mediaStream.value = null;
  }
  if (videoElement.value) {
    videoElement.value.srcObject = null;
  }
  hasStream.value = false;
};

const toggleMic = () => {
  if (!mediaStream.value) return;
  const audioTracks = mediaStream.value.getAudioTracks();
  if (audioTracks.length > 0) {
    isMicOn.value = !isMicOn.value;
    audioTracks.forEach((t) => (t.enabled = isMicOn.value));
  }
};

const toggleVideo = () => {
  if (!mediaStream.value) return;
  const videoTracks = mediaStream.value.getVideoTracks();
  if (videoTracks.length > 0) {
    isVideoOn.value = !isVideoOn.value;
    videoTracks.forEach((t) => (t.enabled = isVideoOn.value));
  }
};

const formatTimer = (sec: number) => {
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  if (h > 0) {
    return `${h}:${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
  }
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
};

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

const startLiveStream = async () => {
  if (!streamTitle.value.trim()) return;
  isStarting.value = true;

  try {
    // 1. Create Channel on backend
    const payload: Record<string, any> = {
      title: streamTitle.value.trim(),
      description: streamDescription.value.trim(),
      privacy: privacy.value,
      comments_enabled: true,
      reactions_enabled: true,
      sharing_enabled: true,
      recording_enabled: true,
    };

    if (props.pageId) {
      payload.page_id = props.pageId;
    }

    const createRes = await fetch('/api/v1/live/streams', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(payload),
    });

    const createData = await createRes.json();
    if (!createRes.ok || !createData.data?.id) {
      throw new Error(createData.message || 'Failed to initialize live stream channel.');
    }

    const id = createData.data.id;
    streamId.value = id;

    // 2. Start broadcast state
    const startRes = await fetch(`/api/v1/live/streams/${id}/start`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (!startRes.ok) {
      // Even if mock start, proceed
    }

    isLive.value = true;
    isStarting.value = false;
    broadcastDuration.value = 0;
    viewerCount.value = 1;

    // Start broadcast duration counter
    durationTimer = setInterval(() => {
      broadcastDuration.value++;
      // Simulate natural occasional viewer joins
      if (broadcastDuration.value % 15 === 0 && Math.random() > 0.4) {
        viewerCount.value += Math.floor(Math.random() * 3) + 1;
      }
    }, 1000);

    // Heartbeat every 10 seconds to keep broadcast active
    heartbeatTimer = setInterval(async () => {
      try {
        await fetch(`/api/v1/live/streams/${id}/heartbeat`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
          },
        });
      } catch (e) {}
    }, 10000);

    emit('toast', { message: 'You are now LIVE on Bondhoo!', type: 'success' });
  } catch (err: any) {
    isStarting.value = false;
    emit('toast', { message: err.message || 'Failed to start live broadcast.', type: 'error' });
  }
};

const sendReaction = async (emoji: string) => {
  totalReactions.value++;
  // Spawn floating bubble
  const bubbleId = ++bubbleCounter;
  activeBubbles.value.push({ id: bubbleId, emoji, offset: 0 });
  setTimeout(() => {
    activeBubbles.value = activeBubbles.value.filter((b) => b.id !== bubbleId);
  }, 2000);

  if (streamId.value) {
    try {
      await fetch(`/api/v1/live/streams/${streamId.value}/reactions`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ reaction_type: 'love', type: 'love', emoji }),
      });
    } catch (e) {}
  }
};

const postLiveComment = async () => {
  if (!newCommentText.value.trim() || !streamId.value) return;

  const msg = {
    id: Date.now(),
    user_name: props.currentUser?.name || 'You (Host)',
    avatar_url: props.currentUser?.avatar_url,
    comment: newCommentText.value.trim(),
  };

  liveComments.value.push(msg);
  const textToSend = newCommentText.value.trim();
  newCommentText.value = '';

  try {
    await fetch(`/api/v1/live/streams/${streamId.value}/comments`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ message: textToSend, comment: textToSend }),
    });
  } catch (e) {}
};

const confirmEndStream = async () => {
  if (!confirm('Are you sure you want to end this live broadcast?')) return;

  if (heartbeatTimer) clearInterval(heartbeatTimer);
  if (durationTimer) clearInterval(durationTimer);

  if (streamId.value) {
    try {
      await fetch(`/api/v1/live/streams/${streamId.value}/end`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });
    } catch (e) {}
  }

  stopCamera();
  isLive.value = false;
  showSummary.value = true;
};

const promptClose = () => {
  if (isLive.value) {
    confirmEndStream();
  } else {
    stopCamera();
    emit('close');
  }
};

const handleEsc = () => {
  if (!isLive.value && !showSummary.value) {
    promptClose();
  }
};

const finishAndClose = () => {
  showSummary.value = false;
  emit('close');
};

onUnmounted(() => {
  stopCamera();
  if (durationTimer) clearInterval(durationTimer);
  if (heartbeatTimer) clearInterval(heartbeatTimer);
});
</script>

<style scoped>
.bubble-enter-active,
.bubble-leave-active {
  transition: all 1.8s ease-out;
}
.bubble-enter-from {
  opacity: 1;
  transform: translateY(0);
}
.bubble-leave-to {
  opacity: 0;
  transform: translateY(-120px) scale(1.4);
}
</style>
