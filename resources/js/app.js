import Alpine from 'alpinejs';

window.Alpine = Alpine;

/** Tehnički trag za mobilni WebView; ne šalje sadržaj dokumenata ni korisničke podatke. */
const mobileLog = (event, context = {}) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (! csrfToken) {
        return;
    }

    fetch('/dijagnostika/mobile', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ event, context }),
    }).catch(() => {});
};

const withMobilePayload = (url, enabled) => {
    if (! enabled) {
        return url;
    }

    return `${url}${url.includes('?') ? '&' : '?'}mobile_payload=1`;
};

const blobFromBase64 = (contents, mime) => {
    const binary = atob(contents);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index++) {
        bytes[index] = binary.charCodeAt(index);
    }

    return new Blob([bytes], { type: mime });
};

/**
 * Tema: svijetla, tamna ili po sistemu. Izbor se pamti u pregledniku jer je vezan
 * za uređaj, ne za podatke aplikacije.
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

/** Jedinstvena potvrda za fiskalne i destruktivne radnje u aplikaciji. */
Alpine.store('confirmation', {
    open: false,
    message: '',
    running: false,
    action: null,

    ask(message, action) {
        this.open = true;
        this.message = message;
        this.running = false;
        this.action = action;
    },

    dismiss() {
        if (! this.running) {
            this.open = false;
        }
    },

    async execute() {
        if (this.running || ! this.action) {
            return;
        }

        this.running = true;

        try {
            await this.action();
        } finally {
            this.open = false;
            this.running = false;
            this.action = null;
        }
    },
});

/** Zadnji poznati status fiskalnog uređaja; osvježava se tiho samo dok je ekran aktivan. */
Alpine.data('fiscalHealth', ({ url, initial }) => ({
    health: initial,
    checking: false,
    timer: null,
    onVisibilityChange: null,

    init() {
        this.refresh();
        this.timer = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.refresh();
            }
        }, 60_000);
        this.onVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                this.refresh();
            }
        };
        document.addEventListener('visibilitychange', this.onVisibilityChange);
    },

    destroy() {
        window.clearInterval(this.timer);
        document.removeEventListener('visibilitychange', this.onVisibilityChange);
    },

    async refresh() {
        if (this.checking) {
            return;
        }

        this.checking = true;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (response.ok) {
                this.health = await response.json();
                this.$dispatch('fiscal-health-updated', this.health);
            }
        } catch {
            // Existing status stays visible when the network is temporarily unavailable.
        } finally {
            this.checking = false;
        }
    },
}));

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || ! form.dataset.confirm || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    Alpine.store('confirmation').ask(form.dataset.confirm, () => {
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });
});

/**
 * Forma računa. Iznosi se drže u fenizima: cijena je sa porezom,
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

Alpine.data('invoiceForm', ({ items, articles, clients, taxRates, currency, currencySymbols, clientId, showMore }) => ({
    items: items.length ? items.map((item) => ({ ...blankItem(), ...item })) : [blankItem()],
    articles,
    clients,
    taxRates,
    currency,
    currencySymbols,
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

    /** Artikal nosi naziv, jedinicu, poresku oznaku i zadnju cijenu. */
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

    currencySymbol() {
        return this.currencySymbols[this.currency] ?? this.currency;
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

Alpine.data('menuSettings', ({ modules, menuModules, maxMenuItems }) => ({
    modules: modules.map((module) => ({
        ...module,
        placement: menuModules.includes(module.key) ? 'menu' : 'drawer',
    })),
    maxMenuItems,

    menu() {
        return this.modules.filter((module) => module.placement === 'menu');
    },

    drawer() {
        return this.modules.filter((module) => module.placement === 'drawer');
    },

    normalizeLimit() {
        this.menu().slice(this.maxMenuItems).forEach((module) => {
            module.placement = 'drawer';
        });
    },

    move(key, direction) {
        const module = this.modules.find((candidate) => candidate.key === key);
        if (! module) return;

        const samePlacement = this.modules.filter((candidate) => candidate.placement === module.placement);
        const index = samePlacement.findIndex((candidate) => candidate.key === key);
        const target = index + direction;
        if (target < 0 || target >= samePlacement.length) return;

        const first = this.modules.indexOf(samePlacement[index]);
        const second = this.modules.indexOf(samePlacement[target]);
        [this.modules[first], this.modules[second]] = [this.modules[second], this.modules[first]];
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
    submitting: false,

    init() {
        this.$nextTick(() => this.focusFirstEmpty());

        // Jump/WebKit ponekad završi učitavanje webviewa nakon Alpine inicijalizacije.
        // Drugi pokušaj fokusira PIN bez čekanja na dodir korisnika.
        window.setTimeout(() => this.focusFirstEmpty(), 250);
    },

    box(index) {
        return this.$refs.box ? this.$root.querySelectorAll('input[type=password]')[index] : null;
    },

    focusFirstEmpty() {
        const index = this.digits.findIndex((digit) => digit === '');

        this.box(index === -1 ? 3 : index)?.focus();
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
        if (! this.filled() || this.submitting) return;

        this.submitting = true;
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

/**
 * Radnje nad računom: fiskalizacija, kopija, storno, fiskalni dokument i mail.
 *
 * Živi ovdje, a ne u opisu liste, jer se detalji koriste na punoj stranici
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

    pdfFile: null,
    pdfPreparing: false,

    receiptModal: false,
    receiptLoading: false,
    receiptError: '',
    receiptUrl: '',
    receiptHtml: '',
    receiptSourceUrl: '',
    receiptVerificationUrl: '',
    receiptKind: 'image',

    async preparePdf(url, filename, useMobilePayload = false) {
        if (this.pdfPreparing) {
            return;
        }

        mobileLog('invoice_pdf_clicked', { has_prepared_file: this.pdfFile !== null });

        if (this.pdfFile) {
            await this.sharePreparedPdf();

            return;
        }

        this.pdfPreparing = true;

        try {
            const response = await fetch(withMobilePayload(url, useMobilePayload), { headers: { Accept: 'application/pdf' } });

            mobileLog('invoice_pdf_response', {
                status: response.status,
                content_type: response.headers.get('content-type'),
            });

            if (! response.ok) {
                throw new Error('PDF nije dostupan.');
            }

            const document = useMobilePayload ? await response.json() : null;
            const documentBlob = document
                ? blobFromBase64(document.contents, document.mime)
                : await response.blob();

            this.pdfFile = new File([documentBlob], document?.filename || filename, { type: 'application/pdf' });
            mobileLog('invoice_pdf_prepared', { bytes: this.pdfFile.size });
            await this.sharePreparedPdf();
        } catch {
            mobileLog('invoice_pdf_failed');
            this.flash('PDF nije moguće pripremiti.', 'error');
        } finally {
            this.pdfPreparing = false;
        }
    },

    async sharePreparedPdf() {
        if (! this.pdfFile) {
            return;
        }

        const shareData = { title: this.pdfFile.name, files: [this.pdfFile] };
        mobileLog('invoice_pdf_share_started', {
            navigator_share: Boolean(navigator.share),
            can_share_file: ! navigator.canShare || navigator.canShare(shareData),
        });

        try {
            if (navigator.share && (! navigator.canShare || navigator.canShare(shareData))) {
                await navigator.share(shareData);
                mobileLog('invoice_pdf_share_opened');

                return;
            }
        } catch (error) {
            if (error?.name === 'AbortError') {
                mobileLog('invoice_pdf_share_cancelled');
                return;
            }

            mobileLog('invoice_pdf_share_failed', { error: error?.name || 'unknown' });
        }

        const link = document.createElement('a');
        link.href = URL.createObjectURL(this.pdfFile);
        link.download = this.pdfFile.name;
        link.click();
        URL.revokeObjectURL(link.href);
        mobileLog('invoice_pdf_download_fallback');
        this.flash('PDF je preuzet na uređaj.');
    },

    async openReceipt(url, kind = 'image', verificationUrl = '', useMobilePayload = false) {
        this.releaseReceiptUrl();
        this.receiptSourceUrl = url;
        this.receiptVerificationUrl = verificationUrl;
        this.receiptKind = kind;
        this.receiptError = '';
        this.receiptUrl = '';
        this.receiptHtml = '';
        this.receiptLoading = true;
        this.receiptModal = true;
        mobileLog('fiscal_receipt_clicked', { kind });

        try {
            const response = await fetch(withMobilePayload(url, useMobilePayload), {
                headers: { Accept: kind === 'html' ? 'text/html' : '*/*' },
            });

            mobileLog('fiscal_receipt_response', {
                kind,
                status: response.status,
                content_type: response.headers.get('content-type'),
            });

            if (wentToUnlock(response)) {
                return;
            }

            if (! response.ok) {
                throw new Error('Fiskalni dokument nije dostupan.');
            }

            const document = useMobilePayload ? await response.json() : null;

            if (kind === 'html') {
                this.receiptHtml = document ? atob(document.contents) : await response.text();
                mobileLog('fiscal_receipt_html_ready', { characters: this.receiptHtml.length });
            } else {
                const documentBlob = document
                    ? blobFromBase64(document.contents, document.mime)
                    : await response.blob();
                this.receiptUrl = document
                    ? `data:${document.mime};base64,${document.contents}`
                    : await this.dataUrl(documentBlob);
                mobileLog('fiscal_receipt_binary_ready', { kind, bytes: documentBlob.size });
            }
        } catch {
            mobileLog('fiscal_receipt_failed', { kind });
            this.receiptError = 'Fiskalni dokument nije moguće prikazati. Pokušajte ponovo.';
        } finally {
            this.receiptLoading = false;
        }
    },

    closeReceipt() {
        this.releaseReceiptUrl();
        this.receiptModal = false;
        this.receiptUrl = '';
        this.receiptHtml = '';
    },

    releaseReceiptUrl() {
        // Data URL nema resurs koji se posebno oslobađa.
    },

    dataUrl(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(blob);
        });
    },

    receiptFailed() {
        mobileLog('fiscal_receipt_render_failed', { kind: this.receiptKind });
        this.receiptError = 'Fiskalni račun nije dostupan. Pokušajte ponovo.';
    },

    /** Fiskalne radnje traže potvrdu prije izvršenja. */
    fiscalAction(url, message) {
        Alpine.store('confirmation').ask(message, () => this.runFiscalAction(url));
    },

    async runFiscalAction(url) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });

            if (wentToUnlock(response)) return;

            const data = await response.json().catch(() => ({}));

            this.flash(data.message || (response.ok ? 'Gotovo.' : 'Radnja nije uspjela.'),
                response.ok ? 'success' : 'error');

            if (response.ok) {
                await this.refreshAfterAction(data);
            }
        } catch {
            this.flash('Radnja nije uspjela.', 'error');
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

Alpine.start();
