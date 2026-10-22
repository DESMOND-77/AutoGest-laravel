const NF_CURRENCY = new Intl.NumberFormat('fr-GA', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 });
const NF_DECIMAL = new Intl.NumberFormat('fr-GA', { maximumFractionDigits: 0 });

export const formatMoney = (value) => NF_CURRENCY.format(Number(value || 0)).replace(/\u202f/g, '\u00a0');
export const formatNumber = (value) => NF_DECIMAL.format(Number(value || 0));
export const formatDate = (value, options = { day: '2-digit', month: 'short', year: 'numeric' }) =>
    new Intl.DateTimeFormat('fr-GA', options).format(new Date(value));

export default { formatMoney, formatNumber, formatDate };
