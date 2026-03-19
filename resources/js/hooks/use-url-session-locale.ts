import { usePage } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import type { PageProps } from '@/types';

function storageKey(conventionId: string): string {
    return `locale_${conventionId}`;
}

export function useUrlSessionLocale() {
    const { urlSession } = usePage<PageProps>().props;
    const { i18n } = useTranslation();

    const conventionId = urlSession?.convention_id ?? null;

    // On mount, restore stored locale for this convention
    useEffect(() => {
        if (!conventionId) return;

        const stored = localStorage.getItem(storageKey(conventionId));
        if (stored && stored !== i18n.language) {
            i18n.changeLanguage(stored);
        }
    }, [conventionId, i18n]);

    const setLocale = useCallback(
        (locale: string) => {
            if (conventionId) {
                localStorage.setItem(storageKey(conventionId), locale);
            }
            i18n.changeLanguage(locale);
        },
        [conventionId, i18n],
    );

    return { setLocale, conventionId };
}
