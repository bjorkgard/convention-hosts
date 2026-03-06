import { Link } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';

import { index as conventionsIndex } from '@/actions/App/Http/Controllers/ConventionController';
import AppLogo from '@/components/app-logo';
import InstallPrompt from '@/components/install-prompt';
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Conventions',
        href: conventionsIndex.url(),
        icon: CalendarDays,
    },
];

export function AppSidebar() {
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
                <NavMain items={mainNavItems} />
                <NavConvention />
            </SidebarContent>

            <SidebarFooter>
                <InstallPrompt />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
