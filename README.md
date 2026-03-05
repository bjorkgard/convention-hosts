# Convention Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive convention management system built with Laravel and React. Manage multi-day events with real-time occupancy tracking, attendance reporting, and role-based access control.

## ✨ Key Features

### Convention Management
- 📅 **Event Management** - Multi-day convention organization with date validation and conflict detection
- 🏢 **Venue Hierarchy** - Convention → Floor → Section structure for organized venue management
- 📊 **Real-time Occupancy** - Live section capacity tracking with color-coded indicators (0-100%)
- 📝 **Attendance Reporting** - Morning/afternoon period tracking with locking for historical data integrity
- 📤 **Data Export** - Multi-format export (.xlsx, .docx, Markdown) for comprehensive reporting

### User Management & Security
- 👥 **Role-Based Access** - Four-tier permission system (Owner, ConventionUser, FloorUser, SectionUser)
- ✉️ **User Invitations** - Secure email invitations with account activation via signed URLs
- 🔐 **Complete Authentication** - Login with "remember me", password reset, email verification
- 🛡️ **Two-Factor Authentication** - TOTP-based 2FA with recovery codes
- 🔒 **Type-Safe Routing** - Laravel Wayfinder for end-to-end type safety

### Search & Accessibility
- 🔍 **Section Search** - Find available sections with accessibility filters (elder-friendly, handicap-friendly)
- 📱 **PWA Support** - Progressive Web App for native-like mobile experience
- 🌓 **Theme Support** - Built-in light/dark mode
- 📱 **Mobile-First Design** - Optimized for on-site convention management

### Developer Experience
- ⚡ **SPA Experience** - Inertia.js bridges Laravel and React seamlessly
- 🎨 **Modern UI** - Tailwind CSS 4 with Radix UI components
- 🧪 **Testing Ready** - Pest PHP for elegant testing
- 🎯 **Code Quality** - ESLint, Prettier, Laravel Pint pre-configured

## 🚀 Quick Start

```bash
# Clone the repository
git clone <your-repo-url>
cd convention-management-system

# Install dependencies and set up the project
composer setup

# Configure email (required for user invitations)
# Add MAILGUN_DOMAIN and MAILGUN_SECRET to .env

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
- [Convention Management](docs/CONVENTIONS.md) - Convention system documentation

### Advanced
- [Testing Guide](docs/TESTING.md) - Writing and running tests
- [Deployment](docs/DEPLOYMENT.md) - Production deployment guide
- [Contributing](docs/CONTRIBUTING.md) - How to contribute

### Reference
- [Changelog](docs/CHANGELOG.md) - Version history

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE)
