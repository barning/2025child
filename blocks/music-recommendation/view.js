document.addEventListener('click', (event) => {
    const button = event.target.closest('.child-music-card__preview-button');
    if (!button) {
        return;
    }

    const preview = button.closest('.child-music-card__preview');
    const previewUrl = preview?.dataset?.previewUrl;
    if (!preview || !previewUrl) {
        return;
    }

    const audio = document.createElement('audio');
    audio.controls = true;
    audio.preload = 'none';
    audio.src = previewUrl;
    audio.className = 'child-music-card__audio';

    preview.replaceChildren(audio);
    audio.focus();
});
