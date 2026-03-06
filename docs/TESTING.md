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
├── Feature/                   # Feature tests (HTTP, integration)
│   ├── Auth/                 # Authentication tests
│   ├── Settings/             # Settings tests
│   ├── CsrfProtectionTest.php
│   └── ...
├── Property/                  # Property-based tests (correctness properties)
│   ├── AttendancePropertiesTest.php
│   ├── ConventionPropertiesTest.php
│   ├── EmailUpdateConfirmationTest.php
│   ├── FloorUserPermissionsTest.php
│   ├── InvitationEmailDeliveryTest.php
│   ├── OccupancyPropertiesTest.php
│   ├── RoleBasedDataScopingTest.php
│   ├── SectionUserRestrictionsTest.php
│   └── UserPropertiesTest.php
├── Unit/                      # Unit tests (with database support via RefreshDatabase)
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

test('user can view dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
```

### Using `it()` for Readability

```php
it('redirects guests to login', function () {
    $this->get('/dashboard')
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

### Registration

```php
<?php

use App\Models\User;

test('user can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile' => '+1234567890',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();
    
    // Verify user was created in database
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
});

test('registration requires valid email', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'invalid-email',
        'mobile' => '+1234567890',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration requires password confirmation', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile' => '+1234567890',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPass123!',
    ]);

    $response->assertSessionHasErrors('password');
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

    $response->assertRedirect('/dashboard');
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
            'name' => 'Updated Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('user cannot update email to existing email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
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
test('dashboard returns inertia response', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('user')
        );
});

test('profile page receives user data', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertInertia(fn ($page) => $page
            ->component('settings/profile')
            ->where('user.name', 'Test User')
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
        'name' => 'Custom Name',
    ]);

    expect($user->name)->toBe('Custom Name');
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
$response->assertRedirect('/dashboard');
$response->assertRedirectToRoute('dashboard');

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
use App\Mail\WelcomeEmail;

test('registration sends welcome email', function () {
    Mail::fake();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Mail::assertSent(WelcomeEmail::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});
```

### Mocking Events

```php
use Illuminate\Support\Facades\Event;
use App\Events\UserRegistered;

test('registration dispatches event', function () {
    Event::fake();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Event::assertDispatched(UserRegistered::class);
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

| Test File | Properties Validated | Requirements |
|-----------|---------------------|--------------|
| `ConventionPropertiesTest` | Date overlap detection, creator role assignment | 1.3, 1.4 |
| `UserPropertiesTest` | Email domain restriction, user deduplication | 4.2, 4.3 |
| `OccupancyPropertiesTest` | Available seats calculation, color coding | 7.7, 9.x |
| `AttendancePropertiesTest` | Max reports per day, update restrictions | 10.6, 11.5, 11.6 |
| `InvitationEmailDeliveryTest` | Invitation email delivery via signed URL | 3.1, 3.2 |
| `EmailUpdateConfirmationTest` | Email update triggers confirmation | 3.5 |
| `RoleBasedDataScopingTest` | Role-based query scoping | 5.5, 5.6, 5.7 |
| `FloorUserPermissionsTest` | FloorUser permission enforcement | 13.1, 13.2, 13.3 |
| `SectionUserRestrictionsTest` | SectionUser edit and scope restrictions | 14.2, 14.3 |
| `CsrfProtectionTest` | CSRF token enforcement on all state-changing routes | 21.3 |

### Writing Property Tests

Property tests use iteration loops with randomized data to verify invariants:

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
- Run multiple iterations (typically 50) with randomized inputs
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
            'name' => 'New Name',
            'email' => $user->email,
        ]);

    // Assert
    $response->assertRedirect();
    expect($user->fresh()->name)->toBe('New Name');
});
```

### 3. Test One Thing Per Test

```php
// ✅ Good - Tests one behavior
test('registration requires email', function () {
    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'mobile' => '+1234567890',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');
});

// ❌ Bad - Tests multiple behaviors
test('registration validation', function () {
    // Tests email, password, name all in one test
});
```

### 4. Use Factories

```php
// ✅ Good
$user = User::factory()->create();

// ❌ Bad
$user = User::create([
    'name' => 'Test',
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
