import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import type { Role } from '@/types/user';

interface ConventionRolePageProps {
    userRoles?: Role[];
    userFloorIds?: number[];
    userSectionIds?: number[];
}

interface UseConventionRoleReturn {
    readonly isOwner: boolean;
    readonly isConventionUser: boolean;
    readonly isFloorUser: boolean;
    readonly isSectionUser: boolean;
    readonly hasFloorAccess: (floorId: number) => boolean;
    readonly hasSectionAccess: (sectionId: number) => boolean;
}

export function useConventionRole(): UseConventionRoleReturn {
    const { userRoles = [], userFloorIds = [], userSectionIds = [] } =
        usePage<ConventionRolePageProps>().props;

    return useMemo(() => {
        const roles = new Set<string>(userRoles);

        const isOwner = roles.has('Owner');
        const isConventionUser = roles.has('ConventionUser');
        const isFloorUser = roles.has('FloorUser');
        const isSectionUser = roles.has('SectionUser');

        const floorIdSet = new Set(userFloorIds);
        const sectionIdSet = new Set(userSectionIds);

        const hasFloorAccess = (floorId: number): boolean => {
            if (isOwner || isConventionUser) return true;
            return floorIdSet.has(floorId);
        };

        const hasSectionAccess = (sectionId: number): boolean => {
            if (isOwner || isConventionUser) return true;
            return sectionIdSet.has(sectionId);
        };

        return {
            isOwner,
            isConventionUser,
            isFloorUser,
            isSectionUser,
            hasFloorAccess,
            hasSectionAccess,
        } as const;
    }, [userRoles, userFloorIds, userSectionIds]);
}
