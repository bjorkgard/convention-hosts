import { Link } from '@inertiajs/react';
import { Armchair, Heart, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { show } from '@/actions/App/Http/Controllers/SectionController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getOccupancyColorClass } from '@/hooks/use-occupancy-color';
import { cn } from '@/lib/utils';
import type { Section } from '@/types/convention';

export default function SectionCard({ section }: { section: Section }) {
    const { t } = useTranslation();

    return (
        <Link href={show.url(section.id)} className="group block">
            <Card className="cursor-pointer border-border transition-colors duration-200 hover:border-primary/30 hover:bg-accent">
                <CardHeader className="flex-row items-center gap-3 space-y-0">
                    <span
                        className={cn('inline-flex size-3 shrink-0 rounded-full', getOccupancyColorClass(section.occupancy))}
                        aria-label={`${t('section.occupancy.label')} ${section.occupancy}%`}
                    />
                    <CardTitle className="flex-1 text-base">{section.name}</CardTitle>
                    <span className="text-muted-foreground text-sm font-medium">{section.occupancy}%</span>
                </CardHeader>
                <CardContent className="flex flex-wrap items-center justify-between gap-2">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Users className="size-4 shrink-0" />
                        {t('section.card.seats', { available: section.available_seats, total: section.number_of_seats })}
                    </span>
                    {(section.elder_friendly || section.handicap_friendly) && (
                        <div className="flex flex-wrap items-center gap-1.5">
                            {section.elder_friendly && (
                                <Badge variant="secondary" aria-label={t('section.card.elder_label')}>
                                    <Heart className="size-3" />
                                    <span className="hidden sm:inline">{t('section.card.elder_short')}</span>
                                </Badge>
                            )}
                            {section.handicap_friendly && (
                                <Badge variant="secondary" aria-label={t('section.card.handicap_label')}>
                                    <Armchair className="size-3" />
                                    <span className="hidden sm:inline">{t('section.card.handicap_short')}</span>
                                </Badge>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}
