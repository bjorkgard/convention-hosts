import { usePage } from '@inertiajs/react';
import type { ConsentContract, PageProps } from '@/types';

const FALLBACK_CONSENT: ConsentContract = {
    state: 'undecided',
    version: 1,
    allowOptionalStorage: false,
    decidedAt: null,
    updatedAt: null,
};

function isConsentContract(value: unknown): value is ConsentContract {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const candidate = value as Partial<ConsentContract>;

    return (
        (candidate.state === 'accepted'
            || candidate.state === 'declined'
            || candidate.state === 'undecided')
        && typeof candidate.version === 'number'
        && typeof candidate.allowOptionalStorage === 'boolean'
    );
}

export function useConsent(): ConsentContract {
    const page = usePage<PageProps>();

    return isConsentContract(page.props.consent)
        ? page.props.consent
        : FALLBACK_CONSENT;
}

export function useAllowsOptionalStorage(): boolean {
    return useConsent().allowOptionalStorage;
}
