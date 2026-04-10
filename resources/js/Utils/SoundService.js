export const playSound = (type) => {
    const sounds = {
        success: '/sounds/success.mp3',
        click: '/sounds/click.mp3',
        pop: '/sounds/pop.mp3',
        error: '/sounds/error.mp3',
    };

    const audio = new Audio(sounds[type]);
    audio.play().catch(e => console.log('Audio play blocked or file missing:', e));
};
