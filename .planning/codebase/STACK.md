# Technology Stack

## Overview

Convention Hosts is a full-stack Laravel + Inertia + React application. The backend is PHP/Laravel, the frontend is React 19 with TypeScript, and the browser bundle is built with Vite. Server-rendered Inertia SSR is enabled, and the app also ships as a PWA.

Primary stack entrypoints:

- Backend bootstrap: `bootstrap/app.php`, `bootstrap/providers.php`
- Backend dependencies: `composer.json`
- Frontend dependencies: `package.json`
- Frontend entrypoints: `resources/js/app.tsx`, `resources/js/ssr.tsx`
- Root Blade shell: `resources/views/app.blade.php`

## Languages And Runtime

### Backend

- PHP `^8.2` in `composer.json`
- Laravel Framework `^12.0` in `composer.json`
- Eloquent ORM models under `app/Models`
- Artisan console entrypoint in `artisan`

### Frontend

- TypeScript `^5.7.2` in `package.json`
- React `^19.2.0` and React DOM `^19.2.0` in `package.json`
- ES modules via `"type": "module"` in `package.json`
- JSX runtime configured in `tsconfig.json` and `vite.config.ts`

## Backend Framework And Architecture

### Laravel Application Layer

- App bootstraps through `bootstrap/app.php`
- Providers registered in `bootstrap/providers.php`
- Core app provider logic in `app/Providers/AppServiceProvider.php`
- Auth and Fortify customization in `app/Providers/FortifyServiceProvider.php`
- Routes defined in `routes/web.php`, `routes/settings.php`, and scheduled commands in `routes/console.php`

### Backend Patterns In Use

- Thin controllers under `app/Http/Controllers`
- Business logic in action classes under `app/Actions`
- Form requests under `app/Http/Requests`
- Policy-based authorization in `app/Policies`
- Middleware-based convention scoping in `app/Http/Middleware`
- Eloquent observers in `app/Observers/UserObserver.php`
- Service classes in `app/Services/AttendanceReportService.php`

## Frontend Framework And Application Structure

### Inertia + React

- Inertia Laravel adapter: `inertiajs/inertia-laravel` in `composer.json`
- Inertia React client: `@inertiajs/react` in `package.json`
- Client app creation in `resources/js/app.tsx`
- SSR app creation in `resources/js/ssr.tsx`
- Inertia root view configured in `app/Http/Middleware/HandleInertiaRequests.php`
- Inertia SSR enabled in `config/inertia.php`

### React UI Layer

- Page components under `resources/js/pages`
- Layout components under `resources/js/layouts`
- Shared components under `resources/js/components`
- Hooks under `resources/js/hooks`
- Local TS types under `resources/js/types`

## Styling And UI Tooling

### CSS Stack

- Tailwind CSS v4 via `tailwindcss` and `@tailwindcss/vite` in `package.json`
- Tailwind imported directly in `resources/css/app.css`
- Tailwind animation helpers via `tw-animate-css` in `package.json`
- Utility class merging via `clsx`, `class-variance-authority`, and `tailwind-merge` in `package.json`

### Component Libraries

- Radix UI primitives in `package.json` and `resources/js/components/ui`
- Headless UI via `@headlessui/react` in `package.json`
- Lucide icon set via `lucide-react` in `package.json`
- shadcn/ui project metadata in `components.json`

### Typography And Themes

- Bunny Fonts loaded from `https://fonts.bunny.net` in `resources/views/app.blade.php` and auth layouts in `resources/js/layouts/auth`
- Global theme tokens and theme variants in `resources/css/app.css`
- Appearance/theme initialization in `resources/js/hooks/use-appearance.tsx` and `resources/js/hooks/use-theme.tsx`

## Routing And Type-Safe URL Generation

- Laravel Wayfinder backend package in `composer.json`
- Vite Wayfinder plugin in `package.json`
- Generated TS actions/routes under `resources/js/actions` and `resources/js/routes`
- Wayfinder Vite plugin enabled in `vite.config.ts`
- Regeneration command documented in `composer.json` scripts and repository instructions

## Database And Persistence Stack

### Database Engines

- Default connection is SQLite in `config/database.php` and `.env.example`
- MySQL, MariaDB, PostgreSQL, and SQL Server are configured as supported alternatives in `config/database.php`

### Application Persistence

- Primary domain schema lives in `database/migrations`
- Sessions default to database storage in `config/session.php` and `.env.example`
- Cache defaults to database storage in `config/cache.php` and `.env.example`
- Queues default to database storage in `config/queue.php` and `.env.example`

## Auth And Security Stack

### Authentication

- Laravel Fortify `^1.30` in `composer.json`
- Session guard configured in `config/auth.php`
- Fortify features configured in `config/fortify.php`
- Two-factor support uses `Laravel\Fortify\TwoFactorAuthenticatable` in `app/Models/User.php`

### Security Controls

- CSP, HSTS, frame, referrer, and MIME-sniffing headers in `app/Http/Middleware/SecureHeaders.php`
- CSP nonce enabled with `Vite::useCspNonce()` in `app/Providers/AppServiceProvider.php`
- Login and 2FA rate limiting in `app/Providers/FortifyServiceProvider.php`
- Destructive DB command protection in production in `app/Providers/AppServiceProvider.php`
- Security event logging in `app/Listeners/SecurityEventListener.php`

## Build And Development Tooling

### PHP Tooling

- Composer scripts in `composer.json`
- Local dev orchestration via `composer dev`
- Laravel Pail for log tailing in `composer.json`
- Laravel Tinker in `composer.json`
- Laravel Sail is installed as a dev dependency in `composer.json`, but no repo-local Docker compose files were found

### Frontend Tooling

- Vite `^7.0.4` in `package.json`
- React plugin for Vite in `vite.config.ts`
- React Compiler Babel plugin in `package.json` and `vite.config.ts`
- Manual vendor chunking configured in `vite.config.ts`

## Testing, Linting, And Formatting

### Backend Testing

- Pest 4 with Laravel plugins in `composer.json`
- PHPUnit config in `phpunit.xml`
- Backend tests organized under `tests/Unit`, `tests/Feature`, and `tests/Property`

### Frontend Testing

- Vitest config in `vitest.config.ts`
- JSDOM test environment in `vitest.config.ts`
- React Testing Library packages in `package.json`
- Test bootstrap in `resources/js/test/setup.ts`

### Quality Tooling

- Laravel Pint in `composer.json`
- ESLint flat config in `eslint.config.js`
- Prettier config in `.prettierrc`
- TypeScript strict mode in `tsconfig.json`

## Scheduled And Background Tooling

- Queue worker expected in local dev via `composer dev` using `php artisan queue:listen`
- Scheduled commands registered in `routes/console.php`
- Custom scheduled commands implemented in `app/Console/Commands/ResetDailyOccupancy.php` and `app/Console/Commands/CleanupUnconfirmedGuestConventions.php`

## Export And Document Tooling

- Excel export via `maatwebsite/excel` and `phpoffice/phpspreadsheet` in `composer.json`
- Word export via `phpoffice/phpword` in `composer.json`
- Export orchestration in `app/Actions/ExportConventionAction.php`
- Export classes in `app/Exports`

## Notable Configuration Files

- Environment defaults: `.env.example`
- App config: `config/app.php`
- Auth config: `config/auth.php`
- Fortify config: `config/fortify.php`
- Inertia SSR config: `config/inertia.php`
- Database config: `config/database.php`
- Cache config: `config/cache.php`
- Queue config: `config/queue.php`
- Filesystem config: `config/filesystems.php`
- Mail/services config: `config/mail.php`, `config/services.php`
