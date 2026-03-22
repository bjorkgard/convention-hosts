import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export function VersionBadge({ className = '' }: { className?: string }) {
    const { appVersion } = usePage<{ appVersion: string | null }>().props;
    const { t } = useTranslation();

    if (!appVersion) return null;

    return (
        <span
            className={`text-xs text-muted-foreground ${className}`}
            aria-label={t('common.version_badge_aria', { version: appVersion })}
        >
            {appVersion}
        </span>
    );
}
