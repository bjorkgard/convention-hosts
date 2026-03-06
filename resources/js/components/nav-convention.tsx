import { Link, usePage } from '@inertiajs/react';
import { Building2, Grid3X3, Search, Users } from 'lucide-react';

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
    const { isOwner, isConventionUser, isFloorUser } = useConventionRole();
    const { isCurrentUrl } = useCurrentUrl();

    if (!convention) return null;

    const conventionId = convention.id;
    const canSeeFloors = isOwner || isConventionUser || isFloorUser;
    const canSeeUsers = isOwner || isConventionUser || isFloorUser;

    const items: NavItem[] = [];

    // Floors: visible to Owner, ConventionUser, FloorUser
    if (canSeeFloors) {
        items.push({
            title: 'Floors',
            href: floorsIndex.url(conventionId),
            icon: Building2,
        });
    }

    // Sections: visible to ALL roles — points to convention detail which shows floors/sections
    items.push({
        title: 'Sections',
        href: conventionShow.url(conventionId),
        icon: Grid3X3,
    });

    // Users: visible to Owner, ConventionUser, FloorUser
    if (canSeeUsers) {
        items.push({
            title: 'Users',
            href: usersIndex.url(conventionId),
            icon: Users,
        });
    }

    // Search: visible to ALL roles
    items.push({
        title: 'Search',
        href: searchIndex.url(conventionId),
        icon: Search,
    });

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
