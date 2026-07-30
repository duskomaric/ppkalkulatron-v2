import Alpine from 'alpinejs';

/**
 * Forma računa. Stavke se dodaju i sabiraju u pregledniku da korisnik odmah vidi iznos;
 * server na kraju sve preračuna iz količine i cijene, pa ovo nije izvor istine.
 */
const blankItem = () => ({ article_id: '', name: '', unit: 'kom', tax_label: '', quantity: 1, unit_price: '' });

Alpine.data('invoiceForm', (initialItems, articles) => ({
    items: initialItems.length ? initialItems : [blankItem()],
    articles,

    addItem() {
        this.items.push(blankItem());
    },

    removeItem(index) {
        this.items.splice(index, 1);
    },

    /** Kad se izabere artikl, prepiši naziv, jedinicu, porez i zadnju cijenu. */
    pickArticle(index) {
        const item = this.items[index];
        const article = this.articles.find((a) => String(a.id) === String(item.article_id));

        if (!article) return;

        item.name = article.name;
        item.unit = article.unit ?? 'kom';
        item.tax_label = article.tax_label ?? '';

        if (article.last_unit_price) {
            item.unit_price = (article.last_unit_price / 100).toFixed(2);
        }
    },

    lineTotal(item) {
        return (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    },

    total() {
        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },

    money(amount) {
        return new Intl.NumberFormat('sr-Latn-BA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
    },
}));

Alpine.start();
