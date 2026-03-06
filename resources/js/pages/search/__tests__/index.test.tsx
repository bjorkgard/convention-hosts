import { render, screen } from '@testing-library/react';
import React from 'react';
import { describe, expect, it, vi } from 'vitest';

// Mock @inertiajs/react
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ href, children, ...props }: { href: string; children?: React.ReactNode; [key: string]: unknown }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    router: {
        get: vi.fn(),
    },
}));

// Mock Wayfinder actions
vi.mock('@/actions/App/Http/Controllers/ConventionController', () => ({
    index: { url: () => '/conventions' },
    show: { url: (id: number) => `/conventions/${id}` },
}));

vi.mock('@/actions/App/Http/Controllers/SearchController', () => ({
    index: { url: (conventionId: number) => `/conventions/${conventionId}/search` },
}));

vi.mock('@/actions/App/Http/Controllers/SectionController', () => ({
    show: { url: (id: number) => `/sections/${id}` },
}));

// Mock OccupancyIndicator
vi.mock('@/components/conventions/occupancy-indicator', () => ({
    default: ({ occupancy }: { occupancy: number }) => (
        <span data-testid={`occupancy-indicator-${occupancy}`}>Occupancy: {occupancy}%</span>
    ),
}));

// Mock Checkbox
vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: ({ id, checked, onCheckedChange }: { id?: string; checked?: boolean; onCheckedChange?: (checked: boolean) => void }) => (
        <input type="checkbox" id={id} checked={checked ?? false} onChange={(e) => onCheckedChange?.(e.target.checked)} />
    ),
}));

// Mock Label
vi.mock('@/components/ui/label', () => ({
    Label: ({ children, ...props }: { children: React.ReactNode; htmlFor?: string; className?: string }) => (
        <label {...props}>{children}</label>
    ),
}));

// Mock Select components (same pattern as occupancy-dropdown tests)
vi.mock('@/components/ui/select', () => ({
    Select: ({
        value,
        onValueChange,
        children,
    }: {
        value?: string;
        onValueChange?: (value: string) => void;
        children: React.ReactNode;
    }) => (
        <div data-testid="select-root" data-value={value}>
            {typeof children === 'function'
                ? children
                : React.Children.map(children, (child) => {
                      if (React.isValidElement(child)) {
                          return React.cloneElement(child as React.ReactElement<Record<string, unknown>>, {
                              __value: value,
                              __onValueChange: onValueChange,
                          });
                      }
                      return child;
                  })}
        </div>
    ),
    SelectTrigger: ({ children, ...props }: { children: React.ReactNode; id?: string; className?: string }) => (
        <button data-testid="select-trigger" {...props}>
            {children}
        </button>
    ),
    SelectValue: ({ placeholder }: { placeholder?: string }) => <span data-testid="select-value">{placeholder}</span>,
    SelectContent: ({
        children,
        __value,
        __onValueChange,
    }: {
        children: React.ReactNode;
        __value?: string;
        __onValueChange?: (value: string) => void;
    }) => (
        <select data-testid="select-content" value={__value} onChange={(e) => __onValueChange?.(e.target.value)}>
            {children}
        </select>
    ),
    SelectItem: ({ value, children }: { value: string; children: React.ReactNode }) => <option value={value}>{children}</option>,
}));

// Mock AppLayout
vi.mock('@/layouts/app-layout', () => ({
    default: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

// Mock lucide-react icons
vi.mock('lucide-react', () => ({
    ArrowLeft: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="arrow-left-icon" {...props} />,
    SearchX: (props: React.SVGProps<SVGSVGElement>) => <svg data-testid="search-x-icon" {...props} />,
}));

import type { Convention, Floor, Section } from '@/types/convention';

import SearchIndex from '../index';

// --- Helper factories ---

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

function makeSection(overrides: Partial<Section & { floor: Floor }> = {}): Section & { floor: Floor } {
    return {
        id: 1,
        floor_id: 1,
        name: 'Section A',
        number_of_seats: 100,
        occupancy: 25,
        available_seats: 75,
        elder_friendly: false,
        handicap_friendly: false,
        information: null,
        last_occupancy_updated_by: null,
        last_occupancy_updated_at: null,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
        floor: {
            id: 1,
            convention_id: 1,
            name: 'Ground Floor',
            created_at: '2025-01-01T00:00:00Z',
            updated_at: '2025-01-01T00:00:00Z',
        },
        ...overrides,
    };
}

interface PaginatedSections {
    data: (Section & { floor: Floor })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

function makePaginatedSections(sections: (Section & { floor: Floor })[] = [], total?: number): PaginatedSections {
    return {
        data: sections,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: total ?? sections.length,
        next_page_url: null,
        prev_page_url: null,
        links: [],
    };
}

const defaultFloors: Pick<Floor, 'id' | 'name'>[] = [
    { id: 1, name: 'Ground Floor' },
    { id: 2, name: 'First Floor' },
];

// --- Tests ---

describe('SearchIndex', () => {
    describe('filter inputs', () => {
        it('renders floor filter dropdown with "All floors" and floor options', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections()}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('Floor')).toBeInTheDocument();

            const options = screen.getAllByRole('option');
            expect(options).toHaveLength(3); // All floors + 2 floors
            expect(options[0]).toHaveTextContent('All floors');
            expect(options[1]).toHaveTextContent('Ground Floor');
            expect(options[2]).toHaveTextContent('First Floor');
        });

        it('renders elder-friendly checkbox', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections()}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByLabelText('Elder-friendly')).toBeInTheDocument();
            expect(screen.getByLabelText('Elder-friendly')).not.toBeChecked();
        });

        it('renders handicap-friendly checkbox', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections()}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByLabelText('Handicap-friendly')).toBeInTheDocument();
            expect(screen.getByLabelText('Handicap-friendly')).not.toBeChecked();
        });

        it('checks elder-friendly when filter is active', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections()}
                    floors={defaultFloors}
                    filters={{ elder_friendly: '1' }}
                />,
            );

            expect(screen.getByLabelText('Elder-friendly')).toBeChecked();
        });

        it('checks handicap-friendly when filter is active', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections()}
                    floors={defaultFloors}
                    filters={{ handicap_friendly: '1' }}
                />,
            );

            expect(screen.getByLabelText('Handicap-friendly')).toBeChecked();
        });
    });

    describe('result display', () => {
        it('renders section names and floor names in results', () => {
            const sections = [
                makeSection({ id: 1, name: 'Alpha', occupancy: 10, floor: { id: 1, convention_id: 1, name: 'Level 1', created_at: '', updated_at: '' } }),
                makeSection({ id: 2, name: 'Beta', occupancy: 30, floor: { id: 2, convention_id: 1, name: 'Level 2', created_at: '', updated_at: '' } }),
            ];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('Alpha')).toBeInTheDocument();
            expect(screen.getByText('Level 1')).toBeInTheDocument();
            expect(screen.getByText('Beta')).toBeInTheDocument();
            expect(screen.getByText('Level 2')).toBeInTheDocument();
        });

        it('renders occupancy percentage for each result', () => {
            const sections = [
                makeSection({ id: 1, name: 'A', occupancy: 10 }),
                makeSection({ id: 2, name: 'B', occupancy: 50 }),
            ];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('10%')).toBeInTheDocument();
            expect(screen.getByText('50%')).toBeInTheDocument();
        });

        it('shows total count text with plural "sections"', () => {
            const sections = [
                makeSection({ id: 1, occupancy: 10 }),
                makeSection({ id: 2, occupancy: 20 }),
                makeSection({ id: 3, occupancy: 30 }),
            ];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections, 3)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('3 sections available')).toBeInTheDocument();
        });

        it('shows singular "section" when total is 1', () => {
            const sections = [makeSection({ id: 1, occupancy: 10 })];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections, 1)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('1 section available')).toBeInTheDocument();
        });
    });

    describe('empty state', () => {
        it('shows "No available sections found" message when sections.data is empty', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections([])}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByText('No available sections found matching your filters.')).toBeInTheDocument();
        });

        it('does not show total count text when empty', () => {
            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections([])}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.queryByText(/sections? available/)).not.toBeInTheDocument();
        });
    });

    describe('result links', () => {
        it('each result links to the correct section detail URL', () => {
            const sections = [
                makeSection({ id: 42, name: 'Section 42', occupancy: 10 }),
                makeSection({ id: 99, name: 'Section 99', occupancy: 20 }),
            ];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            const link42 = screen.getByText('Section 42').closest('a');
            expect(link42).toHaveAttribute('href', '/sections/42');

            const link99 = screen.getByText('Section 99').closest('a');
            expect(link99).toHaveAttribute('href', '/sections/99');
        });
    });

    describe('occupancy display', () => {
        it('renders OccupancyIndicator with correct occupancy value for each result', () => {
            const sections = [
                makeSection({ id: 1, occupancy: 15 }),
                makeSection({ id: 2, occupancy: 60 }),
                makeSection({ id: 3, occupancy: 85 }),
            ];

            render(
                <SearchIndex
                    convention={makeConvention()}
                    sections={makePaginatedSections(sections)}
                    floors={defaultFloors}
                    filters={{}}
                />,
            );

            expect(screen.getByTestId('occupancy-indicator-15')).toBeInTheDocument();
            expect(screen.getByTestId('occupancy-indicator-60')).toBeInTheDocument();
            expect(screen.getByTestId('occupancy-indicator-85')).toBeInTheDocument();
        });
    });
});
