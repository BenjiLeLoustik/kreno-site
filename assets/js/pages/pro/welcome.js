window.WelcomePage = {
    init() {
        const button = document.querySelector('[data-welcome-copy]');
        const field = document.querySelector('[data-welcome-share]');

        if (!button || !field) {
            return;
        }

        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(field.value);
                button.textContent = 'Copié ✓';
            } catch {
                field.select();
            }
        });
    },
};

window.addEventListener('DOMContentLoaded', () => window.WelcomePage.init());