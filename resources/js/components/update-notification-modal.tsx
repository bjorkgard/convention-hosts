import { RefreshCw } from 'lucide-react';

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

    if (!latestRelease) return null;

    return (
        <Dialog open={showModal} onOpenChange={(open) => !open && dismiss()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New Version Available</DialogTitle>
                    <DialogDescription>
                        A new version of the application has been released.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            Current version
                        </span>
                        <code className="bg-muted rounded px-2 py-0.5 text-xs">
                            {currentVersion ?? 'unknown'}
                        </code>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            New version
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
                        <div className="bg-muted max-h-48 overflow-y-auto rounded-md p-3">
                            <pre className="text-muted-foreground whitespace-pre-wrap text-xs">
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
                        Reload Now
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
