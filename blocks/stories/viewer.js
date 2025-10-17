/**
 * Stories Viewer Implementation
 * 
 * This script handles the story viewing experience in the frontend.
 * Features:
 * - Image and video support
 * - Touch and keyboard navigation
 * - Progress indicator
 * - Content warnings
 * - Automatic advancement
 * 
 * @package twentytwentyfivechild
 */

document.addEventListener('DOMContentLoaded', function() {
    const storiesContainer = document.querySelector('.stories-container');
    if (!storiesContainer) return;

    const viewer = document.querySelector('.story-viewer');
    const viewerContent = document.querySelector('.story-viewer-content');
    const mediaContainer = document.querySelector('.story-media');
    const progressContainer = document.querySelector('.story-progress');
    const closeBtn = document.querySelector('.story-close');
    const prevBtn = document.querySelector('.story-prev');
    const nextBtn = document.querySelector('.story-next');

    let currentStoryIndex = 0;
    let stories = [];
    let progressBar = null;
    let progressTimer = null;

    // Stories aus der REST API laden
    fetch('/wp-json/twentytwentyfivechild/v1/stories')
        .then(response => response.json())
        .then(data => {
            stories = data.items;
        });

    // Story-Preview Click Handler
    storiesContainer.addEventListener('click', function(e) {
        const preview = e.target.closest('.story-preview');
        if (!preview) return;

        const storyId = preview.dataset.storyId;
        currentStoryIndex = stories.findIndex(story => story.id === storyId);
        if (currentStoryIndex === -1) return;

        showStory(currentStoryIndex);
        viewer.style.display = 'flex';
    });

    // Story anzeigen
    function showStory(index) {
        if (index < 0 || index >= stories.length) return;

        const story = stories[index];
        mediaContainer.innerHTML = '';
        
        if (progressTimer) {
            clearTimeout(progressTimer);
        }

        // Progress Bar zurücksetzen
        progressContainer.innerHTML = '';
        progressBar = document.createElement('div');
        progressBar.className = 'story-progress-bar';
        progressContainer.appendChild(progressBar);

        // Media Element erstellen
        if (story._open_stories.mime_type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = story._open_stories.url;
            video.autoplay = true;
            video.playsInline = true;
            video.controls = false;
            
            if (story._open_stories.content_warning) {
                const warning = document.createElement('div');
                warning.className = 'content-warning';
                warning.textContent = story._open_stories.content_warning;
                mediaContainer.appendChild(warning);
                
                setTimeout(() => {
                    warning.remove();
                    mediaContainer.appendChild(video);
                    startProgress(video.duration * 1000);
                }, 3000);
            } else {
                mediaContainer.appendChild(video);
                video.play();
                startProgress(video.duration * 1000);
            }

            video.onended = () => {
                showNextStory();
            };
        } else {
            const img = document.createElement('img');
            img.src = story._open_stories.url;
            img.alt = story._open_stories.alt || '';
            
            if (story._open_stories.content_warning) {
                const warning = document.createElement('div');
                warning.className = 'content-warning';
                warning.textContent = story._open_stories.content_warning;
                mediaContainer.appendChild(warning);
                
                setTimeout(() => {
                    warning.remove();
                    mediaContainer.appendChild(img);
                    startProgress(story._open_stories.duration_in_seconds * 1000 || 5000);
                }, 3000);
            } else {
                mediaContainer.appendChild(img);
                startProgress(story._open_stories.duration_in_seconds * 1000 || 5000);
            }
        }
    }

    // Progress Bar Animation
    function startProgress(duration) {
        progressBar.style.transition = `width ${duration}ms linear`;
        progressBar.style.width = '100%';
        
        progressTimer = setTimeout(() => {
            showNextStory();
        }, duration);
    }

    // Navigation
    function showNextStory() {
        if (currentStoryIndex < stories.length - 1) {
            currentStoryIndex++;
            showStory(currentStoryIndex);
        } else {
            closeViewer();
        }
    }

    function showPrevStory() {
        if (currentStoryIndex > 0) {
            currentStoryIndex--;
            showStory(currentStoryIndex);
        }
    }

    // Event Listener
    closeBtn.addEventListener('click', closeViewer);
    nextBtn.addEventListener('click', showNextStory);
    prevBtn.addEventListener('click', showPrevStory);

    // Viewer schließen
    function closeViewer() {
        viewer.style.display = 'none';
        mediaContainer.innerHTML = '';
        if (progressTimer) {
            clearTimeout(progressTimer);
        }
    }

    // Tastatur-Navigation
    document.addEventListener('keydown', function(e) {
        if (viewer.style.display === 'none') return;

        switch(e.key) {
            case 'ArrowLeft':
                showPrevStory();
                break;
            case 'ArrowRight':
                showNextStory();
                break;
            case 'Escape':
                closeViewer();
                break;
        }
    });
});