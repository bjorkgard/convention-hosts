import { useCallback, useEffect, useSyncExternalStore } from 'react';
import { useConsent } from '@/hooks/use-consent';
import {
    SAFE_THEME,
    isOptionalStorageAllowed,
    readOptionalLocalStorage,
    removeOptionalCookie,
    removeOptionalLocalStorage,
    writeOptionalCookie,
    writeOptionalLocalStorage,
} from '@/lib/consent/optional-storage';

export const THEMES = [
    'default',
    'ocean',
    'forest',
    'sunset',
    'rose',
    'apple',
    'android',
] as const;
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

function isTheme(value: string): value is Theme {
    return THEMES.includes(value as Theme);
}

function getBootstrapConsentAllowance(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    const page = document.getElementById('app')?.dataset.page;

    if (!page) {
        return false;
    }

    try {
        const parsed = JSON.parse(page) as {
            props?: { consent?: { allowOptionalStorage?: boolean } };
        };

        return parsed.props?.consent?.allowOptionalStorage === true;
    } catch {
        return false;
    }
}

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

    const allowOptionalStorage = getBootstrapConsentAllowance();

    if (allowOptionalStorage && !window.localStorage.getItem('theme')) {
        let theme: Theme = 'default';
        if (isAndroidDevice()) {
            theme = 'android';
        } else if (isIOSDevice()) {
            theme = 'apple';
        }

        writeOptionalLocalStorage('theme', theme, { allowOptionalStorage });
        writeOptionalCookie('theme', theme, { allowOptionalStorage });
    }

    currentTheme = readOptionalLocalStorage('theme', {
        allowed: allowOptionalStorage,
        fallback: SAFE_THEME,
        validate: isTheme,
    });

    if (!allowOptionalStorage) {
        removeOptionalLocalStorage('theme');
        removeOptionalCookie('theme');
    }

    applyTheme(currentTheme);
}

export function useTheme(): UseThemeReturn {
    const consent = useConsent();
    const allowOptionalStorage = isOptionalStorageAllowed(consent);
    const theme: Theme = useSyncExternalStore(
        subscribe,
        () => currentTheme,
        () => 'default',
    );

    useEffect(() => {
        if (allowOptionalStorage) {
            return;
        }

        removeOptionalLocalStorage('theme');
        removeOptionalCookie('theme');

        if (currentTheme !== SAFE_THEME) {
            currentTheme = SAFE_THEME;
            applyTheme(currentTheme);
            notify();
        }
    }, [allowOptionalStorage]);

    const updateTheme = useCallback(
        (newTheme: Theme): void => {
            currentTheme = newTheme;

            const wroteTheme = writeOptionalLocalStorage(
                'theme',
                newTheme,
                consent,
            );
            const wroteCookie = writeOptionalCookie('theme', newTheme, consent);

            applyTheme(newTheme);
            notify();

            if (wroteTheme && wroteCookie) {
                window.location.reload();
            }
        },
        [consent],
    );

    return { theme, updateTheme } as const;
}
