import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import type { AttendancePeriod, Floor } from '@/types/convention';
import type { Role } from '@/types/user';

interface AttendancePageProps {
    attendancePeriods?: AttendancePeriod[];
    floors?: Floor[];
    userRoles?: Role[];
}

interface UseAttendanceReportReturn {
    readonly activePeriod: AttendancePeriod | null;
    readonly canStart: boolean;
    readonly canStop: boolean;
    readonly reportedCount: number;
    readonly totalCount: number;
}

export function useAttendanceReport(): UseAttendanceReportReturn {
    const {
        attendancePeriods = [],
        floors = [],
        userRoles = [],
    } = usePage<AttendancePageProps>().props;

    return useMemo(() => {
        const roles = new Set<string>(userRoles);
        const isManager = roles.has('Owner') || roles.has('ConventionUser');

        // Find the active (unlocked) period
        const activePeriod =
            attendancePeriods.find((p) => !p.locked) ?? null;

        // Count today's periods to check max 2 per day limit
        const today = new Date().toISOString().slice(0, 10);
        const todayPeriodCount = attendancePeriods.filter(
            (p) => p.date === today,
        ).length;

        const canStart = isManager && todayPeriodCount < 2 && activePeriod === null;
        const canStop = isManager && activePeriod !== null;

        // Count total sections across all floors
        const totalCount = floors.reduce(
            (sum, floor) => sum + (floor.sections?.length ?? 0),
            0,
        );

        // Count sections that have reported in the active period
        const reportedCount = activePeriod?.reports?.length ?? 0;

        return {
            activePeriod,
            canStart,
            canStop,
            reportedCount,
            totalCount,
        } as const;
    }, [attendancePeriods, floors, userRoles]);
}
