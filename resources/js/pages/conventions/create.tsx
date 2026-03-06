import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { create, index, store } from '@/actions/App/Http/Controllers/ConventionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Conventions',
        href: index.url(),
    },
    {
        title: 'Create Convention',
        href: create.url(),
    },
];

export default function ConventionsCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Convention" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index.url()}>
                            <ArrowLeft />
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-tight">Create Convention</h1>
                </div>

                <Card className="mx-auto w-full max-w-2xl">
                    <CardHeader>
                        <CardTitle>Convention Details</CardTitle>
                        <CardDescription>Fill in the details for your new convention.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...store.form()}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            placeholder="Convention name"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="city">City *</Label>
                                            <Input
                                                id="city"
                                                name="city"
                                                required
                                                placeholder="City"
                                                autoComplete="off"
                                            />
                                            <InputError message={errors.city} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="country">Country *</Label>
                                            <Input
                                                id="country"
                                                name="country"
                                                required
                                                placeholder="Country"
                                                autoComplete="off"
                                            />
                                            <InputError message={errors.country} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address">Address</Label>
                                        <Input
                                            id="address"
                                            name="address"
                                            placeholder="Venue address (optional)"
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.address} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_date">Start Date *</Label>
                                            <Input
                                                id="start_date"
                                                name="start_date"
                                                type="date"
                                                required
                                            />
                                            <InputError message={errors.start_date} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="end_date">End Date *</Label>
                                            <Input
                                                id="end_date"
                                                name="end_date"
                                                type="date"
                                                required
                                            />
                                            <InputError message={errors.end_date} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="other_info">Other Information</Label>
                                        <textarea
                                            id="other_info"
                                            name="other_info"
                                            rows={3}
                                            placeholder="Additional information (optional)"
                                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        />
                                        <InputError message={errors.other_info} />
                                    </div>

                                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                                        <Button variant="outline" className="w-full sm:w-auto" asChild>
                                            <Link href={index.url()}>Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                            {processing ? 'Creating...' : 'Create Convention'}
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
