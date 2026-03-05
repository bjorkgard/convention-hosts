# Installation Guide

This guide will walk you through setting up the Convention Management System on your local machine.

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2 or higher** with required extensions:
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PCRE
  - PDO
  - Tokenizer
  - XML
- **Composer 2.x** - [Download Composer](https://getcomposer.org/download/)
- **Node.js 18+** and npm - [Download Node.js](https://nodejs.org/)
- **SQLite** (included with PHP) or MySQL/PostgreSQL

### Verify Prerequisites

```bash
# Check PHP version and extensions
php -v
php -m

# Check Composer
composer --version

# Check Node.js and npm
node --version
npm --version
```

## Installation Steps

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd laravel-react-starter-kit
```

### 2. Automated Setup (Recommended)

The easiest way to set up the project is using the automated setup command:

```bash
composer setup
```

This command will:
1. Install PHP dependencies via Composer
2. Copy `.env.example` to `.env`
3. Generate application key
4. Run database migrations
5. Install Node.js dependencies
6. Build frontend assets

### 3. Manual Setup (Alternative)

If you prefer to set up manually or need more control:

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Install Node.js dependencies
npm install

# Build frontend assets
npm run build
```

## Configuration

### Environment Variables

Edit the `.env` file to configure your application:

```env
# Application
APP_NAME="Convention Hosts"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE="Europe/Stockholm"

# Database (SQLite by default)
DB_CONNECTION=sqlite

# Mail (uses log driver in development)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Application Configuration

The application is configured with the following defaults:

- **Application Name**: "Convention Hosts" - Used in emails, notifications, and UI elements
- **Timezone**: "Europe/Stockholm" - All timestamps are stored in UTC but displayed in this timezone
- **Environment**: Local development mode with debug enabled

### Database Configuration

#### SQLite (Default)

SQLite is configured by default for easy development:

```env
DB_CONNECTION=sqlite
```

The database file is located at `database/database.sqlite`.

#### MySQL

To use MySQL instead:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### PostgreSQL

To use PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Mail Configuration

For development, the log driver is used by default. For production, configure your mail service:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

## Verification

### Start Development Server

```bash
composer dev
```

This starts:
- Laravel development server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server (port 5173)

### Access the Application

Open your browser and navigate to:
- **Application**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173 (proxied through Laravel)

### Test Registration

1. Click "Register" in the navigation
2. Fill in the registration form
3. Submit and verify you're redirected to the dashboard

## Troubleshooting

### Port Already in Use

If port 8000 is already in use:

```bash
# Use a different port
php artisan serve --port=8080
```

### Permission Issues

If you encounter permission errors:

```bash
# Fix storage and cache permissions
chmod -R 775 storage bootstrap/cache
```

### Database Connection Failed

Verify your database configuration in `.env` and ensure:
- Database exists (for MySQL/PostgreSQL)
- Credentials are correct
- Database server is running

### Node Modules Issues

If you encounter npm errors:

```bash
# Clear npm cache and reinstall
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

### Vite Build Errors

If Vite fails to build:

```bash
# Clear Vite cache
rm -rf node_modules/.vite

# Rebuild
npm run build
```

## Next Steps

- Read the [Development Guide](DEVELOPMENT.md) to learn about the development workflow
- Explore the [Architecture Overview](ARCHITECTURE.md) to understand the project structure
- Check out the [Authentication Guide](AUTHENTICATION.md) to learn about the auth system

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Inertia.js Documentation](https://inertiajs.com)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
