import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <>
            <Head>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap"
                    rel="stylesheet"
                />
            </Head>
            <div
                className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
                style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
            >
                <div className="relative hidden h-full flex-col bg-primary p-10 text-primary-foreground lg:flex">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-primary-foreground)/10%,transparent_70%)]" />
                    <Link
                        href={home()}
                        className="relative z-20 flex cursor-pointer items-center gap-2 text-lg font-semibold"
                    >
                        <div className="flex size-8 items-center justify-center rounded-md bg-primary-foreground/20">
                            <AppLogoIcon className="size-5" />
                        </div>
                        {name}
                    </Link>
                </div>
                <div className="relative w-full lg:p-8">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-primary)/5%,transparent_70%)] lg:hidden" />
                    <div className="relative mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                        <Link
                            href={home()}
                            className="relative z-20 flex cursor-pointer items-center justify-center gap-2 lg:hidden"
                        >
                            <div className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5" />
                            </div>
                            <span className="text-lg font-semibold">
                                {name}
                            </span>
                        </Link>
                        <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        </div>
                        <div className="rounded-xl border border-border bg-card p-6 shadow-sm">
                            {children}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
