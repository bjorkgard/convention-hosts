import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

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

export default function ThemeSettings() {
    const { t } = useTranslation();
    const { theme, updateTheme } = useTheme();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('settings.theme.breadcrumb'), href: editTheme() },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.theme.title')} />

            <h1 className="sr-only">{t('settings.theme.title')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.theme.heading')}
                        description={t('settings.theme.description')}
                    />

                    <div className="space-y-2">
                        <label
                            className="text-sm leading-none font-medium"
                            htmlFor="theme-select"
                        >
                            {t('settings.theme.color_theme_label')}
                        </label>
                        <Select
                            value={theme}
                            onValueChange={(value) =>
                                updateTheme(value as Theme)
                            }
                        >
                            <SelectTrigger id="theme-select" className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {THEMES.map((th) => (
                                    <SelectItem key={th} value={th}>
                                        {THEME_LABELS[th]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-sm text-muted-foreground">
                            {t('settings.theme.reload_notice')}
                        </p>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
