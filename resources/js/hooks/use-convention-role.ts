import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import type { UrlSession } from '@/types';
import type { Role } from '@/types/user';

interface ConventionRolePageProps {
    userRoles?: Role[];
    urlSession?: UrlSession | null;
}

interface UseConventionRoleReturn {
    readonly isOwner: boolean;
    readonly isAdministrator: boolean;
    readonly isManager: boolean;
    readonly isUrlSession: boolean;
    readonly isFloorUrlSession: boolean;
    readonly isSectionUrlSession: boolean;
}

export function useConventionRole(): UseConventionRoleReturn {
    const { userRoles = [], urlSession } = usePage<ConventionRolePageProps>().props;

    return useMemo(() => {
        const roles = new Set<string>(userRoles);

        const isOwner = roles.has('Owner');
        const isAdministrator = roles.has('Administrator');
        const isManager = isOwner || isAdministrator;
        const isUrlSession = !!urlSession;
        const isFloorUrlSession = urlSession?.type === 'floor';
        const isSectionUrlSession = urlSession?.type === 'section';

        return {
            isOwner,
            isAdministrator,
            isManager,
            isUrlSession,
            isFloorUrlSession,
            isSectionUrlSession,
        } as const;
    }, [userRoles, urlSession]);
}
