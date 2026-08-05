import Alpine from 'alpinejs';

window.Alpine = Alpine;

/** Tehnički trag za mobilni WebView; ne šalje sadržaj dokumenata ni korisničke podatke. */
const mobileLog = (event, context = {}) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (! csrfToken) {
        return;
    }

    fetch('/dijagnostika/mobilna', {
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

Alpine.data('menuSettings', ({ modules, menuModules, maxMenuItems, primaryColor }) => ({
    primaryColor,

    // Boja se vidi odmah, prije čuvanja — ista promjenljiva koju zaglavlje ubaci u stranicu.
    applyColor() {
        const [, r, g, b] = /^#(\w{2})(\w{2})(\w{2})$/.exec(this.primaryColor) ?? [];

        if (r) {
            document.documentElement.style.setProperty(
                '--primary-base',
                [r, g, b].map((channel) => parseInt(channel, 16)).join(', '),
            );
        }
    },

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
    if (! response.redirected || ! response.url.includes('/otkljucaj')) {
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

/**
 * Uvoz računa sa fiskalne kase.
 *
 * Kasa vrati spisak, pa se sadržaj svakog računa preuzima posebno — zato uvoz ide
 * u serijama: jedan zahtjev ne stoji minutu i korisnik vidi napredak.
 */
Alpine.data('invoiceImport', ({ searchUrl, importUrl, from, to }) => ({
    from,
    to,
    numbering: 'own',
    searching: false,
    importing: false,
    searched: false,
    rows: [],
    selected: [],
    skipped: 0,
    done: 0,
    summary: null,
    error: '',

    get total() {
        return this.rows.length;
    },

    get available() {
        return this.rows.filter((row) => ! row.imported);
    },

    get allSelected() {
        return this.available.length > 0 && this.selected.length === this.available.length;
    },

    get progress() {
        return this.selected.length ? Math.round((this.done / this.selected.length) * 100) : 0;
    },

    toggleAll() {
        this.selected = this.allSelected ? [] : this.available.map((row) => row.number);
    },

    async search() {
        this.searching = true;
        this.error = '';
        this.summary = null;

        const data = await this.post(this.searchUrl(), { from: this.from, to: this.to });

        if (data) {
            this.rows = data.invoices ?? [];
            this.skipped = data.skipped ?? 0;
            this.selected = this.available.map((row) => row.number);
            this.searched = true;
        }

        this.searching = false;
    },

    async runImport() {
        if (! this.selected.length) return;

        this.importing = true;
        this.error = '';
        this.done = 0;

        const result = { imported: 0, skipped: 0, failed: [] };
        const batches = [];
        for (let i = 0; i < this.selected.length; i += 10) {
            batches.push(this.selected.slice(i, i + 10));
        }

        for (const batch of batches) {
            const data = await this.post(this.importUrl(), {
                numbers: batch,
                use_fiscal_numbers: this.numbering === 'fiscal',
            });

            if (! data) break;

            result.imported += data.imported ?? 0;
            result.skipped += data.skipped ?? 0;
            result.failed.push(...(data.failed ?? []));
            this.done += batch.length;
        }

        this.summary = result;
        this.importing = false;

        if (result.imported > 0) {
            this.rows.forEach((row) => {
                if (this.selected.includes(row.number)) row.imported = true;
            });
            this.selected = [];
        }
    },

    searchUrl() {
        return searchUrl;
    },

    importUrl() {
        return importUrl;
    },

    async post(url, body) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify(body),
            });

            if (wentToUnlock(response)) return null;

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.error = data.message || 'Radnja nije uspjela.';

                return null;
            }

            return data;
        } catch {
            this.error = 'Kasa nije dostupna.';

            return null;
        }
    },

    money(value) {
        return new Intl.NumberFormat('sr-Latn-BA', { minimumFractionDigits: 2 }).format(value ?? 0);
    },

    day(value) {
        return value ? value.slice(8, 10) + '.' + value.slice(5, 7) + '.' + value.slice(0, 4) + '.' : '';
    },
}));

Alpine.data('invoiceActions', invoiceActions);

/**
 * Dugme za preuzimanje backupa baze.
 *
 * Preuzimanje ne mijenja stranicu, pa se kraj ne vidi po navigaciji — server uz
 * samu datoteku pošalje kolačić, a ovdje se on čeka pa se dugme vraća u normalu.
 */
Alpine.data('databaseBackup', () => ({
    preparing: false,
    timer: null,
    fallback: null,

    start() {
        this.preparing = true;
        this.timer = window.setInterval(() => {
            if (document.cookie.includes('backup-preuzet')) {
                this.finish();
            }
        }, 300);

        // Ako preuzimanje ne uspije, kolačić nikad ne stigne — dugme se ipak oslobodi.
        this.fallback = window.setTimeout(() => this.finish(), 120000);
    },

    finish() {
        window.clearInterval(this.timer);
        window.clearTimeout(this.fallback);
        document.cookie = 'backup-preuzet=; Max-Age=0; path=/';
        this.preparing = false;
    },
}));

/**
 * Vraćanje backupa. Uređaj u jednom zahtjevu prima svega par megabajta, pa se
 * datoteka šalje isjeckana, a server je ponovo sastavlja i tek na kraju vraća podatke.
 */
Alpine.data('databaseRestore', ({ url, chunkBytes }) => ({
    sending: false,
    restoring: false,
    sent: 0,
    total: 0,
    error: '',

    get progress() {
        return this.total ? Math.round((this.sent / this.total) * 100) : 0;
    },

    confirm() {
        const file = this.$refs.archive.files[0];

        if (! file) {
            this.error = 'Odaberite backup datoteku.';

            return;
        }

        Alpine.store('confirmation').ask(
            'Vraćanje backupa briše sve što je sada u aplikaciji. Nastaviti?',
            () => this.send(file),
        );
    },

    async send(file) {
        this.sending = true;
        this.restoring = false;
        this.error = '';
        this.sent = 0;
        this.total = file.size;

        const chunks = Math.max(1, Math.ceil(file.size / chunkBytes));

        for (let index = 0; index < chunks; index++) {
            const slice = file.slice(index * chunkBytes, (index + 1) * chunkBytes);
            const last = index === chunks - 1;

            const body = new FormData();
            body.append('chunk', slice, 'backup.part');
            body.append('index', index);
            body.append('last', last ? '1' : '0');

            // Zamjena baze i migracije traju, pa posljednji dio nosi drugu poruku.
            this.restoring = last;

            const data = await this.post(body);

            if (! data) {
                this.sending = false;
                this.restoring = false;

                return;
            }

            this.sent += slice.size;

            if (data.redirect) {
                window.location = data.redirect;

                return;
            }
        }

        this.sending = false;
    },

    async post(body) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body,
            });

            if (wentToUnlock(response)) return null;

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.error = data.message || Object.values(data.errors ?? {}).flat()[0] || 'Backup nije vraćen.';

                return null;
            }

            return data;
        } catch {
            this.error = 'Prenos backupa je prekinut. Pokušajte ponovo.';

            return null;
        }
    },
}));

Alpine.start();
