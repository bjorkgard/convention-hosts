import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initializeTheme, useTheme } from '@/hooks/use-theme';

const mockProps = vi.fn(() => ({
    consent: {
        state: 'declined' as const,
        version: 1,
        allowOptionalStorage: false,
        decidedAt: null,
        updatedAt: null,
    },
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps() }),
}));

function setBootstrappedConsent(allowOptionalStorage: boolean): void {
    document.body.innerHTML = '<div id="app"></div>';
    const app = document.getElementById('app');

    if (app) {
        app.dataset.page = JSON.stringify({
            props: {
                consent: {
                    allowOptionalStorage,
                },
            },
        });
    }
}

function setConsentAllowed(allowOptionalStorage: boolean): void {
    mockProps.mockReturnValue({
        consent: {
            state: allowOptionalStorage ? 'accepted' : 'declined',
            version: 1,
            allowOptionalStorage,
            decidedAt: null,
            updatedAt: null,
        },
    });
}

describe('use-theme consent gating', () => {
    beforeEach(() => {
        localStorage.clear();
        document.cookie = '';
        document.body.innerHTML = '';
        document.documentElement.setAttribute('data-theme', 'default');
        Object.defineProperty(window.navigator, 'userAgent', {
            configurable: true,
            value: 'Mozilla/5.0 (Linux; Android 14)',
        });
        Object.defineProperty(window.navigator, 'platform', {
            configurable: true,
            value: 'Linux armv8l',
        });
        Object.defineProperty(window.navigator, 'maxTouchPoints', {
            configurable: true,
            value: 0,
        });
        vi.stubGlobal('location', {
            ...window.location,
            reload: vi.fn(),
        });
        setConsentAllowed(false);
        setBootstrappedConsent(false);
    });

    afterEach(() => {
        localStorage.clear();
        document.cookie = '';
        document.body.innerHTML = '';
        document.documentElement.setAttribute('data-theme', 'default');
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('falls back to default without consent', () => {
        localStorage.setItem('theme', 'ocean');

        initializeTheme();

        const { result } = renderHook(() => useTheme());

        expect(result.current.theme).toBe('default');
        expect(document.documentElement.getAttribute('data-theme')).toBe(
            'default',
        );
    });

    it('skips device-heuristic persisted initialization without consent', () => {
        initializeTheme();

        expect(localStorage.getItem('theme')).toBeNull();
        expect(document.cookie).not.toContain('theme=');
        expect(document.documentElement.getAttribute('data-theme')).toBe(
            'default',
        );
    });

    it('persists writes and reloads only when storage is allowed', () => {
        setConsentAllowed(true);
        setBootstrappedConsent(true);
        initializeTheme();

        const { result } = renderHook(() => useTheme());

        act(() => {
            result.current.updateTheme('forest');
        });

        expect(localStorage.getItem('theme')).toBe('forest');
        expect(document.cookie).toContain('theme=forest');
        expect(globalThis.location.reload).toHaveBeenCalledTimes(1);
    });

    it('does not persist writes or reload when storage is disallowed', () => {
        initializeTheme();

        const { result } = renderHook(() => useTheme());

        act(() => {
            result.current.updateTheme('ocean');
        });

        expect(document.documentElement.getAttribute('data-theme')).toBe(
            'ocean',
        );
        expect(localStorage.getItem('theme')).toBeNull();
        expect(document.cookie).not.toContain('theme=');
        expect(globalThis.location.reload).not.toHaveBeenCalled();
    });
});
