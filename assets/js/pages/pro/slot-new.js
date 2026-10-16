window.PublishPage = {
    form: null,
    money: new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 2 }),

    init() {
        this.form = document.querySelector('[data-publish-form]');

        if (!this.form) {
            return;
        }

        this.form.querySelectorAll('select, input').forEach((field) => {
            field.addEventListener('input', () => this.render());
            field.addEventListener('change', () => this.render());
        });

        this.render();
    },

    field(name) {
        return this.form.querySelector(`[data-publish-${name}]`);
    },

    preview(name) {
        return document.querySelector(`[data-preview-${name}]`);
    },

    render() {
        const option = this.field('service').selectedOptions[0];
        const day = this.field('day').selectedOptions[0];
        const time = this.field('time').value;
        const discount = Number(this.field('discount').value);
        const removal = Number(this.field('removal').value);
        const original = Number(option.dataset.price);
        const price = Math.round(original * (100 - discount) / 100);

        this.field('discount-label').textContent = `-${discount} %`;
        this.preview('service').textContent = option.dataset.name;
        this.preview('duration').textContent = option.dataset.duration;
        this.preview('day').textContent = day.textContent.split(' · ')[0];
        this.preview('time').textContent = time || '--:--';
        this.preview('old').textContent = this.format(original);
        this.preview('price').textContent = this.format(price);
        this.preview('earn').textContent = this.format(price);
        this.preview('discount').textContent = `-${discount} %`;
        this.preview('expires').textContent = this.expiresAt(time, removal);
    },

    format(cents) {
        return this.money.format(cents / 100);
    },

    expiresAt(time, removal) {
        if (!/^\d{2}:\d{2}$/.test(time)) {
            return '—';
        }

        const [hours, minutes] = time.split(':').map(Number);
        const total = (hours * 60 + minutes - removal + 1440) % 1440;

        return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
    },
};

window.addEventListener('DOMContentLoaded', () => window.PublishPage.init());