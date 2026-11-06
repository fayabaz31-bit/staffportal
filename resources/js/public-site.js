document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-slide]')];
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0;
    let paused = motion.matches;
    let timer;
    let transitionTimer;
    const effects = [
        [{ clipPath: 'inset(0 100% 0 0)' }, { clipPath: 'inset(0 0 0 0)' }],
        [{ transform: 'translateX(100%)' }, { transform: 'translateX(0)' }],
        [{ clipPath: 'inset(100% 0 0 0)' }, { clipPath: 'inset(0 0 0 0)' }],
        [{ opacity: 0 }, { opacity: 1 }],
    ];
    let previousEffect = -1;

    const schedule = () => {
        clearTimeout(timer);
        if (!paused && !document.hidden && !carousel.matches(':hover') && !carousel.contains(document.activeElement)) {
            timer = setTimeout(() => show(current + 1), 9000);
        }
    };
    const show = (index) => {
        const outgoing = slides[current];
        clearTimeout(transitionTimer);
        slides.forEach((slide) => slide.classList.remove('is-leaving'));
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, position) => {
            const active = position === current;
            slide.classList.toggle('is-active', active);
            slide.inert = !active;
            slide.setAttribute('aria-hidden', String(!active));
        });
        if (!motion.matches) {
            outgoing.classList.add('is-leaving');
            const effect = (previousEffect + 1 + Math.floor(Math.random() * (effects.length - 1))) % effects.length;
            previousEffect = effect;
            slides[current].animate(effects[effect], {
                duration: 800,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
            });
            transitionTimer = setTimeout(() => outgoing.classList.remove('is-leaving'), 800);
        }
        schedule();
    };
    const updatePause = () => {
        schedule();
    };
    carousel.addEventListener('mouseenter', () => clearTimeout(timer));
    carousel.addEventListener('mouseleave', schedule);
    carousel.addEventListener('focusin', () => clearTimeout(timer));
    carousel.addEventListener('focusout', () => setTimeout(schedule, 0));
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => { paused = motion.matches; updatePause(); });
    updatePause();
});
