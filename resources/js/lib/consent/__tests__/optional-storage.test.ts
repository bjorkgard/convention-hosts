import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    cleanupOptionalStorage,
    readOptionalLocalStorage,
    writeOptionalCookie,
    writeOptionalLocalStorage,
} from '@/lib/consent/optional-storage';

describe('optional-storage policy', () => {
    beforeEach(() => {
        localStorage.clear();
        document.cookie = '';
    });

    afterEach(() => {
        localStorage.clear();
        document.cookie = '';
    });

    it('returns fallbacks when optional storage is disallowed', () => {
        localStorage.setItem('appearance', 'dark');

        const appearance = readOptionalLocalStorage('appearance', {
            allowed: false,
            fallback: 'system',
            validate: (value): value is 'system' | 'light' | 'dark' =>
                value === 'system' || value === 'light' || value === 'dark',
        });

        expect(appearance).toBe('system');
    });

    it('makes writes no-ops when optional storage is disallowed', () => {
        const wroteLocal = writeOptionalLocalStorage('theme', 'ocean', {
            allowOptionalStorage: false,
        });
        const wroteCookie = writeOptionalCookie('theme', 'ocean', {
            allowOptionalStorage: false,
        });

        expect(wroteLocal).toBe(false);
        expect(wroteCookie).toBe(false);
        expect(localStorage.getItem('theme')).toBeNull();
        expect(document.cookie).not.toContain('theme=');
    });

    it('cleans up only the allowlisted optional keys', () => {
        localStorage.setItem('appearance', 'dark');
        localStorage.setItem('theme', 'ocean');
        localStorage.setItem('install-prompt-dismissed', 'true');
        localStorage.setItem('session-safe', 'keep');

        document.cookie = 'appearance=dark; path=/';
        document.cookie = 'theme=ocean; path=/';
        document.cookie = 'sidebar_state=false; path=/';
        document.cookie = 'essential_cookie=keep; path=/';

        cleanupOptionalStorage();

        expect(localStorage.getItem('appearance')).toBeNull();
        expect(localStorage.getItem('theme')).toBeNull();
        expect(localStorage.getItem('install-prompt-dismissed')).toBeNull();
        expect(localStorage.getItem('session-safe')).toBe('keep');
        expect(document.cookie).toContain('essential_cookie=keep');
    });
});
