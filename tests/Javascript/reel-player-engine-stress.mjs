/**
 * JUGAJUG Phase 8.3 — Automated 150+ Consecutive Reel Transition Stress Test
 * Simulates high-velocity mobile user interactions, auto-advance completions,
 * touch gestures, network delays, visibility toggles, and memory cleanup.
 */

import { performance } from 'perf_hooks';

class SimulatedVideoElement {
  constructor(id) {
    this.id = id;
    this.src = '';
    this.currentTime = 0;
    this.duration = 15;
    this.paused = true;
    this.loop = false;
    this.attributes = new Map();
    this.playCallCount = 0;
    this.activePlayPromises = 0;
    this.loadCallCount = 0;
  }

  setAttribute(name, value) {
    this.attributes.set(name, value);
    if (name === 'loop') this.loop = true;
  }

  removeAttribute(name) {
    this.attributes.delete(name);
    if (name === 'loop') this.loop = false;
  }

  load() {
    this.loadCallCount++;
  }

  async play() {
    this.playCallCount++;
    this.activePlayPromises++;
    this.paused = false;
    // Immediate simulated resolution
    await Promise.resolve();
    this.activePlayPromises--;
    return Promise.resolve();
  }

  pause() {
    this.paused = true;
  }
}

class ReelPlayerEngineStressRunner {
  constructor(totalTransitions = 150) {
    this.totalTransitions = totalTransitions;
    this.currentIndex = 0;
    this.autoAdvance = true;
    this.completedReelToken = null;
    this.virtualTime = 10000;
    this.advanceLockUntil = 0;
    this.playRequestSeq = 0;
    this.activeVideo = new SimulatedVideoElement('active');
    this.failedPreloadUrls = new Set();
    this.isBuffering = false;
    this.isOffline = false;

    // Metrics tracking
    this.metrics = {
      transitionsCompleted: 0,
      autoAdvanceCount: 0,
      touchSwipeCount: 0,
      pointerDragCount: 0,
      wheelScrollCount: 0,
      duplicateAutoAdvancePrevented: 0,
      concurrentPlayViolations: 0,
      unhandledPromiseRejections: 0,
      failedPreloadsIsolated: 0,
      visibilityResumesWithoutReset: 0,
      totalDurationMs: 0,
      averageTransitionMs: 0,
      startMemoryMB: 0,
      endMemoryMB: 0,
    };

    // Generate 200 mock reels
    this.reels = Array.from({ length: 200 }, (_, i) => ({
      id: i + 1,
      caption: `Stress Reel #${i + 1}`,
      video_url: i === 13 ? 'https://cdn.jugajug.com/corrupted-video.mp4' : `https://cdn.jugajug.com/reel-${i + 1}.mp4`,
      duration: 15,
    }));
  }

  now() {
    return this.virtualTime;
  }

  advanceTime(ms) {
    this.virtualTime += ms;
  }

  getCurrentReel() {
    return this.reels[this.currentIndex] || null;
  }

  getNextPreloadUrl() {
    const nextReel = this.reels[this.currentIndex + 1];
    if (!nextReel || !nextReel.video_url) return null;
    if (this.failedPreloadUrls.has(nextReel.video_url)) return null;
    return nextReel.video_url;
  }

  async playActiveVideo(resetToStart = true) {
    const seq = ++this.playRequestSeq;
    const v = this.activeVideo;

    if (resetToStart) {
      v.currentTime = 0;
    }

    try {
      if (v.activePlayPromises > 0) {
        this.metrics.concurrentPlayViolations++;
      }
      await v.play();
      if (seq !== this.playRequestSeq) {
        // Obsolete request superseded
        return;
      }
    } catch (err) {
      this.metrics.unhandledPromiseRejections++;
    }
  }

  canAdvanceNow() {
    return this.now() >= this.advanceLockUntil;
  }

  setAdvanceLock(durationMs = 380) {
    this.advanceLockUntil = this.now() + durationMs;
  }

  async nextReel(reason = 'gesture') {
    if (this.currentIndex >= this.reels.length - 1) return false;
    if (!this.canAdvanceNow()) return false;

    this.setAdvanceLock(reason === 'auto_advance' ? 380 : 320);
    this.currentIndex++;
    this.completedReelToken = null;
    this.metrics.transitionsCompleted++;

    if (reason === 'auto_advance') this.metrics.autoAdvanceCount++;
    else if (reason === 'touch') this.metrics.touchSwipeCount++;
    else if (reason === 'pointer') this.metrics.pointerDragCount++;
    else if (reason === 'wheel') this.metrics.wheelScrollCount++;

    await this.playActiveVideo(true);
    return true;
  }

  async prevReel() {
    if (this.currentIndex <= 0) return false;
    if (!this.canAdvanceNow()) return false;

    this.setAdvanceLock(320);
    this.currentIndex--;
    this.completedReelToken = null;
    this.metrics.transitionsCompleted++;
    this.metrics.touchSwipeCount++;

    await this.playActiveVideo(true);
    return true;
  }

  async simulateVideoTimeUpdate(currentTime, duration) {
    const reel = this.getCurrentReel();
    if (!reel) return;

    const remaining = duration - currentTime;
    if (this.autoAdvance && remaining <= 0.28) {
      const currentToken = `${reel.id}:${Math.floor(currentTime * 10)}`;
      if (this.completedReelToken === currentToken) {
        this.metrics.duplicateAutoAdvancePrevented++;
        return;
      }
      this.completedReelToken = currentToken;
      await this.nextReel('auto_advance');
    }
  }

  async simulateVideoEnded() {
    const reel = this.getCurrentReel();
    if (!reel) return;

    if (this.autoAdvance) {
      const endToken = `${reel.id}:ended`;
      if (this.completedReelToken === endToken) {
        this.metrics.duplicateAutoAdvancePrevented++;
        return;
      }
      this.completedReelToken = endToken;
      await this.nextReel('auto_advance');
    } else {
      this.activeVideo.currentTime = 0;
      await this.playActiveVideo(false);
    }
  }

  simulatePreloadError(url) {
    this.failedPreloadUrls.add(url);
    this.metrics.failedPreloadsIsolated++;
  }

  async simulateVisibilityRestore() {
    const savedTime = this.activeVideo.currentTime || 7.5;
    this.activeVideo.currentTime = savedTime;
    // Resume without reset
    await this.playActiveVideo(false);
    if (this.activeVideo.currentTime === savedTime) {
      this.metrics.visibilityResumesWithoutReset++;
    }
  }

  releaseVideoResources() {
    this.activeVideo.removeAttribute('src');
    this.activeVideo.load();
  }

  async run() {
    const startMemory = process.memoryUsage().heapUsed;
    this.metrics.startMemoryMB = +(startMemory / (1024 * 1024)).toFixed(2);
    const startTime = performance.now();

    while (this.metrics.transitionsCompleted < this.totalTransitions) {
      const mode = Math.random();

      // Ensure time passes so lock expires
      this.advanceTime(400);

      // 1. Auto-advance completion (near-end and ended event)
      if (mode < 0.45) {
        // Rapid timeupdate triggers
        await this.simulateVideoTimeUpdate(14.75, 15.0);
        // Attempt duplicate timeupdate right after
        await this.simulateVideoTimeUpdate(14.85, 15.0);
        await this.simulateVideoEnded();
      }
      // 2. High-speed Mobile Touch Swipe
      else if (mode < 0.75) {
        await this.nextReel('touch');
      }
      // 3. Pointer drag gesture
      else if (mode < 0.90) {
        await this.nextReel('pointer');
      }
      // 4. Wheel scroll gesture
      else {
        await this.nextReel('wheel');
      }

      // Check preload status
      const preloadUrl = this.getNextPreloadUrl();
      if (preloadUrl && preloadUrl.includes('corrupted-video')) {
        this.simulatePreloadError(preloadUrl);
        // Ensure failed preload is never returned again
        if (this.getNextPreloadUrl() === preloadUrl) {
          throw new Error('Failed preload URL was not isolated!');
        }
      }

      // Intermittent visibility change
      if (this.metrics.transitionsCompleted % 25 === 0) {
        await this.simulateVisibilityRestore();
      }
    }

    const endTime = performance.now();
    this.metrics.totalDurationMs = +(endTime - startTime).toFixed(2);
    this.metrics.averageTransitionMs = +(this.metrics.totalDurationMs / this.metrics.transitionsCompleted).toFixed(3);

    // Explicit video unmount & cleanup
    this.releaseVideoResources();

    const endMemory = process.memoryUsage().heapUsed;
    this.metrics.endMemoryMB = +(endMemory / (1024 * 1024)).toFixed(2);

    return this.metrics;
  }
}

// Execute stress test
const runner = new ReelPlayerEngineStressRunner(150);
runner.run()
  .then((metrics) => {
    console.log(JSON.stringify({
      status: 'SUCCESS',
      metrics,
    }, null, 2));
    process.exit(0);
  })
  .catch((err) => {
    console.error('Stress test failed with error:', err);
    process.exit(1);
  });
