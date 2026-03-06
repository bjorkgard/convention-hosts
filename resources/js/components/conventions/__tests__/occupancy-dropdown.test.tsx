import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// Mock the Select UI components to render simple HTML select/option elements
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
    SelectTrigger: ({ children, ...props }: { children: React.ReactNode; id?: string }) => (
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
        <select
            data-testid="select-content"
            value={__value}
            onChange={(e) => __onValueChange?.(e.target.value)}
        >
            {children}
        </select>
    ),
    SelectItem: ({ value, children }: { value: string; children: React.ReactNode }) => (
        <option value={value}>{children}</option>
    ),
}));

// Mock Label to render a simple label element
vi.mock('@/components/ui/label', () => ({
    Label: ({ children, ...props }: { children: React.ReactNode; htmlFor?: string }) => (
        <label {...props}>{children}</label>
    ),
}));

// Mock getOccupancyColorClass to return a predictable string
vi.mock('@/hooks/use-occupancy-color', () => ({
    getOccupancyColorClass: (occupancy: number) => `color-${occupancy}`,
}));

// Mock cn to just join classnames
vi.mock('@/lib/utils', () => ({
    cn: (...inputs: string[]) => inputs.filter(Boolean).join(' '),
}));

import React from 'react';

import OccupancyDropdown from '../occupancy-dropdown';

describe('OccupancyDropdown', () => {
    it('renders the "Occupancy" label', () => {
        render(<OccupancyDropdown currentOccupancy={0} onUpdate={vi.fn()} />);

        expect(screen.getByText('Occupancy')).toBeInTheDocument();
    });

    it('renders all 6 occupancy options (0%, 10%, 25%, 50%, 75%, 100%)', () => {
        render(<OccupancyDropdown currentOccupancy={0} onUpdate={vi.fn()} />);

        const options = screen.getAllByRole('option');
        expect(options).toHaveLength(6);

        expect(options[0]).toHaveValue('0');
        expect(options[1]).toHaveValue('10');
        expect(options[2]).toHaveValue('25');
        expect(options[3]).toHaveValue('50');
        expect(options[4]).toHaveValue('75');
        expect(options[5]).toHaveValue('100');
    });

    it('displays the current occupancy value as selected', () => {
        render(<OccupancyDropdown currentOccupancy={50} onUpdate={vi.fn()} />);

        const select = screen.getByTestId('select-content') as HTMLSelectElement;
        expect(select.value).toBe('50');
    });

    it('calls onUpdate when a different value is selected (auto-save)', () => {
        const onUpdate = vi.fn();
        render(<OccupancyDropdown currentOccupancy={25} onUpdate={onUpdate} />);

        const select = screen.getByTestId('select-content');
        fireEvent.change(select, { target: { value: '75' } });

        expect(onUpdate).toHaveBeenCalledOnce();
        expect(onUpdate).toHaveBeenCalledWith(75);
    });

    it('does NOT call onUpdate when the same value is selected', () => {
        const onUpdate = vi.fn();
        render(<OccupancyDropdown currentOccupancy={50} onUpdate={onUpdate} />);

        const select = screen.getByTestId('select-content');
        fireEvent.change(select, { target: { value: '50' } });

        expect(onUpdate).not.toHaveBeenCalled();
    });
});
