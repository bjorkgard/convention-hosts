import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { localeLabel } from '@/lib/locale-labels';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

const LOCALE_OPTIONS: { value: string; label: string }[] = [
    { value: 'sv', label: 'Svenska' },
    { value: 'en', label: 'English' },
];

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t, i18n } = useTranslation();
    const { auth } = usePage().props;
    const [locales, setLocales] = useState(LOCALE_OPTIONS);
    const [selectedLocale, setSelectedLocale] = useState(
        auth.user.locale ?? 'sv',
    );
    const selectRef = useRef<HTMLSelectElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('settings.profile.breadcrumb'), href: edit() },
    ];

    useEffect(() => {
        fetch('/api/locales')
            .then((res) => res.json())
            .then((codes: string[]) => {
                const mapped = codes.map((c) => {
                    const existing = LOCALE_OPTIONS.find((o) => o.value === c);
                    return existing ?? { value: c, label: localeLabel(c) };
                });
                setLocales(mapped);
            })
            .catch(() => {});
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.profile.title')} />

            <h1 className="sr-only">{t('settings.profile.title')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.profile.heading')}
                        description={t('settings.profile.description')}
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">
                                        {t('settings.profile.first_name_label')}
                                    </Label>

                                    <Input
                                        id="first_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.first_name}
                                        name="first_name"
                                        required
                                        autoComplete="given-name"
                                        placeholder={t(
                                            'settings.profile.first_name_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.first_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">
                                        {t('settings.profile.last_name_label')}
                                    </Label>

                                    <Input
                                        id="last_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.last_name}
                                        name="last_name"
                                        required
                                        autoComplete="family-name"
                                        placeholder={t(
                                            'settings.profile.last_name_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.last_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t('settings.profile.email_label')}
                                    </Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder={t(
                                            'settings.profile.email_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="locale">
                                        {t('settings.profile.language_label')}
                                    </Label>

                                    <select
                                        ref={selectRef}
                                        id="locale"
                                        name="locale"
                                        className="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        value={selectedLocale}
                                        onChange={(e) => {
                                            setSelectedLocale(e.target.value);
                                            i18n.changeLanguage(e.target.value);
                                        }}
                                    >
                                        {locales.map((opt) => (
                                            <option
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.locale}
                                    />
                                </div>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                {t(
                                                    'settings.profile.email_unverified',
                                                )}{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    {t(
                                                        'settings.profile.resend_verification',
                                                    )}
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    {t(
                                                        'settings.profile.verification_sent',
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        {t('settings.profile.save')}
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            {t('settings.profile.saved')}
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
