window.Admin = {
    init() {
        document.querySelectorAll('[data-admin-confirm]').forEach((button) => {
            button.addEventListener('click', (event) => {
                if (!window.confirm(button.dataset.adminConfirm)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-admin-auto]').forEach((field) => {
            field.addEventListener('change', () => field.form?.requestSubmit());
        });

        document.querySelectorAll('form[data-admin-strip]').forEach((form) => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.value === '' && field.type !== 'hidden') {
                        field.disabled = true;
                    }
                });
            });
        });

        document.querySelectorAll('[data-admin-apply]').forEach((button) => button.setAttribute('hidden', ''));
    },
};

window.addEventListener('DOMContentLoaded', () => window.Admin.init());