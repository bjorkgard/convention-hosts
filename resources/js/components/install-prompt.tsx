import { Download, MoreVertical, Plus, Share, Smartphone } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { useConsent } from '@/hooks/use-consent';
import {
    isOptionalStorageAllowed,
    readOptionalLocalStorage,
    removeOptionalLocalStorage,
    writeOptionalLocalStorage,
} from '@/lib/consent/optional-storage';

interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const INSTALL_PROMPT_DISMISSED_KEY = 'install-prompt-dismissed';

function isIosSafari(): boolean {
    if (typeof navigator === 'undefined') return false;
    const ua = navigator.userAgent;
    return (
        /iP(hone|od|ad)/.test(ua) &&
        /WebKit/.test(ua) &&
        !/(CriOS|FxiOS|OPiOS|mercury)/.test(ua)
    );
}

function isStandalone(): boolean {
    if (typeof window === 'undefined') return false;
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        ('standalone' in window.navigator &&
            (window.navigator as unknown as { standalone: boolean })
                .standalone === true)
    );
}

function isMobileDevice(): boolean {
    if (typeof navigator === 'undefined') return false;
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent,
    );
}

function wasPromptDismissed(): boolean {
    return (
        readOptionalLocalStorage(INSTALL_PROMPT_DISMISSED_KEY, {
            allowed: true,
            fallback: 'false',
            validate: (value): value is 'true' | 'false' =>
                value === 'true' || value === 'false',
        }) === 'true'
    );
}

export default function InstallPrompt() {
    const consent = useConsent();
    const allowOptionalStorage = isOptionalStorageAllowed(consent);
    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);
    const [showIos] = useState(() => isIosSafari());
    const [installed, setInstalled] = useState(() => isStandalone());
    const [autoOpen, setAutoOpen] = useState(false);

    const markPromptDismissed = useCallback((): void => {
        if (!allowOptionalStorage) {
            return;
        }

        writeOptionalLocalStorage(
            INSTALL_PROMPT_DISMISSED_KEY,
            'true',
            consent,
        );
    }, [allowOptionalStorage, consent]);

    useEffect(() => {
        if (!allowOptionalStorage) {
            removeOptionalLocalStorage(INSTALL_PROMPT_DISMISSED_KEY);
        }
    }, [allowOptionalStorage]);

    useEffect(() => {
        if (installed) return;

        const handler = (e: Event) => {
            e.preventDefault();
            setDeferredPrompt(e as BeforeInstallPromptEvent);
        };

        const appInstalledHandler = () => {
            setInstalled(true);
            setDeferredPrompt(null);
            markPromptDismissed();
        };

        window.addEventListener('beforeinstallprompt', handler);
        window.addEventListener('appinstalled', appInstalledHandler);

        return () => {
            window.removeEventListener('beforeinstallprompt', handler);
            window.removeEventListener('appinstalled', appInstalledHandler);
        };
    }, [installed]);

    // Auto-show modal on first visit for mobile browser users
    useEffect(() => {
        if (installed || isStandalone()) return;
        if (!isMobileDevice()) return;
        if (allowOptionalStorage && wasPromptDismissed()) return;

        // Small delay to let the page settle before showing the modal
        const timer = setTimeout(() => {
            setAutoOpen(true);
        }, 500);

        return () => clearTimeout(timer);
    }, [allowOptionalStorage, installed]);

    const handleAutoOpenChange = useCallback(
        (open: boolean) => {
            setAutoOpen(open);
            if (!open) {
                markPromptDismissed();
            }
        },
        [markPromptDismissed],
    );

    const handleInstallClick = useCallback(async () => {
        if (!deferredPrompt) return;
        await deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            setInstalled(true);
        }
        setDeferredPrompt(null);
        markPromptDismissed();
    }, [deferredPrompt, markPromptDismissed]);

    if (installed || (!deferredPrompt && !showIos)) {
        return null;
    }

    // Android/Chrome: direct install available
    if (deferredPrompt && !showIos) {
        return (
            <Dialog open={autoOpen} onOpenChange={handleAutoOpenChange}>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="flex-1 cursor-pointer"
                        onClick={handleInstallClick}
                    >
                        <Download className="size-4" />
                        Install App
                    </Button>
                    <DialogTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 shrink-0 cursor-pointer"
                            aria-label="Installation instructions"
                        >
                            <Smartphone className="size-4" />
                        </Button>
                    </DialogTrigger>
                </div>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Install App</DialogTitle>
                        <DialogDescription>
                            Add this app to your home screen for quick access.
                        </DialogDescription>
                    </DialogHeader>
                    <InstructionsContent />
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                className="cursor-pointer"
                            >
                                Close
                            </Button>
                        </DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    }

    // iOS Safari: show instructions dialog
    return (
        <Dialog open={autoOpen} onOpenChange={handleAutoOpenChange}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="w-full cursor-pointer"
                >
                    <Download className="size-4" />
                    Install App
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Install App</DialogTitle>
                    <DialogDescription>
                        Add this app to your home screen for quick access.
                    </DialogDescription>
                </DialogHeader>
                <InstructionsContent />
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline" className="cursor-pointer">
                            Close
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function InstructionsContent() {
    return (
        <div className="space-y-6">
            <div className="space-y-3">
                <h3 className="flex items-center gap-2 text-sm font-semibold">
                    iOS (Safari)
                </h3>
                <ol className="space-y-2 text-sm text-muted-foreground">
                    <li className="flex items-start gap-2">
                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                            1
                        </span>
                        <span className="flex items-center gap-1.5">
                            Tap the <Share className="inline size-4" /> Share
                            button
                        </span>
                    </li>
                    <li className="flex items-start gap-2">
                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                            2
                        </span>
                        <span className="flex items-center gap-1.5">
                            Tap <Plus className="inline size-4" /> &quot;Add to
                            Home Screen&quot;
                        </span>
                    </li>
                    <li className="flex items-start gap-2">
                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                            3
                        </span>
                        <span>Tap &quot;Add&quot;</span>
                    </li>
                </ol>
            </div>

            <div className="space-y-3">
                <h3 className="flex items-center gap-2 text-sm font-semibold">
                    Android (Chrome)
                </h3>
                <ol className="space-y-2 text-sm text-muted-foreground">
                    <li className="flex items-start gap-2">
                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                            1
                        </span>
                        <span className="flex items-center gap-1.5">
                            Tap the <MoreVertical className="inline size-4" />{' '}
                            menu
                        </span>
                    </li>
                    <li className="flex items-start gap-2">
                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                            2
                        </span>
                        <span className="flex items-center gap-1.5">
                            Tap <Download className="inline size-4" />{' '}
                            &quot;Install app&quot; or &quot;Add to Home
                            Screen&quot;
                        </span>
                    </li>
                </ol>
            </div>
        </div>
    );
}
