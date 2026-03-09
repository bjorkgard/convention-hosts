import { useCallback, useSyncExternalStore } from 'react';
import { canSetCookies } from '@/hooks/use-cookie-consent';

export const THEMES = ['default', 'ocean', 'forest', 'sunset', 'rose', 'apple', 'android'] as const;
export type Theme = (typeof THEMES)[number];

export const THEME_LABELS: Record<Theme, string> = {
    default: 'Default',
    ocean: 'Ocean',
    forest: 'Forest',
    sunset: 'Sunset',
    rose: 'Rose',
    apple: 'Apple',
    android: 'Android',
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

const isIOSDevice = (): boolean => {
    if (typeof navigator === 'undefined') return false;
    if (/iPhone|iPod|iPad/.test(navigator.userAgent)) return true;
    // iPadOS 13+ uses MacIntel platform with touch points
    return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
};

const isAndroidDevice = (): boolean => {
    if (typeof navigator === 'undefined') return false;
    return /Android/i.test(navigator.userAgent);
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    if (!localStorage.getItem('theme')) {
        let theme: Theme = 'default';
        if (isAndroidDevice()) {
            theme = 'android';
        } else if (isIOSDevice()) {
            theme = 'apple';
        }
        localStorage.setItem('theme', theme);
        if (canSetCookies()) setCookie('theme', theme);
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
        if (canSetCookies()) setCookie('theme', newTheme);

        applyTheme(newTheme);
        notify();

        // Reload so the server-rendered html data-theme attribute is correct,
        // preventing any flash on the next hard navigation.
        window.location.reload();
    }, []);

    return { theme, updateTheme } as const;
}
