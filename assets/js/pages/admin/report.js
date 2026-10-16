window.ReportPage = {
    init() {
        const filters = document.querySelector('[data-report-filters]');

        if (filters) {
            filters.querySelector('[data-report-apply]')?.setAttribute('hidden', '');
            filters.querySelectorAll('[data-report-auto]').forEach((field) => {
                field.addEventListener('change', () => filters.requestSubmit());
            });
        }

        document.querySelectorAll('[data-confirm]').forEach((button) => {
            button.addEventListener('click', (event) => {
                if (!window.confirm(button.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    },
};

window.addEventListener('DOMContentLoaded', () => window.ReportPage.init());