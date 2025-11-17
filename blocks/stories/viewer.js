/**
 * Front-end controller for the Stories block.
 *
 * Fetches the OpenStories-compatible feed and drives the interactive viewer
 * experience (playback, navigation, accessibility helpers).
 */

const DEFAULT_IMAGE_DURATION_MS = 5000;
const CONTENT_WARNING_DELAY_MS = 3000;

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const getDurationMs = (story) => {
    const raw = story?._open_stories?.duration_in_seconds;
    const seconds = Number.isFinite(raw) ? raw : Number(raw);
    const validSeconds = Number.isFinite(seconds) && seconds > 0 ? seconds : null;
    return (validSeconds ?? DEFAULT_IMAGE_DURATION_MS / 1000) * 1000;
};

const shouldAutoAdvance = () => !prefersReducedMotion.matches;

const buildProgressBar = (container, duration) => {
    container.innerHTML = '';
    if (!shouldAutoAdvance()) {
        return null;
    }
    const bar = document.createElement('div');
    bar.className = 'story-progress-bar';
    container.appendChild(bar);

    // Kick off the animation on the next frame.
    requestAnimationFrame(() => {
        bar.style.transition = `width ${duration}ms linear`;
        bar.style.width = '100%';
    });

    return bar;
};

const withContentWarning = (block, message, proceed) => {
    const warning = document.createElement('div');
    warning.className = 'content-warning';
    warning.textContent = message;

    if (prefersReducedMotion.matches) {
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'content-warning__continue';
        action.textContent = block.dataset.continueLabel || 'Continue';
        action.addEventListener('click', () => {
            warning.remove();
            proceed();
        });
        warning.appendChild(action);
    } else {
        setTimeout(() => {
            warning.remove();
            proceed();
        }, CONTENT_WARNING_DELAY_MS);
    }

    return warning;
};

const initStoriesBlock = (block) => {
    const feedUrl = block.dataset.feed;
    if (!feedUrl) {
        return;
    }

    const storiesContainer = block.querySelector('.stories-container');
    const viewer = block.querySelector('.story-viewer');
    const viewerContent = block.querySelector('.story-viewer-content');
    const mediaContainer = block.querySelector('.story-media');
    const progressContainer = block.querySelector('.story-progress');
    const closeBtn = block.querySelector('.story-close');
    const prevBtn = block.querySelector('.story-prev');
    const nextBtn = block.querySelector('.story-next');

    if (!storiesContainer || !viewer || !viewerContent || !mediaContainer || !progressContainer) {
        return;
    }

    let stories = [];
    let currentIndex = -1;
    let progressTimer = null;
    let keydownHandler = null;
    let previouslyFocusedElement = null;

    const resetProgress = () => {
        if (progressTimer) {
            clearTimeout(progressTimer);
            progressTimer = null;
        }
        progressContainer.innerHTML = '';
    };

    const closeViewer = () => {
        resetProgress();
        mediaContainer.innerHTML = '';
        viewer.classList.remove('is-visible');
        viewer.setAttribute('hidden', 'hidden');
        viewerContent.setAttribute('aria-hidden', 'true');
        if (keydownHandler) {
            document.removeEventListener('keydown', keydownHandler);
            keydownHandler = null;
        }
        if (previouslyFocusedElement instanceof HTMLElement) {
            previouslyFocusedElement.focus();
        }
        previouslyFocusedElement = null;
    };

    const showNextStory = () => {
        if (currentIndex < stories.length - 1) {
            showStory(currentIndex + 1);
        } else {
            closeViewer();
        }
    };

    const showPrevStory = () => {
        if (currentIndex > 0) {
            showStory(currentIndex - 1);
        }
    };

    const updateNavigationState = () => {
        prevBtn?.toggleAttribute('disabled', currentIndex <= 0);
        nextBtn?.toggleAttribute('disabled', currentIndex >= stories.length - 1);
    };

    const startAutoAdvance = (duration) => {
        if (!shouldAutoAdvance()) {
            return;
        }
        progressTimer = setTimeout(() => {
            showNextStory();
        }, duration);
    };

    const playVideo = (story) => {
        const source = story?._open_stories?.url;
        if (!source) {
            return;
        }

        const video = document.createElement('video');
        video.src = source;
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.controls = false;
        video.autoplay = true;
        video.muted = true;

        const begin = () => {
            const duration = Number.isFinite(video.duration) && video.duration > 0
                ? video.duration * 1000
                : getDurationMs(story);
            buildProgressBar(progressContainer, duration);
            startAutoAdvance(duration);

            const attemptPlay = video.play();
            if (attemptPlay) {
                attemptPlay.catch(() => {
                    video.muted = true;
                    video.play().catch(() => {});
                });
            }
        };

        if (video.readyState >= 1) {
            begin();
        } else {
            video.addEventListener('loadedmetadata', begin, { once: true });
        }

        video.addEventListener('ended', () => {
            if (shouldAutoAdvance()) {
                showNextStory();
            }
        });

        mediaContainer.appendChild(video);
    };

    const showImage = (story) => {
        const source = story?._open_stories?.url;
        if (!source) {
            return;
        }

        const image = document.createElement('img');
        image.src = source;
        image.alt = story?._open_stories?.alt || story?.title || '';
        image.loading = 'lazy';
        image.decoding = 'async';
        mediaContainer.appendChild(image);

        const duration = getDurationMs(story);
        buildProgressBar(progressContainer, duration);
        startAutoAdvance(duration);
    };

    const displayStoryContent = (story) => {
        resetProgress();
        mediaContainer.innerHTML = '';

        const mimeType = story?._open_stories?.mime_type || '';
        if (mimeType.startsWith('video/')) {
            playVideo(story);
        } else {
            showImage(story);
        }
    };

    const showStory = (index) => {
        if (index < 0 || index >= stories.length) {
            return;
        }

        currentIndex = index;
        const story = stories[index];
        const warning = story?._open_stories?.content_warning;

        mediaContainer.innerHTML = '';
        resetProgress();

        const proceed = () => displayStoryContent(story);

        if (warning) {
            const warningElement = withContentWarning(block, warning, proceed);
            mediaContainer.appendChild(warningElement);
            progressContainer.innerHTML = '';
        } else {
            proceed();
        }

        previouslyFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        viewer.classList.add('is-visible');
        viewer.removeAttribute('hidden');
        viewerContent.setAttribute('aria-hidden', 'false');
        if (typeof viewerContent.focus === 'function') {
            try {
                viewerContent.focus({ preventScroll: true });
            } catch (error) {
                viewerContent.focus();
            }
        }
        updateNavigationState();

        if (!keydownHandler) {
            keydownHandler = (event) => {
                if (!viewer.classList.contains('is-visible')) {
                    return;
                }
                switch (event.key) {
                    case 'ArrowLeft':
                        event.preventDefault();
                        showPrevStory();
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        showNextStory();
                        break;
                    case 'Escape':
                        event.preventDefault();
                        closeViewer();
                        break;
                    default:
                        break;
                }
            };
            document.addEventListener('keydown', keydownHandler);
        }
    };

    const handlePreviewClick = (event) => {
        const target = event.target.closest('[data-story-id]');
        if (!target || target.disabled) {
            return;
        }

        const storyId = target.dataset.storyId;
        const index = stories.findIndex((story) => String(story.id) === String(storyId));
        if (index === -1) {
            return;
        }

        showStory(index);
    };

    const alignPreviews = () => {
        const previews = storiesContainer.querySelectorAll('[data-story-id]');
        previews.forEach((preview) => {
            const hasStory = stories.some((story) => String(story.id) === preview.dataset.storyId);
            preview.toggleAttribute('disabled', !hasStory);
            preview.classList.toggle('story-preview--disabled', !hasStory);
        });
    };

    storiesContainer.addEventListener('click', handlePreviewClick);

    closeBtn?.addEventListener('click', closeViewer);
    prevBtn?.addEventListener('click', showPrevStory);
    nextBtn?.addEventListener('click', showNextStory);
    viewer.addEventListener('click', (event) => {
        if (event.target === viewer) {
            closeViewer();
        }
    });

    const fetchStories = async () => {
        try {
            const response = await fetch(feedUrl, { credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }
            const data = await response.json();
            stories = Array.isArray(data?.items) ? data.items : [];
            alignPreviews();
            block.classList.toggle('stories--loaded', stories.length > 0);
        } catch (error) {
            console.error('Failed to load stories feed', error);
            block.classList.add('stories--error');
        }
    };

    fetchStories();
};

const mountStoriesBlocks = () => {
    const blocks = document.querySelectorAll('.wp-block-twentytwentyfivechild-stories');
    blocks.forEach((block) => initStoriesBlock(block));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountStoriesBlocks);
} else {
    mountStoriesBlocks();
}
