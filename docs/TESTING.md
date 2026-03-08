# Testing Guide

This guide covers testing the Convention Management System using Pest PHP (backend) and Vitest with React Testing Library (frontend).

## Overview

The project uses two testing frameworks:

- **Pest PHP** - Backend tests (feature, property, unit) running on Laravel
- **Vitest** - Frontend tests for React components and hooks using React Testing Library

## Running Tests

### Backend Tests (Pest PHP)

```bash
# Run all backend tests
composer test
# or
php artisan test

# Specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Specific test by name
php artisan test --filter="user can login"

# With coverage
php artisan test --coverage

# Parallel testing
php artisan test --parallel
```

### Frontend Tests (Vitest)

```bash
# Run all frontend tests
npm test

# Watch mode (re-runs on file changes)
npx vitest

# Run specific test file
npx vitest run resources/js/components/conventions/__tests__/user-row.test.tsx

# With coverage
npx vitest run --coverage
```

### All Tests

```bash
# Backend + frontend
composer test && npm test
```

## Test Structure

### Directory Structure

```
tests/                          # Backend tests (Pest PHP)
├── Feature/                   # HTTP-level feature tests
│   ├── Auth/                 # Authentication flows
│   ├── Settings/             # Settings flows
│   ├── Section/              # Section authorization
│   ├── Integration/          # End-to-end multi-step flows and performance
│   ├── GuestConventionVerification/
│   ├── Properties/           # Feature-level property-based tests
│   └── ...                   # Other feature tests
├── Property/                  # Pure property-based tests
│   ├── AttendancePropertiesTest.php
│   ├── ConventionPropertiesTest.php
│   ├── EmailUpdateConfirmationTest.php
│   ├── FloorUserPermissionsTest.php
│   ├── InvitationEmailDeliveryTest.php
│   ├── OccupancyPropertiesTest.php
│   ├── RoleBasedDataScopingTest.php
│   ├── SectionCrudPropertyTest.php
│   ├── SectionFrontendPropertyTest.php
│   ├── SectionUserRestrictionsTest.php
│   └── UserPropertiesTest.php
├── Unit/                      # Unit tests for actions and services
├── Helpers/                   # ConventionTestHelper — shared test setup
├── Pest.php                   # Pest configuration
└── TestCase.php               # Base test case

resources/js/                   # Frontend tests (Vitest)
├── components/
│   └── conventions/__tests__/ # Convention component tests
├── pages/
│   └── search/__tests__/      # Page component tests
└── test/
    └── setup.ts               # Vitest setup (imports jest-dom matchers)
```

### Pest Configuration

All three test directories (`Feature`, `Property`, `Unit`) extend `Tests\TestCase` and use `RefreshDatabase`, so every test has full access to the Laravel application and a fresh database per test.

### Test File Naming

- Feature tests: `*Test.php` (e.g., `LoginTest.php`)
- Property tests: `*Test.php` (e.g., `ConventionPropertiesTest.php`)
- Unit tests: `*Test.php` (e.g., `ValidationTest.php`)

## Writing Tests

### Basic Test Structure

```php
<?php

use App\Models\User;

test('user can view conventions list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/conventions')
        ->assertOk();
});
```

### Using `it()` for Readability

```php
it('redirects guests to login', function () {
    $this->get('/conventions')
        ->assertRedirect('/login');
});
```

### Grouping Tests

```php
describe('authentication', function () {
    test('user can login', function () {
        // Test login
    });

    test('user can logout', function () {
        // Test logout
    });
});
```

## Authentication Tests

> **Note:** There is no public self-registration UI. Users join via invitation or guest convention creation. Use the helpers below to create users with proper roles.

### Guest Convention Creation

```php
<?php

use App\Models\User;

test('new user can create a guest convention and receives verification email', function () {
    Mail::fake();

    $response = $this->post('/conventions/guest', [
        'first_name' => 'Test',
        'last_name'  => 'User',
        'email'      => 'guest@example.com',
        'mobile'     => '+1234567890',
        'name'       => 'My Convention',
        'start_date' => '2026-06-01',
        'end_date'   => '2026-06-03',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', [
        'email'          => 'guest@example.com',
        'email_confirmed' => false,
    ]);
    Mail::assertSent(\App\Mail\GuestConventionVerification::class);
});

test('existing user is auto-logged in when creating a guest convention', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $this->post('/conventions/guest', [
        'first_name' => $user->first_name,
        'last_name'  => $user->last_name,
        'email'      => 'existing@example.com',
        'mobile'     => $user->mobile,
        'name'       => 'My Convention',
        'start_date' => '2026-06-01',
        'end_date'   => '2026-06-03',
    ]);

    $this->assertAuthenticatedAs($user);
});
```

### Login

```php
<?php

use App\Models\User;

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/conventions');
    $this->assertAuthenticatedAs($user);
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});
```

### Logout

```php
test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
```

### Two-Factor Authentication

```php
<?php

use App\Models\User;

test('user can enable two factor authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/user/two-factor-authentication')
        ->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('user can confirm two factor authentication', function () {
    $user = User::factory()->create();
    $user->enableTwoFactorAuthentication();

    $code = app('pragmarx.google2fa')->getCurrentOtp(
        decrypt($user->two_factor_secret)
    );

    $this->actingAs($user)
        ->post('/user/confirmed-two-factor-authentication', [
            'code' => $code,
        ])
        ->assertRedirect();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('user can disable two factor authentication', function () {
    $user = User::factory()->create();
    $user->enableTwoFactorAuthentication();
    $user->confirmTwoFactorAuthentication();

    $this->actingAs($user)
        ->delete('/user/two-factor-authentication')
        ->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});
```

## Profile Management Tests

### Update Profile

```php
<?php

use App\Models\User;

test('user can update profile information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->first_name)->toBe('Updated');
    expect($user->fresh()->last_name)->toBe('Name');
});

test('user cannot update email to existing email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'existing@example.com',
        ])
        ->assertSessionHasErrors('email');
});
```

### Update Password

```php
test('user can update password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
    ]);

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('current password must be correct to update password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password');
});
```

### Delete Account

```php
test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/settings/profile')
        ->assertRedirect('/');

    expect(User::find($user->id))->toBeNull();
    $this->assertGuest();
});
```

## Inertia Tests

### Testing Inertia Responses

```php
test('conventions page returns inertia response', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/conventions')
        ->assertInertia(fn ($page) => $page
            ->component('conventions/index')
            ->has('conventions')
        );
});

test('profile page receives user data', function () {
    $user = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertInertia(fn ($page) => $page
            ->component('settings/profile')
            ->where('user.first_name', 'Test')
            ->where('user.last_name', 'User')
            ->where('user.email', 'test@example.com')
        );
});
```

## Database Tests

### Using Factories

```php
use App\Models\User;

test('factory creates user with valid data', function () {
    $user = User::factory()->create([
        'first_name' => 'Custom',
        'last_name' => 'Name',
    ]);

    expect($user->first_name)->toBe('Custom');
    expect($user->last_name)->toBe('Name');
    expect($user->email)->toContain('@');
});

test('factory can create multiple users', function () {
    $users = User::factory()->count(5)->create();

    expect($users)->toHaveCount(5);
});
```

### Database Transactions

Tests automatically run in database transactions and rollback after each test:

```php
test('user is created in database', function () {
    $user = User::factory()->create();

    expect(User::count())->toBe(1);
}); // Database automatically rolled back
```

### Refreshing Database

```php
uses(RefreshDatabase::class);

test('database is fresh', function () {
    expect(User::count())->toBe(0);
});
```

## Expectations API

### Common Expectations

```php
// Equality
expect($value)->toBe(10);
expect($value)->toEqual([1, 2, 3]);

// Types
expect($value)->toBeInt();
expect($value)->toBeString();
expect($value)->toBeArray();
expect($value)->toBeBool();
expect($value)->toBeNull();

// Truthiness
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeTruthy();
expect($value)->toBeFalsy();

// Collections
expect($array)->toHaveCount(3);
expect($array)->toContain('value');
expect($array)->toBeEmpty();

// Strings
expect($string)->toStartWith('Hello');
expect($string)->toEndWith('World');
expect($string)->toContain('middle');

// Numbers
expect($number)->toBeGreaterThan(5);
expect($number)->toBeLessThan(10);
expect($number)->toBeBetween(1, 100);

// Objects
expect($object)->toHaveProperty('name');
expect($object)->toBeInstanceOf(User::class);

// Negation
expect($value)->not->toBe(10);
expect($value)->not->toBeNull();
```

### Laravel-Specific Expectations

```php
// Authentication
$this->assertAuthenticated();
$this->assertGuest();
$this->assertAuthenticatedAs($user);

// Database
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
$this->assertDatabaseCount('users', 5);

// Session
$response->assertSessionHas('key', 'value');
$response->assertSessionHasErrors('email');
$response->assertSessionDoesntHaveErrors();

// Redirects
$response->assertRedirect('/conventions');
$response->assertRedirectToRoute('conventions.index');

// Status
$response->assertOk();
$response->assertCreated();
$response->assertNoContent();
$response->assertNotFound();
$response->assertForbidden();
$response->assertUnauthorized();
```

## Test Hooks

### Setup and Teardown

```php
beforeEach(function () {
    // Runs before each test
    $this->user = User::factory()->create();
});

afterEach(function () {
    // Runs after each test
});

beforeAll(function () {
    // Runs once before all tests
});

afterAll(function () {
    // Runs once after all tests
});
```

## Mocking

### Mocking Services

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInvitation;

test('inviting a user sends an invitation email', function () {
    Mail::fake();

    $convention = Convention::factory()->create();
    $owner = User::factory()->create();
    $convention->users()->attach($owner, ['role' => 'Owner']);

    $this->actingAs($owner)->post(route('conventions.users.store', $convention), [
        'first_name' => 'Invited',
        'last_name'  => 'User',
        'email'      => 'invited@example.com',
        'mobile'     => '+1234567890',
        'roles'      => ['SectionUser'],
    ]);

    Mail::assertSent(UserInvitation::class, fn ($mail) => $mail->hasTo('invited@example.com'));
});
```

### Mocking Events

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestConventionVerification;

test('guest convention creation sends verification email to new user', function () {
    Mail::fake();

    $this->post('/conventions/guest', [
        'first_name' => 'New',
        'last_name'  => 'User',
        'email'      => 'new@example.com',
        'mobile'     => '+1234567890',
        'name'       => 'Test Convention',
        'start_date' => '2026-06-01',
        'end_date'   => '2026-06-03',
    ]);

    Mail::assertSent(GuestConventionVerification::class);
});
```

## Property-Based Tests

Property-based tests validate formal correctness properties of the system. Each test runs multiple iterations with varied inputs to ensure invariants hold across a wide range of scenarios.

### Running Property Tests

```bash
# Run all property tests
php artisan test tests/Property

# Run a specific property test
php artisan test tests/Property/InvitationEmailDeliveryTest.php

# Run by group
php artisan test --group=property
```

### Test Coverage

| Test File | Properties Validated |
|-----------|---------------------|
| `Property/ConventionPropertiesTest` | Date overlap detection, creator role assignment |
| `Property/UserPropertiesTest` | Email uniqueness, user field validation |
| `Property/OccupancyPropertiesTest` | Available seats calculation, occupancy snapping to dropdown values |
| `Property/AttendancePropertiesTest` | Max 2 reports per day, update restrictions before/after locking |
| `Property/AttendanceCalculationsTest` | Attendance arithmetic invariants |
| `Property/InvitationEmailDeliveryTest` | Invitation email delivery via signed URL |
| `Property/EmailUpdateConfirmationTest` | Email update triggers re-confirmation |
| `Property/RoleBasedDataScopingTest` | Role-based query scoping across all four roles |
| `Property/FloorUserPermissionsTest` | FloorUser permission enforcement |
| `Property/SectionUserRestrictionsTest` | SectionUser edit and scope restrictions |
| `Property/SectionCrudPropertyTest` | Section create/update/delete persistence |
| `Property/SectionFrontendPropertyTest` | Button visibility by role, floor selector, section display data |
| `Property/OccupancyColorCodingTest` | Color coding thresholds across full occupancy range |
| `Property/DailyOccupancyResetTest` | Reset command restores seats and clears metadata |
| `Property/SectionValidationPropertyTest` | Section field validation rules |
| `Feature/Properties/*` | Feature-level property tests (CSRF, role access, guest convention) |
| `Feature/Integration/*` | End-to-end flows (complete user flows, mobile responsiveness, performance, security audit) |

### Writing Property Tests

Property tests use iteration loops with varied inputs to verify invariants:

```php
it('sends exactly one invitation email for any valid user data', function () {
    for ($i = 0; $i < 50; $i++) {
        Mail::fake();

        $convention = Convention::factory()->create();
        $userData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => 'test-'.$i.'@example.com',
            'mobile' => fake()->phoneNumber(),
            'roles' => [fake()->randomElement(['ConventionUser', 'Owner'])],
        ];

        $user = (new InviteUserAction)->execute($userData, $convention);

        // Assert the property holds for every iteration
        Mail::assertSentCount(1);
        Mail::assertSent(UserInvitation::class, fn ($mail) => $mail->hasTo($userData['email']));

        // Cleanup
        $convention->delete();
        $user->delete();
    }
})->group('property', 'email');
```

Key patterns:
- Use `->group('property')` to tag all property tests
- Run multiple iterations with varied inputs (iteration count varies per test based on complexity and cost)
- Use Pest's built-in expectation matchers like `toThrow()` for cleaner assertions
- Assert invariants hold on every iteration
- Clean up state between iterations

## Frontend Testing (Vitest + React Testing Library)

### Configuration

Frontend tests are configured in `vitest.config.ts`:

- **Environment**: jsdom (browser-like DOM for component rendering)
- **Globals**: `describe`, `it`, `expect` available without imports
- **Setup**: `resources/js/test/setup.ts` loads jest-dom matchers
- **Path alias**: `@` resolves to `resources/js/`

### Writing Component Tests

Place test files in `__tests__/` directories alongside the components they test:

```tsx
// resources/js/components/conventions/__tests__/my-component.test.tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MyComponent } from '../my-component';

describe('MyComponent', () => {
    it('renders the title', () => {
        render(<MyComponent title="Hello" />);
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });

    it('handles click events', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();

        render(<MyComponent title="Click me" onClick={onClick} />);
        await user.click(screen.getByRole('button'));

        expect(onClick).toHaveBeenCalledOnce();
    });
});
```

### Frontend Property Tests (fast-check)

The project includes `fast-check` for property-based testing on the frontend:

```tsx
import fc from 'fast-check';

describe('occupancy color', () => {
    it('returns green for 0-25%', () => {
        fc.assert(
            fc.property(fc.integer({ min: 0, max: 25 }), (occupancy) => {
                expect(getOccupancyColor(occupancy)).toBe('green');
            }),
        );
    });
});
```

### Key Testing Libraries

| Package | Purpose |
|---------|---------|
| `vitest` | Test runner and assertion library |
| `@testing-library/react` | Component rendering and queries |
| `@testing-library/user-event` | Simulating user interactions |
| `@testing-library/jest-dom` | DOM-specific matchers (`toBeInTheDocument`, etc.) |
| `fast-check` | Property-based testing for frontend logic |
| `jsdom` | Browser-like DOM environment |

## Best Practices

### 1. Use Descriptive Test Names

```php
// ✅ Good
test('user cannot update profile with invalid email', function () {
    // ...
});

// ❌ Bad
test('profile update fails', function () {
    // ...
});
```

### 2. Arrange, Act, Assert

```php
test('user can update profile', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $response = $this->actingAs($user)
        ->put('/settings/profile', [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $user->email,
        ]);

    // Assert
    $response->assertRedirect();
    expect($user->fresh()->first_name)->toBe('New');
    expect($user->fresh()->last_name)->toBe('Name');
});
```

### 3. Test One Thing Per Test

```php
// ✅ Good - Tests one behavior
test('guest convention creation requires email', function () {
    $this->post('/conventions/guest', [
        'first_name' => 'Test',
        'last_name'  => 'User',
        'mobile'     => '+1234567890',
        'name'       => 'My Convention',
        'start_date' => '2026-06-01',
        'end_date'   => '2026-06-03',
        // email intentionally omitted
    ])->assertSessionHasErrors('email');
});

// ❌ Bad - Tests multiple behaviors
test('guest convention validation', function () {
    // Tests email, name, dates all in one test
});
```

### 4. Use Factories

```php
// ✅ Good
$user = User::factory()->create();

// ❌ Bad
$user = User::create([
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
```

### 5. Clean Up After Tests

```php
// ✅ Good - Uses database transactions (automatic)
uses(RefreshDatabase::class);

test('creates user', function () {
    User::factory()->create();
}); // Automatically rolled back
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: composer test
```

### Running CI Checks Locally

```bash
composer ci:check
```

This runs:
- PHP linting
- JS/TS linting
- Code formatting checks
- Type checking
- All tests

## Troubleshooting

### Tests Failing Randomly

```bash
# Run tests in order
php artisan test --order-by=default

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Database Issues

```bash
# Refresh database
php artisan migrate:fresh

# Check database connection
php artisan db
```

### Slow Tests

```bash
# Run tests in parallel
php artisan test --parallel

# Profile tests
php artisan test --profile
```

## Next Steps

- Read [Development Guide](DEVELOPMENT.md)
- Learn about [Authentication](AUTHENTICATION.md)
- Review [Deployment Guide](DEPLOYMENT.md)
