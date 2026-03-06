import { Link } from '@inertiajs/react';
import { Armchair, Heart, Users } from 'lucide-react';

import { show } from '@/actions/App/Http/Controllers/SectionController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getOccupancyColorClass } from '@/hooks/use-occupancy-color';
import { cn } from '@/lib/utils';
import type { Section } from '@/types/convention';

export default function SectionCard({ section }: { section: Section }) {
    return (
        <Link href={show.url(section.id)} className="group block">
            <Card className="cursor-pointer border-border transition-colors duration-200 hover:border-primary/30 hover:bg-accent">
                <CardHeader className="flex-row items-center gap-3 space-y-0">
                    <span
                        className={cn('inline-flex size-3 shrink-0 rounded-full', getOccupancyColorClass(section.occupancy))}
                        aria-label={`Occupancy ${section.occupancy}%`}
                    />
                    <CardTitle className="flex-1 text-base">{section.name}</CardTitle>
                    <span className="text-muted-foreground text-sm font-medium">{section.occupancy}%</span>
                </CardHeader>
                <CardContent className="flex flex-wrap items-center justify-between gap-2">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Users className="size-4 shrink-0" />
                        {section.available_seats}/{section.number_of_seats} seats
                    </span>
                    {(section.elder_friendly || section.handicap_friendly) && (
                        <div className="flex flex-wrap items-center gap-1.5">
                            {section.elder_friendly && (
                                <Badge variant="secondary" aria-label="Elder friendly">
                                    <Heart className="size-3" />
                                    <span className="hidden sm:inline">Elder</span>
                                </Badge>
                            )}
                            {section.handicap_friendly && (
                                <Badge variant="secondary" aria-label="Handicap friendly">
                                    <Armchair className="size-3" />
                                    <span className="hidden sm:inline">Accessible</span>
                                </Badge>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}
