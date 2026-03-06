import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { create, index } from '@/actions/App/Http/Controllers/ConventionController';
import ConventionCard from '@/components/conventions/convention-card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Convention } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Conventions',
        href: index.url(),
    },
];

interface ConventionsIndexProps {
    conventions: Convention[];
    canCreateConvention: boolean;
}

export default function ConventionsIndex({ conventions, canCreateConvention }: ConventionsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Conventions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">Conventions</h1>
                    {canCreateConvention && (
                        <Button asChild>
                            <Link href={create.url()}>
                                <Plus />
                                <span className="hidden sm:inline">Create Convention</span>
                                <span className="sm:hidden">Create</span>
                            </Link>
                        </Button>
                    )}
                </div>

                {conventions.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center dark:border-sidebar-border">
                        <p className="text-muted-foreground">No conventions yet.</p>
                        {canCreateConvention && (
                            <Button asChild variant="link" className="mt-2">
                                <Link href={create.url()}>Create your first convention</Link>
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
