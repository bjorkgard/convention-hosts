# Frequently Asked Questions

## General

### What is the Convention Management System?

A comprehensive Laravel React application for managing multi-day conventions with real-time occupancy tracking, attendance reporting, role-based access control, and mobile-first PWA support.

### Who is this for?

Developers who want to quickly start building Laravel React applications with best practices already configured.

### Is this production-ready?

Yes! The system includes production-ready features like authentication, role-based access control, security measures, and deployment guides.

## Installation

### What are the system requirements?

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- SQLite/MySQL/PostgreSQL

### Can I use MySQL instead of SQLite?

Yes! Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Installation fails with permission errors

Fix permissions:

```bash
chmod -R 775 storage bootstrap/cache
```

## Development

### How do I start the development server?

```bash
composer dev
```

This starts Laravel, queue worker, logs, and Vite.

### Can I use a different port?

Yes:

```bash
php artisan serve --port=8080
```

### How do I add a new page?

1. Create route in `routes/web.php`
2. Create React component in `resources/js/pages/`
3. Generate Wayfinder routes: `php artisan wayfinder:generate`

### Where do I put reusable components?

In `resources/js/components/` directory.

### How do I add a new API endpoint?

Create a controller and define routes in `routes/web.php` or create `routes/api.php`.

## Authentication

### How do I customize the authentication flow?

Edit actions in `app/Actions/Fortify/` and configure in `config/fortify.php`.

### Can I disable email verification?

Yes, remove from `config/fortify.php`:

```php
'features' => [
    // Features::emailVerification(), // Remove this line
],
```

### How do I customize password requirements?

Edit `app/Concerns/PasswordValidationRules.php`.

### Can I add social authentication?

Yes, install Laravel Socialite and configure providers.

## Frontend

### Can I use Vue instead of React?

The system is built specifically for React. The architecture and components are tightly integrated with React 19 and TypeScript.

### How do I add a new UI component?

Use shadcn/ui CLI or create custom components in `resources/js/components/`.

### Where are the Tailwind styles?

Main CSS file: `resources/css/app.css`

### How do I customize the theme?

Edit Tailwind configuration and CSS variables in `resources/css/app.css`.

### Can I use a different icon library?

Yes, but Lucide React is pre-configured and recommended.

## Type Safety

### What is Wayfinder?

Laravel Wayfinder generates TypeScript definitions from Laravel routes for type-safe routing.

### How do I regenerate routes?

```bash
php artisan wayfinder:generate
```

### Routes not updating in frontend?

Clear caches and regenerate:

```bash
php artisan route:clear
php artisan wayfinder:generate
npm run dev
```

## Testing

### How do I run tests?

```bash
composer test
```

### Can I use PHPUnit instead of Pest?

Pest is built on PHPUnit, so you can use both syntaxes.

### How do I write frontend tests?

The system focuses on backend tests with Pest PHP. For frontend testing, consider adding Vitest or Jest.

## Deployment

### How do I deploy to production?

See the [Deployment Guide](DEPLOYMENT.md) for detailed instructions.

### What hosting providers are recommended?

- Laravel Forge
- Laravel Vapor
- DigitalOcean
- AWS
- Heroku

### Do I need to build assets before deploying?

Yes:

```bash
npm run build
```

### How do I enable HTTPS?

Use Let's Encrypt with Certbot (see [Deployment Guide](DEPLOYMENT.md)).

## Performance

### How do I improve performance?

1. Enable OPcache
2. Use Redis for cache and sessions
3. Cache configuration: `php artisan config:cache`
4. Cache routes: `php artisan route:cache`
5. Optimize autoloader: `composer install --optimize-autoloader --no-dev`

### Should I use SSR?

SSR improves initial page load and SEO but adds complexity. Use if needed:

```bash
composer dev:ssr
npm run build:ssr
```

## Database

### How do I run migrations?

```bash
php artisan migrate
```

### How do I rollback migrations?

```bash
php artisan migrate:rollback
```

### How do I seed the database?

```bash
php artisan db:seed
```

### Can I use MongoDB?

Not out of the box. You'd need to install and configure Laravel MongoDB package.

## Troubleshooting

### Vite not connecting

Clear cache and restart:

```bash
rm -rf node_modules/.vite
npm run dev
```

### 500 Internal Server Error

Check logs:

```bash
tail -f storage/logs/laravel.log
```

### CSRF token mismatch

Clear cache:

```bash
php artisan cache:clear
php artisan config:clear
```

### Queue jobs not processing

Start queue worker:

```bash
php artisan queue:work
```

### TypeScript errors after updating routes

Regenerate Wayfinder routes:

```bash
php artisan wayfinder:generate
```

## Customization

### Can I remove features I don't need?

Yes! Remove routes, controllers, and pages you don't need.

### How do I add more authentication features?

Configure in `config/fortify.php` and implement the features.

### Can I change the database structure?

Yes, create new migrations to modify tables.

### How do I add multi-language support?

Install Laravel's localization features and configure translations.

## Updates

### How do I update dependencies?

```bash
composer update
npm update
```

### How do I update Laravel?

Follow Laravel's upgrade guide for your version.

### Will updates break my application?

Always test updates in a development environment first.

## Support

### Where can I get help?

- Check this FAQ
- Read the documentation
- Open an issue on GitHub
- Check Laravel and React documentation

### How do I report a bug?

Open an issue on GitHub with:
- Description
- Steps to reproduce
- Expected vs actual behavior
- Environment details

### Can I request features?

Yes! Open an issue describing the feature and use case.

## License

### What license is this under?

MIT License - free to use for personal and commercial projects.

### Can I use this for commercial projects?

Yes! The MIT license allows commercial use.

### Do I need to credit this project?

Not required, but appreciated!
