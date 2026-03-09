import { Link } from '@inertiajs/react';
import { Calendar, MapPin } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/ConventionController';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Convention } from '@/types/convention';

function formatDateRange(startDate: string, endDate: string): string {
    // Slice to YYYY-MM-DD to handle full ISO timestamps; use noon to avoid DST edge cases
    const start = new Date(startDate.slice(0, 10) + 'T12:00:00');
    const end = new Date(endDate.slice(0, 10) + 'T12:00:00');

    const fmt = (d: Date, opts: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat('sv-SE', opts).format(d);

    if (startDate.slice(0, 10) === endDate.slice(0, 10)) {
        return fmt(start, { day: 'numeric', month: 'long', year: 'numeric' }); // "15 juni 2025"
    }

    if (start.getFullYear() === end.getFullYear() && start.getMonth() === end.getMonth()) {
        return `${start.getDate()}–${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`; // "10–15 juni 2025"
    }

    if (start.getFullYear() === end.getFullYear()) {
        return `${fmt(start, { day: 'numeric', month: 'long' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`; // "10 juni – 15 juli 2025"
    }

    return `${fmt(start, { day: 'numeric', month: 'long', year: 'numeric' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`; // "28 december 2025 – 3 januari 2026"
}

export default function ConventionCard({ convention }: { convention: Convention }) {
    return (
        <Link href={show.url(convention.id)} className="group block">
            <Card className="cursor-pointer border-border transition-colors duration-200 hover:border-primary/30 hover:bg-accent">
                <CardHeader>
                    <CardTitle className="text-lg">{convention.name}</CardTitle>
                    <CardDescription className="flex flex-col gap-1.5">
                        <span className="flex items-center gap-1.5">
                            <span className="flex size-6 items-center justify-center rounded-md bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-primary-foreground">
                                <Calendar className="size-3.5" />
                            </span>
                            {formatDateRange(convention.start_date, convention.end_date)}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="flex size-6 items-center justify-center rounded-md bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-primary-foreground">
                                <MapPin className="size-3.5" />
                            </span>
                            {convention.city}, {convention.country}
                        </span>
                    </CardDescription>
                </CardHeader>
            </Card>
        </Link>
    );
}
