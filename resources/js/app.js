import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Tema: svijetla, tamna ili po sistemu. Izbor se pamti u pregledniku jer je vezan
 * za uređaj, ne za podatke aplikacije. Klase su iste kao u v1 — `light` na <html>.
 */
Alpine.store('theme', {
    choice: 'dark',

    init() {
        this.choice = localStorage.getItem('theme') || 'dark';
        this.apply();

        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', () => this.choice === 'system' && this.apply());
    },

    set(choice) {
        this.choice = choice;
        localStorage.setItem('theme', choice);
        this.apply();
    },

    apply() {
        const dark = this.choice === 'system'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
            : this.choice === 'dark';

        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.classList.toggle('light', ! dark);
    },
});

/**
 * Forma računa. Iznosi se drže u fenizima, kao u v1: cijena je sa porezom,
 * osnovica i porez se iz nje izvode. Preglednik računa samo prikaz — server
 * na kraju sve preračuna, pa ovo nije izvor istine.
 */
const blankItem = () => ({
    article_id: '',
    name: '',
    description: '',
    unit: 'kom',
    tax_label: '',
    tax_rate: 0,
    quantity: 1,
    unit_price: 0,
    open: false,
    search: '',
});

/** Iz cijene sa porezom izvedi osnovicu i porez. */
const splitTax = (inclusive, rateBasisPoints) => {
    const base = Math.round(inclusive / (1 + rateBasisPoints / 10000));

    return { base, tax: inclusive - base };
};

const money = (cents) => new Intl.NumberFormat('de-DE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format((cents || 0) / 100);

Alpine.data('invoiceForm', ({ items, articles, clients, taxRates, currency, clientId, showMore }) => ({
    items: items.length ? items.map((item) => ({ ...blankItem(), ...item })) : [blankItem()],
    articles,
    clients,
    taxRates,
    currency,
    clientId: clientId ?? '',
    clientOpen: false,
    clientSearch: '',
    showMore,

    addItem() {
        this.items.push(blankItem());
    },

    removeItem(index) {
        this.items.splice(index, 1);

        if (! this.items.length) {
            this.items.push(blankItem());
        }
    },

    selectedClient() {
        return this.clients.find((client) => String(client.id) === String(this.clientId)) || null;
    },

    matchingClients() {
        const needle = this.clientSearch.toLowerCase().trim();

        if (! needle) return this.clients;

        return this.clients.filter((client) =>
            `${client.name} ${client.email || ''} ${client.phone || ''}`.toLowerCase().includes(needle));
    },

    pickClient(client) {
        this.clientId = client.id;
        this.clientOpen = false;
        this.clientSearch = '';
    },

    selectedArticle(item) {
        return this.articles.find((article) => String(article.id) === String(item.article_id)) || null;
    },

    matchingArticles(item) {
        const needle = (item.search || '').toLowerCase().trim();

        if (! needle) return this.articles;

        return this.articles.filter((article) =>
            `${article.name} ${article.description || ''}`.toLowerCase().includes(needle));
    },

    /** Artikal nosi naziv, jedinicu, poresku oznaku i zadnju cijenu — kao u v1. */
    pickArticle(item, article) {
        item.article_id = article.id;
        item.name = article.name;
        item.description = article.description || '';
        item.unit = article.unit || 'kom';
        item.tax_label = article.tax_label || '';
        item.tax_rate = this.rateOf(item.tax_label);
        item.unit_price = article.last_unit_price || 0;
        item.open = false;
        item.search = '';
    },

    clearArticle(item) {
        Object.assign(item, blankItem(), { quantity: item.quantity });
    },

    rateOf(label) {
        return this.taxRates[label] ?? 0;
    },

    lineTotal(item) {
        return (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    },

    lineBase(item) {
        return splitTax(this.lineTotal(item), item.tax_rate).base;
    },

    lineTax(item) {
        return splitTax(this.lineTotal(item), item.tax_rate).tax;
    },

    subtotal() {
        return this.items.reduce((sum, item) => sum + this.lineBase(item), 0);
    },

    taxTotal() {
        return this.items.reduce((sum, item) => sum + this.lineTax(item), 0);
    },

    total() {
        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },

    money,

    /** Cijena se kuca kao na kasi: cifre popunjavaju iznos zdesna nalijevo. */
    typePrice(item, event) {
        const digits = event.target.value.replace(/[^0-9]/g, '');
        item.unit_price = parseInt(digits, 10) || 0;
        event.target.value = money(item.unit_price);
    },

    typeQuantity(item, event) {
        const digits = event.target.value.replace(/\D/g, '');
        event.target.value = digits;
        item.quantity = parseInt(digits, 10) || 0;
    },

    fixQuantity(item, event) {
        item.quantity = Math.max(1, parseInt(event.target.value, 10) || 1);
        event.target.value = item.quantity;
    },
}));

/**
 * Liste šifarnika: forma se otvara u draweru i šalje preko XHR-a, kao kod računa.
 * Jedan opis za klijente, artikle, bankovne račune i valute — razlikuju se samo URL-ovi.
 */
Alpine.data('entityIndex', () => ({
    formDrawer: false,
    formLoading: false,
    formHtml: '',
    formTitle: '',
    formErrors: {},
    saving: false,

    async openForm(url, title) {
        this.formTitle = title;
        this.formErrors = {};
        this.formHtml = '';
        this.formLoading = true;
        this.formDrawer = true;
        this.formHtml = await this.load(url);
        this.formLoading = false;
    },

    closeForm() {
        this.formDrawer = false;
    },

    async load(url) {
        const failure = '<p class="py-8 text-center text-sm font-bold text-[var(--color-error)]">Nije moguće učitati.</p>';

        try {
            const response = await fetch(url, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (wentToUnlock(response)) return failure;

            return response.ok ? await response.text() : failure;
        } catch {
            return failure;
        }
    },

    /** Prepiši listu bez ponovnog učitavanja stranice. */
    async refreshList() {
        const html = await this.load(window.location.href);
        const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-entity-list]');
        const current = this.$el.querySelector('[data-entity-list]');

        if (fresh && current) {
            current.innerHTML = fresh.innerHTML;
        }
    },

    async submitForm(event) {
        if (this.saving) return;

        this.saving = true;
        this.formErrors = {};

        try {
            const form = event.target;
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });

            if (wentToUnlock(response)) return;

            if (response.status === 422) {
                this.formErrors = (await response.json()).errors || {};
                this.$nextTick(() => this.$el.querySelector('[data-error-summary]')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'center' }));

                return;
            }

            if (! response.ok) {
                this.formErrors = { _: ['Čuvanje nije uspjelo. Pokušajte ponovo.'] };

                return;
            }

            const saved = await response.json();
            this.formDrawer = false;
            window.dispatchEvent(new CustomEvent('app-flash', { detail: saved.message }));
            await this.refreshList();
        } catch {
            this.formErrors = { _: ['Čuvanje nije uspjelo. Pokušajte ponovo.'] };
        } finally {
            this.saving = false;
        }
    },
}));

/**
 * Unos PIN-a: jedno polje po cifri.
 *
 * Kucanje pomjera fokus naprijed, Backspace nazad, strelice biraju polje, a kad se
 * unese četvrta cifra forma se šalje sama. Lijepljenje cijelog PIN-a takođe radi.
 */
Alpine.data('pinEntry', () => ({
    digits: ['', '', '', ''],

    init() {
        this.$nextTick(() => this.box(0)?.focus());
    },

    box(index) {
        return this.$refs.box ? this.$root.querySelectorAll('input[type=password]')[index] : null;
    },

    filled() {
        return this.digits.every((digit) => digit !== '');
    },

    type(index, event) {
        const digit = event.target.value.replace(/\D/g, '').slice(-1);

        this.digits[index] = digit;
        event.target.value = digit;

        if (! digit) return;

        if (index < 3) {
            this.box(index + 1)?.focus();
        }

        this.submitWhenFilled();
    },

    key(index, event) {
        if (event.key === 'Backspace' && ! this.digits[index] && index > 0) {
            // Prazno polje: briši prethodnu cifru i vrati fokus na nju.
            event.preventDefault();
            this.digits[index - 1] = '';
            const previous = this.box(index - 1);
            previous.value = '';
            previous.focus();

            return;
        }

        if (event.key === 'ArrowLeft' && index > 0) {
            event.preventDefault();
            this.box(index - 1)?.focus();
        }

        if (event.key === 'ArrowRight' && index < 3) {
            event.preventDefault();
            this.box(index + 1)?.focus();
        }
    },

    paste(event) {
        const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);

        if (! pasted) return;

        pasted.split('').forEach((digit, index) => {
            this.digits[index] = digit;
            const box = this.box(index);

            if (box) box.value = digit;
        });

        this.box(Math.min(pasted.length, 3))?.focus();
        this.submitWhenFilled();
    },

    submitWhenFilled() {
        if (! this.filled()) return;

        this.$nextTick(() => this.$root.requestSubmit());
    },
}));

/**
 * Zaključavanje usred rada u draweru.
 *
 * Kad automatsko zaključavanje udari, POST dobije preusmjerenje na ekran za PIN.
 * `fetch` ga isprati, vrati 200 sa HTML-om, `response.json()` pukne i korisnik
 * dobije „Čuvanje nije uspjelo" na sasvim ispravnom unosu — a ništa nije sačuvano.
 * Zato se to prepozna i korisnik se pošalje na PIN.
 */
const wentToUnlock = (response) => {
    if (! response.redirected || ! response.url.includes('/unlock')) {
        return false;
    }

    window.location = response.url;

    return true;
};

window.wentToUnlock = wentToUnlock;

/**
 * Radnje nad računom: fiskalizacija, kopija, storno, slika računa i mail.
 *
 * Živi ovdje, a ne u opisu liste, jer isti partial detalja koristi i puna stranica
 * računa — tamo bez ovoga svaki fiskalni dugmić samo javi grešku u konzoli.
 * Osvježavanje se traži preko `refreshAfterAction`, koje lista prepisuje.
 */
const invoiceActions = () => ({
    emailModal: false,
    emailSending: false,
    emailError: '',
    emailUrl: '',
    emailReceipts: [],
    emailForm: { to: '', subject: '', body: '', attach_pdf: true, attach_fiscal_record_ids: [] },

    receiptModal: false,
    receiptUrl: '',
    receiptKind: 'image',

    confirm: { open: false, message: '', running: false, action: null },

    openReceipt(url, kind = 'image') {
        this.receiptUrl = url;
        this.receiptKind = kind;
        this.receiptModal = true;
    },

    /** Fiskalne radnje traže potvrdu prije izvršenja. */
    fiscalAction(url, message) {
        this.confirm = { open: true, message, running: false, action: url };
    },

    async runConfirmed() {
        if (this.confirm.running) return;

        this.confirm.running = true;

        try {
            const response = await fetch(this.confirm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });

            if (wentToUnlock(response)) return;

            const data = await response.json().catch(() => ({}));

            this.confirm.open = false;
            this.flash(data.message || (response.ok ? 'Gotovo.' : 'Radnja nije uspjela.'),
                response.ok ? 'success' : 'error');

            if (response.ok) {
                await this.refreshAfterAction(data);
            }
        } catch {
            this.confirm.open = false;
            this.flash('Radnja nije uspjela.', 'error');
        } finally {
            this.confirm.running = false;
        }
    },

    /** Puna stranica se samo ponovo učita; lista ovo prepisuje. */
    async refreshAfterAction(data) {
        window.location = data.invoice_id ? `/racuni/${data.invoice_id}` : window.location.href;
    },

    openEmail(defaults) {
        this.emailError = '';
        this.emailUrl = defaults.url;
        this.emailReceipts = defaults.receipts;
        this.emailForm = {
            to: defaults.to,
            subject: defaults.subject,
            body: defaults.body,
            attach_pdf: true,
            attach_fiscal_record_ids: defaults.receipts.map((record) => record.id),
        };
        this.emailModal = true;
    },

    async sendEmail() {
        if (this.emailSending) return;

        this.emailSending = true;
        this.emailError = '';

        try {
            const response = await fetch(this.emailUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify(this.emailForm),
            });

            if (wentToUnlock(response)) return;

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.emailError = data.message || Object.values(data.errors || {})[0]?.[0]
                    || 'Slanje nije uspjelo.';

                return;
            }

            this.emailModal = false;
            this.flash(data.message);
        } catch {
            this.emailError = 'Slanje nije uspjelo.';
        } finally {
            this.emailSending = false;
        }
    },

    flash(message, type = 'success') {
        window.dispatchEvent(new CustomEvent('app-flash', { detail: { message, type } }));
    },
});

Alpine.data('invoiceActions', invoiceActions);
window.invoiceActions = invoiceActions;

Alpine.start();
