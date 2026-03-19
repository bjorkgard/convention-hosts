import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import type { PageProps } from '@/types';

export function useLocaleSync() {
    const { locale } = usePage<PageProps>().props;
    const { i18n } = useTranslation();

    useEffect(() => {
        if (locale && i18n.language !== locale) {
            i18n.changeLanguage(locale);
        }
    }, [locale, i18n]);
}
