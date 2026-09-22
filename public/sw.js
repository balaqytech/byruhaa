self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data?.json() ?? {};
    } catch {
        payload = {};
    }

    const requestedUrl = payload.data?.url ?? '/minor/orders';
    let notificationUrl = '/minor/orders';

    try {
        const parsedUrl = new URL(requestedUrl, self.location.origin);

        if (parsedUrl.origin === self.location.origin && parsedUrl.pathname.startsWith('/minor/')) {
            notificationUrl = parsedUrl.href;
        }
    } catch {
        notificationUrl = '/minor/orders';
    }

    event.waitUntil(self.registration.showNotification(payload.title ?? 'بيرحاء', {
        body: payload.body ?? 'لديك تحديث جديد في حسابك.',
        icon: payload.icon ?? '/android-chrome-192x192.png',
        badge: payload.badge ?? '/favicon-32x32.png',
        dir: payload.dir ?? 'rtl',
        lang: payload.lang ?? 'ar',
        tag: payload.tag ?? 'minor-order-status',
        data: { url: notificationUrl },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const notificationUrl = event.notification.data?.url ?? '/minor/orders';

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existingWindow = windows.find((client) => new URL(client.url).origin === self.location.origin);

        if (existingWindow) {
            await existingWindow.navigate(notificationUrl);
            return existingWindow.focus();
        }

        return self.clients.openWindow(notificationUrl);
    })());
});
