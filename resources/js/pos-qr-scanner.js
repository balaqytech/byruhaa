document.addEventListener('alpine:init', () => {
    window.Alpine.data('cashierQrScanner', () => ({
        isOpen: false,
        isStarting: false,
        scanner: null,
        visibilityHandler: null,

        init() {
            this.visibilityHandler = () => {
                if (document.hidden) {
                    this.close();
                }
            };

            document.addEventListener('visibilitychange', this.visibilityHandler);
        },

        async open() {
            if (this.isOpen || this.isStarting) {
                return;
            }

            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                this.reportError('تحتاج الكاميرا إلى اتصال HTTPS آمن. استخدم قارئ QR أو أدخل الرمز يدويًا.');
                return;
            }

            this.isOpen = true;
            this.isStarting = true;
            await this.$nextTick();

            try {
                const { default: QrScanner } = await import('qr-scanner');

                if (!this.isOpen) {
                    return;
                }

                const scanner = new QrScanner(this.$refs.video, (result) => this.scanned(result.data), {
                    preferredCamera: 'environment',
                    maxScansPerSecond: 10,
                    highlightScanRegion: true,
                    returnDetailedScanResult: true,
                });

                this.scanner = scanner;
                await scanner.start();

                if (!this.isOpen) {
                    scanner.destroy();
                }
            } catch {
                const wasOpen = this.isOpen;
                this.close();
                if (wasOpen) {
                    this.reportError('تعذّر تشغيل الكاميرا. تحقّق من إذن الكاميرا، أو أدخل الرمز يدويًا.');
                }
            } finally {
                this.isStarting = false;
            }
        },

        async scanned(value) {
            if (!this.isOpen) {
                return;
            }

            this.close();

            try {
                await this.$wire.$set('scanToken', value.trim());
                await this.$wire.scan();
            } catch {
                this.reportError('تعذّر التحقق من البطاقة. حاول المسح مرة أخرى أو أدخل الرمز يدويًا.');
            }
        },

        reportError(message) {
            this.$dispatch('pos-scanner-error', { message });
        },

        close() {
            this.scanner?.destroy();
            this.scanner = null;
            this.isOpen = false;
        },

        destroy() {
            this.close();
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        },
    }));
});
