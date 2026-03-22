import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/ConventionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

export default function ConventionsCreate() {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: index.url() },
        { title: t('convention.create.heading'), href: create.url() },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('convention.create.title')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index.url()}>
                            <ArrowLeft />
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('convention.create.heading')}
                    </h1>
                </div>

                <Card className="mx-auto w-full max-w-2xl rounded-xl border border-border shadow-sm">
                    <CardHeader>
                        <CardTitle>
                            {t('convention.create.card_title')}
                        </CardTitle>
                        <CardDescription>
                            {t('convention.create.card_description')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()} className="space-y-6">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            {t('convention.create.name_label')}
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            placeholder={t(
                                                'convention.create.name_placeholder',
                                            )}
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="city">
                                                {t(
                                                    'convention.create.city_label',
                                                )}
                                            </Label>
                                            <Input
                                                id="city"
                                                name="city"
                                                required
                                                placeholder={t(
                                                    'convention.create.city_placeholder',
                                                )}
                                                autoComplete="off"
                                            />
                                            <InputError message={errors.city} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="country">
                                                {t(
                                                    'convention.create.country_label',
                                                )}
                                            </Label>
                                            <Input
                                                id="country"
                                                name="country"
                                                required
                                                placeholder={t(
                                                    'convention.create.country_placeholder',
                                                )}
                                                autoComplete="off"
                                            />
                                            <InputError
                                                message={errors.country}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address">
                                            {t(
                                                'convention.create.address_label',
                                            )}
                                        </Label>
                                        <Input
                                            id="address"
                                            name="address"
                                            placeholder={t(
                                                'convention.create.address_placeholder',
                                            )}
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.address} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_date">
                                                {t(
                                                    'convention.create.start_date_label',
                                                )}
                                            </Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                required
                                            />
                                            <InputError
                                                message={errors.start_date}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="end_date">
                                                {t(
                                                    'convention.create.end_date_label',
                                                )}
                                            </Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                                required
                                            />
                                            <InputError
                                                message={errors.end_date}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="other_info">
                                            {t(
                                                'convention.create.other_info_label',
                                            )}
                                        </Label>
                                        <textarea
                                            id="other_info"
                                            name="other_info"
                                            rows={3}
                                            placeholder={t(
                                                'convention.create.other_info_placeholder',
                                            )}
                                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        />
                                        <InputError
                                            message={errors.other_info}
                                        />
                                    </div>

                                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                                        <Button
                                            variant="outline"
                                            className="w-full sm:w-auto"
                                            asChild
                                        >
                                            <Link href={index.url()}>
                                                {t('convention.create.cancel')}
                                            </Link>
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            {processing
                                                ? t(
                                                      'convention.create.submitting',
                                                  )
                                                : t('convention.create.submit')}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
