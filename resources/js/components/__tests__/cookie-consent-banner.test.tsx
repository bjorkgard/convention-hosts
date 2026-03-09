import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { COOKIE_CONSENT_VERSION } from '@/hooks/use-cookie-consent';

// We test behaviour, not internal consent detail — mock the hook
vi.mock('@/hooks/use-cookie-consent', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...(actual as object) };
});

import CookieConsentBanner from '../cookie-consent-banner';

describe('CookieConsentBanner', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    it('renders when no consent stored', () => {
        render(<CookieConsentBanner />);
        expect(screen.getByRole('region', { name: /cookie/i })).toBeInTheDocument();
    });

    it('does not render when consent already given', () => {
        localStorage.setItem(
            'cookie_consent',
            JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
        );
        render(<CookieConsentBanner />);
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('hides after clicking Accept all', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /accept all/i }));
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('hides after clicking Decline', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /decline/i }));
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('stores accepted consent when accepting', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /accept all/i }));
        const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
        expect(stored.accepted).toBe(true);
    });

    it('stores declined consent when declining', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /decline/i }));
        const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
        expect(stored.accepted).toBe(false);
    });
});
