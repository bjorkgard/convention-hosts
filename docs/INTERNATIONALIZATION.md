# Internationalization (i18n)

The application supports multiple languages. Laravel is the single source of truth for all translations — the React frontend fetches them via API rather than bundling static JSON files.

## Supported Locales

- English (`en`)
- Swedish (`sv`) — application default

## Translation File Structure

Translations live in `lang/{locale}/` as PHP files organized by domain:

```
lang/
├── en/
│   ├── auth.php          # Authentication pages
│   ├── convention.php    # Convention management
│   ├── floor.php         # Floor management
│   ├── section.php       # Section management
│   ├── user.php          # User management
│   ├── search.php        # Search/availability
│   ├── attendance.php    # Attendance reporting
│   ├── settings.php      # User settings
│   ├── emails.php        # Email templates
│   ├── validation.php    # Validation messages
│   ├── public.php        # Public/welcome page
│   ├── notifications.php # Notifications
│   ├── navigation.php    # Navigation labels
│   ├── export.php        # Data export
│   └── common.php        # Shared strings
└── sv/
    └── (same 15 files with Swedish translations)
```

Keys use dot-notation reflecting domain and context:

```php
// lang/en/convention.php
return [
    'create' => [
        'title' => 'Create Convention',
        'name_label' => 'Convention Name',
    ],
];
```

## Adding a New Language

1. Create a new directory under `lang/`, e.g. `lang/de/`
2. Copy all 15 PHP files from `lang/en/` into the new directory
3. Translate the values (keep the keys identical)

The API auto-discovers new locales from the `lang/` directory — no code changes needed.

## Locale Resolution

The active locale is resolved per-request in this priority order:

1. Authenticated user's `users.locale` column (if set)
2. Convention's `conventions.locale` column (for URL session visitors)
3. Fallback: Swedish (`sv`)

## Database Columns

| Table | Column | Type | Default | Purpose |
|-------|--------|------|---------|---------|
| users | locale | string, nullable | null | User's preferred language |
| conventions | locale | string, nullable | 'sv' | Convention's default language for anonymous visitors |

### Locale Validation

When updating a convention or creating a guest convention, the `locale` field is validated against the `lang/` directory. Only locale codes that have a corresponding `lang/{locale}/` directory are accepted (max 10 characters). This ensures conventions can only be set to locales that have translation files available.

## Backend Usage

Use Laravel's `__()` helper or `@lang()` Blade directive:

```php
// In controllers/actions
__('convention.created_success')

// In Blade email templates
@lang('emails.invitation.greeting', ['name' => $userName])
```

## Frontend Bootstrap

The i18n system is initialized in `resources/js/lib/i18n.ts` and imported as a side effect in `app.tsx`. It uses `i18next-http-backend` to fetch translations from the Laravel API at runtime:

- Backend: `i18next-http-backend` loads from `/api/translations/{{lng}}`
- Fallback language: Swedish (`sv`)
- No static JSON translation files in the frontend source

A `useLocaleSync` hook (in `resources/js/hooks/use-locale-sync.ts`) keeps `react-i18next` in sync with the locale shared via Inertia props on every navigation.

## Frontend Usage

Use the `useTranslation` hook from `react-i18next` in any component:

```tsx
import { useTranslation } from 'react-i18next';

function MyComponent() {
    const { t } = useTranslation();
    return <h1>{t('convention.create.title')}</h1>;
}
```

## API Endpoints

Both endpoints are public (no authentication required):

| Endpoint | Description |
|----------|-------------|
| `GET /api/locales` | Returns JSON array of available locale codes |
| `GET /api/translations/{locale}` | Returns merged translations for the locale (404 if locale doesn't exist) |
