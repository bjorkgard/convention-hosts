import { Link, usePage } from '@inertiajs/react';
import { Building2, Grid3X3, Search, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { show as conventionShow } from '@/actions/App/Http/Controllers/ConventionController';
import { index as floorsIndex } from '@/actions/App/Http/Controllers/FloorController';
import { index as searchIndex } from '@/actions/App/Http/Controllers/SearchController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/UserController';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useConventionRole } from '@/hooks/use-convention-role';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { Convention } from '@/types/convention';
import type { NavItem } from '@/types/navigation';

interface ConventionPageProps {
    [key: string]: unknown;
    convention?: Convention;
}

export function NavConvention() {
    const { convention } = usePage<ConventionPageProps>().props;
    const { isManager, isUrlSession, isSectionUrlSession } = useConventionRole();
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTranslation();

    if (!convention) return null;

    const conventionId = convention.id;

    const items: NavItem[] = [];

    // Floors: visible to managers and floor URL sessions (not section URL sessions)
    if (isManager || (isUrlSession && !isSectionUrlSession)) {
        items.push({
            title: t('navigation.administration'),
            href: floorsIndex.url(conventionId),
            icon: Building2,
        });
    }

    // Sections: visible to ALL roles and URL sessions
    items.push({
        title: t('navigation.sections'),
        href: conventionShow.url(conventionId),
        icon: Grid3X3,
    });

    // Search: visible to ALL roles and URL sessions
    items.push({
        title: t('navigation.availability'),
        href: searchIndex.url(conventionId),
        icon: Search,
    });


    // Users: visible to managers only (not URL sessions)
    if (isManager && !isUrlSession) {
        items.push({
            title: t('navigation.users'),
            href: usersIndex.url(conventionId),
            icon: Users,
        });
    }
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{convention.name}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
