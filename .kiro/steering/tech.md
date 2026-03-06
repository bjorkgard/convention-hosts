# Technology Stack

## Backend

- PHP 8.2+
- Laravel 12.0
- Laravel Fortify (authentication)
- Laravel Wayfinder (type-safe routing)
- Inertia.js Laravel adapter
- SQLite database (development)
- Pest PHP (testing framework)

## Frontend

- React 19
- TypeScript
- Inertia.js React adapter
- Vite (build tool)
- Tailwind CSS 4
- Radix UI components
- Headless UI
- Lucide React (icons)
- Vitest (frontend testing framework)
- fast-check (property-based testing)

## Code Quality Tools

- Laravel Pint (PHP code style, Laravel preset)
- ESLint (JavaScript/TypeScript linting)
- Prettier (code formatting)
- TypeScript compiler (type checking)

## Common Commands

### Setup
```bash
composer setup              # Full project setup (install deps, env, key, migrate, build)
```

### Development
```bash
composer dev                # Start all dev services (server, queue, logs, vite)
composer dev:ssr            # Start with SSR support
npm run dev                 # Vite dev server only
php artisan serve           # Laravel dev server only
```

### Testing
```bash
composer test               # Run lint:check + PHP tests with Pest
php artisan test            # Run PHP tests only (no lint)
npm run test                # Run frontend tests with Vitest (single run)
```

### Linting & Formatting
```bash
composer lint               # Fix PHP code style with Pint
composer lint:check         # Check PHP code style
npm run lint                # Fix JS/TS with ESLint
npm run lint:check          # Check JS/TS with ESLint
npm run format              # Format with Prettier
npm run format:check        # Check formatting
npm run types:check         # TypeScript type checking
```

### Building
```bash
npm run build               # Build frontend assets
npm run build:ssr           # Build with SSR support
```

### CI
```bash
composer ci:check           # Run all checks (lint, format, types, tests)
```

## Key Dependencies

- `@inertiajs/react` - Inertia.js React adapter
- `@laravel/vite-plugin-wayfinder` - Type-safe routing
- `laravel-vite-plugin` - Laravel Vite integration
- `class-variance-authority` - Component variant utilities
- `clsx` / `tailwind-merge` - Tailwind class utilities
- `maatwebsite/excel` - Excel export
- `phpoffice/phpword` - Word document export
- `symfony/mailgun-mailer` - Mailgun email integration
