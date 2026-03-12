import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { initializeTheme, useAppearance } from '@/hooks/use-appearance';

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

describe('use-appearance consent gating', () => {
    beforeEach(() => {
        localStorage.clear();
        document.cookie = '';
        document.documentElement.className = '';
        document.documentElement.style.colorScheme = '';
        Object.defineProperty(window, 'matchMedia', {
            writable: true,
            value: vi.fn().mockImplementation((query: string) => ({
                matches: false,
                media: query,
                onchange: null,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                addListener: vi.fn(),
                removeListener: vi.fn(),
                dispatchEvent: vi.fn(),
            })),
        });
        setConsentAllowed(false);
        setBootstrappedConsent(false);
    });

    afterEach(() => {
        localStorage.clear();
        document.cookie = '';
        document.body.innerHTML = '';
        document.documentElement.className = '';
        document.documentElement.style.colorScheme = '';
    });

    it('falls back to system when storage is disallowed', () => {
        localStorage.setItem('appearance', 'dark');

        initializeTheme();

        const { result } = renderHook(() => useAppearance());

        expect(result.current.appearance).toBe('system');
        expect(result.current.resolvedAppearance).toBe('light');
    });

    it('does not seed localStorage or cookies on init without consent', () => {
        initializeTheme();

        expect(localStorage.getItem('appearance')).toBeNull();
        expect(document.cookie).not.toContain('appearance=');
    });

    it('applies in-session updates to the DOM without persistence when consent is disallowed', () => {
        initializeTheme();

        const { result } = renderHook(() => useAppearance());

        act(() => {
            result.current.updateAppearance('dark');
        });

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.documentElement.style.colorScheme).toBe('dark');
        expect(localStorage.getItem('appearance')).toBeNull();
        expect(document.cookie).not.toContain('appearance=');
    });
});
