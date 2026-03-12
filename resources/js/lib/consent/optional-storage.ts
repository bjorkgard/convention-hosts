import type { ConsentContract } from '@/types';

export const OPTIONAL_COOKIE_NAMES = ['appearance', 'theme', 'sidebar_state'] as const;
export const OPTIONAL_LOCAL_STORAGE_KEYS = [
    'appearance',
    'theme',
    'install-prompt-dismissed',
] as const;

export const SAFE_APPEARANCE = 'system';
export const SAFE_THEME = 'default';
export const SAFE_SIDEBAR_OPEN = true;
export const INSTALL_PROMPT_DISMISSED_FALLBACK = false;

export type OptionalCookieName = (typeof OPTIONAL_COOKIE_NAMES)[number];
export type OptionalLocalStorageKey = (typeof OPTIONAL_LOCAL_STORAGE_KEYS)[number];
export type ConsentPolicy = Pick<ConsentContract, 'allowOptionalStorage'> | null | undefined;

type ReadOptionalStorageOptions<TValue extends string> = {
    allowed: boolean;
    fallback: TValue;
    validate?: (value: string) => value is TValue;
};

export function isOptionalStorageAllowed(consent: ConsentPolicy): boolean {
    return consent?.allowOptionalStorage === true;
}

export function readOptionalLocalStorage<TValue extends string>(
    key: OptionalLocalStorageKey,
    options: ReadOptionalStorageOptions<TValue>,
): TValue {
    if (!options.allowed || typeof window === 'undefined') {
        return options.fallback;
    }

    try {
        const value = window.localStorage.getItem(key);
        if (value === null) {
            return options.fallback;
        }

        if (options.validate && !options.validate(value)) {
            return options.fallback;
        }

        return value as TValue;
    } catch {
        return options.fallback;
    }
}

export function writeOptionalLocalStorage(
    key: OptionalLocalStorageKey,
    value: string,
    consent: ConsentPolicy,
): boolean {
    if (!isOptionalStorageAllowed(consent) || typeof window === 'undefined') {
        return false;
    }

    try {
        window.localStorage.setItem(key, value);
        return true;
    } catch {
        return false;
    }
}

export function removeOptionalLocalStorage(key: OptionalLocalStorageKey): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.removeItem(key);
    } catch {
        // Ignore browser storage failures and keep cleanup best-effort.
    }
}

export function writeOptionalCookie(
    name: OptionalCookieName,
    value: string,
    consent: ConsentPolicy,
    days = 365,
): boolean {
    if (!isOptionalStorageAllowed(consent) || typeof document === 'undefined') {
        return false;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;

    return true;
}

export function removeOptionalCookie(name: OptionalCookieName): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${name}=;path=/;max-age=0;expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=Lax`;
}

export function cleanupOptionalStorage(): void {
    OPTIONAL_LOCAL_STORAGE_KEYS.forEach(removeOptionalLocalStorage);
    OPTIONAL_COOKIE_NAMES.forEach(removeOptionalCookie);
}
