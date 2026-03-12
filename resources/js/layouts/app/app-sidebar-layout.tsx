import { useEffect } from 'react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import AuthenticatedConsentPrompt from '@/components/authenticated-consent-prompt';
import InstallPrompt from '@/components/install-prompt';
import { Toaster } from '@/components/ui/sonner';
import { UpdateNotificationModal } from '@/components/update-notification-modal';
import { useAllowsOptionalStorage } from '@/hooks/use-consent';
import { cleanupOptionalStorage } from '@/lib/consent/optional-storage';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const allowsOptionalStorage = useAllowsOptionalStorage();

    useEffect(() => {
        if (!allowsOptionalStorage) {
            cleanupOptionalStorage();
        }
    }, [allowsOptionalStorage]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
                <AuthenticatedConsentPrompt />
                <div className="mt-auto px-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-20 md:hidden">
                    <InstallPrompt />
                </div>
            </AppContent>
            <UpdateNotificationModal />
            <Toaster />
        </AppShell>
    );
}
