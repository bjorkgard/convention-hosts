import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// Mock @inertiajs/react Link component
vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }: { href: string; children: React.ReactNode; [key: string]: unknown }) => (
        <a href={href} data-testid="inertia-link" {...props}>
            {children}
        </a>
    ),
}));

// Mock Wayfinder show.url
vi.mock('@/actions/App/Http/Controllers/ConventionController', () => ({
    show: {
        url: (id: number) => `/conventions/${id}`,
    },
}));

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
    Calendar: ({ className }: { className?: string }) => <svg data-testid="calendar-icon" className={className} />,
    MapPin: ({ className }: { className?: string }) => <svg data-testid="map-pin-icon" className={className} />,
}));

import type { Convention } from '@/types/convention';

import ConventionCard from '../convention-card';

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

describe('ConventionCard', () => {
    it('renders convention name, city, and country', () => {
        const convention = makeConvention({ name: 'Annual Gathering', city: 'Berlin', country: 'Germany' });
        render(<ConventionCard convention={convention} />);

        expect(screen.getByText('Annual Gathering')).toBeInTheDocument();
        expect(screen.getByText('Berlin, Germany')).toBeInTheDocument();
    });

    it('renders Link with correct href from show.url(convention.id)', () => {
        const convention = makeConvention({ id: 42 });
        render(<ConventionCard convention={convention} />);

        const link = screen.getByTestId('inertia-link');
        expect(link).toHaveAttribute('href', '/conventions/42');
    });

    it('formats same-day date range', () => {
        const convention = makeConvention({ start_date: '2025-06-15', end_date: '2025-06-15' });
        render(<ConventionCard convention={convention} />);

        expect(screen.getByText('Jun 15, 2025')).toBeInTheDocument();
    });

    it('formats same-month date range', () => {
        const convention = makeConvention({ start_date: '2025-06-10', end_date: '2025-06-15' });
        render(<ConventionCard convention={convention} />);

        expect(screen.getByText('Jun 10 - 15, 2025')).toBeInTheDocument();
    });

    it('formats cross-month date range within same year', () => {
        const convention = makeConvention({ start_date: '2025-06-10', end_date: '2025-07-15' });
        render(<ConventionCard convention={convention} />);

        expect(screen.getByText('Jun 10 - Jul 15, 2025')).toBeInTheDocument();
    });

    it('formats cross-year date range', () => {
        const convention = makeConvention({ start_date: '2025-12-28', end_date: '2026-01-03' });
        render(<ConventionCard convention={convention} />);

        expect(screen.getByText('Dec 28, 2025 - Jan 3, 2026')).toBeInTheDocument();
    });
});
