import { useTranslation } from 'react-i18next';

import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { Role } from '@/types/user';

const roleStyles: Record<Role, string> = {
    Owner: 'bg-purple-100 text-purple-800 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300',
    Administrator: 'bg-blue-100 text-blue-800 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300',
};

const roleKeys: Record<Role, { label: string; description: string }> = {
    Owner: { label: 'user.roles.owner', description: 'user.roles.owner_description' },
    Administrator: { label: 'user.roles.administrator', description: 'user.roles.administrator_description' },
};

export default function RoleBadge({ role }: { role: Role }) {
    const { t } = useTranslation();
    const keys = roleKeys[role];

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Badge variant="secondary" className={cn('cursor-default text-xs font-medium', roleStyles[role])}>
                    {t(keys.label)}
                </Badge>
            </TooltipTrigger>
            <TooltipContent>{t(keys.description)}</TooltipContent>
        </Tooltip>
    );
}
