# Development Guide

This guide covers the development workflow, commands, and best practices for working with the Convention Management System.

## Development Workflow

### Starting Development

The fastest way to start all development services:

```bash
composer dev
```

This command starts four services concurrently:
1. **Laravel Server** (blue) - `php artisan serve` on port 8000
2. **Queue Worker** (purple) - Processes background jobs
3. **Log Viewer** (pink) - Real-time log monitoring with Pail
4. **Vite Dev Server** (orange) - Hot module replacement on port 5173

### Individual Services

You can also run services individually:

```bash
# Laravel development server only
php artisan serve

# Vite dev server only (for frontend work)
npm run dev

# Queue worker
php artisan queue:listen

# Log viewer
php artisan pail
```

### SSR Development

For server-side rendering development:

```bash
composer dev:ssr
```

This builds SSR assets and starts the Inertia SSR server instead of Vite.

## Project Structure

```
laravel-react-starter-kit/
├── app/                    # Laravel backend
│   ├── Actions/           # Business logic
│   ├── Http/              # Controllers, middleware, requests
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers
├── resources/
│   ├── js/                # React frontend
│   │   ├── components/    # React components
│   │   ├── pages/         # Inertia pages
│   │   ├── layouts/       # Layout components
│   │   ├── hooks/         # Custom hooks
│   │   └── types/         # TypeScript types
│   └── css/               # Stylesheets
├── routes/                # Laravel routes
├── database/              # Migrations, seeders, factories
└── tests/                 # Pest tests
```

See [Architecture Overview](ARCHITECTURE.md) for detailed structure documentation.

## Common Development Tasks

### Creating a New Page

1. **Create the backend route** in `routes/web.php`:

```php
Route::get('/about', function () {
    return inertia('about');
})->name('about');
```

2. **Create the React page** at `resources/js/pages/about.tsx`:

```tsx
export default function About() {
    return (
        <div>
            <h1>About Us</h1>
        </div>
    );
}
```

3. **Generate Wayfinder routes**:

```bash
php artisan wayfinder:generate
```

4. **Use type-safe routing** in your components:

```tsx
import { route } from '@/routes';

<Link href={route('about')}>About</Link>
```

### Adding a New Component

1. **Create component** in `resources/js/components/`:

```tsx
// resources/js/components/my-component.tsx
interface MyComponentProps {
    title: string;
}

export function MyComponent({ title }: MyComponentProps) {
    return <div>{title}</div>;
}
```

2. **Import and use**:

```tsx
import { MyComponent } from '@/components/my-component';

<MyComponent title="Hello" />
```

### Creating a Controller

```bash
php artisan make:controller MyController
```

### Creating a Model

```bash
# Model only
php artisan make:model MyModel

# Model with migration
php artisan make:model MyModel -m

# Model with migration, factory, and seeder
php artisan make:model MyModel -mfs
```

### Creating a Migration

```bash
php artisan make:migration create_my_table
```

### Creating a Form Request

```bash
php artisan make:request MyRequest
```

## Code Quality

### Linting and Formatting

```bash
# PHP - Laravel Pint
composer lint              # Fix code style issues
composer lint:check        # Check without fixing

# JavaScript/TypeScript - ESLint
npm run lint               # Fix linting issues
npm run lint:check         # Check without fixing

# Prettier
npm run format             # Format code
npm run format:check       # Check formatting

# TypeScript
npm run types:check        # Type checking
```

### Running All Checks

Before committing, run all checks:

```bash
composer ci:check
```

This runs:
- PHP linting (Pint)
- JS/TS linting (ESLint)
- Code formatting (Prettier)
- Type checking (TypeScript)
- Tests (Pest)

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/MyTest.php

# Run with coverage
php artisan test --coverage
```

### Writing Tests

Create a new test:

```bash
php artisan make:test MyTest
```

Example Pest test:

```php
<?php

use App\Models\User;

test('user can view dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
```

See [Testing Guide](TESTING.md) for more details.

## Database

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Reset and seed
php artisan migrate:fresh --seed
```

### Seeding Data

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder
```

### Database Console

```bash
# SQLite
php artisan db

# MySQL/PostgreSQL
php artisan db
```

## Building for Production

### Frontend Assets

```bash
# Build optimized assets
npm run build

# Build with SSR support
npm run build:ssr
```

### Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## Debugging

### Laravel Debugging

```bash
# View logs in real-time
php artisan pail

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Tinker (REPL)
php artisan tinker
```

### Frontend Debugging

- Use React DevTools browser extension
- Check browser console for errors
- Use `console.log()` or debugger statements
- Inspect network requests in browser DevTools

### Common Issues

**Vite not connecting:**
```bash
# Clear Vite cache
rm -rf node_modules/.vite
npm run dev
```

**Inertia version mismatch:**
```bash
# Clear Inertia cache
php artisan inertia:clear
```

**Type errors after route changes:**
```bash
# Regenerate Wayfinder routes
php artisan wayfinder:generate
```

## Git Workflow

### Recommended Workflow

1. Create a feature branch:
```bash
git checkout -b feature/my-feature
```

2. Make changes and commit:
```bash
git add .
git commit -m "feat: add my feature"
```

3. Run checks before pushing:
```bash
composer ci:check
```

4. Push and create pull request:
```bash
git push origin feature/my-feature
```

### Commit Message Convention

Follow conventional commits:

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting)
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

## Environment-Specific Configuration

### Development

```env
APP_ENV=local
APP_DEBUG=true
VITE_APP_NAME="${APP_NAME}"
```

### Staging

```env
APP_ENV=staging
APP_DEBUG=false
```

### Production

```env
APP_ENV=production
APP_DEBUG=false
```

See [Deployment Guide](DEPLOYMENT.md) for production setup.

## Scheduled Tasks

The application includes scheduled tasks that run automatically via Laravel's scheduler:

```bash
# Daily occupancy reset (runs automatically at 6:00 AM)
php artisan app:reset-daily-occupancy
```

This resets all section occupancy to 0%, clears available seats, and removes update metadata. To run the scheduler locally during development:

```bash
php artisan schedule:work
```

## Useful Artisan Commands

```bash
# List all routes
php artisan route:list

# List all commands
php artisan list

# Generate IDE helper files
php artisan ide-helper:generate

# Clear all caches
php artisan optimize:clear

# View application info
php artisan about
```

## IDE Setup

### VS Code

Recommended extensions:
- PHP Intelephense
- Laravel Extension Pack
- ESLint
- Prettier
- Tailwind CSS IntelliSense
- TypeScript and JavaScript Language Features

### PhpStorm

- Enable Laravel plugin
- Configure PHP interpreter
- Set up Node.js interpreter
- Enable ESLint and Prettier

## Next Steps

- Learn about [Type-Safe Routing](ROUTING.md)
- Understand [Authentication System](AUTHENTICATION.md)
- Read [Testing Guide](TESTING.md)
- Review [Deployment Guide](DEPLOYMENT.md)
