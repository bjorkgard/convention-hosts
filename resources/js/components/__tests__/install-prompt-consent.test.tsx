import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import InstallPrompt from '@/components/install-prompt';

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

describe('install prompt consent gating', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.useFakeTimers();
        Object.defineProperty(window.navigator, 'userAgent', {
            configurable: true,
            value: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        });
        Object.defineProperty(window.navigator, 'standalone', {
            configurable: true,
            value: false,
        });
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
    });

    afterEach(() => {
        localStorage.clear();
        vi.runOnlyPendingTimers();
        vi.useRealTimers();
    });

    it('does not persist dismissal when storage is disallowed', () => {
        render(<InstallPrompt />);

        actOpenTimer();
        closeDialog();

        expect(localStorage.getItem('install-prompt-dismissed')).toBeNull();
    });

    it('clears and ignores a stored dismissal after consent becomes disallowed', () => {
        localStorage.setItem('install-prompt-dismissed', 'true');

        render(<InstallPrompt />);

        actOpenTimer();

        expect(localStorage.getItem('install-prompt-dismissed')).toBeNull();
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('keeps current-session close behavior working', () => {
        render(<InstallPrompt />);

        actOpenTimer();
        closeDialog();

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});

function actOpenTimer(): void {
    act(() => {
        vi.advanceTimersByTime(500);
    });
}

function closeDialog(): void {
    const closeButton = document.querySelector<HTMLButtonElement>(
        '[data-slot="dialog-close"]',
    );

    expect(closeButton).not.toBeNull();

    act(() => {
        fireEvent.click(closeButton!);
    });
}
