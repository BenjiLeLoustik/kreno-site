window.App = {
    init() {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        this.initToggles();
    },

    initToggles() {
        const buttons = document.querySelectorAll('[data-toggle]');

        buttons.forEach((button) => {
            button.addEventListener('click', () => this.toggle(button, button.getAttribute('aria-expanded') !== 'true'));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                buttons.forEach((button) => this.toggle(button, false));
            }
        });
    },

    toggle(button, open) {
        const target = document.getElementById(button.dataset.toggle);

        if (!target) {
            return;
        }

        button.setAttribute('aria-expanded', String(open));
        target.classList.toggle('is-open', open);
    },
};

document.addEventListener('DOMContentLoaded', () => window.App.init());