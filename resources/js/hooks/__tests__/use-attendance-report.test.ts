import { renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import type { AttendancePeriod, AttendanceReport, Floor, Section } from '@/types/convention';
import type { Role } from '@/types/user';

// Mock @inertiajs/react usePage
const mockProps = vi.fn<() => { attendancePeriods?: AttendancePeriod[]; floors?: Floor[]; userRoles?: Role[] }>(
    () => ({}),
);

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps() }),
}));

import { useAttendanceReport } from '../use-attendance-report';

function setPageProps(props: { attendancePeriods?: AttendancePeriod[]; floors?: Floor[]; userRoles?: Role[] }) {
    mockProps.mockReturnValue(props);
}

function makePeriod(overrides: Partial<AttendancePeriod> = {}): AttendancePeriod {
    return {
        id: 1,
        convention_id: 1,
        date: '2025-06-15',
        period: 'morning',
        locked: false,
        created_at: '2025-06-15T00:00:00Z',
        updated_at: '2025-06-15T00:00:00Z',
        ...overrides,
    };
}

function makeReport(overrides: Partial<AttendanceReport> = {}): AttendanceReport {
    return {
        id: 1,
        attendance_period_id: 1,
        section_id: 1,
        attendance: 50,
        reported_by: 1,
        reported_at: '2025-06-15T10:00:00Z',
        created_at: '2025-06-15T10:00:00Z',
        updated_at: '2025-06-15T10:00:00Z',
        ...overrides,
    };
}

function makeFloor(overrides: Partial<Floor> & { sections?: Partial<Section>[] } = {}): Floor {
    return {
        id: 1,
        convention_id: 1,
        name: 'Floor 1',
        created_at: '2025-06-15T00:00:00Z',
        updated_at: '2025-06-15T00:00:00Z',
        ...overrides,
    } as Floor;
}

beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2025-06-15T10:00:00Z'));
});

afterEach(() => {
    vi.useRealTimers();
});

describe('useAttendanceReport', () => {
    describe('active period detection', () => {
        it('returns the first unlocked period as activePeriod', () => {
            const unlocked = makePeriod({ id: 2, locked: false });
            setPageProps({
                attendancePeriods: [makePeriod({ id: 1, locked: true }), unlocked],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.activePeriod).toEqual(unlocked);
        });

        it('returns null when all periods are locked', () => {
            setPageProps({
                attendancePeriods: [
                    makePeriod({ id: 1, locked: true }),
                    makePeriod({ id: 2, locked: true }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.activePeriod).toBeNull();
        });

        it('returns null when no periods exist', () => {
            setPageProps({ attendancePeriods: [], userRoles: ['Owner'] });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.activePeriod).toBeNull();
        });
    });

    describe('canStart calculation', () => {
        it('is true when user is Owner, no active period, and fewer than 2 periods today', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ id: 1, locked: true })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(true);
        });

        it('is true when user is ConventionUser, no active period, and fewer than 2 periods today', () => {
            setPageProps({
                attendancePeriods: [],
                userRoles: ['ConventionUser'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(true);
        });

        it('is false when user is FloorUser (not a manager)', () => {
            setPageProps({
                attendancePeriods: [],
                userRoles: ['FloorUser'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(false);
        });

        it('is false when user is SectionUser (not a manager)', () => {
            setPageProps({
                attendancePeriods: [],
                userRoles: ['SectionUser'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(false);
        });

        it('is false when there is already an active (unlocked) period', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: false })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(false);
        });

        it('is false when there are already 2 periods today (max reached)', () => {
            setPageProps({
                attendancePeriods: [
                    makePeriod({ id: 1, period: 'morning', locked: true }),
                    makePeriod({ id: 2, period: 'afternoon', locked: true }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(false);
        });

        it('allows start when periods exist but are from a different day', () => {
            setPageProps({
                attendancePeriods: [
                    makePeriod({ id: 1, date: '2025-06-14', locked: true }),
                    makePeriod({ id: 2, date: '2025-06-14', locked: true }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStart).toBe(true);
        });
    });

    describe('canStop calculation', () => {
        it('is true when manager and active period exists', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: false })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStop).toBe(true);
        });

        it('is true for ConventionUser when active period exists', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: false })],
                userRoles: ['ConventionUser'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStop).toBe(true);
        });

        it('is false when no active period', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: true })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStop).toBe(false);
        });

        it('is false for non-manager even with active period', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: false })],
                userRoles: ['FloorUser'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.canStop).toBe(false);
        });
    });

    describe('reportedCount', () => {
        it('returns count of reports in the active period', () => {
            setPageProps({
                attendancePeriods: [
                    makePeriod({
                        locked: false,
                        reports: [
                            makeReport({ id: 1, section_id: 1 }),
                            makeReport({ id: 2, section_id: 2 }),
                            makeReport({ id: 3, section_id: 3 }),
                        ],
                    }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.reportedCount).toBe(3);
        });

        it('returns 0 when no active period', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: true, reports: [makeReport()] })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.reportedCount).toBe(0);
        });

        it('returns 0 when active period has no reports', () => {
            setPageProps({
                attendancePeriods: [makePeriod({ locked: false, reports: [] })],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.reportedCount).toBe(0);
        });
    });

    describe('totalCount', () => {
        it('sums sections across all floors', () => {
            setPageProps({
                floors: [
                    makeFloor({
                        id: 1,
                        sections: [
                            { id: 1, name: 'A' } as Section,
                            { id: 2, name: 'B' } as Section,
                        ],
                    }),
                    makeFloor({
                        id: 2,
                        sections: [{ id: 3, name: 'C' } as Section],
                    }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.totalCount).toBe(3);
        });

        it('handles floors with no sections', () => {
            setPageProps({
                floors: [
                    makeFloor({ id: 1, sections: undefined }),
                    makeFloor({ id: 2, sections: [] }),
                ],
                userRoles: ['Owner'],
            });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.totalCount).toBe(0);
        });

        it('returns 0 when no floors exist', () => {
            setPageProps({ floors: [], userRoles: ['Owner'] });

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.totalCount).toBe(0);
        });
    });

    describe('default props', () => {
        it('handles missing props gracefully', () => {
            setPageProps({});

            const { result } = renderHook(() => useAttendanceReport());
            expect(result.current.activePeriod).toBeNull();
            expect(result.current.canStart).toBe(false);
            expect(result.current.canStop).toBe(false);
            expect(result.current.reportedCount).toBe(0);
            expect(result.current.totalCount).toBe(0);
        });
    });
});
