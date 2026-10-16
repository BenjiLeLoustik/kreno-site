window.HelpPage = {
    init() {
        const input = document.querySelector('[data-help-search]');

        if (!input) {
            return;
        }

        input.addEventListener('input', () => this.filter(input.value));
    },

    normalize(value) {
        return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    },

    filter(value) {
        const query = this.normalize(value);
        let visible = 0;

        document.querySelectorAll('[data-help-group]').forEach((group) => {
            let count = 0;

            group.querySelectorAll('[data-help-item]').forEach((item) => {
                const match = query === '' || this.normalize(item.textContent).includes(query);
                item.hidden = !match;
                item.open = query !== '' && match;
                count += match ? 1 : 0;
            });

            group.hidden = count === 0;
            visible += count;
        });

        document.querySelector('[data-help-empty]').hidden = visible > 0;
    },
};

document.addEventListener('DOMContentLoaded', () => window.HelpPage.init());