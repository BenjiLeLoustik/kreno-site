window.PasswordForm = {
    labels: ['Trop court', 'Faible', 'Correct', 'Solide'],

    init() {
        const input = document.querySelector('[data-password-input]');

        if (!input) {
            return;
        }

        const bars = document.querySelectorAll('[data-password-bar]');
        const text = document.querySelector('[data-password-text]');
        const initial = text.textContent;
        const confirmation = document.querySelector('[data-password-confirmation]');

        input.addEventListener('input', () => {
            const level = this.level(input.value);
            bars.forEach((bar, index) => {
                bar.dataset.level = index < level ? String(level) : '';
            });
            text.textContent = level === 0 ? initial : this.labels[level - 1];
            text.dataset.level = level === 0 ? '' : String(level);
        });

        if (confirmation) {
            const check = () => confirmation.setCustomValidity(confirmation.value !== '' && confirmation.value !== input.value ? 'Les deux mots de passe ne sont pas identiques.' : '');
            input.addEventListener('input', check);
            confirmation.addEventListener('input', check);
        }
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