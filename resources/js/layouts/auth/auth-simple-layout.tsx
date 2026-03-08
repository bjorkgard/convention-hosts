import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
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
                className="relative flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10"
                style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
            >
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-primary)/5%,transparent_70%)]" />

                <div className="relative w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4">
                            <Link
                                href={home()}
                                className="flex cursor-pointer items-center gap-2 font-medium"
                            >
                                <div className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                    <AppLogoIcon className="size-5" />
                                </div>
                                <span className="text-lg font-semibold">
                                    {import.meta.env.VITE_APP_NAME}
                                </span>
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-xl font-medium">
                                    {title}
                                </h1>
                                <p className="text-center text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
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
