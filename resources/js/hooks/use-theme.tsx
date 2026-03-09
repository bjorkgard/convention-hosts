import { useCallback, useSyncExternalStore } from 'react';

export const THEMES = ['default', 'ocean', 'forest', 'sunset', 'rose'] as const;
export type Theme = (typeof THEMES)[number];

export const THEME_LABELS: Record<Theme, string> = {
    default: 'Default',
    ocean: 'Ocean',
    forest: 'Forest',
    sunset: 'Sunset',
    rose: 'Rose',
};

export type UseThemeReturn = {
    readonly theme: Theme;
    readonly updateTheme: (theme: Theme) => void;
};

const listeners = new Set<() => void>();
let currentTheme: Theme = 'default';

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredTheme = (): Theme => {
    if (typeof window === 'undefined') return 'default';
    return (localStorage.getItem('theme') as Theme) || 'default';
};

const applyTheme = (theme: Theme): void => {
    if (typeof document === 'undefined') return;
    document.documentElement.setAttribute('data-theme', theme);
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    if (!localStorage.getItem('theme')) {
        localStorage.setItem('theme', 'default');
        setCookie('theme', 'default');
    }

    currentTheme = getStoredTheme();
    applyTheme(currentTheme);
}

export function useTheme(): UseThemeReturn {
    const theme: Theme = useSyncExternalStore(
        subscribe,
        () => currentTheme,
        () => 'default',
    );

    const updateTheme = useCallback((newTheme: Theme): void => {
        currentTheme = newTheme;

        localStorage.setItem('theme', newTheme);
        setCookie('theme', newTheme);

        applyTheme(newTheme);
        notify();

        // Reload so the server-rendered html data-theme attribute is correct,
        // preventing any flash on the next hard navigation.
        window.location.reload();
    }, []);

    return { theme, updateTheme } as const;
}
