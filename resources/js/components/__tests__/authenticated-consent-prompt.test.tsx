import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { mockPost, mockProps } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockProps: vi.fn(() => ({
        consent: {
            state: 'undecided' as const,
            version: 1,
            allowOptionalStorage: false,
            decidedAt: null,
            updatedAt: null,
        },
    })),
}));

vi.mock('@inertiajs/react', () => ({
    router: {
        post: mockPost,
    },
    usePage: () => ({ props: mockProps() }),
}));

vi.mock('@/actions/App/Http/Controllers/ConsentController', () => ({
    default: {
        store: {
            url: () => '/consent',
        },
    },
}));

import AuthenticatedConsentPrompt from '../authenticated-consent-prompt';

function setConsentState(state: 'accepted' | 'declined' | 'undecided'): void {
    mockProps.mockReturnValue({
        consent: {
            state,
            version: 1,
            allowOptionalStorage: state === 'accepted',
            decidedAt: null,
            updatedAt: null,
        },
    });
}

describe('AuthenticatedConsentPrompt', () => {
    beforeEach(() => {
        mockPost.mockClear();
    });

    it('renders when consent is undecided', () => {
        setConsentState('undecided');

        render(<AuthenticatedConsentPrompt />);

        expect(screen.getByRole('region', { name: /cookie consent/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /accept all/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /decline/i })).toBeInTheDocument();
    });

    it('hides when consent is accepted or declined', () => {
        setConsentState('accepted');
        const { rerender } = render(<AuthenticatedConsentPrompt />);

        expect(screen.queryByRole('region', { name: /cookie consent/i })).not.toBeInTheDocument();

        setConsentState('declined');
        rerender(<AuthenticatedConsentPrompt />);

        expect(screen.queryByRole('region', { name: /cookie consent/i })).not.toBeInTheDocument();
    });

    it('posts the accepted state through the generated action', async () => {
        setConsentState('undecided');
        const user = userEvent.setup();

        render(<AuthenticatedConsentPrompt />);

        await user.click(screen.getByRole('button', { name: /accept all/i }));

        expect(mockPost).toHaveBeenCalledWith(
            '/consent',
            { state: 'accepted' },
            expect.objectContaining({ preserveScroll: true, onFinish: expect.any(Function) }),
        );
    });

    it('posts the declined state through the generated action', async () => {
        setConsentState('undecided');
        const user = userEvent.setup();

        render(<AuthenticatedConsentPrompt />);

        await user.click(screen.getByRole('button', { name: /decline/i }));

        expect(mockPost).toHaveBeenCalledWith(
            '/consent',
            { state: 'declined' },
            expect.objectContaining({ preserveScroll: true, onFinish: expect.any(Function) }),
        );
    });

    it('prevents duplicate submissions while a request is pending', async () => {
        setConsentState('undecided');
        const user = userEvent.setup();

        render(<AuthenticatedConsentPrompt />);

        const acceptButton = screen.getByRole('button', { name: /accept all/i });

        await user.click(acceptButton);
        await user.click(screen.getByRole('button', { name: /saving/i }));

        expect(mockPost).toHaveBeenCalledTimes(1);
        expect(screen.getByRole('region', { name: /cookie consent/i })).toHaveAttribute(
            'aria-busy',
            'true',
        );
    });
});
