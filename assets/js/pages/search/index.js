window.SearchPage = {
    form: null,

    init() {
        this.form = document.querySelector('[data-search-form]');

        if (!this.form) {
            return;
        }

        this.form.querySelectorAll('[data-search-auto]').forEach((field) => {
            field.addEventListener('change', () => this.form.requestSubmit());
        });

        this.form.querySelector('[data-search-city]')?.addEventListener('change', () => {
            this.clearPosition();
            this.form.requestSubmit();
        });

        this.form.querySelector('[data-search-apply]')?.setAttribute('hidden', '');
        this.initLocate();
    },

    initLocate() {
        const button = this.form.querySelector('[data-search-locate]');

        if (!button || !('geolocation' in navigator)) {
            return;
        }

        button.removeAttribute('hidden');
        button.addEventListener('click', () => {
            const label = button.querySelector('[data-search-locate-label]');
            label.textContent = 'Localisation…';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.setPosition(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6));
                    this.form.requestSubmit();
                },
                () => {
                    label.textContent = 'Position indisponible';
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
            );
        });
    },

    setPosition(latitude, longitude) {
        const latitudeField = this.form.querySelector('[data-search-latitude]');
        const longitudeField = this.form.querySelector('[data-search-longitude]');

        latitudeField.name = 'latitude';
        latitudeField.value = latitude;
        longitudeField.name = 'longitude';
        longitudeField.value = longitude;
    },

    clearPosition() {
        this.form.querySelectorAll('[data-search-latitude], [data-search-longitude]').forEach((field) => {
            field.removeAttribute('name');
            field.value = '';
        });
    },
};

window.addEventListener('DOMContentLoaded', () => window.SearchPage.init());