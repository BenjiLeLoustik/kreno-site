window.ShopPage = {
    button: null,

    init() {
        this.button = document.querySelector('[data-shop-follow]');

        if (!this.button) {
            return;
        }

        this.button.addEventListener('click', () => this.toggle());
    },

    async toggle() {
        this.button.disabled = true;

        try {
            const response = await fetch(this.button.dataset.url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                return;
            }

            this.render(await response.json());
        } finally {
            this.button.disabled = false;
        }
    },

    render(data) {
        this.button.classList.toggle('shop-follow__button--active', data.following);
        this.button.setAttribute('aria-pressed', data.following ? 'true' : 'false');
        this.button.querySelector('[data-shop-follow-label]').textContent = data.label;

        const followers = document.querySelector('[data-shop-followers]');
        const followersText = document.querySelector('[data-shop-followers-text]');

        if (followers && followersText) {
            const template = data.followersCount > 1
                ? followersText.dataset.labelFollowers
                : followersText.dataset.labelFollower;

            followers.textContent = template.replaceAll('{count}', String(data.followersCount));
        }
    },
};

window.addEventListener('DOMContentLoaded', () => window.ShopPage.init());