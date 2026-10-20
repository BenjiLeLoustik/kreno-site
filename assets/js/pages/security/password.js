window.PasswordForm = {
    labels: [],
    mismatchMessage: '',

    init() {
        const meter = document.querySelector('[data-password-meter]');
        const input = document.querySelector('[data-password-input]');

        if (!meter || !input) {
            return;
        }

        this.loadLabels(meter);

        const bars = meter.querySelectorAll('[data-password-bar]');
        const text = meter.querySelector('[data-password-text]');
        const initial = text ? text.textContent.trim() : '';
        const confirmation = document.querySelector('[data-password-confirmation]');

        if (confirmation) {
            this.mismatchMessage = confirmation.dataset.messageMismatch || '';
        }

        input.addEventListener('input', () => {
            const level = this.level(input.value);

            bars.forEach((bar, index) => {
                bar.dataset.level = index < level ? String(level) : '';
            });

            if (text) {
                text.textContent = level === 0 ? initial : this.labels[level - 1];
                text.dataset.level = level === 0 ? '' : String(level);
            }
        });

        if (confirmation) {
            const check = () => {
                const isMismatch = confirmation.value !== '' && confirmation.value !== input.value;

                confirmation.setCustomValidity(isMismatch ? this.mismatchMessage : '');
            };

            input.addEventListener('input', check);
            confirmation.addEventListener('input', check);
        }
    },

    loadLabels(meter) {
        this.labels = [
            meter.dataset.labelEmpty || '',
            meter.dataset.labelWeak || '',
            meter.dataset.labelFair || '',
            meter.dataset.labelStrong || '',
        ];
    },

    level(value) {
        if (value === '') {
            return 0;
        }

        let score = 0;

        if (value.length >= 8) {
            score++;
        }

        if (value.length >= 12) {
            score++;
        }

        if (/[0-9]/.test(value) && /[a-zA-Z]/.test(value)) {
            score++;
        }

        if (/[^a-zA-Z0-9]/.test(value)) {
            score++;
        }

        return Math.max(1, score);
    },
};

window.addEventListener('DOMContentLoaded', () => window.PasswordForm.init());