import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    COOKIE_CONSENT_VERSION,
    canSetCookies,
    getCookieConsent,
    acceptCookies,
    declineCookies,
} from '../use-cookie-consent';

describe('use-cookie-consent legacy compatibility surface', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('getCookieConsent', () => {
        it('returns null when no consent stored', () => {
            expect(getCookieConsent()).toBeNull();
        });

        it('returns stored consent when present and version matches', () => {
            localStorage.setItem(
                'cookie_consent',
                JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
            );
            expect(getCookieConsent()).toEqual({ accepted: true, version: COOKIE_CONSENT_VERSION });
        });

        it('returns null when stored version is outdated', () => {
            localStorage.setItem(
                'cookie_consent',
                JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION - 1 }),
            );
            expect(getCookieConsent()).toBeNull();
        });

        it('returns null when stored value is malformed', () => {
            localStorage.setItem('cookie_consent', 'not-json');
            expect(getCookieConsent()).toBeNull();
        });
    });

    describe('canSetCookies', () => {
        it('returns false when no consent stored', () => {
            expect(canSetCookies()).toBe(false);
        });

        it('returns true when accepted', () => {
            acceptCookies();
            expect(canSetCookies()).toBe(true);
        });

        it('returns false when declined', () => {
            declineCookies();
            expect(canSetCookies()).toBe(false);
        });
    });

    describe('acceptCookies', () => {
        it('stores the legacy accepted compatibility record with current version', () => {
            acceptCookies();
            const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
            expect(stored).toEqual({ accepted: true, version: COOKIE_CONSENT_VERSION });
        });
    });

    describe('declineCookies', () => {
        it('stores the legacy declined compatibility record with current version', () => {
            declineCookies();
            const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
            expect(stored).toEqual({ accepted: false, version: COOKIE_CONSENT_VERSION });
        });
    });
});
