import { Link } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { show } from '@/actions/App/Http/Controllers/SectionController';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { getOccupancyColorClass } from '@/hooks/use-occupancy-color';
import { cn } from '@/lib/utils';
import type { Floor, Section } from '@/types/convention';
import type { Role } from '@/types/user';

interface FloorRowProps {
    floor: Floor;
    sections: Section[];
    userRole: Role;
    userFloorIds?: number[];
    userSectionIds?: number[];
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

function canEdit(role: Role): boolean {
    return role === 'Owner' || role === 'ConventionUser' || role === 'FloorUser';
}

function canDelete(role: Role): boolean {
    return role === 'Owner' || role === 'ConventionUser';
}

function canEditSection(role: Role, floor: Floor, userFloorIds?: number[]): boolean {
    if (role === 'Owner' || role === 'ConventionUser') return true;
    if (role === 'FloorUser' && userFloorIds?.includes(floor.id)) return true;
    return false;
}

function canDeleteSection(role: Role, floor: Floor, userFloorIds?: number[]): boolean {
    if (role === 'Owner' || role === 'ConventionUser') return true;
    if (role === 'FloorUser' && userFloorIds?.includes(floor.id)) return true;
    return false;
}

export default function FloorRow({ floor, sections, userRole, userFloorIds, onEdit, onDelete, onEditSection, onDeleteSection }: FloorRowProps) {
    const [isOpen, setIsOpen] = useState(false);
    const averageOccupancy = getAverageOccupancy(sections);

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen} className="rounded-lg border">
            <div className="flex items-center justify-between gap-2 px-3 py-3 sm:px-4">
                <CollapsibleTrigger className="flex min-w-0 flex-1 cursor-pointer items-center gap-2 sm:gap-3">
                    <ChevronDown
                        className={cn('size-4 shrink-0 transition-transform duration-200', isOpen && 'rotate-180')}
                    />
                    <span className="truncate font-medium">{floor.name}</span>
                    <span
                        className={cn('inline-flex size-3 shrink-0 rounded-full', getOccupancyColorClass(averageOccupancy))}
                        aria-label={`Average occupancy ${averageOccupancy}%`}
                    />
                    <span className="text-muted-foreground shrink-0 text-sm">
                        {sections.length} {sections.length === 1 ? 'section' : 'sections'}
                    </span>
                </CollapsibleTrigger>

                {(canEdit(userRole) || canDelete(userRole)) && (
                    <div className="flex items-center gap-1">
                        {canEdit(userRole) && onEdit && (
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
                        )}
                        {canDelete(userRole) && onDelete && (
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
                        )}
                    </div>
                )}
            </div>

            <CollapsibleContent>
                {sections.length === 0 ? (
                    <p className="text-muted-foreground px-4 pb-3 text-sm">No sections on this floor.</p>
                ) : (
                    <ul className="border-t">
                        {sections.map((section) => {
                            const showEditSection = canEditSection(userRole, floor, userFloorIds) && !!onEditSection;
                            const showDeleteSection = canDeleteSection(userRole, floor, userFloorIds) && !!onDeleteSection;

                            return (
                                <li key={section.id} className="border-b last:border-b-0">
                                    <div className="flex items-center gap-0 transition-colors hover:bg-accent/50">
                                        <Link
                                            href={show.url(section.id)}
                                            className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-4 py-2.5 sm:px-6"
                                        >
                                            <span
                                                className={cn(
                                                    'inline-flex size-2.5 shrink-0 rounded-full',
                                                    getOccupancyColorClass(section.occupancy),
                                                )}
                                                aria-label={`Occupancy ${section.occupancy}%`}
                                            />
                                            <span className="flex-1 truncate text-sm font-medium">{section.name}</span>
                                            <span className="text-muted-foreground shrink-0 text-xs">
                                                {section.available_seats}/{section.number_of_seats} seats
                                            </span>
                                        </Link>
                                        {(showEditSection || showDeleteSection) && (
                                            <div className="flex shrink-0 items-center gap-0.5 pr-2 sm:pr-3">
                                                {showEditSection && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7 cursor-pointer"
                                                        onClick={() => onEditSection(section)}
                                                        aria-label={`Edit ${section.name}`}
                                                    >
                                                        <Pencil className="size-3.5" />
                                                    </Button>
                                                )}
                                                {showDeleteSection && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7 cursor-pointer"
                                                        onClick={() => onDeleteSection(section)}
                                                        aria-label={`Delete ${section.name}`}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
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
