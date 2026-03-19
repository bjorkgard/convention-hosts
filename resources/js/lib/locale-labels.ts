/**
 * Human-readable labels for known locale codes.
 * Unknown codes fall back to code.toUpperCase().
 */
export const LOCALE_LABELS: Record<string, string> = {
    en: 'English',
    sv: 'Svenska',
};

export function localeLabel(code: string): string {
    return LOCALE_LABELS[code] ?? code.toUpperCase();
}
