# Implementation Plan: Internationalization

## Overview

This plan implements a complete i18n system where Laravel is the single source of truth for translations. The work is organized in dependency order: database schema first, then backend infrastructure (middleware, API, email), then frontend bootstrap and components, then the bulk string replacement work, and finally the Kiro automation hook.

## Tasks

- [x] 1. Database schema and model updates
  - [x] 1.1 Create migration to add `locale` column to `users` table
    - Add `locale` column: string, nullable, default `null`, after `email_confirmed`
    - _Requirements: 4.1, 4.3_
  - [x] 1.2 Create migration to add `locale` column to `conventions` table
    - Add `locale` column: string, nullable, default `'sv'`, after `section_url_token`
    - _Requirements: 4.2_
  - [x] 1.3 Update User model to include `locale` in `$fillable`
    - Add `'locale'` to the `$fillable` array in `app/Models/User.php`
    - _Requirements: 4.1_
  - [x] 1.4 Update Convention model to include `locale` in `$fillable`
    - Add `'locale'` to the `$fillable` array in `app/Models/Convention.php`
    - _Requirements: 4.2_
  - [x] 1.5 Update User and Convention factories to support `locale` attribute
    - Add `locale` to `UserFactory` and `ConventionFactory` definitions
    - _Requirements: 4.1, 4.2_
  - [x] 1.6 Update TypeScript types for `locale` fields
    - Add `locale: string | null` to `User` type in `resources/js/types/user.ts`
    - Add `locale: string` to `Convention` type in `resources/js/types/convention.ts`
    - Add `locale: string` to `PageProps` in `resources/js/types/index.ts`
    - _Requirements: 4.1, 4.2, 3.5_

- [x] 2. Translation file structure
  - [x] 2.1 Create English (`en`) translation files for all domains
    - Create `lang/en/` directory with files: `auth.php`, `convention.php`, `floor.php`, `section.php`, `user.php`, `search.php`, `attendance.php`, `settings.php`, `emails.php`, `validation.php`, `public.php`, `notifications.php`, `navigation.php`, `export.php`, `common.php`
    - Scan all existing pages, components, Blade views, controllers, form requests, and email templates to extract every hardcoded string
    - Use dot-notation keys reflecting domain and context (e.g., `convention.create.title`, `auth.login.email_label`)
    - _Requirements: 1.1, 1.2, 1.5_
  - [x] 2.2 Create Swedish (`sv`) translation files with translated equivalents
    - Create `lang/sv/` directory with all 15 domain files mirroring the English locale
    - Provide Swedish translations for all keys present in the English locale
    - Include Laravel's built-in validation messages in Swedish via `lang/sv/validation.php`
    - _Requirements: 1.3, 11.4_

- [x] 3. Checkpoint - Verify migrations and translation files
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Backend locale infrastructure
  - [x] 4.1 Create `LocaleController` with API endpoints
    - Create `app/Http/Controllers/LocaleController.php`
    - Implement `index()`: return JSON array of locale codes from `lang/` subdirectories
    - Implement `show(string $locale)`: return merged translations from all domain files for the locale, 404 if locale directory doesn't exist
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [x] 4.2 Register locale API routes
    - Add `GET /api/locales` and `GET /api/translations/{locale}` routes in `routes/web.php`
    - Ensure routes are public (no auth middleware) so anonymous URL sessions and public pages can load translations
    - _Requirements: 2.1, 2.4_
  - [x] 4.3 Create `SetLocale` middleware
    - Create `app/Http/Middleware/SetLocale.php`
    - Implement locale resolution priority chain: authenticated user `locale` → URL session convention `locale` → fallback `'sv'`
    - Call `App::setLocale()` with the resolved locale
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  - [x] 4.4 Register `SetLocale` as global middleware
    - Add `SetLocale` to the global web middleware stack in `bootstrap/app.php` so it runs before controllers on every request
    - _Requirements: 3.4_
  - [x] 4.5 Share resolved locale via Inertia
    - Add `'locale' => App::getLocale()` to the shared data array in `HandleInertiaRequests`
    - _Requirements: 3.5_
  - [x] 4.6 Write Pest feature tests for `LocaleController`
    - Test `GET /api/locales` returns available locale codes
    - Test `GET /api/translations/en` returns merged translations
    - Test `GET /api/translations/nonexistent` returns 404
    - Test endpoints are accessible without authentication
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [x] 4.7 Write Pest feature tests for `SetLocale` middleware
    - Test authenticated user locale is applied
    - Test URL session convention locale is applied when user locale is null
    - Test fallback to `'sv'` when no locale context exists
    - Test locale is shared via Inertia props
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 5. Checkpoint - Verify backend locale infrastructure
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Email locale resolution
  - [x] 6.1 Update `UserInvitation` mailable for locale-aware rendering
    - Resolve locale from `$this->user->locale ?? 'sv'` before rendering
    - Call `App::setLocale($locale)` in the `build()` method
    - Replace hardcoded subject with `__('emails.invitation_subject', ['convention' => $this->convention->name])`
    - _Requirements: 10.1, 10.3, 10.4_
  - [x] 6.2 Update `EmailConfirmation` mailable for locale-aware rendering
    - Resolve locale from `$this->user->locale ?? 'sv'` before rendering
    - Call `App::setLocale($locale)` before rendering
    - Replace hardcoded subject with translated equivalent
    - _Requirements: 10.1, 10.3, 10.4_
  - [x] 6.3 Update `GuestConventionVerification` mailable for locale-aware rendering
    - Resolve locale from `$this->user->locale ?? 'sv'` (the sending/creating user's locale)
    - Call `App::setLocale($locale)` before rendering
    - Replace hardcoded subject with translated equivalent
    - _Requirements: 10.2, 10.3, 10.4_
  - [x] 6.4 Write Pest tests for email locale resolution
    - Test UserInvitation renders in user's locale
    - Test EmailConfirmation renders in user's locale
    - Test GuestConventionVerification renders in sending user's locale
    - Test fallback to `'sv'` when user locale is null
    - _Requirements: 10.1, 10.2, 10.3_

- [ ] 7. Frontend i18n bootstrap
  - [ ] 7.1 Install `react-i18next`, `i18next`, and `i18next-http-backend` npm packages
    - Run `npm install react-i18next i18next i18next-http-backend`
    - _Requirements: 5.1, 5.2_
  - [ ] 7.2 Create `resources/js/lib/i18n.ts` bootstrap file
    - Configure `i18next` with `HttpBackend` loading from `/api/translations/{{lng}}`
    - Set fallback language to `'sv'`
    - Set `interpolation.escapeValue` to `false` (React handles escaping)
    - Do NOT use static local JSON translation files
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [ ] 7.3 Import i18n bootstrap in `app.tsx`
    - Add `import './lib/i18n'` to `resources/js/app.tsx` so i18n initializes as a side effect
    - _Requirements: 5.1_
  - [ ] 7.4 Create `useLocaleSync` hook
    - Create `resources/js/hooks/use-locale-sync.ts`
    - Read `locale` from Inertia page props via `usePage<PageProps>()`
    - Call `i18n.changeLanguage(locale)` when the prop changes
    - _Requirements: 5.2_
  - [ ] 7.5 Integrate `useLocaleSync` in layout components
    - Call `useLocaleSync()` in `AppLayout` and `AuthLayout` to keep locale in sync on every Inertia navigation
    - _Requirements: 5.2_

- [ ] 8. LocaleSelector component and integration points
  - [ ] 8.1 Create `LocaleSelector` React component
    - Create `resources/js/components/locale-selector.tsx`
    - Fetch available locales from `GET /api/locales` on mount (cache the result)
    - Display current locale with a dropdown (use Radix UI `DropdownMenu` consistent with existing UI patterns)
    - For authenticated users: PATCH to profile update endpoint with `{ locale }`, then call `i18n.changeLanguage()`
    - For URL session users: store in `localStorage` as `locale_{convention_id}`, call `i18n.changeLanguage()`
    - _Requirements: 6.1, 6.2, 6.3_
  - [ ] 8.2 Add `LocaleSelector` to app sidebar for URL session users
    - Display the locale selector in the sidebar footer area for anonymous URL session users
    - _Requirements: 6.5_
  - [ ] 8.3 Add locale selector to convention show page
    - Display locale selector alongside existing Export and Delete actions for Owner/Admin
    - On selection, persist to `conventions.locale` via convention update endpoint
    - _Requirements: 7.1, 7.2_
  - [ ] 8.4 Add locale selector to user profile settings page
    - Add locale dropdown to the profile/settings page
    - Pre-select the user's currently saved locale
    - On update, persist to `users.locale` and immediately apply the new locale
    - _Requirements: 8.1, 8.2, 8.3_
  - [ ] 8.5 Add locale selector to guest convention creation form
    - Add locale dropdown to the welcome page guest convention form
    - Default to Swedish (`sv`)
    - On submission, store selected locale in both `conventions.locale` and `users.locale`
    - _Requirements: 9.1, 9.2, 9.3_
  - [ ] 8.6 Implement localStorage locale persistence for URL session users
    - Create `resources/js/hooks/use-url-session-locale.ts`
    - On locale selection: store in `localStorage` with key `locale_{convention_id}`
    - On page load: check `localStorage` for stored locale for current convention ID
    - Apply stored locale if found, otherwise use convention's default locale
    - _Requirements: 14.1, 14.2, 14.3, 14.4_
  - [ ] 8.7 Update backend to accept `locale` in convention update and profile update requests
    - Add `locale` validation to `UpdateConventionRequest` (nullable string, must be a valid locale directory)
    - Add `locale` field handling to profile update endpoint
    - Update `StoreGuestConventionRequest` to accept `locale` field
    - _Requirements: 7.2, 8.2, 9.3_

- [ ] 9. Checkpoint - Verify frontend i18n and locale selector
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Backend string localization
  - [ ] 10.1 Replace hardcoded strings in controllers with `__()` calls
    - Replace all flash messages (success, error) with translated equivalents
    - Replace any user-facing strings in controller responses
    - Cover: ConventionController, FloorController, SectionController, UserController, SearchController, AttendanceController, GuestConventionController, Auth controllers
    - _Requirements: 11.1, 11.3_
  - [ ] 10.2 Replace hardcoded strings in actions and form requests
    - Replace strings in CreateConventionAction, ExportConventionAction, InviteUserAction, UpdateOccupancyAction
    - Replace custom validation messages in all form request `messages()` methods
    - _Requirements: 11.1_
  - [ ] 10.3 Replace hardcoded strings in Blade email templates
    - Replace all hardcoded strings in `resources/views/emails/` with `@lang()` or `{{ __() }}` directives
    - Cover: user-invitation, email-confirmation, guest-convention-verification templates
    - _Requirements: 11.2_
  - [ ] 10.4 Ensure Laravel validation messages are available in both locales
    - Verify `lang/en/validation.php` covers all custom validation messages used in form requests
    - Verify `lang/sv/validation.php` has Swedish equivalents
    - _Requirements: 11.4_

- [ ] 11. Frontend string localization
  - [ ] 11.1 Replace hardcoded strings in auth page components
    - Replace strings in: login, confirm-password, forgot-password, reset-password, two-factor-challenge, verify-email, invitation, invitation-invalid, guest-convention-confirmation, guest-convention-invalid, guest-convention-set-password
    - Use `const { t } = useTranslation()` and `t('key')` calls
    - _Requirements: 12.1_
  - [ ] 11.2 Replace hardcoded strings in convention, floor, section, and search pages
    - Replace strings in: conventions/index, conventions/create, conventions/show, floors/index, sections/index, sections/show, search/index
    - _Requirements: 12.1_
  - [ ] 11.3 Replace hardcoded strings in settings pages
    - Replace strings in: settings/profile, settings/password, settings/two-factor, settings/appearance
    - _Requirements: 12.1_
  - [ ] 11.4 Replace hardcoded strings in layout components
    - Replace strings in: app-layout, auth-layout, app-sidebar-layout, app-header-layout, settings layout, auth card/simple/split layouts
    - _Requirements: 12.2_
  - [ ] 11.5 Replace hardcoded strings in feature components
    - Replace strings in convention components: attendance-report-banner, available-seats-input, convention-card, export-dropdown, floor-row, full-button, occupancy-dropdown, occupancy-gauge, occupancy-indicator, role-badge, section-card, section-modal, user-row
    - _Requirements: 12.3_
  - [ ] 11.6 Replace hardcoded strings in general components
    - Replace strings in: breadcrumbs, heading, nav-convention, nav-footer, nav-main, nav-user, user-info, user-menu-content, confirmation-dialog, delete-user, install-prompt, update-notification-modal, version-badge, two-factor-setup-modal, two-factor-recovery-codes
    - _Requirements: 12.3_
  - [ ] 11.7 Replace hardcoded strings in welcome page
    - Replace all strings in `resources/js/pages/welcome.tsx`
    - _Requirements: 12.1_
  - [ ] 11.8 Ensure all accessibility attributes use translated strings
    - Audit and replace all `aria-label`, `placeholder`, `title`, and `alt` attributes across all components and pages with `t()` calls
    - _Requirements: 12.4_

- [ ] 12. Checkpoint - Verify all string replacements
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Auto-discovery and Kiro translation scanner hook
  - [ ] 13.1 Verify locale auto-discovery works for new locales
    - Confirm that adding a new `lang/{new_locale}/` directory causes `GET /api/locales` to include the new locale without code changes
    - _Requirements: 1.4_
  - [ ] 13.2 Create Kiro translation scanner hook
    - Create `.kiro/hooks/translation-scanner.md`
    - Configure hook to trigger on file creation and modification events
    - Scan changed files for `t('key')` and `__('key')` calls using regex
    - For keys not present in `lang/en/`, add to the appropriate domain file with the English string as value
    - For new keys added to `en/`, add placeholder entries to all other locale directories
    - _Requirements: 13.1, 13.2, 13.3, 13.4_

- [ ] 14. Final checkpoint - Full integration verification
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The design uses PHP (Laravel) and TypeScript (React) — no language selection needed
- Translation files in `lang/{locale}/*.php` are the single source of truth — no static JSON in the frontend
- The `SetLocale` middleware must be registered before any other middleware that depends on locale
- Wayfinder routes will need regeneration after adding the locale API routes (`php artisan wayfinder:generate --with-form`)
