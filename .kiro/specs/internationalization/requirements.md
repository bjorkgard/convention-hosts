# Requirements Document

## Introduction

This feature adds a complete internationalization (i18n) system to the Convention Management System. Laravel serves as the single source of truth for all translations, stored in `lang/{locale}/` directories. The React frontend consumes translations via API endpoints using `react-i18next`. Locale resolution follows a priority chain: authenticated user preference → convention default locale → application fallback (Swedish `sv`). All hardcoded strings across backend and frontend are replaced with translatable keys.

## Glossary

- **I18n_System**: The internationalization subsystem responsible for managing locales, translations, and locale resolution across the application
- **Locale_API**: The set of HTTP endpoints that serve available locales and translation data to the frontend
- **Locale_Resolver**: The middleware component that determines and applies the active locale per-request based on the priority chain
- **Locale_Selector**: The React dropdown component that displays available locales and allows users to switch locale
- **Translation_Store**: The `lang/{locale}/` directory structure in Laravel containing all translation files organized by domain
- **Translation_Scanner_Hook**: The Kiro automation hook that detects new hardcoded strings and synchronizes them to all locale files

## Requirements

### Requirement 1: Translation File Structure

**User Story:** As a developer, I want all translatable strings organized in domain-specific files under `lang/{locale}/`, so that translations are maintainable and logically grouped.

#### Acceptance Criteria

1. THE Translation_Store SHALL organize translation keys into domain files: `auth`, `convention`, `floor`, `section`, `user`, `search`, `attendance`, `settings`, `emails`, `validation`, `public`, `notifications`, `navigation`, `export`, and `common`
2. THE Translation_Store SHALL provide complete English (`en`) locale files covering all strings discovered in pages, components, Blade views, validation messages, notifications, email templates, and error messages
3. THE Translation_Store SHALL provide complete Swedish (`sv`) locale files with translated equivalents for all keys present in the English locale
4. WHEN a new locale subdirectory is added to `lang/`, THE Locale_API SHALL automatically include the new locale in the available locales list without code changes
5. THE Translation_Store SHALL use dot-notation keys that reflect the domain and context of each string (e.g., `convention.create.title`, `auth.login.email_label`)

### Requirement 2: Translation API Endpoints

**User Story:** As a frontend developer, I want API endpoints that serve locale data, so that the React app can load translations dynamically from Laravel.

#### Acceptance Criteria

1. WHEN a GET request is made to `/api/locales`, THE Locale_API SHALL return a JSON array of available locale codes derived from the subdirectories present in the `lang/` directory
2. WHEN a GET request is made to `/api/translations/{locale}` with a valid locale code, THE Locale_API SHALL return a JSON object containing all translation key-value pairs for that locale, merged from all domain files
3. WHEN a GET request is made to `/api/translations/{locale}` with a locale code that has no corresponding `lang/` subdirectory, THE Locale_API SHALL return a 404 response
4. THE Locale_API SHALL allow unauthenticated access to both endpoints so that public pages and anonymous URL sessions can load translations

### Requirement 3: Backend Locale Resolution Middleware

**User Story:** As a user, I want the application to automatically display content in my preferred language, so that I have a localized experience without manual configuration each visit.

#### Acceptance Criteria

1. WHEN an authenticated user makes a request, THE Locale_Resolver SHALL set the application locale to the value stored in the `users.locale` column for that user
2. WHEN an anonymous user accesses the application via a URL session (floor_url_token or section_url_token), THE Locale_Resolver SHALL set the application locale to the `conventions.locale` value of the associated convention
3. WHEN neither an authenticated user locale nor a convention context locale is available, THE Locale_Resolver SHALL set the application locale to Swedish (`sv`)
4. THE Locale_Resolver SHALL call `App::setLocale()` before any controller logic executes on every request
5. THE Locale_Resolver SHALL share the resolved locale with the frontend via Inertia shared data so that `react-i18next` can synchronize

### Requirement 4: Database Schema for Locale Preferences

**User Story:** As a system administrator, I want locale preferences persisted in the database, so that user and convention locale choices survive across sessions.

#### Acceptance Criteria

1. THE I18n_System SHALL add a `locale` column (string, nullable, default `null`) to the `users` table via migration
2. THE I18n_System SHALL add a `locale` column (string, nullable, default `sv`) to the `conventions` table via migration
3. WHEN a user record has a `null` locale value, THE Locale_Resolver SHALL fall through to the next priority level in the resolution chain (convention locale or application fallback)

### Requirement 5: Frontend i18next Bootstrap

**User Story:** As a frontend developer, I want `react-i18next` initialized from the Laravel translation API, so that the frontend uses the same translations as the backend without maintaining separate files.

#### Acceptance Criteria

1. WHEN the React application loads, THE I18n_System SHALL fetch translations from `GET /api/translations/{locale}` using the locale provided via Inertia shared data
2. THE I18n_System SHALL configure `react-i18next` with the fetched translations and set the active language to the resolved locale
3. THE I18n_System SHALL set the `react-i18next` fallback language to Swedish (`sv`)
4. THE I18n_System SHALL NOT use static local JSON translation files in the frontend source code

### Requirement 6: Locale Selector Component

**User Story:** As a user, I want a language selector in the UI, so that I can switch the application language at any time.

#### Acceptance Criteria

1. WHEN the Locale_Selector renders, THE Locale_Selector SHALL fetch available locales from `GET /api/locales` and display them as selectable options
2. WHEN an authenticated user selects a locale, THE Locale_Selector SHALL persist the selection to the backend via an API call that updates `users.locale` and simultaneously update the `react-i18next` active language
3. WHEN an anonymous URL session user selects a locale, THE Locale_Selector SHALL store the selection in `localStorage` keyed by convention ID and update the `react-i18next` active language
4. WHEN an anonymous URL session user revisits a convention, THE Locale_Selector SHALL restore the previously selected locale from `localStorage` for that convention ID
5. THE Locale_Selector SHALL be displayed in the application sidebar for URL session users

### Requirement 7: Convention Locale Settings

**User Story:** As a convention owner, I want to set a default locale for my convention, so that anonymous visitors see content in the appropriate language.

#### Acceptance Criteria

1. THE I18n_System SHALL display a locale selector in the convention editing interface alongside the existing Export and Delete actions
2. WHEN a convention owner updates the convention locale, THE I18n_System SHALL persist the new value to `conventions.locale`
3. WHEN a guest convention is created, THE I18n_System SHALL default the convention locale to Swedish (`sv`)
4. WHEN an anonymous user accesses a convention via URL token, THE I18n_System SHALL apply the convention's stored locale as the initial display language

### Requirement 8: User Profile Locale Setting

**User Story:** As an authenticated user, I want to update my language preference in my profile settings, so that the application remembers my choice across all conventions.

#### Acceptance Criteria

1. THE I18n_System SHALL add a locale selector to the user profile/settings page
2. WHEN a user updates their locale preference, THE I18n_System SHALL persist the value to `users.locale` and immediately apply the new locale to the current session
3. THE I18n_System SHALL pre-select the user's currently saved locale in the settings page selector

### Requirement 9: Convention Registration Locale

**User Story:** As a guest creating a convention, I want to select a language during registration, so that the convention starts with the correct default locale.

#### Acceptance Criteria

1. THE I18n_System SHALL add a locale selector to the guest convention creation form on the welcome page
2. THE I18n_System SHALL default the locale selector value to Swedish (`sv`) in the convention registration flow
3. WHEN a guest convention is submitted, THE I18n_System SHALL store the selected locale in `conventions.locale` and `users.locale`

### Requirement 10: Email Locale Resolution

**User Story:** As a user receiving emails, I want emails rendered in my preferred language, so that I can understand the content without language barriers.

#### Acceptance Criteria

1. WHEN an email is sent to a registered user (e.g., UserInvitation, EmailConfirmation), THE I18n_System SHALL render the email using the recipient user's saved locale from `users.locale`
2. WHEN an email is sent to a guest (e.g., GuestConventionVerification), THE I18n_System SHALL render the email using the sending user's locale
3. WHEN a recipient user has no saved locale, THE I18n_System SHALL render the email using the application fallback locale (Swedish `sv`)
4. EACH Mailable class SHALL call `App::setLocale()` with the resolved locale before rendering the email content

### Requirement 11: Backend String Localization

**User Story:** As a developer, I want all backend strings to use Laravel's translation helpers, so that every user-facing string is translatable.

#### Acceptance Criteria

1. THE I18n_System SHALL replace all hardcoded English strings in controllers, actions, form requests, validation messages, and service classes with `__()` translation helper calls
2. THE I18n_System SHALL replace all hardcoded strings in Blade email templates with `@lang()` or `{{ __() }}` directives
3. THE I18n_System SHALL replace all hardcoded flash messages (success, error) with translated equivalents
4. THE I18n_System SHALL ensure Laravel's built-in validation messages are available in both English and Swedish via `lang/{locale}/validation.php`

### Requirement 12: Frontend String Localization

**User Story:** As a developer, I want all frontend strings to use `react-i18next` translation calls, so that every visible string in the React app is translatable.

#### Acceptance Criteria

1. THE I18n_System SHALL replace all hardcoded strings in React page components with `t('key')` calls from `react-i18next`
2. THE I18n_System SHALL replace all hardcoded strings in React layout components with `t('key')` calls
3. THE I18n_System SHALL replace all hardcoded strings in React feature components (conventions, search, settings, auth) with `t('key')` calls
4. THE I18n_System SHALL ensure all `aria-label`, `placeholder`, `title`, and `alt` attributes use translated strings

### Requirement 13: Translation Scanner Kiro Hook

**User Story:** As a developer, I want an automated hook that detects missing translation keys when files change, so that new strings are never left untranslated.

#### Acceptance Criteria

1. WHEN a file is created or modified in the project, THE Translation_Scanner_Hook SHALL scan the changed file for `t('key')` and `__('key')` calls
2. WHEN a scanned key is not present in the English (`en`) locale files, THE Translation_Scanner_Hook SHALL add the missing key to the appropriate `lang/en/` domain file with the English string as value
3. WHEN a new key is added to the English locale, THE Translation_Scanner_Hook SHALL add corresponding entries to all other available locale files (e.g., `sv`)
4. THE Translation_Scanner_Hook SHALL trigger on file creation and file modification events within the project

### Requirement 14: Locale Persistence for URL Session Users

**User Story:** As an anonymous user accessing a convention via URL token, I want my language choice remembered for future visits, so that I do not have to re-select my language each time.

#### Acceptance Criteria

1. WHEN an anonymous URL session user selects a locale via the Locale_Selector, THE I18n_System SHALL store the selection in `localStorage` using a key that includes the convention ID (e.g., `locale_{convention_id}`)
2. WHEN an anonymous URL session user loads a convention page, THE I18n_System SHALL check `localStorage` for a previously stored locale for that convention ID
3. WHEN a stored locale exists in `localStorage` for the current convention, THE I18n_System SHALL apply that locale instead of the convention's default locale
4. WHEN no stored locale exists in `localStorage`, THE I18n_System SHALL apply the convention's default locale
