# Laravel React Starter Kit

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A modern, full-stack Laravel React starter kit with authentication, type-safe routing, and a beautiful UI. Built with best practices and developer experience in mind.

## ✨ Key Features

- 🔐 **Complete Authentication** - Login, registration, password reset, email verification
- 🛡️ **Two-Factor Authentication** - TOTP-based 2FA with recovery codes
- 🎨 **Modern UI** - Tailwind CSS 4 with Radix UI components
- 🔒 **Type-Safe Routing** - Laravel Wayfinder for end-to-end type safety
- ⚡ **SPA Experience** - Inertia.js bridges Laravel and React seamlessly
- 🌓 **Theme Support** - Built-in light/dark mode
- 📱 **Responsive Design** - Mobile-first approach
- 🧪 **Testing Ready** - Pest PHP for elegant testing
- 🎯 **Code Quality** - ESLint, Prettier, Laravel Pint pre-configured

## 🚀 Quick Start

```bash
# Clone the repository
git clone <your-repo-url>
cd laravel-react-starter-kit

# Install dependencies and set up the project
composer setup

# Start development servers
composer dev
```

Visit `http://localhost:8000` to see your application.

## 📚 Documentation

### Getting Started
- [Installation Guide](docs/INSTALLATION.md) - Detailed setup instructions
- [Development Guide](docs/DEVELOPMENT.md) - Development workflow and commands
- [FAQ](docs/FAQ.md) - Frequently asked questions

### Core Concepts
- [Architecture Overview](docs/ARCHITECTURE.md) - Project structure and patterns
- [Authentication](docs/AUTHENTICATION.md) - Auth system documentation
- [Type-Safe Routing](docs/ROUTING.md) - Wayfinder usage guide

### Advanced
- [Testing Guide](docs/TESTING.md) - Writing and running tests
- [Deployment](docs/DEPLOYMENT.md) - Production deployment guide
- [Contributing](docs/CONTRIBUTING.md) - How to contribute

### Reference
- [Changelog](docs/CHANGELOG.md) - Version history

## 🛠️ Tech Stack

### Backend
- **PHP 8.2+** - Modern PHP features
- **Laravel 12** - Latest Laravel framework
- **Laravel Fortify** - Authentication scaffolding
- **Laravel Wayfinder** - Type-safe routing
- **Inertia.js** - Server-side routing with SPA experience
- **SQLite** - Lightweight database (development)
- **Pest PHP** - Elegant testing framework

### Frontend
- **React 19** - Latest React with compiler
- **TypeScript** - Type safety throughout
- **Vite** - Lightning-fast build tool
- **Tailwind CSS 4** - Utility-first styling
- **Radix UI** - Accessible component primitives
- **Lucide React** - Beautiful icon library

## 📋 Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL for production)

## 🎯 Common Commands

```bash
# Development
composer dev              # Start all dev services
composer dev:ssr          # Start with SSR support
npm run dev               # Vite dev server only

# Testing
composer test             # Run PHP tests

# Code Quality
composer lint             # Fix PHP code style
npm run lint              # Fix JS/TS issues
npm run format            # Format code with Prettier
npm run types:check       # TypeScript type checking

# Building
npm run build             # Build for production
npm run build:ssr         # Build with SSR

# CI
composer ci:check         # Run all checks
```

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](docs/CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

Built with these amazing tools:
- [Laravel](https://laravel.com)
- [React](https://react.dev)
- [Inertia.js](https://inertiajs.com)
- [Tailwind CSS](https://tailwindcss.com)
- [Radix UI](https://www.radix-ui.com)
