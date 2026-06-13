/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function formatTime(rawSeconds) {
        const total = Number.isFinite(rawSeconds) && rawSeconds > 0 ? Math.floor(rawSeconds) : 0;
        const minutes = Math.floor(total / 60);
        const seconds = total % 60;
        return `${minutes}:${String(seconds).padStart(2, '0')}`;
    }

    function resolvePlayerRoots(root) {
        if (root instanceof HTMLElement && root.matches('[data-video-player]')) {
            return [root];
        }
        if (root && typeof root.querySelectorAll === 'function') {
            return Array.from(root.querySelectorAll('[data-video-player]'));
        }
        return Array.from(document.querySelectorAll('[data-video-player]'));
    }

    function updateButton(button, label, icon) {
        if (!(button instanceof HTMLElement)) {
            return;
        }
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        const iconNode = button.querySelector('span[aria-hidden="true"]');
        if (iconNode) {
            iconNode.textContent = icon;
        }
    }

    function initPlayer(player) {
        if (!(player instanceof HTMLElement) || player.dataset.videoPlayerReady === '1') {
            return;
        }

        const shell = player.querySelector('[data-video-player-shell]');
        const video = player.querySelector('video');
        const bigPlay = player.querySelector('[data-video-player-big-play]');
        const toggle = player.querySelector('[data-video-player-toggle]');
        const seek = player.querySelector('[data-video-player-seek]');
        const current = player.querySelector('[data-video-player-current]');
        const duration = player.querySelector('[data-video-player-duration]');
        const mute = player.querySelector('[data-video-player-mute]');
        const volume = player.querySelector('[data-video-player-volume]');
        const fullscreen = player.querySelector('[data-video-player-fullscreen]');
        const isAmbient = player.dataset.videoPlayerAmbient === '1';

        if (!(shell instanceof HTMLElement) || !(video instanceof HTMLVideoElement)) {
            return;
        }

        player.dataset.videoPlayerReady = '1';
        shell.classList.add('is-enhanced');
        shell.classList.toggle('is-ambient', isAmbient);
        video.controls = false;

        const labels = {
            play: player.dataset.labelPlay || '',
            pause: player.dataset.labelPause || '',
            mute: player.dataset.labelMute || '',
            unmute: player.dataset.labelUnmute || '',
            fullscreen: player.dataset.labelFullscreen || '',
            exitFullscreen: player.dataset.labelExitFullscreen || '',
        };

        const syncDuration = function() {
            if (duration instanceof HTMLElement) {
                duration.textContent = formatTime(video.duration);
            }
            if (seek instanceof HTMLInputElement) {
                seek.max = Number.isFinite(video.duration) && video.duration > 0 ? String(video.duration) : '100';
            }
        };

        const syncProgress = function() {
            if (current instanceof HTMLElement) {
                current.textContent = formatTime(video.currentTime);
            }
            if (seek instanceof HTMLInputElement) {
                seek.value = String(Number.isFinite(video.currentTime) ? video.currentTime : 0);
            }
        };

        const syncPlayState = function() {
            const isPlaying = !video.paused && !video.ended;
            shell.classList.toggle('is-playing', isPlaying);
            updateButton(toggle, isPlaying ? labels.pause : labels.play, isPlaying ? '❚❚' : '▶');
            updateButton(bigPlay, labels.play, '▶');
        };

        const syncMuteState = function() {
            const isMuted = video.muted || video.volume === 0;
            updateButton(mute, isMuted ? labels.unmute : labels.mute, isMuted ? '🔇' : '🔊');
            if (volume instanceof HTMLInputElement) {
                volume.value = String(isMuted ? 0 : video.volume);
            }
        };

        const syncFullscreenState = function() {
            const isFullscreen = document.fullscreenElement === shell;
            updateButton(fullscreen, isFullscreen ? labels.exitFullscreen : labels.fullscreen, isFullscreen ? '⤡' : '⤢');
        };

        const togglePlayback = function() {
            if (video.paused || video.ended) {
                const promise = video.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(function() {});
                }
                return;
            }
            video.pause();
        };

        const toggleFullscreen = function() {
            if (!document.fullscreenElement && typeof shell.requestFullscreen === 'function') {
                shell.requestFullscreen().catch(function() {});
                return;
            }
            if (document.fullscreenElement && typeof document.exitFullscreen === 'function') {
                document.exitFullscreen().catch(function() {});
            }
        };

        const ensureAmbientPlayback = function() {
            if (!isAmbient) {
                return;
            }
            video.muted = true;
            const promise = video.play();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(function() {});
            }
        };

        if (!isAmbient && bigPlay instanceof HTMLButtonElement) {
            bigPlay.addEventListener('click', function(event) {
                event.preventDefault();
                togglePlayback();
            });
        }

        if (!isAmbient && toggle instanceof HTMLButtonElement) {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                togglePlayback();
            });
        }

        if (!isAmbient && mute instanceof HTMLButtonElement) {
            mute.addEventListener('click', function(event) {
                event.preventDefault();
                video.muted = !(video.muted || video.volume === 0);
                if (!video.muted && video.volume === 0) {
                    video.volume = 0.8;
                }
                syncMuteState();
            });
        }

        if (!isAmbient && volume instanceof HTMLInputElement) {
            volume.addEventListener('input', function() {
                const nextVolume = Math.max(0, Math.min(1, parseFloat(volume.value) || 0));
                video.volume = nextVolume;
                video.muted = nextVolume === 0;
                syncMuteState();
            });
        }

        if (!isAmbient && seek instanceof HTMLInputElement) {
            seek.addEventListener('input', function() {
                if (!Number.isFinite(video.duration) || video.duration <= 0) {
                    return;
                }
                video.currentTime = Math.max(0, Math.min(video.duration, parseFloat(seek.value) || 0));
                syncProgress();
            });
        }

        if (!isAmbient && fullscreen instanceof HTMLButtonElement) {
            fullscreen.addEventListener('click', function(event) {
                event.preventDefault();
                toggleFullscreen();
            });
        }

        if (!isAmbient) {
            video.addEventListener('click', togglePlayback);
        }
        video.addEventListener('loadedmetadata', function() {
            syncDuration();
            syncProgress();
            syncMuteState();
            ensureAmbientPlayback();
        });
        video.addEventListener('timeupdate', syncProgress);
        video.addEventListener('durationchange', syncDuration);
        video.addEventListener('play', syncPlayState);
        video.addEventListener('pause', syncPlayState);
        video.addEventListener('ended', syncPlayState);
        video.addEventListener('volumechange', syncMuteState);
        document.addEventListener('fullscreenchange', syncFullscreenState);

        syncDuration();
        syncProgress();
        syncPlayState();
        syncMuteState();
        syncFullscreenState();
        ensureAmbientPlayback();
    }

    function init(root) {
        resolvePlayerRoots(root).forEach(initPlayer);
    }

    window.FlatCMSVideoPlayer = window.FlatCMSVideoPlayer || {};
    window.FlatCMSVideoPlayer.init = init;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init(document);
        });
    } else {
        init(document);
    }
})();
