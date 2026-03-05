# Contributing Guide

Thank you for considering contributing to the Convention Management System! This guide will help you get started.

## Code of Conduct

Be respectful, inclusive, and professional in all interactions.

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in Issues
2. Create a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details (OS, PHP version, Node version)
   - Screenshots if applicable

### Suggesting Features

1. Check if the feature has been suggested
2. Create an issue describing:
   - The problem it solves
   - Proposed solution
   - Alternative solutions considered
   - Additional context

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Write/update tests
5. Run all checks: `composer ci:check`
6. Commit with clear messages
7. Push and create a pull request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/laravel-react-starter-kit.git
cd laravel-react-starter-kit

# Install dependencies
composer setup

# Start development
composer dev
```

## Coding Standards

### PHP

- Follow Laravel conventions
- Use Laravel Pint for formatting: `composer lint`
- Write Pest tests for new features
- Add type hints where possible

### TypeScript/React

- Use TypeScript strict mode
- Follow React best practices
- Use ESLint: `npm run lint`
- Format with Prettier: `npm run format`
- Add proper type definitions

### Commit Messages

Follow conventional commits:

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes
- `refactor:` - Code refactoring
- `test:` - Test changes
- `chore:` - Maintenance tasks

Example: `feat: add user profile avatar upload`

## Testing

All contributions must include tests:

```bash
# Run tests
composer test

# Run with coverage
php artisan test --coverage
```

## Documentation

Update documentation for:
- New features
- API changes
- Configuration changes
- Breaking changes

## Review Process

1. Automated checks must pass
2. Code review by maintainers
3. Address feedback
4. Approval and merge

## Questions?

Open an issue for questions or join discussions.

Thank you for contributing! 🎉
