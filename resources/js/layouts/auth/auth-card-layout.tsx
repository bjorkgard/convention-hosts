import { Head, Link } from '@inertiajs/react';

import type { PropsWithChildren } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

export default function AuthCardLayout({
    children,
    title,
    description,
}: PropsWithChildren<{
    name?: string;
    title?: string;
    description?: string;
}>) {
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
                className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
                style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
            >
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,var(--color-primary)/5%,transparent_70%)]" />

                <div className="relative flex w-full max-w-md flex-col gap-6">
                    <Link
                        href={home()}
                        className="flex cursor-pointer items-center gap-2 self-center font-medium"
                    >
                        <div className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <img src="/icons/favicon-32x32.png" className="size-5" alt="" />
                        </div>
                        <span className="text-lg font-semibold">
                            {import.meta.env.VITE_APP_NAME}
                        </span>
                    </Link>

                    <div className="flex flex-col gap-6">
                        <Card className="rounded-xl">
                            <CardHeader className="px-10 pt-8 pb-0 text-center">
                                <CardTitle className="text-xl">
                                    {title}
                                </CardTitle>
                                <CardDescription>
                                    {description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="px-10 py-8">
                                {children}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
