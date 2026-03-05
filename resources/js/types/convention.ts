import type { User } from './auth';

export interface Convention {
    id: number;
    name: string;
    city: string;
    country: string;
    address: string | null;
    start_date: string;
    end_date: string;
    other_info: string | null;
    created_at: string;
    updated_at: string;
    floors?: Floor[];
    users?: User[];
    attendance_periods?: AttendancePeriod[];
}

export interface Floor {
    id: number;
    convention_id: number;
    name: string;
    created_at: string;
    updated_at: string;
    convention?: Convention;
    sections?: Section[];
    users?: User[];
}

export interface Section {
    id: number;
    floor_id: number;
    name: string;
    number_of_seats: number;
    occupancy: number;
    available_seats: number;
    elder_friendly: boolean;
    handicap_friendly: boolean;
    information: string | null;
    last_occupancy_updated_by: number | null;
    last_occupancy_updated_at: string | null;
    created_at: string;
    updated_at: string;
    floor?: Floor;
    last_updated_by?: User | null;
    users?: User[];
    attendance_reports?: AttendanceReport[];
}

export interface AttendancePeriod {
    id: number;
    convention_id: number;
    date: string;
    period: 'morning' | 'afternoon';
    locked: boolean;
    created_at: string;
    updated_at: string;
    convention?: Convention;
    reports?: AttendanceReport[];
}

export interface AttendanceReport {
    id: number;
    attendance_period_id: number;
    section_id: number;
    attendance: number;
    reported_by: number;
    reported_at: string;
    created_at: string;
    updated_at: string;
    period?: AttendancePeriod;
    section?: Section;
    reported_by_user?: User | null;
}
