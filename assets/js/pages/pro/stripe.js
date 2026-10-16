window.ProStripe = {
    stripe: null,

    init() {
        document.querySelectorAll('[data-stripe-form]').forEach((form) => this.initForm(form));

        const action = document.querySelector('[data-stripe-action]');

        if (action) {
            this.initAction(action);
        }
    },

    client(key) {
        if (!window.Stripe || !key) {
            return null;
        }

        this.stripe ??= window.Stripe(key, { locale: 'fr' });

        return this.stripe;
    },

    initForm(form) {
        const stripe = this.client(form.dataset.stripeKey);
        const error = form.querySelector('[data-stripe-error]');

        if (!stripe) {
            this.fail(error, 'Le module de paiement n’a pas pu être chargé. Rechargez la page.');
            return;
        }

        const elements = stripe.elements({ locale: 'fr' });
        const mounts = {
            card: elements.create('card', { hidePostalCode: true }),
            sepa_debit: elements.create('iban', { supportedCountries: ['SEPA'], placeholderCountry: 'FR' }),
        };
        const mandate = form.querySelector('[data-stripe-mandate]');
        const reference = form.querySelector('[data-stripe-reference]');
        const button = form.querySelector('[data-stripe-submit]');
        const type = () => form.querySelector('[data-stripe-type]:checked')?.value || 'card';
        const toggle = () => {
            form.querySelectorAll('[data-stripe-element]').forEach((node) => {
                node.hidden = node.dataset.stripeElement !== type();
            });
            mandate.hidden = type() !== 'sepa_debit';
            mandate.querySelector('input[type="checkbox"]').required = type() === 'sepa_debit';
        };

        Object.entries(mounts).forEach(([key, element]) => element.mount(form.querySelector(`[data-stripe-element="${key}"]`)));
        form.querySelectorAll('[data-stripe-type]').forEach((input) => input.addEventListener('change', toggle));
        toggle();

        form.addEventListener('submit', async (event) => {
            if (reference.value !== '') {
                return;
            }

            event.preventDefault();
            button.disabled = true;
            error.hidden = true;

            try {
                const response = await fetch(form.dataset.stripeSetup, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Impossible de préparer l’enregistrement du moyen de paiement.');
                }

                const details = {
                    name: form.querySelector('[data-stripe-name]')?.value || undefined,
                    email: form.querySelector('[data-stripe-email]')?.value || undefined,
                };
                const result = type() === 'sepa_debit'
                    ? await stripe.confirmSepaDebitSetup(data.setup.clientSecret, { payment_method: { sepa_debit: mounts.sepa_debit, billing_details: details } })
                    : await stripe.confirmCardSetup(data.setup.clientSecret, { payment_method: { card: mounts.card, billing_details: details } });

                if (result.error) {
                    throw new Error(result.error.message);
                }

                reference.value = result.setupIntent.id;
                form.submit();
            } catch (exception) {
                this.fail(error, exception.message);
                button.disabled = false;
            }
        });
    },

    initAction(form) {
        const stripe = this.client(form.dataset.stripeKey);

        if (!stripe) {
            return;
        }

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed) {
                return;
            }

            event.preventDefault();
            form.querySelector('button[type="submit"]').disabled = true;
            await stripe.confirmCardPayment(form.dataset.stripeSecret);
            form.dataset.confirmed = '1';
            form.submit();
        });
    },

    fail(node, message) {
        if (!node) {
            return;
        }

        node.textContent = message;
        node.hidden = false;
    },
};

window.addEventListener('DOMContentLoaded', () => window.ProStripe.init());