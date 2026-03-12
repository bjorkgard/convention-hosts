export type * from './auth';
export type * from './convention';
export type * from './navigation';
export type * from './ui';
export type * from './user';

import type { Auth } from './auth';

export type ConsentState = 'accepted' | 'declined' | 'undecided';

export interface ConsentContract {
    state: ConsentState;
    version: number;
    allowOptionalStorage: boolean;
    decidedAt: string | null;
    updatedAt: string | null;
}

export type SharedConsentContract = ConsentContract;

export type Flash = {
    success?: string;
    error?: string;
};

export type Errors = Record<string, string>;

export interface PageProps {
    [key: string]: unknown;
    auth: Auth;
    consent: ConsentContract;
    name: string;
    sidebarOpen: boolean;
    appVersion: string | null;
    flash?: Flash;
    errors?: Errors;
}
