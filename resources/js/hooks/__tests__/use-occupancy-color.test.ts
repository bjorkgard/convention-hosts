import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { getOccupancyColorClass, getOccupancyLevel, useOccupancyColor } from '../use-occupancy-color';

describe('getOccupancyLevel', () => {
    it('returns green for 0%', () => {
        expect(getOccupancyLevel(0)).toBe('green');
    });

    it('returns green for 25% (boundary)', () => {
        expect(getOccupancyLevel(25)).toBe('green');
    });

    it('returns dark-green for 26% (boundary)', () => {
        expect(getOccupancyLevel(26)).toBe('dark-green');
    });

    it('returns dark-green for 50% (boundary)', () => {
        expect(getOccupancyLevel(50)).toBe('dark-green');
    });

    it('returns yellow for 51% (boundary)', () => {
        expect(getOccupancyLevel(51)).toBe('yellow');
    });

    it('returns yellow for 75% (boundary)', () => {
        expect(getOccupancyLevel(75)).toBe('yellow');
    });

    it('returns orange for 76% (boundary)', () => {
        expect(getOccupancyLevel(76)).toBe('orange');
    });

    it('returns orange for 90% (boundary)', () => {
        expect(getOccupancyLevel(90)).toBe('orange');
    });

    it('returns red for 91% (boundary)', () => {
        expect(getOccupancyLevel(91)).toBe('red');
    });

    it('returns red for 100%', () => {
        expect(getOccupancyLevel(100)).toBe('red');
    });
});

describe('getOccupancyColorClass', () => {
    it('maps green level to correct Tailwind classes', () => {
        expect(getOccupancyColorClass(0)).toBe('text-green-500 bg-green-50');
    });

    it('maps dark-green level to correct Tailwind classes', () => {
        expect(getOccupancyColorClass(30)).toBe('text-emerald-700 bg-emerald-50');
    });

    it('maps yellow level to correct Tailwind classes', () => {
        expect(getOccupancyColorClass(60)).toBe('text-yellow-500 bg-yellow-50');
    });

    it('maps orange level to correct Tailwind classes', () => {
        expect(getOccupancyColorClass(80)).toBe('text-orange-500 bg-orange-50');
    });

    it('maps red level to correct Tailwind classes', () => {
        expect(getOccupancyColorClass(95)).toBe('text-red-500 bg-red-50');
    });
});

describe('useOccupancyColor', () => {
    it('returns correct class string for green range', () => {
        const { result } = renderHook(() => useOccupancyColor(10));
        expect(result.current).toBe('text-green-500 bg-green-50');
    });

    it('returns correct class string for red range', () => {
        const { result } = renderHook(() => useOccupancyColor(100));
        expect(result.current).toBe('text-red-500 bg-red-50');
    });
});
