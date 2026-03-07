import { Link } from '@inertiajs/react';
import { AlertTriangle, ChevronRight, Pencil, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

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
        <Collapsible open={isOpen} onOpenChange={setIsOpen} className="rounded-xl border border-border transition-colors duration-200 hover:border-primary/20">
            <div className="flex items-center justify-between gap-2 px-3 py-3 sm:px-4">
                <CollapsibleTrigger className="flex min-w-0 flex-1 cursor-pointer items-center gap-2 sm:gap-3">
                    <ChevronRight
                        className={cn('size-4 shrink-0 transition-transform duration-200', isOpen && 'rotate-90')}
                    />
                    <span className="truncate font-medium">{floor.name}</span>
                    <OccupancyGauge occupancy={averageOccupancy} size={28} />
                    <span className="text-muted-foreground shrink-0 text-sm">
                        {sections.length} {sections.length === 1 ? 'section' : 'sections'}
                    </span>
                </CollapsibleTrigger>

                {(floor.users?.length ?? 0) > 0 && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <span className="text-muted-foreground flex shrink-0 cursor-default items-center gap-1 text-sm">
                                <Users className="size-3.5" />
                                {floor.users!.length}
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>
                            {floor.users!.map((u) => `${u.first_name} ${u.last_name}`).join(', ')}
                        </TooltipContent>
                    </Tooltip>
                )}

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
                                <TooltipContent>Rename this floor</TooltipContent>
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
                                <TooltipContent>Delete this floor and all its sections</TooltipContent>
                            </Tooltip>
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
                                    <div className="flex items-center gap-0 transition-colors duration-200 hover:bg-accent">
                                        <Link
                                            href={show.url(section.id)}
                                            className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-4 py-2.5 sm:px-6"
                                        >
                                            <OccupancyGauge occupancy={section.occupancy} size={32} />
                                            <span className={cn('flex-1 truncate text-sm font-medium', (section.users?.length ?? 0) === 0 && 'text-red-600 dark:text-red-400')}>{section.name}</span>
                                            <span className="text-muted-foreground shrink-0 text-xs">
                                                {section.available_seats}/{section.number_of_seats} seats
                                            </span>
                                        </Link>
                                        {(section.users?.length ?? 0) > 0 ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <span className="text-muted-foreground flex shrink-0 cursor-default items-center gap-1 text-xs">
                                                        <Users className="size-3" />
                                                        {section.users!.length}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    {section.users!.map((u) => `${u.first_name} ${u.last_name}`).join(', ')}
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <span className="flex shrink-0 cursor-default items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                                        <AlertTriangle className="size-3" />
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>No section manager assigned</TooltipContent>
                                            </Tooltip>
                                        )}
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
                                                        <TooltipContent>Edit section details</TooltipContent>
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
                                                        <TooltipContent>Delete this section</TooltipContent>
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
