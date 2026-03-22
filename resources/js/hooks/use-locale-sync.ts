import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import type { PageProps } from '@/types';

export function useLocaleSync() {
    const { locale, urlSession } = usePage<PageProps>().props;
    const { i18n } = useTranslation();

    useEffect(() => {
        // URL-session users may have a per-convention preference in localStorage.
        // That preference should take priority over the server-provided convention locale,
        // otherwise every Inertia navigation resets their choice.
        if (urlSession?.convention_id) {
            const stored = localStorage.getItem(
                `locale_${urlSession.convention_id}`,
            );
            if (stored) {
                if (i18n.language !== stored) {
                    i18n.changeLanguage(stored);
                }
                return;
            }
        }

        if (locale && i18n.language !== locale) {
            i18n.changeLanguage(locale);
        }
    }, [locale, urlSession, i18n]);
}
