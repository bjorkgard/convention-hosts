import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// Mock @inertiajs/react router
vi.mock('@inertiajs/react', () => ({
    router: {
        post: vi.fn(),
        delete: vi.fn(),
    },
}));

// Mock Wayfinder actions
vi.mock('@/actions/App/Http/Controllers/UserController', () => ({
    destroy: { url: ({ convention, user }: { convention: number; user: number }) => `/conventions/${convention}/users/${user}` },
    resendInvitation: { url: ({ convention, user }: { convention: number; user: number }) => `/conventions/${convention}/users/${user}/resend` },
}));

// Mock ConfirmationDialog
vi.mock('@/components/confirmation-dialog', () => ({
    default: ({ open, title, description }: { open: boolean; title: string; description: string }) =>
        open ? <div data-testid="confirmation-dialog">{title}: {description}</div> : null,
}));

// Mock RoleBadge
vi.mock('@/components/conventions/role-badge', () => ({
    default: ({ role }: { role: string }) => <span data-testid={`role-badge-${role}`}>{role}</span>,
}));

// Mock Button
vi.mock('@/components/ui/button', () => ({
    Button: ({ children, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: string; size?: string }) => (
        <button {...props}>{children}</button>
    ),
}));

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
    AlertTriangle: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="alert-triangle-icon" {...props} />,
    CheckCircle2: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="check-circle-icon" {...props} />,
    Mail: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="mail-icon" {...props} />,
    Pencil: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="pencil-icon" {...props} />,
    Trash2: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="trash-icon" {...props} />,
}));

import type { Convention } from '@/types/convention';
import type { ConventionUser } from '@/types/user';

import UserRow from '../user-row';

function makeUser(overrides: Partial<ConventionUser> = {}): ConventionUser {
    return {
        id: 1,
        name: 'John Doe',
        first_name: 'John',
        last_name: 'Doe',
        email: 'john@example.com',
        mobile: '+1234567890',
        email_confirmed: true,
        roles: ['ConventionUser'],
        email_verified_at: null,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
        ...overrides,
    };
}

function makeConvention(overrides: Partial<Convention> = {}): Convention {
    return {
        id: 1,
        name: 'Test Convention',
        city: 'Paris',
        country: 'France',
        address: null,
        start_date: '2025-06-15',
        end_date: '2025-06-20',
        other_info: null,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
        ...overrides,
    };
}

describe('UserRow', () => {
    it('renders user name and email', () => {
        const user = makeUser({ first_name: 'Jane', last_name: 'Smith', email: 'jane@example.com' });
        render(<UserRow user={user} convention={makeConvention()} />);

        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.getByText('jane@example.com')).toBeInTheDocument();
    });

    it('shows green checkmark icon when email_confirmed is true', () => {
        const user = makeUser({ email_confirmed: true });
        render(<UserRow user={user} convention={makeConvention()} />);

        expect(screen.getByLabelText('Email confirmed')).toBeInTheDocument();
        expect(screen.queryByLabelText('Email not confirmed')).not.toBeInTheDocument();
    });

    it('shows warning icon when email_confirmed is false', () => {
        const user = makeUser({ email_confirmed: false });
        render(<UserRow user={user} convention={makeConvention()} />);

        expect(screen.getByLabelText('Email not confirmed')).toBeInTheDocument();
        expect(screen.queryByLabelText('Email confirmed')).not.toBeInTheDocument();
    });

    it('renders role badges for each role', () => {
        const user = makeUser({ roles: ['Owner', 'ConventionUser', 'FloorUser'] });
        render(<UserRow user={user} convention={makeConvention()} />);

        expect(screen.getByTestId('role-badge-Owner')).toBeInTheDocument();
        expect(screen.getByTestId('role-badge-ConventionUser')).toBeInTheDocument();
        expect(screen.getByTestId('role-badge-FloorUser')).toBeInTheDocument();
    });

    it('does not render role badges when roles array is empty', () => {
        const user = makeUser({ roles: [] });
        render(<UserRow user={user} convention={makeConvention()} />);

        expect(screen.queryByTestId(/^role-badge-/)).not.toBeInTheDocument();
    });

    it('shows resend invitation button when canManage=true and email_confirmed=false', () => {
        const user = makeUser({ email_confirmed: false });
        render(<UserRow user={user} convention={makeConvention()} canManage={true} />);

        expect(screen.getByLabelText('Resend invitation')).toBeInTheDocument();
    });

    it('disables resend invitation button when email_confirmed=true even with canManage=true', () => {
        const user = makeUser({ email_confirmed: true });
        render(<UserRow user={user} convention={makeConvention()} canManage={true} />);

        const resendButton = screen.getByLabelText('Resend invitation');
        expect(resendButton).toBeDisabled();
    });

    it('hides all action buttons when canManage=false', () => {
        const user = makeUser({ email_confirmed: false });
        render(<UserRow user={user} convention={makeConvention()} canManage={false} />);

        expect(screen.queryByLabelText('Resend invitation')).not.toBeInTheDocument();
        expect(screen.queryByLabelText(/^Edit /)).not.toBeInTheDocument();
        expect(screen.queryByLabelText(/^Delete /)).not.toBeInTheDocument();
    });

    it('shows edit and delete buttons when canManage=true', () => {
        const user = makeUser({ first_name: 'John', last_name: 'Doe' });
        const onEdit = vi.fn();
        render(<UserRow user={user} convention={makeConvention()} canManage={true} onEdit={onEdit} />);

        expect(screen.getByLabelText('Edit John Doe')).toBeInTheDocument();
        expect(screen.getByLabelText('Delete John Doe')).toBeInTheDocument();
    });
});
