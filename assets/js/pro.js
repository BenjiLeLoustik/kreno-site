window.Pro = {
    init() {
        document.querySelectorAll('[data-pro-confirm]').forEach((button) => {
            button.addEventListener('click', (event) => {
                if (!window.confirm(button.dataset.proConfirm)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-pro-auto]').forEach((field) => {
            field.addEventListener('change', () => field.form?.requestSubmit());
        });

        document.querySelectorAll('[data-pro-apply]').forEach((button) => button.setAttribute('hidden', ''));

        this.initSwitch();
    },

    initSwitch() {
        const form = document.querySelector('[data-pro-switch]');

        if (!form) {
            return;
        }

        form.querySelector('[data-pro-switch-target]').value = window.location.pathname + window.location.search;
        form.querySelector('[data-pro-switch-select]').addEventListener('change', (event) => {
            form.action = event.target.value;
            form.requestSubmit();
        });
    },
};

window.addEventListener('DOMContentLoaded', () => window.Pro.init());