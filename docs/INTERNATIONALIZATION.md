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

### Pluralization

Use Laravel's standard plural syntax in translation files. The API automatically converts it to i18next's plural format when serving translations.

Laravel syntax:

```php
// lang/en/attendance.php
return [
    'sections_reported' => '{1} :count section reported|[2,*] :count sections reported',
];
```

The API expands this into two i18next-compatible keys with `_one` / `_other` suffixes and converts `:placeholder` to `{{placeholder}}`:

```json
{
    "attendance": {
        "sections_reported_one": "{{count}} section reported",
        "sections_reported_other": "{{count}} sections reported"
    }
}
```

In React, use i18next's `count` interpolation to select the correct form:

```tsx
t('attendance.sections_reported', { count: reportedCount })
```

## Adding a New Language

1. Create a new directory under `lang/`, e.g. `lang/de/`
2. Copy all 15 PHP files from `lang/en/` into the new directory
3. Translate the values (keep the keys identical)
4. Add a display label in `resources/js/lib/locale-labels.ts` (e.g. `de: 'Deutsch'`)

The API auto-discovers new locales from the `lang/` directory — no code changes needed. Step 4 is optional but recommended; without it the UI falls back to showing the uppercase locale code (e.g. "DE"). This behavior is verified by `tests/Feature/LocaleAutoDiscoveryTest.php`.

## Locale Resolution

The active locale is resolved per-request in this priority order:

1. URL-session `localStorage` preference (`locale_{conventionId}`) — client-side only
2. Authenticated user's `users.locale` column (if set)
3. Convention's `conventions.locale` column (for URL session visitors)
4. Fallback: Swedish (`sv`)

Step 1 is evaluated entirely on the client by `useLocaleSync`. When a URL-session visitor picks a language via the `LocaleSelector`, the choice is written to `localStorage` so it survives page navigations without a backend round-trip.

## Database Columns

| Table | Column | Type | Default | Purpose |
|-------|--------|------|---------|---------|
| users | locale | string, nullable | null | User's preferred language |
| conventions | locale | string, nullable | 'sv' | Convention's default language for anonymous visitors |

### Locale Validation

When updating a convention or creating a guest convention, the `locale` field is validated against the `lang/` directory. Only locale codes that have a corresponding `lang/{locale}/` directory are accepted (max 10 characters). This ensures conventions can only be set to locales that have translation files available.

### Convention Locale Update

A dedicated endpoint (`PATCH /conventions/{convention}/locale`) allows updating only the convention's locale without submitting the full convention update form. The `LocaleSelector` component uses this endpoint when a `conventionId` prop is provided. The same `lang/` directory validation applies.

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

A `useLocaleSync` hook (in `resources/js/hooks/use-locale-sync.ts`) keeps `react-i18next` in sync with the locale shared via Inertia props on every navigation. For URL-session users, the hook checks `localStorage` for a per-convention preference (`locale_{conventionId}`) before falling back to the server-provided locale. This prevents Inertia navigations from resetting a language choice made via the `LocaleSelector` component.

Human-readable locale labels (e.g. `en` → "English", `sv` → "Svenska") are defined in `resources/js/lib/locale-labels.ts`. The `localeLabel()` helper is used by both the `LocaleSelector` dropdown and the profile settings page. When adding a new language, add its label to the `LOCALE_LABELS` map in that file so the UI displays a friendly name instead of the raw locale code.

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
