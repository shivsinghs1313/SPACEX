/* ============================================
   SPACEX — Premium Video Player
   Custom HTML5 player with DRM-lite protections
   ============================================ */

(function () {
  'use strict';

  const API_BASE = 'backend/api';

  class SpacexVideoPlayer {
    constructor(containerSelector) {
      this.container = document.querySelector(containerSelector);
      if (!this.container) return;

      this.video = null;
      this.currentLessonId = null;
      this.currentToken = null;
      this.progressInterval = null;
      this.resumePosition = 0;
      this.isPlaying = false;
      this.isSeeking = false;

      this.init();
    }

    init() {
      this.render();
      this.bindEvents();
      this.bindKeyboardShortcuts();
    }

    render() {
      this.container.innerHTML = `
        <div class="svp-wrapper" id="svpWrapper">
          <!-- Video Element -->
          <video class="svp-video" id="svpVideo" preload="metadata" playsinline>
            Your browser does not support HTML5 video.
          </video>

          <!-- Loading Overlay -->
          <div class="svp-loading" id="svpLoading">
            <div class="svp-spinner"></div>
            <p>Loading lesson...</p>
          </div>

          <!-- Purchase Required Overlay -->
          <div class="svp-locked" id="svpLocked" style="display:none;">
            <div class="svp-locked-icon">🔒</div>
            <h4>Course Purchase Required</h4>
            <p>Unlock all 30 lessons with lifetime access</p>
            <button class="btn btn-primary btn-sm" id="svpBuyBtn">
              Enroll Now — ₹4,999
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
            </button>
          </div>

          <!-- Idle / Select Lesson Overlay -->
          <div class="svp-idle" id="svpIdle">
            <div class="svp-idle-icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5">
                <polygon points="5 3 19 12 5 21 5 3" fill="rgba(0,255,136,0.15)" stroke="#00FF88"/>
              </svg>
            </div>
            <p>Select a lesson to start watching</p>
          </div>

          <!-- Big Play Button Overlay -->
          <div class="svp-big-play" id="svpBigPlay" style="display:none;">
            <button class="svp-big-play-btn" id="svpBigPlayBtn">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5,3 19,12 5,21"/>
              </svg>
            </button>
          </div>

          <!-- Controls Bar -->
          <div class="svp-controls" id="svpControls">
            <!-- Progress Bar -->
            <div class="svp-progress-wrapper" id="svpProgressWrapper">
              <div class="svp-progress-bar">
                <div class="svp-progress-buffered" id="svpBuffered"></div>
                <div class="svp-progress-played" id="svpPlayed"></div>
                <div class="svp-progress-thumb" id="svpThumb"></div>
              </div>
              <div class="svp-progress-tooltip" id="svpTooltip">0:00</div>
            </div>

            <div class="svp-controls-row">
              <!-- Left Controls -->
              <div class="svp-controls-left">
                <button class="svp-btn" id="svpPlayPause" title="Play/Pause (Space)">
                  <svg class="play-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5,3 19,12 5,21"/>
                  </svg>
                  <svg class="pause-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                    <rect x="5" y="3" width="4" height="18" rx="1"/>
                    <rect x="15" y="3" width="4" height="18" rx="1"/>
                  </svg>
                </button>

                <button class="svp-btn" id="svpSkipBack" title="Back 10s (←)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                  </svg>
                  <span class="svp-skip-text">10</span>
                </button>

                <button class="svp-btn" id="svpSkipForward" title="Forward 10s (→)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.13-9.36L23 10"/>
                  </svg>
                  <span class="svp-skip-text">10</span>
                </button>

                <!-- Volume -->
                <div class="svp-volume-group">
                  <button class="svp-btn" id="svpMute" title="Mute (M)">
                    <svg class="vol-on" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                      <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
                    </svg>
                    <svg class="vol-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                      <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                      <line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/>
                    </svg>
                  </button>
                  <input type="range" class="svp-volume-slider" id="svpVolume" min="0" max="1" step="0.05" value="1">
                </div>

                <span class="svp-time" id="svpTime">0:00 / 0:00</span>
              </div>

              <!-- Right Controls -->
              <div class="svp-controls-right">
                <!-- Speed -->
                <div class="svp-speed-group">
                  <button class="svp-btn svp-speed-btn" id="svpSpeedBtn" title="Playback Speed">
                    <span id="svpSpeedLabel">1x</span>
                  </button>
                  <div class="svp-speed-menu" id="svpSpeedMenu">
                    <button class="svp-speed-option" data-speed="0.5">0.5x</button>
                    <button class="svp-speed-option" data-speed="0.75">0.75x</button>
                    <button class="svp-speed-option active" data-speed="1">1x</button>
                    <button class="svp-speed-option" data-speed="1.25">1.25x</button>
                    <button class="svp-speed-option" data-speed="1.5">1.5x</button>
                    <button class="svp-speed-option" data-speed="2">2x</button>
                  </div>
                </div>

                <!-- PiP -->
                <button class="svp-btn" id="svpPip" title="Picture-in-Picture (P)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <rect x="11" y="10" width="9" height="6" rx="1" fill="rgba(0,255,136,0.3)"/>
                  </svg>
                </button>

                <!-- Fullscreen -->
                <button class="svp-btn" id="svpFullscreen" title="Fullscreen (F)">
                  <svg class="fs-enter" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                  </svg>
                  <svg class="fs-exit" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                    <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Now Playing Info -->
        <div class="svp-now-playing" id="svpNowPlaying">
          <div class="svp-np-badge">
            <span class="svp-np-dot"></span>
            Now Playing
          </div>
          <h5 class="svp-np-title" id="svpNpTitle">Select a lesson</h5>
        </div>
      `;

      this.video = document.getElementById('svpVideo');
      this.wrapper = document.getElementById('svpWrapper');
      this.controls = document.getElementById('svpControls');
    }

    bindEvents() {
      const v = this.video;
      if (!v) return;

      // Play/Pause
      document.getElementById('svpPlayPause').addEventListener('click', () => this.togglePlay());
      document.getElementById('svpBigPlayBtn')?.addEventListener('click', () => this.togglePlay());

      // Video click to toggle play
      v.addEventListener('click', () => this.togglePlay());

      // Skip buttons
      document.getElementById('svpSkipBack').addEventListener('click', () => this.skip(-10));
      document.getElementById('svpSkipForward').addEventListener('click', () => this.skip(10));

      // Volume
      document.getElementById('svpMute').addEventListener('click', () => this.toggleMute());
      document.getElementById('svpVolume').addEventListener('input', (e) => {
        v.volume = parseFloat(e.target.value);
        v.muted = false;
        this.updateVolumeIcon();
      });

      // Progress bar seeking
      const progressWrapper = document.getElementById('svpProgressWrapper');
      progressWrapper.addEventListener('click', (e) => this.seekFromClick(e));
      progressWrapper.addEventListener('mousemove', (e) => this.showTooltip(e));
      progressWrapper.addEventListener('mouseleave', () => {
        document.getElementById('svpTooltip').style.opacity = '0';
      });

      // Speed control
      document.getElementById('svpSpeedBtn').addEventListener('click', (e) => {
        e.stopPropagation();
        document.getElementById('svpSpeedMenu').classList.toggle('active');
      });

      document.querySelectorAll('.svp-speed-option').forEach(btn => {
        btn.addEventListener('click', () => {
          const speed = parseFloat(btn.dataset.speed);
          v.playbackRate = speed;
          document.getElementById('svpSpeedLabel').textContent = speed + 'x';
          document.querySelectorAll('.svp-speed-option').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          document.getElementById('svpSpeedMenu').classList.remove('active');
        });
      });

      // Close speed menu on outside click
      document.addEventListener('click', () => {
        document.getElementById('svpSpeedMenu')?.classList.remove('active');
      });

      // PiP
      document.getElementById('svpPip').addEventListener('click', () => this.togglePip());

      // Fullscreen
      document.getElementById('svpFullscreen').addEventListener('click', () => this.toggleFullscreen());

      // Video events
      v.addEventListener('timeupdate', () => this.updateProgress());
      v.addEventListener('loadedmetadata', () => this.onLoaded());
      v.addEventListener('play', () => this.onPlay());
      v.addEventListener('pause', () => this.onPause());
      v.addEventListener('ended', () => this.onEnded());
      v.addEventListener('waiting', () => this.showLoading(true));
      v.addEventListener('canplay', () => this.showLoading(false));
      v.addEventListener('progress', () => this.updateBuffered());

      // Fullscreen change
      document.addEventListener('fullscreenchange', () => this.onFullscreenChange());

      // Controls auto-hide
      let hideTimer;
      this.wrapper.addEventListener('mousemove', () => {
        this.controls.classList.add('visible');
        clearTimeout(hideTimer);
        if (this.isPlaying) {
          hideTimer = setTimeout(() => this.controls.classList.remove('visible'), 3000);
        }
      });

      // Anti-download protections
      v.addEventListener('contextmenu', (e) => e.preventDefault());
      v.setAttribute('controlsList', 'nodownload noremoteplayback');

      // Purchase button in locked overlay
      document.getElementById('svpBuyBtn')?.addEventListener('click', () => {
        if (typeof SpacexPayment !== 'undefined') {
          SpacexPayment.initCheckout(1); // Default course ID 1
        }
      });
    }

    bindKeyboardShortcuts() {
      document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (!this.video || !this.currentLessonId) return;

        switch (e.key.toLowerCase()) {
          case ' ':
          case 'k':
            e.preventDefault();
            this.togglePlay();
            break;
          case 'arrowleft':
          case 'j':
            e.preventDefault();
            this.skip(-10);
            break;
          case 'arrowright':
          case 'l':
            e.preventDefault();
            this.skip(10);
            break;
          case 'f':
            e.preventDefault();
            this.toggleFullscreen();
            break;
          case 'm':
            e.preventDefault();
            this.toggleMute();
            break;
          case 'p':
            e.preventDefault();
            this.togglePip();
            break;
          case 'arrowup':
            e.preventDefault();
            this.video.volume = Math.min(1, this.video.volume + 0.1);
            document.getElementById('svpVolume').value = this.video.volume;
            break;
          case 'arrowdown':
            e.preventDefault();
            this.video.volume = Math.max(0, this.video.volume - 0.1);
            document.getElementById('svpVolume').value = this.video.volume;
            break;
        }
      });
    }

    // ---- Load a lesson ----
    async loadLesson(lessonId) {
      this.currentLessonId = lessonId;
      this.showLoading(true);
      this.hideIdle();
      this.hideLocked();

      const token = localStorage.getItem('spacex_token');
      if (!token) {
        this.showLocked();
        return;
      }

      try {
        const response = await fetch(`${API_BASE}/stream.php?action=token&lesson_id=${lessonId}`, {
          headers: { 'Authorization': `Bearer ${token}` },
        });

        const data = await response.json();

        if (!data.success) {
          if (response.status === 403) {
            this.showLocked();
          } else {
            this.showError(data.message || 'Failed to load video');
          }
          return;
        }

        const videoData = data.data;
        this.currentToken = videoData.token;
        this.resumePosition = videoData.resume_at || 0;

        // Update now playing
        document.getElementById('svpNpTitle').textContent = videoData.title;

        // Set video source — use stream URL
        this.video.src = videoData.stream_url;
        this.video.load();

        // Show big play button
        document.getElementById('svpBigPlay').style.display = 'flex';

        this.showLoading(false);

        // Save to localStorage for resume
        localStorage.setItem('spacex_last_lesson', lessonId);

      } catch (err) {
        console.error('Load lesson error:', err);
        this.showError('Failed to load lesson. Please try again.');
      }
    }

    // ---- Player Controls ----
    togglePlay() {
      if (!this.video.src) return;
      if (this.video.paused) {
        this.video.play().catch(() => {});
      } else {
        this.video.pause();
      }
    }

    skip(seconds) {
      if (!this.video.duration) return;
      this.video.currentTime = Math.max(0, Math.min(this.video.duration, this.video.currentTime + seconds));
    }

    toggleMute() {
      this.video.muted = !this.video.muted;
      this.updateVolumeIcon();
      document.getElementById('svpVolume').value = this.video.muted ? 0 : this.video.volume;
    }

    toggleFullscreen() {
      if (document.fullscreenElement) {
        document.exitFullscreen();
      } else {
        this.wrapper.requestFullscreen();
      }
    }

    async togglePip() {
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture();
        } else if (this.video.src) {
          await this.video.requestPictureInPicture();
        }
      } catch (err) {
        console.warn('PiP not supported');
      }
    }

    // ---- Event Handlers ----
    onLoaded() {
      this.showLoading(false);
      if (this.resumePosition > 0 && this.resumePosition < this.video.duration * 0.95) {
        this.video.currentTime = this.resumePosition;
      }
      this.updateProgress();
    }

    onPlay() {
      this.isPlaying = true;
      document.querySelector('.play-icon').style.display = 'none';
      document.querySelector('.pause-icon').style.display = 'block';
      document.getElementById('svpBigPlay').style.display = 'none';
      this.controls.classList.add('visible');

      // Start progress tracking
      this.startProgressTracking();
    }

    onPause() {
      this.isPlaying = false;
      document.querySelector('.play-icon').style.display = 'block';
      document.querySelector('.pause-icon').style.display = 'none';
      document.getElementById('svpBigPlay').style.display = 'flex';
      this.controls.classList.add('visible');

      // Save progress
      this.saveProgress();
    }

    onEnded() {
      this.isPlaying = false;
      this.markCompleted();
      document.querySelector('.play-icon').style.display = 'block';
      document.querySelector('.pause-icon').style.display = 'none';
    }

    onFullscreenChange() {
      const isFS = !!document.fullscreenElement;
      document.querySelector('.fs-enter').style.display = isFS ? 'none' : 'block';
      document.querySelector('.fs-exit').style.display = isFS ? 'block' : 'none';
    }

    // ---- Progress Updates ----
    updateProgress() {
      if (!this.video.duration) return;

      const percent = (this.video.currentTime / this.video.duration) * 100;
      document.getElementById('svpPlayed').style.width = percent + '%';
      document.getElementById('svpThumb').style.left = percent + '%';
      document.getElementById('svpTime').textContent =
        `${this.formatTime(this.video.currentTime)} / ${this.formatTime(this.video.duration)}`;

      // Auto-mark complete at 90%
      if (percent >= 90 && !this._markedComplete) {
        this._markedComplete = true;
        this.markCompleted();
      }
    }

    updateBuffered() {
      if (!this.video.buffered.length) return;
      const bufferedEnd = this.video.buffered.end(this.video.buffered.length - 1);
      const percent = (bufferedEnd / this.video.duration) * 100;
      document.getElementById('svpBuffered').style.width = percent + '%';
    }

    updateVolumeIcon() {
      const on = document.querySelector('.vol-on');
      const off = document.querySelector('.vol-off');
      if (this.video.muted || this.video.volume === 0) {
        on.style.display = 'none';
        off.style.display = 'block';
      } else {
        on.style.display = 'block';
        off.style.display = 'none';
      }
    }

    seekFromClick(e) {
      const bar = document.getElementById('svpProgressWrapper');
      const rect = bar.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      this.video.currentTime = percent * this.video.duration;
    }

    showTooltip(e) {
      if (!this.video.duration) return;
      const bar = document.getElementById('svpProgressWrapper');
      const rect = bar.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      const time = percent * this.video.duration;
      const tooltip = document.getElementById('svpTooltip');
      tooltip.textContent = this.formatTime(time);
      tooltip.style.left = (percent * 100) + '%';
      tooltip.style.opacity = '1';
    }

    // ---- Progress Tracking (API) ----
    startProgressTracking() {
      this.stopProgressTracking();
      this.progressInterval = setInterval(() => {
        this.saveProgress();
      }, 30000); // Every 30 seconds
    }

    stopProgressTracking() {
      if (this.progressInterval) {
        clearInterval(this.progressInterval);
        this.progressInterval = null;
      }
    }

    async saveProgress() {
      if (!this.currentLessonId || !this.video.currentTime) return;

      const token = localStorage.getItem('spacex_token');
      if (!token) return;

      // Save to localStorage first (offline-friendly)
      localStorage.setItem(`spacex_progress_${this.currentLessonId}`, this.video.currentTime);

      try {
        await fetch(`${API_BASE}/progress.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            lesson_id: this.currentLessonId,
            watch_time: Math.floor(this.video.currentTime),
            completed: false,
          }),
        });
      } catch (err) {
        // Silent fail — progress saved locally
      }
    }

    async markCompleted() {
      if (!this.currentLessonId) return;

      const token = localStorage.getItem('spacex_token');
      if (!token) return;

      try {
        await fetch(`${API_BASE}/progress.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            lesson_id: this.currentLessonId,
            watch_time: Math.floor(this.video.currentTime),
            completed: true,
          }),
        });

        // Update UI — mark lesson as completed in sidebar
        const lessonItem = document.querySelector(`.lesson-item[data-lesson-id="${this.currentLessonId}"]`);
        if (lessonItem && !lessonItem.classList.contains('completed')) {
          lessonItem.classList.add('completed');
          const check = lessonItem.querySelector('.lesson-check');
          if (check) {
            check.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
            check.style.background = 'var(--color-neon)';
            check.style.borderColor = 'var(--color-neon)';
            check.style.color = 'var(--color-bg-primary)';
          }
        }

        // Show toast
        if (typeof SpacexPayment !== 'undefined') {
          SpacexPayment.showToast('Lesson completed! 🎉', 'success');
        }
      } catch (err) {
        console.error('Mark completed error:', err);
      }
    }

    // ---- UI State ----
    showLoading(show) {
      const el = document.getElementById('svpLoading');
      if (el) el.style.display = show ? 'flex' : 'none';
    }

    hideIdle() {
      const el = document.getElementById('svpIdle');
      if (el) el.style.display = 'none';
    }

    showLocked() {
      this.showLoading(false);
      document.getElementById('svpLocked').style.display = 'flex';
      document.getElementById('svpIdle').style.display = 'none';
    }

    hideLocked() {
      document.getElementById('svpLocked').style.display = 'none';
    }

    showError(message) {
      this.showLoading(false);
      const idle = document.getElementById('svpIdle');
      if (idle) {
        idle.style.display = 'flex';
        idle.querySelector('p').textContent = message;
      }
    }

    // ---- Utilities ----
    formatTime(seconds) {
      if (isNaN(seconds)) return '0:00';
      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      const s = Math.floor(seconds % 60);
      const pad = (n) => n.toString().padStart(2, '0');
      return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
    }

    // ---- Destroy ----
    destroy() {
      this.stopProgressTracking();
      if (this.video) {
        this.video.pause();
        this.video.src = '';
      }
    }
  }

  // ---- Initialize on dashboard ----
  window.SpacexVideoPlayer = SpacexVideoPlayer;

  // Auto-init if on dashboard page
  document.addEventListener('DOMContentLoaded', () => {
    const playerContainer = document.querySelector('.video-player');
    if (playerContainer) {
      window.spacexPlayer = new SpacexVideoPlayer('.video-player');

      // Wire lesson items to load video
      document.querySelectorAll('.lesson-item').forEach(item => {
        item.addEventListener('click', (e) => {
          // Don't trigger if clicking the check button
          if (e.target.closest('.lesson-check')) return;

          const lessonId = item.getAttribute('data-lesson-id');
          if (lessonId && window.spacexPlayer) {
            // Highlight active lesson
            document.querySelectorAll('.lesson-item').forEach(l => l.classList.remove('active'));
            item.classList.add('active');

            // Load the video
            window.spacexPlayer.loadLesson(parseInt(lessonId));

            // Update header
            const header = item.closest('.lessons-section')?.querySelector('h4');
            const videoHeader = document.querySelector('.video-player-header h5');
            if (videoHeader && header) {
              videoHeader.textContent = header.textContent;
            }
          }
        });
      });

      // Auto-load last watched lesson
      const lastLesson = localStorage.getItem('spacex_last_lesson');
      if (lastLesson) {
        const item = document.querySelector(`.lesson-item[data-lesson-id="${lastLesson}"]`);
        if (item) item.click();
      }
    }
  });

})();
