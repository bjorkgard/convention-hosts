import { renderHook } from '@testing-library/react';
import * as fc from 'fast-check';
import { describe, expect, it, vi } from 'vitest';

import type { UrlSession } from '@/types';
import type { Role } from '@/types/user';

// Mock @inertiajs/react usePage
const mockProps = vi.fn<() => { userRoles?: Role[]; urlSession?: UrlSession | null }>(() => ({}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps() }),
}));

import { useConventionRole } from '../use-convention-role';

function setPageProps(props: { userRoles?: Role[]; urlSession?: UrlSession | null }) {
    mockProps.mockReturnValue(props);
}

describe('useConventionRole', () => {
    describe('Owner role', () => {
        it('detects Owner role correctly', () => {
            setPageProps({ userRoles: ['Owner'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(true);
            expect(result.current.isAdministrator).toBe(false);
            expect(result.current.isManager).toBe(true);
            expect(result.current.isUrlSession).toBe(false);
        });
    });

    describe('Administrator role', () => {
        it('detects Administrator role correctly', () => {
            setPageProps({ userRoles: ['Administrator'] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isAdministrator).toBe(true);
            expect(result.current.isManager).toBe(true);
            expect(result.current.isUrlSession).toBe(false);
        });
    });

    describe('URL sessions', () => {
        it('detects floor URL session', () => {
            setPageProps({ urlSession: { convention_id: 'abc', type: 'floor' } });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isUrlSession).toBe(true);
            expect(result.current.isFloorUrlSession).toBe(true);
            expect(result.current.isSectionUrlSession).toBe(false);
            expect(result.current.isManager).toBe(false);
        });

        it('detects section URL session', () => {
            setPageProps({ urlSession: { convention_id: 'abc', type: 'section' } });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isUrlSession).toBe(true);
            expect(result.current.isFloorUrlSession).toBe(false);
            expect(result.current.isSectionUrlSession).toBe(true);
            expect(result.current.isManager).toBe(false);
        });
    });

    describe('no roles', () => {
        it('returns all booleans as false when no roles and no session', () => {
            setPageProps({ userRoles: [] });
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isAdministrator).toBe(false);
            expect(result.current.isManager).toBe(false);
            expect(result.current.isUrlSession).toBe(false);
            expect(result.current.isFloorUrlSession).toBe(false);
            expect(result.current.isSectionUrlSession).toBe(false);
        });
    });

    describe('empty/default props', () => {
        it('handles missing props gracefully', () => {
            setPageProps({});
            const { result } = renderHook(() => useConventionRole());

            expect(result.current.isOwner).toBe(false);
            expect(result.current.isAdministrator).toBe(false);
            expect(result.current.isManager).toBe(false);
            expect(result.current.isUrlSession).toBe(false);
        });
    });

    // --- Property-Based Tests ---

    describe('Property 1: Role system invariant', () => {
        it('only recognizes Owner and Administrator — no other role string sets any flag', () => {
            const arbitraryRoleString = fc.string({ minLength: 1, maxLength: 20 });

            fc.assert(
                fc.property(fc.array(arbitraryRoleString, { minLength: 0, maxLength: 5 }), (roles) => {
                    setPageProps({ userRoles: roles as Role[] });
                    const { result } = renderHook(() => useConventionRole());

                    const hasOwner = roles.includes('Owner');
                    const hasAdmin = roles.includes('Administrator');

                    expect(result.current.isOwner).toBe(hasOwner);
                    expect(result.current.isAdministrator).toBe(hasAdmin);
                    expect(result.current.isManager).toBe(hasOwner || hasAdmin);

                    // Without a URL session, these should always be false
                    expect(result.current.isUrlSession).toBe(false);
                    expect(result.current.isFloorUrlSession).toBe(false);
                    expect(result.current.isSectionUrlSession).toBe(false);
                }),
                { numRuns: 100 },
            );
        });
    });
});
