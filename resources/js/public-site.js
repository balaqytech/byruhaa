import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const legacyAppearanceStorageKey = 'byruha.public.appearance';
const appearanceStorageKey = 'flux.appearance';
const root = document.documentElement;
const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

if (!localStorage.getItem(appearanceStorageKey) && localStorage.getItem(legacyAppearanceStorageKey)) {
    localStorage.setItem(appearanceStorageKey, localStorage.getItem(legacyAppearanceStorageKey));
}

const selectedAppearance = () => localStorage.getItem(appearanceStorageKey) || 'system';

const applyAppearance = (appearance = selectedAppearance()) => {
    const resolvedAppearance = appearance === 'system'
        ? (colorSchemeQuery.matches ? 'dark' : 'light')
        : appearance;

    root.classList.toggle('dark', resolvedAppearance === 'dark');
    root.dataset.appearance = appearance;

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.dataset.active = toggle.dataset.theme === appearance ? 'true' : 'false';
    });
};

applyAppearance();

colorSchemeQuery.addEventListener('change', () => {
    if (selectedAppearance() === 'system') {
        applyAppearance('system');
    }
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    localStorage.setItem(appearanceStorageKey, toggle.dataset.theme);

    if (window.Flux) {
        window.Flux.appearance = toggle.dataset.theme;
    }

    applyAppearance(toggle.dataset.theme);
});

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const whatsappButton = document.querySelector('[data-whatsapp-floating-button]');
const whatsappRevealSentinel = document.querySelector('[data-whatsapp-reveal-sentinel]');

if (whatsappButton && whatsappRevealSentinel) {
    const toggleWhatsappButton = (isVisible) => {
        whatsappButton.classList.toggle('pointer-events-none', !isVisible);
        whatsappButton.classList.toggle('invisible', !isVisible);
        whatsappButton.classList.toggle('translate-y-3', !isVisible);
        whatsappButton.classList.toggle('opacity-0', !isVisible);
    };

    const whatsappObserver = new IntersectionObserver(([entry]) => {
        toggleWhatsappButton(!entry.isIntersecting && entry.boundingClientRect.bottom < 0);
    });

    whatsappObserver.observe(whatsappRevealSentinel);
}

if (!prefersReducedMotion) {
    gsap.from('[data-public-header]', {
        y: -24,
        autoAlpha: 0,
        duration: 0.85,
        ease: 'power3.out',
    });

    const publicHeroItems = document.querySelectorAll('.public-hero-copy > *');

    if (publicHeroItems.length > 0) {
        gsap.from(publicHeroItems, {
            y: 28,
            autoAlpha: 0,
            duration: 0.95,
            stagger: 0.12,
            ease: 'power3.out',
            delay: 0.08,
        });
    }

    gsap.utils.toArray('.public-card').forEach((card) => {
        gsap.from(card, {
            y: 34,
            autoAlpha: 0,
            duration: 0.85,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: card,
                start: 'top 88%',
                once: true,
            },
        });
    });
}
