import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { Sidebar, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

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

vi.mock('@/hooks/use-mobile', () => ({
    useIsMobile: () => false,
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

function SidebarHarness() {
    return (
        <SidebarProvider defaultOpen>
            <SidebarTrigger />
            <Sidebar />
        </SidebarProvider>
    );
}

describe('sidebar consent gating', () => {
    beforeEach(() => {
        document.cookie = '';
        setConsentAllowed(false);
    });

    afterEach(() => {
        document.cookie = '';
    });

    it('does not write sidebar_state cookies when storage is disallowed', () => {
        render(<SidebarHarness />);

        fireEvent.click(screen.getByRole('button', { name: /toggle sidebar/i }));

        expect(document.cookie).not.toContain('sidebar_state=');
    });

    it('cleans up an existing sidebar_state cookie after consent becomes disallowed', () => {
        document.cookie = 'sidebar_state=true; path=/';
        setConsentAllowed(true);

        const { rerender } = render(<SidebarHarness />);

        expect(document.cookie).toContain('sidebar_state=true');

        setConsentAllowed(false);
        rerender(<SidebarHarness />);

        expect(document.cookie).not.toContain('sidebar_state=true');
    });

    it('writes sidebar_state cookies when storage is allowed', () => {
        setConsentAllowed(true);

        render(<SidebarHarness />);

        fireEvent.click(screen.getByRole('button', { name: /toggle sidebar/i }));

        expect(document.cookie).toContain('sidebar_state=false');
    });

    it('keeps the shell usable with the default open state', () => {
        render(<SidebarHarness />);

        expect(document.querySelector('[data-slot="sidebar"][data-state="expanded"]')).toBeInTheDocument();
    });
});
