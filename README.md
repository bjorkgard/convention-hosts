# Convention Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive convention management system built with Laravel and React. Manage multi-day events with real-time occupancy tracking, attendance reporting, and role-based access control.

## Key Features

### Convention Management
- Multi-day convention organization with date validation and conflict detection
- Guest convention creation (no account required; existing users auto-login, new users verify email first)
- Hierarchical venue structure: Convention → Floor → Section
- Real-time section occupancy tracking with color-coded indicators (0-100%)
- Morning/afternoon attendance reporting with period locking for data integrity
- Multi-format data export (.xlsx, .docx, Markdown)

### User Management and Security
- Four-tier role-based access control (Owner, ConventionUser, FloorUser, SectionUser)
- Secure email invitations with signed URL account activation (24h expiry)
- Login with "remember me", password reset, email verification
- Two-factor authentication (TOTP) with recovery codes
- Type-safe routing via Laravel Wayfinder

### Search and Accessibility
- Find available sections with elder-friendly and handicap-friendly filters
- Progressive Web App for native-like mobile experience
- Light/dark mode theme support
- Mobile-first responsive design

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+, Laravel 12, Laravel Fortify |
| Frontend | React 19, TypeScript, Inertia.js, Tailwind CSS 4 |
| Database | SQLite (dev), MySQL/PostgreSQL (prod) |
| UI Components | Radix UI, Headless UI, Lucide React |
| Email | Mailgun |
| Export | maatwebsite/excel, phpoffice/phpword |
| Testing | Pest PHP (backend), Vitest + React Testing Library (frontend) |
| Code Quality | Laravel Pint, ESLint, Prettier |

## Quick Start

```bash
# Clone the repository
git clone <your-repo-url>
cd convention-management-system

# Install dependencies and set up the project
composer setup

# Seed demo data (optional but recommended)
php artisan db:seed

# Start development servers
composer dev
```

Visit `http://localhost:8000` to see the application.

## Configuration

### Environment Setup

The `composer setup` command copies `.env.example` to `.env` and generates an app key. Review `.env` and adjust as needed:

```env
APP_NAME="Convention Hosts"
APP_URL=http://localhost:8000
APP_TIMEZONE="Europe/Stockholm"
DB_CONNECTION=sqlite
```

### Mailgun Setup (Required for Email)

The application sends invitation and confirmation emails via Mailgun. To enable email delivery:

1. Sign up at [mailgun.com](https://www.mailgun.com) and verify a sending domain
2. Copy your domain and API key into `.env`:

```env
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAILGUN_ENDPOINT=api.mailgun.net
```

For EU regions, change the endpoint to `api.eu.mailgun.net`.

For local development without Mailgun, switch to the log driver to write emails to `storage/logs/`:

```env
MAIL_MAILER=log
```

### Database

SQLite is the default and requires no extra configuration. The database file lives at `database/database.sqlite`.

To use MySQL or PostgreSQL, update the `DB_*` variables in `.env`. See `.env.example` for commented examples.

## Demo Data

Seed the database with a full demo convention:

```bash
php artisan db:seed
```

This creates floors, sections (with accessibility features), users across all four roles, and a locked attendance period with sample reports.

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@example.com | Password1! |
| ConventionUser | manager@example.com | Password1! |
| FloorUser | floor@example.com | Password1! |
| SectionUser | section@example.com | Password1! |

To reset and re-seed:

```bash
php artisan migrate:fresh --seed
```

## Development Commands

```bash
# Start all dev services (server + queue + logs + vite)
composer dev

# Run PHP tests (Pest)
composer test

# Run frontend tests (Vitest)
npm test

# Lint and format
composer lint               # Fix PHP style (Pint)
npm run lint                # Fix JS/TS (ESLint)
npm run format              # Format (Prettier)
npm run types:check         # TypeScript type checking

# Run all CI checks at once
composer ci:check

# Build frontend assets
npm run build

# Regenerate Wayfinder type-safe routes
php artisan wayfinder:generate

# Run the daily occupancy reset manually
php artisan app:reset-daily-occupancy
```

## Testing

The project uses property-based testing alongside traditional feature and unit tests to validate correctness properties.

```bash
# All backend tests
composer test

# All frontend tests
npm test

# Specific backend test file
php artisan test tests/Property/ConventionPropertiesTest.php

# Specific frontend test file
npx vitest run resources/js/components/conventions/__tests__/user-row.test.tsx

# Backend with coverage
php artisan test --coverage
```

See [Testing Guide](docs/TESTING.md) for details on writing tests and the property-based testing approach.

## Documentation

### Getting Started
- [Installation Guide](docs/INSTALLATION.md) — Detailed setup instructions
- [Development Guide](docs/DEVELOPMENT.md) — Development workflow and commands
- [FAQ](docs/FAQ.md) — Frequently asked questions

### Core Concepts
- [Architecture Overview](docs/ARCHITECTURE.md) — Project structure and patterns
- [Convention Management](docs/CONVENTIONS.md) — Convention system documentation
- [Authentication](docs/AUTHENTICATION.md) — Auth system documentation
- [Type-Safe Routing](docs/ROUTING.md) — Wayfinder usage guide

### Reference
- [API Reference](docs/API.md) — All endpoints, request/response formats, validation rules
- [User Guide](docs/USER_GUIDE.md) — End-user documentation for all features
- [Testing Guide](docs/TESTING.md) — Writing and running tests
- [Deployment](docs/DEPLOYMENT.md) — Production deployment guide
- [Contributing](docs/CONTRIBUTING.md) — How to contribute
- [Changelog](docs/CHANGELOG.md) — Version history

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
