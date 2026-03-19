import { Link, usePage } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';

import { index as conventionsIndex } from '@/actions/App/Http/Controllers/ConventionController';
import AppLogo from '@/components/app-logo';
import { LocaleSelector } from '@/components/locale-selector';
import { NavConvention } from '@/components/nav-convention';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { VersionBadge } from '@/components/version-badge';
import { useConventionRole } from '@/hooks/use-convention-role';
import type { NavItem, PageProps } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Conventions',
        href: conventionsIndex.url(),
        icon: CalendarDays,
    },
];

export function AppSidebar() {
    const { isUrlSession } = useConventionRole();
    const { urlSession } = usePage<PageProps>().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={conventionsIndex.url()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {!isUrlSession && <NavMain items={mainNavItems} />}
                <NavConvention />
            </SidebarContent>

            <SidebarFooter>
                {!isUrlSession && <NavUser />}
                {isUrlSession && urlSession && (
                    <div className="px-2 group-data-[collapsible=icon]:hidden">
                        <LocaleSelector
                            conventionId={urlSession.convention_id}
                            useLocalStorage
                            variant="ghost"
                            size="sm"
                        />
                    </div>
                )}
                <VersionBadge className="px-2 pb-1 text-left group-data-[collapsible=icon]:hidden" />
            </SidebarFooter>
        </Sidebar>
    );
}
