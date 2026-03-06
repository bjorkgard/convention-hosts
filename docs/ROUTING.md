# Type-Safe Routing with Wayfinder

This guide covers Laravel Wayfinder, which provides end-to-end type safety between your Laravel backend routes and React frontend.

## Overview

Laravel Wayfinder generates TypeScript definitions from your Laravel routes, ensuring:

- Type-safe route names
- Type-safe route parameters
- Autocomplete for routes in your IDE
- Compile-time errors for invalid routes
- No runtime errors from typos

## How It Works

1. Define routes in Laravel (PHP)
2. Run `php artisan wayfinder:generate`
3. TypeScript definitions generated automatically
4. Use type-safe routes in React components

## Basic Usage

### Backend Route Definition

```php
// routes/web.php
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->name('posts.show');
```

### Frontend Usage

```tsx
import { route } from '@/routes';

// Type-safe route generation
const url = route('posts.show', { post: 123 });
// Result: "/posts/123"

// TypeScript error if route doesn't exist
const invalid = route('posts.invalid'); // ❌ Error

// TypeScript error if parameters are missing
const missing = route('posts.show'); // ❌ Error: missing 'post' parameter
```

## Route Parameters

### Required Parameters

```php
// Backend
Route::get('/users/{user}/posts/{post}', [PostController::class, 'show'])
    ->name('users.posts.show');
```

```tsx
// Frontend - All parameters required
route('users.posts.show', {
    user: 1,
    post: 123,
});
// Result: "/users/1/posts/123"
```

### Optional Parameters

```php
// Backend
Route::get('/posts/{post?}', [PostController::class, 'index'])
    ->name('posts.index');
```

```tsx
// Frontend - Parameter is optional
route('posts.index'); // "/posts"
route('posts.index', { post: 123 }); // "/posts/123"
```

### Query Parameters

```tsx
// Add query parameters
route('posts.index', {}, {
    page: 2,
    sort: 'date',
});
// Result: "/posts?page=2&sort=date"

// With route parameters and query parameters
route('posts.show', { post: 123 }, {
    comment: 456,
});
// Result: "/posts/123?comment=456"
```

## Using Routes in Components

### Link Component

```tsx
import { Link } from '@inertiajs/react';
import { route } from '@/routes';

export function PostLink({ postId }: { postId: number }) {
    return (
        <Link href={route('posts.show', { post: postId })}>
            View Post
        </Link>
    );
}
```

### Navigation

```tsx
import { router } from '@inertiajs/react';
import { route } from '@/routes';

export function NavigateButton() {
    const navigate = () => {
        router.visit(route('conventions.index'));
    };

    return <button onClick={navigate}>Go to Conventions</button>;
}
```

### Form Submission

```tsx
import { useForm } from '@inertiajs/react';
import { route } from '@/routes';

export function PostForm() {
    const { data, setData, post, processing } = useForm({
        title: '',
        content: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('posts.store'));
    };

    return <form onSubmit={submit}>...</form>;
}
```

## Wayfinder Actions

Wayfinder also generates action classes that mirror your controllers:

```tsx
// Auto-generated action
import { PostController } from '@/actions/App/Http/Controllers';

// Use action methods
const showUrl = PostController.show({ post: 123 });
const indexUrl = PostController.index();
```

### Action Benefits

- Organized by controller structure
- Same type safety as route helper
- Mirrors backend architecture
- Better IDE navigation

## Route Groups

### Named Route Groups

```php
// Backend
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])
        ->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])
        ->name('users');
});
```

```tsx
// Frontend
route('admin.dashboard'); // "/admin/dashboard"
route('admin.users'); // "/admin/users"
```

### Middleware Groups

```php
// Backend
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('conventions', [ConventionController::class, 'index'])
        ->name('conventions.index');
});
```

Middleware doesn't affect frontend routing, but routes are still type-safe.

## Current Route Detection

Check if a route is currently active:

```tsx
import { usePage } from '@inertiajs/react';
import { route } from '@/routes';

export function Navigation() {
    const { url } = usePage();

    const isActive = (routeName: string) => {
        return url === route(routeName);
    };

    return (
        <nav>
            <Link
                href={route('conventions.index')}
                className={isActive('conventions.index') ? 'active' : ''}
            >
                Conventions
            </Link>
        </nav>
    );
}
```

### Using Route Patterns

```tsx
export function Navigation() {
    const { url } = usePage();

    const isActivePattern = (pattern: string) => {
        return url.startsWith(pattern);
    };

    return (
        <nav>
            <Link
                href={route('settings.profile')}
                className={isActivePattern('/settings') ? 'active' : ''}
            >
                Settings
            </Link>
        </nav>
    );
}
```

## Generating Routes

### Manual Generation

```bash
php artisan wayfinder:generate
```

### Automatic Generation

Wayfinder automatically generates routes when:
- Running `npm run dev`
- Running `npm run build`
- Routes are modified (in watch mode)

### Watch Mode

```bash
php artisan wayfinder:generate --watch
```

This regenerates routes automatically when route files change.

## Configuration

### Wayfinder Config

```typescript
// resources/js/wayfinder/config.ts
export default {
    // Configuration options
};
```

### Vite Plugin

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import wayfinder from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
        }),
        wayfinder(),
    ],
});
```

## Generated Files

Wayfinder generates these files (do not edit manually):

```
resources/js/
├── actions/          # Controller action classes
│   └── App/
│       └── Http/
│           └── Controllers/
│               └── ...
└── routes/           # Route definitions
    └── index.ts
```

These files are automatically regenerated when routes change.

## Advanced Usage

### Route Model Binding

```php
// Backend
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->name('posts.show');

// Controller
public function show(Post $post)
{
    // $post is automatically resolved
}
```

```tsx
// Frontend - Just pass the ID
route('posts.show', { post: post.id });
```

### Resource Routes

```php
// Backend
Route::resource('posts', PostController::class);
```

```tsx
// Frontend - All resource routes available
route('posts.index');           // GET /posts
route('posts.create');          // GET /posts/create
route('posts.store');           // POST /posts
route('posts.show', { post: 1 }); // GET /posts/1
route('posts.edit', { post: 1 }); // GET /posts/1/edit
route('posts.update', { post: 1 }); // PUT /posts/1
route('posts.destroy', { post: 1 }); // DELETE /posts/1
```

### Nested Resources

```php
// Backend
Route::resource('posts.comments', CommentController::class);
```

```tsx
// Frontend
route('posts.comments.index', { post: 1 });
route('posts.comments.store', { post: 1 });
route('posts.comments.show', { post: 1, comment: 2 });
```

## Type Safety Examples

### Correct Usage ✅

```tsx
// All parameters provided
route('posts.show', { post: 123 });

// Optional parameter omitted
route('posts.index');

// Query parameters added
route('posts.index', {}, { page: 2 });
```

### TypeScript Errors ❌

```tsx
// Missing required parameter
route('posts.show');
// Error: Argument of type '{}' is not assignable to parameter

// Invalid route name
route('posts.invalid');
// Error: Argument of type '"posts.invalid"' is not assignable

// Wrong parameter name
route('posts.show', { id: 123 });
// Error: Object literal may only specify known properties

// Wrong parameter type
route('posts.show', { post: '123' });
// Error: Type 'string' is not assignable to type 'number'
```

## Best Practices

### 1. Always Name Your Routes

```php
// ✅ Good - Named route
Route::get('/posts', [PostController::class, 'index'])
    ->name('posts.index');

// ❌ Bad - Unnamed route (not available in frontend)
Route::get('/posts', [PostController::class, 'index']);
```

### 2. Use Consistent Naming

```php
// ✅ Good - RESTful naming
Route::name('posts.')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/{post}', [PostController::class, 'show'])->name('show');
});

// ❌ Bad - Inconsistent naming
Route::get('/posts', [PostController::class, 'index'])->name('all-posts');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('viewPost');
```

### 3. Regenerate After Route Changes

```bash
# After modifying routes
php artisan wayfinder:generate
```

### 4. Use Actions for Organization

```tsx
// ✅ Good - Organized by controller
import { PostController } from '@/actions/App/Http/Controllers';
const url = PostController.show({ post: 123 });

// ✅ Also good - Direct route helper
import { route } from '@/routes';
const url = route('posts.show', { post: 123 });
```

### 5. Type Your Route Parameters

```tsx
interface PostRouteParams {
    post: number;
}

function navigateToPost(params: PostRouteParams) {
    router.visit(route('posts.show', params));
}
```

## Troubleshooting

### Routes Not Updating

```bash
# Clear route cache
php artisan route:clear

# Regenerate Wayfinder routes
php artisan wayfinder:generate

# Restart Vite dev server
npm run dev
```

### TypeScript Errors

```bash
# Check TypeScript
npm run types:check

# Regenerate routes
php artisan wayfinder:generate
```

### Missing Routes

Ensure routes are named:

```php
// ❌ Won't appear in Wayfinder
Route::get('/posts', [PostController::class, 'index']);

// ✅ Will appear in Wayfinder
Route::get('/posts', [PostController::class, 'index'])
    ->name('posts.index');
```

## Comparison with Traditional Routing

### Without Wayfinder

```tsx
// ❌ No type safety
<Link href="/posts/123">View Post</Link>

// ❌ Typos cause runtime errors
<Link href="/pots/123">View Post</Link>

// ❌ No autocomplete
<Link href="/posts/123">View Post</Link>
```

### With Wayfinder

```tsx
// ✅ Type-safe
<Link href={route('posts.show', { post: 123 })}>View Post</Link>

// ✅ Compile-time error
<Link href={route('pots.show', { post: 123 })}>View Post</Link>
//                  ^^^^^^^^^^^ Error: Route doesn't exist

// ✅ Full autocomplete
<Link href={route('posts.show', { post: 123 })}>View Post</Link>
//                  ^^^^^^^^^^^ IDE suggests all available routes
```

## Next Steps

- Read [Development Guide](DEVELOPMENT.md) for workflow
- Learn about [Authentication](AUTHENTICATION.md)
- Review [Architecture Overview](ARCHITECTURE.md)
