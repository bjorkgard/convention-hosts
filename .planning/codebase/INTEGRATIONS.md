# External Integrations

## Overview

This document lists external systems, browser platform integrations, infrastructure backends, and third-party libraries that are actually wired into the repository. It distinguishes between actively used integrations and optional providers that are configured as alternatives.

Key integration surfaces:

- Service credentials: `.env.example`, `config/services.php`
- Mail transport: `config/mail.php`
- Storage, cache, queue, DB: `config/filesystems.php`, `config/cache.php`, `config/queue.php`, `config/database.php`
- Browser/PWA integration: `public/sw.js`, `public/manifest.json`, `resources/views/app.blade.php`

## External HTTP APIs

### GitHub Releases API

The app checks GitHub releases to determine whether a newer application version exists.

- Endpoint usage: `app/Http/Controllers/VersionController.php`
- Public app endpoint: `routes/web.php` defines `/api/version/latest`
- Current repo source: `config/app.php` and `.env.example` via `GITHUB_REPO`
- Caching: release payload cached for 300 seconds in `app/Http/Controllers/VersionController.php`
- Frontend consumer: `resources/js/hooks/use-app-version.ts`

This is an outbound integration to `https://api.github.com/repos/{owner}/{repo}/releases/latest`.

## Email Delivery

### Mailgun

Mailgun is the intended production email transport.

- Default mailer in `.env.example`: `MAIL_MAILER=mailgun`
- Mailgun transport configured in `config/mail.php`
- Mailgun credentials configured in `config/services.php`
- Symfony Mailgun transport installed in `composer.json`

### Application Email Flows

Mail is sent synchronously through Laravel Mail in these code paths:

- User invitation email: `app/Actions/InviteUserAction.php` with `app/Mail/UserInvitation.php`
- Guest convention verification email: `app/Http/Controllers/GuestConventionController.php` with `app/Mail/GuestConventionVerification.php`
- Email change confirmation email: `app/Observers/UserObserver.php` with `app/Mail/EmailConfirmation.php`

### Alternate Mail Providers

The repo includes config stubs for alternate mail providers, but they are not used by default:

- SMTP in `config/mail.php`
- Amazon SES in `config/mail.php` and `config/services.php`
- Postmark in `config/mail.php` and `config/services.php`
- Resend in `config/mail.php` and `config/services.php`
- Log/array/failover/roundrobin drivers in `config/mail.php`

## Authentication And Identity Integrations

### Laravel Fortify

Authentication is implemented with Laravel Fortify.

- Package dependency: `composer.json`
- Config: `config/fortify.php`, `config/auth.php`
- Provider wiring: `app/Providers/FortifyServiceProvider.php`
- Two-factor auth model integration: `app/Models/User.php`

### Session-Based Web Auth

- Guard: `web` in `config/auth.php`
- Session driver defaults to database in `config/session.php` and `.env.example`
- Remember-token support exists via the Laravel user model in `app/Models/User.php`

### Signed URL Flows

The app uses Laravel signed URLs for invitation and verification flows:

- Invitation URLs: `app/Actions/InviteUserAction.php`, `app/Http/Controllers/UserController.php`
- Guest verification URLs: `app/Http/Controllers/GuestConventionController.php`
- Email confirmation URLs: `app/Observers/UserObserver.php`
- Invalid signature handling: `bootstrap/app.php`

## Database Backends

### Active Default

- SQLite is the default runtime database in `config/database.php` and `.env.example`

### Supported Alternatives

Configured alternatives exist for:

- MySQL in `config/database.php`
- MariaDB in `config/database.php`
- PostgreSQL in `config/database.php`
- SQL Server in `config/database.php`

These are infrastructure integrations rather than active third-party SaaS dependencies.

## Cache, Queue, And Session Backends

### Current Defaults

- Cache defaults to the `database` store in `config/cache.php` and `.env.example`
- Queue defaults to the `database` connection in `config/queue.php` and `.env.example`
- Session defaults to the `database` driver in `config/session.php` and `.env.example`

### Optional Alternatives

Configured but not default:

- Redis in `config/cache.php`, `config/queue.php`, `config/database.php`
- Memcached in `config/cache.php`
- DynamoDB cache in `config/cache.php`
- Beanstalkd queue in `config/queue.php`
- Amazon SQS queue in `config/queue.php`

### Background Processing

- Local queue worker is expected via `composer dev` in `composer.json`
- Daily scheduled tasks are registered in `routes/console.php`

## Storage Integrations

### Local Filesystem Storage

The app actively stores exported files on the local Laravel disk.

- Default disk: `local` in `config/filesystems.php`
- Export path usage: `app/Actions/ExportConventionAction.php`
- Local disk root: `storage/app/private` via `config/filesystems.php`

### Public Storage

- Public disk configured in `config/filesystems.php`
- Public symlink target configured in `config/filesystems.php`

### S3-Compatible Storage

An S3 disk is configured as an available alternative, but no current application code path was found using it directly.

- S3 disk config: `config/filesystems.php`
- AWS env vars: `.env.example`

## Browser And PWA Integrations

### Service Worker

The app registers a service worker in the browser.

- Registration: `resources/views/app.blade.php`
- Implementation: `public/sw.js`

The service worker provides:

- App-shell precaching
- Cache-first handling for icons, manifest, and favicons
- Network-first handling for dynamic pages and API requests

### Web App Manifest

- Manifest file: `public/manifest.json`
- Manifest linked from: `resources/views/app.blade.php`
- Includes standalone display mode, icons, theme colors, and install metadata

### Install Prompt / App Installation

The frontend integrates with browser PWA install events:

- `beforeinstallprompt` and `appinstalled` handling in `resources/js/components/install-prompt.tsx`
- iOS Safari fallback instructions in `resources/js/components/install-prompt.tsx`
- Standalone-mode detection in `resources/js/components/install-prompt.tsx`

### Cache Storage And Service Worker Reset

When an update is available, the frontend can unregister service workers and clear Cache Storage before reloading:

- Update hook: `resources/js/hooks/use-app-version.ts`

## Font And Static Asset Integrations

### Bunny Fonts

The app loads hosted fonts from Bunny Fonts.

- Base shell include: `resources/views/app.blade.php`
- Auth layouts also preconnect/load Bunny Fonts in `resources/js/layouts/auth`

This is an external asset-hosting integration rather than a local font bundle.

## Export Libraries

These are third-party libraries integrated into the application’s export feature:

- Laravel Excel / maatwebsite: `app/Actions/ExportConventionAction.php`, `app/Exports/ConventionExport.php`
- PhpSpreadsheet: installed in `composer.json` for spreadsheet support
- PhpWord: `app/Exports/ConventionWordExport.php`

Exports generated by the app:

- `.xlsx`
- `.docx`
- `.md`

## Frontend Component And Utility Libraries

These are integrated into the shipped frontend, not just installed:

- Radix UI primitives used under `resources/js/components/ui`
- Headless UI via `@headlessui/react`
- Lucide React icons across `resources/js/components`
- Sonner toast library in `resources/js/components/ui/sonner.tsx`
- `input-otp` used by `resources/js/components/ui/input-otp.tsx`

## Security And Platform Headers

### CSP / Browser Security Headers

The app actively sets browser security headers through middleware:

- Middleware: `app/Http/Middleware/SecureHeaders.php`
- Registered in web middleware stack: `bootstrap/app.php`

Headers include:

- Content Security Policy
- HSTS
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy

Local development explicitly allows Vite dev server connections at `http://localhost:5173` and websocket equivalents in `app/Http/Middleware/SecureHeaders.php`.

## Logging And Monitoring-Like Integrations

### Laravel Pail

Laravel Pail is included and used in the local dev workflow for log streaming.

- Dev dependency: `composer.json`
- Invoked by `composer dev` and `composer dev:ssr`

### Security Event Logging

Security-related events are logged internally through Laravel logging channels:

- Listener: `app/Listeners/SecurityEventListener.php`
- Registration: `app/Providers/AppServiceProvider.php`

This is internal application logging, not an external observability SaaS integration.

## Explicitly Configured But Not Actively Wired To A Feature

These providers/backends are present in configuration, but the repository scan did not find an active application flow depending on them today:

- Slack notification credentials in `config/services.php`
- S3 object storage in `config/filesystems.php`
- SES/Postmark/Resend transports in `config/mail.php`
- SQS, Redis, Beanstalkd queue backends in `config/queue.php`
- DynamoDB cache backend in `config/cache.php`
