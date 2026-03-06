import type { User } from './auth';

export type Role = 'Owner' | 'ConventionUser' | 'FloorUser' | 'SectionUser';

export interface ConventionUser extends User {
    mobile: string | null;
    email_confirmed: boolean;
    roles?: Role[];
    floor_ids?: number[];
    section_ids?: number[];
}
