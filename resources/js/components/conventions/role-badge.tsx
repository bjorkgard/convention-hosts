import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { Role } from '@/types/user';

const roleConfig: Record<Role, { label: string; className: string; description: string }> = {
    Owner: {
        label: 'Owner',
        className: 'bg-purple-100 text-purple-800 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300',
        description: 'Full admin access — can delete convention, export data, and manage everything',
    },
    ConventionUser: {
        label: 'Convention',
        className: 'bg-blue-100 text-blue-800 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300',
        description: 'Convention-wide access — can manage all floors, sections, and users',
    },
    FloorUser: {
        label: 'Floor',
        className: 'bg-amber-100 text-amber-800 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300',
        description: 'Floor-scoped access — can manage sections on assigned floors only',
    },
    SectionUser: {
        label: 'Section',
        className: 'bg-teal-100 text-teal-800 hover:bg-teal-100 dark:bg-teal-900/30 dark:text-teal-300',
        description: 'Section-scoped access — can update occupancy for assigned sections only',
    },
};

export default function RoleBadge({ role }: { role: Role }) {
    const config = roleConfig[role];

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Badge variant="secondary" className={cn('cursor-default text-xs font-medium', config.className)}>
                    {config.label}
                </Badge>
            </TooltipTrigger>
            <TooltipContent>{config.description}</TooltipContent>
        </Tooltip>
    );
}
