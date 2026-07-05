document.addEventListener('click', (event) => {
    const button = event.target.closest('.child-music-card__preview-button');
    if (!button) {
        return;
    }

    const previewUrl = button.dataset?.previewUrl;
    if (!previewUrl) {
        return;
    }

    let audio = button.querySelector('audio');
    const icon = button.querySelector('.child-music-card__preview-icon');

    if (!audio) {
        audio = document.createElement('audio');
        audio.preload = 'none';
        audio.src = previewUrl;
        audio.className = 'child-music-card__audio';
        button.appendChild(audio);

        audio.addEventListener('ended', () => {
            button.classList.remove('is-playing');
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', button.dataset.playLabel || 'Hörprobe abspielen');
            if (icon) {
                icon.textContent = '▶';
            }
        });
    }

    const setPlaying = (isPlaying) => {
        button.classList.toggle('is-playing', isPlaying);
        button.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
        button.setAttribute('aria-label', isPlaying ? (button.dataset.pauseLabel || 'Hörprobe pausieren') : (button.dataset.playLabel || 'Hörprobe abspielen'));
        if (icon) {
            icon.textContent = isPlaying ? '❚❚' : '▶';
        }
    };

    if (!audio.paused) {
        audio.pause();
        setPlaying(false);
        return;
    }

    const playPromise = audio.play();
    setPlaying(true);

    if (playPromise?.catch) {
        playPromise.catch(() => {
            setPlaying(false);
        });
    }
});
