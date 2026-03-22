import { RefreshCw } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAppVersion } from '@/hooks/use-app-version';

export function UpdateNotificationModal() {
    const { showModal, latestRelease, currentVersion, dismiss, hardReload } =
        useAppVersion();
    const { t } = useTranslation();

    if (!latestRelease) return null;

    return (
        <Dialog open={showModal} onOpenChange={(open) => !open && dismiss()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('common.update_notification.title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('common.update_notification.description', {
                            version: latestRelease.version,
                        })}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            {t('common.update_notification.current_version')}
                        </span>
                        <code className="rounded bg-muted px-2 py-0.5 text-xs">
                            {currentVersion ?? 'unknown'}
                        </code>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            {t('common.update_notification.new_version')}
                        </span>
                        <code className="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            {latestRelease.version}
                        </code>
                    </div>

                    {latestRelease.name &&
                        latestRelease.name !== latestRelease.version && (
                            <p className="text-sm font-medium">
                                {latestRelease.name}
                            </p>
                        )}

                    {latestRelease.body && (
                        <div className="max-h-48 overflow-y-auto rounded-md bg-muted p-3">
                            <pre className="text-xs whitespace-pre-wrap text-muted-foreground">
                                {latestRelease.body}
                            </pre>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        variant="default"
                        className="cursor-pointer"
                        onClick={hardReload}
                    >
                        <RefreshCw className="size-4" />
                        {t('common.update_notification.update_button')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
