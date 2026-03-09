# Attendance Report Status Feedback — Design

**Date:** 2026-03-09
**Branch:** attendance-fix

## Goal

When a user submits an attendance number, they should receive:
1. A meaningful toast confirming what they reported and for which period.
2. A persistent visual status in the form showing whether they have already reported for this period.

## Changes

### 1. Backend — Enrich flash message (`AttendanceController::report`)

Replace the generic success flash with one that includes the submitted count and period name:

```
"Attendance of {attendance} reported for the {period} period."
```

Example: `"Attendance of 47 reported for the morning period."`

No frontend changes required for the toast — `useFlashToast` handles it automatically.

### 2. Backend — Pass existing report to view (`SectionController::show`)

Add a `myReport` prop to the Inertia response. It is the `AttendanceReport` record for the current user's section + active period, or `null` if none exists yet.

Query runs only when `$activePeriod` is non-null (no active period = no report to look up).

### 3. Frontend — Attendance form status indicator (`sections/show.tsx`)

Use `myReport` (new prop) to:

- Show a status line above the input:
  - Reported: green checkmark + `"Reported: {count}"` (the previously submitted value)
  - Not reported: muted circle + `"Not yet reported"`
- Pre-fill the input with `myReport.attendance` when a report exists.
- Change the submit button label from **Send** to **Update** when a report exists.

#### Reported state
```
Attendance (morning)
✓ Reported: 47
[  47  ] [Update]
```

#### Unreported state
```
Attendance (morning)
○ Not yet reported
[      ] [Send]
```

## Files Affected

- `app/Http/Controllers/AttendanceController.php` — flash message
- `app/Http/Controllers/SectionController.php` — add `myReport` prop
- `resources/js/pages/sections/show.tsx` — status indicator + pre-fill + button label
- `resources/js/types/convention.ts` — add `AttendanceReport` type if not present
