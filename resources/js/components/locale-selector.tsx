import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { update as updateConvention } from '@/actions/App/Http/Controllers/ConventionController';
import { update as updateProfile } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PageProps } from '@/types';

const LOCALE_LABELS: Record<string, string> = {
    en: 'English',
    sv: 'Svenska',
};

function localeLabel(code: string): string {
    return LOCALE_LABELS[code] ?? code.toUpperCase();
}

let cachedLocales: string[] | null = null;

interface LocaleSelectorProps {
    /** When set, persists selection to conventions.locale for this convention */
    conventionId?: string;
    /** When true, persists to localStorage instead of backend (URL session users) */
    useLocalStorage?: boolean;
    /** Callback after locale changes */
    onLocaleChange?: (locale: string) => void;
    /** Button variant */
    variant?: 'outline' | 'ghost';
    /** Button size */
    size?: 'sm' | 'default' | 'icon';
    /** Additional class names */
    className?: string;
}

export function LocaleSelector({
    conventionId,
    useLocalStorage = false,
    onLocaleChange,
    variant = 'outline',
    size = 'sm',
    className,
}: LocaleSelectorProps) {
    const { i18n } = useTranslation();
    const { auth } = usePage<PageProps>().props;
    const [locales, setLocales] = useState<string[]>(cachedLocales ?? []);

    useEffect(() => {
        if (cachedLocales) return;

        fetch('/api/locales')
            .then((res) => res.json())
            .then((data: string[]) => {
                cachedLocales = data;
                setLocales(data);
            })
            .catch(() => {});
    }, []);

    const handleSelect = useCallback(
        (locale: string) => {
            if (locale === i18n.language) return;

            i18n.changeLanguage(locale);

            if (useLocalStorage && conventionId) {
                localStorage.setItem(`locale_${conventionId}`, locale);
            } else if (conventionId) {
                // Persist to convention locale
                router.put(
                    updateConvention.url(conventionId),
                    { locale } as Record<string, string>,
                    { preserveScroll: true, preserveState: true },
                );
            } else if (auth.user) {
                // Persist to user profile
                router.patch(
                    updateProfile.url(),
                    { locale } as Record<string, string>,
                    { preserveScroll: true, preserveState: true },
                );
            }

            onLocaleChange?.(locale);
        },
        [i18n, conventionId, useLocalStorage, auth.user, onLocaleChange],
    );

    if (locales.length < 2) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant={variant}
                    size={size}
                    className={`cursor-pointer gap-1.5 ${className ?? ''}`}
                >
                    <Globe className="size-4" />
                    {localeLabel(i18n.language)}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {locales.map((code) => (
                    <DropdownMenuItem
                        key={code}
                        className={`cursor-pointer gap-2 ${code === i18n.language ? 'bg-accent' : ''}`}
                        onClick={() => handleSelect(code)}
                    >
                        {localeLabel(code)}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
