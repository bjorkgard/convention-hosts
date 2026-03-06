import { Link } from '@inertiajs/react';
import { Calendar, MapPin } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/ConventionController';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Convention } from '@/types/convention';

function formatDateRange(startDate: string, endDate: string): string {
    const start = new Date(startDate + 'T00:00:00');
    const end = new Date(endDate + 'T00:00:00');

    const startMonth = start.toLocaleDateString('en-US', { month: 'short' });
    const endMonth = end.toLocaleDateString('en-US', { month: 'short' });
    const startDay = start.getDate();
    const endDay = end.getDate();
    const endYear = end.getFullYear();

    if (startDate === endDate) {
        return `${startMonth} ${startDay}, ${endYear}`;
    }

    if (start.getFullYear() === end.getFullYear() && start.getMonth() === end.getMonth()) {
        return `${startMonth} ${startDay} - ${endDay}, ${endYear}`;
    }

    if (start.getFullYear() === end.getFullYear()) {
        return `${startMonth} ${startDay} - ${endMonth} ${endDay}, ${endYear}`;
    }

    const startYear = start.getFullYear();
    return `${startMonth} ${startDay}, ${startYear} - ${endMonth} ${endDay}, ${endYear}`;
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
