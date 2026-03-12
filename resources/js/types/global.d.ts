import type { ConsentContract } from '@/types';
import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            consent: ConsentContract;
            sidebarOpen: boolean;
            appVersion?: string | null;
            [key: string]: unknown;
        };
    }
}
