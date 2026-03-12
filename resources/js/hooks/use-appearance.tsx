import { useCallback, useEffect, useMemo, useSyncExternalStore } from 'react';
import { useConsent } from '@/hooks/use-consent';
import {
    SAFE_APPEARANCE,
    isOptionalStorageAllowed,
    readOptionalLocalStorage,
    removeOptionalCookie,
    removeOptionalLocalStorage,
    writeOptionalCookie,
    writeOptionalLocalStorage,
} from '@/lib/consent/optional-storage';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const listeners = new Set<() => void>();
let currentAppearance: Appearance = 'system';

function isAppearance(value: string): value is Appearance {
    return value === 'system' || value === 'light' || value === 'dark';
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

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') return false;

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const isDarkMode = (appearance: Appearance): boolean => {
    return appearance === 'dark' || (appearance === 'system' && prefersDark());
};

const applyTheme = (appearance: Appearance): void => {
    if (typeof document === 'undefined') return;

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') return null;

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = (): void => applyTheme(currentAppearance);

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    const allowOptionalStorage = getBootstrapConsentAllowance();
    currentAppearance = readOptionalLocalStorage('appearance', {
        allowed: allowOptionalStorage,
        fallback: SAFE_APPEARANCE,
        validate: isAppearance,
    });

    if (!allowOptionalStorage) {
        removeOptionalLocalStorage('appearance');
        removeOptionalCookie('appearance');
    }

    applyTheme(currentAppearance);
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance(): UseAppearanceReturn {
    const consent = useConsent();
    const allowOptionalStorage = isOptionalStorageAllowed(consent);
    const appearance: Appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => 'system',
    );

    const resolvedAppearance: ResolvedAppearance = useMemo(
        () => (isDarkMode(appearance) ? 'dark' : 'light'),
        [appearance],
    );

    useEffect(() => {
        if (allowOptionalStorage) {
            return;
        }

        removeOptionalLocalStorage('appearance');
        removeOptionalCookie('appearance');

        if (currentAppearance !== SAFE_APPEARANCE) {
            currentAppearance = SAFE_APPEARANCE;
            applyTheme(currentAppearance);
            notify();
        }
    }, [allowOptionalStorage]);

    const updateAppearance = useCallback(
        (mode: Appearance): void => {
            currentAppearance = mode;
            writeOptionalLocalStorage('appearance', mode, consent);
            writeOptionalCookie('appearance', mode, consent);

            applyTheme(mode);
            notify();
        },
        [consent],
    );

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
