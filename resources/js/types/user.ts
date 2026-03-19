import type { User } from './auth';

export type Role = 'Owner' | 'Administrator';

export interface ConventionUser extends User {
    mobile: string | null;
    email_confirmed: boolean;
    roles?: Role[];
}
