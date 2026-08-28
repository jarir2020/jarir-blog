/**
 * Date / number formatters used by the blog views.
 *
 * Locale defaults to en-US. Falls back gracefully if Intl data is missing.
 */

const locale = 'en-US';

export const formatDate = (value) => {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(d);
};

export const formatDateLong = (value) => {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(d);
};

export const computeReadingTime = (text) => {
    if (!text) return 1;
    const words = String(text).trim().split(/\s+/).length;
    return Math.max(1, Math.ceil(words / 200));
};
