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
        });
    }

    const setPlaying = (isPlaying) => {
        button.classList.toggle('is-playing', isPlaying);
        button.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
        button.setAttribute('aria-label', isPlaying ? (button.dataset.pauseLabel || 'Hörprobe pausieren') : (button.dataset.playLabel || 'Hörprobe abspielen'));
    };

    if (!audio.paused) {
        audio.pause();
        audio.currentTime = 0;
        setPlaying(false);
        return;
    }

    document.querySelectorAll('.child-music-card__preview-button.is-playing').forEach((playingButton) => {
        if (playingButton === button) {
            return;
        }

        const playingAudio = playingButton.querySelector('audio');
        if (playingAudio) {
            playingAudio.pause();
            playingAudio.currentTime = 0;
        }
        playingButton.classList.remove('is-playing');
        playingButton.setAttribute('aria-pressed', 'false');
        playingButton.setAttribute('aria-label', playingButton.dataset.playLabel || 'Hörprobe abspielen');
    });

    const playPromise = audio.play();
    setPlaying(true);

    if (playPromise?.catch) {
        playPromise.catch(() => {
            setPlaying(false);
        });
    }
});
