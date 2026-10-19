window.App = {
    toggles: [],

    init() {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        this.initToggles();
    },

    initToggles() {
        this.toggles = [...document.querySelectorAll('[data-toggle]')];

        this.toggles.forEach((button) => {
            button.addEventListener('click', () => this.toggle(button, button.getAttribute('aria-expanded') !== 'true'));
        });

        document.addEventListener('click', (event) => this.closeOutside(event.target));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.toggles.forEach((button) => this.toggle(button, false));
            }
        });
    },

    closeOutside(target) {
        this.toggles.forEach((button) => {
            const panel = document.getElementById(button.dataset.toggle);

            if (button.contains(target) || panel?.contains(target)) {
                return;
            }

            this.toggle(button, false);
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