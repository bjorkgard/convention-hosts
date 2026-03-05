export type * from './auth';
export type * from './convention';
export type * from './navigation';
export type * from './ui';
export type * from './user';

import type { Auth } from './auth';

export type Flash = {
    success?: string;
    error?: string;
};

export type Errors = Record<string, string>;

export interface PageProps {
    auth: Auth;
    name: string;
    sidebarOpen: boolean;
    flash?: Flash;
    errors?: Errors;
}
