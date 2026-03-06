# Authentication Guide

This guide covers the authentication system built with Laravel Fortify and Inertia.js.

## Overview

The Convention Management System includes a complete authentication system with:

- Login/logout
- Password reset via email
- Email verification
- Two-factor authentication (2FA)
- Remember me functionality
- Password confirmation for sensitive actions
- Invitation-based user onboarding (no self-registration)

## Authentication Flow

### Guest Convention Creation

Users can create a convention without registering first. The flow differs depending on whether the email already exists in the system.

**Existing user:**
1. Guest submits convention form with name, email, and convention details at `POST /conventions/guest`
2. System finds the existing user by email
3. Convention created with user as Owner and ConventionUser
4. User logged in via `Auth::login()`
5. Redirected to convention detail page

**New user:**
1. Guest submits convention form at `POST /conventions/guest`
2. System creates a new user account with a random password and `email_confirmed` = false
3. Convention created with user as Owner and ConventionUser
4. A verification email is sent containing a signed URL (24h expiry) to set a password
5. User is redirected to a confirmation page (not logged in) showing the convention name and instructions to check their email
6. User clicks the email link, sets a password on the set-password page
7. System saves the password, sets `email_confirmed` = true, logs the user in, and redirects to the convention detail page

If the signed URL is expired or tampered with, an error page is shown with a link to the home page.

### User Onboarding

There is no public self-registration. Users join the system through one of two paths:

1. **Invitation** — A convention manager invites the user via email. The invitation contains a signed link to set a password and activate the account.
2. **Guest convention creation** — An unauthenticated user creates a convention from the welcome page. Existing users are logged in automatically. New users receive a verification email with a signed link to set their password before gaining access.

### Login

1. User submits credentials at `/login`
2. Fortify authenticates user
3. Session created
4. Smart redirect based on convention membership:
   - If the user belongs to exactly one convention, they are redirected directly to that convention's detail page
   - Otherwise, they are redirected to the conventions list

The login page uses Inertia's `<Form>` component with Wayfinder type-safe routing. It includes email, password, and "remember me" fields. There is no link to a registration page — users are onboarded via invitation or guest convention creation.

### Logout

```tsx
import { route } from '@/routes';

<button onClick={() => router.post(route('logout'))}>
    Logout
</button>
```

### Password Reset

1. User requests reset at `/forgot-password`
2. Reset link sent to email
3. User clicks link and sets new password at `/reset-password`
4. Password updated and user logged in

**Request Reset:**
```tsx
// resources/js/pages/auth/forgot-password.tsx
export default function ForgotPassword() {
    const { data, setData, post, processing } = useForm({
        email: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return <form onSubmit={submit}>...</form>;
}
```

**Reset Password:**
```tsx
// resources/js/pages/auth/reset-password.tsx
export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('password.update'));
    };

    return <form onSubmit={submit}>...</form>;
}
```

## Email Verification

### Configuration

Enable email verification in `config/fortify.php`:

```php
'features' => [
    Features::emailVerification(),
],
```

### Protecting Routes

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/conventions', [ConventionController::class, 'index']);
});
```

### Verification Flow

1. User registers
2. Verification email sent automatically
3. User clicks verification link
4. Email marked as verified
5. User can access protected routes

**Verification Notice:**
```tsx
// resources/js/pages/auth/verify-email.tsx
export default function VerifyEmail() {
    const { post, processing } = useForm({});

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <div>
            <p>Please verify your email address.</p>
            <form onSubmit={submit}>
                <button type="submit" disabled={processing}>
                    Resend Verification Email
                </button>
            </form>
        </div>
    );
}
```

## Two-Factor Authentication

### Enabling 2FA

1. User navigates to `/settings/two-factor`
2. Clicks "Enable Two-Factor Authentication"
3. QR code displayed
4. User scans with authenticator app
5. User confirms with code
6. Recovery codes generated

**Frontend Component:**
```tsx
// resources/js/pages/settings/two-factor.tsx
export default function TwoFactor() {
    const { twoFactorEnabled, qrCode, recoveryCodes } = usePage().props;

    const enable = () => {
        router.post(route('two-factor.enable'));
    };

    const confirm = (code: string) => {
        router.post(route('two-factor.confirm'), { code });
    };

    return (
        <div>
            {!twoFactorEnabled ? (
                <button onClick={enable}>Enable 2FA</button>
            ) : (
                <>
                    <div dangerouslySetInnerHTML={{ __html: qrCode }} />
                    <RecoveryCodes codes={recoveryCodes} />
                </>
            )}
        </div>
    );
}
```

**Backend Controller:**
```php
// app/Http/Controllers/Settings/TwoFactorAuthenticationController.php
class TwoFactorAuthenticationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->user()->enableTwoFactorAuthentication();

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->disableTwoFactorAuthentication();

        return back();
    }
}
```

### 2FA Login Flow

1. User enters email and password
2. If 2FA enabled, redirected to `/two-factor-challenge`
3. User enters code from authenticator app
4. Authenticated and redirected to conventions list

**Challenge Component:**
```tsx
// resources/js/pages/auth/two-factor-challenge.tsx
export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('two-factor.login'));
    };

    return (
        <form onSubmit={submit}>
            {!recovery ? (
                <input
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    placeholder="Authentication code"
                />
            ) : (
                <input
                    value={data.recovery_code}
                    onChange={(e) => setData('recovery_code', e.target.value)}
                    placeholder="Recovery code"
                />
            )}
            <button type="button" onClick={() => setRecovery(!recovery)}>
                {recovery ? 'Use authentication code' : 'Use recovery code'}
            </button>
            <button type="submit" disabled={processing}>
                Login
            </button>
        </form>
    );
}
```

### Recovery Codes

Recovery codes allow access if authenticator app is unavailable:

```tsx
// resources/js/components/recovery-codes.tsx
export function RecoveryCodes({ codes }: { codes: string[] }) {
    return (
        <div>
            <h3>Recovery Codes</h3>
            <p>Store these codes in a safe place.</p>
            <ul>
                {codes.map((code) => (
                    <li key={code}>{code}</li>
                ))}
            </ul>
        </div>
    );
}
```

**Regenerate Recovery Codes:**
```tsx
const regenerate = () => {
    router.post(route('two-factor.recovery-codes'));
};
```

## Password Confirmation

Sensitive actions require password confirmation:

```php
Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::delete('/settings/profile', [ProfileController::class, 'destroy']);
});
```

**Confirmation Page:**
```tsx
// resources/js/pages/auth/confirm-password.tsx
export default function ConfirmPassword() {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('password.confirm'));
    };

    return (
        <form onSubmit={submit}>
            <p>Please confirm your password to continue.</p>
            <input
                type="password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
            />
            <button type="submit" disabled={processing}>
                Confirm
            </button>
        </form>
    );
}
```

## User Profile Management

### Update Profile

```tsx
// resources/js/pages/settings/profile.tsx
export default function Profile({ user }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('settings.profile.update'));
    };

    return <form onSubmit={submit}>...</form>;
}
```

**Backend Controller:**
```php
// app/Http/Controllers/Settings/ProfileController.php
class ProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back();
    }
}
```

**Profile Validation Rules:**
```php
// app/Concerns/ProfileValidationRules.php
trait ProfileValidationRules
{
    protected function profileRules(?int $userId = null): array
    {
        return [
            'first_name' => $this->firstNameRules(),
            'last_name' => $this->lastNameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    protected function firstNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    protected function lastNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }
}
```

### Update Password

```tsx
// resources/js/pages/settings/password.tsx
export default function Password() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('settings.password.update'), {
            onSuccess: () => reset(),
        });
    };

    return <form onSubmit={submit}>...</form>;
}
```

### Delete Account

```tsx
const deleteAccount = () => {
    if (confirm('Are you sure? This action cannot be undone.')) {
        router.delete(route('settings.profile.destroy'));
    }
};
```

## Middleware

### Available Middleware

- `auth` - Requires authentication
- `guest` - Only for guests (not authenticated)
- `verified` - Requires verified email
- `password.confirm` - Requires password confirmation

### Usage

```php
// Single middleware
Route::middleware('auth')->group(function () {
    // Protected routes
});

// Multiple middleware
Route::middleware(['auth', 'verified'])->group(function () {
    // Protected routes requiring verified email
});
```

## Customization

### Password Rules

Customize password requirements in `app/Concerns/PasswordValidationRules.php`:

```php
trait PasswordValidationRules
{
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'confirmed',
            // Add custom rules
        ];
    }
}
```

### Fortify Configuration

Configure features in `config/fortify.php`:

```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

### Redirects

Login redirects are handled by a custom `LoginResponse` class bound in `FortifyServiceProvider`:

```php
// app/Http/Responses/LoginResponse.php
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $conventions = $user->conventions()->pluck('conventions.id');

        $redirectTo = $conventions->count() === 1
            ? route('conventions.show', $conventions->first())
            : config('fortify.home', '/conventions');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectTo);
    }
}
```

Users with a single convention skip the list and land directly on their convention page. Users with zero or multiple conventions go to the conventions index.

Customize views in `app/Providers/FortifyServiceProvider.php`:

```php
Fortify::loginView(fn () => inertia('auth/login'));
Fortify::registerView(fn () => inertia('auth/register'));
```

## Security Best Practices

1. **Use HTTPS** in production
2. **Enable 2FA** for admin accounts
3. **Rate limit** authentication endpoints
4. **Hash passwords** with bcrypt (default)
5. **Validate input** using Form Requests
6. **Protect sensitive actions** with password confirmation
7. **Log authentication events** for audit trail

## Testing Authentication

```php
// Test login
test('user can login', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/conventions');
    $this->assertAuthenticatedAs($user);
});

// Test 2FA
test('user can enable two factor authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/user/two-factor-authentication')
        ->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});
```

## Troubleshooting

### Session Issues

```bash
# Clear sessions
php artisan session:flush

# Check session configuration
php artisan config:show session
```

### Email Not Sending

Check mail configuration in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
```

### 2FA Not Working

Ensure time is synchronized on server and client device.

## Next Steps

- Learn about [Type-Safe Routing](ROUTING.md)
- Read [Development Guide](DEVELOPMENT.md)
- Review [Testing Guide](TESTING.md)
