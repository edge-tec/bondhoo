import { ref, computed, watch, nextTick, onMounted, onUnmounted, type Ref } from 'vue';
import type { ReelItem } from '../types';

export interface ReelGestureConfig {
  swipeDistanceThreshold?: number;
  flickVelocityThreshold?: number;
  flickMinDistance?: number;
  directionLockRatio?: number;
  tapMaxDistance?: number;
  longPressDelayMs?: number;
  wheelCooldownMs?: number;
  navigationLockMs?: number;
}

const STORAGE_KEY_AUTO_ADVANCE = 'jugajug_reels_auto_advance';
const EVENT_AUTO_ADVANCE_SYNC = 'jugajug:reels-auto-advance-changed';

export function readPersistedAutoAdvance(): boolean {
  if (typeof window === 'undefined') {
    return true;
  }
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY_AUTO_ADVANCE);
    if (raw === '0' || raw === 'false') {
      return false;
    }
    return true;
  } catch {
    return true;
  }
}

export function writePersistedAutoAdvance(enabled: boolean): void {
  if (typeof window === 'undefined') {
    return;
  }
  try {
    window.localStorage.setItem(STORAGE_KEY_AUTO_ADVANCE, enabled ? '1' : '0');
    window.dispatchEvent(
      new CustomEvent(EVENT_AUTO_ADVANCE_SYNC, { detail: { enabled } })
    );
  } catch {
    // ignore storage errors
  }
}

export function useReelPlayerEngine(options: {
  reels: Ref<ReelItem[]>;
  currentIndex: Ref<number>;
  videoEl: Ref<HTMLVideoElement | null>;
  isActive?: Ref<boolean>;
  initialMuted?: boolean;
  onReelChanged?: (newIndex: number, direction: 'up' | 'down') => void;
  onDoubleTapLike?: (coords: { x: number; y: number } | null) => void;
  onNeedMoreReels?: () => void;
  config?: ReelGestureConfig;
}) {
  const cfg: Required<ReelGestureConfig> = {
    swipeDistanceThreshold: options.config?.swipeDistanceThreshold ?? 36,
    flickVelocityThreshold: options.config?.flickVelocityThreshold ?? 0.24,
    flickMinDistance: options.config?.flickMinDistance ?? 16,
    directionLockRatio: options.config?.directionLockRatio ?? 1.15,
    tapMaxDistance: options.config?.tapMaxDistance ?? 12,
    longPressDelayMs: options.config?.longPressDelayMs ?? 550,
    wheelCooldownMs: options.config?.wheelCooldownMs ?? 380,
    navigationLockMs: options.config?.navigationLockMs ?? 360,
  };

  const isPlaying = ref(true);
  const isMuted = ref(options.initialMuted ?? true);
  const playbackRate = ref(1);
  const videoProgress = ref(0);
  const videoError = ref(false);
  const videoErrorMessage = ref('Video could not be loaded');
  const isBuffering = ref(false);
  const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false);
  const failedPreloadUrls = ref<Set<string>>(new Set());
  const isAutoAdvanceEnabled = ref<boolean>(readPersistedAutoAdvance());
  const slideDirection = ref<'up' | 'down' | 'none'>('none');
  const showPlayPauseIcon = ref(false);
  const showHeartAnim = ref(false);
  const heartBurstPos = ref<{ x: number; y: number } | null>(null);
  const isDragging = ref(false);
  const dragOffset = ref(0);
  const isLongPressing2x = ref(false);

  const currentReel = computed<ReelItem | undefined>(
    () => options.reels.value[options.currentIndex.value]
  );

  const nextReelPreloadUrl = computed<string | null>(() => {
    if (options.isActive && !options.isActive.value) {
      return null;
    }
    const list = options.reels.value;
    if (list.length <= 1) {
      return null;
    }
    const nextIdx = (options.currentIndex.value + 1) % list.length;
    const nextUrl = list[nextIdx]?.video_url || null;
    if (!nextUrl || nextUrl === currentReel.value?.video_url) {
      return null;
    }
    if (failedPreloadUrls.value.has(nextUrl)) {
      return null;
    }
    return nextUrl;
  });

  const cardTransformStyle = computed(() => {
    if (isDragging.value) {
      const damped = Math.max(-260, Math.min(260, dragOffset.value * 0.85));
      return {
        transform: `translate3d(0, ${damped}px, 0)`,
        transition: 'none',
      };
    }
    return {
      transform: 'translate3d(0, 0, 0)',
      transition: 'transform 0.28s cubic-bezier(0.16, 1, 0.3, 1)',
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

  // Internal lifecycle & gesture state
  let advanceLockUntil = 0;
  let completedReelToken = '';
  let playRequestSeq = 0;
  let slideResetTimer: any = null;
  let playPauseTimer: any = null;
  let longPressTimer: any = null;
  let singleTapTimer: any = null;
  let lastTapTime = 0;
  let lastWheelTime = 0;
  let lastTouchEventTime = 0;
  let lastPointerEventTime = 0;
  let suppressClickUntil = 0;

  let gestureActive = false;
  let gestureSource: 'touch' | 'pointer' | 'mouse' | null = null;
  let activePointerId: number | null = null;
  let startX = 0;
  let startY = 0;
  let startTime = 0;
  let startedOnInteractive = false;
  let isVerticalGesture = false;
  let isHorizontalGesture = false;

  const isViewerActive = (): boolean => {
    return options.isActive ? options.isActive.value : true;
  };

  const getReelToken = (): string => {
    const r = currentReel.value;
    return `${r?.id ?? 'none'}:${options.currentIndex.value}`;
  };

  const syncVideoLoopAttribute = () => {
    const v = options.videoEl.value;
    if (!v) {
      return;
    }
    const shouldLoop = !isAutoAdvanceEnabled.value || options.reels.value.length <= 1;
    v.loop = shouldLoop;
    if (shouldLoop) {
      v.setAttribute('loop', '');
    } else {
      v.removeAttribute('loop');
    }
  };

  const toggleAutoAdvance = () => {
    isAutoAdvanceEnabled.value = !isAutoAdvanceEnabled.value;
    writePersistedAutoAdvance(isAutoAdvanceEnabled.value);
    syncVideoLoopAttribute();
  };

  const setAutoAdvance = (enabled: boolean) => {
    isAutoAdvanceEnabled.value = enabled;
    writePersistedAutoAdvance(enabled);
    syncVideoLoopAttribute();
  };

  const playActiveVideo = async (resetToStart = true) => {
    const seq = ++playRequestSeq;
    await nextTick();
    if (seq !== playRequestSeq || !isViewerActive()) {
      return;
    }
    const v = options.videoEl.value;
    if (!v) {
      return;
    }

    videoError.value = false;
    syncVideoLoopAttribute();
    v.muted = isMuted.value;
    v.playbackRate = playbackRate.value;

    if (resetToStart) {
      try {
        v.currentTime = 0;
      } catch {
        // ignore if metadata not yet loaded
      }
    }

    try {
      await v.play();
      if (seq === playRequestSeq) {
        isPlaying.value = true;
        isBuffering.value = false;
      }
    } catch {
      if (seq !== playRequestSeq) {
        return;
      }
      // Browser blocked unmuted autoplay -> fallback to muted autoplay
      if (options.videoEl.value) {
        isMuted.value = true;
        options.videoEl.value.muted = true;
        try {
          await options.videoEl.value.play();
          if (seq === playRequestSeq) {
            isPlaying.value = true;
            isBuffering.value = false;
          }
        } catch {
          if (seq === playRequestSeq) {
            isPlaying.value = false;
            isBuffering.value = false;
          }
        }
      }
    }
  };

  const pauseActiveVideo = () => {
    if (options.videoEl.value) {
      try {
        options.videoEl.value.pause();
      } catch {
        // ignore
      }
    }
    isPlaying.value = false;
    isBuffering.value = false;
  };

  const releaseVideoResources = () => {
    pauseActiveVideo();
    const v = options.videoEl.value;
    if (v) {
      try {
        v.removeAttribute('src');
        v.load();
      } catch {
        // ignore
      }
    }
  };

  const triggerSlideAnimation = (dir: 'up' | 'down') => {
    slideDirection.value = dir;
    if (slideResetTimer) {
      clearTimeout(slideResetTimer);
    }
    slideResetTimer = setTimeout(() => {
      slideDirection.value = 'none';
    }, 300);
  };

  const navigateReel = (direction: 'next' | 'prev', fromAutoAdvance = false): boolean => {
    const list = options.reels.value;
    if (list.length === 0) {
      return false;
    }

    const now = Date.now();
    if (now < advanceLockUntil) {
      return false;
    }
    advanceLockUntil = now + cfg.navigationLockMs;

    // Single-reel playlist: replay from beginning
    if (list.length === 1) {
      if (options.videoEl.value) {
        options.videoEl.value.currentTime = 0;
        options.videoEl.value.play().catch(() => {});
        isPlaying.value = true;
      }
      return true;
    }

    pauseActiveVideo();
    videoProgress.value = 0;
    videoError.value = false;

    if (direction === 'next') {
      triggerSlideAnimation('up');
      if (options.currentIndex.value < list.length - 1) {
        options.currentIndex.value++;
      } else {
        options.currentIndex.value = 0;
      }
      if (options.currentIndex.value >= list.length - 2) {
        options.onNeedMoreReels?.();
      }
      options.onReelChanged?.(options.currentIndex.value, 'up');
    } else {
      triggerSlideAnimation('down');
      if (options.currentIndex.value > 0) {
        options.currentIndex.value--;
      } else {
        options.currentIndex.value = list.length - 1;
      }
      options.onReelChanged?.(options.currentIndex.value, 'down');
    }

    completedReelToken = '';
    playActiveVideo();
    return true;
  };

  const nextReel = (): boolean => navigateReel('next', false);
  const prevReel = (): boolean => navigateReel('prev', false);

  const onTimeUpdate = () => {
    const v = options.videoEl.value;
    if (!v || !v.duration || !Number.isFinite(v.duration)) {
      return;
    }
    const cur = v.currentTime;
    const dur = v.duration;
    videoProgress.value = Math.min(100, Math.max(0, (cur / dur) * 100));

    // Near-completion fallback for mobile browsers that delay/skip `ended`
    if (
      isAutoAdvanceEnabled.value &&
      options.reels.value.length > 1 &&
      dur > 0.6 &&
      cur >= dur - 0.15
    ) {
      const token = getReelToken();
      if (completedReelToken !== token) {
        completedReelToken = token;
        navigateReel('next', true);
      }
    }
  };

  const onVideoEnded = () => {
    const list = options.reels.value;
    if (isAutoAdvanceEnabled.value && list.length > 1) {
      const token = getReelToken();
      if (completedReelToken !== token) {
        completedReelToken = token;
        navigateReel('next', true);
      }
    } else if (options.videoEl.value) {
      options.videoEl.value.currentTime = 0;
      options.videoEl.value.play().catch(() => {});
      isPlaying.value = true;
    }
  };

  const onVideoWaiting = () => {
    if (!videoError.value && isViewerActive()) {
      isBuffering.value = true;
    }
  };

  const onVideoPlaying = () => {
    isBuffering.value = false;
    videoError.value = false;
    isPlaying.value = true;
  };

  const onVideoCanPlay = () => {
    isBuffering.value = false;
  };

  const onPreloadError = () => {
    const url = nextReelPreloadUrl.value;
    if (url) {
      const nextSet = new Set(failedPreloadUrls.value);
      nextSet.add(url);
      failedPreloadUrls.value = nextSet;
    }
  };

  const onVideoError = () => {
    isBuffering.value = false;
    videoError.value = true;
    isPlaying.value = false;

    const v = options.videoEl.value;
    const code = v?.error?.code;
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
      isOffline.value = true;
      videoErrorMessage.value = 'Network connection lost. Playback will resume when back online.';
    } else if (code === 2) {
      videoErrorMessage.value = 'Network error while streaming video. Tap Retry to resume.';
    } else if (code === 3) {
      videoErrorMessage.value = 'Video decoding interrupted or corrupted.';
    } else if (code === 4) {
      videoErrorMessage.value = 'Video source is unavailable or format is unsupported.';
    } else {
      videoErrorMessage.value = 'Video could not be loaded.';
    }
  };

  const retryVideo = () => {
    const currentUrl = currentReel.value?.video_url;
    if (currentUrl && failedPreloadUrls.value.has(currentUrl)) {
      const nextSet = new Set(failedPreloadUrls.value);
      nextSet.delete(currentUrl);
      failedPreloadUrls.value = nextSet;
    }
    videoError.value = false;
    isBuffering.value = true;
    const v = options.videoEl.value;
    const savedTime = v && Number.isFinite(v.currentTime) && v.currentTime > 0 ? v.currentTime : 0;
    if (v) {
      try {
        v.load();
        if (savedTime > 0) {
          v.currentTime = savedTime;
        }
      } catch {
        // ignore
      }
    }
    playActiveVideo(savedTime === 0);
  };

  const togglePlay = () => {
    const v = options.videoEl.value;
    if (!v) {
      return;
    }
    if (v.paused) {
      v.play().catch(() => {});
      isPlaying.value = true;
    } else {
      v.pause();
      isPlaying.value = false;
    }
    showPlayPauseIcon.value = true;
    if (playPauseTimer) {
      clearTimeout(playPauseTimer);
    }
    playPauseTimer = setTimeout(() => {
      showPlayPauseIcon.value = false;
    }, 420);
  };

  const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (options.videoEl.value) {
      options.videoEl.value.muted = isMuted.value;
    }
  };

  // ================= CENTRALIZED GESTURE CONTROLLER =================
  const isBlockedZone = (target: EventTarget | null): boolean => {
    const el = target as HTMLElement | null;
    if (!el || typeof el.closest !== 'function') {
      return false;
    }
    return Boolean(
      el.closest('input, textarea, select, [contenteditable="true"], [data-reel-no-swipe="true"]')
    );
  };

  const isInteractiveControl = (target: EventTarget | null): boolean => {
    const el = target as HTMLElement | null;
    if (!el || typeof el.closest !== 'function') {
      return false;
    }
    return Boolean(el.closest('button, a, [role="button"]'));
  };

  const clearLongPress = () => {
    if (longPressTimer) {
      clearTimeout(longPressTimer);
      longPressTimer = null;
    }
    if (isLongPressing2x.value) {
      isLongPressing2x.value = false;
      if (options.videoEl.value) {
        options.videoEl.value.playbackRate = playbackRate.value;
      }
    }
  };

  const beginGesture = (
    x: number,
    y: number,
    target: EventTarget | null,
    source: 'touch' | 'pointer' | 'mouse',
    pointerId: number | null = null
  ) => {
    if (isBlockedZone(target)) {
      gestureActive = false;
      return;
    }
    gestureActive = true;
    gestureSource = source;
    activePointerId = pointerId;
    startX = x;
    startY = y;
    startTime = Date.now();
    startedOnInteractive = isInteractiveControl(target);
    isVerticalGesture = false;
    isHorizontalGesture = false;
    isDragging.value = false;
    dragOffset.value = 0;

    clearLongPress();
    if (!startedOnInteractive) {
      longPressTimer = setTimeout(() => {
        if (gestureActive && !isDragging.value && !isVerticalGesture && !isHorizontalGesture && options.videoEl.value) {
          isLongPressing2x.value = true;
          options.videoEl.value.playbackRate = 2;
        }
      }, cfg.longPressDelayMs);
    }
  };

  const updateGesture = (x: number, y: number, event?: Event) => {
    if (!gestureActive) {
      return;
    }
    const deltaY = y - startY;
    const deltaX = x - startX;
    const absY = Math.abs(deltaY);
    const absX = Math.abs(deltaX);

    if (absY > 8 || absX > 8) {
      if (longPressTimer) {
        clearTimeout(longPressTimer);
        longPressTimer = null;
      }
      // If user was in 2x long-press but now intentionally drags vertically, exit 2x and allow swipe
      if (isLongPressing2x.value && absY > 18 && absY >= absX * cfg.directionLockRatio) {
        isLongPressing2x.value = false;
        if (options.videoEl.value) {
          options.videoEl.value.playbackRate = playbackRate.value;
        }
      }
    }

    if (isLongPressing2x.value) {
      return;
    }

    if (!isVerticalGesture && !isHorizontalGesture && (absY > 7 || absX > 7)) {
      if (absY >= absX * cfg.directionLockRatio) {
        isVerticalGesture = true;
      } else if (absX > absY * cfg.directionLockRatio) {
        isHorizontalGesture = true;
      }
    }

    if (isHorizontalGesture) {
      return;
    }

    if (isVerticalGesture) {
      if (event && event.cancelable) {
        event.preventDefault();
      }
      isDragging.value = true;
      dragOffset.value = deltaY;
      if (absY >= 14) {
        suppressClickUntil = Date.now() + 420;
      }
    }
  };

  const finishGesture = (x: number, y: number, currentTarget: EventTarget | null) => {
    if (!gestureActive) {
      return;
    }
    const source = gestureSource;
    gestureActive = false;
    gestureSource = null;
    activePointerId = null;

    const wasLongPress2x = isLongPressing2x.value;
    clearLongPress();

    const deltaY = y - startY;
    const deltaX = x - startX;
    const absY = Math.abs(deltaY);
    const absX = Math.abs(deltaX);
    const duration = Math.max(1, Date.now() - startTime);
    const velocityY = absY / duration;

    const wasVertical = isVerticalGesture || (absY >= absX * cfg.directionLockRatio && absY >= cfg.flickMinDistance);
    isDragging.value = false;
    dragOffset.value = 0;
    isVerticalGesture = false;
    isHorizontalGesture = false;

    if (wasLongPress2x && absY < cfg.swipeDistanceThreshold) {
      return;
    }

    // 1. Intentional vertical swipe (slow drag past threshold OR fast flick)
    if (
      wasVertical &&
      (absY >= cfg.swipeDistanceThreshold ||
        (velocityY >= cfg.flickVelocityThreshold && absY >= cfg.flickMinDistance))
    ) {
      suppressClickUntil = Date.now() + 420;
      if (deltaY < 0) {
        nextReel();
      } else {
        prevReel();
      }
      return;
    }

    // 2. If gesture started on a button/link and was NOT a vertical swipe, let the native button click work
    if (startedOnInteractive) {
      return;
    }

    // 3. Tap / Double-tap on video surface
    if (absY <= cfg.tapMaxDistance && absX <= cfg.tapMaxDistance && source !== 'mouse') {
      const now = Date.now();
      if (now - lastTapTime < 280) {
        if (singleTapTimer) {
          clearTimeout(singleTapTimer);
          singleTapTimer = null;
        }
        lastTapTime = 0;

        const rect = (currentTarget as HTMLElement | null)?.getBoundingClientRect?.();
        const coords = rect ? { x: x - rect.left, y: y - rect.top } : null;
        heartBurstPos.value = coords;
        showHeartAnim.value = true;
        setTimeout(() => {
          showHeartAnim.value = false;
        }, 600);
        options.onDoubleTapLike?.(coords);
      } else {
        lastTapTime = now;
        singleTapTimer = setTimeout(() => {
          togglePlay();
        }, 250);
      }
    } else if (absY <= cfg.tapMaxDistance && absX <= cfg.tapMaxDistance && source === 'mouse') {
      togglePlay();
    }
  };

  const cancelGesture = () => {
    gestureActive = false;
    gestureSource = null;
    activePointerId = null;
    clearLongPress();
    isDragging.value = false;
    dragOffset.value = 0;
    isVerticalGesture = false;
    isHorizontalGesture = false;
  };

  // Touch Event Handlers
  const handleTouchStart = (e: TouchEvent) => {
    lastTouchEventTime = Date.now();
    if (e.touches.length !== 1) {
      cancelGesture();
      return;
    }
    const t = e.touches[0];
    beginGesture(t.clientX, t.clientY, e.target, 'touch');
  };

  const handleTouchMove = (e: TouchEvent) => {
    lastTouchEventTime = Date.now();
    if (e.touches.length !== 1) {
      return;
    }
    const t = e.touches[0];
    updateGesture(t.clientX, t.clientY, e);
  };

  const handleTouchEnd = (e: TouchEvent) => {
    lastTouchEventTime = Date.now();
    const t = e.changedTouches[0];
    if (!t) {
      cancelGesture();
      return;
    }
    finishGesture(t.clientX, t.clientY, e.currentTarget);
  };

  const handleTouchCancel = () => {
    cancelGesture();
  };

  // Pointer Event Handlers (skipped when touch events already handled the gesture)
  const handlePointerDown = (e: PointerEvent) => {
    if (e.pointerType === 'touch' || Date.now() - lastTouchEventTime < 500) {
      return;
    }
    if (e.button !== 0) {
      return;
    }
    lastPointerEventTime = Date.now();
    beginGesture(e.clientX, e.clientY, e.target, 'pointer', e.pointerId);
  };

  const handlePointerMove = (e: PointerEvent) => {
    if (!gestureActive || gestureSource !== 'pointer' || e.pointerId !== activePointerId) {
      return;
    }
    lastPointerEventTime = Date.now();
    updateGesture(e.clientX, e.clientY, e);
  };

  const handlePointerUp = (e: PointerEvent) => {
    if (!gestureActive || gestureSource !== 'pointer' || e.pointerId !== activePointerId) {
      return;
    }
    lastPointerEventTime = Date.now();
    finishGesture(e.clientX, e.clientY, e.currentTarget);
  };

  const handlePointerCancel = () => {
    if (gestureSource === 'pointer') {
      cancelGesture();
    }
  };

  // Mouse Event Handlers (fallback for environments dispatching MouseEvents directly)
  const handleMouseDown = (e: MouseEvent) => {
    if (
      Date.now() - lastTouchEventTime < 500 ||
      Date.now() - lastPointerEventTime < 500 ||
      gestureSource === 'pointer'
    ) {
      return;
    }
    if (e.button !== 0) {
      return;
    }
    beginGesture(e.clientX, e.clientY, e.target, 'mouse');
  };

  const handleMouseMove = (e: MouseEvent) => {
    if (!gestureActive || gestureSource !== 'mouse') {
      return;
    }
    updateGesture(e.clientX, e.clientY, e);
  };

  const handleMouseUp = (e: MouseEvent) => {
    if (!gestureActive || gestureSource !== 'mouse') {
      return;
    }
    finishGesture(e.clientX, e.clientY, e.currentTarget);
  };

  const handleMouseCancel = () => {
    if (gestureSource === 'mouse') {
      cancelGesture();
    }
  };

  // Suppress synthetic click after a completed vertical swipe that started on a button/link
  const handleCaptureClick = (e: MouseEvent) => {
    if (Date.now() < suppressClickUntil) {
      e.stopPropagation();
      e.preventDefault();
      suppressClickUntil = 0;
    }
  };

  // Desktop Wheel Navigation
  const handleWheelScroll = (e: WheelEvent) => {
    if (isBlockedZone(e.target)) {
      return;
    }
    const now = Date.now();
    if (now - lastWheelTime < cfg.wheelCooldownMs) {
      return;
    }
    if (Math.abs(e.deltaY) > 22 && Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
      lastWheelTime = now;
      if (e.deltaY > 0) {
        nextReel();
      } else {
        prevReel();
      }
    }
  };

  // Cross-component & multi-tab preference sync + tab visibility handling
  const onExternalAutoAdvanceSync = (e: Event) => {
    const detail = (e as CustomEvent).detail;
    if (typeof detail?.enabled === 'boolean') {
      isAutoAdvanceEnabled.value = detail.enabled;
      syncVideoLoopAttribute();
    }
  };

  const onStorageSync = (e: StorageEvent) => {
    if (e.key === STORAGE_KEY_AUTO_ADVANCE) {
      isAutoAdvanceEnabled.value = e.newValue !== '0' && e.newValue !== 'false';
      syncVideoLoopAttribute();
    }
  };

  const onVisibilityChange = () => {
    if (typeof document === 'undefined') {
      return;
    }
    if (document.hidden) {
      pauseActiveVideo();
    } else if (isViewerActive()) {
      playActiveVideo(false);
    }
  };

  const onNetworkOnline = () => {
    isOffline.value = false;
    failedPreloadUrls.value = new Set();
    if (videoError.value && isViewerActive()) {
      retryVideo();
    }
  };

  const onNetworkOffline = () => {
    isOffline.value = true;
  };

  watch(
    () => options.currentIndex.value,
    () => {
      completedReelToken = '';
      videoProgress.value = 0;
      videoError.value = false;
      isBuffering.value = false;
      playActiveVideo(true);
    }
  );

  watch(
    () => options.reels.value.length,
    (newLen) => {
      if (newLen === 0) {
        options.currentIndex.value = 0;
        pauseActiveVideo();
      } else if (options.currentIndex.value >= newLen) {
        options.currentIndex.value = newLen - 1;
      }
      syncVideoLoopAttribute();
    }
  );

  onMounted(() => {
    if (typeof window !== 'undefined') {
      window.addEventListener(EVENT_AUTO_ADVANCE_SYNC, onExternalAutoAdvanceSync);
      window.addEventListener('storage', onStorageSync);
      window.addEventListener('online', onNetworkOnline);
      window.addEventListener('offline', onNetworkOffline);
      document.addEventListener('visibilitychange', onVisibilityChange);
    }
  });

  onUnmounted(() => {
    releaseVideoResources();
    if (slideResetTimer) clearTimeout(slideResetTimer);
    if (playPauseTimer) clearTimeout(playPauseTimer);
    if (longPressTimer) clearTimeout(longPressTimer);
    if (singleTapTimer) clearTimeout(singleTapTimer);
    if (typeof window !== 'undefined') {
      window.removeEventListener(EVENT_AUTO_ADVANCE_SYNC, onExternalAutoAdvanceSync);
      window.removeEventListener('storage', onStorageSync);
      window.removeEventListener('online', onNetworkOnline);
      window.removeEventListener('offline', onNetworkOffline);
      document.removeEventListener('visibilitychange', onVisibilityChange);
    }
  });

  return {
    isPlaying,
    isMuted,
    playbackRate,
    videoProgress,
    videoError,
    videoErrorMessage,
    isBuffering,
    isOffline,
    isAutoAdvanceEnabled,
    slideDirection,
    showPlayPauseIcon,
    showHeartAnim,
    heartBurstPos,
    isDragging,
    dragOffset,
    isLongPressing2x,
    currentReel,
    nextReelPreloadUrl,
    cardTransformStyle,
    heartAnimStyle,
    toggleAutoAdvance,
    setAutoAdvance,
    playActiveVideo,
    pauseActiveVideo,
    releaseVideoResources,
    retryVideo,
    nextReel,
    prevReel,
    onTimeUpdate,
    onVideoEnded,
    onVideoError,
    onVideoWaiting,
    onVideoPlaying,
    onVideoCanPlay,
    onPreloadError,
    togglePlay,
    toggleMute,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
    handleTouchCancel,
    handlePointerDown,
    handlePointerMove,
    handlePointerUp,
    handlePointerCancel,
    handleMouseDown,
    handleMouseMove,
    handleMouseUp,
    handleMouseCancel,
    handleCaptureClick,
    handleWheelScroll,
  };
}
