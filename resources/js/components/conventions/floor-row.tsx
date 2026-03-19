import { Link } from '@inertiajs/react';
import { ChevronRight, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { show } from '@/actions/App/Http/Controllers/SectionController';
import OccupancyGauge from '@/components/conventions/occupancy-gauge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

import { cn } from '@/lib/utils';
import type { Floor, Section } from '@/types/convention';
import type { Role } from '@/types/user';

interface FloorRowProps {
    floor: Floor;
    sections: Section[];
    userRole: Role | null;
    defaultOpen?: boolean;
    onEdit?: (floor: Floor) => void;
    onDelete?: (floor: Floor) => void;
    onEditSection?: (section: Section) => void;
    onDeleteSection?: (section: Section) => void;
}

function getAverageOccupancy(sections: Section[]): number {
    if (sections.length === 0) return 0;
    const total = sections.reduce((sum, s) => sum + s.occupancy, 0);
    return Math.round(total / sections.length);
}

function canEdit(role: Role | null): boolean {
    return role === 'Owner' || role === 'Administrator';
}

function canDelete(role: Role | null): boolean {
    return role === 'Owner' || role === 'Administrator';
}

function canEditSection(role: Role | null): boolean {
    return role === 'Owner' || role === 'Administrator';
}

function canDeleteSection(role: Role | null): boolean {
    return role === 'Owner' || role === 'Administrator';
}

export default function FloorRow({ floor, sections, userRole, defaultOpen = false, onEdit, onDelete, onEditSection, onDeleteSection }: FloorRowProps) {
    const { t } = useTranslation();
    const [isOpen, setIsOpen] = useState(defaultOpen);
    const averageOccupancy = getAverageOccupancy(sections);

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen} className="rounded-xl border border-border transition-colors duration-200 hover:border-primary/20">
            <div className="flex items-center justify-between gap-2 px-3 py-3 sm:px-4">
                <CollapsibleTrigger className="flex min-w-0 flex-1 cursor-pointer items-center gap-2 sm:gap-3">
                    <ChevronRight
                        className={cn('size-4 shrink-0 transition-transform duration-200', isOpen && 'rotate-90')}
                    />
                    <span className="truncate font-medium">{floor.name}</span>
                    <OccupancyGauge occupancy={averageOccupancy} size={28} />
                    <span className="text-muted-foreground shrink-0 text-sm">
                        {t('floor.row.section_count', { count: sections.length })}
                    </span>
                </CollapsibleTrigger>

                {(canEdit(userRole) || canDelete(userRole)) && (
                    <div className="flex items-center gap-1">
                        {canEdit(userRole) && onEdit && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onEdit(floor);
                                        }}
                                        aria-label={`Edit ${floor.name}`}
                                    >
                                        <Pencil className="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{t('floor.row.edit_tooltip')}</TooltipContent>
                            </Tooltip>
                        )}
                        {canDelete(userRole) && onDelete && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onDelete(floor);
                                        }}
                                        aria-label={`Delete ${floor.name}`}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{t('floor.row.delete_tooltip')}</TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                )}
            </div>

            <CollapsibleContent>
                {sections.length === 0 ? (
                    <p className="text-muted-foreground px-4 pb-3 text-sm">{t('floor.row.no_sections')}</p>
                ) : (
                    <ul className="border-t">
                        {sections.map((section) => {
                            const showEditSection = canEditSection(userRole) && !!onEditSection;
                            const showDeleteSection = canDeleteSection(userRole) && !!onDeleteSection;

                            return (
                                <li key={section.id} className="border-b last:border-b-0">
                                    <div className="flex items-center gap-0 transition-colors duration-200 hover:bg-accent">
                                        <Link
                                            href={show.url(section.id)}
                                            className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-4 py-2.5 sm:px-6"
                                        >
                                            <OccupancyGauge occupancy={section.occupancy} size={32} />
                                            <span className="flex-1 truncate text-sm font-medium">{section.name}</span>
                                            <span className="text-muted-foreground shrink-0 text-xs">
                                                {t('floor.row.seats', { available: section.available_seats, total: section.number_of_seats })}
                                            </span>
                                        </Link>
                                        {(showEditSection || showDeleteSection) && (
                                            <div className="flex shrink-0 items-center gap-0.5 pr-2 sm:pr-3">
                                                {showEditSection && (
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-7 cursor-pointer"
                                                                onClick={() => onEditSection(section)}
                                                                aria-label={`Edit ${section.name}`}
                                                            >
                                                                <Pencil className="size-3.5" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>{t('floor.row.edit_section_tooltip')}</TooltipContent>
                                                    </Tooltip>
                                                )}
                                                {showDeleteSection && (
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-7 cursor-pointer"
                                                                onClick={() => onDeleteSection(section)}
                                                                aria-label={`Delete ${section.name}`}
                                                            >
                                                                <Trash2 className="size-3.5" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>{t('floor.row.delete_section_tooltip')}</TooltipContent>
                                                    </Tooltip>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </CollapsibleContent>
        </Collapsible>
    );
}
