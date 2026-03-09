# Sonner Toast Feedback Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Show Sonner toast notifications when users update occupancy (%, Full, available seats) or submit an attendance report.

**Architecture:** Flash messages from Laravel controllers are shared globally via `HandleInertiaRequests::share()`. A `useFlashToast` hook watches the Inertia page props and fires `toast.success()`/`toast.error()` on every navigation. A `<Toaster>` in the root layout renders the toasts. `sections/show.tsx` mounts the hook.

**Tech Stack:** sonner (new), Laravel flash session, Inertia.js shared props, React hook, Tailwind CSS v4, Vitest

---

### Task 1: Install sonner and create the UI wrapper

**Files:**
- Modify: `package.json` (via npm)
- Create: `resources/js/components/ui/sonner.tsx`

**Step 1: Install the package**

```bash
npm install sonner
```

Expected: sonner appears in `package.json` dependencies.

**Step 2: Create the wrapper component**

Create `resources/js/components/ui/sonner.tsx`:

```tsx
import { Toaster as Sonner } from 'sonner';

import { useAppearance } from '@/hooks/use-appearance';

export function Toaster() {
    const { resolvedAppearance } = useAppearance();

    return (
        <Sonner
            theme={resolvedAppearance}
            className="toaster group"
            toastOptions={{
                classNames: {
                    toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
                    description: 'group-[.toast]:text-muted-foreground',
                    actionButton: 'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
                    cancelButton: 'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
                },
            }}
        />
    );
}
```

**Step 3: Commit**

```bash
git add package.json package-lock.json resources/js/components/ui/sonner.tsx
git commit -m "feat: install sonner and add Toaster wrapper component"
```

---

### Task 2: Share flash data in the Inertia middleware

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/FlashSharingTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/FlashSharingTest.php`:

```php
<?php

use App\Models\Convention;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

it('shares flash success messages as inertia props', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    $response = $this->actingAs($owner)
        ->post(route('sections.setFull', $section))
        ->assertRedirect();

    // Follow the redirect to the sections.show page
    $this->actingAs($owner)
        ->get(route('sections.show', $section))
        ->assertInertia(fn ($page) => $page->has('flash.success'));
});

it('shares flash error messages as inertia props', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $convention = $structure['convention'];

    // Create 2 locked periods today to hit the max limit
    \App\Models\AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => true,
    ]);
    \App\Models\AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'afternoon',
        'locked' => true,
    ]);

    $this->actingAs($owner)
        ->post(route('attendance.start', $convention))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->has('flash.error'));
});
```

**Step 2: Run the test to verify it fails**

```bash
php artisan test --compact tests/Feature/FlashSharingTest.php
```

Expected: FAIL — `flash.success` and `flash.error` not in page props.

**Step 3: Add flash to HandleInertiaRequests::share()**

In `app/Http/Middleware/HandleInertiaRequests.php`, update the `share()` method:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'auth' => [
            'user' => $request->user(),
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        'appVersion' => $this->getAppVersion(),
        'flash' => [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
        ],
    ];
}
```

**Step 4: Run the test again — expect it still fails for the success case**

The `setFull` controller doesn't set a flash message yet. That's fine — the flash key will be present (with null value). The test checks `has('flash.success')` which is true because the key exists. Adjust the test to use `whereNotNull`:

Update the first test's assertion:
```php
->assertInertia(fn ($page) => $page->whereNotNull('flash.success'));
```

But this will still fail because `setFull` doesn't set flash yet. That test will be fully green after Task 3. For now just run the error test:

```bash
php artisan test --compact tests/Feature/FlashSharingTest.php --filter="shares flash error"
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/FlashSharingTest.php
git commit -m "feat: share flash success/error messages via Inertia middleware"
```

---

### Task 3: Add flash messages to SectionController

**Files:**
- Modify: `app/Http/Controllers/SectionController.php`

**Step 1: Add `->with('success', ...)` to updateOccupancy and setFull**

In `app/Http/Controllers/SectionController.php`, update both methods:

```php
public function updateOccupancy(UpdateOccupancyRequest $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
{
    $this->authorize('update', $section);

    $action->execute($section, $request->validated(), $request->user());

    return redirect()->back()->with('success', 'Occupancy updated.');
}

public function setFull(Request $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
{
    $this->authorize('update', $section);

    $action->execute($section, ['occupancy' => 100], $request->user());

    return redirect()->back()->with('success', 'Section marked as full.');
}
```

**Step 2: Run the failing test from Task 2 to verify both tests now pass**

```bash
php artisan test --compact tests/Feature/FlashSharingTest.php
```

Expected: 2 PASS.

**Step 3: Run the full test suite to ensure nothing regressed**

```bash
php artisan test --compact
```

Expected: all pass.

**Step 4: Commit**

```bash
git add app/Http/Controllers/SectionController.php
git commit -m "feat: add flash success messages to occupancy update endpoints"
```

---

### Task 4: Create the useFlashToast hook

**Files:**
- Create: `resources/js/hooks/use-flash-toast.ts`
- Test: `resources/js/hooks/__tests__/use-flash-toast.test.ts`

**Step 1: Write the failing test**

Create `resources/js/hooks/__tests__/use-flash-toast.test.ts`:

```ts
import { renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    usePage: vi.fn(),
}));

import { usePage } from '@inertiajs/react';
import { useFlashToast } from '@/hooks/use-flash-toast';

describe('useFlashToast', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('calls toast.success when flash.success is set', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: 'Occupancy updated.', error: null } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.success).toHaveBeenCalledWith('Occupancy updated.');
        expect(toast.error).not.toHaveBeenCalled();
    });

    it('calls toast.error when flash.error is set', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: null, error: 'Something went wrong.' } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.error).toHaveBeenCalledWith('Something went wrong.');
        expect(toast.success).not.toHaveBeenCalled();
    });

    it('does not call toast when flash is empty', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: null, error: null } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.success).not.toHaveBeenCalled();
        expect(toast.error).not.toHaveBeenCalled();
    });
});
```

**Step 2: Run the test to verify it fails**

```bash
npx vitest run resources/js/hooks/__tests__/use-flash-toast.test.ts
```

Expected: FAIL — `useFlashToast` not found.

**Step 3: Create the hook**

Create `resources/js/hooks/use-flash-toast.ts`:

```ts
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface FlashProps {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}

export function useFlashToast(): void {
    const { flash } = usePage<FlashProps>().props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
}
```

**Step 4: Run the test to verify it passes**

```bash
npx vitest run resources/js/hooks/__tests__/use-flash-toast.test.ts
```

Expected: 3 PASS.

**Step 5: Commit**

```bash
git add resources/js/hooks/use-flash-toast.ts resources/js/hooks/__tests__/use-flash-toast.test.ts
git commit -m "feat: add useFlashToast hook to fire sonner toasts from Inertia flash props"
```

---

### Task 5: Mount the Toaster in the app layout

**Files:**
- Modify: `resources/js/layouts/app/app-sidebar-layout.tsx`

**Step 1: Add the Toaster import and mount it**

In `resources/js/layouts/app/app-sidebar-layout.tsx`, add the import and render the `<Toaster>`:

```tsx
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { UpdateNotificationModal } from '@/components/update-notification-modal';
import { Toaster } from '@/components/ui/sonner';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <UpdateNotificationModal />
            <Toaster />
        </AppShell>
    );
}
```

**Step 2: Run TypeScript check**

```bash
npm run types:check
```

Expected: no errors.

**Step 3: Commit**

```bash
git add resources/js/layouts/app/app-sidebar-layout.tsx
git commit -m "feat: mount Sonner Toaster in app layout"
```

---

### Task 6: Wire useFlashToast into the sections show page

**Files:**
- Modify: `resources/js/pages/sections/show.tsx`

**Step 1: Add the import and hook call**

In `resources/js/pages/sections/show.tsx`, add the import at the top with the other hook imports:

```ts
import { useFlashToast } from '@/hooks/use-flash-toast';
```

Then add the hook call inside the `SectionsShow` component body, just after the existing hook calls (around line 38):

```ts
useFlashToast();
```

**Step 2: Run TypeScript check**

```bash
npm run types:check
```

Expected: no errors.

**Step 3: Run all JS tests**

```bash
npm test
```

Expected: all pass.

**Step 4: Run all PHP tests**

```bash
php artisan test --compact
```

Expected: all pass.

**Step 5: Commit**

```bash
git add resources/js/pages/sections/show.tsx
git commit -m "feat: show Sonner toasts for occupancy and attendance actions on section page"
```

---

### Task 7: Verify in browser

**Manual smoke test:**
1. Navigate to a section page (`/sections/{id}`)
2. Change the occupancy percentage — expect a green "Occupancy updated." toast
3. Click the FULL button — expect a green "Section marked as full." toast
4. Change the available seats number — expect a green "Occupancy updated." toast
5. If an active attendance period exists, submit an attendance count — expect a green "Attendance reported successfully." toast
6. Try submitting attendance when the period is locked (if testable) — expect a red error toast

No code changes in this task.
