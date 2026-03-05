import type { User } from './auth';

export type Role = 'Owner' | 'ConventionUser' | 'FloorUser' | 'SectionUser';

export interface ConventionUser extends User {
    first_name: string;
    last_name: string;
    mobile: string | null;
    email_confirmed: boolean;
    roles?: Role[];
    floor_ids?: number[];
    section_ids?: number[];
}
