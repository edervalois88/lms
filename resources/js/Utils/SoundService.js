export const playSound = (type) => {
    const sounds = {
        success: '/sounds/success.mp3',
        click: '/sounds/click.mp3',
        pop: '/sounds/pop.mp3',
        error: '/sounds/error.mp3',
    };

    const soundFile = sounds[type];
    if (!soundFile) return;

    const audio = new Audio(soundFile);
    audio.volume = 0.3;
    audio.play().catch(() => {
        // Silently fail if audio cannot be played (file missing, blocked, etc)
    });
};
