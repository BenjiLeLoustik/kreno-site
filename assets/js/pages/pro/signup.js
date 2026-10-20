window.ProSignup = {
    init() {
        this.initPassword();
        this.initSiret();
        this.initServices();
        this.initDiscount();
        this.initCounter();
        this.initPromo();
    },

    initPassword() {
        const input = document.querySelector('[data-signup-password]');
        const meter = document.querySelector('[data-signup-meter]');

        if (!input || !meter) {
            return;
        }

        const bars = meter.querySelectorAll('[data-signup-meter-bar]');
        const text = meter.querySelector('[data-signup-meter-text]');
        const initial = text.textContent;
        const labels = [
            meter.dataset.labelEmpty || '',
            meter.dataset.labelWeak || '',
            meter.dataset.labelFair || '',
            meter.dataset.labelStrong || '',
        ];

        input.addEventListener('input', () => {
            const value = input.value;
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

            const level = value === '' ? 0 : Math.max(1, score);
            bars.forEach((bar, index) => {
                bar.dataset.level = index < level ? String(level) : '';
            });
            text.textContent = level === 0 ? initial : labels[level - 1];
            text.dataset.level = level === 0 ? '' : String(level);
        });
    },

    initSiret() {
        const form = document.querySelector('[data-signup-siret]');

        if (!form) {
            return;
        }

        const input = form.querySelector('[data-signup-siret-input]');
        const button = form.querySelector('[data-signup-siret-search]');
        const error = form.querySelector('[data-signup-siret-error]');
        const registry = form.querySelector('[data-signup-registry]');

        const search = async () => {
            const siret = input.value.replace(/\D/g, '');
            error.hidden = true;

            if (siret.length !== 14) {
                this.showError(error, form.dataset.messageSiretLength);
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(form.dataset.signupSiret.replace('00000000000000', siret), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok || !data.found) {
                    registry.hidden = true;
                    this.showError(error, data.error || form.dataset.messageSiretNotFound);
                    return;
                }

                this.fillRegistry(form, registry, data.establishment);
            } catch {
                this.showError(error, form.dataset.messageSiretUnavailable);
            } finally {
                button.disabled = false;
            }
        };

        button.hidden = false;
        button.addEventListener('click', search);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                search();
            }
        });
    },

    fillRegistry(form, registry, data) {
        const values = {
            legalName: data.legalName,
            brandName: data.brandName,
            fullAddress: [data.address, [data.postalCode, data.locality].filter(Boolean).join(' ')].filter(Boolean).join(', '),
            ape: [data.apeCode, data.apeLabel].filter(Boolean).join(' · '),
        };
        const fields = {
            name: data.brandName || data.legalName,
            address: data.address,
            postalCode: data.postalCode,
            locality: data.locality,
            latitude: data.latitude,
            longitude: data.longitude,
        };

        registry.querySelectorAll('[data-registry]').forEach((node) => {
            node.textContent = values[node.dataset.registry] || '—';
        });
        registry.hidden = false;

        Object.entries(fields).forEach(([key, value]) => {
            const field = form.querySelector(`[data-signup-field="${key}"]`);

            if (field && value !== null && value !== undefined && (field.value === '' || field.type === 'hidden')) {
                field.value = value;
            }
        });
    },

    initServices() {
        const form = document.querySelector('[data-signup-services]');

        if (!form) {
            return;
        }

        const rows = form.querySelector('[data-signup-service-rows]');
        const template = form.querySelector('[data-signup-service-template]');
        const add = form.querySelector('[data-signup-service-add]');
        const suggestions = form.querySelector('[data-signup-suggestions]');
        let next = rows.querySelectorAll('[data-signup-service]').length;

        const reveal = (scope) => scope.querySelectorAll('[data-signup-service-remove]').forEach((button) => {
            button.hidden = false;
        });
        const addRow = () => {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__index__', String(next++)));
            reveal(rows.lastElementChild);

            return rows.lastElementChild;
        };
        const emptyRow = () => [...rows.querySelectorAll('[data-signup-service]')].find((row) => row.querySelector('[data-signup-service-name]').value.trim() === '') || addRow();

        reveal(rows);
        add.hidden = false;
        add.addEventListener('click', () => addRow().querySelector('[data-signup-service-name]').focus());

        if (suggestions) {
            suggestions.hidden = false;
            suggestions.querySelectorAll('[data-signup-suggestion]').forEach((button) => {
                button.addEventListener('click', () => {
                    const row = emptyRow();
                    const duration = row.querySelector('[data-signup-service-duration]');
                    row.querySelector('[data-signup-service-name]').value = button.dataset.name;
                    row.querySelector('[data-signup-service-price]').value = button.dataset.price;

                    if ([...duration.options].some((option) => option.value === button.dataset.duration)) {
                        duration.value = button.dataset.duration;
                    }

                    button.disabled = true;
                });
            });
        }

        rows.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-signup-service-remove]');

            if (!remove) {
                return;
            }

            const row = remove.closest('[data-signup-service]');
            row.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });

            if (rows.querySelectorAll('[data-signup-service]').length > 1) {
                row.remove();
            }
        });
    },

    initDiscount() {
        const range = document.querySelector('[data-signup-discount]');

        if (!range) {
            return;
        }

        const value = document.querySelector('[data-signup-discount-value]');
        const example = document.querySelector('[data-signup-example]');

        range.addEventListener('input', () => {
            value.textContent = `−${range.value} %`;

            if (example) {
                const cents = Math.round(Number(example.dataset.price) * (100 - Number(range.value)) / 100);
                example.querySelector('[data-signup-example-price]').textContent = this.money(cents);
            }
        });
    },

    initCounter() {
        const area = document.querySelector('[data-signup-count]');

        if (!area) {
            return;
        }

        const output = document.querySelector('[data-signup-count-output]');
        area.addEventListener('input', () => {
            output.textContent = `${area.value.length} / ${area.maxLength}`;
        });
    },

    initPromo() {
        const box = document.querySelector('[data-signup-promo]');

        if (!box) {
            return;
        }

        const form = box.closest('form');
        const button = box.querySelector('[data-signup-promo-apply]');
        const result = document.querySelector('[data-signup-promo-result]');
        const first = document.querySelector('[data-signup-promo-first]');

        button.hidden = false;
        button.addEventListener('click', async () => {
            const code = box.querySelector('[data-signup-promo-code]').value.trim();

            if (code === '') {
                return;
            }

            const csrf = form.querySelector('input[type="hidden"]');
            const body = new FormData();
            body.append(csrf.name, csrf.value);
            body.append('code', code);
            body.append('plan', box.querySelector('[data-signup-promo-plan]').value);
            body.append('period', box.querySelector('[data-signup-promo-period]').value);
            button.disabled = true;

            try {
                const response = await fetch(box.dataset.signupPromo, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                result.dataset.state = response.ok ? 'success' : 'error';
                result.textContent = response.ok ? `${data.promo.code} : ${data.promo.label}` : data.error;

                if (response.ok && first) {
                    first.textContent = data.quote.firstLabel;
                }
            } catch {
                result.textContent = box.dataset.messagePromoUnavailable;
            } finally {
                button.disabled = false;
            }
        });
    },

    showError(node, message) {
        node.textContent = message;
        node.hidden = false;
    },

    money(cents) {
        return new Intl.NumberFormat(document.documentElement.lang || 'fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: cents % 100 === 0 ? 0 : 2,
        }).format(cents / 100);
    },
};

window.addEventListener('DOMContentLoaded', () => window.ProSignup.init());