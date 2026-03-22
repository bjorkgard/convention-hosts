import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import type { PageProps, UrlSession } from '@/types';
import type { Role } from '@/types/user';

interface ConventionRolePageProps extends PageProps {
    userRoles?: Role[];
    urlSession?: UrlSession | null;
}

interface UseConventionRoleReturn {
    readonly isOwner: boolean;
    readonly isAdministrator: boolean;
    readonly isManager: boolean;
    readonly isUrlSession: boolean;
    readonly isSectionUrlSession: boolean;
}

export function useConventionRole(): UseConventionRoleReturn {
    const { userRoles = [], urlSession } =
        usePage<ConventionRolePageProps>().props;

    return useMemo(() => {
        const roles = new Set<string>(userRoles);

        const isOwner = roles.has('Owner');
        const isAdministrator = roles.has('Administrator');
        const isManager = isOwner || isAdministrator;
        const isUrlSession = !!urlSession;
        const isSectionUrlSession = urlSession?.type === 'section';

        return {
            isOwner,
            isAdministrator,
            isManager,
            isUrlSession,
            isSectionUrlSession,
        } as const;
    }, [userRoles, urlSession]);
}
