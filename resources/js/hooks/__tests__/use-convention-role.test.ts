import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import type { Role } from '@/types/user';

// Mock @inertiajs/react usePage
const mockProps = vi.fn<() => { userRoles?: Role[]; userFloorIds?: number[]; userSectionIds?: number[] }>(() => ({}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps() }),
}));

import { useConventionRole } from '../use-convention-role';

function setPageProps(props: { userRoles?: Role[]; userFloorIds?: number[]; userSectionIds?: number[] }) {
    mockProps.mockReturnValue(props);
}

describe('useConventionRole', () => {
    describe('Owner role', () => {
        it('detects Owner role correctly', () => {
            setPageProps({ userRoles: ['Owner'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(true);
            expect(result.current.isConventionUser).toBe(false);
            expect(result.current.isFloorUser).toBe(false);
            expect(result.current.isSectionUser).toBe(false);
        });

        it('grants floor access for any floor ID', () => {
            setPageProps({ userRoles: ['Owner'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasFloorAccess(1)).toBe(true);
            expect(result.current.hasFloorAccess(999)).toBe(true);
        });

        it('grants section access for any section ID', () => {
            setPageProps({ userRoles: ['Owner'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasSectionAccess(1)).toBe(true);
            expect(result.current.hasSectionAccess(999)).toBe(true);
        });
    });

    describe('ConventionUser role', () => {
        it('detects ConventionUser role correctly', () => {
            setPageProps({ userRoles: ['ConventionUser'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(true);
            expect(result.current.isFloorUser).toBe(false);
            expect(result.current.isSectionUser).toBe(false);
        });

        it('grants floor and section access for any ID', () => {
            setPageProps({ userRoles: ['ConventionUser'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasFloorAccess(42)).toBe(true);
            expect(result.current.hasSectionAccess(42)).toBe(true);
        });
    });

    describe('FloorUser role', () => {
        it('detects FloorUser role correctly', () => {
            setPageProps({ userRoles: ['FloorUser'], userFloorIds: [1, 2] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(false);
            expect(result.current.isFloorUser).toBe(true);
            expect(result.current.isSectionUser).toBe(false);
        });

        it('grants access only for assigned floor IDs', () => {
            setPageProps({ userRoles: ['FloorUser'], userFloorIds: [1, 2] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasFloorAccess(1)).toBe(true);
            expect(result.current.hasFloorAccess(2)).toBe(true);
            expect(result.current.hasFloorAccess(3)).toBe(false);
        });

        it('denies section access for unassigned sections', () => {
            setPageProps({ userRoles: ['FloorUser'], userFloorIds: [1], userSectionIds: [] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasSectionAccess(10)).toBe(false);
        });
    });

    describe('SectionUser role', () => {
        it('detects SectionUser role correctly', () => {
            setPageProps({ userRoles: ['SectionUser'], userSectionIds: [10, 20] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(false);
            expect(result.current.isFloorUser).toBe(false);
            expect(result.current.isSectionUser).toBe(true);
        });

        it('grants access only for assigned section IDs', () => {
            setPageProps({ userRoles: ['SectionUser'], userSectionIds: [10, 20] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasSectionAccess(10)).toBe(true);
            expect(result.current.hasSectionAccess(20)).toBe(true);
            expect(result.current.hasSectionAccess(30)).toBe(false);
        });

        it('denies floor access for unassigned floors', () => {
            setPageProps({ userRoles: ['SectionUser'], userFloorIds: [], userSectionIds: [10] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasFloorAccess(1)).toBe(false);
        });
    });

    describe('multiple roles', () => {
        it('detects both FloorUser and SectionUser flags', () => {
            setPageProps({
                userRoles: ['FloorUser', 'SectionUser'],
                userFloorIds: [1],
                userSectionIds: [10],
            });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isFloorUser).toBe(true);
            expect(result.current.isSectionUser).toBe(true);
            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(false);
        });
    });

    describe('no roles', () => {
        it('returns all booleans as false', () => {
            setPageProps({ userRoles: [] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(false);
            expect(result.current.isFloorUser).toBe(false);
            expect(result.current.isSectionUser).toBe(false);
        });

        it('denies all floor and section access', () => {
            setPageProps({ userRoles: [] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.hasFloorAccess(1)).toBe(false);
            expect(result.current.hasSectionAccess(1)).toBe(false);
        });
    });

    describe('empty/default props', () => {
        it('handles missing userRoles, userFloorIds, userSectionIds gracefully', () => {
            setPageProps({});
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isConventionUser).toBe(false);
            expect(result.current.isFloorUser).toBe(false);
            expect(result.current.isSectionUser).toBe(false);
            expect(result.current.hasFloorAccess(1)).toBe(false);
            expect(result.current.hasSectionAccess(1)).toBe(false);
        });
    });
});
