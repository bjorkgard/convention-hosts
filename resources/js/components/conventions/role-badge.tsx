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
    Administrator: {
        label: 'Administrator',
        className: 'bg-blue-100 text-blue-800 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300',
        description: 'Convention-wide access — can manage all floors, sections, and users',
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
