import { usePage } from '@inertiajs/react';

export function VersionBadge({ className = '' }: { className?: string }) {
    const { appVersion } = usePage<{ appVersion: string | null }>().props;

    if (!appVersion) return null;

    return (
        <span
            className={`text-muted-foreground text-xs ${className}`}
            aria-label={`Application version ${appVersion}`}
        >
            {appVersion}
        </span>
    );
}
