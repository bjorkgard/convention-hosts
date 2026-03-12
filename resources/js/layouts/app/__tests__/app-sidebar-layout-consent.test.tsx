import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AppSidebarLayout from '../app-sidebar-layout';

const { mockCleanupOptionalStorage, mockProps } = vi.hoisted(() => ({
    mockCleanupOptionalStorage: vi.fn(),
    mockProps: vi.fn(() => ({
        auth: {
            user: null,
        },
        consent: {
            state: 'undecided' as const,
            version: 1,
            allowOptionalStorage: false,
            decidedAt: null,
            updatedAt: null,
        },
        name: 'Convention Hosts',
        sidebarOpen: true,
        appVersion: null,
    })),
}));

vi.mock('@inertiajs/react', () => ({
    router: {
        post: vi.fn(),
    },
    usePage: () => ({ props: mockProps() }),
}));

vi.mock('@/components/app-content', () => ({
    AppContent: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="app-content">{children}</div>
    ),
}));

vi.mock('@/components/app-shell', () => ({
    AppShell: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="app-shell">{children}</div>
    ),
}));

vi.mock('@/components/app-sidebar', () => ({
    AppSidebar: () => <aside data-testid="app-sidebar" />,
}));

vi.mock('@/components/app-sidebar-header', () => ({
    AppSidebarHeader: () => <header data-testid="app-sidebar-header" />,
}));

vi.mock('@/components/install-prompt', () => ({
    default: () => <div data-testid="install-prompt" />,
}));

vi.mock('@/components/update-notification-modal', () => ({
    UpdateNotificationModal: () => <div data-testid="update-notification-modal" />,
}));

vi.mock('@/components/ui/sonner', () => ({
    Toaster: () => <div data-testid="toaster" />,
}));

vi.mock('@/lib/consent/optional-storage', () => ({
    cleanupOptionalStorage: mockCleanupOptionalStorage,
}));

function setConsentState(state: 'accepted' | 'declined' | 'undecided'): void {
    mockProps.mockReturnValue({
        auth: {
            user: null,
        },
        consent: {
            state,
            version: 1,
            allowOptionalStorage: state === 'accepted',
            decidedAt: null,
            updatedAt: null,
        },
        name: 'Convention Hosts',
        sidebarOpen: true,
        appVersion: null,
    });
}

describe('AppSidebarLayout consent prompt visibility', () => {
    beforeEach(() => {
        mockCleanupOptionalStorage.mockClear();
    });

    it('mounts the authenticated consent prompt when consent is undecided', () => {
        setConsentState('undecided');

        render(
            <AppSidebarLayout>
                <main>Page content</main>
            </AppSidebarLayout>,
        );

        expect(screen.getByRole('region', { name: /cookie consent/i })).toBeInTheDocument();
        expect(screen.getByText('Page content')).toBeInTheDocument();
    });

    it('does not mount the authenticated consent prompt after a decision is recorded', () => {
        setConsentState('accepted');
        const { rerender } = render(
            <AppSidebarLayout>
                <main>Page content</main>
            </AppSidebarLayout>,
        );

        expect(screen.queryByRole('region', { name: /cookie consent/i })).not.toBeInTheDocument();

        setConsentState('declined');
        rerender(
            <AppSidebarLayout>
                <main>Page content</main>
            </AppSidebarLayout>,
        );

        expect(screen.queryByRole('region', { name: /cookie consent/i })).not.toBeInTheDocument();
    });
});
