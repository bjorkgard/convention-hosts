# Attendance Report Status Feedback — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** When a user submits attendance, show a meaningful toast with the count and period; also show a persistent in-form status indicating whether they've already reported for this period.

**Architecture:** Two backend changes (flash message + a new `myReport` Inertia prop) and one frontend change (status line + pre-fill + button label). No new models, no schema changes, no new routes.

**Tech Stack:** Laravel 12, Inertia.js, React 19, Pest, Sonner toasts via `useFlashToast`.

---

### Task 1: Enrich the attendance flash message

**Files:**
- Modify: `app/Http/Controllers/AttendanceController.php:76`

**Step 1: Open the file and find the success response in `report()`**

```php
// Line ~76 — current:
return redirect()->back()->with('success', 'Attendance reported successfully.');
```

**Step 2: Replace with the richer message**

```php
return redirect()->back()->with('success', "Attendance of {$request->validated('attendance')} reported for the {$attendancePeriod->period} period.");
```

**Step 3: Verify the existing feature test still passes**

```bash
php artisan test --compact tests/Feature/Integration/CompleteUserFlowsTest.php
```

Expected: all green (no test checks the exact flash string, so no updates needed).

**Step 4: Commit**

```bash
git add app/Http/Controllers/AttendanceController.php
git commit -m "feat: include attendance count and period in success flash message"
```

---

### Task 2: Pass `myReport` from `SectionController::show`

**Files:**
- Modify: `app/Http/Controllers/SectionController.php:46-66`
- Test: `tests/Feature/Section/SectionAuthorizationTest.php` (add a new `it()` block)

**Step 1: Write the failing test**

Open `tests/Feature/Section/SectionAuthorizationTest.php` and add at the end:

```php
it('passes myReport to the section show page when user has reported', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    // Create an active attendance period
    $period = AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    // Owner reports attendance
    AttendanceReport::create([
        'attendance_period_id' => $period->id,
        'section_id' => $section->id,
        'attendance' => 42,
        'reported_by' => $owner->id,
        'reported_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('sections.show', $section));

    $response->assertInertia(fn ($page) => $page
        ->component('sections/show')
        ->has('myReport')
        ->where('myReport.attendance', 42)
    );
});

it('passes myReport as null when user has not reported', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    $response = $this->actingAs($owner)->get(route('sections.show', $section));

    $response->assertInertia(fn ($page) => $page
        ->component('sections/show')
        ->where('myReport', null)
    );
});
```

Check what `use` statements the file already has; add any missing:
```php
use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
```

**Step 2: Run to confirm the tests fail**

```bash
php artisan test --compact tests/Feature/Section/SectionAuthorizationTest.php --filter="myReport"
```

Expected: FAIL — `myReport` key not present in Inertia props.

**Step 3: Implement `myReport` in `SectionController::show`**

Open `app/Http/Controllers/SectionController.php`. After the `$activePeriod` query, add:

```php
$myReport = $activePeriod
    ? $activePeriod->reports()->where('section_id', $section->id)->where('reported_by', $request->user()->id)->first()
    : null;
```

Then add `'myReport' => $myReport` to the `Inertia::render()` array:

```php
return Inertia::render('sections/show', [
    'section' => $section,
    'floor' => $section->floor,
    'convention' => $convention,
    'userRoles' => $userRoles,
    'activePeriod' => $activePeriod,
    'myReport' => $myReport,
]);
```

**Step 4: Run the tests**

```bash
php artisan test --compact tests/Feature/Section/SectionAuthorizationTest.php --filter="myReport"
```

Expected: both tests PASS.

**Step 5: Run the full test suite**

```bash
php artisan test --compact
```

Expected: all green.

**Step 6: Commit**

```bash
git add app/Http/Controllers/SectionController.php tests/Feature/Section/SectionAuthorizationTest.php
git commit -m "feat: pass myReport prop to section show page"
```

---

### Task 3: Add status indicator to the attendance form (frontend)

**Files:**
- Modify: `resources/js/pages/sections/show.tsx`

**Context:** The `AttendanceReport` type already exists in `resources/js/types/convention.ts`. No type file changes needed.

**Step 1: Add `myReport` to the page props interface**

In `sections/show.tsx`, find the `SectionsShowProps` interface and add the new prop:

```tsx
interface SectionsShowProps {
    section: Section;
    floor: Floor;
    convention: Convention;
    userRoles: string[];
    activePeriod: AttendancePeriod | null;
    myReport: import('@/types/convention').AttendanceReport | null;
}
```

Or use the existing import — `AttendanceReport` is already exported from `@/types/convention`. Add it to the existing type import line:

```tsx
import type { AttendancePeriod, AttendanceReport, Convention, Floor, Section } from '@/types/convention';
```

**Step 2: Destructure `myReport` from props**

```tsx
export default function SectionsShow({ section, floor, convention, activePeriod, myReport }: SectionsShowProps) {
```

**Step 3: Pre-fill the attendance input with existing value**

Change the `useState` initializer to use `myReport` if present:

```tsx
const [attendanceValue, setAttendanceValue] = useState(myReport ? String(myReport.attendance) : '');
```

**Step 4: Replace the attendance form JSX**

Find the `{activePeriod && (` block (around line 207) and replace the `<CardContent>` portion with:

```tsx
<CardContent>
    {/* Status line */}
    <div className="mb-3 flex items-center gap-1.5 text-sm">
        {myReport ? (
            <>
                <CheckCircle2 className="size-4 text-green-500" />
                <span className="text-green-700 dark:text-green-400">
                    Reported: {myReport.attendance}
                </span>
            </>
        ) : (
            <>
                <Circle className="text-muted-foreground size-4" />
                <span className="text-muted-foreground">Not yet reported</span>
            </>
        )}
    </div>
    <form onSubmit={handleAttendanceSubmit} className="space-y-2">
        <Label htmlFor="attendance-input">
            Attendance ({activePeriod.period})
        </Label>
        <div className="flex items-center gap-2">
            <Input
                id="attendance-input"
                type="number"
                min={0}
                placeholder="Enter attendance count"
                value={attendanceValue}
                onChange={(e) => setAttendanceValue(e.target.value)}
                className="flex-1"
            />
            <Button type="submit" className="cursor-pointer gap-1.5">
                <Send className="size-4" />
                {myReport ? 'Update' : 'Send'}
            </Button>
        </div>
    </form>
</CardContent>
```

**Step 5: Add the new icon imports**

`CheckCircle2` and `Circle` are from `lucide-react`. Add them to the existing lucide import line:

```tsx
import { Accessibility, ArrowLeft, CheckCircle2, Circle, Clock, Heart, Send, Trash2, Users } from 'lucide-react';
```

**Step 6: Type-check and lint**

```bash
npm run types:check && npm run lint
```

Fix any issues before moving on.

**Step 7: Manual smoke test**

```bash
composer dev
```

- Navigate to a section page with an active attendance period.
- Confirm "Not yet reported" state (muted circle + "Send" button).
- Submit a number → toast says "Attendance of X reported for the Y period."
- Page reloads → status line shows green checkmark + "Reported: X", input pre-filled, button says "Update".
- Update the number → toast confirms the new value.

**Step 8: Commit**

```bash
git add resources/js/pages/sections/show.tsx
git commit -m "feat: show attendance report status and pre-fill in section form"
```

---

## Done

All three commits are on `attendance-fix`. When ready, open a PR against `main`.
