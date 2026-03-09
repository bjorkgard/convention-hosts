import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { THEME_LABELS, THEMES, useTheme } from '@/hooks/use-theme';
import type { Theme } from '@/hooks/use-theme';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editTheme } from '@/routes/theme';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Theme settings',
        href: editTheme(),
    },
];

export default function ThemeSettings() {
    const { theme, updateTheme } = useTheme();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Theme settings" />

            <h1 className="sr-only">Theme settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Theme"
                        description="Choose a color theme for the application"
                    />

                    <div className="space-y-2">
                        <label
                            className="text-sm font-medium leading-none"
                            htmlFor="theme-select"
                        >
                            Color theme
                        </label>
                        <Select
                            value={theme}
                            onValueChange={(value) => updateTheme(value as Theme)}
                        >
                            <SelectTrigger id="theme-select" className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {THEMES.map((t) => (
                                    <SelectItem key={t} value={t}>
                                        {THEME_LABELS[t]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-muted-foreground text-sm">
                            The page will reload when you select a new theme.
                        </p>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
