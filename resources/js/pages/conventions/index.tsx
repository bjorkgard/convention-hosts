import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { create, index } from '@/actions/App/Http/Controllers/ConventionController';
import ConventionCard from '@/components/conventions/convention-card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Convention } from '@/types';

interface ConventionsIndexProps {
    conventions: Convention[];
    canCreateConvention: boolean;
}

export default function ConventionsIndex({ conventions, canCreateConvention }: ConventionsIndexProps) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: index.url() },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('convention.index.title')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('convention.index.heading')}</h1>
                        <p className="text-muted-foreground text-sm">
                            {t('convention.index.description')}
                        </p>
                    </div>
                    {canCreateConvention && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button asChild className="cursor-pointer">
                                    <Link href={create.url()}>
                                        <Plus />
                                        <span className="hidden sm:inline">{t('convention.index.create_button')}</span>
                                        <span className="sm:hidden">{t('convention.index.create_short')}</span>
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{t('convention.index.create_tooltip')}</TooltipContent>
                        </Tooltip>
                    )}
                </div>

                {conventions.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <div className="mb-4 flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <CalendarDays className="size-6" />
                        </div>
                        <p className="text-muted-foreground">{t('convention.index.empty')}</p>
                        {canCreateConvention && (
                            <Button asChild variant="link" className="mt-2 cursor-pointer">
                                <Link href={create.url()}>{t('convention.index.empty_create')}</Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {conventions.map((convention) => (
                            <ConventionCard key={convention.id} convention={convention} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
