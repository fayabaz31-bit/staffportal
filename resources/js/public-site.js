const programmeCards = document.querySelectorAll('[data-programme-cards] .nfv-study-option');
const programmeMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (programmeCards.length && 'IntersectionObserver' in window && !programmeMotion.matches) {
    const programmeEntrance = new IntersectionObserver((entries, observer) => {
        entries.forEach(({ target: card, intersectionRatio }) => {
            if (intersectionRatio < 0.3) {
                return;
            }

            observer.unobserve(card);

            if (!programmeMotion.matches && !card.matches(':focus')) {
                card.animate([
                    { transform: 'translateX(50%)', opacity: 0 },
                    { transform: 'translateX(0)', opacity: 1 },
                ], { duration: 1000, easing: 'ease-in-out' });
            }
        });
    }, { threshold: 0.3 });

    programmeCards.forEach((card) => programmeEntrance.observe(card));
}

document.querySelectorAll('[data-campus-showcase]').forEach((section) => {
    const reel = section.querySelector('[data-campus-reel]');
    const video = reel.querySelector('video');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const advance = (direction) => {
        const gap = parseFloat(getComputedStyle(reel).columnGap) || 0;
        const step = reel.firstElementChild.getBoundingClientRect().width + gap;
        const end = reel.scrollWidth - reel.clientWidth;
        let position = reel.scrollLeft + step * direction;

        if (direction > 0 && reel.scrollLeft >= end - 2) {
            position = 0;
        } else if (direction < 0 && reel.scrollLeft <= 2) {
            position = end;
        }

        reel.scrollTo({ left: position, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    };

    section.querySelector('[data-campus-prev]').addEventListener('click', () => advance(-1));
    section.querySelector('[data-campus-next]').addEventListener('click', () => advance(1));

    setInterval(() => {
        const bounds = section.getBoundingClientRect();

        if (!reducedMotion.matches && !document.hidden && bounds.top < window.innerHeight && bounds.bottom > 0 && !section.matches(':hover') && !section.contains(document.activeElement) && video.paused) {
            advance(1);
        }
    }, 4500);
});

document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-slide]')];
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const pagination = [...carousel.querySelectorAll('[data-hero-go]')];
    const pauseButton = carousel.querySelector('[data-hero-pause]');
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
        const next = (index + slides.length) % slides.length;
        if (next === current) { schedule(); return; }
        const outgoing = slides[current];
        clearTimeout(transitionTimer);
        slides.forEach((slide) => { slide.getAnimations().forEach((animation) => animation.cancel()); slide.classList.remove('is-leaving'); });
        current = next;
        slides.forEach((slide, position) => {
            const active = position === current;
            slide.classList.toggle('is-active', active);
            slide.inert = !active;
            slide.setAttribute('aria-hidden', String(!active));
            pagination[position]?.setAttribute('aria-pressed', String(active));
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
        if (pauseButton) {
            pauseButton.textContent = paused ? 'Play' : 'Pause';
            pauseButton.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
        }
        schedule();
    };
    pagination.forEach((button, index) => button.addEventListener('click', () => show(index)));
    carousel.querySelector('[data-hero-prev]')?.addEventListener('click', () => show(current - 1));
    carousel.querySelector('[data-hero-next]')?.addEventListener('click', () => show(current + 1));
    pauseButton?.addEventListener('click', () => { paused = !paused; updatePause(); });
    carousel.addEventListener('mouseenter', () => clearTimeout(timer));
    carousel.addEventListener('mouseleave', schedule);
    carousel.addEventListener('focusin', () => clearTimeout(timer));
    carousel.addEventListener('focusout', () => setTimeout(schedule, 0));
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => { paused = motion.matches; updatePause(); });
    updatePause();
});
