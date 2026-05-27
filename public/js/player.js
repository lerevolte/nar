/**
 * Global Audio Player for Na Repite
 * Handles playback, UI updates, and state management.
 */
class AudioPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentUrl = null;
        this.state = 'stopped'; // stopped, loading, playing, paused
        this.playlist = [];      // For auto-next feature
        this.currentIndex = -1;
        
        // DOM Elements for the Sticky Bottom Player
        this.elements = {
            container: document.getElementById('global-sticky-player'),
            title: document.getElementById('gsp-title'),
            author: document.getElementById('gsp-author'),
            cover: document.getElementById('gsp-cover'),
            playBtn: document.getElementById('gsp-play-btn'),
            playIcon: document.getElementById('gsp-play-icon'),
            pauseIcon: document.getElementById('gsp-pause-icon'),
            loaderIcon: document.getElementById('gsp-loader-icon'),
            progressBar: document.getElementById('gsp-progress-bar'),
            progressFill: document.getElementById('gsp-progress-fill'),
            currentTime: document.getElementById('gsp-current-time'),
            duration: document.getElementById('gsp-duration'),
            closeBtn: document.getElementById('gsp-close-btn')
        };

        this.init();
    }

    init() {
        // Audio Event Listeners
        this.audio.addEventListener('loadstart', () => this.setState('loading'));
        this.audio.addEventListener('waiting', () => this.setState('loading'));
        
        this.audio.addEventListener('canplay', () => {
            if (this.state === 'loading' && !this.audio.paused) {
                 // Waiting for 'playing' event
            }
        });

        this.audio.addEventListener('play', () => this.setState('loading'));
        this.audio.addEventListener('playing', () => this.setState('playing'));
        this.audio.addEventListener('pause', () => this.setState('paused'));
        
        this.audio.addEventListener('timeupdate', () => {
            if (this.state !== 'playing' && !this.audio.paused && this.audio.currentTime > 0) {
                this.setState('playing');
            }
            this.updateProgress();
        });
        
        // Auto-next on track end
        this.audio.addEventListener('ended', () => {
            this.setState('paused');
            this.playNext();
        });

        this.audio.addEventListener('error', (e) => this.handleError(e));

        // Sticky Player Controls
        if (this.elements.playBtn) {
            this.elements.playBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });
        }

        if (this.elements.progressBar) {
            this.elements.progressBar.addEventListener('click', (e) => this.seek(e));
        }

        if (this.elements.closeBtn) {
            this.elements.closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.stop();
            });
        }

        // Global Click Listener for Play Buttons (delegation)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-play-track]');
            if (btn) {
                e.preventDefault();
                const url = btn.dataset.url;
                const title = btn.dataset.title || 'Без названия';
                const author = btn.dataset.author || '';
                const cover = btn.dataset.cover || '🎵';
                
                if (url) {
                    // Build playlist from all visible play buttons
                    this.buildPlaylist();
                    this.play(url, { title, author, cover });
                }
            }
        });
    }

    /**
     * Build playlist from all play buttons on the page
     */
    buildPlaylist() {
        const buttons = document.querySelectorAll('[data-play-track]');
        this.playlist = [];
        buttons.forEach(btn => {
            if (btn.dataset.url) {
                this.playlist.push({
                    url: btn.dataset.url,
                    title: btn.dataset.title || 'Без названия',
                    author: btn.dataset.author || '',
                    cover: btn.dataset.cover || '🎵',
                });
            }
        });
    }

    /**
     * Play next track in playlist
     */
    playNext() {
        if (this.playlist.length === 0) return;

        // Find current track index
        const idx = this.playlist.findIndex(t => t.url === this.currentUrl);
        const nextIdx = idx + 1;

        if (nextIdx < this.playlist.length) {
            const next = this.playlist[nextIdx];
            this.currentUrl = null; // Force new track
            this.play(next.url, next);
        }
    }

    play(url, meta = {}) {
        if (!url) return;

        // Stop any external audio (stem player etc.)
        if (typeof window.stopStemAudio === 'function') {
            window.stopStemAudio();
        }

        // If same track - just toggle
        if (this.currentUrl === url) {
            this.toggle();
            return;
        }

        // New track
        this.currentUrl = url;
        this.audio.src = url;
        
        // Update sticky player metadata
        if (this.elements.title) this.elements.title.textContent = meta.title || 'Трек';
        if (this.elements.author) this.elements.author.textContent = meta.author || '';
        if (this.elements.cover) {
            const c = meta.cover || '🎵';
            if (c.startsWith('http')) {
                this.elements.cover.innerHTML = `<img src="${c}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`;
            } else {
                this.elements.cover.innerHTML = c;
            }
        }
        
        // Show sticky player
        if (this.elements.container) this.elements.container.classList.add('visible');

        this.setState('loading');
        
        const playPromise = this.audio.play();
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.error("Playback prevented:", error);
                this.setState('paused');
            });
        }
    }

    toggle() {
        if (this.audio.paused) {
            // Stop external audio before resuming
            if (typeof window.stopStemAudio === 'function') {
                window.stopStemAudio();
            }
            this.setState('loading');
            this.audio.play().catch(e => {
                console.error(e);
                this.setState('paused');
            });
        } else {
            this.audio.pause();
        }
    }

    stop() {
        this.audio.pause();
        this.audio.currentTime = 0;
        this.currentUrl = null;
        this.setState('stopped');
        if (this.elements.container) this.elements.container.classList.remove('visible');
    }

    /**
     * Stop global player (called from external code like stem player)
     */
    stopForExternal() {
        if (!this.audio.paused) {
            this.audio.pause();
            this.setState('paused');
        }
    }

    seek(event) {
        if (!this.audio.duration) return;
        const rect = this.elements.progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }

    updateProgress() {
        if (!this.audio.duration || isNaN(this.audio.duration)) return;
        
        const percent = (this.audio.currentTime / this.audio.duration) * 100;
        
        if (this.elements.progressFill) this.elements.progressFill.style.width = `${percent}%`;
        if (this.elements.currentTime) this.elements.currentTime.textContent = this.formatTime(this.audio.currentTime);
        if (this.elements.duration) this.elements.duration.textContent = this.formatTime(this.audio.duration);

        const localBars = document.querySelectorAll(`[data-progress-url="${this.currentUrl}"]`);
        localBars.forEach(bar => {
            bar.style.width = `${percent}%`;
        });
    }

    setState(state) {
        if (this.state === 'playing' && state === 'loading' && !this.audio.paused && this.audio.readyState > 2) {
            return; 
        }
        
        this.state = state;
        this.updateUI();
    }

    updateUI() {
        const isPlaying = this.state === 'playing';
        const isLoading = this.state === 'loading';

        // 1. Update Sticky Player Buttons
        if (this.elements.loaderIcon) this.elements.loaderIcon.style.display = isLoading ? 'block' : 'none';
        if (this.elements.playIcon) this.elements.playIcon.style.display = (!isPlaying && !isLoading) ? 'block' : 'none';
        if (this.elements.pauseIcon) this.elements.pauseIcon.style.display = (isPlaying && !isLoading) ? 'block' : 'none';

        // 2. Update ALL buttons on the page
        document.querySelectorAll('[data-play-track]').forEach(btn => {
            const btnUrl = btn.dataset.url;
            const iconPlay = btn.querySelector('.icon-play');
            const iconPause = btn.querySelector('.icon-pause');
            const iconLoad = btn.querySelector('.icon-loading');

            // Reset everyone first
            if (iconPlay) iconPlay.style.display = 'inline-block';
            if (iconPause) iconPause.style.display = 'none';
            if (iconLoad) iconLoad.style.display = 'none';
            btn.classList.remove('active');

            // If this is the current track
            if (btnUrl === this.currentUrl) {
                btn.classList.add('active');
                if (isLoading) {
                    if (iconPlay) iconPlay.style.display = 'none';
                    if (iconPause) iconPause.style.display = 'none';
                    if (iconLoad) iconLoad.style.display = 'inline-block';
                } else if (isPlaying) {
                    if (iconPlay) iconPlay.style.display = 'none';
                    if (iconPause) iconPause.style.display = 'inline-block';
                    if (iconLoad) iconLoad.style.display = 'none';
                }
            }
        });
    }

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    handleError(e) {
        console.error("Audio error:", e);
        this.setState('paused');
    }
}

// Initialize on Load
document.addEventListener('DOMContentLoaded', () => {
    window.player = new AudioPlayer();
});