import { router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import ConsentController from '@/actions/App/Http/Controllers/ConsentController';
import { Button } from '@/components/ui/button';
import { useConsent } from '@/hooks/use-consent';
import { cn } from '@/lib/utils';

type PendingDecision = 'accepted' | 'declined' | null;

export default function AuthenticatedConsentPrompt() {
    const consent = useConsent();
    const { t } = useTranslation();
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
                aria-label={t('common.cookie_consent.aria_label')}
                aria-busy={pendingDecision !== null}
                className="pointer-events-auto overflow-hidden rounded-2xl border border-border/70 bg-card/95 shadow-lg backdrop-blur-sm"
            >
                <div className="flex flex-col gap-4 p-4 md:flex-row md:items-end md:justify-between md:p-5">
                    <div className="space-y-3">
                        <p className="flex items-center gap-2 text-sm font-semibold text-foreground">
                            <ShieldCheck className="size-4" />
                            {t('common.cookie_consent.title')}
                        </p>
                        <div className="space-y-2 text-sm text-muted-foreground">
                            <p>{t('common.cookie_consent.essential')}</p>
                            <p>{t('common.cookie_consent.optional')}</p>
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
                                ? t('common.cookie_consent.saving')
                                : t('common.cookie_consent.decline')}
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
                                ? t('common.cookie_consent.saving')
                                : t('common.cookie_consent.accept_all')}
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    );
}
