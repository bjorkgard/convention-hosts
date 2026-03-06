import { useCallback } from 'react';

export type GetInitialsFn = (firstName: string, lastName: string) => string;

export function useInitials(): GetInitialsFn {
    return useCallback((firstName: string, lastName: string): string => {
        const first = firstName.trim();
        const last = lastName.trim();

        if (!first && !last) return '';
        if (!last) return first.charAt(0).toUpperCase();
        if (!first) return last.charAt(0).toUpperCase();

        return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase();
    }, []);
}
