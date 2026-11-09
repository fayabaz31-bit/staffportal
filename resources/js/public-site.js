document.querySelectorAll('[data-public-header]').forEach((header) => {
    const toggle = header.querySelector('[data-public-menu-toggle]');
    const navigation = header.querySelector('.nfv-main-nav');
    const mobileLayout = window.matchMedia('(max-width: 700px)');
    const closeSubmenus = () => {
        header.querySelectorAll('details[open]').forEach((submenu) => {
            submenu.open = false;
        });
    };
    const setMenuOpen = (open) => {
        navigation.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('span').textContent = open ? '−' : '+';
        if (!open) {
            closeSubmenus();
        }
    };

    toggle.hidden = false;
    header.setAttribute('data-public-menu-ready', '');
    toggle.addEventListener('click', () => {
        setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a') && mobileLayout.matches) {
            setMenuOpen(false);
        }
    });
    header.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        const submenu = header.querySelector('details[open]');
        if (submenu) {
            submenu.open = false;
            submenu.querySelector('summary').focus();
        } else if (mobileLayout.matches && navigation.classList.contains('is-open')) {
            setMenuOpen(false);
            toggle.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) {
            if (mobileLayout.matches) {
                setMenuOpen(false);
            } else {
                closeSubmenus();
            }
        }
    });
    header.addEventListener('focusout', (event) => {
        if (!header.contains(event.relatedTarget)) {
            closeSubmenus();
        }
    });
    mobileLayout.addEventListener('change', () => setMenuOpen(false));
});

document.querySelectorAll('[data-campus-showcase]').forEach((section) => {
    const reel = section.querySelector('[data-campus-reel]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const advance = (direction) => {
        const distance = reel.firstElementChild.getBoundingClientRect().width + 8;
        const atEnd = reel.scrollLeft >= reel.scrollWidth - reel.clientWidth - 4;
        reel.scrollTo({ left: direction > 0 && atEnd ? 0 : reel.scrollLeft + distance * direction, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    };
    section.querySelector('[data-campus-prev]').addEventListener('click', () => advance(-1));
    section.querySelector('[data-campus-next]').addEventListener('click', () => advance(1));
    setInterval(() => {
        const bounds = section.getBoundingClientRect();
        const video = section.querySelector('video');
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
