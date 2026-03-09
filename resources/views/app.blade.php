<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark']) data-theme="{{ $theme ?? 'default' }}">
    <head>
        <!-- ── Primary Charset & Viewport ── -->
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <!-- ── Theme Colors ── -->
        <!-- Android Chrome / browser chrome color -->
        <meta name="theme-color" content="#1a1a2e" />
        <!-- Light/dark variants (Chrome 93+) -->
        <meta name="theme-color" media="(prefers-color-scheme: light)" content="#0f3460" />
        <meta name="theme-color" media="(prefers-color-scheme: dark)"  content="#1a1a2e" />

        <!-- ── PWA Web App Manifest ── -->
        <link rel="manifest" href="/manifest.webmanifest" />

        <!-- ── Favicons ── -->
        <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="48x48" href="/icons/favicon-48x48.png" />

        <!-- Preload the app-logo image used in every page navbar (improves LCP) -->
        <link rel="preload" as="image" href="/icons/favicon-32x32.png" />

        <!-- ── iOS / iPadOS (Apple) ── -->
        <!-- Prevents Safari from adding the URL bar chrome when saved to Home Screen -->
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <!-- Status bar appearance: default | black | black-translucent -->
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="ConvHosts" />

        <!-- Apple Touch Icon — 180×180 covers all modern iOS devices (iPhone 6+, all iPads) -->
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180x180.png" />

        <!-- ── Microsoft Tiles (legacy Windows / Edge) ── -->
        <meta name="msapplication-TileColor"   content="#1a1a2e" />
        <meta name="msapplication-TileImage"   content="/icons/icon-144x144.png" />
        <meta name="msapplication-square70x70logo"   content="/icons/icon-72x72.png" />
        <meta name="msapplication-square150x150logo" content="/icons/icon-152x152.png" />
        <meta name="msapplication-square310x310logo" content="/icons/icon-384x384.png" />

        <!-- ── Open Graph (social sharing) ── -->
        <meta property="og:type"        content="website" />
        <meta property="og:title"       content="Convention Hosts" />
        <meta property="og:description" content="Manage and host conventions with ease" />
        <meta property="og:image"       content="/icons/icon-512x512.png" />
        <meta property="og:url"         content="https://convention-hosts-main-en3g50.laravel.cloud/" />

        <!-- ── Twitter Card ── -->
        <meta name="twitter:card"        content="summary" />
        <meta name="twitter:title"       content="Convention Hosts" />
        <meta name="twitter:description" content="Manage and host conventions with ease" />
        <meta name="twitter:image"       content="/icons/icon-512x512.png" />

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                // Restore theme from localStorage in case cookie is not yet set
                // (first visit before the server sees the cookie)
                const storedTheme = localStorage.getItem('theme');
                if (storedTheme && storedTheme !== 'default') {
                    document.documentElement.setAttribute('data-theme', storedTheme);
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }

            html[data-theme="apple"] {
                background-color: oklch(0.965 0.002 264);
            }

            html.dark[data-theme="apple"] {
                background-color: oklch(0 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    </body>
</html>
