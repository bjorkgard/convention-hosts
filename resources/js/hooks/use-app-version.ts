import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface LatestRelease {
    version: string;
    name: string | null;
    body: string | null;
    published_at: string | null;
    html_url: string | null;
}

const CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutes

function isNewerVersion(latest: string, current: string): boolean {
    const parse = (v: string) =>
        v
            .replace(/^v/, '')
            .split('.')
            .map((n) => parseInt(n, 10) || 0);
    const [lMajor = 0, lMinor = 0, lPatch = 0] = parse(latest);
    const [cMajor = 0, cMinor = 0, cPatch = 0] = parse(current);

    if (lMajor !== cMajor) return lMajor > cMajor;
    if (lMinor !== cMinor) return lMinor > cMinor;
    return lPatch > cPatch;
}

export function useAppVersion() {
    const { appVersion } = usePage<{ appVersion: string | null }>().props;
    const [latestRelease, setLatestRelease] = useState<LatestRelease | null>(
        null,
    );
    const [hasUpdate, setHasUpdate] = useState(false);
    const [dismissed, setDismissed] = useState(false);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const checkForUpdate = useCallback(async () => {
        try {
            const response = await fetch('/api/version/latest');
            if (!response.ok) return;

            const data: LatestRelease = await response.json();

            if (
                data.version &&
                appVersion &&
                isNewerVersion(data.version, appVersion)
            ) {
                setLatestRelease(data);
                setHasUpdate(true);
            } else {
                setHasUpdate(false);
            }
        } catch {
            // Silently fail - version check is non-critical
        }
    }, [appVersion]);

    useEffect(() => {
        // Initial check after a short delay to not block page load
        const timeout = setTimeout(checkForUpdate, 3000);

        // Periodic checks
        intervalRef.current = setInterval(checkForUpdate, CHECK_INTERVAL);

        return () => {
            clearTimeout(timeout);
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [checkForUpdate]);

    const dismiss = useCallback(() => {
        setDismissed(true);
    }, []);

    const hardReload = useCallback(() => {
        window.location.reload();
    }, []);

    return {
        currentVersion: appVersion,
        latestRelease,
        hasUpdate,
        showModal: hasUpdate && !dismissed,
        dismiss,
        hardReload,
    };
}
