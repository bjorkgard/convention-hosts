import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    CalendarDays,
    ChevronDown,
    LayoutGrid,
    Search,
    Shield,
    Smartphone,
    Users,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { index as conventionsIndex } from '@/actions/App/Http/Controllers/ConventionController';
import { store as guestStore } from '@/actions/App/Http/Controllers/GuestConventionController';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UpdateNotificationModal } from '@/components/update-notification-modal';
import { VersionBadge } from '@/components/version-badge';
import { localeLabel } from '@/lib/locale-labels';
import { login } from '@/routes';

const featureIcons = [LayoutGrid, BarChart3, Users, CalendarDays, Search, Smartphone];
const featureKeys = ['venue', 'occupancy', 'roles', 'attendance', 'search', 'mobile'];

export default function Welcome() {
    const { auth } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const formRef = useRef<HTMLDivElement>(null);
    const { t } = useTranslation();
    const [locales, setLocales] = useState<string[]>(['sv', 'en']);

    const features = featureKeys.map((key, i) => ({
        icon: featureIcons[i],
        title: t(`public.welcome.features.${key}_title`),
        description: t(`public.welcome.features.${key}_description`),
    }));

    const steps = [1, 2, 3, 4].map((n) => ({
        number: String(n),
        title: t(`public.welcome.steps.step${n}_title`),
        description: t(`public.welcome.steps.step${n}_description`),
    }));

    useEffect(() => {
        if (showForm && formRef.current) {
            formRef.current.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    }, [showForm]);

    useEffect(() => {
        fetch('/api/locales')
            .then((res) => res.json())
            .then((data: string[]) => {
                if (data.length > 0) setLocales(data);
            })
            .catch(() => {});
    }, []);

    return (
        <>
            <Head title={t('public.welcome.title')}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap"
                    rel="stylesheet"
                />
            </Head>
            <div
                className="min-h-screen bg-background text-foreground"
                style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
            >
                {/* Navbar */}
                <nav className="sticky top-0 z-50 border-b border-border bg-background/80 backdrop-blur-md">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                        <Link
                            href="/"
                            className="flex cursor-pointer items-center gap-2"
                        >
                            <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5" />
                            </div>
                            <span className="text-lg font-semibold">
                                {import.meta.env.VITE_APP_NAME}
                            </span>
                        </Link>
                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={conventionsIndex.url()}>
                                        {t('public.welcome.nav.my_conventions')}
                                        <ArrowRight className="ml-1 size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>{t('public.welcome.nav.login')}</Link>
                                    </Button>
                                    <Button
                                        onClick={() => setShowForm(true)}
                                        className="cursor-pointer"
                                    >
                                        {t('public.welcome.nav.get_started')}
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero */}
                <section className="relative overflow-hidden px-4 pt-20 pb-24 sm:px-6 lg:pt-32 lg:pb-36">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-primary)/5%,transparent_70%)]" />
                    <div className="relative mx-auto max-w-6xl text-center">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-secondary px-4 py-1.5 text-sm text-muted-foreground">
                            <Shield className="size-4" />
                            {t('public.welcome.hero.badge')}
                        </div>
                        <h1 className="mx-auto max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                            {t('public.welcome.hero.heading')}{' '}
                            <span className="text-primary">
                                {t('public.welcome.hero.heading_highlight')}
                            </span>
                        </h1>
                        <p className="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                            {t('public.welcome.hero.description')}
                        </p>
                        <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                            {auth.user ? (
                                <Button size="lg" asChild>
                                    <Link href={conventionsIndex.url()}>
                                        {t('public.welcome.hero.go_to_conventions')}
                                        <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        size="lg"
                                        onClick={() => setShowForm(true)}
                                        className="cursor-pointer"
                                    >
                                        {t('public.welcome.hero.create_convention')}
                                        <ArrowRight className="ml-2 size-4" />
                                    </Button>
                                    <Button size="lg" variant="outline" asChild>
                                        <a href="#features">
                                            {t('public.welcome.hero.see_how')}
                                            <ChevronDown className="ml-2 size-4" />
                                        </a>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section
                    id="features"
                    className="border-t border-border bg-secondary/50 px-4 py-20 sm:px-6 lg:py-28"
                >
                    <div className="mx-auto max-w-6xl">
                        <div className="mb-14 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                {t('public.welcome.features.heading')}
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                {t('public.welcome.features.description')}
                            </p>
                        </div>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature) => (
                                <div
                                    key={feature.title}
                                    className="group cursor-pointer rounded-xl border border-border bg-card p-6 transition-colors duration-200 hover:border-primary/30 hover:bg-accent"
                                >
                                    <div className="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-primary-foreground">
                                        <feature.icon className="size-5" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {feature.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How It Works */}
                <section className="px-4 py-20 sm:px-6 lg:py-28">
                    <div className="mx-auto max-w-6xl">
                        <div className="mb-14 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                {t('public.welcome.steps.heading')}
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                {t('public.welcome.steps.description')}
                            </p>
                        </div>
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            {steps.map((step, i) => (
                                <div
                                    key={step.number}
                                    className="relative text-center"
                                >
                                    {i < steps.length - 1 && (
                                        <div className="absolute top-5 left-[calc(50%+24px)] hidden h-px w-[calc(100%-48px)] bg-border lg:block" />
                                    )}
                                    <div className="mx-auto mb-4 flex size-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                        {step.number}
                                    </div>
                                    <h3 className="mb-2 font-semibold">
                                        {step.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {step.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Guest Convention Form */}
                {!auth.user && (
                    <section
                        ref={formRef}
                        id="create"
                        className="border-t border-border bg-secondary/50 px-4 py-20 sm:px-6 lg:py-28"
                    >
                        <div className="mx-auto max-w-2xl">
                            <div className="mb-10 text-center">
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    {t('public.welcome.form.heading')}
                                </h2>
                                <p className="mt-4 text-muted-foreground">
                                    {t('public.welcome.form.description')}
                                </p>
                            </div>
                            <div className="rounded-xl border border-border bg-card p-6 shadow-sm sm:p-8">
                                <Form
                                    {...guestStore.form()}
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <fieldset className="space-y-4">
                                                <legend className="mb-2 text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                                    {t('public.welcome.form.your_details')}
                                                </legend>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="first_name">
                                                            {t('public.welcome.form.first_name_label')}
                                                        </Label>
                                                        <Input
                                                            id="first_name"
                                                            name="first_name"
                                                            required
                                                            autoComplete="given-name"
                                                            placeholder={t('public.welcome.form.first_name_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.first_name
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="last_name">
                                                            {t('public.welcome.form.last_name_label')}
                                                        </Label>
                                                        <Input
                                                            id="last_name"
                                                            name="last_name"
                                                            required
                                                            autoComplete="family-name"
                                                            placeholder={t('public.welcome.form.last_name_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.last_name
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="email">
                                                            {t('public.welcome.form.email_label')}
                                                        </Label>
                                                        <Input
                                                            id="email"
                                                            name="email"
                                                            type="email"
                                                            required
                                                            autoComplete="email"
                                                            placeholder={t('public.welcome.form.email_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.email
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="mobile">
                                                            {t('public.welcome.form.mobile_label')}
                                                        </Label>
                                                        <Input
                                                            id="mobile"
                                                            name="mobile"
                                                            type="tel"
                                                            required
                                                            autoComplete="tel"
                                                            placeholder={t('public.welcome.form.mobile_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.mobile
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <fieldset className="space-y-4">
                                                <legend className="mb-2 text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                                    {t('public.welcome.form.convention_details')}
                                                </legend>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="name">
                                                        {t('public.welcome.form.name_label')}
                                                    </Label>
                                                    <Input
                                                        id="name"
                                                        name="name"
                                                        required
                                                        autoComplete="off"
                                                        placeholder={t('public.welcome.form.name_placeholder')}
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="city">
                                                            {t('public.welcome.form.city_label')}
                                                        </Label>
                                                        <Input
                                                            id="city"
                                                            name="city"
                                                            required
                                                            autoComplete="off"
                                                            placeholder={t('public.welcome.form.city_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.city
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="country">
                                                            {t('public.welcome.form.country_label')}
                                                        </Label>
                                                        <Input
                                                            id="country"
                                                            name="country"
                                                            required
                                                            autoComplete="off"
                                                            placeholder={t('public.welcome.form.country_placeholder')}
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.country
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="address">
                                                        {t('public.welcome.form.address_label')}
                                                    </Label>
                                                    <Input
                                                        id="address"
                                                        name="address"
                                                        autoComplete="off"
                                                        placeholder={t('public.welcome.form.address_placeholder')}
                                                    />
                                                    <InputError
                                                        message={errors.address}
                                                    />
                                                </div>
                                                <div className="grid gap-4 sm:grid-cols-2">
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="start_date">
                                                            {t('public.welcome.form.start_date_label')}
                                                        </Label>
                                                        <Input
                                                            id="start_date"
                                                            name="start_date"
                                                            type="date"
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.start_date
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="end_date">
                                                            {t('public.welcome.form.end_date_label')}
                                                        </Label>
                                                        <Input
                                                            id="end_date"
                                                            name="end_date"
                                                            type="date"
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.end_date
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="other_info">
                                                        {t('public.welcome.form.other_info_label')}
                                                    </Label>
                                                    <textarea
                                                        id="other_info"
                                                        name="other_info"
                                                        rows={3}
                                                        placeholder={t('public.welcome.form.other_info_placeholder')}
                                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.other_info
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="locale">
                                                        {t('public.welcome.form.language_label')}
                                                    </Label>
                                                    <select
                                                        id="locale"
                                                        name="locale"
                                                        defaultValue="sv"
                                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                    >
                                                        {locales.map((code) => (
                                                            <option key={code} value={code}>
                                                                {localeLabel(code)}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors.locale
                                                        }
                                                    />
                                                </div>
                                            </fieldset>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="w-full cursor-pointer"
                                                size="lg"
                                            >
                                                {processing
                                                    ? t('public.welcome.form.submitting')
                                                    : t('public.welcome.form.submit')}
                                                {!processing && (
                                                    <ArrowRight className="ml-2 size-4" />
                                                )}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </div>
                        </div>
                    </section>
                )}

                {/* CTA */}
                {!auth.user && (
                    <section className="px-4 py-20 sm:px-6 lg:py-28">
                        <div className="mx-auto max-w-3xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                {t('public.welcome.cta.heading')}
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                {t('public.welcome.cta.description')}
                            </p>
                            <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Button
                                    size="lg"
                                    onClick={() => setShowForm(true)}
                                    className="cursor-pointer"
                                >
                                    {t('public.welcome.cta.button')}
                                    <ArrowRight className="ml-2 size-4" />
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <Link href={login()}>
                                        {t('public.welcome.cta.login')}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </section>
                )}

                {/* Footer */}
                <footer className="border-t border-border px-4 py-8 sm:px-6">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <AppLogoIcon className="size-4" />
                            <span>{import.meta.env.VITE_APP_NAME}</span>
                            <VersionBadge />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {t('public.welcome.footer.copyright', { year: new Date().getFullYear(), app: import.meta.env.VITE_APP_NAME })}
                        </p>
                    </div>
                </footer>

                <UpdateNotificationModal />
            </div>
        </>
    );
}
