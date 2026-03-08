<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
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

        <!-- ── iOS / iPadOS (Apple) ── -->
        <!-- Prevents Safari from adding the URL bar chrome when saved to Home Screen -->
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <!-- Status bar appearance: default | black | black-translucent -->
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="ConvHosts" />

        <!-- Apple Touch Icons — NO "precomposed" suffix (let iOS apply gloss) -->
        <!-- iOS uses the largest icon ≤ the device's required size -->
        <link rel="apple-touch-icon" sizes="57x57"   href="/icons/apple-touch-icon-57x57.png" />
        <link rel="apple-touch-icon" sizes="60x60"   href="/icons/apple-touch-icon-60x60.png" />
        <link rel="apple-touch-icon" sizes="72x72"   href="/icons/apple-touch-icon-72x72.png" />
        <link rel="apple-touch-icon" sizes="76x76"   href="/icons/apple-touch-icon-76x76.png" />
        <link rel="apple-touch-icon" sizes="114x114" href="/icons/apple-touch-icon-114x114.png" />
        <link rel="apple-touch-icon" sizes="120x120" href="/icons/apple-touch-icon-120x120.png" />
        <link rel="apple-touch-icon" sizes="144x144" href="/icons/apple-touch-icon-144x144.png" />
        <link rel="apple-touch-icon" sizes="152x152" href="/icons/apple-touch-icon-152x152.png" />
        <link rel="apple-touch-icon" sizes="167x167" href="/icons/apple-touch-icon-167x167.png" />
        <!-- 180×180 is the "primary" modern iOS size (iPhone 6+, all current iPads) -->
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
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.json">

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
