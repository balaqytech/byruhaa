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

const pushManager = document.querySelector('[data-minor-push-manager]');

if (pushManager) {
    const enableButton = pushManager.querySelector('[data-push-enable]');
    const disableButton = pushManager.querySelector('[data-push-disable]');
    const status = pushManager.querySelector('[data-push-status]');
    const iosHelp = pushManager.querySelector('[data-push-ios-help]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const setState = (subscribed, message) => {
        status.textContent = message;
        enableButton.classList.toggle('hidden', subscribed);
        disableButton.classList.toggle('hidden', !subscribed);
    };

    const urlBase64ToUint8Array = (value) => {
        const padding = '='.repeat((4 - (value.length % 4)) % 4);
        const base64 = (value + padding).replaceAll('-', '+').replaceAll('_', '/');
        const raw = window.atob(base64);

        return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
    };

    const subscriptionPayload = (subscription) => {
        const json = subscription.toJSON();

        return {
            endpoint: json.endpoint,
            keys: json.keys,
            content_encoding: window.PushManager.supportedContentEncodings?.[0] ?? 'aes128gcm',
        };
    };

    const sendSubscription = async (url, method, payload) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error(`Push subscription request failed with status ${response.status}`);
        }
    };

    const initializePush = async () => {
        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            setState(false, 'هذا المتصفح أو الاتصال لا يدعم إشعارات الويب الآمنة.');
            enableButton.disabled = true;
            return;
        }

        if (isIos && !isStandalone) {
            iosHelp.classList.remove('hidden');
            setState(false, 'أضف الموقع إلى الشاشة الرئيسية قبل التفعيل على هذا الجهاز.');
            enableButton.disabled = true;
            return;
        }

        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        disableButton.addEventListener('click', async () => {
            disableButton.disabled = true;

            try {
                const currentRegistration = await navigator.serviceWorker.ready;
                const currentSubscription = await currentRegistration.pushManager.getSubscription();

                if (currentSubscription) {
                    await sendSubscription(pushManager.dataset.unsubscribeUrl, 'DELETE', { endpoint: currentSubscription.endpoint });
                    await currentSubscription.unsubscribe();
                }

                setState(false, 'تم تعطيل الإشعارات على هذا الجهاز.');
            } catch (error) {
                console.error('Unable to disable browser notifications.', error);
                status.textContent = 'تعذر تعطيل الإشعارات الآن. حاول مرة أخرى.';
            } finally {
                disableButton.disabled = false;
            }
        });

        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await sendSubscription(pushManager.dataset.subscribeUrl, 'POST', subscriptionPayload(subscription));
            setState(true, 'الإشعارات مفعّلة على هذا الجهاز.');
            return;
        }

        if (Notification.permission === 'denied') {
            setState(false, 'الإذن مرفوض من إعدادات المتصفح. غيّره من إعدادات الموقع ثم حاول مجددًا.');
            enableButton.disabled = true;
            return;
        }

        setState(false, 'الإشعارات غير مفعّلة على هذا الجهاز.');

        enableButton.addEventListener('click', async () => {
            enableButton.disabled = true;
            status.textContent = 'جاري طلب إذن المتصفح…';

            try {
                const permission = await Notification.requestPermission();

                if (permission !== 'granted') {
                    setState(false, 'لم يتم منح إذن الإشعارات.');
                    return;
                }

                const currentRegistration = await navigator.serviceWorker.ready;
                const newSubscription = await currentRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(pushManager.dataset.vapidPublicKey),
                });
                await sendSubscription(pushManager.dataset.subscribeUrl, 'POST', subscriptionPayload(newSubscription));
                setState(true, 'تم تفعيل الإشعارات على هذا الجهاز.');
            } catch (error) {
                console.error('Unable to enable browser notifications.', error);
                setState(false, 'تعذر تفعيل الإشعارات الآن. حاول مرة أخرى.');
            } finally {
                enableButton.disabled = false;
            }
        });

    };

    initializePush().catch((error) => {
        console.error('Unable to initialize browser notifications.', error);
        setState(false, 'تعذر التحقق من حالة الإشعارات الآن.');
    });
}

const whatsappButton = document.querySelector('[data-whatsapp-floating-button]');
const whatsappRevealSentinel = document.querySelector('[data-whatsapp-reveal-sentinel]');
const publicFooter = document.querySelector('[data-public-footer]');

if (whatsappButton && whatsappRevealSentinel) {
    let hasPassedWhatsappRevealSentinel = false;
    let isFooterVisible = false;

    const toggleWhatsappButton = (isVisible) => {
        whatsappButton.classList.toggle('pointer-events-none', !isVisible);
        whatsappButton.classList.toggle('invisible', !isVisible);
        whatsappButton.classList.toggle('translate-y-3', !isVisible);
        whatsappButton.classList.toggle('opacity-0', !isVisible);
    };

    const updateWhatsappButtonVisibility = () => {
        toggleWhatsappButton(hasPassedWhatsappRevealSentinel && !isFooterVisible);
    };

    const whatsappObserver = new IntersectionObserver(([entry]) => {
        hasPassedWhatsappRevealSentinel = !entry.isIntersecting && entry.boundingClientRect.bottom < 0;
        updateWhatsappButtonVisibility();
    });

    whatsappObserver.observe(whatsappRevealSentinel);

    if (publicFooter) {
        const footerObserver = new IntersectionObserver(([entry]) => {
            isFooterVisible = entry.isIntersecting;
            updateWhatsappButtonVisibility();
        });

        footerObserver.observe(publicFooter);
    }
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
