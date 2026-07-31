import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

Alpine.data('invoiceForm', (initialItems, articles, taxRates, currency) => ({
    items: initialItems.length ? initialItems.map((item) => ({ ...blankItem(), ...item })) : [blankItem()],
    articles,
    taxRates,
    currency,
    extraOpen: false,

    addItem() {
        this.items.push(blankItem());
    },

    removeItem(index) {
        this.items.splice(index, 1);

        if (! this.items.length) {
            this.items.push(blankItem());
        }
    },

    /** Artikli koji odgovaraju pretrazi u redu stavke. */
    matches(item) {
        const needle = (item.search || '').toLowerCase().trim();

        if (! needle) return this.articles;

        return this.articles.filter((article) =>
            `${article.name} ${article.description || ''}`.toLowerCase().includes(needle));
    },

    selected(item) {
        return this.articles.find((article) => String(article.id) === String(item.article_id)) || null;
    },

    pick(item, article) {
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

    clear(item) {
        Object.assign(item, blankItem(), { quantity: item.quantity });
    },

    rateOf(label) {
        return this.taxRates[label] ?? 0;
    },

    /** Porez se bira i ručno, pa stopa mora pratiti oznaku. */
    syncRate(item) {
        item.tax_rate = this.rateOf(item.tax_label);
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

Alpine.start();
