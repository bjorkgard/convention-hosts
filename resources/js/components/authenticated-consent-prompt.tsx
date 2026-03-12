import { router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';

import ConsentController from '@/actions/App/Http/Controllers/ConsentController';
import { Button } from '@/components/ui/button';
import { useConsent } from '@/hooks/use-consent';
import { cn } from '@/lib/utils';

type PendingDecision = 'accepted' | 'declined' | null;

export default function AuthenticatedConsentPrompt() {
    const consent = useConsent();
    const [pendingDecision, setPendingDecision] = useState<PendingDecision>(null);

    if (consent.state !== 'undecided') {
        return null;
    }

    const submitDecision = (state: Exclude<PendingDecision, null>) => {
        if (pendingDecision !== null) {
            return;
        }

        setPendingDecision(state);

        router.post(
            ConsentController.store.url(),
            { state },
            {
                preserveScroll: true,
                onFinish: () => setPendingDecision(null),
            },
        );
    };

    return (
        <div className="fixed inset-x-4 bottom-4 z-40 md:left-[calc(var(--sidebar-width)+1rem)] md:right-4">
            <section
                role="region"
                aria-label="Cookie consent"
                aria-busy={pendingDecision !== null}
                className="pointer-events-auto overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-lg backdrop-blur-sm"
            >
                <div className="flex flex-col gap-4 p-4 md:flex-row md:items-end md:justify-between md:p-5">
                    <div className="space-y-3">
                        <p className="flex items-center gap-2 text-sm font-semibold text-foreground">
                            <ShieldCheck className="size-4" />
                            We use cookies
                        </p>
                        <div className="space-y-2 text-sm text-muted-foreground">
                            <p>
                                Essential cookies stay on because they keep you signed in and
                                protect account security.
                            </p>
                            <p>
                                Optional preference storage remembers your theme, appearance, and
                                sidebar state between visits.
                            </p>
                        </div>
                    </div>
                    <div className="grid w-full shrink-0 grid-cols-2 gap-2 md:w-auto md:min-w-72">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={pendingDecision !== null}
                            className={cn(
                                'cursor-pointer',
                                pendingDecision === 'declined' && 'opacity-100',
                            )}
                            onClick={() => submitDecision('declined')}
                        >
                            {pendingDecision === 'declined'
                                ? 'Saving...'
                                : 'Decline'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={pendingDecision !== null}
                            className={cn(
                                'cursor-pointer',
                                pendingDecision === 'accepted' && 'opacity-100',
                            )}
                            onClick={() => submitDecision('accepted')}
                        >
                            {pendingDecision === 'accepted'
                                ? 'Saving...'
                                : 'Accept all'}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    );
}
