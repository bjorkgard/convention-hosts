# Architecture Overview

This document provides a comprehensive overview of the Laravel React Starter Kit architecture, design patterns, and conventions.

## Technology Stack

### Backend Stack

- **PHP 8.2+** - Modern PHP with typed properties, attributes, and enums
- **Laravel 12** - Latest Laravel framework with improved performance
- **Laravel Fortify** - Authentication backend without UI
- **Laravel Wayfinder** - Type-safe routing between backend and frontend
- **Inertia.js** - Glues Laravel and React together
- **SQLite** - Default database for development

### Frontend Stack

- **React 19** - Latest React with compiler optimizations
- **TypeScript** - Full type safety across the frontend
- **Vite** - Fast build tool with HMR
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **Headless UI** - Additional unstyled components
- **Lucide React** - Icon library

## Project Structure

```
laravel-react-starter-kit/
├── app/                           # Laravel Application
│   ├── Actions/                   # Business Logic Actions
│   │   └── Fortify/              # Fortify action implementations
│   ├── Concerns/                  # Reusable Traits
│   ├── Http/
│   │   ├── Controllers/          # HTTP Controllers
│   │   │   └── Settings/         # Settings feature controllers
│   │   ├── Middleware/           # Custom Middleware
│   │   └── Requests/             # Form Request Validation
│   │       └── Settings/         # Settings feature requests
│   ├── Models/                    # Eloquent Models
│   └── Providers/                 # Service Providers
│
├── bootstrap/                     # Laravel Bootstrap
│   ├── app.php                   # Application bootstrap
│   ├── cache/                    # Framework cache
│   └── providers.php             # Provider registration
│
├── config/                        # Configuration Files
│   ├── app.php                   # Application config
│   ├── auth.php                  # Authentication config
│   ├── fortify.php               # Fortify config
│   └── inertia.php               # Inertia config
│
├── database/                      # Database Files
│   ├── factories/                # Model Factories
│   ├── migrations/               # Database Migrations
│   └── seeders/                  # Database Seeders
│
├── public/                        # Public Assets
│   ├── build/                    # Compiled Assets (generated)
│   └── index.php                 # Application Entry Point
│
├── resources/                     # Frontend Resources
│   ├── css/
│   │   └── app.css               # Tailwind Entry Point
│   └── js/
│       ├── actions/              # Wayfinder Actions (generated)
│       ├── components/           # React Components
│       │   ├── ui/               # Base UI Components
│       │   └── ...               # Feature Components
│       ├── hooks/                # Custom React Hooks
│       ├── layouts/              # Layout Components
│       ├── lib/                  # Utility Functions
│       ├── pages/                # Inertia Pages
│       │   ├── auth/             # Authentication Pages
│       │   ├── settings/         # Settings Pages
│       │   ├── dashboard.tsx     # Dashboard Page
│       │   └── welcome.tsx       # Landing Page
│       ├── routes/               # Wayfinder Routes (generated)
│       ├── types/                # TypeScript Types
│       ├── app.tsx               # Client Entry Point
│       └── ssr.tsx               # SSR Entry Point
│
├── routes/                        # Route Definitions
│   ├── web.php                   # Web Routes
│   ├── settings.php              # Settings Routes
│   └── console.php               # Console Commands
│
├── storage/                       # Storage Directory
│   ├── app/                      # Application Storage
│   ├── framework/                # Framework Storage
│   └── logs/                     # Application Logs
│
└── tests/                         # Tests
    ├── Feature/                  # Feature Tests
    └── Unit/                     # Unit Tests
```

## Architecture Patterns

### Backend Architecture

#### MVC Pattern

The application follows Laravel's MVC pattern:

- **Models** (`app/Models/`) - Data layer, Eloquent ORM
- **Views** - Replaced by Inertia.js React components
- **Controllers** (`app/Http/Controllers/`) - Handle HTTP requests

#### Action Pattern

Complex business logic is extracted into Action classes:

```php
// app/Actions/Fortify/CreateNewUser.php
class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        // Validation and user creation logic
    }
}
```

Benefits:
- Single responsibility
- Reusable across controllers
- Easier to test

#### Form Request Validation

Validation logic is encapsulated in Form Request classes:

```php
// app/Http/Requests/Settings/ProfileUpdateRequest.php
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
```

#### Service Provider Pattern

Application bootstrapping and service registration:

```php
// app/Providers/FortifyServiceProvider.php
class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        // Configure Fortify features
    }
}
```

### Frontend Architecture

#### Component-Based Architecture

React components are organized by feature and reusability:

```
components/
├── ui/              # Base components (buttons, inputs, etc.)
├── app-shell.tsx    # Application shell
├── nav-main.tsx     # Main navigation
└── ...              # Feature-specific components
```

#### Page Components

Inertia pages map 1:1 with backend routes:

```tsx
// resources/js/pages/dashboard.tsx
export default function Dashboard() {
    return (
        <AppLayout>
            <h1>Dashboard</h1>
        </AppLayout>
    );
}
```

#### Layout Components

Layouts provide consistent structure:

```tsx
// resources/js/layouts/app-layout.tsx
export function AppLayout({ children }: PropsWithChildren) {
    return (
        <div>
            <Navigation />
            <main>{children}</main>
        </div>
    );
}
```

#### Custom Hooks

Reusable stateful logic:

```tsx
// resources/js/hooks/use-appearance.tsx
export function useAppearance() {
    const [theme, setTheme] = useState<'light' | 'dark'>('light');
    // Theme management logic
    return { theme, setTheme };
}
```

## Data Flow

### Request Flow

1. **User Action** - User interacts with React component
2. **Inertia Request** - Component makes Inertia request
3. **Laravel Route** - Request hits Laravel route
4. **Controller** - Controller processes request
5. **Action/Model** - Business logic executed
6. **Inertia Response** - Controller returns Inertia response
7. **React Update** - React component receives props and re-renders

### Type-Safe Routing with Wayfinder

```tsx
// Frontend - Type-safe route generation
import { route } from '@/routes';

// TypeScript knows available routes and parameters
<Link href={route('settings.profile')}>Profile</Link>
```

```php
// Backend - Route definition
Route::get('/settings/profile', [ProfileController::class, 'edit'])
    ->name('settings.profile');
```

Wayfinder generates TypeScript definitions from Laravel routes, ensuring type safety.

## Authentication System

### Fortify Integration

Laravel Fortify provides the authentication backend:

- User registration
- Login/logout
- Password reset
- Email verification
- Two-factor authentication

### Authentication Flow

1. **Frontend Form** - User submits credentials
2. **Inertia POST** - Form data sent to Fortify endpoint
3. **Fortify Action** - Fortify processes authentication
4. **Session Created** - Laravel session established
5. **Redirect** - User redirected to dashboard
6. **Authenticated State** - React receives authenticated user props

### Middleware Protection

Routes are protected using Laravel middleware:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## State Management

### Server State (Inertia)

Inertia manages server state through props:

```tsx
interface DashboardProps {
    user: User;
    stats: Stats;
}

export default function Dashboard({ user, stats }: DashboardProps) {
    // Props are automatically typed and reactive
}
```

### Client State (React)

Local component state using React hooks:

```tsx
const [isOpen, setIsOpen] = useState(false);
```

### Shared State (Context)

Global client state using React Context:

```tsx
// resources/js/hooks/use-appearance.tsx
const AppearanceContext = createContext<AppearanceContext | undefined>(undefined);

export function AppearanceProvider({ children }: PropsWithChildren) {
    // Shared theme state
}
```

## Database Design

### Users Table

```sql
users
├── id
├── name
├── email
├── email_verified_at
├── password
├── two_factor_secret
├── two_factor_recovery_codes
├── two_factor_confirmed_at
├── remember_token
├── created_at
└── updated_at
```

### Sessions Table

Laravel stores sessions in the database for better scalability.

## Security Considerations

### CSRF Protection

All POST requests include CSRF token automatically via Inertia.

### Password Hashing

Passwords are hashed using bcrypt with configurable rounds:

```env
BCRYPT_ROUNDS=12
```

### Two-Factor Authentication

TOTP-based 2FA with recovery codes for account recovery.

### Input Validation

All user input is validated using Form Requests before processing.

### SQL Injection Prevention

Eloquent ORM uses prepared statements, preventing SQL injection.

## Performance Optimizations

### Frontend

- **Code Splitting** - Automatic route-based code splitting
- **React Compiler** - Automatic memoization
- **Vite** - Fast HMR and optimized builds
- **Lazy Loading** - Components loaded on demand

### Backend

- **Query Optimization** - Eager loading to prevent N+1 queries
- **Caching** - Configuration, routes, and views cached in production
- **Queue System** - Background job processing
- **Database Indexing** - Proper indexes on frequently queried columns

## Testing Strategy

### Backend Tests (Pest)

```php
test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->name)->toBe('New Name');
});
```

### Frontend Tests

TypeScript provides compile-time type checking, catching many errors before runtime.

## Deployment Architecture

### Production Stack

- **Web Server** - Nginx or Apache
- **PHP** - PHP-FPM
- **Database** - MySQL or PostgreSQL
- **Cache** - Redis
- **Queue** - Redis or database
- **Assets** - CDN for static assets

See [Deployment Guide](DEPLOYMENT.md) for detailed deployment instructions.

## Conventions

### Naming Conventions

- **Controllers** - Singular, `ProfileController`
- **Models** - Singular, `User`
- **Tables** - Plural, `users`
- **Routes** - Kebab-case, `settings.two-factor`
- **Components** - PascalCase, `AppShell`
- **Files** - Kebab-case, `app-shell.tsx`

### Code Organization

- Group by feature, not by type
- Keep related files close together
- Use index files for clean imports
- Separate concerns (UI, logic, types)

### Type Safety

- Use TypeScript strict mode
- Define interfaces for all props
- Avoid `any` type
- Use Wayfinder for route type safety

## Next Steps

- Learn about [Type-Safe Routing](ROUTING.md)
- Understand [Authentication System](AUTHENTICATION.md)
- Read [Development Guide](DEVELOPMENT.md)
- Review [Testing Guide](TESTING.md)
