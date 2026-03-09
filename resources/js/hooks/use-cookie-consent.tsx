import { useCallback, useSyncExternalStore } from 'react';

export const COOKIE_CONSENT_VERSION = 1;

const STORAGE_KEY = 'cookie_consent';

export type ConsentRecord = {
    accepted: boolean;
    version: number;
};

// --- Pure helpers (no React) ---

export function getCookieConsent(): ConsentRecord | null {
    if (typeof window === 'undefined') return null;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed: ConsentRecord = JSON.parse(raw);
        if (parsed.version !== COOKIE_CONSENT_VERSION) return null;
        return parsed;
    } catch {
        return null;
    }
}

export function canSetCookies(): boolean {
    return getCookieConsent()?.accepted === true;
}

export function acceptCookies(): void {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
    );
    notify();
}

export function declineCookies(): void {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ accepted: false, version: COOKIE_CONSENT_VERSION }),
    );
    notify();
}

// --- React hook ---

const listeners = new Set<() => void>();

function notify(): void {
    listeners.forEach((l) => l());
}

function subscribe(callback: () => void): () => void {
    listeners.add(callback);
    return () => listeners.delete(callback);
}

export type UseCookieConsentReturn = {
    readonly pending: boolean;
    readonly accepted: boolean | null;
    readonly accept: () => void;
    readonly decline: () => void;
};

// Returns a primitive for stable Object.is comparison in useSyncExternalStore
function getAcceptedSnapshot(): boolean | null {
    return getCookieConsent()?.accepted ?? null;
}

export function useCookieConsent(): UseCookieConsentReturn {
    // null = no decision yet, true = accepted, false = declined
    const accepted = useSyncExternalStore(
        subscribe,
        getAcceptedSnapshot,
        () => null,
    );

    const accept = useCallback(() => acceptCookies(), []);
    const decline = useCallback(() => declineCookies(), []);

    return {
        pending: accepted === null,
        accepted,
        accept,
        decline,
    } as const;
}
