import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Role } from '@/types/user';

const roleConfig: Record<Role, { label: string; className: string }> = {
    Owner: {
        label: 'Owner',
        className: 'bg-purple-100 text-purple-800 hover:bg-purple-100',
    },
    ConventionUser: {
        label: 'Convention',
        className: 'bg-blue-100 text-blue-800 hover:bg-blue-100',
    },
    FloorUser: {
        label: 'Floor',
        className: 'bg-amber-100 text-amber-800 hover:bg-amber-100',
    },
    SectionUser: {
        label: 'Section',
        className: 'bg-teal-100 text-teal-800 hover:bg-teal-100',
    },
};

export default function RoleBadge({ role }: { role: Role }) {
    const config = roleConfig[role];

    return (
        <Badge variant="secondary" className={cn('text-xs font-medium', config.className)}>
            {config.label}
        </Badge>
    );
}
